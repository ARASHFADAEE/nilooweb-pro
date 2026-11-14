<?php

if (!defined('ABSPATH')) {
    exit;
}

$icons = function_exists('nasim_benefit_icons') ? nasim_benefit_icons() : ['spark', 'check', 'refresh', 'users'];
?>
    <section class="benefits-wrap container-wide" aria-label="مزایای آکادمی">
      <div class="benefits-bar">
        <?php for ($index = 1; $index <= 4; $index++) :
            $icon = $icons[$index - 1] ?? 'spark';
            $tone = 2 === $index ? 'solid' : 'soft';
            ?>
        <article class="benefit-item">
          <span class="benefit-icon benefit-icon--<?php echo esc_attr($tone); ?>" aria-hidden="true">
            <?php echo nasim_icon($icon); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
          </span>
          <div>
            <strong><?php echo esc_html(nasim_mod("benefit_{$index}_title")); ?></strong>
            <small><?php echo esc_html(nasim_mod("benefit_{$index}_text")); ?></small>
          </div>
        </article>
        <?php endfor; ?>
      </div>
    </section>
