<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Seed blog posts + categories from live آکادمی نیلوفر وفایی dump.
 */

define('NASIM_NILOO_POSTS_SEED_VERSION', '1');

function nasim_niloo_posts_json_path(): string
{
    return trailingslashit(get_template_directory()) . 'data/niloo-posts.json';
}

/**
 * @return array<int, array<string, mixed>>
 */
function nasim_niloo_posts_seed_data(): array
{
    $path = nasim_niloo_posts_json_path();
    if (!is_readable($path)) {
        return [];
    }
    $raw = json_decode((string) file_get_contents($path), true);

    return is_array($raw) ? $raw : [];
}

/**
 * Ensure a category exists by slug; return term_id.
 */
function nasim_niloo_ensure_category(string $slug, string $name = ''): int
{
    $slug = sanitize_title($slug);
    if ('' === $slug) {
        return 0;
    }

    $term = get_term_by('slug', $slug, 'category');
    if ($term && !is_wp_error($term)) {
        return (int) $term->term_id;
    }

    $created = wp_insert_term($name ?: $slug, 'category', ['slug' => $slug]);
    if (is_wp_error($created)) {
        return 0;
    }

    return (int) $created['term_id'];
}

/**
 * @param array<string, mixed> $row
 */
function nasim_niloo_upsert_post(array $row): int
{
    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    if ('' === $slug) {
        return 0;
    }

    $existing = get_posts([
        'name'             => $slug,
        'post_type'        => 'post',
        'post_status'      => 'any',
        'posts_per_page'   => 1,
        'fields'           => 'ids',
        'suppress_filters' => true,
    ]);

    $postarr = [
        'post_type'    => 'post',
        'post_status'  => 'publish',
        'post_title'   => sanitize_text_field((string) ($row['title'] ?? $slug)),
        'post_name'    => $slug,
        'post_excerpt' => wp_strip_all_tags((string) ($row['excerpt'] ?? '')),
        'post_content' => wp_kses_post((string) ($row['content'] ?? '')),
    ];

    if (!empty($row['date'])) {
        $raw_date = sanitize_text_field((string) $row['date']);
        // REST dates are ISO-8601; WP permalinks need MySQL datetime.
        $ts = strtotime($raw_date);
        if ($ts) {
            $postarr['post_date']     = gmdate('Y-m-d H:i:s', $ts + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS));
            $postarr['post_date_gmt'] = gmdate('Y-m-d H:i:s', $ts);
        }
    }

    if ($existing) {
        $postarr['ID'] = (int) $existing[0];
        $post_id       = wp_update_post($postarr, true);
    } else {
        $post_id = wp_insert_post($postarr, true);
    }

    if (is_wp_error($post_id) || !$post_id) {
        return 0;
    }

    $post_id = (int) $post_id;

    if (!empty($row['source_id'])) {
        update_post_meta($post_id, '_nasim_source_post_id', (int) $row['source_id']);
    }

    $term_ids = [];
    foreach ((array) ($row['categories'] ?? []) as $cat) {
        $cslug = sanitize_title((string) ($cat['slug'] ?? ''));
        $cname = sanitize_text_field((string) ($cat['name'] ?? $cslug));
        $tid   = nasim_niloo_ensure_category($cslug, $cname);
        if ($tid) {
            $term_ids[] = $tid;
        }
    }
    if ($term_ids) {
        wp_set_post_terms($post_id, $term_ids, 'category', false);
    }

    if (!has_post_thumbnail($post_id) && !empty($row['image']) && function_exists('nasim_niloo_sideload_image')) {
        $att = nasim_niloo_sideload_image((string) $row['image'], $post_id);
        if ($att) {
            set_post_thumbnail($post_id, $att);
        } else {
            update_post_meta($post_id, '_nasim_remote_thumbnail', esc_url_raw((string) $row['image']));
        }
    }

    return $post_id;
}

/**
 * @return array{created:int,updated:int,total:int}
 */
