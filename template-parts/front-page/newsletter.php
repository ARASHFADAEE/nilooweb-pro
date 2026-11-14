<?php

if (!defined('ABSPATH')) {
    exit;
}

$cta_text = nasim_mod('cta_button_text');
$cta_url  = nasim_mod('cta_button_url') ?: home_url('/request/');
?>
    <section class="newsletter-section container-wide" id="contact" aria-label="تماس و خبرنامه">
      <div class="newsletter-card">
        <div class="newsletter-copy">
          <?php if (nasim_mod('cta_eyebrow')) : ?>
          <span class="eyebrow"><span class="eyebrow-line"></span> <?php echo esc_html(nasim_mod('cta_eyebrow')); ?></span>
          <?php endif; ?>
          <h2><?php echo esc_html(nasim_mod('cta_title')); ?></h2>
          <p><?php echo esc_html(nasim_mod('cta_text')); ?></p>
          <?php if ($cta_text) : ?>
          <div class="newsletter-actions">
            <a class="button button-primary" href="<?php echo esc_url(nasim_url($cta_url)); ?>"><?php echo esc_html($cta_text); ?> <span>←</span></a>
            <a class="button button-outline" href="<?php echo esc_url(home_url('/contact/')); ?>">تماس با ما</a>
          </div>
          <?php endif; ?>
        </div>
        <form class="newsletter-form" method="post" action="<?php echo esc_url(home_url('/')); ?>">
          <label class="screen-reader-text" for="newsletter-email">ایمیل</label>
          <input id="newsletter-email" type="email" name="nasim_newsletter_email" placeholder="ایمیل خود را وارد کنید" required>
          <button type="submit" class="button button-primary">عضویت در خبرنامه <span>←</span></button>
        </form>
      </div>
    </section>
