(() => {
  const form = document.querySelector('[data-contact-form]');
  if (!form) return;
  form.noValidate = true;

  const summary = document.querySelector('[data-plan-summary]');
  const typeSelect = form.querySelector('[data-plan-type-select]');
  const durationSelect = form.querySelector('[data-plan-duration-select]');
  const typeOutput = summary?.querySelector('[data-plan-type]');
  const durationOutput = summary?.querySelector('[data-plan-duration]');
  const priceOutput = summary?.querySelector('[data-plan-price]');
  const submitButton = form.querySelector('[data-submit-button]');
  const message = form.elements.namedItem('message');
  const messageCount = form.querySelector('[data-message-count]');
  const statuses = Object.fromEntries(
    [...form.querySelectorAll('[data-form-status]')].map((status) => [status.dataset.formStatus, status])
  );
  const allowedFields = [
    'full_name', 'email', 'organisation', 'phone', 'enquiry_type',
    'type', 'duration', 'message', 'consent', 'ref_check'
  ];
  const allowedTypes = ['normal', 'government'];
  const allowedDurations = ['1', '3', '6', '12'];
  let planRequest = null;

  const hideStatuses = () => {
    Object.values(statuses).forEach((status) => { status.hidden = true; });
  };

  const showStatus = (name, focus = false) => {
    hideStatuses();
    const status = statuses[name];
    if (!status) return;
    status.hidden = false;
    if (focus) status.focus();
  };

  const clearErrors = () => {
    form.querySelectorAll('[data-error-for]').forEach((error) => {
      error.hidden = true;
      error.textContent = '';
    });
    [...form.elements].forEach((field) => field.removeAttribute?.('aria-invalid'));
  };

  const showValidation = (errors = {}) => {
    let firstInvalid = null;
    Object.entries(errors).forEach(([name, text]) => {
      if (!allowedFields.includes(name) || typeof text !== 'string') return;
      const field = form.elements.namedItem(name);
      const error = form.querySelector(`[data-error-for="${name}"]`);
      if (!field || !error) return;
      field.setAttribute('aria-invalid', 'true');
      error.textContent = text;
      error.hidden = false;
      if (!firstInvalid) firstInvalid = field;
    });
    showStatus('validation');
    (firstInvalid || statuses.validation)?.focus();
  };

  const setBusy = (busy) => {
    form.setAttribute('aria-busy', String(busy));
    submitButton.disabled = busy;
    [...form.elements].forEach((field) => {
      if (field !== submitButton) field.disabled = busy;
    });
    if (busy) showStatus('loading');
  };

  const clearPlanSummary = () => {
    if (summary) summary.hidden = true;
  };

  const syncPlan = (updateAddress = true) => {
    const type = typeSelect.value;
    const duration = durationSelect.value;
    const valid = allowedTypes.includes(type) && allowedDurations.includes(duration);
    planRequest?.abort();
    clearPlanSummary();

    if (updateAddress) {
      const address = new URL(window.location.href);
      address.search = '';
      if (valid) {
        address.searchParams.set('type', type);
        address.searchParams.set('duration', duration);
      }
      window.history.replaceState({}, '', `${address.pathname}${address.search}${address.hash}`);
    }

    if (!valid || !summary) return;

    planRequest = new AbortController();
    const request = new URL('/api/plan.php', window.location.href);
    request.searchParams.set('type', type);
    request.searchParams.set('duration', duration);

    fetch(request, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      signal: planRequest.signal
    })
      .then((response) => response.ok ? response.json() : Promise.reject(new Error('Invalid plan response')))
      .then((payload) => {
        const plan = payload.plan;
        if (!payload.success || !plan || plan.type !== type || String(plan.duration) !== duration
          || !Number.isInteger(plan.price_cents) || typeof plan.price_display !== 'string'
          || !/^RM\d{1,6}(\.\d{2})?$/.test(plan.price_display)) {
          throw new Error('Invalid plan record');
        }
        typeOutput.textContent = plan.type === 'government' ? 'Government' : 'Normal';
        durationOutput.textContent = plan.duration === 12 ? '1 year' : `${plan.duration} ${plan.duration === 1 ? 'month' : 'months'}`;
        priceOutput.textContent = plan.price_display;
        summary.hidden = false;
      })
      .catch((error) => {
        if (error.name !== 'AbortError') clearPlanSummary();
      });
  };

  const parameters = new URLSearchParams(window.location.search);
  const parameterKeys = [...parameters.keys()];
  const queryType = parameters.get('type');
  const queryDuration = parameters.get('duration');
  const validQuery = parameterKeys.length === 2
    && new Set(parameterKeys).size === 2
    && parameterKeys.every((key) => key === 'type' || key === 'duration')
    && parameters.getAll('type').length === 1
    && parameters.getAll('duration').length === 1
    && allowedTypes.includes(queryType)
    && allowedDurations.includes(queryDuration);

  if (validQuery) {
    typeSelect.value = queryType;
    durationSelect.value = queryDuration;
    syncPlan(false);
  } else if (window.location.search) {
    window.history.replaceState({}, '', window.location.pathname + window.location.hash);
  }

  typeSelect.addEventListener('change', syncPlan);
  durationSelect.addEventListener('change', syncPlan);
  message.addEventListener('input', () => { messageCount.textContent = String(message.value.length); });

  form.addEventListener('invalid', (event) => {
    event.target.setAttribute('aria-invalid', 'true');
  }, true);

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    hideStatuses();

    if (!form.checkValidity()) {
      const errors = {};
      [...form.elements].forEach((field) => {
        if (field.name && !field.validity?.valid) errors[field.name] = field.validationMessage;
      });
      showValidation(errors);
      return;
    }

    const body = new URLSearchParams();
    allowedFields.forEach((name) => {
      const field = form.elements.namedItem(name);
      if (!field) return;
      if (field.type === 'checkbox') body.set(name, field.checked ? field.value : '');
      else body.set(name, field.value);
    });

    setBusy(true);
    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        credentials: 'same-origin',
        body: body.toString()
      });
      let payload = null;
      try {
        payload = await response.json();
      } catch {
        payload = null;
      }

      if (response.ok && payload?.success === true) {
        form.reset();
        messageCount.textContent = '0';
        clearPlanSummary();
        window.history.replaceState({}, '', window.location.pathname + window.location.hash);
        showStatus('success', true);
      } else if (response.status === 422 && payload?.errors && typeof payload.errors === 'object') {
        showValidation(payload.errors);
      } else if (response.status === 429) {
        showStatus('rate', true);
      } else {
        showStatus('error', true);
      }
    } catch {
      showStatus('error', true);
    } finally {
      setBusy(false);
    }
  });
})();
