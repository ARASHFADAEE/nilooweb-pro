<?php

if(!defined('ABSPATH')) exit;



/**
 * Course meta boxes: details and curriculum
 */
function nasim_add_course_meta_boxes() {

    add_meta_box('nasim_course_curriculum', __('سرفصل‌های دوره', 'niloo-web'), 'nasim_render_curriculum_meta_box', 'nasim_course', 'normal', 'high');
    add_meta_box('nasim_course_details', __('جزئیات دوره', 'niloo-web'), 'nasim_render_course_details_meta_box', 'nasim_course', 'side');
}
add_action('add_meta_boxes', 'nasim_add_course_meta_boxes');


function nasim_render_course_details_meta_box($post) {

    $level    = get_post_meta($post->ID, '_nasim_course_level', true);
    $outcomes = get_post_meta($post->ID, '_nasim_course_outcomes', true);
    ?>
    <p>
        <label for="nasim_course_level"><strong><?php esc_html_e('سطح دوره', 'niloo-web'); ?></strong></label>
        <input type="text" class="widefat" id="nasim_course_level" name="nasim_course_level" value="<?php echo esc_attr($level); ?>" placeholder="مقدماتی" list="nasim_course_levels">
        <datalist id="nasim_course_levels"><option value="مقدماتی"><option value="متوسط"><option value="پیشرفته"><option value="همه سطوح"></datalist>
    </p>
    <p>
        <label for="nasim_course_outcomes"><strong><?php esc_html_e('در این دوره یاد می‌گیری (هر مورد در یک خط)', 'niloo-web'); ?></strong></label>
        <textarea class="widefat" id="nasim_course_outcomes" name="nasim_course_outcomes" rows="6"><?php echo esc_textarea($outcomes); ?></textarea>
    </p>
    <?php
    $delivery = nasim_course_delivery($post->ID);
    $access   = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($post->ID) : 'subscription';
    $spot     = get_post_meta($post->ID, '_nasim_spotplayer_course', true);
    $wc_id    = (int) get_post_meta($post->ID, '_nasim_wc_product_id', true);
    ?>
    <p>
        <label for="nasim_course_access_mode"><strong><?php esc_html_e('نحوه دسترسی', 'niloo-web'); ?></strong></label>
        <select id="nasim_course_access_mode" name="nasim_course_access_mode" class="widefat">
            <option value="free" <?php selected($access, 'free'); ?>><?php esc_html_e('رایگان (برای همه)', 'niloo-web'); ?></option>
            <option value="subscription" <?php selected($access, 'subscription'); ?>><?php esc_html_e('رایگان با اشتراک', 'niloo-web'); ?></option>
            <option value="purchase" <?php selected($access, 'purchase'); ?>><?php esc_html_e('خرید جداگانه این دوره', 'niloo-web'); ?></option>
        </select>
        <span class="description"><?php esc_html_e('اشتراک فقط دوره‌های «رایگان با اشتراک» را باز می‌کند؛ دوره‌های خرید جداگانه باید جدا پرداخت شوند.', 'niloo-web'); ?></span>
    </p>
    <p>
        <label for="nasim_course_delivery"><strong><?php esc_html_e('نوع تحویل', 'niloo-web'); ?></strong></label>
        <select id="nasim_course_delivery" name="nasim_course_delivery" class="widefat">
            <option value="lms" <?php selected($delivery, 'lms'); ?>><?php esc_html_e('پلیر داخلی قالب (LMS)', 'niloo-web'); ?></option>
            <option value="spot" <?php selected($delivery, 'spot'); ?>><?php esc_html_e('اسپات پلیر (ضدکپی)', 'niloo-web'); ?></option>
            <option value="hybrid" <?php selected($delivery, 'hybrid'); ?>><?php esc_html_e('هر دو', 'niloo-web'); ?></option>
        </select>
    </p>
    <p>
        <label for="nasim_spotplayer_course"><strong><?php esc_html_e('شناسه دوره اسپات (با , جدا)', 'niloo-web'); ?></strong></label>
        <textarea class="widefat ltr" id="nasim_spotplayer_course" name="nasim_spotplayer_course" rows="2" placeholder="24char,24char"><?php echo esc_textarea($spot); ?></textarea>
    </p>
    <?php if ($wc_id) : ?>
    <p class="description"><?php printf(esc_html__('متصل به محصول ووکامرس #%d', 'niloo-web'), $wc_id); ?></p>
    <?php endif; ?>
    <?php
}


