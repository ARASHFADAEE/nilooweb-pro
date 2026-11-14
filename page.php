<?php

if(!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();

    $is_builder = nasim_is_built_with_elementor(get_the_ID());
    ?>

  <main id="main-content">
    <?php if (!$is_builder) : ?>
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
    <?php endif; ?>

    <article <?php post_class($is_builder ? 'page-layout page-layout--builder' : 'container-wide page-layout'); ?>>
      <?php if (!$is_builder) : ?>
      <h1><?php the_title(); ?></h1>

      <?php if (has_post_thumbnail()) : ?>
      <figure class="article-cover"><?php the_post_thumbnail('large', ['alt' => the_title_attribute(['echo' => false])]); ?></figure>
      <?php endif; ?>
      <?php endif; ?>

      <div class="<?php echo $is_builder ? 'page-builder-content entry-content' : 'article-body'; ?>">
        <?php the_content(); ?>
        <?php wp_link_pages(['before' => '<nav class="page-links">', 'after' => '</nav>']); ?>
      </div>

      <?php if (!$is_builder && (comments_open() || get_comments_number())) : ?>
        <?php comments_template(); ?>
      <?php endif; ?>
    </article>
  </main>

<?php
endwhile;

get_footer();
