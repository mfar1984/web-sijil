<?php

declare(strict_types=1);

define('ECERT_PAGE', true);

require_once __DIR__ . '/partials/app-api.php';

$ecertPage = [
    'title' => 'Checkout | e-Certificate',
    'description' => 'Secure checkout for an e-Certificate package.',
    'og' => null,
    'nav' => 'pricing',
    'path' => '/checkout',
    'robots' => 'noindex',
    'bodyClass' => 'inner-page checkout-page',
    'scripts' => ['/assets/js/checkout.js'],
];

// Exactly one query key, "plan", holding a package code. The app decides the
// price; this page only shows it and never sends it.
$ecertPlanCode = null;
if (array_keys($_GET) === ['plan'] && is_string($_GET['plan'])
    && preg_match('/^(normal|government)-(01|03|06|12)m$/D', $_GET['plan']) === 1) {
    $ecertPlanCode = $_GET['plan'];
}

$ecertPackages = ecert_packages();
$ecertSelected = null;
if ($ecertPlanCode !== null && $ecertPackages['source'] === 'live' && $ecertPackages['checkout_enabled']) {
    foreach ($ecertPackages['packages'] as $ecertPackage) {
        if ($ecertPackage['code'] === $ecertPlanCode) {
            $ecertSelected = $ecertPackage;
            break;
        }
    }
}

