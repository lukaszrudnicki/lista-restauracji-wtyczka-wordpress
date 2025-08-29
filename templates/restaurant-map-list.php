<?php
/**
 * Template for restaurant map and list display
 * Compatible with existing plugin structure
 */

// Funkcja pomocnicza - jeśli nie istnieje
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

// Pobierz wszystkie restauracje dla filtra miast
$restaurants = get_posts(array(
    'post_type' => 'restauracje',
    'posts_per_page' => -1,
    'post_status' => 'publish'
));

// Pobierz unikalne miasta
$cities = array();
foreach ($restaurants as $restaurant) {
    $city = get_post_meta($restaurant->ID, '_restaurant_city', true);
    if (!empty($city) && !in_array($city, $cities)) {
        $cities[] = $city;
    }
}
sort($cities);

// Pobierz parametry z shortcode (jeśli przekazane)
$lr_atts = get_query_var('lr_atts', array());
$lr_unique_id = get_query_var('lr_unique_id', 'lr-default-' . rand(1000, 9999));

// Wartości domyślne
$show_images = isset($lr_atts['show_images']) ? $lr_atts['show_images'] : 'yes';
$show_city_filter = isset($lr_atts['show_city_filter']) ? $lr_atts['show_city_filter'] : 'yes';
$city_filter_type = isset($lr_atts['city_filter_type']) ? $lr_atts['city_filter_type'] : 'dropdown';
$display_mode = isset($lr_atts['display_mode']) ? $lr_atts['display_mode'] : 'both';
$columns_desktop = isset($lr_atts['columns_desktop']) ? $lr_atts['columns_desktop'] : '4';
$map_height = isset($lr_atts['map_height']) ? intval($lr_atts['map_height']) : 400;
$el_class = isset($lr_atts['el_class']) ? $lr_atts['el_class'] : '';

// Klasy CSS
$container_classes = array('lr-plugin-container');
if (!empty($el_class)) {
    $container_classes[] = $el_class;
}
$column_class = lr_get_column_class($columns_desktop);
?>

<div id="<?php echo esc_attr($lr_unique_id); ?>" class="<?php echo esc_attr(implode(' ', $container_classes)); ?>">
    
    <?php if ($show_city_filter === 'yes' && ($display_mode === 'both' || $display_mode === 'list_only') && !empty($cities)): ?>
    <div class="lr-restaurant-filter">
        <label for="lr-city-filter-<?php echo esc_attr($lr_unique_id); ?>">
            Filtruj po mieście:
        </label>
        
        <?php if ($city_filter_type === 'badges'): ?>
        <div id="lr-city-badges-<?php echo esc_attr($lr_unique_id); ?>" class="lr-city-badges">
            <span class="lr-city-badge active" data-city="">Wszystkie miasta</span>
            <?php foreach ($cities as $city): ?>
                <span class="lr-city-badge" data-city="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <select id="lr-city-filter-<?php echo esc_attr($lr_unique_id); ?>" class="lr-form-control lr-city-filter">
            <option value="">Wszystkie miasta</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($display_mode === 'both' || $display_mode === 'map_only'): ?>
    <div id="lr-restaurant-map-<?php echo esc_attr($lr_unique_id); ?>" 
         class="lr-restaurant-map" 
         style="width: 100%; height: <?php echo $map_height; ?>px; margin-bottom: 30px;">
    </div>
    <?php endif; ?>

    <?php if ($display_mode === 'both' || $display_mode === 'list_only'): ?>
    <div id="lr-restaurant-list-<?php echo esc_attr($lr_unique_id); ?>" 
         class="lr-restaurant-list lr-row lr-row-equal-height">
         <!-- Restauracje będą załadowane przez JavaScript -->
    </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div class="lr-modal" id="lr-restaurantModal-<?php echo esc_attr($lr_unique_id); ?>">
    <div class="lr-modal__content">
        <span class="lr-modal__close">&times;</span>
        <div id="lr-restaurantModalBody-<?php echo esc_attr($lr_unique_id); ?>">
            <!-- Zawartość modalu będzie dynamicznie generowana przez JavaScript -->
        </div>
    </div>
</div>

<?php if ($show_images !== 'yes'): ?>
<style>
#<?php echo esc_attr($lr_unique_id); ?> .lr-card__img-container {
    display: none !important;
}
#<?php echo esc_attr($lr_unique_id); ?> .lr-modal__image-container {
    display: none !important;
}
</style>
<?php endif; ?>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Ustawienia dla tej instancji
    var settings = {
        display_mode: '<?php echo esc_js($display_mode); ?>',
        show_images: '<?php echo esc_js($show_images); ?>',
        show_city_filter: '<?php echo esc_js($show_city_filter); ?>',
        city_filter_type: '<?php echo esc_js($city_filter_type); ?>',
        columns_desktop: '<?php echo esc_js($columns_desktop); ?>',
        map_height: '<?php echo intval($map_height); ?>',
        map_zoom: 5,
        map_center_lat: 52.0692,
        map_center_lng: 19.4803,
        show_fields: 'address,city,phone,hours'
    };
    
    // Przekaż ustawienia do głównej funkcji (jeśli istnieje)
    if (typeof initRestaurantMapInstance === 'function') {
        // Dodaj ustawienia do elementu DOM
        document.getElementById('<?php echo esc_js($lr_unique_id); ?>').setAttribute('data-settings', JSON.stringify(settings));
        initRestaurantMapInstance('<?php echo esc_js($lr_unique_id); ?>');
    } else {
        // Fallback - podstawowa inicjalizacja
        console.log('Restaurant map settings:', settings);
    }
});
</script>