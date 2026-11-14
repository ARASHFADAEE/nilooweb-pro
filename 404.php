<?php

if(!defined('ABSPATH')) exit;

get_header();
?>

  <main class="container-wide not-found">
    <span class="not-found-code" aria-hidden="true">۴۰۴</span>
    <h1>این صفحه پیدا نشد</h1>
    <p>ممکن است آدرس اشتباه باشد یا صفحه جابه‌جا شده باشد. از جستجو کمک بگیر یا به صفحه اصلی برگرد.</p>

    <form class="blog-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
      <input type="search" name="s" placeholder="دنبال چه مطلبی هستی؟" aria-label="جستجو در سایت">
      <button type="submit" class="button button-primary">جستجو</button>
    </form>

    <div class="not-found-actions">
      <a class="button button-primary" href="<?php echo esc_url(home_url('/')); ?>">صفحه اصلی <span>←</span></a>
      <a class="button button-outline" href="<?php echo esc_url(get_post_type_archive_link('nasim_course')); ?>">دوره‌ها</a>
      <a class="button button-outline" href="<?php echo esc_url(nasim_blog_url()); ?>">مقالات</a>
    </div>
  </main>

<?php
get_footer();
