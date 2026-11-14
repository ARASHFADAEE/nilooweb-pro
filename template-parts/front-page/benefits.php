<?php

if(!defined('ABSPATH')) exit;

$benefit_icons = [
    1 => '<path d="M3 9h18M5 9v6m14-6v6M8 6v12m8-12v12M3 15h18"></path>',
    2 => '<path d="M12 21s-8-4.8-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.2-8 11-8 11Z"></path>',
    3 => '<path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.5M4 4v4.5h4.5M12 7v5l3 2"></path>',
    4 => '<path d="M12 3v18M3 12h18M6 6l12 12M18 6 6 18"></path>',
];
?>
    <section class="benefits container-wide" aria-label="مزایای همکاری با آرش فدایی">
      <?php foreach ($benefit_icons as $index => $icon_path) : ?>
      <div class="benefit-item">
        <span class="benefit-icon"><svg viewBox="0 0 24 24"><?php echo $icon_path; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG markup. ?></svg></span>
        <div><strong><?php echo esc_html(nasim_mod("benefit_{$index}_title")); ?></strong><small><?php echo esc_html(nasim_mod("benefit_{$index}_text")); ?></small></div>
      </div>
      <?php endforeach; ?>
    </section>