$ecertAppUrl = ecert_app_url();
$ecertStates = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis',
    'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Wilayah Persekutuan Kuala Lumpur',
    'Wilayah Persekutuan Labuan', 'Wilayah Persekutuan Putrajaya',
];
// Same list as the app's User Management form (resources/js/app.js COUNTRIES).
$ecertCountries = [
    'Malaysia', 'Singapore', 'Thailand', 'Indonesia', 'Brunei', 'Philippines', 'Vietnam', 'Myanmar',
    'Cambodia', 'Laos', 'China', 'Japan', 'South Korea', 'Taiwan', 'Hong Kong', 'India', 'Pakistan',
    'Bangladesh', 'United States', 'United Kingdom', 'Australia', 'New Zealand', 'Canada', 'Germany',
    'France', 'Italy', 'Spain', 'Netherlands', 'Others',
];
// Dial codes ported from the app's phone field (resources/js/app.js): name, digits, region.
$ecertDialCodes = [
    ['Malaysia', '60', 'ASEAN'],
    ['Singapore', '65', 'ASEAN'],
    ['Indonesia', '62', 'ASEAN'],
    ['Thailand', '66', 'ASEAN'],
    ['Philippines', '63', 'ASEAN'],
    ['Vietnam', '84', 'ASEAN'],
    ['Brunei', '673', 'ASEAN'],
    ['Myanmar', '95', 'ASEAN'],
    ['Cambodia', '855', 'ASEAN'],
    ['Laos', '856', 'ASEAN'],
    ['China', '86', 'East Asia'],
    ['Japan', '81', 'East Asia'],
    ['South Korea', '82', 'East Asia'],
    ['Taiwan', '886', 'East Asia'],
    ['Hong Kong', '852', 'East Asia'],
    ['Macau', '853', 'East Asia'],
    ['North Korea', '850', 'East Asia'],
    ['Mongolia', '976', 'East Asia'],
    ['India', '91', 'South Asia'],
    ['Pakistan', '92', 'South Asia'],
    ['Bangladesh', '880', 'South Asia'],
    ['Nepal', '977', 'South Asia'],
    ['Sri Lanka', '94', 'South Asia'],
    ['Bhutan', '975', 'South Asia'],
    ['Maldives', '960', 'South Asia'],
    ['Afghanistan', '93', 'South Asia'],
    ['Saudi Arabia', '966', 'Middle East'],
    ['UAE', '971', 'Middle East'],
    ['Qatar', '974', 'Middle East'],
    ['Kuwait', '965', 'Middle East'],
    ['Turkey', '90', 'Middle East'],
    ['Israel', '972', 'Middle East'],
    ['Jordan', '962', 'Middle East'],
    ['Lebanon', '961', 'Middle East'],
    ['Oman', '968', 'Middle East'],
    ['Bahrain', '973', 'Middle East'],
    ['Iran', '98', 'Middle East'],
    ['Iraq', '964', 'Middle East'],
    ['Syria', '963', 'Middle East'],
    ['Yemen', '967', 'Middle East'],
    ['Palestine', '970', 'Middle East'],
    ['United Kingdom', '44', 'Europe'],
    ['Germany', '49', 'Europe'],
    ['France', '33', 'Europe'],
    ['Italy', '39', 'Europe'],
    ['Spain', '34', 'Europe'],
    ['Netherlands', '31', 'Europe'],
    ['Switzerland', '41', 'Europe'],
    ['Sweden', '46', 'Europe'],
    ['Norway', '47', 'Europe'],
    ['Denmark', '45', 'Europe'],
    ['Finland', '358', 'Europe'],
    ['Russia', '7', 'Europe'],
    ['Poland', '48', 'Europe'],
    ['Austria', '43', 'Europe'],
    ['Belgium', '32', 'Europe'],
    ['Ireland', '353', 'Europe'],
    ['Portugal', '351', 'Europe'],
    ['Greece', '30', 'Europe'],
    ['Czech Republic', '420', 'Europe'],
    ['Romania', '40', 'Europe'],
    ['Hungary', '36', 'Europe'],
    ['Bulgaria', '359', 'Europe'],
    ['Slovakia', '421', 'Europe'],
    ['Croatia', '385', 'Europe'],
    ['Lithuania', '370', 'Europe'],
    ['Slovenia', '386', 'Europe'],
    ['Latvia', '371', 'Europe'],
    ['Estonia', '372', 'Europe'],
    ['Cyprus', '357', 'Europe'],
    ['Luxembourg', '352', 'Europe'],
    ['Malta', '356', 'Europe'],
    ['Iceland', '354', 'Europe'],
    ['Albania', '355', 'Europe'],
    ['North Macedonia', '389', 'Europe'],
    ['Serbia', '381', 'Europe'],
    ['Montenegro', '382', 'Europe'],
    ['Bosnia', '387', 'Europe'],
    ['Moldova', '373', 'Europe'],
    ['Belarus', '375', 'Europe'],
    ['Ukraine', '380', 'Europe'],
    ['United States', '1', 'North America'],
    ['Canada', '1', 'North America'],
    ['Mexico', '52', 'North America'],
    ['Guatemala', '502', 'North America'],
    ['Belize', '501', 'North America'],
    ['El Salvador', '503', 'North America'],
    ['Honduras', '504', 'North America'],
    ['Nicaragua', '505', 'North America'],
    ['Costa Rica', '506', 'North America'],
    ['Panama', '507', 'North America'],
    ['Bahamas', '1242', 'North America'],
    ['Cuba', '53', 'North America'],
    ['Jamaica', '1876', 'North America'],
    ['Haiti', '509', 'North America'],
    ['Dominican Republic', '1809', 'North America'],
    ['Puerto Rico', '1787', 'North America'],
    ['Brazil', '55', 'South America'],
    ['Argentina', '54', 'South America'],
    ['Colombia', '57', 'South America'],
    ['Peru', '51', 'South America'],
    ['Venezuela', '58', 'South America'],
    ['Chile', '56', 'South America'],
    ['Ecuador', '593', 'South America'],
    ['Bolivia', '591', 'South America'],
    ['Paraguay', '595', 'South America'],
    ['Uruguay', '598', 'South America'],
    ['Guyana', '592', 'South America'],
    ['Suriname', '597', 'South America'],
    ['Australia', '61', 'Oceania'],
    ['New Zealand', '64', 'Oceania'],
    ['Papua New Guinea', '675', 'Oceania'],
    ['Fiji', '679', 'Oceania'],
    ['Solomon Islands', '677', 'Oceania'],
    ['Vanuatu', '678', 'Oceania'],
    ['Samoa', '685', 'Oceania'],
    ['Tonga', '676', 'Oceania'],
    ['South Africa', '27', 'Africa'],
    ['Nigeria', '234', 'Africa'],
    ['Egypt', '20', 'Africa'],
    ['Morocco', '212', 'Africa'],
    ['Kenya', '254', 'Africa'],
    ['Algeria', '213', 'Africa'],
    ['Ethiopia', '251', 'Africa'],
    ['Tanzania', '255', 'Africa'],
    ['Ghana', '233', 'Africa'],
    ['DR Congo', '243', 'Africa'],
    ['Côte d\'Ivoire', '225', 'Africa'],
    ['Uganda', '256', 'Africa'],
    ['Zambia', '260', 'Africa'],
    ['Madagascar', '261', 'Africa'],
    ['Cameroon', '237', 'Africa'],
    ['Senegal', '221', 'Africa'],
    ['Angola', '244', 'Africa'],
    ['Somalia', '252', 'Africa'],
    ['Mozambique', '258', 'Africa'],
    ['Zimbabwe', '263', 'Africa'],
    ['Sudan', '249', 'Africa'],
    ['Rwanda', '250', 'Africa'],
    ['Mali', '223', 'Africa'],
    ['Burkina Faso', '226', 'Africa'],
    ['Niger', '227', 'Africa'],
    ['Tunisia', '216', 'Africa'],
    ['Chad', '235', 'Africa'],
    ['Guinea', '224', 'Africa'],
    ['Benin', '229', 'Africa'],
    ['Burundi', '257', 'Africa'],
    ['South Sudan', '211', 'Africa'],
    ['Togo', '228', 'Africa'],
    ['Libya', '218', 'Africa'],
    ['Eritrea', '291', 'Africa'],
];
$ecertDialGroups = [];
foreach ($ecertDialCodes as [$ecertDialName, $ecertDialDigits, $ecertDialRegion]) {
    $ecertDialGroups[$ecertDialRegion][] = [$ecertDialName, $ecertDialDigits];
}
uksort($ecertDialGroups, static fn (string $a, string $b): int => $a === 'ASEAN' ? -1 : ($b === 'ASEAN' ? 1 : strcmp($a, $b)));

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';

