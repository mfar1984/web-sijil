(() => {
  // Parses what a visitor scans or types into one certificate identifier.
  // It only ever extracts a certificate number or the app's encrypted QR
  // payload; it never navigates to, opens or fetches decoded text.
  const NUMBER_PATTERN = /^CERT-\d{14}-[0-9A-F]{6}$/;
  const QR_PATTERN = /^eyJpdiI6[A-Za-z0-9+/]+={0,2}$/;
  const QR_MAX_LENGTH = 4096;
  const APP_HOST = 'apps.e-certificate.com.my';
  const SITE_HOSTS = ['www.e-certificate.com.my', 'e-certificate.com.my'];
  const SITE_PARAMETERS = ['number', 'certificate_number'];
  const DEEP_LINK_PARAMETERS = ['number', 'certificate_number', 'qr_data'];
  // https, a plain host (no userinfo, port, backslash or whitespace), a path, no fragment.
  const URL_SHAPE = /^https:\/\/[A-Za-z0-9.-]+\/[^#\s\\]*$/;

  const normaliseNumber = (value) => {
    if (typeof value !== 'string') return null;
    const number = value.trim().toUpperCase();
    return NUMBER_PATTERN.test(number) ? number : null;
  };

  const normaliseQr = (value) => {
    if (typeof value !== 'string') return null;
    const qr = value.trim();
    return qr.length <= QR_MAX_LENGTH && QR_PATTERN.test(qr) ? qr : null;
  };

  const parseVerificationUrl = (value) => {
    if (!URL_SHAPE.test(value)) return null;

    let url;
    try {
      url = new URL(value);
    } catch {
      return null;
    }

    if (url.protocol !== 'https:' || url.username !== '' || url.password !== '' || url.port !== '' || url.hash !== '') {
      return null;
    }

    // Reject anything the URL parser rewrote (dot segments, encodings), so the
    // accepted text is exactly the shape the app issues.
    const rawPath = value.slice(value.indexOf('/', 'https://'.length));
    if (rawPath !== url.pathname + url.search) return null;

    if (url.hostname === APP_HOST) {
      if (value.includes('?')) return null;
      const match = /^\/verify\/([^/]+)$/.exec(url.pathname);
      const number = match ? normaliseNumber(match[1]) : null;
      return number ? { kind: 'number', number } : null;
    }

    if (SITE_HOSTS.includes(url.hostname)) {
      if (url.pathname !== '/verify') return null;
      const entries = [...url.searchParams];
      if (entries.length !== 1 || !SITE_PARAMETERS.includes(entries[0][0])) return null;
      const number = normaliseNumber(entries[0][1]);
      return number ? { kind: 'number', number } : null;
    }

    return null;
  };

  const parseQrPayload = (text) => {
    if (typeof text !== 'string') return null;
    const value = text.trim();
    if (value === '' || value.length > QR_MAX_LENGTH) return null;

    const number = normaliseNumber(value);
    if (number) return { kind: 'number', number };

    const qr = normaliseQr(value);
    if (qr) return { kind: 'qr', qr };

    return parseVerificationUrl(value);
  };

  const readDeepLink = (search) => {
    const parameters = new URLSearchParams(typeof search === 'string' ? search : '');

    for (const name of DEEP_LINK_PARAMETERS) {
      const values = parameters.getAll(name);
      if (values.length === 0) continue;

      const raw = values[0];
      if (values.length > 1) return { kind: 'invalid', raw };

      if (name === 'qr_data') {
        // Base64 never contains spaces, so a space here is an unencoded "+"
        // that URLSearchParams decoded; restore it before validating.
        const qr = normaliseQr(raw.replace(/ /g, '+'));
        return qr ? { kind: 'qr', qr } : { kind: 'invalid', raw };
      }

      const number = normaliseNumber(raw);
      return number ? { kind: 'number', number } : { kind: 'invalid', raw };
    }

    return null;
  };

  window.EcertVerifyPayload = Object.freeze({ NUMBER_PATTERN, normaliseNumber, parseQrPayload, readDeepLink });
})();
