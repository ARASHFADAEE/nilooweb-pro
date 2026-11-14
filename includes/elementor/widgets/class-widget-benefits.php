<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;

class Nasim_Elementor_Widget_Benefits extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_benefits';
    }

    public function get_title(): string
    {
        return __('نیلو · مزایا', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-info-box';
    }

    public function get_keywords(): array
    {
        return ['benefits', 'مزایا', 'niloo'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('مزایا', 'niloo-web')]);

        $repeater = new Repeater();
        $repeater->add_control('title', [
            'label' => __('عنوان', 'niloo-web'),
            'type'  => Controls_Manager::TEXT,
        ]);
        $repeater->add_control('text', [
            'label' => __('متن', 'niloo-web'),
            'type'  => Controls_Manager::TEXT,
        ]);

        $defaults = [];
        for ($i = 1; $i <= 4; $i++) {
            $defaults[] = [
                'title' => (string) nasim_mod("benefit_{$i}_title"),
                'text'  => (string) nasim_mod("benefit_{$i}_text"),
            ];
        }

        $this->add_control('items', [
            'label'       => __('آیتم‌ها', 'niloo-web'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => $defaults,
            'title_field' => '{{{ title }}}',
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s     = $this->get_settings_for_display();
        $items = array_values((array) ($s['items'] ?? []));
        $mods  = [];
        for ($i = 1; $i <= 4; $i++) {
            $mods["benefit_{$i}_title"] = $items[$i - 1]['title'] ?? '';
            $mods["benefit_{$i}_text"]  = $items[$i - 1]['text'] ?? '';
        }
        $this->render_template('benefits', $mods);
    }
}
