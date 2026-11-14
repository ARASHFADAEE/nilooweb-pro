<?php

if(!defined('ABSPATH')) exit;



/**
 * Theme settings, stored as one array in the "nasim_theme_settings" option
 */
function nasim_settings_defaults() {

    return [
        'sms_provider'                 => 'smsir',
        'smsir_api_key'                => '',
        'smsir_login_template'         => '',
        'smsir_register_template'      => '',
        'smsir_subscription_template'  => '',
        'smsir_code_param'             => 'CODE',
        'msgway_api_key'               => '',
        'msgway_login_template'        => '',
        'msgway_register_template'     => '',
        'msgway_subscription_template' => '',
        'msgway_line'                  => 0,
        'sms_subscription'             => 0,
        'sms_subscription_params'      => 'NAME,PLAN,EXPIRE',
        'otp_length'                   => 5,
        'otp_ttl'                      => 120,
        'otp_resend'                   => 90,
        'auth_otp_login'               => 1,
        'auth_registration'            => 1,
    ];
}


function nasim_setting($key) {

    $defaults = nasim_settings_defaults();
    $settings = (array) get_option('nasim_theme_settings', []);

    return $settings[$key] ?? ($defaults[$key] ?? null);
}


function nasim_register_theme_settings() {

    register_setting('nasim_theme_settings', 'nasim_theme_settings', [
        'type'              => 'array',
        'sanitize_callback' => 'nasim_sanitize_theme_settings',
        'default'           => nasim_settings_defaults(),
    ]);
}
add_action('admin_init', 'nasim_register_theme_settings');


function nasim_sanitize_theme_settings($input) {

    $input   = is_array($input) ? $input : [];
    $current = (array) get_option('nasim_theme_settings', []);

    $clamp = function ($value, $min, $max) {
        return min($max, max($min, absint(nasim_latin_digits($value))));
    };

    $template = function ($field) use ($input) {
        return absint(nasim_latin_digits($input[$field] ?? '')) ?: '';
    };

    // API keys are never printed back into the form; an empty field keeps the saved key.
    $secret = function ($field) use ($input, $current) {
        $value = sanitize_text_field($input[$field] ?? '');

        return ('' === $value && empty($input[$field . '_clear'])) ? ($current[$field] ?? '') : $value;
    };

    $provider = sanitize_key($input['sms_provider'] ?? '');
    $line     = absint($input['msgway_line'] ?? 0);
    $params   = array_intersect(nasim_split_list(strtoupper(sanitize_text_field($input['sms_subscription_params'] ?? ''))), array_keys(nasim_subscription_sms_variables()));

    return [
        'sms_provider'                 => isset(nasim_sms_providers()[$provider]) ? $provider : 'smsir',
        'smsir_api_key'                => $secret('smsir_api_key'),
        'smsir_login_template'         => $template('smsir_login_template'),
        'smsir_register_template'      => $template('smsir_register_template'),
        'smsir_subscription_template'  => $template('smsir_subscription_template'),
        'smsir_code_param'             => preg_replace('/[^A-Za-z0-9_]/', '', (string) ($input['smsir_code_param'] ?? '')) ?: 'CODE',
        'msgway_api_key'               => $secret('msgway_api_key'),
        'msgway_login_template'        => $template('msgway_login_template'),
        'msgway_register_template'     => $template('msgway_register_template'),
        'msgway_subscription_template' => $template('msgway_subscription_template'),
        'msgway_line'                  => isset(Nasim_Msgway::LINES[$line]) ? $line : 0,
        'sms_subscription'             => empty($input['sms_subscription']) ? 0 : 1,
        'sms_subscription_params'      => implode(',', array_unique($params)) ?: 'NAME,PLAN,EXPIRE',
        'otp_length'                   => $clamp($input['otp_length'] ?? 5, 4, 8),
        'otp_ttl'                      => $clamp($input['otp_ttl'] ?? 120, 60, 600),
        'otp_resend'                   => $clamp($input['otp_resend'] ?? 90, 30, 600),
        'auth_otp_login'               => empty($input['auth_otp_login']) ? 0 : 1,
        'auth_registration'            => empty($input['auth_registration']) ? 0 : 1,
    ];
}


