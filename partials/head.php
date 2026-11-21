<?php

declare(strict_types=1);

if (!defined('ECERT_PAGE')) {
    http_response_code(404);
    exit;
}

$ecertEscape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$ecertTitle = (string) ($ecertPage['title'] ?? 'e-Certificate');
$ecertDescription = (string) ($ecertPage['description'] ?? '');
$ecertOpenGraph = is_array($ecertPage['og'] ?? null) ? $ecertPage['og'] : null;
$ecertPath = (string) ($ecertPage['path'] ?? '/');
$ecertCanonical = 'https://www.e-certificate.com.my' . $ecertPath;
$ecertScripts = array_merge(['/assets/js/main.js'], array_map('strval', (array) ($ecertPage['scripts'] ?? [])));
$ecertBodyClass = (string) ($ecertPage['bodyClass'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#07162c">
  <meta name="description" content="<?= $ecertEscape($ecertDescription) ?>">
  <link rel="canonical" href="<?= $ecertEscape($ecertCanonical) ?>">
  <meta property="og:url" content="<?= $ecertEscape($ecertCanonical) ?>">
<?php if ($ecertOpenGraph !== null): ?>
  <meta property="og:title" content="<?= $ecertEscape((string) ($ecertOpenGraph['title'] ?? $ecertTitle)) ?>">
  <meta property="og:description" content="<?= $ecertEscape((string) ($ecertOpenGraph['description'] ?? $ecertDescription)) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="en_MY">
<?php endif; ?>
  <title><?= $ecertEscape($ecertTitle) ?></title>
  <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/styles.css">
<?php foreach ($ecertScripts as $ecertScript): ?>
  <script src="<?= $ecertEscape($ecertScript) ?>" defer></script>
<?php endforeach; ?>
</head>
<body<?php if ($ecertBodyClass !== ''): ?> class="<?= $ecertEscape($ecertBodyClass) ?>"<?php endif; ?>>
  <a class="skip-link" href="#main-content">Skip to main content</a>

