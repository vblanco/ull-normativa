<?php
/**
 * Plugin Name: ULL Normativa
 * Plugin URI: https://www.ull.es
 * Description: Sistema integral de gestión de normativa universitaria con control de versiones, importación/exportación y búsqueda avanzada.
 * Version: 2.5.0
 * Author: Universidad de La Laguna
 * Author URI: https://www.ull.es
 * License: GPL v2 or later
 * Text Domain: ull-normativa
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('ULL_NORMATIVA_VERSION', '2.5.0');
define('ULL_NORMATIVA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ULL_NORMATIVA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ULL_NORMATIVA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
final class ULL_Normativa {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_dependencies'));
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function load_dependencies() {
        // Core - siempre cargar
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-html-sanitizer.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-version-control.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-relations.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-meta-boxes.php';
        
        // NUEVO v2.5.0: Exportación PDF unificada para normas y códigos
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-unified-pdf-export.php';
        
        // NUEVO v2.0: Visualización mejorada de versiones
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-version-display.php';
        
        // NUEVO v2.0: Módulo de Códigos (colecciones de normas)
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'codigos/class-codigos-loader.php';
        
        // Admin
        if (is_admin()) {
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-admin-settings.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-import-export.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-admin-columns.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-help-page.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-toc-settings.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-pdf-settings.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-dompdf-diagnostic.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-autoload-fixer.php';
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'admin/class-dompdf-structure-diagnostic.php';
            
            // Inicializar configuración PDF
            new ULL_PDF_Settings();
        }
        
        // Frontend y AJAX
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'public/class-shortcodes.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'public/class-ajax-handler.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'public/class-template-loader.php';
        
        // Inicializar exportador PDF unificado
        new ULL_Unified_PDF_Export();
    }
    
    public function activate() {
        // Crear tablas personalizadas
        $this->create_tables();
        
        // Cargar dependencias para activación
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-taxonomies.php';
        
        // Registrar CPT y taxonomías
        ULL_Post_Types::register();
        ULL_Taxonomies::register();
        
        // Activar módulo de códigos
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'codigos/class-codigos-loader.php';
        ULL_Codigos_Module::activate();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Opciones por defecto
        $this->set_default_options();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de versiones
        $table_versions = $wpdb->prefix . 'ull_norma_versions';
        $sql_versions = "CREATE TABLE IF NOT EXISTS $table_versions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            norma_id bigint(20) unsigned NOT NULL,
            version_number varchar(50) NOT NULL,
            version_date date NOT NULL,
            content longtext NOT NULL,
            changes_summary text,
            is_current tinyint(1) DEFAULT 0,
            created_by bigint(20) unsigned,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY norma_id (norma_id),
            KEY is_current (is_current),
            KEY version_date (version_date)
        ) $charset_collate;";
        
        // Tabla de relaciones entre normas
        $table_relations = $wpdb->prefix . 'ull_norma_relations';
        $sql_relations = "CREATE TABLE IF NOT EXISTS $table_relations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            norma_id bigint(20) unsigned NOT NULL,
            related_norma_id bigint(20) unsigned NOT NULL,
            relation_type varchar(50) NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY norma_id (norma_id),
            KEY related_norma_id (related_norma_id),
            KEY relation_type (relation_type),
            UNIQUE KEY unique_relation (norma_id, related_norma_id, relation_type)
        ) $charset_collate;";
        
        // Tabla de log de importaciones
        $table_imports = $wpdb->prefix . 'ull_norma_imports';
        $sql_imports = "CREATE TABLE IF NOT EXISTS $table_imports (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            import_date datetime DEFAULT CURRENT_TIMESTAMP,
            file_name varchar(255),
            total_records int DEFAULT 0,
            imported int DEFAULT 0,
            updated int DEFAULT 0,
            errors int DEFAULT 0,
            error_log longtext,
            user_id bigint(20) unsigned,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_versions);
        dbDelta($sql_relations);
        dbDelta($sql_imports);
    }
    
    private function set_default_options() {
        $defaults = array(
            'ull_normativa_display_mode' => 'list',
            'ull_normativa_items_per_page' => 20,
            'ull_normativa_show_search' => true,
            'ull_normativa_search_fields' => array('title', 'content', 'numero', 'fecha'),
            'ull_normativa_list_columns' => array('titulo', 'tipo', 'numero', 'fecha', 'estado'),
            'ull_normativa_card_fields' => array('titulo', 'tipo', 'numero', 'fecha', 'resumen'),
            'ull_normativa_ficha_sections' => array('info', 'contenido', 'versiones', 'relaciones', 'documentos'),
            'ull_normativa_html_allowed_tags' => '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td><a><blockquote><span><div>',
            'ull_normativa_enable_versions' => true,
            'ull_normativa_enable_relations' => true,
            'ull_normativa_use_classic_editor' => true,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'ull-normativa',
            false,
            dirname(ULL_NORMATIVA_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'ull-normativa-frontend',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ULL_NORMATIVA_VERSION
        );
        
        // NUEVO v2.0: CSS para visualización mejorada de versiones
        wp_enqueue_style(
            'ull-normativa-version-display',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/css/version-display.css',
            array('ull-normativa-frontend'),
            ULL_NORMATIVA_VERSION
        );
        
        // Cargar CSS personalizado si existe
        $upload_dir = wp_upload_dir();
        $custom_css_file = $upload_dir['basedir'] . '/ull-normativa/custom-styles.css';
        $custom_css_url = $upload_dir['baseurl'] . '/ull-normativa/custom-styles.css';
        
        if (file_exists($custom_css_file)) {
            $version = get_option('ull_normativa_styles_version', ULL_NORMATIVA_VERSION);
            wp_enqueue_style(
                'ull-normativa-custom',
                $custom_css_url,
                array('ull-normativa-frontend'),
                $version
            );
        }
        
        wp_enqueue_script(
            'ull-normativa-frontend',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ULL_NORMATIVA_VERSION,
            true
        );
        
        // Script dedicado para navegación de tabla de contenidos
        wp_enqueue_script(
            'ull-normativa-toc',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/js/toc-navigation.js',
            array(),  // Sin dependencias - vanilla JavaScript
            ULL_NORMATIVA_VERSION,
            true
        );
        
        wp_localize_script('ull-normativa-frontend', 'ullNormativa', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ull_normativa_nonce'),
            'i18n' => array(
                'loading' => __('Cargando...', 'ull-normativa'),
                'noResults' => __('No se encontraron resultados', 'ull-normativa'),
                'error' => __('Error al realizar la búsqueda', 'ull-normativa'),
                'searchPlaceholder' => __('Buscar normativa...', 'ull-normativa'),
            )
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        
        if (!$screen) {
            return;
        }
        
        $allowed_screens = array(
            'norma', 
            'edit-norma', 
            'toplevel_page_ull-normativa', 
            'normativa_page_ull-normativa-import', 
            'normativa_page_ull-normativa-export', 
            'normativa_page_ull-normativa-settings'
        );
        
        if (!in_array($screen->id, $allowed_screens)) {
            return;
        }
        
        wp_enqueue_style(
            'ull-normativa-admin',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ULL_NORMATIVA_VERSION
        );
        
        wp_enqueue_script(
            'ull-normativa-admin',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'),
            ULL_NORMATIVA_VERSION,
            true
        );
        
        wp_localize_script('ull-normativa-admin', 'ullNormativaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ull_normativa_admin_nonce'),
            'i18n' => array(
                'confirmDelete' => __('¿Está seguro de eliminar esta versión?', 'ull-normativa'),
                'confirmImport' => __('¿Está seguro de importar este archivo?', 'ull-normativa'),
                'importing' => __('Importando...', 'ull-normativa'),
                'exporting' => __('Exportando...', 'ull-normativa'),
            )
        ));
    }
}

// Inicializar plugin
function ull_normativa() {
    return ULL_Normativa::instance();
}

ull_normativa();
