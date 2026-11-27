<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SpotPlayer bridge: uses the same API option as the official plugin (spotplayer).
 * Issues licenses on LMS payment for plans tied to Spot-backed courses.
 */

/**
 * @return array<string, mixed>
 */
function nasim_spot_option(): array
{
    if (function_exists('spot_option')) {
        $opt = spot_option();
    } else {
        $opt = get_option('spotplayer', []);
    }

    $opt = is_array($opt) ? $opt : [];

    if (function_exists('nasim_opt')) {
        $api = (string) nasim_opt('spot_api', '');
        if ('' !== $api) {
            $opt['api'] = $api;
        }
        if (null !== nasim_opt('spot_test', null)) {
            $opt['test'] = nasim_opt('spot_test') ? 1 : 0;
        }
    }

    return $opt;
}

function nasim_course_spot_ids(int $course_id): array
{
    $raw = (string) get_post_meta($course_id, '_nasim_spotplayer_course', true);
    if ('' === $raw) {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw)), function ($id) {
        return (bool) preg_match('/^[0-9a-f]{24}$/i', $id);
    }));
}

function nasim_course_delivery(int $course_id): string
{
    $mode = (string) get_post_meta($course_id, '_nasim_course_delivery', true);

    return in_array($mode, ['lms', 'spot', 'hybrid'], true) ? $mode : 'lms';
}

/**
 * Build license payload (compatible with Spot eval when custom code is set).
 *
 * @return array<string, mixed>
 */
function nasim_spot_license_payload(int $user_id, object $payment): array
{
    $user = get_userdata($user_id);
    if (!$user) {
        return [];
    }

    $opt  = nasim_spot_option();
    $code = isset($opt['code']) ? trim((string) $opt['code']) : '';

    if ($code !== '') {
        /** @var object $order Compatibility with Spot plugin eval templates. */
        $order = $payment;
        try {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged -- same contract as SpotPlayer plugin.
            $result = eval('return ' . $code . ';');
            if (is_array($result)) {
                return $result;
            }
        } catch (Throwable $e) {
            // Fall through to defaults.
        }
    }

    $phone = get_user_meta($user_id, 'nasim_phone', true);
    if (!$phone && function_exists('nasim_get_user_mobile')) {
        $phone = nasim_get_user_mobile($user_id);
    }

    $label = $phone ?: $user->user_email;

    return [
        'name'      => $user->display_name ?: $user->user_login,
        'watermark' => ['texts' => [['text' => (string) $label]]],
    ];
}

/**
 * @return array<string, mixed>|WP_Error
 */
function nasim_spot_api_request(string $url, array $data = [])
{
    $api = nasim_spot_option()['api'] ?? '';
    if ('' === $api) {
        return new WP_Error('nasim_spot_api', 'کلید API اسپات پلیر در تنظیمات پلاگین وارد نشده است.');
    }

    $headers = [
        'Content-Type' => 'application/json',
        '$Level'       => '-1',
        '$API'         => $api,
        'X-WpSpot'     => defined('SPOT_VERSION') ? (string) SPOT_VERSION : 'nilooweb-pro',
    ];

    $args = [
        'timeout' => 30,
        'headers' => $headers,
    ];

    if ($data) {
        $args['method'] = 'POST';
        $args['body']   = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        return new WP_Error('nasim_spot_json', 'پاسخ نامعتبر از اسپات پلیر.');
    }

    if (!empty($body['ex']) && is_array($body['ex'])) {
        return new WP_Error('nasim_spot_remote', (string) ($body['ex']['msg'] ?? 'خطای اسپات پلیر'));
    }

    return $body;
}

/**
 * @param array<string, mixed> $base
 * @return array<string, mixed>|WP_Error
 */
function nasim_spot_request_license(array $base, array $course_ids)
{
    $wm = $base['watermark']['texts'][0]['text'] ?? '';
    if (empty($base['name']) || strlen((string) $wm) < 4) {
        return new WP_Error('nasim_spot_fields', 'نام یا واترمارک لایسنس برای اسپات کامل نیست.');
    }

    $payload = array_merge($base, [
        'course'  => $course_ids,
        'test'    => !empty(nasim_spot_option()['test']) ? 1 : 0,
    ]);

    return nasim_spot_api_request('https://panel.spotplayer.ir/license/edit/', $payload);
}

/**
 * @return array<int, array<string, mixed>>
 */
