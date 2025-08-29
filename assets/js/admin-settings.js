jQuery(document).ready(function($) {
    
    // Media uploader dla ikon
    $('.lr-upload-button').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var targetInput = $('#' + button.data('target'));
        
        var mediaUploader = wp.media({
            title: 'Wybierz obrazek',
            button: {
                text: 'Użyj tego obrazka'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            targetInput.val(attachment.url);
            updatePreview(button.data('target'), attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // Aktualizacja podglądu ikon
    function updatePreview(target, url) {
        var previewId = '';
        if (target === 'lr_marker_icon') {
            previewId = '#marker-preview';
        } else if (target === 'lr_cluster_icon') {
            previewId = '#cluster-preview';
        }
        
        if (previewId && url) {
            $(previewId).html('<img src="' + url + '" alt="Icon preview" style="max-width: 50px;">');
        }
    }
    
    // Obsługa zakładek
    $('.lr-tab-link').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href');
        
        // Usuń aktywne klasy
        $('.lr-tab-link').removeClass('active');
        $('.lr-tab-pane').removeClass('active');
        
        // Dodaj aktywne klasy
        $(this).addClass('active');
        $(targetTab).addClass('active');
        
        // Zapisz aktywną zakładkę w localStorage
        localStorage.setItem('lr_active_tab', targetTab);
    });
    
    // Przywróć ostatnią aktywną zakładkę
    var activeTab = localStorage.getItem('lr_active_tab');
    if (activeTab && $(activeTab).length) {
        $('.lr-tab-link[href="' + activeTab + '"]').trigger('click');
    }
    
    // Walidacja formularza
    $('.lr-settings-form').on('submit', function(e) {
        var apiKey = $('#lr_google_maps_api_key').val().trim();
        
        if (!apiKey) {
            if (!confirm('Nie ustawiono klucza API Google Maps. Mapa nie będzie działać. Czy chcesz kontynuować?')) {
                e.preventDefault();
                return false;
            }
        }
        
        // Walidacja URL-i
        var urlFields = ['lr_marker_icon', 'lr_cluster_icon', 'lr_default_image'];
        var hasInvalidUrl = false;
        
        urlFields.forEach(function(fieldId) {
            var url = $('#' + fieldId).val().trim();
            if (url && !isValidUrl(url)) {
                $('#' + fieldId).addClass('lr-invalid-url');
                hasInvalidUrl = true;
            } else {
                $('#' + fieldId).removeClass('lr-invalid-url');
            }
        });
        
        if (hasInvalidUrl) {
            alert('Niektóre URL-e wydają się nieprawidłowe. Sprawdź zaznaczone pola.');
            e.preventDefault();
            return false;
        }
    });
    
    // Funkcja walidacji URL
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Automatyczne generowanie przykładów shortcode
    $('#generate-shortcode').on('click', function(e) {
        e.preventDefault();
        
        var shortcode = '[restauracje_mapa_lista';
        var params = [];
        
        // Zbierz parametry z formularza (jeśli byłyby)
        if ($('#example-display-mode').val() !== 'both') {
            params.push('display_mode="' + $('#example-display-mode').val() + '"');
        }
        
        if (params.length > 0) {
            shortcode += ' ' + params.join(' ');
        }
        
        shortcode += ']';
        
        $('#generated-shortcode').val(shortcode).select();
    });
    
    // Copy shortcode to clipboard
    $(document).on('click', '.lr-copy-shortcode', function(e) {
        e.preventDefault();
        
        var shortcode = $(this).prev('code').text();
        var tempInput = $('<textarea>');
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Pokaż potwierdzenie
        var originalText = $(this).text();
        $(this).text('Skopiowano!').addClass('lr-copied');
        
        setTimeout(() => {
            $(this).text(originalText).removeClass('lr-copied');
        }, 2000);
    });
    
    // Testowanie połączenia z Google Maps API
    $('#test-api-key').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var apiKey = $('#lr_google_maps_api_key').val().trim();
        
        if (!apiKey) {
            alert('Wprowadź klucz API przed testowaniem.');
            return;
        }
        
        button.prop('disabled', true).text('Testowanie...');
        
        // Sprawdź czy można załadować Google Maps z tym kluczem
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&callback=testApiCallback';
        script.onerror = function() {
            button.prop('disabled', false).text('Testuj klucz API');
            alert('Błąd ładowania API. Sprawdź klucz.');
        };
        
        window.testApiCallback = function() {
            button.prop('disabled', false).text('Testuj klucz API');
            alert('Klucz API działa poprawnie!');
            // Usuń skrypt testowy
            document.head.removeChild(script);
        };
        
        document.head.appendChild(script);
    });
    
    // Accordion dla przykładów
    $('.lr-example h4').on('click', function() {
        $(this).next('code').slideToggle();
    });
    
    // Tooltip dla parametrów
    $('.lr-parameter-help').hover(
        function() {
            var tooltip = $('<div class="lr-tooltip">' + $(this).data('help') + '</div>');
            $('body').append(tooltip);
            
            var pos = $(this).offset();
            tooltip.css({
                top: pos.top - tooltip.outerHeight() - 5,
                left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
            });
        },
        function() {
            $('.lr-tooltip').remove();
        }
    );
});