jQuery(document).ready(function($) {
    var map;
    var markers = [];
    var markerCluster;
    var allRestaurants = [];

    function initMap() {
        map = new google.maps.Map(document.getElementById('lr-restaurant-map'), {
            center: {lat: 52.0692, lng: 19.4803},
            streetViewControl: false,
            zoom: 5
    });

        fetchRestaurants();
    }

    function fetchRestaurants() {
        $.ajax({
            url: lr_data.ajax_url,
            method: 'POST',
            data: {
                action: 'get_restaurants',
                nonce: lr_data.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    allRestaurants = response.data;
                    allRestaurants.forEach(addMarker);
                    setupMarkerClusterer();
                    generateRestaurantList(allRestaurants);
                } else {
                    console.error("Błąd podczas pobierania restauracji:", response);
                }
            },
            error: function(xhr, status, error) {
                console.error("Błąd podczas pobierania restauracji:", error);
            }
        });
    }

    function addMarker(restaurant) {
        var lat = parseFloat(restaurant.latitude);
        var lng = parseFloat(restaurant.longitude);
    
        if (isNaN(lat) || isNaN(lng)) {
            console.warn("Nieprawidłowe współrzędne dla restauracji:", restaurant.title);
            return null;
        }
    
        var markerOptions = {
            position: {lat: lat, lng: lng},
            map: map,
            title: restaurant.title
        };
    
        // Dodaj customową ikonę, jeśli jest zdefiniowana
        if (lr_data.marker_icon) {
            markerOptions.icon = lr_data.marker_icon;
        }
    
        var marker = new google.maps.Marker(markerOptions);
    
        marker.addListener('click', function() {
            showRestaurantModal(restaurant);
        });
    
        markers.push(marker);
        return marker;
    }

    function setupMarkerClusterer() {
        if (markerCluster) {
            markerCluster.clearMarkers();
        }
        
        var clusterStyles = [{
            textColor: 'white',
            textSize: 18,
            fontFamily: 'inherit',
            height: 53,
            width: 53,
            textAlign: 'center',
            anchorText: [16, 1], // Wyśrodkowanie tekstu
            anchorIcon: [27, 27]
        }];
    
        if (lr_data.cluster_icon) {
            clusterStyles[0].url = lr_data.cluster_icon;
        }
    
        var clusterOptions = {
            gridSize: 60,
            styles: clusterStyles
        };
    
        markerCluster = new MarkerClusterer(map, markers, clusterOptions);
    
        // Dodaj niestandardowy styl CSS dla lepszego centrowania
        var style = document.createElement('style');
        style.textContent = `
            .cluster-marker {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .cluster-marker > * {
                position: static !important;
                transform: none !important;
            }
        `;
        document.head.appendChild(style);
    }
    

    function showRestaurantModal(restaurant) {
        var modalContent = `
            <div class="lr-modal__image-container">
                <img src="${restaurant.image}" alt="${restaurant.title}" class="lr-modal__image">
            </div>
            <div class="lr-modal__info">
                <h3 class="lr-modal__title">${restaurant.title}</h3>
                <p><strong>Adres:</strong> ${restaurant.address}</p>
                <p><strong>Miasto:</strong> ${restaurant.city}</p>
                <p><strong>Telefon:</strong> ${restaurant.phone}</p>
                <p><strong>Godziny otwarcia:</strong><br>${restaurant.opening_hours}</p>
            </div>
        `;
    
        $('#lr-restaurantModalBody').html(modalContent);
        $('#lr-restaurantModal').css('display', 'block');
        $('.lr-plugin-container').addClass('lr-blur');
    }

    function generateRestaurantList(restaurants) {
        var $list = $('#lr-restaurant-list');
        $list.empty();
        $list.addClass('lr-row-equal-height');
    
        restaurants.forEach(function(restaurant) {
            $list.append(`
                <div class="lr-col-md-3">
                    <div class="lr-card" data-restaurant-id="${restaurant.id}">
                        <div class="lr-card__img-container">
                            <img src="${restaurant.image}" alt="${restaurant.title}" class="lr-card__img">
                        </div>
                        <div class="lr-card__body">
                            <h5 class="lr-card__title">${restaurant.title}</h5>
                            <p class="lr-card__text">${restaurant.address}</p>
                            <p class="lr-card__text">${restaurant.city}</p>
                        </div>
                    </div>
                </div>
            `);
        });
    
        $list.on('click', '.lr-card', function() {
            var restaurantId = $(this).data('restaurant-id');
            var restaurant = allRestaurants.find(r => r.id == restaurantId);
            if (restaurant) {
                showRestaurantModal(restaurant);
            } else {
                console.error('Nie znaleziono restauracji o ID:', restaurantId);
            }
        });
    }

    function filterRestaurants(selectedCity) {
        markers.forEach(function(marker) {
            marker.setMap(null);
        });
        markers = [];
    
        var filteredRestaurants = allRestaurants.filter(function(restaurant) {
            return selectedCity === '' || restaurant.city === selectedCity;
        });
    
        filteredRestaurants.forEach(function(restaurant) {
            var marker = addMarker(restaurant);
            if (marker) {
                markers.push(marker);
            }
        });
    
        if (markerCluster) {
            markerCluster.clearMarkers();
            markerCluster.addMarkers(markers);
        } else {
            setupMarkerClusterer();
        }
    
        if (markers.length > 0) {
            var bounds = new google.maps.LatLngBounds();
            markers.forEach(function(marker) {
                bounds.extend(marker.getPosition());
            });
    
            // Ustawienie maksymalnego poziomu przybliżenia
            var maxZoom = 12; // Możesz dostosować tę wartość
    
            map.fitBounds(bounds);
    
            // Ograniczenie poziomu przybliżenia
            google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
                if (map.getZoom() > maxZoom) {
                    map.setZoom(maxZoom);
                }
            });
        }
    
        generateRestaurantList(filteredRestaurants);
    }
    function closeRestaurantModal() {
        $('#lr-restaurantModal').css('display', 'none');
        $('.lr-plugin-container').removeClass('lr-blur');
        $('body').css('overflow', '');
    }
    $('#lr-city-filter').on('change', function() {
        var selectedCity = $(this).val();
        filterRestaurants(selectedCity);
    });

// Definiujemy funkcję zamykającą modal
function closeRestaurantModal() {
    $('#lr-restaurantModal').css('display', 'none');
    $('.lr-plugin-container').removeClass('lr-blur');
    $('body').css('overflow', ''); // Przywraca możliwość scrollowania strony
}

// Obsługa kliknięcia przycisku zamykania
$('.lr-modal__close').on('click', closeRestaurantModal);

// Obsługa kliknięcia poza modalem
$(window).on('click', function(event) {
    if (event.target == document.getElementById('lr-restaurantModal')) {
        closeRestaurantModal();
    }
});

    // Inicjalizacja mapy po załadowaniu API Google Maps
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initMap();
    } else {
        console.error('Google Maps API nie jest załadowane');
    }
});