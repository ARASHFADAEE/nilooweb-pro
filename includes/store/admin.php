<?php

if(!defined('ABSPATH')) exit;



/**
 * Store admin: licenses list, domain settings, integration guide
 */
function nasim_store_admin_menu() {

    add_submenu_page(
        'edit.php?post_type=nasim_product',
        __('لایسنس‌ها', 'nasim-fitness'),
        __('لایسنس‌ها', 'nasim-fitness'),
        'manage_options',
        'nasim-licenses',
        'nasim_render_licenses_page'
    );

    add_submenu_page(
        'edit.php?post_type=nasim_product',
        __('راهنمای اتصال لایسنس', 'nasim-fitness'),
        __('راهنمای اتصال', 'nasim-fitness'),
        'manage_options',
        'nasim-license-guide',
        'nasim_render_license_guide_page'
    );
}
add_action('admin_menu', 'nasim_store_admin_menu');


function nasim_register_store_settings() {

    register_setting('nasim_payment_settings', 'nasim_domain_slot_price', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 90000,
    ]);

    register_setting('nasim_payment_settings', 'nasim_license_default_slots', [
        'type'              => 'integer',
        'sanitize_callback' => function ($value) {
            return max(1, absint($value));
        },
        'default'           => 1,
    ]);
}
add_action('admin_init', 'nasim_register_store_settings');


/**
 * Domain slot fields on the existing Zibal settings page
 */
