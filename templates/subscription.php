<?php

if(!defined('ABSPATH')) exit;

$user_id    = get_current_user_id();
$user       = $user_id ? wp_get_current_user() : null;
$plans      = nasim_get_plans('subscription');
$is_active  = nasim_has_active_subscription();
$expires_at = nasim_subscription_expires();
$days_left  = nasim_subscription_days_left();
$statuses   = nasim_payment_statuses();
$history    = $user_id ? nasim_get_user_payments($user_id, 20) : [];

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only notices after redirects.
$payment       = isset($_GET['payment']) ? nasim_get_payment(absint($_GET['payment'])) : null;
$error         = sanitize_key(wp_unslash($_GET['nasim_error'] ?? ''));
$tab           = sanitize_key(wp_unslash($_GET['tab'] ?? ''));
$highlight     = absint(wp_unslash($_GET['highlight'] ?? 0));
$course_buy_id = absint(wp_unslash($_GET['course'] ?? 0));
$buy_mode      = sanitize_key(wp_unslash($_GET['buy'] ?? ''));
// phpcs:enable

$is_course_checkout = false;
$purchase_course    = null;

// Resolve linked purchase plan from ?course= even without highlight.
if (!$highlight && $course_buy_id) {
    $linked = (int) get_post_meta($course_buy_id, '_nasim_linked_plan_id', true);
    if ($linked) {
        $highlight = $linked;
    }
}

