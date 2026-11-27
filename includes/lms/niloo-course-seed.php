<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Seed آکادمی نیلوفر وفایی: دوره‌های استخراج‌شده از فروشگاه live (Store API + جلسات عمومی).
 * ویدیوهای DRM اسپات در خود اسپات می‌مانند؛ سرفصل‌ها + پلن قیمت اینجا ست می‌شود.
 * روی پرود با ووکامرس، «ایمپورت ووکامرس» شناسه اسپات و فایل‌های آموزشیار را تکمیل می‌کند.
 */

define('NASIM_NILOO_COURSE_SEED_VERSION', '3');

/**
 * Absolute path to the bundled JSON dump.
 */
function nasim_niloo_courses_json_path(): string
{
    return trailingslashit(get_template_directory()) . 'data/niloo-courses.json';
}

/**
 * Featured image URL: attachment or remote fallback from seed.
 */
function nasim_niloo_course_image_url(int $course_id, string $size = 'large'): string
{
    $src = get_the_post_thumbnail_url($course_id, $size);
    if ($src) {
        return $src;
    }

    return (string) get_post_meta($course_id, '_nasim_remote_thumbnail', true);
}

/**
 * @return array<int, array<string, mixed>>
 */
function nasim_niloo_courses_seed_data(): array
{
    $path = nasim_niloo_courses_json_path();
    if (!is_readable($path)) {
        return [];
    }

    $raw = json_decode((string) file_get_contents($path), true);

    return is_array($raw) ? $raw : [];
}

/**
 * Build curriculum with stable lesson ids.
 *
 * @param array<int, array<string, mixed>> $sections
 * @return array<int, array<string, mixed>>
 */
function nasim_niloo_normalize_curriculum(array $sections, string $course_slug): array
{
    $out      = [];
    $used_ids = [];
    $i        = 0;

    foreach ($sections as $section) {
        $lessons = [];
        foreach ((array) ($section['lessons'] ?? []) as $lesson) {
            $title = sanitize_text_field((string) ($lesson['title'] ?? ''));
            if ('' === $title) {
                continue;
            }
            $i++;
            $id = sanitize_key((string) ($lesson['id'] ?? ''));
            if ('' === $id || in_array($id, $used_ids, true)) {
                $id = substr(md5($course_slug . '|' . $i . '|' . $title), 0, 12);
            }
            $used_ids[] = $id;

            $embed = (string) ($lesson['embed'] ?? '');
            if ($embed && function_exists('nasim_normalize_lesson_embed')) {
                $embed = nasim_normalize_lesson_embed($embed);
            } elseif ($embed && function_exists('nasim_sanitize_embed')) {
                $embed = nasim_sanitize_embed($embed);
            }

            $lessons[] = [
                'id'             => $id,
                'title'          => $title,
                'type'           => sanitize_text_field((string) ($lesson['type'] ?? '')),
                'duration'       => sanitize_text_field((string) ($lesson['duration'] ?? '')),
                'description'    => sanitize_textarea_field((string) ($lesson['description'] ?? '')),
                'overview'       => sanitize_textarea_field((string) ($lesson['overview'] ?? '')),
                'embed'          => $embed,
                'is_free'        => !empty($lesson['is_free']),
                'resource_title' => sanitize_text_field((string) ($lesson['resource_title'] ?? '')),
                'resource_url'   => esc_url_raw((string) ($lesson['resource_url'] ?? '')),
            ];
        }

        $section_title = sanitize_text_field((string) ($section['title'] ?? ''));
        if ('' === $section_title && !$lessons) {
            continue;
        }
        $out[] = [
            'title'   => $section_title ?: __('سرفصل', 'niloo-web'),
            'lessons' => $lessons,
        ];
    }

    return $out;
}

/**
 * Sideload remote image as attachment; returns attachment ID or 0.
 */
function nasim_niloo_sideload_image(string $url, int $parent_id = 0): int
{
    $url = esc_url_raw($url);
    if (!$url) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($url, 45);
    if (is_wp_error($tmp)) {
        return 0;
    }

    $file_array = [
        'name'     => basename(parse_url($url, PHP_URL_PATH) ?: 'course.jpg'),
        'tmp_name' => $tmp,
    ];

    $id = media_handle_sideload($file_array, $parent_id);
    if (is_wp_error($id)) {
        @unlink($tmp);

        return 0;
    }

    return (int) $id;
}

/**
 * Insert or update one course from seed row.
 *
 * @param array<string, mixed> $row
 */
