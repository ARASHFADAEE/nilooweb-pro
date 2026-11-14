<?php

if (!defined('ABSPATH')) {
    exit;
}

$course_id = isset($args['course_id']) ? (int) $args['course_id'] : (int) get_the_ID();
if (!$course_id || 'nasim_course' !== get_post_type($course_id)) {
    return;
}

if (function_exists('nasim_user_has_course_access') && nasim_user_has_course_access($course_id)) {
    return;
}

$mode  = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'subscription';
$plan  = function_exists('nasim_express_plan_for_course') ? nasim_express_plan_for_course($course_id) : null;
$label = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($course_id) : 'خرید';
$title = get_the_title($course_id);

if ('free' === $mode) {
    $lessons = function_exists('nasim_get_course_lessons') ? nasim_get_course_lessons($course_id) : [];
    $free    = array_values(array_filter($lessons, static function ($l) {
        return !empty($l['is_free']);
    }));
    $start = $free ? nasim_lesson_url($course_id, $free[0]['id']) : get_permalink($course_id);
    ?>
<div class="express-buybar" data-express-bar>
  <div class="express-buybar-inner container-wide">
    <div class="express-buybar-copy">
      <strong><?php echo esc_html($title); ?></strong>
      <span class="express-buybar-price is-free">رایگان</span>
    </div>
    <a class="button button-primary" href="<?php echo esc_url($start); ?>">شروع رایگان <span>←</span></a>
  </div>
</div>
    <?php
    return;
}

if (!$plan) {
    return;
}

$price_label = function_exists('nasim_format_price') ? nasim_format_price((int) $plan['price']) : number_format((int) $plan['price']) . ' تومان';
$is_purchase = in_array($plan['type'] ?? '', ['course', 'spot_course'], true);
$user        = wp_get_current_user();
$prefill_name = is_user_logged_in() ? ($user->display_name ?: '') : '';
$prefill_mobile = is_user_logged_in() ? nasim_get_user_mobile($user->ID) : '';
?>
<div class="express-buybar" data-express-bar>
  <div class="express-buybar-inner container-wide">
    <div class="express-buybar-copy">
      <strong><?php echo esc_html($title); ?></strong>
      <span class="express-buybar-price"><?php echo esc_html($price_label); ?><?php echo $is_purchase ? '' : ' <small>/ ماه</small>'; ?></span>
    </div>
    <button type="button" class="button button-primary" data-express-open>
      <?php echo esc_html($label); ?> <span>←</span>
    </button>
  </div>
</div>

<div class="express-modal" id="expressCheckoutModal" hidden data-express-modal>
  <div class="express-modal-backdrop" data-express-close tabindex="-1"></div>
  <div class="express-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="expressModalTitle">
    <button type="button" class="express-modal-x" data-express-close aria-label="بستن">×</button>

    <div class="express-modal-head">
      <span class="eyebrow"><span class="eyebrow-line"></span> خرید سریع</span>
      <h2 id="expressModalTitle"><?php echo esc_html($is_purchase ? 'خرید این دوره' : 'خرید اشتراک'); ?></h2>
      <p><?php echo esc_html($title); ?> — <strong><?php echo esc_html($price_label); ?></strong></p>
    </div>

    <form class="express-form" data-express-form novalidate
      data-plan-id="<?php echo esc_attr((string) $plan['id']); ?>"
      data-course-id="<?php echo esc_attr((string) $course_id); ?>"
      data-return-url="<?php echo esc_url(get_permalink($course_id)); ?>"
      data-logged-in="<?php echo is_user_logged_in() ? '1' : '0'; ?>">

      <div class="express-step is-active" data-step="identity">
        <label>
          <span>نام و نام خانوادگی</span>
          <input type="text" name="name" autocomplete="name" required value="<?php echo esc_attr($prefill_name); ?>" placeholder="مثلاً نیلوفر وفایی">
        </label>
        <label>
          <span>شماره موبایل</span>
          <input type="tel" name="mobile" inputmode="numeric" autocomplete="tel" required dir="ltr" value="<?php echo esc_attr($prefill_mobile); ?>" placeholder="09xxxxxxxxx" <?php echo $prefill_mobile ? 'readonly' : ''; ?>>
        </label>
        <p class="express-hint">کد تأیید پیامک می‌شود؛ بعد از تأیید به درگاه پرداخت می‌روی.</p>
        <button type="submit" class="button button-primary button-large" data-express-send>
          <?php echo is_user_logged_in() ? 'ادامه و پرداخت' : 'دریافت کد تأیید'; ?> <span>←</span>
        </button>
      </div>

      <div class="express-step" data-step="otp" hidden>
        <p class="express-otp-msg" data-express-otp-msg></p>
        <label>
          <span>کد تأیید</span>
          <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="8" placeholder="-----">
        </label>
        <div class="express-otp-actions">
          <button type="button" class="button button-outline" data-express-back>بازگشت</button>
          <button type="submit" class="button button-primary" data-express-verify>تأیید و پرداخت <span>←</span></button>
        </div>
        <button type="button" class="text-link express-resend" data-express-resend disabled>ارسال دوباره کد</button>
      </div>

      <p class="express-error" data-express-error hidden></p>
      <p class="express-status" data-express-status hidden></p>
    </form>
  </div>
</div>
