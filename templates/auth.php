<?php

if(!defined('ABSPATH')) exit;

$otp_login    = nasim_otp_login_enabled();
$registration = nasim_registration_enabled();
$tab          = 'register' === get_query_var('nasim_page') && $registration ? 'register' : 'login';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- redirect target, validated on login.
$redirect_to  = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
$code_length  = (int) nasim_setting('otp_length');

get_header();
?>

  <main class="auth-page">
    <div class="container-wide auth-shell">
      <section class="auth-card" data-tab-active="<?php echo esc_attr($tab); ?>">
        <div class="auth-card-head">
          <h1 data-title-login="ورود به حساب" data-title-register="ساخت حساب کاربری"><?php echo 'register' === $tab ? 'ساخت حساب کاربری' : 'ورود به حساب'; ?></h1>
          <p data-title-login="برای دسترسی به دوره‌ها، پیشرفت و اشتراکت وارد شو." data-title-register="چند ثانیه تا شروع؛ شماره موبایلت با یک پیامک تأیید می‌شود."><?php echo 'register' === $tab ? 'چند ثانیه تا شروع؛ شماره موبایلت با یک پیامک تأیید می‌شود.' : 'برای دسترسی به دوره‌ها، پیشرفت و اشتراکت وارد شو.'; ?></p>
        </div>

        <?php if ($registration) : ?>
        <div class="auth-tabs" role="tablist" aria-label="ورود یا ثبت‌نام">
          <button type="button" role="tab" id="auth-tab-login" aria-controls="auth-panel-login" aria-selected="<?php echo 'login' === $tab ? 'true' : 'false'; ?>" data-tab="login">ورود</button>
          <button type="button" role="tab" id="auth-tab-register" aria-controls="auth-panel-register" aria-selected="<?php echo 'register' === $tab ? 'true' : 'false'; ?>" data-tab="register">ثبت‌نام</button>
        </div>
        <?php endif; ?>

        <div class="auth-panel" id="auth-panel-login" role="tabpanel" aria-labelledby="auth-tab-login" <?php echo 'login' === $tab ? '' : 'hidden'; ?>>
          <?php if ($otp_login) : ?>
          <div class="auth-methods" role="group" aria-label="روش ورود">
            <button type="button" class="is-active" data-method="otp" aria-pressed="true">کد یک‌بارمصرف</button>
            <button type="button" data-method="password" aria-pressed="false">رمز عبور</button>
          </div>

          <form class="auth-form" data-form="otp-login" data-method-panel="otp" novalidate>
            <div class="auth-step" data-step="details">
              <label class="auth-field">
                <span>شماره موبایل</span>
                <input type="tel" name="mobile" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="09123456789" required>
              </label>
              <button type="submit" class="button button-primary auth-submit">دریافت کد ورود <span>←</span></button>
            </div>

            <div class="auth-step" data-step="code" hidden>
              <p class="auth-code-hint">کد <?php echo esc_html(nasim_fa_digits($code_length)); ?> رقمی ارسال‌شده به <bdi dir="ltr" data-masked-mobile></bdi> را وارد کن. <button type="button" class="auth-link" data-edit>ویرایش شماره</button></p>
              <label class="auth-field">
                <span>کد تأیید</span>
                <input type="text" name="code" class="auth-code-input" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="<?php echo esc_attr($code_length); ?>" placeholder="<?php echo esc_attr(str_repeat('•', $code_length)); ?>">
              </label>
              <button type="submit" class="button button-primary auth-submit">ورود <span>←</span></button>
              <div class="auth-resend"><span data-countdown></span><button type="button" class="auth-link" data-resend hidden>ارسال دوباره کد</button></div>
            </div>

            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
            <p class="auth-message" role="alert" aria-live="polite"></p>
          </form>
          <?php endif; ?>

          <form class="auth-form" data-form="password" data-method-panel="password" novalidate <?php echo $otp_login ? 'hidden' : ''; ?>>
            <label class="auth-field">
              <span>نام کاربری، موبایل یا ایمیل</span>
              <input type="text" name="identifier" autocomplete="username" dir="auto" required>
            </label>
            <label class="auth-field">
              <span>رمز عبور</span>
              <span class="auth-password">
                <input type="password" name="password" autocomplete="current-password" dir="ltr" required>
                <button type="button" class="auth-password-toggle" data-toggle-password aria-label="نمایش رمز عبور"><?php echo nasim_icon('eye'); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
              </span>
            </label>
            <div class="auth-row">
              <label class="auth-check"><input type="checkbox" name="remember" value="1" checked> مرا به خاطر بسپار</label>
              <?php if ($otp_login) : ?>
              <button type="button" class="auth-link" data-method="otp">رمز را فراموش کرده‌ای؟</button>
              <?php else : ?>
              <a class="auth-link" href="<?php echo esc_url(wp_lostpassword_url()); ?>">رمز را فراموش کرده‌ای؟</a>
              <?php endif; ?>
            </div>
            <button type="submit" class="button button-primary auth-submit">ورود <span>←</span></button>
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
            <p class="auth-message" role="alert" aria-live="polite"></p>
          </form>
        </div>

        <?php if ($registration) : ?>
        <div class="auth-panel" id="auth-panel-register" role="tabpanel" aria-labelledby="auth-tab-register" <?php echo 'register' === $tab ? '' : 'hidden'; ?>>
          <form class="auth-form" data-form="register" novalidate>
            <div class="auth-step" data-step="details">
              <label class="auth-field">
                <span>نام و نام خانوادگی</span>
                <input type="text" name="name" autocomplete="name" required>
              </label>
              <div class="auth-grid">
                <label class="auth-field">
                  <span>نام کاربری</span>
                  <input type="text" name="username" autocomplete="username" dir="ltr" placeholder="nasim_fit" required>
                </label>
                <label class="auth-field">
                  <span>شماره موبایل</span>
                  <input type="tel" name="mobile" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="09123456789" required>
                </label>
              </div>
              <label class="auth-field">
                <span>رمز عبور <small>(حداقل ۸ کاراکتر)</small></span>
                <span class="auth-password">
                  <input type="password" name="password" autocomplete="new-password" dir="ltr" minlength="8" required>
                  <button type="button" class="auth-password-toggle" data-toggle-password aria-label="نمایش رمز عبور"><?php echo nasim_icon('eye'); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
                </span>
              </label>
              <button type="submit" class="button button-primary auth-submit">دریافت کد تأیید <span>←</span></button>
            </div>

            <div class="auth-step" data-step="code" hidden>
              <p class="auth-code-hint">برای تأیید شماره، کد ارسال‌شده به <bdi dir="ltr" data-masked-mobile></bdi> را وارد کن. <button type="button" class="auth-link" data-edit>ویرایش اطلاعات</button></p>
              <label class="auth-field">
                <span>کد تأیید</span>
                <input type="text" name="code" class="auth-code-input" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="<?php echo esc_attr($code_length); ?>" placeholder="<?php echo esc_attr(str_repeat('•', $code_length)); ?>">
              </label>
              <button type="submit" class="button button-primary auth-submit">ساخت حساب <span>←</span></button>
              <div class="auth-resend"><span data-countdown></span><button type="button" class="auth-link" data-resend hidden>ارسال دوباره کد</button></div>
            </div>

            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
            <p class="auth-message" role="alert" aria-live="polite"></p>
          </form>
        </div>
        <?php endif; ?>
      </section>

      <aside class="auth-aside" aria-hidden="true">
        <span class="auth-aside-ring"></span>
        <div class="auth-aside-copy">
          <?php if (function_exists('nasim_theme_logo_url') && nasim_theme_logo_url('white')) : ?>
          <img src="<?php echo esc_url(nasim_theme_logo_url('white')); ?>" alt="<?php echo esc_attr(get_bloginfo('name') ?: 'نیلوفر وفایی'); ?>" width="160" height="72" decoding="async" style="height:48px;width:auto;margin-bottom:22px">
          <?php else : ?>
          <span class="brand-mark">ن</span>
          <?php endif; ?>
          <h2>پشتیبانی وردپرس و افزونه‌های کاربردی، یکجا</h2>
          <ul>
            <li><span>✓</span>دسترسی به دوره‌های اشتراکی با یک پلن</li>
            <li><span>✓</span>ذخیره پیشرفت، درس‌های محبوب و یادداشت‌ها</li>
            <li><span>✓</span>ورود سریع و امن با کد پیامکی</li>
          </ul>
        </div>
      </aside>
    </div>
  </main>

<?php
get_footer();
