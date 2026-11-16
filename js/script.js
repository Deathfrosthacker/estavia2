/* Estavia landing page scripts */
(function () {
  'use strict';

  // Mobile nav toggle
  var toggle = document.getElementById('nav-toggle');
  var nav = document.getElementById('main-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open);
    });
    nav.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        nav.classList.remove('open');
        toggle.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Sticky header shadow on scroll
  var header = document.querySelector('.site-header');
  window.addEventListener('scroll', function () {
    if (window.scrollY > 40) {
      header.style.boxShadow = '0 6px 20px rgba(0,0,0,.4)';
    } else {
      header.style.boxShadow = 'none';
    }
  }, { passive: true });

  // Reveal-on-scroll animation
  var revealTargets = document.querySelectorAll(
    '.intro-item, .service-card, .why-copy, .why-media, .gallery-grid figure, .contact-copy, .contact-form, .process-step, .sector-card, .faq-item, .faq-copy'
  );
  revealTargets.forEach(function (el) { el.classList.add('reveal'); });

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealTargets.forEach(function (el) { observer.observe(el); });
  } else {
    revealTargets.forEach(function (el) { el.classList.add('visible'); });
  }

  var carousel = document.getElementById('gallery-carousel');
  if (carousel) {
    var viewport = document.getElementById('gallery-viewport');
    var track = document.getElementById('gallery-track');
    var pauseButton = document.getElementById('gallery-toggle');
    var previousButton = document.getElementById('gallery-prev');
    var nextButton = document.getElementById('gallery-next');
    var slides = Array.prototype.slice.call(track.querySelectorAll('figure'));
    var slideIndex = 0;
    var paused = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var interval = null;

    function slideStep() {
      var gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
      return slides[0].getBoundingClientRect().width + gap;
    }

    function setPosition(animate) {
      track.style.transition = animate ? '' : 'none';
      track.style.transform = 'translateX(-' + Math.round(slideIndex * slideStep()) + 'px)';
      if (!animate) {
        track.offsetWidth;
        track.style.transition = '';
      }
    }

    function setupCarousel() {
      Array.prototype.slice.call(track.querySelectorAll('[data-gallery-clone]')).forEach(function (clone) {
        clone.parentNode.removeChild(clone);
      });
      var gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
      var slideWidth = slides[0].getBoundingClientRect().width;
      var visibleCount = Math.max(1, Math.round((viewport.clientWidth + gap) / (slideWidth + gap)));
      slides.slice(0, visibleCount).forEach(function (slide) {
        var clone = slide.cloneNode(true);
        clone.setAttribute('data-gallery-clone', '');
        clone.setAttribute('aria-hidden', 'true');
        clone.classList.remove('reveal', 'visible');
        track.appendChild(clone);
      });
      slideIndex = 0;
      setPosition(false);
    }

    function moveNext() {
      if (slideIndex >= slides.length) {
        slideIndex = 0;
        setPosition(false);
      }
      slideIndex += 1;
      setPosition(true);
      if (slideIndex === slides.length) {
        window.setTimeout(function () {
          if (slideIndex === slides.length) {
            slideIndex = 0;
            setPosition(false);
          }
        }, 700);
      }
    }

    function movePrevious() {
      if (slideIndex === 0 || slideIndex === slides.length) {
        slideIndex = slides.length;
        setPosition(false);
        window.requestAnimationFrame(function () {
          slideIndex = slides.length - 1;
          setPosition(true);
        });
      } else {
        slideIndex -= 1;
        setPosition(true);
      }
    }

    function updateInterval() {
      if (interval) window.clearInterval(interval);
      interval = null;
      if (!paused) interval = window.setInterval(moveNext, 4200);
    }

    function updatePauseButton() {
      pauseButton.textContent = paused ? 'Play' : 'Pause';
      pauseButton.setAttribute('aria-pressed', paused ? 'true' : 'false');
      pauseButton.setAttribute('aria-label', paused ? 'Play automatic slideshow' : 'Pause automatic slideshow');
    }

    previousButton.addEventListener('click', function () {
      movePrevious();
      updateInterval();
    });
    nextButton.addEventListener('click', function () {
      moveNext();
      updateInterval();
    });
    pauseButton.addEventListener('click', function () {
      paused = !paused;
      updatePauseButton();
      updateInterval();
    });
    window.addEventListener('resize', function () {
      window.requestAnimationFrame(setupCarousel);
    });

    setupCarousel();
    updatePauseButton();
    updateInterval();
  }

  // Contact form -> saves to the Estavia database (api/quote.php).
  // Falls back to WhatsApp if the server is unreachable.
  var form = document.getElementById('quote-form');
  var note = document.getElementById('form-note');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      note.className = 'form-note';
      note.textContent = 'Sending…';
      var submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (result.data.ok) {
            note.className = 'form-note success';
            note.textContent = result.data.message || 'Thank you! Your request has been received.';
            form.reset();
          } else {
            note.className = 'form-note error';
            note.textContent = result.data.error || 'Something went wrong. Please try again or call us.';
          }
        })
        .catch(function () {
          // Network failure — fall back to WhatsApp with the same details
          var data = new FormData(form);
          var text =
            'Hello Estavia,%0A%0AI would like a quote for: ' + encodeURIComponent(data.get('service')) +
            '%0AName: ' + encodeURIComponent(data.get('name')) +
            '%0APhone: ' + encodeURIComponent(data.get('phone')) +
            (data.get('email') ? '%0AEmail: ' + encodeURIComponent(data.get('email')) : '') +
            (data.get('message') ? '%0ADetails: ' + encodeURIComponent(data.get('message')) : '');
          window.open('https://wa.me/254750856829?text=' + text, '_blank');
          note.className = 'form-note';
          note.textContent = 'Opening WhatsApp with your request — press send there to complete it.';
          form.reset();
        })
        .finally(function () {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  // Animated stat counters
  var counters = document.querySelectorAll('.counter');
  if (counters.length && 'IntersectionObserver' in window) {
    var counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        counterObserver.unobserve(el);
        var target = parseInt(el.getAttribute('data-target'), 10) || 0;
        var duration = 1400;
        var start = null;
        function tick(ts) {
          if (!start) start = ts;
          var p = Math.min((ts - start) / duration, 1);
          el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))); // ease-out cubic
          if (p < 1) window.requestAnimationFrame(tick);
        }
        window.requestAnimationFrame(tick);
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { counterObserver.observe(el); });
  } else {
    counters.forEach(function (el) { el.textContent = el.getAttribute('data-target'); });
  }

  // Footer year
  var year = document.getElementById('year');
  if (year) { year.textContent = new Date().getFullYear(); }
})();
