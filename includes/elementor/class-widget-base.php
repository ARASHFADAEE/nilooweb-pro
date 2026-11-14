<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Shared helpers for Niloo Elementor widgets.
 */
abstract class Nasim_Elementor_Widget_Base extends Widget_Base
{
    /** @var array<string, mixed> */
    private static $mod_stack = [];

    public function get_categories(): array
    {
        return ['niloo-web'];
    }

    public function get_style_depends(): array
    {
        return ['nasim-main', 'nasim-tailwind'];
    }

    /**
     * Temporary theme_mod overrides while rendering a template part.
     *
     * @param array<string, mixed> $mods
     */
    protected function with_theme_mods(array $mods, callable $callback): void
    {
        $filters = [];

        foreach ($mods as $key => $value) {
            if (null === $value || '' === $value) {
                continue;
            }
            $filter = static function () use ($value) {
                return $value;
            };
            $filters[$key] = $filter;
            add_filter("theme_mod_{$key}", $filter, 50);
        }

        try {
            $callback();
        } finally {
            foreach ($filters as $key => $filter) {
                remove_filter("theme_mod_{$key}", $filter, 50);
            }
        }
    }

    /**
     * Render a front-page template part with optional mod overrides.
     *
     * @param array<string, mixed> $mods
     */
    protected function render_template(string $slug, array $mods = []): void
    {
        $this->with_theme_mods($mods, static function () use ($slug) {
            get_template_part('template-parts/front-page/' . $slug);
        });
    }

    protected function add_heading_controls(string $prefix, array $defaults = []): void
    {
        $this->add_control($prefix . '_eyebrow', [
            'label'   => __('تیتر کوچک', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => $defaults['eyebrow'] ?? '',
        ]);
        $this->add_control($prefix . '_title', [
            'label'   => __('عنوان', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => $defaults['title'] ?? '',
        ]);
        $this->add_control($prefix . '_title_highlight', [
            'label'   => __('عنوان رنگی', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => $defaults['highlight'] ?? '',
        ]);
    }

    /**
     * @return array<int, array{id:int,title:string}>
     */
    protected function course_options(): array
    {
        $posts = get_posts([
            'post_type'      => 'nasim_course',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        ]);

        $out = [];
        foreach ($posts as $post) {
            $out[(string) $post->ID] = $post->post_title;
        }

        return $out;
    }
}
