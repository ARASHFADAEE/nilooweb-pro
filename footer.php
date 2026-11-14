<?php if(!defined('ABSPATH')) exit; ?>

  <footer class="site-footer">
    <div class="container-wide footer-main">
      <?php nasim_brand('brand-footer'); ?>
      <?php if (nasim_mod('footer_tagline')) : ?>
      <p><?php echo esc_html(nasim_mod('footer_tagline')); ?></p>
      <?php endif; ?>
      <nav aria-label="لینک‌های پایین صفحه"><?php nasim_nav_menu('footer'); ?></nav>
      <?php if (nasim_mod('social_instagram') || nasim_mod('social_telegram')) : ?>
      <div class="social-links">
        <?php if (nasim_mod('social_instagram')) : ?>
        <a href="<?php echo esc_url(nasim_mod('social_instagram')); ?>" aria-label="اینستاگرام" target="_blank" rel="noopener noreferrer">اینستاگرام</a>
        <?php endif; ?>
        <?php if (nasim_mod('social_telegram')) : ?>
        <a href="<?php echo esc_url(nasim_mod('social_telegram')); ?>" aria-label="تلگرام" target="_blank" rel="noopener noreferrer">تلگرام</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="container-wide footer-bottom">
      <span><?php echo esc_html(nasim_mod('footer_copyright')); ?></span>
      <span class="footer-credit">طراحی و توسعه با <a href="https://ranksho.com/" target="_blank" rel="noopener">رنک شو</a> <b>✳</b></span>
    </div>
  </footer>

  <nav class="mobile-bottom-nav" aria-label="میانبر موبایل: خانه، دوره‌ها، پنل">
    <?php nasim_mobile_bottom_nav(); ?>
  </nav>

<?php wp_footer(); ?>
</body>
</html>
