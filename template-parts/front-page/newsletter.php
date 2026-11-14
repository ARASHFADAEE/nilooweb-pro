<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
    <section class="newsletter-section container-wide" aria-label="عضویت در خبرنامه">
      <div class="newsletter-card">
        <div class="newsletter-copy">
          <h2><?php echo esc_html(nasim_mod('cta_title')); ?></h2>
          <p><?php echo esc_html(nasim_mod('cta_text')); ?></p>
        </div>
        <form class="newsletter-form" method="post" action="<?php echo esc_url(home_url('/')); ?>">
          <label class="screen-reader-text" for="newsletter-email">ایمیل</label>
          <input id="newsletter-email" type="email" name="nasim_newsletter_email" placeholder="ایمیل خود را وارد کنید" required>
          <button type="submit" class="button button-primary">عضویت در خبرنامه <span>←</span></button>
        </form>
      </div>
    </section>