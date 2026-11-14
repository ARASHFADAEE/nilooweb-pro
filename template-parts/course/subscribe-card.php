<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Access CTA for users without course access — subscription vs single purchase.
 */
$course_id = isset($args['course_id']) ? (int) $args['course_id'] : (int) get_the_ID();
$mode      = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'subscription';
$cta_url   = function_exists('nasim_course_checkout_url') ? nasim_course_checkout_url($course_id) : nasim_subscription_url();
$cta_label = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($course_id) : 'مشاهده پلن‌ها و خرید';
$plan_id   = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
$plan      = $plan_id ? nasim_get_plan($plan_id, false) : null;

if ('purchase' === $mode) {
    $title    = 'خرید جداگانه این دوره';
    $text     = 'این دوره داخل اشتراک نیست و باید جداگانه تهیه شود.';
    $features = $plan && $plan['features'] ? $plan['features'] : [
        'دسترسی دائمی به همین دوره',
        'پشتیبانی آکادمی',
        $plan && !empty($plan['type']) && 'spot_course' === $plan['type'] ? 'لایسنس اسپات‌پلیر پس از پرداخت' : 'مشاهده در پلیر دوره',
    ];
    $price = $plan ? (int) $plan['price'] : 0;
} else {
    $sub_plans = nasim_get_plans('subscription');
    $featured  = current(array_filter($sub_plans, function ($p) {
        return !empty($p['featured']);
    })) ?: ($sub_plans[0] ?? null);
    $title    = 'رایگان با اشتراک';
    $text     = 'با خرید اشتراک، به این دوره و بقیه دوره‌های اشتراکی دسترسی داری.';
    $features = $featured && $featured['features'] ? $featured['features'] : [
        'دسترسی به دوره‌های «رایگان با اشتراک»',
        'دوره‌های پرچمدار همچنان خرید جداگانه دارند',
        'تمدید دستی؛ بدون کسر خودکار',
    ];
    $monthly_prices = array_map('nasim_plan_monthly_price', $sub_plans);
    $price          = $monthly_prices ? min($monthly_prices) : 0;
}
?>
<div class="subscribe-card">
  <span class="subscribe-card-icon">✦</span>
  <h3><?php echo esc_html($title); ?></h3>
  <p><?php echo esc_html($text); ?></p>

  <?php if ($price > 0) : ?>
  <div class="subscribe-card-price">
    <?php if ('purchase' === $mode) : ?>
    <strong><?php echo esc_html(nasim_format_price($price)); ?></strong>
    <?php else : ?>
    <small>شروع از</small>
    <strong><?php echo esc_html(nasim_format_price($price)); ?></strong>
    <small>در ماه</small>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <ul>
    <?php foreach ($features as $feature) : ?>
    <li><span>✓</span><?php echo esc_html($feature); ?></li>
    <?php endforeach; ?>
  </ul>

  <a class="button button-primary" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_label); ?> <span>←</span></a>

  <?php if (!is_user_logged_in()) : ?>
  <small class="subscribe-card-login">قبلاً خریده‌ای؟ <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">وارد شو</a></small>
  <?php endif; ?>
</div>
