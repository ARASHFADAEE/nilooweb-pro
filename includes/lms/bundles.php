<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Course bundles (پکیج دوره)
 *
 * A bundle is a public post (nasim_bundle) that groups several courses and sells them for one price.
 * Each bundle owns a hidden purchase plan (nasim_plan, type "bundle") that the existing checkout,
 * gateways and payment table use unchanged. After a successful payment every course of the bundle
 * is granted to the buyer (snapshot at purchase time), so later edits never take access away.
 */


/**
 * Bump when bundle routes change
 */
define('NASIM_BUNDLE_REWRITE_VERSION', '1');


function nasim_register_bundle_post_type()
{
    register_post_type('nasim_bundle', [
        'labels' => [
            'name'               => __('پکیج‌ها', 'niloo-web'),
            'singular_name'      => __('پکیج', 'niloo-web'),
            'menu_name'          => __('پکیج‌ها', 'niloo-web'),
            'all_items'          => __('پکیج‌های دوره', 'niloo-web'),
            'add_new'            => __('افزودن پکیج', 'niloo-web'),
            'add_new_item'       => __('افزودن پکیج جدید', 'niloo-web'),
            'edit_item'          => __('ویرایش پکیج', 'niloo-web'),
            'view_item'          => __('مشاهده پکیج', 'niloo-web'),
            'search_items'       => __('جستجوی پکیج', 'niloo-web'),
            'not_found'          => __('پکیجی پیدا نشد.', 'niloo-web'),
            'not_found_in_trash' => __('پکیجی در زباله‌دان نیست.', 'niloo-web'),
        ],
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => 'edit.php?post_type=nasim_course',
        'has_archive'  => 'bundles',
        'rewrite'      => ['slug' => 'bundle', 'with_front' => false],
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'nasim_register_bundle_post_type');


function nasim_bundle_maybe_flush_rewrite_rules()
{
    if (get_option('nasim_bundle_rewrite_version') === NASIM_BUNDLE_REWRITE_VERSION) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('nasim_bundle_rewrite_version', NASIM_BUNDLE_REWRITE_VERSION);
}
add_action('init', 'nasim_bundle_maybe_flush_rewrite_rules', 99);


/* -------------------------------------------------------------------------
 * Data helpers
 * ---------------------------------------------------------------------- */

/**
 * Course ids of a bundle, in the order chosen by the admin (only existing courses)
 *
 * @return int[]
 */
function nasim_bundle_course_ids($bundle_id)
{
    $ids = get_post_meta((int) $bundle_id, '_nasim_bundle_courses', true);
    $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('absint', $ids)))) : [];

    return array_values(array_filter($ids, static function ($id) {
        return 'nasim_course' === get_post_type($id);
    }));
}


/**
 * Published courses of a bundle as posts
 *
 * @return WP_Post[]
 */
function nasim_bundle_courses($bundle_id)
{
    $ids = nasim_bundle_course_ids($bundle_id);

    if (!$ids) {
        return [];
    }

    return get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'publish',
        'post__in'       => $ids,
        'orderby'        => 'post__in',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    ]);
}


function nasim_bundle_price($bundle_id)
{
    return (int) get_post_meta((int) $bundle_id, '_nasim_bundle_price', true);
}


/**
 * Sum of the single-course prices of the bundle (what the buyer would pay separately)
 */
function nasim_bundle_regular_price($bundle_id)
{
    $manual = (int) get_post_meta((int) $bundle_id, '_nasim_bundle_regular_price', true);

    if ($manual > 0) {
        return $manual;
    }

    $sum = 0;
    foreach (nasim_bundle_course_ids($bundle_id) as $course_id) {
        $sum += function_exists('nasim_course_catalog_price') ? nasim_course_catalog_price($course_id) : 0;
    }

    return $sum;
}


/**
 * Savings of the bundle compared with buying courses one by one
 *
 * @return array{amount:int, percent:int}
 */
