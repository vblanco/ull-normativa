<?php
/**
 * Instalador de DOMPDF
 * Descarga e instala dompdf automáticamente sin incluirlo en el plugin
 * 
 * @package ULL_Normativa
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_DOMPDF_Installer {
    
    /**
     * URL del repositorio de dompdf en GitHub
     */
    const DOMPDF_REPO = 'https://github.com/dompdf/dompdf';
    
    /**
     * Versión de dompdf a descargar
     */
    const DOMPDF_VERSION = '2.0.4';
    
    /**
     * Directorio donde se instalará dompdf
     */
    private $install_dir;
    
    /**
     * Directorio temporal para descargas
     */
    private $temp_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->install_dir = $upload_dir['basedir'] . '/ull-normativa-libs';
        $this->temp_dir = $upload_dir['basedir'] . '/ull-normativa-temp';
        
        add_action('admin_init', array($this, 'handle_install_request'));
    }
    
    /**
     * Verificar si dompdf está instalado
     */
    public function is_installed() {
        $autoload = $this->install_dir . '/dompdf/autoload.inc.php';
        return file_exists($autoload);
    }
    
    /**
     * Obtener ruta del autoloader de dompdf
     */
    public function get_autoload_path() {
        if (!$this->is_installed()) {
            return false;
        }
        return $this->install_dir . '/dompdf/autoload.inc.php';
    }
    
    /**
     * Obtener información del estado de instalación
     */
    public function get_status() {
        return array(
            'installed' => $this->is_installed(),
            'version' => $this->get_installed_version(),
            'install_dir' => $this->install_dir,
            'size' => $this->get_installation_size(),
        );
    }
    
    /**
     * Obtener versión instalada de dompdf
     */
    private function get_installed_version() {
        if (!$this->is_installed()) {
            return false;
        }
        
        $version_file = $this->install_dir . '/dompdf/src/Dompdf.php';
        if (!file_exists($version_file)) {
            return 'unknown';
        }
        
        $content = file_get_contents($version_file);
        if (preg_match('/VERSION\s*=\s*[\'"]([^\'"]+)[\'"]/i', $content, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }
    
    /**
     * Obtener tamaño de la instalación
     */
    private function get_installation_size() {
        if (!$this->is_installed()) {
            return 0;
        }
        
        return $this->get_directory_size($this->install_dir);
    }
    
    /**
     * Calcular tamaño de un directorio
     */
    private function get_directory_size($path) {
        $size = 0;
        if (!is_dir($path)) {
            return $size;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Formatear tamaño en bytes a formato legible
     */
    public static function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Manejar petición de instalación desde admin
     */
    public function handle_install_request() {
        if (!isset($_POST['ull_install_dompdf']) || !isset($_POST['_wpnonce'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'ull-normativa'));
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'], 'ull_install_dompdf')) {
            wp_die(__('Verificación de seguridad fallida.', 'ull-normativa'));
        }
        
        $result = $this->install();
        
        if ($result['success']) {
            add_settings_error(
                'ull_dompdf_installer',
                'dompdf_installed',
                __('DOMPDF instalado correctamente.', 'ull-normativa'),
                'success'
            );
        } else {
            add_settings_error(
                'ull_dompdf_installer',
                'dompdf_install_failed',
                sprintf(__('Error al instalar DOMPDF: %s', 'ull-normativa'), $result['message']),
                'error'
            );
        }
        
        set_transient('ull_dompdf_installer_message', get_settings_errors('ull_dompdf_installer'), 30);
    }
    
    /**
     * Instalar dompdf
     */
    public function install() {
        $log = array(); // Para debug
        
        // Crear directorios
        if (!$this->create_directories()) {
            return array(
                'success' => false,
                'message' => __('No se pudieron crear los directorios necesarios.', 'ull-normativa'),
                'log' => $log
            );
        }
        $log[] = 'Directorios creados correctamente';
        
        // Descargar archivo ZIP
        $zip_file = $this->download_dompdf();
        if (!$zip_file) {
            return array(
                'success' => false,
                'message' => __('No se pudo descargar dompdf desde GitHub.', 'ull-normativa'),
                'log' => $log
            );
        }
        $log[] = 'DOMPDF descargado: ' . filesize($zip_file) . ' bytes';
        
        // Extraer archivo
        $extract_result = $this->extract_zip($zip_file);
        if (!$extract_result['success']) {
            @unlink($zip_file);
            return array(
                'success' => false,
                'message' => $extract_result['message'],
                'log' => array_merge($log, $extract_result['log'])
            );
        }
        $log = array_merge($log, $extract_result['log']);
        
        // Crear autoload personalizado
        if (!$this->create_autoload()) {
            return array(
                'success' => false,
                'message' => __('No se pudo crear el archivo autoload.', 'ull-normativa'),
                'log' => $log
            );
        }
        $log[] = 'Autoload creado correctamente';
        
        // Limpiar archivos temporales
        @unlink($zip_file);
        $this->cleanup_temp_directory();
        $log[] = 'Archivos temporales limpiados';
        
        // Verificar instalación
        if (!$this->is_installed()) {
            return array(
                'success' => false,
                'message' => __('La instalación no se completó correctamente. Verificar permisos de archivos.', 'ull-normativa'),
                'log' => $log
            );
        }
        
        $log[] = 'Instalación completada y verificada';
        
        return array(
            'success' => true,
            'message' => __('DOMPDF instalado correctamente.', 'ull-normativa'),
            'log' => $log
        );
    }
    
    /**
     * Crear directorios necesarios
     */
    private function create_directories() {
        if (!wp_mkdir_p($this->install_dir)) {
            return false;
        }
        
        if (!wp_mkdir_p($this->temp_dir)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Descargar dompdf desde GitHub
     */
    private function download_dompdf() {
        $url = sprintf(
            'https://github.com/dompdf/dompdf/archive/refs/tags/v%s.zip',
            self::DOMPDF_VERSION
        );
        
        $zip_file = $this->temp_dir . '/dompdf.zip';
        
        // Usar wp_remote_get para descargar
        $response = wp_remote_get($url, array(
            'timeout' => 300,
            'stream' => true,
            'filename' => $zip_file
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        if (!file_exists($zip_file) || filesize($zip_file) === 0) {
            return false;
        }
        
        return $zip_file;
    }
    
    /**
     * Extraer archivo ZIP
     */
    private function extract_zip($zip_file) {
        $log = array();
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        WP_Filesystem();
        global $wp_filesystem;
        
        $extract_to = $this->temp_dir . '/extracted';
        
        $unzip_result = unzip_file($zip_file, $extract_to);
        
        if (is_wp_error($unzip_result)) {
            return array(
                'success' => false,
                'message' => 'Error al descomprimir: ' . $unzip_result->get_error_message(),
                'log' => $log
            );
        }
        
        $log[] = 'Archivo ZIP extraído a directorio temporal';
        
        // Mover archivos a ubicación final
        $source = $extract_to . '/dompdf-' . self::DOMPDF_VERSION;
        $destination = $this->install_dir . '/dompdf';
        
        // Verificar que el directorio fuente existe
        if (!$wp_filesystem->exists($source)) {
            return array(
                'success' => false,
                'message' => 'El directorio extraído no existe: ' . $source,
                'log' => $log
            );
        }
        
        $log[] = 'Directorio fuente verificado: ' . $source;
        
        // Eliminar instalación previa si existe
        if ($wp_filesystem->exists($destination)) {
            $wp_filesystem->delete($destination, true);
            $log[] = 'Instalación previa eliminada';
        }
        
        // Mover directorio
        if (!$wp_filesystem->move($source, $destination)) {
            return array(
                'success' => false,
                'message' => 'No se pudo mover el directorio a la ubicación final',
                'log' => $log
            );
        }
        
        $log[] = 'DOMPDF movido a: ' . $destination;
        
        // Descargar e instalar dependencias necesarias
        $dep_result = $this->install_dependencies($destination);
        $log = array_merge($log, $dep_result['log']);
        
        if (!$dep_result['success']) {
            $log[] = 'ADVERTENCIA: Algunas dependencias no se instalaron, pero DOMPDF puede funcionar';
        } else {
            $log[] = 'Todas las dependencias instaladas correctamente';
        }
        
        return array(
            'success' => true,
            'message' => 'Extracción completada',
            'log' => $log
        );
    }
    
    /**
     * Instalar dependencias de dompdf
     */
    private function install_dependencies($dompdf_dir) {
        $log = array();
        $success_count = 0;
        $total_count = 0;
        
        // dompdf necesita algunas dependencias mínimas
        // Las descargamos directamente desde sus repositorios
        
        $dependencies = array(
            'phenx/php-font-lib' => array(
                'url' => 'https://github.com/dompdf/php-font-lib/archive/refs/tags/0.5.4.zip',
                'dir' => 'php-font-lib-0.5.4',
                'target' => 'lib/php-font-lib'
            ),
            'phenx/php-svg-lib' => array(
                'url' => 'https://github.com/dompdf/php-svg-lib/archive/refs/tags/0.5.0.zip',
                'dir' => 'php-svg-lib-0.5.0',
                'target' => 'lib/php-svg-lib'
            ),
            'sabberworm/php-css-parser' => array(
                'url' => 'https://github.com/sabberworm/PHP-CSS-Parser/archive/refs/tags/8.4.0.zip',
                'dir' => 'PHP-CSS-Parser-8.4.0',
                'target' => 'lib/php-css-parser'
            ),
            'masterminds/html5' => array(
                'url' => 'https://github.com/Masterminds/html5-php/archive/refs/tags/2.8.1.zip',
                'dir' => 'html5-php-2.8.1',
                'target' => 'lib/html5-php'
            )
        );
        
        global $wp_filesystem;
        WP_Filesystem();
        
        foreach ($dependencies as $name => $info) {
            $total_count++;
            $log[] = 'Procesando dependencia: ' . $name;
            
            $dep_zip = $this->temp_dir . '/' . basename($info['url']);
            
            // Descargar dependencia
            $response = wp_remote_get($info['url'], array(
                'timeout' => 300,
                'stream' => true,
                'filename' => $dep_zip
            ));
            
            if (is_wp_error($response)) {
                $log[] = 'ERROR: No se pudo descargar ' . $name . ': ' . $response->get_error_message();
                continue;
            }
            
            if (!file_exists($dep_zip)) {
                $log[] = 'ERROR: Archivo no existe después de descarga: ' . $name;
                continue;
            }
            
            $log[] = 'Descargado ' . $name . ': ' . filesize($dep_zip) . ' bytes';
            
            // Extraer
            $extract_to = $this->temp_dir . '/dep-extracted';
            $wp_filesystem->delete($extract_to, true);
            
            $unzip_result = unzip_file($dep_zip, $extract_to);
            
            if (is_wp_error($unzip_result)) {
                $log[] = 'ERROR: No se pudo extraer ' . $name . ': ' . $unzip_result->get_error_message();
                @unlink($dep_zip);
                continue;
            }
            
            $source = $extract_to . '/' . $info['dir'];
            $target = $dompdf_dir . '/' . $info['target'];
            
            if (!$wp_filesystem->exists($source)) {
                $log[] = 'ERROR: Directorio fuente no existe: ' . $source;
                @unlink($dep_zip);
                continue;
            }
            
            // Crear directorio de destino
            $target_parent = dirname($target);
            if (!$wp_filesystem->exists($target_parent)) {
                $wp_filesystem->mkdir($target_parent, 0755, true);
            }
            
            // Mover archivos
            if ($wp_filesystem->exists($source)) {
                if ($wp_filesystem->move($source, $target)) {
                    $log[] = 'Instalado ' . $name . ' en ' . $info['target'];
                    $success_count++;
                } else {
                    $log[] = 'ERROR: No se pudo mover ' . $name . ' a destino';
                }
            }
            
            @unlink($dep_zip);
        }
        
        $log[] = sprintf('Dependencias instaladas: %d de %d', $success_count, $total_count);
        
        return array(
            'success' => $success_count >= 2, // Al menos 2 de 3 dependencias necesarias
            'installed' => $success_count,
            'total' => $total_count,
            'log' => $log
        );
    }
    
    /**
     * Limpiar directorio temporal
     */
    private function cleanup_temp_directory() {
        global $wp_filesystem;
        WP_Filesystem();
        
        if ($wp_filesystem->exists($this->temp_dir)) {
            $wp_filesystem->delete($this->temp_dir, true);
        }
    }
    
    /**
     * Crear archivo autoload personalizado para DOMPDF
     */
    private function create_autoload() {
        $dompdf_dir = $this->install_dir . '/dompdf';
        $autoload_file = $dompdf_dir . '/autoload.inc.php';
        
        // Contenido del autoload personalizado - VERSIÓN SIMPLIFICADA Y SEGURA
        $autoload_content = <<<'PHP'
<?php
/**
 * Autoload personalizado para DOMPDF
 * Generado automáticamente por ULL Normativa - Versión Segura
 */

// Definir constantes de DOMPDF
if (!defined('DOMPDF_DIR')) {
    define('DOMPDF_DIR', __DIR__);
}

if (!defined('DOMPDF_FONT_DIR')) {
    define('DOMPDF_FONT_DIR', DOMPDF_DIR . '/lib/fonts/');
}

if (!defined('DOMPDF_FONT_CACHE')) {
    $upload_dir = wp_upload_dir();
    if ($upload_dir && isset($upload_dir['basedir'])) {
        define('DOMPDF_FONT_CACHE', $upload_dir['basedir'] . '/ull-normativa/dompdf-cache/');
    }
}

if (!defined('DOMPDF_TEMP_DIR')) {
    $upload_dir = wp_upload_dir();
    if ($upload_dir && isset($upload_dir['basedir'])) {
        define('DOMPDF_TEMP_DIR', $upload_dir['basedir'] . '/ull-normativa/dompdf-temp/');
    }
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
        
        if ($result === false) {
            return false;
        }
        
        // Crear directorio de caché de fuentes si no existe
        $font_cache = wp_upload_dir()['basedir'] . '/ull-normativa/dompdf-cache/';
        wp_mkdir_p($font_cache);
        
        // Crear directorio temporal si no existe
        $temp_dir = wp_upload_dir()['basedir'] . '/ull-normativa/dompdf-temp/';
        wp_mkdir_p($temp_dir);
        
        return true;
    }
    
    /**
     * Desinstalar dompdf
     */
    public function uninstall() {
        global $wp_filesystem;
        WP_Filesystem();
        
        if ($wp_filesystem->exists($this->install_dir)) {
            return $wp_filesystem->delete($this->install_dir, true);
        }
        
        return true;
    }
    
    /**
     * Obtener instancia de Dompdf si está instalado
     */
    public function get_dompdf_instance() {
        if (!$this->is_installed()) {
            error_log('ULL Normativa: DOMPDF no está instalado');
            return false;
        }
        
        $autoload = $this->get_autoload_path();
        if (!$autoload || !file_exists($autoload)) {
            error_log('ULL Normativa: Archivo autoload no encontrado: ' . $autoload);
            return false;
        }
        
        // Cargar autoload
        try {
            require_once $autoload;
        } catch (Exception $e) {
            error_log('ULL Normativa: Error cargando autoload: ' . $e->getMessage());
            return false;
        }
        
        // Verificar que las clases necesarias existen
        if (!class_exists('Dompdf\Dompdf')) {
            error_log('ULL Normativa: Clase Dompdf\Dompdf no encontrada después de cargar autoload');
            
            // Intentar cargar manualmente si el autoload falló
            $dompdf_class = $this->install_dir . '/dompdf/src/Dompdf.php';
            if (file_exists($dompdf_class)) {
                try {
                    require_once $dompdf_class;
                } catch (Exception $e) {
                    error_log('ULL Normativa: Error cargando clase Dompdf manualmente: ' . $e->getMessage());
                    return false;
                }
            } else {
                error_log('ULL Normativa: Archivo Dompdf.php no encontrado en: ' . $dompdf_class);
                return false;
            }
        }
        
        // Verificar clase Options
        if (!class_exists('Dompdf\Options')) {
            error_log('ULL Normativa: Clase Dompdf\Options no encontrada');
            $options_class = $this->install_dir . '/dompdf/src/Options.php';
            if (file_exists($options_class)) {
                try {
                    require_once $options_class;
                } catch (Exception $e) {
                    error_log('ULL Normativa: Error cargando clase Options: ' . $e->getMessage());
                }
            }
        }
        
        // Crear instancia
        try {
            return new \Dompdf\Dompdf();
        } catch (Exception $e) {
            error_log('ULL Normativa: Error creando instancia de Dompdf: ' . $e->getMessage());
            return false;
        } catch (Error $e) {
            error_log('ULL Normativa: Error fatal creando instancia de Dompdf: ' . $e->getMessage());
            return false;
        }
    }
}
