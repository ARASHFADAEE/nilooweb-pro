<?php

if(!defined('ABSPATH')) exit;

/**
 * Subscription call-to-action for users without access
 */
$plans    = nasim_get_plans();
$featured = current(array_filter($plans, function ($plan) {
    return $plan['featured'];
})) ?: ($plans[0] ?? null);

$monthly_prices = array_map('nasim_plan_monthly_price', $plans);
$features       = $featured && $featured['features'] ? $featured['features'] : [
    'دسترسی کامل به همه دوره‌ها و جلسات',
    'دانلود افزونه‌ها و قالب‌ها با لایسنس',
    'آپدیت رایگان محصولات در طول اشتراک',
];
?>
<div class="subscribe-card">
  <span class="subscribe-card-icon">✦</span>
  <h3>با اشتراک ماهانه</h3>
  <p>یک اشتراک، دسترسی به دوره‌ها و محصولات.</p>

  <?php if ($monthly_prices) : ?>
  <div class="subscribe-card-price">
    <small>شروع از</small>
    <strong><?php echo esc_html(nasim_format_price(min($monthly_prices))); ?></strong>
    <small>در ماه</small>
  </div>
  <?php endif; ?>

  <ul>
    <?php foreach ($features as $feature) : ?>
    <li><span>✓</span><?php echo esc_html($feature); ?></li>
    <?php endforeach; ?>
  </ul>

  <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url()); ?>">مشاهده پلن‌ها و خرید <span>←</span></a>

  <?php if (!is_user_logged_in()) : ?>
  <small class="subscribe-card-login">قبلاً اشتراک خریده‌ای؟ <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">وارد شو</a></small>
  <?php endif; ?>
</div>
