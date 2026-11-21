<?php

declare(strict_types=1);

// Read-only client for the e-Certificate app's public packages endpoint.
// The app is the only authority for prices: these values are for display, and
// the checkout API charges the package row whatever this site shows.
if (!defined('ECERT_PAGE') && !defined('ECERT_API')) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../api/plan-catalog.php';

const ECERT_PACKAGES_FRESH_SECONDS = 120;
const ECERT_PACKAGES_STALE_SECONDS = 86400;
const ECERT_PACKAGES_FAIL_SECONDS = 60;
const ECERT_PACKAGES_MAX_BYTES = 65536;

/**
 * Origin of the app (same rule as verify.php): only a bare scheme://host[:port]
 * from ECERT_APP_URL is accepted, anything else falls back to production.
 */
function ecert_app_url(): string
{
    $url = 'https://apps.e-certificate.com.my';
    $configured = getenv('ECERT_APP_URL');
    if (is_string($configured)) {
        $configured = preg_replace('#/$#', '', trim($configured));
        if (is_string($configured) && preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?$#', $configured) === 1) {
            $url = $configured;
        }
    }

    return $url;
}

function ecert_format_rm(int $cents): string
{
    return $cents % 100 === 0
        ? 'RM' . intdiv($cents, 100)
        : sprintf('RM%d.%02d', intdiv($cents, 100), $cents % 100);
}

function ecert_app_cache_dir(): ?string
{
    $directory = getenv('ECERT_CACHE_DIR');
    if (!is_string($directory) || $directory === '') {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ecert-web-cache';
    }
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        return null;
    }

    return is_writable($directory) ? $directory : null;
}

function ecert_app_cache_write(string $file, string $contents): void
{
    $temporary = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
    if (@file_put_contents($temporary, $contents, LOCK_EX) === false || !@rename($temporary, $file)) {
        @unlink($temporary);
    }
}

