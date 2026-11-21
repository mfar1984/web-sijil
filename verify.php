<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

$ecertPage = [
    'title' => 'Verify a Certificate | e-Certificate',
    'description' => 'Scan the QR code or enter the certificate number to confirm that a certificate was issued through e-Certificate.',
    'og' => [
        'title' => 'Verify a Certificate | e-Certificate',
        'description' => 'Check that a certificate is genuine by scanning its QR code or entering its certificate number.',
    ],
    'nav' => 'verify',
    'path' => '/verify',
    'bodyClass' => 'inner-page verify-page',
    'scripts' => ['/assets/js/verify-payload.js', '/assets/js/verify.js'],
];

// The browser calls the app's public verification API directly, so each
// visitor is rate limited on their own IP. Only a bare origin is accepted
// from ECERT_APP_URL (local testing); anything else falls back to production.
$ecertAppUrl = 'https://apps.e-certificate.com.my';
$ecertConfiguredAppUrl = getenv('ECERT_APP_URL');
if (is_string($ecertConfiguredAppUrl)) {
    $ecertConfiguredAppUrl = preg_replace('#/$#', '', trim($ecertConfiguredAppUrl));
    if (is_string($ecertConfiguredAppUrl) && preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?$#', $ecertConfiguredAppUrl) === 1) {
        $ecertAppUrl = $ecertConfiguredAppUrl;
    }
}

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
  <main id="main-content">
    <section class="page-hero shell-page-hero verify-hero" aria-labelledby="page-title">
      <div class="page-hero-linework" aria-hidden="true"></div>
      <div class="container shell-hero-copy">
        <p class="eyebrow"><span></span> Verify Certificate</p>
        <h1 id="page-title">Check that a certificate is <em>genuine.</em></h1>
        <p>Scan the QR code printed on an e-Certificate, or type its certificate number, to confirm it was issued through e-Certificate.</p>
      </div>
    </section>

    <section class="verify-section" aria-labelledby="verify-tool-title">
      <div class="container verify-layout">
        <div class="verify-tool" data-verify-tool data-api-base="<?= $ecertEscape($ecertAppUrl) ?>" data-jsqr-src="/assets/vendor/jsqr/jsQR.js">
          <div class="verify-tool-heading">
            <p class="form-step">Check a certificate</p>
            <h2 id="verify-tool-title">How would you like to verify?</h2>
          </div>

          <noscript>
            <p class="verify-noscript">Certificate verification needs JavaScript. Turn it on to check a number or scan a QR code.</p>
          </noscript>

          <div class="verify-modes" data-verify-modes>
            <button class="verify-mode" type="button" id="verify-mode-scan" data-verify-mode="scan">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M7 12h10"/></svg>
              <span>Scan QR code</span>
              <small>Use your camera or a photo</small>
            </button>
            <button class="verify-mode" type="button" id="verify-mode-manual" data-verify-mode="manual">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v10H4zM7 11h2M11 11h2M15 11h2M8 14h8"/></svg>
              <span>Enter certificate number</span>
              <small>Type the number on the certificate</small>
            </button>
          </div>

          <div class="verify-panel verify-scan-panel" id="verify-panel-scan" data-verify-panel="scan" hidden>
            <div class="verify-viewfinder" data-viewfinder data-camera-state="idle">
              <video data-camera-video playsinline muted aria-label="Camera preview"></video>
              <span class="viewfinder-corner viewfinder-corner-tl" aria-hidden="true"></span>
              <span class="viewfinder-corner viewfinder-corner-tr" aria-hidden="true"></span>
              <span class="viewfinder-corner viewfinder-corner-bl" aria-hidden="true"></span>
              <span class="viewfinder-corner viewfinder-corner-br" aria-hidden="true"></span>
              <span class="viewfinder-scan-line" aria-hidden="true"></span>
              <p class="viewfinder-idle" aria-hidden="true">Camera is off</p>
            </div>
            <div class="verify-camera-actions">
              <button class="button verify-camera-button" type="button" data-camera-start>Start camera</button>
              <button class="button feature-outline-button verify-camera-button" type="button" data-camera-stop hidden>Stop camera</button>
            </div>
            <p class="verify-scan-status" data-scan-status role="status">Start the camera and point it at the QR code on the certificate.</p>
            <div class="verify-upload">
              <input class="verify-upload-input" id="qr-upload" type="file" accept="image/*" data-qr-upload aria-describedby="qr-upload-help">
              <label class="verify-upload-label" for="qr-upload">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 16v3h16v-3M12 4v11M7.5 8.5 12 4l4.5 4.5"/></svg>
                <span>Upload a QR image</span>
              </label>
              <p class="field-help" id="qr-upload-help">No camera access? Choose a clear photo or screenshot of the QR code.</p>
            </div>
          </div>

          <div class="verify-panel verify-manual-panel" id="verify-panel-manual" data-verify-panel="manual">
            <form class="verify-form" action="/verify" method="get" novalidate data-verify-form>
              <div class="form-field verify-number-field">
                <label for="certificate-number">Certificate number</label>
                <input id="certificate-number" name="number" type="text" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40" aria-describedby="certificate-number-hint certificate-number-error" data-number-input>
                <p class="field-help" id="certificate-number-hint">Printed on the certificate as CERT-YYYYMMDDHHMMSS-XXXXXX, for example CERT-20260101093000-A1B2C3.</p>
                <p class="field-error" id="certificate-number-error" data-number-error hidden></p>
              </div>
              <button class="button verify-submit" type="submit" data-verify-submit>Verify certificate <span aria-hidden="true">→</span></button>
            </form>
          </div>
        </div>

        <div class="verify-aside">
          <section class="verify-result" data-verify-result aria-live="polite" aria-label="Verification result" hidden></section>

          <section class="verify-explainer" aria-labelledby="verify-how-title">
            <p class="eyebrow dark"><span></span> How verification works</p>
            <h2 id="verify-how-title">Three quick steps.</h2>
            <ol class="verify-steps">
              <li><span>01</span><div><strong>Scan or type</strong><p>Scan the QR code on the certificate, upload a photo of it, or type the certificate number.</p></div></li>
              <li><span>02</span><div><strong>We check the record</strong><p>e-Certificate looks the certificate up in the records kept by the organisation that issued it.</p></div></li>
              <li><span>03</span><div><strong>See the result</strong><p>A genuine certificate shows who it was awarded to, the event, its dates and who issued it.</p></div></li>
            </ol>
          </section>

          <aside class="verify-privacy" aria-labelledby="verify-privacy-title">
            <h2 id="verify-privacy-title">Your privacy</h2>
            <p>Camera frames are processed on your device and never uploaded. Only the certificate number, or the encrypted code printed on the certificate, is sent to e-Certificate.</p>
          </aside>
        </div>
      </div>

      <template data-result-template="valid">
        <article class="verify-card" data-tone="valid">
          <span class="verify-seal" aria-hidden="true">✓</span>
          <p class="verify-card-kicker">Verification result</p>
          <h2 class="verify-card-heading" tabindex="-1" data-result-heading>This certificate is genuine</h2>
          <p class="verify-card-text" data-result-text>It matches a record issued through e-Certificate.</p>
          <dl class="verify-card-fields" data-result-fields></dl>
        </article>
      </template>
      <template data-result-template="not_found">
        <article class="verify-card" data-tone="not-found">
          <span class="verify-seal" aria-hidden="true">✕</span>
          <p class="verify-card-kicker">Verification result</p>
          <h2 class="verify-card-heading" tabindex="-1" data-result-heading>We cannot verify this certificate</h2>
          <p class="verify-card-text" data-result-text>No certificate matches this number. Check for a typo, or ask the holder to send the certificate again.</p>
          <p class="verify-card-checked" data-result-checked hidden>Number checked: <strong data-result-number></strong></p>
        </article>
      </template>
      <template data-result-template="message">
        <article class="verify-card" data-tone="notice">
          <span class="verify-seal" aria-hidden="true">!</span>
          <p class="verify-card-kicker">Verification result</p>
          <h2 class="verify-card-heading" tabindex="-1" data-result-heading></h2>
          <p class="verify-card-text" data-result-text></p>
        </article>
      </template>
    </section>
  </main>

<?php require __DIR__ . '/partials/footer.php'; ?>