function nasim_bundle_savings($bundle_id)
{
    $price   = nasim_bundle_price($bundle_id);
    $regular = nasim_bundle_regular_price($bundle_id);

    if ($regular <= $price || $price <= 0) {
        return ['amount' => 0, 'percent' => 0];
    }

    return [
        'amount'  => $regular - $price,
        'percent' => (int) round((($regular - $price) / $regular) * 100),
    ];
}


function nasim_bundle_features($bundle_id)
{
    return nasim_split_lines(get_post_meta((int) $bundle_id, '_nasim_bundle_features', true));
}


function nasim_bundle_plan($bundle_id)
{
    $plan_id = (int) get_post_meta((int) $bundle_id, '_nasim_linked_plan_id', true);
    $plan    = $plan_id ? nasim_get_plan($plan_id, false) : null;

    return ($plan && !empty($plan['id']) && $plan['price'] > 0) ? $plan : null;
}


/**
 * Total lessons and duration of all courses in a bundle
 *
 * @return array{courses:int, lessons:int, seconds:int}
 */
function nasim_bundle_stats($bundle_id)
{
    $courses = nasim_bundle_course_ids($bundle_id);
    $lessons = 0;
    $seconds = 0;

    foreach ($courses as $course_id) {
        $lessons += count(nasim_get_course_lessons($course_id));
        $seconds += nasim_course_total_seconds($course_id);
    }

    return ['courses' => count($courses), 'lessons' => $lessons, 'seconds' => $seconds];
}


/**
 * Course ids a bundle plan unlocks (snapshot stored on the plan)
 *
 * @return int[]
 */
function nasim_plan_course_ids($plan_id)
{
    $ids = get_post_meta((int) $plan_id, '_nasim_plan_course_ids', true);

    if (is_array($ids) && $ids) {
        return array_values(array_filter(array_map('absint', $ids)));
    }

    $bundle_id = (int) get_post_meta((int) $plan_id, '_nasim_plan_bundle_id', true);

    return $bundle_id ? nasim_bundle_course_ids($bundle_id) : [];
}


/**
 * Whether a user can already watch every course of the bundle
 */
function nasim_user_owns_bundle($bundle_id, $user_id = null)
{
    $user_id = $user_id ?? get_current_user_id();
    $courses = nasim_bundle_course_ids($bundle_id);

    if (!$user_id || !$courses) {
        return false;
    }

    foreach ($courses as $course_id) {
        if (!nasim_user_has_course_access($course_id, $user_id)) {
            return false;
        }
    }

    return true;
}


/**
 * Published bundles that contain a course (for cross-selling on the course page)
 *
 * @return WP_Post[]
 */
function nasim_bundles_for_course($course_id)
{
    $bundles = get_posts([
        'post_type'      => 'nasim_bundle',
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'no_found_rows'  => true,
    ]);

    return array_values(array_filter($bundles, static function ($bundle) use ($course_id) {
        return in_array((int) $course_id, nasim_bundle_course_ids($bundle->ID), true);
    }));
}


/**
 * Bundles for listings (homepage, archive, Elementor)
 *
 * @return WP_Post[]
 */
function nasim_get_bundles($limit = -1, $featured_only = false)
{
    $args = [
        'post_type'      => 'nasim_bundle',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'no_found_rows'  => true,
    ];

    if ($featured_only) {
        $args['meta_key']   = '_nasim_bundle_featured';
        $args['meta_value'] = '1';
    }

    return get_posts($args);
}


function nasim_bundles_archive_url()
{
    return (string) (get_post_type_archive_link('nasim_bundle') ?: home_url('/bundles/'));
}


/* -------------------------------------------------------------------------
 * Linked plan (keeps checkout / gateways / payments unchanged)
 * ---------------------------------------------------------------------- */

/**
 * Create or update the hidden purchase plan of a bundle
 */