$ecertStateOptions = static function () use ($ecertStates, $ecertEscape): void {
    echo '<option value="">Select a state</option>';
    foreach ($ecertStates as $state) {
        echo '<option value="', $ecertEscape($state), '">', $ecertEscape($state), '</option>';
    }
    echo '<option value="others">Others</option>';
};
$ecertCountryOptions = static function () use ($ecertCountries, $ecertEscape): void {
    echo '<option value="">Select a country</option>';
    foreach ($ecertCountries as $country) {
        echo '<option value="', $ecertEscape($country), '"', $country === 'Malaysia' ? ' selected' : '', '>', $ecertEscape($country), '</option>';
    }
};
$ecertField = static function (string $name, string $id, string $label, array $attributes = [], bool $required = false, string $help = '') use ($ecertEscape): void {
    $describedBy = trim(($help !== '' ? $id . '-help ' : '') . $id . '-error');
    echo '<div class="form-field', !empty($attributes['wide']) ? ' form-field-wide' : '', '">';
    echo '<label for="', $id, '">', $ecertEscape($label), ' ', $required ? '<span aria-hidden="true">*</span>' : '<span class="optional-label">Optional</span>', '</label>';
    unset($attributes['wide']);
    echo '<input id="', $id, '" name="', $name, '"';
    foreach ($attributes + ['type' => 'text'] as $key => $value) {
        echo ' ', $key, '="', $ecertEscape((string) $value), '"';
    }
    echo $required ? ' required' : '', ' aria-describedby="', $describedBy, '">';
    if ($help !== '') {
        echo '<p class="field-help" id="', $id, '-help">', $ecertEscape($help), '</p>';
    }
    echo '<p class="field-error" id="', $id, '-error" data-error-for="', $name, '" hidden></p></div>';
};
$ecertDurationLabel = static fn (int $months): string => $months === 12 ? '1 year' : $months . ($months === 1 ? ' month' : ' months');
?>
  <main id="main-content">
    <section class="page-hero shell-page-hero" aria-labelledby="page-title">
      <div class="page-hero-linework" aria-hidden="true"></div>
      <div class="container shell-hero-copy">
        <p class="eyebrow"><span></span> Secure checkout</p>
        <h1 id="page-title">Set up your <em>e-Certificate account.</em></h1>
        <p>Enter the details for your Organizer account. After payment we email a link to set your password.</p>
      </div>
    </section>

    <section class="shell-content contact-shell-content" aria-labelledby="checkout-form-title">
