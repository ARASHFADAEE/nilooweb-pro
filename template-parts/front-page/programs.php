<?php

if(!defined('ABSPATH')) exit;

$programs = [
    1 => ['symbol' => '↗', 'anchor' => '', 'image_class' => 'image-training'],
    2 => ['symbol' => '♡', 'anchor' => 'nutrition', 'image_class' => ''],
];
?>
    <section class="section container-wide" id="programs">
      <div class="section-heading">
        <?php nasim_section_title('programs'); ?>
        <p><?php echo esc_html(nasim_mod('programs_description')); ?></p>
      </div>
      <div class="program-grid">
        <?php foreach ($programs as $index => $program) :
            $image = nasim_mod("program_{$index}_image");
            $title = nasim_mod("program_{$index}_title");
            $url   = nasim_mod("program_{$index}_url");
            ?>
        <article class="program-card"<?php echo $program['anchor'] ? ' id="' . esc_attr($program['anchor']) . '"' : ''; ?>>
          <div class="program-image <?php echo esc_attr($program['image_class']); ?>">
            <?php if ($image) : ?>
            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(nasim_image_alt($image, $title)); ?>" loading="lazy">
            <?php endif; ?>
            <span class="image-label"><?php echo esc_html(nasim_mod("program_{$index}_label")); ?></span>
            <span class="image-arrow">↙</span>
          </div>
          <div class="program-content">
            <div class="program-title-row"><span class="program-symbol"><?php echo esc_html($program['symbol']); ?></span><h3><?php echo esc_html($title); ?></h3></div>
            <p><?php echo esc_html(nasim_mod("program_{$index}_description")); ?></p>
            <?php $tags = nasim_split_list(nasim_mod("program_{$index}_tags")); ?>
            <?php if ($tags) : ?>
            <div class="tag-row">
              <?php foreach ($tags as $tag) : ?><span><?php echo esc_html($tag); ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($url) : ?>
            <a class="text-link" href="<?php echo esc_url(nasim_url($url)); ?>">جزئیات برنامه <span>←</span></a>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
