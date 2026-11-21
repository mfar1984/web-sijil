<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

require_once __DIR__ . '/partials/app-api.php';

$ecertPage = [
    'title' => 'Payment status | e-Certificate',
    'description' => 'Check the status of your e-Certificate payment.',
    'og' => null,
    'nav' => 'pricing',
    'path' => '/payment/status',
    'robots' => 'noindex',
    'bodyClass' => 'inner-page payment-status-page',
    'scripts' => ['/assets/js/payment-status.js'],
];

// Only "ref" (the order reference) and an optional "result" hint are read.
// CHIP may add its own parameters; they are ignored. Nothing here is proof of
// payment: the page asks the app, which asks CHIP.
$ecertReference = isset($_GET['ref']) && is_string($_GET['ref'])
    && preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/D', $_GET['ref']) === 1 ? $_GET['ref'] : null;
$ecertResult = isset($_GET['result']) && in_array($_GET['result'], ['failed', 'cancelled'], true) ? $_GET['result'] : '';

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
  <main id="main-content">
    <section class="page-hero shell-page-hero" aria-labelledby="page-title">
      <div class="page-hero-linework" aria-hidden="true"></div>
      <div class="container shell-hero-copy">
        <p class="eyebrow"><span></span> Payment status</p>
        <h1 id="page-title">Your e-Certificate <em>payment.</em></h1>
        <p>We confirm every payment directly with CHIP before your account is set up.</p>
      </div>
    </section>

    <section class="shell-content contact-shell-content" aria-labelledby="status-title">
      <div class="container">
<?php if ($ecertReference === null): ?>
        <div class="checkout-notice" role="status">
          <p class="form-step">Payment status</p>
          <h2 id="status-title">We could not find this payment.</h2>
          <p>The link is incomplete. If you paid, check your email, or contact us with the time of payment.</p>
          <div class="checkout-notice-actions">
            <a class="button" href="/pricing">View pricing <span aria-hidden="true">↗</span></a>
            <a class="text-link" href="/contact">Contact us <span aria-hidden="true">↗</span></a>
          </div>
        </div>
<?php else: ?>
        <div class="checkout-notice payment-status" data-payment-status
          data-api-base="<?= $ecertEscape(ecert_app_url()) ?>"
          data-reference="<?= $ecertEscape($ecertReference) ?>"
          data-result="<?= $ecertEscape($ecertResult) ?>"
          data-state="pending">
          <p class="form-step">Payment status <span class="status-badge status-badge-test" data-status-test hidden>TEST</span></p>
          <h2 id="status-title" tabindex="-1" data-status-heading><?= $ecertResult !== '' ? 'Payment not completed' : 'Checking your payment…' ?></h2>
          <p class="payment-status-spinner" data-status-spinner aria-hidden="true"></p>
          <p data-status-message aria-live="polite"><?= $ecertResult !== '' ? 'We are confirming the result with CHIP.' : 'This usually takes a few seconds.' ?></p>
          <noscript><p class="verify-noscript">This page needs JavaScript to check your payment. We also email you once the payment is confirmed.</p></noscript>
          <dl class="payment-status-details" data-status-details hidden>
            <div><dt>Package</dt><dd data-status-package></dd></div>
            <div><dt>Amount</dt><dd data-status-amount></dd></div>
            <div><dt>Reference</dt><dd data-status-reference></dd></div>
          </dl>
          <div class="checkout-notice-actions">
            <button class="button" type="button" data-status-retry hidden>Check again</button>
            <a class="button" href="/pricing" data-status-pricing hidden>Try again <span aria-hidden="true">↗</span></a>
            <a class="button" href="<?= $ecertEscape(ecert_app_url() . '/login') ?>" data-status-signin hidden>Sign in <span aria-hidden="true">↗</span></a>
            <a class="text-link" href="/contact">Contact us <span aria-hidden="true">↗</span></a>
          </div>
        </div>
<?php endif; ?>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/partials/footer.php'; ?>
