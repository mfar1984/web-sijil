<?php

declare(strict_types=1);

if (!defined('ECERT_PAGE')) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/private-config.php';

$ecertEscape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$ecertSiteUrl = 'https://www.e-certificate.com.my';
$ecertSiteName = 'e-Certificate';
$ecertTitle = (string) ($ecertPage['title'] ?? 'e-Certificate');
$ecertDescription = (string) ($ecertPage['description'] ?? '');
$ecertOpenGraph = is_array($ecertPage['og'] ?? null) ? $ecertPage['og'] : null;
$ecertOgTitle = (string) ($ecertOpenGraph['title'] ?? $ecertTitle);
$ecertOgDescription = (string) ($ecertOpenGraph['description'] ?? $ecertDescription);
$ecertPath = (string) ($ecertPage['path'] ?? '/');
$ecertCanonical = $ecertSiteUrl . $ecertPath;
// Share image: the committed 1200x630 cover unless a page sets its own. A page
// value may be a root-absolute path or a full https URL; og:image needs the
// absolute form.
$ecertOgImage = (string) ($ecertPage['ogImage'] ?? '/assets/images/og-cover.png');
$ecertOgImageUrl = preg_match('#^https?://#', $ecertOgImage) === 1 ? $ecertOgImage : $ecertSiteUrl . $ecertOgImage;
$ecertOgImageWidth = (string) ($ecertPage['ogImageWidth'] ?? '1200');
$ecertOgImageHeight = (string) ($ecertPage['ogImageHeight'] ?? '630');
$ecertOgImageAlt = (string) ($ecertPage['ogImageAlt'] ?? 'e-Certificate: issue every certificate with confidence');
// Optional Search Console token from the private settings file or the
// environment. Nothing is printed when the owner has not set one.
$ecertSiteVerification = ecert_google_site_verification();
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
<?php if (($ecertPage['robots'] ?? '') === 'noindex'): ?>
  <meta name="robots" content="noindex">
<?php endif; ?>
<?php if ($ecertSiteVerification !== null): ?>
  <meta name="google-site-verification" content="<?= $ecertEscape($ecertSiteVerification) ?>">
<?php endif; ?>
  <link rel="canonical" href="<?= $ecertEscape($ecertCanonical) ?>">
  <meta property="og:url" content="<?= $ecertEscape($ecertCanonical) ?>">
  <meta property="og:site_name" content="<?= $ecertEscape($ecertSiteName) ?>">
  <meta property="og:title" content="<?= $ecertEscape($ecertOgTitle) ?>">
  <meta property="og:description" content="<?= $ecertEscape($ecertOgDescription) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="en_MY">
  <meta property="og:image" content="<?= $ecertEscape($ecertOgImageUrl) ?>">
  <meta property="og:image:width" content="<?= $ecertEscape($ecertOgImageWidth) ?>">
  <meta property="og:image:height" content="<?= $ecertEscape($ecertOgImageHeight) ?>">
  <meta property="og:image:alt" content="<?= $ecertEscape($ecertOgImageAlt) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $ecertEscape($ecertOgTitle) ?>">
  <meta name="twitter:description" content="<?= $ecertEscape($ecertOgDescription) ?>">
  <meta name="twitter:image" content="<?= $ecertEscape($ecertOgImageUrl) ?>">
  <title><?= $ecertEscape($ecertTitle) ?></title>
  <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/styles.css">
<?php foreach ($ecertScripts as $ecertScript): ?>
  <script src="<?= $ecertEscape($ecertScript) ?>" defer></script>
<?php endforeach; ?>
<?php require __DIR__ . '/structured-data.php'; ?>
</head>
<body<?php if ($ecertBodyClass !== ''): ?> class="<?= $ecertEscape($ecertBodyClass) ?>"<?php endif; ?>>
  <a class="skip-link" href="#main-content">Skip to main content</a>

