<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «محتوای رایگان» – رادیو نیل، مجله آموزشی و کپسول رایگان (template-parts/front-page/free-content).
 */
class Nasim_Elementor_Widget_Free_Content extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_free_content';
    }

    public function get_title(): string
    {
        return __('نیلو · محتوای رایگان', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-gallery-grid';
    }

    public function get_keywords(): array
    {
        return ['رایگان', 'رادیو', 'مجله', 'کپسول', 'free', 'niloo'];
    }

    protected function register_controls(): void
    {
        // محتوای این بخش از رادیو نیل، مقالات و دوره رایگان سایت خوانده می‌شود؛ تنظیمی ندارد.
    }

    protected function render(): void
    {
        $this->render_template('free-content');
    }
}
