<?php
/**
 * Registro de Taxonomías personalizadas
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Taxonomies {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register'));
        // Reemplazar meta boxes de taxonomías por radio buttons
        add_action('add_meta_boxes', array(__CLASS__, 'replace_taxonomy_metaboxes'));
        add_action('save_post_norma', array(__CLASS__, 'save_taxonomy_metaboxes'), 10, 2);
    }
    
    /**
     * Reemplazar meta boxes de taxonomías para permitir solo una selección
     */
    public static function replace_taxonomy_metaboxes() {
        // Quitar meta boxes originales
        remove_meta_box('tipo_normadiv', 'norma', 'side');
        remove_meta_box('organo_normadiv', 'norma', 'side');
        
        // Añadir meta boxes personalizados con radio buttons
        add_meta_box(
            'ull_tipo_norma_radio',
            __('Tipo de Norma', 'ull-normativa'),
            array(__CLASS__, 'render_tipo_norma_metabox'),
            'norma',
            'side',
            'high'
        );
        
        add_meta_box(
            'ull_organo_norma_radio',
            __('Órgano Emisor', 'ull-normativa'),
            array(__CLASS__, 'render_organo_norma_metabox'),
            'norma',
            'side',
            'default'
        );
    }
    
    /**
     * Renderizar meta box de tipo de norma con radio buttons
     */
    public static function render_tipo_norma_metabox($post) {
        $terms = get_terms(array(
            'taxonomy' => 'tipo_norma',
            'hide_empty' => false,
            'orderby' => 'name',
        ));
        
        $current_terms = wp_get_post_terms($post->ID, 'tipo_norma', array('fields' => 'ids'));
        $current_term = !empty($current_terms) ? $current_terms[0] : 0;
        
        wp_nonce_field('ull_taxonomy_metabox', 'ull_taxonomy_nonce');
        ?>
        <div class="ull-radio-taxonomy" style="max-height: 200px; overflow-y: auto; padding: 5px;">
            <label style="display: block; margin-bottom: 5px;">
                <input type="radio" name="tipo_norma_radio" value="" <?php checked($current_term, 0); ?>>
                <em><?php _e('— Ninguno —', 'ull-normativa'); ?></em>
            </label>
            <?php foreach ($terms as $term) : ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="radio" name="tipo_norma_radio" value="<?php echo esc_attr($term->term_id); ?>" <?php checked($current_term, $term->term_id); ?>>
                <?php echo esc_html($term->name); ?>
            </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Renderizar meta box de órgano con radio buttons
     */
    public static function render_organo_norma_metabox($post) {
        $terms = get_terms(array(
            'taxonomy' => 'organo_norma',
            'hide_empty' => false,
            'orderby' => 'name',
        ));
        
        $current_terms = wp_get_post_terms($post->ID, 'organo_norma', array('fields' => 'ids'));
        $current_term = !empty($current_terms) ? $current_terms[0] : 0;
        ?>
        <div class="ull-radio-taxonomy" style="max-height: 200px; overflow-y: auto; padding: 5px;">
            <label style="display: block; margin-bottom: 5px;">
                <input type="radio" name="organo_norma_radio" value="" <?php checked($current_term, 0); ?>>
                <em><?php _e('— Ninguno —', 'ull-normativa'); ?></em>
            </label>
            <?php foreach ($terms as $term) : ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="radio" name="organo_norma_radio" value="<?php echo esc_attr($term->term_id); ?>" <?php checked($current_term, $term->term_id); ?>>
                <?php echo esc_html($term->name); ?>
            </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Guardar taxonomías desde radio buttons
     */
    public static function save_taxonomy_metaboxes($post_id, $post) {
        if (!isset($_POST['ull_taxonomy_nonce']) || !wp_verify_nonce($_POST['ull_taxonomy_nonce'], 'ull_taxonomy_metabox')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Guardar tipo de norma
        if (isset($_POST['tipo_norma_radio'])) {
            $term_id = intval($_POST['tipo_norma_radio']);
            if ($term_id > 0) {
                wp_set_post_terms($post_id, array($term_id), 'tipo_norma');
            } else {
                wp_set_post_terms($post_id, array(), 'tipo_norma');
            }
        }
        
        // Guardar órgano emisor
        if (isset($_POST['organo_norma_radio'])) {
            $term_id = intval($_POST['organo_norma_radio']);
            if ($term_id > 0) {
                wp_set_post_terms($post_id, array($term_id), 'organo_norma');
            } else {
                wp_set_post_terms($post_id, array(), 'organo_norma');
            }
        }
    }
    
    public static function register() {
        self::register_tipo_norma();
        self::register_categoria_norma();
        self::register_materia();
        self::register_organo();
    }
    
    private static function register_tipo_norma() {
        $labels = array(
            'name'              => _x('Tipos de Norma', 'taxonomy general name', 'ull-normativa'),
            'singular_name'     => _x('Tipo de Norma', 'taxonomy singular name', 'ull-normativa'),
            'search_items'      => __('Buscar Tipos', 'ull-normativa'),
            'all_items'         => __('Todos los Tipos', 'ull-normativa'),
            'edit_item'         => __('Editar Tipo', 'ull-normativa'),
            'add_new_item'      => __('Añadir Nuevo Tipo', 'ull-normativa'),
            'menu_name'         => __('Tipos', 'ull-normativa'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'tipo-norma'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('tipo_norma', array('norma'), $args);
        
        self::insert_default_terms('tipo_norma', array(
            'ley-organica' => 'Ley Orgánica',
            'ley' => 'Ley',
            'real-decreto' => 'Real Decreto',
            'decreto' => 'Decreto',
            'orden' => 'Orden',
            'resolucion' => 'Resolución',
            'acuerdo' => 'Acuerdo',
            'reglamento' => 'Reglamento',
            'estatuto' => 'Estatuto',
            'normativa-interna' => 'Normativa Interna',
            'instruccion' => 'Instrucción',
            'circular' => 'Circular',
            'convenio' => 'Convenio',
        ));
    }
    
    private static function register_categoria_norma() {
        $labels = array(
            'name'              => _x('Categorías de Normativa', 'taxonomy general name', 'ull-normativa'),
            'singular_name'     => _x('Categoría', 'taxonomy singular name', 'ull-normativa'),
            'search_items'      => __('Buscar Categorías', 'ull-normativa'),
            'all_items'         => __('Todas las Categorías', 'ull-normativa'),
            'edit_item'         => __('Editar Categoría', 'ull-normativa'),
            'add_new_item'      => __('Añadir Nueva Categoría', 'ull-normativa'),
            'menu_name'         => __('Categorías', 'ull-normativa'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'categoria-norma'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('categoria_norma', array('norma'), $args);
        
        self::insert_default_terms('categoria_norma', array(
            'organizacion' => 'Organización y Funcionamiento',
            'academica' => 'Normativa Académica',
            'personal' => 'Personal',
            'estudiantes' => 'Estudiantes',
            'investigacion' => 'Investigación',
            'economica' => 'Gestión Económica',
            'servicios' => 'Servicios Universitarios',
        ));
    }
    
    private static function register_materia() {
        $labels = array(
            'name'              => _x('Materias', 'taxonomy general name', 'ull-normativa'),
            'singular_name'     => _x('Materia', 'taxonomy singular name', 'ull-normativa'),
            'search_items'      => __('Buscar Materias', 'ull-normativa'),
            'all_items'         => __('Todas las Materias', 'ull-normativa'),
            'edit_item'         => __('Editar Materia', 'ull-normativa'),
            'add_new_item'      => __('Añadir Nueva Materia', 'ull-normativa'),
            'menu_name'         => __('Materias', 'ull-normativa'),
        );
        
        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'materia'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('materia_norma', array('norma'), $args);
    }
    
    private static function register_organo() {
        $labels = array(
            'name'              => _x('Órganos', 'taxonomy general name', 'ull-normativa'),
            'singular_name'     => _x('Órgano', 'taxonomy singular name', 'ull-normativa'),
            'search_items'      => __('Buscar Órganos', 'ull-normativa'),
            'all_items'         => __('Todos los Órganos', 'ull-normativa'),
            'edit_item'         => __('Editar Órgano', 'ull-normativa'),
            'add_new_item'      => __('Añadir Nuevo Órgano', 'ull-normativa'),
            'menu_name'         => __('Órganos', 'ull-normativa'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'organo'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('organo_norma', array('norma'), $args);
        
        self::insert_default_terms('organo_norma', array(
            'claustro' => 'Claustro Universitario',
            'consejo-gobierno' => 'Consejo de Gobierno',
            'consejo-social' => 'Consejo Social',
            'rectorado' => 'Rectorado',
            'gerencia' => 'Gerencia',
            'secretaria-general' => 'Secretaría General',
        ));
    }
    
    private static function insert_default_terms($taxonomy, $terms) {
        foreach ($terms as $slug => $name) {
            if (!term_exists($slug, $taxonomy)) {
                wp_insert_term($name, $taxonomy, array('slug' => $slug));
            }
        }
    }
}

ULL_Taxonomies::init();
