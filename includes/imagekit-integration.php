<?php
add_action('save_post', 'update_featured_image_url', 10, 2);

function update_featured_image_url($post_id, $post) {
    // Sprawdź, czy post ma ustawiony obrazek wyróżniający
    if (has_post_thumbnail($post_id)) {
        // Pobierz URL obrazka wyróżniającego
        $image_url = wp_get_attachment_url(get_post_thumbnail_id($post_id));
        
        // Pobierz URL endpoint ImageKit z ustawień
        $imagekit_url_endpoint = get_option('restaurant_map_imagekit_url_endpoint', '');
        
        // Sprawdź, czy URL endpoint jest ustawiony
        if (!empty($imagekit_url_endpoint)) {
            // Transformacja URL obrazu za pomocą ImageKit
            $imagekit_url = trailingslashit($imagekit_url_endpoint) . basename($image_url);
            
            // Zapisz URL ImageKit jako metadane posta
            update_post_meta($post_id, '_imagekit_featured_image_url', $imagekit_url);
        }
    } else {
        // Jeśli nie ma obrazka wyróżniającego, usuń metadane ImageKit
        delete_post_meta($post_id, '_imagekit_featured_image_url');
    }
}

// Funkcja do pobierania URL obrazka wyróżniającego (używaj tej funkcji zamiast get_the_post_thumbnail_url)
function get_imagekit_featured_image_url($post_id = null) {
    if (null === $post_id) {
        $post_id = get_the_ID();
    }
    
    $imagekit_url = get_post_meta($post_id, '_imagekit_featured_image_url', true);
    
    if (!empty($imagekit_url)) {
        return $imagekit_url;
    }
    
    // Jeśli nie ma URL-a ImageKit, zwróć standardowy URL obrazka wyróżniającego
    return get_the_post_thumbnail_url($post_id);
}
?>