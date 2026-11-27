<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * صفحه «درباره نیلوفر» (مطابق دیزاین ریدیزاین).
 * برای اسلاگ‌های about و connect از page-about.php / page-connect.php فراخوانی می‌شود.
 */
$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$contact_url = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');
$success_url = function_exists('nasim_default_page_url') ? nasim_default_page_url('success', home_url('/success/')) : home_url('/success/');
$radio_url   = function_exists('nasim_radio_url') ? nasim_radio_url() : home_url('/blog/');
$blog_url    = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');
$portrait    = THEME_URL . '/assets/images/brand/portrait-2.jpg';

$check_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 text-violet-300 shrink-0 mt-0.5" aria-hidden="true"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>';

$stats = [
    ['۱۰', 'سال', 'فعالیت متمرکز در حوزه وب', 'از سال ۱۳۹۵'],
    ['۵', 'سال+', 'تجربه تدریس', 'از سال ۱۴۰۰'],
    ['۳۲۹', 'نفر', 'دانشجوی دوره جامع اولترا وب', 'مستند در صفحه دوره'],
    ['۵۳', 'ساعت', 'محتوای آموزشی اولترا وب', 'در ۱۳ فصل'],
];

$timeline = [
    ['۱۳۹۵', 'شروع فعالیت متمرکز در حوزه وب', 'ورود به دنیای طراحی و توسعه وب به‌صورت حرفه‌ای.'],
    ['۱۴۰۰', 'آغاز تجربه تدریس', 'شروع آموزش طراحی سایت به زبان ساده و کاربردی.'],
    ['وبلبز', 'هم‌بنیان‌گذار برند وبلبز', 'ارائه خدمات طراحی سایت و توسعه پروژه‌های واقعی در وبلبز.'],
    ['ژاکت', 'توسعه‌دهنده مارکت ژاکت', 'توسعه‌دهنده قالب اولترا مارکت، اولترا بیز، اولترا گجت و… در مارکت ژاکت.'],
    ['امروز', 'بنیان‌گذار آکادمی نیلوفر وفایی', 'آموزش پروژه‌محور طراحی سایت با پشتیبانی تا ورود به بازار کار.'],
];

$credentials = [
    'کارشناس مهندسی نرم‌افزار',
    'فعال متمرکز در حوزه وب از سال ۱۳۹۵ تاکنون',
    'تجربه تدریس از سال ۱۴۰۰ تاکنون',
    'هم‌بنیان‌گذار برند وبلبز (WebLabs)',
    'توسعه‌دهنده قالب اولترا مارکت، اولترا بیز، اولترا گجت و…',
    'فعال در مارکت ژاکت به‌عنوان توسعه‌دهنده',
    'بلاگر حوزه تکنولوژی در شبکه اینستاگرام',
    'بنیان‌گذار آکادمی آموزشی نیلوفر وفایی',
];

$icon_send = '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>';
$socials = [
    ['اینستاگرام', 'https://instagram.com/niloofarvafaei.ir', '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>'],
    ['یوتیوب', 'https://www.youtube.com/channel/UColubQELWRLE3miuKRr_Kzw', '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>'],
    ['لینکدین', 'https://www.linkedin.com/in/niloofar-vafaei-55b840242/', '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>'],
    ['گیت‌هاب', 'https://github.com/NiIfr', '<path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/>'],
    ['توییتر', 'https://twitter.com/NiIfar77', '<path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>'],
    ['دیسکورد', 'https://discord.gg/nGe6Rv7Cu7', $icon_send],
    ['کانال بله', 'https://ble.ir/niloofarvafaei', $icon_send],
];
?>

