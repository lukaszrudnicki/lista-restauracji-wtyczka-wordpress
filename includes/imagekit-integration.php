<?php
/**
 * ImageKit Integration for Lista Restauracji Plugin
 * 
 * This file handles integration with ImageKit service for image optimization
 * and delivery via CDN.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LR_ImageKit_Integration {
    
    private $imagekit_endpoint;
    private $transformations;
    
    public function __construct() {
        $this->imagekit_endpoint = get_option('lr_imagekit_url_endpoint', '');
        $this->transformations = array(
            'thumbnail' => 'tr:w-300,h-200,c-maintain_ratio,fo-auto,q-80',
            'card' => 'tr:w-400,h-250,c-maintain_ratio,fo-auto,q-85',
            'modal' => 'tr:w-800,h-500,c-maintain_ratio,fo-auto,q-90',
            'large' => 'tr:w-1200,h-800,c-maintain_ratio,fo-auto,q-95'
        );
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('save_post_restauracje', array($this, 'update_featured_image_url'), 10, 2);
        add_action('delete_post', array($this, 'cleanup_image_meta'));
        add_filter('lr_get_restaurant_image', array($this, 'get_optimized_image'), 10, 3);
        add_action('admin_init', array($this, 'register_imagekit_settings'));
        
        // Hook do AJAX dla pobierania restauracji
        add_filter('lr_restaurant_data_before_send', array($this, 'optimize_restaurant_images'));
    }
    
    /**
     * Register ImageKit settings
     */
    public function register_imagekit_settings() {
        register_setting('lr_settings_group', 'lr_imagekit_url_endpoint', array(
            'sanitize_callback' => array($this, 'sanitize_imagekit_endpoint')
        ));
        register_setting('lr_settings_group', 'lr_imagekit_enabled', array(
            'sanitize_callback' => 'absint'
        ));
        
        add_settings_section(
            'lr_imagekit_section',
            __('Ustawienia ImageKit', 'lista-restauracji'),
            array($this, 'imagekit_section_callback'),
            'lista-restauracji-settings'
        );
        
        add_settings_field(
            'lr_imagekit_enabled',
            __('Włącz ImageKit', 'lista-restauracji'),
            array($this, 'imagekit_enabled_callback'),
            'lista-restauracji-settings',
            'lr_imagekit_section'
        );
        
        add_settings_field(
            'lr_imagekit_url_endpoint',
            __('URL Endpoint ImageKit', 'lista-restauracji'),
            array($this, 'imagekit_endpoint_callback'),
            'lista-restauracji-settings',
            'lr_imagekit_section'
        );
    }
    
    /**
     * Update featured image URL when post is saved
     */
    public function update_featured_image_url($post_id, $post) {
        if (!$this->is_imagekit_enabled()) {
            return;
        }
        
        // Check if post has featured image
        if (has_post_thumbnail($post_id)) {
            $image_url = wp_get_attachment_url(get_post_thumbnail_id($post_id));
            
            if (!empty($this->imagekit_endpoint) && !empty($image_url)) {
                // Generate ImageKit URLs for different sizes
                $imagekit_urls = array();
                
                foreach ($this->transformations as $size => $transform) {
                    $imagekit_urls[$size] = $this->generate_imagekit_url($image_url, $transform);
                }
                
                // Save ImageKit URLs as post meta
                update_post_meta($post_id, '_lr_imagekit_urls', $imagekit_urls);
                update_post_meta($post_id, '_lr_original_image_url', $image_url);
            }
        } else {
            // If no featured image, remove ImageKit meta
            delete_post_meta($post_id, '_lr_imagekit_urls');
            delete_post_meta($post_id, '_lr_original_image_url');
        }
    }
    
    /**
     * Clean up image meta when post is deleted
     */
    public function cleanup_image_meta($post_id) {
        if (get_post_type($post_id) === 'restauracje') {
            delete_post_meta($post_id, '_lr_imagekit_urls');
            delete_post_meta($post_id, '_lr_original_image_url');
        }
    }
    
    /**
     * Get optimized image URL
     */
    public function get_optimized_image($image_url, $post_id, $size = 'card') {
        if (!$this->is_imagekit_enabled()) {
            return $image_url;
        }
        
        $imagekit_urls = get_post_meta($post_id, '_lr_imagekit_urls', true);
        
        if (is_array($imagekit_urls) && isset($imagekit_urls[$size])) {
            return $imagekit_urls[$size];
        }
        
        // Fallback: generate URL on the fly
        if (!empty($image_url) && !empty($this->imagekit_endpoint)) {
            $transform = isset($this->transformations[$size]) ? $this->transformations[$size] : $this->transformations['card'];
            return $this->generate_imagekit_url($image_url, $transform);
        }
        
        return $image_url;
    }
    
    /**
     * Optimize restaurant images before sending via AJAX
     */
    public function optimize_restaurant_images($restaurants) {
        if (!$this->is_imagekit_enabled()) {
            return $restaurants;
        }
        
        foreach ($restaurants as &$restaurant) {
            if (!empty($restaurant['image'])) {
                $restaurant['image'] = apply_filters('lr_get_restaurant_image', $restaurant['image'], $restaurant['id'], 'card');
                
                // Add additional sizes for different use cases
                $restaurant['image_thumbnail'] = apply_filters('lr_get_restaurant_image', $restaurant['image'], $restaurant['id'], 'thumbnail');
                $restaurant['image_modal'] = apply_filters('lr_get_restaurant_image', $restaurant['image'], $restaurant['id'], 'modal');
            }
        }
        
        return $restaurants;
    }
    
    /**
     * Generate ImageKit URL
     */
    private function generate_imagekit_url($original_url, $transformation = '') {
        if (empty($this->imagekit_endpoint) || empty($original_url)) {
            return $original_url;
        }
        
        $filename = basename($original_url);
        $imagekit_url = trailingslashit($this->imagekit_endpoint);
        
        if (!empty($transformation)) {
            $imagekit_url .= $transformation . '/';
        }
        
        $imagekit_url .= $filename;
        
        return $imagekit_url;
    }
    
    /**
     * Check if ImageKit is enabled
     */
    private function is_imagekit_enabled() {
        return get_option('lr_imagekit_enabled', 0) && !empty($this->imagekit_endpoint);
    }
    
    /**
     * Sanitize ImageKit endpoint
     */
    public function sanitize_imagekit_endpoint($input) {
        if (empty($input)) {
            return '';
        }
        
        $url = esc_url_raw($input);
        
        // Remove trailing slash
        $url = rtrim($url, '/');
        
        // Basic validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            add_settings_error('lr_imagekit_url_endpoint', 'invalid_url', __('Nieprawidłowy URL endpoint ImageKit.', 'lista-restauracji'));
            return get_option('lr_imagekit_url_endpoint', '');
        }
        
        return $url;
    }
    
    // Settings callbacks
    public function imagekit_section_callback() {
        echo '<p>' . __('ImageKit pozwala na optymalizację i dostarczanie obrazów przez CDN.', 'lista-restauracji') . '</p>';
        echo '<p><a href="https://imagekit.io" target="_blank">' . __('Dowiedz się więcej o ImageKit', 'lista-restauracji') . '</a></p>';
    }
    
    public function imagekit_enabled_callback() {
        $enabled = get_option('lr_imagekit_enabled', 0);
        echo '<input type="checkbox" id="lr_imagekit_enabled" name="lr_imagekit_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
        echo '<label for="lr_imagekit_enabled">' . __('Włącz optymalizację obrazów przez ImageKit', 'lista-restauracji') . '</label>';
    }
    
    public function imagekit_endpoint_callback() {
        $endpoint = get_option('lr_imagekit_url_endpoint', '');
        echo '<input type="url" id="lr_imagekit_url_endpoint" name="lr_imagekit_url_endpoint" value="' . esc_attr($endpoint) . '" size="50" />';
        echo '<p class="description">' . __('URL endpoint Twojego konta ImageKit (np. https://ik.imagekit.io/twoje_id)', 'lista-restauracji') . '</p>';
    }
}