function ecert_app_fetch(string $url): ?string
{
    if (function_exists('curl_init')) {
        $body = '';
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body): int {
                $body .= $chunk;
                return strlen($body) > ECERT_PACKAGES_MAX_BYTES ? 0 : strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return $ok !== false && $status === 200 ? $body : null;
    }

    $context = stream_context_create([
        'http' => ['method' => 'GET', 'header' => "Accept: application/json\r\n", 'timeout' => 3, 'follow_location' => 0, 'ignore_errors' => true],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents($url, false, $context, 0, ECERT_PACKAGES_MAX_BYTES + 1);
    $statusLine = (string) ($http_response_header[0] ?? '');
    if (!is_string($body) || strlen($body) > ECERT_PACKAGES_MAX_BYTES || preg_match('#^HTTP/\S+\s+200\b#', $statusLine) !== 1) {
        return null;
    }

    return $body;
}

/**
 * Keeps only well-formed entries; null when nothing valid is left.
 *
 * @return array{checkout_enabled: bool, government_domains: list<string>, packages: list<array{code: string, name: string, category: string, duration: int, price_cents: int}>}|null
 */
function ecert_packages_validate(mixed $data): ?array
{
    if (!is_array($data) || !is_array($data['packages'] ?? null)) {
        return null;
    }

    $packages = [];
    foreach ($data['packages'] as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $code = $entry['code'] ?? null;
        $category = $entry['category'] ?? null;
        $duration = $entry['duration_months'] ?? ($entry['duration'] ?? null);
        $price = $entry['price_cents'] ?? null;
        $name = $entry['name'] ?? null;
        if (!is_string($code) || preg_match('/^(normal|government)-(01|03|06|12)m$/D', $code, $match) !== 1
            || $category !== $match[1] || !is_int($duration) || $duration !== (int) $match[2]
            || !is_int($price) || $price < 100 || $price > 10000000
            || !is_string($name) || mb_strlen($name) < 1 || mb_strlen($name) > 100) {
            continue;
        }
        $packages[] = ['code' => $code, 'name' => $name, 'category' => $category, 'duration' => $duration, 'price_cents' => $price];
    }

    if ($packages === []) {
        return null;
    }

    $domains = [];
    foreach ((array) ($data['government_email_domains'] ?? ($data['government_domains'] ?? [])) as $domain) {
        if (is_string($domain) && preg_match('/^[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?$/D', $domain) === 1) {
            $domains[] = $domain;
        }
    }

    return [
        'checkout_enabled' => ($data['checkout_enabled'] ?? false) === true,
        'government_domains' => $domains !== [] ? $domains : ['gov.my'],
        'packages' => $packages,
    ];
}

/**
 * @return array{source: string, checkout_enabled: bool, government_domains: list<string>, packages: list<array{code: string, name: string, category: string, duration: int, price_cents: int}>}
 */
function ecert_packages(): array
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }

    $directory = ecert_app_cache_dir();
    $cacheFile = $directory !== null ? $directory . DIRECTORY_SEPARATOR . 'packages.json' : null;
    $failFile = $directory !== null ? $directory . DIRECTORY_SEPARATOR . 'packages.fail' : null;
    $now = time();
    $cached = null;
    $cachedAge = null;

    if ($cacheFile !== null && is_file($cacheFile)) {
        $stored = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($stored) && is_int($stored['fetched_at'] ?? null)) {
            $cached = ecert_packages_validate($stored['data'] ?? null);
            $cachedAge = $now - $stored['fetched_at'];
        }
    }

    if ($cached !== null && $cachedAge >= 0 && $cachedAge <= ECERT_PACKAGES_FRESH_SECONDS) {
        return $memo = ['source' => 'live'] + $cached;
    }

    $recentFailure = $failFile !== null && is_file($failFile) && $now - (int) @filemtime($failFile) < ECERT_PACKAGES_FAIL_SECONDS;
    if (!$recentFailure) {
        $body = ecert_app_fetch(ecert_app_url() . '/api/public/v1/packages');
        $live = $body !== null ? ecert_packages_validate(json_decode($body, true)) : null;
        if ($live !== null) {
            if ($cacheFile !== null) {
                ecert_app_cache_write($cacheFile, (string) json_encode(['fetched_at' => $now, 'data' => [
                    'checkout_enabled' => $live['checkout_enabled'],
                    'government_email_domains' => $live['government_domains'],
                    'packages' => array_map(static fn (array $p): array => [
                        'code' => $p['code'], 'name' => $p['name'], 'category' => $p['category'],
                        'duration_months' => $p['duration'], 'price_cents' => $p['price_cents'],
                    ], $live['packages']),
                ]], JSON_UNESCAPED_SLASHES));
                if ($failFile !== null && is_file($failFile)) {
                    @unlink($failFile);
                }
            }

            return $memo = ['source' => 'live'] + $live;
        }
        if ($failFile !== null) {
            @touch($failFile);
        }
    }

    if ($cached !== null && $cachedAge >= 0 && $cachedAge <= ECERT_PACKAGES_STALE_SECONDS) {
        return $memo = ['source' => 'stale', 'checkout_enabled' => false] + $cached;
    }

    $packages = [];
    foreach (ecert_plan_catalog() as $plan) {
        $packages[] = [
            'code' => $plan['id'],
            'name' => ucfirst($plan['type']) . ' ' . ($plan['duration'] === 12 ? '1 Year' : $plan['duration'] . ($plan['duration'] === 1 ? ' Month' : ' Months')),
            'category' => $plan['type'],
            'duration' => $plan['duration'],
            'price_cents' => $plan['price'] * 100,
        ];
    }

    return $memo = ['source' => 'fallback', 'checkout_enabled' => false, 'government_domains' => ['gov.my'], 'packages' => $packages];
}

/**
 * @return array{id: string, type: string, duration: int, price: int|float, price_cents: int, price_display: string}|null
 */
function ecert_plan_from_packages(string $type, int $duration): ?array
{
    $code = ecert_plan_id($type, $duration);
    foreach (ecert_packages()['packages'] as $package) {
        if ($package['code'] !== $code) {
            continue;
        }
        $cents = $package['price_cents'];

        return [
            'id' => $code,
            'type' => $type,
            'duration' => $duration,
            'price' => $cents % 100 === 0 ? intdiv($cents, 100) : $cents / 100,
            'price_cents' => $cents,
            'price_display' => ecert_format_rm($cents),
        ];
    }

    return null;
}
