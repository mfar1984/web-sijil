<?php

declare(strict_types=1);

// Reader for the private settings file outside every document root. The pages
// use it for the optional Search Console token; api/contact.php uses it for the
// SMTP credential, the allowed origins and the rate directory. It only reads a
// file the hosting account owns and never prints any value it loads.
if (!defined('ECERT_PAGE') && !defined('ECERT_API')) {
    http_response_code(404);
    exit;
}

function ecert_path_is_inside(string $path, string $parent): bool
{
    $path = rtrim(str_replace('\\', '/', strtolower($path)), '/') . '/';
    $parent = rtrim(str_replace('\\', '/', strtolower($parent)), '/') . '/';
    return str_starts_with($path, $parent);
}

// Private settings file outside the document root. ECERT_CONTACT_CONFIG wins;
// otherwise <account home>/private/e-certificate-web.php, which on cPanel is
// /home/kflegacy/private/e-certificate-web.php (partials/ -> site root -> home).
function ecert_private_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }
    $config = [];

    $configPath = getenv('ECERT_CONTACT_CONFIG');
    if (!is_string($configPath) || $configPath === '') {
        $configPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'e-certificate-web.php';
    }
    $resolvedPath = realpath($configPath);
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($resolvedPath === false || !is_file($resolvedPath) || !is_readable($resolvedPath)
        || ($documentRoot !== false && ecert_path_is_inside($resolvedPath, $documentRoot))) {
        return $config;
    }

    try {
        $loaded = (static function (string $path): mixed {
            return require $path;
        })($resolvedPath);
    } catch (Throwable) {
        return $config;
    }
    if (is_array($loaded)) {
        $config = $loaded;
    }
    return $config;
}

/**
 * Optional Google Search Console token: ECERT_GOOGLE_SITE_VERIFICATION first,
 * then google_site_verification in the private settings file. Returns null when
 * the owner has not set one, or when the value is not a bare Google token.
 */
function ecert_google_site_verification(): ?string
{
    $token = getenv('ECERT_GOOGLE_SITE_VERIFICATION');
    if (!is_string($token) || trim($token) === '') {
        $token = ecert_private_config()['google_site_verification'] ?? null;
    }
    if (!is_string($token)) {
        return null;
    }
    $token = trim($token);

    return preg_match('/^[A-Za-z0-9_-]{16,128}$/D', $token) === 1 ? $token : null;
}
