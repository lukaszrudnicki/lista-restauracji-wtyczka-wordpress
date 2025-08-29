jQuery(document).ready(function($) {
    // Sprawdź czy Google Maps jest dostępne
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.warn('Google Maps API nie jest załadowane');
        return;
    }

    // Znajdź wszystkie instancje mapy restauracji
    $('.lr-plugin-container[id^="lr-instance-"]').each(function() {
        var $container = $(this);
        var instanceId = $container.attr('id');
        
        // Pobierz ustawienia z data-settings lub użyj domyślnych
        var settings = $container.data('settings') || {
            display_mode: 'both',
            show_images: 'yes',
            map_zoom: 5,
            map_center_lat: 52.0692,
            map_center_lng: 19.4803,
            show_fields: 'address,city,phone,hours'
        };
        
        var instance = {
            id: instanceId,
            settings: settings,
            map: null,
            markers: [],
            markerCluster: null,
            allRestaurants: [],
            $container: $container
        };
        
        // Inicjalizuj mapę jeśli potrzebna
        if (settings.display_mode === 'both' || settings.display_mode === 'map_only') {
            initMap(instance);
        }
        
        // Pobierz dane restauracji
        fetchRestaurants(instance);
        
        // Obsługa filtra miasta
        setupCityFilter(instance);
        
        // Obsługa zamykania modala
        setupModalHandlers(instance);
    });

    function initMap(instance) {
        var mapElement = document.getElementById('lr-restaurant-map-' + instance.id);
        if (!mapElement) return;

        var centerLat = parseFloat(instance.settings.map_center_lat) || 52.0692;
        var centerLng = parseFloat(instance.settings.map_center_lng) || 19.4803;
        var zoom = parseInt(instance.settings.map_zoom) || 5;

        instance.map = new google.maps.Map(mapElement, {
            center: {lat: centerLat, lng: centerLng},
            streetViewControl: false,
            zoom: zoom
        });
    }

    function fetchRestaurants(instance) {
        $.ajax({
            url: lr_data.ajax_url,
            method: 'POST',
            data: {
                action: 'get_restaurants',
                nonce: lr_data.nonce,
                limit: instance.settings.limit || -1
            },
            success: function(response) {
                if (response.success && response.data) {
                    instance.allRestaurants = response.data;
                    
                    if (instance.map) {
                        instance.allRestaurants.forEach(function(restaurant) {
                            addMarker(instance, restaurant);
                        });
                        setupMarkerCluster(instance);
                    }
                    
                    generateRestaurantList(instance, instance.allRestaurants);
                } else {
                    console.error("Błąd podczas pobierania restauracji:", response);
                }
            },
            error: function(xhr, status, error) {
                console.error("Błąd AJAX:", error);
            }
        });
    }

    function addMarker(instance, restaurant) {
        if (!instance.map) return null;

        var lat = parseFloat(restaurant.latitude);
        var lng = parseFloat(restaurant.longitude);

        if (isNaN(lat) || isNaN(lng)) {
            return null;
        }

        var markerOptions = {
            position: {lat: lat, lng: lng},
            map: instance.map,
            title: restaurant.title
        };

        if (lr_data.marker_icon) {
            markerOptions.icon = lr_data.marker_icon;
        }

        var marker = new google.maps.Marker(markerOptions);

        marker.addListener('click', function() {
            showRestaurantModal(instance, restaurant);
        });

        instance.markers.push(marker);
        return marker;
    }

    function setupMarkerCluster(instance) {
        if (!instance.map || !instance.markers.length) return;
        
        if (typeof MarkerClusterer === 'undefined') {
            console.warn('MarkerClusterer nie jest dostępny');
            return;
        }

        if (instance.markerCluster) {
            instance.markerCluster.clearMarkers();
        }

        var clusterStyles = [{
            textColor: '#000000', // Czarny kolor tekstu
            textSize: 14,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            height: 52,
            width: 53,
            anchorText: [15, 0], // Wyśrodkowanie tekstu (połowa szerokości i wysokości)
            anchorIcon: [26, 26]  // Wyśrodkowanie ikony
        }];

        if (lr_data.cluster_icon) {
            clusterStyles[0].url = lr_data.cluster_icon;
        }

        instance.markerCluster = new MarkerClusterer(instance.map, instance.markers, {
            gridSize: 60,
            styles: clusterStyles
        });
    }

    function generateRestaurantList(instance, restaurants) {
        var $list = $('#lr-restaurant-list-' + instance.id);
        if (!$list.length) return;

        $list.empty();

        if (!restaurants || restaurants.length === 0) {
            $list.html('<div class="lr-no-restaurants"><p>Brak restauracji do wyświetlenia.</p></div>');
            return;
        }

        var showImages = instance.settings.show_images === 'yes';
        var showFields = (instance.settings.show_fields || '').split(',');

        restaurants.forEach(function(restaurant) {
            var imageHtml = '';
            if (showImages && restaurant.image) {
                imageHtml = `
                    <div class="lr-card__img-container">
                        <img src="${restaurant.image}" alt="${restaurant.title}" class="lr-card__img">
                    </div>
                `;
            }

            var fieldsHtml = '';
            showFields.forEach(function(field) {
                field = field.trim();
                if (field === 'address' && restaurant.address) {
                    fieldsHtml += `<p class="lr-card__text">${restaurant.address}</p>`;
                } else if (field === 'city' && restaurant.city) {
                    fieldsHtml += `<p class="lr-card__text">${restaurant.city}</p>`;
                } else if (field === 'phone' && restaurant.phone) {
                    fieldsHtml += `<p class="lr-card__text">${restaurant.phone}</p>`;
                }
            });

            $list.append(`
                <div class="lr-col-md-3">
                    <div class="lr-card" data-restaurant-id="${restaurant.id}">
                        ${imageHtml}
                        <div class="lr-card__body">
                            <h5 class="lr-card__title">${restaurant.title}</h5>
                            ${fieldsHtml}
                        </div>
                    </div>
                </div>
            `);
        });

        // Obsługa kliknięć w karty
        $list.off('click.lr-card').on('click.lr-card', '.lr-card', function() {
            var restaurantId = $(this).data('restaurant-id');
            var restaurant = instance.allRestaurants.find(r => r.id == restaurantId);
            if (restaurant) {
                showRestaurantModal(instance, restaurant);
            }
        });
    }

    function showRestaurantModal(instance, restaurant) {
        var showImages = instance.settings.show_images === 'yes';
        var showFields = (instance.settings.show_fields || '').split(',');

        var imageHtml = '';
        if (showImages && restaurant.image) {
            imageHtml = `
                <div class="lr-modal__image-container">
                    <img src="${restaurant.image}" alt="${restaurant.title}" class="lr-modal__image">
                </div>
            `;
        }

        var fieldsHtml = '';
        showFields.forEach(function(field) {
            field = field.trim();
            if (field === 'address' && restaurant.address) {
                fieldsHtml += `<p><strong>Adres:</strong> ${restaurant.address}</p>`;
            } else if (field === 'city' && restaurant.city) {
                fieldsHtml += `<p><strong>Miasto:</strong> ${restaurant.city}</p>`;
            } else if (field === 'phone' && restaurant.phone) {
                fieldsHtml += `<p><strong>Telefon:</strong> ${restaurant.phone}</p>`;
            } else if (field === 'hours' && restaurant.opening_hours) {
                fieldsHtml += `<p><strong>Godziny otwarcia:</strong><br>${restaurant.opening_hours}</p>`;
            }
        });

        var modalContent = `
            ${imageHtml}
            <div class="lr-modal__info">
                <h3 class="lr-modal__title">${restaurant.title}</h3>
                ${fieldsHtml}
            </div>
        `;

        $('#lr-restaurantModalBody-' + instance.id).html(modalContent);
        $('#lr-restaurantModal-' + instance.id).css('display', 'block');
        instance.$container.addClass('lr-blur');
    }

    function setupCityFilter(instance) {
        var $cityFilter = $('#lr-city-filter-' + instance.id);
        var $cityBadges = $('#lr-city-badges-' + instance.id);
        
        // Obsługa dropdown filtra
        if ($cityFilter.length) {
            $cityFilter.on('change', function() {
                filterByCity(instance, $(this).val());
            });
        }
        
        // Obsługa badge filtra
        if ($cityBadges.length) {
            $cityBadges.on('click', '.lr-city-badge', function() {
                var city = $(this).data('city');
                
                // Usuń aktywną klasę z innych badge'ów
                $cityBadges.find('.lr-city-badge').removeClass('active');
                
                // Dodaj aktywną klasę do klikniętego badge'a
                $(this).addClass('active');
                
                filterByCity(instance, city);
            });
        }
    }
    
    function filterByCity(instance, selectedCity) {
        // Wyczyść markery
        if (instance.map) {
            instance.markers.forEach(function(marker) {
                marker.setMap(null);
            });
            instance.markers = [];
        }

        // Przefiltruj restauracje
        var filteredRestaurants = instance.allRestaurants.filter(function(restaurant) {
            return selectedCity === '' || restaurant.city === selectedCity;
        });

        // Dodaj markery z powrotem
        if (instance.map) {
            filteredRestaurants.forEach(function(restaurant) {
                var marker = addMarker(instance, restaurant);
                if (marker) {
                    instance.markers.push(marker);
                }
            });
            setupMarkerCluster(instance);
        }

        // Zaktualizuj listę
        generateRestaurantList(instance, filteredRestaurants);
    }

    function setupModalHandlers(instance) {
        // Zamykanie przez X
        $(document).on('click', '#lr-restaurantModal-' + instance.id + ' .lr-modal__close', function() {
            closeModal(instance);
        });

        // Zamykanie przez kliknięcie w tło
        $(document).on('click', '#lr-restaurantModal-' + instance.id, function(event) {
            if (event.target === this) {
                closeModal(instance);
            }
        });
    }

    function closeModal(instance) {
        $('#lr-restaurantModal-' + instance.id).css('display', 'none');
        instance.$container.removeClass('lr-blur');
    }

    // Globalna funkcja dla kompatybilności
    window.initRestaurantMapInstance = function(instanceId) {
        // Ta funkcja jest wywoływana z template, ale logika jest już w $(document).ready
        console.log('Initializing restaurant map instance:', instanceId);
    };
});