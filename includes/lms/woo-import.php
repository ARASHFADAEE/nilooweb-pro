<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import WooCommerce products into nasim_course + optional Spot-linked purchase plan.
 */

function nasim_woo_import_admin_menu(): void
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    add_submenu_page(
        'edit.php?post_type=nasim_course',
        'ایمپورت از ووکامرس',
        'ایمپورت ووکامرس',
        'manage_options',
        'nasim-woo-import',
        'nasim_render_woo_import_page'
    );
}
add_action('admin_menu', 'nasim_woo_import_admin_menu');

/**
 * @return int[] product IDs
 */
function nasim_woo_spot_course_ids(WC_Product $product): array
{
    $raw = (string) $product->get_meta('_spotplayer_course');
    if ('' === $raw) {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw)), function ($id) {
        return (bool) preg_match('/^[0-9a-f]{24}$/i', $id);
    }));
}

/**
 * Import or update one product as course.
 *
 * @return int|WP_Error course post ID
 */
function nasim_woo_import_product(int $product_id, bool $create_plan = true)
{
    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_Error('nasim_woo_missing', 'محصول پیدا نشد.');
    }

    $existing = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'meta_key'       => '_nasim_wc_product_id',
        'meta_value'     => $product_id,
        'fields'         => 'ids',
    ]);

    $postarr = [
        'post_type'    => 'nasim_course',
        'post_status'  => $product->get_status() === 'publish' ? 'publish' : 'draft',
        'post_title'   => $product->get_name(),
        'post_content' => $product->get_description(),
        'post_excerpt' => $product->get_short_description(),
        'post_name'    => $product->get_slug(),
    ];

    if ($existing) {
        $postarr['ID'] = (int) $existing[0];
        $course_id = wp_update_post($postarr, true);
    } else {
        $course_id = wp_insert_post($postarr, true);
    }

    if (is_wp_error($course_id)) {
        return $course_id;
    }

    update_post_meta($course_id, '_nasim_wc_product_id', $product_id);

    $thumb = $product->get_image_id();
    if ($thumb) {
        set_post_thumbnail($course_id, $thumb);
    }

    $spots = nasim_woo_spot_course_ids($product);
    if ($spots) {
        update_post_meta($course_id, '_nasim_spotplayer_course', implode(',', $spots));
        update_post_meta($course_id, '_nasim_course_delivery', 'spot');
    } else {
        update_post_meta($course_id, '_nasim_course_delivery', 'lms');
    }

    $curriculum = nasim_woo_build_curriculum_from_product($product);
    if ($curriculum) {
        update_post_meta($course_id, '_nasim_curriculum', $curriculum);
    }

    $price = (int) $product->get_price();
    if ($create_plan && $price > 0) {
        $plan_id = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
        if (!$plan_id || 'nasim_plan' !== get_post_type($plan_id)) {
            $plan_id = wp_insert_post([
                'post_type'   => 'nasim_plan',
                'post_status' => 'publish',
                'post_title'  => 'خرید دوره: ' . $product->get_name(),
            ], true);
            if (!is_wp_error($plan_id)) {
                update_post_meta($course_id, '_nasim_linked_plan_id', $plan_id);
            }
        }

        if ($plan_id && !is_wp_error($plan_id)) {
            update_post_meta($plan_id, '_nasim_plan_price', $price);
            update_post_meta($plan_id, '_nasim_plan_days', $spots ? 0 : 365);
            update_post_meta($plan_id, '_nasim_plan_course_id', $course_id);
            update_post_meta($plan_id, '_nasim_plan_type', $spots ? 'spot_course' : 'subscription');
            update_post_meta($plan_id, '_nasim_plan_features', "دسترسی کامل به دوره\nپشتیبانی آکادمی");
        }
    }

    return (int) $course_id;
}

/**
 * Known Amoozeshyar / DornaLMS product meta keys that may hold session lists.
 *
 * @return string[]
 */
