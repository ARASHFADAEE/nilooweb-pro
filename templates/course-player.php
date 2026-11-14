<?php

if(!defined('ABSPATH')) exit;

$course_id   = get_queried_object_id();
$lesson      = nasim_current_lesson();
$lessons     = nasim_get_course_lessons($course_id);
$curriculum  = nasim_get_curriculum($course_id);
$index       = $lesson['number'] - 1;
$prev        = $lessons[$index - 1] ?? null;
$next        = $lessons[$index + 1] ?? null;
$can_watch   = nasim_can_watch_lesson($course_id, $lesson);
$logged_in   = is_user_logged_in();
$has_access  = nasim_user_has_course_access($course_id);
$interactive = $logged_in && $can_watch;
$completed   = nasim_get_user_lesson_list($course_id, 'progress');
$bookmarks   = nasim_get_user_lesson_list($course_id, 'bookmarks');
$is_done     = in_array($lesson['id'], $completed, true);
$progress    = nasim_course_progress($course_id);
$duration    = nasim_format_total_duration(nasim_course_total_seconds($course_id));
$level       = get_post_meta($course_id, '_nasim_course_level', true);
$overview    = nasim_split_lines($lesson['overview'] ?? '');
$total       = nasim_fa_digits(count($lessons));

get_header();
?>

  <main class="container-wide lms-page">
    <div class="lms-breadcrumb">
      <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span>／</span>
      <a href="<?php echo esc_url(get_post_type_archive_link('nasim_course')); ?>">دوره‌ها</a><span>／</span>
      <a href="<?php echo esc_url(get_permalink($course_id)); ?>"><?php echo esc_html(get_the_title($course_id)); ?></a>
    </div>

    <div class="course-heading">
      <div>
        <span class="eyebrow">دوره آموزشی</span>
        <h1><?php echo esc_html(get_the_title($course_id)); ?></h1>
        <?php if (has_excerpt($course_id)) : ?><p><?php echo esc_html(get_the_excerpt($course_id)); ?></p><?php endif; ?>
      </div>
      <div class="course-meta">
        <span>◷ <?php echo esc_html($total); ?> درس</span>
        <?php if ($level) : ?><span>▱ سطح <?php echo esc_html($level); ?></span><?php endif; ?>
        <span>♧ <?php echo $has_access ? 'دسترسی فعال' : 'دسترسی با اشتراک'; ?></span>
      </div>
    </div>

    <div class="lms-layout">
      <section class="lesson-main">
        <div class="video-shell">
          <div class="video-frame">
            <?php if ($can_watch && !empty($lesson['embed'])) : ?>
              <?php echo wp_kses($lesson['embed'], nasim_embed_allowed_html()); ?>
            <?php elseif ($can_watch) : ?>
              <div class="video-placeholder">
                <div class="placeholder-play">▶</div>
                <strong>ویدیوی این درس به‌زودی اضافه می‌شود</strong>
              </div>
            <?php else : ?>
              <div class="video-placeholder video-locked">
                <div class="placeholder-play"><?php echo nasim_lock_icon(); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></div>
                <strong>این درس مخصوص مشترکین است</strong>
                <span>با خرید اشتراک به همه درس‌های این دوره و دوره‌های دیگر دسترسی داری.</span>
                <div class="video-locked-actions">
                  <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url()); ?>">خرید اشتراک <span>←</span></a>
                  <?php if (!$logged_in) : ?>
                  <a class="button button-outline" href="<?php echo esc_url(wp_login_url(nasim_lesson_url($course_id, $lesson['id']))); ?>">ورود به حساب</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="video-underbar">
            <span class="live-dot<?php echo $can_watch ? '' : ' is-locked'; ?>"></span>
            <span><?php echo esc_html($can_watch ? (!empty($lesson['is_free']) && !$has_access ? 'درس رایگان' : 'در حال پخش') : 'قفل — نیاز به اشتراک'); ?></span>
            <a class="subtle-button" href="<?php echo esc_url(get_permalink($course_id)); ?>">صفحه دوره</a>
          </div>
        </div>

        <div class="lesson-title-row">
          <div>
            <span class="lesson-kicker">درس <?php echo esc_html(nasim_fa_digits($lesson['number'])); ?> از <?php echo esc_html($total); ?><?php echo $lesson['section'] ? ' · ' . esc_html($lesson['section']) : ''; ?></span>
            <h2 id="lessonTitle"><?php echo esc_html($lesson['title']); ?></h2>
          </div>
          <?php if ($interactive) :
              $is_bookmarked = in_array($lesson['id'], $bookmarks, true);
              ?>
          <button class="bookmark-button" id="bookmarkButton" aria-pressed="<?php echo $is_bookmarked ? 'true' : 'false'; ?>"><?php echo $is_bookmarked ? '♥ <span>ذخیره شد</span>' : '♡ <span>ذخیره درس</span>'; ?></button>
          <?php endif; ?>
        </div>

        <?php if (!empty($lesson['description'])) : ?>
        <p class="lesson-description"><?php echo esc_html($lesson['description']); ?></p>
        <?php endif; ?>

        <div class="lesson-actions">
          <?php if ($prev) : ?>
            <a class="button button-outline" href="<?php echo esc_url(nasim_lesson_url($course_id, $prev['id'])); ?>">→ درس قبلی</a>
          <?php else : ?>
            <span class="button button-outline is-disabled" aria-disabled="true">→ درس قبلی</span>
          <?php endif; ?>

          <?php if ($interactive) : ?>
            <button class="button button-primary" id="completeLesson" aria-pressed="<?php echo $is_done ? 'true' : 'false'; ?>"><?php echo $is_done ? 'تکمیل شد ✓ (لغو)' : 'علامت‌گذاری به‌عنوان تکمیل‌شده ✓'; ?></button>
          <?php endif; ?>

          <?php if ($next) : ?>
            <a class="button button-outline" href="<?php echo esc_url(nasim_lesson_url($course_id, $next['id'])); ?>">درس بعدی ←</a>
          <?php else : ?>
            <span class="button button-outline is-disabled" aria-disabled="true">درس بعدی ←</span>
          <?php endif; ?>
        </div>

        <?php if ($can_watch) : ?>
        <div class="lesson-tabs" role="tablist">
          <button class="lesson-tab active" data-tab="overview" role="tab">درباره درس</button>
          <?php if ($interactive) : ?><button class="lesson-tab" data-tab="notes" role="tab">یادداشت‌های من</button><?php endif; ?>
          <?php if (!empty($lesson['resource_url'])) : ?><button class="lesson-tab" data-tab="resources" role="tab">فایل‌های همراه</button><?php endif; ?>
        </div>

        <div class="tab-panel" id="tab-overview">
          <h3>در این درس چه یاد می‌گیری؟</h3>
          <?php if ($overview) : ?>
          <ul><?php foreach ($overview as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul>
          <?php else : ?>
          <p><?php echo esc_html($lesson['description'] ?: 'توضیحات این درس به‌زودی اضافه می‌شود.'); ?></p>
          <?php endif; ?>
        </div>

        <?php if ($interactive) : ?>
        <div class="tab-panel" id="tab-notes" hidden>
          <h3>یادداشت‌های شخصی</h3>
          <textarea id="lessonNotes" rows="5" placeholder="نکته‌ها و تمرین‌های این درس را اینجا یادداشت کن..."><?php echo esc_textarea(nasim_get_lesson_note($course_id, $lesson['id'])); ?></textarea>
          <div class="notes-actions"><span id="notesStatus">یادداشت‌ها در حساب کاربری‌ات ذخیره می‌شوند.</span><button class="button button-primary" id="saveNotes">ذخیره یادداشت</button></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($lesson['resource_url'])) : ?>
        <div class="tab-panel" id="tab-resources" hidden>
          <h3>فایل‌های همراه</h3>
          <a class="resource-row" href="<?php echo esc_url($lesson['resource_url']); ?>" target="_blank" rel="noopener">
            <span class="resource-icon"><?php echo esc_html(strtoupper(pathinfo(wp_parse_url($lesson['resource_url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION)) ?: 'FILE'); ?></span>
            <div><strong><?php echo esc_html($lesson['resource_title'] ?: 'دانلود فایل'); ?></strong><small>برای دانلود کلیک کن</small></div>
            <span class="resource-tag">↓</span>
          </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </section>

      <aside class="course-sidebar">
        <?php if ($logged_in && $has_access) : ?>
        <div class="course-progress-card">
          <div class="progress-top"><strong>پیشرفت دوره</strong><span id="progressText"><?php echo esc_html(nasim_fa_digits($progress['percent'])); ?>٪</span></div>
          <div class="progress-track"><span id="progressBar" style="width:<?php echo esc_attr($progress['percent']); ?>%"></span></div>
          <small><span id="completedCount"><?php echo esc_html(nasim_fa_digits($progress['completed'])); ?></span> از <?php echo esc_html($total); ?> درس تکمیل شده</small>
        </div>
        <?php elseif (!$has_access) : ?>
          <?php get_template_part('template-parts/course/subscribe-card'); ?>
        <?php endif; ?>

        <div class="curriculum-card">
          <div class="curriculum-heading">
            <div><h2>محتوای دوره</h2><p><?php echo esc_html($total . ' درس' . ($duration ? ' · ' . $duration : '')); ?></p></div>
            <span class="curriculum-icon">▤</span>
          </div>
          <div class="lesson-list" id="lessonList">
            <?php foreach ($curriculum as $section_index => $section) :
                $section_lessons = $section['lessons'] ?? [];
                $section_ids     = wp_list_pluck($section_lessons, 'id');
                $section_done    = count(array_intersect($section_ids, $completed));
                $section_seconds = array_sum(array_map(function ($item) {
                    return nasim_parse_duration($item['duration'] ?? '');
                }, $section_lessons));
                $section_meta    = nasim_fa_digits(count($section_lessons)) . ' درس' . ($section_seconds ? ' · ' . nasim_format_total_duration($section_seconds) : '');
                $is_open         = in_array($lesson['id'], $section_ids, true);
                ?>
            <details class="player-section<?php echo $section_lessons && $section_done === count($section_lessons) ? ' is-complete' : ''; ?>"<?php echo $is_open ? ' open' : ''; ?>>
              <summary>
                <span class="player-section-index"><?php echo esc_html(nasim_fa_digits($section_index + 1)); ?></span>
                <span class="player-section-copy">
                  <strong><?php echo esc_html($section['title'] ?: 'فصل ' . nasim_fa_digits($section_index + 1)); ?></strong>
                  <small><?php echo esc_html($section_meta); ?></small>
                </span>
                <?php if ($logged_in && $has_access && $section_lessons) : ?>
                <span class="player-section-progress" data-total="<?php echo esc_attr(count($section_lessons)); ?>"><span data-section-done><?php echo esc_html(nasim_fa_digits($section_done)); ?></span>/<?php echo esc_html(nasim_fa_digits(count($section_lessons))); ?></span>
                <?php endif; ?>
                <span class="player-section-chevron" aria-hidden="true">⌄</span>
              </summary>
              <div class="player-section-lessons">
              <?php foreach ($section_lessons as $item) :
                  $item_can_watch = nasim_can_watch_lesson($course_id, $item);
                  $item_is_done   = in_array($item['id'], $completed, true);
                  $is_current     = $item['id'] === $lesson['id'];
                  $classes        = array_filter(['lesson-row', $is_current ? 'selected' : '', $item_is_done ? 'completed' : '', $item_can_watch ? '' : 'locked']);
                  $meta           = array_filter([$item['type'] ?? '', !empty($item['duration']) ? nasim_fa_digits($item['duration']) : '']);
                  ?>
              <a class="<?php echo esc_attr(implode(' ', $classes)); ?>" href="<?php echo esc_url(nasim_lesson_url($course_id, $item['id'])); ?>" data-lesson="<?php echo esc_attr($item['id']); ?>"<?php echo $is_current ? ' aria-current="step"' : ''; ?>>
                <span class="lesson-state"><?php echo $item_is_done ? '✓' : ($item_can_watch ? ($is_current ? '▶' : '▷') : nasim_lock_icon()); // phpcs:ignore WordPress.Security.EscapeOutput -- static markup. ?></span>
                <span class="lesson-row-copy"><strong><?php echo esc_html($item['title']); ?></strong><?php if ($meta) : ?><small><?php echo esc_html(implode(' · ', $meta)); ?></small><?php endif; ?></span>
                <?php if (!empty($item['is_free']) && !$has_access) : ?><span class="free-badge">رایگان</span><?php endif; ?>
                <span class="lesson-done" aria-hidden="true">✓</span>
              </a>
              <?php endforeach; ?>
              <?php if (!$section_lessons) : ?><p class="player-section-empty">درسی در این فصل نیست.</p><?php endif; ?>
              </div>
            </details>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="instructor-card">
          <div class="instructor-avatar"><?php echo esc_html(mb_substr(get_the_author_meta('display_name', get_post_field('post_author', $course_id)), 0, 1)); ?></div>
          <div><strong><?php echo esc_html(get_the_author_meta('display_name', get_post_field('post_author', $course_id))); ?></strong><small>مربی دوره</small></div>
          <a href="<?php echo esc_url(home_url('/#about')); ?>">معرفی ←</a>
        </div>
      </aside>
    </div>
  </main>

<?php
get_footer();