// Initialize ImageKit integration
new LR_ImageKit_Integration();

/**
 * Helper function to get optimized featured image URL
 * 
 * @param int|null $post_id Post ID (optional, defaults to current post)
 * @param string $size Image size (thumbnail, card, modal, large)
 * @return string Optimized image URL or original URL
 */
function lr_get_featured_image_url($post_id = null, $size = 'card') {
    if (null === $post_id) {
        $post_id = get_the_ID();
    }
    
    $original_url = get_the_post_thumbnail_url($post_id, 'large');
    
    if (empty($original_url)) {
        // Return default image if no featured image
        $default_image = get_option('lr_default_image', '');
        if (!empty($default_image)) {
            return apply_filters('lr_get_restaurant_image', $default_image, $post_id, $size);
        }
        
        return '';
    }
    
    return apply_filters('lr_get_restaurant_image', $original_url, $post_id, $size);
}

/**
 * Helper function to get restaurant image with fallback
 * 
 * @param int $post_id Post ID
 * @param string $size Image size
 * @param bool $use_default Whether to use default image as fallback
 * @return string Image URL
 */
function lr_get_restaurant_image($post_id, $size = 'card', $use_default = true) {
    $image_url = lr_get_featured_image_url($post_id, $size);
    
    if (empty($image_url) && $use_default) {
        $default_image = get_option('lr_default_image', '');
        if (!empty($default_image)) {
            return $default_image;
        }
        
        // Last fallback - plugin default
        return LR_PLUGIN_URL . 'assets/images/no-image.jpg';
    }
    
    return $image_url;
}