<main id="main-content" class="flex-1">

  <!-- ۱) هیرو -->
  <section class="relative overflow-hidden border-b border-border" aria-label="درباره نیلوفر">
    <div class="absolute inset-0 dot-grid opacity-50" aria-hidden="true"></div>
    <div class="container-site relative pt-12 pb-14 md:pt-16 md:pb-20 grid lg:grid-cols-12 gap-10 items-center">
      <div class="lg:col-span-7">
        <div>
          <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">
            <span class="size-1.5 rounded-full bg-violet-400"></span>
            درباره نیلوفر
          </span>
          <h1 class="mt-5 text-2xl md:text-4xl font-black leading-[1.5]">
            طراح و توسعه‌دهنده وب،<br>که مسیر «<span class="mark-violet">شروع از صفر تا پروژه واقعی</span>» را خودش طی کرده
          </h1>
          <p class="mt-5 fa-line text-[15px] md:text-base text-muted-foreground max-w-2xl">
            من نیلوفر وفایی هستم، طراح و توسعه‌دهنده وب با سال‌ها تجربه در طراحی سایت و آموزش. هدف من این است که شما را از یادگیری تئوری به اجرای پروژه‌های واقعی و کسب درآمد از طراحی وب برسانم؛ آموزش از پایه، ساده و کاربردی، با همراهی و پشتیبانی در مسیر یادگیری و آماده‌سازی برای ورود به بازار کار.
          </p>
        </div>
        <div class="mt-7 flex flex-wrap gap-3">
          <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-violet btn-lg rounded-full shadow-[0_8px_20px_-8px_rgba(147,145,255,0.6)] font-bold">
            مشاهده دوره‌ها
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
          <a href="<?php echo esc_url($contact_url); ?>" class="btn btn-outline btn-lg rounded-full font-bold bg-background">مشاوره و سفارش سایت</a>
        </div>
      </div>

      <div class="lg:col-span-5">
        <div class="relative max-w-md mx-auto">
          <div class="absolute inset-0 translate-y-4 rounded-[2rem] bg-violet-300" aria-hidden="true"></div>
          <img src="<?php echo esc_url($portrait); ?>" alt="نیلوفر وفایی؛ مدرس و طراح وب" width="640" height="768" fetchpriority="high" decoding="async" class="relative rounded-[2rem] w-full aspect-[4/4.8] object-cover shadow-lift" />
        </div>
      </div>
    </div>
  </section>

  <!-- ۲) آمار -->
  <section class="border-b border-border bg-card/70" aria-label="آمار و اعتبار">
    <div class="container-site py-10 md:py-12 grid grid-cols-2 lg:grid-cols-4 gap-8">
      <?php foreach ($stats as $st) : ?>
      <div>
        <div class="ps-4 border-s-2 border-violet-400">
          <p class="text-2xl md:text-[2rem] font-black text-ink-900"><?php echo esc_html($st[0]); ?><span class="text-base font-extrabold text-violet-700 ms-1.5"><?php echo esc_html($st[1]); ?></span></p>
          <p class="mt-2 text-[13px] font-bold text-foreground/85"><?php echo esc_html($st[2]); ?></p>
          <p class="text-xs text-muted-foreground mt-0.5"><?php echo esc_html($st[3]); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ۳) مسیر حرفه‌ای (تایم‌لاین) -->
  <section class="container-site py-16 md:py-20" aria-label="مسیر حرفه‌ای">
    <div class="flex flex-col gap-6 mb-10 md:mb-14">
      <div class="max-w-2xl">
        <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-semibold mb-4 bg-accent text-accent-foreground">
          <span class="size-1.5 rounded-full bg-violet-400 inline-block"></span>
          مسیر حرفه‌ای
        </span>
        <h2 class="text-2xl md:text-[2rem] leading-[1.4] font-extrabold text-balance text-foreground">یک دهه ساختن، تدریس کردن و به اشتراک گذاشتن</h2>
      </div>
    </div>

    <div class="relative">
      <div class="absolute top-0 bottom-0 start-[19px] md:start-1/2 w-px bg-border md:-translate-x-1/2" aria-hidden="true"></div>
      <ol class="space-y-8">
        <?php foreach ($timeline as $i => $item) :
            $even = (0 === $i % 2);
        ?>
        <li class="relative flex md:items-center gap-5 <?php echo $even ? 'md:flex-row' : 'md:flex-row-reverse'; ?>">
          <span class="relative z-10 grid place-items-center size-10 rounded-xl bg-ink-900 text-violet-300 text-[12px] font-black shrink-0"><?php echo esc_html($item[0]); ?></span>
          <div class="md:w-1/2 <?php echo $even ? 'md:pe-10' : 'md:ps-10'; ?> md:text-start">
            <div class="rounded-2xl border border-border bg-card p-5 shadow-soft">
              <h3 class="font-extrabold text-[15.5px]"><?php echo esc_html($item[1]); ?></h3>
              <p class="mt-1.5 fa-line text-[13px] text-muted-foreground"><?php echo esc_html($item[2]); ?></p>
            </div>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- ۴) سوابق و تخصص + شبکه‌های اجتماعی -->
  <section class="bg-ink-950 text-cream relative overflow-hidden" aria-label="سوابق حرفه‌ای">
    <div class="absolute inset-0 dot-grid-light opacity-40" aria-hidden="true"></div>
    <div class="container-site relative py-16 md:py-20 grid lg:grid-cols-12 gap-10">
      <div class="lg:col-span-6">
        <div class="flex flex-col gap-6 mb-10 md:mb-14">
          <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-semibold mb-4 bg-ink-800 text-violet-300 border border-ink-700">
              <span class="size-1.5 rounded-full bg-violet-400 inline-block"></span>
              سوابق و تخصص
            </span>
            <h2 class="text-2xl md:text-[2rem] leading-[1.4] font-extrabold text-balance text-cream">خلاصه‌ای از کارنامه حرفه‌ای</h2>
          </div>
        </div>
        <ul class="grid sm:grid-cols-2 gap-x-8 gap-y-3.5">
          <?php foreach ($credentials as $cred) : ?>
          <li class="flex items-start gap-2.5 text-[13.5px] font-semibold text-cream/85 fa-line">
            <?php echo $check_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php echo esc_html($cred); ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="lg:col-span-5 lg:col-start-8">
        <div class="rounded-3xl border border-ink-800 bg-ink-900 p-6 md:p-7">
          <span class="inline-flex items-center gap-3 select-none">
            <span class="relative grid place-items-center size-10 rounded-2xl bg-ink-900 overflow-hidden">
              <span class="absolute -bottom-2 -left-2 size-6 rounded-full bg-violet-400/90"></span>
              <span class="relative font-black text-lg text-ink-950">ن</span>
            </span>
            <span class="flex flex-col leading-none">
              <span class="font-extrabold text-[17px] text-cream">نیلوفر وفایی</span>
              <span class="mt-1 text-[11px] font-medium text-ink-300">آکادمی طراحی سایت</span>
            </span>
          </span>

          <p class="mt-4 fa-line text-[13px] text-ink-200 leading-7">
            برای سفارش طراحی سایت و دیدن نمونه‌کارهای تیمی، برند وبلبز (هم‌بنیان‌گذاری نیلوفر) مرجع اصلی است؛ و برای دنبال کردن محتوای آموزشی و پشت‌صحنه، شبکه‌های اجتماعی زیر بهترین نقطه ارتباط هستند.
          </p>

          <div class="mt-5 grid grid-cols-2 gap-2.5">
            <?php foreach ($socials as $soc) : ?>
            <a href="<?php echo esc_url($soc[1]); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 rounded-xl border border-ink-800 bg-ink-800/60 px-3.5 py-2.5 text-[12.5px] font-bold text-cream/90 hover:border-violet-400/50 hover:text-violet-200 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5" aria-hidden="true"><?php echo $soc[2]; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
              <?php echo esc_html($soc[0]); ?>
            </a>
            <?php endforeach; ?>
          </div>

          <a href="https://weblabs.ir/" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 text-[13px] font-bold text-violet-300 hover:text-violet-200">
            وبلبز — خدمات و نمونه‌کارها
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ۵) پیام برند -->
  <section class="container-site py-16 md:py-20 text-center" aria-label="پیام برند">
    <div>
      <p class="text-[13px] font-bold text-violet-700">پیام اصلی برند</p>
      <blockquote class="mt-4 mx-auto max-w-3xl text-xl md:text-[1.7rem] font-black leading-[1.7] text-balance">
        «از یادگیری طراحی سایت، تا ساخت پروژه‌های واقعی و ورود به بازار کار»
      </blockquote>
      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="<?php echo esc_url($success_url); ?>" class="rounded-full border border-border bg-card px-5 py-2.5 text-[13.5px] font-bold hover:border-violet-400 transition-colors">نتایج دانشجویان</a>
        <a href="<?php echo esc_url($radio_url); ?>" class="rounded-full border border-border bg-card px-5 py-2.5 text-[13.5px] font-bold hover:border-violet-400 transition-colors">رادیو نیل</a>
        <a href="<?php echo esc_url($blog_url); ?>" class="rounded-full border border-border bg-card px-5 py-2.5 text-[13.5px] font-bold hover:border-violet-400 transition-colors">مجله آموزشی</a>
      </div>
    </div>
  </section>

</main>
