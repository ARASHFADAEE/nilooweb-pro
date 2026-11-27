<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

/**
 * Elementor: course showcase / grid for homepage.
 */
class Nasim_Elementor_Widget_Courses extends Nasim_Elementor_Widget_Base
{
    public function get_name(): string
    {
        return 'niloo_courses';
    }

    public function get_title(): string
    {
        return __('نیلو · دوره‌ها', 'niloo-web');
    }

    public function get_icon(): string
    {
        return 'eicon-gallery-grid';
    }

    public function get_keywords(): array
    {
        return ['course', 'دوره', 'niloo', 'lms', 'آموزش'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', [
            'label' => __('محتوا', 'niloo-web'),
        ]);

        $this->add_heading_controls('courses_home', [
            'eyebrow'   => (string) nasim_mod('courses_home_eyebrow'),
            'title'     => (string) nasim_mod('courses_home_title'),
            'highlight' => (string) nasim_mod('courses_home_title_highlight'),
        ]);

        $this->add_control('count', [
            'label'   => __('تعداد دوره', 'niloo-web'),
            'type'    => Controls_Manager::NUMBER,
            'default' => (int) nasim_mod('courses_home_count') ?: 4,
            'min'     => 1,
            'max'     => 24,
        ]);

        $this->add_control('course_ids', [
            'label'       => __('انتخاب دستی دوره‌ها (اختیاری)', 'niloo-web'),
            'type'        => Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => $this->course_options(),
            'label_block' => true,
            'description' => __('اگر خالی باشد، آخرین/اولویت‌دارترین دوره‌ها نمایش داده می‌شوند.', 'niloo-web'),
        ]);

        $this->add_control('layout', [
            'label'   => __('چیدمان', 'niloo-web'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'showcase',
            'options' => [
                'showcase' => __('کارت‌های صفحه اصلی', 'niloo-web'),
                'grid'     => __('گرید آرشیو', 'niloo-web'),
            ],
        ]);

        $this->add_control('show_archive_link', [
            'label'        => __('لینک همه دوره‌ها', 'niloo-web'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $mods = [
            'courses_home_eyebrow'         => $s['courses_home_eyebrow'] ?? '',
            'courses_home_title'           => $s['courses_home_title'] ?? '',
            'courses_home_title_highlight' => $s['courses_home_title_highlight'] ?? '',
            'courses_home_count'           => (int) ($s['count'] ?? 4),
        ];

        $ids    = array_filter(array_map('absint', (array) ($s['course_ids'] ?? [])));
        $layout = sanitize_key((string) ($s['layout'] ?? 'showcase'));
        $limit  = max(1, (int) ($s['count'] ?? 4));

        $args = [
            'post_type'      => 'nasim_course',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'no_found_rows'  => true,
        ];

        if ($ids) {
            $args['post__in'] = $ids;
            $args['orderby']  = 'post__in';
            $args['posts_per_page'] = count($ids);
        }

        $query = new WP_Query($args);
        $posts = $query->posts;

        if (!$posts) {
            if ($this->is_preview_mode()) {
                echo '<p style="padding:16px;border:1px dashed #ccc;text-align:center">' . esc_html__('دوره‌ای برای نمایش پیدا نشد.', 'niloo-web') . '</p>';
            }
            return;
        }

        $archive = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
        $show_link = ('yes' === ($s['show_archive_link'] ?? 'yes'));

        if ('grid' === $layout) {
            echo '<section class="section container-wide course-archive niloo-el-courses">';
            echo '<div class="section-heading"><div>';
            if (!empty($s['courses_home_eyebrow'])) {
                echo '<span class="eyebrow"><span class="eyebrow-line"></span> ' . esc_html($s['courses_home_eyebrow']) . '</span>';
            }
            echo '<h2>' . esc_html($s['courses_home_title'] ?? '') . ' <span class="accent">' . esc_html($s['courses_home_title_highlight'] ?? '') . '</span></h2>';
            echo '</div>';
            if ($show_link && $archive) {
                echo '<a class="text-link" href="' . esc_url($archive) . '">همه دوره‌ها <span>←</span></a>';
            }
            echo '</div><div class="course-grid">';
            global $post;
            foreach ($posts as $post) {
                setup_postdata($post);
                get_template_part('template-parts/course/card');
            }
            wp_reset_postdata();
            echo '</div></section>';
            return;
        }

        // Showcase layout: the redesigned homepage section, fed with the widget's courses.
        $this->with_theme_mods($mods, function () use ($posts, $show_link) {
            get_template_part('template-parts/front-page/courses-showcase', null, [
                'course_ids' => wp_list_pluck($posts, 'ID'),
                'hide_link'  => !$show_link,
            ]);
        });
    }

    private function is_preview_mode(): bool
    {
        return class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode();
    }
}
