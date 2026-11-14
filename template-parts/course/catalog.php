<?php

if (!defined('ABSPATH')) {
    exit;
}

$pack       = nasim_course_catalog_query();
$query      = $pack['query'];
$filters    = $pack['filters'];
$stats      = $pack['stats'];
$categories = $pack['categories'];
$levels     = ['مقدماتی', 'متوسط', 'پیشرفته'];

$access_tabs = [
    'all'          => ['label' => 'همه', 'count' => $stats['all']],
    'free'         => ['label' => 'رایگان', 'count' => $stats['free']],
    'subscription' => ['label' => 'با اشتراک', 'count' => $stats['subscription']],
    'purchase'     => ['label' => 'خرید جداگانه', 'count' => $stats['purchase']],
];

$has_active = ('all' !== $filters['access']) || $filters['cat'] || $filters['level'] || $filters['q'] || ('featured' !== $filters['sort']);
?>
<main class="course-catalog" id="course-catalog">
  <header class="catalog-hero">
    <div class="container-wide catalog-hero-inner">
      <nav class="lms-breadcrumb catalog-crumb" aria-label="مسیر">
        <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span>／</span><span>دوره‌ها</span>
      </nav>
      <div class="catalog-hero-copy">
        <span class="catalog-kicker">آکادمی نیلوفر وفایی</span>
        <h1>کاتالوگ <em>دوره‌ها</em></h1>
        <p>مسیرت را فیلتر کن: رایگان شروع کن، با اشتراک ادامه بده، یا دوره‌های پرچمدار را یک‌بار بخر.</p>
      </div>
      <div class="catalog-hero-stats" aria-label="آمار دوره‌ها">
        <?php foreach (['free' => 'رایگان', 'subscription' => 'اشتراک', 'purchase' => 'خرید'] as $key => $label) : ?>
        <a class="catalog-stat<?php echo $filters['access'] === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url(nasim_course_catalog_url(['access' => $key, 'paged' => 1])); ?>">
          <strong><?php echo esc_html(nasim_fa_digits($stats[$key])); ?></strong>
          <small><?php echo esc_html($label); ?></small>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="catalog-hero-glow" aria-hidden="true"></div>
  </header>

  <div class="container-wide catalog-shell">
    <aside class="catalog-aside" aria-label="فیلتر دوره‌ها">
      <form id="catalogAsidePanel" class="catalog-filters" method="get" action="<?php echo esc_url(function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/')); ?>">
        <div class="catalog-search">
          <label class="screen-reader-text" for="catalog-q">جستجو</label>
          <input id="catalog-q" type="search" name="q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="جستجو در عنوان دوره‌ها…">
          <button type="submit" class="button button-primary button-small">بگرد</button>
        </div>

        <div class="catalog-block">
          <h2>نوع دسترسی</h2>
          <div class="catalog-chips" role="list">
            <?php foreach ($access_tabs as $key => $tab) : ?>
            <a role="listitem" class="catalog-chip<?php echo $filters['access'] === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url(nasim_course_catalog_url(['access' => $key, 'paged' => 1])); ?>">
              <?php echo esc_html($tab['label']); ?>
              <b><?php echo esc_html(nasim_fa_digits($tab['count'])); ?></b>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($categories) : ?>
        <div class="catalog-block">
          <h2>دسته‌بندی</h2>
          <div class="catalog-chips catalog-chips--wrap" role="list">
            <a role="listitem" class="catalog-chip<?php echo !$filters['cat'] ? ' is-active' : ''; ?>" href="<?php echo esc_url(nasim_course_catalog_url(['cat' => '', 'paged' => 1])); ?>">همه دسته‌ها</a>
            <?php foreach ($categories as $term) : ?>
            <a role="listitem" class="catalog-chip<?php echo $filters['cat'] === $term->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url(nasim_course_catalog_url(['cat' => $term->slug, 'paged' => 1])); ?>">
              <?php echo esc_html($term->name); ?>
              <b><?php echo esc_html(nasim_fa_digits($term->count)); ?></b>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="catalog-block">
          <h2>سطح</h2>
          <div class="catalog-chips catalog-chips--wrap" role="list">
            <a role="listitem" class="catalog-chip<?php echo !$filters['level'] ? ' is-active' : ''; ?>" href="<?php echo esc_url(nasim_course_catalog_url(['level' => '', 'paged' => 1])); ?>">همه سطوح</a>
            <?php foreach ($levels as $level) : ?>
            <a role="listitem" class="catalog-chip<?php echo $filters['level'] === $level ? ' is-active' : ''; ?>" href="<?php echo esc_url(nasim_course_catalog_url(['level' => $level, 'paged' => 1])); ?>"><?php echo esc_html($level); ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="catalog-block">
          <h2>مرتب‌سازی</h2>
          <label class="screen-reader-text" for="catalog-sort">مرتب‌سازی</label>
          <select id="catalog-sort" name="sort" class="catalog-select" onchange="this.form.submit()">
            <option value="featured" <?php selected($filters['sort'], 'featured'); ?>>پیشنهادی آکادمی</option>
            <option value="newest" <?php selected($filters['sort'], 'newest'); ?>>جدیدترین</option>
            <option value="title" <?php selected($filters['sort'], 'title'); ?>>عنوان (الفبا)</option>
          </select>
          <?php if ($filters['access'] && 'all' !== $filters['access']) : ?>
          <input type="hidden" name="access" value="<?php echo esc_attr($filters['access']); ?>">
          <?php endif; ?>
          <?php if ($filters['cat']) : ?>
          <input type="hidden" name="cat" value="<?php echo esc_attr($filters['cat']); ?>">
          <?php endif; ?>
          <?php if ($filters['level']) : ?>
          <input type="hidden" name="level" value="<?php echo esc_attr($filters['level']); ?>">
          <?php endif; ?>
        </div>

        <?php if ($has_active) : ?>
        <a class="catalog-reset" href="<?php echo esc_url(nasim_course_catalog_url(['access' => 'all', 'cat' => '', 'level' => '', 'q' => '', 'sort' => 'featured', 'paged' => 1])); ?>">پاک کردن فیلترها</a>
        <?php endif; ?>
      </form>
    </aside>

    <section class="catalog-main" aria-live="polite">
      <div class="catalog-toolbar">
        <p class="catalog-result">
          <?php
            $found = (int) $query->found_posts;
            echo esc_html(nasim_fa_digits($found) . ' دوره');
            if ($filters['access'] !== 'all') {
                echo ' · ' . esc_html($access_tabs[$filters['access']]['label'] ?? '');
            }
            if ($filters['cat']) {
                $term = get_term_by('slug', $filters['cat'], 'nasim_course_cat');
                if ($term) {
                    echo ' · ' . esc_html($term->name);
                }
            }
            ?>
        </p>
        <div class="catalog-mobile-filters">
          <button type="button" class="button button-outline button-small" id="catalogFilterToggle" aria-expanded="false" aria-controls="catalogAsidePanel">فیلترها</button>
        </div>
      </div>

      <?php if ($query->have_posts()) : ?>
      <div class="catalog-grid">
        <?php
        while ($query->have_posts()) :
            $query->the_post();
            get_template_part('template-parts/course/card', 'catalog');
        endwhile;
        wp_reset_postdata();
        ?>
      </div>

      <?php
        $total = (int) $query->max_num_pages;
        if ($total > 1) :
            echo '<nav class="catalog-pagination" aria-label="صفحه‌بندی">';
            for ($i = 1; $i <= $total; $i++) {
                $url = nasim_course_catalog_url(['paged' => $i]);
                $cls = $i === (int) $filters['paged'] ? ' is-current' : '';
                printf(
                    '<a class="catalog-page%s" href="%s">%s</a>',
                    esc_attr($cls),
                    esc_url($url),
                    esc_html(nasim_fa_digits($i))
                );
            }
            echo '</nav>';
        endif;
        ?>
      <?php else : ?>
      <div class="catalog-empty">
        <strong>دوره‌ای با این فیلتر پیدا نشد</strong>
        <p>فیلتر را عوض کن یا همه دوره‌ها را ببین.</p>
        <a class="button button-primary" href="<?php echo esc_url(nasim_course_catalog_url(['access' => 'all', 'cat' => '', 'level' => '', 'q' => '', 'paged' => 1])); ?>">مشاهده همه دوره‌ها</a>
      </div>
      <?php endif; ?>

      <?php if (!nasim_has_active_subscription()) : ?>
      <aside class="catalog-cta">
        <div>
          <span class="eyebrow"><span class="eyebrow-line"></span> اشتراک آموزشی</span>
          <h2>دوره‌های «رایگان با اشتراک» با یک پلن باز می‌شوند</h2>
          <p>اولترا وب و دوره‌های پرچمدار همچنان خرید جداگانه دارند.</p>
        </div>
        <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>">مشاهده پلن‌ها <span>←</span></a>
      </aside>
      <?php endif; ?>
    </section>
  </div>
</main>
