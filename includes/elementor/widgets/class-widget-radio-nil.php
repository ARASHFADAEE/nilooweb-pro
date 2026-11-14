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
            'default' => ['url' => (string) (nasim_mod('radio_more_url') ?: home_url('/blog/'))],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s   = $this->get_settings_for_display();
        $cat = sanitize_title((string) ($s['category_slug'] ?? 'radionil'));
        $count = max(1, (int) ($s['count'] ?? 3));
        $more  = !empty($s['more_url']['url']) ? $s['more_url']['url'] : home_url('/blog/');

        $mods = [
            'radio_eyebrow'         => $s['radio_eyebrow'] ?? '',
            'radio_title'           => $s['radio_title'] ?? '',
            'radio_title_highlight' => $s['radio_title_highlight'] ?? '',
            'radio_cat'             => $cat,
            'radio_more_url'        => $more,
        ];

        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'category_name'  => $cat,
            'no_found_rows'  => true,
        ]);

        if (!$posts) {
            $posts = get_posts([
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => $count,
                'no_found_rows'  => true,
            ]);
        }

        if (!$posts) {
            return;
        }

        $this->with_theme_mods($mods, static function () use ($posts, $more, $s) {
            ?>
    <section class="section container-wide home-radio niloo-el-radio" id="radio">
      <div class="section-heading">
        <div>
          <span class="eyebrow"><span class="eyebrow-line"></span> <?php echo esc_html($s['radio_eyebrow'] ?? 'رادیو نیل'); ?></span>
          <h2><?php echo esc_html($s['radio_title'] ?? ''); ?> <span class="accent"><?php echo esc_html($s['radio_title_highlight'] ?? ''); ?></span></h2>
        </div>
        <a class="text-link" href="<?php echo esc_url($more); ?>">همه اپیزودها <span>←</span></a>
      </div>
      <div class="home-radio-grid">
        <?php foreach ($posts as $post) : ?>
        <a class="home-radio-card" href="<?php echo esc_url(get_permalink($post)); ?>">
          <?php if (has_post_thumbnail($post)) : ?>
          <?php echo get_the_post_thumbnail($post, 'medium_large', ['loading' => 'lazy', 'decoding' => 'async']); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          <?php endif; ?>
          <div>
            <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            <strong><?php echo esc_html(get_the_title($post)); ?></strong>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
            <?php
        });
    }
}
