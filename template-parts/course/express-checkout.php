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
$label = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($course_id) : 'ثبت‌نام در دوره';
$title = get_the_title($course_id);

if ('free' === $mode) {
    $start = function_exists('nasim_course_start_url') ? nasim_course_start_url($course_id) : get_permalink($course_id);
    $cta   = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($course_id) : 'ورود و شروع رایگان';
    ?>
<div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-ink-900/95 backdrop-blur border-t border-ink-700 safe-bottom" data-express-bar>
  <div class="container-site flex items-center justify-between gap-3 py-3">
    <div class="flex flex-col min-w-0">
      <span class="text-xs text-ink-300 font-medium truncate"><?php echo esc_html($title); ?></span>
      <span class="text-mint-300 font-extrabold text-[15px]">رایگان</span>
    </div>
    <a class="btn btn-violet rounded-full px-6 font-black shrink-0" href="<?php echo esc_url($start); ?>"><?php echo esc_html($cta); ?></a>
  </div>
</div>
    <?php
    return;
}

if (!$plan) {
    return;
}

$price_label    = function_exists('nasim_format_price') ? nasim_format_price((int) $plan['price']) : number_format((int) $plan['price']) . ' تومان';
$user           = wp_get_current_user();
$prefill_name   = is_user_logged_in() ? ($user->display_name ?: '') : '';
$prefill_mobile = is_user_logged_in() ? nasim_get_user_mobile($user->ID) : '';
?>

<!-- نوار خرید چسبان موبایل (مطابق دیزاین ریدیزاین) -->
<div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-ink-900/95 backdrop-blur border-t border-ink-700 safe-bottom" data-express-bar>
  <div class="container-site flex items-center justify-between gap-3 py-3">
    <div class="flex flex-col min-w-0">
      <span class="text-xs text-ink-300 font-medium">سرمایه‌گذاری روی مهارت</span>
      <span class="text-cream font-extrabold text-[15px]"><?php echo esc_html($price_label); ?></span>
    </div>
    <button type="button" class="btn btn-violet rounded-full px-6 font-black shrink-0" data-express-open>
      <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a4 4 0 0 1-8 0"/><path d="M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></svg>
      <?php echo esc_html($label); ?>
    </button>
  </div>
</div>

<!-- مودال خرید سریع (متصل به express-checkout.js و درگاه زیبال/زرین‌پال) -->
<div class="express-modal" id="expressCheckoutModal" hidden data-express-modal>
  <div class="express-modal-backdrop" data-express-close tabindex="-1"></div>
  <div class="express-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="expressModalTitle">
    <button type="button" class="express-modal-close" data-express-close aria-label="بستن">×</button>

    <div class="express-modal-head">
      <span class="express-modal-eyebrow">ثبت‌نام سریع و امن</span>
      <h2 id="expressModalTitle"><?php echo esc_html($title); ?></h2>
      <p class="express-modal-price"><?php echo esc_html($price_label); ?></p>
    </div>

    <form class="space-y-4" data-express-form novalidate
      data-plan-id="<?php echo esc_attr((string) $plan['id']); ?>"
      data-course-id="<?php echo esc_attr((string) $course_id); ?>"
      data-return-url="<?php echo esc_url(get_permalink($course_id)); ?>"
      data-logged-in="<?php echo is_user_logged_in() ? '1' : '0'; ?>">

      <div class="express-step is-active" data-step="identity">
        <label class="express-field">
          <span>نام و نام خانوادگی</span>
          <input type="text" name="name" autocomplete="name" required value="<?php echo esc_attr($prefill_name); ?>" placeholder="مثلاً سارا محمدی" />
        </label>
        <label class="express-field">
          <span>شماره موبایل</span>
          <input type="tel" name="mobile" inputmode="numeric" autocomplete="tel" required dir="ltr" value="<?php echo esc_attr($prefill_mobile); ?>" placeholder="09xxxxxxxxx" <?php echo $prefill_mobile ? 'readonly' : ''; ?> />
        </label>
        <?php get_template_part('template-parts/checkout/gateway-selector', null, ['amount' => (int) $plan['price'], 'theme' => 'light']); ?>
        <p class="express-otp-hint">کد تأیید پیامک می‌شود؛ بلافاصله پس از پرداخت دسترسی دوره و لایسنس اسپات‌پلیر در پنل فعال می‌شود.</p>
        <button type="submit" class="btn btn-violet btn-lg w-full rounded-2xl font-black" data-express-send>
          <?php echo is_user_logged_in() ? 'ادامه و انتقال به درگاه' : 'دریافت کد تأیید و پرداخت'; ?>
        </button>
      </div>

      <div class="express-step" data-step="otp" hidden>
        <p class="express-otp-hint" data-express-otp-msg></p>
        <label class="express-field">
          <span>کد تأیید پیامکی</span>
          <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="8" placeholder="-----" class="text-center tracking-widest font-black" />
        </label>
        <div class="flex items-center gap-2.5">
          <button type="button" class="btn btn-outline rounded-xl" data-express-back>بازگشت</button>
          <button type="submit" class="btn btn-violet flex-1 rounded-xl font-black" data-express-verify>تأیید و پرداخت ←</button>
        </div>
        <button type="button" class="express-link-btn" data-express-resend disabled>ارسال دوباره کد</button>
      </div>

      <p class="express-modal-error" data-express-error hidden></p>
      <p class="text-xs font-bold text-mint-700 text-center" data-express-status hidden></p>
    </form>
  </div>
</div>
