<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
    <section class="benefits-wrap container-wide" aria-label="مزایای آکادمی">
      <div class="benefits-bar">
        <?php for ($index = 1; $index <= 4; $index++) : ?>
        <article class="benefit-item benefit-item--rocket">
          <span class="benefit-rocket benefit-rocket--<?php echo (int) $index % 2 ? 'violet' : 'orange'; ?>" aria-hidden="true">🚀</span>
          <div>
            <strong><?php echo esc_html(nasim_mod("benefit_{$index}_title")); ?></strong>
            <small><?php echo esc_html(nasim_mod("benefit_{$index}_text")); ?></small>
          </div>
        </article>
        <?php endfor; ?>
      </div>
    </section>