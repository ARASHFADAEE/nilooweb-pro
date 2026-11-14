<?php

if(!defined('ABSPATH')) exit;

$user_id   = (int) ($args['user_id'] ?? get_current_user_id());
$is_active = !empty($args['is_active']);

$statuses    = nasim_ticket_statuses();
$departments = nasim_ticket_departments();
$priorities  = nasim_ticket_priorities();
$limits      = nasim_ticket_limits();
$max_mb      = (int) round(nasim_ticket_max_upload_bytes() / MB_IN_BYTES);
$accept      = '.jpg,.jpeg,.png,.webp,.gif,.pdf,.zip,.txt,.log';

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only view state after redirects.
$view_param = sanitize_key(wp_unslash($_GET['ticket'] ?? ''));
$filter     = sanitize_key(wp_unslash($_GET['ticket_status'] ?? ''));
$notice     = sanitize_key(wp_unslash($_GET['ticket_notice'] ?? ''));
$error      = sanitize_key(wp_unslash($_GET['ticket_error'] ?? ''));
$pre_product = absint($_GET['product'] ?? 0);
// phpcs:enable

$error_text = '';
if ('custom' === $error) {
    $error_text = (string) get_transient('nasim_ticket_error_' . $user_id);
    delete_transient('nasim_ticket_error_' . $user_id);
} elseif ('flood' === $error) {
    $error_text = sprintf('چند ثانیه صبر کن و دوباره ارسال کن (حداقل %s ثانیه بین پیام‌ها).', nasim_fa_digits($limits['flood_secs']));
} elseif ('ticket' === $error) {
    $error_text = 'تیکت پیدا نشد یا به آن دسترسی نداری.';
}

$old = nasim_ticket_pull_form($user_id);

$ticket = null;
$view   = 'list';

if ('new' === $view_param) {
    $view = 'new';
} elseif (ctype_digit($view_param)) {
    $ticket = nasim_get_ticket((int) $view_param);
    if ($ticket && (int) $ticket->user_id === $user_id) {
        $view = 'single';
    } else {
        $ticket     = null;
        $error_text = 'تیکت پیدا نشد یا به آن دسترسی نداری.';
    }
}

$notices = [
    'created' => ['تیکت ثبت شد', 'پیام شما به تیم پشتیبانی رسید؛ پاسخ را همین‌جا می‌بینی.'],
    'replied' => ['پاسخ ارسال شد', 'تیکت دوباره در صف بررسی قرار گرفت.'],
    'closed'  => ['تیکت بسته شد', 'اگر دوباره مشکلی بود، با ارسال پاسخ تیکت باز می‌شود.'],
];

$brand_initial = mb_substr(get_bloginfo('name'), 0, 1);
?>

