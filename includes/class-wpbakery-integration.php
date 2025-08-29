<?php
class WPBakery_Integration {
    public function __construct() {
        add_action('vc_before_init', array($this, 'integrate_with_vc'));
    }

    public function integrate_with_vc() {
        vc_map(array(
            'name' => __('Mapa i Lista Restauracji', 'lista-restauracji'),
            'base' => 'restauracje_mapa_lista',
            'category' => __('Restauracje', 'lista-restauracji'),
            'description' => __('Wyświetla mapę i listę restauracji z możliwością filtrowania', 'lista-restauracji'),
            'icon' => 'icon-wpb-map-pin',
            'params' => array(
                // Sekcja wyświetlania
                array(
                    'type' => 'dropdown',
                    'heading' => __('Tryb wyświetlania', 'lista-restauracji'),
                    'param_name' => 'display_mode',
                    'value' => array(
                        __('Mapa + Lista', 'lista-restauracji') => 'both',
                        __('Tylko mapa', 'lista-restauracji') => 'map_only',
                        __('Tylko lista', 'lista-restauracji') => 'list_only'
                    ),
                    'std' => 'both',
                    'description' => __('Wybierz elementy do wyświetlenia', 'lista-restauracji'),
                    'group' => __('Wyświetlanie', 'lista-restauracji')
                ),
                array(
                    'type' => 'checkbox',
                    'heading' => __('Wyświetl zdjęcia', 'lista-restauracji'),
                    'param_name' => 'show_images',
                    'value' => array(__('Tak', 'lista-restauracji') => 'yes'),
                    'std' => 'yes',
                    'description' => __('Wyświetl zdjęcia restauracji w liście i modalu', 'lista-restauracji'),
                    'group' => __('Wyświetlanie', 'lista-restauracji')
                ),
                array(
                    'type' => 'checkbox',
                    'heading' => __('Wyświetl filtr miasta', 'lista-restauracji'),
                    'param_name' => 'show_city_filter',
                    'value' => array(__('Tak', 'lista-restauracji') => 'yes'),
                    'std' => 'yes',
                    'description' => __('Pokaż filtr do filtrowania po mieście', 'lista-restauracji'),
                    'group' => __('Wyświetlanie', 'lista-restauracji')
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Typ filtra miasta', 'lista-restauracji'),
                    'param_name' => 'city_filter_type',
                    'value' => array(
                        __('Lista rozwijana', 'lista-restauracji') => 'dropdown',
                        __('Badge (przyciski)', 'lista-restauracji') => 'badges'
                    ),
                    'std' => 'dropdown',
                    'description' => __('Wybierz sposób wyświetlania filtra miast', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'show_city_filter',
                        'value' => array('yes')
                    ),
                    'group' => __('Wyświetlanie', 'lista-restauracji')
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __('Domyślne miasto', 'lista-restauracji'),
                    'param_name' => 'default_city',
                    'description' => __('Nazwa miasta do wybrania domyślnie (opcjonalne)', 'lista-restauracji'),
                    'group' => __('Wyświetlanie', 'lista-restauracji')
                ),

                // Sekcja mapy
                array(
                    'type' => 'textfield',
                    'heading' => __('Wysokość mapy', 'lista-restauracji'),
                    'param_name' => 'map_height',
                    'value' => '400',
                    'description' => __('Wysokość mapy w pikselach', 'lista-restauracji'),
                    'group' => __('Ustawienia mapy', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'display_mode',
                        'value' => array('both', 'map_only')
                    )
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Domyślny zoom mapy', 'lista-restauracji'),
                    'param_name' => 'map_zoom',
                    'value' => array(
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                        '7' => '7',
                        '8' => '8',
                        '10' => '10',
                        '12' => '12'
                    ),
                    'std' => '5',
                    'description' => __('Poziom przybliżenia mapy', 'lista-restauracji'),
                    'group' => __('Ustawienia mapy', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'display_mode',
                        'value' => array('both', 'map_only')
                    )
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __('Szerokość geograficzna centrum', 'lista-restauracji'),
                    'param_name' => 'map_center_lat',
                    'value' => '52.0692',
                    'description' => __('Współrzędna centrum mapy', 'lista-restauracji'),
                    'group' => __('Ustawienia mapy', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'display_mode',
                        'value' => array('both', 'map_only')
                    )
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __('Długość geograficzna centrum', 'lista-restauracji'),
                    'param_name' => 'map_center_lng',
                    'value' => '19.4803',
                    'description' => __('Współrzędna centrum mapy', 'lista-restauracji'),
                    'group' => __('Ustawienia mapy', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'display_mode',
                        'value' => array('both', 'map_only')
                    )
                ),

                // Sekcja listy
                array(
                    'type' => 'dropdown',
                    'heading' => __('Liczba kolumn na desktop', 'lista-restauracji'),
                    'param_name' => 'columns_desktop',
                    'value' => array(
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '6' => '6'
                    ),
                    'std' => '4',
                    'description' => __('Ile restauracji w rzędzie na komputerach', 'lista-restauracji'),
                    'group' => __('Ustawienia listy', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'display_mode',
                        'value' => array('both', 'list_only')
                    )
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Liczba kolumn na tablet', 'lista-restauracji'),
                    'param_name' => 'columns_tablet',
                    'value' => array(
                        '1' => '1',
                        '2' => '2',
                        '3' => '3'
                    ),
                    'std' => '2',
                    'description' => __('Ile restauracji w rzędzie na tabletach', 'lista-restauracji'),
                    'group' => __('Ustawienia listy', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'display_mode',
                        'value' => array('both', 'list_only')
                    )
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __('Liczba kolumn na mobile', 'lista-restauracji'),
                    'param_name' => 'columns_mobile',
                    'value' => array(
                        '1' => '1',
                        '2' => '2'
                    ),
                    'std' => '1',
                    'description' => __('Ile restauracji w rzędzie na telefonach', 'lista-restauracji'),
                    'group' => __('Ustawienia listy', 'lista-restauracji'),
                    'dependency' => array(
                        'element' => 'display_mode',
                        'value' => array('both', 'list_only')
                    )
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __('Limit restauracji', 'lista-restauracji'),
                    'param_name' => 'limit',
                    'value' => '-1',
                    'description' => __('Maksymalna liczba restauracji do wyświetlenia (-1 = bez limitu)', 'lista-restauracji'),
                    'group' => __('Ustawienia listy', 'lista-restauracji')
                ),

                // Sekcja pól
                array(
                    'type' => 'checkbox',
                    'heading' => __('Wyświetlane informacje', 'lista-restauracji'),
                    'param_name' => 'show_fields',
                    'value' => array(
                        __('Adres', 'lista-restauracji') => 'address',
                        __('Miasto', 'lista-restauracji') => 'city',
                        __('Telefon', 'lista-restauracji') => 'phone',
                        __('Godziny otwarcia', 'lista-restauracji') => 'hours'
                    ),
                    'std' => 'address,city,phone,hours',
                    'description' => __('Wybierz które pola wyświetlać w karcie i modalu', 'lista-restauracji'),
                    'group' => __('Wyświetlane pola', 'lista-restauracji')
                ),

                // Sekcja stylów
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Kolor tła kart', 'lista-restauracji'),
                    'param_name' => 'card_bg_color',
                    'description' => __('Kolor tła dla kart restauracji', 'lista-restauracji'),
                    'group' => __('Kolory', 'lista-restauracji')
                ),
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Kolor tekstu kart', 'lista-restauracji'),
                    'param_name' => 'card_text_color',
                    'description' => __('Kolor tekstu w kartach restauracji', 'lista-restauracji'),
                    'group' => __('Kolory', 'lista-restauracji')
                ),

                // Sekcja CSS
                array(
                    'type' => 'textfield',
                    'heading' => __('Dodatkowa klasa CSS', 'lista-restauracji'),
                    'param_name' => 'el_class',
                    'description' => __('Dodaj własną klasę CSS', 'lista-restauracji'),
                    'group' => __('Zaawansowane', 'lista-restauracji')
                ),
                array(
                    'type' => 'textarea',
                    'heading' => __('Dodatkowe style CSS', 'lista-restauracji'),
                    'param_name' => 'custom_css',
                    'description' => __('Dodaj własne style CSS (bez znaczników &lt;style&gt;)', 'lista-restauracji'),
                    'group' => __('Zaawansowane', 'lista-restauracji')
                )
            )
        ));
    }
}