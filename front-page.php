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
                if (nasim_store_enabled()) {
                    get_template_part('template-parts/front-page/products');
                }
                get_template_part('template-parts/front-page/programs');
                get_template_part('template-parts/front-page/about');
                get_template_part('template-parts/front-page/testimonials');
                get_template_part('template-parts/front-page/articles');
                get_template_part('template-parts/front-page/cta');
                ?>
    <div class="container-wide home-hubs">
      <?php nasim_seo_render_hub_links(); ?>
    </div>
                <?php
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