<?php

if(!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();

    $product_id = get_the_ID();
    $meta       = nasim_get_product_meta($product_id);
    $has_access = nasim_user_can_download_product($product_id);
    $type_label = nasim_product_type_label($meta['type']);
    ?>

  <main class="container-wide course-page product-page">
    <div class="lms-breadcrumb">
      <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span>／</span>
      <a href="<?php echo esc_url(get_post_type_archive_link('nasim_product')); ?>">محصولات</a><span>／</span>
      <span><?php the_title(); ?></span>
    </div>

    <section class="course-hero">
      <div class="course-hero-copy">
        <span class="eyebrow"><span class="eyebrow-line"></span> <?php echo esc_html($type_label); ?></span>
        <h1><?php the_title(); ?></h1>
        <?php if (has_excerpt()) : ?>
        <p class="course-hero-lead"><?php echo esc_html(get_the_excerpt()); ?></p>
        <?php endif; ?>

        <div class="course-meta">
          <?php if ($meta['version']) : ?><span>⬡ نسخه <?php echo esc_html($meta['version']); ?></span><?php endif; ?>
          <?php if ($meta['requires']) : ?><span>وردپرس <?php echo esc_html($meta['requires']); ?>+</span><?php endif; ?>
          <?php if ($meta['tested']) : ?><span>تست تا <?php echo esc_html($meta['tested']); ?></span><?php endif; ?>
          <span>♧ <?php echo $has_access ? 'دانلود فعال' : 'دسترسی با اشتراک'; ?></span>
        </div>

        <div class="hero-actions">
          <?php if ($has_access && $meta['file_id']) : ?>
            <a class="button button-primary button-large" href="<?php echo esc_url(nasim_product_download_url($product_id)); ?>">دانلود ZIP <span>↓</span></a>
            <a class="button button-outline button-large" href="<?php echo esc_url(nasim_subscription_url()); ?>#downloads">مشاهده لایسنس</a>
          <?php elseif ($has_access) : ?>
            <span class="button button-outline button-large is-disabled">فایل هنوز آماده نیست</span>
          <?php else : ?>
            <a class="button button-primary button-large" href="<?php echo esc_url(nasim_subscription_url()); ?>">خرید اشتراک و دانلود <span>←</span></a>
          <?php endif; ?>
          <?php if ($meta['demo_url']) : ?>
            <a class="button button-outline button-large" href="<?php echo esc_url($meta['demo_url']); ?>" target="_blank" rel="noopener">مشاهده پیش‌نمایش <span class="play-icon">▶</span></a>
          <?php endif; ?>
        </div>
      </div>

      <div class="course-hero-media<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>">
        <?php if (has_post_thumbnail()) : ?>
          <?php the_post_thumbnail('large', ['alt' => the_title_attribute(['echo' => false]), 'fetchpriority' => 'high']); ?>
        <?php else : ?>
          <div class="course-hero-placeholder"><span class="brand-mark"><?php echo esc_html(mb_substr(get_bloginfo('name'), 0, 1)); ?></span></div>
        <?php endif; ?>
      </div>
    </section>

    <div class="course-layout">
      <div class="course-main">
        <section class="course-block">
          <h2>درباره محصول</h2>
          <div class="course-content">
            <?php the_content(); ?>
          </div>
        </section>

        <?php if ($meta['changelog']) : ?>
        <section class="course-block">
          <h2>تغییرات نسخه</h2>
          <div class="course-content product-changelog">
            <?php echo wp_kses_post(wpautop(esc_html($meta['changelog']))); ?>
          </div>
        </section>
        <?php endif; ?>
      </div>

      <aside class="course-aside">
        <?php if ($has_access) : ?>
        <div class="subscribe-card">
          <span class="subscribe-card-icon">↓</span>
          <h3>دانلود و لایسنس</h3>
          <p>اشتراکت فعال است؛ فایل و کلید لایسنس در پنل کاربری در دسترس‌اند.</p>
          <?php if ($meta['file_id']) : ?>
          <a class="button button-primary" href="<?php echo esc_url(nasim_product_download_url($product_id)); ?>">دانلود آخرین نسخه <span>↓</span></a>
          <?php endif; ?>
          <a class="button button-outline course-aside-button" href="<?php echo esc_url(nasim_subscription_url()); ?>#downloads">رفتن به پنل</a>
        </div>
        <?php else : ?>
          <?php get_template_part('template-parts/course/subscribe-card'); ?>
        <?php endif; ?>

        <?php if ($meta['demo_url']) : ?>
        <a class="button button-outline product-demo-button" href="<?php echo esc_url($meta['demo_url']); ?>" target="_blank" rel="noopener">مشاهده پیش‌نمایش زنده <span class="play-icon">▶</span></a>
        <?php endif; ?>

        <div class="product-info-card">
          <h3>مشخصات</h3>
          <ul>
            <li><span>نوع</span><strong><?php echo esc_html($type_label); ?></strong></li>
            <?php if ($meta['slug']) : ?><li><span>اسلاگ</span><strong dir="ltr"><?php echo esc_html($meta['slug']); ?></strong></li><?php endif; ?>
            <?php if ($meta['version']) : ?><li><span>نسخه</span><strong dir="ltr"><?php echo esc_html($meta['version']); ?></strong></li><?php endif; ?>
            <?php if ($meta['requires']) : ?><li><span>حداقل وردپرس</span><strong dir="ltr"><?php echo esc_html($meta['requires']); ?></strong></li><?php endif; ?>
            <?php if ($meta['tested']) : ?><li><span>تست‌شده تا</span><strong dir="ltr"><?php echo esc_html($meta['tested']); ?></strong></li><?php endif; ?>
          </ul>
        </div>

        <?php if (function_exists('nasim_ticket_new_url')) :
            $ticket_url = nasim_ticket_new_url($product_id);
            ?>
        <a class="product-support-card" href="<?php echo esc_url(is_user_logged_in() ? $ticket_url : nasim_auth_url('login', $ticket_url)); ?>">
          <span aria-hidden="true">✉</span>
          <div><strong>سؤال یا مشکل درباره این محصول؟</strong><small>یک تیکت پشتیبانی ثبت کن</small></div>
        </a>
        <?php endif; ?>
      </aside>
    </div>
  </main>

<?php
endwhile;

get_footer();
