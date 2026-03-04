/* ═══════════════════════════════════════════════════════
   GÓTICA SU LDA — main.js
   
   Includes:
   ✔ Formspree AJAX form submission (budget + contact)
   ✔ Header scroll effect
   ✔ Mobile navigation
   ✔ Smooth scroll
   ✔ Counter animations
   ✔ Scroll reveal
   ✔ Gallery filter + lightbox
   ✔ Budget modal
   ✔ Toast notifications
═══════════════════════════════════════════════════════ */

'use strict';

/* ────────────────────────────────────────────────────
   FORMSPREE CONFIGURATION
   
   1. Cria conta gratuita em https://formspree.io
   2. Clica em "New Form"
   3. Dá o teu email como destino
   4. Copia o endpoint (ex: https://formspree.io/f/xabc1234)
   5. Cola nos campos abaixo — um para cada formulário
      (ou usa o mesmo endpoint para ambos)
   
   As mensagens chegam ao teu email em segundos.
──────────────────────────────────────────────────── */
const FORMSPREE = {
  // Endpoint para o formulário de ORÇAMENTO
  budget:  'https://formspree.io/f/YOUR_FORM_ID',

  // Endpoint para o formulário de CONTACTO
  // (pode ser o mesmo que budget, ou um formulário separado)
  contact: 'https://formspree.io/f/YOUR_FORM_ID',
};


/* ════════════════════════════════════════════════════
   UTILITIES
════════════════════════════════════════════════════ */

/** Query selector shorthand */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

/** Easing function for counters */
function easeOutQuart(t) {
  return 1 - Math.pow(1 - t, 4);
}


/* ════════════════════════════════════════════════════
   HEADER — scroll effect
════════════════════════════════════════════════════ */
(function initHeader() {
  const hdr = document.getElementById('hdr');
  if (!hdr) return;

  const onScroll = () => {
    hdr.classList.toggle('scrolled', window.scrollY > 30);
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // run once on load
})();


/* ════════════════════════════════════════════════════
   SMOOTH SCROLL — for all anchor links
════════════════════════════════════════════════════ */
(function initSmoothScroll() {
  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href^="#"]');
    if (!link) return;

    const target = document.querySelector(link.getAttribute('href'));
    if (!target) return;

    e.preventDefault();
    window.scrollTo({
      top: target.offsetTop - 68, // header height offset
      behavior: 'smooth',
    });
  });
})();


/* ════════════════════════════════════════════════════
   MOBILE NAVIGATION
════════════════════════════════════════════════════ */
(function initMobileNav() {
  const ham    = document.getElementById('ham');
  const mobNav = document.getElementById('mob-nav');
  if (!ham || !mobNav) return;

  const open  = () => { ham.classList.add('open');    mobNav.classList.add('open');    ham.setAttribute('aria-expanded', 'true');  mobNav.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; };
  const close = () => { ham.classList.remove('open'); mobNav.classList.remove('open'); ham.setAttribute('aria-expanded', 'false'); mobNav.setAttribute('aria-hidden', 'true');  document.body.style.overflow = ''; };
  const toggle = () => mobNav.classList.contains('open') ? close() : open();

  ham.addEventListener('click', toggle);

  // Close on link/button click inside mobile nav
  mobNav.addEventListener('click', (e) => {
    if (e.target.matches('a, button')) close();
  });

  // Close on ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });
})();


/* ════════════════════════════════════════════════════
   COUNTER ANIMATION
════════════════════════════════════════════════════ */
(function initCounters() {
  const counters = $$('[data-count]');
  if (!counters.length) return;

  const DURATION = 1800; // ms

  function animateCounter(el) {
    if (el.dataset.animated) return;
    el.dataset.animated = 'true';

    const target = parseInt(el.dataset.count, 10);
    const start  = performance.now();

    function step(now) {
      const elapsed  = now - start;
      const progress = Math.min(elapsed / DURATION, 1);
      el.textContent = Math.round(easeOutQuart(progress) * target);

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = target;
      }
    }

    requestAnimationFrame(step);
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) animateCounter(entry.target);
      });
    },
    { threshold: 0.4 }
  );

  counters.forEach((el) => io.observe(el));
})();


