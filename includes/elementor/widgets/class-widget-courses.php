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
            'default' => (int) nasim_mod('courses_home_count') ?: 2,
            'min'     => 1,
            'max'     => 24,
        ]);

        $this->add_control('access_mode', [
            'label'   => __('فیلتر نحوه دسترسی', 'niloo-web'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'any',
            'options' => [
                'any'          => __('همه', 'niloo-web'),
                'purchase'     => __('خرید جداگانه', 'niloo-web'),
                'subscription' => __('رایگان با اشتراک', 'niloo-web'),
                'free'         => __('رایگان', 'niloo-web'),
            ],
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
            'courses_home_count'           => (int) ($s['count'] ?? 2),
        ];

        $ids  = array_filter(array_map('absint', (array) ($s['course_ids'] ?? [])));
        $mode = sanitize_key((string) ($s['access_mode'] ?? 'any'));
        $layout = sanitize_key((string) ($s['layout'] ?? 'showcase'));

        $args = [
            'post_type'      => 'nasim_course',
            'post_status'    => 'publish',
            'posts_per_page' => max(1, (int) ($s['count'] ?? 2)),
            'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'no_found_rows'  => true,
        ];

        if ($ids) {
            $args['post__in'] = $ids;
            $args['orderby']  = 'post__in';
            $args['posts_per_page'] = count($ids);
        }

        if ('any' !== $mode && function_exists('nasim_course_access_mode')) {
            // Filter after query for meta consistency (legacy inference).
            $args['posts_per_page'] = max(24, (int) $args['posts_per_page']);
        }

        $query = new WP_Query($args);
        $posts = $query->posts;

        if ('any' !== $mode && function_exists('nasim_course_access_mode')) {
            $posts = array_values(array_filter($posts, static function ($post) use ($mode) {
                return nasim_course_access_mode($post->ID) === $mode;
            }));
            $posts = array_slice($posts, 0, max(1, (int) ($s['count'] ?? 2)));
        }

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

        // Showcase layout (homepage cards) — reuse markup with a custom loop.
        $this->with_theme_mods($mods, function () use ($posts, $archive, $show_link, $s) {
            ?>
    <section class="section container-wide home-courses niloo-el-courses" id="courses">
      <div class="section-heading">
        <div>
          <?php if (!empty($s['courses_home_eyebrow'])) : ?>
          <span class="eyebrow"><span class="eyebrow-line"></span> <?php echo esc_html($s['courses_home_eyebrow']); ?></span>
          <?php endif; ?>
          <h2><?php echo esc_html($s['courses_home_title'] ?? ''); ?> <span class="accent"><?php echo esc_html($s['courses_home_title_highlight'] ?? ''); ?></span></h2>
        </div>
        <?php if ($show_link && $archive) : ?>
        <a class="text-link" href="<?php echo esc_url($archive); ?>">همه دوره‌ها <span>←</span></a>
        <?php endif; ?>
      </div>
      <div class="home-course-grid">
        <?php
        global $post;
        foreach ($posts as $post) :
            setup_postdata($post);
            $outcomes = array_filter(array_map('trim', explode("\n", (string) get_post_meta(get_the_ID(), '_nasim_course_outcomes', true))));
            $cid      = (int) get_the_ID();
            $buy_url  = function_exists('nasim_course_checkout_url') ? nasim_course_checkout_url($cid) : get_permalink($cid);
            $cta      = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($cid) : 'شروع کنید';
            $badge    = function_exists('nasim_course_access_label') ? nasim_course_access_label($cid) : '';
            $badge_cl = function_exists('nasim_course_access_badge_class') ? nasim_course_access_badge_class($cid) : '';
            $thumb    = function_exists('nasim_niloo_course_image_url')
                ? nasim_niloo_course_image_url($cid, 'large')
                : (string) get_the_post_thumbnail_url($cid, 'large');
            ?>
        <article class="home-course-card">
          <a class="home-course-media" href="<?php the_permalink(); ?>">
            <?php if ($thumb) : ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async">
            <?php else : ?>
              <span class="home-course-placeholder" aria-hidden="true">◆</span>
            <?php endif; ?>
            <?php if ($badge) : ?>
            <span class="<?php echo esc_attr($badge_cl); ?> course-access-badge--overlay"><?php echo esc_html($badge); ?></span>
            <?php endif; ?>
          </a>
          <div class="home-course-body">
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php if (get_the_excerpt()) : ?>
            <p><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p>
            <?php endif; ?>
            <?php if ($outcomes) : ?>
            <ul class="home-course-features">
              <?php foreach (array_slice($outcomes, 0, 5) as $line) : ?>
              <li><?php echo esc_html($line); ?></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <div class="home-course-actions">
              <a class="button button-primary" href="<?php echo esc_url($buy_url); ?>"><?php echo esc_html($cta); ?> <span>←</span></a>
              <a class="text-link" href="<?php the_permalink(); ?>">جزئیات</a>
            </div>
          </div>
        </article>
            <?php
        endforeach;
        wp_reset_postdata();
        ?>
      </div>
    </section>
            <?php
        });
    }

    private function is_preview_mode(): bool
    {
        return class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode();
    }
}
