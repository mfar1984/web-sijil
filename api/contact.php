<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

const ECERT_MAX_REQUEST_BYTES = 16384;
const ECERT_RATE_WINDOW_SECONDS = 900;
const ECERT_RATE_MAX_ATTEMPTS = 5;
const ECERT_RATE_MAX_CLIENTS = 1000;
const ECERT_SMTP_HOST = 'indigo.herosite.pro';
const ECERT_SMTP_USER = 'support@e-certificate.com.my';
const ECERT_MAILBOX = 'support@e-certificate.com.my';

ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

function ecert_contact_respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function ecert_contact_fail(int $status, string $message): never
{
    ecert_contact_respond($status, ['success' => false, 'message' => $message]);
}

function ecert_normalize_origin(string $origin): ?string
{
    $parts = parse_url($origin);
    if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
        return null;
    }
    if (isset($parts['user']) || isset($parts['pass']) || isset($parts['path']) || isset($parts['query']) || isset($parts['fragment'])) {
        return null;
    }

    $normalized = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
    if (isset($parts['port'])) {
        $normalized .= ':' . $parts['port'];
    }
    return $normalized;
}

function ecert_expected_origin(): ?string
{
    $configured = getenv('ECERT_ALLOWED_ORIGIN');
    if (is_string($configured) && $configured !== '') {
        return ecert_normalize_origin($configured);
    }

    $serverAddress = $_SERVER['SERVER_ADDR'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!in_array($serverAddress, ['127.0.0.1', '::1'], true)
        || !preg_match('/^(?:127\.0\.0\.1|localhost)(?::\d{1,5})?$/D', $host)) {
        return null;
    }

    return 'http://' . strtolower($host);
}

function ecert_parse_form_body(string $body): ?array
{
    $values = [];
    if ($body === '') {
        return $values;
    }

    foreach (explode('&', $body) as $pair) {
        if ($pair === '') {
            continue;
        }
        [$encodedKey, $encodedValue] = array_pad(explode('=', $pair, 2), 2, '');
        if (preg_match('/%(?![0-9A-Fa-f]{2})/', $encodedKey . $encodedValue)) {
            return null;
        }
        $key = urldecode($encodedKey);
        $value = urldecode($encodedValue);
        if ($key === '' || str_contains($key, '[') || str_contains($key, ']') || array_key_exists($key, $values)) {
            return null;
        }
        if (preg_match('//u', $key) !== 1 || preg_match('//u', $value) !== 1) {
            return null;
        }
        $values[$key] = $value;
    }

    return $values;
}

function ecert_text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function ecert_single_line(string $value): bool
{
    return preg_match('/[\x00-\x1F\x7F]/u', $value) !== 1;
}

function ecert_validate_contact(array $input): array
{
    $allowedFields = [
        'full_name', 'email', 'organisation', 'phone', 'enquiry_type',
        'type', 'duration', 'message', 'consent', 'website',
    ];
    foreach (array_keys($input) as $field) {
        if (!in_array($field, $allowedFields, true)) {
            return [[], ['form' => 'The submitted form contains an unsupported field.']];
        }
    }

    $values = [];
    foreach ($allowedFields as $field) {
        $values[$field] = trim((string) ($input[$field] ?? ''));
    }

    if ($values['website'] !== '') {
        return [[], ['form' => 'The enquiry could not be accepted.']];
    }

    $errors = [];
    if (ecert_text_length($values['full_name']) < 2 || ecert_text_length($values['full_name']) > 120 || !ecert_single_line($values['full_name'])) {
        $errors['full_name'] = 'Enter your full name using 2 to 120 characters.';
    }
    if (ecert_text_length($values['email']) > 254 || !ecert_single_line($values['email']) || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid work email address.';
    }
    if (ecert_text_length($values['organisation']) < 2 || ecert_text_length($values['organisation']) > 160 || !ecert_single_line($values['organisation'])) {
        $errors['organisation'] = 'Enter your organisation using 2 to 160 characters.';
    }
    if ($values['phone'] !== '' && (ecert_text_length($values['phone']) > 30 || !ecert_single_line($values['phone']) || preg_match('/^[0-9+().\-\s]+$/u', $values['phone']) !== 1)) {
        $errors['phone'] = 'Enter a valid phone number using up to 30 characters.';
    }

    $enquiryLabels = [
        'sales' => 'Sales enquiry',
        'plan' => 'Plan enquiry',
        'product-support' => 'Product support',
        'verification' => 'Certificate verification',
        'other' => 'Other enquiry',
    ];
    if (!array_key_exists($values['enquiry_type'], $enquiryLabels)) {
        $errors['enquiry_type'] = 'Select a valid enquiry type.';
    }

    $message = str_replace(["\r\n", "\r"], "\n", $values['message']);
    if (ecert_text_length($message) < 20 || ecert_text_length($message) > 3000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $message) === 1) {
        $errors['message'] = 'Enter a message using 20 to 3,000 characters.';
    }
    $values['message'] = $message;

    if ($values['consent'] !== '1') {
        $errors['consent'] = 'Confirm your consent so we can respond to your enquiry.';
    }

    $plan = null;
    if ($values['type'] !== '' || $values['duration'] !== '') {
        require_once __DIR__ . '/plan-catalog.php';
        $plan = ecert_resolve_plan(['type' => $values['type'], 'duration' => $values['duration']]);
        if ($plan === null) {
            $errors['type'] = 'Select a valid plan category.';
            $errors['duration'] = 'Select a valid plan duration.';
        }
    }

    if ($errors !== []) {
        return [[], $errors];
    }

    $values['enquiry_label'] = $enquiryLabels[$values['enquiry_type']];
    $values['plan'] = $plan;
    unset($values['website'], $values['consent'], $values['type'], $values['duration']);
    return [$values, []];
}

