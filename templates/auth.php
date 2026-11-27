<?php

if (!defined('ABSPATH')) {
    exit;
}

$otp_login    = nasim_otp_login_enabled();
$registration = nasim_registration_enabled();
$otp_flow     = $otp_login || $registration;
$is_register  = 'register' === get_query_var('nasim_page');
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- redirect target, validated on login.
$redirect_to  = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
$code_length  = max(4, (int) nasim_setting('otp_length'));
$site_name    = get_bloginfo('name') ?: 'نیلوفر وفایی';
$site_tagline = get_bloginfo('description') ?: 'آکادمی طراحی سایت';
$terms_url    = function_exists('nasim_default_page_url') ? nasim_default_page_url('terms', home_url('/terms/')) : home_url('/terms/');
$privacy_url  = function_exists('get_privacy_policy_url') && get_privacy_policy_url() ? get_privacy_policy_url() : $terms_url;

if ($is_register && $otp_flow) {
    $title    = 'ورود و ثبت‌نام';
    $subtitle = 'لطفاً برای ساخت حساب، شماره موبایل خود را وارد کنید';
} elseif ($otp_flow) {
    $title    = 'ورود و ثبت‌نام';
    $subtitle = 'لطفاً برای ورود، شماره موبایل خود را وارد کنید';
} else {
    $title    = 'ورود با رمز عبور';
    $subtitle = 'لطفاً برای ورود، موبایل یا ایمیل و رمز خود را وارد کنید';
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#281e45">
  <?php wp_head(); ?>
</head>
<body <?php body_class('bg-[#efedf6] text-foreground antialiased min-h-screen flex flex-col'); ?>>
<?php wp_body_open(); ?>

  <main id="main-content" class="flex-1 flex flex-col items-center justify-center px-4 py-6 sm:py-10">

    <!-- لوگو بالای کارت (الهام از طراحی مینیمال سون‌لرن) -->
    <a href="<?php echo esc_url(home_url('/')); ?>" data-auth-brand class="mb-5 inline-flex items-center gap-3 select-none group" aria-label="<?php echo esc_attr($site_name); ?>">
      <span class="relative grid place-items-center size-11 rounded-2xl bg-ink-900 overflow-hidden shadow-soft group-hover:scale-105 transition-transform">
        <span class="absolute -bottom-2 -left-2 size-6 rounded-full bg-violet-400/90" aria-hidden="true"></span>
        <span class="relative font-black text-xl text-cream"><?php echo esc_html(mb_substr($site_name, 0, 1)); ?></span>
      </span>
      <span class="flex flex-col leading-none">
        <span class="font-black text-xl text-ink-950"><?php echo esc_html($site_name); ?></span>
        <span class="mt-1 text-[11px] font-semibold text-muted-foreground"><?php echo esc_html(mb_strimwidth($site_tagline, 0, 32, '…')); ?></span>
      </span>
    </a>

    <!-- کارت اصلی احراز هویت -->
    <section class="auth-card relative w-full max-w-[460px] rounded-[28px] bg-white border border-border/70 shadow-lift px-6 py-7 sm:px-10 sm:py-9 overflow-hidden" aria-labelledby="authTitle">

      <!-- لایه لودینگ بلور (حالت بررسی اطلاعات / ارسال کد) -->
      <div class="auth-loading" data-auth-loading aria-live="polite" aria-busy="false" hidden>
        <span class="auth-loading-ring" aria-hidden="true"></span>
        <p class="auth-loading-text" data-auth-loading-text>در حال بررسی اطلاعات...</p>
      </div>

      <!-- آیکون مرحله کد تأیید -->
      <div class="auth-step-icon mx-auto mb-5 size-14 rounded-2xl bg-sand border border-border/60 grid place-items-center" data-step-icon hidden>
        <span class="relative grid place-items-center size-9 rounded-xl bg-ink-900 overflow-hidden">
          <span class="absolute -bottom-1.5 -left-1.5 size-4 rounded-full bg-violet-400/90" aria-hidden="true"></span>
          <span class="relative font-black text-sm text-cream"><?php echo esc_html(mb_substr($site_name, 0, 1)); ?></span>
        </span>
      </div>

      <!-- عنوان و زیرعنوان پویا -->
      <header class="text-center mb-7">
        <h1 id="authTitle" data-auth-title class="text-2xl sm:text-[26px] font-black text-ink-950 tracking-tight"><?php echo esc_html($title); ?></h1>
        <p data-auth-subtitle class="mt-2.5 text-sm font-medium text-muted-foreground leading-7"><?php echo esc_html($subtitle); ?></p>
      </header>

      <?php if ($otp_flow) : ?>
      <form class="auth-form" data-view="otp" novalidate>

        <!-- مرحله ۱: ورود شماره موبایل -->
        <div class="auth-step space-y-4" data-step="mobile">
          <div>
            <label for="authMobile" class="sr-only">شماره موبایل</label>
            <input
              id="authMobile"
              type="tel"
              name="mobile"
              inputmode="numeric"
              autocomplete="tel"
              dir="ltr"
              placeholder="شماره موبایل"
              maxlength="16"
              required
              autofocus
              class="auth-input"
            />
          </div>

          <button type="submit" class="auth-submit auth-submit-btn" data-mobile-submit disabled>
            <span class="auth-submit-label">ورود</span>
          </button>
        </div>

        <!-- مرحله ۲: ورود کد تأیید (+ نام برای کاربر جدید) -->
        <div class="auth-step space-y-5" data-step="code" hidden>
          <div class="text-center -mt-3">
            <p class="text-sm font-medium text-muted-foreground leading-7">
              کد تأیید ارسال شده به تلفن همراه <bdi dir="ltr" data-masked-mobile class="font-extrabold text-ink-950 mx-0.5"></bdi> را وارد کنید
            </p>
            <button type="button" class="mt-3 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-sand hover:bg-sand-deep text-xs font-bold text-ink-700 transition-colors" data-edit>
              ویرایش تلفن همراه
            </button>
          </div>

          <div data-name-field hidden>
            <label for="authName" class="sr-only">نام و نام خانوادگی</label>
            <input
              id="authName"
              type="text"
              name="name"
              autocomplete="name"
              placeholder="نام و نام خانوادگی شما"
              maxlength="60"
              class="auth-input"
            />
          </div>

          <div>
            <label class="sr-only" for="authCode">کد تأیید</label>
            <div class="auth-otp" data-otp style="--otp-len:<?php echo (int) $code_length; ?>">
              <input
                id="authCode"
                type="text"
                name="code"
                class="auth-otp-input"
                inputmode="numeric"
                autocomplete="one-time-code"
                dir="ltr"
                maxlength="<?php echo (int) $code_length; ?>"
                pattern="[0-9]*"
                aria-describedby="authResend"
              />
              <div class="auth-otp-cells" aria-hidden="true">
                <?php for ($i = 0; $i < $code_length; $i++) : ?><span></span><?php endfor; ?>
              </div>
            </div>
          </div>

          <button type="submit" class="auth-submit auth-submit-btn" data-verify-submit disabled>
            <span class="auth-submit-label" data-verify-label>تأیید</span>
            <span class="auth-btn-timer" data-countdown></span>
          </button>

          <div class="text-center" id="authResend">
            <button type="button" class="text-xs font-extrabold text-violet-700 hover:text-ink-950 transition-colors" data-resend hidden>
              ارسال دوباره کد تأیید
            </button>
          </div>
        </div>

        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
        <p class="auth-message mt-4" role="alert" aria-live="polite"></p>

        <!-- لینک تغییر به ورود با رمز عبور -->
        <div class="mt-6 text-center">
          <button type="button" class="text-sm font-extrabold text-ink-900 hover:text-violet-700 transition-colors" data-switch="password">
            ورود با رمز عبور
          </button>
        </div>
      </form>
      <?php endif; ?>

      <!-- فرم ورود با رمز عبور -->
      <form class="auth-form" data-view="password" novalidate <?php echo $otp_flow ? 'hidden' : ''; ?>>
        <div class="auth-step space-y-4">
          <div>
            <label for="authIdentifier" class="sr-only">موبایل، ایمیل یا نام کاربری</label>
            <input
              id="authIdentifier"
              type="text"
              name="identifier"
              autocomplete="username"
              dir="ltr"
              placeholder="موبایل یا ایمیل"
              required
              class="auth-input"
            />
          </div>

          <div class="relative">
            <label for="authPassword" class="sr-only">رمز عبور</label>
            <input
              id="authPassword"
              type="password"
              name="password"
              autocomplete="current-password"
              dir="ltr"
              placeholder="رمز عبور ..."
              required
              class="auth-input px-12"
            />
            <button
              type="button"
              class="absolute inset-y-0 start-0 px-3.5 text-muted-foreground hover:text-ink-900 transition-colors"
              data-toggle-password
              aria-label="نمایش رمز عبور"
            >
              <?php echo nasim_icon('eye'); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
            </button>
          </div>

          <input type="hidden" name="remember" value="1" />

          <button type="submit" class="auth-submit auth-submit-btn mt-1" data-password-submit disabled>
            <span class="auth-submit-label">ورود</span>
          </button>
        </div>

        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
        <p class="auth-message mt-4" role="alert" aria-live="polite"></p>

        <div class="mt-6 flex items-center justify-between gap-3 text-sm font-extrabold text-ink-900">
          <a class="hover:text-violet-700 transition-colors" href="<?php echo esc_url(wp_lostpassword_url()); ?>">فراموشی رمز عبور</a>
          <?php if ($otp_flow) : ?>
          <button type="button" class="hover:text-violet-700 transition-colors" data-switch="otp">ورود با رمز یکبار مصرف</button>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <!-- قوانین زیر کارت -->
    <p class="mt-6 text-center text-xs sm:text-[13px] font-medium text-muted-foreground leading-7 max-w-md">
      ورود شما به معنای پذیرش
      <a href="<?php echo esc_url($privacy_url); ?>" class="font-extrabold text-ink-900 hover:text-violet-700 transition-colors">قوانین حریم خصوصی</a>
      و
      <a href="<?php echo esc_url($terms_url); ?>" class="font-extrabold text-ink-900 hover:text-violet-700 transition-colors">شرایط <?php echo esc_html($site_name); ?></a>
      است.
    </p>
  </main>

<?php wp_footer(); ?>
</body>
</html>
