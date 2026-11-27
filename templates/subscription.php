<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Student Account Panel (/subscription/) + One-off Course/Bundle Checkout
 * Redesigned with Tailwind v4 brand system, preserving 100% of WP LMS/SpotPlayer/Tickets/Payments/Profile logic.
 */

$user_id  = get_current_user_id();
$user     = $user_id ? wp_get_current_user() : null;
$statuses = nasim_payment_statuses();

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only screen state after redirects.
$payment       = isset($_GET['payment']) ? nasim_get_payment(absint($_GET['payment'])) : null;
$error         = sanitize_key(wp_unslash($_GET['nasim_error'] ?? ''));
$tab           = sanitize_key(wp_unslash($_GET['tab'] ?? ''));
$highlight     = absint(wp_unslash($_GET['highlight'] ?? 0));
$course_buy_id = absint(wp_unslash($_GET['course'] ?? 0));
$buy_mode      = sanitize_key(wp_unslash($_GET['buy'] ?? ''));
$welcome       = !empty($_GET['welcome']);
$profile_msg   = sanitize_key(wp_unslash($_GET['profile'] ?? ''));
// phpcs:enable

/* ---------- checkout context ---------- */
$plans              = [];
$is_course_checkout = false;
$purchase_course    = null;

if (!$highlight && $course_buy_id) {
    $linked = (int) get_post_meta($course_buy_id, '_nasim_linked_plan_id', true);
    if ($linked) {
        $highlight = $linked;
    }
}

if ($highlight) {
    $highlighted = nasim_get_plan($highlight);
    if ($highlighted && $highlighted['price'] > 0) {
        $is_purchase_plan = in_array($highlighted['type'] ?? '', ['course', 'spot_course', 'bundle'], true);
        if ($is_purchase_plan || 'course' === $buy_mode || $course_buy_id) {
            $is_course_checkout = true;
            $plans              = [$highlighted];
            $course_buy_id      = $course_buy_id ?: (int) ($highlighted['course_id'] ?? 0);
            if ($course_buy_id) {
                $purchase_course = get_post($course_buy_id);
            } elseif (!empty($highlighted['bundle_id'])) {
                $purchase_course = get_post((int) $highlighted['bundle_id']);
            }
        }
    }
}

$checkout_return = nasim_subscription_url(array_filter([
    'tab'       => 'plans',
    'highlight' => $highlight ?: null,
    'course'    => $course_buy_id ?: null,
    'buy'       => 'course',
]));

if ($payment && (int) $payment->user_id !== $user_id) {
    $payment = null;
}

$errors = [
    'plan'    => 'پلن انتخاب‌شده معتبر نیست.',
    'gateway' => 'درگاه پرداخت هنوز تنظیم نشده است؛ لطفاً بعداً دوباره تلاش کن.',
    'payment' => 'تراکنش پیدا نشد یا نامعتبر است.',
];

$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');

/* ---------- panel data ---------- */
$tabs = $user_id ? nasim_panel_tabs($user_id) : [];

if ($user_id) {
    if ($is_course_checkout) {
        $tab = 'plans';
    } elseif (!isset($tabs[$tab])) {
        $tab = 'overview';
    }
} else {
    $tab = $is_course_checkout ? 'plans' : 'overview';
}

$learning        = $user_id ? nasim_panel_learning_courses($user_id) : [];
$in_progress     = array_values(array_filter($learning, static function ($i) { return $i['progress']['percent'] > 0 && $i['progress']['percent'] < 100; }));
$completed       = array_values(array_filter($learning, static function ($i) { return $i['progress']['percent'] >= 100; }));
$done_lessons    = array_sum(array_map(static function ($i) { return $i['progress']['completed']; }, $learning));
$history         = $user_id ? nasim_get_user_payments($user_id, 30) : [];
$paid_count      = count(array_filter($history, static function ($row) { return 'paid' === $row->status; }));
$ticket_answered = (int) ($tabs['tickets']['badge'] ?? 0);
$mobile          = $user_id ? nasim_get_user_mobile($user_id) : '';
$display         = $user ? ($user->display_name ?: $user->user_login) : '';
$initial         = $display ? mb_substr($display, 0, 1) : '؟';

