<?php

if(!defined('ABSPATH')) exit;

$hero_image = nasim_mod('hero_image');
?>
    <section class="hero container-wide" id="home">
      <div class="hero-copy">
        <span class="eyebrow"><span class="eyebrow-line"></span> <?php echo esc_html(nasim_mod('hero_eyebrow')); ?></span>
        <h1><?php echo esc_html(nasim_mod('hero_title')); ?><br><span><?php echo esc_html(nasim_mod('hero_title_highlight')); ?></span></h1>
        <p class="hero-description"><?php echo esc_html(nasim_mod('hero_description')); ?></p>
        <div class="hero-actions">
          <?php if (nasim_mod('hero_primary_text')) : ?>
          <a class="button button-primary button-large" href="<?php echo esc_url(nasim_url(nasim_mod('hero_primary_url'))); ?>"><?php echo esc_html(nasim_mod('hero_primary_text')); ?> <span>←</span></a>
          <?php endif; ?>
          <?php if (nasim_mod('hero_secondary_text')) : ?>
          <a class="button button-outline button-large" href="<?php echo esc_url(nasim_url(nasim_mod('hero_secondary_url'))); ?>"><?php echo esc_html(nasim_mod('hero_secondary_text')); ?> <span class="play-icon">▶</span></a>
          <?php endif; ?>
        </div>
        <?php if (nasim_mod('hero_proof_title')) : ?>
        <div class="hero-proof">
          <div class="avatar-stack" aria-hidden="true">
            <span>ن</span><span>م</span><span>س</span>
          </div>
          <div><strong><?php echo esc_html(nasim_mod('hero_proof_title')); ?></strong><small><?php echo esc_html(nasim_mod('hero_proof_text')); ?></small></div>
          <span class="proof-spark">✳</span>
        </div>
        <?php endif; ?>
      </div>

      <div class="hero-visual">
        <div class="hero-blob"></div>
        <div class="hero-grid"></div>
        <?php if ($hero_image) : ?>
        <img class="coach-image" src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr(nasim_image_alt($hero_image, get_bloginfo('name') ?: 'آرش فدایی، توسعه‌دهنده وردپرس')); ?>" fetchpriority="high" decoding="async" width="560" height="700">
        <?php endif; ?>
        <div class="floating-note note-top"><span class="note-icon">✦</span><span><strong><?php echo esc_html(nasim_mod('hero_note_top_title')); ?></strong><small><?php echo esc_html(nasim_mod('hero_note_top_text')); ?></small></span></div>
        <div class="floating-note note-bottom"><span class="note-check">✓</span><span><strong><?php echo esc_html(nasim_mod('hero_note_bottom_title')); ?></strong><small><?php echo esc_html(nasim_mod('hero_note_bottom_text')); ?></small></span></div>
        <div class="vertical-caption"><?php echo esc_html(nasim_mod('hero_caption')); ?></div>
      </div>
    </section>
