<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * صفحه «تماس با ما» (مطابق دیزاین ریدیزاین).
 * فرم به admin-post.php?action=nasim_contact ارسال می‌شود (includes/contact.php).
 */
$account_url = function_exists('nasim_account_url') ? nasim_account_url() : home_url('/account/');
$subjects    = function_exists('nasim_contact_subjects') ? nasim_contact_subjects() : [];
$prefill     = is_user_logged_in() ? wp_get_current_user() : null;
$prefill_nm  = $prefill ? (string) $prefill->display_name : '';
$prefill_ct  = $prefill ? (string) $prefill->user_email : '';

$ble_url      = 'https://ble.ir/niloofarvafaei';
$discord_url  = 'https://discord.gg/nGe6Rv7Cu7';
$instagram    = 'https://instagram.com/niloofarvafaei.ir';
$youtube      = 'https://www.youtube.com/channel/UColubQELWRLE3miuKRr_Kzw';
$linkedin     = 'https://www.linkedin.com/in/niloofar-vafaei-55b840242/';
$github       = 'https://github.com/NiIfr';
$twitter      = 'https://twitter.com/NiIfar77';
$weblabs_url  = 'https://weblabs.ir/web-design/';

$icon_send = '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>';
$socials = [
    ['اینستاگرام', $instagram, '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>'],
    ['یوتیوب', $youtube, '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>'],
    ['لینکدین', $linkedin, '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>'],
    ['گیت‌هاب', $github, '<path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/>'],
    ['توییتر', $twitter, '<path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>'],
    ['دیسکورد', $discord_url, $icon_send],
];
?>

