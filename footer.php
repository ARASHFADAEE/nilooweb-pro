<?php

if (!defined('ABSPATH')) {
    exit;
}

$courses_url  = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$blog_url     = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');
$radio_url    = nasim_mod('radio_more_url') ?: home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/');
$about_url    = function_exists('nasim_default_page_url') ? nasim_default_page_url('about', home_url('/about/')) : home_url('/about/');
$contact_url  = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');
$bundles_url  = get_post_type_archive_link('nasim_bundle') ?: home_url('/bundles/');
$course_links = function_exists('nasim_niloo_footer_course_links') ? nasim_niloo_footer_course_links() : [];
$site_name    = get_bloginfo('name') ?: 'نیلوفر وفایی';
$site_tagline = get_bloginfo('description') ?: 'آکادمی طراحی سایت';

$socials = array_filter([
    'instagram' => nasim_mod('social_instagram'),
    'youtube'   => nasim_mod('social_youtube'),
    'linkedin'  => nasim_mod('social_linkedin'),
    'github'    => nasim_mod('social_github'),
    'twitter'   => nasim_mod('social_twitter'),
    'discord'   => nasim_mod('social_discord'),
    'bale'      => nasim_mod('social_bale') ?: (function_exists('nasim_profile') ? (nasim_profile()['bale'] ?? '') : ''),
    'telegram'  => nasim_mod('social_telegram'),
]);

