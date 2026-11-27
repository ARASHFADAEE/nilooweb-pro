<?php

if (!defined('ABSPATH')) {
    exit;
}

$logged_in    = is_user_logged_in();
$current_user = $logged_in ? wp_get_current_user() : null;
$auth_url     = function_exists('nasim_auth_url') ? nasim_auth_url('login') : wp_login_url();
$account_url  = function_exists('nasim_account_url') ? nasim_account_url() : $auth_url;
$courses_url  = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$blog_url     = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');
$radio_url    = nasim_mod('radio_more_url') ?: home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/');
$about_url    = function_exists('nasim_default_page_url') ? nasim_default_page_url('about', home_url('/about/')) : home_url('/about/');
$contact_url  = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');
$bundles_url  = get_post_type_archive_link('nasim_bundle') ?: home_url('/bundles/');

$site_name    = get_bloginfo('name') ?: 'نیلوفر وفایی';
$site_tagline = get_bloginfo('description') ?: 'آکادمی طراحی سایت';

// Active navigation detection
$is_home_nav    = is_front_page();
$is_courses_nav = is_page('courses') || is_page_template('page-courses.php') || is_post_type_archive('nasim_course') || is_singular('nasim_course') || is_tax('nasim_course_cat') || is_post_type_archive('nasim_bundle') || is_singular('nasim_bundle');
$radio_slug     = nasim_mod('radio_cat') ?: 'radionil';
$is_radio_nav   = is_category($radio_slug) || (is_singular('post') && has_category($radio_slug));
$is_blog_nav    = !$is_radio_nav && function_exists('nasim_is_blog_context') && nasim_is_blog_context();
$is_about_nav   = is_page(['about', 'connect']);
$is_contact_nav = is_page(['contact', 'request']);

$nav_cls = static function (bool $active): string {
    return $active
        ? 'px-3.5 py-2 rounded-full text-[15px] font-semibold transition-colors text-ink-900 bg-secondary'
        : 'px-3.5 py-2 rounded-full text-[15px] font-semibold transition-colors text-foreground/75 hover:text-foreground hover:bg-secondary/60';
};
$mob_cls = static function (bool $active): string {
    return $active
        ? 'rounded-xl px-4 py-3 text-[15px] font-semibold flex items-center justify-between bg-secondary text-ink-900'
        : 'rounded-xl px-4 py-3 text-[15px] font-semibold flex items-center justify-between text-foreground/80 hover:bg-sand';
};

