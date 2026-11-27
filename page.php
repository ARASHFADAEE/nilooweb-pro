<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    $is_builder = function_exists('nasim_is_built_with_elementor') && nasim_is_built_with_elementor(get_the_ID());
?>

  <main id="main-content" class="flex-1">
    <?php if ($is_builder) : ?>
      <article <?php post_class('w-full'); ?>>
        <div class="entry-content article-prose">
          <?php the_content(); ?>
        </div>
      </article>
    <?php else : ?>
      <section class="relative overflow-hidden border-b border-border">
        <div class="absolute inset-0 dot-grid opacity-50" aria-hidden="true"></div>
        <div class="container-site relative pt-10 pb-10 md:pt-14 md:pb-12">
          <nav aria-label="مسیر صفحه" class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground mb-4">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-foreground">خانه</a>
            <span>/</span>
            <span class="text-foreground/80"><?php the_title(); ?></span>
          </nav>
          <h1 class="text-2xl md:text-4xl font-black"><?php the_title(); ?></h1>
        </div>
      </section>

      <div class="container-site py-10 md:py-14">
        <article <?php post_class('max-w-4xl mx-auto'); ?>>
          <?php if (has_post_thumbnail()) : ?>
          <figure class="mb-8 rounded-3xl overflow-hidden border border-border shadow-soft">
            <?php the_post_thumbnail('large', ['class' => 'w-full h-auto object-cover', 'alt' => the_title_attribute(['echo' => false])]); ?>
          </figure>
          <?php endif; ?>

          <div class="rounded-3xl border border-border bg-card p-6 md:p-10 shadow-soft">
            <div class="article-prose">
              <?php the_content(); ?>
              <?php wp_link_pages(['before' => '<nav class="mt-6 flex gap-2">', 'after' => '</nav>']); ?>
            </div>
          </div>

          <?php if (comments_open() || get_comments_number()) : ?>
          <div class="mt-10">
            <?php comments_template(); ?>
          </div>
          <?php endif; ?>
        </article>
      </div>
    <?php endif; ?>
  </main>

<?php
endwhile;

get_footer();
