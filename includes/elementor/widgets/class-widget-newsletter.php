<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class Nasim_Elementor_Widget_Newsletter extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_newsletter';
    }

    public function get_title(): string
    {
        return __('نیلو · خبرنامه / تماس', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-email-field';
    }

    public function get_keywords(): array
    {
        return ['newsletter', 'خبرنامه', 'تماس', 'cta', 'niloo'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'niloo-web')]);

        $this->add_control('cta_eyebrow', [
            'label'   => __('تیتر کوچک', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('cta_eyebrow'),
        ]);
        $this->add_control('cta_title', [
            'label'   => __('عنوان', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('cta_title'),
        ]);
        $this->add_control('cta_text', [
            'label'   => __('متن', 'niloo-web'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => (string) nasim_mod('cta_text'),
        ]);
        $this->add_control('cta_button_text', [
            'label'   => __('متن دکمه', 'niloo-web'),
            'type'    => Controls_Manager::TEXT,
            'default' => (string) nasim_mod('cta_button_text'),
        ]);
        $this->add_control('cta_button_url', [
            'label'   => __('لینک دکمه', 'niloo-web'),
            'type'    => Controls_Manager::URL,
            'default' => ['url' => (string) nasim_mod('cta_button_url')],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $this->render_template('newsletter', [
            'cta_eyebrow'     => $s['cta_eyebrow'] ?? '',
            'cta_title'       => $s['cta_title'] ?? '',
            'cta_text'        => $s['cta_text'] ?? '',
            'cta_button_text' => $s['cta_button_text'] ?? '',
            'cta_button_url'  => $s['cta_button_url']['url'] ?? '',
        ]);
    }
}