<section class="account-panel ticket-panel" id="tickets">

  <?php if ($notice && isset($notices[$notice])) : ?>
  <div class="lms-notice is-success" role="status"><strong><?php echo esc_html($notices[$notice][0]); ?></strong><span><?php echo esc_html($notices[$notice][1]); ?></span></div>
  <?php endif; ?>
  <?php if ($error_text) : ?>
  <div class="lms-notice is-error" role="alert"><strong><?php echo esc_html($error_text); ?></strong></div>
  <?php endif; ?>

  <?php
  /* ---------------------------------------------------------------- list */
  if ('list' === $view) :
      $counts  = nasim_ticket_counts($user_id);
      $tickets = nasim_get_user_tickets($user_id, $filter);
      ?>
  <div class="account-section-intro">
    <div>
      <span class="eyebrow">پشتیبانی</span>
      <h2>تیکت‌های من</h2>
    </div>
    <a class="button button-primary" href="<?php echo esc_url(nasim_ticket_new_url()); ?>">تیکت جدید <span>+</span></a>
  </div>

  <div class="ticket-filters" role="tablist">
    <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets'])); ?>" class="<?php echo $filter ? '' : 'is-active'; ?>">همه <b><?php echo esc_html(nasim_fa_digits(array_sum($counts))); ?></b></a>
    <?php foreach ($statuses as $key => $label) : ?>
    <a href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets', 'ticket_status' => $key])); ?>" class="is-<?php echo esc_attr($key); ?><?php echo $filter === $key ? ' is-active' : ''; ?>"><?php echo esc_html($label); ?> <b><?php echo esc_html(nasim_fa_digits($counts[$key])); ?></b></a>
    <?php endforeach; ?>
  </div>

  <div class="ticket-layout">
    <div>
      <?php if ($tickets) : ?>
      <ul class="ticket-list">
        <?php foreach ($tickets as $row) : ?>
        <li>
          <a class="ticket-row is-<?php echo esc_attr($row->status); ?>" href="<?php echo esc_url(nasim_ticket_url($row->id)); ?>">
            <span class="ticket-row-icon" aria-hidden="true"><?php echo 'answered' === $row->status ? '✓' : ('closed' === $row->status ? '—' : '…'); ?></span>
            <span class="ticket-row-main">
              <strong><?php echo esc_html($row->subject); ?></strong>
              <small>
                <?php echo esc_html(nasim_ticket_number($row->id)); ?>
                · <?php echo esc_html(nasim_ticket_label($departments, $row->department)); ?>
                <?php if ($row->product_id) : ?> · <?php echo esc_html(get_the_title($row->product_id)); ?><?php endif; ?>
              </small>
            </span>
            <span class="ticket-row-side">
              <span class="ticket-status is-<?php echo esc_attr($row->status); ?>"><?php echo esc_html(nasim_ticket_label($statuses, $row->status)); ?></span>
              <small><?php echo esc_html(nasim_ticket_time_ago($row->updated_at)); ?></small>
            </span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else : ?>
      <div class="account-empty">
        <strong><?php echo $filter ? 'تیکتی با این وضعیت نداری' : 'هنوز تیکتی ثبت نکرده‌ای'; ?></strong>
        <p>سؤال فنی، مشکل لایسنس یا پیشنهاد داری؟ یک تیکت بفرست تا بررسی کنیم.</p>
        <a class="button button-primary" href="<?php echo esc_url(nasim_ticket_new_url()); ?>">ثبت اولین تیکت <span>+</span></a>
      </div>
      <?php endif; ?>
    </div>

    <aside class="account-card ticket-help-card">
      <div class="account-card-head"><h2>پشتیبانی چطور کار می‌کند؟</h2></div>
      <ul class="ticket-help-list">
        <li><span>⏱</span><div><strong>زمان پاسخ</strong><small>معمولاً کمتر از ۲۴ ساعت کاری؛ تیکت‌های فوری زودتر بررسی می‌شوند.</small></div></li>
        <li><span>◈</span><div><strong>توکن اسپات‌پلیر</strong><small>برای مشکل پخش، از تب «اسپات پلیر» توکن را بردار یا اسکرین‌شات بفرست.</small></div></li>
        <li><span>▶</span><div><strong>مشکل دوره</strong><small>نام دوره، شماره جلسه و توضیح خطا را بنویس تا سریع‌تر حل شود.</small></div></li>
      </ul>
    </aside>
  </div>

  <?php
  /* ----------------------------------------------------------------- new */
  elseif ('new' === $view) :
      $products = get_posts([
          'post_type'      => 'nasim_product',
          'post_status'    => 'publish',
          'posts_per_page' => 100,
          'orderby'        => 'title',
          'order'          => 'ASC',
      ]);
      $sel_product    = (int) ($old['product_id'] ?? $pre_product);
      $sel_department = $old['department'] ?? ($sel_product ? 'technical' : ($is_active ? 'technical' : 'presale'));
      $sel_priority   = $old['priority'] ?? 'normal';
      ?>
  <div class="account-section-intro">
    <div>
      <a class="ticket-back" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets'])); ?>">→ بازگشت به تیکت‌ها</a>
      <h2>ثبت تیکت جدید</h2>
    </div>
    <p>هرچه دقیق‌تر توضیح بدهی، پاسخ سریع‌تر و دقیق‌تر می‌گیری.</p>
  </div>

  <div class="ticket-layout">
    <form class="account-card ticket-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" data-ticket-form>
      <input type="hidden" name="action" value="nasim_ticket_create">
      <?php wp_nonce_field('nasim_ticket_create'); ?>

      <label class="ticket-field">
        <span>عنوان تیکت</span>
        <input type="text" name="subject" required minlength="<?php echo esc_attr($limits['subject_min']); ?>" maxlength="<?php echo esc_attr($limits['subject_max']); ?>" value="<?php echo esc_attr($old['subject'] ?? ''); ?>" placeholder="مثلاً: مشکل پخش جلسه ۳ دوره اولترا وب">
      </label>

      <div class="ticket-field-row">
        <label class="ticket-field">
          <span>دپارتمان</span>
          <select name="department" required>
            <?php foreach ($departments as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($sel_department, $key); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="ticket-field">
          <span>محصول مرتبط <em>(اختیاری)</em></span>
          <select name="product_id">
            <option value="0">عمومی / بدون محصول</option>
            <?php foreach ($products as $product) : ?>
            <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($sel_product, $product->ID); ?>><?php echo esc_html(get_the_title($product)); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <div class="ticket-field-row">
        <label class="ticket-field">
          <span>آدرس سایت <em>(اختیاری)</em></span>
          <input type="url" name="site_url" dir="ltr" value="<?php echo esc_attr($old['site_url'] ?? ''); ?>" placeholder="https://example.com">
        </label>
        <fieldset class="ticket-field">
          <span>اولویت</span>
          <div class="ticket-priority">
            <?php foreach ($priorities as $key => $label) : ?>
            <label class="is-<?php echo esc_attr($key); ?>">
              <input type="radio" name="priority" value="<?php echo esc_attr($key); ?>" <?php checked($sel_priority, $key); ?>>
              <span><?php echo esc_html($label); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      </div>

      <label class="ticket-field">
        <span>شرح مشکل یا سؤال</span>
        <textarea name="message" rows="8" required minlength="<?php echo esc_attr($limits['message_min']); ?>" maxlength="<?php echo esc_attr($limits['message_max']); ?>" placeholder="چه اتفاقی افتاد؟ نام دوره، شماره جلسه و اسکرین‌شات خطا را هم بفرست."><?php echo esc_textarea($old['message'] ?? ''); ?></textarea>
      </label>

      <div class="ticket-form-foot">
        <label class="ticket-file" data-ticket-file data-max="<?php echo esc_attr((string) nasim_ticket_max_upload_bytes()); ?>">
          <input type="file" name="attachment" accept="<?php echo esc_attr($accept); ?>">
          <span class="ticket-file-icon" aria-hidden="true">📎</span>
          <span class="ticket-file-text" data-empty="پیوست فایل (تصویر، PDF، ZIP · حداکثر <?php echo esc_attr(nasim_fa_digits($max_mb)); ?> مگابایت)">پیوست فایل (تصویر، PDF، ZIP · حداکثر <?php echo esc_html(nasim_fa_digits($max_mb)); ?> مگابایت)</span>
        </label>
        <button type="submit" class="button button-primary">ارسال تیکت <span>←</span></button>
      </div>
    </form>

    <aside class="account-card ticket-help-card">
      <div class="account-card-head"><h2>قبل از ارسال</h2></div>
      <ul class="ticket-help-list">
        <li><span>۱</span><div><strong>جزئیات را بنویس</strong><small>نام دوره، شماره جلسه و دستگاه/مرورگر.</small></div></li>
        <li><span>۲</span><div><strong>مراحل تکرار خطا</strong><small>دقیقاً چه کاری کردی که مشکل پیش آمد.</small></div></li>
        <li><span>۳</span><div><strong>اسکرین‌شات یا لاگ</strong><small>تصویر خطا یا فایل debug.log را پیوست کن.</small></div></li>
      </ul>
      <p class="account-hint">هرگز رمز عبور پیشخوان یا هاست را در تیکت ننویس؛ اگر دسترسی لازم شد، خودمان روش امن را می‌گوییم.</p>
    </aside>
  </div>

  <?php
  /* -------------------------------------------------------------- single */
  else :
      $replies = nasim_get_ticket_replies($ticket->id);
      $me      = wp_get_current_user();
      $my_init = mb_substr($me->display_name ?: $me->user_login, 0, 1);
      ?>
  <div class="ticket-head account-card">
    <div class="ticket-head-main">
      <a class="ticket-back" href="<?php echo esc_url(nasim_subscription_url(['tab' => 'tickets'])); ?>">→ همه تیکت‌ها</a>
      <h2><?php echo esc_html($ticket->subject); ?></h2>
      <div class="ticket-meta">
        <span><?php echo esc_html(nasim_ticket_number($ticket->id)); ?></span>
        <span><?php echo esc_html(nasim_ticket_label($departments, $ticket->department)); ?></span>
        <?php if ($ticket->product_id) : ?><span><a href="<?php echo esc_url(get_permalink($ticket->product_id)); ?>"><?php echo esc_html(get_the_title($ticket->product_id)); ?></a></span><?php endif; ?>
        <span class="is-<?php echo esc_attr($ticket->priority); ?>">اولویت <?php echo esc_html(nasim_ticket_label($priorities, $ticket->priority)); ?></span>
        <span><?php echo esc_html(nasim_jdate(nasim_payment_time($ticket->created_at), true)); ?></span>
      </div>
    </div>
    <div class="ticket-head-side">
      <span class="ticket-status is-<?php echo esc_attr($ticket->status); ?>"><?php echo esc_html(nasim_ticket_label($statuses, $ticket->status)); ?></span>
      <?php if ('closed' !== $ticket->status) : ?>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('این تیکت بسته شود؟');">
        <input type="hidden" name="action" value="nasim_ticket_close">
        <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->id); ?>">
        <?php wp_nonce_field('nasim_ticket_close'); ?>
        <button type="submit" class="button button-outline button-small">بستن تیکت</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <ol class="ticket-thread">
    <?php foreach ($replies as $reply) : ?>
    <li class="ticket-msg<?php echo $reply->is_staff ? ' is-staff' : ' is-me'; ?>">
      <span class="ticket-msg-avatar" aria-hidden="true"><?php echo esc_html($reply->is_staff ? $brand_initial : $my_init); ?></span>
      <div class="ticket-msg-bubble">
        <header>
          <strong><?php echo esc_html($reply->is_staff ? nasim_ticket_author_name($reply) : 'شما'); ?></strong>
          <time datetime="<?php echo esc_attr(gmdate('c', nasim_payment_time($reply->created_at))); ?>"><?php echo esc_html(nasim_jdate(nasim_payment_time($reply->created_at), true)); ?></time>
        </header>
        <div class="ticket-msg-body"><?php echo nl2br(esc_html($reply->message)); ?></div>
        <?php if ($reply->attachment) : ?>
          <?php if (nasim_ticket_is_image($reply->attachment)) : ?>
          <a class="ticket-msg-image" href="<?php echo esc_url(add_query_arg('inline', 1, nasim_ticket_file_url($reply->id))); ?>" target="_blank" rel="noopener">
            <img src="<?php echo esc_url(add_query_arg('inline', 1, nasim_ticket_file_url($reply->id))); ?>" alt="<?php echo esc_attr($reply->attachment_name); ?>" loading="lazy">
          </a>
          <?php else : ?>
          <a class="ticket-msg-file" href="<?php echo esc_url(nasim_ticket_file_url($reply->id)); ?>"><span aria-hidden="true">📎</span> <?php echo esc_html($reply->attachment_name); ?></a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>

  <form class="account-card ticket-form ticket-reply" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" data-ticket-form>
    <input type="hidden" name="action" value="nasim_ticket_reply">
    <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->id); ?>">
    <?php wp_nonce_field('nasim_ticket_reply'); ?>
    <div class="account-card-head">
      <h2>ارسال پاسخ</h2>
      <?php if ('closed' === $ticket->status) : ?><span class="account-tag">با ارسال پاسخ، تیکت دوباره باز می‌شود</span><?php endif; ?>
    </div>
    <label class="ticket-field">
      <span class="screen-reader-text">متن پاسخ</span>
      <textarea name="message" rows="5" required maxlength="<?php echo esc_attr($limits['message_max']); ?>" placeholder="پاسخ یا توضیح تکمیلی…"><?php echo esc_textarea($old['message'] ?? ''); ?></textarea>
    </label>
    <div class="ticket-form-foot">
      <label class="ticket-file" data-ticket-file data-max="<?php echo esc_attr((string) nasim_ticket_max_upload_bytes()); ?>">
        <input type="file" name="attachment" accept="<?php echo esc_attr($accept); ?>">
        <span class="ticket-file-icon" aria-hidden="true">📎</span>
        <span class="ticket-file-text" data-empty="پیوست فایل (اختیاری)">پیوست فایل (اختیاری)</span>
      </label>
      <button type="submit" class="button button-primary">ارسال پاسخ <span>←</span></button>
    </div>
  </form>
  <?php endif; ?>

</section>
