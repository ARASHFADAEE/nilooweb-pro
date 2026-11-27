<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy entry point kept for Elementor widgets (course grid / course card).
 * Renders the unified design-system card so every course listing looks the same.
 */
get_template_part('template-parts/course/card-minimal', null, [
    'course' => get_post(),
    'delay'  => 0,
]);
