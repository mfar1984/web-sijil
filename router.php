<?php

declare(strict_types=1);

// Router for PHP's built-in development server only:
//   php -S 127.0.0.1:4173 -t f:\Programming\certificate\web2 f:\Programming\certificate\web2\router.php
// It mirrors .htaccess in the same order so local previews behave like
// Apache/LiteSpeed on cPanel. Production never serves this file (.htaccess
// denies it), and it refuses to run under any other SAPI.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$ecertRequestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$ecertQueryStart = strpos($ecertRequestUri, '?');
$ecertRawPath = $ecertQueryStart === false ? $ecertRequestUri : substr($ecertRequestUri, 0, $ecertQueryStart);
$ecertRawQuery = $ecertQueryStart === false ? '' : substr($ecertRequestUri, $ecertQueryStart + 1);
$ecertPath = rawurldecode($ecertRawPath);

// Pages run in their own scope so the router's variables never leak into them.
$ecertServe = static function (string $file): void {
    require $file;
};

$ecertStatus = static function (int $code, string $message): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message, "\n";
};

$ecertRedirect = static function (string $target) use ($ecertRawQuery): void {
    // Apache appends the original query string because the substitutions have
    // no "?"; do the same here with the raw, still-encoded query.
    http_response_code(301);
    header('Location: ' . $target . ($ecertRawQuery !== '' ? '?' . $ecertRawQuery : ''));
};

if ($ecertPath === '' || $ecertPath[0] !== '/' || str_contains($ecertPath, '..') || str_contains($ecertPath, "\0")) {
    $ecertStatus(404, 'Not Found');
    return true;
}

// Path relative to the document root, like the per-directory RewriteRule subject.
$ecertRelative = substr($ecertPath, 1);

// Deny non-public directories (403), including the bare directory name.
if (preg_match('#^(?:tests|vendor|config|partials|previews)(?:/|$)#i', $ecertRelative) === 1) {
    $ecertStatus(403, 'Forbidden');
    return true;
}

// Deny project and environment files at the site root (403), including this router.
if (preg_match('#^(?:README\.md|composer\.(?:json|lock)|\.env.*|\.gitignore|\.htaccess|router\.php)$#i', $ecertRelative) === 1) {
    $ecertStatus(403, 'Forbidden');
    return true;
}

// FilesMatch layer: the same private file names in any directory.
if (preg_match('#^(?:README\.md|composer\.(?:json|lock)|\.env.*|\.gitignore|\.htaccess)$#i', basename($ecertPath)) === 1) {
    $ecertStatus(403, 'Forbidden');
    return true;
}

// Apex domain to www. Never matches 127.0.0.1 or localhost.
$ecertHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
if (preg_match('/^e-certificate\.com\.my$/i', $ecertHost) === 1) {
    http_response_code(301);
    header('Location: https://www.e-certificate.com.my' . $ecertRequestUri);
    return true;
}

// The four 301 groups. The router only sees the original request, so there is
// no internal-rewrite loop to guard against (THE_REQUEST in .htaccess).
if (preg_match('#^index(?:\.php|\.html)?/?$#', $ecertRelative) === 1) {
    $ecertRedirect('/');
    return true;
}

if (preg_match('#^(features|pricing|contact|verify)\.php$#', $ecertRelative, $ecertMatch) === 1) {
    $ecertRedirect('/' . $ecertMatch[1]);
    return true;
}

if (preg_match('#^(features|pricing|contact)\.html$#', $ecertRelative, $ecertMatch) === 1) {
    $ecertRedirect('/' . $ecertMatch[1]);
    return true;
}

if (preg_match('#^(features|pricing|contact|verify)/$#', $ecertRelative, $ecertMatch) === 1) {
    $ecertRedirect('/' . $ecertMatch[1]);
    return true;
}

// DirectoryIndex index.php.
if ($ecertRelative === '') {
    $ecertServe(__DIR__ . '/index.php');
    return true;
}

// Clean URL: serve /<page> from <page>.php when the file exists.
if (preg_match('#^(features|pricing|contact|verify)$#', $ecertRelative, $ecertMatch) === 1
    && is_file(__DIR__ . '/' . $ecertMatch[1] . '.php')) {
    $ecertServe(__DIR__ . '/' . $ecertMatch[1] . '.php');
    return true;
}

$ecertFile = __DIR__ . '/' . $ecertRelative;

// Existing file: let the built-in server serve it (or run it, for /api/*.php).
if (is_file($ecertFile)) {
    return false;
}

// Existing directory: no listings, mirroring Options -Indexes.
if (is_dir($ecertFile)) {
    $ecertStatus(403, 'Forbidden');
    return true;
}

$ecertStatus(404, 'Not Found');
return true;
