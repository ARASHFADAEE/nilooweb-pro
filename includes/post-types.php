<?php

if(!defined('ABSPATH')) exit;



/**
 * Register testimonials post type
 *
 * Title = person name, content = quote, meta = role & rating.
 */
function nasim_register_testimonial_post_type() {

    register_post_type('nasim_testimonial', [
        'labels' => [
            'name'          => __('نظرات همراهان', 'nasim-fitness'),
            'singular_name' => __('نظر همراه', 'nasim-fitness'),
            'add_new'       => __('افزودن نظر', 'nasim-fitness'),
            'add_new_item'  => __('افزودن نظر جدید', 'nasim-fitness'),
            'edit_item'     => __('ویرایش نظر', 'nasim-fitness'),
            'all_items'     => __('همه نظرات', 'nasim-fitness'),
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-format-quote',
        'supports'     => ['title', 'editor', 'page-attributes'],
    ]);
}
add_action('init', 'nasim_register_testimonial_post_type');


/**
 * Testimonial details meta box
 */
function nasim_add_testimonial_meta_box() {

    add_meta_box('nasim_testimonial_details', __('جزئیات نظر', 'nasim-fitness'), 'nasim_render_testimonial_meta_box', 'nasim_testimonial', 'side');
}
add_action('add_meta_boxes', 'nasim_add_testimonial_meta_box');


function nasim_render_testimonial_meta_box($post) {

    $role   = get_post_meta($post->ID, '_nasim_testimonial_role', true);
    $rating = (int) get_post_meta($post->ID, '_nasim_testimonial_rating', true) ?: 5;

    wp_nonce_field('nasim_save_testimonial', 'nasim_testimonial_nonce');
    ?>
    <p>
        <label for="nasim_testimonial_role"><?php esc_html_e('عنوان (مثلاً: همراه برنامه تمرینی)', 'nasim-fitness'); ?></label>
        <input type="text" class="widefat" id="nasim_testimonial_role" name="nasim_testimonial_role" value="<?php echo esc_attr($role); ?>">
    </p>
    <p>
        <label for="nasim_testimonial_rating"><?php esc_html_e('امتیاز', 'nasim-fitness'); ?></label>
        <select class="widefat" id="nasim_testimonial_rating" name="nasim_testimonial_rating">
            <?php for ($i = 5; $i >= 1; $i--) : ?>
                <option value="<?php echo esc_attr($i); ?>" <?php selected($rating, $i); ?>><?php echo esc_html(str_repeat('★', $i)); ?></option>
            <?php endfor; ?>
        </select>
    </p>
    <?php
}


function nasim_save_testimonial_meta($post_id) {

    if (!isset($_POST['nasim_testimonial_nonce']) || !wp_verify_nonce(sanitize_key($_POST['nasim_testimonial_nonce']), 'nasim_save_testimonial')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['nasim_testimonial_role'])) {
        update_post_meta($post_id, '_nasim_testimonial_role', sanitize_text_field(wp_unslash($_POST['nasim_testimonial_role'])));
    }

    if (isset($_POST['nasim_testimonial_rating'])) {
        $rating = min(5, max(1, absint($_POST['nasim_testimonial_rating'])));
        update_post_meta($post_id, '_nasim_testimonial_rating', $rating);
    }
}
add_action('save_post_nasim_testimonial', 'nasim_save_testimonial_meta');
