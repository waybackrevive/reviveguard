// ReviveGuard Marketing Site — Main JS
// Pure vanilla JS, no libraries. <3KB.

(function () {
  'use strict';

  // ── Nav scroll effect ─────────────────────────────────────
  var nav = document.querySelector('.nav');
  if (nav) {
    function onScroll() {
      nav.classList.toggle('scrolled', window.scrollY > 20);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ── Mobile hamburger ──────────────────────────────────────
  var hamburger = document.querySelector('.nav__hamburger');
  var mobileMenu = document.querySelector('.nav__mobile');
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function () {
      var open = mobileMenu.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    // Close on link click
    mobileMenu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        mobileMenu.classList.remove('open');
      });
    });
  }

  // ── FAQ accordion ─────────────────────────────────────────
  document.querySelectorAll('.faq-question').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      var isOpen = item.classList.contains('open');
      // Close all
      document.querySelectorAll('.faq-item.open').forEach(function (el) {
        el.classList.remove('open');
      });
      if (!isOpen) item.classList.add('open');
    });
  });

  // ── Scroll fade-up animations ─────────────────────────────
  var fadeEls = document.querySelectorAll('.fade-up');
  if ('IntersectionObserver' in window && fadeEls.length) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    fadeEls.forEach(function (el) { observer.observe(el); });
  } else {
    // Fallback: show all
    fadeEls.forEach(function (el) { el.classList.add('visible'); });
  }

  // ── Smooth counter animation ──────────────────────────────
  function animateCount(el) {
    var target = parseInt(el.getAttribute('data-target'), 10);
    var duration = 1600;
    var start = performance.now();
    function step(now) {
      var progress = Math.min((now - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target).toLocaleString();
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  var counters = document.querySelectorAll('[data-target]');
  if (counters.length && 'IntersectionObserver' in window) {
    var counterObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          counterObs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    counters.forEach(function (c) { counterObs.observe(c); });
  }

  // ── Pricing CTA links — scroll to pricing on same page ──────
  document.querySelectorAll('a[href="#pricing"]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var pricingSection = document.getElementById('pricing');
      if (pricingSection) {
        e.preventDefault();
        pricingSection.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // ── How It Works path switcher ────────────────────────────
  var hiwBtns = document.querySelectorAll('.hiw-path-btn');
  if (hiwBtns.length) {
    hiwBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var path = btn.getAttribute('data-path');
        // Update buttons
        hiwBtns.forEach(function (b) {
          b.classList.toggle('active', b === btn);
          b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
        });
        // Show correct journey
        document.querySelectorAll('.journey-content').forEach(function (panel) {
          panel.classList.toggle('active', panel.id === 'path-' + path);
        });
      });
    });
  }

})();
