<?php
class Settings_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_settings_page() {
        add_options_page(
            __('Ustawienia Listy Restauracji', 'lista-restauracji'),
            __('Lista Restauracji', 'lista-restauracji'),
            'manage_options',
            'lista-restauracji-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        // Grupa ustawień głównych
        register_setting('lr_settings_group', 'lr_google_maps_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('lr_settings_group', 'lr_marker_icon', array(
            'sanitize_callback' => 'esc_url_raw'
        ));
        register_setting('lr_settings_group', 'lr_cluster_icon', array(
            'sanitize_callback' => 'esc_url_raw'
        ));
        register_setting('lr_settings_group', 'lr_default_image', array(
            'sanitize_callback' => 'esc_url_raw'
        ));

        // Sekcje ustawień
        add_settings_section(
            'lr_main_section',
            __('Główne ustawienia', 'lista-restauracji'),
            array($this, 'main_section_callback'),
            'lista-restauracji-settings'
        );

        add_settings_section(
            'lr_map_section',
            __('Ustawienia mapy', 'lista-restauracji'),
            array($this, 'map_section_callback'),
            'lista-restauracji-settings'
        );

        add_settings_section(
            'lr_display_section',
            __('Ustawienia wyświetlania', 'lista-restauracji'),
            array($this, 'display_section_callback'),
            'lista-restauracji-settings'
        );

        // Pola ustawień - Główne
        add_settings_field(
            'lr_google_maps_api_key',
            __('Klucz API Google Maps', 'lista-restauracji'),
            array($this, 'api_key_callback'),
            'lista-restauracji-settings',
            'lr_main_section'
        );

        // Pola ustawień - Mapa
        add_settings_field(
            'lr_marker_icon',
            __('URL ikony markera', 'lista-restauracji'),
            array($this, 'marker_icon_callback'),
            'lista-restauracji-settings',
            'lr_map_section'
        );

        add_settings_field(
            'lr_cluster_icon',
            __('URL ikony klastra', 'lista-restauracji'),
            array($this, 'cluster_icon_callback'),
            'lista-restauracji-settings',
            'lr_map_section'
        );

        // Pola ustawień - Wyświetlanie
        add_settings_field(
            'lr_default_image',
            __('Domyślny obrazek', 'lista-restauracji'),
            array($this, 'default_image_callback'),
            'lista-restauracji-settings',
            'lr_display_section'
        );
    }

