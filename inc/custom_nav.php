<?php

// **************************************************
// Add menus
// **************************************************

function register_my_menus() {
    register_nav_menus(
      array(
        'fullscreen-menu' => __( 'Fullscreen Menu' ),
        'sidebar-menu' => __( 'Sidebar Menu' )
       )
    );
}
add_action( 'init', 'register_my_menus' );


// **************************************************
// Menu level class
// **************************************************

add_filter('wp_nav_menu_objects' , 'my_menu_class');
function my_menu_class($menu) {
    $level = 0;
    $stack = array('0');
    foreach($menu as $key => $item) {
        while($item->menu_item_parent != array_pop($stack)) {
            $level--;
        }   
        $level++;
        $stack[] = $item->menu_item_parent;
        $stack[] = $item->ID;
        $menu[$key]->classes[] = 'level-'. ($level - 1);
    }                    
    return $menu;        
}

// **************************************************
// Custom walker nav menu
// **************************************************

class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
    private $current_item;

    function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        $this->current_item = $item;
        
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
        
        $output .= $indent . '<li' . $id . $class_names .'>';
        
        $atts = array();
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target)     ? $item->target     : '';
        $atts['rel']    = !empty($item->xfn)        ? $item->xfn        : '';
        $atts['href']   = !empty($item->url)        ? $item->url        : '';
        
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);
        
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
        
        // Check if the item has children
        $has_children = in_array('menu-item-has-children', $classes);
        
        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';
        $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
        
        // Add caret icon if item has children (for all levels)
        if ($has_children) {
            $item_output .= '<span class="submenu-icon"> ›</span>';
        }
        
        $item_output .= '</a>';
        $item_output .= $args->after;
        
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    function start_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat("\t", $depth);
        $title = !empty($this->current_item->title) ? esc_html($this->current_item->title) : 'Sub Menu';

        $output .= "\n$indent<div class=\"mp-level\">\n";
        $output .= "$indent\t<a class=\"mp-back\" href=\"#\">← Zurück</a>\n";
        $output .= "$indent\t<h2 class=\"mp-heading\">{$title}</h2>\n";
        $output .= "$indent\t<ul class=\"sub-menu\">\n";
    }

    function end_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent\t</ul>\n";
        $output .= "$indent</div>\n";
    }
}