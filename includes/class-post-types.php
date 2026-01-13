<?php
/**
 * Registro de Custom Post Types
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Post_Types {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register'));
        add_action('save_post_norma', array(__CLASS__, 'generate_numero_norma'), 5, 3);
        add_action('set_object_terms', array(__CLASS__, 'on_terms_set'), 10, 4);
        // Hook adicional con prioridad baja para asegurar que el tipo ya está guardado
        add_action('save_post_norma', array(__CLASS__, 'check_numero_on_save'), 99, 1);
        // También al editar desde REST API (editor de bloques)
        add_action('rest_after_insert_norma', array(__CLASS__, 'check_numero_on_rest'), 10, 1);
    }
    
    public static function register() {
        $labels = array(
            'name'                  => _x('Normativa', 'Post type general name', 'ull-normativa'),
            'singular_name'         => _x('Norma', 'Post type singular name', 'ull-normativa'),
            'menu_name'             => _x('Normativa', 'Admin Menu text', 'ull-normativa'),
            'add_new'               => __('Añadir Nueva', 'ull-normativa'),
            'add_new_item'          => __('Añadir Nueva Norma', 'ull-normativa'),
            'new_item'              => __('Nueva Norma', 'ull-normativa'),
            'edit_item'             => __('Editar Norma', 'ull-normativa'),
            'view_item'             => __('Ver Norma', 'ull-normativa'),
            'all_items'             => __('Todas las Normas', 'ull-normativa'),
            'search_items'          => __('Buscar Normativa', 'ull-normativa'),
            'not_found'             => __('No se encontraron normas.', 'ull-normativa'),
            'not_found_in_trash'    => __('No hay normas en la papelera.', 'ull-normativa'),
        );
        
        // Verificar si usar editor clásico
        $use_classic = get_option('ull_normativa_use_classic_editor', true);
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'normativa', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-book-alt',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'),
            'show_in_rest'       => !$use_classic, // Desactivar REST para forzar editor clásico
        );
        
        register_post_type('norma', $args);
    }
    
    /**
     * Genera automáticamente el número de norma al guardar
     */
    public static function generate_numero_norma($post_id, $post, $update) {
        // Evitar auto-guardados y revisiones
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Solo para normas publicadas o en borrador
        if (!in_array($post->post_status, array('publish', 'draft', 'pending'))) {
            return;
        }
        
        // Verificar si ya tiene número asignado
        $numero_actual = get_post_meta($post_id, '_numero_norma', true);
        if (!empty($numero_actual)) {
            return;
        }
        
        // Obtener el tipo de norma
        $tipos = get_the_terms($post_id, 'tipo_norma');
        if (!$tipos || is_wp_error($tipos)) {
            return;
        }
        
        $tipo = $tipos[0];
        $prefijo = self::get_tipo_prefix($tipo->slug);
        $siguiente_numero = self::get_next_sequence_number($prefijo);
        
        $numero_norma = $prefijo . '-' . str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT);
        
        // Guardar el número
        update_post_meta($post_id, '_numero_norma', $numero_norma);
    }
    
    /**
     * Renumerar cuando se cambia el tipo de norma
     */
    public static function on_terms_set($object_id, $terms, $tt_ids, $taxonomy) {
        if ($taxonomy !== 'tipo_norma') {
            return;
        }
        
        $post = get_post($object_id);
        if (!$post || $post->post_type !== 'norma') {
            return;
        }
        
        // Solo para normas publicadas o en borrador
        if (!in_array($post->post_status, array('publish', 'draft', 'pending', 'auto-draft'))) {
            return;
        }
        
        if (empty($terms)) {
            return;
        }
        
        // Obtener el nuevo tipo
        $term_id = is_array($terms) ? reset($terms) : $terms;
        $term = get_term($term_id, 'tipo_norma');
        
        if (!$term || is_wp_error($term)) {
            return;
        }
        
        $nuevo_prefijo = self::get_tipo_prefix($term->slug);
        
        // Verificar si el número actual tiene un prefijo diferente
        $numero_actual = get_post_meta($object_id, '_numero_norma', true);
        
        if (!empty($numero_actual)) {
            // Extraer el prefijo del número actual
            $partes = explode('-', $numero_actual);
            $prefijo_actual = $partes[0];
            
            // Si el prefijo es el mismo, no hacer nada
            if ($prefijo_actual === $nuevo_prefijo) {
                return;
            }
        }
        
        // Asignar nuevo número con el prefijo del nuevo tipo
        $siguiente_numero = self::get_next_sequence_number($nuevo_prefijo);
        $numero_norma = $nuevo_prefijo . '-' . str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT);
        
        update_post_meta($object_id, '_numero_norma', $numero_norma);
    }
    
    /**
     * Hook adicional: verificar número después de guardar metadatos
     */
    public static function check_numero_on_save($post_id) {
        // Evitar loops y autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'norma') {
            return;
        }
        
        // Solo para normas publicadas o en borrador
        if (!in_array($post->post_status, array('publish', 'draft', 'pending'))) {
            return;
        }
        
        // Si ya tiene número, salir
        $numero_actual = get_post_meta($post_id, '_numero_norma', true);
        if (!empty($numero_actual)) {
            return;
        }
        
        // Obtener tipo de norma
        $tipos = get_the_terms($post_id, 'tipo_norma');
        if (!$tipos || is_wp_error($tipos)) {
            return;
        }
        
        $tipo = $tipos[0];
        $prefijo = self::get_tipo_prefix($tipo->slug);
        $siguiente_numero = self::get_next_sequence_number($prefijo);
        
        $numero_norma = $prefijo . '-' . str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT);
        
        update_post_meta($post_id, '_numero_norma', $numero_norma);
    }
    
    /**
     * Hook para REST API (editor de bloques Gutenberg)
     */
    public static function check_numero_on_rest($post) {
        $post_id = is_object($post) ? $post->ID : $post;
        self::check_numero_on_save($post_id);
    }
    
    /**
     * Obtiene el prefijo de 3 letras para un tipo de norma
     */
    public static function get_tipo_prefix($tipo_slug) {
        // Prefijos personalizados guardados en opciones
        $custom_prefijos = get_option('ull_normativa_custom_prefijos', array());
        
        // Prefijos por defecto
        $prefijos = array(
            'ley-organica'        => 'LOR',
            'ley'                 => 'LEY',
            'real-decreto'        => 'RDE',
            'decreto'             => 'DEC',
            'orden'               => 'ORD',
            'resolucion'          => 'RES',
            'acuerdo'             => 'ACU',
            'reglamento'          => 'REG',
            'estatuto'            => 'EST',
            'normativa-interna'   => 'NIN',
            'instruccion'         => 'INS',
            'circular'            => 'CIR',
            'convenio'            => 'CON',
            'texto-consolidado'   => 'TEX',
            'norma'               => 'NOR',
            'protocolo'           => 'PRO',
            'directriz'           => 'DIR',
            'manual'              => 'MAN',
            'guia'                => 'GUI',
            'procedimiento'       => 'PRC',
        );
        
        // Los personalizados tienen prioridad
        $prefijos = array_merge($prefijos, $custom_prefijos);
        
        if (isset($prefijos[$tipo_slug])) {
            return $prefijos[$tipo_slug];
        }
        
        // Generar prefijo automático: primeras 3 letras en mayúsculas
        $slug_clean = str_replace('-', '', $tipo_slug);
        return strtoupper(substr($slug_clean, 0, 3));
    }
    
    /**
     * Guardar prefijo personalizado para un tipo
     */
    public static function save_custom_prefix($tipo_slug, $prefijo) {
        $prefijo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $prefijo), 0, 3));
        if (strlen($prefijo) < 2) {
            return false;
        }
        
        $custom_prefijos = get_option('ull_normativa_custom_prefijos', array());
        $custom_prefijos[$tipo_slug] = $prefijo;
        update_option('ull_normativa_custom_prefijos', $custom_prefijos);
        
        return $prefijo;
    }
    
    /**
     * Obtiene el siguiente número secuencial para un prefijo
     */
    public static function get_next_sequence_number($prefijo) {
        global $wpdb;
        
        // Buscar todos los números con este prefijo
        $like_pattern = $prefijo . '-%';
        
        $numeros = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_numero_norma' 
             AND pm.meta_value LIKE %s
             AND p.post_type = 'norma'
             AND p.post_status != 'trash'",
            $like_pattern
        ));
        
        if (empty($numeros)) {
            return 1;
        }
        
        $max = 0;
        foreach ($numeros as $numero) {
            // Extraer todos los dígitos del final del número
            // Funciona con XXX-0001, XXX-2024-001, etc.
            if (preg_match('/(\d+)$/', $numero, $matches)) {
                $num = intval($matches[1]);
                if ($num > $max) {
                    $max = $num;
                }
            }
        }
        
        return $max + 1;
    }
    
    /**
     * Asignar número a una norma sin numerar
     */
    public static function assign_number_to_post($post_id, $prefijo = null) {
        $numero_actual = get_post_meta($post_id, '_numero_norma', true);
        if (!empty($numero_actual)) {
            return $numero_actual; // Ya tiene número
        }
        
        // Si no se proporciona prefijo, obtenerlo del tipo de norma
        if (empty($prefijo)) {
            $tipos = get_the_terms($post_id, 'tipo_norma');
            if ($tipos && !is_wp_error($tipos)) {
                $tipo = $tipos[0];
                $prefijo = self::get_tipo_prefix($tipo->slug);
            } else {
                // Fallback al prefijo general si no tiene tipo
                $prefijo = get_option('ull_normativa_prefijo_numero', 'NOR');
            }
        }
        
        $siguiente_numero = self::get_next_sequence_number($prefijo);
        $numero_norma = $prefijo . '-' . str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT);
        
        update_post_meta($post_id, '_numero_norma', $numero_norma);
        
        return $numero_norma;
    }
    
    /**
     * Asignar números a todas las normas sin numerar
     * Cada norma recibe el prefijo correspondiente a su tipo
     */
    public static function assign_numbers_to_unnumbered() {
        global $wpdb;
        
        // Obtener normas sin número
        $posts_sin_numero = $wpdb->get_col(
            "SELECT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_numero_norma'
             WHERE p.post_type = 'norma'
             AND p.post_status IN ('publish', 'draft', 'pending')
             AND (pm.meta_value IS NULL OR pm.meta_value = '')
             ORDER BY p.post_date ASC"
        );
        
        $count = 0;
        
        foreach ($posts_sin_numero as $post_id) {
            // Cada norma obtiene su prefijo según su tipo
            self::assign_number_to_post($post_id);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Obtiene todos los prefijos disponibles
     */
    public static function get_all_prefixes() {
        return array(
            'LOR' => __('Ley Orgánica', 'ull-normativa'),
            'LEY' => __('Ley', 'ull-normativa'),
            'RDE' => __('Real Decreto', 'ull-normativa'),
            'DEC' => __('Decreto', 'ull-normativa'),
            'ORD' => __('Orden', 'ull-normativa'),
            'RES' => __('Resolución', 'ull-normativa'),
            'ACU' => __('Acuerdo', 'ull-normativa'),
            'REG' => __('Reglamento', 'ull-normativa'),
            'EST' => __('Estatuto', 'ull-normativa'),
            'NIN' => __('Normativa Interna', 'ull-normativa'),
            'INS' => __('Instrucción', 'ull-normativa'),
            'CIR' => __('Circular', 'ull-normativa'),
            'CON' => __('Convenio', 'ull-normativa'),
        );
    }
    
    public static function get_meta_fields() {
        return array(
            'numero_norma' => array(
                'label' => __('Número de Norma', 'ull-normativa'),
                'type' => 'text',
                'required' => false,
                'readonly' => true,
                'description' => __('Se genera automáticamente al asignar el tipo de norma', 'ull-normativa'),
            ),
            'fecha_aprobacion' => array(
                'label' => __('Fecha de Aprobación', 'ull-normativa'),
                'type' => 'date',
                'required' => true,
            ),
            'fecha_publicacion' => array(
                'label' => __('Fecha de Publicación', 'ull-normativa'),
                'type' => 'date',
                'required' => false,
            ),
            'fecha_vigencia' => array(
                'label' => __('Fecha de Entrada en Vigor', 'ull-normativa'),
                'type' => 'date',
                'required' => false,
            ),
            'fecha_derogacion' => array(
                'label' => __('Fecha de Derogación', 'ull-normativa'),
                'type' => 'date',
                'required' => false,
            ),
            'organo_emisor' => array(
                'label' => __('Órgano Emisor', 'ull-normativa'),
                'type' => 'text',
                'required' => false,
            ),
            'boletin_oficial' => array(
                'label' => __('Boletín Oficial', 'ull-normativa'),
                'type' => 'text',
                'required' => false,
            ),
            'url_boletin' => array(
                'label' => __('URL del Boletín', 'ull-normativa'),
                'type' => 'url',
                'required' => false,
            ),
            'estado_norma' => array(
                'label' => __('Estado', 'ull-normativa'),
                'type' => 'select',
                'options' => array(
                    'vigente' => __('Vigente', 'ull-normativa'),
                    'derogada' => __('Derogada', 'ull-normativa'),
                    'modificada' => __('Modificada', 'ull-normativa'),
                    'pendiente' => __('Pendiente de Vigencia', 'ull-normativa'),
                ),
                'required' => true,
            ),
            'ambito_aplicacion' => array(
                'label' => __('Ámbito de Aplicación', 'ull-normativa'),
                'type' => 'textarea',
                'required' => false,
            ),
            'resumen' => array(
                'label' => __('Resumen', 'ull-normativa'),
                'type' => 'textarea',
                'required' => false,
            ),
            'palabras_clave' => array(
                'label' => __('Palabras Clave', 'ull-normativa'),
                'type' => 'text',
                'required' => false,
                'description' => __('Separadas por comas', 'ull-normativa'),
            ),
            'version_actual' => array(
                'label' => __('Versión Actual', 'ull-normativa'),
                'type' => 'text',
                'required' => false,
            ),
        );
    }
}

ULL_Post_Types::init();