function nasim_render_curriculum_meta_box($post) {

    wp_nonce_field('nasim_save_course', 'nasim_course_nonce');
    ?>
    <div class="nasim-curriculum" id="nasimCurriculum">
        <p class="description"><?php esc_html_e('فصل‌ها و درس‌ها را با کشیدن آیکون ⇅ مرتب کنید. کد Embed ویدیو (iframe) را از سرویس ویدیویی کپی کنید. درس‌های «رایگان» پیش‌نمایش عمومی‌اند؛ بقیه بر اساس «نحوه دسترسی» دوره (اشتراک یا خرید جدا) باز می‌شوند.', 'niloo-web'); ?></p>

        <div class="nasim-sections">
            <?php foreach (nasim_get_curriculum($post->ID) as $section_index => $section) : ?>
                <?php nasim_render_curriculum_section('s' . $section_index, $section); ?>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-primary nasim-add-section"><?php esc_html_e('+ افزودن فصل', 'niloo-web'); ?></button>
    </div>

    <script type="text/html" id="tmpl-nasim-section"><?php nasim_render_curriculum_section('__S__', []); ?></script>
    <script type="text/html" id="tmpl-nasim-lesson"><?php nasim_render_curriculum_lesson('__S__', '__L__', []); ?></script>
    <?php
}


function nasim_render_curriculum_section($section_key, $section) {

    $name = "nasim_curriculum[{$section_key}]";
    ?>
    <div class="nasim-section" data-key="<?php echo esc_attr($section_key); ?>">
        <div class="nasim-section-head">
            <span class="nasim-handle dashicons dashicons-sort" title="جابه‌جایی"></span>
            <input type="text" class="nasim-section-title" name="<?php echo esc_attr($name); ?>[title]" value="<?php echo esc_attr($section['title'] ?? ''); ?>" placeholder="<?php esc_attr_e('عنوان فصل (مثلاً: فصل اول - آشنایی)', 'niloo-web'); ?>">
            <button type="button" class="button-link button-link-delete nasim-remove-section"><?php esc_html_e('حذف فصل', 'niloo-web'); ?></button>
        </div>
        <div class="nasim-lessons">
            <?php foreach ($section['lessons'] ?? [] as $lesson_index => $lesson) : ?>
                <?php nasim_render_curriculum_lesson($section_key, 'l' . $lesson_index, $lesson); ?>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button nasim-add-lesson"><?php esc_html_e('+ افزودن درس', 'niloo-web'); ?></button>
    </div>
    <?php
}


