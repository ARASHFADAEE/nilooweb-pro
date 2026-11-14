(function () {
  'use strict';

  var cfg = window.nasimAuth;
  var card = document.querySelector('.auth-card');

  if (!cfg || !card) return;

  var codeLength = parseInt(cfg.codeLength, 10) || 5;

  var faDigits = function (value) {
    return String(value).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
  };

  var latinDigits = function (value) {
    return String(value)
      .replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 1776; })
      .replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; });
  };

  /* Tabs: login / register */
  var headings = card.querySelectorAll('.auth-card-head [data-title-login]');

  function showTab(name) {
    card.querySelectorAll('[data-tab]').forEach(function (tab) {
      tab.setAttribute('aria-selected', tab.dataset.tab === name ? 'true' : 'false');
    });

    card.querySelectorAll('.auth-panel').forEach(function (panel) {
      panel.hidden = panel.id !== 'auth-panel-' + name;
    });

    card.dataset.tabActive = name;
    headings.forEach(function (el) {
      el.textContent = el.dataset['title' + name.charAt(0).toUpperCase() + name.slice(1)];
    });

    if (cfg.urls[name] && window.history.replaceState) {
      window.history.replaceState(null, '', cfg.urls[name] + window.location.search);
    }
  }

  card.querySelectorAll('[data-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () { showTab(tab.dataset.tab); });
  });

  /* Login method: one-time code / password */
  function showMethod(method) {
    card.querySelectorAll('.auth-methods [data-method]').forEach(function (button) {
      var active = button.dataset.method === method;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    card.querySelectorAll('[data-method-panel]').forEach(function (panel) {
      panel.hidden = panel.dataset.methodPanel !== method;
    });

    var first = card.querySelector('[data-method-panel="' + method + '"] input:not([type=hidden])');
    if (first) first.focus();
  }

  card.querySelectorAll('[data-method]').forEach(function (button) {
    button.addEventListener('click', function () { showMethod(button.dataset.method); });
  });

  /* Password visibility */
  card.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = button.parentNode.querySelector('input');
      var show = 'password' === input.type;
      input.type = show ? 'text' : 'password';
      button.classList.toggle('is-visible', show);
      button.setAttribute('aria-label', show ? cfg.i18n.hide : cfg.i18n.show);
    });
  });

  /* Helpers */
  function request(action, form, extra) {
    var data = new FormData(form);
    data.append('action', action);
    data.append('nonce', cfg.nonce);
    Object.keys(extra || {}).forEach(function (key) { data.append(key, extra[key]); });

    return fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .catch(function () { return { success: false, data: { message: cfg.i18n.error } }; })
      .then(function (json) {
        json = json && typeof json === 'object' ? json : { success: false, data: {} };
        json.data = json.data || {};

        if (json.success && json.data.redirect) {
          window.location.assign(json.data.redirect);
        }

        return json;
      });
  }

  function setMessage(form, text, type) {
    var box = form.querySelector('.auth-message');
    box.textContent = text || '';
    box.className = 'auth-message' + (text ? ' is-' + (type || 'error') : '');
    return box;
  }

  function markField(form, field) {
    form.querySelectorAll('[aria-invalid]').forEach(function (input) { input.removeAttribute('aria-invalid'); });

    var input = field && form.querySelector('[name="' + field + '"]');
    if (input) {
      input.setAttribute('aria-invalid', 'true');
      input.focus();
    }
  }

  function setBusy(form, busy) {
    form.classList.toggle('is-busy', busy);
    form.querySelectorAll('.auth-submit').forEach(function (button) { button.disabled = busy; });
  }

  /* Password login */
  var passwordForm = card.querySelector('[data-form="password"]');

  if (passwordForm) {
    passwordForm.addEventListener('submit', function (event) {
      event.preventDefault();
      setBusy(passwordForm, true);
      setMessage(passwordForm, '');

      request('nasim_auth_password', passwordForm).then(function (res) {
        if (res.success) return;
        setBusy(passwordForm, false);
        setMessage(passwordForm, res.data.message || cfg.i18n.error);
        markField(passwordForm, res.data.field);
      });
    });
  }

  /* Two-step forms: details (mobile or registration fields) -> one-time code */
  function otpForm(form, purpose, verifyAction) {
    if (!form) return;

    var steps = { details: form.querySelector('[data-step="details"]'), code: form.querySelector('[data-step="code"]') };
    var codeInput = form.querySelector('[name="code"]');
    var countdown = form.querySelector('[data-countdown]');
    var resend = form.querySelector('[data-resend]');
    var timer = null;

    function showStep(name) {
      steps.details.hidden = name !== 'details';
      steps.code.hidden = name !== 'code';

      var focus = name === 'code' ? codeInput : steps.details.querySelector('input');
      if (focus) focus.focus();
    }

    function startCountdown(seconds) {
      clearInterval(timer);
      resend.hidden = true;

      var left = Math.max(0, parseInt(seconds, 10) || 0);
      var tick = function () {
        if (left <= 0) {
          clearInterval(timer);
          countdown.textContent = '';
          resend.hidden = false;
          return;
        }
        var clock = Math.floor(left / 60) + ':' + ('0' + (left % 60)).slice(-2);
        countdown.textContent = cfg.i18n.resendIn.replace('%s', faDigits(clock));
        left--;
      };

      tick();
      timer = setInterval(tick, 1000);
    }

    function sendCode() {
      setBusy(form, true);
      setMessage(form, '');

      return request('nasim_auth_send_code', form, { purpose: purpose }).then(function (res) {
        setBusy(form, false);

        if (res.success) {
          if (res.data.redirect) return;
          form.querySelector('[data-masked-mobile]').textContent = res.data.mobile;
          codeInput.value = '';
          showStep('code');
          startCountdown(res.data.resend);
          setMessage(form, res.data.message, 'success');
          return;
        }

        // A code was sent moments ago (e.g. after a reload): continue with it.
        if (res.data.wait) {
          if (steps.code.hidden) {
            form.querySelector('[data-masked-mobile]').textContent = latinDigits(form.querySelector('[name="mobile"]').value).trim();
            showStep('code');
          }
          startCountdown(res.data.wait);
        }

        var box = setMessage(form, res.data.message || cfg.i18n.error);
        if (!res.data.wait) markField(form, res.data.field);

        if (res.data.register) {
          var link = document.createElement('button');
          link.type = 'button';
          link.className = 'auth-link';
          link.textContent = cfg.i18n.register;
          link.addEventListener('click', function () {
            var mobile = form.querySelector('[name="mobile"]').value;
            var target = card.querySelector('[data-form="register"] [name="mobile"]');
            if (target) target.value = mobile;
            showTab('register');
            var name = card.querySelector('[data-form="register"] [name="name"]');
            if (name) name.focus();
          });
          box.appendChild(document.createTextNode(' '));
          box.appendChild(link);
        }
      });
    }

    function verify() {
      codeInput.value = latinDigits(codeInput.value).replace(/\D/g, '');

      if (!codeInput.value) {
        markField(form, 'code');
        return;
      }

      setBusy(form, true);
      setMessage(form, '');

      request(verifyAction, form).then(function (res) {
        if (res.success) return;
        setBusy(form, false);
        setMessage(form, res.data.message || cfg.i18n.error);

        if (res.data.field && res.data.field !== 'code') {
          showStep('details');
          markField(form, res.data.field);
          return;
        }

        codeInput.value = '';
        markField(form, 'code');

        if (res.data.expired) {
          clearInterval(timer);
          countdown.textContent = '';
          resend.hidden = false;
        }
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (form.classList.contains('is-busy')) return;
      steps.code.hidden ? sendCode() : verify();
    });

    codeInput.addEventListener('input', function () {
      codeInput.value = latinDigits(codeInput.value).replace(/\D/g, '').slice(0, codeLength);
      codeInput.removeAttribute('aria-invalid');
      if (codeInput.value.length === codeLength && !form.classList.contains('is-busy')) verify();
    });

    resend.addEventListener('click', sendCode);

    form.querySelector('[data-edit]').addEventListener('click', function () {
      clearInterval(timer);
      setMessage(form, '');
      showStep('details');
    });
  }

  otpForm(card.querySelector('[data-form="otp-login"]'), 'login', 'nasim_auth_otp_login');
  otpForm(card.querySelector('[data-form="register"]'), 'register', 'nasim_auth_register');
})();
