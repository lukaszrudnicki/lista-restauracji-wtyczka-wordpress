<?php
/**
 * Helper Functions for Lista Restauracji Plugin
 * 
 * This file contains utility functions and developer hooks
 * that can be used to extend the plugin functionality.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get restaurant data by ID
 * 
 * @param int $restaurant_id Restaurant post ID
 * @param bool $with_meta Include meta fields
 * @return array|false Restaurant data or false if not found
 */
function lr_get_restaurant($restaurant_id, $with_meta = true) {
    $post = get_post($restaurant_id);
    
    if (!$post || $post->post_type !== 'restauracje') {
        return false;
    }
    
    $data = array(
        'id' => $post->ID,
        'title' => get_the_title($post->ID),
        'slug' => $post->post_name,
        'status' => $post->post_status,
        'date_created' => $post->post_date,
        'date_modified' => $post->post_modified
    );
    
    if ($with_meta) {
        $data = array_merge($data, lr_get_restaurant_meta($restaurant_id));
    }
    
    return apply_filters('lr_get_restaurant_data', $data, $restaurant_id);
}

/**
 * Get all restaurant meta fields
 * 
 * @param int $restaurant_id Restaurant post ID
 * @return array Meta fields
 */
function lr_get_restaurant_meta($restaurant_id) {
    $meta_fields = array(
        'address' => get_post_meta($restaurant_id, '_restaurant_address', true),
        'city' => get_post_meta($restaurant_id, '_restaurant_city', true),
        'phone' => get_post_meta($restaurant_id, '_restaurant_phone', true),
        'opening_hours' => get_post_meta($restaurant_id, '_restaurant_opening_hours', true),
        'latitude' => get_post_meta($restaurant_id, '_restaurant_latitude', true),
        'longitude' => get_post_meta($restaurant_id, '_restaurant_longitude', true),
        'image' => lr_get_restaurant_image($restaurant_id)
    );
    
    // Remove empty values
    $meta_fields = array_filter($meta_fields, function($value) {
        return !empty($value);
    });
    
    return apply_filters('lr_get_restaurant_meta', $meta_fields, $restaurant_id);
}

/**
 * Get restaurants by city
 * 
 * @param string $city City name
 * @param array $args Additional query arguments
 * @return array Restaurant data
 */
function lr_get_restaurants_by_city($city, $args = array()) {
    $defaults = array(
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    $args = wp_parse_args($args, $defaults);
    $args['post_type'] = 'restauracje';
    $args['meta_query'] = array(
        array(
            'key' => '_restaurant_city',
            'value' => $city,
            'compare' => '='
        )
    );
    
    $posts = get_posts($args);
    $restaurants = array();
    
    foreach ($posts as $post) {
        $restaurants[] = lr_get_restaurant($post->ID);
    }
    
    return apply_filters('lr_get_restaurants_by_city', $restaurants, $city, $args);
}

/**
 * Get all unique cities
 * 
 * @param bool $with_counts Include restaurant counts
 * @return array Cities
 */
function lr_get_all_cities($with_counts = false) {
    global $wpdb;
    
    $query = "
        SELECT pm.meta_value as city, COUNT(*) as count
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_restaurant_city'
        AND pm.meta_value != ''
        AND p.post_type = 'restauracje'
        AND p.post_status = 'publish'
        GROUP BY pm.meta_value
        ORDER BY pm.meta_value ASC
    ";
    
    $results = $wpdb->get_results($query);
    
    if ($with_counts) {
        return apply_filters('lr_get_all_cities', $results);
    }
    
    $cities = wp_list_pluck($results, 'city');
    return apply_filters('lr_get_all_cities', $cities);
}

/**
 * Get restaurants within radius
 * 
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @param float $radius Radius in kilometers
 * @param array $args Additional arguments
 * @return array Restaurants with distance
 */
function lr_get_restaurants_by_radius($lat, $lng, $radius = 10, $args = array()) {
    $defaults = array(
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    $args = wp_parse_args($args, $defaults);
    $args['post_type'] = 'restauracje';
    $args['meta_query'] = array(
        array(
            'key' => '_restaurant_latitude',
            'compare' => 'EXISTS'
        ),
        array(
            'key' => '_restaurant_longitude',
            'compare' => 'EXISTS'
        )
    );
    
    $posts = get_posts($args);
    $restaurants = array();
    
    foreach ($posts as $post) {
        $post_lat = floatval(get_post_meta($post->ID, '_restaurant_latitude', true));
        $post_lng = floatval(get_post_meta($post->ID, '_restaurant_longitude', true));
        
        $distance = lr_calculate_distance($lat, $lng, $post_lat, $post_lng);
        
        if ($distance <= $radius) {
            $restaurant = lr_get_restaurant($post->ID);
            $restaurant['distance'] = round($distance, 2);
            $restaurants[] = $restaurant;
        }
    }
    
    // Sort by distance
    usort($restaurants, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });
    
    return apply_filters('lr_get_restaurants_by_radius', $restaurants, $lat, $lng, $radius);
}

/**
 * Calculate distance between two coordinates using Haversine formula
 * 
 * @param float $lat1 Latitude 1
 * @param float $lng1 Longitude 1
 * @param float $lat2 Latitude 2
 * @param float $lng2 Longitude 2
 * @return float Distance in kilometers
 */
function lr_calculate_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // Earth's radius in kilometers
    
    $lat_diff = deg2rad($lat2 - $lat1);
    $lng_diff = deg2rad($lng2 - $lng1);
    
    $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lng_diff / 2) * sin($lng_diff / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

/**
 * Validate restaurant coordinates
 * 
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @return bool True if valid
 */
function lr_validate_coordinates($lat, $lng) {
    $lat = floatval($lat);
    $lng = floatval($lng);
    
    if ($lat < -90 || $lat > 90) {
        return false;
    }
    
    if ($lng < -180 || $lng > 180) {
        return false;
    }
    
    return true;
}

/**
 * Geocode address using Google Maps API
 * 
 * @param string $address Address to geocode
 * @return array|false Coordinates or false on failure
 */
function lr_geocode_address($address) {
    $api_key = get_option('lr_google_maps_api_key');
    
    if (empty($api_key) || empty($address)) {
        return false;
    }
    
    $address = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";
    
    $response = wp_remote_get($url);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['status'] !== 'OK' || empty($data['results'])) {
        return false;
    }
    
    $location = $data['results'][0]['geometry']['location'];
    
    return array(
        'latitude' => $location['lat'],
        'longitude' => $location['lng'],
        'formatted_address' => $data['results'][0]['formatted_address']
    );
}

