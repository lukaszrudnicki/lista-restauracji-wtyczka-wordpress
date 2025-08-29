jQuery(document).ready(function($) {
    var map;
    var marker;
    var geocoder;
    var autocomplete;

    function initMap() {
        geocoder = new google.maps.Geocoder();
        var lat = parseFloat($('#restaurant_latitude').val()) || 52.0692;
        var lng = parseFloat($('#restaurant_longitude').val()) || 19.4803;

        map = new google.maps.Map(document.getElementById('restaurant_map'), {
            center: {lat: lat, lng: lng},
            zoom: 8
        });

        marker = new google.maps.Marker({
            position: {lat: lat, lng: lng},
            map: map,
            draggable: true
        });

        google.maps.event.addListener(marker, 'dragend', function() {
            var pos = marker.getPosition();
            updateLatLng(pos.lat(), pos.lng());
            reverseGeocode(pos);
        });

        initAutocomplete();
    }

    function initAutocomplete() {
        var input = document.getElementById('restaurant_map_address');
        autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['geocode', 'establishment'],
            componentRestrictions: {country: 'pl'}
        });

        autocomplete.bindTo('bounds', map);

        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) {
                console.log("Nie można znaleźć wybranego miejsca");
                return;
            }

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }

            marker.setPosition(place.geometry.location);
            updateLatLng(place.geometry.location.lat(), place.geometry.location.lng());
        });
    }

    function updateLatLng(lat, lng) {
        $('#restaurant_latitude').val(lat.toFixed(6));
        $('#restaurant_longitude').val(lng.toFixed(6));
    }

    function reverseGeocode(latlng) {
        geocoder.geocode({'location': latlng}, function(results, status) {
            if (status === 'OK') {
                if (results[0]) {
                    $('#restaurant_map_address').val(results[0].formatted_address);
                }
            }
        });
    }

    $('#find_on_map').on('click', function(e) {
        e.preventDefault();
        var address = $('#restaurant_map_address').val();
        geocoder.geocode({'address': address}, function(results, status) {
            if (status === 'OK') {
                map.setCenter(results[0].geometry.location);
                marker.setPosition(results[0].geometry.location);
                map.setZoom(15);
                updateLatLng(results[0].geometry.location.lat(), results[0].geometry.location.lng());
            } else {
                console.log('Geocode was not successful for the following reason: ' + status);
            }
        });
    });

    // Inicjalizacja mapy po załadowaniu API Google Maps
    if (typeof google !== 'undefined') {
        initMap();
    } else {
        console.error('Google Maps API nie jest załadowane');
    }
});