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
      <p>محصولات را به <code>nasim_course</code> تبدیل می‌کند. اگر متای <code>_spotplayer_course</code> روی محصول باشد، پلن از نوع اسپات ساخته می‌شود.</p>
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