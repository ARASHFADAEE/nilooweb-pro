(() => {
  const $ = id => document.getElementById(id);
  const faNumber = value => String(value).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[Number(d)]);

  document.querySelectorAll('.lesson-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.lesson-tab').forEach(item => item.classList.toggle('active', item === tab));
      document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.hidden = panel.id !== `tab-${tab.dataset.tab}`;
      });
    });
  });

  const config = window.nasimLms;
  if (!config) return;

  // Mobile LMS: pin sticky offset to header, keep current lesson in the list viewport.
  const isMobileLms = () => window.matchMedia('(max-width: 800px)').matches;
  const syncStickyOffset = () => {
    if (!document.body.classList.contains('is-course-player') || !isMobileLms()) return;
    const header = document.querySelector('.site-header');
    // Header itself is sticky at top:0 once the announcement scrolls away.
    const top = Math.ceil(header?.offsetHeight || 66);
    document.documentElement.style.setProperty('--lms-sticky-top', `${top}px`);
  };
  syncStickyOffset();
  window.addEventListener('resize', syncStickyOffset, { passive: true });

  const lessonList = document.getElementById('lessonList');
  const currentRow = document.querySelector(`.lesson-row[data-lesson="${config.lessonId}"]`);
  if (currentRow && isMobileLms() && lessonList) {
    requestAnimationFrame(() => {
      const listRect = lessonList.getBoundingClientRect();
      const rowRect = currentRow.getBoundingClientRect();
      if (rowRect.top < listRect.top || rowRect.bottom > listRect.bottom) {
        lessonList.scrollTop += rowRect.top - listRect.top - 12;
      }
    });
  }

  document.querySelectorAll('.player-section').forEach((section) => {
    section.addEventListener('toggle', () => {
      if (!section.open || !isMobileLms() || !lessonList) return;
      const listRect = lessonList.getBoundingClientRect();
      const rect = section.getBoundingClientRect();
      if (rect.top < listRect.top || rect.bottom > listRect.bottom) {
        lessonList.scrollTo({
          top: lessonList.scrollTop + (rect.top - listRect.top) - 8,
          behavior: 'smooth',
        });
      }
    });
  });

  // Direct-file lessons (free courses): Plyr player.
  if (config.usePlyr && window.Plyr) {
    const media = document.getElementById('nasimPlyr');
    if (media) {
      const player = new window.Plyr(media, {
        controls: [
          'play-large',
          'play',
          'progress',
          'current-time',
          'duration',
          'mute',
          'volume',
          'settings',
          'pip',
          'fullscreen',
        ],
        settings: ['speed'],
        speed: { selected: 1, options: [0.75, 1, 1.25, 1.5, 1.75, 2] },
        seekTime: 10,
        keyboard: { focused: true, global: true },
        tooltips: { controls: true, seek: true },
        i18n: {
          play: 'پخش',
          pause: 'توقف',
          mute: 'بی‌صدا',
          unmute: 'با صدا',
          enterFullscreen: 'تمام‌صفحه',
          exitFullscreen: 'خروج از تمام‌صفحه',
          settings: 'تنظیمات',
          speed: 'سرعت',
          normal: 'عادی',
          pip: 'تصویر در تصویر',
        },
      });
      window.nasimPlyrPlayer = player;
    }
  }

  async function sendAction(type, extra = {}) {
    const body = new FormData();
    body.append('action', 'nasim_lesson_action');
    body.append('nonce', config.nonce);
    body.append('course_id', config.courseId);
    body.append('lesson_id', config.lessonId);
    body.append('type', type);
    Object.entries(extra).forEach(([key, value]) => body.append(key, value));

    const response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body });
    const json = await response.json().catch(() => null);
    if (!json?.success) throw new Error(json?.data?.message || config.i18n.error);
    return json.data;
  }

  const completeButton = $('completeLesson');
  completeButton?.addEventListener('click', async () => {
    completeButton.disabled = true;
    try {
      const data = await sendAction('complete');
      completeButton.setAttribute('aria-pressed', String(data.active));
      completeButton.textContent = data.active ? config.i18n.completed : config.i18n.complete;

      const currentRow = document.querySelector(`.lesson-row[data-lesson="${config.lessonId}"]`);
      currentRow?.classList.toggle('completed', data.active);
      const state = currentRow?.querySelector('.lesson-state');
      if (state) state.textContent = data.active ? '✓' : '▶';

      const section = currentRow?.closest('.player-section');
      const sectionProgress = section?.querySelector('.player-section-progress');
      if (sectionProgress) {
        const done = section.querySelectorAll('.lesson-row.completed').length;
        sectionProgress.querySelector('[data-section-done]').textContent = faNumber(done);
        section.classList.toggle('is-complete', done === Number(sectionProgress.dataset.total));
      }

      if ($('progressBar')) $('progressBar').style.width = `${data.percent}%`;
      if ($('progressText')) $('progressText').textContent = `${faNumber(data.percent)}٪`;
      if ($('completedCount')) $('completedCount').textContent = faNumber(data.completed);
    } catch (error) {
      window.alert(error.message);
    } finally {
      completeButton.disabled = false;
    }
  });

  const bookmarkButton = $('bookmarkButton');
  bookmarkButton?.addEventListener('click', async () => {
    bookmarkButton.disabled = true;
    try {
      const data = await sendAction('bookmark');
      bookmarkButton.setAttribute('aria-pressed', String(data.active));
      bookmarkButton.innerHTML = data.active ? config.i18n.bookmarked : config.i18n.bookmark;
    } catch (error) {
      window.alert(error.message);
    } finally {
      bookmarkButton.disabled = false;
    }
  });

  const saveNotes = $('saveNotes');
  saveNotes?.addEventListener('click', async () => {
    saveNotes.disabled = true;
    try {
      await sendAction('note', { note: $('lessonNotes').value });
      $('notesStatus').textContent = config.i18n.noteSaved;
    } catch (error) {
      $('notesStatus').textContent = error.message;
    } finally {
      saveNotes.disabled = false;
    }
  });
})();
