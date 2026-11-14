<?php

if(!defined('ABSPATH')) exit;

$products = new WP_Query([
    'post_type'      => 'nasim_product',
    'posts_per_page' => max(1, (int) nasim_mod('products_count')),
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
]);

if (!$products->have_posts()) {
    return;
}

$archive_url = get_post_type_archive_link('nasim_product');
?>
    <section class="section container-wide products-section" id="products">
      <div class="section-heading">
        <?php nasim_section_title('products'); ?>
        <?php if ($archive_url) : ?>
        <a class="text-link" href="<?php echo esc_url($archive_url); ?>">همه افزونه‌ها <span>←</span></a>
        <?php endif; ?>
      </div>
      <div class="pcard-grid is-home">
        <?php while ($products->have_posts()) : $products->the_post(); ?>
          <?php get_template_part('template-parts/product/card'); ?>
        <?php endwhile; ?>
      </div>
    </section>
<?php
wp_reset_postdata();