function nasim_niloo_install_posts_seed(bool $force = false): array
{
    if (!$force && get_option('nasim_niloo_posts_seed_version') === NASIM_NILOO_POSTS_SEED_VERSION) {
        return ['created' => 0, 'updated' => 0, 'total' => 0];
    }

    // Baseline categories used by homepage radio + blog (slugs match live site).
    nasim_niloo_ensure_category('radionil', 'رادیونیل');
    nasim_niloo_ensure_category('آموزش', 'آموزش');
    nasim_niloo_ensure_category('wordpress-learning', 'آموزش وردپرس');
    nasim_niloo_ensure_category('مقالات', 'مقالات');
    nasim_niloo_ensure_category('سفارش-سایت', 'سفارش سایت');

    $created = 0;
    $updated = 0;
    $rows    = nasim_niloo_posts_seed_data();

    foreach ($rows as $row) {
        $slug = sanitize_title((string) ($row['slug'] ?? ''));
        $had  = $slug && get_page_by_path($slug, OBJECT, 'post');
        $id   = nasim_niloo_upsert_post($row);
        if (!$id) {
            continue;
        }
        if ($had) {
            $updated++;
        } else {
            $created++;
        }
    }

    // Drop default Hello world if still the only English sample.
    $hello = get_page_by_path('hello-world', OBJECT, 'post');
    if ($hello instanceof WP_Post && 'Hello world!' === $hello->post_title) {
        wp_trash_post($hello->ID);
    }

    update_option('nasim_niloo_posts_seed_version', NASIM_NILOO_POSTS_SEED_VERSION, false);

    return [
        'created' => $created,
        'updated' => $updated,
        'total'   => count($rows),
    ];
}

function nasim_niloo_maybe_seed_posts(): void
{
    if (function_exists('nasim_store_enabled') && nasim_store_enabled()) {
        return;
    }
    if (get_option('nasim_niloo_posts_seed_version') === NASIM_NILOO_POSTS_SEED_VERSION) {
        return;
    }
    nasim_niloo_install_posts_seed(false);
}
add_action('after_switch_theme', static function () {
    nasim_niloo_install_posts_seed(true);
}, 35);
add_action('init', 'nasim_niloo_maybe_seed_posts', 45);

function nasim_niloo_posts_seed_admin_menu(): void
{
    add_submenu_page(
        'edit.php',
        'سید مقالات نیلو',
        'سید مقالات نیلو',
        'manage_options',
        'nasim-niloo-posts-seed',
        'nasim_niloo_render_posts_seed_page'
    );
}
add_action('admin_menu', 'nasim_niloo_posts_seed_admin_menu');

function nasim_niloo_render_posts_seed_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $result = null;
    if (isset($_POST['nasim_niloo_posts_seed']) && check_admin_referer('nasim_niloo_posts_seed')) {
        delete_option('nasim_niloo_posts_seed_version');
        $result = nasim_niloo_install_posts_seed(true);
    }
    $count = (int) wp_count_posts('post')->publish;
    $data  = nasim_niloo_posts_seed_data();
    ?>
    <div class="wrap">
      <h1>سید مقالات نیلوفر وفایی</h1>
      <?php if ($result) : ?>
      <div class="notice notice-success"><p><?php echo esc_html(sprintf('%d جدید، %d به‌روز از %d ردیف.', $result['created'], $result['updated'], $result['total'])); ?></p></div>
      <?php endif; ?>
      <p>فایل: <code>data/niloo-posts.json</code> — <?php echo esc_html((string) count($data)); ?> مطلب</p>
      <p>مقالات منتشرشده: <strong><?php echo esc_html((string) $count); ?></strong></p>
      <form method="post"><?php wp_nonce_field('nasim_niloo_posts_seed'); ?>
        <p class="submit"><button class="button button-primary" name="nasim_niloo_posts_seed" value="1">اجرای مجدد سید مقالات</button></p>
      </form>
    </div>
    <?php
}
