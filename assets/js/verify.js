(() => {
  const tool = document.querySelector('[data-verify-tool]');
  const parser = window.EcertVerifyPayload;
  if (!tool || !parser) return;

  const DEFAULT_API_BASE = 'https://apps.e-certificate.com.my';
  const API_PATH = '/api/public/v1/certificates/verify';
  const LOOKUP_TIMEOUT_MS = 10000;
  const FRAME_INTERVAL_MS = 120;
  const CAMERA_MAX_SIDE = 720;
  const UPLOAD_MAX_SIDE = 1600;
  const UPLOAD_MAX_BYTES = 10 * 1024 * 1024;
  const FIELDS = [
    ['awarded_to', 'Awarded to'],
    ['event', 'Event'],
    ['held_on', 'Held on'],
    ['location', 'Location'],
    ['issued_by', 'Issued by'],
    ['issued_on', 'Issued on'],
    ['certificate_number', 'Certificate number'],
  ];
  const COPY = {
    scanReady: 'Start the camera and point it at the QR code on the certificate.',
    scanStarting: 'Opening the camera…',
    scanning: 'Point the camera at the QR code on the certificate.',
    scanStopped: 'Camera stopped. Start it again or upload a photo of the QR code.',
    reading: 'Reading the QR code…',
    readOk: 'QR code read. Checking the certificate…',
    denied: 'Camera access was blocked. Allow camera access for this site in your browser settings, or upload a photo of the QR code instead.',
    noCamera: "We couldn't find a camera on this device. Upload a photo of the QR code or enter the certificate number.",
    inUse: 'Your camera is being used by another app. Close it and try again, or upload a photo of the QR code.',
    unsupported: "This browser can't open the camera. Upload a photo of the QR code or enter the certificate number.",
    insecure: 'The camera only works over a secure https connection. Upload a photo of the QR code or enter the certificate number.',
    unreadable: "We couldn't read a QR code in that image. Try a sharper, well-lit photo with the whole code in view.",
    foreign: 'This QR code is not an e-Certificate verification code.',
    malformed: 'Enter the number exactly as printed, for example CERT-20260101093000-A1B2C3.',
    unavailable: "The verification service isn't responding right now. Please try again in a few minutes.",
    decoderFailed: "QR scanning couldn't start. Enter the certificate number instead.",
    checking: 'Checking the certificate…',
  };

  const apiBaseAttribute = tool.dataset.apiBase || '';
  const apiBase = /^https?:\/\/[A-Za-z0-9.-]+(?::\d{1,5})?$/.test(apiBaseAttribute) ? apiBaseAttribute : DEFAULT_API_BASE;
  const jsqrAttribute = tool.dataset.jsqrSrc || '';
  const jsqrSrc = jsqrAttribute.startsWith('/') && !jsqrAttribute.startsWith('//') ? jsqrAttribute : null;

  const modeList = tool.querySelector('[data-verify-modes]');
  const modeButtons = [...tool.querySelectorAll('[data-verify-mode]')];
  const panels = [...tool.querySelectorAll('[data-verify-panel]')];
  const viewfinder = tool.querySelector('[data-viewfinder]');
  const video = tool.querySelector('[data-camera-video]');
  const startButton = tool.querySelector('[data-camera-start]');
  const stopButton = tool.querySelector('[data-camera-stop]');
  const scanStatus = tool.querySelector('[data-scan-status]');
  const upload = tool.querySelector('[data-qr-upload]');
  const form = tool.querySelector('[data-verify-form]');
  const numberInput = tool.querySelector('[data-number-input]');
  const numberError = tool.querySelector('[data-number-error]');
  const submitButton = tool.querySelector('[data-verify-submit]');
  const result = document.querySelector('[data-verify-result]');
  const templates = Object.fromEntries(
    [...document.querySelectorAll('template[data-result-template]')].map((template) => [template.dataset.resultTemplate, template])
  );

  let stream = null;
  let frameRequest = 0;
  let lastFrameTime = 0;
  let frameBusy = false;
  let cameraSession = 0;
  let lookupController = null;
  let lookupSession = 0;
  let detectorPromise = null;
  let jsqrPromise = null;
  const canvas = document.createElement('canvas');
  const context = canvas.getContext('2d', { willReadFrequently: true });

  // ----- Status and field messages -------------------------------------------------

  const setScanStatus = (text, tone = 'info') => {
    scanStatus.textContent = text;
    scanStatus.dataset.tone = tone;
  };

  const clearNumberError = () => {
    numberError.hidden = true;
    numberError.textContent = '';
    numberInput.removeAttribute('aria-invalid');
  };

  const showNumberError = (focus = true) => {
    numberError.textContent = COPY.malformed;
    numberError.hidden = false;
    numberInput.setAttribute('aria-invalid', 'true');
    if (focus) numberInput.focus();
  };

  // ----- Mode switch (tablist, enhanced like the pricing tabs) ----------------------

  document.documentElement.classList.add('verify-enhanced');
  modeList.setAttribute('role', 'tablist');
  modeList.setAttribute('aria-label', 'Verification method');
  modeButtons.forEach((button) => {
    const panel = panels.find((item) => item.dataset.verifyPanel === button.dataset.verifyMode);
    button.setAttribute('role', 'tab');
    if (panel) button.setAttribute('aria-controls', panel.id);
  });
  panels.forEach((panel) => {
    const button = modeButtons.find((item) => item.dataset.verifyMode === panel.dataset.verifyPanel);
    panel.setAttribute('role', 'tabpanel');
    if (button) panel.setAttribute('aria-labelledby', button.id);
  });

  const selectMode = (mode) => {
    const selectedMode = mode === 'manual' ? 'manual' : 'scan';
    if (selectedMode !== 'scan') stopCamera();

    modeButtons.forEach((button) => {
      const selected = button.dataset.verifyMode === selectedMode;
      button.setAttribute('aria-selected', String(selected));
      button.tabIndex = selected ? 0 : -1;
      button.classList.toggle('is-selected', selected);
    });
    panels.forEach((panel) => {
      panel.hidden = panel.dataset.verifyPanel !== selectedMode;
    });
  };

  modeButtons.forEach((button, index) => {
    button.addEventListener('click', () => selectMode(button.dataset.verifyMode));
    button.addEventListener('keydown', (event) => {
      let nextIndex = null;
      if (event.key === 'ArrowRight') nextIndex = (index + 1) % modeButtons.length;
      if (event.key === 'ArrowLeft') nextIndex = (index - 1 + modeButtons.length) % modeButtons.length;
      if (event.key === 'Home') nextIndex = 0;
      if (event.key === 'End') nextIndex = modeButtons.length - 1;
      if (nextIndex === null) return;

      event.preventDefault();
      modeButtons[nextIndex].focus();
      selectMode(modeButtons[nextIndex].dataset.verifyMode);
    });
  });

  // ----- QR decoding (BarcodeDetector first, vendored jsQR otherwise) ---------------

  const getDetector = () => {
    if (!detectorPromise) {
      detectorPromise = (async () => {
        if (typeof window.BarcodeDetector !== 'function') return null;
        try {
          const formats = await window.BarcodeDetector.getSupportedFormats();
          return formats.includes('qr_code') ? new window.BarcodeDetector({ formats: ['qr_code'] }) : null;
        } catch {
          return null;
        }
      })();
    }
    return detectorPromise;
  };

  const loadJsQr = () => {
    if (typeof window.jsQR === 'function') return Promise.resolve(window.jsQR);
    if (!jsqrSrc) return Promise.reject(new Error('No decoder source'));
    if (!jsqrPromise) {
      jsqrPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = jsqrSrc;
        script.async = true;
        script.addEventListener('load', () => (typeof window.jsQR === 'function' ? resolve(window.jsQR) : reject(new Error('Decoder missing'))));
        script.addEventListener('error', () => reject(new Error('Decoder failed to load')));
        document.head.append(script);
      }).catch((error) => {
        jsqrPromise = null;
        throw error;
      });
    }
    return jsqrPromise;
  };

  // Resolves to a decode(canvas, inversionAttempts) function, or rejects when
  // no decoder can be started.
  const getDecoder = async () => {
    const detector = await getDetector();
    if (detector) {
      return async () => {
        const codes = await detector.detect(canvas);
        return codes.length > 0 && typeof codes[0].rawValue === 'string' ? codes[0].rawValue : null;
      };
    }
    const jsQR = await loadJsQr();
    return async (inversionAttempts) => {
      const image = context.getImageData(0, 0, canvas.width, canvas.height);
      const code = jsQR(image.data, image.width, image.height, { inversionAttempts });
      return code && typeof code.data === 'string' && code.data !== '' ? code.data : null;
    };
  };

  const drawScaled = (source, width, height, maxSide) => {
    const scale = Math.min(1, maxSide / Math.max(width, height));
    canvas.width = Math.max(1, Math.round(width * scale));
    canvas.height = Math.max(1, Math.round(height * scale));
    context.drawImage(source, 0, 0, canvas.width, canvas.height);
  };

  // ----- Camera lifecycle ----------------------------------------------------------

  const setCameraState = (state) => {
    viewfinder.dataset.cameraState = state;
    const active = state === 'starting' || state === 'scanning';
    startButton.hidden = active;
    stopButton.hidden = !active;
  };

  function stopCamera() {
    cameraSession += 1;
    if (frameRequest) cancelAnimationFrame(frameRequest);
    frameRequest = 0;
    frameBusy = false;
    const hadCamera = stream !== null || viewfinder.dataset.cameraState === 'starting';
    if (stream) stream.getTracks().forEach((track) => track.stop());
    stream = null;
    video.pause();
    video.srcObject = null;
    if (hadCamera) setCameraState('stopped');
    return hadCamera;
  }

  const cameraErrorMessage = (error) => {
    const name = error && typeof error.name === 'string' ? error.name : '';
    if (name === 'NotAllowedError' || name === 'SecurityError') return COPY.denied;
    if (name === 'NotFoundError' || name === 'OverconstrainedError') return COPY.noCamera;
    if (name === 'NotReadableError') return COPY.inUse;
    return COPY.unsupported;
  };

  const scanFrame = (now) => {
    if (!stream) return;
    frameRequest = requestAnimationFrame(scanFrame);
    if (frameBusy || now - lastFrameTime < FRAME_INTERVAL_MS || video.readyState < 2 || !video.videoWidth) return;

    lastFrameTime = now;
    frameBusy = true;
    const session = cameraSession;
    drawScaled(video, video.videoWidth, video.videoHeight, CAMERA_MAX_SIDE);
    decodeFrame('dontInvert')
      .then((text) => {
        if (session !== cameraSession) return;
        frameBusy = false;
        if (text === null) return;
        // Stop every track before the payload is even looked at.
        stopCamera();
        handleDecodedText(text);
      })
      .catch(() => {
        if (session === cameraSession) frameBusy = false;
      });
  };

  let decodeFrame = async () => null;

  const startCamera = async () => {
    if (!window.isSecureContext) {
      setScanStatus(COPY.insecure, 'error');
      return;
    }
    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
      setScanStatus(COPY.unsupported, 'error');
      return;
    }

    stopCamera();
    const session = cameraSession;
    setCameraState('starting');
    setScanStatus(COPY.scanStarting);

    let decoder;
    try {
      decoder = await getDecoder();
    } catch {
      if (session !== cameraSession) return;
      setCameraState('stopped');
      setScanStatus(COPY.decoderFailed, 'error');
      return;
    }
    if (session !== cameraSession) return;

    let mediaStream;
    try {
      mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
    } catch (error) {
      if (session !== cameraSession) return;
      setCameraState('stopped');
      setScanStatus(cameraErrorMessage(error), 'error');
      return;
    }

    // The visitor stopped, switched mode or left while the prompt was open.
    if (session !== cameraSession) {
      mediaStream.getTracks().forEach((track) => track.stop());
      return;
    }

    stream = mediaStream;
    decodeFrame = decoder;
    video.srcObject = mediaStream;
    try {
      await video.play();
    } catch {
      // Autoplay of a muted inline video is allowed; frames are read regardless.
    }
    if (session !== cameraSession) return;

    setCameraState('scanning');
    setScanStatus(COPY.scanning);
    lastFrameTime = 0;
    frameRequest = requestAnimationFrame(scanFrame);
  };

  startButton.addEventListener('click', () => { startCamera(); });
  stopButton.addEventListener('click', () => {
    if (stopCamera()) setScanStatus(COPY.scanStopped);
  });
  window.addEventListener('pagehide', () => { stopCamera(); });
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden' && stopCamera()) setScanStatus(COPY.scanStopped);
  });

  // ----- Image upload ------------------------------------------------------------

  const loadImage = (file) => new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();
    image.addEventListener('load', () => {
      URL.revokeObjectURL(objectUrl);
      resolve(image);
    });
    image.addEventListener('error', () => {
      URL.revokeObjectURL(objectUrl);
      reject(new Error('Unreadable image'));
    });
    image.src = objectUrl;
  });

  upload.addEventListener('change', async () => {
    const file = upload.files && upload.files[0];
    upload.value = '';
    if (!file) return;

    stopCamera();
    if (!file.type.startsWith('image/') || file.size > UPLOAD_MAX_BYTES) {
      setScanStatus(COPY.unreadable, 'error');
      return;
    }

    setScanStatus(COPY.reading);
    let decoder;
    try {
      decoder = await getDecoder();
    } catch {
      setScanStatus(COPY.decoderFailed, 'error');
      return;
    }

    let text = null;
    try {
      const image = await loadImage(file);
      drawScaled(image, image.naturalWidth, image.naturalHeight, UPLOAD_MAX_SIDE);
      text = await decoder('attemptBoth');
    } catch {
      text = null;
    }

    if (text === null) {
      setScanStatus(COPY.unreadable, 'error');
      return;
    }
    handleDecodedText(text);
  });

  // ----- Decoded text: parse only, never navigate, open or fetch it ---------------

  function handleDecodedText(text) {
    const parsed = parser.parseQrPayload(text);
    if (!parsed) {
      setScanStatus(COPY.foreign, 'error');
      return;
    }
    setScanStatus(COPY.readOk);
    lookup(parsed);
  }

  // ----- Result rendering (textContent only) -------------------------------------

  const renderCard = (name) => {
    const template = templates[name];
    if (!template) return null;
    const card = template.content.firstElementChild.cloneNode(true);
    result.replaceChildren(card);
    result.hidden = false;
    return card;
  };

  const focusHeading = (card) => {
    card.querySelector('[data-result-heading]')?.focus();
  };

  const renderValid = (certificate) => {
    const card = renderCard('valid');
    if (!card) return;
    const list = card.querySelector('[data-result-fields]');
    FIELDS.forEach(([key, label]) => {
      const value = certificate[key];
      if (typeof value !== 'string' || value.trim() === '') return;
      const row = document.createElement('div');
      const term = document.createElement('dt');
      const detail = document.createElement('dd');
      term.textContent = label;
      detail.textContent = value;
      row.append(term, detail);
      list.append(row);
    });
    focusHeading(card);
  };

  const renderNotFound = (number) => {
    const card = renderCard('not_found');
    if (!card) return;
    if (number) {
      card.querySelector('[data-result-number]').textContent = number;
      card.querySelector('[data-result-checked]').hidden = false;
    }
    focusHeading(card);
  };

  const renderMessage = (heading, text, tone = 'notice', focus = true) => {
    const card = renderCard('message');
    if (!card) return;
    card.dataset.tone = tone;
    if (tone === 'loading') card.querySelector('.verify-seal').textContent = '…';
    card.querySelector('[data-result-heading]').textContent = heading;
    card.querySelector('[data-result-text]').textContent = text;
    if (focus) focusHeading(card);
  };

  const rateLimitText = (header) => {
    const seconds = /^\d{1,4}$/.test((header || '').trim()) ? Number(header.trim()) : 0;
    if (seconds < 1) return 'Too many checks in a short time. Please wait a minute and try again.';
    return `Too many checks in a short time. Please wait ${seconds} ${seconds === 1 ? 'second' : 'seconds'} and try again.`;
  };

  // ----- Lookup ------------------------------------------------------------------

  const setBusy = (busy) => {
    result.setAttribute('aria-busy', String(busy));
    submitButton.disabled = busy;
  };

  async function lookup(identifier) {
    lookupController?.abort();
    const controller = new AbortController();
    lookupController = controller;
    const session = ++lookupSession;
    const timer = window.setTimeout(() => controller.abort(), LOOKUP_TIMEOUT_MS);

    const byNumber = identifier.kind === 'number';
    const parameters = byNumber
      ? `?number=${encodeURIComponent(identifier.number)}`
      : `?qr=${encodeURIComponent(identifier.qr)}`;
    window.history.replaceState({}, '', byNumber
      ? `${window.location.pathname}?number=${encodeURIComponent(identifier.number)}`
      : window.location.pathname);

    setBusy(true);
    renderMessage('Checking certificate', COPY.checking, 'loading', false);

    let response = null;
    let payload = null;
    try {
      response = await fetch(new URL(API_PATH, apiBase) + parameters, {
        method: 'GET',
        mode: 'cors',
        credentials: 'omit',
        cache: 'no-store',
        referrerPolicy: 'no-referrer',
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });
      if (response.status === 200) payload = await response.json();
    } catch {
      response = null;
    } finally {
      window.clearTimeout(timer);
    }

    if (session !== lookupSession) return;
    lookupController = null;
    setBusy(false);

    if (response && response.status === 200 && payload && typeof payload === 'object') {
      if (payload.status === 'valid' && payload.certificate && typeof payload.certificate === 'object' && !Array.isArray(payload.certificate)) {
        renderValid(payload.certificate);
        return;
      }
      if (payload.status === 'not_found') {
        renderNotFound(byNumber ? identifier.number : null);
        return;
      }
    }
    if (response && response.status === 422) {
      renderMessage('Check the certificate number', COPY.malformed);
      return;
    }
    if (response && response.status === 429) {
      renderMessage('Please wait a moment', rateLimitText(response.headers.get('Retry-After')));
      return;
    }
    renderMessage('Verification is unavailable', COPY.unavailable, 'error');
  }

  // ----- Manual entry ------------------------------------------------------------

  numberInput.addEventListener('input', clearNumberError);
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const number = parser.normaliseNumber(numberInput.value);
    if (!number) {
      showNumberError();
      return;
    }
    clearNumberError();
    numberInput.value = number;
    lookup({ kind: 'number', number });
  });

  // ----- Initial state and deep link ---------------------------------------------

  setCameraState('idle');
  setScanStatus(COPY.scanReady);

  const deepLink = parser.readDeepLink(window.location.search);
  if (deepLink === null) {
    selectMode('scan');
  } else if (deepLink.kind === 'number') {
    selectMode('manual');
    numberInput.value = deepLink.number;
    lookup(deepLink);
  } else if (deepLink.kind === 'qr') {
    selectMode('scan');
    lookup(deepLink);
  } else {
    selectMode('manual');
    numberInput.value = deepLink.raw.slice(0, 40);
    showNumberError(false);
  }
})();
