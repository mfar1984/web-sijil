<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

$ecertPage = [
    'title' => 'e-Certificate | Issue certificates with confidence',
    'description' => 'e-Certificate helps Malaysian organisations design, issue, deliver and verify digital certificates from one organised workspace.',
    'og' => [
        'title' => 'e-Certificate | Issue certificates with confidence',
        'description' => 'A focused certificate workflow for events, training and education, from participant import to public verification.',
    ],
    'nav' => 'home',
    'path' => '/',
    'bodyClass' => '',
    'scripts' => [],
];

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
  <main id="main-content">
    <section class="hero" id="home" aria-labelledby="hero-title">
      <div class="hero-glow" aria-hidden="true"></div>
      <div class="hero-motion-lines" aria-hidden="true"></div>
      <div class="hero-carousel" data-hero-carousel role="region" aria-roledescription="carousel" aria-label="e-Certificate product highlights" tabindex="0">
        <p class="visually-hidden" data-hero-status aria-live="polite" aria-atomic="true">Slide 1 of 3: Issue certificates with confidence</p>
        <div class="hero-slides">
          <article class="hero-slide is-active" data-hero-slide aria-roledescription="slide" aria-label="1 of 3: Issue certificates with confidence" aria-hidden="false">
            <div class="container hero-grid">
              <div class="hero-copy">
                <p class="eyebrow"><span></span> Certificate operations, brought together</p>
                <h1 id="hero-title">Issue every certificate with <em>confidence.</em></h1>
                <p class="hero-lead">Design, generate and deliver digital certificates from one organised workspace, with public verification built into every award.</p>
                <div class="hero-actions"><a class="button" href="/contact">Get Started <span aria-hidden="true">↗</span></a><a class="button button-secondary" href="/features">Explore Features</a></div>
                <ul class="hero-points" aria-label="Key capabilities"><li><span aria-hidden="true">✓</span> Bulk participant import</li><li><span aria-hidden="true">✓</span> PDF generation</li><li><span aria-hidden="true">✓</span> Email delivery</li></ul>
              </div>
              <div class="product-stage issue-stage" aria-label="Illustration of certificate issuing workspace">
                <div class="stage-grid" aria-hidden="true"></div>
                <div class="dashboard-card">
                  <div class="window-bar"><span class="window-brand"><i></i> e-Certificate</span><span class="window-dots"><i></i><i></i><i></i></span></div>
                  <div class="dashboard-body"><aside aria-hidden="true"><span class="dash-logo"></span><span></span><span class="selected"></span><span></span><span></span><span></span></aside><div class="dashboard-content"><div class="dash-heading"><div><small>Certificate workspace</small><strong>Issue certificates</strong></div><span class="status"><i></i> Ready</span></div><div class="dash-stats"><div><span>01</span><p>Design</p></div><div><span>02</span><p>Recipients</p></div><div class="current"><span>03</span><p>Issue</p></div></div><div class="recipient-list"><div class="list-head"><span>Recipient</span><span>Delivery</span></div><div class="recipient"><span><i>AR</i><b>Aisyah Rahman</b></span><em>Prepared</em></div><div class="recipient muted"><span><i>DK</i><b>Daniel Kumar</b></span><em>Prepared</em></div></div></div></div>
                </div>
                <article class="certificate-card" aria-label="Illustrative sample certificate"><div class="cert-lines" aria-hidden="true"></div><span class="sample-label">SAMPLE</span><div class="cert-seal" aria-hidden="true"><svg viewBox="0 0 36 36"><path d="M18 2.5 31 10v16L18 33.5 5 26V10L18 2.5Z" fill="currentColor" opacity=".15"/><path d="m10 12 8-4.6 8 4.6-8 4.7-8-4.7Zm0 5.6 8 4.7 8-4.7v5.5L18 28l-8-4.9v-5.5Z" fill="currentColor"/></svg></div><p class="cert-kicker">Certificate of Completion</p><p class="cert-copy">This certificate is presented to</p><h2>Aisyah Rahman</h2><div class="cert-rule"></div><p class="cert-programme">Digital Leadership Programme</p><div class="cert-bottom"><div><span>18 JUNE 2025</span><small>Date issued</small></div><div class="signature"><span>e-Certificate</span><small>Authorised issuer</small></div><svg class="qr" viewBox="0 0 29 29" aria-label="Sample QR verification code" role="img"><rect width="29" height="29" rx="2" fill="#fff"/><g fill="#081a34"><path d="M2 2h8v8H2zM4 4v4h4V4zM19 2h8v8h-8zM21 4v4h4V4zM2 19h8v8H2zM4 21v4h4v-4z"/><path d="M12 2h2v4h-2zm4 1h2v7h-2zm-4 7h5v2h-5zm7 2h7v2h-7zm-7 3h3v5h-3zm5 2h3v3h-3zm5-1h4v5h-4zm-10 7h5v4h-5zm7 1h3v3h-3zm5-1h3v4h-3z"/></g></svg></div></article>
                <div class="verified-chip"><span aria-hidden="true">✓</span><div><small>Public verification</small><strong>Certificate prepared</strong></div></div>
              </div>
            </div>
          </article>

          <article class="hero-slide" data-hero-slide aria-roledescription="slide" aria-label="2 of 3: From attendance to certificate" aria-hidden="true">
            <div class="container hero-grid">
              <div class="hero-copy">
                <p class="eyebrow"><span></span> Connected programme records</p>
                <h2>From attendance to certificate, <em>connected.</em></h2>
                <p class="hero-lead">Keep participant and attendance records connected to certificate creation, so your team can move from check-in to prepared awards in one system.</p>
                <div class="hero-actions"><a class="button" href="#workflow">See How It Works <span aria-hidden="true">↓</span></a><a class="button button-secondary" href="/contact">Talk to Us</a></div>
                <ul class="hero-points" aria-label="Attendance workflow capabilities"><li><span aria-hidden="true">✓</span> QR attendance</li><li><span aria-hidden="true">✓</span> Participant records</li><li><span aria-hidden="true">✓</span> Bulk certificates</li></ul>
              </div>
              <div class="product-stage attendance-stage" aria-label="Illustration of attendance records moving into certificate preparation">
                <div class="stage-grid" aria-hidden="true"></div>
                <div class="attendance-board">
                  <div class="window-bar"><span class="window-brand"><i></i> Programme workspace</span><span class="window-dots"><i></i><i></i><i></i></span></div>
                  <div class="attendance-body"><div class="attendance-head"><div><small>Digital Leadership Programme</small><strong>Attendance session</strong></div><span class="status"><i></i> In progress</span></div><div class="attendance-rows"><div><b>AR</b><span><strong>Aisyah Rahman</strong><small>Participant record</small></span><em>Checked in</em></div><div><b>DK</b><span><strong>Daniel Kumar</strong><small>Participant record</small></span><em>Checked in</em></div><div><b>NL</b><span><strong>Nur Liyana</strong><small>Participant record</small></span><em>Checked in</em></div></div></div>
                </div>
                <div class="flow-connector" aria-hidden="true"><span>Attendance</span><i>→</i><span>Certificate</span></div>
                <div class="ready-award"><span class="award-file">PDF</span><div><small>Certificate creation</small><strong>Recipients prepared</strong><p>Participant records connected</p></div><i>✓</i></div>
              </div>
            </div>
          </article>

          <article class="hero-slide" data-hero-slide aria-roledescription="slide" aria-label="3 of 3: Verify every certificate instantly" aria-hidden="true">
            <div class="container hero-grid">
              <div class="hero-copy">
                <p class="eyebrow"><span></span> Public certificate verification</p>
                <h2>Verify every certificate <em>instantly.</em></h2>
                <p class="hero-lead">Give recipients, employers and institutions a direct public route to check a certificate by number or its QR-linked record.</p>
                <div class="hero-actions"><a class="button" href="/verify">Verify a Certificate <span aria-hidden="true">↗</span></a><a class="button button-secondary" href="#verification">Learn About Verification</a></div>
                <ul class="hero-points" aria-label="Verification capabilities"><li><span aria-hidden="true">✓</span> Public access</li><li><span aria-hidden="true">✓</span> Certificate lookup</li><li><span aria-hidden="true">✓</span> QR-linked record</li></ul>
              </div>
              <div class="product-stage verification-stage" aria-label="Illustration of QR certificate verification">
                <div class="stage-grid" aria-hidden="true"></div>
                <div class="verify-certificate"><span class="sample-label">SAMPLE</span><span class="verify-cert-mark">EC</span><small>Certificate of Completion</small><strong>Aisyah Rahman</strong><i></i><p>Digital Leadership Programme</p><svg class="qr hero-qr" viewBox="0 0 29 29" aria-label="Sample QR verification code" role="img"><rect width="29" height="29" rx="2" fill="#fff"/><g fill="#081a34"><path d="M2 2h8v8H2zM4 4v4h4V4zM19 2h8v8h-8zM21 4v4h4V4zM2 19h8v8H2zM4 21v4h4v-4z"/><path d="M12 2h2v4h-2zm4 1h2v7h-2zm-4 7h5v2h-5zm7 2h7v2h-7zm-7 3h3v5h-3zm5 2h3v3h-3zm5-1h4v5h-4zm-10 7h5v4h-5zm7 1h3v3h-3zm5-1h3v4h-3z"/></g></svg></div>
                <div class="scan-frame" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                <div class="verification-result"><span>✓</span><div><small>Verification result</small><strong>Certificate is valid</strong><p>Certificate record confirmed</p></div></div>
              </div>
            </div>
          </article>
        </div>

        <div class="container hero-carousel-nav">
          <div class="hero-dots" aria-label="Choose a product highlight"><button class="is-active" type="button" data-hero-to="0" aria-current="true" aria-label="Show slide 1: Issue certificates"><span></span></button><button type="button" data-hero-to="1" aria-label="Show slide 2: Attendance to certificate"><span></span></button><button type="button" data-hero-to="2" aria-label="Show slide 3: Public verification"><span></span></button></div>
          <div class="hero-controls"><button type="button" data-hero-prev aria-label="Previous hero slide"><span aria-hidden="true">←</span></button><button type="button" data-hero-toggle aria-pressed="false" aria-label="Pause hero slideshow"><span data-pause-icon aria-hidden="true">Ⅱ</span><span class="control-label">Pause</span></button><button type="button" data-hero-next aria-label="Next hero slide"><span aria-hidden="true">→</span></button></div>
          <p class="hero-count"><span data-hero-current>01</span> / 03</p>
        </div>
      </div>
    </section>

    <section class="audience-strip" aria-label="Built for Malaysian organisations">
      <div class="container strip-inner">
        <p>Built for teams issuing at scale</p>
        <ul>
          <li>Event organisers</li><li>Training providers</li><li>Education</li><li>Associations</li><li>HR teams</li>
        </ul>
      </div>
    </section>

    <section class="section features" id="features" aria-labelledby="features-title">
      <div class="container">
        <div class="section-heading split-heading">
          <div><p class="eyebrow dark"><span></span> One connected workflow</p><h2 id="features-title">Less certificate admin.<br>More control.</h2></div>
          <p>Move from participant records to delivered certificates without stitching together templates, spreadsheets and separate verification tools.</p>
        </div>

        <div class="bento-grid">
          <article class="bento bento-large bento-design">
            <div class="bento-copy"><span class="feature-number">01</span><h3>Design for every programme</h3><p>Create reusable certificate templates with a drag-and-drop designer and custom placeholders.</p></div>
            <div class="mini-designer" aria-hidden="true">
              <div class="designer-tools"><i></i><i></i><i></i><i></i></div>
              <div class="designer-canvas"><span class="canvas-mark">EC</span><small>CERTIFICATE</small><b>Participant Name</b><i></i><p>Programme title</p><span class="selection"><i></i><i></i><i></i><i></i></span></div>
            </div>
          </article>

          <article class="bento bento-dark">
            <span class="feature-number">02</span>
            <div class="import-icon" aria-hidden="true"><span>CSV</span><i>↓</i></div>
            <h3>Bring participant lists in</h3>
            <p>Import participants in bulk, then manage their records in one central database.</p>
          </article>

          <article class="bento bento-light">
            <span class="feature-number">03</span>
            <div class="mail-visual" aria-hidden="true"><span></span><i>✓</i></div>
            <h3>Generate and deliver</h3>
            <p>Create PDF certificates in bulk and send them by email from the same workflow.</p>
          </article>

          <article class="bento bento-wide">
            <div class="bento-copy"><span class="feature-number">04</span><h3>Verification that travels with the certificate</h3><p>Recipients and third parties can use a certificate number or QR-linked public page to confirm an award.</p><a class="text-link" href="/verify">Open public verification <span aria-hidden="true">↗</span></a></div>
            <div class="verify-panel" aria-hidden="true"><span class="scan-corners"></span><svg class="qr large" viewBox="0 0 29 29"><rect width="29" height="29" rx="2" fill="#fff"/><g fill="#081a34"><path d="M2 2h8v8H2zM4 4v4h4V4zM19 2h8v8h-8zM21 4v4h4V4zM2 19h8v8H2zM4 21v4h4v-4z" fill-rule="evenodd"/><path d="M12 2h2v2h-2zm3 0h2v5h-2zm-3 4h2v3h-2zm1 5h3v2h-3zm5 0h3v2h-3zm4 1h2v3h-2zm3-1h2v5h-2zm-14 4h2v3h-2zm4-1h2v3h-2zm3 1h3v2h-3zm-6 5h3v2h-3zm4-2h2v2h-2zm3 1h3v4h-3zm5-1h3v3h-3zm-12 6h2v3h-2zm4-1h2v4h-2zm4 2h2v2h-2zm4-2h3v4h-3z"/></g></svg><div><i>✓</i><small>Verification result</small><strong>Certificate is valid</strong></div></div>
          </article>

          <article class="bento bento-report">
            <span class="feature-number">05</span><h3>See the operational picture</h3><p>Review attendance, certificate distribution and event statistics, with export support for reporting.</p>
            <div class="report-lines" aria-hidden="true"><span style="--w:76%"></span><span style="--w:92%"></span><span style="--w:58%"></span></div>
          </article>
        </div>
      </div>
    </section>

    <section class="section workflow" id="workflow" aria-labelledby="workflow-title">
      <div class="workflow-linework" aria-hidden="true"></div>
      <div class="container">
        <div class="section-heading centered">
          <p class="eyebrow dark"><span></span> From setup to proof</p>
          <h2 id="workflow-title">A clearer path to every certificate</h2>
          <p>Keep the work in sequence, so teams know what is ready and recipients know what is real.</p>
        </div>

        <div class="workflow-steps">
          <div class="workflow-timeline">
            <ol class="workflow-step-list">
              <li class="workflow-step">
                <div class="slide-copy">
                  <span class="slide-number">Step 01</span>
                  <h3>Design once. Stay consistent.</h3>
                  <p>Build a reusable certificate template with the drag-and-drop designer, then place participant and programme details with custom placeholders.</p>
                  <span class="slide-note"><i aria-hidden="true">✓</i> Reusable certificate templates</span>
                </div>
                <div class="slide-visual design-visual" aria-hidden="true">
                  <div class="design-toolbar"><i></i><i></i><i></i><i></i></div>
                  <div class="design-sheet">
                    <span class="sheet-mark">EC</span><small>Certificate of Completion</small><strong>Participant Name</strong><i></i><p>Programme title</p>
                    <span class="selection-box"><i></i><i></i><i></i><i></i></span>
                  </div>
                </div>
              </li>
              <li class="workflow-step">
                <div class="slide-copy">
                  <span class="slide-number">Step 02</span>
                  <h3>Bring every recipient into view.</h3>
                  <p>Import participant lists in bulk and keep individual records organised in one central workspace before certificates are created.</p>
                  <span class="slide-note"><i aria-hidden="true">✓</i> Bulk participant import</span>
                </div>
                <div class="slide-visual import-visual" aria-hidden="true">
                  <div class="csv-file"><span>CSV</span><small>participant-list.csv</small></div>
                  <div class="import-path"><i></i><i></i><i></i></div>
                  <div class="people-panel"><small>Participant records</small><div><b>AR</b><span>Aisyah Rahman<em>Ready</em></span></div><div><b>DK</b><span>Daniel Kumar<em>Ready</em></span></div><div><b>NL</b><span>Nur Liyana<em>Ready</em></span></div></div>
                </div>
              </li>
              <li class="workflow-step">
                <div class="slide-copy">
                  <span class="slide-number">Step 03</span>
                  <h3>Generate and deliver together.</h3>
                  <p>Create PDF certificates in bulk and send them by email from the same workflow, without rebuilding each award by hand.</p>
                  <span class="slide-note"><i aria-hidden="true">✓</i> PDF generation and email delivery</span>
                </div>
                <div class="slide-visual issue-visual" aria-hidden="true">
                  <div class="pdf-stack"><span>PDF</span><i></i><i></i></div>
                  <div class="delivery-line"><span>01</span><span>02</span><span>03</span></div>
                  <div class="mail-card"><svg viewBox="0 0 24 24"><path d="M3 6h18v12H3V6Zm0 1 9 7 9-7"/></svg><div><small>Email delivery</small><strong>Prepared to send</strong></div><i>✓</i></div>
                </div>
              </li>
              <li class="workflow-step">
                <div class="slide-copy">
                  <span class="slide-number">Step 04</span>
                  <h3>Make the outcome easy to prove.</h3>
                  <p>Connect each award to a public verification route, giving reviewers a direct way to check the certificate number or QR-linked record.</p>
                  <span class="slide-note"><i aria-hidden="true">✓</i> Public certificate verification</span>
                </div>
                <div class="slide-visual carousel-verify-visual" aria-hidden="true">
                  <svg class="qr carousel-qr" viewBox="0 0 29 29"><rect width="29" height="29" rx="2" fill="#fff"/><g fill="#081a34"><path d="M2 2h8v8H2zM4 4v4h4V4zM19 2h8v8h-8zM21 4v4h4V4zM2 19h8v8H2zM4 21v4h4v-4z" fill-rule="evenodd"/><path d="M12 2h2v2h-2zm3 0h2v5h-2zm-3 4h2v3h-2zm1 5h3v2h-3zm5 0h3v2h-3zm4 1h2v3h-2zm3-1h2v5h-2zm-14 4h2v3h-2zm4-1h2v3h-2zm3 1h3v2h-3zm-6 5h3v2h-3zm4-2h2v2h-2zm3 1h3v4h-3zm5-1h3v3h-3zm-12 6h2v3h-2zm4-1h2v4h-2zm4 2h2v2h-2zm4-2h3v4h-3z"/></g></svg>
                  <div class="verify-result"><i>✓</i><div><small>Verification result</small><strong>Certificate is valid</strong><span>Certificate record confirmed</span></div></div>
                </div>
              </li>
            </ol>
          </div>

        </div>
      </div>
    </section>

    <section class="section trust" id="verification" aria-labelledby="trust-title">
      <div class="container trust-grid">
        <div class="trust-art" aria-hidden="true">
          <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
          <div class="trust-document"><span class="doc-mark">EC</span><i></i><i></i><i></i><div class="doc-qr"></div></div>
          <div class="shield"><svg viewBox="0 0 48 48"><path d="M24 4 40 10v11c0 10-6.7 18.5-16 23C14.7 39.5 8 31 8 21V10l16-6Z"/><path d="m17 24 5 5 10-12"/></svg></div>
          <div class="trust-tag"><i>✓</i><span><small>Status</small><b>Verified</b></span></div>
        </div>
        <div class="trust-copy">
          <p class="eyebrow light"><span></span> Built-in public verification</p>
          <h2 id="trust-title">Make every award easy to trust.</h2>
          <p>A shareable verification route gives employers, institutions and other reviewers a direct way to check a certificate without signing in.</p>
          <ul class="check-list">
            <li><i>✓</i><span><strong>QR-ready verification</strong>Connect certificates to the public verification experience.</span></li>
            <li><i>✓</i><span><strong>Certificate number lookup</strong>Support a clear manual path when scanning is not convenient.</span></li>
            <li><i>✓</i><span><strong>Focused verification result</strong>Confirm the award through the application’s public certificate record.</span></li>
          </ul>
          <a class="button button-cyan" href="/verify">Verify a Certificate <span aria-hidden="true">↗</span></a>
        </div>
      </div>
    </section>

    <section class="section use-cases" id="use-cases" aria-labelledby="use-cases-title">
      <div class="container">
        <div class="section-heading split-heading compact">
          <div><p class="eyebrow dark"><span></span> Made for Malaysian teams</p><h2 id="use-cases-title">One system. Many programmes.</h2></div>
          <p>Use the same structured process across recurring training, formal learning, events and internal development.</p>
        </div>
        <div class="use-case-list">
          <article><span>01</span><div><h3>Training providers</h3><p>Keep participant lists, attendance records and certificate delivery connected for each programme.</p></div></article>
          <article><span>02</span><div><h3>Educational institutions</h3><p>Create consistent templates and give recipients a simple way to share verifiable awards.</p></div></article>
          <article><span>03</span><div><h3>Event organisers &amp; associations</h3><p>Manage registration, attendance and post-event certificate distribution in one workflow.</p></div></article>
          <article><span>04</span><div><h3>HR &amp; organisational teams</h3><p>Issue certificates for internal learning and maintain organised distribution records.</p></div></article>
        </div>
      </div>
    </section>

    <section class="section pricing" id="pricing" aria-labelledby="pricing-title">
      <div class="container pricing-card">
        <div>
          <p class="eyebrow light"><span></span> Flexible plans</p>
          <h2 id="pricing-title">Choose a setup that fits how you issue.</h2>
          <p>Whether you run occasional programmes or manage certificates across teams, talk to us about the workflow and support you need.</p>
        </div>
        <div class="pricing-action">
          <span>Normal and Government plan options</span>
          <a class="button button-cyan" href="/pricing">View Pricing <span aria-hidden="true">↗</span></a>
          <small>Compare the available plan durations and prices.</small>
        </div>
      </div>
    </section>

    <section class="final-cta" id="contact" aria-labelledby="contact-title">
      <div class="cta-pattern" aria-hidden="true"></div>
      <div class="container cta-content">
        <p class="eyebrow"><span></span> Ready when your next programme is</p>
        <h2 id="contact-title">Turn certificate day into<br><em>a confident workflow.</em></h2>
        <p>Tell us how your organisation creates, distributes and verifies certificates today.</p>
        <div class="cta-actions">
          <a class="button button-light" href="/contact">Start a conversation <span aria-hidden="true">↗</span></a>
          <a class="plain-link" href="https://apps.e-certificate.com.my/">Already have an account? <strong>Sign in</strong></a>
        </div>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/partials/footer.php'; ?>