function nasim_woo_amoozeshyar_video_meta_keys(): array
{
    return [
        '_amzshyar_product_videos',
        'amzshyar_product_videos',
        '_amzshyar_videos',
        'amzshyar_videos',
        '_product_videos',
        'product_videos',
        '_dornalms_course_items',
        'dornalms_course_items',
        '_course_videos',
        'course_videos',
        '_amzshyar_downloads',
        'amzshyar_downloads',
    ];
}

/**
 * @param mixed $node
 * @return array{title:string,duration:string,url:string,is_free:bool}|null
 */
function nasim_woo_normalize_video_node($node): ?array
{
    if (!is_array($node)) {
        return null;
    }

    $title = (string) (
        $node['title'] ?? $node['name'] ?? $node['label'] ?? $node['text'] ?? ''
    );
    $url = (string) (
        $node['url'] ?? $node['watch'] ?? $node['file'] ?? $node['src']
        ?? $node['video'] ?? $node['link'] ?? $node['download'] ?? ''
    );
    $duration = (string) ($node['duration'] ?? $node['time'] ?? $node['length'] ?? '');
    $is_free  = !empty($node['free']) || !empty($node['is_free']) || !empty($node['preview']);

    // Nested single-file object.
    if ('' === $url && isset($node['file']) && is_array($node['file'])) {
        $url = (string) ($node['file']['url'] ?? $node['file']['link'] ?? '');
    }

    if ('' === $title && '' === $url) {
        return null;
    }

    return [
        'title'    => $title ?: __('جلسه', 'niloo-web'),
        'duration' => $duration,
        'url'      => $url,
        'is_free'  => $is_free,
    ];
}

/**
 * Walk arbitrary nested arrays from theme meta and collect lesson-like nodes.
 *
 * @param mixed $data
 * @return array<int, array{title:string,duration:string,url:string,is_free:bool}>
 */
function nasim_woo_flatten_video_nodes($data): array
{
    $found = [];

    $walk = function ($node) use (&$walk, &$found) {
        if (!is_array($node)) {
            return;
        }

        $normalized = nasim_woo_normalize_video_node($node);
        $has_kids   = false;
        foreach ($node as $value) {
            if (is_array($value)) {
                $has_kids = true;
                break;
            }
        }

        // Prefer leaf-ish items that look like lessons.
        if ($normalized && (!$has_kids || $normalized['url'] || isset($node['title']) || isset($node['name']))) {
            // Skip pure section wrappers that only hold children.
            $child_lessons = 0;
            foreach ($node as $value) {
                if (is_array($value) && nasim_woo_normalize_video_node($value)) {
                    $child_lessons++;
                }
            }
            if ($child_lessons < 2) {
                $found[] = $normalized;
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $walk($value);
            }
        }
    };

    $walk($data);

    // Dedupe by title+url
    $uniq = [];
    $out  = [];
    foreach ($found as $item) {
        $key = md5($item['title'] . '|' . $item['url']);
        if (isset($uniq[$key])) {
            continue;
        }
        $uniq[$key] = true;
        $out[]      = $item;
    }

    return $out;
}

/**
 * Build LMS curriculum from Amoozeshyar meta and/or public product description.
 *
 * @return array<int, array{title:string,lessons:array<int,array<string,mixed>>}>
 */