// Buying a single course: show ONLY that purchase plan (never monthly subscription packs).
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
            if ('plans' !== $tab && !$user_id) {
                $tab = 'plans';
            } elseif ($user_id && !$tab) {
                $tab = 'plans';
            }
        } else {
            $ids = wp_list_pluck($plans, 'id');
            if (!in_array($highlighted['id'], $ids, true)) {
                array_unshift($plans, $highlighted);
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
    : nasim_subscription_url(['tab' => 'plans']);

if ($payment && (int) $payment->user_id !== $user_id) {
    $payment = null;
}

$errors = [
    'plan'         => 'پلن انتخاب‌شده معتبر نیست.',
    'gateway'      => 'درگاه پرداخت هنوز تنظیم نشده است؛ لطفاً بعداً دوباره تلاش کن.',
    'payment'      => 'تراکنش پیدا نشد یا نامعتبر است.',
    'subscription' => 'برای خرید دامنه اضافه، اول اشتراک فعال لازم است.',
    'domain_price' => 'خرید دامنه اضافه فعلاً غیرفعال است.',
    'domain'       => 'دامنه انتخاب‌شده معتبر نیست.',
];

$license      = null;
$activations  = [];
$slots_used   = 0;
$slots_max    = 1;
$slots_left   = 0;
$slot_price   = function_exists('nasim_domain_slot_price') ? nasim_domain_slot_price() : 0;
$products     = [];
$courses_url  = get_post_type_archive_link('nasim_course');
$products_url = get_post_type_archive_link('nasim_product');

if ($user_id && $is_active && function_exists('nasim_ensure_user_license')) {
    $license     = nasim_ensure_user_license($user_id);
    $activations = $license ? nasim_license_activations($license) : [];
    $slots_used  = count($activations);
    $slots_max   = $license ? (int) $license->max_activations : 1;
    $slots_left  = max(0, $slots_max - $slots_used);
    $products    = get_posts([
        'post_type'      => 'nasim_product',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
}

$product_count = (int) wp_count_posts('nasim_product')->publish;
$course_count  = (int) wp_count_posts('nasim_course')->publish;

if ($user_id) {
    $allowed_tabs = ['overview', 'license', 'spot', 'tickets', 'plans', 'payments'];
    if ($is_course_checkout) {
        $tab = 'plans';
    } elseif (!$tab || !in_array($tab, $allowed_tabs, true)) {
        if ($payment && function_exists('nasim_is_domain_slot_payment') && nasim_is_domain_slot_payment($payment)) {
            $tab = 'license';
        } elseif (!$is_active) {
            $tab = 'plans';
        } else {
            $tab = 'overview';
        }
    }
} else {
    $tab = 'plans';
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
          <span class="eyebrow"><span class="eyebrow-line"></span> پنل توسعه</span>
          <h1>سلام <?php echo esc_html($user->display_name); ?></h1>
          <p>از اینجا اشتراک، لایسنس دامنه‌ها، دانلود افزونه‌ها، تیکت‌های پشتیبانی و سوابق پرداختت را مدیریت کن.</p>
        </div>
        <div class="account-hero-actions">
          <?php if ($is_active) : ?>
          <span class="account-pill is-live">اشتراک فعال · <?php echo esc_html(nasim_fa_digits($days_left)); ?> روز</span>
          <?php elseif ($expires_at) : ?>
          <span class="account-pill is-warn">منقضی شده</span>
          <?php else : ?>
          <span class="account-pill">بدون اشتراک</span>
          <?php endif; ?>
          <a class="button button-outline button-small" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">خروج</a>
        </div>
      </div>

      <div class="account-stats">
        <article class="account-stat">
          <span class="account-stat-label">وضعیت اشتراک</span>
          <strong><?php echo $is_active ? 'فعال' : ($expires_at ? 'منقضی' : 'ندارد'); ?></strong>
          <small><?php echo $expires_at ? 'تا ' . esc_html(nasim_jdate($expires_at)) : 'هنوز پلن نگرفته‌ای'; ?></small>
        </article>
        <article class="account-stat">
          <span class="account-stat-label">دامنه رایگان هر افزونه</span>
          <strong><?php echo esc_html(nasim_fa_digits($is_active ? nasim_license_default_slots() : 0)); ?></strong>
          <small><?php echo $is_active ? 'به‌ازای هر محصول · دامنه اضافه جدا' : 'با اشتراک فعال می‌شود'; ?></small>
        </article>
        <article class="account-stat">
          <span class="account-stat-label">محصولات قابل دانلود</span>
          <strong><?php echo esc_html(nasim_fa_digits($is_active ? count($products) : 0)); ?></strong>
          <small><?php echo esc_html(nasim_fa_digits($product_count)); ?> محصول منتشرشده در فروشگاه</small>
        </article>
        <article class="account-stat">
          <span class="account-stat-label">دوره‌های آموزشی</span>
          <strong><?php echo esc_html(nasim_fa_digits($course_count)); ?></strong>
          <small><?php echo $is_active ? 'با اشتراکت باز هستند' : 'نیاز به اشتراک دارد'; ?></small>
        </article>
      </div>
    </section>

    <nav class="account-tabs" aria-label="بخش‌های پنل">
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'overview'])); ?>" class="<?php echo 'overview' === $tab ? 'is-active' : ''; ?>">خلاصه</a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'license'])); ?>" class="<?php echo 'license' === $tab ? 'is-active' : ''; ?>">لایسنس و دانلود</a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets'])); ?>" class="<?php echo 'tickets' === $tab ? 'is-active' : ''; ?>">پشتیبانی<?php if ($ticket_answered) : ?> <b class="account-tab-badge" title="پاسخ جدید"><?php echo esc_html(nasim_fa_digits($ticket_answered)); ?></b><?php endif; ?></a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>" class="<?php echo 'plans' === $tab ? 'is-active' : ''; ?>"><?php echo $is_active ? 'تمدید اشتراک' : 'خرید اشتراک'; ?></a>
      <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'payments'])); ?>" class="<?php echo 'payments' === $tab ? 'is-active' : ''; ?>">تراکنش‌ها</a>
      <?php do_action('nasim_account_tabs_after'); ?>
    </nav>
    <?php else : ?>
    <section class="account-guest-hero">
      <div class="account-guest-copy">
        <?php if ($is_course_checkout) : ?>
        <span class="eyebrow"><span class="eyebrow-line"></span> خرید جداگانه دوره</span>
        <h1>خرید دوره<?php echo $purchase_course ? ': <span>' . esc_html(get_the_title($purchase_course)) . '</span>' : ''; ?></h1>
        <p>این دوره داخل اشتراک ماهانه نیست. بعد از ورود، فقط همین دوره را یک‌بار می‌خری و دسترسی دائمی می‌گیری.</p>
        <div class="account-guest-actions">
          <a class="button button-primary button-large" href="<?php echo esc_url(nasim_auth_url('login', $checkout_return)); ?>">ورود و خرید دوره <span>←</span></a>
          <a class="button button-outline button-large" href="<?php echo esc_url(nasim_auth_url('register', $checkout_return)); ?>">ثبت‌نام و خرید</a>
        </div>
        <?php else : ?>
        <span class="eyebrow"><span class="eyebrow-line"></span> اشتراک آموزشی</span>
        <h1>اشتراک برای دوره‌های <span>رایگان با اشتراک</span></h1>
        <p>اشتراک فقط دوره‌های علامت‌خورده «رایگان با اشتراک» را باز می‌کند. دوره‌های پرچمدار (مثل اولترا وب) خرید جداگانه دارند.</p>
        <div class="account-guest-actions">
          <a class="button button-primary button-large" href="<?php echo esc_url(nasim_auth_url('login', $checkout_return)); ?>">ورود و خرید <span>←</span></a>
          <a class="button button-outline button-large" href="<?php echo esc_url(nasim_auth_url('register', $checkout_return)); ?>">ثبت‌نام رایگان</a>
        </div>
        <?php endif; ?>
      </div>
      <ul class="account-benefit-list">
        <?php if ($is_course_checkout) : ?>
        <li><span>▶</span><div><strong>دسترسی دائمی</strong><small>یک‌بار خرید؛ بدون تمدید ماهانه</small></div></li>
        <li><span>◈</span><div><strong>محتوای کامل دوره</strong><small>سرفصل‌ها و پشتیبانی آکادمی</small></div></li>
        <li><span>✓</span><div><strong>جدا از اشتراک</strong><small>این خرید اشتراک ماهانه فعال نمی‌کند</small></div></li>
        <li><span>↻</span><div><strong>پرداخت امن</strong><small>از طریق درگاه زیبال</small></div></li>
        <?php else : ?>
        <li><span>▶</span><div><strong>دوره‌های اشتراکی</strong><small>دسترسی به جلسات دوره‌های «رایگان با اشتراک»</small></div></li>
        <li><span>◉</span><div><strong>تمدید دستی</strong><small>بدون کسر خودکار از حساب</small></div></li>
        <li><span>↻</span><div><strong>آپدیت در طول اشتراک</strong><small>تا وقتی اشتراکت فعال باشد</small></div></li>
        <li><span>✓</span><div><strong>دوره‌های پرچمدار جدا</strong><small>اولترا وب و مشابه باید جدا خریده شوند</small></div></li>
        <?php endif; ?>
      </ul>
    </section>
    <?php endif; ?>

    <?php if ($payment && 'paid' === $payment->status) : ?>
      <div class="lms-notice is-success" role="status">
        <?php if ((function_exists('nasim_is_domain_slot_payment') && nasim_is_domain_slot_payment($payment)) || !empty($_GET['domain_slot'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <strong>دامنه اضافه برای این افزونه فعال شد</strong>
        <span>حالا می‌تونی همین محصول را روی یک دامنه دیگر هم فعال کنی.</span>
        <a class="button button-primary button-small" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'license'])); ?>">مشاهده لایسنس</a>
        <?php else : ?>
        <strong>پرداخت موفق بود</strong>
        <span>اشتراکت تا <?php echo esc_html(nasim_jdate($expires_at)); ?> فعال است<?php echo $payment->ref_number ? ' · کد: ' . esc_html($payment->ref_number) : ''; ?></span>
        <a class="button button-primary button-small" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'license'])); ?>">دانلودها</a>
        <?php endif; ?>
      </div>
    <?php elseif ($payment && 'failed' === $payment->status) : ?>
      <div class="lms-notice is-error" role="alert">
        <strong>پرداخت ناموفق بود</strong>
        <span><?php echo esc_html($payment->note ?: 'در صورت کسر وجه، مبلغ حداکثر تا ۷۲ ساعت برمی‌گردد.'); ?></span>
      </div>
    <?php elseif (!empty($_GET['domain_released'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
      <div class="lms-notice is-success" role="status"><strong>دامنه آزاد شد</strong><span>می‌توانی لایسنس را روی دامنه دیگری فعال کنی.</span></div>
    <?php elseif ($error && isset($errors[$error])) : ?>
      <div class="lms-notice is-error" role="alert"><strong><?php echo esc_html($errors[$error]); ?></strong></div>
    <?php endif; ?>

    <?php if ($user_id && 'overview' === $tab) : ?>
    <section class="account-panel" id="overview">
      <div class="account-panel-grid">
        <div class="account-card account-status-card<?php echo $is_active ? ' is-active' : ''; ?>">
          <div class="account-card-head">
            <h2>وضعیت دسترسی</h2>
            <?php if ($is_active) : ?><span class="account-tag is-live">فعال</span><?php endif; ?>
          </div>
          <?php if ($is_active) : ?>
          <p class="account-lead">اشتراکت فعاله. افزونه‌ها، قالب‌ها و دوره‌ها در اختیارته.</p>
          <div class="account-progress">
            <div class="account-progress-bar"><span style="width:<?php echo esc_attr((string) min(100, max(8, $days_left > 90 ? 100 : (int) round($days_left / 90 * 100)))); ?>%"></span></div>
            <small><?php echo esc_html(nasim_fa_digits($days_left)); ?> روز تا <?php echo esc_html(nasim_jdate($expires_at)); ?> — تمدید به انتهای همین دوره اضافه می‌شود.</small>
          </div>
          <div class="account-card-actions">
            <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'license'])); ?>">لایسنس و دانلود <span>←</span></a>
            <a class="button button-outline" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>">تمدید اشتراک</a>
          </div>
          <?php elseif ($expires_at) : ?>
          <p class="account-lead">اشتراکت در <?php echo esc_html(nasim_jdate($expires_at)); ?> تموم شده. برای ادامه دانلود و آپدیت، تمدید کن.</p>
          <div class="account-card-actions">
            <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>">تمدید الان <span>←</span></a>
          </div>
          <?php else : ?>
          <p class="account-lead">هنوز پلنی نداری. با یک اشتراک به همه محصولات و دوره‌ها می‌رسی.</p>
          <div class="account-card-actions">
            <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>">مشاهده پلن‌ها <span>←</span></a>
          </div>
          <?php endif; ?>
        </div>

        <div class="account-card">
          <div class="account-card-head"><h2>میانبرها</h2></div>
          <div class="account-quick-links">
            <?php if ($products_url) : ?>
            <a href="<?php echo esc_url($products_url); ?>"><span>⬡</span><strong>فروشگاه افزونه</strong><small>مشاهده همه محصولات</small></a>
            <?php endif; ?>
            <?php if ($courses_url) : ?>
            <a href="<?php echo esc_url($courses_url); ?>"><span>▶</span><strong>دوره‌ها</strong><small>آموزش‌های ویدیویی</small></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'license'])); ?>"><span>◈</span><strong>کلید لایسنس</strong><small>دامنه و دانلود</small></a>
            <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets'])); ?>"><span>✉</span><strong>پشتیبانی</strong><small><?php echo $ticket_answered ? esc_html(nasim_fa_digits($ticket_answered)) . ' پاسخ جدید' : 'ثبت و پیگیری تیکت'; ?></small></a>
          </div>
        </div>

        <?php if ($is_active && $license) : ?>
        <div class="account-card account-license-preview">
          <div class="account-card-head">
            <h2>کلید لایسنس</h2>
            <a class="text-link" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'license'])); ?>">مدیریت ←</a>
          </div>
          <div class="license-key-row">
            <code dir="ltr" data-license-key><?php echo esc_html($license->license_key); ?></code>
            <button type="button" class="button button-outline button-small" data-copy-license>کپی</button>
          </div>
          <small class="account-hint">هر افزونه یک دامنه رایگان دارد · <?php echo esc_html(nasim_fa_digits(count($activations))); ?> فعال‌سازی ثبت‌شده</small>
        </div>
        <?php endif; ?>

        <?php if ($is_active && $products) : ?>
        <div class="account-card account-downloads-preview">
          <div class="account-card-head">
            <h2>آخرین محصولات</h2>
            <a class="text-link" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'license'])); ?>">همه ←</a>
          </div>
          <ul class="account-mini-list">
            <?php foreach (array_slice($products, 0, 4) as $product) :
                $meta = nasim_get_product_meta($product->ID);
                ?>
            <li>
              <div>
                <strong><a href="<?php echo esc_url(get_permalink($product)); ?>"><?php echo esc_html(get_the_title($product)); ?></a></strong>
                <small><?php echo esc_html(nasim_product_type_label($meta['type'])); ?><?php echo $meta['version'] ? ' · v' . esc_html($meta['version']) : ''; ?></small>
              </div>
              <?php if ($meta['file_id']) : ?>
              <a class="button button-primary button-small" href="<?php echo esc_url(nasim_product_download_url($product->ID)); ?>">ZIP</a>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($user_id && 'license' === $tab) : ?>
    <section class="account-panel" id="downloads">
      <?php if (!$is_active) : ?>
      <div class="account-empty">
        <strong>برای دانلود و لایسنس، اشتراک لازم است</strong>
        <p>بعد از خرید پلن، کلید لایسنس و فایل ZIP همه محصولات اینجا می‌آید.</p>
        <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>">مشاهده پلن‌ها <span>←</span></a>
      </div>
      <?php else : ?>

      <div class="account-panel-grid account-license-layout">
        <div class="account-card">
          <div class="account-card-head"><h2>کلید لایسنس</h2></div>
          <p class="account-lead">یک کلید برای همه محصولات. <strong>هر افزونه جداگانه یک دامنه رایگان</strong> می‌گیرد؛ دامنه اضافه فقط برای همان افزونه خریداری می‌شود.</p>
          <?php if ($license) : ?>
          <div class="license-key-row is-large">
            <code dir="ltr" data-license-key><?php echo esc_html($license->license_key); ?></code>
            <button type="button" class="button button-primary button-small" data-copy-license>کپی کلید</button>
          </div>
          <?php endif; ?>
          <ol class="account-steps">
            <li>افزونه را دانلود و روی سایت مشتری نصب کن.</li>
            <li>کلید را وارد کن؛ همان دامنه فقط برای همان افزونه ثبت می‌شود.</li>
            <li>برای سایت دوم همان افزونه، دامنه اضافه بخر یا دامنه قبلی را آزاد کن.</li>
          </ol>
        </div>

        <div class="account-card">
          <div class="account-card-head">
            <h2>فعال‌سازی‌ها به‌ازای محصول</h2>
            <span class="account-tag"><?php echo esc_html(nasim_fa_digits(count($activations))); ?></span>
          </div>
          <p class="account-lead">سقف پیش‌فرض: <?php echo esc_html(nasim_fa_digits(nasim_license_default_slots())); ?> دامنه رایگان برای هر افزونه.</p>
          <?php if ($activations) : ?>
          <ul class="license-domain-list">
            <?php foreach ($activations as $row) :
                $row_slug = sanitize_title($row['slug'] ?? '');
                $row_product = $row_slug ? nasim_get_product_by_slug($row_slug) : null;
                ?>
            <li>
              <div>
                <code dir="ltr"><?php echo esc_html($row['domain'] ?? ''); ?></code>
                <small>
                  <?php echo $row_product ? esc_html(get_the_title($row_product)) : ($row_slug ? esc_html($row_slug) : 'محصول نامشخص'); ?>
                  <?php if (!empty($row['activated_at'])) : ?> · از <?php echo esc_html(nasim_jdate(strtotime($row['activated_at']))); ?><?php endif; ?>
                </small>
              </div>
              <?php if ($row_slug) : ?>
              <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('این دامنه برای این افزونه آزاد شود؟');">
                <input type="hidden" name="action" value="nasim_release_domain">
                <input type="hidden" name="domain" value="<?php echo esc_attr($row['domain'] ?? ''); ?>">
                <input type="hidden" name="product_slug" value="<?php echo esc_attr($row_slug); ?>">
                <?php wp_nonce_field('nasim_release_domain'); ?>
                <button type="submit" class="button-link">آزاد کردن</button>
              </form>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php else : ?>
          <p class="license-domains-empty">هنوز دامنه‌ای ثبت نشده. با فعال‌سازی لایسنس داخل هر افزونه، دامنه همان محصول اینجا دیده می‌شود.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="account-card account-products-card">
        <div class="account-card-head">
          <h2>دانلود و دامنه هر محصول</h2>
          <?php if ($products_url) : ?><a class="text-link" href="<?php echo esc_url($products_url); ?>">فروشگاه ←</a><?php endif; ?>
        </div>
        <?php if ($products) : ?>
        <div class="account-product-grid">
          <?php foreach ($products as $product) :
              $meta       = nasim_get_product_meta($product->ID);
              $slug       = $meta['slug'] ?: sanitize_title(get_post_field('post_name', $product));
              $prod_rows  = $slug ? nasim_license_product_activations($license, $slug) : [];
              $prod_limit = $slug ? nasim_product_domain_limit($license, $slug) : nasim_license_default_slots();
              $prod_left  = max(0, $prod_limit - count($prod_rows));
              ?>
          <article class="account-product">
            <div class="account-product-thumb">
              <?php if (has_post_thumbnail($product)) : ?>
                <?php echo get_the_post_thumbnail($product, 'medium', ['loading' => 'lazy']); ?>
              <?php else : ?>
                <span class="brand-mark"><?php echo esc_html(mb_substr(get_bloginfo('name'), 0, 1)); ?></span>
              <?php endif; ?>
            </div>
            <div class="account-product-body">
              <span class="product-type-badge"><?php echo esc_html(nasim_product_type_label($meta['type'])); ?></span>
              <h3><a href="<?php echo esc_url(get_permalink($product)); ?>"><?php echo esc_html(get_the_title($product)); ?></a></h3>
              <small dir="ltr"><?php echo $meta['version'] ? 'v' . esc_html($meta['version']) : '—'; ?><?php echo $slug ? ' · ' . esc_html($slug) : ''; ?></small>
              <small class="account-product-slots">دامنه: <?php echo esc_html(nasim_fa_digits(count($prod_rows))); ?> از <?php echo esc_html(nasim_fa_digits($prod_limit)); ?><?php echo $prod_left ? ' · ' . esc_html(nasim_fa_digits($prod_left)) . ' آزاد' : ' · پر'; ?></small>
              <?php if ($prod_rows) : ?>
              <ul class="account-product-domains">
                <?php foreach ($prod_rows as $row) : ?>
                <li dir="ltr"><?php echo esc_html($row['domain'] ?? ''); ?></li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
              <div class="account-product-actions">
                <?php if ($meta['file_id']) : ?>
                <a class="button button-primary button-small" href="<?php echo esc_url(nasim_product_download_url($product->ID)); ?>">دانلود ZIP</a>
                <?php else : ?>
                <span class="button button-outline button-small is-disabled">به‌زودی</span>
                <?php endif; ?>
                <?php if ($slot_price > 0 && $slug) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                  <input type="hidden" name="action" value="nasim_buy_domain_slot">
                  <input type="hidden" name="product_slug" value="<?php echo esc_attr($slug); ?>">
                  <?php wp_nonce_field('nasim_buy_domain_slot'); ?>
                  <button type="submit" class="button button-outline button-small">+ دامنه · <?php echo esc_html(nasim_format_price($slot_price)); ?></button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="course-empty" style="margin:0">هنوز محصولی منتشر نشده است.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($user_id && 'tickets' === $tab && function_exists('nasim_ticket_counts')) : ?>
      <?php get_template_part('template-parts/account/tickets', null, ['user_id' => $user_id, 'is_active' => $is_active]); ?>
    <?php endif; ?>

    <?php if ((!$user_id) || 'plans' === $tab) : ?>
    <section class="account-panel" id="plans">
      <?php if ($user_id) : ?>
      <div class="account-section-intro">
        <div>
          <span class="eyebrow"><?php echo $is_course_checkout ? 'خرید دوره' : 'پلن‌ها'; ?></span>
          <h2><?php
            if ($is_course_checkout) {
                echo 'تکمیل خرید دوره';
            } elseif ($is_active) {
                echo 'تمدید یا ارتقای اشتراک';
            } else {
                echo 'انتخاب پلن مناسب';
            }
          ?></h2>
        </div>
        <p><?php
          if ($is_course_checkout) {
              echo 'این پرداخت فقط همین دوره را باز می‌کند و اشتراک ماهانه فعال نمی‌شود.';
          } elseif ($is_active) {
              echo 'خرید دوباره به انتهای اشتراک فعلی اضافه می‌شود.';
          } else {
              echo 'اشتراک فقط دوره‌های «رایگان با اشتراک» را باز می‌کند؛ خرید جداگانه برای دوره‌های پرچمدار جداست.';
          }
        ?></p>
      </div>
      <?php endif; ?>

      <?php if ($plans) : ?>
      <div class="plan-grid<?php echo $is_course_checkout ? ' plan-grid--purchase' : ''; ?>">
        <?php foreach ($plans as $plan) :
            $monthly     = nasim_plan_monthly_price($plan);
            $is_purchase = in_array($plan['type'] ?? '', ['course', 'spot_course'], true);
            $features    = $plan['features'] ?: ($is_purchase ? [
                'دسترسی دائمی به همین دوره',
                'پشتیبانی آکادمی',
            ] : [
                'دسترسی به دوره‌های «رایگان با اشتراک»',
                'تمدید دستی بدون کسر خودکار',
            ]);
            $btn_label = $is_purchase
                ? 'پرداخت و خرید دوره'
                : ($is_active ? 'تمدید با این پلن' : 'خرید اشتراک');
            $login_return = $is_purchase
                ? nasim_subscription_url(array_filter([
                    'tab'       => 'plans',
                    'highlight' => $plan['id'],
                    'course'    => (int) ($plan['course_id'] ?? 0) ?: null,
                    'buy'       => 'course',
                ]))
                : nasim_subscription_url(['tab' => 'plans']);
            ?>
        <article class="plan-card<?php echo $plan['featured'] ? ' is-featured' : ''; ?><?php echo $is_purchase ? ' is-course-purchase' : ''; ?>">
          <?php if ($plan['featured']) : ?><span class="plan-badge">پیشنهاد ویژه</span><?php endif; ?>
          <?php if ($is_purchase) : ?><span class="plan-badge plan-badge--purchase">خرید جداگانه</span><?php endif; ?>
          <span class="plan-duration"><?php echo esc_html(nasim_plan_duration_label($plan)); ?></span>
          <h3><?php echo esc_html($plan['title']); ?></h3>
          <div class="plan-price">
            <strong><?php echo esc_html(nasim_fa_digits(number_format($plan['price']))); ?></strong>
            <small>تومان</small>
          </div>
          <?php if (!$is_purchase && $plan['days'] > 30) : ?>
          <small class="plan-monthly">معادل <?php echo esc_html(nasim_format_price($monthly)); ?> در ماه</small>
          <?php endif; ?>
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
            <button type="submit" class="button <?php echo ($plan['featured'] || $is_purchase) ? 'button-primary' : 'button-outline'; ?>"><?php echo esc_html($btn_label); ?> <span>←</span></button>
          </form>
          <?php else : ?>
          <a class="button <?php echo ($plan['featured'] || $is_purchase) ? 'button-primary' : 'button-outline'; ?>" href="<?php echo esc_url(nasim_auth_url('login', $login_return)); ?>"><?php echo $is_purchase ? 'ورود و خرید دوره' : 'ورود و خرید'; ?> <span>←</span></a>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
      <p class="plan-note"><?php echo $is_course_checkout
          ? 'پرداخت امن با زیبال · یک‌بار خرید · بدون اشتراک ماهانه · دسترسی دائمی به همین دوره'
          : 'پرداخت امن با زیبال · تمدید خودکار نیست · اشتراک فقط دوره‌های «رایگان با اشتراک»'; ?></p>
      <?php else : ?>
      <p class="course-empty"><?php echo $is_course_checkout ? 'پلن خرید این دوره پیدا نشد.' : 'پلن اشتراکی تعریف نشده است.'; ?></p>
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
        <p>همه تراکنش‌های اشتراک و خرید دامنه اضافه.</p>
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
        <p>بعد از خرید اشتراک یا دامنه اضافه، اینجا ثبت می‌شود.</p>
        <a class="button button-primary" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'plans'])); ?>">رفتن به پلن‌ها <span>←</span></a>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php do_action('nasim_account_tab_content'); ?>

  </main>

<?php
get_footer();
