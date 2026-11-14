<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $author_id   = (int) get_the_author_meta('ID');
    $author_name = nasim_post_author_name();
    $author_bio  = nasim_seo_author_bio($author_id);
    $category    = nasim_primary_category();
    $tags        = get_the_tags();
    $content     = nasim_article_content();
    $content_html = nasim_seo_inject_mid_cta($content['html']);
    $updated     = get_the_modified_time('U') - get_the_time('U') > DAY_IN_SECONDS;
    $caption     = has_post_thumbnail() ? wp_get_attachment_caption(get_post_thumbnail_id()) : '';
    $related     = nasim_related_posts(get_the_ID(), 4);
    $blog_label  = nasim_mod('articles_eyebrow') ?: 'مقالات';
    $crumbs      = nasim_seo_breadcrumb_items();
    $cta         = nasim_seo_article_cta();
    $share       = nasim_seo_share_links();
    $toc         = $content['toc'];
    ?>

  <div class="reading-progress" id="readingProgress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="پیشرفت مطالعه"></div>

  <main id="main-content">
    <nav class="container-wide article-breadcrumb" aria-label="مسیر صفحه">
      <?php foreach ($crumbs as $index => $crumb) : ?>
        <?php if ($index > 0) : ?><span aria-hidden="true">／</span><?php endif; ?>
        <?php if ($index === count($crumbs) - 1) : ?>
          <span aria-current="page"><?php echo esc_html($crumb['name']); ?></span>
        <?php else : ?>
          <a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['name']); ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <article <?php post_class('container-wide article-layout'); ?>>
      <div class="article-main">
        <?php if ($category) : ?><a class="article-category" href="<?php echo esc_url(get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a><?php endif; ?>
        <h1><?php the_title(); ?></h1>
        <?php if (has_excerpt()) : ?><p class="article-lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>

        <div class="article-byline">
          <div class="article-author-avatar" aria-hidden="true"><?php echo esc_html(mb_substr($author_name, 0, 1)); ?></div>
          <div><strong><?php echo esc_html($author_name); ?></strong><?php if (get_bloginfo('description')) : ?><small><?php echo esc_html(get_bloginfo('description')); ?></small><?php endif; ?></div>
          <span class="byline-separator"></span>
          <span><?php echo esc_html(nasim_fa_digits(nasim_reading_time())); ?> دقیقه مطالعه</span><span>•</span>
          <time datetime="<?php echo esc_attr(get_the_modified_date('c')); ?>"><?php echo $updated ? 'به‌روزرسانی: ' : 'انتشار: '; ?><?php echo esc_html(nasim_jdate($updated ? get_the_modified_time('U') : get_the_time('U'))); ?></time>
        </div>

        <?php if (has_post_thumbnail()) : ?>
        <figure class="article-cover">
          <?php the_post_thumbnail('large', ['alt' => the_title_attribute(['echo' => false]), 'fetchpriority' => 'high', 'decoding' => 'async']); ?>
          <?php if ($caption) : ?><figcaption><?php echo esc_html($caption); ?></figcaption><?php endif; ?>
        </figure>
        <?php endif; ?>

        <?php if (count($toc) > 1) : ?>
        <nav class="toc-card toc-mobile" aria-label="فهرست مطالب">
          <strong>در این مقاله می‌خوانید</strong>
          <?php foreach ($toc as $heading) : ?>
          <a href="#<?php echo esc_attr($heading['id']); ?>"><?php echo esc_html($heading['title']); ?></a>
          <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <div class="article-body">
          <?php echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content + theme CTA. ?>
          <?php wp_link_pages(['before' => '<nav class="page-links">', 'after' => '</nav>']); ?>
        </div>

        <div class="article-share" aria-label="اشتراک‌گذاری">
          <span>اشتراک‌گذاری:</span>
          <a href="<?php echo esc_url($share['telegram']); ?>" target="_blank" rel="noopener noreferrer">تلگرام</a>
          <a href="<?php echo esc_url($share['whatsapp']); ?>" target="_blank" rel="noopener noreferrer">واتساپ</a>
          <a href="<?php echo esc_url($share['linkedin']); ?>" target="_blank" rel="noopener noreferrer">لینکدین</a>
          <a href="<?php echo esc_url($share['twitter']); ?>" target="_blank" rel="noopener noreferrer">ایکس</a>
        </div>

        <?php if ($tags) : ?>
        <div class="article-tags"><span>برچسب‌ها:</span>
          <?php foreach ($tags as $tag) : ?><a href="<?php echo esc_url(get_tag_link($tag)); ?>"><?php echo esc_html($tag->name); ?></a><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php nasim_seo_render_hub_links(); ?>

        <div class="article-author-box">
          <div class="article-author-avatar large" aria-hidden="true"><?php echo esc_html(mb_substr($author_name, 0, 1)); ?></div>
          <div>
            <strong><?php echo esc_html($author_name); ?></strong>
            <p><?php echo esc_html($author_bio); ?></p>
            <p class="author-bio-links">
              <a class="text-link" href="<?php echo esc_url(nasim_profile_bio_url()); ?>"><?php echo esc_html(function_exists('nasim_profile') ? (nasim_profile()['bio_label'] ?? 'درباره من') : 'درباره من'); ?></a>
              <?php if ($author_id) : ?>
              <span aria-hidden="true">·</span>
              <a class="text-link" href="<?php echo esc_url(get_author_posts_url($author_id)); ?>">همه مطالب</a>
              <?php endif; ?>
            </p>
          </div>
        </div>

        <?php if (comments_open() || get_comments_number()) : ?>
          <?php comments_template(); ?>
        <?php endif; ?>

        <?php if ($related) : ?>
        <section class="related-articles">
          <div class="section-heading"><div><span class="eyebrow">ادامه مطالعه</span><h2>مقالات مرتبط برای <span>مدیران سایت وردپرسی</span></h2></div></div>
          <div class="related-grid">
            <?php foreach ($related as $item) : ?>
            <a href="<?php echo esc_url(get_permalink($item)); ?>" class="related-card">
              <?php nasim_post_thumbnail($item); ?>
              <strong><?php echo esc_html(get_the_title($item)); ?></strong>
              <small><?php echo esc_html(nasim_post_card_meta($item)); ?></small>
            </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>
      </div>

      <aside class="article-sidebar">
        <?php if (count($toc) > 1) : ?>
        <nav class="sidebar-card toc-card toc-desktop" aria-label="فهرست مطالب">
          <strong>در این مقاله می‌خوانید</strong>
          <?php foreach ($toc as $heading) : ?>
          <a href="#<?php echo esc_attr($heading['id']); ?>"><?php echo esc_html($heading['title']); ?></a>
          <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <div class="sidebar-cta">
          <span class="sidebar-cta-icon" aria-hidden="true">✳</span>
          <h3><?php echo esc_html($cta['title']); ?></h3>
          <p><?php echo esc_html($cta['text']); ?></p>
          <a class="button button-primary" href="<?php echo esc_url($cta['url']); ?>"><?php echo esc_html($cta['button']); ?> ←</a>
          <a class="sidebar-cta-secondary" href="<?php echo esc_url(nasim_seo_hubs()['plugins']['url']); ?>">مشاهده افزونه‌ها</a>
        </div>
        <div class="sidebar-card sidebar-links">
          <strong>لینک‌های کلیدی</strong>
          <?php foreach (['support', 'plugins', 'custom', 'courses'] as $hub_key) :
              $hub = nasim_seo_hubs()[$hub_key];
              ?>
          <a href="<?php echo esc_url($hub['url']); ?>"><?php echo esc_html($hub['title']); ?></a>
          <?php endforeach; ?>
        </div>
      </aside>
    </article>
  </main>

  <button type="button" class="back-to-top" id="backToTop" aria-label="بازگشت به بالای صفحه" hidden>↑</button>

<?php
endwhile;

get_footer();