public function enqueue_admin_scripts($hook) {
    if ($hook !== 'settings_page_lista-restauracji-settings') {
        return;
    }

    wp_enqueue_media();
    
    // Bezpieczne użycie wersji - sprawdź czy stała istnieje
    $plugin_version = defined('LR_PLUGIN_VERSION') ? LR_PLUGIN_VERSION : '1.1';
    
    // Sprawdź czy pliki istnieją przed załadowaniem
    $css_file = LR_PLUGIN_URL . 'assets/css/admin-settings.css';
    $js_file = LR_PLUGIN_URL . 'assets/js/admin-settings.js';
    
    if (file_exists(str_replace(LR_PLUGIN_URL, LR_PLUGIN_DIR, $js_file))) {
        wp_enqueue_script('lr-admin-settings', $js_file, array('jquery'), $plugin_version, true);
    }
    
    if (file_exists(str_replace(LR_PLUGIN_URL, LR_PLUGIN_DIR, $css_file))) {
        wp_enqueue_style('lr-admin-settings', $css_file, array(), $plugin_version);
    }
}

    public function render_settings_page() {
        // Sprawdź czy klucz API jest ustawiony
        $api_key = get_option('lr_google_maps_api_key');
        $has_api_key = !empty($api_key);

        ?>
        <div class="wrap">
            <h1><?php _e('Ustawienia Listy Restauracji', 'lista-restauracji'); ?></h1>
            
            <?php if (!$has_api_key): ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('Uwaga!', 'lista-restauracji'); ?></strong> 
                    <?php _e('Aby wtyczka działała poprawnie, musisz ustawić klucz API Google Maps.', 'lista-restauracji'); ?>
                    <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
                        <?php _e('Jak uzyskać klucz API?', 'lista-restauracji'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>

            <?php settings_errors(); ?>
            
            <div class="lr-settings-container">
                <form method="post" action="options.php" class="lr-settings-form">
                    <?php settings_fields('lr_settings_group'); ?>
                    
                    <div class="lr-tabs">
                        <div class="lr-tab-nav">
                            <a href="#main" class="lr-tab-link active"><?php _e('Główne', 'lista-restauracji'); ?></a>
                            <a href="#map" class="lr-tab-link"><?php _e('Mapa', 'lista-restauracji'); ?></a>
                            <a href="#display" class="lr-tab-link"><?php _e('Wyświetlanie', 'lista-restauracji'); ?></a>
                            <a href="#shortcode" class="lr-tab-link"><?php _e('Shortcode', 'lista-restauracji'); ?></a>
                        </div>
                        
                        <div class="lr-tab-content">
                            <!-- Zakładka główna -->
                            <div id="main" class="lr-tab-pane active">
                                <h2><?php _e('Główne ustawienia', 'lista-restauracji'); ?></h2>
                                <?php do_settings_fields('lista-restauracji-settings', 'lr_main_section'); ?>
                            </div>
                            
                            <!-- Zakładka mapa -->
                            <div id="map" class="lr-tab-pane">
                                <h2><?php _e('Ustawienia mapy', 'lista-restauracji'); ?></h2>
                                <?php do_settings_fields('lista-restauracji-settings', 'lr_map_section'); ?>
                                
                                <div class="lr-preview-section">
                                    <h3><?php _e('Podgląd ikon', 'lista-restauracji'); ?></h3>
                                    <div class="lr-icon-previews">
                                        <div class="lr-icon-preview">
                                            <h4><?php _e('Ikona markera', 'lista-restauracji'); ?></h4>
                                            <div id="marker-preview" class="lr-icon-display">
                                                <?php 
                                                $marker_icon = get_option('lr_marker_icon');
                                                if ($marker_icon): ?>
                                                    <img src="<?php echo esc_url($marker_icon); ?>" alt="Marker icon" style="max-width: 50px;">
                                                <?php else: ?>
                                                    <span class="lr-no-icon"><?php _e('Brak ikony', 'lista-restauracji'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="lr-icon-preview">
                                            <h4><?php _e('Ikona klastra', 'lista-restauracji'); ?></h4>
                                            <div id="cluster-preview" class="lr-icon-display">
                                                <?php 
                                                $cluster_icon = get_option('lr_cluster_icon');
                                                if ($cluster_icon): ?>
                                                    <img src="<?php echo esc_url($cluster_icon); ?>" alt="Cluster icon" style="max-width: 50px;">
                                                <?php else: ?>
                                                    <span class="lr-no-icon"><?php _e('Brak ikony', 'lista-restauracji'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Zakładka wyświetlanie -->
                            <div id="display" class="lr-tab-pane">
                                <h2><?php _e('Ustawienia wyświetlania', 'lista-restauracji'); ?></h2>
                                <?php do_settings_fields('lista-restauracji-settings', 'lr_display_section'); ?>
                                
                                <div class="lr-info-box">
                                    <h3><?php _e('Domyślny obrazek', 'lista-restauracji'); ?></h3>
                                    <p><?php _e('Ten obrazek będzie wyświetlany dla restauracji, które nie mają ustawionego obrazka wyróżniającego.', 'lista-restauracji'); ?></p>
                                </div>
                            </div>
                            
                            <!-- Zakładka shortcode -->
                            <div id="shortcode" class="lr-tab-pane">
                                <h2><?php _e('Użycie shortcode', 'lista-restauracji'); ?></h2>
                                
                                <div class="lr-shortcode-examples">
                                    <h3><?php _e('Podstawowe użycie', 'lista-restauracji'); ?></h3>
                                    <code>[restauracje_mapa_lista]</code>
                                    
                                    <h3><?php _e('Przykłady z parametrami', 'lista-restauracji'); ?></h3>
                                    
                                    <div class="lr-example">
                                        <h4><?php _e('Tylko lista (bez mapy)', 'lista-restauracji'); ?></h4>
                                        <code>[restauracje_mapa_lista display_mode="list_only"]</code>
                                    </div>
                                    
                                    <div class="lr-example">
                                        <h4><?php _e('Bez zdjęć', 'lista-restauracji'); ?></h4>
                                        <code>[restauracje_mapa_lista show_images="no"]</code>
                                    </div>
                                    
                                    <div class="lr-example">
                                        <h4><?php _e('3 kolumny na desktop', 'lista-restauracji'); ?></h4>
                                        <code>[restauracje_mapa_lista columns_desktop="3"]</code>
                                    </div>
                                    
                                    <div class="lr-example">
                                        <h4><?php _e('Tylko wybrane pola', 'lista-restauracji'); ?></h4>
                                        <code>[restauracje_mapa_lista show_fields="address,phone"]</code>
                                    </div>
                                    
                                    <div class="lr-example">
                                        <h4><?php _e('Domyślne miasto i własne kolory', 'lista-restauracji'); ?></h4>
                                        <code>[restauracje_mapa_lista default_city="Warszawa" card_bg_color="#f8f9fa" card_text_color="#333"]</code>
                                    </div>
                                </div>
                                
                                <div class="lr-parameters-table">
                                    <h3><?php _e('Wszystkie dostępne parametry', 'lista-restauracji'); ?></h3>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Parametr', 'lista-restauracji'); ?></th>
                                                <th><?php _e('Wartości', 'lista-restauracji'); ?></th>
                                                <th><?php _e('Domyślna', 'lista-restauracji'); ?></th>
                                                <th><?php _e('Opis', 'lista-restauracji'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>display_mode</code></td>
                                                <td>both, map_only, list_only</td>
                                                <td>both</td>
                                                <td><?php _e('Co wyświetlać', 'lista-restauracji'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><code>show_images</code></td>
                                                <td>yes, no</td>
                                                <td>yes</td>
                                                <td><?php _e('Wyświetlaj zdjęcia', 'lista-restauracji'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><code>show_city_filter</code></td>
                                                <td>yes, no</td>
                                                <td>yes</td>
                                                <td><?php _e('Pokaż filtr miasta', 'lista-restauracji'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><code>default_city</code></td>
                                                <td><?php _e('nazwa miasta', 'lista-restauracji'); ?></td>
                                                <td><?php _e('puste', 'lista-restauracji'); ?></td>
                                                <td><?php _e('Domyślnie wybrane miasto', 'lista-restauracji'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><code>columns_desktop</code></td>
                                                <td>2, 3, 4, 6</td>
                                                <td>4</td>
                                                <td><?php _e('Kolumny na komputerach', 'lista-restauracji'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><code>show_fields</code></td>
                                                <td>address,city,phone,hours</td>
                                                <td><?php _e('wszystkie', 'lista-restauracji'); ?></td>
                                                <td><?php _e('Które pola wyświetlać', 'lista-restauracji'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php submit_button(); ?>
                </form>
                
                <div class="lr-sidebar">
                    <div class="lr-widget">
                        <h3><?php _e('Status wtyczki', 'lista-restauracji'); ?></h3>
                        <div class="lr-status-items">
                            <div class="lr-status-item">
                                <span class="lr-status-label"><?php _e('Klucz API:', 'lista-restauracji'); ?></span>
                                <span class="lr-status-value <?php echo $has_api_key ? 'lr-status-ok' : 'lr-status-error'; ?>">
                                    <?php echo $has_api_key ? __('Ustawiony', 'lista-restauracji') : __('Brak', 'lista-restauracji'); ?>
                                </span>
                            </div>
                            <div class="lr-status-item">
                                <span class="lr-status-label"><?php _e('Restauracje:', 'lista-restauracji'); ?></span>
                                <span class="lr-status-value">
                                    <?php echo wp_count_posts('restauracje')->publish; ?>
                                </span>
                            </div>
                            <div class="lr-status-item">
                                <span class="lr-status-label"><?php _e('WPBakery:', 'lista-restauracji'); ?></span>
                                <span class="lr-status-value <?php echo class_exists('Vc_Manager') ? 'lr-status-ok' : 'lr-status-warning'; ?>">
                                    <?php echo class_exists('Vc_Manager') ? __('Aktywny', 'lista-restauracji') : __('Nieaktywny', 'lista-restauracji'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lr-widget">
                        <h3><?php _e('Przydatne linki', 'lista-restauracji'); ?></h3>
                        <ul>
                            <li><a href="<?php echo admin_url('post-new.php?post_type=restauracje'); ?>"><?php _e('Dodaj restaurację', 'lista-restauracji'); ?></a></li>
                            <li><a href="<?php echo admin_url('edit.php?post_type=restauracje'); ?>"><?php _e('Zarządzaj restauracjami', 'lista-restauracji'); ?></a></li>
                            <li><a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank"><?php _e('Uzyskaj klucz API', 'lista-restauracji'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Obsługa zakładek
            $('.lr-tab-link').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.lr-tab-link').removeClass('active');
                $('.lr-tab-pane').removeClass('active');
                
                $(this).addClass('active');
                $(target).addClass('active');
            });
        });
        </script>
        <?php
    }

    // Callback functions for settings sections
    public function main_section_callback() {
        echo '<p>' . __('Podstawowe ustawienia wtyczki.', 'lista-restauracji') . '</p>';
    }

    public function map_section_callback() {
        echo '<p>' . __('Ustawienia dotyczące wyświetlania mapy.', 'lista-restauracji') . '</p>';
    }

    public function display_section_callback() {
        echo '<p>' . __('Ustawienia wyglądu i wyświetlania.', 'lista-restauracji') . '</p>';
    }

    // Callback functions for settings fields
    public function api_key_callback() {
        $api_key = get_option('lr_google_maps_api_key');
        echo '<input type="text" id="lr_google_maps_api_key" name="lr_google_maps_api_key" value="' . esc_attr($api_key) . '" size="50" />';
        echo '<p class="description">' . __('Klucz API Google Maps jest wymagany do działania mapy.', 'lista-restauracji') . '</p>';
    }

    public function marker_icon_callback() {
        $marker_icon = get_option('lr_marker_icon');
        echo '<input type="url" id="lr_marker_icon" name="lr_marker_icon" value="' . esc_attr($marker_icon) . '" size="50" />';
        echo '<button type="button" class="button lr-upload-button" data-target="lr_marker_icon">' . __('Wybierz obrazek', 'lista-restauracji') . '</button>';
        echo '<p class="description">' . __('URL do ikony markera na mapie (opcjonalne).', 'lista-restauracji') . '</p>';
    }

    public function cluster_icon_callback() {
        $cluster_icon = get_option('lr_cluster_icon');
        echo '<input type="url" id="lr_cluster_icon" name="lr_cluster_icon" value="' . esc_attr($cluster_icon) . '" size="50" />';
        echo '<button type="button" class="button lr-upload-button" data-target="lr_cluster_icon">' . __('Wybierz obrazek', 'lista-restauracji') . '</button>';
        echo '<p class="description">' . __('URL do ikony klastra markerów (opcjonalne).', 'lista-restauracji') . '</p>';
    }

    public function default_image_callback() {
        $default_image = get_option('lr_default_image');
        echo '<input type="url" id="lr_default_image" name="lr_default_image" value="' . esc_attr($default_image) . '" size="50" />';
        echo '<button type="button" class="button lr-upload-button" data-target="lr_default_image">' . __('Wybierz obrazek', 'lista-restauracji') . '</button>';
        echo '<p class="description">' . __('Domyślny obrazek dla restauracji bez zdjęcia.', 'lista-restauracji') . '</p>';
    }
}