function nasim_theme_settings_menu() {

    add_menu_page(
        __('تنظیمات قالب', 'nasim-fitness'),
        __('تنظیمات قالب', 'nasim-fitness'),
        'manage_options',
        'nasim-theme-settings',
        'nasim_render_theme_settings_page',
        'dashicons-admin-generic',
        61
    );
}
add_action('admin_menu', 'nasim_theme_settings_menu');


/**
 * Cached account credit, so the settings page doesn't call sms.ir on every load
 */
function nasim_smsir_cached_credit() {

    $credit = get_transient('nasim_smsir_credit');

    if (false === $credit) {
        $credit = Nasim_SmsIr::from_settings()->credit();
        set_transient('nasim_smsir_credit', $credit, 5 * MINUTE_IN_SECONDS);
    }

    return $credit;
}


function nasim_flush_smsir_credit() {

    delete_transient('nasim_smsir_credit');
}
add_action('update_option_nasim_theme_settings', 'nasim_flush_smsir_credit');


/**
 * Password-type API key field; the saved key is only indicated, never echoed
 */
function nasim_settings_secret_field($field, $description) {

    $name    = 'nasim_theme_settings';
    $has_key = '' !== (string) nasim_setting($field);
    ?>
    <input type="password" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($name . '[' . $field . ']'); ?>" value="" class="regular-text" dir="ltr" autocomplete="new-password" placeholder="<?php echo $has_key ? esc_attr__('ذخیره شده — برای تغییر، کلید جدید را وارد کنید', 'nasim-fitness') : ''; ?>">
    <?php if ($has_key) : ?>
    <label style="margin-inline-start:10px"><input type="checkbox" name="<?php echo esc_attr($name . '[' . $field . '_clear]'); ?>" value="1"> <?php esc_html_e('حذف کلید', 'nasim-fitness'); ?></label>
    <?php endif; ?>
    <p class="description"><?php echo esc_html($description); ?></p>
    <?php
}


function nasim_settings_template_field($field, $label, $description = '') {

    ?>
    <tr>
        <th><label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label></th>
        <td><input type="text" inputmode="numeric" id="<?php echo esc_attr($field); ?>" name="nasim_theme_settings[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr(nasim_setting($field)); ?>" class="regular-text" dir="ltr">
            <?php if ($description) : ?><p class="description"><?php echo esc_html($description); ?></p><?php endif; ?></td>
    </tr>
    <?php
}


