<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class Nasim_Elementor_Widget_Testimonials extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_testimonials';
    }

    public function get_title(): string
    {
        return __('نیلو · نظرات', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-testimonial-carousel';
    }

    public function get_keywords(): array
    {
        return ['testimonial', 'نظرات', 'niloo'];
    }

    public function get_script_depends(): array
    {
        return ['nasim-main'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'niloo-web')]);

        $this->add_heading_controls('testimonials', [
            'eyebrow'   => (string) nasim_mod('testimonials_eyebrow'),
            'title'     => (string) nasim_mod('testimonials_title'),
            'highlight' => (string) nasim_mod('testimonials_title_highlight'),
        ]);

        $this->add_control('count', [
            'label'   => __('تعداد', 'niloo-web'),
            'type'    => Controls_Manager::NUMBER,
            'default' => (int) nasim_mod('testimonials_count') ?: 6,
            'min'     => 1,
            'max'     => 20,
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $this->render_template('testimonials', [
            'testimonials_eyebrow'         => $s['testimonials_eyebrow'] ?? '',
            'testimonials_title'           => $s['testimonials_title'] ?? '',
            'testimonials_title_highlight' => $s['testimonials_title_highlight'] ?? '',
            'testimonials_count'           => max(1, (int) ($s['count'] ?? 6)),
        ]);
    }
}
