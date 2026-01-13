<?php
/**
 * Cargador de funcionalidad de Códigos ULL
 * 
 * Este módulo permite crear colecciones/códigos de normas seleccionadas
 * con exportación a PDF y diferentes estilos de visualización.
 * 
 * @package ULL_Normativa
 * @subpackage Codigos
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Codigos_Module {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Definir constantes del módulo
     */
    private function define_constants() {
        if (!defined('ULL_CODIGOS_DIR')) {
            define('ULL_CODIGOS_DIR', ULL_NORMATIVA_PLUGIN_DIR . 'codigos/');
        }
        if (!defined('ULL_CODIGOS_URL')) {
            define('ULL_CODIGOS_URL', ULL_NORMATIVA_PLUGIN_URL . 'codigos/');
        }
    }
    
    /**
     * Cargar archivos necesarios
     */
    private function load_dependencies() {
        require_once ULL_CODIGOS_DIR . 'includes/class-post-type.php';
        require_once ULL_CODIGOS_DIR . 'includes/class-meta-boxes.php';
        require_once ULL_CODIGOS_DIR . 'includes/class-shortcodes.php';
        // DESACTIVADO v2.5.0: Ahora usa ULL_Unified_PDF_Export
        // require_once ULL_CODIGOS_DIR . 'includes/class-pdf-export.php';
        require_once ULL_CODIGOS_DIR . 'includes/class-template-loader.php';
        // DESACTIVADO v2.5.0: La configuración PDF está centralizada en admin/class-pdf-settings.php
        // require_once ULL_CODIGOS_DIR . 'admin/class-admin-settings.php';
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Encolar assets frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'ull-codigos-frontend',
            ULL_CODIGOS_URL . 'assets/css/frontend.css',
            array(),
            ULL_NORMATIVA_VERSION
        );
        
        wp_enqueue_script(
            'ull-codigos-frontend',
            ULL_CODIGOS_URL . 'assets/js/frontend.js',
            array('jquery'),
            ULL_NORMATIVA_VERSION,
            true
        );
    }
    
    /**
     * Encolar assets admin
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        if ($post_type === 'codigo' || strpos($hook, 'ull-normativa') !== false) {
            wp_enqueue_media();
            
            wp_enqueue_style(
                'ull-codigos-admin',
                ULL_CODIGOS_URL . 'assets/css/admin.css',
                array(),
                ULL_NORMATIVA_VERSION
            );
            
            wp_enqueue_script(
                'ull-codigos-admin',
                ULL_CODIGOS_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                ULL_NORMATIVA_VERSION,
                true
            );
            
            wp_localize_script('ull-codigos-admin', 'ullCodigosAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ull_codigos_nonce'),
                'i18n' => array(
                    'selectImage' => __('Seleccionar imagen', 'ull-normativa'),
                    'useImage' => __('Usar esta imagen', 'ull-normativa'),
                    'remove' => __('Eliminar', 'ull-normativa'),
                    'searching' => __('Buscando...', 'ull-normativa'),
                    'noResults' => __('No se encontraron resultados', 'ull-normativa'),
                ),
            ));
        }
    }
    
    /**
     * Activación del módulo
     */
    public static function activate() {
        require_once ULL_CODIGOS_DIR . 'includes/class-post-type.php';
        ULL_Codigos_Post_Type::register();
        flush_rewrite_rules();
        
        // Opciones por defecto
        self::set_default_options();
    }
    
    /**
     * Establecer opciones por defecto
     */
    private static function set_default_options() {
        $defaults = array(
            'ull_codigos_post_type' => 'norma',
            'ull_codigos_pdf_logo' => '',
            'ull_codigos_pdf_header_color' => '#003366',
            'ull_codigos_pdf_header_text_color' => '#ffffff',
            'ull_codigos_pdf_title_color' => '#003366',
            'ull_codigos_pdf_header_text' => get_bloginfo('name'),
            'ull_codigos_pdf_footer_text' => '© ' . date('Y') . ' ' . get_bloginfo('name'),
            'ull_codigos_pdf_filename_pattern' => '{slug}',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

// Inicializar módulo de códigos
ULL_Codigos_Module::get_instance();
