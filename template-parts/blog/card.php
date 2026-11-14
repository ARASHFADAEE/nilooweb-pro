<?php if(!defined('ABSPATH')) exit; ?>
        <a class="article-card" href="<?php the_permalink(); ?>">
          <?php nasim_post_thumbnail(); ?>
          <div class="article-meta"><?php echo esc_html(nasim_post_card_meta()); ?></div>
          <h3><?php the_title(); ?></h3>
          <span class="article-arrow" aria-hidden="true">←</span>
        </a>
