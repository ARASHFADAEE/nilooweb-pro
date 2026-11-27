<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $pid         = get_the_ID();
    $cats        = get_the_category($pid);
    $cat         = !empty($cats) ? $cats[0]->name : 'آموزش';
    $mins        = function_exists('nasim_reading_time') ? nasim_reading_time($pid) : 3;
    $blog_url    = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');
    $courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
    $contact_url = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');
    $radio_slug  = nasim_mod('radio_cat') ?: 'radionil';
    $is_radio    = has_category($radio_slug, $pid);
    $related     = function_exists('nasim_related_posts') ? nasim_related_posts($pid, 3) : [];
?>

  <div class="reading-progress" id="readingProgress" aria-hidden="true"><span></span></div>

  <main id="main-content" class="flex-1">
    <div class="container-site py-10 md:py-14">

      <!-- بردکرامب -->
      <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-1.5 text-[12px] font-semibold text-muted-foreground">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-foreground">خانه</a>
        <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        <a href="<?php echo esc_url($blog_url); ?>" class="hover:text-foreground"><?php echo $is_radio ? 'رادیو نیل' : 'مقالات'; ?></a>
        <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        <span class="text-foreground/80 truncate max-w-[260px]"><?php the_title(); ?></span>
      </nav>

      <article <?php post_class('mt-8 grid lg:grid-cols-12 gap-10'); ?>>
        <!-- بدنه مقاله -->
        <div class="lg:col-span-8 min-w-0">
          <header>
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full bg-violet-100 border border-violet-200 px-2.5 py-1 text-xs font-bold text-violet-800"><?php echo esc_html($cat); ?></span>
              <span class="text-xs font-bold text-muted-foreground"><?php echo esc_html(get_the_date()); ?></span>
              <span class="text-xs font-bold text-muted-foreground">· زمان مطالعه: <?php echo esc_html(nasim_fa_digits($mins)); ?> دقیقه</span>
            </div>
            <h1 class="mt-4 text-xl md:text-3xl font-black leading-[1.6]"><?php the_title(); ?></h1>
          </header>

          <?php if (has_post_thumbnail()) : ?>
          <figure class="mt-6 rounded-3xl overflow-hidden border border-border shadow-soft">
            <?php the_post_thumbnail('large', ['class' => 'w-full h-auto object-cover', 'alt' => the_title_attribute(['echo' => false])]); ?>
          </figure>
          <?php endif; ?>

          <div class="article-body article-prose mt-8">
            <?php the_content(); ?>
            <?php wp_link_pages(['before' => '<nav class="mt-6 flex gap-2">', 'after' => '</nav>']); ?>
          </div>

          <!-- CTA گام بعدی -->
          <div class="reveal mt-10 rounded-3xl border border-violet-300 bg-violet-100/50 p-6 md:p-7 flex flex-col md:flex-row items-center gap-5 justify-between">
            <div>
              <p class="text-[13px] font-black text-violet-900">گام بعدی مسیر یادگیری</p>
              <h3 class="mt-1.5 text-[16px] font-extrabold text-ink-950">مسیر پروژه‌محور طراحی سایت در آکادمی نیلوفر وفایی</h3>
            </div>
            <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-violet rounded-full font-bold shrink-0">
              مشاهده دوره‌ها
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            </a>
          </div>

          <!-- دیدگاه‌های واقعی وردپرس -->
          <?php if (comments_open() || get_comments_number()) : ?>
          <div class="mt-12">
            <?php comments_template(); ?>
          </div>
          <?php endif; ?>

          <!-- مقالات مرتبط -->
          <?php if ($related) : ?>
          <div class="reveal mt-12">
            <h2 class="text-lg font-extrabold mb-4">مطالب مرتبط</h2>
            <ul class="space-y-2.5">
              <?php foreach ($related as $rel) : ?>
              <li>
                <a href="<?php echo esc_url(get_permalink($rel->ID)); ?>" class="block rounded-2xl border border-border bg-card px-4 py-3.5 hover:border-violet-400 transition-colors">
                  <span class="block text-[14px] font-bold"><?php echo esc_html($rel->post_title); ?></span>
                  <span class="block mt-1 text-xs font-semibold text-muted-foreground"><?php echo esc_html(get_the_date('', $rel->ID)); ?></span>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
            <div class="mt-6">
              <a href="<?php echo esc_url($blog_url); ?>" class="btn btn-outline rounded-full font-bold">بازگشت به همه مقالات</a>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- سایدبار مقاله -->
        <aside class="lg:col-span-4 space-y-5 min-w-0">
          <?php if ($related) : ?>
          <div class="rounded-3xl border border-border bg-card p-6 shadow-soft">
            <h3 class="font-extrabold text-[14.5px] mb-4">مطالب پیشنهادی</h3>
            <ul class="space-y-4">
              <?php foreach ($related as $rel) : ?>
              <li>
                <a href="<?php echo esc_url(get_permalink($rel->ID)); ?>" class="group block">
                  <span class="block text-[13.5px] font-bold leading-6 group-hover:text-violet-700 transition-colors"><?php echo esc_html($rel->post_title); ?></span>
                  <span class="mt-1 block text-xs font-semibold text-muted-foreground"><?php echo esc_html(get_the_date('', $rel->ID)); ?></span>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <div class="rounded-3xl bg-ink-900 text-cream p-6">
            <p class="text-[14px] font-extrabold">مشاوره رایگان قبل از ثبت‌نام</p>
            <p class="mt-2 fa-line text-xs text-ink-200 leading-6">
              اگر سوالی درباره این آموزش یا انتخاب دوره مناسب خودت داری، همین الان بپرس.
            </p>
            <a href="<?php echo esc_url($contact_url); ?>" class="btn btn-violet btn-sm w-full mt-4 rounded-full font-bold">ارتباط و مشاوره</a>
          </div>
        </aside>
      </article>

    </div>
  </main>

<?php
endwhile;

get_footer();
