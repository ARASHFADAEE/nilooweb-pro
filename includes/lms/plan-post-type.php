<?php

if(!defined('ABSPATH')) exit;



/**
 * Subscription plans (e.g. monthly, 3 months); listed under the courses menu
 */
function nasim_register_plan_post_type() {

    register_post_type('nasim_plan', [
        'labels' => [
            'name'          => __('پلن‌های اشتراک', 'niloo-web'),
            'singular_name' => __('پلن اشتراک', 'niloo-web'),
            'add_new'       => __('افزودن پلن', 'niloo-web'),
            'add_new_item'  => __('افزودن پلن جدید', 'niloo-web'),
            'edit_item'     => __('ویرایش پلن', 'niloo-web'),
            'all_items'     => __('پلن‌های اشتراک', 'niloo-web'),
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'edit.php?post_type=nasim_course',
        'supports'     => ['title', 'page-attributes'],
    ]);
}
add_action('init', 'nasim_register_plan_post_type');


function nasim_add_plan_meta_box() {

    add_meta_box('nasim_plan_details', __('جزئیات پلن', 'niloo-web'), 'nasim_render_plan_meta_box', 'nasim_plan', 'normal', 'high');
}
add_action('add_meta_boxes', 'nasim_add_plan_meta_box');


function nasim_render_plan_meta_box($post) {

    $plan = nasim_get_plan($post->ID, false);

    wp_nonce_field('nasim_save_plan', 'nasim_plan_nonce');
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="nasim_plan_price"><?php esc_html_e('قیمت (تومان)', 'niloo-web'); ?></label></th>
            <td><input type="number" min="0" step="1000" id="nasim_plan_price" name="nasim_plan_price" value="<?php echo esc_attr($plan['price'] ?: ''); ?>" class="regular-text" dir="ltr"></td>
        </tr>
        <tr>
            <th><label for="nasim_plan_days"><?php esc_html_e('مدت اشتراک (روز)', 'niloo-web'); ?></label></th>
            <td><input type="number" min="1" id="nasim_plan_days" name="nasim_plan_days" value="<?php echo esc_attr($plan['days']); ?>" class="small-text" dir="ltr">
                <p class="description"><?php esc_html_e('ماهانه = ۳۰ روز. اگر کاربر اشتراک فعال داشته باشد، مدت به انتهای اشتراک فعلی اضافه می‌شود.', 'niloo-web'); ?></p></td>
        </tr>
        <tr>
            <th><label for="nasim_plan_features"><?php esc_html_e('ویژگی‌ها (هر مورد در یک خط)', 'niloo-web'); ?></label></th>
            <td><textarea id="nasim_plan_features" name="nasim_plan_features" rows="5" class="large-text"><?php echo esc_textarea(implode("\n", $plan['features'])); ?></textarea></td>
        </tr>
        <tr>
            <th><?php esc_html_e('پلن پیشنهادی', 'niloo-web'); ?></th>
            <td><label><input type="checkbox" name="nasim_plan_featured" value="1" <?php checked($plan['featured']); ?>> <?php esc_html_e('با برچسب «پیشنهاد ویژه» نمایش داده شود', 'niloo-web'); ?></label></td>
        </tr>
        <tr>
            <th><label for="nasim_plan_type"><?php esc_html_e('نوع پلن', 'niloo-web'); ?></label></th>
            <td>
                <select name="nasim_plan_type" id="nasim_plan_type">
                    <option value="subscription" <?php selected(get_post_meta($post->ID, '_nasim_plan_type', true) ?: 'subscription', 'subscription'); ?>><?php esc_html_e('اشتراک (روز) — دوره‌های «رایگان با اشتراک»', 'niloo-web'); ?></option>
                    <option value="course" <?php selected(get_post_meta($post->ID, '_nasim_plan_type', true), 'course'); ?>><?php esc_html_e('خرید جداگانه دوره (LMS)', 'niloo-web'); ?></option>
                    <option value="spot_course" <?php selected(get_post_meta($post->ID, '_nasim_plan_type', true), 'spot_course'); ?>><?php esc_html_e('خرید جداگانه دوره اسپات', 'niloo-web'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="nasim_plan_course_id"><?php esc_html_e('دوره مرتبط', 'niloo-web'); ?></label></th>
            <td>
                <input type="number" min="0" id="nasim_plan_course_id" name="nasim_plan_course_id" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_nasim_plan_course_id', true)); ?>" class="small-text" dir="ltr">
                <p class="description"><?php esc_html_e('برای پلن‌های خرید جداگانه، شناسه پست دوره را وارد کنید.', 'niloo-web'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}


function nasim_save_plan_meta($post_id) {

    if (!isset($_POST['nasim_plan_nonce']) || !wp_verify_nonce(sanitize_key($_POST['nasim_plan_nonce']), 'nasim_save_plan')) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta($post_id, '_nasim_plan_price', absint(nasim_latin_digits(wp_unslash($_POST['nasim_plan_price'] ?? 0))));
    update_post_meta($post_id, '_nasim_plan_days', max(1, absint(nasim_latin_digits(wp_unslash($_POST['nasim_plan_days'] ?? 30)))));
    update_post_meta($post_id, '_nasim_plan_features', sanitize_textarea_field(wp_unslash($_POST['nasim_plan_features'] ?? '')));
    update_post_meta($post_id, '_nasim_plan_featured', !empty($_POST['nasim_plan_featured']) ? 1 : 0);

    $type = sanitize_key(wp_unslash($_POST['nasim_plan_type'] ?? 'subscription'));
    if (!in_array($type, ['subscription', 'course', 'spot_course'], true)) {
        $type = 'subscription';
    }
    update_post_meta($post_id, '_nasim_plan_type', $type);
    update_post_meta($post_id, '_nasim_plan_course_id', absint($_POST['nasim_plan_course_id'] ?? 0));

    if (in_array($type, ['course', 'spot_course'], true)) {
        update_post_meta($post_id, '_nasim_plan_days', 0);
    }
}
add_action('save_post_nasim_plan', 'nasim_save_plan_meta');


/**
 * Plan data; with $published_only, drafts and other post types return null
 */
function nasim_get_plan($plan_id, $published_only = true) {

    $post = get_post($plan_id);

    if (!$post || 'nasim_plan' !== $post->post_type || ($published_only && 'publish' !== $post->post_status)) {
        return $published_only ? null : ['id' => 0, 'title' => '', 'price' => 0, 'days' => 30, 'features' => [], 'featured' => false, 'type' => 'subscription', 'course_id' => 0];
    }

    $type = sanitize_key((string) get_post_meta($post->ID, '_nasim_plan_type', true));
    if (!in_array($type, ['subscription', 'course', 'spot_course'], true)) {
        $type = 'subscription';
    }

    $days = (int) get_post_meta($post->ID, '_nasim_plan_days', true);
    if ('subscription' === $type && $days < 1) {
        $days = 30;
    }

    return [
        'id'        => $post->ID,
        'title'     => get_the_title($post),
        'price'     => (int) get_post_meta($post->ID, '_nasim_plan_price', true),
        'days'      => $days,
        'features'  => nasim_split_lines(get_post_meta($post->ID, '_nasim_plan_features', true)),
        'featured'  => (bool) get_post_meta($post->ID, '_nasim_plan_featured', true),
        'type'      => $type,
        'course_id' => (int) get_post_meta($post->ID, '_nasim_plan_course_id', true),
    ];
}


/**
 * Published plans with a price, in menu order.
 *
 * @param string|null $type subscription|course|spot_course|null (all paid plans)
 */
function nasim_get_plans($type = 'subscription') {

    $posts = get_posts([
        'post_type'   => 'nasim_plan',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => ['menu_order' => 'ASC', 'date' => 'ASC'],
    ]);

    return array_values(array_filter(array_map(function ($post) {
        return nasim_get_plan($post->ID);
    }, $posts), function ($plan) use ($type) {
        if (!$plan || $plan['price'] <= 0) {
            return false;
        }
        if (null === $type || '' === $type) {
            return true;
        }
        if ('purchase' === $type) {
            return in_array($plan['type'], ['course', 'spot_course'], true);
        }

        return $plan['type'] === $type;
    }));
}


/**
 * Price per 30 days, for comparing plans
 */
function nasim_plan_monthly_price($plan) {

    return (int) round($plan['price'] / max(1, $plan['days'] / 30));
}


/**
 * Duration label: "ماهانه", "۳ ماهه" or "۴۵ روزه"
 */
function nasim_plan_duration_label($plan) {

    $type = $plan['type'] ?? (string) get_post_meta((int) ($plan['id'] ?? 0), '_nasim_plan_type', true);
    if (in_array($type, ['course', 'spot_course'], true) || (int) $plan['days'] <= 0) {
        return 'یک‌بار خرید';
    }

    if (30 === (int) $plan['days']) {
        return 'ماهانه';
    }

    if (0 === (int) $plan['days'] % 30) {
        return nasim_fa_digits((int) $plan['days'] / 30) . ' ماهه';
    }

    return nasim_fa_digits((int) $plan['days']) . ' روزه';
}


/**
 * Price and duration columns in the plans list
 */
function nasim_plan_columns($columns) {

    return array_merge(array_slice($columns, 0, 2), [
        'nasim_price' => __('قیمت', 'niloo-web'),
        'nasim_days'  => __('مدت', 'niloo-web'),
    ], array_slice($columns, 2));
}
add_filter('manage_nasim_plan_posts_columns', 'nasim_plan_columns');


function nasim_plan_column_content($column, $post_id) {

    $plan = nasim_get_plan($post_id, false);

    if ('nasim_price' === $column) {
        echo esc_html(nasim_format_price($plan['price']));
    }

    if ('nasim_days' === $column) {
        echo esc_html(nasim_plan_duration_label($plan));
    }
}
add_action('manage_nasim_plan_posts_custom_column', 'nasim_plan_column_content', 10, 2);