/**
 * Bulk regenerate ImageKit URLs for all restaurants
 * 
 * @return int Number of processed restaurants
 */
function lr_regenerate_imagekit_urls() {
    if (!get_option('lr_imagekit_enabled', 0)) {
        return 0;
    }
    
    $restaurants = get_posts(array(
        'post_type' => 'restauracje',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    $processed = 0;
    $imagekit = new LR_ImageKit_Integration();
    
    foreach ($restaurants as $restaurant) {
        $imagekit->update_featured_image_url($restaurant->ID, $restaurant);
        $processed++;
    }
    
    return $processed;
}

/**
 * Add admin notice about ImageKit regeneration
 */
add_action('admin_notices', function() {
    if (isset($_GET['lr_regenerate_imagekit']) && $_GET['lr_regenerate_imagekit'] === 'success') {
        $count = intval($_GET['count']);
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . sprintf(__('Przetworzono %d restauracji. URL-e ImageKit zostały zaktualizowane.', 'lista-restauracji'), $count) . '</p>';
        echo '</div>';
    }
});

/**
 * Add regenerate button to settings page
 */
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_lista-restauracji-settings') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            if ($('#lr_imagekit_enabled').is(':checked')) {
                $('#lr_imagekit_url_endpoint').after(
                    '<button type="button" id="lr-regenerate-imagekit" class="button button-secondary" style="margin-left: 10px;">' +
                    '<?php _e("Przetwórz ponownie", "lista-restauracji"); ?>' +
                    '</button>'
                );
                
                $('#lr-regenerate-imagekit').on('click', function() {
                    if (confirm('<?php _e("Czy chcesz przetworzyć ponownie wszystkie obrazy restauracji?", "lista-restauracji"); ?>')) {
                        window.location.href = '<?php echo admin_url("admin-ajax.php?action=lr_regenerate_imagekit&nonce=" . wp_create_nonce("lr_regenerate")); ?>';
                    }
                });
            }
        });
        </script>
        <?php
    }
});

/**
 * AJAX handler for regenerating ImageKit URLs
 */
add_action('wp_ajax_lr_regenerate_imagekit', function() {
    if (!wp_verify_nonce($_GET['nonce'], 'lr_regenerate') || !current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $count = lr_regenerate_imagekit_urls();
    wp_redirect(admin_url('options-general.php?page=lista-restauracji-settings&lr_regenerate_imagekit=success&count=' . $count));
    exit;
});