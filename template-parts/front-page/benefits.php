<?php

if (!defined('ABSPATH')) {
    exit;
}

$items = [
    [
        'title' => nasim_mod('benefit_1_title') ?: 'پروژه‌محور و عملی',
        'text'  => nasim_mod('benefit_1_text') ?: 'یادگیری با انجام پروژه‌های واقعی که مستقیم وارد رزومه و نمونه‌کارت می‌شود.',
        'icon'  => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/>',
    ],
    [
        'title' => nasim_mod('benefit_2_title') ?: 'پشتیبانی مستقیم مدرس',
        'text'  => nasim_mod('benefit_2_text') ?: 'در طول مسیر تنها نیستی؛ پاسخ سوالات فنی و راهنمایی ورود به بازار کار همین‌جاست.',
        'icon'  => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
    ],
    [
        'title' => nasim_mod('benefit_3_title') ?: 'آپدیت دائم و رایگان',
        'text'  => nasim_mod('benefit_3_text') ?: 'دوره‌ها مطابق با آخرین تغییرات وردپرس، المنتور و بازار کار به‌روزرسانی می‌شوند.',
        'icon'  => '<path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>',
    ],
    [
        'title' => nasim_mod('benefit_4_title') ?: 'جامعه فعال دانشجویان',
        'text'  => nasim_mod('benefit_4_text') ?: 'یادگیری کنار هم‌مسیرهایی که پروژه می‌گیرند، تجربه به اشتراک می‌گذارند و رشد می‌کنند.',
        'icon'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/>',
    ],
];
?>

<section class="bg-cream/70 border-y border-border" aria-label="چرا آموزش با نیلوفر؟">
  <div class="container-site py-16 md:py-20">
    <div class="reveal text-center max-w-2xl mx-auto mb-12">
      <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">چرا آکادمی نیلوفر وفایی؟</span>
      <h2 class="mt-4 text-2xl md:text-3xl font-black">آموزشی که واقعاً به <span class="mark-violet">نتیجه و درآمد</span> می‌رسد</h2>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php foreach ($items as $i => $item) : ?>
      <div class="reveal rounded-3xl border border-border bg-card p-6 shadow-soft" style="--reveal-delay:<?php echo esc_attr($i * 70); ?>ms">
        <span class="grid place-items-center size-12 rounded-2xl bg-violet-100 text-violet-700">
          <svg class="size-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
        </span>
        <h3 class="mt-4 text-[16px] font-extrabold"><?php echo esc_html($item['title']); ?></h3>
        <p class="mt-2 fa-line text-[13.5px] text-muted-foreground"><?php echo esc_html($item['text']); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
