<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

$ecertPage = [
    'title' => 'Pricing | e-Certificate',
    'description' => 'Compare exact Normal and Government e-Certificate plan prices by duration.',
    'og' => null,
    'nav' => 'pricing',
    'path' => '/pricing',
    'bodyClass' => 'inner-page',
    'scripts' => ['/assets/js/pricing.js'],
];

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
  <main id="main-content">
    <section class="page-hero pricing-page-hero" aria-labelledby="page-title">
      <div class="page-hero-linework" aria-hidden="true"></div>
      <div class="container pricing-hero-grid">
        <div class="pricing-hero-copy">
          <p class="eyebrow"><span></span> Clear plan options</p>
          <h1 id="page-title">Choose the duration that fits your <em>issuing schedule.</em></h1>
          <p>Compare the approved Normal and Government plan prices. Choose a duration to carry your selection into the Contact page.</p>
        </div>
        <div class="pricing-hero-note" aria-label="How plan selection works">
          <span>01</span><p><strong>Pick a category</strong>Switch between Normal and Government prices.</p>
          <span>02</span><p><strong>Choose a duration</strong>Continue to Contact Us with the selected plan.</p>
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
            <article class="plan-card" data-plan-card data-plan-id="normal-01m" data-plan-type="normal" data-plan-duration="1">
              <p class="plan-duration"><span>01</span> 1 Month</p><p class="plan-price" data-plan-price>RM40</p><p class="plan-period">Plan period: 1 month</p><a class="button plan-choice" href="/contact?type=normal&amp;duration=1">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
            <article class="plan-card" data-plan-card data-plan-id="normal-03m" data-plan-type="normal" data-plan-duration="3">
              <p class="plan-duration"><span>02</span> 3 Months</p><p class="plan-price" data-plan-price>RM65</p><p class="plan-period">Plan period: 3 months</p><a class="button plan-choice" href="/contact?type=normal&amp;duration=3">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
            <article class="plan-card" data-plan-card data-plan-id="normal-06m" data-plan-type="normal" data-plan-duration="6">
              <p class="plan-duration"><span>03</span> 6 Months</p><p class="plan-price" data-plan-price>RM125</p><p class="plan-period">Plan period: 6 months</p><a class="button plan-choice" href="/contact?type=normal&amp;duration=6">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
            <article class="plan-card" data-plan-card data-plan-id="normal-12m" data-plan-type="normal" data-plan-duration="12">
              <p class="plan-duration"><span>04</span> 1 Year</p><p class="plan-price" data-plan-price>RM250</p><p class="plan-period">Plan period: 12 months</p><a class="button plan-choice" href="/contact?type=normal&amp;duration=12">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
          </div>
        </section>

        <section class="pricing-panel" id="government-panel" data-pricing-panel="government">
          <div class="plan-group-heading"><div><span>Government</span><h3>Government plans</h3></div><p>Select the period that matches your current schedule.</p></div>
          <div class="plan-grid">
            <article class="plan-card government-plan" data-plan-card data-plan-id="government-01m" data-plan-type="government" data-plan-duration="1">
              <p class="plan-duration"><span>01</span> 1 Month</p><p class="plan-price" data-plan-price>RM30</p><p class="plan-period">Plan period: 1 month</p><a class="button plan-choice" href="/contact?type=government&amp;duration=1">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
            <article class="plan-card government-plan" data-plan-card data-plan-id="government-03m" data-plan-type="government" data-plan-duration="3">
              <p class="plan-duration"><span>02</span> 3 Months</p><p class="plan-price" data-plan-price>RM50</p><p class="plan-period">Plan period: 3 months</p><a class="button plan-choice" href="/contact?type=government&amp;duration=3">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
            <article class="plan-card government-plan" data-plan-card data-plan-id="government-06m" data-plan-type="government" data-plan-duration="6">
              <p class="plan-duration"><span>03</span> 6 Months</p><p class="plan-price" data-plan-price>RM100</p><p class="plan-period">Plan period: 6 months</p><a class="button plan-choice" href="/contact?type=government&amp;duration=6">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
            <article class="plan-card government-plan" data-plan-card data-plan-id="government-12m" data-plan-type="government" data-plan-duration="12">
              <p class="plan-duration"><span>04</span> 1 Year</p><p class="plan-price" data-plan-price>RM200</p><p class="plan-period">Plan period: 12 months</p><a class="button plan-choice" href="/contact?type=government&amp;duration=12">Choose Plan <span aria-hidden="true">↗</span></a>
            </article>
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
