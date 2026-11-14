<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

/**
 * Single course card — pick one course to drop into any Elementor section.
 */
class Nasim_Elementor_Widget_Course_Card extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_course_card';
    }

    public function get_title(): string
    {
        return __('نیلو · کارت یک دوره', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-single-post';
    }

    public function get_keywords(): array
    {
        return ['course', 'card', 'دوره', 'niloo'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('دوره', 'niloo-web')]);

        $this->add_control('course_id', [
            'label'       => __('انتخاب دوره', 'niloo-web'),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $this->course_options(),
            'label_block' => true,
        ]);

        $this->add_control('style', [
            'label'   => __('استایل', 'niloo-web'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'archive',
            'options' => [
                'archive'  => __('کارت آرشیو', 'niloo-web'),
                'showcase' => __('کارت صفحه اصلی', 'niloo-web'),
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s  = $this->get_settings_for_display();
        $id = absint($s['course_id'] ?? 0);
        if (!$id || 'nasim_course' !== get_post_type($id)) {
            if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p style="padding:12px;border:1px dashed #bbb;text-align:center">' . esc_html__('یک دوره انتخاب کن.', 'niloo-web') . '</p>';
            }
            return;
        }

        global $post;
        $post = get_post($id);
        setup_postdata($post);

        echo '<div class="niloo-el-course-card niloo-el-course-card--' . esc_attr(sanitize_key((string) ($s['style'] ?? 'archive'))) . '">';
        get_template_part('template-parts/course/card');
        echo '</div>';

        wp_reset_postdata();
    }
}
