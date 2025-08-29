<?php
class WPBakery_Integration {
    public function __construct() {
        add_action('vc_before_init', array($this, 'integrate_with_vc'));
    }

    public function integrate_with_vc() {
        vc_map(array(
            'name' => 'Mapa i Lista Restauracji',
            'base' => 'restauracje_mapa_lista',
            'category' => 'Restauracje',
            'params' => array(
                // Możesz dodać parametry, jeśli są potrzebne
            )
        ));
    }
}