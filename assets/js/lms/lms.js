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
