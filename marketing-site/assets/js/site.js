(function () {
  'use strict';

  const nav = document.querySelector('.nav');
  const toggle = document.querySelector('.nav__toggle');

  if (nav) {
    const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 24);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      nav.classList.toggle('is-open');
      const open = nav.classList.contains('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.querySelectorAll('.faq-q').forEach((btn) => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const open = item.classList.contains('is-open');
      item.closest('.faq')?.querySelectorAll('.faq-item').forEach((i) => i.classList.remove('is-open'));
      if (!open) item.classList.add('is-open');
      btn.setAttribute('aria-expanded', (!open).toString());
    });
  });

  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  const form = document.getElementById('contactForm');
  if (form) {
    const status = document.getElementById('formStatus');
    const params = new URLSearchParams(window.location.search);
    const topic = params.get('topic');
    const topicSelect = form.querySelector('[name="topic_select"]');
    const topicInput = form.querySelector('[name="topic"]');
    if (topic && topicSelect?.querySelector(`option[value="${topic}"]`)) {
      topicSelect.value = topic;
      if (topicInput) topicInput.value = topic;
    }

    topicSelect?.addEventListener('change', () => {
      if (topicInput) topicInput.value = topicSelect.value;
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!status) return;

      status.className = 'form-status';
      status.textContent = '';

      const data = new FormData(form);
      const btn = form.querySelector('[type="submit"]');
      if (btn) btn.disabled = true;

      try {
        const res = await fetch('/contact/send.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.ok) {
          form.reset();
          status.className = 'form-status form-status--ok is-visible';
          status.textContent = 'Message sent. We reply within one business day.';
        } else {
          status.className = 'form-status form-status--err is-visible';
          status.textContent = json.message || 'Something went wrong. Email support@reviveguard.com directly.';
        }
      } catch {
        status.className = 'form-status form-status--err is-visible';
        status.textContent = 'Network error. Email support@reviveguard.com directly.';
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  }
})();