<?php if ($ecertSelected === null): ?>
      <div class="container">
        <div class="checkout-notice" role="status">
          <p class="form-step">Checkout</p>
          <h2 id="checkout-form-title">Online checkout is not available for this plan.</h2>
          <p>Choose a plan on the Pricing page, or contact us and we will set up your account.</p>
          <div class="checkout-notice-actions">
            <a class="button" href="/pricing">View pricing <span aria-hidden="true">↗</span></a>
            <a class="text-link" href="/contact">Contact us <span aria-hidden="true">↗</span></a>
          </div>
        </div>
      </div>
<?php else: ?>
      <div class="container contact-layout">
        <div class="contact-sidebar">
          <div class="contact-introduction">
            <p class="eyebrow dark"><span></span> Your package</p>
            <h2 id="checkout-form-title">Organizer account details.</h2>
            <p>These details create your account. Payment is handled on CHIP's secure page; we never see your card.</p>
          </div>

          <aside class="selected-plan" aria-labelledby="selected-plan-title">
            <p class="selected-plan-kicker">Selected package</p>
            <h2 id="selected-plan-title"><?= $ecertEscape($ecertSelected['name']) ?></h2>
            <dl>
              <div><dt>Category</dt><dd><?= $ecertSelected['category'] === 'government' ? 'Government' : 'Normal' ?></dd></div>
              <div><dt>Duration</dt><dd><?= $ecertDurationLabel($ecertSelected['duration']) ?></dd></div>
              <div><dt>Price</dt><dd><?= $ecertEscape(ecert_format_rm($ecertSelected['price_cents'])) ?></dd></div>
            </dl>
<?php if ($ecertSelected['category'] === 'government'): ?>
            <p>Government packages need an email address ending in <?= $ecertEscape(implode(' or ', array_map(static fn (string $d): string => '.' . $d, $ecertPackages['government_domains']))) ?>.</p>
<?php else: ?>
            <p>The amount charged is confirmed by e-Certificate when payment starts.</p>
<?php endif; ?>
            <a class="selected-plan-change" href="/pricing">Change plan</a>
          </aside>
        </div>

        <form class="contact-form checkout-form" method="post" action="#" novalidate data-checkout-form
          data-api-base="<?= $ecertEscape($ecertAppUrl) ?>"
          data-plan="<?= $ecertEscape($ecertSelected['code']) ?>"
          data-plan-category="<?= $ecertEscape($ecertSelected['category']) ?>"
          data-government-domains="<?= $ecertEscape((string) json_encode($ecertPackages['government_domains'])) ?>">
          <div class="form-heading">
            <p class="form-step">Account details</p>
            <p>Fields marked <span aria-hidden="true">*</span><span class="visually-hidden">required</span> are required.</p>
          </div>

          <noscript><p class="verify-noscript">Checkout needs JavaScript. Turn it on, or contact us to buy a package.</p></noscript>

          <fieldset class="checkout-fieldset">
            <legend class="form-step">Your details</legend>
            <div class="form-grid">
