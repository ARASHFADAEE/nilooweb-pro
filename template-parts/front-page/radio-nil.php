<?php

if (!defined('ABSPATH')) {
    exit;
}

$cat = sanitize_title(nasim_mod('radio_cat') ?: 'podcast');
$posts = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'category_name'  => $cat,
    'no_found_rows'  => true,
]);

if (!$posts) {
    $posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'no_found_rows'  => true,
    ]);
}

if (!$posts) {
    return;
}

$more = nasim_mod('radio_more_url') ?: home_url('/blog/');
?>
    <section class="section container-wide home-radio" id="radio">
      <div class="section-heading">
        <div>
          <span class="eyebrow"><span class="eyebrow-line"></span> <?php echo esc_html(nasim_mod('radio_eyebrow') ?: 'رادیو نیل'); ?></span>
          <h2><?php echo esc_html(nasim_mod('radio_title') ?: 'پادکست'); ?> <span class="accent"><?php echo esc_html(nasim_mod('radio_title_highlight') ?: ''); ?></span></h2>
        </div>
        <a class="text-link" href="<?php echo esc_url($more); ?>">همه اپیزودها <span>←</span></a>
      </div>
      <div class="home-radio-grid">
        <?php foreach ($posts as $post) : ?>
        <a class="home-radio-card" href="<?php echo esc_url(get_permalink($post)); ?>">
          <?php if (has_post_thumbnail($post)) : ?>
          <?php echo get_the_post_thumbnail($post, 'medium_large', ['loading' => 'lazy', 'decoding' => 'async']); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          <?php endif; ?>
          <div>
            <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            <strong><?php echo esc_html(get_the_title($post)); ?></strong>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>