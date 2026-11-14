<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

?>

  <main id="main-content">
    <?php
    // Theme marketing sections must render once — never inside the posts loop
    // (when Reading = "latest posts", the main query has posts_per_page items).
    if (nasim_use_theme_front_sections()) {
        get_template_part('template-parts/front-page/hero');
        get_template_part('template-parts/front-page/benefits');
        get_template_part('template-parts/front-page/courses-showcase');
        get_template_part('template-parts/front-page/about');
        get_template_part('template-parts/front-page/testimonials');
        get_template_part('template-parts/front-page/radio-nil');
        get_template_part('template-parts/front-page/articles');
        get_template_part('template-parts/front-page/newsletter');
    } elseif (have_posts()) {
        while (have_posts()) {
            the_post();
            ?>
    <article <?php post_class('page-layout page-layout--builder'); ?>>
      <div class="page-builder-content entry-content">
        <?php the_content(); ?>
        <?php wp_link_pages(['before' => '<nav class="page-links">', 'after' => '</nav>']); ?>
      </div>
    </article>
            <?php
        }
    }
    ?>
  </main>

<?php
get_footer();