// Dynamic courses for Mega Menu & Mobile Drawer (grouped into paid vs free)
$mega_courses = get_posts([
    'post_type'      => 'nasim_course',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows'  => true,
]);
$mega_paid = [];
$mega_free = [];
foreach ($mega_courses as $mc) {
    $is_free = function_exists('nasim_course_access_mode') && 'free' === nasim_course_access_mode($mc->ID);
    if ($is_free) {
        $mega_free[] = $mc;
    } else {
        $mega_paid[] = $mc;
    }
}
$mega_paid_col1 = array_slice($mega_paid, 0, 3);
$mega_paid_col2 = array_slice($mega_paid, 3, 3);
$mega_free_col  = array_slice($mega_free, 0, 3);
$has_bundles    = !empty(get_posts(['post_type' => 'nasim_bundle', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true]));
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_bloginfo('language')); ?>" dir="rtl">
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#281e45" />
  <?php if (is_front_page() && get_bloginfo('description') && !defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION')) : ?>
  <meta name="description" content="<?php echo esc_attr(get_bloginfo('description')); ?>" />
  <?php endif; ?>
  <?php if (is_404()) : ?>
  <meta name="robots" content="noindex, follow" />
  <?php endif; ?>
  <?php wp_head(); ?>
</head>
<body <?php body_class('antialiased min-h-screen flex flex-col bg-background text-foreground'); ?>>
<?php wp_body_open(); ?>

  <?php if (nasim_mod('announcement_text') && (!function_exists('nasim_opt') || nasim_opt('announcement_enabled', true))) : ?>
  <div class="bg-ink-950 text-cream border-b border-ink-800/80 text-xs sm:text-[13px] font-semibold">
    <div class="container-site py-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center">
      <span class="size-2 rounded-full bg-mint-300 shrink-0" aria-hidden="true"></span>
      <span><?php echo esc_html(nasim_mod('announcement_text')); ?></span>
      <?php if (nasim_mod('announcement_link_text')) : ?>
      <a href="<?php echo esc_url(nasim_url(nasim_mod('announcement_link_url'))); ?>" class="text-violet-300 hover:text-violet-200 underline decoration-dotted underline-offset-4 font-bold inline-flex items-center gap-1" target="_blank" rel="noopener noreferrer">
        <?php echo esc_html(nasim_mod('announcement_link_text')); ?>
        <span aria-hidden="true">←</span>
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ═══════════ هدر ═══════════ -->
  <header id="site-header" class="sticky top-0 z-50 w-full transition-[background-color,border-color,box-shadow] duration-200 border-b">
    <div class="container-site flex items-center justify-between gap-3 h-[68px]">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="min-w-0 shrink">
        <span class="inline-flex items-center gap-2.5 sm:gap-3 select-none max-w-full">
          <span class="relative grid place-items-center size-10 rounded-2xl bg-ink-900 overflow-hidden shrink-0">
            <span class="absolute -bottom-2 -left-2 size-6 rounded-full bg-violet-400/90" aria-hidden="true"></span>
            <span class="relative font-black text-lg text-cream"><?php echo esc_html(mb_substr($site_name, 0, 1)); ?></span>
          </span>
          <span class="flex flex-col leading-none min-w-0">
            <span class="font-extrabold text-[16px] sm:text-[17px] text-foreground truncate"><?php echo esc_html($site_name); ?></span>
            <span class="mt-1 text-[11px] font-medium text-muted-foreground truncate"><?php echo esc_html(mb_strimwidth($site_tagline, 0, 28, '…')); ?></span>
          </span>
        </span>
      </a>

      <!-- منوی دسکتاپ -->
      <nav class="hidden lg:flex items-center gap-1" aria-label="منوی اصلی">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="<?php echo esc_attr($nav_cls($is_home_nav)); ?>">خانه</a>

        <div class="relative flex items-center" id="mega-wrap">
          <a href="<?php echo esc_url($courses_url); ?>" class="<?php echo esc_attr($nav_cls($is_courses_nav)); ?> inline-flex items-center gap-1">دوره‌ها</a>
          <?php if ($mega_courses) : ?>
          <button type="button" aria-expanded="false" aria-haspopup="true" aria-controls="courses-mega" aria-label="باز و بسته کردن منوی دوره‌ها" id="mega-toggle"
            class="-ms-1 p-2 rounded-full text-foreground/75 hover:text-foreground hover:bg-secondary/60 transition-colors">
            <svg class="size-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </button>

          <div id="courses-mega" class="absolute top-full start-0 pt-3 w-[560px]">
            <div class="rounded-3xl border border-border bg-card shadow-lift p-6 grid grid-cols-3 gap-6">
              <div>
                <p class="text-[13px] font-bold text-muted-foreground mb-3 flex items-center gap-2">
                  <span class="size-1.5 rounded-full bg-violet-400"></span> دوره‌های تخصصی
                </p>
                <ul class="space-y-1.5">
                  <?php foreach ($mega_paid_col1 as $c) :
                      $price_id = (int) get_post_meta($c->ID, '_nasim_linked_plan_id', true);
                      $price    = $price_id ? (int) get_post_meta($price_id, '_nasim_plan_price', true) : 0;
                  ?>
                  <li>
                    <a href="<?php echo esc_url(get_permalink($c->ID)); ?>" class="block rounded-xl px-3 py-2.5 hover:bg-sand transition-colors">
                      <span class="block text-[13.5px] font-bold leading-5 line-clamp-1"><?php echo esc_html($c->post_title); ?></span>
                      <?php if ($price > 0) : ?>
                      <span class="block text-xs font-semibold text-muted-foreground mt-0.5"><?php echo esc_html(nasim_format_price($price)); ?></span>
                      <?php endif; ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div>
                <p class="text-[13px] font-bold text-muted-foreground mb-3 flex items-center gap-2">
                  <span class="size-1.5 rounded-full bg-violet-400"></span> <?php echo $mega_paid_col2 ? 'سایر دوره‌ها' : 'پکیج‌ها و مسیرها'; ?>
                </p>
                <ul class="space-y-1.5">
                  <?php foreach ($mega_paid_col2 as $c) :
                      $price_id = (int) get_post_meta($c->ID, '_nasim_linked_plan_id', true);
                      $price    = $price_id ? (int) get_post_meta($price_id, '_nasim_plan_price', true) : 0;
                  ?>
                  <li>
                    <a href="<?php echo esc_url(get_permalink($c->ID)); ?>" class="block rounded-xl px-3 py-2.5 hover:bg-sand transition-colors">
                      <span class="block text-[13.5px] font-bold leading-5 line-clamp-1"><?php echo esc_html($c->post_title); ?></span>
                      <?php if ($price > 0) : ?>
                      <span class="block text-xs font-semibold text-muted-foreground mt-0.5"><?php echo esc_html(nasim_format_price($price)); ?></span>
                      <?php endif; ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                  <?php if ($has_bundles) : ?>
                  <li>
                    <a href="<?php echo esc_url($bundles_url); ?>" class="block rounded-xl px-3 py-2.5 bg-violet-50 hover:bg-violet-100 transition-colors">
                      <span class="block text-[13.5px] font-bold text-violet-800 leading-5">پکیج‌های اقتصادی</span>
                      <span class="block text-xs font-semibold text-violet-600 mt-0.5">چند دوره با تخفیف ویژه</span>
                    </a>
                  </li>
                  <?php endif; ?>
                </ul>
              </div>

              <div class="rounded-2xl bg-ink-900 p-4 flex flex-col">
                <p class="text-[13px] font-bold text-violet-300 mb-3 flex items-center gap-2">
                  <span class="size-1.5 rounded-full bg-mint-300"></span> رایگان شروع کن
                </p>
                <ul class="space-y-1.5 flex-1">
                  <?php if ($mega_free_col) : ?>
                    <?php foreach ($mega_free_col as $c) : ?>
                    <li><a href="<?php echo esc_url(get_permalink($c->ID)); ?>" class="block rounded-xl px-3 py-2.5 text-cream/90 hover:bg-ink-800 transition-colors text-[13.5px] font-semibold line-clamp-1"><?php echo esc_html($c->post_title); ?></a></li>
                    <?php endforeach; ?>
                  <?php else : ?>
                    <li class="text-xs text-ink-300 px-2 py-2 leading-6">مسیر یادگیری طراحی سایت از صفر تا پروژه واقعی.</li>
                  <?php endif; ?>
                </ul>
                <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-violet w-full mt-3 rounded-xl">مشاهده همه دوره‌ها</a>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <a href="<?php echo esc_url($radio_url); ?>" class="<?php echo esc_attr($nav_cls($is_radio_nav)); ?>">رادیو نیل</a>
        <a href="<?php echo esc_url($blog_url); ?>" class="<?php echo esc_attr($nav_cls($is_blog_nav)); ?>">مقالات</a>
        <a href="<?php echo esc_url($about_url); ?>" class="<?php echo esc_attr($nav_cls($is_about_nav)); ?>">درباره نیلوفر</a>
        <a href="<?php echo esc_url($contact_url); ?>" class="<?php echo esc_attr($nav_cls($is_contact_nav)); ?>">تماس</a>
      </nav>

      <!-- اکشن‌های دسکتاپ -->
      <div class="hidden lg:flex items-center gap-2">
        <button type="button" id="searchToggle" aria-label="جستجو در سایت" aria-expanded="false" aria-controls="searchPanel" class="btn btn-ghost btn-icon rounded-full">
          <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </button>

        <?php if ($logged_in) : ?>
        <a href="<?php echo esc_url($account_url); ?>" class="btn btn-outline rounded-full font-bold gap-2">
          <span class="grid place-items-center size-6 rounded-full bg-violet-400 text-ink-950 text-xs font-black"><?php echo esc_html(mb_substr($current_user->display_name ?: $current_user->user_login, 0, 1)); ?></span>
          <span>پنل کاربری</span>
        </a>
        <?php else : ?>
        <a href="<?php echo esc_url($auth_url); ?>" class="btn btn-ghost font-semibold gap-1.5 rounded-full">
          <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
          ورود
        </a>
        <?php endif; ?>

        <a href="<?php echo esc_url(nasim_url(nasim_mod('header_cta_url')) ?: $courses_url); ?>" class="btn btn-violet rounded-full">
          <?php echo esc_html(nasim_mod('header_cta_text') ?: 'مشاهده دوره‌ها'); ?>
        </a>
      </div>

      <!-- اکشن‌های موبایل -->
      <div class="flex lg:hidden items-center gap-2 shrink-0">
        <a href="<?php echo esc_url($logged_in ? $account_url : $auth_url); ?>" aria-label="<?php echo esc_attr($logged_in ? 'پنل کاربری' : 'ورود / ثبت‌نام'); ?>" class="btn btn-ghost btn-icon">
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
        </a>
        <button type="button" id="mobile-toggle" class="btn btn-outline btn-icon" aria-label="باز کردن منو" aria-expanded="false" aria-controls="mobile-menu">
          <svg data-icon-open class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16"/><path d="M4 18h16"/><path d="M4 6h16"/></svg>
          <svg data-icon-close class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>
    </div>

    <!-- پنل جستجوی هدر -->
    <div id="searchPanel" class="border-t border-border bg-paper/95 backdrop-blur" hidden>
      <form class="container-site py-3 flex items-center gap-2.5" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="جستجو در دوره‌ها، مقالات و رادیو نیل…" aria-label="جستجو در سایت" class="field flex-1" />
        <button type="submit" class="btn btn-violet rounded-xl">جستجو</button>
        <button type="button" id="searchClose" class="btn btn-outline rounded-xl">بستن</button>
      </form>
    </div>

    <!-- کشوی موبایل -->
    <div id="mobile-menu" class="lg:hidden bg-paper" aria-label="منوی موبایل">
      <div class="container-site py-4 flex flex-col gap-1">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="mb-2 flex gap-2">
          <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="جستجو در سایت…" aria-label="جستجو" class="field flex-1" />
          <button type="submit" class="btn btn-outline btn-icon shrink-0" aria-label="جستجو">
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          </button>
        </form>

        <a href="<?php echo esc_url(home_url('/')); ?>" class="<?php echo esc_attr($mob_cls($is_home_nav)); ?>">خانه</a>
        <a href="<?php echo esc_url($courses_url); ?>" class="<?php echo esc_attr($mob_cls($is_courses_nav)); ?>">همه دوره‌ها</a>
        <?php if ($has_bundles) : ?>
        <a href="<?php echo esc_url($bundles_url); ?>" class="<?php echo esc_attr($mob_cls(is_post_type_archive('nasim_bundle') || is_singular('nasim_bundle'))); ?>">پکیج‌های دوره</a>
        <?php endif; ?>
        <a href="<?php echo esc_url($radio_url); ?>" class="<?php echo esc_attr($mob_cls($is_radio_nav)); ?>">رادیو نیل</a>
        <a href="<?php echo esc_url($blog_url); ?>" class="<?php echo esc_attr($mob_cls($is_blog_nav)); ?>">مقالات</a>
        <a href="<?php echo esc_url($about_url); ?>" class="<?php echo esc_attr($mob_cls($is_about_nav)); ?>">درباره نیلوفر</a>
        <a href="<?php echo esc_url($contact_url); ?>" class="<?php echo esc_attr($mob_cls($is_contact_nav)); ?>">تماس</a>

        <?php if ($mega_paid || $mega_free) : ?>
        <p class="mt-3 px-4 text-xs font-bold text-muted-foreground">دسترسی سریع به دوره‌ها</p>
        <div class="px-2 pt-1 pb-2 flex flex-col gap-2">
          <?php if ($mega_paid) : ?>
          <div class="flex flex-wrap gap-2">
            <?php foreach (array_slice($mega_paid, 0, 5) as $c) : ?>
            <a href="<?php echo esc_url(get_permalink($c->ID)); ?>" class="rounded-full border border-border bg-card px-3.5 py-2 text-[13px] font-bold text-ink-700"><?php echo esc_html($c->post_title); ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if ($mega_free) : ?>
          <div class="flex flex-wrap gap-2">
            <?php foreach (array_slice($mega_free, 0, 3) as $c) : ?>
            <a href="<?php echo esc_url(get_permalink($c->ID)); ?>" class="rounded-full border border-mint-200 bg-mint-100 px-3.5 py-2 text-[13px] font-bold text-mint-700 inline-flex items-center gap-1.5">
              <?php echo esc_html($c->post_title); ?>
              <span class="rounded-full bg-mint-600 text-white text-xs font-black px-2 py-0.5">رایگان</span>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="flex gap-2 px-2 pt-3 pb-2">
          <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-violet flex-1 rounded-full">مشاهده دوره‌ها</a>
          <a href="<?php echo esc_url($logged_in ? $account_url : $auth_url); ?>" class="btn btn-outline rounded-full font-semibold">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
            <?php echo $logged_in ? 'پنل کاربری' : 'ورود'; ?>
          </a>
        </div>
      </div>
    </div>
  </header>