$social_labels = [
    'instagram' => 'اینستاگرام',
    'youtube'   => 'یوتیوب',
    'linkedin'  => 'لینکدین',
    'github'    => 'گیت‌هاب',
    'twitter'   => 'توییتر / X',
    'discord'   => 'دیسکورد',
    'bale'      => 'کانال بله',
    'telegram'  => 'تلگرام',
];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display status after redirect.
$nl_status = isset($_GET['newsletter']) ? sanitize_key(wp_unslash($_GET['newsletter'])) : '';
?>

  <!-- ═══════════ فوتر ═══════════ -->
  <footer id="site-footer" class="bg-ink-950 text-ink-200 mt-auto">
    <div class="container-site py-14 md:py-16">
      <div class="grid gap-10 md:grid-cols-12">
        <!-- برند و شبکه‌های اجتماعی -->
        <div class="md:col-span-4">
          <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center gap-3 select-none">
            <span class="relative grid place-items-center size-10 rounded-2xl bg-ink-900 border border-ink-800 overflow-hidden">
              <span class="absolute -bottom-2 -left-2 size-6 rounded-full bg-violet-400/90" aria-hidden="true"></span>
              <span class="relative font-black text-lg text-cream"><?php echo esc_html(mb_substr($site_name, 0, 1)); ?></span>
            </span>
            <span class="flex flex-col leading-none">
              <span class="font-extrabold text-[17px] text-cream"><?php echo esc_html($site_name); ?></span>
              <span class="mt-1 text-[11px] font-medium text-ink-300"><?php echo esc_html(mb_strimwidth($site_tagline, 0, 28, '…')); ?></span>
            </span>
          </a>

          <?php if (nasim_mod('footer_tagline')) : ?>
          <p class="mt-5 fa-line text-sm leading-7 text-ink-300 max-w-sm">
            <?php echo esc_html(nasim_mod('footer_tagline')); ?>
          </p>
          <?php endif; ?>

          <?php if ($socials) : ?>
          <div class="mt-6 flex flex-wrap gap-2" aria-label="شبکه‌های اجتماعی">
            <?php foreach ($socials as $key => $url) : ?>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($social_labels[$key] ?? $key); ?>" class="grid place-items-center size-11 rounded-xl bg-ink-900 border border-ink-800 text-ink-200 hover:text-violet-300 hover:border-ink-700 transition-colors [&_svg]:size-4.5">
              <?php echo nasim_icon($key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted SVG from nasim_icon(). ?>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- ستون دوره‌ها -->
        <div class="md:col-span-2">
          <h3 class="text-cream font-bold text-[15px] mb-4">دوره‌ها</h3>
          <ul class="space-y-1 text-sm font-medium">
            <?php if ($course_links) : ?>
              <?php foreach (array_slice($course_links, 0, 4) as $link) : ?>
              <li><a href="<?php echo esc_url($link['url']); ?>" class="block py-2 hover:text-violet-300 transition-colors"><?php echo esc_html($link['title']); ?></a></li>
              <?php endforeach; ?>
            <?php endif; ?>
            <li><a href="<?php echo esc_url($courses_url); ?>" class="block py-2 hover:text-violet-300 transition-colors">همه دوره‌ها</a></li>
            <li><a href="<?php echo esc_url($bundles_url); ?>" class="block py-2 hover:text-violet-300 transition-colors">پکیج‌های دوره</a></li>
          </ul>
        </div>

        <!-- ستون دسترسی سریع -->
        <div class="md:col-span-2">
          <h3 class="text-cream font-bold text-[15px] mb-4">دسترسی سریع</h3>
          <ul class="space-y-1 text-sm font-medium">
            <li><a href="<?php echo esc_url($radio_url); ?>" class="block py-2 hover:text-violet-300 transition-colors">رادیو نیل</a></li>
            <li><a href="<?php echo esc_url($blog_url); ?>" class="block py-2 hover:text-violet-300 transition-colors">مقالات آموزشی</a></li>
            <li><a href="<?php echo esc_url($about_url); ?>" class="block py-2 hover:text-violet-300 transition-colors">درباره نیلوفر</a></li>
            <li><a href="<?php echo esc_url($contact_url); ?>" class="block py-2 hover:text-violet-300 transition-colors">تماس و مشاوره</a></li>
            <li><a href="<?php echo esc_url(function_exists('nasim_account_url') ? nasim_account_url() : home_url('/subscription/')); ?>" class="block py-2 hover:text-violet-300 transition-colors">پنل کاربری</a></li>
          </ul>
        </div>

        <!-- ستون خبرنامه واقعی وردپرس -->
        <div id="newsletter" class="md:col-span-4">
          <h3 class="text-cream font-bold text-[15px] mb-3">خبرنامه آکادمی</h3>
          <p class="fa-line text-sm text-ink-300 leading-7">
            از دوره‌های جدید، قسمت‌های تازه رادیو نیل و نکات پروژه‌گیری زودتر از همه باخبر شو.
          </p>

          <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-validate novalidate class="mt-4">
            <input type="hidden" name="action" value="nasim_newsletter" />
            <?php wp_nonce_field('nasim_newsletter', 'nasim_newsletter_nonce'); ?>
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <div class="flex gap-2 items-start">
              <div data-field class="flex-1 min-w-0">
                <label for="footer-newsletter-email" class="sr-only">ایمیل شما</label>
                <input id="footer-newsletter-email" name="nasim_newsletter_email" type="email" dir="ltr" class="field field-dark" data-rules="required email" placeholder="you@example.com" autocomplete="email" />
                <p class="field-error" hidden></p>
              </div>
              <button type="submit" class="btn btn-violet rounded-xl shrink-0">عضویت</button>
            </div>

            <?php if ('ok' === $nl_status) : ?>
            <p class="mt-3 text-xs font-bold text-mint-300">ایمیلت ثبت شد؛ خوش آمدی به خبرنامه!</p>
            <?php elseif ('invalid' === $nl_status) : ?>
            <p class="mt-3 text-xs font-bold text-red-300">لطفاً یک ایمیل معتبر وارد کن.</p>
            <?php elseif ('limit' === $nl_status) : ?>
            <p class="mt-3 text-xs font-bold text-red-300">تعداد درخواست‌ها زیاد است؛ کمی بعد دوباره تلاش کن.</p>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="mt-12 pt-6 border-t border-ink-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-400 font-medium">
        <span><?php echo esc_html(nasim_mod('footer_copyright') ?: 'تمامی حقوق برای © نیلوفر وفایی محفوظ می‌باشد.'); ?></span>
        <span>ساخته‌شده با عشق برای جامعه طراحان وب ایران</span>
      </div>
    </div>
  </footer>

<?php wp_footer(); ?>
</body>
</html>