function nasim_user_spot_licenses(int $user_id): array
{
    $all = get_user_meta($user_id, 'nasim_spot_licenses', true);

    return is_array($all) ? $all : [];
}

/**
 * @param array<string, mixed> $entry
 */
function nasim_save_user_spot_license(int $user_id, int $payment_id, array $entry): void
{
    $all = nasim_user_spot_licenses($user_id);
    $all[$payment_id] = $entry;
    update_user_meta($user_id, 'nasim_spot_licenses', $all);
}

/**
 * After a successful Zibal payment, grant Spot access when plan is tied to a Spot course.
 */
function nasim_spot_on_payment_completed(int $payment_id): void
{
    $payment = nasim_get_payment($payment_id);
    if (!$payment || 'paid' !== $payment->status) {
        return;
    }

    $plan_id = (int) $payment->plan_id;
    $type = (string) get_post_meta($plan_id, '_nasim_plan_type', true);
    if (!in_array($type, ['spot_course', 'bundle'], true)) {
        return;
    }

    if ('bundle' === $type) {
        // One SpotPlayer license that covers every protected course in the bundle.
        $course_id = 0;
        $courses   = [];
        foreach (nasim_plan_course_ids($plan_id) as $bundle_course) {
            if (in_array(nasim_course_delivery($bundle_course), ['spot', 'hybrid'], true)) {
                $courses = array_merge($courses, nasim_course_spot_ids($bundle_course));
            }
        }
        $courses = array_values(array_unique($courses));
    } else {
        $course_id = (int) get_post_meta($plan_id, '_nasim_plan_course_id', true);
        if (!$course_id) {
            return;
        }
        $courses = nasim_course_spot_ids($course_id);
    }

    if (!$courses) {
        return;
    }

    $existing = nasim_user_spot_licenses((int) $payment->user_id);
    if (!empty($existing[$payment_id]['key'])) {
        return;
    }

    $base = nasim_spot_license_payload((int) $payment->user_id, $payment);
    $base['payload'] = (string) $payment_id;

    $license = nasim_spot_request_license($base, $courses);
    if (is_wp_error($license)) {
        global $wpdb;
        $wpdb->update(
            nasim_payments_table(),
            ['note' => mb_substr('اسپات: ' . $license->get_error_message(), 0, 255)],
            ['id' => $payment_id],
            ['%s'],
            ['%d']
        );
        return;
    }

    nasim_save_user_spot_license((int) $payment->user_id, $payment_id, [
        'payment_id'  => $payment_id,
        'course_id'   => $course_id,
        'course_ids'  => $courses,
        'plan_title'  => $payment->plan_title,
        'data'        => $license,
        'created'     => time(),
    ]);
}
add_action('nasim_payment_completed', 'nasim_spot_on_payment_completed', 15);

/**
 * Enqueue Spot shop styles when plugin folder exists.
 */
function nasim_spot_enqueue_assets(): void
{
    if (!is_page_template('templates/subscription.php') && !is_singular('nasim_course')) {
        return;
    }

    $plugin_css = WP_PLUGIN_DIR . '/spotplayer/shop.css';
    if (is_readable($plugin_css)) {
        wp_enqueue_style('nasim-spot-shop', plugins_url('spotplayer/shop.css'), [], '1');
        $c = nasim_spot_option()['color'] ?? '#7466f0';
        wp_add_inline_style('nasim-spot-shop', "#sp_license > BUTTON {background: {$c}}");
    }
}
add_action('wp_enqueue_scripts', 'nasim_spot_enqueue_assets', 25);

/**
 * Player UI (mirrors Spot shortcode output for a single purchase).
 */
/**
 * Official SpotPlayer app download page.
 */
function nasim_spot_app_download_url(): string
{
    $default = function_exists('nasim_opt') ? (string) nasim_opt('spot_app_url', 'https://spotplayer.ir/') : 'https://spotplayer.ir/';

    return (string) apply_filters('nasim_spot_app_download_url', $default ?: 'https://spotplayer.ir/');
}

