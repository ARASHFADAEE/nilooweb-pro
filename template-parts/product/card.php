<?php

if(!defined('ABSPATH')) exit;

$product_id = get_the_ID();
$meta       = nasim_get_product_meta($product_id);
$pricing    = nasim_product_pricing($meta);
$sales      = nasim_product_sales_count($meta);
$title      = get_the_title();
?>
<article class="pcard">
  <div class="pcard-media">
    <a class="pcard-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
      <?php if (has_post_thumbnail()) : ?>
        <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => '']); ?>
      <?php else : ?>
        <span class="pcard-placeholder"><span class="brand-mark"><?php echo esc_html(mb_substr(get_bloginfo('name'), 0, 1)); ?></span></span>
      <?php endif; ?>
    </a>
    <span class="pcard-type"><?php echo esc_html(nasim_product_type_label($meta['type'])); ?></span>
    <?php if ($meta['demo_url']) : ?>
    <a class="pcard-demo" href="<?php echo esc_url($meta['demo_url']); ?>" target="_blank" rel="noopener">پیش‌نمایش <span aria-hidden="true">▶</span></a>
    <?php endif; ?>
  </div>

  <div class="pcard-body">
    <h3 class="pcard-title"><a href="<?php the_permalink(); ?>" title="<?php echo esc_attr($title); ?>"><?php echo esc_html($title); ?></a></h3>
    <p class="pcard-sub">
      <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="1.5" y="1.5" width="5" height="5" rx="1.2"/><rect x="9.5" y="1.5" width="5" height="5" rx="1.2"/><rect x="1.5" y="9.5" width="5" height="5" rx="1.2"/><rect x="9.5" y="9.5" width="5" height="5" rx="1.2"/></svg>
      <span><?php echo esc_html(nasim_product_card_subtitle($product_id)); ?></span>
    </p>

    <div class="pcard-foot">
      <dl class="pcard-stats">
        <div class="pcard-stat is-boxed">
          <dt>فروش</dt>
          <dd><?php echo $sales ? esc_html(nasim_product_number($sales)) : 'جدید'; ?></dd>
        </div>
        <div class="pcard-stat">
          <dt>رضایت</dt>
          <dd><?php echo '' !== $meta['satisfaction'] ? esc_html(nasim_fa_digits($meta['satisfaction'])) . '<small>٪</small>' : '-'; ?></dd>
        </div>
        <div class="pcard-stat">
          <dt>پشتیبانی</dt>
          <dd dir="ltr"><?php echo $meta['support'] ? esc_html($meta['support']) : '-'; ?></dd>
        </div>
      </dl>

      <div class="pcard-price">
        <?php if ($meta['promo_label']) : ?>
        <span class="pcard-promo"><?php echo esc_html($meta['promo_label']); ?></span>
        <?php endif; ?>
        <?php if ($pricing['discount']) : ?>
        <span class="pcard-price-old">
          <del><?php echo esc_html(nasim_product_number($pricing['regular'])); ?></del>
          <b class="pcard-off"><?php echo esc_html(nasim_fa_digits($pricing['discount'])); ?>٪</b>
        </span>
        <?php endif; ?>
        <?php if ($pricing['final']) : ?>
        <span class="pcard-price-now"><strong><?php echo esc_html(nasim_product_number($pricing['final'])); ?></strong> <small>تومان</small></span>
        <?php else : ?>
        <span class="pcard-price-now is-sub"><strong>با اشتراک</strong></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</article>