function nasim_woo_build_curriculum_from_product(WC_Product $product): array
{
    $product_id = $product->get_id();
    $lessons    = [];

    foreach (nasim_woo_amoozeshyar_video_meta_keys() as $key) {
        $raw = get_post_meta($product_id, $key, true);
        if (!$raw) {
            continue;
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw     = is_array($decoded) ? $decoded : maybe_unserialize($raw);
        }
        if (!is_array($raw)) {
            continue;
        }
        foreach (nasim_woo_flatten_video_nodes($raw) as $node) {
            $lessons[] = $node;
        }
        if ($lessons) {
            break;
        }
    }

    // Fallback: scan all meta for arrays that look like video lists (theme key renamed).
    if (!$lessons) {
        foreach (get_post_meta($product_id) as $key => $values) {
            if (0 === strpos((string) $key, '_') && false === stripos((string) $key, 'video')
                && false === stripos((string) $key, 'course') && false === stripos((string) $key, 'download')
                && false === stripos((string) $key, 'amzshyar') && false === stripos((string) $key, 'dorna')) {
                continue;
            }
            $raw = maybe_unserialize($values[0] ?? '');
            if (!is_array($raw)) {
                continue;
            }
            $flat = nasim_woo_flatten_video_nodes($raw);
            if (count($flat) >= 2) {
                $lessons = $flat;
                break;
            }
        }
    }

    if (!$lessons) {
        return [];
    }

    $price   = (float) $product->get_price();
    $built   = [];
    $used    = [];
    $index   = 0;

    foreach ($lessons as $node) {
        $index++;
        $id = substr(md5($product_id . '|' . $index . '|' . $node['title']), 0, 12);
        if (in_array($id, $used, true)) {
            $id = substr(md5($id . $index), 0, 12);
        }
        $used[] = $id;

        $embed = '';
        if ($node['url'] && function_exists('nasim_normalize_lesson_embed')) {
            $embed = nasim_normalize_lesson_embed($node['url']);
        }

        $built[] = [
            'id'             => $id,
            'title'          => sanitize_text_field($node['title']),
            'type'           => '',
            'duration'       => sanitize_text_field($node['duration']),
            'description'    => '',
            'overview'       => '',
            'embed'          => $embed,
            'is_free'        => $node['is_free'] || $price <= 0,
            'resource_title' => '',
            'resource_url'   => $embed ? '' : esc_url_raw($node['url']),
        ];
    }

    return [
        [
            'title'   => __('جلسات دوره', 'niloo-web'),
            'lessons' => $built,
        ],
    ];
}

function nasim_render_woo_import_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    if (isset($_POST['nasim_woo_import']) && check_admin_referer('nasim_woo_import')) {
        $ids = isset($_POST['product_ids']) ? array_map('absint', (array) wp_unslash($_POST['product_ids'])) : [];
        $ok  = 0;
        foreach ($ids as $pid) {
            $r = nasim_woo_import_product($pid, !empty($_POST['create_plan']));
            if (!is_wp_error($r)) {
                $ok++;
            }
        }
        $message = sprintf('%d دوره ایمپورت یا به‌روز شد.', $ok);
    }

    $products = wc_get_products(['limit' => 200, 'status' => ['publish', 'draft'], 'orderby' => 'date']);
    ?>
    <div class="wrap">
      <h1>ایمپورت دوره از ووکامرس → LMS</h1>
      <?php if ($message) : ?>
      <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
      <?php endif; ?>
      <p>محصولات را به <code>nasim_course</code> تبدیل می‌کند. متای <code>_spotplayer_course</code> → پلن اسپات؛ جلسات قالب آموزشیار (DornaLMS) در صورت وجود به سرفصل LMS با لینک ویدیو منتقل می‌شوند.</p>
      <form method="post">
        <?php wp_nonce_field('nasim_woo_import'); ?>
        <p><label><input type="checkbox" name="create_plan" value="1" checked> ساخت/به‌روزرسانی پلن پرداخت با قیمت ووکامرس</label></p>
        <table class="widefat striped">
          <thead><tr><th></th><th>شناسه</th><th>نام</th><th>قیمت</th><th>اسپات</th></tr></thead>
          <tbody>
          <?php foreach ($products as $product) :
              $spots = nasim_woo_spot_course_ids($product);
              ?>
            <tr>
              <td><input type="checkbox" name="product_ids[]" value="<?php echo esc_attr($product->get_id()); ?>"></td>
              <td><?php echo esc_html((string) $product->get_id()); ?></td>
              <td><?php echo esc_html($product->get_name()); ?></td>
              <td><?php echo esc_html((string) $product->get_price()); ?></td>
              <td><?php echo $spots ? esc_html(implode(',', $spots)) : '—'; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <p class="submit"><button type="submit" name="nasim_woo_import" class="button button-primary" value="1">ایمپورت انتخاب‌شده‌ها</button></p>
      </form>
    </div>
    <?php
}