function nasim_niloo_upsert_course(array $row): int
{
    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    if ('' === $slug) {
        return 0;
    }

    $existing = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'any',
        'name'           => $slug,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    if (!$existing && !empty($row['wc_id'])) {
        $by_wc = get_posts([
            'post_type'      => 'nasim_course',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_nasim_wc_product_id',
            'meta_value'     => (int) $row['wc_id'],
        ]);
        $existing = $by_wc;
    }

    $featured_order = [
        'capsule-ultra'        => 1,
        'ultra-web'            => 2,
        'ultra-plus-webdesign' => 3,
        'ultra-business'       => 4,
        'ultra-dev'            => 5,
        'basic-webdesign'      => 6,
        'learning-discord'     => 7,
        'photoshop-intro'      => 8,
    ];

    $postarr = [
        'post_type'    => 'nasim_course',
        'post_status'  => 'publish',
        'post_title'   => sanitize_text_field((string) ($row['title'] ?? $slug)),
        'post_name'    => $slug,
        'post_excerpt' => wp_strip_all_tags((string) ($row['excerpt'] ?? '')),
        'post_content' => wp_kses_post((string) ($row['content'] ?? '')),
        'menu_order'   => (int) ($featured_order[$slug] ?? (100 + (int) ($row['wc_id'] ?? 0))),
    ];

    if ($existing) {
        $postarr['ID'] = (int) $existing[0];
        $course_id     = wp_update_post($postarr, true);
    } else {
        $course_id = wp_insert_post($postarr, true);
    }

    if (is_wp_error($course_id) || !$course_id) {
        return 0;
    }

    $course_id = (int) $course_id;

    if (!empty($row['wc_id'])) {
        update_post_meta($course_id, '_nasim_wc_product_id', (int) $row['wc_id']);
    }

    $delivery = in_array(($row['delivery'] ?? ''), ['lms', 'spot', 'hybrid'], true)
        ? $row['delivery']
        : 'lms';
    update_post_meta($course_id, '_nasim_course_delivery', $delivery);

    $access = sanitize_key((string) ($row['access'] ?? ''));
    if (!in_array($access, ['free', 'subscription', 'purchase'], true)) {
        $price_guess = (int) ($row['price'] ?? 0);
        $access      = $price_guess <= 0 ? 'free' : 'purchase';
    }
    update_post_meta($course_id, '_nasim_course_access_mode', $access);

    if (!empty($row['spot_ids'])) {
        update_post_meta($course_id, '_nasim_spotplayer_course', sanitize_text_field((string) $row['spot_ids']));
    }

    if (!empty($row['level'])) {
        update_post_meta($course_id, '_nasim_course_level', sanitize_text_field((string) $row['level']));
    }

    $text_meta_map = [
        'tagline'        => '_nasim_course_tagline',
        'badge'          => '_nasim_course_badge',
        'duration_label' => '_nasim_course_duration_label',
        'projects'       => '_nasim_course_projects',
        'students'       => '_nasim_course_students',
        'status_label'   => '_nasim_course_status_label',
        'prereq'         => '_nasim_course_prereq',
    ];
    foreach ($text_meta_map as $row_key => $meta_key) {
        if (!empty($row[$row_key])) {
            update_post_meta($course_id, $meta_key, sanitize_text_field((string) $row[$row_key]));
        }
    }

    if (function_exists('nasim_assign_course_categories')) {
        nasim_assign_course_categories($course_id, $row);
    }

    if (!empty($row['outcomes'])) {
        update_post_meta($course_id, '_nasim_course_outcomes', sanitize_textarea_field((string) $row['outcomes']));
    }

    if (!empty($row['audience'])) {
        update_post_meta($course_id, '_nasim_course_audience', sanitize_textarea_field((string) $row['audience']));
    }

    $curriculum = nasim_niloo_normalize_curriculum((array) ($row['curriculum'] ?? []), $slug);
    update_post_meta($course_id, '_nasim_curriculum', $curriculum);

    if (!has_post_thumbnail($course_id) && !empty($row['image'])) {
        $att = nasim_niloo_sideload_image((string) $row['image'], $course_id);
        if ($att) {
            set_post_thumbnail($course_id, $att);
        } else {
            // Keep remote URL as fallback for local/dev without download.
            update_post_meta($course_id, '_nasim_remote_thumbnail', esc_url_raw((string) $row['image']));
        }
    }

    $price = (int) ($row['price'] ?? 0);
    // Only purchase-mode courses get a one-off checkout plan.
    if ('purchase' === $access && $price > 0) {
        $plan_id = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
        if (!$plan_id || 'nasim_plan' !== get_post_type($plan_id)) {
            $plan_id = wp_insert_post([
                'post_type'   => 'nasim_plan',
                'post_status' => 'publish',
                'post_title'  => 'خرید دوره: ' . $postarr['post_title'],
            ], true);
            if (!is_wp_error($plan_id)) {
                update_post_meta($course_id, '_nasim_linked_plan_id', (int) $plan_id);
            }
        }
        if ($plan_id && !is_wp_error($plan_id)) {
            update_post_meta($plan_id, '_nasim_plan_price', $price);
            update_post_meta($plan_id, '_nasim_plan_days', 0);
            update_post_meta($plan_id, '_nasim_plan_course_id', $course_id);
            update_post_meta($plan_id, '_nasim_plan_type', 'spot' === $delivery ? 'spot_course' : 'course');
            update_post_meta($plan_id, '_nasim_plan_features', "دسترسی دائمی به این دوره\nپشتیبانی آکادمی نیلوفر وفایی");
        }
    }

    return $course_id;
}

