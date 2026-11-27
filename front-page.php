<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

  <main id="main-content" class="flex-1">
    <?php
    if (nasim_use_theme_front_sections()) {
        get_template_part('template-parts/front-page/hero');
        get_template_part('template-parts/front-page/learning-path');
        get_template_part('template-parts/front-page/courses-showcase');
        get_template_part('template-parts/front-page/bundles');
        get_template_part('template-parts/front-page/benefits');
        get_template_part('template-parts/front-page/testimonials');
        get_template_part('template-parts/front-page/about');
        get_template_part('template-parts/front-page/radio-nil');
        get_template_part('template-parts/front-page/articles');
        get_template_part('template-parts/front-page/newsletter');
    } elseif (have_posts()) {
        while (have_posts()) {
            the_post();
            ?>
    <article <?php post_class('w-full'); ?>>
      <div class="entry-content article-prose">
        <?php the_content(); ?>
        <?php wp_link_pages(['before' => '<nav class="container-site py-4">', 'after' => '</nav>']); ?>
      </div>
    </article>
            <?php
        }
    }
    ?>
  </main>

<?php
get_footer();
