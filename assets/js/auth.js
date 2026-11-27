(function () {
  'use strict';

  var cfg = window.nasimAuth;
  var card = document.querySelector('.auth-card');
  if (!cfg || !card) return;

  var i18n = cfg.i18n || {};
  var codeLength = parseInt(cfg.codeLength, 10) || 5;
  var titleEl = card.querySelector('[data-auth-title]');
  var subtitleEl = card.querySelector('[data-auth-subtitle]');
  var brandEl = document.querySelector('[data-auth-brand]');
  var stepIconEl = card.querySelector('[data-step-icon]');
  var loadingEl = card.querySelector('[data-auth-loading]');
  var loadingTextEl = card.querySelector('[data-auth-loading-text]');
  var initialTitle = titleEl ? titleEl.textContent : '';
  var initialSubtitle = subtitleEl ? subtitleEl.textContent : '';

  /* ---------- digits ---------- */
  function faDigits(value) {
    return String(value).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
  }
  function latinDigits(value) {
    return String(value)
      .replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 1776; })
      .replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; });
  }
  function onlyDigits(value) {
    return latinDigits(value).replace(/\D/g, '');
  }

  /* ---------- UI helpers ---------- */
  function setHead(title, subtitle) {
    if (titleEl) titleEl.textContent = title || '';
    if (subtitleEl) {
      subtitleEl.textContent = subtitle || '';
      subtitleEl.hidden = !subtitle;
    }
  }

  function setMessage(form, text, type) {
    var box = form.querySelector('.auth-message');
    if (!box) return null;
    box.textContent = text || '';
    box.className = 'auth-message mt-4' + (text ? ' is-' + (type || 'error') : '');
    return box;
  }

  function markField(form, field) {
    form.querySelectorAll('[aria-invalid]').forEach(function (el) { el.removeAttribute('aria-invalid'); });
    var input = field && form.querySelector('[name="' + field + '"]');
    if (input && !input.closest('[hidden]')) {
      input.setAttribute('aria-invalid', 'true');
      input.focus();
    }
  }

  function setBusy(form, busy, text) {
    form.classList.toggle('is-busy', busy);
    card.classList.toggle('is-busy', busy);
    if (loadingEl) {
      loadingEl.hidden = !busy;
      loadingEl.setAttribute('aria-busy', busy ? 'true' : 'false');
    }
    if (loadingTextEl && text) {
      loadingTextEl.textContent = text;
    }
    if (!busy) {
      syncButtonStates();
    } else {
      form.querySelectorAll('.auth-submit').forEach(function (b) { b.disabled = true; });
    }
  }

  function request(action, form, extra) {
    var data = new FormData(form);
    data.append('action', action);
    data.append('nonce', cfg.nonce);
    Object.keys(extra || {}).forEach(function (k) { data.set(k, extra[k]); });

    return fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .catch(function () { return { success: false, data: { message: i18n.network || i18n.error } }; })
      .then(function (json) {
        json = json && typeof json === 'object' ? json : { success: false, data: {} };
        json.data = json.data || {};
        if (json.success && json.data.redirect) {
          window.location.assign(json.data.redirect);
        }
        return json;
      });
  }

  /* ---------- views: otp / password ---------- */
  var views = {
    otp: card.querySelector('[data-view="otp"]'),
    password: card.querySelector('[data-view="password"]')
  };

  function showView(name) {
    Object.keys(views).forEach(function (key) {
      if (views[key]) views[key].hidden = key !== name;
    });
    if (stepIconEl) stepIconEl.hidden = true;
    if (brandEl) brandEl.hidden = false;
    if (name === 'password') {
      setHead(i18n.passwordTitle || 'ورود با رمز عبور', i18n.passwordSubtitle || '');
    } else {
      setHead(initialTitle, initialSubtitle);
    }
    syncButtonStates();
    var first = views[name] && views[name].querySelector('.auth-step:not([hidden]) input:not([type=hidden])');
    if (first) first.focus();
  }

  card.querySelectorAll('[data-switch]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = btn.dataset.switch;
      var from = target === 'password' ? views.otp : views.password;
      var to = target === 'password' ? views.password : views.otp;
      if (from && to) {
        var src = from.querySelector('[name="mobile"], [name="identifier"]');
        var dst = to.querySelector('[name="identifier"], [name="mobile"]');
        if (src && dst && src.value) {
          dst.value = target === 'password' ? faDigits(onlyDigits(src.value) || src.value.trim()) : src.value;
        }
      }
      if (target === 'otp' && views.otp) {
        // Reset back to mobile step when returning from password view
        var mobileStep = views.otp.querySelector('[data-step="mobile"]');
        var codeStep = views.otp.querySelector('[data-step="code"]');
        if (mobileStep && codeStep) {
          mobileStep.hidden = false;
          codeStep.hidden = true;
        }
      }
      showView(target);
    });
  });

  /* ---------- password visibility ---------- */
  card.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = btn.parentNode.querySelector('input');
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('is-visible', show);
      btn.setAttribute('aria-label', show ? (i18n.hide || 'پنهان کردن') : (i18n.show || 'نمایش'));
    });
  });

  /* ---------- password login ---------- */
  var pwIdentifier = views.password && views.password.querySelector('[name="identifier"]');
  var pwPassword = views.password && views.password.querySelector('[name="password"]');
  var pwSubmit = views.password && views.password.querySelector('[data-password-submit]');

  if (views.password) {
    [pwIdentifier, pwPassword].forEach(function (inp) {
      if (!inp) return;
      inp.addEventListener('input', function () {
        inp.removeAttribute('aria-invalid');
        syncButtonStates();
      });
    });

    views.password.addEventListener('submit', function (e) {
      e.preventDefault();
      var form = views.password;
      if (form.classList.contains('is-busy')) return;
      var cleanId = latinDigits(pwIdentifier.value).replace(/\s+/g, '').trim();
      setBusy(form, true, 'در حال بررسی اطلاعات...');
      setMessage(form, '');
      request('nasim_auth_password', form, { identifier: cleanId }).then(function (res) {
        if (res.success) return;
        setBusy(form, false);
        setMessage(form, res.data.message || i18n.error);
        markField(form, res.data.field);
      });
    });
  }

  /* ---------- OTP flow ---------- */
  var form = views.otp;
  var steps = form ? { mobile: form.querySelector('[data-step="mobile"]'), code: form.querySelector('[data-step="code"]') } : null;
  var mobileInput = form && form.querySelector('[name="mobile"]');
  var codeInput = form && form.querySelector('[name="code"]');
  var nameField = form && form.querySelector('[data-name-field]');
  var nameInput = form && form.querySelector('[name="name"]');
  var cells = form ? form.querySelectorAll('.auth-otp-cells span') : [];
  var verifyLabel = form && form.querySelector('[data-verify-label]');
  var countdown = form && form.querySelector('[data-countdown]');
  var resendBtn = form && form.querySelector('[data-resend]');
  var mobileSubmit = form && form.querySelector('[data-mobile-submit]');
  var verifySubmit = form && form.querySelector('[data-verify-submit]');
  var timer = null;
  var isNew = false;
  var otpAbort = null;

  function mobileValue() {
    return mobileInput ? onlyDigits(mobileInput.value) : '';
  }

  function syncButtonStates() {
    if (mobileSubmit && mobileInput) {
      mobileSubmit.disabled = !/^09\d{9}$/.test(mobileValue());
    }
    if (verifySubmit && codeInput) {
      var nameOk = !isNew || (nameInput && nameInput.value.trim().length >= 2);
      verifySubmit.disabled = !(codeInput.value.length === codeLength && nameOk);
    }
    if (pwSubmit && pwIdentifier && pwPassword) {
      pwSubmit.disabled = !(pwIdentifier.value.trim().length > 0 && pwPassword.value.length > 0);
    }
  }

  if (!form) {
    syncButtonStates();
    return;
  }

  // Live formatting: accept Persian/Arabic/Latin digits, +98, spaces; display Persian digits cleanly like 7learn.
  mobileInput.addEventListener('input', function () {
    var digits = onlyDigits(mobileInput.value);
    if (digits.indexOf('0098') === 0) digits = '0' + digits.slice(4);
    else if (digits.indexOf('98') === 0 && digits.length > 10) digits = '0' + digits.slice(2);
    else if (digits.charAt(0) === '9') digits = '0' + digits;
    digits = digits.slice(0, 11);
    mobileInput.value = digits ? faDigits(digits) : '';
    mobileInput.removeAttribute('aria-invalid');
    syncButtonStates();
  });

  if (nameInput) {
    nameInput.addEventListener('input', function () {
      nameInput.removeAttribute('aria-invalid');
      syncButtonStates();
    });
  }

  function paintCells() {
    var v = codeInput.value;
    cells.forEach(function (cell, i) {
      cell.textContent = v[i] ? faDigits(v[i]) : '';
      cell.classList.toggle('is-filled', !!v[i]);
      cell.classList.toggle('is-active', document.activeElement === codeInput && i === Math.min(v.length, codeLength - 1));
    });
  }

  function showStep(name) {
    steps.mobile.hidden = name !== 'mobile';
    steps.code.hidden = name !== 'code';
    if (stepIconEl) stepIconEl.hidden = name !== 'code';
    if (brandEl) brandEl.hidden = name === 'code';

    if (name === 'code') {
      if (nameField) nameField.hidden = !isNew;
      if (verifyLabel) verifyLabel.textContent = isNew ? (i18n.createAccount || 'تأیید و ثبت‌نام') : (i18n.login || 'تأیید');
      // On code step, the subtitle is rendered inside the step (with masked mobile + edit pill), matching 7learn.
      setHead(isNew ? (i18n.newTitle || 'تکمیل ثبت‌نام') : (i18n.codeTitle || 'ورود کد تأیید'), '');
      (isNew && nameInput && !nameInput.value ? nameInput : codeInput).focus();
      listenWebOtp();
    } else {
      setHead(initialTitle, initialSubtitle);
      mobileInput.focus();
      if (otpAbort) otpAbort.abort();
    }
    paintCells();
    syncButtonStates();
  }

  function startCountdown(seconds) {
    clearInterval(timer);
    if (resendBtn) resendBtn.hidden = true;
    var left = Math.max(0, parseInt(seconds, 10) || 0);
    var tick = function () {
      if (left <= 0) {
        clearInterval(timer);
        if (countdown) countdown.textContent = '';
        if (resendBtn) resendBtn.hidden = false;
        return;
      }
      var mins = ('0' + Math.floor(left / 60)).slice(-2);
      var secs = ('0' + (left % 60)).slice(-2);
      if (countdown) {
        countdown.textContent = '( ' + faDigits(mins) + ' : ' + faDigits(secs) + ' )';
      }
      left--;
    };
    tick();
    timer = setInterval(tick, 1000);
  }

  // Android Chrome can read the SMS code automatically.
  function listenWebOtp() {
    if (!('OTPCredential' in window) || !window.AbortController) return;
    if (otpAbort) otpAbort.abort();
    otpAbort = new AbortController();
    navigator.credentials.get({ otp: { transport: ['sms'] }, signal: otpAbort.signal })
      .then(function (otp) {
        if (otp && otp.code) {
          codeInput.value = onlyDigits(otp.code).slice(0, codeLength);
          paintCells();
          syncButtonStates();
          maybeAutoVerify();
        }
      })
      .catch(function () {});
  }

  function sendCode() {
    var mobile = mobileValue();
    if (!/^09\d{9}$/.test(mobile)) {
      setMessage(form, i18n.badMobile);
      markField(form, 'mobile');
      return;
    }

    setBusy(form, true, 'در حال بررسی اطلاعات...');
    setMessage(form, '');

    request('nasim_auth_start', form, { mobile: mobile }).then(function (res) {
      setBusy(form, false);

      if (res.success) {
        isNew = !!res.data.is_new;
        var mobileTarget = form.querySelector('[data-masked-mobile]');
        if (mobileTarget) mobileTarget.textContent = faDigits(mobile);
        codeInput.value = '';
        showStep('code');
        startCountdown(res.data.resend);
        return;
      }

      // A code was sent moments ago: continue with that one.
      if (res.data.wait) {
        isNew = !!res.data.is_new;
        var mobileEl = form.querySelector('[data-masked-mobile]');
        if (mobileEl) mobileEl.textContent = faDigits(mobile);
        if (steps.code.hidden) showStep('code');
        startCountdown(res.data.wait);
        setMessage(form, res.data.message, 'info');
        return;
      }

      var box = setMessage(form, res.data.message || i18n.error);
      markField(form, res.data.field);

      if (res.data.password && views.password && box) {
        var link = document.createElement('button');
        link.type = 'button';
        link.className = 'underline font-extrabold ms-1';
        link.textContent = i18n.usePassword || 'ورود با رمز عبور';
        link.addEventListener('click', function () {
          if (pwIdentifier) pwIdentifier.value = faDigits(mobile);
          showView('password');
        });
        box.appendChild(document.createTextNode(' '));
        box.appendChild(link);
      }
    });
  }

  function verify() {
    codeInput.value = onlyDigits(codeInput.value).slice(0, codeLength);
    paintCells();

    if (isNew && nameInput && nameInput.value.trim().length < 2) {
      setMessage(form, i18n.needName);
      markField(form, 'name');
      return;
    }
    if (codeInput.value.length < codeLength) {
      setMessage(form, i18n.needCode);
      markField(form, 'code');
      return;
    }

    setBusy(form, true, 'در حال بررسی کد تأیید...');
    setMessage(form, '');

    request('nasim_auth_verify', form, { mobile: mobileValue() }).then(function (res) {
      if (res.success) {
        if (loadingTextEl) loadingTextEl.textContent = i18n.success || 'در حال ورود به پنل...';
        return;
      }
      setBusy(form, false);
      setMessage(form, res.data.message || i18n.error);

      if (res.data.field === 'name') {
        markField(form, 'name');
        return;
      }
      if (res.data.field === 'mobile') {
        showStep('mobile');
        markField(form, 'mobile');
        return;
      }

      codeInput.value = '';
      paintCells();
      syncButtonStates();
      markField(form, 'code');
      var otpWrap = form.querySelector('.auth-otp');
      if (otpWrap) {
        otpWrap.classList.add('is-shake');
        setTimeout(function () { otpWrap.classList.remove('is-shake'); }, 450);
      }

      if (res.data.expired) {
        clearInterval(timer);
        if (countdown) countdown.textContent = '';
        if (resendBtn) resendBtn.hidden = false;
      }
    });
  }

  function maybeAutoVerify() {
    if (codeInput.value.length === codeLength && !form.classList.contains('is-busy') && (!isNew || (nameInput && nameInput.value.trim().length >= 2))) {
      verify();
    }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (form.classList.contains('is-busy')) return;
    if (steps.code.hidden) sendCode(); else verify();
  });

  codeInput.addEventListener('input', function () {
    codeInput.value = onlyDigits(codeInput.value).slice(0, codeLength);
    codeInput.removeAttribute('aria-invalid');
    paintCells();
    syncButtonStates();
    maybeAutoVerify();
  });
  ['focus', 'blur', 'keyup', 'click'].forEach(function (ev) { codeInput.addEventListener(ev, paintCells); });

  if (nameInput) {
    nameInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        codeInput.focus();
      }
    });
  }

  if (resendBtn) {
    resendBtn.addEventListener('click', function () {
      if (!form.classList.contains('is-busy')) sendCode();
    });
  }

  var editBtn = form.querySelector('[data-edit]');
  if (editBtn) {
    editBtn.addEventListener('click', function () {
      clearInterval(timer);
      setMessage(form, '');
      showStep('mobile');
    });
  }

  paintCells();
  syncButtonStates();
})();
