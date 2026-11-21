<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

$ecertPage = [
    'title' => 'Contact Us | e-Certificate',
    'description' => 'Contact the e-Certificate team about digital certificate management for your organisation.',
    'og' => null,
    'nav' => 'contact',
    'path' => '/contact',
    'bodyClass' => 'inner-page',
    'scripts' => ['/assets/js/contact.js'],
];

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
  <main id="main-content">
    <section class="page-hero shell-page-hero" aria-labelledby="page-title">
      <div class="page-hero-linework" aria-hidden="true"></div>
      <div class="container shell-hero-copy">
        <p class="eyebrow"><span></span> Contact Us</p>
        <h1 id="page-title">Tell us about your next <em>certificate programme.</em></h1>
        <p>Share what your organisation needs to design, issue, deliver or verify. Our team will help you plan the next step.</p>
        <a class="button" href="mailto:support@e-certificate.com.my">Email support@e-certificate.com.my <span aria-hidden="true">↗</span></a>
      </div>
    </section>

    <section class="shell-content contact-shell-content" aria-labelledby="contact-form-title">
      <div class="container contact-layout">
        <div class="contact-sidebar">
          <div class="contact-introduction">
            <p class="eyebrow dark"><span></span> Start a conversation</p>
            <h2 id="contact-form-title">Contact the e-Certificate team.</h2>
            <p>Complete the form and your enquiry will be sent securely to our support team.</p>
            <a class="text-link" href="mailto:support@e-certificate.com.my">support@e-certificate.com.my <span aria-hidden="true">↗</span></a>
          </div>

          <aside class="selected-plan" data-plan-summary hidden aria-live="polite" aria-labelledby="selected-plan-title">
            <p class="selected-plan-kicker">Your pricing selection</p>
            <h2 id="selected-plan-title">Plan summary</h2>
            <dl>
              <div><dt>Category</dt><dd data-plan-type></dd></div>
              <div><dt>Duration</dt><dd data-plan-duration></dd></div>
              <div><dt>Price</dt><dd data-plan-price></dd></div>
            </dl>
            <p>The displayed price is confirmed from the e-Certificate plan catalog.</p>
            <a class="selected-plan-change" href="/pricing">Change plan</a>
          </aside>
        </div>

        <form class="contact-form" action="/api/contact.php" method="post" enctype="application/x-www-form-urlencoded" data-contact-form>
          <div class="form-heading">
            <p class="form-step">Enquiry details</p>
            <p>Fields marked <span aria-hidden="true">*</span><span class="visually-hidden">required</span> are required.</p>
          </div>

          <div class="form-grid">
            <div class="form-field">
              <label for="full-name">Full name <span aria-hidden="true">*</span></label>
              <input id="full-name" name="full_name" type="text" autocomplete="name" minlength="2" maxlength="120" required aria-describedby="full-name-error">
              <p class="field-error" id="full-name-error" data-error-for="full_name" hidden></p>
            </div>
            <div class="form-field">
              <label for="work-email">Work email <span aria-hidden="true">*</span></label>
              <input id="work-email" name="email" type="email" autocomplete="email" maxlength="254" required aria-describedby="work-email-error">
              <p class="field-error" id="work-email-error" data-error-for="email" hidden></p>
            </div>
            <div class="form-field">
              <label for="organisation">Organisation <span aria-hidden="true">*</span></label>
              <input id="organisation" name="organisation" type="text" autocomplete="organization" minlength="2" maxlength="160" required aria-describedby="organisation-error">
              <p class="field-error" id="organisation-error" data-error-for="organisation" hidden></p>
            </div>
            <div class="form-field">
              <label for="phone">Phone <span class="optional-label">Optional</span></label>
              <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="30" aria-describedby="phone-error">
              <p class="field-error" id="phone-error" data-error-for="phone" hidden></p>
            </div>
            <div class="form-field form-field-wide">
              <label for="enquiry-type">Enquiry type <span aria-hidden="true">*</span></label>
              <select id="enquiry-type" name="enquiry_type" required aria-describedby="enquiry-type-error">
                <option value="">Select an enquiry type</option>
                <option value="sales">Sales enquiry</option>
                <option value="plan">Plan enquiry</option>
                <option value="product-support">Product support</option>
                <option value="verification">Certificate verification</option>
                <option value="other">Other enquiry</option>
              </select>
              <p class="field-error" id="enquiry-type-error" data-error-for="enquiry_type" hidden></p>
            </div>
            <div class="form-field">
              <label for="plan-type">Plan category <span class="optional-label">Optional</span></label>
              <select id="plan-type" name="type" aria-describedby="plan-type-help plan-type-error" data-plan-type-select>
                <option value="">No plan selected</option>
                <option value="normal">Normal</option>
                <option value="government">Government</option>
              </select>
              <p class="field-help" id="plan-type-help">Choose both plan fields to include a plan.</p>
              <p class="field-error" id="plan-type-error" data-error-for="type" hidden></p>
            </div>
            <div class="form-field">
              <label for="plan-duration">Plan duration <span class="optional-label">Optional</span></label>
              <select id="plan-duration" name="duration" aria-describedby="plan-duration-help plan-duration-error" data-plan-duration-select>
                <option value="">No duration selected</option>
                <option value="1">1 month</option>
                <option value="3">3 months</option>
                <option value="6">6 months</option>
                <option value="12">1 year</option>
              </select>
              <p class="field-help" id="plan-duration-help">Prices are always confirmed by the server catalog.</p>
              <p class="field-error" id="plan-duration-error" data-error-for="duration" hidden></p>
            </div>
            <div class="form-field form-field-wide">
              <label for="message">Message <span aria-hidden="true">*</span></label>
              <textarea id="message" name="message" rows="7" minlength="20" maxlength="3000" required aria-describedby="message-help message-error"></textarea>
              <div class="field-meta"><p class="field-help" id="message-help">20 to 3,000 characters.</p><p><span data-message-count>0</span>/3000</p></div>
              <p class="field-error" id="message-error" data-error-for="message" hidden></p>
            </div>
          </div>

          <div class="honeypot-field" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
          </div>

          <div class="consent-field">
            <input id="consent" name="consent" type="checkbox" value="1" required aria-describedby="consent-error">
            <label for="consent">I consent to e-Certificate using these details to respond to my enquiry. <span aria-hidden="true">*</span></label>
          </div>
          <p class="field-error consent-error" id="consent-error" data-error-for="consent" hidden></p>

          <div class="form-feedback" aria-atomic="true">
            <p class="form-status loading-status" data-form-status="loading" role="status" aria-live="polite" hidden>Sending your enquiry…</p>
            <div class="form-status success-status" data-form-status="success" role="status" aria-live="polite" tabindex="-1" hidden>
              <strong>Your enquiry was sent.</strong>
              <span>The e-Certificate support team has received your message.</span>
            </div>
            <div class="form-status validation-status" data-form-status="validation" role="alert" tabindex="-1" hidden>
              <strong>Please check your details.</strong>
              <span>Correct the highlighted fields and submit again.</span>
            </div>
            <div class="form-status rate-status" data-form-status="rate" role="alert" tabindex="-1" hidden>
              <strong>Too many enquiries were submitted.</strong>
              <span>Please wait before trying again.</span>
            </div>
            <div class="form-status error-status" data-form-status="error" role="alert" tabindex="-1" hidden>
              <strong>We could not send your enquiry.</strong>
              <span>Please try again later or email support@e-certificate.com.my.</span>
            </div>
          </div>

          <button class="button contact-submit" type="submit" data-submit-button>Send enquiry <span aria-hidden="true">→</span></button>
        </form>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/partials/footer.php'; ?>
