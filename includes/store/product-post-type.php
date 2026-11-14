<?php

if(!defined('ABSPATH')) exit;



/**
 * Digital products: plugins and themes sold via subscription
 */
function nasim_register_product_post_type() {

    register_post_type('nasim_product', [
        'labels' => [
            'name'               => __('محصولات', 'nasim-fitness'),
            'singular_name'      => __('محصول', 'nasim-fitness'),
            'add_new'            => __('افزودن محصول', 'nasim-fitness'),
            'add_new_item'       => __('افزودن محصول جدید', 'nasim-fitness'),
            'edit_item'          => __('ویرایش محصول', 'nasim-fitness'),
            'all_items'          => __('همه محصولات', 'nasim-fitness'),
            'view_item'          => __('مشاهده محصول', 'nasim-fitness'),
            'search_items'       => __('جستجوی محصول', 'nasim-fitness'),
            'not_found'          => __('محصولی پیدا نشد.', 'nasim-fitness'),
            'featured_image'     => __('تصویر محصول', 'nasim-fitness'),
            'set_featured_image' => __('انتخاب تصویر محصول', 'nasim-fitness'),
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
            'name'          => __('دسته‌بندی محصولات', 'nasim-fitness'),
            'singular_name' => __('دسته‌بندی', 'nasim-fitness'),
            'menu_name'     => __('دسته‌بندی‌ها', 'nasim-fitness'),
            'all_items'     => __('همه دسته‌ها', 'nasim-fitness'),
            'add_new_item'  => __('افزودن دسته جدید', 'nasim-fitness'),
            'edit_item'     => __('ویرایش دسته', 'nasim-fitness'),
            'search_items'  => __('جستجوی دسته', 'nasim-fitness'),
            'parent_item'   => __('دسته مادر', 'nasim-fitness'),
            'not_found'     => __('دسته‌ای پیدا نشد.', 'nasim-fitness'),
        ],
        'public'            => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'product-category', 'with_front' => false, 'hierarchical' => true],
    ]);
}
add_action('init', 'nasim_register_product_taxonomy');