/* ════════════════════════════════════════════════════
   SCROLL REVEAL
════════════════════════════════════════════════════ */
(function initScrollReveal() {
  const items = $$('.fade-up, .fade-in');
  if (!items.length) return;

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          io.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -36px 0px' }
  );

  items.forEach((el) => io.observe(el));
})();


/* ════════════════════════════════════════════════════
   GALLERY FILTER
════════════════════════════════════════════════════ */
(function initGalleryFilter() {
  const btns  = $$('.gf-btn');
  const items = $$('.gal-item');
  const grid  = document.getElementById('galGrid');
  if (!btns.length || !items.length) return;

  btns.forEach((btn) => {
    btn.addEventListener('click', () => {
      // Update active button
      btns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.dataset.f;

      // Show / hide items
      items.forEach((item) => {
        const match = filter === 'todos' || item.dataset.cat === filter;
        item.style.display = match ? 'block' : 'none';
      });

      // Reset horizontal scroll on mobile
      if (grid) grid.scrollLeft = 0;
    });
  });
})();


/* ════════════════════════════════════════════════════
   LIGHTBOX
════════════════════════════════════════════════════ */
(function initLightbox() {
  const lb    = document.getElementById('lightbox');
  const lbImg = document.getElementById('lbImg');
  const lbCap = document.getElementById('lbCap');
  const lbClose = document.getElementById('lbClose');
  if (!lb) return;

  function openLb(src, caption) {
    lbImg.src = src;
    lbImg.alt = caption || '';
    if (lbCap) lbCap.textContent = caption || '';
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLb() {
    lb.classList.remove('open');
    document.body.style.overflow = '';
    // Clear src after transition to avoid flash
    setTimeout(() => { lbImg.src = ''; }, 350);
  }

  // Open on gallery item click
  $$('.gal-item').forEach((item) => {
    item.addEventListener('click', () => {
      const img     = item.querySelector('img');
      const caption = item.querySelector('h4')?.textContent || '';
      if (img) openLb(img.src, caption);
    });
  });

  if (lbClose) lbClose.addEventListener('click', closeLb);

  // Close on backdrop click
  lb.addEventListener('click', (e) => {
    if (e.target === lb || e.target.closest('.lb-content') === null) closeLb();
  });

  // Close on ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && lb.classList.contains('open')) closeLb();
  });
})();


/* ════════════════════════════════════════════════════
   BUDGET MODAL
════════════════════════════════════════════════════ */
(function initBudgetModal() {
  const overlay    = document.getElementById('budgetOverlay');
  const closeBtn   = document.getElementById('modalClose');
  const triggerIds = ['triggerModal', 'triggerModal2', 'triggerModal3', 'triggerModal5'];
  if (!overlay) return;

  function openModal() {
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  // Attach triggers
  triggerIds.forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('click', openModal);
  });

  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  // Close on backdrop click
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal();
  });

  // Close on ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
  });
})();


/* ════════════════════════════════════════════════════
   TOAST NOTIFICATION
════════════════════════════════════════════════════ */
const Toast = (function () {
  const el      = document.getElementById('toast');
  const iconEl  = document.getElementById('toastIcon');
  const titleEl = document.getElementById('toastTitle');
  const msgEl   = document.getElementById('toastMsg');
  let timer;

  /**
   * @param {string} title   — Bold heading
   * @param {string} msg     — Subtitle
   * @param {'success'|'error'} type
   */
  function show(title, msg, type = 'success') {
    if (!el) return;

    clearTimeout(timer);

    titleEl.textContent = title;
    msgEl.textContent   = msg;

    const isError = type === 'error';
    el.style.borderColor = isError ? '#c0392b' : 'var(--green)';
    if (iconEl) {
      iconEl.className     = isError
        ? 'fas fa-exclamation-circle toast-icon'
        : 'fas fa-check-circle toast-icon';
      iconEl.style.color   = isError ? '#c0392b' : 'var(--green)';
    }

    el.classList.add('show');
    timer = setTimeout(() => el.classList.remove('show'), 5500);
  }

  return { show };
})();


