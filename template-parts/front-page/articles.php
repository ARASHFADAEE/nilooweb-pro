<?php

if (!defined('ABSPATH')) {
    exit;
}

$blog_url  = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');
$radio_cat = get_category_by_slug(nasim_mod('radio_cat') ?: 'radionil');

$query_args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'no_found_rows'  => true,
];
if ($radio_cat && !is_wp_error($radio_cat)) {
    $query_args['category__not_in'] = [(int) $radio_cat->term_id];
}

$posts = get_posts($query_args);
if (!$posts) {
    return;
}
?>

<section id="articles" class="bg-cream/60 border-t border-border" aria-label="آخرین مقالات آموزشی">
  <div class="container-site py-16 md:py-20">
    <div class="reveal flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
      <div>
        <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">
          <?php echo esc_html(nasim_mod('articles_eyebrow') ?: 'مجله آموزشی'); ?>
        </span>
        <h2 class="mt-4 text-2xl md:text-3xl font-black">
          <?php echo esc_html(nasim_mod('articles_title') ?: 'جدیدترین'); ?>
          <?php if (nasim_mod('articles_title_highlight')) : ?>
          <span class="mark-violet"><?php echo esc_html(nasim_mod('articles_title_highlight')); ?></span>
          <?php endif; ?>
        </h2>
      </div>
      <a href="<?php echo esc_url($blog_url); ?>" class="btn btn-outline rounded-full font-bold self-start md:self-auto">
        همه مقالات
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
      </a>
    </div>

    <div class="grid md:grid-cols-3 gap-5 md:gap-6">
      <?php foreach ($posts as $i => $post_item) :
          $cats  = get_the_category($post_item->ID);
          $cat   = !empty($cats) ? $cats[0]->name : 'آموزش';
          $thumb = get_the_post_thumbnail_url($post_item->ID, 'medium_large');
          $mins  = function_exists('nasim_reading_time') ? nasim_reading_time($post_item->ID) : 3;
      ?>
      <a href="<?php echo esc_url(get_permalink($post_item->ID)); ?>" class="reveal group card-hover flex flex-col rounded-3xl border border-border bg-card overflow-hidden" style="--reveal-delay:<?php echo esc_attr($i * 70); ?>ms">
        <div class="relative aspect-video bg-ink-900 overflow-hidden">
          <?php if ($thumb) : ?>
          <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($post_item->post_title); ?>" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]" />
          <?php else : ?>
          <div class="absolute inset-0 dot-grid-light opacity-40" aria-hidden="true"></div>
          <div class="absolute inset-0 grid place-items-center text-cream/90 font-black text-lg px-4 text-center"><?php echo esc_html($cat); ?></div>
          <?php endif; ?>
        </div>

        <div class="p-5 md:p-6 flex flex-col flex-1">
          <div class="flex items-center gap-2 text-xs font-bold">
            <span class="rounded-full bg-violet-100 border border-violet-200 px-2.5 py-0.5 text-violet-800"><?php echo esc_html($cat); ?></span>
            <span class="text-muted-foreground"><?php echo esc_html(nasim_fa_digits($mins)); ?> دقیقه مطالعه</span>
          </div>
          <h3 class="mt-3 text-[16px] font-extrabold leading-7 group-hover:text-violet-700 transition-colors line-clamp-2">
            <?php echo esc_html($post_item->post_title); ?>
          </h3>
          <p class="mt-2 fa-line text-[13.5px] text-muted-foreground line-clamp-2 flex-1">
            <?php echo esc_html(wp_strip_all_tags(get_the_excerpt($post_item->ID))); ?>
          </p>
          <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-ink-700 group-hover:gap-2.5 transition-all">
            ادامه مطلب
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
