<?php

if (!defined('ABSPATH')) {
    exit;
}

$courses = get_posts([
    'post_type'      => 'nasim_course',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows'  => true,
]);

$cats = get_terms([
    'taxonomy'   => 'nasim_course_cat',
    'hide_empty' => true,
]);
if (is_wp_error($cats)) {
    $cats = [];
}

// Collect unique levels from published courses
$levels = [];
$has_free = false;
foreach ($courses as $c) {
    $lvl = trim((string) get_post_meta($c->ID, '_nasim_course_level', true));
    if ($lvl && !in_array($lvl, $levels, true)) {
        $levels[] = $lvl;
    }
    if (function_exists('nasim_course_access_mode') && 'free' === nasim_course_access_mode($c->ID)) {
        $has_free = true;
    }
}

$bundles = function_exists('nasim_get_bundles') ? nasim_get_bundles(3) : [];
?>

<main id="main-content" class="flex-1">

  <!-- هدر آرشیو -->
  <section class="relative overflow-hidden border-b border-border">
    <div class="absolute inset-0 dot-grid opacity-50" aria-hidden="true"></div>
    <div class="absolute -top-24 -start-24 size-80 rounded-full bg-violet-200/50 blur-3xl" aria-hidden="true"></div>
    <div class="container-site relative pt-12 pb-10 md:pt-16 md:pb-14">
      <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z"/><circle cx="12" cy="12" r="10"/></svg>
        آرشیو دوره‌ها
      </span>
      <h1 class="mt-5 text-2xl md:text-4xl font-black leading-[1.5]">
        کدام دوره <span class="mark-violet">برای تو</span> مناسب است؟
      </h1>
      <p class="mt-4 fa-line text-[15px] text-muted-foreground max-w-2xl">
        همه دوره‌های آکادمی در یک نگاه؛ سطح، پروژه، پشتیبانی و قیمت هر کدام مشخص است. اگر بین انتخاب‌ها مردد هستی، اول راهنمای انتخاب زیر را امتحان کن.
      </p>
    </div>
  </section>

  <!-- راهنمای انتخاب تعاملی -->
  <section class="container-site py-10 md:py-12" aria-label="راهنمای انتخاب دوره" data-course-guide>
    <div class="reveal rounded-3xl bg-ink-900 text-cream p-6 md:p-8 relative overflow-hidden">
      <div class="absolute inset-0 dot-grid-light opacity-30" aria-hidden="true"></div>
      <div class="relative grid lg:grid-cols-12 gap-6 items-center">
        <div class="lg:col-span-5">
          <h2 class="text-lg md:text-xl font-extrabold flex items-center gap-2.5">
            <svg class="size-5 text-violet-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z"/><circle cx="12" cy="12" r="10"/></svg>
            راهنمای انتخاب: خودت را معرفی کن
          </h2>
          <p class="mt-2.5 fa-line text-[13.5px] text-ink-200">
            یک گزینه را انتخاب کن تا مسیر پیشنهادی مخصوص وضعیت تو نمایش داده شود.
          </p>
        </div>
        <div class="lg:col-span-7 flex flex-wrap gap-2.5">
          <button type="button" data-guide-target="zero" aria-pressed="true" class="rounded-full border px-4.5 py-2.5 text-[13.5px] font-bold transition-all bg-violet-400 text-ink-950 border-violet-400">از صفر می‌خواهم شروع کنم</button>
          <button type="button" data-guide-target="pro" aria-pressed="false" class="rounded-full border px-4.5 py-2.5 text-[13.5px] font-bold transition-all border-ink-600 text-cream hover:border-violet-300 hover:text-violet-200">پایه بلدم، حرفه‌ای‌تر می‌خواهم</button>
          <button type="button" data-guide-target="code" aria-pressed="false" class="rounded-full border px-4.5 py-2.5 text-[13.5px] font-bold transition-all border-ink-600 text-cream hover:border-violet-300 hover:text-violet-200">می‌خواهم کدنویسی یاد بگیرم</button>
        </div>
      </div>

      <div data-guide-card="zero" class="relative mt-6 rounded-2xl bg-ink-800 border border-ink-700 p-5 flex flex-col md:flex-row md:items-center gap-4 justify-between">
        <p class="text-[14.5px] fa-line leading-7">
          <span class="font-black text-violet-300">پیشنهاد ما: </span>
          اول دوره‌های رایگان شروع (مثل کپسول اولترا) را ببین، بعد با خیال راحت وارد مسیر جامع طراحی سایت شو.
        </p>
        <a href="#courses-list" class="btn btn-violet btn-sm rounded-full whitespace-nowrap shrink-0">دیدن دوره‌های شروع</a>
      </div>
      <div data-guide-card="pro" hidden class="relative mt-6 rounded-2xl bg-ink-800 border border-ink-700 p-5 flex flex-col md:flex-row md:items-center gap-4 justify-between">
        <p class="text-[14.5px] fa-line leading-7">
          <span class="font-black text-violet-300">پیشنهاد ما: </span>
          دوره‌های پیشرفته و بوت‌کمپ پروژه‌محور بهترین انتخاب برای ساخت رزومه قوی و پروژه‌گیری واقعی هستند.
        </p>
        <a href="#courses-list" class="btn btn-violet btn-sm rounded-full whitespace-nowrap shrink-0">دیدن دوره‌های پیشرفته</a>
      </div>
      <div data-guide-card="code" hidden class="relative mt-6 rounded-2xl bg-ink-800 border border-ink-700 p-5 flex flex-col md:flex-row md:items-center gap-4 justify-between">
        <p class="text-[14.5px] fa-line leading-7">
          <span class="font-black text-violet-300">پیشنهاد ما: </span>
          با دوره پروژه‌محور HTML و CSS اولین قدم اصولی کدنویسی وب را بردار.
        </p>
        <a href="#courses-list" class="btn btn-violet btn-sm rounded-full whitespace-nowrap shrink-0">مشاهده مسیر کدنویسی</a>
      </div>
    </div>
  </section>

  <!-- فیلترها و شبکه دوره‌ها -->
  <div id="courses-list" data-courses-catalog>
    <section class="container-site pb-4" aria-label="فیلتر دوره‌ها">
      <div class="reveal flex flex-col md:flex-row md:items-center gap-4 md:gap-6 rounded-2xl border border-border bg-card p-4">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-[12px] font-bold text-muted-foreground ms-1">دسته:</span>
          <button type="button" data-filter-cat="all" aria-pressed="true" class="rounded-full px-3.5 py-1.5 text-[13px] font-bold transition-all bg-ink-900 text-cream">همه</button>
          <?php foreach ($cats as $term) : ?>
          <button type="button" data-filter-cat="<?php echo esc_attr($term->slug); ?>" aria-pressed="false" class="rounded-full px-3.5 py-1.5 text-[13px] font-bold transition-all bg-sand text-foreground/70 hover:bg-sand-deep"><?php echo esc_html($term->name); ?></button>
          <?php endforeach; ?>
          <?php if ($has_free) : ?>
          <button type="button" data-filter-cat="free" aria-pressed="false" class="rounded-full px-3.5 py-1.5 text-[13px] font-bold transition-all bg-sand text-foreground/70 hover:bg-sand-deep">رایگان</button>
          <?php endif; ?>
        </div>

        <?php if ($levels) : ?>
        <div class="hidden md:block w-px h-6 bg-border" aria-hidden="true"></div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-[12px] font-bold text-muted-foreground ms-1">سطح:</span>
          <button type="button" data-filter-level="all" aria-pressed="true" class="rounded-full px-3.5 py-1.5 text-[13px] font-bold transition-all bg-violet-400 text-ink-950">همه سطوح</button>
          <?php foreach ($levels as $lvl) : ?>
          <button type="button" data-filter-level="<?php echo esc_attr($lvl); ?>" aria-pressed="false" class="rounded-full px-3.5 py-1.5 text-[13px] font-bold transition-all bg-sand text-foreground/70 hover:bg-sand-deep"><?php echo esc_html($lvl); ?></button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <span data-course-count class="md:ms-auto text-[12.5px] font-bold text-muted-foreground">
          <?php echo esc_html(nasim_fa_digits(count($courses))); ?> دوره نمایش داده شده
        </span>
      </div>
    </section>

    <section class="container-site py-8 md:py-10" aria-label="لیست دوره‌ها">
      <?php if ($courses) : ?>
      <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6">
        <?php foreach ($courses as $idx => $course) : ?>
          <?php get_template_part('template-parts/course/card-minimal', null, ['course' => $course, 'delay' => ($idx % 3) * 60]); ?>
        <?php endforeach; ?>
      </div>
      <div data-course-empty hidden class="rounded-3xl border border-dashed border-border bg-card p-10 text-center mt-6">
        <p class="font-extrabold text-base">با این ترکیب فیلتر دوره‌ای پیدا نشد.</p>
        <p class="mt-1.5 text-sm text-muted-foreground">فیلتر دسته یا سطح را روی «همه» قرار بده.</p>
      </div>
      <?php else : ?>
      <div class="rounded-3xl border border-dashed border-border bg-card p-10 text-center">
        <p class="font-extrabold text-lg">هنوز دوره‌ای منتشر نشده است.</p>
      </div>
      <?php endif; ?>
    </section>
  </div>

  <?php if ($bundles) : ?>
  <section class="container-site py-12 md:py-16 border-t border-border">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
      <div>
        <span class="inline-flex rounded-full bg-violet-100 border border-violet-300 px-3.5 py-1 text-xs font-bold text-violet-800">پیشنهاد اقتصادی</span>
        <h2 class="mt-3 text-xl md:text-2xl font-black">پکیج‌های چنددوره‌ای</h2>
      </div>
      <a href="<?php echo esc_url(nasim_bundles_archive_url()); ?>" class="btn btn-outline btn-sm rounded-full font-bold self-start md:self-auto">همه پکیج‌ها</a>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($bundles as $bundle) : ?>
        <?php get_template_part('template-parts/bundle/card', null, ['bundle' => $bundle]); ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

</main>
