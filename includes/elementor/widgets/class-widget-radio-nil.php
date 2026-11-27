<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class Nasim_Elementor_Widget_Radio_Nil extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_radio';
    }

    public function get_title(): string
    {
        return __('نیلو · رادیو نیل', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-headphones';
    }

    public function get_keywords(): array
    {
        return ['radio', 'رادیو', 'پادکست', 'podcast', 'niloo'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'niloo-web')]);

        $this->add_heading_controls('radio', [
            'eyebrow'   => (string) (nasim_mod('radio_eyebrow') ?: 'رادیو نیل'),
            'title'     => (string) (nasim_mod('radio_title') ?: 'پادکست و گفتگو'),
            'highlight' => (string) (nasim_mod('radio_title_highlight') ?: 'در مسیر طراحی'),
        ]);

        $this->add_control('count', [
            'label'   => __('تعداد اپیزود', 'niloo-web'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 3,
            'min'     => 1,
            'max'     => 9,
        ]);

        $this->add_control('category_slug', [
            'label'       => __('اسلاگ دسته', 'niloo-web'),
            'type'        => Controls_Manager::TEXT,
            'default'     => (string) (nasim_mod('radio_cat') ?: 'radionil'),
            'description' => __('مثلاً radionil', 'niloo-web'),
        ]);

        $this->add_control('more_url', [
            'label'   => __('لینک همه اپیزودها', 'niloo-web'),
            'type'    => Controls_Manager::URL,
            'default' => ['url' => (string) (function_exists('nasim_radio_url') ? nasim_radio_url() : home_url('/blog/'))],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s   = $this->get_settings_for_display();
        $cat = sanitize_title((string) ($s['category_slug'] ?? 'radionil'));
        $count = max(1, (int) ($s['count'] ?? 3));
        $more  = !empty($s['more_url']['url']) ? $s['more_url']['url'] : (function_exists('nasim_radio_url') ? nasim_radio_url() : home_url('/blog/'));

        $eyebrow   = (string) ($s['radio_eyebrow'] ?? '');
        $title     = (string) ($s['radio_title'] ?? '');
        $highlight = (string) ($s['radio_title_highlight'] ?? '');

        // Same dark radio card as the homepage "محتوای رایگان" section.
        $this->with_theme_mods([
            'radio_cat'      => $cat,
            'radio_more_url' => $more,
        ], static function () use ($count, $eyebrow, $title, $highlight) {
            echo '<section class="container-site py-14 md:py-18" aria-label="پادکست رادیو نیل">';
            if ($eyebrow || $title) {
                echo '<div class="mb-8">';
                if ($eyebrow) {
                    echo '<span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-semibold mb-4 text-accent-foreground"><span class="size-1.5 rounded-full bg-violet-400 inline-block"></span>' . esc_html($eyebrow) . '</span>';
                }
                if ($title) {
                    echo '<h2 class="text-2xl md:text-[2rem] font-extrabold text-foreground">' . esc_html($title) . ($highlight ? ' <span class="mark-violet">' . esc_html($highlight) . '</span>' : '') . '</h2>';
                }
                echo '</div>';
            }
            get_template_part('template-parts/front-page/radio-nil', null, ['count' => $count]);
            echo '</section>';
        });
    }
}
