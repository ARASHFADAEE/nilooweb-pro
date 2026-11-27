<?php

if (!defined('ABSPATH')) {
    exit;
}

$eyebrow   = nasim_mod('courses_home_eyebrow') ?: 'دوره‌های آموزشی';
$title     = nasim_mod('courses_home_title') ?: 'مسیر یادگیری خود را';
$highlight = nasim_mod('courses_home_title_highlight') ?: 'از اینجا شروع کنید';
$count     = max(2, min(12, (int) (nasim_mod('courses_home_count') ?: 6)));
$more_url  = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');

$courses = get_posts([
    'post_type'      => 'nasim_course',
    'post_status'    => 'publish',
    'posts_per_page' => $count,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows'  => true,
]);

if (!$courses) {
    return;
}
?>

<section id="courses" class="container-site py-16 md:py-20" aria-label="دوره‌های منتخب آکادمی">
  <div class="reveal flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
    <div>
      <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">
        <span class="size-1.5 rounded-full bg-violet-600" aria-hidden="true"></span>
        <?php echo esc_html($eyebrow); ?>
      </span>
      <h2 class="mt-4 text-2xl md:text-3xl font-black">
        <?php echo esc_html($title); ?>
        <?php if ($highlight) : ?> <span class="mark-violet"><?php echo esc_html($highlight); ?></span><?php endif; ?>
      </h2>
    </div>
    <a href="<?php echo esc_url($more_url); ?>" class="btn btn-outline rounded-full font-bold self-start md:self-auto">
      مشاهده همه دوره‌ها
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
    </a>
  </div>

  <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6">
    <?php foreach ($courses as $idx => $course) : ?>
      <?php get_template_part('template-parts/course/card-minimal', null, ['course' => $course, 'delay' => ($idx % 3) * 60]); ?>
    <?php endforeach; ?>
  </div>
</section>
