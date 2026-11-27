<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
  <main id="main-content" class="flex-1">
    <section class="relative overflow-hidden border-b border-border">
      <div class="absolute inset-0 dot-grid opacity-50" aria-hidden="true"></div>
      <div class="container-site relative pt-12 pb-10 md:pt-16 md:pb-14">
        <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">پکیج‌های آموزشی</span>
        <h1 class="mt-5 text-2xl md:text-4xl font-black">مسیر کامل یادگیری، <span class="mark-violet">با قیمت کمتر</span></h1>
        <p class="mt-4 fa-line text-[15px] text-muted-foreground max-w-2xl">
          هر پکیج چند دوره مکمل را کنار هم می‌گذارد. یک‌بار پرداخت می‌کنی و همه دوره‌ها برای همیشه در پنلت فعال می‌شوند.
        </p>
      </div>
    </section>

    <section class="container-site py-12 md:py-16">
      <?php if (have_posts()) : ?>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/bundle/card', null, ['bundle' => get_post()]); ?>
        <?php endwhile; ?>
      </div>
      <?php the_posts_pagination(['mid_size' => 1]); ?>
      <?php else : ?>
      <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
        <p class="font-extrabold text-lg">هنوز پکیجی منتشر نشده است.</p>
        <a class="btn btn-violet rounded-full mt-5" href="<?php echo esc_url(nasim_courses_listing_url()); ?>">مشاهده همه دوره‌ها</a>
      </div>
      <?php endif; ?>
    </section>
  </main>
<?php
get_footer();
