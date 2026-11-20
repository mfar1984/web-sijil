<?php

declare(strict_types=1);

if (!defined('ECERT_PAGE')) {
    http_response_code(404);
    exit;
}

$ecertEscape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$ecertActiveNav = (string) ($ecertPage['nav'] ?? '');
$ecertNavItems = [
    'home' => ['Home', 'index.php'],
    'features' => ['Features', 'features.php'],
    'pricing' => ['Pricing', 'pricing.php'],
    'verify' => ['Verify Certificate', 'https://apps.e-certificate.com.my/verify'],
    'contact' => ['Contact Us', 'contact.php'],
];
?>
  <header class="site-header" data-header>
    <div class="header-inner">
      <a class="brand" href="index.php" aria-label="e-Certificate home">
        <svg class="brand-mark" viewBox="0 0 36 36" aria-hidden="true">
          <path d="M18 2.5 31 10v16L18 33.5 5 26V10L18 2.5Z" fill="currentColor" opacity=".18"/>
          <path d="m9 12 9-5.2 9 5.2-9 5.2L9 12Zm0 5.9 9 5.2 9-5.2v5.9L18 29l-9-5.2v-5.9Z" fill="currentColor"/>
        </svg>
        <span>e-Certificate</span>
      </a>

      <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="primary-navigation">
        <span></span><span></span><span></span>
      </button>

      <nav class="primary-nav" id="primary-navigation" aria-label="Primary navigation">
        <div class="nav-links">
<?php foreach ($ecertNavItems as $ecertNavKey => [$ecertNavLabel, $ecertNavHref]): ?>
<?php if ($ecertNavKey === $ecertActiveNav): ?>
          <a class="active" href="<?= $ecertEscape($ecertNavHref) ?>" aria-current="page"><?= $ecertEscape($ecertNavLabel) ?></a>
<?php else: ?>
          <a href="<?= $ecertEscape($ecertNavHref) ?>"><?= $ecertEscape($ecertNavLabel) ?></a>
<?php endif; ?>
<?php endforeach; ?>
        </div>
        <div class="nav-actions">
          <a class="button button-small" href="https://apps.e-certificate.com.my/">Sign In</a>
        </div>
      </nav>
    </div>
  </header>

