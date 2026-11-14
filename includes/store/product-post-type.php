<?php

if(!defined('ABSPATH')) exit;



/**
 * Digital products: plugins and themes sold via subscription
 */
function nasim_register_product_post_type() {

    register_post_type('nasim_product', [
        'labels' => [
            'name'               => __('محصولات', 'niloo-web'),
            'singular_name'      => __('محصول', 'niloo-web'),
            'add_new'            => __('افزودن محصول', 'niloo-web'),
            'add_new_item'       => __('افزودن محصول جدید', 'niloo-web'),
            'edit_item'          => __('ویرایش محصول', 'niloo-web'),
            'all_items'          => __('همه محصولات', 'niloo-web'),
            'view_item'          => __('مشاهده محصول', 'niloo-web'),
            'search_items'       => __('جستجوی محصول', 'niloo-web'),
            'not_found'          => __('محصولی پیدا نشد.', 'niloo-web'),
            'featured_image'     => __('تصویر محصول', 'niloo-web'),
            'set_featured_image' => __('انتخاب تصویر محصول', 'niloo-web'),
        ],
        'public'        => true,
        'has_archive'   => 'products',
        'rewrite'       => ['slug' => 'products', 'with_front' => false],
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-download',
        'menu_position' => 6,
        'supports'      => ['title', 'editor', 'excerpt', 'thumbnail'],
    ]);
}
add_action('init', 'nasim_register_product_post_type');


function nasim_register_product_taxonomy() {

    register_taxonomy('nasim_product_cat', 'nasim_product', [
        'labels' => [
            'name'          => __('دسته‌بندی محصولات', 'niloo-web'),
            'singular_name' => __('دسته‌بندی', 'niloo-web'),
            'menu_name'     => __('دسته‌بندی‌ها', 'niloo-web'),
            'all_items'     => __('همه دسته‌ها', 'niloo-web'),
            'add_new_item'  => __('افزودن دسته جدید', 'niloo-web'),
            'edit_item'     => __('ویرایش دسته', 'niloo-web'),
            'search_items'  => __('جستجوی دسته', 'niloo-web'),
            'parent_item'   => __('دسته مادر', 'niloo-web'),
            'not_found'     => __('دسته‌ای پیدا نشد.', 'niloo-web'),
        ],
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'product-category', 'with_front' => false, 'hierarchical' => true],
    ]);
}
add_action('init', 'nasim_register_product_taxonomy');