function ecert_path_is_inside(string $path, string $parent): bool
{
    $path = rtrim(str_replace('\\', '/', strtolower($path)), '/') . '/';
    $parent = rtrim(str_replace('\\', '/', strtolower($parent)), '/') . '/';
    return str_starts_with($path, $parent);
}

function ecert_rate_directory(): ?string
{
    $directory = getenv('ECERT_RATE_LIMIT_DIR');
    if (!is_string($directory) || $directory === '') {
        $serverAddress = $_SERVER['SERVER_ADDR'] ?? '';
        if (!in_array($serverAddress, ['127.0.0.1', '::1'], true)) {
            return null;
        }
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ecert-contact-rate-limit';
    }

    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        return null;
    }
    $resolvedDirectory = realpath($directory);
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($resolvedDirectory === false || !is_writable($resolvedDirectory)) {
        return null;
    }
    if ($documentRoot !== false && ecert_path_is_inside($resolvedDirectory, $documentRoot)) {
        return null;
    }
    return $resolvedDirectory;
}

function ecert_rate_limit(string $directory, string $remoteAddress): ?bool
{
    $handle = @fopen($directory . DIRECTORY_SEPARATOR . 'contact-rate.json', 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return null;
    }

    try {
        $size = fstat($handle)['size'] ?? 0;
        $raw = $size > 0 && $size <= 1048576 ? fread($handle, $size) : '';
        $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($state)) {
            $state = [];
        }

        $now = time();
        $cutoff = $now - ECERT_RATE_WINDOW_SECONDS;
        foreach ($state as $hash => $timestamps) {
            if (!is_string($hash) || !is_array($timestamps)) {
                unset($state[$hash]);
                continue;
            }
            $timestamps = array_values(array_filter(
                $timestamps,
                static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $cutoff && $timestamp <= $now
            ));
            if ($timestamps === []) {
                unset($state[$hash]);
            } else {
                $state[$hash] = array_slice($timestamps, -ECERT_RATE_MAX_ATTEMPTS);
            }
        }

        $clientHash = hash('sha256', $remoteAddress);
        if (count($state) >= ECERT_RATE_MAX_CLIENTS) {
            uasort($state, static fn (array $a, array $b): int => end($b) <=> end($a));
            $maximumExistingClients = array_key_exists($clientHash, $state)
                ? ECERT_RATE_MAX_CLIENTS
                : ECERT_RATE_MAX_CLIENTS - 1;
            $state = array_slice($state, 0, $maximumExistingClients, true);
        }

        $attempts = $state[$clientHash] ?? [];
        $allowed = count($attempts) < ECERT_RATE_MAX_ATTEMPTS;
        if ($allowed) {
            $attempts[] = $now;
            $state[$clientHash] = $attempts;
        }

        $encoded = json_encode($state, JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || ftruncate($handle, 0) === false || rewind($handle) === false) {
            return null;
        }
        $encodedLength = strlen($encoded);
        $written = fwrite($handle, $encoded);
        if (!is_int($written) || $written !== $encodedLength || fflush($handle) === false) {
            return null;
        }
        return $allowed;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function ecert_load_smtp_password(): ?string
{
    $password = getenv('ECERT_SMTP_PASSWORD');
    if (is_string($password) && $password !== '' && $password !== '<SMTP_PASSWORD>') {
        return $password;
    }

    $configPath = getenv('ECERT_CONTACT_CONFIG');
    if (!is_string($configPath) || $configPath === '') {
        return null;
    }
    $resolvedPath = realpath($configPath);
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($resolvedPath === false || !is_file($resolvedPath) || !is_readable($resolvedPath)
        || ($documentRoot !== false && ecert_path_is_inside($resolvedPath, $documentRoot))) {
        return null;
    }

    $config = (static function (string $path): mixed {
        return require $path;
    })($resolvedPath);
    if (!is_array($config)) {
        return null;
    }
    $password = $config['smtp_password'] ?? null;
    return is_string($password) && $password !== '' && $password !== '<SMTP_PASSWORD>' ? $password : null;
}

function ecert_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    ecert_contact_fail(405, 'Method not allowed.');
}

