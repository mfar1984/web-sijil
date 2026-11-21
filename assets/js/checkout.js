(() => {
  const form = document.querySelector('[data-checkout-form]');
  if (!form) return;

  const apiBase = form.dataset.apiBase || '';
  const plan = form.dataset.plan || '';
  const category = form.dataset.planCategory || '';
  let governmentDomains = [];
  try {
    governmentDomains = JSON.parse(form.dataset.governmentDomains || '[]').filter((d) => typeof d === 'string');
  } catch {
    governmentDomains = [];
  }

  const submitButton = form.querySelector('[data-submit-button]');
  const dial = form.querySelector('[data-phone-dial]');
  const phoneNumber = form.querySelector('[data-phone-number]');
  const phoneValue = form.querySelector('[data-phone-value]');
  const statuses = Object.fromEntries(
    [...form.querySelectorAll('[data-form-status]')].map((status) => [status.dataset.formStatus, status])
  );
  const fields = [
    'name', 'email', 'phone', 'address_line1', 'address_line2', 'postcode', 'city', 'state', 'country',
    'org_type', 'org_name', 'org_address_line1', 'org_address_line2', 'org_state', 'org_city',
    'org_postcode', 'org_country', 'org_telephone', 'org_fax', 'org_email', 'org_website'
  ];
  const errorFields = [...fields, 'consent', 'package'];
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const loopbackApi = /^http:\/\/127\.0\.0\.1(:\d+)?$/.test(apiBase);

  // Same shape as the app's phone field: dial digits + national digits, no leading 0.
  const syncPhone = () => {
    let digits = phoneNumber.value.replace(/\D/g, '');
    if (digits.startsWith(dial.value)) digits = digits.slice(dial.value.length);
    digits = digits.replace(/^0+/, '');
    phoneValue.value = digits ? dial.value + digits : '';
  };
  phoneNumber.addEventListener('input', syncPhone);
  dial.addEventListener('change', syncPhone);

  const hideStatuses = () => {
    Object.values(statuses).forEach((status) => { status.hidden = true; });
  };

  const showStatus = (name, text = null) => {
    hideStatuses();
    const status = statuses[name];
    if (!status) return;
    const target = status.querySelector('[data-status-text]');
    if (text && target) target.textContent = text;
    status.hidden = false;
    status.focus?.();
  };

  const fieldFor = (name) => (name === 'phone' ? phoneNumber : form.elements.namedItem(name));

  const clearErrors = () => {
    form.querySelectorAll('[data-error-for]').forEach((error) => {
      error.hidden = true;
      error.textContent = '';
    });
    form.querySelectorAll('[aria-invalid]').forEach((field) => field.removeAttribute('aria-invalid'));
  };

  const showErrors = (errors, summary) => {
    let firstInvalid = null;
    Object.entries(errors).forEach(([name, value]) => {
      if (!errorFields.includes(name)) return;
      const text = Array.isArray(value) ? value[0] : value;
      if (typeof text !== 'string') return;
      const error = form.querySelector(`[data-error-for="${name}"]`);
      const field = fieldFor(name);
      if (error) {
        error.textContent = text;
        error.hidden = false;
      }
      if (field && field.setAttribute) {
        field.setAttribute('aria-invalid', 'true');
        if (!firstInvalid) firstInvalid = field;
      }
      if (name === 'package' && !firstInvalid) firstInvalid = submitButton;
    });
    showStatus('validation', summary);
    firstInvalid?.focus();
  };

  const value = (name) => {
    const field = form.elements.namedItem(name);
    return field && typeof field.value === 'string' ? field.value.trim() : '';
  };

  const governmentEmail = (email) => {
    const domain = email.slice(email.lastIndexOf('@') + 1).toLowerCase();
    return governmentDomains.some((d) => domain === d || domain.endsWith(`.${d}`));
  };

  // Quick checks for a better first experience; the app validates everything again.
  const localErrors = () => {
    const errors = {};
    const email = value('email');
    if (!value('name')) errors.name = 'Enter your full name.';
    if (!email) errors.email = 'Enter your email address.';
    else if (!emailPattern.test(email)) errors.email = 'Enter a valid email address.';
    else if (category === 'government' && governmentDomains.length && !governmentEmail(email)) {
      errors.email = `Government packages are for email addresses ending in .${governmentDomains.join(' or .')}. Choose a Normal package or contact us.`;
    }
    syncPhone();
    if (!phoneValue.value) errors.phone = 'Enter your phone number.';
    else if (phoneValue.value.length > 15) errors.phone = 'The phone number is too long.';
    const orgEmail = value('org_email');
    if (orgEmail && !emailPattern.test(orgEmail)) errors.org_email = 'Enter a valid organisation email address.';
    const website = value('org_website');
    if (website) {
      try {
        const parsed = new URL(website);
        if (!['http:', 'https:'].includes(parsed.protocol)) throw new Error('scheme');
      } catch {
        errors.org_website = 'Enter a full website address, for example https://example.com.';
      }
    }
    if (!form.elements.namedItem('consent').checked) errors.consent = 'Please accept the terms and privacy policy to continue.';
    return errors;
  };

  const setBusy = (busy) => {
    form.setAttribute('aria-busy', String(busy));
    [...form.elements].forEach((field) => { field.disabled = busy; });
    if (busy) showStatus('loading');
  };

  const safeCheckoutUrl = (raw) => {
    try {
      const url = new URL(raw);
      if (url.protocol === 'https:') return url.href;
      if (loopbackApi && url.protocol === 'http:' && url.hostname === '127.0.0.1') return url.href;
    } catch {
      return null;
    }
    return null;
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    hideStatuses();

    const errors = localErrors();
    if (Object.keys(errors).length) {
      showErrors(errors, 'Correct the highlighted fields and submit again.');
      return;
    }

    const body = { package: plan, consent: true, ref_check: value('ref_check') };
    fields.forEach((name) => {
      const v = name === 'phone' ? phoneValue.value : value(name);
      if (v !== '') body[name] = name === 'email' ? v.toLowerCase() : v;
    });

    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), 20000);
    setBusy(true);
    let redirected = false;
    try {
      const response = await fetch(`${apiBase}/api/public/v1/checkout`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body),
        credentials: 'omit',
        cache: 'no-store',
        referrerPolicy: 'no-referrer',
        signal: controller.signal
      });
      let payload = null;
      try {
        payload = await response.json();
      } catch {
        payload = null;
      }

      if (response.status === 201 && payload && typeof payload.checkout_url === 'string') {
        const target = safeCheckoutUrl(payload.checkout_url);
        if (target) {
          redirected = true;
          window.location.assign(target);
          return;
        }
        showStatus('error');
      } else if (response.status === 422 && payload && payload.errors && typeof payload.errors === 'object') {
        setBusy(false);
        showErrors(payload.errors, typeof payload.message === 'string' ? payload.message : null);
      } else if (response.status === 429) {
        const seconds = Number(response.headers.get('Retry-After')) || Number(payload?.retry_after) || 600;
        const minutes = Math.max(1, Math.ceil(seconds / 60));
        showStatus('rate', `Try again in ${minutes} ${minutes === 1 ? 'minute' : 'minutes'}.`);
      } else {
        showStatus('error');
      }
    } catch {
      showStatus('error');
    } finally {
      window.clearTimeout(timer);
      if (!redirected) {
        form.setAttribute('aria-busy', 'false');
        [...form.elements].forEach((field) => { field.disabled = false; });
      }
    }
  });
})();
