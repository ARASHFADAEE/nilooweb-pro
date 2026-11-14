<?php

if(!defined('ABSPATH')) exit;

/**
 * Curriculum accordion of the course page
 *
 * @var array $args { course_id, curriculum }
 */
$course_id  = $args['course_id'];
$completed  = nasim_get_user_lesson_list($course_id, 'progress');
$number     = 0;
?>
<div class="curriculum-accordion">
  <?php foreach ($args['curriculum'] as $section_index => $section) :
      $section_lessons = $section['lessons'] ?? [];
      $section_seconds = array_sum(array_map(function ($lesson) {
          return nasim_parse_duration($lesson['duration'] ?? '');
      }, $section_lessons));
      ?>
  <details class="curriculum-section" <?php echo 0 === $section_index ? 'open' : ''; ?>>
    <summary>
      <span class="curriculum-section-index"><?php echo esc_html(nasim_fa_digits($section_index + 1)); ?></span>
      <span class="curriculum-section-copy">
        <strong><?php echo esc_html($section['title'] ?: 'فصل ' . nasim_fa_digits($section_index + 1)); ?></strong>
        <small><?php echo esc_html(nasim_fa_digits(count($section_lessons)) . ' درس' . ($section_seconds ? ' · ' . nasim_format_total_duration($section_seconds) : '')); ?></small>
      </span>
      <span class="curriculum-chevron" aria-hidden="true">⌄</span>
    </summary>

    <ol class="curriculum-lessons">
      <?php foreach ($section_lessons as $lesson) :
          $number++;
          $can_watch = nasim_can_watch_lesson($course_id, $lesson);
          $is_done   = in_array($lesson['id'], $completed, true);
          $tag       = $can_watch ? 'a' : 'div';
          ?>
      <li>
        <<?php echo $tag; ?> class="curriculum-lesson<?php echo $can_watch ? '' : ' is-locked'; ?><?php echo $is_done ? ' is-done' : ''; ?>"<?php echo $can_watch ? ' href="' . esc_url(nasim_lesson_url($course_id, $lesson['id'])) . '"' : ''; ?>>
          <span class="curriculum-lesson-state"><?php echo $is_done ? '✓' : ($can_watch ? '▶' : nasim_lock_icon()); // phpcs:ignore WordPress.Security.EscapeOutput -- static markup. ?></span>
          <span class="curriculum-lesson-copy">
            <strong><?php echo esc_html(nasim_fa_digits($number) . '. ' . $lesson['title']); ?></strong>
            <?php if (!empty($lesson['type'])) : ?><small><?php echo esc_html($lesson['type']); ?></small><?php endif; ?>
          </span>
          <?php if (!empty($lesson['is_free'])) : ?><span class="free-badge">رایگان</span><?php endif; ?>
          <?php if (!empty($lesson['duration'])) : ?><span class="curriculum-lesson-duration"><?php echo esc_html(nasim_fa_digits($lesson['duration'])); ?></span><?php endif; ?>
        </<?php echo $tag; ?>>
      </li>
      <?php endforeach; ?>
    </ol>
  </details>
  <?php endforeach; ?>
</div>
