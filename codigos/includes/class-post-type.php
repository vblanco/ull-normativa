<?php
/**
 * Registro del Custom Post Type Código
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Codigos_Post_Type {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register'));
    }
    
    public static function register() {
        $labels = array(
            'name'               => __('Códigos', 'ull-normativa'),
            'singular_name'      => __('Código', 'ull-normativa'),
            'menu_name'          => __('Códigos ULL', 'ull-normativa'),
            'add_new'            => __('Añadir Nuevo', 'ull-normativa'),
            'add_new_item'       => __('Añadir Nuevo Código', 'ull-normativa'),
            'edit_item'          => __('Editar Código', 'ull-normativa'),
            'new_item'           => __('Nuevo Código', 'ull-normativa'),
            'view_item'          => __('Ver Código', 'ull-normativa'),
            'search_items'       => __('Buscar Códigos', 'ull-normativa'),
            'not_found'          => __('No se encontraron códigos', 'ull-normativa'),
            'not_found_in_trash' => __('No hay códigos en la papelera', 'ull-normativa'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'codigos'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-book',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'       => false, // Editor clásico
        );
        
        register_post_type('codigo', $args);
    }
}

ULL_Codigos_Post_Type::init();
