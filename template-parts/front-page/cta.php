<?php

if(!defined('ABSPATH')) exit;
?>
    <section class="cta-section container-wide" id="contact">
      <div class="cta-decoration"></div>
      <div class="cta-copy">
        <span class="eyebrow"><?php echo esc_html(nasim_mod('cta_eyebrow')); ?></span>
        <h2><?php echo esc_html(nasim_mod('cta_title')); ?></h2>
        <p><?php echo esc_html(nasim_mod('cta_text')); ?></p>
      </div>
      <?php if (nasim_mod('cta_button_text')) : ?>
      <a class="button button-light button-large" href="<?php echo esc_url(nasim_url(nasim_mod('cta_button_url'))); ?>"><?php echo esc_html(nasim_mod('cta_button_text')); ?> <span>←</span></a>
      <?php endif; ?>
    </section>
