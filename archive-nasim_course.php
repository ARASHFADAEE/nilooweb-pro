<?php

if(!defined('ABSPATH')) exit;

get_header();
?>

  <main class="container-wide course-archive">
    <div class="lms-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span>／</span><span>دوره‌ها</span></div>

    <div class="section-heading course-archive-heading">
      <div><span class="eyebrow">دوره‌های آموزشی</span><h2>قدم‌به‌قدم، <span>با برنامه‌ای روشن</span></h2></div>
      <p>بعضی دوره‌ها رایگان یا داخل اشتراک‌اند؛ دوره‌های پرچمدار را جداگانه تهیه می‌کنی.</p>
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
      <div class="cta-copy"><span class="eyebrow">اشتراک ماهانه</span><h2>دوره‌های اشتراکی، با یک پلن.</h2><p>دوره‌های علامت‌خورده «رایگان با اشتراک» با خرید اشتراک باز می‌شوند؛ بقیه خرید جدا دارند.</p></div>
      <a class="button button-light button-large" href="<?php echo esc_url(nasim_subscription_url()); ?>">مشاهده پلن‌ها <span>←</span></a>
    </section>
    <?php endif; ?>
  </main>

<?php
get_footer();
