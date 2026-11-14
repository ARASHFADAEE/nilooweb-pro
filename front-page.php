<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

?>

  <main id="main-content">
    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();

            if (nasim_use_theme_front_sections()) {
                get_template_part('template-parts/front-page/hero');
                get_template_part('template-parts/front-page/benefits');
                get_template_part('template-parts/front-page/courses-showcase');
                get_template_part('template-parts/front-page/about');
                get_template_part('template-parts/front-page/testimonials');
                get_template_part('template-parts/front-page/radio-nil');
                get_template_part('template-parts/front-page/articles');
                get_template_part('template-parts/front-page/newsletter');
            } else {
                ?>
    <article <?php post_class('page-layout page-layout--builder'); ?>>
      <div class="page-builder-content entry-content">
        <?php the_content(); ?>
        <?php wp_link_pages(['before' => '<nav class="page-links">', 'after' => '</nav>']); ?>
      </div>
    </article>
                <?php
            }
        endwhile;
    endif;
    ?>
  </main>

<?php
get_footer();