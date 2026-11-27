<?php

if (!defined('ABSPATH')) {
    exit;
}

$course_id   = get_queried_object_id();
$lesson      = nasim_current_lesson();
$lessons     = nasim_get_course_lessons($course_id);
$curriculum  = nasim_get_curriculum($course_id);
$index       = $lesson['number'] - 1;
$prev        = $lessons[$index - 1] ?? null;
$next        = $lessons[$index + 1] ?? null;
$can_watch   = nasim_can_watch_lesson($course_id, $lesson);
$logged_in   = is_user_logged_in();
$has_access  = nasim_user_has_course_access($course_id);
$interactive = $logged_in && $can_watch;
$completed   = nasim_get_user_lesson_list($course_id, 'progress');
$bookmarks   = nasim_get_user_lesson_list($course_id, 'bookmarks');
$is_done     = in_array($lesson['id'], $completed, true);
$is_saved    = in_array($lesson['id'], $bookmarks, true);
$progress    = nasim_course_progress($course_id);
$level       = get_post_meta($course_id, '_nasim_course_level', true);
$overview    = nasim_split_lines($lesson['overview'] ?? '');
$total       = nasim_fa_digits(count($lessons));
$note_val    = $interactive && function_exists('nasim_get_lesson_note') ? nasim_get_lesson_note($course_id, $lesson['id']) : '';

$is_free_course = function_exists('nasim_course_access_mode') && 'free' === nasim_course_access_mode($course_id);
$login_url      = function_exists('nasim_auth_url')
    ? nasim_auth_url('login', nasim_lesson_url($course_id, $lesson['id']))
    : wp_login_url(nasim_lesson_url($course_id, $lesson['id']));
$courses_url    = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');

