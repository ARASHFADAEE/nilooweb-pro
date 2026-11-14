<?php

/**
 * Template Name: کاتالوگ دوره‌ها
 * Catalog of nasim_course posts (used by /courses/ when CPT archive is off).
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$query = new WP_Query([
    'post_type'      => 'nasim_course',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
]);
?>

  <main class="container-wide course-archive">
    <div class="lms-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span>／</span><span>دوره‌ها</span></div>

    <div class="section-heading course-archive-heading">
      <div>
        <span class="eyebrow"><span class="eyebrow-line"></span> دوره‌های آموزشی</span>
        <h1>مسیر یادگیری خود را <span class="accent">از اینجا شروع کنید</span></h1>
      </div>
      <p>بعضی دوره‌ها رایگان یا داخل اشتراک‌اند؛ دوره‌های پرچمدار مثل اولترا وب را جداگانه تهیه می‌کنی.</p>
    </div>

    <?php if ($query->have_posts()) : ?>
    <div class="course-grid">
      <?php
        while ($query->have_posts()) :
            $query->the_post();
            get_template_part('template-parts/course/card');
        endwhile;
        wp_reset_postdata();
        ?>
    </div>

    <?php
        echo paginate_links([ // phpcs:ignore WordPress.Security.EscapeOutput
            'total'     => (int) $query->max_num_pages,
            'current'   => $paged,
            'prev_text' => '→',
            'next_text' => '←',
            'type'      => 'list',
        ]);
        ?>
    <?php else : ?>
    <p class="course-empty">هنوز دوره‌ای منتشر نشده است.</p>
    <?php endif; ?>

    <?php if (!nasim_has_active_subscription()) : ?>
    <section class="cta-section">
      <div class="cta-decoration"></div>
      <div class="cta-copy">
        <span class="eyebrow">اشتراک آموزشی</span>
        <h2>دوره‌های «رایگان با اشتراک»، با یک پلن.</h2>
        <p>اشتراک فقط دوره‌های علامت‌خورده را باز می‌کند؛ اولترا وب و دوره‌های پرچمدار خرید جداگانه دارند.</p>
      </div>
      <a class="button button-light button-large" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>">مشاهده پلن‌ها <span>←</span></a>
    </section>
    <?php endif; ?>
  </main>

<?php
get_footer();
