<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $bundle_id = get_the_ID();
    $courses   = nasim_bundle_courses($bundle_id);
    $stats     = nasim_bundle_stats($bundle_id);
    $price     = nasim_bundle_price($bundle_id);
    $regular   = nasim_bundle_regular_price($bundle_id);
    $savings   = nasim_bundle_savings($bundle_id);
    $plan      = nasim_bundle_plan($bundle_id);
    $features  = nasim_bundle_features($bundle_id) ?: [
        'دسترسی دائمی به همه دوره‌های پکیج',
        'فعال‌سازی فوری بعد از پرداخت + لایسنس مشترک اسپات‌پلیر',
        'پشتیبانی مستقیم از طریق تیکت در پنل کاربری',
    ];
    $badge     = (string) get_post_meta($bundle_id, '_nasim_bundle_badge', true);
    $owned_all = nasim_user_owns_bundle($bundle_id);
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only banner after payment return.
    $paid_id   = absint($_GET['payment'] ?? 0);
    $paid      = $paid_id ? nasim_get_payment($paid_id) : null;
    $paid_ok   = $paid && 'paid' === $paid->status && (int) $paid->user_id === get_current_user_id();
    $can_buy   = $plan && !$owned_all && $courses;
?>

  <main id="main-content" class="flex-1">
    <!-- هیرو پکیج -->
    <section class="relative bg-ink-950 text-cream overflow-hidden">
      <div class="absolute inset-0 dot-grid-light opacity-40" aria-hidden="true"></div>
      <div class="container-site relative pt-8 pb-12 md:pt-12 md:pb-16">
        <nav aria-label="مسیر صفحه" class="flex items-center gap-1.5 text-xs font-semibold text-ink-300">
          <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-cream">خانه</a>
          <span>/</span>
          <a href="<?php echo esc_url(nasim_bundles_archive_url()); ?>" class="hover:text-cream">پکیج‌ها</a>
          <span>/</span>
          <span class="text-cream/90"><?php the_title(); ?></span>
        </nav>

        <?php if ($paid_ok) : ?>
        <div class="mt-6 rounded-2xl border border-mint-400/50 bg-mint-700/25 p-4 flex flex-wrap items-center justify-between gap-4" role="status">
          <div>
            <strong class="block text-mint-200 font-extrabold">پرداخت موفق بود!</strong>
            <span class="text-xs text-cream/90">همه دوره‌های این پکیج در حسابت فعال شد<?php echo $paid->ref_number ? ' · کد پیگیری: ' . esc_html($paid->ref_number) : ''; ?></span>
          </div>
          <a class="btn btn-violet btn-sm rounded-full font-bold" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'courses'])); ?>">رفتن به دوره‌های من</a>
        </div>
        <?php elseif ($paid && 'failed' === $paid->status && (int) $paid->user_id === get_current_user_id()) : ?>
        <div class="mt-6 rounded-2xl border border-red-400/50 bg-red-900/30 p-4 text-sm text-red-100" role="alert">
          <strong>پرداخت ناموفق بود:</strong> <?php echo esc_html($paid->note ?: 'اگر مبلغی کسر شده، حداکثر تا ۷۲ ساعت به حسابت برمی‌گردد.'); ?>
        </div>
        <?php endif; ?>

        <div class="mt-8 grid lg:grid-cols-12 gap-8 items-start">
          <div class="lg:col-span-7 min-w-0">
            <span class="inline-flex items-center gap-2 rounded-full bg-violet-400 text-ink-950 text-xs font-black px-3.5 py-1.5">
              <?php echo esc_html($badge ?: 'پکیج آموزشی'); ?>
            </span>
            <h1 class="mt-4 text-2xl md:text-4xl font-black leading-[1.45]"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
            <p class="mt-4 fa-line text-[15px] text-ink-200 max-w-2xl"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>

            <div class="mt-6 flex flex-wrap gap-2">
              <span class="rounded-full border border-ink-600 bg-ink-900/60 px-3 py-1.5 text-xs font-bold text-ink-200"><?php echo esc_html(nasim_fa_digits($stats['courses']) . ' دوره'); ?></span>
              <?php if ($stats['lessons']) : ?>
              <span class="rounded-full border border-ink-600 bg-ink-900/60 px-3 py-1.5 text-xs font-bold text-ink-200"><?php echo esc_html(nasim_fa_digits($stats['lessons']) . ' جلسه'); ?></span>
              <?php endif; ?>
              <?php if ($stats['seconds']) : ?>
              <span class="rounded-full border border-ink-600 bg-ink-900/60 px-3 py-1.5 text-xs font-bold text-ink-200"><?php echo esc_html(nasim_format_total_duration($stats['seconds'])); ?></span>
              <?php endif; ?>
              <span class="rounded-full border border-ink-600 bg-ink-900/60 px-3 py-1.5 text-xs font-bold text-mint-300">دسترسی دائمی</span>
            </div>
          </div>

          <!-- باکس خرید پکیج -->
          <div class="lg:col-span-5">
            <div class="rounded-3xl border border-ink-700 bg-ink-900 p-6 md:p-7 shadow-lift">
              <?php if ($owned_all) : ?>
                <span class="inline-flex rounded-full bg-mint-100 text-mint-700 px-3 py-1 text-xs font-black">فعال در حساب شما</span>
                <p class="mt-3 text-sm text-ink-200 fa-line">تمام دوره‌های این پکیج برای شما فعال است.</p>
                <a class="btn btn-violet btn-lg w-full rounded-2xl mt-5 font-black" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'courses'])); ?>">ورود به دوره‌های من</a>
              <?php else : ?>
                <div class="flex items-baseline justify-between gap-3">
                  <div>
                    <?php if (!empty($savings['amount'])) : ?>
                    <del class="block text-sm text-ink-400"><?php echo esc_html(nasim_format_price($regular)); ?></del>
                    <?php endif; ?>
                    <strong class="text-2xl font-black text-cream"><?php echo esc_html(nasim_format_price($price)); ?></strong>
                  </div>
                  <?php if (!empty($savings['percent'])) : ?>
                  <span class="rounded-full bg-mint-600 text-white text-xs font-black px-3 py-1"><?php echo esc_html(nasim_fa_digits($savings['percent']) . '٪ تخفیف'); ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($price > 0 && function_exists('nasim_has_installment_gateways') && nasim_has_installment_gateways()) :
                    $inst_names = [];
                    if (class_exists('Nasim_Snapppay_Gateway') && Nasim_Snapppay_Gateway::from_settings()->is_available()) {
                        $inst_names[] = 'اسنپ‌پی';
                    }
                    if (class_exists('Nasim_Digipay_Gateway') && Nasim_Digipay_Gateway::from_settings()->is_available()) {
                        $inst_names[] = 'دیجی‌پی';
                    }
                ?>
                <p class="mt-3 rounded-xl bg-mint-500/15 border border-mint-400/25 px-3 py-2 text-[11.5px] font-bold text-mint-200">
                  امکان پرداخت اقساطی با <?php echo esc_html(implode(' و ', $inst_names)); ?> (از <?php echo esc_html(nasim_format_price((int) ceil($price / 4))); ?> ماهانه)
                </p>
                <?php endif; ?>

                <ul class="mt-5 space-y-2.5 text-xs font-semibold text-ink-200">
                  <?php foreach ($features as $feat) : ?>
                  <li class="flex items-start gap-2">
                    <svg class="size-4 text-violet-300 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    <span><?php echo esc_html($feat); ?></span>
                  </li>
                  <?php endforeach; ?>
                </ul>

                <?php if ($can_buy) : ?>
                  <?php if (is_user_logged_in()) : ?>
                  <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans', 'highlight' => $plan['id'], 'buy' => 'course'])); ?>" class="btn btn-violet btn-lg w-full rounded-2xl mt-6 font-black">
                    ثبت‌نام و پرداخت آنلاین
                  </a>
                  <?php else : ?>
                  <button type="button" data-express-open="bundle-express-<?php echo esc_attr($bundle_id); ?>" class="btn btn-violet btn-lg w-full rounded-2xl mt-6 font-black">
                    ثبت‌نام و خرید سریع پکیج
                  </button>
                  <?php endif; ?>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- لیست دوره‌های داخل پکیج + توضیحات -->
    <div class="container-site py-12 md:py-16">
      <div class="max-w-4xl mx-auto space-y-12">
        <?php if ($courses) : ?>
        <section>
          <h2 class="text-xl md:text-2xl font-extrabold mb-6">دوره‌های شامل این پکیج (<?php echo esc_html(nasim_fa_digits(count($courses))); ?> دوره)</h2>
          <div class="space-y-4">
            <?php foreach ($courses as $course) :
                $cid     = (int) $course->ID;
                $cthumb  = function_exists('nasim_niloo_course_image_url') ? nasim_niloo_course_image_url($cid, 'medium') : get_the_post_thumbnail_url($cid, 'medium');
                $clesson = count(nasim_get_course_lessons($cid));
                $cdur    = nasim_format_total_duration(nasim_course_total_seconds($cid));
                $cowned  = is_user_logged_in() && nasim_user_has_course_access($cid);
            ?>
            <a href="<?php echo esc_url(get_permalink($cid)); ?>" class="group flex flex-col sm:flex-row gap-4 rounded-3xl border border-border bg-card p-4 hover:shadow-lift transition-all">
              <div class="w-full sm:w-44 aspect-video rounded-2xl overflow-hidden bg-ink-900 shrink-0">
                <?php if ($cthumb) : ?>
                <img src="<?php echo esc_url($cthumb); ?>" alt="<?php echo esc_attr($course->post_title); ?>" loading="lazy" class="w-full h-full object-cover" />
                <?php endif; ?>
              </div>
              <div class="flex-1 min-w-0 flex flex-col justify-center">
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-extrabold group-hover:text-violet-700 transition-colors"><?php echo esc_html($course->post_title); ?></h3>
                  <?php if ($cowned) : ?><span class="rounded-full bg-mint-100 text-mint-700 text-xs font-bold px-2.5 py-0.5">دارید</span><?php endif; ?>
                </div>
                <p class="mt-1.5 text-xs font-semibold text-muted-foreground">
                  <?php echo esc_html(implode(' · ', array_filter([$clesson ? nasim_fa_digits($clesson) . ' جلسه' : '', $cdur]))); ?>
                </p>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if (trim(get_the_content())) : ?>
        <section class="rounded-3xl border border-border bg-card p-6 md:p-8 shadow-soft">
          <div class="article-prose">
            <?php the_content(); ?>
          </div>
        </section>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($can_buy && !is_user_logged_in()) : ?>
      <?php get_template_part('template-parts/bundle/express-modal', null, ['bundle_id' => $bundle_id, 'plan' => $plan]); ?>
    <?php endif; ?>
  </main>

<?php
endwhile;

get_footer();
