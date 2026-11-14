<?php

if(!defined('ABSPATH')) exit;



/**
 * Outputs menu items as bare <a> tags matching the theme markup
 *
 * On the "mobile" location, the item description (Screen Options > Description) holds its icon name, see nasim_icon().
 */
class Nasim_Nav_Walker extends Walker_Nav_Menu {

    /**
     * Menu location being rendered
     *
     * @var string
     */
    private $location;


    public function __construct($location = 'primary') {
        $this->location = $location;
    }


    public function start_lvl(&$output, $depth = 0, $args = null) {}


    public function end_lvl(&$output, $depth = 0, $args = null) {}


    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {

        $classes   = empty($item->classes) ? [] : (array) $item->classes;
        $is_active = (bool) array_intersect(['current-menu-item', 'current-menu-ancestor'], $classes);

        $output .= self::render_link($this->location, $item->title, nasim_url($item->url), $is_active, trim((string) $item->description));
    }


    public function end_el(&$output, $item, $depth = 0, $args = null) {}


    /**
     * Build a single escaped link for the given location
     */
    public static function render_link($location, $title, $url, $is_active = false, $icon = '') {

        $active_class = [
            'primary' => 'active',
            'mobile'  => 'selected',
        ];

        $class = $is_active && isset($active_class[$location]) ? ' class="' . esc_attr($active_class[$location]) . '"' : '';

        if ('mobile' === $location) {
            $label = '<span class="nav-icon">' . nasim_icon(sanitize_key($icon)) . '</span><small>' . esc_html($title) . '</small>';
        } else {
            $label = esc_html($title);
        }

        return '<a' . $class . ' href="' . esc_url($url) . '">' . $label . '</a>';
    }
}
