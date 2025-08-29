<?php
$restaurants = get_posts(array(
    'post_type' => 'restauracje',
    'posts_per_page' => -1
));

$cities = array_unique(array_filter(array_map(function($restaurant) {
    return get_post_meta($restaurant->ID, '_restaurant_city', true);
}, $restaurants)));
sort($cities);
?>

<div class="lr-plugin-container">
    <div class="lr-restaurant-filter">
        <label for="lr-city-filter">Filtruj po mieście:</label>
        <select id="lr-city-filter" class="lr-form-control">
            <option value="">Wszystkie miasta</option>
            <?php foreach ($cities as $city): ?>
                <?php if (!empty($city)): ?>
                    <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="lr-restaurant-map" style="width: 100%; height: 400px;"></div>

    <div id="lr-restaurant-list" class="lr-row"></div>
</div>

<div class="lr-modal" id="lr-restaurantModal">
    <div class="lr-modal__content">
        <span class="lr-modal__close">&times;</span>
        <div id="lr-restaurantModalBody">
            <!-- Zawartość modalu będzie dynamicznie generowana przez JavaScript -->
        </div>
    </div>
</div>