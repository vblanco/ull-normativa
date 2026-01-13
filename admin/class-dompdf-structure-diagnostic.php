<?php
/**
 * Herramienta de diagnóstico de estructura de DOMPDF
 * Explora los archivos y genera un autoload correcto
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_DOMPDF_Structure_Diagnostic {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_diagnostic_page'));
    }
    
    public function add_diagnostic_page() {
        add_submenu_page(
            null, // Página oculta
            __('Estructura DOMPDF', 'ull-normativa'),
            __('Estructura DOMPDF', 'ull-normativa'),
            'manage_options',
            'ull-dompdf-structure',
            array($this, 'render_diagnostic_page')
        );
    }
    
    public function render_diagnostic_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos.', 'ull-normativa'));
        }
        
        $upload_dir = wp_upload_dir();
        $dompdf_dir = $upload_dir['basedir'] . '/ull-normativa-libs/dompdf';
        
        ?>
        <div class="wrap">
            <h1><?php _e('Estructura de DOMPDF', 'ull-normativa'); ?></h1>
            
            <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
                
                <h2>Archivos en /src/</h2>
                <?php
                $src_dir = $dompdf_dir . '/src';
                if (is_dir($src_dir)) {
                    echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">';
                    $this->list_php_files($src_dir, $src_dir);
                    echo '</pre>';
                } else {
                    echo '<p style="color: red;">Directorio src/ no encontrado</p>';
                }
                ?>
                
                <h2>Buscar Clases Específicas</h2>
                <?php
                $classes_to_find = array('Canvas', 'Cpdf', 'CPDF');
                
                foreach ($classes_to_find as $class_name) {
                    echo '<h3>Buscando: ' . esc_html($class_name) . '</h3>';
                    $found_files = $this->find_class_file($dompdf_dir, $class_name);
                    
                    if (empty($found_files)) {
                        echo '<p style="color: orange;">No se encontraron archivos para ' . esc_html($class_name) . '</p>';
                    } else {
                        echo '<ul>';
                        foreach ($found_files as $file) {
                            $relative = str_replace($dompdf_dir . '/', '', $file);
                            echo '<li><code>' . esc_html($relative) . '</code>';
                            
                            // Intentar leer el namespace de la clase
                            $content = file_get_contents($file);
                            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                                echo ' <span style="color: blue;">(namespace: ' . esc_html($matches[1]) . ')</span>';
                            }
                            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                                echo ' <span style="color: green;">(class: ' . esc_html($matches[1]) . ')</span>';
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                    }
                }
                ?>
                
                <h2>Generar Autoload Correcto</h2>
                <?php
                // Mostrar contenido del autoload si se solicita
                if (isset($_GET['show_autoload'])) {
                    $autoload_file = $dompdf_dir . '/autoload.inc.php';
                    if (file_exists($autoload_file)) {
                        echo '<h3>Contenido Actual del autoload.inc.php:</h3>';
                        echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 500px;">';
                        echo esc_html(file_get_contents($autoload_file));
                        echo '</pre>';
                        echo '<p><a href="' . admin_url('admin.php?page=ull-dompdf-structure') . '" class="button">Volver</a></p>';
                    }
                }
                
                if (isset($_POST['generate_autoload'])) {
                    check_admin_referer('generate_autoload');
                    
                    $result = $this->generate_correct_autoload($dompdf_dir);
                    
                    if ($result) {
                        echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
                        echo '<strong>✓ Autoload generado correctamente</strong><br>';
                        echo 'El archivo autoload.inc.php ha sido actualizado.';
                        echo '</div>';
                        echo '<p><a href="' . admin_url('edit.php?post_type=norma&page=ull-dompdf-diagnostic') . '" class="button button-primary">Ir a Diagnóstico</a></p>';
                    } else {
                        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
                        echo '<strong>✗ Error al generar autoload</strong><br>';
                        echo 'Verifique permisos de escritura.';
                        echo '</div>';
                    }
                } else {
                    ?>
                    <form method="post">
                        <?php wp_nonce_field('generate_autoload'); ?>
                        <input type="hidden" name="generate_autoload" value="1">
                        <p>
                            <button type="submit" class="button button-primary">
                                🔧 Generar Autoload Basado en Estructura Real
                            </button>
                            <a href="<?php echo admin_url('admin.php?page=ull-dompdf-structure&show_autoload=1'); ?>" class="button" style="margin-left: 10px;">
                                👁️ Ver Contenido del Autoload Actual
                            </a>
                        </p>
                        <p class="description">
                            Esto analizará la estructura real de archivos de DOMPDF y generará un autoload que funcione correctamente.
                        </p>
                    </form>
                    
                    <h3>Prueba de Carga Manual de Archivos</h3>
                    <p>Vamos a revisar el contenido real de los archivos problemáticos:</p>
                    <?php
                    echo '<div style="background: #f5f5f5; padding: 15px; margin: 10px 0; font-family: monospace; font-size: 12px;">';
                    
                    // Intentar cargar lib/Cpdf.php
                    $cpdf_file = $dompdf_dir . '/lib/Cpdf.php';
                    echo '<strong>1. Archivo lib/Cpdf.php:</strong><br>';
                    echo 'Ruta: ' . esc_html($cpdf_file) . '<br>';
                    echo 'Existe: ' . (file_exists($cpdf_file) ? '✓ Sí' : '✗ No') . '<br>';
                    
                    if (file_exists($cpdf_file)) {
                        try {
                            $content = file_get_contents($cpdf_file);
                            $lines = explode("\n", $content);
                            $first_lines = array_slice($lines, 0, 50);
                            
                            echo 'Primeras 50 líneas:<br>';
                            echo '<pre style="background: white; padding: 5px; margin: 5px 0; max-height: 300px; overflow: auto;">';
                            foreach ($first_lines as $i => $line) {
                                echo sprintf("%3d: %s\n", $i + 1, esc_html($line));
                            }
                            echo '</pre>';
                            
                            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                                echo 'Namespace: <strong>' . esc_html($matches[1]) . '</strong><br>';
                            } else {
                                echo 'Namespace: <span style="color: orange;">No encontrado (clase global)</span><br>';
                            }
                            
                            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                                echo 'Clase: <strong>' . esc_html($matches[1]) . '</strong><br>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<span style="color: red;">Error: ' . esc_html($e->getMessage()) . '</span><br>';
                        }
                    }
                    
                    echo '<br><hr><br>';
                    
                    echo '<strong>2. Archivo src/Canvas.php:</strong><br>';
                    $canvas_file = $dompdf_dir . '/src/Canvas.php';
                    echo 'Ruta: ' . esc_html($canvas_file) . '<br>';
                    echo 'Existe: ' . (file_exists($canvas_file) ? '✓ Sí' : '✗ No') . '<br>';
                    
                    if (file_exists($canvas_file)) {
                        try {
                            $content = file_get_contents($canvas_file);
                            $lines = explode("\n", $content);
                            $first_lines = array_slice($lines, 0, 50);
                            
                            echo 'Primeras 50 líneas:<br>';
                            echo '<pre style="background: white; padding: 5px; margin: 5px 0; max-height: 300px; overflow: auto;">';
                            foreach ($first_lines as $i => $line) {
                                echo sprintf("%3d: %s\n", $i + 1, esc_html($line));
                            }
                            echo '</pre>';
                            
                            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                                echo 'Namespace: <strong>' . esc_html($matches[1]) . '</strong><br>';
                            } else {
                                echo 'Namespace: <span style="color: orange;">No encontrado</span><br>';
                            }
                            
                            if (preg_match('/(abstract\s+)?class\s+(\w+)/', $content, $matches)) {
                                echo 'Clase: <strong>' . esc_html($matches[2]) . '</strong>';
                                if (!empty($matches[1])) {
                                    echo ' (abstracta)';
                                }
                                echo '<br>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<span style="color: red;">Error: ' . esc_html($e->getMessage()) . '</span><br>';
                        }
                    }
                    
                    echo '</div>';
                    
                    echo '<h3>Diagnóstico de Carga Manual</h3>';
                    echo '<div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107;">';
                    echo '<strong>Vamos a intentar cargar los archivos manualmente para ver qué pasa:</strong><br><br>';
                    
                    // Limpiar cualquier clase cargada previamente
                    echo '1. <strong>Intentando cargar lib/Cpdf.php manualmente:</strong><br>';
                    $cpdf_file_test = $dompdf_dir . '/lib/Cpdf.php';
                    echo 'Archivo: <code>' . esc_html($cpdf_file_test) . '</code><br>';
                    echo 'Existe: ' . (file_exists($cpdf_file_test) ? '✓ Sí' : '✗ No') . '<br>';
                    
                    if (file_exists($cpdf_file_test)) {
                        echo 'Tamaño: ' . filesize($cpdf_file_test) . ' bytes<br>';
                        echo 'Legible: ' . (is_readable($cpdf_file_test) ? '✓ Sí' : '✗ No') . '<br>';
                        
                        try {
                            // Verificar si ya está cargada
                            $before_load = class_exists('Dompdf\\Cpdf', false);
                            echo 'Clase Dompdf\\Cpdf antes de require: ' . ($before_load ? '✓ Ya existe' : '✗ No existe') . '<br>';
                            
                            if (!$before_load) {
                                echo 'Intentando require_once...<br>';
                                ob_start();
                                require_once $cpdf_file_test;
                                $output = ob_get_clean();
                                
                                if (!empty($output)) {
                                    echo 'Output del require: <pre>' . esc_html($output) . '</pre>';
                                }
                                
                                $after_load = class_exists('Dompdf\\Cpdf', false);
                                echo 'Clase Dompdf\\Cpdf después de require: ' . ($after_load ? '<strong style="color: green;">✓ AHORA SÍ EXISTE</strong>' : '<strong style="color: red;">✗ SIGUE SIN EXISTIR</strong>') . '<br>';
                                
                                if ($after_load) {
                                    echo '<span style="color: green; font-weight: bold;">¡El archivo se cargó correctamente!</span><br>';
                                    
                                    // Intentar crear instancia
                                    try {
                                        $reflection = new ReflectionClass('Dompdf\\Cpdf');
                                        echo 'Namespace de la clase: <code>' . esc_html($reflection->getNamespaceName()) . '</code><br>';
                                        echo 'Nombre de la clase: <code>' . esc_html($reflection->getShortName()) . '</code><br>';
                                        echo 'Nombre completo: <code>' . esc_html($reflection->getName()) . '</code><br>';
                                    } catch (Exception $e) {
                                        echo 'Error al inspeccionar clase: ' . esc_html($e->getMessage()) . '<br>';
                                    }
                                } else {
                                    echo '<span style="color: red; font-weight: bold;">ERROR: El archivo se cargó pero la clase no está disponible.</span><br>';
                                    echo 'Esto significa que el archivo NO define la clase Dompdf\\Cpdf correctamente.<br>';
                                }
                            }
                        } catch (Throwable $e) {
                            echo '<strong style="color: red;">Error al cargar: ' . esc_html($e->getMessage()) . '</strong><br>';
                            echo 'Archivo: ' . esc_html($e->getFile()) . '<br>';
                            echo 'Línea: ' . esc_html($e->getLine()) . '<br>';
                            echo 'Trace:<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
                        }
                    }
                    
                    echo '<br>2. <strong>Intentando cargar src/Canvas.php manualmente:</strong><br>';
                    $canvas_file_test = $dompdf_dir . '/src/Canvas.php';
                    echo 'Archivo: <code>' . esc_html($canvas_file_test) . '</code><br>';
                    echo 'Existe: ' . (file_exists($canvas_file_test) ? '✓ Sí' : '✗ No') . '<br>';
                    
                    if (file_exists($canvas_file_test)) {
                        echo 'Tamaño: ' . filesize($canvas_file_test) . ' bytes<br>';
                        
                        try {
                            $before_load = interface_exists('Dompdf\\Canvas', false);
                            echo 'Interface Dompdf\\Canvas antes de require: ' . ($before_load ? '✓ Ya existe' : '✗ No existe') . '<br>';
                            
                            if (!$before_load) {
                                ob_start();
                                require_once $canvas_file_test;
                                $output = ob_get_clean();
                                
                                $after_load = interface_exists('Dompdf\\Canvas', false);
                                echo 'Interface Dompdf\\Canvas después de require: ' . ($after_load ? '<strong style="color: green;">✓ AHORA SÍ EXISTE</strong>' : '<strong style="color: red;">✗ SIGUE SIN EXISTIR</strong>') . '<br>';
                            }
                        } catch (Throwable $e) {
                            echo '<strong style="color: red;">Error: ' . esc_html($e->getMessage()) . '</strong><br>';
                        }
                    }
                    
                    echo '<br>3. <strong>Verificación de OPcache:</strong><br>';
                    if (function_exists('opcache_get_status')) {
                        $opcache = opcache_get_status();
                        if ($opcache && isset($opcache['opcache_enabled'])) {
                            echo 'OPcache está: <strong>' . ($opcache['opcache_enabled'] ? 'ACTIVADO' : 'Desactivado') . '</strong><br>';
                            if ($opcache['opcache_enabled']) {
                                echo '<span style="color: orange;">⚠ OPcache podría estar cacheando el autoload antiguo.</span><br>';
                                echo 'Solución: Reinicia PHP-FPM o Apache.<br>';
                            }
                        }
                    } else {
                        echo 'OPcache: No disponible<br>';
                    }
                    
                    echo '</div>';
                    ?>
                    <?php
                }
                ?>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Listar archivos PHP recursivamente
     */
    private function list_php_files($dir, $base_dir, $indent = 0) {
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . '/' . $item;
            $relative = str_replace($base_dir . '/', '', $path);
            
            if (is_dir($path)) {
                echo str_repeat('  ', $indent) . '📁 ' . esc_html($item) . '/
';
                $this->list_php_files($path, $base_dir, $indent + 1);
            } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                echo str_repeat('  ', $indent) . '📄 ' . esc_html($relative) . '
';
            }
        }
    }
    
    /**
     * Buscar archivo de clase
     */
    private function find_class_file($dir, $class_name) {
        $found = array();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filename = $file->getFilename();
                
                // Buscar por nombre de archivo
                if (stripos($filename, $class_name) !== false) {
                    $found[] = $file->getPathname();
                }
            }
        }
        
        return $found;
    }
    
    /**
     * Generar autoload correcto basado en estructura real
     */
    private function generate_correct_autoload($dompdf_dir) {
        // Buscar todas las clases importantes
        $canvas_files = $this->find_class_file($dompdf_dir, 'Canvas');
        $cpdf_files = $this->find_class_file($dompdf_dir, 'Cpdf');
        
        // Filtrar para encontrar los archivos correctos
        $canvas_src = null;
        $cpdf_lib = null;
        $adapter_cpdf = null;
        
        foreach ($canvas_files as $file) {
            if (strpos($file, '/src/Canvas.php') !== false) {
                $canvas_src = $file;
            }
        }
        
        foreach ($cpdf_files as $file) {
            // Cpdf está en lib/, no en src/
            if (strpos($file, '/lib/Cpdf.php') !== false) {
                $cpdf_lib = $file;
            }
            if (strpos($file, '/src/Adapter/CPDF.php') !== false) {
                $adapter_cpdf = $file;
            }
        }
        
        // Generar autoload
        $autoload_content = <<<'PHP'
<?php
/**
 * Autoload personalizado para DOMPDF
 * Generado basado en estructura real analizada
 */

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

// CRÍTICO: Pre-cargar archivos que DOMPDF necesita antes del autoloader
// Estos archivos están en ubicaciones no estándar

PHP;

        // Añadir carga de Cpdf si existe en lib/
        if ($cpdf_lib && file_exists($cpdf_lib)) {
            $autoload_content .= "\n// 1. Cpdf - La clase principal de backend está en lib/Cpdf.php\n";
            $autoload_content .= "\$cpdf_file = DOMPDF_DIR . '/lib/Cpdf.php';\n";
            $autoload_content .= "if (file_exists(\$cpdf_file)) {\n";
            $autoload_content .= "    require_once \$cpdf_file;\n";
            $autoload_content .= "}\n";
        }
        
        // Añadir carga de Canvas si existe
        if ($canvas_src && file_exists($canvas_src)) {
            $autoload_content .= "\n// 2. Canvas - Define la clase abstracta Canvas\n";
            $autoload_content .= "\$canvas_file = DOMPDF_DIR . '/src/Canvas.php';\n";
            $autoload_content .= "if (file_exists(\$canvas_file)) {\n";
            $autoload_content .= "    require_once \$canvas_file;\n";
            $autoload_content .= "}\n";
        }
        
        // Añadir carga del Adapter CPDF
        if ($adapter_cpdf && file_exists($adapter_cpdf)) {
            $autoload_content .= "\n// 3. Adapter CPDF - Adaptador que usa Cpdf\n";
            $autoload_content .= "\$adapter_file = DOMPDF_DIR . '/src/Adapter/CPDF.php';\n";
            $autoload_content .= "if (file_exists(\$adapter_file)) {\n";
            $autoload_content .= "    require_once \$adapter_file;\n";
            $autoload_content .= "}\n";
        }
        
        $autoload_content .= <<<'PHP'

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

// Cargar funciones helper
$functions_file = DOMPDF_DIR . '/src/functions.php';
if (file_exists($functions_file)) {
    require_once $functions_file;
}

PHP;
        
        // Guardar autoload
        $autoload_file = $dompdf_dir . '/autoload.inc.php';
        
        // Backup
        if (file_exists($autoload_file)) {
            @copy($autoload_file, $autoload_file . '.backup.' . date('YmdHis'));
        }
        
        return @file_put_contents($autoload_file, $autoload_content) !== false;
    }
}

// Inicializar si estamos en la página de diagnóstico
if (isset($_GET['page']) && $_GET['page'] === 'ull-dompdf-structure') {
    new ULL_DOMPDF_Structure_Diagnostic();
}
