<?php if (!defined('ABSPATH')) {
    exit;
}

$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$blog_url    = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');
$radio_url   = nasim_mod('radio_more_url') ?: home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/');
$course_links = function_exists('nasim_niloo_footer_course_links') ? nasim_niloo_footer_course_links() : [];

$socials = array_filter([
    'twitter'   => nasim_mod('social_twitter'),
    'linkedin'  => nasim_mod('social_linkedin'),
    'instagram' => nasim_mod('social_instagram'),
    'youtube'   => nasim_mod('social_youtube'),
    'github'    => nasim_mod('social_github'),
    'discord'   => nasim_mod('social_discord'),
    'telegram'  => nasim_mod('social_telegram'),
]);

$social_labels = [
    'twitter'   => 'Twitter',
    'linkedin'  => 'Linkedin',
    'instagram' => 'Instagram',
    'youtube'   => 'Youtube',
    'github'    => 'Github',
    'discord'   => 'Discord',
    'telegram'  => 'Telegram',
];
?>

  <footer class="site-footer">
    <div class="container-wide footer-grid">
      <div class="footer-brand-col">
        <?php nasim_brand('brand-footer'); ?>
        <?php if (nasim_mod('footer_tagline')) : ?>
        <p><?php echo esc_html(nasim_mod('footer_tagline')); ?></p>
        <?php endif; ?>
        <?php if ($socials) : ?>
        <div class="social-links" aria-label="شبکه‌های اجتماعی">
          <?php foreach ($socials as $key => $url) : ?>
          <a href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($social_labels[$key] ?? $key); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($social_labels[$key] ?? $key); ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="footer-col">
        <strong class="footer-col-title">دوره‌ها</strong>
        <nav aria-label="دوره‌های فوتر">
          <?php if ($course_links) : ?>
            <?php foreach ($course_links as $link) : ?>
            <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['title']); ?></a>
            <?php endforeach; ?>
          <?php else : ?>
            <a href="<?php echo esc_url($courses_url); ?>">همه دوره‌ها</a>
          <?php endif; ?>
        </nav>
      </div>

      <div class="footer-col">
        <strong class="footer-col-title">دسترسی سریع</strong>
        <nav aria-label="دسترسی سریع">
          <?php if (has_nav_menu('footer')) : ?>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'items_wrap'     => '%3$s',
                'depth'          => 1,
                'fallback_cb'    => false,
                'walker'         => new Nasim_Nav_Walker('footer'),
            ]);
            ?>
          <?php else : ?>
            <a href="<?php echo esc_url($courses_url); ?>">دوره‌ها</a>
            <a href="<?php echo esc_url($blog_url); ?>">مقالات</a>
            <a href="<?php echo esc_url($radio_url); ?>">رادیو نیل</a>
            <a href="https://weblabs.ir/web-design/">سفارش سایت</a>
            <a href="<?php echo esc_url(home_url('/connect/')); ?>">معرفی من</a>
          <?php endif; ?>
        </nav>
      </div>

      <div class="footer-col footer-trust">
        <strong class="footer-col-title">اعتماد به ما ✨</strong>
        <p>آکادمی نیلوفر وفایی — آموزش کاربردی طراحی سایت با پروژه واقعی و پشتیبانی تا ورود به بازار کار.</p>
        <a class="button button-outline footer-cta" href="<?php echo esc_url(home_url('/request/')); ?>">مشاوره رایگان</a>
      </div>
    </div>

    <div class="container-wide footer-bottom">
      <span><?php echo esc_html(nasim_mod('footer_copyright') ?: 'تمامی حقوق برای ©نیلوفر وفایی محفوظ می‌باشد.'); ?></span>
    </div>
  </footer>

  <nav class="mobile-bottom-nav" aria-label="میانبر موبایل: خانه، دوره‌ها، پنل">
    <?php nasim_mobile_bottom_nav(); ?>
  </nav>

<?php wp_footer(); ?>
</body>
</html>