$contentType = trim((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
if (preg_match('#^application/x-www-form-urlencoded(?:\s*;\s*charset=UTF-8)?$#i', $contentType) !== 1) {
    ecert_contact_fail(422, 'The submitted form could not be accepted.');
}

$contentLength = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT);
if ($contentLength !== false && $contentLength > ECERT_MAX_REQUEST_BYTES) {
    ecert_contact_fail(422, 'The submitted form is too large.');
}

$expectedOrigin = ecert_expected_origin();
$requestOrigin = ecert_normalize_origin((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($expectedOrigin === null) {
    ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
}
if ($requestOrigin === null || !hash_equals($expectedOrigin, $requestOrigin)) {
    ecert_contact_fail(422, 'The submitted form could not be accepted.');
}

$rawBody = file_get_contents('php://input', false, null, 0, ECERT_MAX_REQUEST_BYTES + 1);
if (!is_string($rawBody) || strlen($rawBody) > ECERT_MAX_REQUEST_BYTES) {
    ecert_contact_fail(422, 'The submitted form is too large.');
}
$input = ecert_parse_form_body($rawBody);
if ($input === null) {
    ecert_contact_fail(422, 'The submitted form could not be accepted.');
}

[$contact, $errors] = ecert_validate_contact($input);
if ($errors !== []) {
    ecert_contact_respond(422, [
        'success' => false,
        'message' => 'Please check the highlighted fields.',
        'errors' => $errors,
    ]);
}

$rateDirectory = ecert_rate_directory();
$remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if ($rateDirectory === null || $remoteAddress === '') {
    ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
}
$rateAllowed = ecert_rate_limit($rateDirectory, $remoteAddress);
if ($rateAllowed === null) {
    ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
}
if (!$rateAllowed) {
    header('Retry-After: ' . ECERT_RATE_WINDOW_SECONDS);
    ecert_contact_fail(429, 'Too many enquiries were submitted. Please try again later.');
}

try {
    $password = ecert_load_smtp_password();
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if ($password === null || !is_file($autoload)) {
        ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
    }
    require $autoload;
} catch (Throwable) {
    ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
}

$mode = strtolower((string) (getenv('ECERT_SMTP_MODE') ?: 'starttls'));
if (!in_array($mode, ['starttls', 'smtps'], true)) {
    ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
}

$planText = 'Not selected';
$planHtml = 'Not selected';
if (is_array($contact['plan'])) {
    $plan = $contact['plan'];
    $category = $plan['type'] === 'government' ? 'Government' : 'Normal';
    $duration = $plan['duration'] === 12 ? '1 year' : $plan['duration'] . ' ' . ($plan['duration'] === 1 ? 'month' : 'months');
    $planText = sprintf('%s (%s), RM%d [%s]', $category, $duration, $plan['price'], $plan['id']);
    $planHtml = ecert_escape($planText);
}
$phoneText = $contact['phone'] !== '' ? $contact['phone'] : 'Not provided';

$textBody = "A new e-Certificate website enquiry was submitted.\n\n"
    . "Name: {$contact['full_name']}\n"
    . "Work email: {$contact['email']}\n"
    . "Organisation: {$contact['organisation']}\n"
    . "Phone: {$phoneText}\n"
    . "Enquiry type: {$contact['enquiry_label']}\n"
    . "Plan: {$planText}\n\n"
    . "Message:\n{$contact['message']}\n";
$htmlBody = '<h1>New e-Certificate website enquiry</h1>'
    . '<dl>'
    . '<dt>Name</dt><dd>' . ecert_escape($contact['full_name']) . '</dd>'
    . '<dt>Work email</dt><dd>' . ecert_escape($contact['email']) . '</dd>'
    . '<dt>Organisation</dt><dd>' . ecert_escape($contact['organisation']) . '</dd>'
    . '<dt>Phone</dt><dd>' . ecert_escape($phoneText) . '</dd>'
    . '<dt>Enquiry type</dt><dd>' . ecert_escape($contact['enquiry_label']) . '</dd>'
    . '<dt>Plan</dt><dd>' . $planHtml . '</dd>'
    . '</dl><h2>Message</h2><p>' . nl2br(ecert_escape($contact['message']), false) . '</p>';

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host = ECERT_SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = ECERT_SMTP_USER;
    $mail->Password = $password;
    $mail->Port = $mode === 'smtps' ? 465 : 587;
    $mail->SMTPSecure = $mode === 'smtps' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAutoTLS = true;
    $mail->Timeout = 10;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(ECERT_SMTP_USER, 'e-Certificate Support');
    $mail->addAddress(ECERT_MAILBOX, 'e-Certificate Support');
    $mail->addReplyTo($contact['email'], $contact['full_name']);
    $mail->Subject = '[e-Certificate enquiry] ' . $contact['enquiry_label'];
    $mail->isHTML(true);
    $mail->Body = $htmlBody;
    $mail->AltBody = $textBody;

    if ($mail->send() !== true) {
        ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
    }
} catch (Throwable) {
    ecert_contact_fail(503, 'The enquiry service is temporarily unavailable.');
}

ecert_contact_respond(200, [
    'success' => true,
    'message' => 'Your enquiry was sent.',
]);