/* ════════════════════════════════════════════════════
   FORMSPREE AJAX SUBMISSION
   
   Handles both forms:
   ✔ #budgetForm  — Pedido de Orçamento
   ✔ #contactForm — Mensagem de Contacto
   
   How Formspree works:
   - POST form data as JSON to the endpoint
   - Returns { ok: true } on success
   - No page reload needed (AJAX)
   - Anti-spam via honeypot field (_gotcha)
   - Custom subject via hidden field (_subject)
════════════════════════════════════════════════════ */

/**
 * Submit a form to Formspree via fetch (AJAX)
 * @param {HTMLFormElement} form
 * @param {string} endpoint — Formspree URL
 * @returns {Promise<void>}
 */
async function submitToFormspree(form, endpoint) {
  const data = new FormData(form);

  const response = await fetch(endpoint, {
    method:  'POST',
    body:    data,
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    // Formspree returns error details in JSON
    const json = await response.json().catch(() => ({}));
    const msg  = json?.errors?.map((e) => e.message).join(', ') || 'Erro desconhecido';
    throw new Error(msg);
  }

  return response.json();
}

/**
 * Generic form handler factory
 * @param {object} opts
 */
function createFormHandler({
  formId,
  submitBtnId,
  statusId,
  endpoint,
  successTitle,
  successMsg,
  afterSuccess,
}) {
  const form   = document.getElementById(formId);
  const btn    = document.getElementById(submitBtnId);
  const status = document.getElementById(statusId);
  if (!form || !btn) return;

  // Helper to show inline status
  function setStatus(type, msg) {
    if (!status) return;
    status.className     = `form-status ${type}`;
    status.innerHTML     = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i><span>${msg}</span>`;
    status.style.display = 'flex';
  }

  function clearStatus() {
    if (!status) return;
    status.style.display = 'none';
    status.className = 'form-status';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Basic HTML5 validation
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    // Loading state
    btn.classList.add('loading');
    btn.disabled = true;
    clearStatus();

    try {
      await submitToFormspree(form, endpoint);

      // Success
      setStatus('success', successMsg);
      Toast.show(successTitle, successMsg, 'success');
      form.reset();

      if (typeof afterSuccess === 'function') {
        afterSuccess();
      }
    } catch (err) {
      console.error(`[${formId}] Formspree error:`, err);

      const errMsg = 'Erro ao enviar. Por favor tente novamente ou contacte-nos directamente.';
      setStatus('error', errMsg);
      Toast.show('Erro ao enviar', errMsg, 'error');
    } finally {
      btn.classList.remove('loading');
      btn.disabled = false;
    }
  });
}


/* ── Budget form ── */
createFormHandler({
  formId:       'budgetForm',
  submitBtnId:  'budgetSubmit',
  statusId:     'modalStatus',
  endpoint:     FORMSPREE.budget,
  successTitle: 'Pedido enviado!',
  successMsg:   'Recebemos o seu pedido. Entramos em contacto em até 24 horas.',
  afterSuccess: () => {
    // Close modal after short delay so user sees the success message
    setTimeout(() => {
      const overlay = document.getElementById('budgetOverlay');
      if (overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }
    }, 2800);
  },
});


/* ── Contact form ── */
createFormHandler({
  formId:       'contactForm',
  submitBtnId:  'ctxSubmit',
  statusId:     'ctxStatus',
  endpoint:     FORMSPREE.contact,
  successTitle: 'Mensagem enviada!',
  successMsg:   'Recebemos a sua mensagem. Respondemos o mais brevemente possível.',
});
