/* LemboShop — shared UI behaviour. Progressive enhancement only: every
   feature here has a working server-side fallback. */
(() => {
  'use strict';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const csrf = () => $('meta[name="csrf-token"]')?.content || '';
  const baseUrl = ($('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');

  /* ---------- fetch helper (adds CSRF + JSON handling) ---------- */
  async function api(path, { method = 'GET', data = null } = {}) {
    const opts = {
      method,
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      credentials: 'same-origin',
    };
    if (method !== 'GET') {
      opts.headers['X-CSRF-Token'] = csrf();
      opts.body = data instanceof FormData ? data : new URLSearchParams(data || {});
    }
    let res;
    try {
      res = await fetch(baseUrl + path.replace(/^\//, ''), opts);
    } catch {
      throw new Error('Network error. Check your connection and try again.');
    }
    let json = null;
    try { json = await res.json(); } catch { /* non-JSON response */ }
    if (!res.ok || !json || json.ok === false) {
      const err = new Error(json?.error || 'Something went wrong. Please try again.');
      err.status = res.status;
      err.payload = json;
      throw err;
    }
    return json;
  }

  /* ---------- Toasts ---------- */
  const toastIcons = { success: 'check-circle', error: 'alert', info: 'info' };
  const iconSprite = $('meta[name="icon-sprite"]')?.content || '';

  function toast(message, type = 'success', timeout = 4500) {
    const region = $('[data-toast-region]');
    if (!region) return;
    const el = document.createElement('div');
    el.className = `toast toast--${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `<svg class="icon" aria-hidden="true"><use href="${iconSprite}#i-${toastIcons[type] || 'info'}"></use></svg>
      <p></p><button type="button" aria-label="Dismiss" data-toast-dismiss>
      <svg class="icon icon--sm" aria-hidden="true"><use href="${iconSprite}#i-x"></use></svg></button>`;
    $('p', el).textContent = message; // textContent: never inject HTML from messages
    region.appendChild(el);
    if (timeout) setTimeout(() => dismissToast(el), timeout);
  }
  function dismissToast(el) {
    if (!el.isConnected) return;
    el.classList.add('is-leaving');
    el.addEventListener('animationend', () => el.remove(), { once: true });
  }
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-toast-dismiss]');
    if (btn) dismissToast(btn.closest('.toast'));
  });
  // Auto-dismiss server-rendered flash toasts (errors stay longer)
  $$('[data-toast-region] .toast').forEach((el) => {
    setTimeout(() => dismissToast(el), el.classList.contains('toast--error') ? 8000 : 5000);
  });

  /* ---------- Focus trap for drawer/overlays ---------- */
  function trapFocus(container, e) {
    const focusables = $$('a[href], button:not([disabled]), input:not([disabled]), select, textarea, summary, [tabindex]:not([tabindex="-1"])', container)
      .filter((el) => el.offsetParent !== null);
    if (!focusables.length) return;
    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }

  function makeOverlay(root, { openSel, closeSel, openClass = 'is-open', onOpen }) {
    if (!root) return;
    let lastFocus = null;
    const openers = $$(openSel);
    const open = () => {
      lastFocus = document.activeElement;
      root.classList.add(openClass);
      root.setAttribute('aria-hidden', 'false');
      openers.forEach((b) => b.setAttribute('aria-expanded', 'true'));
      document.body.classList.add('no-scroll');
      onOpen ? onOpen() : $('a, button, input', root)?.focus();
    };
    const close = () => {
      root.classList.remove(openClass);
      root.setAttribute('aria-hidden', 'true');
      openers.forEach((b) => b.setAttribute('aria-expanded', 'false'));
      document.body.classList.remove('no-scroll');
      lastFocus?.focus();
    };
    openers.forEach((b) => b.addEventListener('click', open));
    $$(closeSel, root).forEach((b) => b.addEventListener('click', close));
    root.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
      if (e.key === 'Tab') trapFocus(root, e);
    });
    return { open, close };
  }

  makeOverlay($('[data-drawer]'), { openSel: '[data-drawer-open]', closeSel: '[data-drawer-close]' });
  makeOverlay($('[data-search-overlay]'), {
    openSel: '[data-search-open]',
    closeSel: '[data-search-close]',
    onOpen: () => $('#overlay-q')?.focus(),
  });

  /* ---------- Dropdowns ---------- */
  $$('[data-dropdown]').forEach((dd) => {
    const toggle = $('[data-dropdown-toggle]', dd);
    const setOpen = (open) => {
      dd.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', String(open));
    };
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      setOpen(!dd.classList.contains('is-open'));
      if (dd.classList.contains('is-open')) $('[role="menuitem"]', dd)?.focus();
    });
    dd.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { setOpen(false); toggle.focus(); }
    });
    document.addEventListener('click', (e) => { if (!dd.contains(e.target)) setOpen(false); });
  });

  /* ---------- Password visibility + strength ---------- */
  $$('[data-toggle-password]').forEach((btn) => {
    const input = $('#' + btn.getAttribute('aria-controls'));
    btn.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', String(show));
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      $('use', btn)?.setAttribute('href', `${iconSprite}#i-${show ? 'eye-off' : 'eye'}`);
    });
  });
  $$('[data-strength-for]').forEach((meter) => {
    const input = $('#' + meter.dataset.strengthFor);
    const label = $('[data-strength-label]', meter.parentElement);
    const words = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    input?.addEventListener('input', () => {
      const v = input.value;
      let score = 0;
      if (v.length >= 8) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v) || v.length >= 14) score++;
      if (!v) score = 0;
      meter.dataset.score = score;
      if (label) label.textContent = v ? `Password strength: ${words[score] || 'Weak'}` : '';
    });
  });

  /* ---------- Forms: prevent double submit + confirm dialogs ---------- */
  const dialog = $('#confirm-dialog');
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.confirm && !form.dataset.confirmed && dialog?.showModal) {
      e.preventDefault();
      $('#confirm-title', dialog).textContent = form.dataset.confirmTitle || 'Are you sure?';
      $('#confirm-text', dialog).textContent = form.dataset.confirm;
      $('[data-confirm-ok]', dialog).textContent = form.dataset.confirmLabel || 'Confirm';
      dialog.returnValue = '';
      dialog.showModal();
      dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'confirm') {
          form.dataset.confirmed = '1';
          form.requestSubmit ? form.requestSubmit() : form.submit();
        }
      }, { once: true });
      return;
    }
    if (form.method.toLowerCase() === 'post' && !form.hasAttribute('data-ajax')) {
      const btn = $('button[type="submit"], button:not([type])', form);
      if (form.dataset.submitting) { e.preventDefault(); return; }
      form.dataset.submitting = '1';
      btn?.classList.add('is-loading');
    }
  });
  // Restore buttons when navigating back (bfcache)
  window.addEventListener('pageshow', () => {
    $$('form[data-submitting]').forEach((f) => {
      delete f.dataset.submitting;
      delete f.dataset.confirmed;
      $$('.is-loading', f).forEach((b) => b.classList.remove('is-loading'));
    });
  });

  /* ---------- Newsletter (AJAX with normal-POST fallback) ---------- */
  $$('[data-newsletter]').forEach((form) => {
    form.setAttribute('data-ajax', '');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('button', form);
      btn.classList.add('is-loading');
      try {
        const res = await api('api/newsletter.php', { method: 'POST', data: new FormData(form) });
        toast(res.message, 'success');
        form.reset();
      } catch (err) {
        toast(err.message, 'error');
      } finally {
        btn.classList.remove('is-loading');
      }
    });
  });

  window.Lembo = { api, toast, $, $$, baseUrl, iconSprite };
})();
