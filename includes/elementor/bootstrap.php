<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Niloo Elementor category + widgets.
 */
final class Nasim_Elementor_Bootstrap
{
    public static function init(): void
    {
        add_action('elementor/elements/categories_registered', [__CLASS__, 'register_category']);
        add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
        add_action('elementor/editor/after_enqueue_styles', [__CLASS__, 'editor_styles']);
        add_action('elementor/frontend/after_enqueue_styles', [__CLASS__, 'frontend_styles']);
    }

    /**
     * @param \Elementor\Elements_Manager $manager
     */
    public static function register_category($manager): void
    {
        $manager->add_category('niloo-web', [
            'title' => __('آکادمی نیلوفر وفایی', 'niloo-web'),
            'icon'  => 'fa fa-plug',
        ]);
    }

    /**
     * @param \Elementor\Widgets_Manager $manager
     */
    public static function register_widgets($manager): void
    {
        require_once THEME_DIR . '/includes/elementor/class-widget-base.php';

        $widgets = [
            'class-widget-hero.php'         => 'Nasim_Elementor_Widget_Hero',
            'class-widget-benefits.php'     => 'Nasim_Elementor_Widget_Benefits',
            'class-widget-courses.php'      => 'Nasim_Elementor_Widget_Courses',
            'class-widget-course-card.php'  => 'Nasim_Elementor_Widget_Course_Card',
            'class-widget-about.php'        => 'Nasim_Elementor_Widget_About',
            'class-widget-testimonials.php' => 'Nasim_Elementor_Widget_Testimonials',
            'class-widget-radio-nil.php'    => 'Nasim_Elementor_Widget_Radio_Nil',
            'class-widget-articles.php'     => 'Nasim_Elementor_Widget_Articles',
            'class-widget-newsletter.php'   => 'Nasim_Elementor_Widget_Newsletter',
            'class-widget-bundles.php'      => 'Nasim_Elementor_Widget_Bundles',
            'class-widget-learning-path.php' => 'Nasim_Elementor_Widget_Learning_Path',
            'class-widget-free-content.php'  => 'Nasim_Elementor_Widget_Free_Content',
        ];

        foreach ($widgets as $file => $class) {
            $path = THEME_DIR . '/includes/elementor/widgets/' . $file;
            if (!is_readable($path)) {
                continue;
            }
            require_once $path;
            if (!class_exists($class)) {
                continue;
            }
            $widget = new $class();
            // Elementor 3.5+ register(); older register_widget_type().
            if (method_exists($manager, 'register')) {
                $manager->register($widget);
            } else {
                $manager->register_widget_type($widget);
            }
        }
    }

    public static function editor_styles(): void
    {
        wp_enqueue_style(
            'nasim-theme',
            THEME_URL . '/assets/css/theme.css',
            [],
            function_exists('nasim_asset_version') ? nasim_asset_version('/assets/css/theme.css') : null
        );
    }

    public static function frontend_styles(): void
    {
        if (wp_style_is('nasim-theme', 'registered')) {
            wp_enqueue_style('nasim-theme');
        }
    }
}

/**
 * Boot when Elementor is ready.
 */
function nasim_elementor_widgets_boot(): void
{
    if (!did_action('elementor/loaded') && !class_exists('\Elementor\Plugin')) {
        return;
    }
    Nasim_Elementor_Bootstrap::init();
}
add_action('elementor/loaded', 'nasim_elementor_widgets_boot');

// Fallback if theme loads after Elementor already fired `elementor/loaded`.
if (did_action('elementor/loaded')) {
    nasim_elementor_widgets_boot();
}
