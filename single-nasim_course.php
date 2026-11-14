<?php

if(!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();

    $course_id    = get_the_ID();
    $curriculum   = nasim_get_curriculum($course_id);
    $lessons      = nasim_get_course_lessons($course_id);
    $has_access   = nasim_user_has_course_access($course_id);
    $access_label = nasim_course_access_label($course_id);
    $duration     = nasim_format_total_duration(nasim_course_total_seconds($course_id));
    $level        = get_post_meta($course_id, '_nasim_course_level', true);
    $outcomes     = nasim_split_lines(get_post_meta($course_id, '_nasim_course_outcomes', true));
    $progress     = $has_access ? nasim_course_progress($course_id) : null;
    $next_lesson  = $has_access ? nasim_next_lesson_to_watch($course_id) : null;
    $free_lessons = array_values(array_filter($lessons, function ($lesson) {
        return !empty($lesson['is_free']);
    }));
    $courses_list = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : get_post_type_archive_link('nasim_course');
    ?>

  <main class="container-wide course-page">
    <div class="lms-breadcrumb">
      <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span>／</span>
      <a href="<?php echo esc_url($courses_list ?: home_url('/courses/')); ?>">دوره‌ها</a><span>／</span>
      <span><?php the_title(); ?></span>
    </div>

    <section class="course-hero">
      <div class="course-hero-copy">
        <span class="eyebrow"><span class="eyebrow-line"></span> دوره آموزشی</span>
        <h1><?php the_title(); ?></h1>
        <?php if (has_excerpt()) : ?>
        <p class="course-hero-lead"><?php echo esc_html(get_the_excerpt()); ?></p>
        <?php endif; ?>

        <div class="course-meta">
          <span class="<?php echo esc_attr(nasim_course_access_badge_class($course_id)); ?>"><?php echo esc_html($access_label); ?></span>
          <span>◷ <?php echo esc_html(nasim_fa_digits(count($lessons))); ?> درس</span>
          <?php if ($duration) : ?><span>⧗ <?php echo esc_html($duration); ?></span><?php endif; ?>
          <?php if ($level) : ?><span>▱ سطح <?php echo esc_html($level); ?></span><?php endif; ?>
          <span>♧ <?php echo $has_access ? 'دسترسی فعال' : esc_html($access_label); ?></span>
        </div>

        <div class="hero-actions">
          <?php if ($has_access && $next_lesson) : ?>
            <a class="button button-primary button-large" href="<?php echo esc_url(nasim_lesson_url($course_id, $next_lesson['id'])); ?>"><?php echo $progress['completed'] ? 'ادامه دوره' : 'شروع دوره'; ?> <span>←</span></a>
          <?php elseif (!$has_access) : ?>
            <a class="button button-primary button-large" href="<?php echo esc_url(nasim_course_checkout_url($course_id)); ?>"><?php echo esc_html(nasim_course_cta_label($course_id)); ?> <span>←</span></a>
          <?php endif; ?>
          <?php if (!$has_access && $free_lessons) : ?>
            <a class="button button-outline button-large" href="<?php echo esc_url(nasim_lesson_url($course_id, $free_lessons[0]['id'])); ?>">مشاهده درس رایگان <span class="play-icon">▶</span></a>
          <?php elseif ($lessons) : ?>
            <a class="button button-outline button-large" href="#curriculum">سرفصل‌ها <span>↓</span></a>
          <?php endif; ?>
        </div>
      </div>

      <div class="course-hero-media<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>">
        <?php if (has_post_thumbnail()) : ?>
          <?php the_post_thumbnail('large', ['alt' => the_title_attribute(['echo' => false]), 'fetchpriority' => 'high']); ?>
        <?php else : ?>
          <div class="course-hero-placeholder"><span class="brand-mark">ن</span></div>
        <?php endif; ?>
        <?php if ($lessons) : ?>
        <a class="course-hero-play" href="<?php echo esc_url(nasim_lesson_url($course_id, ($next_lesson ?: ($free_lessons[0] ?? $lessons[0]))['id'])); ?>" aria-label="پخش دوره"><span>▶</span></a>
        <?php endif; ?>
      </div>
    </section>

    <div class="course-layout">
      <div class="course-main">

        <?php if ($outcomes) : ?>
        <section class="course-block">
          <h2>در این دوره یاد می‌گیری</h2>
          <ul class="outcome-list">
            <?php foreach ($outcomes as $outcome) : ?>
            <li><span>✓</span><?php echo esc_html($outcome); ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
        <?php endif; ?>

        <?php if (get_the_content()) : ?>
        <section class="course-block">
          <h2>درباره دوره</h2>
          <div class="course-content"><?php the_content(); ?></div>
        </section>
        <?php endif; ?>

        <?php if ($curriculum) : ?>
        <section class="course-block" id="curriculum">
          <div class="course-block-heading">
            <h2>سرفصل‌های دوره</h2>
            <span><?php echo esc_html(nasim_fa_digits(count($curriculum)) . ' فصل · ' . nasim_fa_digits(count($lessons)) . ' درس' . ($duration ? ' · ' . $duration : '')); ?></span>
          </div>
          <?php get_template_part('template-parts/course/curriculum', null, ['course_id' => $course_id, 'curriculum' => $curriculum]); ?>
        </section>
        <?php endif; ?>
      </div>

      <aside class="course-aside">
        <?php if ($has_access) : ?>
          <div class="course-progress-card">
            <div class="progress-top"><strong>پیشرفت دوره</strong><span><?php echo esc_html(nasim_fa_digits($progress['percent'])); ?>٪</span></div>
            <div class="progress-track"><span style="width:<?php echo esc_attr($progress['percent']); ?>%"></span></div>
            <small><?php echo esc_html(nasim_fa_digits($progress['completed']) . ' از ' . nasim_fa_digits($progress['total'])); ?> درس تکمیل شده</small>
            <?php if ($next_lesson) : ?>
            <a class="button button-primary course-aside-button" href="<?php echo esc_url(nasim_lesson_url($course_id, $next_lesson['id'])); ?>"><?php echo $progress['completed'] ? 'ادامه یادگیری' : 'شروع یادگیری'; ?> <span>←</span></a>
            <?php endif; ?>
          </div>
        <?php else : ?>
          <?php get_template_part('template-parts/course/subscribe-card', null, ['course_id' => $course_id]); ?>
        <?php endif; ?>

        <div class="instructor-card">
          <div class="instructor-avatar"><?php echo esc_html(mb_substr(get_the_author(), 0, 1)); ?></div>
          <div><strong><?php the_author(); ?></strong><small>مربی دوره</small></div>
          <a href="<?php echo esc_url(home_url('/#about')); ?>">معرفی ←</a>
        </div>
      </aside>
    </div>
  </main>

<?php
endwhile;

get_footer();
