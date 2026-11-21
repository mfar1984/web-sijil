<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

$ecertPage = [
    'title' => 'Features | e-Certificate',
    'description' => 'Explore e-Certificate features for template design, participant records, certificate delivery, attendance, verification and reporting.',
    'og' => [
        'title' => 'Features | e-Certificate',
        'description' => 'A connected workspace for designing, issuing, delivering and verifying digital certificates.',
    ],
    'nav' => 'features',
    'path' => '/features',
    'bodyClass' => 'inner-page',
    'scripts' => [],
];

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
  <main id="main-content">
    <section class="page-hero feature-page-hero" aria-labelledby="page-title">
      <div class="page-hero-linework" aria-hidden="true"></div>
      <div class="container page-hero-grid">
        <div class="page-hero-copy">
          <p class="eyebrow"><span></span> Product capabilities</p>
          <h1 id="page-title">One clear path from <em>design to proof.</em></h1>
          <p>e-Certificate connects the practical work behind each award: prepare the template, organise recipients, issue certificates and make the result easy to verify.</p>
          <div class="page-hero-actions">
            <a class="button" href="/pricing">View Pricing <span aria-hidden="true">↗</span></a>
            <a class="button button-secondary" href="/contact">Talk to Us</a>
          </div>
        </div>
        <ol class="capability-index" aria-label="Feature areas">
          <li><span>01</span><strong>Prepare</strong><small>Templates and placeholders</small></li>
          <li><span>02</span><strong>Organise</strong><small>Participants and attendance</small></li>
          <li><span>03</span><strong>Issue</strong><small>PDF generation and email</small></li>
          <li><span>04</span><strong>Confirm</strong><small>Verification and reporting</small></li>
        </ol>
      </div>
    </section>

    <section class="feature-introduction" aria-labelledby="feature-intro-title">
      <div class="container feature-intro-grid">
        <p class="eyebrow dark"><span></span> A connected operating flow</p>
        <div>
          <h2 id="feature-intro-title">Keep the certificate process in one organised sequence.</h2>
          <p>Each capability supports a specific part of the issuing journey. Teams can move from programme setup to a public certificate record without losing sight of participant and attendance information.</p>
        </div>
      </div>
    </section>

    <section class="feature-narrative" aria-label="e-Certificate capabilities">
      <div class="container feature-story-list">
        <article class="feature-story">
          <div class="story-copy">
            <span class="story-number">01 / Design</span>
            <h2>Build a reusable certificate template.</h2>
            <p>Use the template designer to prepare certificate layouts and place custom placeholders for participant and programme details.</p>
          </div>
          <div class="story-visual template-story-visual" aria-hidden="true">
            <div class="story-toolbar"><i></i><i></i><i></i><i></i></div>
            <div class="story-certificate"><small>Certificate of Completion</small><strong>Participant Name</strong><span></span><p>Programme title</p><i></i><i></i><i></i><i></i></div>
          </div>
        </article>

        <article class="feature-story feature-story-reverse">
          <div class="story-copy">
            <span class="story-number">02 / Participants</span>
            <h2>Bring recipient records into view.</h2>
            <p>Import participant lists and manage participant records before selecting recipients for certificate generation.</p>
          </div>
          <div class="story-visual participant-story-visual" aria-hidden="true">
            <div class="import-file"><strong>CSV</strong><span>Participant import</span></div>
            <div class="record-list"><small>Participant records</small><p><i>AR</i><strong>Aisyah Rahman</strong><em>Selected</em></p><p><i>DK</i><strong>Daniel Kumar</strong><em>Selected</em></p><p><i>NL</i><strong>Nur Liyana</strong><em>Ready</em></p></div>
          </div>
        </article>

        <article class="feature-story">
          <div class="story-copy">
            <span class="story-number">03 / Issue</span>
            <h2>Generate selected certificates in bulk.</h2>
            <p>Create PDF certificates for selected participants and send the completed certificates by email from the issuing workflow.</p>
          </div>
          <div class="story-visual delivery-story-visual" aria-hidden="true">
            <div class="pdf-file"><span>PDF</span><i></i><i></i></div>
            <div class="delivery-progress"><span>Selected participants</span><i></i><span>PDF certificates</span><i></i><span>Email delivery</span></div>
            <div class="delivery-status"><i>✓</i><p><small>Certificate workflow</small><strong>Prepared for delivery</strong></p></div>
          </div>
        </article>

        <article class="feature-story feature-story-reverse">
          <div class="story-copy">
            <span class="story-number">04 / Attendance</span>
            <h2>Keep attendance tied to the programme.</h2>
            <p>Create attendance sessions and manage participant attendance alongside the records used for certificate operations.</p>
          </div>
          <div class="story-visual attendance-story-visual" aria-hidden="true">
            <div class="session-heading"><span><small>Programme</small><strong>Attendance session</strong></span><em>Open</em></div>
            <div class="session-row"><i>AR</i><span><strong>Aisyah Rahman</strong><small>Participant</small></span><em>Present</em></div>
            <div class="session-row"><i>DK</i><span><strong>Daniel Kumar</strong><small>Participant</small></span><em>Present</em></div>
          </div>
        </article>

        <article class="feature-story">
          <div class="story-copy">
            <span class="story-number">05 / Verify</span>
            <h2>Give every certificate a public check.</h2>
            <p>A reviewer can use the public verification page to check a certificate by its number or follow its QR-linked record.</p>
            <a class="text-link" href="https://apps.e-certificate.com.my/verify">Open public verification <span aria-hidden="true">↗</span></a>
          </div>
          <div class="story-visual verification-story-visual" aria-hidden="true">
            <div class="feature-qr"><span></span></div>
            <div class="feature-verify-result"><i>✓</i><p><small>Verification result</small><strong>Certificate is valid</strong><span>Certificate record confirmed</span></p></div>
          </div>
        </article>

        <article class="feature-story feature-story-reverse">
          <div class="story-copy">
            <span class="story-number">06 / Report</span>
            <h2>Review records and export reports.</h2>
            <p>Review attendance, certificate distribution and event statistics, then use the available exports for reporting.</p>
          </div>
          <div class="story-visual report-story-visual" aria-hidden="true">
            <div class="report-heading"><span><small>Programme report</small><strong>Certificate distribution</strong></span><em>Export</em></div>
            <div class="report-bar"><span>Issued</span><i style="--bar: 82%"></i></div>
            <div class="report-bar"><span>Delivered</span><i style="--bar: 68%"></i></div>
            <div class="report-bar"><span>Attendance</span><i style="--bar: 91%"></i></div>
          </div>
        </article>
      </div>
    </section>

    <section class="feature-verification" aria-labelledby="verification-title">
      <div class="container feature-verification-grid">
        <div>
          <p class="eyebrow light"><span></span> Public verification</p>
          <h2 id="verification-title">Make the outcome easy to confirm.</h2>
        </div>
        <div>
          <p>Open the live verification route to check an existing certificate record, with no account required for the public lookup.</p>
          <a class="button button-cyan" href="https://apps.e-certificate.com.my/verify">Verify a Certificate <span aria-hidden="true">↗</span></a>
        </div>
      </div>
    </section>

    <section class="feature-next" aria-labelledby="next-title">
      <div class="container feature-next-grid">
        <div><p class="eyebrow dark"><span></span> Plan your next programme</p><h2 id="next-title">Choose the next step for your team.</h2></div>
        <div class="feature-next-actions"><a class="button" href="/pricing">View Pricing</a><a class="button feature-outline-button" href="/contact">Contact Us</a></div>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/partials/footer.php'; ?>
