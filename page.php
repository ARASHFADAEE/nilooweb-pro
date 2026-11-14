<?php

if(!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();
    ?>

  <main id="main-content">
    <nav class="container-wide article-breadcrumb" aria-label="مسیر صفحه">
      <?php
      $page_crumbs = nasim_seo_breadcrumb_items();
      foreach ($page_crumbs as $index => $crumb) :
          if ($index > 0) {
              echo '<span aria-hidden="true">／</span>';
          }
          if ($index === count($page_crumbs) - 1) {
              echo '<span aria-current="page">' . esc_html($crumb['name']) . '</span>';
          } else {
              echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['name']) . '</a>';
          }
      endforeach;
      ?>
    </nav>

    <article <?php post_class('container-wide page-layout'); ?>>
      <h1><?php the_title(); ?></h1>

      <?php if (has_post_thumbnail()) : ?>
      <figure class="article-cover"><?php the_post_thumbnail('large', ['alt' => the_title_attribute(['echo' => false])]); ?></figure>
      <?php endif; ?>

      <div class="article-body">
        <?php the_content(); ?>
        <?php wp_link_pages(['before' => '<nav class="page-links">', 'after' => '</nav>']); ?>
      </div>

      <?php if (comments_open() || get_comments_number()) : ?>
        <?php comments_template(); ?>
      <?php endif; ?>
    </article>
  </main>

<?php
endwhile;

get_footer();