function nasim_render_curriculum_lesson($section_key, $lesson_key, $lesson) {

    $name  = "nasim_curriculum[{$section_key}][lessons][{$lesson_key}]";
    $field = function ($key) use ($lesson) {
        return $lesson[$key] ?? '';
    };
    ?>
    <details class="nasim-lesson" <?php echo empty($lesson) ? 'open' : ''; ?>>
        <summary>
            <span class="nasim-handle dashicons dashicons-sort" title="جابه‌جایی"></span>
            <span class="nasim-lesson-preview"><?php echo esc_html($field('title') ?: __('درس جدید', 'niloo-web')); ?></span>
            <span class="nasim-lesson-badges">
                <?php if ($field('duration')) : ?><span class="nasim-badge"><?php echo esc_html($field('duration')); ?></span><?php endif; ?>
                <?php if (!empty($lesson['is_free'])) : ?><span class="nasim-badge nasim-badge-free"><?php esc_html_e('رایگان', 'niloo-web'); ?></span><?php endif; ?>
                <?php if (empty($lesson['embed']) && !empty($lesson)) : ?><span class="nasim-badge nasim-badge-warning"><?php esc_html_e('بدون ویدیو', 'niloo-web'); ?></span><?php endif; ?>
            </span>
            <button type="button" class="button-link button-link-delete nasim-remove-lesson"><?php esc_html_e('حذف', 'niloo-web'); ?></button>
        </summary>

        <div class="nasim-lesson-body">
            <input type="hidden" name="<?php echo esc_attr($name); ?>[id]" value="<?php echo esc_attr($field('id')); ?>">

            <div class="nasim-grid">
                <label class="nasim-col-2"><?php esc_html_e('عنوان درس', 'niloo-web'); ?>
                    <input type="text" class="nasim-lesson-title" name="<?php echo esc_attr($name); ?>[title]" value="<?php echo esc_attr($field('title')); ?>">
                </label>
                <label><?php esc_html_e('نوع درس', 'niloo-web'); ?>
                    <input type="text" name="<?php echo esc_attr($name); ?>[type]" value="<?php echo esc_attr($field('type')); ?>" placeholder="تمرین عملی">
                </label>
                <label><?php esc_html_e('مدت (دقیقه:ثانیه)', 'niloo-web'); ?>
                    <input type="text" name="<?php echo esc_attr($name); ?>[duration]" value="<?php echo esc_attr($field('duration')); ?>" placeholder="7:30" dir="ltr">
                </label>
            </div>

            <label><?php esc_html_e('توضیح کوتاه درس', 'niloo-web'); ?>
                <textarea name="<?php echo esc_attr($name); ?>[description]" rows="2"><?php echo esc_textarea($field('description')); ?></textarea>
            </label>

            <label><?php esc_html_e('در این درس چه یاد می‌گیری؟ (هر مورد در یک خط)', 'niloo-web'); ?>
                <textarea name="<?php echo esc_attr($name); ?>[overview]" rows="3"><?php echo esc_textarea($field('overview')); ?></textarea>
            </label>

            <label><?php esc_html_e('کد Embed ویدیو (iframe)', 'niloo-web'); ?>
                <textarea name="<?php echo esc_attr($name); ?>[embed]" rows="3" dir="ltr" class="code" placeholder='<iframe src="https://..." allowfullscreen></iframe>'><?php echo esc_textarea($field('embed')); ?></textarea>
            </label>

            <div class="nasim-grid">
                <label class="nasim-col-2"><?php esc_html_e('عنوان فایل همراه', 'niloo-web'); ?>
                    <input type="text" name="<?php echo esc_attr($name); ?>[resource_title]" value="<?php echo esc_attr($field('resource_title')); ?>" placeholder="چک‌لیست شروع تمرین">
                </label>
                <label class="nasim-col-2"><?php esc_html_e('لینک فایل همراه', 'niloo-web'); ?>
                    <input type="url" name="<?php echo esc_attr($name); ?>[resource_url]" value="<?php echo esc_attr($field('resource_url')); ?>" dir="ltr">
                </label>
            </div>

            <label class="nasim-checkbox">
                <input type="checkbox" name="<?php echo esc_attr($name); ?>[is_free]" value="1" <?php checked(!empty($lesson['is_free'])); ?>>
                <?php esc_html_e('درس رایگان (پیش‌نمایش برای همه)', 'niloo-web'); ?>
            </label>
        </div>
    </details>
    <?php
}


/**
 * Sanitize the submitted curriculum; lessons keep a stable id so user progress survives reordering
 */