get_header();
?>

  <main id="main-content" class="flex-1 bg-background py-8 md:py-10">
    <div class="container-site">

      <!-- بردکرامب و تیتر دوره -->
      <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-muted-foreground mb-5">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-foreground">خانه</a>
        <span>/</span>
        <a href="<?php echo esc_url($courses_url); ?>" class="hover:text-foreground">دوره‌ها</a>
        <span>/</span>
        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="hover:text-foreground"><?php echo esc_html(get_the_title($course_id)); ?></a>
      </nav>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 class="text-xl md:text-2xl font-black"><?php echo esc_html($lesson['title'] ?? get_the_title($course_id)); ?></h1>
          <p class="mt-1 text-xs font-bold text-muted-foreground">
            جلسه <?php echo esc_html(nasim_fa_digits($lesson['number'])); ?> از <?php echo esc_html($total); ?>
            <?php if ($level) : ?> · سطح <?php echo esc_html($level); ?><?php endif; ?>
          </p>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-40">
            <div class="flex items-center justify-between text-xs font-bold mb-1">
              <span class="text-muted-foreground">پیشرفت دوره</span>
              <span id="progressPercent" class="text-ink-900"><?php echo esc_html(nasim_fa_digits($progress['percent'])); ?>٪</span>
            </div>
            <div class="meter"><span id="progressFill" style="width:<?php echo esc_attr($progress['percent']); ?>%"></span></div>
          </div>
        </div>
      </div>

      <!-- لایوت اصلی پلیر + لیست سرفصل‌ها -->
      <div class="grid lg:grid-cols-12 gap-6 items-start">

        <!-- ستون ویدیو و ابزارهای جلسه -->
        <div class="lg:col-span-8 space-y-6 min-w-0" id="lmsPlayer">
          <div class="video-frame shadow-lift border border-border">
            <?php if ($can_watch && !empty($lesson['embed'])) : ?>
              <?php
                echo function_exists('nasim_render_lesson_player_html')
                    ? nasim_render_lesson_player_html($course_id, $lesson) // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper.
                    : wp_kses($lesson['embed'], nasim_embed_allowed_html());
              ?>
            <?php elseif ($can_watch) : ?>
              <div class="w-full h-full grid place-items-center text-center p-8 text-cream">
                <div>
                  <span class="grid place-items-center size-14 rounded-2xl bg-violet-400 text-ink-950 font-black text-xl mx-auto mb-3">▶</span>
                  <strong class="block text-base font-extrabold">ویدیوی این درس به‌زودی اضافه می‌شود</strong>
                </div>
              </div>
            <?php else : ?>
              <div class="w-full h-full grid place-items-center text-center p-8 bg-ink-950 text-cream relative">
                <div class="absolute inset-0 dot-grid-light opacity-30" aria-hidden="true"></div>
                <div class="relative max-w-md">
                  <span class="grid place-items-center size-14 rounded-2xl bg-ink-800 border border-ink-700 text-violet-300 mx-auto mb-4">🔒</span>
                  <?php if (!$logged_in) : ?>
                    <strong class="block text-lg font-extrabold"><?php echo $is_free_course ? 'برای تماشای دوره رایگان وارد شو' : 'برای تماشای این جلسه وارد حسابت شو'; ?></strong>
                    <p class="mt-2 text-xs text-ink-300 fa-line">اگر قبلاً ثبت‌نام کرده‌ای، با شماره موبایلت وارد شو تا جلسات باز شوند.</p>
                    <a class="btn btn-violet rounded-full mt-5 font-black" href="<?php echo esc_url($login_url); ?>">ورود به حساب کاربری ←</a>
                  <?php else : ?>
                    <strong class="block text-lg font-extrabold">این جلسه مخصوص دانشجویان ثبت‌نام‌شده است</strong>
                    <p class="mt-2 text-xs text-ink-300 fa-line">برای باز شدن تمام جلسات این دوره و پشتیبانی مستقیم، در دوره ثبت‌نام کن.</p>
                    <a class="btn btn-violet rounded-full mt-5 font-black" href="<?php echo esc_url(get_permalink($course_id)); ?>">مشاهده صفحه ثبت‌نام دوره ←</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- اکشن‌های جلسه -->
          <div class="rounded-3xl border border-border bg-card p-5 flex flex-wrap items-center justify-between gap-3 shadow-soft">
            <div class="flex flex-wrap items-center gap-2.5">
              <?php if ($interactive) : ?>
              <button type="button" id="completeLesson" aria-pressed="<?php echo $is_done ? 'true' : 'false'; ?>" class="btn <?php echo $is_done ? 'btn-default' : 'btn-violet'; ?> btn-sm rounded-full font-bold">
                <?php echo $is_done ? 'تکمیل شد ✓ (لغو)' : 'علامت‌گذاری به‌عنوان تکمیل‌شده ✓'; ?>
              </button>
              <button type="button" id="bookmarkLesson" aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>" class="btn btn-outline btn-sm rounded-full font-bold">
                <?php echo $is_saved ? '♥ <span>ذخیره شد</span>' : '♡ <span>ذخیره درس</span>'; ?>
              </button>
              <?php endif; ?>
            </div>

            <div class="flex items-center gap-2">
              <?php if ($prev) : ?>
              <a href="<?php echo esc_url(nasim_lesson_url($course_id, $prev['id'])); ?>" class="btn btn-outline btn-sm rounded-full font-bold">→ جلسه قبلی</a>
              <?php endif; ?>
              <?php if ($next) : ?>
              <a href="<?php echo esc_url(nasim_lesson_url($course_id, $next['id'])); ?>" class="btn btn-default btn-sm rounded-full font-bold">جلسه بعدی ←</a>
              <?php endif; ?>
            </div>
          </div>

          <!-- تب‌های توضیحات و یادداشت شخصی -->
          <div class="rounded-3xl border border-border bg-card p-6 shadow-soft">
            <div class="flex items-center gap-2 border-b border-border pb-3 mb-5">
              <button type="button" class="lesson-tab active" data-tab="overview">توضیحات جلسه</button>
              <?php if ($interactive) : ?>
              <button type="button" class="lesson-tab" data-tab="notes">یادداشت شخصی من</button>
              <?php endif; ?>
            </div>

            <div id="tab-overview" class="tab-panel">
              <?php if ($overview) : ?>
              <ul class="space-y-2.5 text-sm fa-line text-foreground/85">
                <?php foreach ($overview as $line) : ?>
                <li class="flex items-start gap-2">
                  <span class="size-1.5 rounded-full bg-violet-500 mt-2.5 shrink-0"></span>
                  <span><?php echo esc_html($line); ?></span>
                </li>
                <?php endforeach; ?>
              </ul>
              <?php else : ?>
              <p class="text-sm text-muted-foreground">برای این جلسه توضیح متنی ثبت نشده است.</p>
              <?php endif; ?>
            </div>

            <?php if ($interactive) : ?>
            <div id="tab-notes" class="tab-panel" hidden>
              <label for="lessonNote" class="field-label">یادداشت خصوصی روی این جلسه (فقط برای خودت نمایش داده می‌شود)</label>
              <textarea id="lessonNote" class="field" rows="4" placeholder="نکات مهم این جلسه را اینجا بنویس…"><?php echo esc_textarea($note_val); ?></textarea>
              <div class="mt-3 flex items-center gap-3">
                <button type="button" id="saveNote" class="btn btn-violet btn-sm rounded-full font-bold">ذخیره یادداشت</button>
                <span id="noteStatus" class="text-xs font-bold text-mint-700" role="status"></span>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- سایدبار سرفصل‌ها -->
        <aside class="lg:col-span-4 min-w-0">
          <div class="rounded-3xl border border-border bg-card p-5 shadow-soft sticky top-24">
            <h2 class="text-base font-extrabold mb-4">سرفصل‌های دوره</h2>
            <div id="lessonList" class="space-y-3 max-h-[65vh] overflow-y-auto pe-1">
              <?php foreach ($curriculum as $s_idx => $section) :
                  $sec_lessons = $section['lessons'] ?? [];
                  $contains_current = false;
                  foreach ($sec_lessons as $sl) {
                      if (($sl['id'] ?? '') === $lesson['id']) {
                          $contains_current = true;
                          break;
                      }
                  }
              ?>
              <details class="player-section rounded-2xl border border-border overflow-hidden" <?php echo ($contains_current || 0 === $s_idx) ? 'open' : ''; ?>>
                <summary class="cursor-pointer list-none px-4 py-3 bg-sand/70 font-extrabold text-[13.5px] flex items-center justify-between gap-2">
                  <span class="truncate"><?php echo esc_html($section['title'] ?? 'فصل'); ?></span>
                  <span class="text-xs font-bold text-muted-foreground shrink-0"><?php echo esc_html(nasim_fa_digits(count($sec_lessons))); ?></span>
                </summary>
                <ul class="divide-y divide-border/70">
                  <?php foreach ($sec_lessons as $ls) :
                      $is_curr  = ($ls['id'] ?? '') === $lesson['id'];
                      $ls_done  = in_array($ls['id'] ?? '', $completed, true);
                      $ls_watch = $has_access || !empty($ls['free']) || $is_free_course;
                  ?>
                  <li>
                    <a href="<?php echo esc_url(nasim_lesson_url($course_id, $ls['id'])); ?>"
                       data-lesson="<?php echo esc_attr($ls['id']); ?>"
                       class="lesson-row flex items-center gap-2.5 px-4 py-3 text-xs font-bold transition-colors <?php echo $is_curr ? 'bg-violet-100/80 text-ink-950' : 'hover:bg-sand/50 text-foreground/80'; ?><?php echo $ls_done ? ' completed' : ''; ?>">
                      <span class="lesson-state grid place-items-center size-6 rounded-full shrink-0 <?php echo $ls_done ? 'bg-mint-600 text-white' : ($is_curr ? 'bg-violet-500 text-white' : 'bg-sand text-muted-foreground'); ?>">
                        <?php echo $ls_done ? '✓' : ($ls_watch ? '▶' : '🔒'); ?>
                      </span>
                      <span class="flex-1 min-w-0 truncate"><?php echo esc_html($ls['title'] ?? ''); ?></span>
                      <?php if (!empty($ls['duration'])) : ?>
                      <span class="text-[11px] font-semibold text-muted-foreground shrink-0"><?php echo esc_html(nasim_fa_digits($ls['duration'])); ?></span>
                      <?php endif; ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </details>
              <?php endforeach; ?>
            </div>
          </div>
        </aside>

      </div>
    </div>
  </main>

<?php
get_footer();
