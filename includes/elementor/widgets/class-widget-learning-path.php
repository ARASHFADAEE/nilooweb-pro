<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «مسیر یادگیری ۵ مرحله‌ای» – همان بخش صفحه اصلی (template-parts/front-page/learning-path).
 */
class Nasim_Elementor_Widget_Learning_Path extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_learning_path';
    }

    public function get_title(): string
    {
        return __('نیلو · مسیر یادگیری', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-navigation-horizontal';
    }

    public function get_keywords(): array
    {
        return ['مسیر', 'یادگیری', 'path', 'steps', 'niloo'];
    }

    protected function register_controls(): void
    {
        // محتوای این بخش از دوره‌های واقعی سایت خوانده می‌شود؛ تنظیمی ندارد.
    }

    protected function render(): void
    {
        $this->render_template('learning-path');
    }
}
