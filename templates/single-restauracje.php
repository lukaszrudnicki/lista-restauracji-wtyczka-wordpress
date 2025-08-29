<?php
/**
 * Template for displaying single restaurant
 * Lista Restauracji Plugin
 * File: templates/single-restauracje.php
 */

get_header(); 

// Pobierz dane restauracji
$restaurant_id = get_the_ID();
$address = get_post_meta($restaurant_id, '_restaurant_address', true);
$city = get_post_meta($restaurant_id, '_restaurant_city', true);
$phone = get_post_meta($restaurant_id, '_restaurant_phone', true);
$opening_hours = get_post_meta($restaurant_id, '_restaurant_opening_hours', true);
$latitude = get_post_meta($restaurant_id, '_restaurant_latitude', true);
$longitude = get_post_meta($restaurant_id, '_restaurant_longitude', true);

// Pobierz obrazek restauracji
$featured_image = get_the_post_thumbnail_url($restaurant_id, 'large');
if (empty($featured_image)) {
    $featured_image = get_option('lr_default_image', '');
}
?>

<div class="lr-single-restaurant">
    <div class="container">
        <div class="lr-single-content">
            
            <!-- Nagłówek z obrazem -->
            <div class="lr-single-header">
                <?php if (!empty($featured_image)): ?>
                <div class="lr-single-image">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php the_title(); ?>" />
                </div>
                <?php endif; ?>
                
                <div class="lr-single-title-wrap">
                    <h1 class="lr-single-title"><?php the_title(); ?></h1>
                    <?php if (!empty($city)): ?>
                        <p class="lr-single-city"><?php echo esc_html($city); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informacje o restauracji -->
            <div class="lr-single-info">
                <div class="lr-single-details">
                    
                    <?php if (!empty($address)): ?>
                    <div class="lr-single-detail">
                        <h3>Adres</h3>
                        <p><?php echo esc_html($address); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($phone)): ?>
                    <div class="lr-single-detail">
                        <h3>Telefon</h3>
                        <p><a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($opening_hours)): ?>
                    <div class="lr-single-detail">
                        <h3>Godziny otwarcia</h3>
                        <div class="lr-opening-hours">
                            <?php echo wpautop(wp_kses_post($opening_hours)); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Mapa -->
                <?php if (!empty($latitude) && !empty($longitude)): ?>
                <div class="lr-single-map-container">
                    <h3>Lokalizacja</h3>
                    <div id="lr-single-map" style="width: 100%; height: 400px; border-radius: 8px; overflow: hidden;"></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Powrót do listy -->
            <div class="lr-single-back">
                <a href="javascript:history.back()" class="lr-back-button">← Powrót do listy restauracji</a>
            </div>

        </div>
    </div>
</div>

<style>
.lr-single-restaurant {
    padding: 40px 0;
    background-color: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.lr-single-restaurant .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.lr-single-content {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.lr-single-header {
    position: relative;
}

.lr-single-image {
    width: 100%;
    height: 300px;
    overflow: hidden;
    background: #f0f0f0;
}

.lr-single-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lr-single-title-wrap {
    padding: 30px 40px 20px;
}

.lr-single-title {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin: 0 0 10px 0;
    line-height: 1.3;
}

.lr-single-city {
    font-size: 18px;
    color: #666;
    margin: 0;
}

.lr-single-info {
    padding: 0 40px 40px;
}

.lr-single-details {
    margin-bottom: 40px;
}

.lr-single-detail {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.lr-single-detail:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.lr-single-detail h3 {
    font-size: 18px;
    font-weight: 600;
    color: #007bff;
    margin: 0 0 10px 0;
}

.lr-single-detail p,
.lr-opening-hours {
    font-size: 16px;
    line-height: 1.6;
    color: #555;
    margin: 0;
}

.lr-single-detail a {
    color: #007bff;
    text-decoration: none;
}

.lr-single-detail a:hover {
    text-decoration: underline;
}

.lr-opening-hours p {
    margin: 8px 0;
}

.lr-opening-hours strong {
    color: #333;
}

.lr-single-map-container {
    margin-top: 30px;
}

.lr-single-map-container h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 15px 0;
}

.lr-single-back {
    padding: 30px 40px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

.lr-back-button {
    display: inline-block;
    padding: 12px 24px;
    background: #007bff;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.lr-back-button:hover {
    background: #0056b3;
    color: #fff;
}

@media (max-width: 768px) {
    .lr-single-restaurant {
        padding: 20px 0;
    }
    
    .lr-single-restaurant .container {
        padding: 0 15px;
    }
    
    .lr-single-title-wrap,
    .lr-single-info,
    .lr-single-back {
        padding-left: 20px;
        padding-right: 20px;
    }
    
    .lr-single-title {
        font-size: 24px;
    }
    
    .lr-single-city {
        font-size: 16px;
    }
    
    .lr-single-image {
        height: 200px;
    }
}
</style>

<?php if (!empty($latitude) && !empty($longitude)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sprawdź czy Google Maps API jest załadowane
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initSingleRestaurantMap();
    } else {
        // Jeśli nie ma API, spróbuj załadować
        var apiKey = '<?php echo get_option("lr_google_maps_api_key"); ?>';
        if (apiKey) {
            var script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&callback=initSingleRestaurantMap';
            document.head.appendChild(script);
        }
    }
});

function initSingleRestaurantMap() {
    var lat = <?php echo floatval($latitude); ?>;
    var lng = <?php echo floatval($longitude); ?>;
    var title = '<?php echo esc_js(get_the_title()); ?>';
    
    var map = new google.maps.Map(document.getElementById('lr-single-map'), {
        center: {lat: lat, lng: lng},
        zoom: 15,
        streetViewControl: false,
        mapTypeControl: false
    });
    
    var markerOptions = {
        position: {lat: lat, lng: lng},
        map: map,
        title: title
    };
    
    // Użyj ikony z ustawień jeśli dostępna
    var markerIcon = '<?php echo get_option("lr_marker_icon"); ?>';
    if (markerIcon) {
        markerOptions.icon = markerIcon;
    }
    
    var marker = new google.maps.Marker(markerOptions);
    
    var infoWindow = new google.maps.InfoWindow({
        content: '<div style="padding: 10px;"><h4 style="margin: 0 0 5px 0;">' + title + '</h4></div>'
    });
    
    marker.addListener('click', function() {
        infoWindow.open(map, marker);
    });
}
</script>
<?php endif; ?>

<?php get_footer(); ?>