(() => {
  const header = document.querySelector('[data-header]');
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.primary-nav');
  const year = document.querySelector('[data-year]');
  let lastFocused = null;

  if (year) year.textContent = new Date().getFullYear();

  const setMenu = (open) => {
    if (!toggle || !nav) return;
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    document.body.classList.toggle('menu-open', open);
    if (open) {
      lastFocused = document.activeElement;
      nav.querySelector('a')?.focus();
    } else if (lastFocused === toggle) {
      toggle.focus();
    }
  };

  toggle?.addEventListener('click', () => {
    setMenu(toggle.getAttribute('aria-expanded') !== 'true');
  });

  nav?.addEventListener('click', (event) => {
    if (event.target.closest('a') && window.innerWidth < 900) setMenu(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
      lastFocused = toggle;
      setMenu(false);
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth >= 900 && toggle?.getAttribute('aria-expanded') === 'true') setMenu(false);
  });

  let headerRafPending = false;
  const updateHeader = () => {
    if (headerRafPending) return;
    headerRafPending = true;
    requestAnimationFrame(() => {
      headerRafPending = false;
      header?.classList.toggle('scrolled', window.scrollY > 12);
    });
  };
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  const carousel = document.querySelector('[data-hero-carousel]');

  if (carousel) {
    const slides = [...carousel.querySelectorAll('[data-hero-slide]')];
    const dots = [...carousel.querySelectorAll('[data-hero-to]')];
    const previous = carousel.querySelector('[data-hero-prev]');
    const next = carousel.querySelector('[data-hero-next]');
    const playback = carousel.querySelector('[data-hero-toggle]');
    const playbackIcon = carousel.querySelector('[data-pause-icon]');
    const playbackLabel = playback?.querySelector('.control-label');
    const current = carousel.querySelector('[data-hero-current]');
    const status = carousel.querySelector('[data-hero-status]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const autoplayDelay = 7000;
    let activeIndex = 0;
    let timer = null;
    let playing = !reducedMotion.matches;
    let touchStartX = null;
    let touchStartY = null;

    const stopTimer = () => {
      window.clearTimeout(timer);
      timer = null;
    };

    const syncPlaybackControl = () => {
      if (!playback) return;
      const motionDisabled = reducedMotion.matches;
      if (motionDisabled) playing = false;
      playback.disabled = motionDisabled;
      playback.setAttribute('aria-pressed', String(!playing));
      playback.setAttribute('aria-label', motionDisabled
        ? 'Hero slideshow autoplay disabled by reduced motion preference'
        : playing ? 'Pause hero slideshow' : 'Play hero slideshow');
      if (playbackIcon) playbackIcon.textContent = playing ? 'Ⅱ' : '▶';
      if (playbackLabel) playbackLabel.textContent = playing ? 'Pause' : 'Play';
    };

    const schedule = () => {
      stopTimer();
      if (!playing || reducedMotion.matches || document.hidden) return;
      timer = window.setTimeout(() => showSlide(activeIndex + 1, 'auto'), autoplayDelay);
    };

    const pauseForManualInput = () => {
      playing = false;
      stopTimer();
      syncPlaybackControl();
    };

    const showSlide = (requestedIndex, source = 'manual') => {
      activeIndex = (requestedIndex + slides.length) % slides.length;
      slides.forEach((slide, index) => {
        const active = index === activeIndex;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', String(!active));
        if ('inert' in slide) slide.inert = !active;
      });
      dots.forEach((dot, index) => {
        const active = index === activeIndex;
        dot.classList.toggle('is-active', active);
        if (active) dot.setAttribute('aria-current', 'true');
        else dot.removeAttribute('aria-current');
      });
      if (current) current.textContent = String(activeIndex + 1).padStart(2, '0');
      if (status) {
        status.setAttribute('aria-live', source === 'auto' ? 'off' : 'polite');
        status.textContent = `Slide ${activeIndex + 1} of ${slides.length}: ${slides[activeIndex].getAttribute('aria-label').replace(/^\d+ of \d+: /, '')}`;
      }
      schedule();
    };

    const manualShow = (index) => {
      pauseForManualInput();
      showSlide(index, 'manual');
    };

    previous?.addEventListener('click', () => manualShow(activeIndex - 1));
    next?.addEventListener('click', () => manualShow(activeIndex + 1));
    dots.forEach((dot, index) => dot.addEventListener('click', () => manualShow(index)));

    playback?.addEventListener('click', () => {
      if (reducedMotion.matches) return;
      playing = !playing;
      syncPlaybackControl();
      schedule();
    });

    carousel.addEventListener('keydown', (event) => {
      if (event.target !== carousel) return;
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        manualShow(activeIndex - 1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        manualShow(activeIndex + 1);
      }
    });

    carousel.addEventListener('touchstart', (event) => {
      touchStartX = event.changedTouches[0].clientX;
      touchStartY = event.changedTouches[0].clientY;
    }, { passive: true });

    carousel.addEventListener('touchend', (event) => {
      if (touchStartX === null || touchStartY === null) return;
      const deltaX = event.changedTouches[0].clientX - touchStartX;
      const deltaY = event.changedTouches[0].clientY - touchStartY;
      touchStartX = null;
      touchStartY = null;
      if (Math.abs(deltaX) > 42 && Math.abs(deltaX) > Math.abs(deltaY) * 1.2) {
        manualShow(activeIndex + (deltaX < 0 ? 1 : -1));
      }
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stopTimer();
      else schedule();
    });

    const handleMotionChange = () => {
      playing = !reducedMotion.matches;
      syncPlaybackControl();
      schedule();
    };
    if (typeof reducedMotion.addEventListener === 'function') reducedMotion.addEventListener('change', handleMotionChange);
    else reducedMotion.addListener(handleMotionChange);

    syncPlaybackControl();
    showSlide(0, 'initial');
  }
})();
