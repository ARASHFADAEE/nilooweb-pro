(($) => {
  const root = document.getElementById('nasimCurriculum');
  if (!root) return;

  const sectionTemplate = document.getElementById('tmpl-nasim-section').innerHTML;
  const lessonTemplate = document.getElementById('tmpl-nasim-lesson').innerHTML;
  const sectionsList = root.querySelector('.nasim-sections');

  // Keys only need to be unique inside the form; PHP re-indexes them in DOM order on save.
  const uniqueKey = prefix => `${prefix}${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;

  const toElement = html => {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    return wrapper.firstElementChild;
  };

  const makeLessonsSortable = list => {
    $(list).sortable({ handle: '.nasim-handle', items: '> .nasim-lesson', axis: 'y', tolerance: 'pointer' });
  };

  const addLesson = section => {
    const html = lessonTemplate.replaceAll('__S__', section.dataset.key).replaceAll('__L__', uniqueKey('l'));
    const lesson = toElement(html);
    // The block editor saves meta boxes without reloading them, so the stable lesson id must exist before the first save.
    lesson.querySelector('input[name$="[id]"]').value = crypto.getRandomValues(new Uint8Array(6)).reduce((id, byte) => id + byte.toString(16).padStart(2, '0'), '');
    section.querySelector('.nasim-lessons').appendChild(lesson);
    lesson.querySelector('.nasim-lesson-title')?.focus();
  };

  const addSection = () => {
    const key = uniqueKey('s');
    const section = toElement(sectionTemplate.replaceAll('__S__', key));
    sectionsList.appendChild(section);
    makeLessonsSortable(section.querySelector('.nasim-lessons'));
    addLesson(section);
    section.querySelector('.nasim-section-title')?.focus();
  };

  $(sectionsList).sortable({ handle: '.nasim-section-head .nasim-handle', items: '> .nasim-section', axis: 'y', tolerance: 'pointer' });
  root.querySelectorAll('.nasim-lessons').forEach(makeLessonsSortable);

  root.addEventListener('click', event => {
    const target = event.target;

    if (target.closest('.nasim-add-section')) {
      addSection();
      return;
    }

    if (target.closest('.nasim-add-lesson')) {
      addLesson(target.closest('.nasim-section'));
      return;
    }

    if (target.closest('.nasim-remove-lesson')) {
      event.preventDefault();
      if (window.confirm('این درس حذف شود؟')) target.closest('.nasim-lesson').remove();
      return;
    }

    if (target.closest('.nasim-remove-section')) {
      if (window.confirm('این فصل و همه درس‌هایش حذف شود؟')) target.closest('.nasim-section').remove();
    }
  });

  root.addEventListener('input', event => {
    if (!event.target.classList.contains('nasim-lesson-title')) return;
    const preview = event.target.closest('.nasim-lesson').querySelector('.nasim-lesson-preview');
    preview.textContent = event.target.value || 'درس جدید';
  });

  if (!sectionsList.children.length) addSection();
})(jQuery);