/**
 * Install / refresh seed courses (idempotent by slug / wc_id).
 *
 * @return array{created:int,updated:int,total:int}
 */
function nasim_niloo_install_course_seed(bool $force = false): array
{
    $version_key = 'nasim_niloo_course_seed_version';
    if (!$force && get_option($version_key) === NASIM_NILOO_COURSE_SEED_VERSION) {
        return ['created' => 0, 'updated' => 0, 'total' => 0];
    }

    $rows = nasim_niloo_courses_seed_data();
    $created = 0;
    $updated = 0;

    foreach ($rows as $row) {
        $slug = sanitize_title((string) ($row['slug'] ?? ''));
        $had  = $slug && get_page_by_path($slug, OBJECT, 'nasim_course');
        $id   = nasim_niloo_upsert_course($row);
        if (!$id) {
            continue;
        }
        if ($had) {
            $updated++;
        } else {
            $created++;
        }
    }

    update_option($version_key, NASIM_NILOO_COURSE_SEED_VERSION, false);

    return [
        'created' => $created,
        'updated' => $updated,
        'total'   => count($rows),
    ];
}

function nasim_niloo_maybe_seed_courses(): void
{
    // Only for Niloo client profile (store off).
    if (function_exists('nasim_store_enabled') && nasim_store_enabled()) {
        return;
    }

    if (get_option('nasim_niloo_course_seed_version') === NASIM_NILOO_COURSE_SEED_VERSION) {
        return;
    }

    nasim_niloo_install_course_seed(false);
}
add_action('after_switch_theme', static function () {
    nasim_niloo_install_course_seed(true);
}, 30);
add_action('init', 'nasim_niloo_maybe_seed_courses', 40);

function nasim_niloo_seed_admin_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=nasim_course',
        'سید دوره‌های نیلو',
        'سید دوره‌های نیلو',
        'manage_options',
        'nasim-niloo-seed',
        'nasim_niloo_render_seed_page'
    );
}
add_action('admin_menu', 'nasim_niloo_seed_admin_menu');

function nasim_niloo_render_seed_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $result = null;
    if (isset($_POST['nasim_niloo_seed']) && check_admin_referer('nasim_niloo_seed')) {
        delete_option('nasim_niloo_course_seed_version');
        $result = nasim_niloo_install_course_seed(true);
    }

    $count = (int) wp_count_posts('nasim_course')->publish;
    $data  = nasim_niloo_courses_seed_data();
    ?>
    <div class="wrap">
      <h1>سید دوره‌های آکادمی نیلوفر وفایی</h1>
      <?php if ($result) : ?>
      <div class="notice notice-success"><p>
        <?php echo esc_html(sprintf('انجام شد: %d جدید، %d به‌روز از مجموع %d ردیف JSON.', $result['created'], $result['updated'], $result['total'])); ?>
      </p></div>
      <?php endif; ?>
      <p>فایل: <code>data/niloo-courses.json</code> — <?php echo esc_html((string) count($data)); ?> دوره در باندل قالب.</p>
      <p>دوره‌های منتشرشده الان: <strong><?php echo esc_html((string) $count); ?></strong></p>
      <p>ویدیوهای قفل‌شدهٔ آموزشیار / اسپات فقط با خرید یا ایمپورت ووکامرس روی سرور اصلی به پلیر وصل می‌شوند؛ این سید عنوان، قیمت، تصویر و سرفصل‌ها را می‌آورد.</p>
      <form method="post">
        <?php wp_nonce_field('nasim_niloo_seed'); ?>
        <p class="submit"><button class="button button-primary" name="nasim_niloo_seed" value="1">اجرای مجدد سید</button></p>
      </form>
    </div>
    <?php
}
