<?php
class Restaurant_Post_Type {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_filter('post_row_actions', array($this, 'remove_view_action'), 10, 2);
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Restauracje',
            'singular_name'      => 'Restauracja',
            'menu_name'          => 'Restauracje',
            'name_admin_bar'     => 'Restauracja',
            'add_new'            => 'Dodaj nową',
            'add_new_item'       => 'Dodaj nową restaurację',
            'new_item'           => 'Nowa restauracja',
            'edit_item'          => 'Edytuj restaurację',
            'view_item'          => 'Zobacz restaurację',
            'all_items'          => 'Wszystkie restauracje',
            'search_items'       => 'Szukaj restauracji',
            'parent_item_colon'  => 'Nadrzędna restauracja:',
            'not_found'          => 'Nie znaleziono restauracji.',
            'not_found_in_trash' => 'Nie znaleziono restauracji w koszu.'
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'restauracje'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'thumbnail'),
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-store',
        );

        register_post_type('restauracje', $args);
    }

    public function remove_view_action($actions, $post) {
        if ($post->post_type === 'restauracje') {
            unset($actions['view']);
        }
        return $actions;
    }
}