function nasim_bundle_sync_plan($bundle_id)
{
    $bundle = get_post($bundle_id);

    if (!$bundle || 'nasim_bundle' !== $bundle->post_type) {
        return 0;
    }

    $plan_id = (int) get_post_meta($bundle_id, '_nasim_linked_plan_id', true);
    $status  = 'publish' === $bundle->post_status ? 'publish' : 'draft';
    $postarr = [
        'post_type'   => 'nasim_plan',
        'post_status' => $status,
        'post_title'  => 0 === mb_strpos($bundle->post_title, 'پکیج') ? $bundle->post_title : 'پکیج: ' . $bundle->post_title,
    ];

    if ($plan_id && 'nasim_plan' === get_post_type($plan_id)) {
        $postarr['ID'] = $plan_id;
        wp_update_post(wp_slash($postarr));
    } else {
        $plan_id = (int) wp_insert_post(wp_slash($postarr));
        if (!$plan_id) {
            return 0;
        }
        update_post_meta($bundle_id, '_nasim_linked_plan_id', $plan_id);
    }

    $features = nasim_bundle_features($bundle_id);

    update_post_meta($plan_id, '_nasim_plan_type', 'bundle');
    update_post_meta($plan_id, '_nasim_plan_price', nasim_bundle_price($bundle_id));
    update_post_meta($plan_id, '_nasim_plan_days', 0);
    update_post_meta($plan_id, '_nasim_plan_course_id', 0);
    update_post_meta($plan_id, '_nasim_plan_bundle_id', (int) $bundle_id);
    update_post_meta($plan_id, '_nasim_plan_course_ids', nasim_bundle_course_ids($bundle_id));
    update_post_meta($plan_id, '_nasim_plan_features', implode("\n", $features));

    return $plan_id;
}


/**
 * Keep the plan status in sync when a bundle is trashed, restored or unpublished
 */
function nasim_bundle_status_transition($new_status, $old_status, $post)
{
    if ('nasim_bundle' !== $post->post_type || $new_status === $old_status) {
        return;
    }

    $plan_id = (int) get_post_meta($post->ID, '_nasim_linked_plan_id', true);

    if ($plan_id && 'nasim_plan' === get_post_type($plan_id)) {
        wp_update_post(['ID' => $plan_id, 'post_status' => 'publish' === $new_status ? 'publish' : 'draft']);
    }
}
add_action('transition_post_status', 'nasim_bundle_status_transition', 10, 3);


/* -------------------------------------------------------------------------
 * Admin: meta box + list columns
 * ---------------------------------------------------------------------- */

function nasim_bundle_meta_boxes()
{
    add_meta_box('nasim_bundle_details', __('دوره‌ها و قیمت پکیج', 'niloo-web'), 'nasim_render_bundle_meta_box', 'nasim_bundle', 'normal', 'high');
}
add_action('add_meta_boxes', 'nasim_bundle_meta_boxes');


