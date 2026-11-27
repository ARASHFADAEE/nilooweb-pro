<?php

if (!defined('ABSPATH')) {
    exit;
}

$radio_slug  = nasim_mod('radio_cat') ?: 'radionil';
$is_radio    = is_category($radio_slug);
$heading     = function_exists('nasim_archive_heading') ? nasim_archive_heading() : ['مجله آموزشی', 'مقالات و آموزش‌های', 'رایگان', ''];
$radio_url   = nasim_mod('radio_more_url') ?: home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/');
$contact_url = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');
$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');

get_header();
?>

  <main id="main-content" class="flex-1">

    <?php if ($is_radio) : ?>
    <!-- هیرو رادیو نیل -->
    <section class="relative bg-ink-950 text-cream overflow-hidden" aria-label="پادکست رادیو نیل">
      <div class="absolute inset-0 dot-grid-light opacity-40" aria-hidden="true"></div>
      <div class="absolute -top-24 -end-24 size-80 rounded-full bg-violet-400/10 blur-3xl" aria-hidden="true"></div>
      <div class="container-site relative pt-12 pb-14 md:pt-16 md:pb-18">
        <span class="inline-flex items-center gap-2 rounded-full border border-ink-700 bg-ink-900 px-3.5 py-1.5 text-[13px] font-bold text-violet-300">
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></svg>
          پادکست رادیو نیل
        </span>
        <h1 class="mt-5 text-2xl md:text-4xl font-black leading-[1.5] max-w-3xl">
          رادیو نیل؛ <span class="text-violet-300">تکنولوژی و وب، با صدای نیلوفر</span>
        </h1>
        <p class="mt-4 fa-line text-[15px] text-ink-200 max-w-2xl">
          پادکست تخصصی در زمینه تکنولوژی، طراحی سایت و مسیر پروژه‌گیری؛ موضوعاتی که در کلاس کمتر گفته می‌شود اما در مسیر شغلی خیلی مهم است.
        </p>
      </div>
    </section>
    <?php else : ?>
    <!-- هیرو مجله / آرشیو -->
    <section class="relative overflow-hidden border-b border-border" aria-label="مجله آموزشی">
      <div class="absolute inset-0 dot-grid opacity-50" aria-hidden="true"></div>
      <div class="container-site relative pt-12 pb-10 md:pt-16 md:pb-14">
        <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18h-5"/><path d="M18 14h-8"/><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="10" y="6" rx="1"/></svg>
          <?php echo esc_html($heading[0] ?: 'مجله آموزشی'); ?>
        </span>
        <h1 class="mt-5 text-2xl md:text-4xl font-black">
          <?php echo esc_html(trim($heading[1] . ' ' . $heading[2]) ?: 'مقالات و آموزش‌های رایگان'); ?>
        </h1>
        <?php if (!empty($heading[3])) : ?>
        <p class="mt-4 fa-line text-[15px] text-muted-foreground max-w-2xl"><?php echo esc_html($heading[3]); ?></p>
        <?php else : ?>
        <p class="mt-4 fa-line text-[15px] text-muted-foreground max-w-2xl">
          یادداشت‌ها و آموزش‌های کاربردی حوزه وب؛ همراه با رادیو نیل، نقطه ارتباط رایگان با آکادمی.
        </p>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- لیست مطالب + سایدبار -->
    <section class="container-site py-12 md:py-16">
      <div class="grid lg:grid-cols-12 gap-8">

        <!-- ستون اصلی -->
        <div class="lg:col-span-8 space-y-5 min-w-0">
          <?php if (have_posts()) : ?>
            <?php $i = 0; while (have_posts()) : the_post();
                $pid    = get_the_ID();
                $cats   = get_the_category($pid);
                $cat    = !empty($cats) ? $cats[0]->name : 'آموزش';
                $thumb  = get_the_post_thumbnail_url($pid, 'medium_large');
                $mins   = function_exists('nasim_reading_time') ? nasim_reading_time($pid) : 3;
                $c_num  = (int) get_comments_number($pid);
                $delay  = ($i % 4) * 60;
                $i++;
            ?>

            <?php if ($is_radio) : ?>
            <article class="reveal rounded-3xl border border-border bg-card p-5 md:p-6 shadow-soft" style="--reveal-delay:<?php echo esc_attr($delay); ?>ms">
              <div class="flex items-start gap-4">
                <a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr('پخش و جزئیات ' . get_the_title()); ?>" class="grid place-items-center size-14 rounded-2xl shrink-0 bg-ink-900 text-violet-300 hover:bg-ink-800 transition-colors">
                  <svg class="size-6 me-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                </a>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-ink-900 text-violet-300 text-xs font-black px-2.5 py-1">رادیو نیل</span>
                    <span class="text-xs font-bold text-muted-foreground"><?php echo esc_html(get_the_date()); ?></span>
                  </div>
                  <h2 class="mt-2 text-[16px] font-extrabold leading-7">
                    <a href="<?php the_permalink(); ?>" class="hover:text-violet-700 transition-colors"><?php the_title(); ?></a>
                  </h2>
                  <p class="mt-1.5 fa-line text-[13.5px] leading-6 text-muted-foreground line-clamp-2"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p>
                </div>
              </div>
              <div class="mt-4 flex items-center justify-between gap-3 border-t border-dashed border-border pt-3.5">
                <span class="text-xs font-bold text-muted-foreground"><?php echo esc_html(nasim_fa_digits($c_num)); ?> دیدگاه</span>
                <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-[13px] font-bold text-ink-600 hover:text-ink-900 hover:bg-sand transition-colors">
                  صفحه این قسمت
                  <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
              </div>
            </article>
            <?php else : ?>
            <a href="<?php the_permalink(); ?>" class="reveal group grid md:grid-cols-12 gap-5 rounded-3xl border border-border bg-card p-5 md:p-6 hover:shadow-lift hover:-translate-y-0.5 transition-all" style="--reveal-delay:<?php echo esc_attr($delay); ?>ms">
              <div class="md:col-span-8 md:order-2 min-w-0 flex flex-col">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-violet-100 border border-violet-200 px-2.5 py-1 text-xs font-bold text-violet-800"><?php echo esc_html($cat); ?></span>
                  <span class="text-xs font-bold text-muted-foreground">زمان مطالعه: <?php echo esc_html(nasim_fa_digits($mins)); ?> دقیقه</span>
                </div>
                <h2 class="mt-3 text-[16.5px] md:text-lg font-extrabold leading-7 group-hover:text-ink-600 transition-colors"><?php the_title(); ?></h2>
                <p class="mt-2.5 fa-line text-[13.5px] leading-7 text-muted-foreground line-clamp-2 flex-1"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p>
                <div class="mt-4 flex items-center gap-4 text-[12px] font-bold text-muted-foreground">
                  <span class="inline-flex items-center gap-1.5">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                    <?php echo esc_html(nasim_fa_digits($c_num)); ?> دیدگاه
                  </span>
                  <span class="inline-flex items-center gap-1.5 text-ink-600 group-hover:gap-2.5 transition-all">
                    ادامه مطلب
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                  </span>
                </div>
              </div>
              <div class="md:col-span-4 md:order-1 min-w-0">
                <div class="relative h-full min-h-[140px] rounded-2xl bg-ink-900 overflow-hidden grid place-items-center">
                  <?php if ($thumb) : ?>
                  <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]" />
                  <?php else : ?>
                  <div class="absolute inset-0 dot-grid-light opacity-40" aria-hidden="true"></div>
                  <span class="relative text-cream/90 font-black text-lg text-center px-4"><?php echo esc_html($cat); ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </a>
            <?php endif; ?>

            <?php endwhile; ?>

            <?php the_posts_pagination(['mid_size' => 1, 'prev_text' => '→ قبلی', 'next_text' => 'بعدی ←']); ?>
          <?php else : ?>
            <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
              <p class="font-extrabold text-lg">مطلبی پیدا نشد.</p>
              <p class="mt-2 text-sm text-muted-foreground">عبارت دیگری جستجو کن یا دوره‌های آموزشی را ببین.</p>
              <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-violet rounded-full mt-5">مشاهده دوره‌ها</a>
            </div>
          <?php endif; ?>
        </div>

        <!-- سایدبار -->
        <aside class="lg:col-span-4 space-y-5 min-w-0">
          <!-- جستجو -->
          <div class="rounded-3xl border border-border bg-card p-5 shadow-soft">
            <h3 class="font-extrabold text-[15px] mb-3">جستجو در مطالب</h3>
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="flex gap-2">
              <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="کلیدواژه را بنویس…" aria-label="جستجو" class="field flex-1" />
              <button type="submit" class="btn btn-violet rounded-xl shrink-0">جستجو</button>
            </form>
          </div>

          <?php if (!$is_radio) : ?>
          <!-- کارت رادیو نیل -->
          <div class="rounded-3xl bg-ink-900 text-cream p-6">
            <h3 class="font-extrabold text-[15.5px] flex items-center gap-2">
              <svg class="size-5 text-violet-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></svg>
              رادیو نیل
            </h3>
            <p class="mt-2.5 fa-line text-xs text-ink-200 leading-6">
              پادکست‌های رایگان درباره مسیر طراحی سایت، انتخاب رشته و تکنیک‌های پروژه‌گیری.
            </p>
            <a href="<?php echo esc_url($radio_url); ?>" class="btn btn-violet btn-sm w-full mt-4 rounded-full font-bold">شنیدن قسمت‌های رادیو نیل</a>
          </div>
          <?php endif; ?>

          <!-- کارت مشاوره -->
          <div class="rounded-3xl border border-violet-300 bg-violet-100/50 p-6">
            <h3 class="font-extrabold text-[15px] text-ink-950">سوالی درباره مسیر یادگیری داری؟</h3>
            <p class="mt-2 fa-line text-xs text-foreground/80 leading-6">
              اگر بین انتخاب دوره مردد هستی یا سوال فنی داری، مستقیم پیام بفرست.
            </p>
            <a href="<?php echo esc_url($contact_url); ?>" class="btn btn-default btn-sm w-full mt-4 rounded-full font-bold">ارتباط و مشاوره</a>
          </div>
        </aside>

      </div>
    </section>

  </main>

<?php
get_footer();
