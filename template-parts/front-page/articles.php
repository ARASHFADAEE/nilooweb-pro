<?php

if(!defined('ABSPATH')) exit;

$articles = new WP_Query([
    'post_type'           => 'post',
    'posts_per_page'      => max(1, (int) nasim_mod('articles_count')),
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

if (!$articles->have_posts()) {
    return;
}
?>
    <section class="section container-wide articles-section" id="articles">
      <div class="section-heading">
        <?php nasim_section_title('articles'); ?>
        <a class="text-link" href="<?php echo esc_url(nasim_blog_url()); ?>">همه مقاله‌ها <span>←</span></a>
      </div>
      <div class="article-grid">
        <?php while ($articles->have_posts()) : $articles->the_post(); ?>
          <?php get_template_part('template-parts/blog/card'); ?>
        <?php endwhile; ?>
      </div>
    </section>
<?php
wp_reset_postdata();
