(() => {
  const root = document.querySelector('[data-catalog]');
  if (!root) return;

  const aside = document.getElementById('catalogAside');
  const form = root.querySelector('[data-catalog-form]');
  const toggle = document.getElementById('catalogFilterToggle');
  const loading = root.querySelector('[data-catalog-loading]');
  const clearBtn = root.querySelector('[data-catalog-clear]');
  const mq = window.matchMedia('(max-width: 900px)');

  const isSheet = () => mq.matches;

  const setOpen = (open) => {
    if (!aside || !toggle) return;
    aside.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    document.documentElement.classList.toggle('catalog-sheet-open', open && isSheet());
  };

  const showLoading = () => {
    if (!loading) return;
    loading.hidden = false;
    requestAnimationFrame(() => loading.classList.add('is-on'));
  };

  const setField = (name, value) => {
    const field = form?.querySelector(`[data-catalog-field="${name}"]`);
    if (field) field.value = value ?? '';
  };

  const syncClearVisibility = () => {
    if (!clearBtn || !form) return;
    const cat = form.querySelector('[data-catalog-field="cat"]')?.value || '';
    const level = form.querySelector('[data-catalog-field="level"]')?.value || '';
    const q = form.querySelector('#catalog-q')?.value?.trim() || '';
    const sort = form.querySelector('[data-catalog-sort]')?.value || 'featured';
    const active = !!cat || !!level || !!q || sort !== 'featured';
    clearBtn.hidden = !active;
  };

  toggle?.addEventListener('click', () => {
    if (!isSheet()) return;
    setOpen(!aside.classList.contains('is-open'));
  });

  root.querySelectorAll('[data-catalog-close]').forEach((el) => {
    el.addEventListener('click', () => setOpen(false));
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setOpen(false);
  });

  form?.querySelectorAll('[data-filter]').forEach((chip) => {
    chip.addEventListener('click', () => {
      const name = chip.getAttribute('data-filter');
      const value = chip.getAttribute('data-value') ?? '';
      if (!name || !form) return;

      form.querySelectorAll(`[data-filter="${name}"]`).forEach((sib) => {
        sib.classList.toggle('is-active', sib === chip);
      });
      setField(name, value);
      syncClearVisibility();

      // Desktop sidebar: apply immediately.
      if (!isSheet()) {
        showLoading();
        form.requestSubmit ? form.requestSubmit() : form.submit();
      }
    });
  });

  form?.querySelector('[data-catalog-sort]')?.addEventListener('change', () => {
    syncClearVisibility();
    if (!isSheet()) {
      showLoading();
      form.requestSubmit ? form.requestSubmit() : form.submit();
    }
  });

  clearBtn?.addEventListener('click', () => {
    if (!form) return;
    setField('cat', '');
    setField('level', '');
    const q = form.querySelector('#catalog-q');
    if (q) q.value = '';
    const sort = form.querySelector('[data-catalog-sort]');
    if (sort) sort.value = 'featured';

    form.querySelectorAll('[data-filter]').forEach((chip) => {
      const name = chip.getAttribute('data-filter');
      const value = chip.getAttribute('data-value') ?? '';
      const on = (name === 'cat' || name === 'level') && value === '';
      chip.classList.toggle('is-active', on);
    });
    syncClearVisibility();
  });

  form?.addEventListener('submit', (e) => {
    if (form.dataset.catalogReady === '1') return;

    e.preventDefault();
    ['cat', 'level', 'q'].forEach((name) => {
      const field = form.querySelector(`[name="${name}"]`);
      if (field && !String(field.value || '').trim()) field.disabled = true;
    });
    const sort = form.querySelector('[name="sort"]');
    if (sort && sort.value === 'featured') sort.disabled = true;

    showLoading();
    setOpen(false);
    form.dataset.catalogReady = '1';
    window.setTimeout(() => {
      form.requestSubmit ? form.requestSubmit() : form.submit();
    }, 520);
  });

  mq.addEventListener?.('change', () => {
    if (!isSheet()) setOpen(false);
  });

  syncClearVisibility();
})();