$render_course_card = static function ($item) {
    $post     = $item['post'];
    $cid      = (int) $post->ID;
    $pct      = (int) $item['progress']['percent'];
    $next     = $item['next'];
    $url      = $next ? nasim_lesson_url($cid, $next['id']) : get_permalink($cid);
    $thumb    = function_exists('nasim_niloo_course_image_url') ? nasim_niloo_course_image_url($cid, 'medium_large') : get_the_post_thumbnail_url($cid, 'medium_large');
    $delivery = function_exists('nasim_course_delivery') ? nasim_course_delivery($cid) : 'lms';
    $has_spot = in_array($delivery, ['spot', 'hybrid'], true);
    $label    = $pct >= 100 ? 'مرور دوره' : ($pct > 0 ? 'ادامه یادگیری' : 'شروع دوره');
    ?>
    <article class="rounded-3xl border border-border bg-card p-5 md:p-6 shadow-soft">
      <div class="flex flex-col sm:flex-row gap-4">
        <a href="<?php echo esc_url(get_permalink($cid)); ?>" class="w-full sm:w-44 aspect-video rounded-2xl overflow-hidden bg-ink-900 shrink-0 block">
          <?php if ($thumb) : ?>
          <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy" class="w-full h-full object-cover" />
          <?php else : ?>
          <div class="w-full h-full grid place-items-center text-cream font-black text-xl"><?php echo esc_html(mb_substr($post->post_title, 0, 1)); ?></div>
          <?php endif; ?>
        </a>
        <div class="flex-1 min-w-0">
          <h3 class="text-[15.5px] font-extrabold leading-6">
            <a href="<?php echo esc_url(get_permalink($cid)); ?>" class="hover:text-violet-700 transition-colors"><?php echo esc_html($post->post_title); ?></a>
          </h3>
          <p class="mt-1.5 text-xs font-bold text-muted-foreground">
            <?php echo esc_html($has_spot ? 'مجازی - ویدیویی (لایسنس اسپات‌پلیر)' : 'مجازی - ویدیویی (پلیر آنلاین آکادمی)'); ?>
          </p>

          <?php if ($item['progress']['total'] > 0) : ?>
          <div class="mt-3.5">
            <div class="flex items-center justify-between text-xs font-bold">
              <span class="text-muted-foreground truncate"><?php echo $next && $pct < 100 ? esc_html('جلسه بعدی: ' . $next['title']) : ($pct >= 100 ? 'تکمیل شده' : esc_html(nasim_fa_digits($item['progress']['completed']) . ' از ' . nasim_fa_digits($item['progress']['total']) . ' جلسه')); ?></span>
              <span class="text-ink-800 shrink-0 ms-2"><?php echo esc_html(nasim_fa_digits($pct)); ?>٪</span>
            </div>
            <div class="meter mt-1.5"><span style="width:<?php echo esc_attr($pct); ?>%"></span></div>
          </div>
          <?php endif; ?>

          <div class="mt-4 flex flex-wrap gap-2">
            <?php if ('spot' !== $delivery) : ?>
            <a href="<?php echo esc_url($url); ?>" class="btn btn-violet btn-sm rounded-full font-bold"><?php echo esc_html($label); ?></a>
            <?php endif; ?>
            <?php if ($has_spot) : ?>
            <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'spot'])); ?>" class="btn btn-outline btn-sm rounded-full font-bold">دریافت لایسنس اسپات‌پلیر</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </article>
    <?php
};

get_header();
?>

