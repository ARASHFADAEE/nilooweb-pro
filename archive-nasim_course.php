<?php

if(!defined('ABSPATH')) exit;

get_header();
?>

  <main class="container-wide course-archive">
    <div class="lms-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span>／</span><span>دوره‌ها</span></div>

    <div class="section-heading course-archive-heading">
      <div><span class="eyebrow">دوره‌های آموزشی</span><h2>قدم‌به‌قدم، <span>با برنامه‌ای روشن</span></h2></div>
      <p>با یک اشتراک به همه دوره‌ها دسترسی داری؛ از هر کدام که با هدفت هماهنگ است شروع کن.</p>
    </div>

    <?php if (have_posts()) : ?>
    <div class="course-grid">
      <?php while (have_posts()) : the_post(); ?>
        <?php get_template_part('template-parts/course/card'); ?>
      <?php endwhile; ?>
    </div>

    <?php the_posts_pagination(['prev_text' => '→', 'next_text' => '←']); ?>
    <?php else : ?>
    <p class="course-empty">هنوز دوره‌ای منتشر نشده است.</p>
    <?php endif; ?>

    <?php if (!nasim_has_active_subscription()) : ?>
    <section class="cta-section">
      <div class="cta-decoration"></div>
      <div class="cta-copy"><span class="eyebrow">اشتراک ماهانه</span><h2>یک اشتراک، همه دوره‌ها.</h2><p>هر ماه با دوره‌ها، افزونه‌ها و قالب‌های جدید همراه باش.</p></div>
      <a class="button button-light button-large" href="<?php echo esc_url(nasim_subscription_url()); ?>">مشاهده پلن‌ها <span>←</span></a>
    </section>
    <?php endif; ?>
  </main>

<?php
get_footer();
