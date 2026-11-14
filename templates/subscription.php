<?php

if (!defined('ABSPATH')) {
    exit;
}

$user_id   = get_current_user_id();
$user      = $user_id ? wp_get_current_user() : null;
$statuses  = nasim_payment_statuses();
$history   = $user_id ? nasim_get_user_payments($user_id, 20) : [];
$owned     = $user_id && function_exists('nasim_user_owned_courses') ? nasim_user_owned_courses($user_id) : [];
$spot_n    = 0;
if ($user_id && function_exists('nasim_user_spot_licenses')) {
    $spot_n = count(nasim_user_spot_licenses($user_id));
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only notices after redirects.
$payment       = isset($_GET['payment']) ? nasim_get_payment(absint($_GET['payment'])) : null;
$error         = sanitize_key(wp_unslash($_GET['nasim_error'] ?? ''));
$tab           = sanitize_key(wp_unslash($_GET['tab'] ?? ''));
$highlight     = absint(wp_unslash($_GET['highlight'] ?? 0));
$course_buy_id = absint(wp_unslash($_GET['course'] ?? 0));
$buy_mode      = sanitize_key(wp_unslash($_GET['buy'] ?? ''));
// phpcs:enable

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
        $is_purchase_plan = in_array($highlighted['type'] ?? '', ['course', 'spot_course'], true);
        if ($is_purchase_plan || 'course' === $buy_mode || $course_buy_id) {
            $is_course_checkout = true;
            $plans              = [$highlighted];
            $course_buy_id      = $course_buy_id ?: (int) ($highlighted['course_id'] ?? 0);
            if ($course_buy_id) {
                $purchase_course = get_post($course_buy_id);
            }
        }
    }
}

$checkout_return = $is_course_checkout
    ? nasim_subscription_url(array_filter([
        'tab'       => 'plans',
        'highlight' => $highlight,
        'course'    => $course_buy_id ?: null,
        'buy'       => 'course',
    ]))
    : nasim_subscription_url(['tab' => 'overview']);

if ($payment && (int) $payment->user_id !== $user_id) {
    $payment = null;
}

$errors = [
    'plan'    => 'پلن انتخاب‌شده معتبر نیست.',
    'gateway' => 'درگاه پرداخت هنوز تنظیم نشده است؛ لطفاً بعداً دوباره تلاش کن.',
    'payment' => 'تراکنش پیدا نشد یا نامعتبر است.',
];

$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$course_count = (int) (wp_count_posts('nasim_course')->publish ?? 0);

if ($user_id) {
    $allowed_tabs = ['overview', 'courses', 'spot', 'tickets', 'plans', 'payments'];
    if ($is_course_checkout) {
        $tab = 'plans';
    } elseif (!$tab || !in_array($tab, $allowed_tabs, true) || 'license' === $tab) {
        $tab = 'overview';
    }
} else {
    $tab = $is_course_checkout ? 'plans' : 'overview';
}

$initial = $user ? mb_substr($user->display_name ?: $user->user_login, 0, 1) : '؟';
$ticket_counts   = ($user_id && function_exists('nasim_ticket_counts')) ? nasim_ticket_counts($user_id) : [];
$ticket_answered = (int) ($ticket_counts['answered'] ?? 0);

