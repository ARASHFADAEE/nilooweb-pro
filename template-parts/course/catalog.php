<?php

if (!defined('ABSPATH')) {
    exit;
}

$pack       = nasim_course_catalog_query();
$query      = $pack['query'];
$filters    = $pack['filters'];
$categories = $pack['categories'];
$levels     = ['مقدماتی', 'متوسط', 'پیشرفته'];

$has_active = $filters['cat'] || $filters['level'] || $filters['q'] || ('featured' !== $filters['sort']);
$active_n   = (int) (bool) $filters['cat'] + (int) (bool) $filters['level'] + (int) (bool) $filters['q'] + (int) ('featured' !== $filters['sort']);
$action     = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
?>
<main class="course-catalog" id="course-catalog" data-catalog>
  <div class="container-wide catalog-shell">
    <aside class="catalog-aside" id="catalogAside" aria-label="فیلتر دوره‌ها">
      <button type="button" class="catalog-sheet-backdrop" data-catalog-close tabindex="-1" aria-label="بستن فیلتر"></button>

      <form id="catalogAsidePanel" class="catalog-filters" method="get" action="<?php echo esc_url($action); ?>" data-catalog-form>
        <div class="catalog-sheet-grab" aria-hidden="true"><span></span></div>

        <div class="catalog-sheet-head">
          <div>
            <p class="catalog-sheet-kicker">جستجو و فیلتر</p>
            <h2 id="catalogSheetTitle">فیلتر دوره‌ها</h2>
          </div>
          <button type="button" class="catalog-sheet-x" data-catalog-close aria-label="بستن">×</button>
        </div>

        <div class="catalog-sheet-body">
          <input type="hidden" name="cat" id="catalog-cat" value="<?php echo esc_attr($filters['cat']); ?>" data-catalog-field="cat">
          <input type="hidden" name="level" id="catalog-level" value="<?php echo esc_attr($filters['level']); ?>" data-catalog-field="level">

          <div class="catalog-search">
            <label class="screen-reader-text" for="catalog-q">جستجو</label>
            <input id="catalog-q" type="search" name="q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="جستجو در عنوان دوره‌ها…" autocomplete="off">
            <button type="submit" class="button button-primary button-small catalog-search-go">بگرد</button>
          </div>

          <?php if ($categories) : ?>
          <div class="catalog-block">
            <h3>دسته‌بندی</h3>
            <div class="catalog-chips catalog-chips--wrap" role="list">
              <button type="button" role="listitem" class="catalog-chip<?php echo !$filters['cat'] ? ' is-active' : ''; ?>" data-filter="cat" data-value="">همه دسته‌ها</button>
              <?php foreach ($categories as $term) : ?>
              <button type="button" role="listitem" class="catalog-chip<?php echo $filters['cat'] === $term->slug ? ' is-active' : ''; ?>" data-filter="cat" data-value="<?php echo esc_attr($term->slug); ?>">
                <?php echo esc_html($term->name); ?>
                <b><?php echo esc_html(nasim_fa_digits($term->count)); ?></b>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="catalog-block">
            <h3>سطح</h3>
            <div class="catalog-chips catalog-chips--wrap" role="list">
              <button type="button" role="listitem" class="catalog-chip<?php echo !$filters['level'] ? ' is-active' : ''; ?>" data-filter="level" data-value="">همه سطوح</button>
              <?php foreach ($levels as $level) : ?>
              <button type="button" role="listitem" class="catalog-chip<?php echo $filters['level'] === $level ? ' is-active' : ''; ?>" data-filter="level" data-value="<?php echo esc_attr($level); ?>"><?php echo esc_html($level); ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="catalog-block">
            <h3>مرتب‌سازی</h3>
            <label class="screen-reader-text" for="catalog-sort">مرتب‌سازی</label>
            <select id="catalog-sort" name="sort" class="catalog-select" data-catalog-sort>
              <option value="featured" <?php selected($filters['sort'], 'featured'); ?>>پیشنهادی آکادمی</option>
              <option value="newest" <?php selected($filters['sort'], 'newest'); ?>>جدیدترین</option>
              <option value="title" <?php selected($filters['sort'], 'title'); ?>>عنوان (الفبا)</option>
            </select>
          </div>
        </div>

        <div class="catalog-sheet-foot">
          <button type="button" class="catalog-clear" data-catalog-clear <?php echo $has_active ? '' : 'hidden'; ?>>پاک کردن</button>
          <button type="submit" class="button button-primary catalog-apply">اعمال فیلتر</button>
        </div>
      </form>
    </aside>

    <section class="catalog-main" aria-live="polite">
      <div class="catalog-toolbar">
        <p class="catalog-result">
          <?php
            $found = (int) $query->found_posts;
            echo esc_html(nasim_fa_digits($found) . ' دوره');
            if ($filters['cat']) {
                $term = get_term_by('slug', $filters['cat'], 'nasim_course_cat');
                if ($term) {
                    echo ' · ' . esc_html($term->name);
                }
            }
            ?>
        </p>
        <div class="catalog-mobile-filters">
          <button
            type="button"
            class="catalog-filter-btn<?php echo $has_active ? ' has-active' : ''; ?>"
            id="catalogFilterToggle"
            aria-expanded="false"
            aria-controls="catalogAsidePanel"
            aria-haspopup="dialog"
            aria-label="باز کردن فیلتر دوره‌ها"
          >
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 6h16M7 12h10M10 18h4"/>
            </svg>
            <?php if ($active_n > 0) : ?>
            <span class="catalog-filter-count"><?php echo esc_html(nasim_fa_digits($active_n)); ?></span>
            <?php endif; ?>
          </button>
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
        <a class="button button-primary" href="<?php echo esc_url(nasim_course_catalog_url(['cat' => '', 'level' => '', 'q' => '', 'paged' => 1])); ?>">مشاهده همه دوره‌ها</a>
      </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="catalog-loading" data-catalog-loading hidden>
    <div class="catalog-loading-card">
      <div class="catalog-loading-orbit" aria-hidden="true">
        <span></span><span></span><span></span>
      </div>
      <strong>در حال اعمال فیلتر</strong>
      <p>دوره‌های مناسب برات جور می‌شن…</p>
    </div>
  </div>
</main>