<?php $ecertField('name', 'checkout-name', 'Full name', ['autocomplete' => 'name', 'maxlength' => 255, 'wide' => true], true); ?>
<?php $ecertField('email', 'checkout-email', 'Email', ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => 255], true, 'Your sign-in email. We send the set-password link here.'); ?>
              <div class="form-field">
                <label for="checkout-phone">Phone number <span aria-hidden="true">*</span></label>
                <div class="checkout-phone">
                  <select id="checkout-dial" aria-label="Country dialling code" data-phone-dial>
<?php foreach ($ecertDialGroups as $ecertRegion => $ecertGroup): ?>
                    <optgroup label="<?= $ecertEscape($ecertRegion) ?>">
<?php foreach ($ecertGroup as [$ecertDialName, $ecertDialDigits]): ?>
                      <option value="<?= $ecertDialDigits ?>"<?= $ecertDialName === 'Malaysia' ? ' selected' : '' ?>><?= $ecertEscape($ecertDialName . ' +' . $ecertDialDigits) ?></option>
<?php endforeach; ?>
                    </optgroup>
<?php endforeach; ?>
                  </select>
                  <input id="checkout-phone" type="tel" inputmode="tel" autocomplete="tel-national" maxlength="15" required aria-describedby="checkout-phone-help checkout-phone-error" data-phone-number>
                </div>
                <input type="hidden" name="phone" data-phone-value>
                <p class="field-help" id="checkout-phone-help">For example 123456789.</p>
                <p class="field-error" id="checkout-phone-error" data-error-for="phone" hidden></p>
              </div>
            </div>
          </fieldset>

          <fieldset class="checkout-fieldset">
            <legend class="form-step">Address</legend>
            <div class="form-grid">
<?php $ecertField('address_line1', 'checkout-address1', 'Address line 1', ['autocomplete' => 'address-line1', 'maxlength' => 255]); ?>
<?php $ecertField('address_line2', 'checkout-address2', 'Address line 2', ['autocomplete' => 'address-line2', 'maxlength' => 255]); ?>
              <div class="form-field">
                <label for="checkout-state">State <span class="optional-label">Optional</span></label>
                <select id="checkout-state" name="state" aria-describedby="checkout-state-error"><?php $ecertStateOptions(); ?></select>
                <p class="field-error" id="checkout-state-error" data-error-for="state" hidden></p>
              </div>
<?php $ecertField('city', 'checkout-city', 'City', ['autocomplete' => 'address-level2', 'maxlength' => 100]); ?>
<?php $ecertField('postcode', 'checkout-postcode', 'Postcode', ['autocomplete' => 'postal-code', 'inputmode' => 'numeric', 'maxlength' => 10]); ?>
              <div class="form-field">
                <label for="checkout-country">Country <span class="optional-label">Optional</span></label>
                <select id="checkout-country" name="country" autocomplete="country-name" aria-describedby="checkout-country-error"><?php $ecertCountryOptions(); ?></select>
                <p class="field-error" id="checkout-country-error" data-error-for="country" hidden></p>
              </div>
            </div>
          </fieldset>

          <fieldset class="checkout-fieldset">
            <legend class="form-step">Organisation</legend>
            <div class="form-grid">
              <div class="form-field">
                <label for="checkout-org-type">Organisation type <span class="optional-label">Optional</span></label>
                <select id="checkout-org-type" name="org_type" aria-describedby="checkout-org-type-error">
                  <option value="">Select a type</option>
                  <option value="company">Company</option>
                  <option value="government">Government</option>
                  <option value="ngo">NGO</option>
                  <option value="other">Other</option>
                </select>
                <p class="field-error" id="checkout-org-type-error" data-error-for="org_type" hidden></p>
              </div>
