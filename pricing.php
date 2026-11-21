<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

require_once __DIR__ . '/partials/app-api.php';

$ecertPage = [
    'title' => 'Pricing | e-Certificate',
    'description' => 'Compare exact Normal and Government e-Certificate plan prices by duration.',
    'og' => null,
    'nav' => 'pricing',
    'path' => '/pricing',
    'bodyClass' => 'inner-page',
    'scripts' => ['/assets/js/pricing.js'],
];

// Prices come from the app's packages (the price authority). When the app
// cannot be reached the site's own catalog is shown and checkout stays off.
$ecertPackages = ecert_packages();
$ecertCheckout = $ecertPackages['checkout_enabled'];
$ecertGroups = ['normal' => [], 'government' => []];
foreach ($ecertPackages['packages'] as $ecertPackage) {
    $ecertGroups[$ecertPackage['category']][] = $ecertPackage;
}
foreach ($ecertGroups as &$ecertGroup) {
    usort($ecertGroup, static fn (array $a, array $b): int => $a['duration'] <=> $b['duration']);
}
unset($ecertGroup);

$ecertDurationLabel = static fn (int $months): string => $months === 12 ? '1 Year' : $months . ($months === 1 ? ' Month' : ' Months');
$ecertPeriodLabel = static fn (int $months): string => 'Plan period: ' . $months . ($months === 1 ? ' month' : ' months');

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';

$ecertRenderPlans = static function (string $type) use ($ecertGroups, $ecertCheckout, $ecertEscape, $ecertDurationLabel, $ecertPeriodLabel): void {
    foreach ($ecertGroups[$type] as $index => $package) {
        $href = $ecertCheckout
            ? '/checkout?plan=' . rawurlencode($package['code'])
            : '/contact?type=' . $type . '&duration=' . $package['duration'];
        $label = $ecertCheckout ? 'Buy now' : 'Choose Plan';
        $class = $type === 'government' ? 'plan-card government-plan' : 'plan-card';
        ?>
            <article class="<?= $class ?>" data-plan-card data-plan-id="<?= $ecertEscape($package['code']) ?>" data-plan-type="<?= $type ?>" data-plan-duration="<?= $package['duration'] ?>">
              <p class="plan-duration"><span><?= sprintf('%02d', $index + 1) ?></span> <?= $ecertDurationLabel($package['duration']) ?></p><p class="plan-price" data-plan-price><?= $ecertEscape(ecert_format_rm($package['price_cents'])) ?></p><p class="plan-period"><?= $ecertPeriodLabel($package['duration']) ?></p><a class="button plan-choice" href="<?= $ecertEscape($href) ?>"><?= $label ?> <span aria-hidden="true">↗</span></a>
            </article>
<?php
    }
};
?>
  <main id="main-content">
    <section class="page-hero pricing-page-hero" aria-labelledby="page-title">
      <div class="page-hero-linework" aria-hidden="true"></div>
      <div class="container pricing-hero-grid">
        <div class="pricing-hero-copy">
          <p class="eyebrow"><span></span> Clear plan options</p>
          <h1 id="page-title">Choose the duration that fits your <em>issuing schedule.</em></h1>
<?php if ($ecertCheckout): ?>
          <p>Compare the Normal and Government plan prices. Choose a duration to continue to secure checkout.</p>
<?php else: ?>
          <p>Compare the approved Normal and Government plan prices. Choose a duration to carry your selection into the Contact page.</p>
<?php endif; ?>
        </div>
        <div class="pricing-hero-note" aria-label="How plan selection works">
          <span>01</span><p><strong>Pick a category</strong>Switch between Normal and Government prices.</p>
<?php if ($ecertCheckout): ?>
          <span>02</span><p><strong>Choose a duration</strong>Continue to secure checkout with the selected plan.</p>
<?php else: ?>
          <span>02</span><p><strong>Choose a duration</strong>Continue to Contact Us with the selected plan.</p>
<?php endif; ?>
        </div>
      </div>
    </section>

    <section class="pricing-comparison" aria-labelledby="comparison-title" data-pricing-tabs>
      <div class="container">
        <div class="pricing-heading">
          <div><p class="eyebrow dark"><span></span> Plan comparison</p><h2 id="comparison-title">Simple prices by duration.</h2></div>
          <p>Prices below are shown in Malaysian Ringgit. For policy or account details not listed here, Contact us to confirm.</p>
        </div>

        <div class="pricing-tablist" data-pricing-tablist>
          <h3 class="pricing-tab" id="normal-tab" data-pricing-tab="normal"><span>Normal</span><small>Standard plan prices</small></h3>
          <h3 class="pricing-tab" id="government-tab" data-pricing-tab="government"><span>Government</span><small>Government plan prices</small></h3>
        </div>

        <section class="pricing-panel" id="normal-panel" data-pricing-panel="normal">
          <div class="plan-group-heading"><div><span>Normal</span><h3>Normal plans</h3></div><p>Select the period that matches your current schedule.</p></div>
          <div class="plan-grid">
<?php $ecertRenderPlans('normal'); ?>
          </div>
        </section>

        <section class="pricing-panel" id="government-panel" data-pricing-panel="government">
          <div class="plan-group-heading"><div><span>Government</span><h3>Government plans</h3></div><p><?= $ecertCheckout ? $ecertEscape('Government plans need an email address ending in ' . implode(' or ', array_map(static fn (string $d): string => '.' . $d, $ecertPackages['government_domains'])) . '.') : 'Select the period that matches your current schedule.' ?></p></div>
          <div class="plan-grid">
<?php $ecertRenderPlans('government'); ?>
          </div>
        </section>

        <aside class="pricing-confirmation" aria-labelledby="confirmation-title">
          <div><p class="eyebrow light"><span></span> Need a detail confirmed?</p><h2 id="confirmation-title">Ask before you choose.</h2></div>
          <div><p>Terms or policies not shown on this page have not been assumed. Contact us to confirm.</p><a class="button button-cyan" href="/contact">Talk to Us <span aria-hidden="true">↗</span></a></div>
        </aside>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/partials/footer.php'; ?>
