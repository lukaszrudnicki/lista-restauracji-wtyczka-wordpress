<?php
if (!defined('ABSPATH')) {
    exit;
}

class Restaurant_Meta_Boxes {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_restauracje', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_meta_boxes() {
        add_meta_box(
            'restaurant_details',
            'Szczegóły restauracji',
            array($this, 'render_details_meta_box'),
            'restauracje',
            'normal',
            'high'
        );

        add_meta_box(
            'restaurant_location',
            'Lokalizacja restauracji',
            array($this, 'render_location_meta_box'),
            'restauracje',
            'normal',
            'high'
        );
    }

    public function render_details_meta_box($post) {
        wp_nonce_field('restaurant_meta_box', 'restaurant_meta_box_nonce');

        $address = get_post_meta($post->ID, '_restaurant_address', true);
        $city = get_post_meta($post->ID, '_restaurant_city', true);
        $phone = get_post_meta($post->ID, '_restaurant_phone', true);
        $opening_hours = get_post_meta($post->ID, '_restaurant_opening_hours', true);

        ?>
        <div class="lr-restaurant-meta-box">
            <p>
                <label for="restaurant_address">Adres:</label>
                <input type="text" id="restaurant_address" name="restaurant_address" value="<?php echo esc_attr($address); ?>" size="40" />
            </p>

            <p>
                <label for="restaurant_city">Miasto:</label>
                <input type="text" id="restaurant_city" name="restaurant_city" value="<?php echo esc_attr($city); ?>" size="40" />
            </p>

            <p>
                <label for="restaurant_phone">Numer telefonu:</label>
                <input type="text" id="restaurant_phone" name="restaurant_phone" value="<?php echo esc_attr($phone); ?>" size="40" />
            </p>

            <p>
                <label for="restaurant_opening_hours">Godziny otwarcia:</label>
                <?php
                // Użyj wp_editor dla lepszej obsługi HTML
                wp_editor($opening_hours, 'restaurant_opening_hours', array(
                    'textarea_name' => 'restaurant_opening_hours',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => array(
                        'buttons' => 'strong,em,ul,ol,li,link'
                    ),
                    'tinymce' => array(
                        'toolbar1' => 'bold,italic,bullist,numlist,separator,link,unlink',
                        'toolbar2' => '',
                        'toolbar3' => ''
                    )
                ));
                ?>
                <span class="description">
                    Możesz używać <strong>pogrubienia</strong>, <em>kursywy</em>, list i enterów do formatowania godzin otwarcia.
                </span>
            </p>
        </div>
        <?php
    }

    public function render_location_meta_box($post) {
        $latitude = get_post_meta($post->ID, '_restaurant_latitude', true);
        $longitude = get_post_meta($post->ID, '_restaurant_longitude', true);

        ?>
        <div class="lr-restaurant-location-box">
            <p>
                <label for="restaurant_latitude">Szerokość geograficzna:</label>
                <input type="text" id="restaurant_latitude" name="restaurant_latitude" value="<?php echo esc_attr($latitude); ?>" />
            </p>

            <p>
                <label for="restaurant_longitude">Długość geograficzna:</label>
                <input type="text" id="restaurant_longitude" name="restaurant_longitude" value="<?php echo esc_attr($longitude); ?>" />
            </p>

            <p>
                <label for="restaurant_map_address">Znajdź adres na mapie:</label>
                <input type="text" id="restaurant_map_address" size="40" />
                <button id="find_on_map" class="button">Znajdź na mapie</button>
            </p>

            <div id="restaurant_map" style="width: 100%; height: 400px;"></div>
        </div>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['restaurant_meta_box_nonce']) || !wp_verify_nonce($_POST['restaurant_meta_box_nonce'], 'restaurant_meta_box')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array(
            'restaurant_address' => 'sanitize_text_field',
            'restaurant_city' => 'sanitize_text_field',
            'restaurant_phone' => 'sanitize_text_field',
            'restaurant_opening_hours' => 'wp_kses_post', // Pozwala na podstawowe HTML
            'restaurant_latitude' => 'sanitize_text_field',
            'restaurant_longitude' => 'sanitize_text_field'
        );

        foreach ($fields as $field => $sanitize_function) {
            if (isset($_POST[$field])) {
                $value = $sanitize_function($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }

    public function enqueue_admin_scripts($hook) {
        global $post_type;
        if ('post.php' != $hook && 'post-new.php' != $hook) {
            return;
        }

        if ('restauracje' !== $post_type) {
            return;
        }

        $api_key = get_option('lr_google_maps_api_key');
        if (!empty($api_key)) {
            wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places", array(), null, true);
            wp_enqueue_script('lr-admin-script', LR_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'google-maps'), '1.0', true);
        }
        wp_enqueue_style('lr-admin-style', LR_PLUGIN_URL . 'assets/css/admin-style.css');
    }
}