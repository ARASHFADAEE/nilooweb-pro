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
        return spot_option();
    }

    $opt = get_option('spotplayer', []);

    return is_array($opt) ? $opt : [];
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
    if (!$phone && function_exists('nasim_auth_user_phone')) {
        $phone = nasim_auth_user_phone($user_id);
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
    if ('spot_course' !== $type) {
        return;
    }

    $course_id = (int) get_post_meta($plan_id, '_nasim_plan_course_id', true);
    if (!$course_id) {
        return;
    }

    $courses = nasim_course_spot_ids($course_id);
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
function nasim_spot_render_player(array $license_data, string $title = '', ?string $course = null): void
{
    if (function_exists('spot_shop_success')) {
        spot_shop_success($license_data, $title, $course);
        return;
    }

    $key = $license_data['key'] ?? '';
    if (!$key) {
        echo '<p class="lms-notice is-error">لایسنس اسپات هنوز ایجاد نشده است.</p>';
        return;
    }

    $domain = nasim_spot_option()['domain'] ?? 'app.spotplayer.ir';
    $domain = $domain ?: 'app.spotplayer.ir';
    ?>
    <div class="nasim-spot-fallback">
      <h2><?php echo esc_html($title); ?></h2>
      <p>کلید لایسنس را در اپ اسپات پلیر وارد کنید:</p>
      <textarea readonly dir="ltr" style="width:100%;min-height:88px"><?php echo esc_textarea($key); ?></textarea>
      <?php if (!empty(nasim_spot_option()['web'])) : ?>
      <div id="spotplayer" style="min-height:320px;margin-top:16px"></div>
      <script src="https://<?php echo esc_attr($domain); ?>/assets/js/app-api.js"></script>
      <script>
        (function () {
          if (typeof SpotPlayer === 'undefined') return;
          var root = document.getElementById('spotplayer');
          new SpotPlayer(root, '<?php echo esc_js(parse_url(home_url(), PHP_URL_PATH) ?: ''); ?>/spotx')
            .Open('<?php echo esc_js($key); ?>', <?php echo $course && preg_match('/^[0-9a-f]{24}$/i', $course) ? "'" . esc_js($course) . "'" : 'null'; ?>);
        })();
      </script>
      <?php endif; ?>
    </div>
    <?php
}

function nasim_spot_render_account_tab(): void
{
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $licenses = nasim_user_spot_licenses($user_id);
    if (!$licenses) {
        echo '<p class="account-lead">هنوز دوره اسپات‌پلیر فعالی ندارید.</p>';
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $view_payment = isset($_GET['spot_payment']) ? absint($_GET['spot_payment']) : 0;

    if ($view_payment && isset($licenses[$view_payment])) {
        $entry = $licenses[$view_payment];
        nasim_spot_render_player($entry['data'] ?? [], $entry['plan_title'] ?? '', $entry['course_ids'][0] ?? null);
        echo '<p><a class="text-link" href="' . esc_url(nasim_subscription_url(['tab' => 'spot'])) . '">← بازگشت به لیست</a></p>';
        return;
    }

    echo '<div class="home-course-grid" style="margin-top:12px">';
    foreach ($licenses as $pid => $entry) {
        $url = nasim_subscription_url(['tab' => 'spot', 'spot_payment' => $pid]);
        echo '<a class="home-course-card" href="' . esc_url($url) . '" style="grid-template-columns:1fr">';
        echo '<div class="home-course-body"><h3>' . esc_html($entry['plan_title'] ?? 'دوره') . '</h3>';
        echo '<p>مشاهده در پلیر وب یا دریافت کلید لایسنس</p></div></a>';
    }
    echo '</div>';
}

function nasim_spot_account_tab_link(): void
{
    if (!is_user_logged_in()) {
        return;
    }
    $tab = sanitize_key(wp_unslash($_GET['tab'] ?? '')); // phpcs:ignore
    echo '<a href="' . esc_url(nasim_subscription_url(['tab' => 'spot'])) . '" class="' . ('spot' === $tab ? 'is-active' : '') . '">اسپات پلیر</a>';
}
add_action('nasim_account_tabs_after', 'nasim_spot_account_tab_link');

function nasim_spot_account_tab_panel(): void
{
    $tab = sanitize_key(wp_unslash($_GET['tab'] ?? '')); // phpcs:ignore
    if ('spot' !== $tab || !is_user_logged_in()) {
        return;
    }
    ?>
    <section class="account-panel" id="spot">
      <h2>دوره‌های اسپات پلیر</h2>
      <?php nasim_spot_render_account_tab(); ?>
    </section>
    <?php
}
add_action('nasim_account_tab_content', 'nasim_spot_account_tab_panel', 20);