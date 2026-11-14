<?php if(!defined('ABSPATH')) exit; ?>
<!doctype html>
<html lang="<?php echo esc_attr(get_bloginfo('language')); ?>" dir="rtl">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#7466f0">
  <?php if (is_front_page() && get_bloginfo('description') && !defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION')) : ?>
  <meta name="description" content="<?php echo esc_attr(get_bloginfo('description')); ?>">
  <?php endif; ?>
  <?php wp_head(); ?>
</head>
<body <?php body_class('font-sans bg-slate-50 text-slate-900 antialiased'); ?>>
<?php wp_body_open(); ?>

  <?php if (nasim_mod('announcement_text')) : ?>
  <div class="announcement">
    <div class="container-wide announcement-inner">
      <span class="announcement-dot"></span>
      <p><?php echo esc_html(nasim_mod('announcement_text')); ?></p>
      <?php if (nasim_mod('announcement_link_text')) : ?>
      <a href="<?php echo esc_url(nasim_url(nasim_mod('announcement_link_url'))); ?>"><?php echo esc_html(nasim_mod('announcement_link_text')); ?> <span aria-hidden="true">←</span></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <header class="site-header">
    <div class="container-wide nav-wrap">
      <?php nasim_brand(); ?>

      <button class="mobile-menu-button" id="menuToggle" aria-label="باز کردن منو" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>

      <nav class="main-nav" id="mainNav" aria-label="منوی اصلی">
        <?php nasim_nav_menu('primary'); ?>
      </nav>

      <div class="nav-actions">
        <button class="icon-button" aria-label="جستجو" id="searchToggle">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path></svg>
        </button>
        <a class="icon-button" href="<?php echo esc_url(nasim_account_url()); ?>" aria-label="<?php echo is_user_logged_in() ? 'پنل کاربری' : 'ورود / ثبت‌نام'; ?>" title="<?php echo is_user_logged_in() ? 'پنل کاربری' : 'ورود / ثبت‌نام'; ?>"><?php echo nasim_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a>
        <?php if (nasim_mod('header_cta_text')) : ?>
        <a href="<?php echo esc_url(nasim_url(nasim_mod('header_cta_url'))); ?>" class="button button-primary nav-cta"><?php echo esc_html(nasim_mod('header_cta_text')); ?> <span>←</span></a>
        <?php endif; ?>
      </div>
    </div>
    <form class="search-panel container-wide" id="searchPanel" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" hidden>
      <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="جستجو در دوره‌ها و مقالات..." aria-label="جستجو در سایت">
      <button type="button" class="button button-primary" id="searchClose">بستن</button>
    </form>
  </header>
