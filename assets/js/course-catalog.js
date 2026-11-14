(() => {
  const toggle = document.getElementById('catalogFilterToggle');
  const panel = document.getElementById('catalogAsidePanel');
  if (!toggle || !panel) return;

  toggle.addEventListener('click', () => {
    const open = panel.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(open));
    toggle.textContent = open ? 'بستن فیلترها' : 'فیلترها';
  });
})();
