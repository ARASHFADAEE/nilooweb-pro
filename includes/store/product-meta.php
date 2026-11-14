<?php

if(!defined('ABSPATH')) exit;



/**
 * Meta box for product package details and ZIP upload
 */
function nasim_product_meta_boxes() {

    add_meta_box(
        'nasim_product_details',
        __('جزئیات محصول دیجیتال', 'nasim-fitness'),
        'nasim_render_product_meta_box',
        'nasim_product',
        'normal',
        'high'
    );

    add_meta_box(
        'nasim_product_card',
        __('کارت فروشگاه: قیمت و آمار', 'nasim-fitness'),
        'nasim_render_product_card_meta_box',
        'nasim_product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'nasim_product_meta_boxes');


function nasim_render_product_card_meta_box($post) {

    $meta = nasim_get_product_meta($post->ID);
    ?>
    <div class="nasim-product-meta">
      <div class="row">
        <label for="nasim_product_price"><?php esc_html_e('قیمت (تومان)', 'nasim-fitness'); ?></label>
        <div>
          <input type="number" min="0" step="1000" dir="ltr" name="nasim_product_price" id="nasim_product_price" value="<?php echo esc_attr($meta['price'] ?: ''); ?>" class="regular-text">
          <p class="hint"><?php esc_html_e('خالی یا صفر = روی کارت «با اشتراک» نمایش داده می‌شود.', 'nasim-fitness'); ?></p>
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_sale_price"><?php esc_html_e('قیمت با تخفیف (تومان)', 'nasim-fitness'); ?></label>
        <div>
          <input type="number" min="0" step="1000" dir="ltr" name="nasim_product_sale_price" id="nasim_product_sale_price" value="<?php echo esc_attr($meta['sale_price'] ?: ''); ?>" class="regular-text">
          <p class="hint"><?php esc_html_e('اگر کمتر از قیمت باشد، قیمت قبلی خط می‌خورد و درصد تخفیف نمایش داده می‌شود.', 'nasim-fitness'); ?></p>
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_promo_label"><?php esc_html_e('برچسب تبلیغاتی', 'nasim-fitness'); ?></label>
        <div>
          <input type="text" name="nasim_product_promo_label" id="nasim_product_promo_label" value="<?php echo esc_attr($meta['promo_label']); ?>" maxlength="40" placeholder="<?php esc_attr_e('مثلاً: تخفیف ویژه هفته', 'nasim-fitness'); ?>">
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_sales_base"><?php esc_html_e('فروش قبلی', 'nasim-fitness'); ?></label>
        <div>
          <input type="number" min="0" dir="ltr" name="nasim_product_sales_base" id="nasim_product_sales_base" value="<?php echo esc_attr($meta['sales_base'] ?: ''); ?>" class="small-text">
          <p class="hint"><?php printf(esc_html__('به دانلودهای این سایت (%s کاربر) اضافه می‌شود؛ مثلاً فروش در مارکت‌های دیگر.', 'nasim-fitness'), esc_html(number_format_i18n($meta['downloads']))); ?></p>
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_satisfaction"><?php esc_html_e('رضایت (٪)', 'nasim-fitness'); ?></label>
        <div>
          <input type="number" min="0" max="100" dir="ltr" name="nasim_product_satisfaction" id="nasim_product_satisfaction" value="<?php echo esc_attr($meta['satisfaction']); ?>" class="small-text">
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_support_grade"><?php esc_html_e('نمره پشتیبانی', 'nasim-fitness'); ?></label>
        <div>
          <select name="nasim_product_support_grade" id="nasim_product_support_grade">
            <option value=""><?php esc_html_e('— نمایش داده نشود —', 'nasim-fitness'); ?></option>
            <?php foreach (['A+', 'A', 'B+', 'B', 'C'] as $grade) : ?>
            <option value="<?php echo esc_attr($grade); ?>" <?php selected($meta['support'], $grade); ?>><?php echo esc_html($grade); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <p class="hint"><?php esc_html_e('زیرعنوان کارت از «چکیده» محصول خوانده می‌شود؛ اگر خالی باشد نام دسته‌بندی نمایش داده می‌شود.', 'nasim-fitness'); ?></p>
    </div>
    <?php
}


function nasim_render_product_meta_box($post) {

    $meta = nasim_get_product_meta($post->ID);
    $file = $meta['file_id'] ? get_post($meta['file_id']) : null;

    wp_nonce_field('nasim_save_product_meta', 'nasim_product_meta_nonce');
    ?>
    <style>
      .nasim-product-meta{display:grid;gap:14px;max-width:720px}
      .nasim-product-meta .row{display:grid;grid-template-columns:160px 1fr;gap:12px;align-items:center}
      .nasim-product-meta label{font-weight:600}
      .nasim-product-meta input[type=text],.nasim-product-meta select,.nasim-product-meta textarea{width:100%}
      .nasim-product-meta .hint{color:#646970;font-size:12px;margin:4px 0 0}
      .nasim-product-file{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
      .nasim-product-file code{background:#f0f0f1;padding:4px 8px;border-radius:4px}
    </style>
    <div class="nasim-product-meta">
      <div class="row">
        <label for="nasim_product_type"><?php esc_html_e('نوع', 'nasim-fitness'); ?></label>
        <div>
          <select name="nasim_product_type" id="nasim_product_type">
            <option value="plugin" <?php selected($meta['type'], 'plugin'); ?>><?php esc_html_e('افزونه', 'nasim-fitness'); ?></option>
            <option value="theme" <?php selected($meta['type'], 'theme'); ?>><?php esc_html_e('قالب', 'nasim-fitness'); ?></option>
          </select>
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_slug"><?php esc_html_e('اسلاگ بسته', 'nasim-fitness'); ?></label>
        <div>
          <input type="text" dir="ltr" name="nasim_product_slug" id="nasim_product_slug" value="<?php echo esc_attr($meta['slug']); ?>" placeholder="my-plugin">
          <p class="hint"><?php esc_html_e('همان اسلاگ پوشه افزونه/قالب؛ برای API آپدیت استفاده می‌شود.', 'nasim-fitness'); ?></p>
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_version"><?php esc_html_e('نسخه', 'nasim-fitness'); ?></label>
        <div>
          <input type="text" dir="ltr" name="nasim_product_version" id="nasim_product_version" value="<?php echo esc_attr($meta['version']); ?>" placeholder="1.0.0">
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_requires"><?php esc_html_e('حداقل وردپرس', 'nasim-fitness'); ?></label>
        <div>
          <input type="text" dir="ltr" name="nasim_product_requires" id="nasim_product_requires" value="<?php echo esc_attr($meta['requires']); ?>" placeholder="6.0">
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_tested"><?php esc_html_e('تست‌شده تا', 'nasim-fitness'); ?></label>
        <div>
          <input type="text" dir="ltr" name="nasim_product_tested" id="nasim_product_tested" value="<?php echo esc_attr($meta['tested']); ?>" placeholder="6.7">
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_homepage"><?php esc_html_e('صفحه محصول', 'nasim-fitness'); ?></label>
        <div>
          <input type="url" dir="ltr" name="nasim_product_homepage" id="nasim_product_homepage" value="<?php echo esc_attr($meta['homepage']); ?>">
        </div>
      </div>
      <div class="row">
        <label for="nasim_product_demo_url"><?php esc_html_e('لینک پیش‌نمایش (دمو)', 'nasim-fitness'); ?></label>
        <div>
          <input type="url" dir="ltr" name="nasim_product_demo_url" id="nasim_product_demo_url" value="<?php echo esc_attr($meta['demo_url']); ?>" placeholder="https://demo.example.com/">
          <p class="hint"><?php esc_html_e('آدرس سایت دمو که افزونه/قالب روی آن نصب است. خالی بماند، دکمه پیش‌نمایش نمایش داده نمی‌شود.', 'nasim-fitness'); ?></p>
        </div>
      </div>
      <div class="row">
        <label><?php esc_html_e('فایل ZIP', 'nasim-fitness'); ?></label>
        <div>
          <div class="nasim-product-file">
            <input type="hidden" name="nasim_product_file" id="nasim_product_file" value="<?php echo esc_attr($meta['file_id']); ?>">
            <code id="nasim_product_file_label"><?php echo $file ? esc_html($file->post_title) : '—'; ?></code>
            <button type="button" class="button" id="nasim_product_file_pick"><?php esc_html_e('انتخاب فایل', 'nasim-fitness'); ?></button>
            <button type="button" class="button" id="nasim_product_file_clear"><?php esc_html_e('حذف', 'nasim-fitness'); ?></button>
          </div>
          <p class="hint"><?php esc_html_e('فقط فایل ZIP. لینک مستقیم عمومی نشود؛ دانلود از پنل کاربر انجام می‌شود.', 'nasim-fitness'); ?></p>
        </div>
      </div>
      <div class="row" style="align-items:start">
        <label for="nasim_product_changelog"><?php esc_html_e('تغییرات نسخه', 'nasim-fitness'); ?></label>
        <div>
          <textarea name="nasim_product_changelog" id="nasim_product_changelog" rows="6"><?php echo esc_textarea($meta['changelog']); ?></textarea>
        </div>
      </div>
    </div>
    <script>
    (function ($) {
      var frame;
      $('#nasim_product_file_pick').on('click', function (e) {
        e.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({
          title: 'انتخاب فایل ZIP',
          button: { text: 'انتخاب' },
          multiple: false,
          library: { type: 'application/zip,application/x-zip-compressed' }
        });
        frame.on('select', function () {
          var file = frame.state().get('selection').first().toJSON();
          $('#nasim_product_file').val(file.id);
          $('#nasim_product_file_label').text(file.filename || file.title);
        });
        frame.open();
      });
      $('#nasim_product_file_clear').on('click', function (e) {
        e.preventDefault();
        $('#nasim_product_file').val('');
        $('#nasim_product_file_label').text('—');
      });
    })(jQuery);
    </script>
    <?php
}


function nasim_enqueue_product_admin_assets($hook) {

    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || 'nasim_product' !== $screen->post_type) {
        return;
    }

    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'nasim_enqueue_product_admin_assets');


function nasim_save_product_meta($post_id) {

    if (!isset($_POST['nasim_product_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nasim_product_meta_nonce'])), 'nasim_save_product_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $type = sanitize_key(wp_unslash($_POST['nasim_product_type'] ?? 'plugin'));
    $type = in_array($type, ['plugin', 'theme'], true) ? $type : 'plugin';

    update_post_meta($post_id, '_nasim_product_type', $type);
    update_post_meta($post_id, '_nasim_product_slug', sanitize_title(wp_unslash($_POST['nasim_product_slug'] ?? '')));
    update_post_meta($post_id, '_nasim_product_version', sanitize_text_field(wp_unslash($_POST['nasim_product_version'] ?? '')));
    update_post_meta($post_id, '_nasim_product_requires', sanitize_text_field(wp_unslash($_POST['nasim_product_requires'] ?? '')));
    update_post_meta($post_id, '_nasim_product_tested', sanitize_text_field(wp_unslash($_POST['nasim_product_tested'] ?? '')));
    update_post_meta($post_id, '_nasim_product_homepage', esc_url_raw(wp_unslash($_POST['nasim_product_homepage'] ?? '')));
    update_post_meta($post_id, '_nasim_product_demo_url', esc_url_raw(wp_unslash($_POST['nasim_product_demo_url'] ?? '')));
    update_post_meta($post_id, '_nasim_product_file', absint($_POST['nasim_product_file'] ?? 0));
    update_post_meta($post_id, '_nasim_product_changelog', sanitize_textarea_field(wp_unslash($_POST['nasim_product_changelog'] ?? '')));

    $satisfaction = trim((string) wp_unslash($_POST['nasim_product_satisfaction'] ?? ''));
    $grade        = sanitize_text_field(wp_unslash($_POST['nasim_product_support_grade'] ?? ''));

    update_post_meta($post_id, '_nasim_product_price', absint($_POST['nasim_product_price'] ?? 0));
    update_post_meta($post_id, '_nasim_product_sale_price', absint($_POST['nasim_product_sale_price'] ?? 0));
    update_post_meta($post_id, '_nasim_product_promo_label', mb_substr(sanitize_text_field(wp_unslash($_POST['nasim_product_promo_label'] ?? '')), 0, 40));
    update_post_meta($post_id, '_nasim_product_sales_base', absint($_POST['nasim_product_sales_base'] ?? 0));
    update_post_meta($post_id, '_nasim_product_satisfaction', '' === $satisfaction ? '' : (string) min(100, absint($satisfaction)));
    update_post_meta($post_id, '_nasim_product_support_grade', in_array($grade, ['A+', 'A', 'B+', 'B', 'C'], true) ? $grade : '');

    nasim_refresh_product_sort_meta($post_id);
}
add_action('save_post_nasim_product', 'nasim_save_product_meta');


/**
 * Admin list columns: type and version
 */
function nasim_product_columns($columns) {

    $columns['nasim_type']    = __('نوع', 'nasim-fitness');
    $columns['nasim_version'] = __('نسخه', 'nasim-fitness');

    return $columns;
}
add_filter('manage_nasim_product_posts_columns', 'nasim_product_columns');


function nasim_product_column_content($column, $post_id) {

    $meta = nasim_get_product_meta($post_id);

    if ('nasim_type' === $column) {
        echo esc_html(nasim_product_type_label($meta['type']));
    }

    if ('nasim_version' === $column) {
        echo $meta['version'] ? esc_html($meta['version']) : '—';
    }
}
add_action('manage_nasim_product_posts_custom_column', 'nasim_product_column_content', 10, 2);