<?php $ecertField('org_name', 'checkout-org-name', 'Organisation name', ['autocomplete' => 'organization', 'maxlength' => 255]); ?>
<?php $ecertField('org_address_line1', 'checkout-org-address1', 'Organisation address line 1', ['maxlength' => 255]); ?>
<?php $ecertField('org_address_line2', 'checkout-org-address2', 'Organisation address line 2', ['maxlength' => 255]); ?>
              <div class="form-field">
                <label for="checkout-org-state">Organisation state <span class="optional-label">Optional</span></label>
                <select id="checkout-org-state" name="org_state" aria-describedby="checkout-org-state-error"><?php $ecertStateOptions(); ?></select>
                <p class="field-error" id="checkout-org-state-error" data-error-for="org_state" hidden></p>
              </div>
<?php $ecertField('org_city', 'checkout-org-city', 'Organisation city', ['maxlength' => 100]); ?>
<?php $ecertField('org_postcode', 'checkout-org-postcode', 'Organisation postcode', ['inputmode' => 'numeric', 'maxlength' => 10]); ?>
              <div class="form-field">
                <label for="checkout-org-country">Organisation country <span class="optional-label">Optional</span></label>
                <select id="checkout-org-country" name="org_country" aria-describedby="checkout-org-country-error"><?php $ecertCountryOptions(); ?></select>
                <p class="field-error" id="checkout-org-country-error" data-error-for="org_country" hidden></p>
              </div>
<?php $ecertField('org_telephone', 'checkout-org-telephone', 'Organisation telephone', ['type' => 'tel', 'maxlength' => 20]); ?>
<?php $ecertField('org_fax', 'checkout-org-fax', 'Organisation fax', ['type' => 'tel', 'maxlength' => 20]); ?>
<?php $ecertField('org_email', 'checkout-org-email', 'Organisation email', ['type' => 'email', 'maxlength' => 255]); ?>
<?php $ecertField('org_website', 'checkout-org-website', 'Organisation website', ['type' => 'url', 'maxlength' => 255, 'placeholder' => 'https://']); ?>
            </div>
          </fieldset>

          <div class="honeypot-field" aria-hidden="true">
            <label for="checkout-hp">Company</label>
            <input id="checkout-hp" name="hp_company" type="text" tabindex="-1" autocomplete="off">
          </div>

          <div class="consent-field">
            <input id="checkout-consent" name="consent" type="checkbox" value="1" required aria-describedby="checkout-consent-error">
            <label for="checkout-consent">I agree to the <a href="<?= $ecertEscape($ecertAppUrl . '/terms-and-conditions') ?>" target="_blank" rel="noopener">Terms and Conditions</a> and the <a href="<?= $ecertEscape($ecertAppUrl . '/privacy-policy') ?>" target="_blank" rel="noopener">Privacy Policy</a>. <span aria-hidden="true">*</span></label>
          </div>
          <p class="field-error consent-error" id="checkout-consent-error" data-error-for="consent" hidden></p>

          <div class="form-feedback" aria-atomic="true">
            <p class="form-status loading-status" data-form-status="loading" role="status" aria-live="polite" hidden>Starting secure payment…</p>
            <div class="form-status validation-status" data-form-status="validation" role="alert" tabindex="-1" hidden>
              <strong>Please check your details.</strong>
              <span data-status-text>Correct the highlighted fields and submit again.</span>
            </div>
            <div class="form-status rate-status" data-form-status="rate" role="alert" tabindex="-1" hidden>
              <strong>Too many attempts.</strong>
              <span data-status-text>Please wait before trying again.</span>
            </div>
            <div class="form-status error-status" data-form-status="error" role="alert" tabindex="-1" hidden>
              <strong>We could not start the payment.</strong>
              <span>Please try again in a few minutes or <a href="/contact">contact us</a>.</span>
            </div>
          </div>

          <button class="button contact-submit" type="submit" data-submit-button>Continue to payment <span aria-hidden="true">→</span></button>
        </form>
      </div>
<?php endif; ?>
    </section>
  </main>

<?php require __DIR__ . '/partials/footer.php'; ?>