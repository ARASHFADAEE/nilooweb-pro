<?php if (!defined('ABSPATH')) {
    exit;
}

$auth_url      = function_exists('nasim_auth_url') ? nasim_auth_url('login') : wp_login_url();
$account_url   = function_exists('nasim_account_url') ? nasim_account_url() : $auth_url;
$account_label = is_user_logged_in() ? 'ورود به پنل کاربری' : 'ورود / ثبت‌نام';
?>
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
      <button class="mobile-menu-button" id="menuToggle" type="button" aria-label="باز کردن منو" aria-expanded="false" aria-controls="navDrawer">
        <span></span><span></span><span></span>
      </button>

      <?php nasim_brand(); ?>

      <nav class="main-nav" id="mainNav" aria-label="منوی اصلی">
        <?php nasim_nav_menu('primary'); ?>
      </nav>

      <div class="nav-actions">
        <button class="icon-button" aria-label="جستجو" id="searchToggle" type="button">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path></svg>
        </button>
        <a class="icon-button nav-account-icon" href="<?php echo esc_url($account_url); ?>" aria-label="<?php echo esc_attr($account_label); ?>" title="<?php echo esc_attr($account_label); ?>"><?php echo nasim_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a>
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

  <div class="nav-drawer" id="navDrawer" hidden>
    <button type="button" class="nav-drawer-backdrop" data-nav-close aria-label="بستن منو"></button>
    <div class="nav-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="navDrawerTitle">
      <div class="nav-drawer-head">
        <div>
          <p class="nav-drawer-kicker">آکادمی نیلوفر وفایی</p>
          <strong id="navDrawerTitle">منوی سایت</strong>
        </div>
        <button type="button" class="nav-drawer-x" data-nav-close aria-label="بستن منو">×</button>
      </div>

      <nav class="nav-drawer-nav" aria-label="منوی موبایل">
        <?php nasim_nav_menu('primary'); ?>
      </nav>

      <div class="nav-drawer-foot">
        <?php if (is_user_logged_in()) : ?>
        <a class="button button-primary nav-drawer-auth" href="<?php echo esc_url(nasim_subscription_url()); ?>">ورود به پنل کاربری</a>
        <?php else : ?>
        <a class="button button-primary nav-drawer-auth" href="<?php echo esc_url($auth_url); ?>">ورود / ثبت‌نام</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
