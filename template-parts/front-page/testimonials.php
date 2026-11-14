<?php

if(!defined('ABSPATH')) exit;

$testimonials = new WP_Query([
    'post_type'      => 'nasim_testimonial',
    'posts_per_page' => max(1, (int) nasim_mod('testimonials_count')),
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows'  => true,
]);

if (!$testimonials->have_posts()) {
    return;
}

$avatar_classes = ['', 'avatar-two', 'avatar-three'];
?>
    <section class="section container-wide" id="testimonials">
      <div class="section-heading">
        <?php nasim_section_title('testimonials'); ?>
        <div class="slider-controls"><button id="testimonialPrev" aria-label="نظر قبلی">→</button><button id="testimonialNext" aria-label="نظر بعدی">←</button></div>
      </div>
      <div class="testimonial-grid" id="testimonialGrid">
        <?php while ($testimonials->have_posts()) : $testimonials->the_post();
            $name         = get_the_title();
            $role         = get_post_meta(get_the_ID(), '_nasim_testimonial_role', true);
            $rating       = (int) get_post_meta(get_the_ID(), '_nasim_testimonial_rating', true) ?: 5;
            $avatar_class = $avatar_classes[$testimonials->current_post % count($avatar_classes)];
            ?>
        <article class="testimonial-card">
          <div class="quote-mark">“</div>
          <p><?php echo esc_html(wp_strip_all_tags(get_the_content())); ?></p>
          <div class="testimonial-person">
            <span class="person-avatar <?php echo esc_attr($avatar_class); ?>"><?php echo esc_html(mb_substr($name, 0, 1)); ?></span>
            <div><strong><?php echo esc_html($name); ?></strong><?php if ($role) : ?><small><?php echo esc_html($role); ?></small><?php endif; ?></div>
            <span class="stars" aria-label="<?php echo esc_attr(sprintf('امتیاز %s از ۵', nasim_fa_digits($rating))); ?>"><?php echo esc_html(str_repeat('★', $rating)); ?></span>
          </div>
        </article>
        <?php endwhile; ?>
      </div>
    </section>
<?php
wp_reset_postdata();
