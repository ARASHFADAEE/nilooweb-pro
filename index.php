<?php

if(!defined('ABSPATH')) exit;

[$eyebrow, $title, $highlight, $description] = nasim_archive_heading();
$is_blog_index = !is_search() && !is_archive();

get_header();
?>

  <main id="main-content" class="container-wide blog-archive">
    <nav class="article-breadcrumb" aria-label="مسیر صفحه">
      <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span aria-hidden="true">／</span>
      <?php if ($is_blog_index) : ?>
      <span aria-current="page"><?php echo esc_html(nasim_mod('articles_eyebrow') ?: 'مقالات'); ?></span>
      <?php else : ?>
      <a href="<?php echo esc_url(nasim_blog_url()); ?>"><?php echo esc_html(nasim_mod('articles_eyebrow') ?: 'مقالات'); ?></a><span aria-hidden="true">／</span><span aria-current="page"><?php echo esc_html($highlight); ?></span>
      <?php endif; ?>
    </nav>

    <div class="section-heading blog-heading">
      <div>
        <?php if ($eyebrow) : ?><span class="eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
        <h1><?php echo esc_html($title); ?> <span><?php echo esc_html($highlight); ?></span></h1>
      </div>
      <?php if ($description) : ?><p><?php echo esc_html($description); ?></p><?php endif; ?>
    </div>

    <?php if (is_search()) : ?>
    <form class="blog-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
      <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="دنبال چه مطلبی هستی؟" aria-label="جستجو در سایت">
      <button type="submit" class="button button-primary">جستجو</button>
    </form>
    <?php endif; ?>

    <?php if (have_posts()) : ?>
    <div class="article-grid blog-grid">
      <?php while (have_posts()) : the_post(); ?>
        <?php get_template_part('template-parts/blog/card'); ?>
      <?php endwhile; ?>
    </div>

    <?php the_posts_pagination(['prev_text' => '→', 'next_text' => '←', 'mid_size' => 1]); ?>
    <?php else : ?>
    <p class="blog-empty"><?php echo is_search() ? 'مطلبی با این عبارت پیدا نشد؛ عبارت دیگری را امتحان کن.' : 'هنوز مطلبی منتشر نشده است.'; ?></p>
    <?php endif; ?>

    <?php nasim_seo_render_hub_links(); ?>
  </main>

<?php
get_footer();
