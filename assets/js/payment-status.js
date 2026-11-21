(() => {
  const root = document.querySelector('[data-payment-status]');
  if (!root) return;

  const apiBase = root.dataset.apiBase || '';
  const reference = root.dataset.reference || '';
  const hinted = root.dataset.result === 'failed' || root.dataset.result === 'cancelled';
  const el = (name) => root.querySelector(`[data-status-${name}]`);
  const heading = el('heading');
  const message = el('message');
  const spinner = el('spinner');
  const details = el('details');
  const retry = el('retry');
  const pricing = el('pricing');
  const signin = el('signin');
  const testBadge = el('test');
  const POLL_MS = 4000;
  const MAX_POLLS = 30;
  let polls = 0;
  let timer = null;
  let shown = '';

  const show = (state, title, text, payload = null) => {
    root.dataset.state = state;
    const changed = shown !== `${state}|${title}`;
    shown = `${state}|${title}`;
    heading.textContent = title;
    message.textContent = text;
    spinner.hidden = state !== 'pending';
    retry.hidden = state !== 'stalled';
    pricing.hidden = !['failed', 'cancelled', 'expired'].includes(state);
    // Paid leaves only Sign in and Contact us: checking again or paying again
    // makes no sense once the money is in.
    signin.hidden = state !== 'paid';
    if (payload && typeof payload.reference === 'string') {
      el('package').textContent = typeof payload.package_name === 'string' ? payload.package_name : '';
      el('amount').textContent = typeof payload.amount_display === 'string' ? payload.amount_display : '';
      el('reference').textContent = payload.reference;
      details.hidden = false;
      testBadge.hidden = payload.is_test !== true;
    }
    if (changed && state !== 'pending') heading.focus();
  };

  const render = (payload) => {
    switch (payload.status) {
      case 'paid':
        if (payload.account === 'renewal') {
          const until = typeof payload.expires_on === 'string' ? ` until ${payload.expires_on}` : '';
          show('paid', 'Payment received', `Your package is active${until}. You can sign in now.`, payload);
        } else {
          show('paid', 'Payment received', 'Check your inbox at the address you used: we sent a link to set your password and open your account.', payload);
        }
        return true;
      case 'review':
        show('review', 'Payment received', 'We are checking this payment and will contact you by email.', payload);
        return true;
      case 'failed':
        show('failed', 'Payment not completed', 'The payment could not be completed. You can choose a plan and try again.', payload);
        return true;
      case 'cancelled':
        show('cancelled', 'Payment cancelled', 'This order was cancelled, so no payment was taken. You can choose a plan on Pricing and start again.', payload);
        return true;
      case 'expired':
        show('expired', 'Payment not completed in time', 'This order was not paid before its deadline, so the payment link is closed. You can choose a plan on Pricing and start again.', payload);
        return true;
      default:
        show('pending', hinted ? 'Payment not completed' : 'Checking your payment…',
          hinted ? 'We are confirming the result with CHIP.' : 'Waiting for confirmation from CHIP. This usually takes a few seconds.', payload);
        return false;
    }
  };

  const schedule = () => {
    if (polls >= MAX_POLLS) {
      show('stalled', 'Still processing', 'We will email you once the payment is confirmed. You can also check again.');
      return;
    }
    timer = window.setTimeout(poll, POLL_MS);
  };

  async function poll() {
    polls += 1;
    try {
      const response = await fetch(`${apiBase}/api/public/v1/checkout/${encodeURIComponent(reference)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'omit',
        cache: 'no-store',
        referrerPolicy: 'no-referrer'
      });
      if (response.status === 404) {
        show('missing', 'We could not find this payment', 'Check the link in your browser, or contact us with the time of payment.');
        return;
      }
      const payload = response.ok ? await response.json() : null;
      if (payload && typeof payload.status === 'string' && render(payload)) return;
    } catch {
      // Network trouble: keep polling until the limit.
    }
    schedule();
  }

  retry.addEventListener('click', () => {
    window.clearTimeout(timer);
    polls = 0;
    show('pending', 'Checking your payment…', 'Waiting for confirmation from CHIP.');
    poll();
  });

  poll();
})();
