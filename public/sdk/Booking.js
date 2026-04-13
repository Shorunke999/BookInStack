/**
 * BookInStack SDK — booking.js
 * Version: 1.1.0
 *
 * Supports three booking modes: appointment | ticket | reservation
 * Mode is fetched from the API on Booking.init() and drives the widget UI automatically.
 * No config changes needed in your integration — it just works.
 *
 * Usage:
 *   await Booking.init({ publicKey: 'pk_live_xxxxx', returnUrl: '/success' })
 *   const booking = await Booking.create({ amount, description, customer_email })
 *   await Booking.pay(booking.reference)
 */

;(function (global, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    global.Booking = factory();
  }
}(typeof window !== 'undefined' ? window : this, function () {

  'use strict';

  // ─── Configuration ────────────────────────────────────────────────────────────

  const DEFAULT_CONFIG = {
    baseUrl:   'https://api.bookinstack.dev/api',
    version:   '1.1.0',
    timeout:   30000,
    returnUrl: null,
    onSuccess: null,
    onError:   null,
  };

  let _config        = { ...DEFAULT_CONFIG };
  let _initialized   = false;
  let _modeConfig    = null;
  let _bookingOpen   = true;
  let _bookingReason = null;
  let _catalog       = [];   // BookingCategory[] from API
  let _widgetConfig      = {};   // appearance from developer's CMS settings
  let _reservationUnit   = 'night'; // 'night' | 'day'
  let _enableNegotiate   = false;   // show negotiate button on widget
  let _whatsappNumber    = '';      // developer WhatsApp number

  // ─── Mode defaults (fallback when API is unreachable) ─────────────────────────

  const MODE_DEFAULTS = {
    appointment: {
      mode:                    'appointment',
      label:                   'Appointment',
      plural:                  'Appointments',
      cta:                     'Book Appointment',
      amount_label:            'Service Fee (₦)',
      description_label:       'Service',
      description_placeholder: 'e.g. Haircut & Styling',
      success_message:         'Appointment booked!',
      success_sub:             'Your appointment is confirmed.',
      supports_quantity:       false,
      supports_dates:          true,
      supports_time:           true,
      attendance_label:        'Attended',
    },
    ticket: {
      mode:                    'ticket',
      label:                   'Ticket',
      plural:                  'Tickets',
      cta:                     'Buy Ticket',
      amount_label:            'Ticket Price',
      description_label:       'Event Name',
      description_placeholder: 'e.g. Annual Tech Conference',
      success_message:         'Ticket confirmed!',
      success_sub:             'Your ticket has been issued.',
      supports_quantity:       true,
      supports_dates:          false,
      supports_time:           false,
      attendance_label:        'Checked In',
    },
    reservation: {
      mode:                    'reservation',
      label:                   'Reservation',
      plural:                  'Reservations',
      cta:                     'Reserve Now',
      amount_label:            'Rate per Night (₦)',
      description_label:       'Room / Space',
      description_placeholder: 'e.g. Deluxe Suite',
      success_message:         'Reservation confirmed!',
      success_sub:             'Your reservation is secured.',
      supports_quantity:       false,
      supports_dates:          true,
      supports_time:           false,
      attendance_label:        'Checked Out',
    },
  };

  // ─── Utils ────────────────────────────────────────────────────────────────────

  const utils = {
    formatAmount(kobo) {
      return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(kobo);
    },
    isValidEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); },
    isValidAmount(amount) { return Number.isInteger(amount) && amount >= 100; },
    today() { return new Date().toISOString().split('T')[0]; },
    tomorrow() {
      const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().split('T')[0];
    },
    nightsBetween(checkIn, checkOut) {
      return Math.max(1, Math.round((new Date(checkOut) - new Date(checkIn)) / 86400000));
    },
    merge(target, source) {
      Object.keys(source).forEach(k => {
        if (source[k] !== '' && source[k] != null) target[k] = source[k];
      });
      return target;
    },
    emit(event, detail = {}) {
      if (typeof window === 'undefined') return;
      window.dispatchEvent(new CustomEvent(`bookstack:${event}`, { detail, bubbles: true }));
    },
  };

  // ─── HTTP Client ──────────────────────────────────────────────────────────────

  const request = {
    async call(method, path, body = null) {
      if (!_initialized) {
        throw new BookStackError('SDK not initialized. Call Booking.init({ publicKey }) first.', 'NOT_INITIALIZED');
      }
      const url     = `${_config.baseUrl}${path}`;
      console.log('[BookInStack] fetching:', url, '| baseUrl:', _config.baseUrl);
      const headers = {
        'Content-Type':  'application/json',
        'Authorization': `Bearer ${_config.publicKey}`,
      };
      const opts = { method, headers };
      if (body && ['POST', 'PUT', 'PATCH'].includes(method)) opts.body = JSON.stringify(body);

      const controller = new AbortController();
      const timeout    = setTimeout(() => controller.abort(), _config.timeout);
      opts.signal      = controller.signal;

      try {
        const response = await fetch(url, opts);
        clearTimeout(timeout);
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          const error  = new BookStackError(data.message || `Request failed (${response.status})`, data.error || 'REQUEST_FAILED', data);
          error.status = response.status;
          throw error;
        }
        return data;
      } catch (err) {
        clearTimeout(timeout);
        if (err.name === 'AbortError') throw new BookStackError('Request timed out', 'TIMEOUT');
        throw err;
      }
    },
    get:  (path)       => request.call('GET',  path),
    post: (path, body) => request.call('POST', path, body),
  };

  // ─── Custom Error ─────────────────────────────────────────────────────────────

  class BookStackError extends Error {
    constructor(message, code, data = {}) {
      super(message);
      this.name = 'BookStackError';
      this.code = code;
      this.data = data;
    }
  }

  // ─── Widget ───────────────────────────────────────────────────────────────────

  const widget = {

    injectStyles() {
      if (document.getElementById('bookstack-styles')) return;
      const style = document.createElement('style');
      style.id    = 'bookstack-styles';
      style.textContent = `
        .bks-widget {
          --bks-accent: #4f46e5;
          font-family: 'DM Sans','Segoe UI',system-ui,sans-serif;
          max-width: 420px;
          border: 1px solid #e5e7eb; border-radius: 14px;
          padding: 26px;
          box-shadow: 0 1px 4px rgba(0,0,0,.06), 0 6px 24px rgba(0,0,0,.05);
        }
        .bks-widget * { box-sizing: border-box; }

        .bks-header { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
        .bks-header h3 { margin:0; font-size:18px; font-weight:700; color:#111827; flex:1; }
        .bks-mode-badge {
          font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
          background:rgba(79,70,229,.1); color:var(--bks-accent); padding:3px 9px; border-radius:20px;
        }

        /* Price display — always category-driven, never a free-form input */
        .bks-price-display {
          margin: 4px 0 14px;
          padding: 14px 16px;
          background:rgba(79,70,229,.05);
          border:1px solid rgba(79,70,229,.2);
          border-radius: 10px;
        }
        .bks-price-main {
          display: block;
          font-size: 28px;
          font-weight: 800;
          color: #111827;
          letter-spacing: -.04em;
          line-height: 1;
        }
        .bks-price-sub {
          display: block;
          font-size: 12px;
          color: #6b7280;
          margin-top: 5px;
          line-height: 1.4;
        }

        /* Quantity controls */
        .bks-qty-row { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
        .bks-qty-label { flex:1; font-size:13px; font-weight:500; color:#374151; }
        .bks-qty-unit  { font-size:12px; color:#9ca3af; margin-top:2px; }
        .bks-qty-total { font-size:13px; font-weight:600; color:#4f46e5; min-width:80px; text-align:right; }
        .bks-qty-ctrl  {
          display:flex; align-items:center;
          border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;
        }
        .bks-qty-btn {
          width:34px; height:34px; border:none; background:#f9fafb;
          color:#374151; font-size:18px; cursor:pointer;
          display:flex; align-items:center; justify-content:center;
          transition:background .1s; line-height:1;
        }
        .bks-qty-btn:hover { background:#f3f4f6; }
        .bks-qty-btn:disabled { opacity:.3; cursor:not-allowed; }
        .bks-qty-num {
          width:40px; text-align:center; font-size:14px; font-weight:600; color:#111827;
          border:none; border-left:1px solid #e5e7eb; border-right:1px solid #e5e7eb;
          outline:none; background:#fff; height:34px; padding:0;
        }

        /* Date range */
        .bks-date-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }
        .bks-nights-badge {
          display:inline-flex; align-items:center; gap:4px;
          font-size:12px; font-weight:600;
          background:#f0fdf4; color:#15803d;
          padding:3px 10px; border-radius:20px; margin-bottom:14px;
        }

        /* Fields */
        .bks-field { margin-bottom:14px; }
        .bks-field label {
          display:block; font-size:12px; font-weight:600;
          color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px;
        }
        .bks-field input, .bks-field select {
          width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px;
          font-size:14px; color:#111827; outline:none;
          transition:border-color .15s, box-shadow .15s; font-family:inherit; background:#fff;
        }
        .bks-field input:focus, .bks-field select:focus {
          border-color:var(--bks-accent); box-shadow:0 0 0 3px rgba(79,70,229,.1);
        }
        .bks-field input[readonly] { background:#f9fafb; color:#6b7280; }

        .bks-divider { border:none; border-top:1px solid #f3f4f6; margin:14px 0; }

        /* Button */
        .bks-btn {
          width:100%; padding:13px; background:#4f46e5; color:#fff;
          font-size:15px; font-weight:700; border:none; border-radius:9px;
          cursor:pointer; transition:background .15s, transform .1s, box-shadow .15s;
          font-family:inherit; letter-spacing:-.01em;
        }
        .bks-btn:hover { background:var(--bks-accent); box-shadow:0 4px 14px rgba(79,70,229,.3); }
        .bks-btn:active { transform:scale(.98); }
        .bks-btn:disabled { background:rgba(79,70,229,.4); cursor:not-allowed; box-shadow:none; }

        /* Error */
        .bks-error {
          display:flex; align-items:flex-start; gap:6px;
          color:#ef4444; font-size:13px; margin-bottom:12px;
          background:#fff5f5; border:1px solid #fecaca; border-radius:7px; padding:9px 11px;
        }

        /* Success */
        .bks-success { text-align:center; padding:20px 0 10px; }
        .bks-check { font-size:52px; margin-bottom:12px; }
        .bks-success h4 {
          margin:0 0 6px; font-size:20px; font-weight:800; color:#111827; letter-spacing:-.03em;
        }
        .bks-success p { color:#6b7280; font-size:14px; margin:0; line-height:1.5; }
        .bks-ref {
          display:inline-block; margin-top:12px;
          font-family:'DM Mono',monospace; font-size:12px;
          background:#f3f4f6; color:#374151; padding:4px 12px; border-radius:6px;
        }

        /* Negotiate button */
        .bks-negotiate {
          width:100%; padding:11px; margin-top:8px;
          background:transparent; color:var(--bks-accent);
          border:1px solid var(--bks-accent); border-radius:9px;
          font-size:13px; font-weight:600; font-family:inherit;
          cursor:pointer; transition:all .15s; letter-spacing:-.01em;
          display:flex; align-items:center; justify-content:center; gap:7px;
        }
        .bks-negotiate:hover { background:rgba(79,70,229,.06); }
        /* Custom amount input */
        .bks-custom-amount {
          margin-bottom:12px;
        }
        .bks-custom-amount label {
          display:block; font-size:12px; font-weight:600;
          color:#374151; margin-bottom:6px;
        }
        .bks-custom-amount input {
          width:100%; padding:10px 12px; border:1px solid #e5e7eb;
          border-radius:8px; font-size:15px; font-weight:600;
          font-family:inherit; color:#111827;
        }
        .bks-custom-amount input:focus {
          outline:none; border-color:var(--bks-accent);
          box-shadow:0 0 0 3px rgba(79,70,229,.1);
        }
        .bks-price-range {
          font-size:11px; color:#9ca3af; margin-top:4px;
        }
        .bks-powered { text-align:center; margin-top:14px; font-size:11px; color:#d1d5db; }
        .bks-powered a { color:#a5b4fc; text-decoration:none; }

        /* Catalog */
        .bks-catalog { display:flex; flex-direction:column; gap:8px; }
        .bks-cat-card {
          padding:12px 14px; border:2px solid #e5e7eb; border-radius:9px;
          cursor:pointer; transition:all .15s;
        }
        .bks-cat-card:hover   { border-color:#a5b4fc; background:#fafafe; }
        .bks-cat-card.selected { border-color:var(--bks-accent); background:rgba(79,70,229,.06); }
        .bks-cat-name  { font-weight:700; font-size:14px; color:var(--bks-accent); }
        .bks-cat-price { font-size:13px; color:var(--bks-accent); font-weight:600; margin-top:2px; }
        .bks-cat-desc  { font-size:12px; color:#6b7280; margin-top:3px; }
        .bks-cat-meta  { font-size:11px; color:#9ca3af; margin-top:3px; }

        /* Ticket qty section */
        .bks-qty-section {
          background:#f8fafc; border:1px solid #e5e7eb; border-radius:9px;
          padding:12px 14px; margin-bottom:2px;
        }
        .bks-qty-row {
          display:flex; align-items:center; justify-content:space-between;
          padding:6px 0; border-bottom:1px solid #f3f4f6;
        }
        .bks-qty-row:last-of-type { border-bottom:none; }
        .bks-qty-label { font-size:13px; color:#374151; font-weight:500; }
        .bks-qty-ctrl  { display:flex; align-items:center; gap:12px; }
        .bks-qty-num   { font-size:15px; font-weight:700; color:#111827; min-width:20px; text-align:center; }
        .bks-total-row {
          display:flex; align-items:center; justify-content:space-between;
          padding-top:10px; margin-top:4px;
        }
        .bks-total { font-size:18px; font-weight:800; color:#111827; letter-spacing:-.03em; }
      `;
      document.head.appendChild(style);
    },

    render(selector, options = {}) {
      this.injectStyles();

      const container = typeof selector === 'string' ? document.querySelector(selector) : selector;
      if (!container) { console.error(`[BookInStack] Widget container not found: ${selector}`); return; }

      const mode    = _modeConfig || MODE_DEFAULTS.appointment;
      const catalog = _catalog    || [];
      const today   = utils.today();
      const tomorrow = utils.tomorrow();

      container.innerHTML = '';
      const wrap = document.createElement('div');
      wrap.className = 'bks-widget';

      // ── Apply developer CMS appearance ────────────────────────────────────────
      const wc     = _widgetConfig || {};
      const accent = wc.accent_color || '#4f46e5';

      // Apply background — always set explicitly, never rely on CSS default
      if (wc.bg_type === 'color' && wc.bg_color) {
        wrap.style.background = wc.bg_color;
      } else if (wc.bg_type === 'image' && wc.bg_image_url) {
        wrap.style.background           = 'transparent';
        wrap.style.backgroundImage      = `url('${wc.bg_image_url}')`;
        wrap.style.backgroundSize       = 'cover';
        wrap.style.backgroundPosition   = 'center';
        wrap.style.backgroundRepeat     = 'no-repeat';
        wrap.style.setProperty('--bks-field-bg', 'rgba(255,255,255,0.88)');
      } else {
        wrap.style.background = '#fff';
      }

      if (wc.border_radius !== undefined) wrap.style.borderRadius = wc.border_radius + 'px';
      wrap.style.setProperty('--bks-accent', accent);

      // ── Closed / no-catalog states ────────────────────────────────────────────
      if (!_bookingOpen) {
        wrap.innerHTML = `
          <div style="text-align:center; padding:24px 8px;">
            <div style="font-size:36px; margin-bottom:10px;">🔒</div>
            <div style="font-weight:700; font-size:15px; color:#111827; margin-bottom:6px;">Bookings Closed</div>
            <div style="font-size:13px; color:#6b7280; line-height:1.5;">
              ${_bookingReason || 'Bookings are not currently available.'}
            </div>
          </div>
          <div class="bks-powered">Powered by <a href="https://bookinstack.dev" target="_blank">BookInStack</a></div>`;
        container.appendChild(wrap);
        return;
      }

      if (catalog.length === 0) {
        wrap.innerHTML = `
          <div style="text-align:center; padding:24px 8px;">
            <div style="font-size:36px; margin-bottom:10px;">⚙️</div>
            <div style="font-weight:700; font-size:15px; color:#111827; margin-bottom:6px;">No ${mode.plural} Available</div>
            <div style="font-size:13px; color:#6b7280; line-height:1.5;">
              No booking types have been configured yet.
            </div>
          </div>
          <div class="bks-powered">Powered by <a href="https://bookinstack.dev" target="_blank">BookInStack</a></div>`;
        container.appendChild(wrap);
        return;
      }


      if (_enableNegotiate && mode.mode !== 'ticket') {
        const wa = _whatsappNumber.replace(/\D/g, '');

        // Build catalog options for negotiate mode
        const catOptions = catalog.length > 0
            ? catalog.map((cat, i) => `
                <div class="bks-cat-card ${i === 0 ? 'selected' : ''}"
                    data-id="${cat.id}"
                    data-name="${cat.name}"
                    data-price="${cat.price}"
                    data-neg-trigger
                    style="cursor:pointer;">
                  <div class="bks-cat-name">${cat.name}</div>
                  <div class="bks-cat-price">${utils.formatAmount(cat.price)}</div>
                  ${cat.description ? `<div class="bks-cat-desc">${cat.description}</div>` : ''}
                </div>`).join('')
            : '';

        const firstCat = catalog[0];

        wrap.innerHTML = `
          <div class="bks-header">
            <h3>${mode.cta}</h3>
            <span class="bks-mode-badge">${mode.label}</span>
          </div>

          ${catOptions ? `
            <div class="bks-field" style="margin-bottom:14px;">
              <label>${mode.desc_label}</label>
              <div class="bks-catalog" id="bks-neg-catalog">${catOptions}</div>
            </div>` : ''}

          <div class="bks-field">
            <label>Your Name</label>
            <input type="text" id="bks-neg-name" placeholder="Full name" />
          </div>
          <div class="bks-field" style="margin-bottom:20px;">
            <label>Your Email <span style="font-weight:400;color:#9ca3af;">(optional)</span></label>
            <input type="text" id="bks-neg-email" placeholder="you@email.com" />
          </div>

          <div style="text-align:center; padding:4px 0 12px;">
            <div style="font-size:13px; color:#6b7280; line-height:1.6; margin-bottom:16px;">
              Select what you're interested in, then chat with us on WhatsApp to discuss pricing.
              We'll send you a secure payment link once agreed.
            </div>
            <a id="bks-wa-link" href="https://wa.me/${wa}?text=${encodeURIComponent(buildNegotiateText(firstCat))}"
              target="_blank"
              style="display:inline-flex;align-items:center;gap:8px;background:#25d366;color:#fff;padding:13px 28px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;transition:all .2s;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
              </svg>
              Chat on WhatsApp
            </a>
          </div>
          <div class="bks-powered">Powered by <a href="https://bookinstack.dev" target="_blank">BookInStack</a></div>`;

        container.appendChild(wrap);

        // ── Helper — build pre-filled WhatsApp message ────────────────────────────
        function buildNegotiateText(cat) {
            const name  = wrap.querySelector('#bks-neg-name')?.value.trim()  || '';
            const email = wrap.querySelector('#bks-neg-email')?.value.trim() || '';
            const lines = [
                'Hello,',
                '',
                `I am interested in making a booking.`,
            ];
            if (cat) {
                lines.push(`📋 *${mode.label} Details*`);
                lines.push(`Service: ${cat.name}`);
                lines.push(`Listed Price: ${utils.formatAmount(cat.price)}`);
                if (cat.description) lines.push(`Details: ${cat.description}`);
            }
            if (name)  lines.push(``, `My Name: ${name}`);
            if (email) lines.push(`My Email: ${email}`);
            lines.push('', 'Can we discuss availability and pricing?');
            return lines.join('\n');
        }

        // ── Update WA link when category or name/email changes ────────────────────
        let selectedNegCat = firstCat || null;

        function refreshWaLink() {
            const link = wrap.querySelector('#bks-wa-link');
            if (link) {
                link.href = `https://wa.me/${wa}?text=${encodeURIComponent(buildNegotiateText(selectedNegCat))}`;
            }
        }

        // Category selection
        wrap.querySelector('#bks-neg-catalog')?.addEventListener('click', (e) => {
            const card = e.target.closest('[data-neg-trigger]');
            if (!card) return;
            wrap.querySelectorAll('[data-neg-trigger]').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedNegCat = catalog.find(c => c.id == card.dataset.id) || selectedNegCat;
            refreshWaLink();
        });

        // Name / email input updates WA link live
        wrap.querySelector('#bks-neg-name')?.addEventListener('input',  refreshWaLink);
        wrap.querySelector('#bks-neg-email')?.addEventListener('input', refreshWaLink);

        return;
      }

      // ── State (declared first — used by all sections below) ──────────────────
      let selectedCat = catalog[0];
      let adultCount  = 1;
      let childCount  = 0;

      // ── Helpers (declared before HTML so they can be referenced in events) ───
      const q          = (sel) => wrap.querySelector(sel);
      const showError  = (msg) => {
        q('#bks-error').style.display  = msg ? 'flex' : 'none';
        q('#bks-err-msg').textContent  = msg;
      };
      const setLoading = (on) => {
        const btn = q('#bks-submit');
        btn.disabled    = on;
        btn.textContent = on ? 'Processing…' : mode.cta;
      };

      // ── Price display updater ─────────────────────────────────────────────────
      const refreshPriceDisplay = () => {
        const display = q('#bks-price-display');
        if (!display || !selectedCat) return;

        if (mode.mode === 'reservation') {
          const ci = q('#bks-checkin');
          const co = q('#bks-checkout');
          const n  = (ci && co) ? utils.nightsBetween(ci.value, co.value) : 1;
          display.innerHTML = `
            <span class="bks-price-main">${utils.formatAmount(selectedCat.price * n)}</span>
            <span class="bks-price-sub">${utils.formatAmount(selectedCat.price)} × ${n} ${_reservationUnit === 'day' ? 'day' : 'night'}${n !== 1 ? 's' : ''}</span>`;
        } else if (mode.mode === 'ticket') {
          const adultPrice = selectedCat.price;
          const childPrice = selectedCat.enable_child_pricing
            ? (selectedCat.child_price || adultPrice)
            : adultPrice;
          const total = (adultPrice * adultCount) + (childPrice * childCount);
          display.innerHTML = `
            <span class="bks-price-main">${utils.formatAmount(total)}</span>
            <span class="bks-price-sub">
              ${adultCount} adult${adultCount !== 1 ? 's' : ''}
              ${childCount > 0 ? `· ${childCount} child${childCount !== 1 ? 'ren' : ''}` : ''}
              @ ${utils.formatAmount(adultPrice)}${selectedCat.enable_child_pricing && childPrice !== adultPrice ? ` / ${utils.formatAmount(childPrice)}` : ''}
            </span>`;
        } else {
          // appointment — flat price per category
          display.innerHTML = `
            <span class="bks-price-main">${utils.formatAmount(selectedCat.price)}</span>
            ${selectedCat.duration_minutes
              ? `<span class="bks-price-sub">⏱ ${selectedCat.duration_minutes} min</span>`
              : ''}`;
        }
      };

      // ── Ticket qty refresher ──────────────────────────────────────────────────
      const refreshQty = () => {
        const max = selectedCat?.max_per_order || 99;
        q('#bks-adult-count').textContent = adultCount;
        q('#bks-child-count').textContent = childCount;
        q('#bks-adult-down').disabled     = adultCount <= 1;
        q('#bks-adult-up').disabled       = (adultCount + childCount) >= max;
        q('#bks-child-down').disabled     = childCount <= 0;
        q('#bks-child-up').disabled       = (adultCount + childCount) >= max;
        refreshPriceDisplay();
      };

      // ── Nights refresher ──────────────────────────────────────────────────────
      const refreshNights = () => {
        const ci    = q('#bks-checkin');
        const co    = q('#bks-checkout');
        const badge = q('#bks-nights');
        if (!ci || !co) return;
        if (co.value <= ci.value) {
          const d = new Date(ci.value); d.setDate(d.getDate() + 1);
          co.value = d.toISOString().split('T')[0];
          co.min   = co.value;
        }
        const n = utils.nightsBetween(ci.value, co.value);
        if (badge) badge.textContent = `🌙 ${n} night${n !== 1 ? 's' : ''}`;
        refreshPriceDisplay();
      };

      // ── Category selection handler ────────────────────────────────────────────
      const selectCat = (el) => {
        wrap.querySelectorAll('.bks-cat-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        selectedCat = catalog.find(c => c.id == el.dataset.id) || selectedCat;

        // Toggle pay vs negotiate based on fixed_price
        const isFixed     = selectedCat.fixed_price !== false;
        const hasNegotiate = _enableNegotiate && _whatsappNumber;
        const submitBtn   = q('#bks-submit');
        const negoWrap    = q('#bks-negotiate-wrap');
        if (submitBtn) submitBtn.style.display  = (!isFixed && hasNegotiate) ? 'none' : 'block';
        if (negoWrap)  negoWrap.style.display   = (!isFixed && hasNegotiate) ? 'block' : 'none';

        // Show/hide custom amount input
        const customWrap = q('#bks-custom-amount-wrap');
        if (customWrap) customWrap.style.display = !isFixed ? 'block' : 'none';

        // Ticket: show/hide child row based on new category's setting
        if (mode.mode === 'ticket') {
          const childRow = q('#bks-child-row');
          if (childRow) childRow.style.display = selectedCat.enable_child_pricing ? 'flex' : 'none';
          if (!selectedCat.enable_child_pricing) childCount = 0;
          refreshQty();
        } else {
          refreshPriceDisplay();
        }
      };

      // ── Build HTML ────────────────────────────────────────────────────────────

      // Category cards
      const catalogHTML = `
        <div class="bks-field">
          <label>${mode.desc_label}</label>
          <div class="bks-catalog">
            ${catalog.map((cat, i) => `
              <div class="bks-cat-card${i === 0 ? ' selected' : ''}"
                   data-id="${cat.id}"
                   data-cat-trigger>
                <div class="bks-cat-name">${cat.name}</div>
                <div class="bks-cat-price">
                  ${utils.formatAmount(cat.price)}${mode.mode === 'reservation' ? '<span style="font-size:11px;font-weight:400;">/night</span>' : ''}
                  ${cat.enable_child_pricing && cat.child_price
                    ? `<span style="font-size:11px; color:#9ca3af; margin-left:6px;">Child: ${utils.formatAmount(cat.child_price)}</span>`
                    : ''}
                </div>
                ${cat.description ? `<div class="bks-cat-desc">${cat.description}</div>` : ''}
                ${cat.duration_minutes ? `<div class="bks-cat-meta">⏱ ${cat.duration_minutes} min</div>` : ''}
                ${cat.capacity ? `<div class="bks-cat-meta">👥 Max ${cat.capacity} guests</div>` : ''}
              </div>
            `).join('')}
          </div>
        </div>`;

      // Price display (always shown, always category-driven)
      const priceHTML = `
        <div id="bks-price-display" class="bks-price-display">
          <span class="bks-price-main">${utils.formatAmount(selectedCat.price)}</span>
        </div>`;

      // Mode-specific extras
      let extrasHTML = '';

      if (mode.mode === 'ticket') {
        extrasHTML = `
          <div class="bks-qty-section">
            <div class="bks-qty-row">
              <span class="bks-qty-label">Adults</span>
              <div class="bks-qty-ctrl">
                <button class="bks-qty-btn" id="bks-adult-down" type="button" disabled>−</button>
                <span class="bks-qty-num" id="bks-adult-count">1</span>
                <button class="bks-qty-btn" id="bks-adult-up" type="button">+</button>
              </div>
            </div>
            <div class="bks-qty-row" id="bks-child-row"
                 style="${catalog[0].enable_child_pricing ? 'display:flex' : 'display:none'}">
              <span class="bks-qty-label">Children</span>
              <div class="bks-qty-ctrl">
                <button class="bks-qty-btn" id="bks-child-down" type="button" disabled>−</button>
                <span class="bks-qty-num" id="bks-child-count">0</span>
                <button class="bks-qty-btn" id="bks-child-up" type="button">+</button>
              </div>
            </div>
          </div>`;
      }

      if (mode.mode === 'reservation') {
        extrasHTML = `
          <div class="bks-date-row">
            <div class="bks-field" style="margin-bottom:0;">
              <label>${_reservationUnit === 'day' ? 'Start Date' : 'Check-in'}</label>
              <input type="date" id="bks-checkin" min="${today}" value="${today}" />
            </div>
            <div class="bks-field" style="margin-bottom:0;">
              <label>${_reservationUnit === 'day' ? 'End Date' : 'Check-out'}</label>
              <input type="date" id="bks-checkout" min="${tomorrow}" value="${tomorrow}" />
            </div>
          </div>
          <div class="bks-nights-badge" id="bks-nights">${_reservationUnit === 'day' ? '☀️' : '🌙'} 1 ${_reservationUnit}</div>`;
      }

      if (mode.mode === 'appointment') {
        extrasHTML = `
          <div class="bks-date-row">
            <div class="bks-field" style="margin-bottom:0;">
              <label>Preferred Date</label>
              <input type="date" id="bks-date" min="${today}" value="${today}" />
            </div>
            <div class="bks-field" style="margin-bottom:0;">
              <label>Preferred Time</label>
              <input type="time" id="bks-time" value="09:00" />
            </div>
          </div>`;
      }

      wrap.innerHTML = `
        <div class="bks-header">
          <h3>${options.title || mode.cta}</h3>
          <span class="bks-mode-badge">${mode.label}</span>
        </div>
        ${catalogHTML}
        ${priceHTML}
        ${extrasHTML}
        <hr class="bks-divider" />
        <div class="bks-field">
          <label>Your Email</label>
          <input type="email" id="bks-email" placeholder="you@example.com" />
        </div>
        <div class="bks-field">
          <label>Your Name</label>
          <input type="text" id="bks-name" placeholder="Full name" />
        </div>
        <div id="bks-error" class="bks-error" style="display:none;">
          <span>⚠</span><span id="bks-err-msg"></span>
        </div>
        ${(() => {
          // Custom amount input — shown when selected category has fixed_price = false
          const cat = catalog[0];
          if (cat && cat.fixed_price === false) {
            const min = cat.min_price ? utils.formatAmount(cat.min_price) : null;
            const max = cat.max_price ? utils.formatAmount(cat.max_price) : null;
            const range = [min ? 'Min: ' + min : '', max ? 'Max: ' + max : ''].filter(Boolean).join(' · ');
            return `<div class="bks-custom-amount">
              <label>Enter Amount (₦)</label>
              <input type="number" id="bks-custom-amount-input"
                     placeholder="Enter agreed amount"
                     min="${cat.min_price ? cat.min_price/100 : 1}"
                     ${cat.max_price ? 'max="' + cat.max_price/100 + '"' : ''}
                     step="0.01" />
              ${range ? '<div class="bks-price-range">' + range + '</div>' : ''}
            </div>`;
          }
          return '';
        })()}
        <button class="bks-btn" id="bks-submit">${mode.cta}</button>
        <div id="bks-negotiate-wrap" style="display:none;">
          <div style="margin-top:10px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;text-align:center;">
            <div style="font-weight:700;color:#15803d;margin-bottom:4px;font-size:13px;">💬 Interested? Let's talk price</div>
            <div style="font-size:12px;color:#166534;margin-bottom:10px;line-height:1.5;">Chat with us, agree on a price, and we'll send you a secure payment link.</div>
            <button type="button" class="bks-negotiate" id="bks-negotiate">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
              </svg>
              Chat on WhatsApp
            </button>
          </div>
        </div>
        <div class="bks-powered">Powered by <a href="https://bookinstack.dev" target="_blank">BookInStack</a></div>
      `;

      container.appendChild(wrap);

      // ── Wire up events (after innerHTML is set) ───────────────────────────────

      // Category card clicks — use event delegation, no globals
      wrap.querySelector('.bks-catalog').addEventListener('click', (e) => {
        const card = e.target.closest('[data-cat-trigger]');
        if (card) selectCat(card);
      });

      // Ticket qty buttons
      if (mode.mode === 'ticket') {
        q('#bks-adult-up')?.addEventListener('click',   () => { adultCount++;                             refreshQty(); });
        q('#bks-adult-down')?.addEventListener('click', () => { if (adultCount > 1) { adultCount--; }     refreshQty(); });
        q('#bks-child-up')?.addEventListener('click',   () => { childCount++;                             refreshQty(); });
        q('#bks-child-down')?.addEventListener('click', () => { if (childCount > 0) { childCount--; }     refreshQty(); });
        refreshQty(); // initial render
      }

      // Reservation date changes
      if (mode.mode === 'reservation') {
        q('#bks-checkin')?.addEventListener('change',  refreshNights);
        q('#bks-checkout')?.addEventListener('change', refreshNights);
        refreshNights(); // initial render
      }

      // Appointment — just init price display
      if (mode.mode === 'appointment') {
        refreshPriceDisplay();
      }

      // ── Negotiate button ──────────────────────────────────────────────────────
      q('#bks-negotiate')?.addEventListener('click', () => {
        const cat   = selectedCat;
        const email = q('#bks-email').value.trim();
        const name  = q('#bks-name').value.trim();

        const lines = [
          `Hello, I'm interested in *${cat?.name || mode.desc_label}*.`,
          cat?.price ? `Listed price: ${utils.formatAmount(cat.price)}` : '',
          name  ? `My name: ${name}`   : '',
          email ? `My email: ${email}` : '',
          '',
          'Can we discuss the price?',
        ].filter(Boolean);

        const text = encodeURIComponent(lines.join('\n'));
        const wa   = _whatsappNumber.replace(/\D/g, '');
        window.open(`https://wa.me/${wa}?text=${text}`, '_blank');
      });

      // ── Submit ────────────────────────────────────────────────────────────────
      q('#bks-submit').addEventListener('click', async () => {
        showError('');

        const email = q('#bks-email').value.trim();
        const name  = q('#bks-name').value.trim();

        if (!utils.isValidEmail(email)) return showError('Please enter a valid email address.');
        if (!selectedCat) return showError('Please select a category.');

        const params = {
          category_id:    selectedCat.id,
          customer_email: email,
          customer_name:  name || undefined,
        };

        // Handle custom amount (when fixed_price = false)
        if (selectedCat.fixed_price === false) {
          const customInput = q('#bks-custom-amount-input');
          const customVal   = customInput ? parseFloat(customInput.value) : 0;
          if (!customVal || customVal < 1) return showError('Please enter a valid amount.');
          const customKobo = Math.round(customVal * 100);
          if (selectedCat.min_price && customKobo < selectedCat.min_price) {
            return showError(`Minimum amount is ${utils.formatAmount(selectedCat.min_price)}.`);
          }
          if (selectedCat.max_price && customKobo > selectedCat.max_price) {
            return showError(`Maximum amount is ${utils.formatAmount(selectedCat.max_price)}.`);
          }
          params.amount = customKobo;
        }

        if (mode.mode === 'ticket') {
          if (adultCount < 1) return showError('Please select at least 1 adult.');
          params.adults   = adultCount;
          params.children = childCount;
        }

        if (mode.mode === 'reservation') {
          const ci = q('#bks-checkin')?.value;
          const co = q('#bks-checkout')?.value;
          if (!ci || !co) return showError('Please select check-in and check-out dates.');
          if (co <= ci)   return showError('Check-out must be after check-in.');
          params.check_in  = ci;
          params.check_out = co;
        }

        if (mode.mode === 'appointment') {
          params.preferred_date = q('#bks-date')?.value;
          params.preferred_time = q('#bks-time')?.value;
        }

        setLoading(true);
        try {
          const booking = await Booking.create(params);
          await Booking.pay(booking.reference, {
            returnUrl: options.returnUrl || _config.returnUrl,
          });
        } catch (err) {
          showError(err.message || 'Something went wrong. Please try again.');
          console.error('[BookInStack]', err);
        } finally {
          setLoading(false);
        }
      });
    },
  };

  // ─── Public API ───────────────────────────────────────────────────────────────

  const Booking = {

    /**
     * Initialize the SDK.
     * Fetches booking mode from API so widget renders with correct fields/labels.
     * Make sure to await this if you're rendering a widget immediately after.
     */
    async init(options = {}) {
      if (!options.publicKey) {
        throw new BookStackError('publicKey is required in Booking.init()', 'MISSING_PUBLIC_KEY');
      }

      _config      = utils.merge({ ...DEFAULT_CONFIG }, options);
      _initialized = true;

      console.info(`[BookInStack] SDK v${DEFAULT_CONFIG.version} initialized.`);

      // Fetch mode from API — always resolve against MODE_DEFAULTS
      // so the JS shape (description_label, supports_quantity etc) is guaranteed correct
      try {
        const status    = await request.get('/booking-window/status');
        const modeKey   = status.booking_mode || 'appointment';
        _modeConfig     = MODE_DEFAULTS[modeKey] || MODE_DEFAULTS.appointment;
        _catalog        = status.catalog       || [];
        _widgetConfig   = status.widget_config || {};
        _enableNegotiate = status.enable_negotiate;
        _bookingOpen    = status.open !== false;
        _bookingReason  = status.reason || null;
        console.info(`[BookInStack] Mode: ${modeKey}, catalog: ${_catalog.length} items, window open: ${_bookingOpen}`);
        utils.emit('ready', { version: DEFAULT_CONFIG.version, mode: modeKey, open: _bookingOpen, catalog: _catalog });
      } catch (err) {
        console.warn('[BookInStack] Could not fetch mode config, defaulting to appointment.', err?.message);
        _modeConfig    = MODE_DEFAULTS.appointment;
        _catalog       = [];
        _widgetConfig      = {};
        _enableNegotiate   = false;
        _whatsappNumber    = '';
        _reservationUnit = 'night';
        _bookingOpen   = true;
        _bookingReason = null;
        utils.emit('ready', { version: DEFAULT_CONFIG.version, mode: 'appointment', open: true, catalog: [] });
      }

      return this;
    },

    async create(params = {}) {
      // When a category_id is provided, amount and description are derived
      // server-side from the category — do not validate them here.
      if (!params.category_id) {
        if (!utils.isValidAmount(params.amount)) {
          throw new BookStackError('amount must be a positive integer in kobo (min 100)', 'INVALID_AMOUNT');
        }
        if (!params.description) {
          throw new BookStackError('description is required', 'MISSING_DESCRIPTION');
        }
      }
      if (!params.customer_email || !utils.isValidEmail(params.customer_email)) {
        throw new BookStackError('a valid customer_email is required', 'INVALID_EMAIL');
      }
      const result = await request.post('/bookings', params);
      utils.emit('booking:created', result.booking);
      return result.booking;
    },

    async pay(bookingReference, options = {}) {
      if (!bookingReference) {
        throw new BookStackError('bookingReference is required', 'MISSING_REFERENCE');
      }
      const returnUrl = options.returnUrl || _config.returnUrl;
      if (!returnUrl) {
        throw new BookStackError(
          'returnUrl is required. Set it in Booking.init({ returnUrl }) or Booking.pay(ref, { returnUrl })',
          'MISSING_RETURN_URL'
        );
      }
      const { authorization_url, reference } = await request.post('/payments/initialize', {
        booking_reference: bookingReference,
        callback_url:      returnUrl,
      });
      utils.emit('payment:redirecting', { reference, bookingReference, returnUrl });
      window.location.href = authorization_url;
    },

    async list(params = {}) {
      const qs = new URLSearchParams(params).toString();
      return request.get(`/bookings${qs ? '?' + qs : ''}`);
    },

    async get(reference) {
      const result = await request.get(`/bookings/${reference}`);
      return result.booking;
    },

    async attend(bookingReference, options = {}) {
      if (!bookingReference) throw new BookStackError('bookingReference is required', 'MISSING_REFERENCE');
      if (typeof options.attended !== 'boolean') throw new BookStackError('attended must be a boolean', 'INVALID_PARAM');
      const result = await request.post(`/bookings/${bookingReference}/attend`, {
        attended: options.attended,
        note:     options.note || null,
      });
      utils.emit('booking:attendance', result.booking);
      return result.booking;
    },

    async windowStatus() {
      return request.get('/booking-window/status');
    },

    /**
     * Render the booking widget.
     * Mode (appointment / ticket / reservation) is driven by your dashboard setting.
     *
     * @param {string|HTMLElement} selector
     * @param {object} [options]
     * @param {number}  [options.amount]      Fixed price per unit in kobo (optional)
     * @param {string}  [options.description] Pre-fill & lock description
     * @param {string}  [options.title]       Override widget heading
     * @param {string}  [options.returnUrl]   Override global returnUrl per widget
     */
    widget(selector, options = {}) {
      widget.render(selector, options);
      return this;
    },

    get modeConfig()    { return _modeConfig; },
    get catalog()       { return _catalog; },
    get widgetConfig()      { return _widgetConfig; },
    get enableNegotiate()   { return _enableNegotiate; },
    get whatsappNumber()    { return _whatsappNumber; },
    get reservationUnit()   { return _reservationUnit; },
    get bookingOpen()   { return _bookingOpen; },
    get bookingReason() { return _bookingReason; },
    utils,
    BookStackError,
    version: DEFAULT_CONFIG.version,
  };

  return Booking;

}));
