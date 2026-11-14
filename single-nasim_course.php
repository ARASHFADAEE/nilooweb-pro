<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $course_id    = get_the_ID();
    $curriculum   = nasim_get_curriculum($course_id);
    $lessons      = nasim_get_course_lessons($course_id);
    $has_access   = nasim_user_has_course_access($course_id);
    $access_label = nasim_course_access_label($course_id);
    $duration     = nasim_format_total_duration(nasim_course_total_seconds($course_id));
    $level        = get_post_meta($course_id, '_nasim_course_level', true);
    $outcomes     = nasim_split_lines(get_post_meta($course_id, '_nasim_course_outcomes', true));
    $progress     = $has_access ? nasim_course_progress($course_id) : null;
    $next_lesson  = $has_access ? nasim_next_lesson_to_watch($course_id) : null;
    $free_lessons = array_values(array_filter($lessons, static function ($lesson) {
        return !empty($lesson['is_free']);
    }));
    $courses_list = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : get_post_type_archive_link('nasim_course');
    $profile      = function_exists('nasim_profile') ? nasim_profile() : [];
    $bio_url      = function_exists('nasim_profile_bio_url') ? nasim_profile_bio_url() : home_url('/connect/');
    $instructor   = $profile['name'] ?? get_the_author();
    $role         = $profile['role'] ?? 'مدرس دوره';
    $about_short  = $profile['about_short'] ?? ($profile['author_bio'] ?? '');
    $delivery     = function_exists('nasim_course_delivery') ? nasim_course_delivery($course_id) : 'lms';
    $access_mode  = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'purchase';
    $is_free_course = 'free' === $access_mode;
    $lesson_dest  = function_exists('nasim_course_first_lesson_url') ? nasim_course_first_lesson_url($course_id) : get_permalink($course_id);
    $start_url    = function_exists('nasim_course_start_url') ? nasim_course_start_url($course_id) : $lesson_dest;
    $plan_id      = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
    $plan         = $plan_id ? nasim_get_plan($plan_id, false) : null;
    $price          = $plan ? (int) $plan['price'] : 0;
    $preview_media  = function_exists('nasim_course_preview_media') ? nasim_course_preview_media($course_id) : null;
    $has_preview    = is_array($preview_media) && !empty($preview_media['html']);
    $preview_plyr   = $has_preview && !empty($preview_media['use_plyr']);

    $section_count = count($curriculum);
    $lesson_count  = count($lessons);

    $faqs = [
        [
            'q' => $is_free_course ? 'چطور دوره رایگان را شروع کنم؟' : 'بعد از خرید چطور به دوره دسترسی پیدا می‌کنم؟',
            'a' => $is_free_course
                ? 'با ورود یا ساخت حساب، بلافاصله به پلیر دوره (LMS) دسترسی پیدا می‌کنی و می‌توانی جلسات را تماشا کنی.'
                : 'بلافاصله بعد از پرداخت موفق، حسابت فعال می‌شود و می‌توانی از همین صفحه یا پنل کاربری وارد درس‌ها شوی.',
        ],
        [
            'q' => 'مدت دسترسی به دوره چقدر است؟',
            'a' => $is_free_course
                ? 'تا وقتی حسابت فعال باشد، به این دوره رایگان دسترسی داری.'
                : 'با خرید این دوره، دسترسی دائمی به محتوای همین دوره برای حساب تو باز می‌ماند.',
        ],
        [
            'q' => 'محتوای دوره چطور ارائه می‌شود؟',
            'a' => 'spot' === $delivery || 'hybrid' === $delivery
                ? 'بخشی از جلسات از طریق اسپات‌پلیر (ویدیوی محافظت‌شده) و در صورت نیاز پلیر آکادمی در دسترس است.'
                : 'جلسات داخل پلیر آکادمی نیلوفر وفایی پخش می‌شوند و سرفصل‌ها را همین‌جا می‌بینی.',
        ],
        [
            'q' => 'اگر سؤال داشتم پشتیبانی دارید؟',
            'a' => 'بله؛ از طریق تیکت پنل کاربری و کانال‌های پشتیبانی آکادمی می‌توانی سؤال‌هایت را بپرسی.',
        ],
    ];

    if (!$is_free_course && $price > 0) {
        array_unshift($faqs, [
            'q' => 'هزینه دوره چقدر است؟',
            'a' => sprintf('قیمت این دوره %s است و پرداخت از طریق درگاه امن انجام می‌شود.', nasim_format_price($price)),
        ]);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only success banner after payment return.
    $paid_id = absint($_GET['payment'] ?? 0);
    ?>

  <main class="course-sale-page">
    <?php if ($paid_id && is_user_logged_in()) :
        $paid = function_exists('nasim_get_payment') ? nasim_get_payment($paid_id) : null;
        if ($paid && 'paid' === $paid->status && (int) $paid->user_id === get_current_user_id()) :
            ?>
    <div class="container-wide">
      <div class="lms-notice is-success" role="status">
        <strong>پرداخت موفق</strong>
        <span>حسابت فعال شد و دسترسی به این دوره برایت باز است.</span>
      </div>
    </div>
            <?php
        endif;
    endif; ?>

    <header class="course-hero-band">
      <div class="container-wide course-hero-band-inner">
        <nav class="course-breadcrumb" aria-label="مسیر صفحه">
          <div class="course-breadcrumb-track">
            <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a>
            <span aria-hidden="true">/</span>
            <a href="<?php echo esc_url($courses_list ?: home_url('/courses/')); ?>">دوره‌ها</a>
            <span aria-hidden="true">/</span>
            <span class="course-breadcrumb-current"><?php the_title(); ?></span>
          </div>
        </nav>

        <div class="course-hero-stage">
          <div class="course-hero-copy">
            <h1><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
            <p class="course-hero-lead"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>

            <ul class="course-hero-stats">
              <?php if ($duration) : ?>
              <li><span aria-hidden="true">⧗</span> <?php echo esc_html($duration); ?> آموزش</li>
              <?php endif; ?>
              <li>
                <span aria-hidden="true">☰</span>
                <?php echo esc_html(nasim_fa_digits($section_count)); ?> سرفصل
                ·
                <?php echo esc_html(nasim_fa_digits($lesson_count)); ?> جلسه
              </li>
              <?php if ($level) : ?>
              <li><span aria-hidden="true">▱</span> سطح <?php echo esc_html($level); ?></li>
              <?php endif; ?>
              <?php if ($has_access || $access_label) : ?>
              <li><span aria-hidden="true">◇</span> <?php echo $has_access ? 'دسترسی فعال' : esc_html($access_label); ?></li>
              <?php endif; ?>
            </ul>

            <div class="course-hero-instructor">
              <div class="course-hero-instructor-avatar" aria-hidden="true"><?php echo esc_html(mb_substr($instructor, 0, 1)); ?></div>
              <div>
                <small>مدرس دوره</small>
                <strong><?php echo esc_html($instructor); ?></strong>
                <?php if ($role) : ?><span><?php echo esc_html($role); ?></span><?php endif; ?>
              </div>
            </div>

            <div class="course-hero-actions">
              <?php if ($has_access && $next_lesson) : ?>
                <a class="button button-light button-large" href="<?php echo esc_url(nasim_lesson_url($course_id, $next_lesson['id'])); ?>">
                  <?php echo !empty($progress['completed']) ? 'ادامه دوره' : 'شروع دوره'; ?> <span>←</span>
                </a>
              <?php elseif ($is_free_course) : ?>
                <a class="button button-light button-large" href="<?php echo esc_url($start_url); ?>">
                  <?php echo esc_html(nasim_course_cta_label($course_id)); ?> <span>←</span>
                </a>
              <?php elseif (!$has_access) : ?>
                <button type="button" class="button button-light button-large" data-express-open>
                  <?php echo esc_html(nasim_course_cta_label($course_id)); ?> <span>←</span>
                </button>
              <?php endif; ?>
              <?php if ($has_preview) : ?>
                <button type="button" class="button button-ghost button-large" data-preview-play>
                  پخش پیش‌نمایش <span>▶</span>
                </button>
              <?php endif; ?>
            </div>
          </div>

          <div class="course-hero-preview<?php echo has_post_thumbnail() ? ' has-image' : ''; ?><?php echo $has_preview ? ' has-preview' : ''; ?>" data-course-preview<?php echo $preview_plyr ? ' data-preview-plyr="1"' : ''; ?>>
            <div class="course-hero-preview-poster">
              <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', ['alt' => the_title_attribute(['echo' => false]), 'fetchpriority' => 'high']); ?>
              <?php else : ?>
                <div class="course-hero-placeholder"><span class="brand-mark">ن</span></div>
              <?php endif; ?>
            </div>
            <?php if ($has_preview) : ?>
            <button type="button" class="course-hero-play" data-preview-play aria-label="پخش پیش‌نمایش دوره">
              <span class="course-hero-play-stage" aria-hidden="true">
                <span class="course-hero-play-aura"></span>
                <span class="course-hero-play-ring"></span>
                <span class="course-hero-play-ring is-delayed"></span>
                <span class="course-hero-play-core">
                  <svg viewBox="0 0 24 24" width="30" height="30" focusable="false">
                    <path d="M8.2 5.6c-.8-.5-1.7.1-1.7 1v10.8c0 .9.9 1.5 1.7 1l9.2-5.4c.7-.4.7-1.5 0-1.9L8.2 5.6z" fill="currentColor"/>
                  </svg>
                </span>
              </span>
              <span class="course-hero-play-copy">
                <strong>پیش‌نمایش دوره</strong>
                <small>پخش همین‌جا · بدون ورود</small>
              </span>
            </button>
            <div class="course-hero-preview-player" data-preview-player hidden>
              <?php echo $preview_media['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized player markup. ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </header>

    <nav class="course-section-nav" data-course-nav aria-label="بخش‌های صفحه دوره">
      <div class="container-wide course-section-nav-inner">
        <a href="#intro" class="is-active">معرفی</a>
        <?php if ($curriculum) : ?><a href="#curriculum">سرفصل‌ها</a><?php endif; ?>
        <a href="#instructor">مدرس</a>
        <?php if (!$has_access) : ?><a href="#enroll"><?php echo $is_free_course ? 'شروع' : 'ثبت‌نام'; ?></a><?php endif; ?>
        <a href="#faq">سؤالات</a>
        <?php if (!$has_access && $is_free_course) : ?>
        <a class="button button-primary course-section-nav-cta" href="<?php echo esc_url($start_url); ?>">
          <?php echo esc_html(nasim_course_cta_label($course_id)); ?>
        </a>
        <?php elseif (!$has_access) : ?>
        <button type="button" class="button button-primary course-section-nav-cta" data-express-open>
          <?php echo esc_html(nasim_course_cta_label($course_id)); ?>
        </button>
        <?php endif; ?>
      </div>
    </nav>

    <div class="container-wide course-sale-shell">
      <div class="course-sale-body">
        <section class="course-glance" id="glance" aria-labelledby="glance-title">
          <div class="course-glance-head">
            <span class="course-kicker">در یک نگاه</span>
            <h2 id="glance-title">همه‌چیز، پیش از شروع.</h2>
          </div>
          <div class="course-glance-grid">
            <?php if ($duration) : ?>
            <article>
              <small>زمان آموزش</small>
              <strong><?php echo esc_html($duration); ?></strong>
            </article>
            <?php endif; ?>
            <article>
              <small>سرفصل‌ها</small>
              <strong><?php echo esc_html(nasim_fa_digits($section_count)); ?> فصل</strong>
            </article>
            <article>
              <small>جلسات</small>
              <strong><?php echo esc_html(nasim_fa_digits($lesson_count)); ?> درس</strong>
            </article>
            <?php if ($level) : ?>
            <article>
              <small>سطح</small>
              <strong><?php echo esc_html($level); ?></strong>
            </article>
            <?php endif; ?>
          </div>
        </section>

        <section class="course-block" id="intro">
          <span class="course-kicker">معرفی دوره</span>
          <h2>آن‌چه یاد می‌گیری و می‌سازی</h2>
          <?php if ($outcomes) : ?>
          <ul class="outcome-list">
            <?php foreach ($outcomes as $outcome) : ?>
            <li><span>✓</span><?php echo esc_html($outcome); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <?php if (get_the_content()) : ?>
          <div class="course-content"><?php the_content(); ?></div>
          <?php elseif (!$outcomes) : ?>
          <p class="course-empty-copy">توضیحات این دوره به‌زودی تکمیل می‌شود.</p>
          <?php endif; ?>
        </section>

        <?php if ($curriculum) : ?>
        <section class="course-block" id="curriculum">
          <div class="course-block-heading">
            <div>
              <span class="course-kicker">سرفصل‌ها</span>
              <h2>مسیرت، جلسه به جلسه.</h2>
            </div>
            <span><?php echo esc_html(nasim_fa_digits($section_count) . ' سرفصل · ' . nasim_fa_digits($lesson_count) . ' جلسه' . ($duration ? ' · ' . $duration : '')); ?></span>
          </div>
          <?php get_template_part('template-parts/course/curriculum', null, ['course_id' => $course_id, 'curriculum' => $curriculum]); ?>
        </section>
        <?php endif; ?>

        <section class="course-block course-instructor-block" id="instructor">
          <span class="course-kicker">مدرس</span>
          <h2>مدرست را بشناس.</h2>
          <div class="course-instructor-panel">
            <div class="course-instructor-avatar" aria-hidden="true"><?php echo esc_html(mb_substr($instructor, 0, 1)); ?></div>
            <div class="course-instructor-copy">
              <strong><?php echo esc_html($instructor); ?></strong>
              <?php if ($role) : ?><small><?php echo esc_html($role); ?></small><?php endif; ?>
              <?php if ($about_short) : ?><p><?php echo esc_html($about_short); ?></p><?php endif; ?>
              <a class="text-link" href="<?php echo esc_url($bio_url); ?>">معرفی کامل ←</a>
            </div>
          </div>
        </section>

        <?php if (!$has_access) : ?>
        <section class="course-block course-enroll-block" id="enroll">
          <span class="course-kicker"><?php echo $is_free_course ? 'شروع رایگان' : 'ثبت‌نام'; ?></span>
          <h2>همین حالا شروع کن.</h2>
          <div class="course-enroll-copy">
            <?php if ($is_free_course) : ?>
            <p>این دوره رایگان است؛ فقط وارد حساب شو تا در پلیر آکادمی جلسات را تماشا کنی.</p>
            <ul>
              <li><span>✓</span> رایگان بعد از ورود</li>
              <li><span>✓</span> تماشا داخل LMS</li>
              <li><span>✓</span> ذخیره پیشرفت در حساب</li>
            </ul>
            <a class="button button-primary button-large" href="<?php echo esc_url($start_url); ?>">
              <?php echo esc_html(nasim_course_cta_label($course_id)); ?> <span>←</span>
            </a>
            <?php else : ?>
            <p>با یک‌بار پرداخت، به همه جلسات این دوره دسترسی دائمی می‌گیری و پشتیبانی آکادمی همراهت است.</p>
            <ul>
              <li><span>✓</span> دسترسی آنی بعد از پرداخت</li>
              <li><span>✓</span> پرداخت امن از درگاه بانکی</li>
              <li><span>✓</span> پشتیبانی از طریق پنل کاربری</li>
            </ul>
            <button type="button" class="button button-primary button-large" data-express-open>
              <?php echo esc_html(nasim_course_cta_label($course_id)); ?> <span>←</span>
            </button>
            <?php endif; ?>
          </div>
        </section>
        <?php endif; ?>

        <section class="course-block" id="faq">
          <span class="course-kicker">سؤالات پرتکرار</span>
          <h2>قبل از ثبت‌نام، این‌ها را بدان.</h2>
          <div class="course-faq">
            <?php foreach ($faqs as $i => $item) : ?>
            <details class="course-faq-item" <?php echo 0 === $i ? 'open' : ''; ?>>
              <summary><?php echo esc_html($item['q']); ?></summary>
              <p><?php echo esc_html($item['a']); ?></p>
            </details>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <aside class="course-sale-aside" aria-label="خرید یا پیشرفت دوره">
        <?php if ($has_access) : ?>
          <div class="course-progress-card course-progress-card--hero">
            <div class="progress-top">
              <strong>پیشرفت دوره</strong>
              <span><?php echo esc_html(nasim_fa_digits($progress['percent'])); ?>٪</span>
            </div>
            <div class="progress-track"><span style="width:<?php echo esc_attr($progress['percent']); ?>%"></span></div>
            <small><?php echo esc_html(nasim_fa_digits($progress['completed']) . ' از ' . nasim_fa_digits($progress['total'])); ?> درس تکمیل شده</small>
            <?php if ($next_lesson) : ?>
            <a class="button button-primary course-aside-button" href="<?php echo esc_url(nasim_lesson_url($course_id, $next_lesson['id'])); ?>">
              <?php echo !empty($progress['completed']) ? 'ادامه یادگیری' : 'شروع یادگیری'; ?> <span>←</span>
            </a>
            <?php endif; ?>
          </div>
        <?php elseif ($is_free_course) : ?>
          <div class="subscribe-card subscribe-card--aside subscribe-card--free">
            <div class="subscribe-card-head">
              <span class="subscribe-card-icon">▶</span>
              <div>
                <h3>شروع رایگان</h3>
                <p>برای تماشا در LMS وارد حساب شو؛ بعد از ورود همه جلسات این دوره باز است.</p>
              </div>
            </div>
            <a class="button button-primary" href="<?php echo esc_url($start_url); ?>">
              <?php echo esc_html(nasim_course_cta_label($course_id)); ?> <span>←</span>
            </a>
          </div>
        <?php else : ?>
          <?php get_template_part('template-parts/course/subscribe-card', null, ['course_id' => $course_id, 'variant' => 'aside']); ?>
        <?php endif; ?>
      </aside>
    </div>
  </main>

  <?php
    if (!$has_access) {
        get_template_part('template-parts/course/express-checkout', null, ['course_id' => $course_id]);
    }
    ?>

<?php
endwhile;

get_footer();
