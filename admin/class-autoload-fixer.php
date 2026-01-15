<?php
/**
 * Herramienta para regenerar autoload de DOMPDF
 * Sin necesidad de reinstalar toda la librería
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_DOMPDF_Autoload_Fixer {
    
    private $installer;
    
    public function __construct() {
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-dompdf-installer.php';
        $this->installer = new ULL_DOMPDF_Installer();
        
        add_action('admin_notices', array($this, 'show_fix_notice'));
        add_action('admin_init', array($this, 'handle_fix_request'));
    }
    
    /**
     * Mostrar aviso si el autoload necesita actualización
     */
    public function show_fix_notice() {
        // Solo en páginas del plugin
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'norma') === false) {
            return;
        }
        
        // Solo si DOMPDF está instalado pero el autoload es viejo
        if (!$this->installer->is_installed()) {
            return;
        }
        
        // Verificar si ya se mostró el aviso
        if (get_transient('ull_autoload_fixed')) {
            return;
        }
        
        // Verificar si el autoload es correcto
        if ($this->is_autoload_updated()) {
            return;
        }
        
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php _e('ULL Normativa:', 'ull-normativa'); ?></strong>
                <?php _e('Se ha detectado que el autoload de DOMPDF necesita actualización para corregir errores.', 'ull-normativa'); ?>
            </p>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ull-dompdf-diagnostic&action=regenerate_autoload'), 'regenerate_autoload'); ?>" class="button button-primary">
                    <?php _e('Regenerar Autoload (Recomendado)', 'ull-normativa'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=norma&page=ull-dompdf-diagnostic'); ?>" class="button">
                    <?php _e('Ver Diagnóstico', 'ull-normativa'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Verificar si el autoload está actualizado
     */
    private function is_autoload_updated() {
        $autoload_path = $this->installer->get_autoload_path();
        if (!file_exists($autoload_path)) {
            return false;
        }
        
        $content = file_get_contents($autoload_path);
        
        // Verificar que contiene las correcciones necesarias
        $has_cpdf_preload = strpos($content, 'lib/Cpdf.php') !== false;
        $has_masterminds = strpos($content, 'Masterminds') !== false;
        
        return $has_cpdf_preload && $has_masterminds;
    }
    
    /**
     * Manejar petición de regeneración
     */
    public function handle_fix_request() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'regenerate_autoload') {
            return;
        }
        
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'regenerate_autoload')) {
            wp_die(__('Verificación de seguridad fallida.', 'ull-normativa'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'ull-normativa'));
        }
        
        $result = $this->regenerate_autoload();
        
        if ($result) {
            set_transient('ull_autoload_fixed', true, HOUR_IN_SECONDS);
            add_settings_error(
                'ull_autoload_fixer',
                'autoload_fixed',
                __('Autoload regenerado correctamente. Por favor, prueba exportar un PDF ahora.', 'ull-normativa'),
                'success'
            );
        } else {
            add_settings_error(
                'ull_autoload_fixer',
                'autoload_fix_failed',
                __('Error al regenerar el autoload. Intenta reinstalar DOMPDF.', 'ull-normativa'),
                'error'
            );
        }
        
        set_transient('ull_autoload_fixer_message', get_settings_errors('ull_autoload_fixer'), 30);
        
        wp_redirect(admin_url('edit.php?post_type=norma&page=ull-dompdf-diagnostic'));
        exit;
    }
    
    /**
     * Regenerar archivo autoload
     */
    private function regenerate_autoload() {
        // Forzar la ruta global de uploads en Multisite
        if ( is_multisite() ) {
            $install_dir = WP_CONTENT_DIR . '/uploads/ull-normativa-libs';
        } else {
            $upload_dir = wp_upload_dir();
            $install_dir = $upload_dir['basedir'] . '/ull-normativa-libs';
        }
        $dompdf_dir = $install_dir . '/dompdf';
        $autoload_file = $dompdf_dir . '/autoload.inc.php';
        
        if (!is_dir($dompdf_dir)) {
            return false;
        }
        
        // Hacer backup del autoload anterior
        if (file_exists($autoload_file)) {
            $backup_file = $autoload_file . '.backup.' . date('YmdHis');
            @copy($autoload_file, $backup_file);
        }
        
        // Contenido actualizado del autoload - Build 13
        $autoload_content = <<<'PHP'
<?php
/**
 * Autoload personalizado para DOMPDF
 * Build 13 - Incluye pre-carga de Cpdf y soporte para Masterminds/HTML5
 */

// Definir constantes de DOMPDF
if (!defined('DOMPDF_DIR')) {
    define('DOMPDF_DIR', __DIR__);
}

if (!defined('DOMPDF_FONT_DIR')) {
    define('DOMPDF_FONT_DIR', DOMPDF_DIR . '/lib/fonts/');
}

if (!defined('DOMPDF_FONT_CACHE')) {
    // Usar ruta global de uploads para el caché de fuentes
    $font_cache = (is_multisite()) ? WP_CONTENT_DIR . '/uploads/ull-normativa/dompdf-cache/' : wp_upload_dir()['basedir'] . '/ull-normativa/dompdf-cache/';
    if (!is_dir($font_cache)) { @mkdir($font_cache, 0755, true); }
    define('DOMPDF_FONT_CACHE', $font_cache);
}


if (!defined('DOMPDF_TEMP_DIR')) {
    $temp_dir = (is_multisite()) ? WP_CONTENT_DIR . '/uploads/ull-normativa/dompdf-temp/' : wp_upload_dir()['basedir'] . '/ull-normativa/dompdf-temp/';
    if (!is_dir($temp_dir)) { @mkdir($temp_dir, 0755, true); }
    define('DOMPDF_TEMP_DIR', $temp_dir);
}

// CRÍTICO: Cargar Cpdf ANTES del autoloader
// Cpdf está en lib/Cpdf.php con namespace Dompdf
$cpdf_file = DOMPDF_DIR . '/lib/Cpdf.php';
if (file_exists($cpdf_file)) {
    require_once $cpdf_file;
}

// Cargar Canvas (es una interface)
$canvas_file = DOMPDF_DIR . '/src/Canvas.php';
if (file_exists($canvas_file)) {
    require_once $canvas_file;
}

// Autoloader principal
spl_autoload_register(function ($class) {
    static $base_dir = null;
    
    if ($base_dir === null) {
        $base_dir = DOMPDF_DIR . '/';
    }
    
    // Mapeo de prefijos a directorios
    $prefixes = array(
        'Dompdf\\' => 'src/',
        'FontLib\\' => 'lib/php-font-lib/src/FontLib/',
        'Svg\\' => 'lib/php-svg-lib/src/Svg/',
        'Sabberworm\\CSS\\' => 'lib/php-css-parser/src/',
        'Masterminds\\' => 'lib/html5-php/src/',
    );
    
    foreach ($prefixes as $prefix => $dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relative_class = substr($class, $len);
            $file = $base_dir . $dir . str_replace('\\', '/', $relative_class) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
    
    return false;
});

// Cargar funciones helper si existen
$functions_file = DOMPDF_DIR . '/src/functions.php';
if (file_exists($functions_file)) {
    require_once $functions_file;
}

PHP;
        
        // Escribir archivo
        $result = @file_put_contents($autoload_file, $autoload_content);
        
        return $result !== false;
    }
}

// Inicializar
new ULL_DOMPDF_Autoload_Fixer();