function nasim_render_bundle_meta_box($post)
{
    $selected = nasim_bundle_course_ids($post->ID);
    $courses  = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => ['publish', 'draft', 'private'],
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    // Selected courses first, in their saved order.
    usort($courses, static function ($a, $b) use ($selected) {
        $ia = array_search((int) $a->ID, $selected, true);
        $ib = array_search((int) $b->ID, $selected, true);
        $ia = false === $ia ? PHP_INT_MAX : $ia;
        $ib = false === $ib ? PHP_INT_MAX : $ib;

        return $ia <=> $ib;
    });

    $price   = nasim_bundle_price($post->ID);
    $regular = (int) get_post_meta($post->ID, '_nasim_bundle_regular_price', true);
    $auto    = 0;
    foreach ($selected as $course_id) {
        $auto += nasim_course_catalog_price($course_id);
    }

    wp_nonce_field('nasim_save_bundle', 'nasim_bundle_nonce');
    ?>
    <style>
      .nasim-bundle-courses{max-height:340px;overflow:auto;border:1px solid #dcdcde;border-radius:6px;padding:4px 0;margin:0;background:#fff}
      .nasim-bundle-courses li{display:flex;align-items:center;gap:10px;padding:8px 12px;margin:0;border-bottom:1px solid #f0f0f1}
      .nasim-bundle-courses li:last-child{border-bottom:0}
      .nasim-bundle-courses .price{margin-inline-start:auto;color:#646970;font-size:12px}
      .nasim-bundle-courses .order{display:flex;gap:2px}
      .nasim-bundle-courses .order button{min-height:24px;line-height:1;padding:0 6px}
      .nasim-bundle-sum{background:#f6f7f7;border-radius:6px;padding:10px 12px;margin-top:10px}
    </style>
    <table class="form-table" role="presentation">
      <tr>
        <th><?php esc_html_e('دوره‌های پکیج', 'niloo-web'); ?></th>
        <td>
          <?php if (!$courses) : ?>
            <em><?php esc_html_e('هنوز دوره‌ای ساخته نشده است.', 'niloo-web'); ?></em>
          <?php else : ?>
          <ul class="nasim-bundle-courses" id="nasimBundleCourses">
            <?php foreach ($courses as $course) :
                $course_price = nasim_course_catalog_price($course->ID);
                $is_free      = 'free' === nasim_course_access_mode($course->ID);
                ?>
            <li>
              <span class="order">
                <button type="button" class="button button-small" data-move="-1" aria-label="<?php esc_attr_e('بالا', 'niloo-web'); ?>">↑</button>
                <button type="button" class="button button-small" data-move="1" aria-label="<?php esc_attr_e('پایین', 'niloo-web'); ?>">↓</button>
              </span>
              <label><input type="checkbox" name="nasim_bundle_courses[]" value="<?php echo esc_attr($course->ID); ?>" data-price="<?php echo esc_attr($course_price); ?>" <?php checked(in_array((int) $course->ID, $selected, true)); ?>>
                <?php echo esc_html($course->post_title); ?>
                <?php if ('publish' !== $course->post_status) : ?><em>(<?php esc_html_e('منتشرنشده', 'niloo-web'); ?>)</em><?php endif; ?>
              </label>
              <span class="price"><?php echo $is_free ? esc_html__('رایگان', 'niloo-web') : esc_html($course_price ? nasim_format_price($course_price) : '—'); ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <p class="description"><?php esc_html_e('ترتیب نمایش دوره‌ها در صفحه پکیج با دکمه‌های ↑ ↓ تنظیم می‌شود. بعد از پرداخت، کاربر به همه دوره‌های تیک‌خورده دسترسی دائمی می‌گیرد.', 'niloo-web'); ?></p>
          <div class="nasim-bundle-sum"><?php esc_html_e('جمع قیمت تکی دوره‌های انتخاب‌شده:', 'niloo-web'); ?> <strong id="nasimBundleSum"><?php echo esc_html(nasim_format_price($auto)); ?></strong></div>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="nasim_bundle_price"><?php esc_html_e('قیمت پکیج (تومان)', 'niloo-web'); ?></label></th>
        <td><input type="number" min="0" step="1000" id="nasim_bundle_price" name="nasim_bundle_price" value="<?php echo esc_attr($price ?: ''); ?>" class="regular-text" dir="ltr" required>
          <p class="description"><?php esc_html_e('مبلغی که کاربر برای کل پکیج پرداخت می‌کند.', 'niloo-web'); ?></p></td>
      </tr>
      <tr>
        <th><label for="nasim_bundle_regular_price"><?php esc_html_e('قیمت قبل از تخفیف (اختیاری)', 'niloo-web'); ?></label></th>
        <td><input type="number" min="0" step="1000" id="nasim_bundle_regular_price" name="nasim_bundle_regular_price" value="<?php echo esc_attr($regular ?: ''); ?>" class="regular-text" dir="ltr">
          <p class="description"><?php esc_html_e('خالی بماند تا جمع قیمت تکی دوره‌ها به‌عنوان قیمت خط‌خورده و میزان صرفه‌جویی نمایش داده شود.', 'niloo-web'); ?></p></td>
      </tr>
      <tr>
        <th><label for="nasim_bundle_badge"><?php esc_html_e('برچسب', 'niloo-web'); ?></label></th>
        <td><input type="text" id="nasim_bundle_badge" name="nasim_bundle_badge" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_nasim_bundle_badge', true)); ?>" class="regular-text" placeholder="<?php esc_attr_e('مثلاً: پرفروش‌ترین', 'niloo-web'); ?>"></td>
      </tr>
      <tr>
        <th><label for="nasim_bundle_features"><?php esc_html_e('ویژگی‌ها (هر مورد در یک خط)', 'niloo-web'); ?></label></th>
        <td><textarea id="nasim_bundle_features" name="nasim_bundle_features" rows="4" class="large-text" placeholder="<?php esc_attr_e("دسترسی دائمی به همه دوره‌ها\nپشتیبانی در پنل کاربری", 'niloo-web'); ?>"><?php echo esc_textarea((string) get_post_meta($post->ID, '_nasim_bundle_features', true)); ?></textarea></td>
      </tr>
      <tr>
        <th><?php esc_html_e('صفحه اصلی', 'niloo-web'); ?></th>
        <td><label><input type="checkbox" name="nasim_bundle_featured" value="1" <?php checked((bool) get_post_meta($post->ID, '_nasim_bundle_featured', true)); ?>> <?php esc_html_e('در بخش پکیج‌های صفحه اصلی نمایش داده شود', 'niloo-web'); ?></label></td>
      </tr>
    </table>
    <script>
    (function () {
      var list = document.getElementById('nasimBundleCourses');
      var sum = document.getElementById('nasimBundleSum');
      if (!list) return;
      var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
      var update = function () {
        var total = 0;
        list.querySelectorAll('input:checked').forEach(function (i) { total += parseInt(i.dataset.price || '0', 10); });
        if (sum) sum.textContent = fa(total.toLocaleString('en-US')) + ' تومان';
      };
      list.addEventListener('change', update);
      list.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-move]');
        if (!btn) return;
        var li = btn.closest('li');
        if (btn.dataset.move === '-1' && li.previousElementSibling) list.insertBefore(li, li.previousElementSibling);
        if (btn.dataset.move === '1' && li.nextElementSibling) list.insertBefore(li.nextElementSibling, li);
      });
    })();
    </script>
    <?php
}


function nasim_save_bundle_meta($post_id)
{
    if (!isset($_POST['nasim_bundle_nonce']) || !wp_verify_nonce(sanitize_key($_POST['nasim_bundle_nonce']), 'nasim_save_bundle')) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $courses = array_values(array_unique(array_filter(array_map('absint', (array) ($_POST['nasim_bundle_courses'] ?? [])), static function ($id) {
        return 'nasim_course' === get_post_type($id);
    })));

    update_post_meta($post_id, '_nasim_bundle_courses', $courses);
    update_post_meta($post_id, '_nasim_bundle_price', absint(nasim_latin_digits(wp_unslash($_POST['nasim_bundle_price'] ?? 0))));
    update_post_meta($post_id, '_nasim_bundle_regular_price', absint(nasim_latin_digits(wp_unslash($_POST['nasim_bundle_regular_price'] ?? 0))));
    update_post_meta($post_id, '_nasim_bundle_badge', sanitize_text_field(wp_unslash($_POST['nasim_bundle_badge'] ?? '')));
    update_post_meta($post_id, '_nasim_bundle_features', sanitize_textarea_field(wp_unslash($_POST['nasim_bundle_features'] ?? '')));
    update_post_meta($post_id, '_nasim_bundle_featured', !empty($_POST['nasim_bundle_featured']) ? 1 : 0);

    nasim_bundle_sync_plan($post_id);
}
add_action('save_post_nasim_bundle', 'nasim_save_bundle_meta');


function nasim_bundle_columns($columns)
{
    return array_merge(array_slice($columns, 0, 2), [
        'nasim_bundle_courses' => __('دوره‌ها', 'niloo-web'),
        'nasim_bundle_price'   => __('قیمت', 'niloo-web'),
        'nasim_bundle_sales'   => __('فروش موفق', 'niloo-web'),
    ], array_slice($columns, 2));
}
add_filter('manage_nasim_bundle_posts_columns', 'nasim_bundle_columns');


function nasim_bundle_column_content($column, $post_id)
{
    if ('nasim_bundle_courses' === $column) {
        echo esc_html(nasim_fa_digits(count(nasim_bundle_course_ids($post_id))) . ' دوره');
    }

    if ('nasim_bundle_price' === $column) {
        $savings = nasim_bundle_savings($post_id);
        echo esc_html(nasim_format_price(nasim_bundle_price($post_id)));
        if ($savings['percent']) {
            echo '<br><small>' . esc_html(nasim_fa_digits($savings['percent']) . '٪ تخفیف') . '</small>';
        }
    }

    if ('nasim_bundle_sales' === $column) {
        global $wpdb;
        $plan_id = (int) get_post_meta($post_id, '_nasim_linked_plan_id', true);
        $table   = nasim_payments_table();
        $count   = $plan_id ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE plan_id = %d AND status = 'paid'", $plan_id)) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        echo esc_html(nasim_fa_digits($count));
    }
}
add_action('manage_nasim_bundle_posts_custom_column', 'nasim_bundle_column_content', 10, 2);


/**
 * Hide bundle plans from the plans list: they are edited from the bundle screen
 */
function nasim_hide_bundle_plans_in_admin($query)
{
    if (!is_admin() || !$query->is_main_query() || 'nasim_plan' !== $query->get('post_type')) {
        return;
    }

    $query->set('meta_query', [
        'relation' => 'OR',
        ['key' => '_nasim_plan_type', 'value' => 'bundle', 'compare' => '!='],
        ['key' => '_nasim_plan_type', 'compare' => 'NOT EXISTS'],
    ]);
}
add_action('pre_get_posts', 'nasim_hide_bundle_plans_in_admin');


/* -------------------------------------------------------------------------
 * Fulfilment
 * ---------------------------------------------------------------------- */

/**
 * Grant every course of a bundle plan to the buyer
 *
 * @return int[] granted course ids
 */
function nasim_fulfill_bundle_plan($user_id, $plan_id)
{
    $course_ids = nasim_plan_course_ids($plan_id);

    foreach ($course_ids as $course_id) {
        nasim_grant_course_access((int) $user_id, (int) $course_id);
    }

    $bundle_id = (int) get_post_meta($plan_id, '_nasim_plan_bundle_id', true);

    if ($bundle_id) {
        $owned = (array) get_user_meta($user_id, '_nasim_bundles', true);
        $owned = array_values(array_unique(array_filter(array_map('absint', array_merge($owned, [$bundle_id])))));
        update_user_meta($user_id, '_nasim_bundles', $owned);
    }

    do_action('nasim_bundle_purchased', (int) $user_id, $bundle_id, $course_ids);

    return $course_ids;
}


/* -------------------------------------------------------------------------
 * Front-end
 * ---------------------------------------------------------------------- */

function nasim_bundle_document_title($parts)
{
    if (is_post_type_archive('nasim_bundle')) {
        $parts['title'] = 'پکیج‌های آموزشی';
    }

    return $parts;
}
add_filter('document_title_parts', 'nasim_bundle_document_title');


function nasim_bundle_archive_query($query)
{
    if (!is_admin() && $query->is_main_query() && $query->is_post_type_archive('nasim_bundle')) {
        $query->set('posts_per_page', 24);
        $query->set('orderby', ['menu_order' => 'ASC', 'date' => 'DESC']);
    }
}
add_action('pre_get_posts', 'nasim_bundle_archive_query');


/**
 * Product schema for bundle pages (plays well with the theme's SEO module)
 */
function nasim_bundle_schema()
{
    if (!is_singular('nasim_bundle')) {
        return;
    }

    $bundle_id = get_queried_object_id();
    $price     = nasim_bundle_price($bundle_id);

    if ($price <= 0) {
        return;
    }

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => get_the_title($bundle_id),
        'description' => wp_strip_all_tags(get_the_excerpt($bundle_id)),
        'url'         => get_permalink($bundle_id),
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => (string) ($price * 10),
            'priceCurrency' => 'IRR',
            'availability'  => 'https://schema.org/InStock',
            'url'           => get_permalink($bundle_id),
        ],
    ];

    $image = get_the_post_thumbnail_url($bundle_id, 'large');
    if ($image) {
        $schema['image'] = $image;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
add_action('wp_head', 'nasim_bundle_schema', 30);
