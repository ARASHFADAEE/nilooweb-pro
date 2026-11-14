<?php

if (!defined('ABSPATH')) {
    exit;
}

$about_image = nasim_mod('about_image') ?: nasim_mod('hero_image');
if ($about_image && str_contains($about_image, 'niloo-about.webp')) {
    $about_image = nasim_mod('hero_image') ?: $about_image;
}
$profile     = nasim_profile();
$bio_url     = nasim_profile_bio_url();
$about_url   = nasim_seo_clean_url(nasim_mod('about_button_url') ?: $bio_url);
$has_image   = (bool) $about_image;
?>
    <section class="about-section" id="about">
      <div class="container-wide about-layout<?php echo $has_image ? '' : ' about-layout--text-only'; ?>">
        <div class="about-visual<?php echo $has_image ? '' : ' is-empty'; ?>"<?php echo $has_image ? '' : ' aria-hidden="true"'; ?>>
          <?php if ($has_image) : ?>
          <img src="<?php echo esc_url($about_image); ?>" alt="<?php echo esc_attr(nasim_image_alt($about_image, $profile['name'])); ?>" loading="lazy" decoding="async" width="480" height="560">
          <?php else : ?>
          <div class="about-visual-fallback">
            <span class="brand-mark" aria-hidden="true"><?php echo esc_html(mb_substr($profile['name'], 0, 1)); ?></span>
            <strong><?php echo esc_html($profile['name']); ?></strong>
            <small><?php echo esc_html($profile['role']); ?></small>
          </div>
          <?php endif; ?>
          <?php if (nasim_mod('about_sticker')) : ?>
          <div class="about-sticker"><span aria-hidden="true">✳</span><strong><?php echo esc_html(nasim_mod('about_sticker')); ?></strong></div>
          <?php endif; ?>
        </div>
        <div class="about-copy">
          <?php nasim_section_title('about', true); ?>
          <p><?php echo esc_html(nasim_mod('about_text') ?: $profile['about_short']); ?></p>
          <ul class="about-domains">
            <?php foreach ($profile['domains'] as $domain) : ?>
            <li><strong><?php echo esc_html($domain['title']); ?></strong><span><?php echo esc_html($domain['text']); ?></span></li>
            <?php endforeach; ?>
          </ul>
          <div class="stats-row">
            <?php for ($index = 1; $index <= 3; $index++) : ?>
              <?php if (nasim_mod("about_stat_{$index}_value")) : ?>
            <div><strong><?php echo esc_html(nasim_mod("about_stat_{$index}_value")); ?></strong><small><?php echo esc_html(nasim_mod("about_stat_{$index}_label")); ?></small></div>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
          <div class="about-actions">
            <a class="button button-primary" href="<?php echo esc_url($bio_url); ?>"><?php echo esc_html($profile['bio_label']); ?> <span>←</span></a>
            <?php if (nasim_mod('about_button_text') && $about_url && $about_url !== $bio_url) : ?>
            <a class="button button-outline" href="<?php echo esc_url(nasim_url($about_url)); ?>"><?php echo esc_html(nasim_mod('about_button_text')); ?></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
