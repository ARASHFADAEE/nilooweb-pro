<?php

if(!defined('ABSPATH')) exit;

global $wp_query;

$filters     = nasim_product_filters();
$sorts       = nasim_product_sort_options();
$types       = nasim_product_type_options();
$archive_url = get_post_type_archive_link('nasim_product');
$current     = $filters['pcat'] ? get_term_by('slug', $filters['pcat'], 'nasim_product_cat') : null;
$total       = (int) $wp_query->found_posts;
$all_count   = (int) wp_count_posts('nasim_product')->publish;
$terms       = get_terms([
    'taxonomy'   => 'nasim_product_cat',
    'hide_empty' => true,
    'orderby'    => 'count',
    'order'      => 'DESC',
]);
$terms       = is_wp_error($terms) ? [] : $terms;
$top_terms   = array_filter($terms, function ($term) { return 0 === (int) $term->parent; });
$has_filters = $filters['pcat'] || $filters['ptype'] || '' !== $filters['q'];

$heading = $current ? $current->name : 'افزونه‌ها و قالب‌ها';

get_header();
?>

  <main id="main-content" class="container-wide shop-page">
    <nav class="lms-breadcrumb" aria-label="مسیر صفحه">
      <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a><span aria-hidden="true">／</span>
      <?php if ($current) : ?>
      <a href="<?php echo esc_url($archive_url); ?>">افزونه‌ها</a><span aria-hidden="true">／</span><span aria-current="page"><?php echo esc_html($current->name); ?></span>
      <?php else : ?>
      <span aria-current="page">افزونه‌ها</span>
      <?php endif; ?>
    </nav>

    <header class="shop-head">
      <div>
        <span class="eyebrow"><span class="eyebrow-line"></span> فروشگاه</span>
        <h1><?php echo esc_html($heading); ?></h1>
        <p><?php echo $current && $current->description ? esc_html(wp_strip_all_tags($current->description)) : 'افزونه‌ها و قالب‌های حرفه‌ای وردپرس با لایسنس، آپدیت و پشتیبانی.'; ?></p>
      </div>
      <form class="shop-search" method="get" action="<?php echo esc_url($archive_url); ?>" role="search">
        <?php foreach (['pcat', 'ptype', 'sort'] as $keep) : if ($filters[$keep] && !('sort' === $keep && 'newest' === $filters[$keep])) : ?>
        <input type="hidden" name="<?php echo esc_attr($keep); ?>" value="<?php echo esc_attr($filters[$keep]); ?>">
        <?php endif; endforeach; ?>
        <label class="screen-reader-text" for="shop-q">جستجو در محصولات</label>
        <input type="search" id="shop-q" name="q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="جستجوی افزونه یا قالب…">
        <button type="submit" aria-label="جستجو"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg></button>
      </form>
    </header>

    <?php if ($top_terms) : ?>
    <nav class="shop-chips" aria-label="دسته‌بندی‌ها">
      <a href="<?php echo esc_url(nasim_product_filter_url(['pcat' => ''])); ?>" class="<?php echo $filters['pcat'] ? '' : 'is-active'; ?>">همه</a>
      <?php foreach ($top_terms as $term) : ?>
      <a href="<?php echo esc_url(nasim_product_filter_url(['pcat' => $term->slug])); ?>" class="<?php echo $filters['pcat'] === $term->slug ? 'is-active' : ''; ?>"><?php echo esc_html($term->name); ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <div class="shop-layout">
      <aside class="shop-sidebar" id="shop-filters" data-shop-filters>
        <div class="shop-sidebar-head">
          <strong>فیلتر و دسته‌بندی</strong>
          <button type="button" class="shop-close" data-shop-filters-close aria-label="بستن">×</button>
        </div>

        <section class="shop-box">
          <h2>دسته‌بندی‌ها</h2>
          <ul class="shop-cats">
            <li><a href="<?php echo esc_url(nasim_product_filter_url(['pcat' => ''])); ?>" class="<?php echo $filters['pcat'] ? '' : 'is-active'; ?>"><span>همه محصولات</span><b><?php echo esc_html(nasim_fa_digits($all_count)); ?></b></a></li>
            <?php foreach ($top_terms as $term) :
                $children = array_filter($terms, function ($child) use ($term) { return (int) $child->parent === (int) $term->term_id; });
                ?>
            <li>
              <a href="<?php echo esc_url(nasim_product_filter_url(['pcat' => $term->slug])); ?>" class="<?php echo $filters['pcat'] === $term->slug ? 'is-active' : ''; ?>"><span><?php echo esc_html($term->name); ?></span><b><?php echo esc_html(nasim_fa_digits($term->count)); ?></b></a>
              <?php if ($children) : ?>
              <ul>
                <?php foreach ($children as $child) : ?>
                <li><a href="<?php echo esc_url(nasim_product_filter_url(['pcat' => $child->slug])); ?>" class="<?php echo $filters['pcat'] === $child->slug ? 'is-active' : ''; ?>"><span><?php echo esc_html($child->name); ?></span><b><?php echo esc_html(nasim_fa_digits($child->count)); ?></b></a></li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php if (!$terms) : ?><p class="shop-muted">هنوز دسته‌ای ساخته نشده است.</p><?php endif; ?>
        </section>

        <section class="shop-box">
          <h2>نوع محصول</h2>
          <div class="shop-segment">
            <a href="<?php echo esc_url(nasim_product_filter_url(['ptype' => ''])); ?>" class="<?php echo $filters['ptype'] ? '' : 'is-active'; ?>">همه</a>
            <?php foreach ($types as $key => $label) : ?>
            <a href="<?php echo esc_url(nasim_product_filter_url(['ptype' => $key])); ?>" class="<?php echo $filters['ptype'] === $key ? 'is-active' : ''; ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="shop-box">
          <h2>مرتب‌سازی</h2>
          <ul class="shop-sort">
            <?php foreach ($sorts as $key => $label) : ?>
            <li><a href="<?php echo esc_url(nasim_product_filter_url(['sort' => $key])); ?>" class="<?php echo $filters['sort'] === $key ? 'is-active' : ''; ?>"><?php echo esc_html($label); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </section>

        <?php if (!nasim_has_active_subscription()) : ?>
        <a class="shop-sub-card" href="<?php echo esc_url(nasim_subscription_url()); ?>">
          <strong>یک اشتراک، همه محصولات</strong>
          <small>دانلود، لایسنس و آپدیت همه افزونه‌ها و قالب‌ها</small>
          <span>مشاهده پلن‌ها ←</span>
        </a>
        <?php endif; ?>
      </aside>
      <div class="shop-backdrop" data-shop-filters-close></div>

      <div class="shop-main">
        <div class="shop-toolbar">
          <button type="button" class="shop-filter-toggle" data-shop-filters-open aria-controls="shop-filters">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 5h14M6 10h8M8.5 15h3"/></svg>
            فیلترها<?php if ($has_filters) : ?> <b><?php echo esc_html(nasim_fa_digits((int) (bool) $filters['pcat'] + (int) (bool) $filters['ptype'] + (int) ('' !== $filters['q']))); ?></b><?php endif; ?>
          </button>
          <span class="shop-count"><?php echo esc_html(nasim_fa_digits($total)); ?> محصول</span>
          <label class="shop-sort-select">
            <span class="screen-reader-text">مرتب‌سازی</span>
            <select data-shop-sort>
              <?php foreach ($sorts as $key => $label) : ?>
              <option value="<?php echo esc_url(nasim_product_filter_url(['sort' => $key])); ?>" <?php selected($filters['sort'], $key); ?>><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <?php if ($has_filters) : ?>
        <div class="shop-active">
          <?php if ($current) : ?><a href="<?php echo esc_url(nasim_product_filter_url(['pcat' => ''])); ?>"><?php echo esc_html($current->name); ?> <span aria-hidden="true">×</span></a><?php endif; ?>
          <?php if ($filters['ptype']) : ?><a href="<?php echo esc_url(nasim_product_filter_url(['ptype' => ''])); ?>"><?php echo esc_html($types[$filters['ptype']]); ?> <span aria-hidden="true">×</span></a><?php endif; ?>
          <?php if ('' !== $filters['q']) : ?><a href="<?php echo esc_url(nasim_product_filter_url(['q' => ''])); ?>">«<?php echo esc_html($filters['q']); ?>» <span aria-hidden="true">×</span></a><?php endif; ?>
          <a class="is-clear" href="<?php echo esc_url($archive_url); ?>">پاک کردن همه</a>
        </div>
        <?php endif; ?>

        <?php if (have_posts()) : ?>
        <div class="pcard-grid is-shop">
          <?php while (have_posts()) : the_post(); ?>
            <?php get_template_part('template-parts/product/card'); ?>
          <?php endwhile; ?>
        </div>

        <?php the_posts_pagination(['prev_text' => '→', 'next_text' => '←', 'mid_size' => 1]); ?>
        <?php else : ?>
        <div class="account-empty shop-empty">
          <strong>محصولی با این فیلترها پیدا نشد</strong>
          <p>فیلترها را تغییر بده یا همه محصولات را ببین.</p>
          <a class="button button-primary" href="<?php echo esc_url($archive_url); ?>">همه محصولات <span>←</span></a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php nasim_seo_render_hub_links(); ?>
  </main>

<?php
get_footer();
