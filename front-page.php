<?php

if(!defined('ABSPATH')) exit;

get_header();
?>

  <main id="main-content">
    <?php
    get_template_part('template-parts/front-page/hero');
    get_template_part('template-parts/front-page/benefits');
    get_template_part('template-parts/front-page/products');
    get_template_part('template-parts/front-page/about');
    get_template_part('template-parts/front-page/testimonials');
    get_template_part('template-parts/front-page/articles');
    get_template_part('template-parts/front-page/cta');
    ?>
    <div class="container-wide home-hubs">
      <?php nasim_seo_render_hub_links(); ?>
    </div>
  </main>

<?php
get_footer();
