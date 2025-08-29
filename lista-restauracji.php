<?php
/*
Plugin Name: Lista restauracji
Description: Wtyczka do tworzenia mapy i listy restauracji z funkcjonalnością CRUD
Version: 1.1
Author: Łukasz Rudnicki
*/

if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych - MUSZĄ być na początku
define('LR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LR_PLUGIN_VERSION', '1.1');

// Ładowanie klas (tylko te, które istnieją)
if (file_exists(LR_PLUGIN_DIR . 'includes/class-restaurant-post-type.php')) {
    require_once LR_PLUGIN_DIR . 'includes/class-restaurant-post-type.php';
}
if (file_exists(LR_PLUGIN_DIR . 'includes/class-restaurant-meta-boxes.php')) {
    require_once LR_PLUGIN_DIR . 'includes/class-restaurant-meta-boxes.php';
}
if (file_exists(LR_PLUGIN_DIR . 'includes/class-settings-page.php')) {
    require_once LR_PLUGIN_DIR . 'includes/class-settings-page.php';
}
if (file_exists(LR_PLUGIN_DIR . 'includes/class-wpbakery-integration.php')) {
    require_once LR_PLUGIN_DIR . 'includes/class-wpbakery-integration.php';
}

// Funkcja pomocnicza do generowania klas CSS kolumn
if (!function_exists('lr_get_column_class')) {
    function lr_get_column_class($columns) {
        switch($columns) {
            case '2': return 'lr-col-md-6';
            case '3': return 'lr-col-md-4';  
            case '4': return 'lr-col-md-3';
            case '6': return 'lr-col-md-2';
            default: return 'lr-col-md-3';
        }
    }
}

// Funkcja pomocnicza do pobierania obrazka restauracji
if (!function_exists('lr_get_restaurant_image')) {
    function lr_get_restaurant_image($post_id, $size = 'large', $use_default = true) {
        $image_url = get_the_post_thumbnail_url($post_id, $size);
        
        if (empty($image_url) && $use_default) {
            // Sprawdź domyślny obrazek z ustawień
            $default_image = get_option('lr_default_image', '');
            if (!empty($default_image)) {
                return $default_image;
            }
            
            // Fallback - obrazek z wtyczki (jeśli istnieje)
            $fallback_image = LR_PLUGIN_URL . 'assets/images/no-image.jpg';
            if (file_exists(LR_PLUGIN_DIR . 'assets/images/no-image.jpg')) {
                return $fallback_image;
            }
        }
        
        return $image_url ?: '';
    }
}

// Inicjalizacja wtyczki
function lr_init_plugin() {
    if (class_exists('Restaurant_Post_Type')) {
        new Restaurant_Post_Type();
    }
    if (class_exists('Restaurant_Meta_Boxes')) {
        new Restaurant_Meta_Boxes();
    }
    if (class_exists('Settings_Page')) {
        new Settings_Page();
    }
    
    // Sprawdź czy WPBakery jest aktywny
    if (class_exists('Vc_Manager') && class_exists('WPBakery_Integration')) {
        new WPBakery_Integration();
    }
}
add_action('plugins_loaded', 'lr_init_plugin');

// Rejestracja skryptów i stylów
function lr_enqueue_scripts() {
    wp_enqueue_script('jquery');
    
    $api_key = get_option('lr_google_maps_api_key');
    if (!empty($api_key)) {
        wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places", array(), null, false);
        wp_enqueue_script('markerclusterer', 'https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js', array('google-maps'), null, true);
        
        // Sprawdź czy plik skryptu istnieje
        $js_file = LR_PLUGIN_DIR . 'assets/js/frontend-script.js';
        if (file_exists($js_file)) {
            wp_enqueue_script('lr-frontend-script', LR_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery', 'google-maps', 'markerclusterer'), LR_PLUGIN_VERSION, true);
        }
    }
    
    // Sprawdź czy plik CSS istnieje
    $css_file = LR_PLUGIN_DIR . 'assets/css/frontend-style.css';
    if (file_exists($css_file)) {
        wp_enqueue_style('lr-frontend-style', LR_PLUGIN_URL . 'assets/css/frontend-style.css', array(), LR_PLUGIN_VERSION);
    }

    $cluster_icon = get_option('lr_cluster_icon');
    $marker_icon = get_option('lr_marker_icon');

    // Lokalizacja dla JavaScript
    wp_localize_script('lr-frontend-script', 'lr_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lr_nonce'),
        'marker_icon' => $marker_icon ? esc_url($marker_icon) : '',
        'cluster_icon' => $cluster_icon ? esc_url($cluster_icon) : '',
        'plugin_url' => LR_PLUGIN_URL
    ));
}
add_action('wp_enqueue_scripts', 'lr_enqueue_scripts');

