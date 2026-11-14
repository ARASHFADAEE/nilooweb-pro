<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class Nasim_Elementor_Widget_About extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_about';
    }

    public function get_title(): string
    {
        return __('نیلو · درباره من', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-person';
    }

    public function get_keywords(): array
    {
        return ['about', 'درباره', 'niloo', 'bio'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'niloo-web')]);

        $this->add_heading_controls('about', [
            'eyebrow'   => (string) nasim_mod('about_eyebrow'),
            'title'     => (string) nasim_mod('about_title'),
            'highlight' => (string) nasim_mod('about_title_highlight'),
        ]);

        $this->add_control('about_text', [
            'label'   => __('متن', 'niloo-web'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => (string) nasim_mod('about_text'),
        ]);
        $this->add_control('about_sticker', [
            'label'   => __('برچسب تصویر', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('about_sticker'),
        ]);
        $this->add_control('about_image', [
            'label'   => __('تصویر', 'niloo-web'),
            'type'    => Controls_Manager::MEDIA,
            'default' => ['url' => (string) nasim_mod('about_image')],
        ]);
        $this->add_control('about_button_text', [
            'label'   => __('متن دکمه', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('about_button_text'),
        ]);
        $this->add_control('about_button_url', [
            'label'   => __('لینک دکمه', 'niloo-web'),
            'type'    => Controls_Manager::URL,
            'default' => ['url' => (string) nasim_mod('about_button_url')],
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $this->add_control("about_stat_{$i}_value", [
                'label'   => sprintf(__('آمار %d · مقدار', 'niloo-web'), $i),
                'type'    => Controls_Manager::TEXT,
                'default' => (string) nasim_mod("about_stat_{$i}_value"),
            ]);
            $this->add_control("about_stat_{$i}_label", [
                'label'   => sprintf(__('آمار %d · برچسب', 'niloo-web'), $i),
                'type'    => Controls_Manager::TEXT,
                'default' => (string) nasim_mod("about_stat_{$i}_label"),
            ]);
        }

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $mods = [
            'about_eyebrow'         => $s['about_eyebrow'] ?? '',
            'about_title'           => $s['about_title'] ?? '',
            'about_title_highlight' => $s['about_title_highlight'] ?? '',
            'about_text'            => $s['about_text'] ?? '',
            'about_sticker'         => $s['about_sticker'] ?? '',
            'about_image'           => (string) (($s['about_image']['url'] ?? '') ?: nasim_mod('hero_image')),
            'about_button_text'     => $s['about_button_text'] ?? '',
            'about_button_url'      => $s['about_button_url']['url'] ?? '',
        ];
        for ($i = 1; $i <= 3; $i++) {
            $mods["about_stat_{$i}_value"] = $s["about_stat_{$i}_value"] ?? '';
            $mods["about_stat_{$i}_label"] = $s["about_stat_{$i}_label"] ?? '';
        }
        $this->render_template('about', $mods);
    }
}