/**
 * Format opening hours
 * 
 * @param string $hours Raw opening hours
 * @param string $format Output format (html, text, array)
 * @return string|array Formatted opening hours
 */
function lr_format_opening_hours($hours, $format = 'html') {
    if (empty($hours)) {
        return '';
    }
    
    $hours = wp_kses_post($hours);
    
    switch ($format) {
        case 'text':
            return wp_strip_all_tags($hours);
        
        case 'array':
            $lines = explode("\n", wp_strip_all_tags($hours));
            return array_filter(array_map('trim', $lines));
        
        case 'html':
        default:
            return wpautop($hours);
    }
}

/**
 * Get restaurant count statistics
 * 
 * @return array Statistics
 */
function lr_get_restaurant_stats() {
    $stats = array(
        'total' => 0,
        'published' => 0,
        'draft' => 0,
        'cities' => 0,
        'with_coordinates' => 0,
        'with_images' => 0
    );
    
    // Post counts
    $post_counts = wp_count_posts('restauracje');
    $stats['total'] = $post_counts->publish + $post_counts->draft;
    $stats['published'] = $post_counts->publish;
    $stats['draft'] = $post_counts->draft;
    
    // Cities count
    $cities = lr_get_all_cities();
    $stats['cities'] = count($cities);
    
    // Restaurants with coordinates
    global $wpdb;
    $with_coords = $wpdb->get_var("
        SELECT COUNT(DISTINCT p.ID)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
        WHERE p.post_type = 'restauracje'
        AND p.post_status = 'publish'
        AND pm1.meta_key = '_restaurant_latitude'
        AND pm1.meta_value != ''
        AND pm2.meta_key = '_restaurant_longitude'
        AND pm2.meta_value != ''
    ");
    $stats['with_coordinates'] = intval($with_coords);
    
    // Restaurants with featured images
    $with_images = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'restauracje'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_thumbnail_id'
        AND pm.meta_value != ''
    ");
    $stats['with_images'] = intval($with_images);
    
    return apply_filters('lr_get_restaurant_stats', $stats);
}

/**
 * Export restaurants data
 * 
 * @param string $format Export format (json, csv)
 * @param array $args Export arguments
 * @return string|false Export data or false on failure
 */
function lr_export_restaurants($format = 'json', $args = array()) {
    $defaults = array(
        'include_meta' => true,
        'city' => '',
        'limit' => -1
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $query_args = array(
        'post_type' => 'restauracje',
        'posts_per_page' => $args['limit'],
        'post_status' => 'publish'
    );
    
    if (!empty($args['city'])) {
        $query_args['meta_query'] = array(
            array(
                'key' => '_restaurant_city',
                'value' => $args['city'],
                'compare' => '='
            )
        );
    }
    
    $posts = get_posts($query_args);
    $restaurants = array();
    
    foreach ($posts as $post) {
        $restaurants[] = lr_get_restaurant($post->ID, $args['include_meta']);
    }
    
    switch ($format) {
        case 'csv':
            return lr_convert_to_csv($restaurants);
        
        case 'json':
        default:
            return json_encode($restaurants, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Convert restaurant data to CSV format
 * 
 * @param array $restaurants Restaurant data
 * @return string CSV data
 */
function lr_convert_to_csv($restaurants) {
    if (empty($restaurants)) {
        return '';
    }
    
    $csv = array();
    
    // Headers
    $headers = array_keys($restaurants[0]);
    $csv[] = implode(',', array_map(function($header) {
        return '"' . str_replace('"', '""', $header) . '"';
    }, $headers));
    
    // Data rows
    foreach ($restaurants as $restaurant) {
        $row = array();
        foreach ($headers as $header) {
            $value = isset($restaurant[$header]) ? $restaurant[$header] : '';
            $row[] = '"' . str_replace('"', '""', $value) . '"';
        }
        $csv[] = implode(',', $row);
    }
    
    return implode("\n", $csv);
}

/**
 * Import restaurants from data
 * 
 * @param array $data Restaurant data to import
 * @param array $options Import options
 * @return array Import results
 */
function lr_import_restaurants($data, $options = array()) {
    $defaults = array(
        'update_existing' => false,
        'skip_images' => false,
        'default_status' => 'publish'
    );
    
    $options = wp_parse_args($options, $defaults);
    
    $results = array(
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => array()
    );
    
    foreach ($data as $restaurant_data) {
        try {
            $result = lr_import_single_restaurant($restaurant_data, $options);
            $results[$result['action']]++;
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
    }
    
    return apply_filters('lr_import_results', $results, $data, $options);
}

/**
 * Import single restaurant
 * 
 * @param array $restaurant_data Restaurant data
 * @param array $options Import options
 * @return array Import result
 */
function lr_import_single_restaurant($restaurant_data, $options) {
    // Check if restaurant exists
    $existing = get_posts(array(
        'post_type' => 'restauracje',
        'title' => $restaurant_data['title'],
        'posts_per_page' => 1
    ));
    
    if (!empty($existing) && !$options['update_existing']) {
        return array('action' => 'skipped', 'id' => $existing[0]->ID);
    }
    
    $post_data = array(
        'post_title' => $restaurant_data['title'],
        'post_type' => 'restauracje',
        'post_status' => $options['default_status']
    );
    
    if (!empty($existing)) {
        $post_data['ID'] = $existing[0]->ID;
        $post_id = wp_update_post($post_data);
        $action = 'updated';
    } else {
        $post_id = wp_insert_post($post_data);
        $action = 'imported';
    }
    
    if (is_wp_error($post_id)) {
        throw new Exception($post_id->get_error_message());
    }
    
    // Update meta fields
    $meta_fields = array('address', 'city', 'phone', 'opening_hours', 'latitude', 'longitude');
    
    foreach ($meta_fields as $field) {
        if (isset($restaurant_data[$field])) {
            update_post_meta($post_id, '_restaurant_' . $field, $restaurant_data[$field]);
        }
    }
    
    return array('action' => $action, 'id' => $post_id);
}

/**
 * Register developer hooks and filters
 */
function lr_register_developer_hooks() {
    // Allow developers to modify restaurant query
    add_filter('lr_restaurant_query_args', function($args, $context) {
        return apply_filters("lr_restaurant_query_args_{$context}", $args);
    }, 10, 2);
    
    // Allow modification of restaurant data before display
    add_filter('lr_restaurant_display_data', function($data, $context) {
        return apply_filters("lr_restaurant_display_data_{$context}", $data);
    }, 10, 2);
    
    // Allow custom validation for restaurant data
    add_filter('lr_validate_restaurant_data', function($is_valid, $data, $context) {
        return apply_filters("lr_validate_restaurant_data_{$context}", $is_valid, $data);
    }, 10, 3);
}
add_action('init', 'lr_register_developer_hooks');

/**
 * Debug function for development
 * 
 * @param mixed $data Data to debug
 * @param string $label Debug label
 */
function lr_debug($data, $label = 'LR Debug') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($label . ': ' . print_r($data, true));
    }
}

/**
 * Check if current user can manage restaurants
 * 
 * @return bool
 */
function lr_current_user_can_manage_restaurants() {
    return current_user_can('edit_posts') && current_user_can('edit_others_posts');
}

/**
 * Get plugin information
 * 
 * @return array Plugin info
 */
function lr_get_plugin_info() {
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    return get_plugin_data(LR_PLUGIN_DIR . 'lista-restauracji.php');
}

/**
 * Check plugin requirements
 * 
 * @return array Requirements status
 */
function lr_check_requirements() {
    $requirements = array(
        'php_version' => version_compare(PHP_VERSION, '7.4', '>='),
        'wordpress_version' => version_compare(get_bloginfo('version'), '5.0', '>='),
        'google_maps_api' => !empty(get_option('lr_google_maps_api_key')),
        'curl_enabled' => function_exists('curl_init'),
        'json_enabled' => function_exists('json_encode')
    );
    
    return apply_filters('lr_requirements_check', $requirements);
}