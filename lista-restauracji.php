<?php
/*
Plugin Name: Lista restauracji
Description: Wtyczka do tworzenia mapy i listy restauracji z funkcjonalnością CRUD
Version: 1.0
Author: Łukasz Rudnicki
*/

if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('LR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Ładowanie klas
require_once LR_PLUGIN_DIR . 'includes/class-restaurant-post-type.php';
require_once LR_PLUGIN_DIR . 'includes/class-restaurant-meta-boxes.php';
require_once LR_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once LR_PLUGIN_DIR . 'includes/class-wpbakery-integration.php';

// Inicjalizacja wtyczki
function lr_init_plugin() {
    new Restaurant_Post_Type();
    new Restaurant_Meta_Boxes();
    new Settings_Page();
    new WPBakery_Integration();
}
add_action('plugins_loaded', 'lr_init_plugin');

// Rejestracja skryptów i stylów
function lr_enqueue_scripts() {
    wp_enqueue_script('jquery');
    
    $api_key = get_option('lr_google_maps_api_key');
    wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places", array(), null, false);
    wp_enqueue_script('markerclusterer', 'https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js', array('google-maps'), null, true);
    wp_enqueue_script('lr-frontend-script', LR_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery', 'google-maps', 'markerclusterer'), '1.0', true);
    wp_enqueue_style('lr-frontend-style', LR_PLUGIN_URL . 'assets/css/frontend-style.css');

    $cluster_icon = get_option('lr_cluster_icon');
    $marker_icon = get_option('lr_marker_icon');

    wp_localize_script('lr-frontend-script', 'lr_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lr_nonce'),
        'marker_icon' => $marker_icon ? esc_url($marker_icon) : '',
        'cluster_icon' => $cluster_icon ? esc_url($cluster_icon) : '',
    ));

    // Dodaj to do debugowania
    error_log('Cluster icon URL (PHP): ' . $cluster_icon);
}
add_action('wp_enqueue_scripts', 'lr_enqueue_scripts');

// Shortcode
function lr_restaurant_map_list_shortcode($atts) {
    ob_start();
    include LR_PLUGIN_DIR . 'templates/restaurant-map-list.php';
    return ob_get_clean();
}
add_shortcode('restauracje_mapa_lista', 'lr_restaurant_map_list_shortcode');

// AJAX handler
add_action('wp_ajax_get_restaurants', 'lr_get_restaurants');
add_action('wp_ajax_nopriv_get_restaurants', 'lr_get_restaurants');

function lr_get_restaurants() {
    check_ajax_referer('lr_nonce', 'nonce');

    $restaurants = get_posts(array(
        'post_type' => 'restauracje',
        'posts_per_page' => -1
    ));

    $data = array();
    foreach ($restaurants as $restaurant) {
        $data[] = array(
            'id' => $restaurant->ID,
            'title' => get_the_title($restaurant->ID),
            'address' => get_post_meta($restaurant->ID, '_restaurant_address', true),
            'city' => get_post_meta($restaurant->ID, '_restaurant_city', true),
            'phone' => get_post_meta($restaurant->ID, '_restaurant_phone', true),
            'opening_hours' => get_post_meta($restaurant->ID, '_restaurant_opening_hours', true),
            'latitude' => get_post_meta($restaurant->ID, '_restaurant_latitude', true),
            'longitude' => get_post_meta($restaurant->ID, '_restaurant_longitude', true),
            'image' => get_the_post_thumbnail_url($restaurant->ID, 'large')
        );
    }

    wp_send_json_success($data);
}