<?php

if (!defined('ABSPATH')) {
    exit;
}

if (post_password_required()) {
    return;
}

$count = (int) get_comments_number();
?>

<section id="comments" class="rounded-3xl border border-border bg-card p-6 md:p-8 shadow-soft">
  <h2 class="text-lg font-extrabold flex items-center gap-2 mb-6">
    <svg class="size-5 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
    <?php echo $count ? esc_html(nasim_fa_digits($count)) . ' دیدگاه' : 'دیدگاه‌ها'; ?>
  </h2>

  <?php if (have_comments()) : ?>
  <ol class="space-y-4 mb-8 list-none p-0">
    <?php
    wp_list_comments([
        'style'       => 'ol',
        'short_ping'  => true,
        'avatar_size' => 40,
        'reply_text'  => 'پاسخ',
        'callback'    => static function ($comment, $args, $depth) {
            ?>
            <li <?php comment_class('rounded-2xl border border-border bg-background p-4 md:p-5'); ?> id="comment-<?php comment_ID(); ?>">
              <div class="flex items-center justify-between gap-3 mb-2">
                <div class="flex items-center gap-2.5">
                  <span class="grid place-items-center size-9 rounded-xl bg-ink-900 text-violet-300 font-black text-xs shrink-0">
                    <?php echo esc_html(mb_substr(get_comment_author($comment), 0, 1)); ?>
                  </span>
                  <div>
                    <strong class="block text-sm font-extrabold"><?php echo esc_html(get_comment_author($comment)); ?></strong>
                    <time class="text-[11.5px] font-semibold text-muted-foreground"><?php echo esc_html(get_comment_date('', $comment)); ?></time>
                  </div>
                </div>
                <?php
                comment_reply_link(array_merge($args, [
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'before'    => '<span class="text-xs font-bold text-violet-700 hover:underline">',
                    'after'     => '</span>',
                ]));
                ?>
              </div>
              <div class="fa-line text-[13.5px] text-foreground/85">
                <?php comment_text(); ?>
              </div>
            <?php
        },
    ]);
    ?>
  </ol>
  <?php the_comments_navigation(['prev_text' => '→ دیدگاه‌های قدیمی‌تر', 'next_text' => 'دیدگاه‌های جدیدتر ←']); ?>
  <?php endif; ?>

  <?php
  $commenter = wp_get_current_commenter();

  comment_form([
      'fields'               => [
          'author'  => '<div class="mb-4"><label for="author" class="field-label">نام <span class="text-destructive">*</span></label><input id="author" name="author" type="text" class="field" value="' . esc_attr($commenter['comment_author']) . '" maxlength="245" autocomplete="name" required></div>',
          'email'   => '<div class="mb-4"><label for="email" class="field-label">ایمیل <span class="text-destructive">*</span></label><input id="email" name="email" type="email" class="field" value="' . esc_attr($commenter['comment_author_email']) . '" maxlength="100" autocomplete="email" dir="ltr" required></div>',
          'cookies' => '<label class="flex items-center gap-2 text-xs font-semibold text-muted-foreground mb-4 cursor-pointer"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . (empty($commenter['comment_author_email']) ? '' : ' checked') . '><span>نام و ایمیلم را برای دیدگاه بعدی به خاطر بسپار.</span></label>',
      ],
      'comment_field'        => '<div class="mb-4"><label for="comment" class="field-label">دیدگاه شما <span class="text-destructive">*</span></label><textarea id="comment" name="comment" rows="4" class="field" maxlength="65525" required></textarea></div>',
      'title_reply'          => 'دیدگاهت را بنویس',
      'title_reply_before'   => '<h3 id="reply-title" class="text-base font-extrabold mb-4">',
      'title_reply_after'    => '</h3>',
      'title_reply_to'       => 'پاسخ به %s',
      'cancel_reply_link'    => 'انصراف',
      'label_submit'         => 'ارسال دیدگاه',
      'class_submit'         => 'btn btn-violet rounded-full font-bold px-6',
      'comment_notes_before' => '<p class="text-xs text-muted-foreground mb-4">نشانی ایمیلت منتشر نمی‌شود.</p>',
      'logged_in_as'         => '',
      'must_log_in'          => '<p class="text-sm text-muted-foreground">برای نوشتن دیدگاه <a class="text-violet-700 font-bold underline" href="' . esc_url(function_exists('nasim_auth_url') ? nasim_auth_url('login', get_permalink()) : wp_login_url(get_permalink())) . '">وارد حسابت شو</a>.</p>',
  ]);
  ?>
</section>
