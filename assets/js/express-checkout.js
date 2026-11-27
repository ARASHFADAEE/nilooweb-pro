(() => {
  const cfg = window.nasimExpress || {};
  const modal = document.querySelector('[data-express-modal]');
  const form = document.querySelector('[data-express-form]');
  if (!modal || !form) return;

  const errorEl = form.querySelector('[data-express-error]');
  const statusEl = form.querySelector('[data-express-status]');
  const otpMsg = form.querySelector('[data-express-otp-msg]');
  const stepIdentity = form.querySelector('[data-step="identity"]');
  const stepOtp = form.querySelector('[data-step="otp"]');
  const resendBtn = form.querySelector('[data-express-resend]');
  let resendTimer = null;
  let step = 'identity';

  const qs = (sel) => form.querySelector(sel);

  function showError(msg) {
    if (!errorEl) return;
    errorEl.hidden = !msg;
    errorEl.textContent = msg || '';
  }

  function showStatus(msg) {
    if (!statusEl) return;
    statusEl.hidden = !msg;
    statusEl.textContent = msg || '';
  }

  function setStep(next) {
    step = next;
    if (stepIdentity) {
      stepIdentity.hidden = next !== 'identity';
      stepIdentity.classList.toggle('is-active', next === 'identity');
    }
    if (stepOtp) {
      stepOtp.hidden = next !== 'otp';
      stepOtp.classList.toggle('is-active', next === 'otp');
    }
    showError('');
  }

  function openModal() {
    modal.hidden = false;
    document.body.classList.add('express-modal-open');
    setStep(form.dataset.loggedIn === '1' ? 'identity' : 'identity');
    qs('input[name="name"]')?.focus();
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('express-modal-open');
    showError('');
    showStatus('');
  }

  function startResend(seconds) {
    if (!resendBtn) return;
    let left = Number(seconds) || 90;
    resendBtn.disabled = true;
    const tick = () => {
      resendBtn.textContent = left > 0 ? `ارسال دوباره (${left})` : 'ارسال دوباره کد';
      if (left <= 0) {
        resendBtn.disabled = false;
        clearInterval(resendTimer);
        return;
      }
      left -= 1;
    };
    clearInterval(resendTimer);
    tick();
    resendTimer = setInterval(tick, 1000);
  }

  async function post(action, body) {
    const data = new FormData();
    data.append('action', action);
    data.append('nonce', cfg.nonce || '');
    Object.entries(body).forEach(([k, v]) => data.append(k, v));
    const res = await fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data });
    return res.json();
  }

  document.querySelectorAll('[data-express-open]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal();
    });
  });

  modal.querySelectorAll('[data-express-close]').forEach((el) => {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });

  qs('[data-express-back]')?.addEventListener('click', () => setStep('identity'));

  resendBtn?.addEventListener('click', async () => {
    showError('');
    showStatus('در حال ارسال دوباره…');
    const payload = {
      name: qs('input[name="name"]')?.value || '',
      mobile: qs('input[name="mobile"]')?.value || '',
      plan_id: form.dataset.planId || '',
    };
    const json = await post('nasim_express_send_otp', payload);
    showStatus('');
    if (!json?.success) {
      showError(json?.data?.message || cfg.i18n?.error || 'خطا');
      return;
    }
    if (otpMsg) otpMsg.textContent = json.data.message || '';
    startResend(json.data.resend || 90);
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    showStatus('');

    const name = qs('input[name="name"]')?.value?.trim() || '';
    const mobile = qs('input[name="mobile"]')?.value?.trim() || '';
    const code = qs('input[name="code"]')?.value?.trim() || '';
    const gateway = qs('input[name="gateway"]:checked')?.value || qs('input[name="gateway"][type="hidden"]')?.value || '';
    const loggedIn = form.dataset.loggedIn === '1';

    if (step === 'identity') {
      if (!name || name.length < 2) {
        showError('نام را وارد کن.');
        return;
      }
      if (!loggedIn && !mobile) {
        showError('شماره موبایل را وارد کن.');
        return;
      }

      if (loggedIn) {
        showStatus(cfg.i18n?.paying || 'در حال انتقال به درگاه…');
        const json = await post('nasim_express_pay', {
          plan_id: form.dataset.planId || '',
          course_id: form.dataset.courseId || '',
          return_url: form.dataset.returnUrl || '',
          gateway,
          name,
          mobile,
        });
        if (!json?.success) {
          showStatus('');
          showError(json?.data?.message || cfg.i18n?.error || 'خطا');
          return;
        }
        window.location.href = json.data.redirect;
        return;
      }

      showStatus('در حال ارسال کد…');
      const json = await post('nasim_express_send_otp', {
        name,
        mobile,
        plan_id: form.dataset.planId || '',
      });
      showStatus('');
      if (!json?.success) {
        showError(json?.data?.message || cfg.i18n?.error || 'خطا');
        return;
      }
      if (otpMsg) otpMsg.textContent = json.data.message || '';
      setStep('otp');
      startResend(json.data.resend || 90);
      qs('input[name="code"]')?.focus();
      return;
    }

    if (!code) {
      showError('کد تأیید را وارد کن.');
      return;
    }

    showStatus(cfg.i18n?.paying || 'در حال انتقال به درگاه…');
    const json = await post('nasim_express_pay', {
      plan_id: form.dataset.planId || '',
      course_id: form.dataset.courseId || '',
      return_url: form.dataset.returnUrl || '',
      gateway,
      name,
      mobile,
      code,
    });
    if (!json?.success) {
      showStatus('');
      showError(json?.data?.message || cfg.i18n?.error || 'خطا');
      if (json?.data?.expired) setStep('identity');
      return;
    }
    window.location.href = json.data.redirect;
  });

  // Wire existing CTAs on the course page to the same modal.
  document.querySelectorAll('[data-express-trigger], .subscribe-card .button-primary, .course-hero .hero-actions .button-primary').forEach((btn) => {
    if (btn.matches('[data-express-open]')) return;
    if (btn.closest('[data-express-bar]')) return;
    const href = btn.getAttribute('href') || '';
    if (btn.tagName === 'A' && href && !href.includes('subscription') && !href.includes('buy=course') && !href.includes('highlight=')) {
      return;
    }
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal();
    });
  });
})();
