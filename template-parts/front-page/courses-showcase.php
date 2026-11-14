<?php

if (!defined('ABSPATH')) {
    exit;
}

$count = max(1, (int) nasim_mod('courses_home_count'));
$courses = new WP_Query([
    'post_type'      => 'nasim_course',
    'post_status'    => 'publish',
    'posts_per_page' => $count,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows'  => true,
]);

$archive = nasim_courses_listing_url();
?>
    <section class="section container-wide home-courses" id="courses">
      <div class="section-heading">
        <div>
          <?php if (nasim_mod('courses_home_eyebrow')) : ?>
          <span class="eyebrow"><span class="eyebrow-line"></span> <?php echo esc_html(nasim_mod('courses_home_eyebrow')); ?></span>
          <?php endif; ?>
          <h2><?php echo esc_html(nasim_mod('courses_home_title')); ?> <span class="accent"><?php echo esc_html(nasim_mod('courses_home_title_highlight')); ?></span></h2>
        </div>
        <?php if ($archive) : ?>
        <a class="text-link" href="<?php echo esc_url($archive); ?>">همه دوره‌ها <span>←</span></a>
        <?php endif; ?>
      </div>

      <div class="home-course-grid">
        <?php
        if ($courses->have_posts()) :
            while ($courses->have_posts()) :
                $courses->the_post();
                get_template_part('template-parts/course/card-catalog');
            endwhile;
            wp_reset_postdata();
        else :
            ?>
        <p class="home-course-empty">دوره‌ای هنوز منتشر نشده — پس از ایمپورت از ووکامرس اینجا نمایش داده می‌شود.</p>
            <?php
        endif;
        ?>
      </div>
    </section>
