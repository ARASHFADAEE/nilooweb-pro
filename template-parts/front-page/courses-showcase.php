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
                $outcomes = array_filter(array_map('trim', explode("\n", (string) get_post_meta(get_the_ID(), '_nasim_course_outcomes', true))));
                $plan_id  = (int) get_post_meta(get_the_ID(), '_nasim_linked_plan_id', true);
                $plan     = $plan_id ? nasim_get_plan($plan_id, false) : null;
                $buy_url  = $plan ? nasim_subscription_url(['tab' => 'plans', 'highlight' => $plan_id]) : get_permalink();
                ?>
        <article class="home-course-card">
          <a class="home-course-media" href="<?php the_permalink(); ?>">
            <?php
            $thumb = function_exists('nasim_niloo_course_image_url')
                ? nasim_niloo_course_image_url((int) get_the_ID(), 'large')
                : (string) get_the_post_thumbnail_url(get_the_ID(), 'large');
            if ($thumb) :
                ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async">
            <?php else : ?>
              <span class="home-course-placeholder" aria-hidden="true">◆</span>
            <?php endif; ?>
          </a>
          <div class="home-course-body">
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php if (get_the_excerpt()) : ?>
            <p><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p>
            <?php endif; ?>
            <?php if ($outcomes) : ?>
            <ul class="home-course-features">
              <?php foreach (array_slice($outcomes, 0, 5) as $line) : ?>
              <li><?php echo esc_html($line); ?></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <div class="home-course-actions">
              <a class="button button-primary" href="<?php echo esc_url($buy_url); ?>">شروع کنید <span>←</span></a>
              <a class="text-link" href="<?php the_permalink(); ?>">جزئیات</a>
            </div>
          </div>
        </article>
                <?php
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