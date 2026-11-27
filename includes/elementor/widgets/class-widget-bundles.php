<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class Nasim_Elementor_Widget_Bundles extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_bundles';
    }

    public function get_title(): string
    {
        return __('نیلو · پکیج‌ها', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-products';
    }

    public function get_keywords(): array
    {
        return ['bundle', 'package', 'پکیج', 'niloo'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'niloo-web')]);

        $this->add_heading_controls('bundles_home', [
            'eyebrow'   => (string) nasim_mod('bundles_home_eyebrow'),
            'title'     => (string) nasim_mod('bundles_home_title'),
            'highlight' => (string) nasim_mod('bundles_home_title_highlight'),
        ]);

        $this->add_control('bundles_home_description', [
            'label'   => __('توضیح', 'niloo-web'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => (string) nasim_mod('bundles_home_description'),
        ]);

        $this->add_control('limit', [
            'label'   => __('تعداد پکیج', 'niloo-web'),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 12,
            'default' => 3,
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();

        $this->with_theme_mods([
            'bundles_home_eyebrow'         => $s['bundles_home_eyebrow'] ?? '',
            'bundles_home_title'           => $s['bundles_home_title'] ?? '',
            'bundles_home_title_highlight' => $s['bundles_home_title_highlight'] ?? '',
            'bundles_home_description'     => $s['bundles_home_description'] ?? '',
        ], static function () use ($s) {
            get_template_part('template-parts/front-page/bundles', null, ['limit' => max(1, (int) ($s['limit'] ?? 3))]);
        });
    }
}