<main id="main-content" class="flex-1">
  <div class="container-site py-10 md:py-14">

  <?php if (!$user_id) : ?>
    <!-- مهمان (چک‌اوت) -->
    <div class="max-w-lg mx-auto rounded-3xl border border-border bg-card p-7 md:p-9 shadow-lift text-center">
      <span class="inline-flex rounded-full bg-violet-100 text-violet-800 border border-violet-300 px-3.5 py-1 text-xs font-black">تکمیل ثبت‌نام</span>
      <h1 class="mt-4 text-xl md:text-2xl font-black"><?php echo $purchase_course ? esc_html(get_the_title($purchase_course)) : 'ورود به پنل کاربری'; ?></h1>
      <p class="mt-3 fa-line text-sm text-muted-foreground">برای خرید، وارد حسابت شو یا با شماره موبایل در چند ثانیه حساب بساز؛ بعد از پرداخت دسترسی دائمی می‌گیری.</p>

      <?php if ($error && isset($errors[$error])) : ?>
      <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700" role="alert"><?php echo esc_html($errors[$error]); ?></div>
      <?php endif; ?>

      <?php if ($plans) : $plan = $plans[0]; ?>
      <div class="mt-6 rounded-2xl bg-sand p-4 flex items-center justify-between">
        <span class="text-xs font-bold text-muted-foreground">مبلغ قابل پرداخت</span>
        <strong class="text-lg font-black text-ink-900"><?php echo esc_html(nasim_format_price($plan['price'])); ?></strong>
      </div>
      <?php endif; ?>

      <div class="mt-6 flex flex-col sm:flex-row gap-3">
        <a class="btn btn-violet btn-lg flex-1 rounded-2xl font-black" href="<?php echo esc_url(nasim_auth_url('login', $checkout_return)); ?>">ورود یا ثبت‌نام و ادامه خرید ←</a>
        <a class="btn btn-outline btn-lg rounded-2xl font-bold" href="<?php echo esc_url($purchase_course ? get_permalink($purchase_course) : $courses_url); ?>">بازگشت</a>
      </div>
    </div>

  <?php else : ?>

    <!-- هدر تیره پنل دانشجو -->
    <div class="flex flex-col md:flex-row md:items-center gap-5 justify-between rounded-3xl bg-ink-900 text-cream p-6 md:p-8 relative overflow-hidden">
      <div class="absolute inset-0 dot-grid-light opacity-30" aria-hidden="true"></div>
      <div class="relative flex items-center gap-4">
        <span class="grid place-items-center size-14 rounded-2xl bg-violet-400 text-ink-950 text-xl font-black shrink-0" aria-hidden="true"><?php echo esc_html($initial); ?></span>
        <div class="min-w-0">
          <p class="text-xs font-bold text-ink-300">پنل کاربری دانشجو</p>
          <h1 class="mt-1 text-xl font-extrabold truncate">سلام، <?php echo esc_html($display); ?></h1>
          <?php if ($mobile || $user->user_email) : ?>
          <p class="mt-1 text-xs font-semibold text-violet-300" dir="ltr"><?php echo esc_html($mobile ?: $user->user_email); ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="relative flex flex-wrap gap-2.5">
        <?php if (isset($tabs['tickets'])) : ?>
        <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets', 'ticket' => 'new'])); ?>" class="btn btn-violet btn-sm rounded-full font-bold">+ ثبت تیکت پشتیبانی</a>
        <?php endif; ?>
        <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="btn btn-ghost btn-sm rounded-full font-bold text-cream border border-ink-700 hover:bg-ink-800 hover:text-cream">خروج</a>
      </div>
    </div>

    <!-- نوار تب افقی برای موبایل (UX بهبودیافته) -->
    <nav aria-label="تب‌های پنل در موبایل" class="lg:hidden mt-5 flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
      <?php foreach ($tabs as $key => $item) : ?>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => $key])); ?>" class="shrink-0 inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-extrabold transition-colors <?php echo $key === $tab ? 'bg-ink-900 text-cream' : 'bg-card border border-border text-foreground/80'; ?>">
        <span><?php echo esc_html($item['label']); ?></span>
        <?php if (!empty($item['badge'])) : ?>
        <span class="rounded-full bg-violet-400 text-ink-950 px-1.5 py-0.5 text-[10px] font-black"><?php echo esc_html(nasim_fa_digits($item['badge'])); ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <!-- اعلان‌ها -->
    <?php if ($welcome) : ?>
    <div class="mt-5 rounded-2xl border border-mint-200 bg-mint-100 p-4 text-sm font-bold text-mint-700" role="status">
      حسابت با موفقیت ساخته شد! از کاتالوگ دوره‌ها مسیرت را شروع کن.
    </div>
    <?php endif; ?>
    <?php if ($payment && 'paid' === $payment->status) : ?>
    <div class="mt-5 rounded-2xl border border-mint-200 bg-mint-100 p-4 flex flex-wrap items-center justify-between gap-3 text-sm text-mint-700" role="status">
      <div><strong>پرداخت موفق بود.</strong> «<?php echo esc_html($payment->plan_title); ?>» فعال شد<?php echo $payment->ref_number ? ' · کد پیگیری: <bdi dir="ltr">' . esc_html($payment->ref_number) . '</bdi>' : ''; ?></div>
      <a class="btn btn-default btn-sm rounded-full" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'courses'])); ?>">مشاهده دوره‌ها ←</a>
    </div>
    <?php elseif ($payment && 'failed' === $payment->status) : ?>
    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700" role="alert">
      <strong>پرداخت ناموفق بود:</strong> <?php echo esc_html($payment->note ?: 'اگر مبلغی کسر شده، حداکثر تا ۷۲ ساعت به حسابت برمی‌گردد.'); ?>
    </div>
    <?php elseif ($error && isset($errors[$error])) : ?>
    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700" role="alert"><?php echo esc_html($errors[$error]); ?></div>
    <?php endif; ?>

    <!-- گرید اصلی: محتوا + منوی دسکتاپ -->
    <div class="mt-7 grid lg:grid-cols-12 gap-6 items-start">

      <!-- سایدبار منوی پنل (دسکتاپ) -->
      <aside class="hidden lg:block lg:col-span-3 space-y-4">
        <nav aria-label="منوی پنل کاربری" class="rounded-3xl border border-border bg-card p-3 shadow-soft">
          <ul class="space-y-1">
            <?php foreach ($tabs as $key => $item) : ?>
            <li>
              <a href="<?php echo esc_url(nasim_subscription_url(['tab' => $key])); ?>"
                 class="flex items-center justify-between gap-2 rounded-xl px-3.5 py-3 text-[13.5px] font-bold transition-colors <?php echo $key === $tab ? 'bg-secondary text-ink-900' : 'text-foreground/80 hover:bg-sand'; ?>"
                 <?php echo $key === $tab ? 'aria-current="page"' : ''; ?>>
                <span class="flex items-center gap-2.5 [&_svg]:size-4.5">
                  <?php echo nasim_icon($item['icon']); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                  <span><?php echo esc_html($item['label']); ?></span>
                </span>
                <?php if (!empty($item['badge'])) : ?>
                <span class="rounded-full bg-violet-400 text-ink-950 px-2 py-0.5 text-[11px] font-black"><?php echo esc_html(nasim_fa_digits($item['badge'])); ?></span>
                <?php endif; ?>
              </a>
            </li>
            <?php endforeach; ?>
            <li class="pt-2 mt-2 border-t border-border">
              <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="flex items-center gap-2.5 rounded-xl px-3.5 py-3 text-[13.5px] font-bold text-destructive hover:bg-red-50 transition-colors">
                <span>خروج از حساب</span>
              </a>
            </li>
          </ul>
        </nav>
      </aside>

      <!-- محتوای تب فعال -->
      <div class="lg:col-span-9 space-y-6 min-w-0">

        <?php /* ================= OVERVIEW ================= */ ?>
        <?php if ('overview' === $tab) : ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
          <div class="rounded-2xl border border-border bg-card p-5 shadow-soft">
            <p class="font-black text-xl text-ink-900"><?php echo esc_html(nasim_fa_digits(count($learning))); ?></p>
            <p class="text-xs font-bold text-muted-foreground mt-1">دوره فعال</p>
          </div>
          <div class="rounded-2xl border border-border bg-card p-5 shadow-soft">
            <p class="font-black text-xl text-ink-900"><?php echo esc_html(nasim_fa_digits($done_lessons)); ?></p>
            <p class="text-xs font-bold text-muted-foreground mt-1">جلسه دیده‌شده</p>
          </div>
          <div class="rounded-2xl border border-border bg-card p-5 shadow-soft">
            <p class="font-black text-xl text-ink-900"><?php echo esc_html(nasim_fa_digits(count($completed))); ?></p>
            <p class="text-xs font-bold text-muted-foreground mt-1">دوره تکمیل‌شده</p>
          </div>
          <div class="rounded-2xl border border-border bg-card p-5 shadow-soft">
            <p class="font-black text-xl text-ink-900"><?php echo esc_html(nasim_fa_digits($ticket_answered)); ?></p>
            <p class="text-xs font-bold text-muted-foreground mt-1">پاسخ جدید پشتیبانی</p>
          </div>
        </div>

        <section class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-extrabold">دوره‌های من</h2>
            <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-outline btn-sm rounded-full font-bold">کاتالوگ دوره‌ها</a>
          </div>

          <?php if ($learning) : ?>
            <?php foreach (array_slice($learning, 0, 4) as $item) { $render_course_card($item); } ?>
          <?php else : ?>
          <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
            <p class="font-extrabold text-base">هنوز دوره‌ای شروع نکرده‌ای</p>
            <p class="mt-1.5 text-sm text-muted-foreground">از کاتالوگ دوره‌ها یک دوره انتخاب کن؛ دوره‌های رایگان بلافاصله باز می‌شوند.</p>
            <a class="btn btn-violet rounded-full mt-5 font-bold" href="<?php echo esc_url($courses_url); ?>">مشاهده دوره‌ها</a>
          </div>
          <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php /* ================= COURSES ================= */ ?>
        <?php if ('courses' === $tab) : ?>
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-extrabold">همه دوره‌های من (<?php echo esc_html(nasim_fa_digits(count($learning))); ?>)</h2>
            <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-outline btn-sm rounded-full font-bold">افزودن دوره جدید</a>
          </div>
          <?php if ($learning) : ?>
          <div class="space-y-4">
            <?php foreach ($learning as $item) { $render_course_card($item); } ?>
          </div>
          <?php else : ?>
          <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
            <p class="font-extrabold text-base">هنوز دوره‌ای در حسابت فعال نیست</p>
            <a class="btn btn-violet rounded-full mt-5 font-bold" href="<?php echo esc_url($courses_url); ?>">مشاهده دوره‌ها</a>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php /* ================= SPOTPLAYER ================= */ ?>
        <?php if ('spot' === $tab && function_exists('nasim_spot_render_account_tab')) : ?>
        <div class="rounded-3xl border border-border bg-card p-6 md:p-8 shadow-soft">
          <?php nasim_spot_render_account_tab(); ?>
        </div>
        <?php endif; ?>

        <?php /* ================= TICKETS ================= */ ?>
        <?php if ('tickets' === $tab && function_exists('nasim_ticket_counts')) : ?>
        <div class="rounded-3xl border border-border bg-card p-6 md:p-8 shadow-soft">
          <?php get_template_part('template-parts/account/tickets', null, ['user_id' => $user_id, 'is_active' => true]); ?>
        </div>
        <?php endif; ?>

        <?php /* ================= PAYMENTS ================= */ ?>
        <?php if ('payments' === $tab) : ?>
          <h2 class="text-lg font-extrabold">سوابق تراکنش‌ها</h2>
          <?php if ($history) : ?>
          <div class="rounded-3xl border border-border bg-card overflow-hidden shadow-soft">
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-start">
                <thead class="bg-sand text-xs font-extrabold text-muted-foreground border-b border-border">
                  <tr>
                    <th class="px-5 py-3.5 text-start">شرح</th>
                    <th class="px-5 py-3.5 text-start">تاریخ</th>
                    <th class="px-5 py-3.5 text-start">مبلغ</th>
                    <th class="px-5 py-3.5 text-start">وضعیت</th>
                    <th class="px-5 py-3.5 text-start">کد پیگیری</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <?php foreach ($history as $row) :
                      $tone = nasim_panel_status_tone($row->status);
                      $badge_cls = 'ok' === $tone ? 'bg-mint-100 text-mint-700' : ('err' === $tone ? 'bg-red-100 text-red-700' : 'bg-violet-100 text-violet-800');
                  ?>
                  <tr>
                    <td class="px-5 py-4 font-bold"><?php echo esc_html($row->plan_title); ?></td>
                    <td class="px-5 py-4 text-xs text-muted-foreground"><?php echo esc_html(nasim_jdate(nasim_payment_time($row->created_at), true)); ?></td>
                    <td class="px-5 py-4 font-bold"><?php echo (int) $row->amount ? esc_html(nasim_format_price((int) $row->amount / 10)) : '—'; ?></td>
                    <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold <?php echo esc_attr($badge_cls); ?>"><?php echo esc_html($statuses[$row->status] ?? $row->status); ?></span></td>
                    <td class="px-5 py-4 font-mono text-xs" dir="ltr"><?php echo esc_html($row->ref_number ?: '—'); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <p class="text-xs font-semibold text-muted-foreground">تراکنش‌های موفق: <?php echo esc_html(nasim_fa_digits($paid_count)); ?></p>
          <?php else : ?>
          <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
            <p class="font-extrabold text-base">هنوز تراکنشی ثبت نشده است.</p>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php /* ================= PROFILE ================= */ ?>
        <?php if ('profile' === $tab) :
            $messages = nasim_panel_profile_messages();
        ?>
          <?php if (isset($messages[$profile_msg])) : ?>
          <div class="rounded-2xl border p-4 text-sm font-bold <?php echo 'ok' === $messages[$profile_msg][0] ? 'border-mint-200 bg-mint-100 text-mint-700' : 'border-red-200 bg-red-50 text-red-700'; ?>" role="status">
            <?php echo esc_html($messages[$profile_msg][1]); ?>
          </div>
          <?php endif; ?>

          <div class="grid md:grid-cols-2 gap-6">
            <form class="rounded-3xl border border-border bg-card p-6 shadow-soft space-y-4" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <h2 class="text-base font-extrabold">اطلاعات شخصی</h2>
              <input type="hidden" name="action" value="nasim_profile_update" />
              <input type="hidden" name="section" value="info" />
              <?php wp_nonce_field('nasim_profile_update'); ?>

              <div>
                <label for="profName" class="field-label">نام و نام خانوادگی</label>
                <input id="profName" type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" autocomplete="name" required maxlength="60" class="field" />
              </div>
              <div>
                <label for="profMobile" class="field-label">شماره موبایل</label>
                <input id="profMobile" type="tel" value="<?php echo esc_attr($mobile ?: '—'); ?>" dir="ltr" readonly class="field bg-sand/70" />
                <p class="mt-1 text-[11.5px] text-muted-foreground">برای تغییر شماره موبایل، یک تیکت پشتیبانی ثبت کن.</p>
              </div>
              <div>
                <label for="profEmail" class="field-label">ایمیل (اختیاری)</label>
                <input id="profEmail" type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" dir="ltr" autocomplete="email" placeholder="you@example.com" class="field" />
              </div>
              <button type="submit" class="btn btn-violet rounded-xl font-bold w-full">ذخیره تغییرات</button>
            </form>

            <form class="rounded-3xl border border-border bg-card p-6 shadow-soft space-y-4" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <h2 class="text-base font-extrabold">تنظیم یا تغییر رمز عبور</h2>
              <p class="text-xs text-muted-foreground fa-line">ورود با کد پیامکی همیشه فعال است؛ اگر می‌خواهی با رمز عبور هم وارد شوی اینجا رمز بساز.</p>
              <input type="hidden" name="action" value="nasim_profile_update" />
              <input type="hidden" name="section" value="password" />
              <?php wp_nonce_field('nasim_profile_update'); ?>

              <div>
                <label for="profPass1" class="field-label">رمز عبور جدید (حداقل ۸ کاراکتر)</label>
                <input id="profPass1" type="password" name="new_password" minlength="8" autocomplete="new-password" dir="ltr" required class="field" />
              </div>
              <div>
                <label for="profPass2" class="field-label">تکرار رمز عبور جدید</label>
                <input id="profPass2" type="password" name="confirm_password" minlength="8" autocomplete="new-password" dir="ltr" required class="field" />
              </div>
              <button type="submit" class="btn btn-outline rounded-xl font-bold w-full">ذخیره رمز عبور</button>
            </form>
          </div>
        <?php endif; ?>

        <?php /* ================= CHECKOUT ================= */ ?>
        <?php if ($is_course_checkout && 'plans' === $tab) : ?>
          <?php if ($plans) : $plan = $plans[0];
              $is_bundle = 'bundle' === ($plan['type'] ?? '');
              $features  = $plan['features'] ?: ($is_bundle
                  ? ['دسترسی دائمی به همه دوره‌های پکیج', 'فعال‌سازی فوری بعد از پرداخت', 'پشتیبانی مستقیم در پنل کاربری']
                  : ['دسترسی دائمی به دوره', 'پشتیبانی مستقیم آکادمی', 'صدور فوری لایسنس اسپات‌پلیر در صورت نیاز']);
          ?>
          <div class="grid md:grid-cols-12 gap-6 items-start">
            <div class="md:col-span-7 rounded-3xl border border-border bg-card p-6 md:p-7 shadow-soft">
              <span class="inline-flex rounded-full bg-violet-100 text-violet-800 px-3 py-1 text-xs font-bold"><?php echo $is_bundle ? 'پکیج آموزشی' : 'دوره آموزشی'; ?></span>
              <h2 class="mt-3 text-xl font-black"><?php echo esc_html($purchase_course ? get_the_title($purchase_course) : $plan['title']); ?></h2>
              <ul class="mt-5 space-y-2.5 text-sm font-semibold text-foreground/85">
                <?php foreach ($features as $feature) : ?>
                <li class="flex items-start gap-2">
                  <svg class="size-4.5 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                  <span><?php echo esc_html($feature); ?></span>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="md:col-span-5 rounded-3xl border border-ink-800 bg-ink-900 text-cream p-6 md:p-7 shadow-lift">
              <div class="flex items-center justify-between text-sm text-ink-300">
                <span>مبلغ دوره</span>
                <span><?php echo esc_html(nasim_format_price($plan['price'])); ?></span>
              </div>
              <div class="mt-3 pt-3 border-t border-ink-800 flex items-center justify-between">
                <span class="text-sm font-bold">قابل پرداخت</span>
                <strong class="text-xl font-black text-violet-300"><?php echo esc_html(nasim_format_price($plan['price'])); ?></strong>
              </div>

              <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mt-6 space-y-4">
                <input type="hidden" name="action" value="nasim_subscribe" />
                <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan['id']); ?>" />
                <?php wp_nonce_field('nasim_subscribe'); ?>
                <?php get_template_part('template-parts/checkout/gateway-selector', null, ['amount' => (int) $plan['price'], 'theme' => 'dark']); ?>
                <button type="submit" class="btn btn-violet btn-lg w-full rounded-2xl font-black">پرداخت و فعال‌سازی فوری ←</button>
              </form>
              <p class="mt-3 text-[11.5px] text-ink-300 text-center">
                <?php if (function_exists('nasim_has_installment_gateways') && nasim_has_installment_gateways()) : ?>
                  پرداخت امن آنلاین و اقساطی با فعال‌سازی فوری دوره
                <?php else : ?>
                  پرداخت امن از درگاه <?php echo esc_html(nasim_payment_gateway_labels()[nasim_payment_gateway_id()] ?? 'بانکی'); ?>
                <?php endif; ?>
              </p>
            </div>
          </div>
          <?php else : ?>
          <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
            <strong>پلن خرید پیدا نشد.</strong>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php do_action('nasim_account_tab_content'); ?>
      </div>
    </div>

  <?php endif; ?>
  </div>
</main>

<?php
get_footer();