<main id="main-content" class="flex-1">

  <!-- ۱) هیرو -->
  <section class="relative overflow-hidden border-b border-border" aria-label="تماس با ما">
    <div class="absolute inset-0 dot-grid opacity-50" aria-hidden="true"></div>
    <div class="container-site relative pt-12 pb-10 md:pt-16 md:pb-14">
      <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
        تماس و مشاوره
      </span>
      <h1 class="mt-5 text-2xl md:text-4xl font-black">سوال داری؟ <span class="mark-violet">مستقیم بپرس</span></h1>
      <p class="mt-4 fa-line text-[15px] text-muted-foreground max-w-2xl">
        برای مشاوره قبل از خرید دوره، سوالات پشتیبانی یا سفارش طراحی سایت؛ پاسخ مستقیم از خود نیلوفر دریافت می‌شود.
      </p>
    </div>
  </section>

  <section class="container-site py-12 md:py-16" id="contact">
    <div class="grid lg:grid-cols-12 gap-8">

      <!-- فرم تماس -->
      <div class="lg:col-span-7 min-w-0">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate class="rounded-3xl border border-border bg-card p-6 md:p-8 shadow-soft">
          <input type="hidden" name="action" value="nasim_contact" />
          <?php wp_nonce_field('nasim_contact', 'nasim_contact_nonce'); ?>
          <!-- honeypot: بات‌ها این فیلد را پر می‌کنند -->
          <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
            <label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
          </div>

          <h2 class="text-lg font-extrabold mb-6">فرم تماس</h2>

          <div class="grid md:grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="field-label" for="contact-name">نام و نام خانوادگی</label>
              <input type="text" id="contact-name" name="name" class="field" autocomplete="name" required minlength="2" placeholder="مثلاً سارا محمدی" value="<?php echo esc_attr($prefill_nm); ?>" />
            </div>
            <div class="space-y-2">
              <label class="field-label" for="contact-contact">ایمیل یا شماره تماس</label>
              <input type="text" id="contact-contact" name="contact" class="field" dir="ltr" autocomplete="email" required placeholder="مثلاً 09121234567 یا name@mail.com" value="<?php echo esc_attr($prefill_ct); ?>" />
            </div>
          </div>

          <div class="mt-5 space-y-2">
            <label class="field-label" for="contact-subject">موضوع پیام</label>
            <select id="contact-subject" name="subject" class="field">
              <?php foreach ($subjects as $key => $label) : ?>
              <option value="<?php echo esc_attr($key); ?>" <?php selected('consult', $key); ?>><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mt-5 space-y-2">
            <label class="field-label" for="contact-msg">متن پیام</label>
            <textarea id="contact-msg" name="msg" class="field" rows="5" required minlength="5" placeholder="سوالت را دقیق و کوتاه بنویس تا سریع‌تر پاسخ بگیری."></textarea>
          </div>

          <button type="submit" class="btn btn-violet btn-lg w-full mt-6 rounded-2xl font-black shadow-[0_8px_20px_-8px_rgba(147,145,255,0.6)]">
            ارسال پیام
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5" aria-hidden="true"><?php echo $icon_send; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
          </button>

          <p class="mt-3 text-xs font-medium text-muted-foreground text-center">فرم‌ها کوتاه و بدون اطلاعات غیرضروری طراحی شده‌اند.</p>
        </form>
      </div>

      <!-- ستون کناری -->
      <div class="lg:col-span-5 space-y-5 min-w-0">
        <div class="rounded-3xl bg-ink-900 text-cream p-6">
          <h3 class="font-extrabold text-[15.5px] flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 text-violet-300" aria-hidden="true"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
            کانال‌های دسترسی سریع
          </h3>
          <ul class="mt-4 space-y-3">
            <li>
              <a href="<?php echo esc_url($ble_url); ?>" target="_blank" rel="noopener noreferrer" class="block rounded-2xl bg-ink-800/70 border border-ink-800 px-4 py-3.5 hover:bg-ink-800 transition-colors">
                <span class="flex items-center justify-between"><span class="text-[13.5px] font-bold">کانال بله</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-violet-300" aria-hidden="true"><?php echo $icon_send; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg></span>
                <span class="block mt-1 text-xs font-semibold text-ink-300">اعلان آپدیت‌ها و فعالیت‌های روزانه</span>
              </a>
            </li>
            <li>
              <a href="<?php echo esc_url($discord_url); ?>" target="_blank" rel="noopener noreferrer" class="block rounded-2xl bg-ink-800/70 border border-ink-800 px-4 py-3.5 hover:bg-ink-800 transition-colors">
                <span class="flex items-center justify-between"><span class="text-[13.5px] font-bold">سرور دیسکورد آکادمی</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-violet-300" aria-hidden="true"><?php echo $icon_send; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg></span>
                <span class="block mt-1 text-xs font-semibold text-ink-300">جامعه دانشجویان و رفع اشکال</span>
              </a>
            </li>
            <li>
              <a href="<?php echo esc_url($account_url); ?>" class="block rounded-2xl bg-ink-800/70 border border-ink-800 px-4 py-3.5 hover:bg-ink-800 transition-colors">
                <span class="flex items-center justify-between"><span class="text-[13.5px] font-bold">پنل کاربری و تیکت</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-violet-300" aria-hidden="true"><?php echo $icon_send; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg></span>
                <span class="block mt-1 text-xs font-semibold text-ink-300">دانشجویان: پاسخ در کوتاه‌ترین زمان</span>
              </a>
            </li>
          </ul>

          <div class="mt-4 flex flex-wrap gap-2">
            <?php foreach ($socials as $soc) : ?>
            <a href="<?php echo esc_url($soc[1]); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($soc[0]); ?>" class="grid place-items-center size-11 rounded-xl bg-ink-800 border border-ink-700 hover:text-violet-300 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><?php echo $soc[2]; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="rounded-3xl border border-violet-300 bg-violet-100/60 p-6">
          <h3 class="font-extrabold text-[15px] text-violet-800 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5" aria-hidden="true"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
            سفارش طراحی سایت
          </h3>
          <p class="mt-2 fa-line text-[12.5px] text-foreground/75 leading-6">
            طراحی و توسعه سایت تجاری/فروشگاهی توسط تیم وبلبز (هم‌بنیان‌گذاری نیلوفر) انجام می‌شود؛ قیمت‌گذاری شفاف و قرارداد رسمی.
          </p>
          <a href="<?php echo esc_url($weblabs_url); ?>" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 rounded-full bg-ink-900 text-cream px-5 py-3 text-[13px] font-bold hover:bg-ink-800 transition-colors">
            قیمت و شرایط طراحی سایت
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
          </a>
        </div>
      </div>

    </div>
  </section>

</main>
