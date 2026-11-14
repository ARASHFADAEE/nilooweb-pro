const menuToggle = document.getElementById('menuToggle');
const mainNav = document.getElementById('mainNav');

menuToggle?.addEventListener('click', () => {
  const isOpen = mainNav.classList.toggle('open');
  menuToggle.setAttribute('aria-expanded', String(isOpen));
  menuToggle.setAttribute('aria-label', isOpen ? 'بستن منو' : 'باز کردن منو');
});

mainNav?.querySelectorAll('a').forEach(link => {
  link.addEventListener('click', () => {
    mainNav.classList.remove('open');
    menuToggle?.setAttribute('aria-expanded', 'false');
  });
});

const searchToggle = document.getElementById('searchToggle');
const searchPanel = document.getElementById('searchPanel');
const searchClose = document.getElementById('searchClose');

searchToggle?.addEventListener('click', () => {
  searchPanel.hidden = !searchPanel.hidden;
  if (!searchPanel.hidden) searchPanel.querySelector('input')?.focus();
});
searchClose?.addEventListener('click', () => { searchPanel.hidden = true; });

const testimonialGrid = document.getElementById('testimonialGrid');
document.getElementById('testimonialNext')?.addEventListener('click', () => {
  testimonialGrid?.scrollBy({ left: -320, behavior: 'smooth' });
});
document.getElementById('testimonialPrev')?.addEventListener('click', () => {
  testimonialGrid?.scrollBy({ left: 320, behavior: 'smooth' });
});

// Keep the active desktop nav item in sync with the section currently being viewed.
// Mobile bottom bar tabs (products / courses / account) are set server-side and must not be cleared.
const sections = [...document.querySelectorAll('main section[id]')];
const sectionNavLinks = [...document.querySelectorAll('.main-nav a[href^="#"], .mobile-bottom-nav a[href^="#"]')];
if ('IntersectionObserver' in window && sectionNavLinks.length) {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const hash = `#${entry.target.id}`;
      sectionNavLinks.forEach(link => {
        const active = link.getAttribute('href') === hash;
        if (link.closest('.main-nav')) {
          link.classList.toggle('active', active);
        } else {
          link.classList.toggle('selected', active);
        }
      });
    });
  }, { rootMargin: '-25% 0px -60% 0px' });
  sections.forEach(section => observer.observe(section));
}

document.querySelectorAll('[data-copy-license]').forEach(button => {
  button.addEventListener('click', async () => {
    const row = button.closest('.license-key-row');
    const code = row?.querySelector('[data-license-key]');
    const text = code?.textContent?.trim();
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
      const original = button.textContent;
      button.textContent = 'کپی شد';
      setTimeout(() => { button.textContent = original; }, 1600);
    } catch (e) {
      const range = document.createRange();
      range.selectNodeContents(code);
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
    }
  });
});

document.querySelectorAll('[data-ticket-file]').forEach(picker => {
  const input = picker.querySelector('input[type="file"]');
  const label = picker.querySelector('.ticket-file-text');
  const max = parseInt(picker.dataset.max || '0', 10);
  input?.addEventListener('change', () => {
    const file = input.files?.[0];
    if (file && max && file.size > max) {
      alert('حجم فایل بیشتر از حد مجاز است.');
      input.value = '';
    }
    const current = input.files?.[0];
    picker.classList.toggle('has-file', !!current);
    if (label) label.textContent = current ? current.name : label.dataset.empty;
  });
});

const shopFilters = document.querySelector('[data-shop-filters]');
if (shopFilters) {
  const backdrop = document.querySelector('.shop-backdrop');
  const setOpen = open => {
    shopFilters.classList.toggle('is-open', open);
    backdrop?.classList.toggle('is-open', open);
    document.documentElement.style.overflow = open ? 'hidden' : '';
  };
  document.querySelectorAll('[data-shop-filters-open]').forEach(btn => btn.addEventListener('click', () => setOpen(true)));
  document.querySelectorAll('[data-shop-filters-close]').forEach(el => el.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
}

document.querySelectorAll('[data-shop-sort]').forEach(select => {
  select.addEventListener('change', () => { if (select.value) window.location.href = select.value; });
});

document.querySelectorAll('[data-ticket-form]').forEach(form => {
  form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (!button) return;
    setTimeout(() => { button.disabled = true; button.textContent = 'در حال ارسال…'; }, 0);
  });
});

/* Article reading progress, back-to-top, and TOC active state */
(() => {
  const progress = document.getElementById('readingProgress');
  const backTop = document.getElementById('backToTop');
  const article = document.querySelector('.article-body');
  if (!article) return;

  const tocLinks = [...document.querySelectorAll('.toc-card a[href^="#"]')];
  const headings = tocLinks
    .map(link => document.getElementById(decodeURIComponent(link.getAttribute('href').slice(1))))
    .filter(Boolean);

  const onScroll = () => {
    const rect = article.getBoundingClientRect();
    const total = article.offsetHeight - window.innerHeight;
    const scrolled = Math.min(Math.max(-rect.top, 0), Math.max(total, 1));
    const ratio = total > 0 ? scrolled / total : 0;

    if (progress) {
      progress.style.transform = `scaleX(${ratio})`;
      progress.setAttribute('aria-valuenow', String(Math.round(ratio * 100)));
    }

    if (backTop) {
      backTop.hidden = window.scrollY < 480;
    }

    if (!headings.length) return;
    let current = headings[0];
    for (const heading of headings) {
      if (heading.getBoundingClientRect().top <= 120) current = heading;
    }
    tocLinks.forEach(link => {
      const active = link.getAttribute('href') === `#${current.id}`;
      link.classList.toggle('is-active', active);
    });
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  backTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();