function nasim_render_domain_slot_settings_fields() {
    ?>
    <tr>
        <th colspan="2"><h2 style="margin:24px 0 8px"><?php esc_html_e('لایسنس و دامنه', 'nasim-fitness'); ?></h2></th>
    </tr>
    <tr>
        <th><label for="nasim_license_default_slots"><?php esc_html_e('تعداد دامنه رایگان', 'nasim-fitness'); ?></label></th>
        <td>
            <input type="number" min="1" id="nasim_license_default_slots" name="nasim_license_default_slots" value="<?php echo esc_attr((string) nasim_license_default_slots()); ?>" class="small-text">
            <p class="description"><?php esc_html_e('تعداد دامنه رایگان برای هر افزونه/قالب جداگانه (پیشنهاد: ۱).', 'nasim-fitness'); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="nasim_domain_slot_price"><?php esc_html_e('قیمت دامنه اضافه هر محصول (تومان)', 'nasim-fitness'); ?></label></th>
        <td>
            <input type="number" min="0" step="1000" id="nasim_domain_slot_price" name="nasim_domain_slot_price" value="<?php echo esc_attr((string) nasim_domain_slot_price()); ?>" class="regular-text" dir="ltr">
            <p class="description"><?php esc_html_e('برای دامنه دوم همان افزونه. صفر = غیرفعال.', 'nasim-fitness'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('nasim_payment_settings_after_gateway', 'nasim_render_domain_slot_settings_fields');


function nasim_handle_admin_license_actions() {

    if (!current_user_can('manage_options')) {
        return;
    }

    if (empty($_POST['nasim_license_action']) || empty($_POST['license_id'])) {
        return;
    }

    check_admin_referer('nasim_admin_license');

    $license_id = absint($_POST['license_id']);
    $action     = sanitize_key(wp_unslash($_POST['nasim_license_action']));
    $license    = nasim_get_license_by_id($license_id);

    if (!$license) {
        return;
    }

    if ('add_slot' === $action) {
        nasim_add_license_activation_slot((int) $license->user_id, 1);
    }

    if ('set_slots' === $action) {
        nasim_set_license_max_activations($license_id, absint($_POST['max_activations'] ?? 1));
    }

    if ('release_domain' === $action) {
        $domain = nasim_normalize_domain(wp_unslash($_POST['domain'] ?? ''));
        $slug   = sanitize_title(wp_unslash($_POST['product_slug'] ?? ''));
        if ($domain) {
            nasim_deactivate_license($license->license_key, $domain, $slug);
        }
    }

    wp_safe_redirect(add_query_arg([
        'post_type' => 'nasim_product',
        'page'      => 'nasim-licenses',
        'updated'   => 1,
    ], admin_url('edit.php')));
    exit;
}
add_action('admin_init', 'nasim_handle_admin_license_actions');


function nasim_render_licenses_page() {

    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    $page   = max(1, absint($_GET['paged'] ?? 1));
    $search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
    // phpcs:enable
    $result = nasim_get_licenses($page, 30, $search);
    $base   = admin_url('edit.php?post_type=nasim_product&page=nasim-licenses');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('لایسنس‌ها', 'nasim-fitness'); ?></h1>
        <p><?php esc_html_e('هر اشتراک یک کلید لایسنس دارد. هر افزونه جداگانه یک دامنه رایگان می‌گیرد؛ دامنه اضافه فقط برای همان محصول خریداری می‌شود.', 'nasim-fitness'); ?></p>

        <?php if (!empty($_GET['updated'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('ذخیره شد.', 'nasim-fitness'); ?></p></div>
        <?php endif; ?>

        <form method="get" style="margin:16px 0">
            <input type="hidden" name="post_type" value="nasim_product">
            <input type="hidden" name="page" value="nasim-licenses">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('جستجو کلید یا کاربر…', 'nasim-fitness'); ?>">
            <?php submit_button(__('جستجو', 'nasim-fitness'), 'secondary', '', false); ?>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th><?php esc_html_e('کاربر', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('کلید', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('دامنه‌ها', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('سقف', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('اشتراک', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('عملیات', 'nasim-fitness'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$result['items']) : ?>
                <tr><td colspan="7"><?php esc_html_e('لایسنسی ثبت نشده است.', 'nasim-fitness'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($result['items'] as $license) :
                $user         = get_userdata($license->user_id);
                $activations  = nasim_license_activations($license);
                $sub_active   = nasim_has_active_subscription((int) $license->user_id);
                ?>
                <tr>
                    <td><?php echo esc_html((string) $license->id); ?></td>
                    <td>
                        <?php if ($user) : ?>
                            <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>"><?php echo esc_html($user->display_name); ?></a>
                            <br><small dir="ltr"><?php echo esc_html($user->user_email); ?></small>
                        <?php else : ?>—<?php endif; ?>
                    </td>
                    <td><code dir="ltr"><?php echo esc_html($license->license_key); ?></code></td>
                    <td>
                        <?php if (!$activations) : ?>
                            <em><?php esc_html_e('فعال نشده', 'nasim-fitness'); ?></em>
                        <?php else : ?>
                            <ul style="margin:0;padding:0 16px 0 0">
                            <?php foreach ($activations as $row) : ?>
                                <li>
                                    <code dir="ltr"><?php echo esc_html($row['domain'] ?? ''); ?></code>
                                    <?php if (!empty($row['slug'])) : ?><small dir="ltr"> · <?php echo esc_html($row['slug']); ?></small><?php endif; ?>
                                    <form method="post" style="display:inline;margin-right:8px">
                                        <?php wp_nonce_field('nasim_admin_license'); ?>
                                        <input type="hidden" name="license_id" value="<?php echo esc_attr($license->id); ?>">
                                        <input type="hidden" name="domain" value="<?php echo esc_attr($row['domain'] ?? ''); ?>">
                                        <input type="hidden" name="product_slug" value="<?php echo esc_attr($row['slug'] ?? ''); ?>">
                                        <button type="submit" name="nasim_license_action" value="release_domain" class="button-link-delete"><?php esc_html_e('آزاد کردن', 'nasim-fitness'); ?></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html(count($activations) . ' / ' . (int) $license->max_activations); ?></td>
                    <td><?php echo $sub_active ? '<span style="color:#19834a">فعال</span>' : '<span style="color:#b42318">منقضی</span>'; ?></td>
                    <td>
                        <form method="post" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            <?php wp_nonce_field('nasim_admin_license'); ?>
                            <input type="hidden" name="license_id" value="<?php echo esc_attr($license->id); ?>">
                            <button type="submit" name="nasim_license_action" value="add_slot" class="button"><?php esc_html_e('+۱ دامنه', 'nasim-fitness'); ?></button>
                            <input type="number" min="1" name="max_activations" value="<?php echo esc_attr($license->max_activations); ?>" class="small-text">
                            <button type="submit" name="nasim_license_action" value="set_slots" class="button"><?php esc_html_e('تنظیم سقف', 'nasim-fitness'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $total_pages = (int) ceil($result['total'] / 30);
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo wp_kses_post(paginate_links([
                'base'      => add_query_arg('paged', '%#%', $base),
                'format'    => '',
                'current'   => $page,
                'total'     => $total_pages,
                'prev_text' => '‹',
                'next_text' => '›',
            ]));
            echo '</div></div>';
        }
        ?>
    </div>
    <?php
}


function nasim_render_license_guide_page() {

    $api_base = esc_url_raw(rest_url('nasim/v1'));
    $home     = esc_url_raw(home_url('/'));
    $sdk_path = THEME_DIR . '/includes/store/client/class-nasim-license-client.php';
    $sdk      = file_exists($sdk_path) ? file_get_contents($sdk_path) : '';
    ?>
    <div class="wrap" style="max-width:920px">
        <h1><?php esc_html_e('راهنمای اتصال لایسنس به افزونه / قالب', 'nasim-fitness'); ?></h1>

        <div class="card" style="padding:16px 20px;margin-top:16px">
            <h2><?php esc_html_e('مدل کار', 'nasim-fitness'); ?></h2>
            <ol style="line-height:1.9">
                <li><?php esc_html_e('کاربر اشتراک می‌خرد → یک کلید لایسنس در پنل کاربری‌اش ساخته می‌شود.', 'nasim-fitness'); ?></li>
                <li><?php esc_html_e('هر افزونه/قالب جداگانه یک دامنه رایگان دارد (نه یک دامنه برای همه محصولات).', 'nasim-fitness'); ?></li>
                <li><?php printf(esc_html__('دامنه دوم همان محصول با پرداخت %s اضافه می‌شود.', 'nasim-fitness'), esc_html(nasim_format_price(nasim_domain_slot_price()))); ?></li>
                <li><?php esc_html_e('افزونه روی سایت مشتری با API، دامنه + اسلاگ خودش را چک می‌کند.', 'nasim-fitness'); ?></li>
            </ol>
        </div>

        <div class="card" style="padding:16px 20px;margin-top:16px">
            <h2><?php esc_html_e('آدرس‌های API', 'nasim-fitness'); ?></h2>
            <p><?php esc_html_e('پایه:', 'nasim-fitness'); ?> <code dir="ltr"><?php echo esc_html($api_base); ?></code></p>
            <table class="widefat striped">
                <thead><tr><th>متد</th><th>مسیر</th><th>بدنه / پارامتر</th></tr></thead>
                <tbody>
                    <tr><td>POST</td><td dir="ltr">/license/activate</td><td dir="ltr"><code>{"license":"...","domain":"example.com","slug":"my-plugin"}</code></td></tr>
                    <tr><td>POST</td><td dir="ltr">/license/deactivate</td><td dir="ltr"><code>{"license":"...","domain":"example.com","slug":"my-plugin"}</code></td></tr>
                    <tr><td>GET</td><td dir="ltr">/license/status</td><td dir="ltr"><code>?license=...&amp;domain=example.com&amp;slug=my-plugin</code></td></tr>
                    <tr><td>GET</td><td dir="ltr">/update/{slug}</td><td dir="ltr"><code>?license=...&amp;domain=example.com</code></td></tr>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e('دامنه را بدون http و بدون www بفرستید (مثل mydomain.com).', 'nasim-fitness'); ?></p>
        </div>

        <div class="card" style="padding:16px 20px;margin-top:16px">
            <h2><?php esc_html_e('۱) فایل کلاینت را در افزونه کپی کنید', 'nasim-fitness'); ?></h2>
            <p><?php esc_html_e('فایل زیر را داخل افزونه/قالب خود بگذارید (مثلاً includes/class-nasim-license-client.php) و require کنید:', 'nasim-fitness'); ?></p>
            <p><code dir="ltr"><?php echo esc_html(str_replace(ABSPATH, '', $sdk_path)); ?></code></p>
            <textarea class="large-text code" rows="18" readonly dir="ltr" style="font-size:12px;white-space:pre"><?php echo esc_textarea($sdk); ?></textarea>
        </div>

        <div class="card" style="padding:16px 20px;margin-top:16px">
            <h2><?php esc_html_e('۲) در افزونه خود این کد را بگذارید', 'nasim-fitness'); ?></h2>
            <p><?php esc_html_e('ثابت‌ها را با اسلاگ محصول (همان اسلاگ بسته‌ای که در متاباکس محصول زده‌اید) و آدرس این سایت تنظیم کنید:', 'nasim-fitness'); ?></p>
            <?php
            $sample_boot = <<<'PHP'
define('MY_PLUGIN_LICENSE_API', 'https://example.com/'); // آدرس همین سایت فروش
define('MY_PLUGIN_SLUG', 'wp-s3-media-offload'); // اسلاگ بسته در محصولات
define('MY_PLUGIN_VERSION', '1.0.0');
define('MY_PLUGIN_FILE', __FILE__);

require_once __DIR__ . '/includes/class-nasim-license-client.php';

add_action('init', function () {
    Nasim_License_Client::instance([
        'api_home' => MY_PLUGIN_LICENSE_API,
        'slug'     => MY_PLUGIN_SLUG,
        'version'  => MY_PLUGIN_VERSION,
        'file'     => MY_PLUGIN_FILE,
        'option'   => 'my_plugin_license_key',
    ]);
});
PHP;
            $sample_boot = str_replace('https://example.com/', $home, $sample_boot);
            ?>
            <pre class="code" dir="ltr" style="background:#1d2327;color:#f0f0f1;padding:16px;overflow:auto;border-radius:6px;line-height:1.5"><?php echo esc_html($sample_boot); ?></pre>
        </div>

        <div class="card" style="padding:16px 20px;margin-top:16px">
            <h2><?php esc_html_e('۳) صفحه تنظیمات لایسنس در افزونه', 'nasim-fitness'); ?></h2>
            <p><?php esc_html_e('یک فیلد برای وارد کردن کلید لایسنس بسازید و با دکمه «فعال‌سازی» متدهای کلاینت را صدا بزنید:', 'nasim-fitness'); ?></p>
            <?php
            $sample_settings = <<<'PHP'
// ذخیره کلید
update_option('my_plugin_license_key', sanitize_text_field($_POST['license']));

$client = Nasim_License_Client::instance();
$result = $client->activate(get_option('my_plugin_license_key'));

if (is_wp_error($result)) {
    // نمایش $result->get_error_message()
} else {
    // «لایسنس روی این دامنه فعال شد»
}

// بررسی دوره‌ای (مثلاً روزانه با transient)
$status = $client->status(get_option('my_plugin_license_key'));
if (empty($status['valid']) || empty($status['domain_activated'])) {
    // امکانات پرمیوم را محدود کنید
}
PHP;
            ?>
            <pre class="code" dir="ltr" style="background:#1d2327;color:#f0f0f1;padding:16px;overflow:auto;border-radius:6px;line-height:1.5"><?php echo esc_html($sample_settings); ?></pre>
        </div>

        <div class="card" style="padding:16px 20px;margin-top:16px">
            <h2><?php esc_html_e('چک‌لیست محصول در این سایت', 'nasim-fitness'); ?></h2>
            <ul style="line-height:1.9">
                <li><?php esc_html_e('محصولات ← افزودن: نوع (افزونه/قالب)، اسلاگ بسته، نسخه، فایل ZIP', 'nasim-fitness'); ?></li>
                <li><?php esc_html_e('اسلاگ بسته باید با پوشه افزونه/قالب و ثابت MY_PLUGIN_SLUG یکی باشد.', 'nasim-fitness'); ?></li>
                <li><?php esc_html_e('دوره‌ها ← تنظیمات پرداخت: قیمت دامنه اضافه و درگاه زیبال', 'nasim-fitness'); ?></li>
                <li><?php esc_html_e('محصولات ← لایسنس‌ها: مشاهده دامنه‌های فعال و افزایش دستی سقف', 'nasim-fitness'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}
