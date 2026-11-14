<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class Nasim_Elementor_Widget_Articles extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_articles';
    }

    public function get_title(): string
    {
        return __('نیلو · مقالات', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-post-list';
    }

    public function get_keywords(): array
    {
        return ['blog', 'مقاله', 'articles', 'niloo'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('محتوا', 'niloo-web')]);

        $this->add_heading_controls('articles', [
            'eyebrow'   => (string) nasim_mod('articles_eyebrow'),
            'title'     => (string) nasim_mod('articles_title'),
            'highlight' => (string) nasim_mod('articles_title_highlight'),
        ]);

        $this->add_control('count', [
            'label'   => __('تعداد', 'niloo-web'),
            'type'    => Controls_Manager::NUMBER,
            'default' => (int) nasim_mod('articles_count') ?: 3,
            'min'     => 1,
            'max'     => 12,
        ]);

        $categories = [];
        foreach (get_categories(['hide_empty' => false]) as $cat) {
            $categories[(string) $cat->term_id] = $cat->name;
        }

        $this->add_control('category', [
            'label'       => __('دسته (اختیاری)', 'niloo-web'),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $categories,
            'multiple'    => true,
            'label_block' => true,
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $mods = [
            'articles_eyebrow'         => $s['articles_eyebrow'] ?? '',
            'articles_title'           => $s['articles_title'] ?? '',
            'articles_title_highlight' => $s['articles_title_highlight'] ?? '',
            'articles_count'           => (int) ($s['count'] ?? 3),
        ];

        $args = [
            'post_type'           => 'post',
            'posts_per_page'      => max(1, (int) ($s['count'] ?? 3)),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ];

        $cats = array_filter(array_map('absint', (array) ($s['category'] ?? [])));
        if ($cats) {
            $args['category__in'] = $cats;
        }

        $articles = new WP_Query($args);
        if (!$articles->have_posts()) {
            return;
        }

        $this->with_theme_mods($mods, static function () use ($articles) {
            ?>
    <section class="section container-wide articles-section niloo-el-articles" id="articles">
      <div class="section-heading">
        <?php nasim_section_title('articles'); ?>
        <a class="text-link" href="<?php echo esc_url(nasim_blog_url()); ?>">همه مقاله‌ها <span>←</span></a>
      </div>
      <div class="article-grid">
        <?php while ($articles->have_posts()) : $articles->the_post(); ?>
          <?php get_template_part('template-parts/blog/card'); ?>
        <?php endwhile; ?>
      </div>
    </section>
            <?php
            wp_reset_postdata();
        });
    }
}
