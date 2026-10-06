(() => {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ——— Mobile nav ——— */
  const toggle = document.querySelector('[data-nav-toggle]');
  const mobile = document.querySelector('[data-mobile-nav]');
  const closeBtn = document.querySelector('[data-nav-close]');

  const setNavOpen = (open) => {
    if (!toggle || !mobile) return;
    mobile.classList.toggle('is-open', open);
    toggle.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    mobile.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.style.overflow = open ? 'hidden' : '';
  };

  if (toggle && mobile) {
    toggle.addEventListener('click', () => setNavOpen(!mobile.classList.contains('is-open')));
    closeBtn?.addEventListener('click', () => setNavOpen(false));
    mobile.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => setNavOpen(false));
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') setNavOpen(false);
    });
  }

  /* ——— Hero cinematic slideshow ——— */
  const slides = [...document.querySelectorAll('[data-hero-slide]')];
  const dots = [...document.querySelectorAll('[data-hero-dot]')];
  let slideIndex = 0;
  let slideTimer = null;
  const INTERVAL = 5500;

  const goSlide = (index) => {
    if (slides.length < 2) return;
    slideIndex = (index + slides.length) % slides.length;
    slides.forEach((el, i) => el.classList.toggle('is-active', i === slideIndex));
    dots.forEach((el, i) => {
      el.classList.remove('is-active');
      // restart progress fill
      void el.offsetWidth;
      if (i === slideIndex) el.classList.add('is-active');
    });
  };

  const startSlideshow = () => {
    if (reduceMotion || slides.length < 2) return;
    stopSlideshow();
    slideTimer = window.setInterval(() => goSlide(slideIndex + 1), INTERVAL);
  };

  const stopSlideshow = () => {
    if (slideTimer) window.clearInterval(slideTimer);
    slideTimer = null;
  };

  dots.forEach((dot) => {
    dot.addEventListener('click', () => {
      const i = Number(dot.getAttribute('data-hero-dot') || 0);
      goSlide(i);
      startSlideshow();
    });
  });

  if (slides.length > 1) {
    goSlide(0);
    startSlideshow();
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stopSlideshow();
      else startSlideshow();
    });
  }

  /* ——— Scroll reveals ——— */
  const revealEls = document.querySelectorAll('[data-reveal]');
  if (reduceMotion) {
    revealEls.forEach((el) => el.classList.add('is-in'));
  } else if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
          }
        });
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
    );
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('is-in'));
  }

  /* ——— Donate form ——— */
  const donateForm = document.querySelector('[data-donate-form]');
  if (donateForm) {
    const amountInput = donateForm.querySelector('#amount');
    const amountError = donateForm.querySelector('#amount-error');
    const minAmount = Number(donateForm.getAttribute('data-min-amount') || 1000);
    const minMessage = `Please enter at least ₦${minAmount.toLocaleString('en-NG')} to continue to payment.`;

    const setAmountError = (show) => {
      if (!amountInput || !amountError) return;
      if (show) {
        amountError.textContent = minMessage;
        amountError.hidden = false;
        amountInput.classList.add('is-invalid');
        amountInput.setAttribute('aria-invalid', 'true');
        amountInput.focus();
      } else {
        amountError.textContent = '';
        amountError.hidden = true;
        amountInput.classList.remove('is-invalid');
        amountInput.setAttribute('aria-invalid', 'false');
      }
    };

    const amountValue = () => Number.parseFloat(String(amountInput?.value ?? '').trim());

    amountInput?.addEventListener('input', () => {
      if (amountValue() >= minAmount) setAmountError(false);
    });

    donateForm.querySelectorAll('[data-amount-chip]').forEach((chip) => {
      chip.addEventListener('click', () => {
        const amount = chip.getAttribute('data-amount-chip');
        document.querySelectorAll('[data-amount-chip]').forEach((c) => c.classList.remove('is-active'));
        chip.classList.add('is-active');
        if (amountInput && amount) {
          amountInput.value = amount;
          setAmountError(false);
        }
      });
    });

    donateForm.addEventListener('submit', (e) => {
      const amount = amountValue();
      if (!Number.isFinite(amount) || amount < minAmount) {
        e.preventDefault();
        setAmountError(true);
      }
    });
  }

  /* ——— Testimonials marquee ——— */
  const marquee = document.querySelector('[data-voice-marquee]');
  if (marquee) {
    const pause = () => marquee.classList.add('is-paused');
    const resume = () => {
      if (!reduceMotion) marquee.classList.remove('is-paused');
    };
    if (reduceMotion) {
      pause();
    } else {
      marquee.addEventListener('mouseenter', pause);
      marquee.addEventListener('mouseleave', resume);
      marquee.addEventListener('focusin', pause);
      marquee.addEventListener('focusout', (e) => {
        if (!marquee.contains(e.relatedTarget)) resume();
      });
      marquee.addEventListener('touchstart', pause, { passive: true });
      marquee.addEventListener('touchend', resume, { passive: true });
    }
  }

  /* Ensure every CTA has the nested arrow */
  document.querySelectorAll('.btn').forEach((btn) => {
    if (btn.querySelector('.btn-icon')) return;
    const icon = document.createElement('span');
    icon.className = 'btn-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '↗';
    btn.appendChild(icon);
  });
})();