get_header();
?>

  <main class="container-wide account-page<?php echo $user_id ? ' is-dashboard' : ' is-guest'; ?> is-tab-<?php echo esc_attr($tab); ?>">

    <?php if ($user_id) : ?>
    <section class="account-hero">
      <div class="account-hero-glow" aria-hidden="true"></div>
      <div class="account-hero-main">
        <div class="account-avatar" aria-hidden="true"><?php echo esc_html($initial); ?></div>
        <div class="account-hero-copy">
          <span class="eyebrow"><span class="eyebrow-line"></span> پنل کاربری آکادمی</span>
          <h1>سلام <?php echo esc_html($user->display_name); ?></h1>
          <p>دوره‌های خریداری‌شده، توکن اسپات‌پلیر، پشتیبانی و سوابق پرداختت اینجاست.</p>
        </div>
        <div class="account-hero-actions">
          <span class="account-pill is-live"><?php echo esc_html(nasim_fa_digits(count($owned))); ?> دوره فعال</span>
          <a class="button button-outline button-small" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">خروج</a>
        </div>
      </div>

      <div class="account-stats">
        <article class="account-stat">
          <span class="account-stat-label">دوره‌های من</span>
          <strong><?php echo esc_html(nasim_fa_digits(count($owned))); ?></strong>
          <small>خریداری‌شده و فعال</small>
        </article>
        <article class="account-stat">
          <span class="account-stat-label">لایسنس اسپات</span>
          <strong><?php echo esc_html(nasim_fa_digits($spot_n)); ?></strong>
          <small>توکن آماده در پنل</small>
        </article>
        <article class="account-stat">
          <span class="account-stat-label">پشتیبانی</span>
          <strong><?php echo $ticket_answered ? esc_html(nasim_fa_digits($ticket_answered)) . ' پاسخ' : '—'; ?></strong>
          <small><?php echo $ticket_answered ? 'پاسخ جدید داری' : 'تیکت بساز'; ?></small>
        </article>
        <article class="account-stat">
          <span class="account-stat-label">کاتالوگ آکادمی</span>
          <strong><?php echo esc_html(nasim_fa_digits($course_count)); ?></strong>
          <small>دوره منتشرشده</small>
        </article>
      </div>
    </section>

    <nav class="account-tabs" aria-label="بخش‌های پنل">
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'overview'])); ?>" class="<?php echo 'overview' === $tab ? 'is-active' : ''; ?>">خلاصه</a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'courses'])); ?>" class="<?php echo 'courses' === $tab ? 'is-active' : ''; ?>">دوره‌های من</a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'spot'])); ?>" class="<?php echo 'spot' === $tab ? 'is-active' : ''; ?>">اسپات پلیر</a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets'])); ?>" class="<?php echo 'tickets' === $tab ? 'is-active' : ''; ?>">پشتیبانی<?php if ($ticket_answered) : ?> <b class="account-tab-badge" title="پاسخ جدید"><?php echo esc_html(nasim_fa_digits($ticket_answered)); ?></b><?php endif; ?></a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'payments'])); ?>" class="<?php echo 'payments' === $tab ? 'is-active' : ''; ?>">تراکنش‌ها</a>
      <?php if ($is_course_checkout) : ?>
      <a href="<?php echo esc_url($checkout_return); ?>" class="<?php echo 'plans' === $tab ? 'is-active' : ''; ?>">خرید دوره</a>
      <?php endif; ?>
    </nav>
    <?php else : ?>
    <section class="account-guest-hero">
      <div class="account-guest-copy">
        <?php if ($is_course_checkout) : ?>
        <span class="eyebrow"><span class="eyebrow-line"></span> خرید دوره</span>
        <h1>خرید دوره<?php echo $purchase_course ? ': <span>' . esc_html(get_the_title($purchase_course)) . '</span>' : ''; ?></h1>
        <p>بعد از ورود، همین دوره را یک‌بار می‌خری و دسترسی دائمی می‌گیری.</p>
        <div class="account-guest-actions">
          <a class="button button-primary button-large" href="<?php echo esc_url(nasim_auth_url('login', $checkout_return)); ?>">ورود و خرید دوره <span>←</span></a>
          <a class="button button-outline button-large" href="<?php echo esc_url(nasim_auth_url('register', $checkout_return)); ?>">ثبت‌نام و خرید</a>
        </div>
        <?php else : ?>
        <span class="eyebrow"><span class="eyebrow-line"></span> پنل کاربری</span>
        <h1>دوره‌ها و لایسنس اسپات‌پلیرت اینجاست</h1>
        <p>وارد شو تا دوره‌های خریداری‌شده، توکن اسپات و پشتیبانی را ببینی.</p>
        <div class="account-guest-actions">
          <a class="button button-primary button-large" href="<?php echo esc_url(nasim_auth_url('login', nasim_subscription_url())); ?>">ورود به پنل <span>←</span></a>
          <a class="button button-outline button-large" href="<?php echo esc_url($courses_url); ?>">مشاهده دوره‌ها</a>
        </div>
        <?php endif; ?>
      </div>
      <ul class="account-benefit-list">
        <li><span>▶</span><div><strong>دسترسی دائمی</strong><small>یک‌بار خرید برای هر دوره</small></div></li>
        <li><span>◈</span><div><strong>توکن اسپات‌پلیر</strong><small>کلید لایسنس داخل پنل</small></div></li>
        <li><span>✓</span><div><strong>دانلود اپ اسپات</strong><small>لینک رسمی برنامه</small></div></li>
        <li><span>↻</span><div><strong>پرداخت امن</strong><small>از طریق درگاه زیبال</small></div></li>
      </ul>
    </section>
    <?php endif; ?>

    <?php if ($payment && 'paid' === $payment->status) : ?>
      <div class="lms-notice is-success" role="status">
        <strong>پرداخت موفق بود</strong>
        <span>دوره‌ات فعال شد<?php echo $payment->ref_number ? ' · کد: ' . esc_html($payment->ref_number) : ''; ?></span>
        <a class="button button-primary button-small" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'courses'])); ?>">دوره‌های من</a>
        <a class="button button-outline button-small" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'spot'])); ?>">اسپات پلیر</a>
      </div>
    <?php elseif ($payment && 'failed' === $payment->status) : ?>
      <div class="lms-notice is-error" role="alert">
        <strong>پرداخت ناموفق بود</strong>
        <span><?php echo esc_html($payment->note ?: 'در صورت کسر وجه، مبلغ حداکثر تا ۷۲ ساعت برمی‌گردد.'); ?></span>
      </div>
    <?php elseif ($error && isset($errors[$error])) : ?>
      <div class="lms-notice is-error" role="alert"><strong><?php echo esc_html($errors[$error]); ?></strong></div>
    <?php endif; ?>

    <?php if ($user_id && 'overview' === $tab) : ?>
    <section class="account-panel" id="overview">
      <div class="account-panel-grid">
        <div class="account-card account-status-card<?php echo $owned ? ' is-active' : ''; ?>">
          <div class="account-card-head">
            <h2>وضعیت یادگیری</h2>
            <?php if ($owned) : ?><span class="account-tag is-live">فعال</span><?php endif; ?>
          </div>
          <?php if ($owned) : ?>
          <p class="account-lead"><?php echo esc_html(nasim_fa_digits(count($owned))); ?> دوره خریداری‌شده داری. از «دوره‌های من» ادامه بده یا توکن اسپات را بردار.</p>
          <div class="account-card-actions">
            <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'courses'])); ?>">دوره‌های من <span>←</span></a>
            <a class="button button-outline" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'spot'])); ?>">اسپات پلیر</a>
          </div>
          <?php else : ?>
          <p class="account-lead">هنوز دوره‌ای نخریده‌ای. از کاتالوگ شروع کن.</p>
          <div class="account-card-actions">
            <a class="button button-primary" href="<?php echo esc_url($courses_url); ?>">مشاهده دوره‌ها <span>←</span></a>
          </div>
          <?php endif; ?>
        </div>

        <div class="account-card">
          <div class="account-card-head"><h2>میانبرها</h2></div>
          <div class="account-quick-links">
            <a href="<?php echo esc_url($courses_url); ?>"><span>▶</span><strong>کاتالوگ دوره‌ها</strong><small>خرید دوره جدید</small></a>
            <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'spot'])); ?>"><span>◈</span><strong>اسپات پلیر</strong><small>توکن و دانلود اپ</small></a>
            <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets'])); ?>"><span>✉</span><strong>پشتیبانی</strong><small><?php echo $ticket_answered ? esc_html(nasim_fa_digits($ticket_answered)) . ' پاسخ جدید' : 'ثبت تیکت'; ?></small></a>
            <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'payments'])); ?>"><span>✓</span><strong>تراکنش‌ها</strong><small>سوابق پرداخت</small></a>
          </div>
        </div>

        <?php if ($owned) : ?>
        <div class="account-card account-downloads-preview">
          <div class="account-card-head">
            <h2>آخرین دوره‌ها</h2>
            <a class="text-link" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'courses'])); ?>">همه ←</a>
          </div>
          <ul class="account-mini-list">
            <?php foreach (array_slice($owned, 0, 4) as $course) :
                $cid = (int) $course->ID;
                $next = function_exists('nasim_next_lesson_to_watch') ? nasim_next_lesson_to_watch($cid) : null;
                $url  = $next && function_exists('nasim_lesson_url')
                    ? nasim_lesson_url($cid, $next['id'])
                    : get_permalink($cid);
                ?>
            <li>
              <div>
                <strong><a href="<?php echo esc_url(get_permalink($cid)); ?>"><?php echo esc_html(get_the_title($cid)); ?></a></strong>
                <small><?php echo $next ? 'ادامه: ' . esc_html($next['title']) : 'مشاهده دوره'; ?></small>
              </div>
              <a class="button button-primary button-small" href="<?php echo esc_url($url); ?>">ادامه</a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($user_id && 'courses' === $tab) : ?>
    <section class="account-panel" id="courses">
      <div class="account-section-intro">
        <div>
          <span class="eyebrow">یادگیری</span>
          <h2>دوره‌های من</h2>
        </div>
        <p>دوره‌هایی که خریدی یا به حسابت اضافه شده‌اند.</p>
      </div>
      <?php if ($owned) : ?>
      <div class="home-course-grid account-course-grid">
        <?php
        global $post;
        foreach ($owned as $post) :
            setup_postdata($post);
            get_template_part('template-parts/course/card-catalog');
        endforeach;
        wp_reset_postdata();
        ?>
      </div>
      <?php else : ?>
      <div class="account-empty">
        <strong>هنوز دوره‌ای نداری</strong>
        <p>از کاتالوگ یک دوره بخر تا اینجا نمایش داده شود.</p>
        <a class="button button-primary" href="<?php echo esc_url($courses_url); ?>">مشاهده دوره‌ها <span>←</span></a>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($user_id && 'tickets' === $tab && function_exists('nasim_ticket_counts')) : ?>
      <?php get_template_part('template-parts/account/tickets', null, ['user_id' => $user_id, 'is_active' => true]); ?>
    <?php endif; ?>

    <?php if ($is_course_checkout && ((!$user_id) || 'plans' === $tab)) : ?>
    <section class="account-panel" id="plans">
      <?php if ($user_id) : ?>
      <div class="account-section-intro">
        <div>
          <span class="eyebrow">خرید دوره</span>
          <h2>تکمیل خرید</h2>
        </div>
        <p>این پرداخت فقط همین دوره را باز می‌کند و دسترسی دائمی می‌دهد.</p>
      </div>
      <?php endif; ?>

      <?php if ($plans) : ?>
      <div class="plan-grid plan-grid--purchase">
        <?php foreach ($plans as $plan) :
            $features = $plan['features'] ?: [
                'دسترسی دائمی به همین دوره',
                'پشتیبانی آکادمی',
                'توکن اسپات‌پلیر در صورت نیاز',
            ];
            $login_return = nasim_subscription_url(array_filter([
                'tab'       => 'plans',
                'highlight' => $plan['id'],
                'course'    => (int) ($plan['course_id'] ?? 0) ?: null,
                'buy'       => 'course',
            ]));
            ?>
        <article class="plan-card is-course-purchase<?php echo $plan['featured'] ? ' is-featured' : ''; ?>">
          <?php if ($plan['featured']) : ?><span class="plan-badge">پیشنهاد ویژه</span><?php endif; ?>
          <span class="plan-duration">خرید یک‌باره</span>
          <h3><?php echo esc_html($plan['title']); ?></h3>
          <div class="plan-price">
            <strong><?php echo esc_html(nasim_fa_digits(number_format($plan['price']))); ?></strong>
            <small>تومان</small>
          </div>
          <ul>
            <?php foreach ($features as $feature) : ?>
            <li><span>✓</span><?php echo esc_html($feature); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($user_id) : ?>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="nasim_subscribe">
            <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan['id']); ?>">
            <?php wp_nonce_field('nasim_subscribe'); ?>
            <button type="submit" class="button button-primary">پرداخت و خرید دوره <span>←</span></button>
          </form>
          <?php else : ?>
          <a class="button button-primary" href="<?php echo esc_url(nasim_auth_url('login', $login_return)); ?>">ورود و خرید دوره <span>←</span></a>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
      <p class="plan-note">پرداخت امن با زیبال · یک‌بار خرید · دسترسی دائمی به همین دوره</p>
      <?php else : ?>
      <p class="course-empty">پلن خرید این دوره پیدا نشد.</p>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($user_id && 'payments' === $tab) : ?>
    <section class="account-panel" id="payments">
      <div class="account-section-intro">
        <div>
          <span class="eyebrow">مالی</span>
          <h2>سوابق پرداخت</h2>
        </div>
        <p>تراکنش‌های خرید دوره.</p>
      </div>
      <?php if ($history) : ?>
      <div class="payment-table-wrap">
        <table class="payment-table">
          <thead><tr><th>تاریخ</th><th>شرح</th><th>مبلغ</th><th>وضعیت</th><th>کد پیگیری</th></tr></thead>
          <tbody>
          <?php foreach ($history as $row) : ?>
            <tr>
              <td><?php echo esc_html(nasim_jdate(nasim_payment_time($row->created_at), true)); ?></td>
              <td><?php echo esc_html($row->plan_title); ?></td>
              <td><?php echo esc_html(nasim_format_price((int) $row->amount / 10)); ?></td>
              <td><span class="payment-status is-<?php echo esc_attr($row->status); ?>"><?php echo esc_html($statuses[$row->status] ?? $row->status); ?></span></td>
              <td dir="ltr"><?php echo esc_html($row->ref_number ?: '—'); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else : ?>
      <div class="account-empty">
        <strong>هنوز تراکنشی نداری</strong>
        <p>بعد از خرید دوره، اینجا ثبت می‌شود.</p>
        <a class="button button-primary" href="<?php echo esc_url($courses_url); ?>">رفتن به دوره‌ها <span>←</span></a>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php do_action('nasim_account_tab_content'); ?>

  </main>

<?php
get_footer();
