<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * لیست قسمت‌های رادیو نیل (آرشیو دسته رادیو) با پخش‌کننده صوتی و سایدبار.
 */
$contact_url = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');

$capsule_post = get_page_by_path('capsule-ultra', OBJECT, 'nasim_course');
$capsule_url  = ($capsule_post instanceof WP_Post && 'publish' === $capsule_post->post_status)
    ? get_permalink($capsule_post->ID)
    : (function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/'));
?>

<section class="container-site py-14 md:py-18" aria-label="قسمت‌های رادیو نیل">
  <div class="grid lg:grid-cols-12 gap-8">

    <!-- لیست قسمت‌ها -->
    <div class="lg:col-span-8 space-y-5 min-w-0">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post();
            $pid       = get_the_ID();
            $slug      = (string) get_post_field('post_name', $pid);
            $ep_num    = preg_match('/ep0*([0-9]+)$/i', $slug, $m) ? (int) $m[1] : 0;
            $audio_url = function_exists('nasim_radio_audio_url') ? nasim_radio_audio_url($pid) : '';
            $excerpt   = wp_strip_all_tags(get_the_excerpt());
            $excerpt   = trim(preg_replace('/\s*\[…\]\s*$|\s*\[&hellip;\]\s*$/u', '', $excerpt));
        ?>
        <div>
          <div class="rounded-3xl border border-border bg-card p-5 md:p-6 shadow-soft" data-radio-player>
            <div class="flex items-start gap-4">
              <?php if ($audio_url) : ?>
              <button type="button" data-radio-toggle aria-label="پخش قسمت" aria-pressed="false" class="grid place-items-center size-14 rounded-2xl shrink-0 transition-colors bg-ink-900 text-violet-300 hover:bg-ink-800">
                <svg data-icon-play xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 me-0.5" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                <svg data-icon-pause xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6" aria-hidden="true" hidden><rect x="14" y="4" width="4" height="16" rx="1"/><rect x="6" y="4" width="4" height="16" rx="1"/></svg>
              </button>
              <?php else : ?>
              <a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr('پخش و جزئیات ' . get_the_title()); ?>" class="grid place-items-center size-14 rounded-2xl shrink-0 transition-colors bg-ink-900 text-violet-300 hover:bg-ink-800">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 me-0.5" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg>
              </a>
              <?php endif; ?>

              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-ink-900 text-violet-300 text-xs font-black px-2.5 py-1">
                    <?php echo $ep_num ? esc_html('قسمت ' . nasim_fa_digits($ep_num)) : 'رادیو نیل'; ?>
                  </span>
                  <span class="text-xs font-bold text-muted-foreground"><?php echo esc_html(get_the_date()); ?></span>
                </div>
                <h3 class="mt-2 text-[15.5px] font-extrabold leading-7">
                  <a href="<?php the_permalink(); ?>" class="hover:text-violet-700 transition-colors"><?php the_title(); ?></a>
                </h3>
                <?php if ($excerpt) : ?>
                <p class="mt-1.5 fa-line text-[13px] leading-6 text-muted-foreground line-clamp-2"><?php echo esc_html($excerpt); ?></p>
                <?php endif; ?>
              </div>
            </div>

            <?php if ($audio_url) : ?>
            <audio src="<?php echo esc_url($audio_url); ?>" preload="none" class="hidden" data-radio-audio></audio>
            <div class="mt-4 flex items-center gap-3">
              <div class="h-2 flex-1 rounded-full bg-sand overflow-hidden cursor-pointer" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="پیشرفت پخش" data-radio-track>
                <div class="h-full rounded-full bg-gradient-to-l from-violet-400 to-mint-400 transition-[width] duration-300" style="width: 0%;" data-radio-bar></div>
              </div>
              <span class="text-xs font-bold text-muted-foreground shrink-0" data-radio-time>MP3</span>
            </div>
            <?php endif; ?>
          </div>

          <a href="<?php the_permalink(); ?>" class="mt-2 inline-flex items-center gap-1.5 px-3 py-2 -my-1 rounded-xl text-[13px] font-bold text-ink-600 hover:text-ink-900 hover:bg-sand transition-colors">
            صفحه این قسمت
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
        </div>
        <?php endwhile; ?>

        <?php the_posts_pagination(['mid_size' => 1, 'prev_text' => '→ قبلی', 'next_text' => 'بعدی ←']); ?>
      <?php else : ?>
        <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
          <p class="font-extrabold text-lg">هنوز قسمتی منتشر نشده است.</p>
          <p class="mt-2 text-sm text-muted-foreground">به‌زودی قسمت‌های جدید رادیو نیل اینجا قرار می‌گیرد.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- سایدبار -->
    <aside class="lg:col-span-4 space-y-5 min-w-0">
      <div>
        <div class="rounded-3xl border border-border bg-card p-6">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-7 text-violet-600" aria-hidden="true"><path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/></svg>
          <h3 class="mt-3 font-extrabold text-[15px]">موضوع بعدی را شما پیشنهاد دهید</h3>
          <p class="mt-2 fa-line text-[13px] text-muted-foreground leading-6">هر موضوعی که دوست دارید در قسمت‌های بعدی درباره‌اش صحبت شود، برای نیلوفر بنویسید.</p>
          <a href="<?php echo esc_url($contact_url); ?>" class="btn btn-violet w-full mt-4 rounded-full font-bold shadow-[0_8px_20px_-8px_rgba(147,145,255,0.6)]">پیشنهاد موضوع</a>
        </div>
      </div>

      <div>
        <div class="rounded-3xl bg-violet-100/70 border border-violet-300 p-6">
          <h3 class="font-extrabold text-[15px] text-violet-800">مسیر یادگیری را شروع کن</h3>
          <p class="mt-2 fa-line text-[13px] text-foreground/70 leading-6">گوش دادن به رادیو نیل را با دیدن کپسول رایگان اولترا کامل کن؛ مسیر کاملاً شفاف می‌شود.</p>
          <a href="<?php echo esc_url($capsule_url); ?>" class="mt-4 inline-flex items-center gap-2 rounded-full bg-ink-900 text-cream px-5 py-3 text-[13px] font-bold hover:bg-ink-800 transition-colors">
            کپسول اولترا (رایگان)
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
        </div>
      </div>
    </aside>

  </div>
</section>