// Shortcode z obsługą parametrów
function lr_restaurant_map_list_shortcode($atts) {
    $atts = shortcode_atts(array(
        'display_mode' => 'both',
        'show_images' => 'yes',
        'show_city_filter' => 'yes',
        'default_city' => '',
        'map_height' => '400',
        'map_zoom' => '5',
        'map_center_lat' => '52.0692',
        'map_center_lng' => '19.4803',
        'columns_desktop' => '4',
        'columns_tablet' => '2',
        'columns_mobile' => '1',
        'limit' => '-1',
        'show_fields' => 'address,city,phone,hours',
        'card_bg_color' => '',
        'card_text_color' => '',
        'el_class' => '',
        'custom_css' => ''
    ), $atts, 'restauracje_mapa_lista');

    // Generuj unikalne ID dla tej instancji
    static $instance = 0;
    $instance++;
    $unique_id = 'lr-instance-' . $instance;

    ob_start();
    
    // Dodatkowe style CSS
    if (!empty($atts['custom_css']) || !empty($atts['card_bg_color']) || !empty($atts['card_text_color'])) {
        echo '<style>';
        
        if (!empty($atts['card_bg_color'])) {
            echo "#{$unique_id} .lr-card { background-color: {$atts['card_bg_color']} !important; }";
        }
        
        if (!empty($atts['card_text_color'])) {
            echo "#{$unique_id} .lr-card, #{$unique_id} .lr-card * { color: {$atts['card_text_color']} !important; }";
        }
        
        if (!empty($atts['custom_css'])) {
            echo "#{$unique_id} { {$atts['custom_css']} }";
        }
        
        echo '</style>';
    }
    
    // Przekaż parametry do template
    set_query_var('lr_atts', $atts);
    set_query_var('lr_unique_id', $unique_id);
    
    // Sprawdź czy template istnieje
    $template_file = LR_PLUGIN_DIR . 'templates/restaurant-map-list.php';
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        echo '<div class="lr-error">Template pliku nie znaleziono: ' . $template_file . '</div>';
    }
    
    return ob_get_clean();
}
add_shortcode('restauracje_mapa_lista', 'lr_restaurant_map_list_shortcode');

// AJAX handler
add_action('wp_ajax_get_restaurants', 'lr_get_restaurants');
add_action('wp_ajax_nopriv_get_restaurants', 'lr_get_restaurants');

function lr_get_restaurants() {
    // Sprawdź nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lr_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : -1;
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';

    $args = array(
        'post_type' => 'restauracje',
        'posts_per_page' => $limit,
        'post_status' => 'publish'
    );

    // Filtrowanie po mieście
    if (!empty($city)) {
        $args['meta_query'] = array(
            array(
                'key' => '_restaurant_city',
                'value' => $city,
                'compare' => '='
            )
        );
    }

    $restaurants = get_posts($args);
    $data = array();
    
    foreach ($restaurants as $restaurant) {
        $latitude = get_post_meta($restaurant->ID, '_restaurant_latitude', true);
        $longitude = get_post_meta($restaurant->ID, '_restaurant_longitude', true);
        
        // Pomiń restauracje bez współrzędnych
        if (empty($latitude) || empty($longitude)) {
            continue;
        }

        $image_url = lr_get_restaurant_image($restaurant->ID, 'large');

        $data[] = array(
            'id' => $restaurant->ID,
            'title' => get_the_title($restaurant->ID),
            'address' => get_post_meta($restaurant->ID, '_restaurant_address', true),
            'city' => get_post_meta($restaurant->ID, '_restaurant_city', true),
            'phone' => get_post_meta($restaurant->ID, '_restaurant_phone', true),
            'opening_hours' => get_post_meta($restaurant->ID, '_restaurant_opening_hours', true),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'image' => $image_url
        );
    }

    wp_send_json_success($data);
}

// Hook aktywacji
register_activation_hook(__FILE__, 'lr_plugin_activate');
function lr_plugin_activate() {
    flush_rewrite_rules();
    
    // Dodaj domyślne opcje
    add_option('lr_google_maps_api_key', '');
    add_option('lr_marker_icon', '');
    add_option('lr_cluster_icon', '');
    add_option('lr_default_image', '');
}

// Hook deaktywacji
register_deactivation_hook(__FILE__, 'lr_plugin_deactivate');
function lr_plugin_deactivate() {
    flush_rewrite_rules();
}

// Admin notice dla braku klucza API
add_action('admin_notices', 'lr_admin_notices');
function lr_admin_notices() {
    $api_key = get_option('lr_google_maps_api_key');
    if (empty($api_key) && get_current_screen()->id !== 'settings_page_lista-restauracji-settings') {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>Lista Restauracji:</strong>
                Aby wtyczka działała poprawnie, musisz
                <a href="<?php echo admin_url('options-general.php?page=lista-restauracji-settings'); ?>">
                    ustawić klucz API Google Maps
                </a>.
            </p>
        </div>
        <?php
    }
}

// Bezpieczeństwo
if (!defined('ABSPATH')) {
    exit;
}