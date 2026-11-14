<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class Nasim_Elementor_Widget_Hero extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_hero';
    }

    public function get_title(): string
    {
        return __('نیلو · هیرو', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-banner';
    }

    public function get_keywords(): array
    {
        return ['hero', 'هیرو', 'banner', 'niloo'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'niloo-web')]);

        $this->add_control('hero_eyebrow', [
            'label'   => __('تیتر کوچک', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_eyebrow'),
        ]);
        $this->add_control('hero_title', [
            'label'   => __('عنوان', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_title'),
        ]);
        $this->add_control('hero_title_highlight', [
            'label'   => __('عنوان رنگی', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_title_highlight'),
        ]);
        $this->add_control('hero_description', [
            'label'   => __('توضیح', 'niloo-web'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => (string) nasim_mod('hero_description'),
        ]);
        $this->add_control('hero_primary_text', [
            'label'   => __('دکمه اصلی', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_primary_text'),
        ]);
        $this->add_control('hero_primary_url', [
            'label'   => __('لینک دکمه اصلی', 'niloo-web'),
            'type'    => Controls_Manager::URL,
            'default' => ['url' => (string) nasim_mod('hero_primary_url')],
        ]);
        $this->add_control('hero_secondary_text', [
            'label'   => __('دکمه دوم', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_secondary_text'),
        ]);
        $this->add_control('hero_secondary_url', [
            'label'   => __('لینک دکمه دوم', 'niloo-web'),
            'type'    => Controls_Manager::URL,
            'default' => ['url' => (string) nasim_mod('hero_secondary_url')],
        ]);
        $this->add_control('hero_image', [
            'label'   => __('تصویر هیرو', 'niloo-web'),
            'type'    => Controls_Manager::MEDIA,
            'default' => ['url' => (string) nasim_mod('hero_image')],
        ]);
        $this->add_control('hero_note_top_title', [
            'label'   => __('نوت بالا · عنوان', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_note_top_title'),
        ]);
        $this->add_control('hero_note_top_text', [
            'label'   => __('نوت بالا · متن', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_note_top_text'),
        ]);
        $this->add_control('hero_note_bottom_title', [
            'label'   => __('نوت پایین · عنوان', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_note_bottom_title'),
        ]);
        $this->add_control('hero_note_bottom_text', [
            'label'   => __('نوت پایین · متن', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('hero_note_bottom_text'),
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $mods = [
            'hero_eyebrow'           => $s['hero_eyebrow'] ?? '',
            'hero_title'             => $s['hero_title'] ?? '',
            'hero_title_highlight'   => $s['hero_title_highlight'] ?? '',
            'hero_description'       => $s['hero_description'] ?? '',
            'hero_primary_text'      => $s['hero_primary_text'] ?? '',
            'hero_primary_url'       => $s['hero_primary_url']['url'] ?? '',
            'hero_secondary_text'    => $s['hero_secondary_text'] ?? '',
            'hero_secondary_url'     => $s['hero_secondary_url']['url'] ?? '',
            'hero_image'             => $s['hero_image']['url'] ?? '',
            'hero_note_top_title'    => $s['hero_note_top_title'] ?? '',
            'hero_note_top_text'     => $s['hero_note_top_text'] ?? '',
            'hero_note_bottom_title' => $s['hero_note_bottom_title'] ?? '',
            'hero_note_bottom_text'  => $s['hero_note_bottom_text'] ?? '',
        ];
        $this->render_template('hero', $mods);
    }
}