function nasim_sanitize_curriculum($raw_sections) {

    $curriculum = [];
    $used_ids   = [];

    foreach ((array) $raw_sections as $raw_section) {

        $lessons = [];

        foreach ((array) ($raw_section['lessons'] ?? []) as $raw_lesson) {

            $title = sanitize_text_field($raw_lesson['title'] ?? '');

            if ('' === $title) {
                continue;
            }

            $id = sanitize_key($raw_lesson['id'] ?? '');

            if ('' === $id || in_array($id, $used_ids, true)) {
                $id = substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
            }

            $used_ids[] = $id;

            $lessons[] = [
                'id'             => $id,
                'title'          => $title,
                'type'           => sanitize_text_field($raw_lesson['type'] ?? ''),
                'duration'       => sanitize_text_field($raw_lesson['duration'] ?? ''),
                'description'    => sanitize_textarea_field($raw_lesson['description'] ?? ''),
                'overview'       => sanitize_textarea_field($raw_lesson['overview'] ?? ''),
                'embed'          => nasim_sanitize_embed($raw_lesson['embed'] ?? ''),
                'is_free'        => !empty($raw_lesson['is_free']),
                'resource_title' => sanitize_text_field($raw_lesson['resource_title'] ?? ''),
                'resource_url'   => esc_url_raw($raw_lesson['resource_url'] ?? ''),
            ];
        }

        $section_title = sanitize_text_field($raw_section['title'] ?? '');

        if ('' === $section_title && !$lessons) {
            continue;
        }

        $curriculum[] = [
            'title'   => $section_title,
            'lessons' => $lessons,
        ];
    }

    return $curriculum;
}


function nasim_save_course_meta($post_id) {

    if (!isset($_POST['nasim_course_nonce']) || !wp_verify_nonce(sanitize_key($_POST['nasim_course_nonce']), 'nasim_save_course')) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized field by field in nasim_sanitize_curriculum().
    $raw_curriculum = isset($_POST['nasim_curriculum']) ? wp_unslash($_POST['nasim_curriculum']) : [];

    update_post_meta($post_id, '_nasim_curriculum', nasim_sanitize_curriculum($raw_curriculum));

    if (isset($_POST['nasim_course_level'])) {
        update_post_meta($post_id, '_nasim_course_level', sanitize_text_field(wp_unslash($_POST['nasim_course_level'])));
    }

    if (isset($_POST['nasim_course_outcomes'])) {
        update_post_meta($post_id, '_nasim_course_outcomes', sanitize_textarea_field(wp_unslash($_POST['nasim_course_outcomes'])));
    }

    if (isset($_POST['nasim_course_access_mode'])) {
        $access = sanitize_key(wp_unslash($_POST['nasim_course_access_mode']));
        if (in_array($access, ['free', 'subscription', 'purchase'], true)) {
            update_post_meta($post_id, '_nasim_course_access_mode', $access);
        }
    }

    if (isset($_POST['nasim_course_delivery'])) {
        $mode = sanitize_key(wp_unslash($_POST['nasim_course_delivery']));
        if (in_array($mode, ['lms', 'spot', 'hybrid'], true)) {
            update_post_meta($post_id, '_nasim_course_delivery', $mode);
        }
    }

    if (isset($_POST['nasim_spotplayer_course'])) {
        $raw = sanitize_textarea_field(wp_unslash($_POST['nasim_spotplayer_course']));
        $ids = array_filter(array_map('trim', explode(',', $raw)), function ($id) {
            return (bool) preg_match('/^[0-9a-f]{24}$/i', $id);
        });
        update_post_meta($post_id, '_nasim_spotplayer_course', implode(',', $ids));
    }
}
add_action('save_post_nasim_course', 'nasim_save_course_meta');


/**
 * Curriculum editor assets
 */
function nasim_course_admin_assets($hook) {

    $screen = get_current_screen();

    if (!in_array($hook, ['post.php', 'post-new.php'], true) || !$screen || 'nasim_course' !== $screen->post_type) {
        return;
    }

    wp_enqueue_style('nasim-curriculum', THEME_URL . '/assets/css/admin/curriculum.css', [], nasim_asset_version('/assets/css/admin/curriculum.css'));
    wp_enqueue_script('nasim-curriculum', THEME_URL . '/assets/js/admin/curriculum.js', ['jquery', 'jquery-ui-sortable'], nasim_asset_version('/assets/js/admin/curriculum.js'), true);
}
add_action('admin_enqueue_scripts', 'nasim_course_admin_assets');