function nasim_render_theme_settings_page() {

    $name     = 'nasim_theme_settings';
    $provider = nasim_sms_provider();
    $credit   = 'smsir' === $provider && Nasim_SmsIr::from_settings()->is_configured() ? nasim_smsir_cached_credit() : null;
    $test     = get_transient('nasim_sms_test_' . get_current_user_id());

    if ($test) {
        delete_transient('nasim_sms_test_' . get_current_user_id());
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('تنظیمات قالب', 'nasim-fitness'); ?></h1>

        <?php if ($test) : ?>
        <div class="notice notice-<?php echo 'ok' === $test['status'] ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html($test['message']); ?></p></div>
        <?php endif; ?>

        <?php if (defined('NASIM_OTP_DEBUG') && NASIM_OTP_DEBUG) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('حالت اشکال‌زدایی OTP فعال است (NASIM_OTP_DEBUG): کدها به‌جای پیامک در لاگ PHP نوشته می‌شوند. روی سایت اصلی این ثابت را حذف کنید.', 'nasim-fitness'); ?></p></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('nasim_theme_settings'); ?>

            <h2 class="title"><?php esc_html_e('سامانه پیامک', 'nasim-fitness'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><?php esc_html_e('ارسال پیامک با', 'nasim-fitness'); ?></th>
                    <td>
                        <?php foreach (nasim_sms_providers() as $key => $label) : ?>
                        <label style="margin-inline-end:18px"><input type="radio" name="<?php echo esc_attr($name); ?>[sms_provider]" value="<?php echo esc_attr($key); ?>" <?php checked($provider, $key); ?> data-sms-provider> <?php echo esc_html($label); ?></label>
                        <?php endforeach; ?>
                        <p class="description"><?php esc_html_e('کد ورود/ثبت‌نام و پیامک فعال‌سازی اشتراک با سامانه انتخاب‌شده فرستاده می‌شود. تنظیمات هر دو سامانه ذخیره می‌ماند تا بتوانید هر وقت خواستید جابه‌جا کنید.', 'nasim-fitness'); ?></p>
                    </td>
                </tr>
            </table>

            <div data-sms-section="smsir">
                <h2 class="title"><?php esc_html_e('sms.ir', 'nasim-fitness'); ?></h2>
                <p><?php esc_html_e('پیامک‌ها با متد ارسال سریع (Verify) فرستاده می‌شوند. قالب‌ها را در پنل sms.ir بخش «ارسال سریع» بسازید؛ متن قالب کد باید متغیر کد را داشته باشد، مثلاً: کد ورود شما: #CODE#', 'nasim-fitness'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="smsir_api_key"><?php esc_html_e('کلید API', 'nasim-fitness'); ?></label></th>
                        <td>
                            <?php nasim_settings_secret_field('smsir_api_key', __('از پنل sms.ir، بخش برنامه‌نویسان ← لیست کلیدهای API. برای تست می‌توانید کلید نوع Sandbox بسازید (قالب پیش‌فرض آن 123456 با متغیر CODE است).', 'nasim-fitness')); ?>
                            <?php if (null !== $credit) : ?>
                            <p>
                                <?php if (is_wp_error($credit)) : ?>
                                <span style="color:#b32d2e"><?php echo esc_html(sprintf(__('اتصال ناموفق: %s', 'nasim-fitness'), $credit->get_error_message())); ?></span>
                                <?php else : ?>
                                <span style="color:#008a20"><?php echo esc_html(sprintf(__('اتصال برقرار است · اعتبار فعلی: %s', 'nasim-fitness'), number_format_i18n((float) $credit, 1))); ?></span>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    nasim_settings_template_field('smsir_login_template', __('کد قالب (پترن) ورود', 'nasim-fitness'));
                    nasim_settings_template_field('smsir_register_template', __('کد قالب (پترن) ثبت‌نام', 'nasim-fitness'), __('اگر خالی بماند، از قالب ورود استفاده می‌شود.', 'nasim-fitness'));
                    ?>
                    <tr>
                        <th><label for="smsir_code_param"><?php esc_html_e('نام متغیر کد در قالب', 'nasim-fitness'); ?></label></th>
                        <td><input type="text" id="smsir_code_param" name="<?php echo esc_attr($name); ?>[smsir_code_param]" value="<?php echo esc_attr(nasim_setting('smsir_code_param')); ?>" class="regular-text" dir="ltr">
                            <p class="description"><?php esc_html_e('بدون علامت #؛ مثلاً برای #CODE# بنویسید CODE', 'nasim-fitness'); ?></p></td>
                    </tr>
                    <?php nasim_settings_template_field('smsir_subscription_template', __('کد قالب فعال‌سازی اشتراک', 'nasim-fitness'), __('متغیرهای قالب را با همان نام‌های بخش «پیامک فعال‌سازی اشتراک» بسازید، مثلاً #NAME#', 'nasim-fitness')); ?>
                </table>
            </div>

            <div data-sms-section="msgway">
                <h2 class="title"><?php esc_html_e('راه پیام', 'nasim-fitness'); ?></h2>
                <p><?php esc_html_e('کد یک‌بارمصرف توسط قالب ساخته و بررسی می‌شود و در فیلد code برای راه پیام فرستاده می‌شود. در پنل راه پیام برای کد ورود/ثبت‌نام یک قالب از نوع کد یک‌بارمصرف (OTP) بسازید.', 'nasim-fitness'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="msgway_api_key"><?php esc_html_e('کلید API', 'nasim-fitness'); ?></label></th>
                        <td><?php nasim_settings_secret_field('msgway_api_key', __('از پنل راه پیام (msgway.com)، بخش توسعه‌دهندگان.', 'nasim-fitness')); ?></td>
                    </tr>
                    <?php
                    nasim_settings_template_field('msgway_login_template', __('کد قالب (templateID) ورود', 'nasim-fitness'));
                    nasim_settings_template_field('msgway_register_template', __('کد قالب (templateID) ثبت‌نام', 'nasim-fitness'), __('اگر خالی بماند، از قالب ورود استفاده می‌شود.', 'nasim-fitness'));
                    nasim_settings_template_field('msgway_subscription_template', __('کد قالب فعال‌سازی اشتراک', 'nasim-fitness'), __('پارامترهای قالب به ترتیب بخش «پیامک فعال‌سازی اشتراک» پر می‌شوند (هر پارامتر حداکثر ۴۰ کاراکتر).', 'nasim-fitness'));
                    ?>
                    <tr>
                        <th><label for="msgway_line"><?php esc_html_e('خط ارسال', 'nasim-fitness'); ?></label></th>
                        <td>
                            <select id="msgway_line" name="<?php echo esc_attr($name); ?>[msgway_line]">
                                <option value="0"><?php esc_html_e('پیش‌فرض راه پیام', 'nasim-fitness'); ?></option>
                                <?php foreach (Nasim_Msgway::LINES as $line => $label) : ?>
                                <option value="<?php echo esc_attr($line); ?>" <?php selected((int) nasim_setting('msgway_line'), $line); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <h2 class="title"><?php esc_html_e('پیامک فعال‌سازی اشتراک', 'nasim-fitness'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><?php esc_html_e('وضعیت', 'nasim-fitness'); ?></th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr($name); ?>[sms_subscription]" value="1" <?php checked(nasim_setting('sms_subscription')); ?>> <?php esc_html_e('بعد از خرید، تمدید یا افزودن دستی اشتراک، به موبایل کاربر پیامک ارسال شود', 'nasim-fitness'); ?></label></td>
                </tr>
                <tr>
                    <th><label for="sms_subscription_params"><?php esc_html_e('متغیرهای قالب (به ترتیب)', 'nasim-fitness'); ?></label></th>
                    <td>
                        <input type="text" id="sms_subscription_params" name="<?php echo esc_attr($name); ?>[sms_subscription_params]" value="<?php echo esc_attr(implode(',', nasim_subscription_sms_params())); ?>" class="regular-text" dir="ltr">
                        <p class="description">
                            <?php
                            $variables = [];
                            foreach (nasim_subscription_sms_variables() as $variable => $label) {
                                $variables[] = $variable . ' = ' . $label;
                            }
                            echo esc_html(sprintf(__('با کاما جدا کنید. متغیرهای مجاز: %s', 'nasim-fitness'), implode('، ', $variables)));
                            ?>
                        </p>
                        <p class="description"><?php esc_html_e('نمونه متن قالب: «#NAME# عزیز، اشتراک #PLAN# شما فعال شد و تا #EXPIRE# اعتبار دارد.» — در راه پیام به‌جای نام متغیر، پارامترها به همین ترتیب جایگذاری می‌شوند.', 'nasim-fitness'); ?></p>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php esc_html_e('ورود و ثبت‌نام', 'nasim-fitness'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><?php esc_html_e('روش‌های فعال', 'nasim-fitness'); ?></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr($name); ?>[auth_otp_login]" value="1" <?php checked(nasim_setting('auth_otp_login')); ?>> <?php esc_html_e('ورود با کد یک‌بارمصرف (پیامک)', 'nasim-fitness'); ?></label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr($name); ?>[auth_registration]" value="1" <?php checked(nasim_setting('auth_registration')); ?>> <?php esc_html_e('ثبت‌نام کاربر جدید (با تأیید شماره موبایل)', 'nasim-fitness'); ?></label>
                        <p class="description"><?php esc_html_e('ورود با نام کاربری و رمز عبور همیشه فعال است.', 'nasim-fitness'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="otp_length"><?php esc_html_e('تعداد ارقام کد', 'nasim-fitness'); ?></label></th>
                    <td><input type="number" min="4" max="8" id="otp_length" name="<?php echo esc_attr($name); ?>[otp_length]" value="<?php echo esc_attr(nasim_setting('otp_length')); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="otp_ttl"><?php esc_html_e('اعتبار کد (ثانیه)', 'nasim-fitness'); ?></label></th>
                    <td><input type="number" min="60" max="600" id="otp_ttl" name="<?php echo esc_attr($name); ?>[otp_ttl]" value="<?php echo esc_attr(nasim_setting('otp_ttl')); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="otp_resend"><?php esc_html_e('فاصله ارسال مجدد (ثانیه)', 'nasim-fitness'); ?></label></th>
                    <td><input type="number" min="30" max="600" id="otp_resend" name="<?php echo esc_attr($name); ?>[otp_resend]" value="<?php echo esc_attr(nasim_setting('otp_resend')); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('صفحات', 'nasim-fitness'); ?></th>
                    <td><code dir="ltr"><?php echo esc_html(nasim_auth_url('login')); ?></code> · <code dir="ltr"><?php echo esc_html(nasim_auth_url('register')); ?></code></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <hr>
        <h2 class="title"><?php esc_html_e('ارسال پیامک آزمایشی', 'nasim-fitness'); ?></h2>
        <p><?php echo esc_html(sprintf(__('با سامانه ذخیره‌شده (%s) ارسال می‌شود. کد ورود نمونه 12345 است؛ پیامک اشتراک با نام شما و اعتبار ۳۰ روزه ساخته می‌شود.', 'nasim-fitness'), nasim_sms_providers()[$provider])); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="nasim_sms_test">
            <?php wp_nonce_field('nasim_sms_test'); ?>
            <input type="tel" name="mobile" class="regular-text" dir="ltr" placeholder="09123456789" required>
            <select name="type">
                <option value="code"><?php esc_html_e('کد ورود', 'nasim-fitness'); ?></option>
                <option value="subscription"><?php esc_html_e('فعال‌سازی اشتراک', 'nasim-fitness'); ?></option>
            </select>
            <?php submit_button(__('ارسال آزمایشی', 'nasim-fitness'), 'secondary', 'submit', false); ?>
        </form>
    </div>

    <script>
    (function () {
        const radios = document.querySelectorAll('[data-sms-provider]');
        const toggle = function () {
            const active = document.querySelector('[data-sms-provider]:checked');
            document.querySelectorAll('[data-sms-section]').forEach(function (section) {
                section.hidden = !active || section.dataset.smsSection !== active.value;
            });
        };
        radios.forEach(function (radio) { radio.addEventListener('change', toggle); });
        toggle();
    })();
    </script>
    <?php
}


function nasim_handle_sms_test() {

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('دسترسی غیرمجاز.', 'nasim-fitness'), 403);
    }

    check_admin_referer('nasim_sms_test');

    $mobile = nasim_normalize_mobile(wp_unslash($_POST['mobile'] ?? ''));
    $type   = 'subscription' === ($_POST['type'] ?? '') ? 'subscription' : 'code';

    if (!$mobile) {
        $result = new WP_Error('mobile', __('شماره موبایل معتبر نیست.', 'nasim-fitness'));
    } elseif ('subscription' === $type) {
        $values = nasim_subscription_sms_values(get_current_user_id(), time() + 30 * DAY_IN_SECONDS);
        $result = nasim_sms_send_values($mobile, nasim_sms_template('subscription'), $values);
    } else {
        $result = nasim_sms_send_code($mobile, 'login', '12345');
    }

    set_transient('nasim_sms_test_' . get_current_user_id(), [
        'status'  => is_wp_error($result) ? 'error' : 'ok',
        'message' => is_wp_error($result) ? sprintf(__('ارسال ناموفق: %s', 'nasim-fitness'), $result->get_error_message()) : sprintf(__('پیامک آزمایشی به %s ارسال شد.', 'nasim-fitness'), $mobile),
    ], MINUTE_IN_SECONDS);

    nasim_flush_smsir_credit();

    wp_safe_redirect(admin_url('admin.php?page=nasim-theme-settings'));
    exit;
}
add_action('admin_post_nasim_sms_test', 'nasim_handle_sms_test');