function nasim_spot_render_player(array $license_data, string $title = '', ?string $course = null): void
{
    $key = $license_data['key'] ?? '';
    if (!$key && function_exists('spot_shop_success')) {
        spot_shop_success($license_data, $title, $course);
        return;
    }

    if (!$key) {
        echo '<p class="lms-notice is-error">توکن اسپات هنوز ایجاد نشده است.</p>';
        return;
    }

    $app_url = nasim_spot_app_download_url();
    ?>
    <div class="nasim-spot-license-card account-card">
      <?php if ($title) : ?><h3><?php echo esc_html($title); ?></h3><?php endif; ?>
      <p class="account-lead">توکن لایسنس را کپی کن و داخل اپ اسپات‌پلیر وارد کن.</p>
      <div class="license-key-row is-large">
        <code dir="ltr" data-license-key><?php echo esc_html($key); ?></code>
        <button type="button" class="button button-primary button-small" data-copy-license>کپی توکن</button>
      </div>
      <div class="account-card-actions" style="margin-top:14px">
        <a class="button button-outline" href="<?php echo esc_url($app_url); ?>" target="_blank" rel="noopener">دانلود برنامه اسپات‌پلیر <span>↓</span></a>
      </div>
    </div>
    <?php
}

function nasim_spot_render_account_tab(): void
{
    if (!is_user_logged_in()) {
        return;
    }

    $user_id  = get_current_user_id();
    $licenses = nasim_user_spot_licenses($user_id);
    $app_url  = nasim_spot_app_download_url();

    echo '<div class="account-card" style="margin-bottom:16px">';
    echo '<div class="account-card-head"><h3>دانلود اپ اسپات‌پلیر</h3></div>';
    echo '<p class="account-lead">برنامه رسمی اسپات‌پلیر را نصب کن، بعد توکن لایسنس دوره‌ات را داخل اپ وارد کن.</p>';
    echo '<a class="button button-primary" href="' . esc_url($app_url) . '" target="_blank" rel="noopener">دانلود از سایت اسپات‌پلیر <span>↓</span></a>';
    echo '</div>';

    if (!$licenses) {
        echo '<div class="account-empty"><strong>هنوز توکن اسپاتی نداری</strong><p>بعد از خرید دوره‌های اسپات‌پلیر، توکن اینجا نمایش داده می‌شود.</p>';
        $courses = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
        echo '<a class="button button-outline" href="' . esc_url($courses) . '">مشاهده دوره‌ها</a></div>';
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $view_payment = isset($_GET['spot_payment']) ? absint($_GET['spot_payment']) : 0;

    if ($view_payment && isset($licenses[$view_payment])) {
        $entry = $licenses[$view_payment];
        nasim_spot_render_player($entry['data'] ?? [], $entry['plan_title'] ?? '', $entry['course_ids'][0] ?? null);
        echo '<p style="margin-top:12px"><a class="text-link" href="' . esc_url(nasim_subscription_url(['tab' => 'spot'])) . '">← بازگشت به لیست</a></p>';
        return;
    }

    echo '<div class="account-spot-list">';
    foreach ($licenses as $pid => $entry) {
        $url = nasim_subscription_url(['tab' => 'spot', 'spot_payment' => $pid]);
        $key = (string) (($entry['data']['key'] ?? '') ?: '');
        echo '<article class="account-card nasim-spot-license-card">';
        echo '<div class="account-card-head"><h3>' . esc_html($entry['plan_title'] ?? 'دوره') . '</h3>';
        echo '<a class="text-link" href="' . esc_url($url) . '">جزئیات ←</a></div>';
        if ($key) {
            echo '<div class="license-key-row"><code dir="ltr" data-license-key>' . esc_html($key) . '</code>';
            echo '<button type="button" class="button button-outline button-small" data-copy-license>کپی</button></div>';
        } else {
            echo '<p class="account-lead">توکن هنوز آماده نیست.</p>';
        }
        echo '</article>';
    }
    echo '</div>';
}

function nasim_spot_account_tab_panel(): void
{
    $tab = sanitize_key(wp_unslash($_GET['tab'] ?? '')); // phpcs:ignore
    if ('spot' !== $tab || !is_user_logged_in()) {
        return;
    }
    ?>
    <section class="account-panel" id="spot">
      <div class="account-section-intro">
        <div>
          <span class="eyebrow">ضدکپی</span>
          <h2>اسپات پلیر</h2>
        </div>
        <p>فقط توکن لایسنس و لینک دانلود برنامه اسپات‌پلیر.</p>
      </div>
      <?php nasim_spot_render_account_tab(); ?>
    </section>
    <?php
}
// Rendered directly by templates/subscription.php (panel v2).