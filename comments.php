<?php

if(!defined('ABSPATH')) exit;

if (post_password_required()) {
    return;
}

$count = (int) get_comments_number();
?>

<section class="comments-area" id="comments">
  <h2 class="comments-title"><?php echo $count ? esc_html(nasim_fa_digits($count)) . ' دیدگاه' : 'دیدگاه‌ها'; ?></h2>

  <?php if (have_comments()) : ?>
  <ol class="comment-list">
    <?php
    wp_list_comments([
        'style'       => 'ol',
        'short_ping'  => true,
        'avatar_size' => 40,
        'reply_text'  => 'پاسخ',
    ]);
    ?>
  </ol>
  <?php the_comments_navigation(['prev_text' => '→ دیدگاه‌های قدیمی‌تر', 'next_text' => 'دیدگاه‌های جدیدتر ←']); ?>
  <?php endif; ?>

  <?php
  $commenter = wp_get_current_commenter();

  comment_form([
      'fields'               => [
          'author'  => '<p class="comment-form-author"><label for="author">نام <span class="required">*</span></label><input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" maxlength="245" autocomplete="name" required></p>',
          'email'   => '<p class="comment-form-email"><label for="email">ایمیل <span class="required">*</span></label><input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" maxlength="100" autocomplete="email" dir="ltr" required></p>',
          'cookies' => '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . (empty($commenter['comment_author_email']) ? '' : ' checked') . '><label for="wp-comment-cookies-consent">نام و ایمیلم را برای دیدگاه بعدی به خاطر بسپار.</label></p>',
      ],
      'comment_field'        => '<p class="comment-form-comment"><label for="comment">دیدگاه <span class="required">*</span></label><textarea id="comment" name="comment" rows="5" maxlength="65525" required></textarea></p>',
      'title_reply'          => 'دیدگاهت را بنویس',
      'title_reply_to'       => 'پاسخ به %s',
      'cancel_reply_link'    => 'انصراف',
      'label_submit'         => 'ارسال دیدگاه',
      'class_submit'         => 'button button-primary',
      'comment_notes_before' => '<p class="comment-notes">نشانی ایمیلت منتشر نمی‌شود.</p>',
      'logged_in_as'         => '',
      'must_log_in'          => '<p class="must-log-in">برای نوشتن دیدگاه <a href="' . esc_url(wp_login_url(get_permalink())) . '">وارد شو</a>.</p>',
  ]);
  ?>
</section>
