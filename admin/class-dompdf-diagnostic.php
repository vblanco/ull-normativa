<?php
/**
 * Página de diagnóstico de DOMPDF
 * Para depurar problemas de instalación
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_DOMPDF_Diagnostic {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_diagnostic_page'));
        add_action('wp_ajax_ull_test_pdf_export', array($this, 'ajax_test_pdf_export'));
    }
    
    /**
     * Test de exportación PDF via AJAX
     */
    public function ajax_test_pdf_export() {
        check_ajax_referer('ull_test_pdf', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'No tienes permisos'));
        }
        
        try {
            // Cargar el exportador unificado
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-unified-pdf-export.php';
            $exporter = new ULL_Unified_PDF_Export();
            
            // Verificar que DOMPDF esté disponible
            if (!$exporter->has_pdf_library()) {
                wp_send_json_error(array(
                    'message' => 'DOMPDF no está disponible según el exportador',
                    'library' => $exporter->get_pdf_library()
                ));
            }
            
            // Intentar obtener instancia de DOMPDF
            $reflection = new ReflectionClass($exporter);
            $method = $reflection->getMethod('get_dompdf_instance');
            $method->setAccessible(true);
            $dompdf = $method->invoke($exporter);
            
            if (!$dompdf) {
                wp_send_json_error(array(
                    'message' => 'get_dompdf_instance() devolvió FALSE',
                    'error' => 'No se pudo obtener instancia de DOMPDF'
                ));
            }
            
            if (!($dompdf instanceof \Dompdf\Dompdf)) {
                wp_send_json_error(array(
                    'message' => 'La instancia no es de tipo Dompdf\Dompdf',
                    'type' => get_class($dompdf)
                ));
            }
            
            // Intentar generar un PDF de prueba
            $html = '<html><body><h1>Prueba</h1><p>Este es un PDF de prueba.</p></body></html>';
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Si llegamos aquí, funcionó
            wp_send_json_success(array(
                'message' => '✓ PDF de prueba generado correctamente. DOMPDF funciona.'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Exception capturada',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
        } catch (Error $e) {
            wp_send_json_error(array(
                'message' => 'Error fatal capturado',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
        }
    }
    
    public function add_diagnostic_page() {
        add_submenu_page(
            'edit.php?post_type=norma',
            __('Diagnóstico DOMPDF', 'ull-normativa'),
            __('Diagnóstico PDF', 'ull-normativa'),
            'manage_options',
            'ull-dompdf-diagnostic',
            array($this, 'render_diagnostic_page')
        );
    }
    
    public function render_diagnostic_page() {
        // Mostrar mensajes si existen
        $messages = get_transient('ull_autoload_fixer_message');
        if ($messages) {
            delete_transient('ull_autoload_fixer_message');
            foreach ($messages as $message) {
                $class = $message['type'] === 'error' ? 'notice-error' : 'notice-success';
                echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($message['message']) . '</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Diagnóstico de DOMPDF', 'ull-normativa'); ?></h1>
            
            <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
                <?php
                require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-dompdf-installer.php';
                $installer = new ULL_DOMPDF_Installer();
                
                echo '<h2>Estado de la Instalación</h2>';
                
                // Verificar instalación básica
                $is_installed = $installer->is_installed();
                echo '<p>';
                echo $is_installed 
                    ? '<span style="color: green;">✓</span> DOMPDF está instalado' 
                    : '<span style="color: red;">✗</span> DOMPDF NO está instalado';
                echo '</p>';
                
                if (!$is_installed) {
                    echo '<p><a href="' . admin_url('edit.php?post_type=norma&page=ull-pdf-settings') . '" class="button button-primary">Instalar DOMPDF</a></p>';
                    return;
                }
                
                // Verificar autoload
                $autoload_path = $installer->get_autoload_path();
                echo '<p><strong>Ruta de autoload:</strong> <code>' . esc_html($autoload_path) . '</code></p>';
                
                $autoload_exists = file_exists($autoload_path);
                echo '<p>';
                echo $autoload_exists
                    ? '<span style="color: green;">✓</span> Archivo autoload existe'
                    : '<span style="color: red;">✗</span> Archivo autoload NO existe';
                echo '</p>';
                
                // NUEVO: Verificar contenido del autoload
                if ($autoload_exists) {
                    $autoload_content = file_get_contents($autoload_path);
                    $has_cpdf_preload = strpos($autoload_content, "require_once \$cpdf_file") !== false || 
                                       strpos($autoload_content, 'lib/Cpdf.php') !== false;
                    $has_masterminds = strpos($autoload_content, 'Masterminds') !== false;
                    
                    echo '<p><strong>Contenido del autoload:</strong></p>';
                    echo '<ul style="list-style: disc; margin-left: 20px;">';
                    echo '<li>' . ($has_cpdf_preload ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . ' Pre-carga de Cpdf (lib/Cpdf.php)</li>';
                    echo '<li>' . ($has_masterminds ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . ' Soporte para Masterminds\\HTML5</li>';
                    echo '</ul>';
                    
                    if (!$has_cpdf_preload || !$has_masterminds) {
                        echo '<div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;">';
                        echo '<strong>⚠ Autoload desactualizado</strong><br>';
                        echo 'El autoload no tiene las correcciones necesarias. ';
                        echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=ull-dompdf-diagnostic&action=regenerate_autoload'), 'regenerate_autoload') . '">Regenerar autoload ahora</a>';
                        echo '</div>';
                    }
                }
                
                if (!$autoload_exists) {
                    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
                    echo '<strong>Error:</strong> El archivo autoload.inc.php no existe. Intente reinstalar DOMPDF.';
                    echo '</div>';
                    return;
                }
                
                // Cargar autoload y verificar clases
                echo '<h2>Verificación de Clases</h2>';
                
                $autoload_error = null;
                try {
                    // Capturar errores en un buffer
                    ob_start();
                    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$autoload_error) {
                        $autoload_error = "Error #$errno: $errstr en $errfile:$errline";
                        return true;
                    });
                    
                    require_once $autoload_path;
                    
                    restore_error_handler();
                    $output = ob_get_clean();
                    
                    if ($autoload_error) {
                        throw new Exception($autoload_error);
                    }
                    
                    echo '<p><span style="color: green;">✓</span> Autoload cargado correctamente</p>';
                    
                } catch (Throwable $e) {
                    restore_error_handler();
                    ob_end_clean();
                    
                    echo '<p><span style="color: red;">✗</span> Error al cargar autoload</p>';
                    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
                    echo '<strong>Error:</strong> ' . esc_html($e->getMessage()) . '<br>';
                    echo '<strong>Archivo:</strong> ' . esc_html($e->getFile()) . '<br>';
                    echo '<strong>Línea:</strong> ' . esc_html($e->getLine()) . '<br><br>';
                    echo '<strong>Solución:</strong> Regenere el autoload haciendo clic en el botón de abajo.';
                    echo '</div>';
                    
                    echo '<p>';
                    echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=ull-dompdf-diagnostic&action=regenerate_autoload'), 'regenerate_autoload') . '" class="button button-primary">';
                    echo '🔄 Regenerar Autoload';
                    echo '</a>';
                    echo '</p>';
                    
                    return;
                }
                
                // Verificar clases principales
                $classes_to_check = array(
                    'Dompdf\Dompdf' => array('desc' => 'Clase principal de DOMPDF', 'type' => 'class'),
                    'Dompdf\Options' => array('desc' => 'Clase de opciones de DOMPDF', 'type' => 'class'),
                    'Dompdf\Canvas' => array('desc' => 'Interface Canvas de DOMPDF', 'type' => 'interface'),
                    'FontLib\Font' => array('desc' => 'Librería de fuentes', 'type' => 'class'),
                    'Sabberworm\CSS\Parser' => array('desc' => 'Parser de CSS', 'type' => 'class')
                );
                
                echo '<table class="widefat" style="margin: 10px 0;">';
                echo '<thead><tr><th>Clase/Interface</th><th>Descripción</th><th>Estado</th></tr></thead>';
                echo '<tbody>';
                
                $all_ok = true;
                foreach ($classes_to_check as $class => $info) {
                    // Verificar si es clase o interface
                    if ($info['type'] === 'interface') {
                        $exists = interface_exists($class);
                    } else {
                        $exists = class_exists($class);
                    }
                    
                    $all_ok = $all_ok && $exists;
                    
                    echo '<tr>';
                    echo '<td><code>' . esc_html($class) . '</code></td>';
                    echo '<td>' . esc_html($info['desc']) . '</td>';
                    echo '<td>';
                    echo $exists 
                        ? '<span style="color: green;">✓ Disponible</span>'
                        : '<span style="color: red;">✗ No encontrada</span>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                
                // Verificar directorios
                echo '<h2>Directorios</h2>';
                
                $upload_dir = wp_upload_dir();
                $directories = array(
                    'Librería DOMPDF' => $upload_dir['basedir'] . '/ull-normativa-libs/dompdf',
                    'Caché de fuentes' => $upload_dir['basedir'] . '/ull-normativa/dompdf-cache',
                    'Temporal' => $upload_dir['basedir'] . '/ull-normativa/dompdf-temp'
                );
                
                echo '<table class="widefat" style="margin: 10px 0;">';
                echo '<thead><tr><th>Directorio</th><th>Ruta</th><th>Estado</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($directories as $name => $path) {
                    $exists = is_dir($path);
                    $writable = $exists && is_writable($path);
                    
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($name) . '</strong></td>';
                    echo '<td><code>' . esc_html($path) . '</code></td>';
                    echo '<td>';
                    if ($exists) {
                        if ($writable) {
                            echo '<span style="color: green;">✓ Existe y escribible</span>';
                        } else {
                            echo '<span style="color: orange;">⚠ Existe pero no escribible</span>';
                        }
                    } else {
                        echo '<span style="color: red;">✗ No existe</span>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                
                // Prueba de creación de instancia
                echo '<h2>Prueba de Instancia</h2>';
                
                try {
                    $test_instance = new \Dompdf\Dompdf();
                    echo '<p><span style="color: green;">✓</span> Se pudo crear una instancia de Dompdf correctamente</p>';
                    
                    // Probar configurar opciones
                    try {
                        $test_options = new \Dompdf\Options();
                        $test_options->set('isHtml5ParserEnabled', true);
                        $test_instance->setOptions($test_options);
                        echo '<p><span style="color: green;">✓</span> Se pudieron configurar opciones correctamente</p>';
                    } catch (Exception $e) {
                        echo '<p><span style="color: red;">✗</span> Error configurando opciones: ' . esc_html($e->getMessage()) . '</p>';
                        $all_ok = false;
                    }
                    
                } catch (Exception $e) {
                    echo '<p><span style="color: red;">✗</span> Error creando instancia: ' . esc_html($e->getMessage()) . '</p>';
                    $all_ok = false;
                } catch (Error $e) {
                    echo '<p><span style="color: red;">✗</span> Error fatal creando instancia: ' . esc_html($e->getMessage()) . '</p>';
                    $all_ok = false;
                }
                
                // Resumen final
                echo '<h2>Resumen</h2>';
                if ($all_ok) {
                    echo '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">';
                    echo '<strong>✓ Todo funciona correctamente</strong><br>';
                    echo 'DOMPDF está completamente instalado y listo para generar PDFs.';
                    echo '</div>';
                    
                    echo '<p><a href="' . admin_url('edit.php?post_type=norma') . '" class="button button-primary">Ver Normas</a></p>';
                    
                    // NUEVO: Añadir herramienta de test de exportación
                    echo '<hr style="margin: 30px 0;">';
                    echo '<h3>🧪 Prueba de Exportación PDF</h3>';
                    echo '<p>Haz clic en el botón de abajo para probar la exportación PDF y detectar cualquier error:</p>';
                    echo '<div id="pdf-test-result" style="margin: 15px 0;"></div>';
                    echo '<button type="button" id="test-pdf-export" class="button button-secondary">🧪 Probar Exportación PDF Ahora</button>';
                    
                    ?>
                    <script>
                    jQuery(document).ready(function($) {
                        $("#test-pdf-export").on("click", function() {
                            var button = $(this);
                            button.prop("disabled", true).text("⏳ Probando...");
                            $("#pdf-test-result").html('<div style="background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460;"><strong>Probando exportación PDF...</strong><br>Esto puede tardar unos segundos.</div>');
                            
                            $.post(ajaxurl, {
                                action: "ull_test_pdf_export",
                                _wpnonce: '<?php echo wp_create_nonce("ull_test_pdf"); ?>'
                            }, function(response) {
                                button.prop("disabled", false).text("🧪 Probar Exportación PDF Ahora");
                                
                                if (response.success) {
                                    $("#pdf-test-result").html(
                                        '<div style="background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;">' +
                                        '<strong>✓ Prueba exitosa</strong><br>' +
                                        (response.data.message || 'PDF se generó correctamente') +
                                        '</div>'
                                    );
                                } else {
                                    var errorHtml = '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">' +
                                        '<strong>✗ Error en la prueba de PDF</strong><br><br>';
                                    
                                    if (response.data.message) {
                                        errorHtml += '<strong>Mensaje:</strong> ' + response.data.message + '<br>';
                                    }
                                    if (response.data.error) {
                                        errorHtml += '<strong>Error:</strong> <code>' + response.data.error + '</code><br>';
                                    }
                                    if (response.data.file) {
                                        errorHtml += '<strong>Archivo:</strong> <code>' + response.data.file + '</code><br>';
                                    }
                                    if (response.data.line) {
                                        errorHtml += '<strong>Línea:</strong> ' + response.data.line + '<br>';
                                    }
                                    if (response.data.trace) {
                                        errorHtml += '<br><strong>Trace:</strong><br><pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px; font-size: 11px;">' + response.data.trace + '</pre>';
                                    }
                                    
                                    errorHtml += '</div>';
                                    $("#pdf-test-result").html(errorHtml);
                                }
                            }).fail(function(xhr, status, error) {
                                button.prop("disabled", false).text("🧪 Probar Exportación PDF Ahora");
                                $("#pdf-test-result").html(
                                    '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;">' +
                                    '<strong>✗ Error AJAX</strong><br>' +
                                    'Status: ' + status + '<br>' +
                                    'Error: ' + error + '<br>' +
                                    'Response: ' + xhr.responseText.substring(0, 500) +
                                    '</div>'
                                );
                            });
                        });
                    });
                    </script>
                    <?php
                } else {
                    echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0;">';
                    echo '<strong>⚠ Se encontraron problemas</strong><br>';
                    echo 'Revise los errores anteriores.';
                    echo '</div>';
                    
                    echo '<h3>Soluciones</h3>';
                    echo '<p>';
                    echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=ull-dompdf-diagnostic&action=regenerate_autoload'), 'regenerate_autoload') . '" class="button button-primary" style="margin-right: 10px;">';
                    echo '<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Regenerar Autoload (Recomendado)';
                    echo '</a>';
                    echo '<a href="' . admin_url('admin.php?page=ull-dompdf-structure') . '" class="button button-secondary" style="margin-right: 10px;">';
                    echo '<span class="dashicons dashicons-search" style="margin-top: 3px;"></span> Analizar Estructura de DOMPDF';
                    echo '</a>';
                    echo '<a href="' . admin_url('edit.php?post_type=norma&page=ull-pdf-settings') . '" class="button">';
                    echo 'Reinstalar DOMPDF';
                    echo '</a>';
                    echo '</p>';
                    
                    echo '<div style="background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 10px 0;">';
                    echo '<strong>💡 Consejo:</strong> Si el error es "Class Dompdf\Cpdf not found" o "Class Dompdf\Canvas not found", ';
                    echo 'haz clic en <strong>Analizar Estructura de DOMPDF</strong>. Esta herramienta explorará los archivos reales y generará un autoload personalizado.';
                    echo '</div>';
                }
                
                // Información del sistema
                echo '<h2>Información del Sistema</h2>';
                echo '<table class="widefat" style="margin: 10px 0;">';
                echo '<tbody>';
                echo '<tr><td><strong>PHP Version</strong></td><td>' . PHP_VERSION . '</td></tr>';
                echo '<tr><td><strong>WordPress Version</strong></td><td>' . get_bloginfo('version') . '</td></tr>';
                echo '<tr><td><strong>Plugin Version</strong></td><td>' . ULL_NORMATIVA_VERSION . '</td></tr>';
                echo '<tr><td><strong>Memory Limit</strong></td><td>' . ini_get('memory_limit') . '</td></tr>';
                echo '<tr><td><strong>Max Execution Time</strong></td><td>' . ini_get('max_execution_time') . 's</td></tr>';
                echo '</tbody></table>';
                ?>
            </div>
        </div>
        <?php
    }
}

// Inicializar diagnóstico
new ULL_DOMPDF_Diagnostic();
