<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elementor compatibility: wide alignment, minimal theme chrome on builder pages.
 */
function nasim_elementor_theme_setup(): void
{
    add_theme_support('align-wide');

    if (did_action('elementor/loaded') || class_exists('\Elementor\Plugin')) {
        add_theme_support('elementor');
    }
}
add_action('after_setup_theme', 'nasim_elementor_theme_setup', 20);

/**
 * @param string[] $classes
 * @return string[]
 */
function nasim_elementor_body_class(array $classes): array
{
    if (is_singular() && nasim_is_built_with_elementor(get_queried_object_id())) {
        $classes[] = 'niloo-elementor-page';
    }

    return $classes;
}
add_filter('body_class', 'nasim_elementor_body_class');

/**
 * Let Elementor control header/footer on full-width canvas templates.
 */
function nasim_elementor_should_render_theme_shell(): bool
{
    if (!is_singular()) {
        return true;
    }

    $post_id = get_queried_object_id();
    $template = (string) get_page_template_slug($post_id);

    if (in_array($template, ['elementor_canvas', 'elementor_header_footer'], true)) {
        return false;
    }

    return true;
}