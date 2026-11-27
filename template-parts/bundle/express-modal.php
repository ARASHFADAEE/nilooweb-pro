<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Express checkout modal for a bundle (same contract and markup as the course modal;
 * driven by assets/js/express-checkout.js via [data-express-modal] / [data-express-open]).
 *
 * @var array $args { bundle_id: int, plan: array }
 */
$bundle_id  = isset($args['bundle_id']) ? (int) $args['bundle_id'] : (int) get_the_ID();
$plan       = $args['plan'] ?? null;

if (!$plan || empty($plan['id']) || (int) ($plan['price'] ?? 0) <= 0) {
    return;
}

$title      = get_the_title($bundle_id);
$return_url = (string) get_permalink($bundle_id);
$logged_in  = is_user_logged_in();
$user       = wp_get_current_user();
$name       = $logged_in ? (string) $user->display_name : '';
$mobile     = $logged_in ? (string) nasim_get_user_mobile($user->ID) : '';
$price      = nasim_format_price((int) $plan['price']);
?>
<div class="express-modal" id="expressCheckoutModal" hidden data-express-modal>
  <div class="express-modal-backdrop" data-express-close tabindex="-1"></div>
  <div class="express-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="expressModalTitle">
    <button type="button" class="express-modal-close" data-express-close aria-label="بستن">×</button>

    <div class="express-modal-head">
      <span class="express-modal-eyebrow">ثبت‌نام سریع و امن · پکیج</span>
      <h2 id="expressModalTitle"><?php echo esc_html($title); ?></h2>
      <p class="express-modal-price"><?php echo esc_html($price); ?></p>
    </div>

    <form class="space-y-4" data-express-form novalidate
      data-plan-id="<?php echo esc_attr((string) $plan['id']); ?>"
      data-course-id="0"
      data-return-url="<?php echo esc_url($return_url); ?>"
      data-logged-in="<?php echo $logged_in ? '1' : '0'; ?>">

      <div class="express-step is-active" data-step="identity">
        <label class="express-field">
          <span>نام و نام خانوادگی</span>
          <input type="text" name="name" autocomplete="name" required value="<?php echo esc_attr($name); ?>" placeholder="مثلاً سارا محمدی">
        </label>
        <label class="express-field">
          <span>شماره موبایل</span>
          <input type="tel" name="mobile" inputmode="numeric" autocomplete="tel" required dir="ltr" value="<?php echo esc_attr($mobile); ?>" placeholder="09xxxxxxxxx" <?php echo $mobile ? 'readonly' : ''; ?>>
        </label>
        <?php get_template_part('template-parts/checkout/gateway-selector', null, ['amount' => (int) $plan['price'], 'theme' => 'light']); ?>
        <p class="express-otp-hint"><?php echo $logged_in ? 'بعد از پرداخت، همه دوره‌های پکیج در پنلت فعال می‌شود.' : 'کد تأیید پیامک می‌شود؛ حسابت خودکار ساخته می‌شود و بلافاصله پس از پرداخت، همه دوره‌های پکیج فعال می‌شود.'; ?></p>
        <button type="submit" class="btn btn-violet btn-lg w-full rounded-2xl font-black" data-express-send>
          <?php echo $logged_in ? 'پرداخت و فعال‌سازی پکیج' : 'دریافت کد و ادامه پرداخت'; ?>
        </button>
      </div>

      <div class="express-step" data-step="otp" hidden>
        <p class="express-otp-hint" data-express-otp-msg></p>
        <label class="express-field">
          <span>کد تأیید</span>
          <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="8" placeholder="-----" class="text-center tracking-widest font-black">
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
