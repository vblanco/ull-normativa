<?php
/**
 * Exportador unificado de normas y códigos a PDF usando DOMPDF
 * 
 * @package ULL_Normativa
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Unified_PDF_Export {
    
    private $pdf_library = null;
    private $dompdf_installer = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'handle_pdf_export'));
        add_action('init', array($this, 'handle_xml_export'));
        $this->detect_pdf_library();
    }
    
    /**
     * Detectar si hay librería PDF disponible
     */
    private function detect_pdf_library() {
        // Primero verificar si existe Composer
        $composer_autoload = ULL_NORMATIVA_PLUGIN_DIR . 'vendor/autoload.php';
        if (file_exists($composer_autoload)) {
            $this->pdf_library = 'composer';
            return;
        }
        
        // Verificar si hay DOMPDF instalado via el instalador
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-dompdf-installer.php';
        $this->dompdf_installer = new ULL_DOMPDF_Installer();
        
        if ($this->dompdf_installer->is_installed()) {
            $this->pdf_library = 'dompdf';
        } else {
            $this->pdf_library = 'html';
        }
    }
    
    /**
     * Obtener la librería PDF activa
     */
    public function get_pdf_library() {
        return $this->pdf_library;
    }
    
    /**
     * Verificar si hay una librería PDF disponible
     */
    public function has_pdf_library() {
        return $this->pdf_library === 'composer' || $this->pdf_library === 'dompdf';
    }
    
    /**
     * Obtener instancia de DOMPDF
     */
    private function get_dompdf_instance() {
        if ($this->pdf_library === 'composer') {
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'vendor/autoload.php';
            return new \Dompdf\Dompdf();
        } elseif ($this->pdf_library === 'dompdf') {
            if (!$this->dompdf_installer) {
                require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-dompdf-installer.php';
                $this->dompdf_installer = new ULL_DOMPDF_Installer();
            }

            // Cargar el autoloader explícitamente antes de devolver la instancia
            $autoload_path = $this->dompdf_installer->get_autoload_path();
            if ($autoload_path && file_exists($autoload_path)) {
                require_once $autoload_path;
            }

            return $this->dompdf_installer->get_dompdf_instance();
        }
        
        return false;
    }
    
    /**
     * Manejar petición de exportación PDF
     */
    public function handle_pdf_export() {
        if (!isset($_GET['ull_export_pdf']) || empty($_GET['ull_export_pdf'])) {
            return;
        }
        
        $post_id = intval($_GET['ull_export_pdf']);
        $post = get_post($post_id);
        
        // Verificar que el post existe y es de tipo norma o codigo
        if (!$post || !in_array($post->post_type, array('norma', 'codigo')) || $post->post_status !== 'publish') {
            wp_die(__('Contenido no encontrado.', 'ull-normativa'));
        }
        
        $this->generate_pdf($post);
    }
    
    /**
     * Manejar petición de exportación XML
     */
    public function handle_xml_export() {
        if (!isset($_GET['ull_export_xml']) || empty($_GET['ull_export_xml'])) {
            return;
        }
        
        $post_id = intval($_GET['ull_export_xml']);
        $post = get_post($post_id);
        
        // Solo para normas
        if (!$post || $post->post_type !== 'norma' || $post->post_status !== 'publish') {
            wp_die(__('Norma no encontrada.', 'ull-normativa'));
        }
        
        $this->generate_xml($post);
    }
    
    /**
     * Generar PDF
     */
    private function generate_pdf($post) {
        // Determinar si es norma individual o código
        $normas_data = null;
        
        if ($post->post_type === 'codigo') {
            // Obtener información de normas para bookmarks
            $normas_ids = get_post_meta($post->ID, '_codigo_normas', true);
            if (!empty($normas_ids) && is_array($normas_ids)) {
                $normas_data = array();
                foreach ($normas_ids as $norma_data_item) {
                    $norma_id = isset($norma_data_item['id']) ? $norma_data_item['id'] : $norma_data_item;
                    $norma = get_post($norma_id);
                    if ($norma) {
                        $normas_data[] = array(
                            'id' => $norma_id,
                            'title' => $norma->post_title,
                            'seccion' => isset($norma_data_item['seccion']) ? $norma_data_item['seccion'] : ''
                        );
                    }
                }
            }
            
            $html = $this->get_codigo_pdf_html($post);
        } else {
            $html = $this->get_norma_pdf_html($post);
        }
        
        $numero = get_post_meta($post->ID, '_numero_norma', true);
        $filename = sanitize_file_name(($numero ? $numero : $post->post_type . '-' . $post->ID) . '.pdf');
        
        // Obtener configuración
        $settings = $this->get_pdf_settings();
        
        // Generar PDF con DOMPDF o fallback a HTML
        if ($this->has_pdf_library()) {
            $this->render_with_dompdf($html, $filename, $settings, $normas_data);
        } else {
            $this->render_html_page($html);
        }
    }
    
    /**
     * Renderizar PDF con DOMPDF
     */
    private function render_with_dompdf($html, $filename, $settings = array(), $normas_data = null) {
        $dompdf = $this->get_dompdf_instance();
        
        // VERIFICACIÓN CRÍTICA PARA MULTISITE
        if (!$dompdf || !class_exists('\Dompdf\Options')) {
            // Si falla, intentamos cargar la ruta global manualmente como último recurso
            $global_autoload = WP_CONTENT_DIR . '/uploads/ull-normativa-libs/dompdf/autoload.inc.php';
            if (file_exists($global_autoload)) {
                require_once $global_autoload;
            }
        
            // Reintentar instancia
            if (!class_exists('\Dompdf\Options')) {
                wp_die(__('La librería PDF no está configurada correctamente para la red Multisite.', 'ull-normativa'));
            }
        }
        
        // Obtener configuración
        $options = get_option('ull_pdf_options', array());
        $orientation = isset($options['pdf_orientation']) ? $options['pdf_orientation'] : 'portrait';
        $paper_size = isset($options['pdf_paper_size']) ? $options['pdf_paper_size'] : 'A4';
        
        // Configurar opciones de DOMPDF
        $dompdf_options = new \Dompdf\Options();
        $dompdf_options->set('isRemoteEnabled', true);
        $dompdf_options->set('isHtml5ParserEnabled', true);
        $dompdf_options->set('isPhpEnabled', true); // Habilitar scripts PHP para bookmarks
        
        // Usar fuente de la configuración si está disponible
        $default_font = !empty($settings['font_family']) ? $settings['font_family'] : 'DejaVu Sans';
        $dompdf_options->set('defaultFont', $default_font);
        
        $dompdf->setOptions($dompdf_options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper_size, $orientation);
        $dompdf->render();
        
        // Agregar bookmarks si tenemos datos de normas
        if (!empty($normas_data) && is_array($normas_data)) {
            $this->add_pdf_bookmarks_post_render($dompdf, $normas_data);
        }
        
        // Mostrar en el navegador (false) en lugar de forzar descarga (true)
        $dompdf->stream($filename, array('Attachment' => false));
        exit;
    }
    
    /**
     * Agregar bookmarks al PDF después del render
     */
    private function add_pdf_bookmarks_post_render($dompdf, $normas_data) {
        try {
            $canvas = $dompdf->getCanvas();
            $cpdf = $canvas->get_cpdf();
            
            // Preparar array de outlines
            $outlines = array();
            $current_section = '';
            $section_index = -1;
            
            foreach ($normas_data as $index => $norma) {
                $norma_id = $norma['id'];
                $title = $norma['title'];
                $seccion = !empty($norma['seccion']) ? $norma['seccion'] : '';
                
                // Encontrar la página donde está la norma
                // Esto es aproximado ya que no tenemos acceso directo a las posiciones
                $page_num = 1; // Por defecto página 1
                
                // Si cambia la sección, crear outline de sección
                if ($seccion && $seccion !== $current_section) {
                    $current_section = $seccion;
                    $section_index++;
                    
                    // Agregar outline de sección
                    $outlines[] = array(
                        'title' => $seccion,
                        'level' => 0,
                        'page' => $page_num,
                        'top' => 800
                    );
                }
                
                // Agregar outline de norma
                $outlines[] = array(
                    'title' => $title,
                    'level' => $seccion ? 1 : 0,
                    'page' => $page_num,
                    'top' => 800
                );
            }
            
            // Intentar agregar los outlines usando diferentes métodos
            if (method_exists($cpdf, 'addOutline')) {
                foreach ($outlines as $outline) {
                    $cpdf->addOutline($outline['title'], $outline['level'], $outline['page'], $outline['top']);
                }
            } elseif (method_exists($cpdf, 'add_outline')) {
                foreach ($outlines as $outline) {
                    $cpdf->add_outline($outline['title'], $outline['level'], $outline['top'], $outline['page']);
                }
            }
            
        } catch (\Exception $e) {
            // Si falla, continuar sin bookmarks
            error_log('ULL Normativa - Error agregando bookmarks: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener HTML para PDF de norma individual
     */
    private function get_norma_pdf_html($post) {
        // Obtener configuración
        $settings = $this->get_pdf_settings();
        
        // Guardar referencia al post como variable local para evitar conflictos con global
        $local_post = $post;
        
        // Obtener metadatos
        $numero = get_post_meta($local_post->ID, '_numero_norma', true);
        $fecha_aprobacion = get_post_meta($local_post->ID, '_fecha_aprobacion', true);
        $fecha_publicacion = get_post_meta($local_post->ID, '_fecha_publicacion', true);
        $fecha_vigencia = get_post_meta($local_post->ID, '_fecha_vigencia', true);
        $organo = get_post_meta($local_post->ID, '_organo_emisor', true);
        $boletin = get_post_meta($local_post->ID, '_boletin_oficial', true);
        $estado = get_post_meta($local_post->ID, '_estado_norma', true);
        
        // Procesar contenido - Los shortcodes se ejecutan PRIMERO, luego se sanitiza
        $content = $local_post->post_content;
        
        // IMPORTANTE: Asegurar que el $post global esté disponible para los shortcodes
        global $post;
        $backup_global_post = $post; // Guardar el post global actual
        $post = $local_post; // Establecer el post global con nuestro post local
        setup_postdata($post); // Configurar datos del post
        
        $content = do_shortcode($content); // Ejecuta shortcodes (genera HTML del índice)
        
        // Restaurar el post global anterior
        $post = $backup_global_post;
        if ($backup_global_post) {
            setup_postdata($backup_global_post);
        } else {
            wp_reset_postdata();
        }
        
        $content = $this->add_heading_ids_for_pdf($content);
        $content = $this->sanitize_html_for_pdf($content); // Sanitiza (incluye wpautop dentro)
        
        // Generar TOC automático SOLO si el contenido no tiene el shortcode de tabla de contenidos
        $has_toc_shortcode = (
            strpos($local_post->post_content, '[ull_tabla_contenidos]') !== false ||
            strpos($local_post->post_content, '[tabla_contenidos]') !== false
        );
        $toc_html = $has_toc_shortcode ? '' : $this->generate_toc_from_content($content);
        
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($local_post->post_title); ?></title>
    <?php echo $this->get_pdf_styles($settings); ?>
</head>
<body>
    <?php echo $this->get_pdf_header($settings, $local_post); ?>
    
    <div class="document-title">
        <h1><?php echo esc_html($local_post->post_title); ?></h1>
        <?php if ($numero): ?>
            <p class="numero"><?php echo esc_html($numero); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if ($fecha_aprobacion || $fecha_publicacion || $fecha_vigencia || $organo || $boletin || $estado): ?>
    <div class="metadata">
        <h3><?php _e('Información normativa', 'ull-normativa'); ?></h3>
        <table class="metadata-table">
            <?php if ($fecha_aprobacion): ?>
            <tr><td><?php _e('Fecha de aprobación:', 'ull-normativa'); ?></td><td><?php echo esc_html(date_i18n('j \d\e F \d\e Y', strtotime($fecha_aprobacion))); ?></td></tr>
            <?php endif; ?>
            <?php if ($fecha_publicacion): ?>
            <tr><td><?php _e('Fecha de publicación:', 'ull-normativa'); ?></td><td><?php echo esc_html(date_i18n('j \d\e F \d\e Y', strtotime($fecha_publicacion))); ?></td></tr>
            <?php endif; ?>
            <?php if ($fecha_vigencia): ?>
            <tr><td><?php _e('En vigor desde:', 'ull-normativa'); ?></td><td><?php echo esc_html(date_i18n('j \d\e F \d\e Y', strtotime($fecha_vigencia))); ?></td></tr>
            <?php endif; ?>
            <?php if ($organo): ?>
            <tr><td><?php _e('Órgano emisor:', 'ull-normativa'); ?></td><td><?php echo esc_html($organo); ?></td></tr>
            <?php endif; ?>
            <?php if ($boletin): ?>
            <tr><td><?php _e('Boletín oficial:', 'ull-normativa'); ?></td><td><?php echo esc_html($boletin); ?></td></tr>
            <?php endif; ?>
            <?php if ($estado): 
                $estado_label = '';
                $estado_class = '';
                
                if ($estado === 'vigente') {
                    $estado_label = __('Vigente', 'ull-normativa');
                    $estado_class = 'estado-vigente';
                } elseif ($estado === 'derogada') {
                    $estado_label = __('Derogada', 'ull-normativa');
                    $estado_class = 'estado-derogada';
                } else {
                    $estado_label = ucfirst($estado);
                }
            ?>
            <tr><td><?php _e('Estado:', 'ull-normativa'); ?></td><td><span class="<?php echo esc_attr($estado_class); ?>"><?php echo esc_html($estado_label); ?></span></td></tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <?php
    // Mostrar relaciones normativas si existen
    $relaciones_html = $this->get_relaciones_norma_html($local_post->ID);
    if (!empty($relaciones_html)) {
        echo $relaciones_html;
    }
    ?>
    
    <?php if (!empty($toc_html)): ?>
    <div class="table-of-contents">
        <h3><?php _e('Índice de contenidos', 'ull-normativa'); ?></h3>
        <?php echo $toc_html; ?>
    </div>
    <?php endif; ?>
    
    <div class="content">
        <?php echo $content; ?>
    </div>
    
    <?php echo $this->get_pdf_footer($settings); ?>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtener HTML para PDF de código
     */
    private function get_codigo_pdf_html($post) {
        // Obtener configuración
        $settings = $this->get_pdf_settings();
        
        // Obtener normas del código - el meta_key correcto es '_codigo_normas'
        $normas_ids = get_post_meta($post->ID, '_codigo_normas', true);
        if (empty($normas_ids) || !is_array($normas_ids)) {
            $normas_ids = array();
        }
        
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($post->post_title); ?></title>
    <!-- Plugin Version: 2.5.0-METADATOS-COMPLETOS -->
    <?php echo $this->get_pdf_styles($settings); ?>
</head>
<body>
    <?php echo $this->get_pdf_header($settings, $post); ?>
    
    <div id="inicio-pdf"></div>
    
    <div class="document-title">
        <h1><?php echo esc_html($post->post_title); ?></h1>
    </div>
    
    <?php if (!empty($post->post_excerpt)): ?>
    <div class="codigo-introduccion">
        <?php echo wpautop($post->post_excerpt); ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($normas_ids)): ?>
    <div class="indice">
        <h3><?php _e('Índice', 'ull-normativa'); ?></h3>
        <ul>
        <?php
        $seccion_actual = '';
        foreach ($normas_ids as $norma_data) {
            $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
            $norma = get_post($norma_id);
            if (!$norma) continue;
            
            $seccion = isset($norma_data['seccion']) ? $norma_data['seccion'] : '';
            
            if ($seccion && $seccion !== $seccion_actual) {
                if ($seccion_actual) echo '</ul></li>';
                $seccion_actual = $seccion;
                echo '<li><strong>' . esc_html($seccion) . '</strong><ul>';
            }
            
            // Enlace interno a la norma
            echo '<li><a href="#norma-' . esc_attr($norma_id) . '">' . esc_html($norma->post_title) . '</a></li>';
        }
        if ($seccion_actual) echo '</ul></li>';
        ?>
        </ul>
    </div>
    
    <?php
    // Contenido de normas
    $seccion_actual = '';
    foreach ($normas_ids as $norma_data) {
        $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
        $norma = get_post($norma_id);
        if (!$norma) continue;
        
        // Obtener todos los metadatos
        $numero = get_post_meta($norma_id, '_numero_norma', true);
        $fecha_aprobacion = get_post_meta($norma_id, '_fecha_aprobacion', true);
        $fecha_publicacion = get_post_meta($norma_id, '_fecha_publicacion', true);
        $fecha_vigencia = get_post_meta($norma_id, '_fecha_vigencia', true);
        $organo = get_post_meta($norma_id, '_organo_emisor', true);
        $boletin = get_post_meta($norma_id, '_boletin_oficial', true);
        $estado = get_post_meta($norma_id, '_estado_norma', true);
        $seccion = isset($norma_data['seccion']) ? $norma_data['seccion'] : '';
        $nota = isset($norma_data['nota']) ? $norma_data['nota'] : '';
        
        // DEBUG: Mostrar qué metadatos existen (TEMPORAL - ELIMINAR DESPUÉS)
        // error_log("Norma ID: $norma_id - Fecha pub: " . var_export($fecha_publicacion, true));
        // error_log("Norma ID: $norma_id - Fecha vig: " . var_export($fecha_vigencia, true));
        // error_log("Norma ID: $norma_id - Organo: " . var_export($organo, true));
        // error_log("Norma ID: $norma_id - Boletin: " . var_export($boletin, true));
        
        if ($seccion && $seccion !== $seccion_actual) {
            $seccion_actual = $seccion;
            echo '<h2 class="seccion-titulo">' . esc_html($seccion) . '</h2>';
        }
        
        // Agregar ID único para navegación desde el índice
        echo '<div class="norma" id="norma-' . esc_attr($norma_id) . '">';
        
        // Botón "Ir al inicio" solo para PDFs de códigos - Flecha compatible
        echo '<div class="ir-al-inicio-container">';
        echo '<a href="#inicio-pdf" class="ir-al-inicio-btn" title="' . __('Ir al inicio', 'ull-normativa') . '">^</a>';
        echo '</div>';
        
        echo '<h3 class="norma-titulo">' . esc_html($norma->post_title);
        
        if ($numero) {
            echo '<span class="norma-numero">' . esc_html($numero) . '</span>';
        }
        
        echo '</h3>';
        
        // Tabla de información normativa
        if ($fecha_aprobacion || $fecha_publicacion || $fecha_vigencia || $organo || $boletin || $estado) {
            echo '<div class="metadata">';
            echo '<h4 class="metadata-titulo">' . __('Información normativa', 'ull-normativa') . '</h4>';
            echo '<table class="metadata-table">';
            
            if ($fecha_aprobacion) {
                echo '<tr><td>' . __('Fecha de aprobación:', 'ull-normativa') . '</td>';
                echo '<td>' . esc_html(date_i18n('j \d\e F \d\e Y', strtotime($fecha_aprobacion))) . '</td></tr>';
            }
            
            if ($fecha_publicacion) {
                echo '<tr><td>' . __('Fecha de publicación:', 'ull-normativa') . '</td>';
                echo '<td>' . esc_html(date_i18n('j \d\e F \d\e Y', strtotime($fecha_publicacion))) . '</td></tr>';
            }
            
            if ($fecha_vigencia) {
                echo '<tr><td>' . __('En vigor desde:', 'ull-normativa') . '</td>';
                echo '<td>' . esc_html(date_i18n('j \d\e F \d\e Y', strtotime($fecha_vigencia))) . '</td></tr>';
            }
            
            if ($organo) {
                echo '<tr><td>' . __('Órgano emisor:', 'ull-normativa') . '</td>';
                echo '<td>' . esc_html($organo) . '</td></tr>';
            }
            
            if ($boletin) {
                echo '<tr><td>' . __('Boletín oficial:', 'ull-normativa') . '</td>';
                echo '<td>' . esc_html($boletin) . '</td></tr>';
            }
            
            if ($estado) {
                $estado_label = '';
                $estado_class = '';
                
                if ($estado === 'vigente') {
                    $estado_label = __('Vigente', 'ull-normativa');
                    $estado_class = 'estado-vigente';
                } elseif ($estado === 'derogada') {
                    $estado_label = __('Derogada', 'ull-normativa');
                    $estado_class = 'estado-derogada';
                } else {
                    $estado_label = ucfirst($estado);
                }
                
                echo '<tr><td>' . __('Estado:', 'ull-normativa') . '</td>';
                echo '<td><span class="' . esc_attr($estado_class) . '">' . esc_html($estado_label) . '</span></td></tr>';
            }
            
            echo '</table>';
            echo '</div>';
        }
        
        if ($nota) {
            echo '<div class="norma-nota">' . wpautop($nota) . '</div>';
        }
        
        // Tabla de contenidos de la norma
        $toc = $this->get_norma_toc($norma_id);
        if (!empty($toc)) {
            echo $toc;
        }
        
        // Relaciones normativas
        $relaciones = $this->get_relaciones_norma_html($norma_id);
        if (!empty($relaciones)) {
            echo $relaciones;
        }
        
        // Contenido
        $content = do_shortcode($norma->post_content);
        $content = $this->sanitize_html_for_pdf($content);
        
        echo '<div class="norma-contenido">' . $content . '</div></div>';
    }
    ?>
    <?php endif; ?>
    
    <?php echo $this->get_pdf_footer($settings); ?>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtener configuración de PDF
     */
    private function get_pdf_settings() {
        $options = get_option('ull_pdf_options', array());
        
        return array(
            'logo_url' => isset($options['header_logo']) && $options['header_logo'] ? wp_get_attachment_url($options['header_logo']) : '',
            'header_text' => isset($options['header_text']) ? $options['header_text'] : __('Universidad de La Laguna', 'ull-normativa'),
            'footer_text' => isset($options['footer_text']) ? $options['footer_text'] : __('Universidad de La Laguna - Normativa', 'ull-normativa'),
            'show_date' => isset($options['show_generation_date']) ? $options['show_generation_date'] : true,
            'show_page_numbers' => isset($options['show_page_numbers']) ? $options['show_page_numbers'] : true,
            'header_bg_color' => isset($options['header_bg_color']) ? $options['header_bg_color'] : '#003366',
            'header_text_color' => isset($options['header_text_color']) ? $options['header_text_color'] : '#ffffff',
            'title_color' => isset($options['title_color']) ? $options['title_color'] : '#003366',
            'index_bg_color' => isset($options['index_bg_color']) ? $options['index_bg_color'] : '#f9f9f9',
            'norma_title_color' => isset($options['norma_title_color']) ? $options['norma_title_color'] : '#003366',
            'font_size' => isset($options['pdf_font_size']) ? $options['pdf_font_size'] : '11',
            'font_family' => isset($options['pdf_font_family']) ? $options['pdf_font_family'] : 'DejaVu Sans',
            'margins' => isset($options['pdf_margins']) ? $options['pdf_margins'] : array('top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15),
        );
    }
    
    /**
     * Obtener estilos CSS para PDF
     */
    private function get_pdf_styles($settings) {
        $margins = $settings['margins'];
        
        ob_start();
        ?>
<style>
    @page {
        margin: <?php echo intval($margins['top']); ?>mm <?php echo intval($margins['right']); ?>mm <?php echo intval($margins['bottom']); ?>mm <?php echo intval($margins['left']); ?>mm;
    }
    
    body {
        font-family: '<?php echo esc_attr($settings['font_family']); ?>', Arial, sans-serif;
        font-size: <?php echo intval($settings['font_size']); ?>pt;
        line-height: 1.6;
        color: #333;
    }
    
    .pdf-header {
        background-color: <?php echo esc_attr($settings['header_bg_color']); ?>;
        color: <?php echo esc_attr($settings['header_text_color']); ?>;
        padding: 15px;
        margin-bottom: 20px;
        display: table;
        width: 100%;
    }
    
    .pdf-header .logo {
        display: table-cell;
        vertical-align: middle;
    }
    
    .pdf-header .logo img {
        height: auto;
        width: auto;
    }
    
    .pdf-header .header-text {
        display: table-cell;
        vertical-align: middle;
        padding-left: 15px;
        font-size: 14pt;
        font-weight: bold;
    }
    
    .document-title {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid <?php echo esc_attr($settings['title_color']); ?>;
        padding-bottom: 15px;
    }
    
    .document-title h1 {
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 20pt;
        margin: 0 0 10px 0;
    }
    
    .document-title .numero {
        font-size: 12pt;
        color: #666;
        margin: 0;
    }
    
    .metadata {
        background-color: #f5f5f5;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 4px solid <?php echo esc_attr($settings['title_color']); ?>;
    }
    
    .metadata h3 {
        margin-top: 0;
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 14pt;
    }
    
    .metadata h4 {
        margin-top: 0;
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 12pt;
        font-weight: bold;
    }
    
    .metadata-titulo {
        margin-bottom: 10px;
    }
    
    .metadata-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .metadata-table td {
        padding: 5px 10px;
        border-bottom: 1px solid #ddd;
    }
    
    .metadata-table td:first-child {
        font-weight: bold;
        width: 40%;
    }
    
    .estado-vigente {
        color: #155724;
        font-weight: normal;
        display: inline;
    }
    
    .estado-derogada {
        color: #721c24;
        font-weight: normal;
        display: inline;
    }
    
    .table-of-contents {
        background-color: <?php echo esc_attr($settings['index_bg_color']); ?>;
        padding: 15px;
        margin: 20px 0;
        border: 1px solid #ddd;
    }
    
    .table-of-contents h3 {
        margin-top: 0;
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 14pt;
    }
    
    .table-of-contents ol,
    .table-of-contents ul {
        margin: 10px 0;
        padding-left: 25px;
        font-size: 10pt;
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .table-of-contents ol li,
    .table-of-contents ul li {
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .table-of-contents li {
        margin-bottom: 5px;
        font-size: 10pt;
        line-height: 1.3;
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .table-of-contents li::before {
        content: none !important;
    }
    
    .table-of-contents a {
        color: #333;
        text-decoration: none;
        font-size: 10pt;
    }
    
    .content {
        margin: 20px 0;
    }
    
    .content h2 {
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 16pt;
        margin-top: 25px;
        margin-bottom: 15px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 5px;
    }
    
    .content h3 {
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 14pt;
        margin-top: 20px;
        margin-bottom: 10px;
    }
    
    .content h4 {
        color: #666;
        font-size: 12pt;
        margin-top: 15px;
        margin-bottom: 8px;
    }
    
    .content p {
        margin: 10px 0;
        text-align: justify;
    }
    
    .content ul, .content ol {
        margin: 10px 0;
        padding-left: 30px;
    }
    
    .content li {
        margin-bottom: 5px;
    }
    
    .content table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    
    .content table th,
    .content table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    .content table th {
        background-color: #f0f0f0;
        font-weight: bold;
    }
    
    .norma-relaciones-pdf {
        background-color: #fff8dc;
        padding: 15px;
        margin: 20px 0;
        border-left: 4px solid #ffa500;
    }
    
    .relaciones-titulo-pdf {
        margin-top: 0;
        color: #ff8c00;
        font-size: 14pt;
    }
    
    .relacion-grupo-pdf {
        margin-bottom: 15px;
    }
    
    .relacion-tipo-pdf {
        color: #ff8c00;
        font-size: 11pt;
    }
    
    .relacion-lista-pdf {
        margin: 5px 0;
        padding-left: 20px;
    }
    
    .relacion-numero-pdf {
        color: #666;
        font-style: italic;
    }
    
    .relacion-nota-pdf {
        color: #666;
        font-size: 10pt;
    }
    
    .indice {
        background-color: <?php echo esc_attr($settings['index_bg_color']); ?>;
        padding: 15px;
        margin: 20px 0;
        border: 1px solid #ddd;
    }
    
    .indice h3 {
        margin-top: 0;
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 14pt;
    }
    
    .indice ol,
    .indice ul {
        margin: 10px 0;
        padding-left: 25px;
        font-size: 10pt;
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .indice ol li,
    .indice ul li {
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .indice li {
        margin-bottom: 5px;
        font-size: 10pt;
        line-height: 1.3;
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .indice li::before {
        content: none !important;
    }
    
    .indice a {
        color: #333;
        text-decoration: none;
        font-size: 10pt;
    }
    
    /* Estilos para tabla de contenidos del shortcode */
    .ull-tabla-contenidos {
        background-color: #f0f8ff;
        padding: 15px;
        margin: 20px 0;
        border: 1px solid #b0d4ff;
        page-break-inside: avoid;
    }
    
    .ull-toc-header {
        margin-bottom: 10px;
    }
    
    .ull-toc-titulo {
        margin: 0;
        padding: 0;
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 14pt;
        font-weight: bold;
    }
    
    .ull-toc-lista {
        margin: 10px 0;
        padding: 0;
        font-size: 10pt;
    }
    
    .ull-toc-lista ol,
    .ull-toc-lista ul {
        margin: 5px 0;
        padding-left: 25px;
        font-size: 10pt;
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .ull-toc-lista ol li,
    .ull-toc-lista ul li {
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .ull-toc-lista li {
        margin: 3px 0;
        line-height: 1.3;
        font-size: 10pt;
        list-style: none !important;
        list-style-type: none !important;
    }
    
    .ull-toc-lista li::before {
        content: none !important;
    }
    
    .ull-toc-lista a {
        color: #333;
        text-decoration: none;
        font-size: 10pt;
    }
    
    /* Ocultar elementos interactivos que no se deberían mostrar en PDF */
    .ull-toc-toggle,
    .ull-toc-toggle-icon,
    .ull-toc-toggle-text,
    .ull-toc-counter {
        display: none !important;
    }
    
    .seccion-titulo {
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 18pt;
        margin-top: 30px;
        margin-bottom: 20px;
        border-top: 3px solid <?php echo esc_attr($settings['title_color']); ?>;
        padding-top: 15px;
    }
    
    .norma {
        margin-bottom: 30px;
        page-break-inside: avoid;
    }
    
    .ir-al-inicio-container {
        text-align: right;
        margin-bottom: 10px;
    }
    
    .ir-al-inicio-btn {
        display: inline-block;
        background-color: <?php echo esc_attr($settings['header_bg_color']); ?>;
        color: <?php echo esc_attr($settings['header_text_color']); ?>;
        padding: 6px 10px;
        text-decoration: none;
        font-size: 18pt;
        border-radius: 3px;
        font-weight: bold;
        line-height: 0.8;
    }
    
    .ir-al-inicio-btn:hover {
        opacity: 0.8;
    }
    
    .norma-titulo {
        color: <?php echo esc_attr($settings['norma_title_color']); ?>;
        font-size: 16pt;
        margin-bottom: 10px;
    }
    
    .norma-numero {
        color: #666;
        font-size: 12pt;
        font-weight: normal;
        margin-left: 10px;
    }
    
    .norma-fecha {
        color: #666;
        font-size: 10pt;
        margin: 5px 0;
    }
    
    .norma-nota {
        background-color: #fffacd;
        padding: 10px;
        margin: 10px 0;
        border-left: 3px solid #ffd700;
        font-style: italic;
    }
    
    .toc-normativa {
        background-color: <?php echo esc_attr($settings['index_bg_color']); ?>;
        padding: 15px;
        margin: 20px 0;
        border: 1px solid #ddd;
    }
    
    .toc-titulo {
        margin-top: 0;
        color: <?php echo esc_attr($settings['title_color']); ?>;
        font-size: 14pt;
    }
    
    .toc-lista {
        list-style: none;
        padding: 0;
        padding-left: 25px;
        margin: 10px 0;
        font-size: 10pt;
    }
    
    .toc-lista li {
        margin-bottom: 5px;
        padding-left: 0;
        font-size: 10pt;
        line-height: 1.3;
    }
    
    .toc-lista a {
        color: #333;
        text-decoration: none;
        font-size: 10pt;
    }
    
    .toc-level-3 { padding-left: 15px; }
    .toc-level-4 { padding-left: 30px; }
    .toc-level-5 { padding-left: 45px; }
    .toc-level-6 { padding-left: 60px; }
    
    .toc-numero {
        font-weight: bold;
        color: #333;
        font-size: 10pt;
    }
    
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 9pt;
        color: #666;
        padding: 10px;
        border-top: 1px solid #ddd;
    }
    
    .page-number:after {
        content: counter(page);
    }
</style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtener cabecera del PDF
     */
    private function get_pdf_header($settings, $post = null) {
        ob_start();
        ?>
<div class="pdf-header">
    <?php if (!empty($settings['logo_url'])): ?>
    <div class="logo">
        <img src="<?php echo esc_url($settings['logo_url']); ?>" alt="Logo">
    </div>
    <?php endif; ?>
    <div class="header-text">
        <?php echo esc_html($settings['header_text']); ?>
    </div>
</div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtener pie del PDF
     */
    private function get_pdf_footer($settings) {
        ob_start();
        ?>
<div class="footer">
    <?php echo esc_html($settings['footer_text']); ?>
    <?php if ($settings['show_page_numbers']): ?>
        <span style="margin: 0 10px;">|</span>
        <span><?php _e('Página', 'ull-normativa'); ?> <span class="page-number"></span></span>
    <?php endif; ?>
    <?php if ($settings['show_date']): ?>
        <br><?php printf(__('Generado el %s', 'ull-normativa'), date_i18n('j \d\e F \d\e Y \a \l\a\s H:i')); ?>
    <?php endif; ?>
</div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Sanitizar HTML para PDF
     */
    private function sanitize_html_for_pdf($content) {
        if (empty($content)) {
            return '';
        }
        
        // Decodificar entidades HTML
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // NOTA: NO eliminamos shortcodes aquí porque ya fueron procesados con do_shortcode()
        // antes de llamar a esta función
        
        // Eliminar elementos interactivos del índice de contenidos (botones, SVG, etc.)
        // Eliminar botón de toggle completo con sus contenidos
        $content = preg_replace('/<button[^>]*class="[^"]*ull-toc-toggle[^"]*"[^>]*>.*?<\/button>/is', '', $content);
        
        // Eliminar atributos onclick y otros eventos
        $content = preg_replace('/\s+on\w+="[^"]*"/i', '', $content);
        $content = preg_replace("/\s+on\w+='[^']*'/i", '', $content);
        
        // Aplicar formato de párrafos
        $content = wpautop($content);
        
        // Eliminar scripts
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        
        // Eliminar estilos globales pero mantener inline
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
        
        // Normalizar atributos bgcolor a style
        $content = preg_replace_callback(
            '/(<(?:td|th)[^>]*)\s+bgcolor=(["\'])([^"\']+)\2([^>]*>)/i',
            function($matches) {
                $tag_start = $matches[1];
                $color = $matches[3];
                $tag_end = $matches[4];
                
                if (preg_match('/style=(["\'])([^"\']*)\1/', $tag_start, $style_match)) {
                    $existing_style = $style_match[2];
                    if (strpos($existing_style, 'background') === false) {
                        $new_style = $existing_style . '; background-color: ' . $color;
                        $tag_start = str_replace($style_match[0], 'style="' . $new_style . '"', $tag_start);
                    }
                } else {
                    $tag_start .= ' style="background-color: ' . $color . '"';
                }
                
                return $tag_start . $tag_end;
            },
            $content
        );
        
        // Normalizar atributos width
        $content = preg_replace_callback(
            '/(<(?:table|td|th)[^>]*)\s+width=(["\'])([^"\']+)\2([^>]*>)/i',
            function($matches) {
                $tag_start = $matches[1];
                $width = $matches[3];
                $tag_end = $matches[4];
                
                if (is_numeric($width)) {
                    $width .= 'px';
                }
                
                if (preg_match('/style=(["\'])([^"\']*)\1/', $tag_start, $style_match)) {
                    $existing_style = $style_match[2];
                    if (strpos($existing_style, 'width') === false) {
                        $new_style = $existing_style . '; width: ' . $width;
                        $tag_start = str_replace($style_match[0], 'style="' . $new_style . '"', $tag_start);
                    }
                } else {
                    $tag_start .= ' style="width: ' . $width . '"';
                }
                
                return $tag_start . $tag_end;
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Añadir IDs a los encabezados para enlaces de TOC
     */
    private function add_heading_ids_for_pdf($content) {
        $counter = 0;
        $pattern = '/<h([1-6])([^>]*)>(.*?)<\/h\1>/i';
        
        $content = preg_replace_callback($pattern, function($matches) use (&$counter) {
            $level = $matches[1];
            $attributes = $matches[2];
            $text = $matches[3];
            
            // Si ya tiene ID, no sobrescribir
            if (preg_match('/id=["\']([^"\']+)["\']/i', $attributes)) {
                return $matches[0];
            }
            
            $counter++;
            $text_plain = strip_tags($text);
            $text_plain = html_entity_decode($text_plain, ENT_QUOTES, 'UTF-8');
            $id = $this->generate_heading_id($text_plain, $counter);
            
            return '<h' . $level . ' id="' . esc_attr($id) . '"' . $attributes . '>' . $text . '</h' . $level . '>';
        }, $content);
        
        return $content;
    }
    
    /**
     * Generar ID único para un encabezado
     */
    private function generate_heading_id($text, $counter) {
        $slug = sanitize_title($text);
        
        if (empty($slug)) {
            $slug = 'seccion';
        }
        
        return 'toc-' . $slug . '-' . $counter;
    }
    
    /**
     * Generar tabla de contenidos desde el contenido HTML
     */
    private function generate_toc_from_content($content) {
        // Eliminar shortcodes de TOC
        $content = preg_replace('/\[\s*ull_tabla_contenidos[^\]]*\]/i', '', $content);
        $content = preg_replace('/\[\s*tabla_contenidos[^\]]*\]/i', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Buscar encabezados H2 a H6
        preg_match_all('/<h([2-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);
        
        if (empty($matches)) {
            return '';
        }
        
        $toc_html = '<div class="toc-normativa">';
        $toc_html .= '<h3>' . __('Índice de contenidos', 'ull-normativa') . '</h3>';
        $toc_html .= '<ul class="toc-lista">';
        $current_level = 2;
        $counter = array();
        
        foreach ($matches as $match) {
            $level = intval($match[1]);
            $title = strip_tags($match[2]);
            $title = trim($title);
            
            if (empty($title)) {
                continue;
            }
            
            if (!isset($counter[$level])) {
                $counter[$level] = 0;
            }
            
            if ($level < $current_level) {
                for ($i = $level + 1; $i <= 6; $i++) {
                    $counter[$i] = 0;
                }
            }
            
            $counter[$level]++;
            $current_level = $level;
            
            $indent_class = 'toc-level-' . $level;
            
            $numero = '';
            for ($i = 2; $i <= $level; $i++) {
                if (isset($counter[$i]) && $counter[$i] > 0) {
                    $numero .= $counter[$i] . '.';
                }
            }
            
            $toc_html .= '<li class="' . $indent_class . '">';
            $toc_html .= '<span class="toc-numero">' . rtrim($numero, '.') . '</span> ';
            $toc_html .= esc_html($title);
            $toc_html .= '</li>';
        }
        
        $toc_html .= '</ul>';
        $toc_html .= '</div>';
        
        return $toc_html;
    }
    
    /**
     * Obtener TOC de una norma guardada en meta
     */
    private function get_norma_toc($norma_id) {
        if (get_post_type($norma_id) !== 'norma') {
            return '';
        }
        
        $toc_guardada = get_post_meta($norma_id, '_ull_normativa_toc', true);
        
        if (!empty($toc_guardada)) {
            return $toc_guardada;
        }
        
        // Generar desde contenido
        $content = get_post_field('post_content', $norma_id);
        
        if (empty($content)) {
            return '';
        }
        
        return $this->generate_toc_from_content($content);
    }
    
    /**
     * Obtener las relaciones de una norma formateadas para el PDF
     */
    private function get_relaciones_norma_html($norma_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ull_norma_relations';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        if (!$table_exists) {
            return '';
        }
        
        $relations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE norma_id = %d ORDER BY relation_type, created_at DESC",
            $norma_id
        ));
        
        if (empty($relations)) {
            return '';
        }
        
        $relation_types = array(
            'deroga' => __('Deroga a', 'ull-normativa'),
            'derogada_por' => __('Derogada por', 'ull-normativa'),
            'modifica' => __('Modifica a', 'ull-normativa'),
            'modificada_por' => __('Modificada por', 'ull-normativa'),
            'desarrolla' => __('Desarrolla a', 'ull-normativa'),
            'desarrollada_por' => __('Desarrollada por', 'ull-normativa'),
            'complementa' => __('Complementa a', 'ull-normativa'),
            'complementada_por' => __('Complementada por', 'ull-normativa'),
            'cita' => __('Cita a', 'ull-normativa'),
            'citada_por' => __('Citada por', 'ull-normativa'),
            'relacionada' => __('Relacionada con', 'ull-normativa'),
        );
        
        $grouped = array();
        foreach ($relations as $relation) {
            $type = $relation->relation_type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = array();
            }
            
            $related_post = get_post($relation->related_norma_id);
            if ($related_post) {
                $grouped[$type][] = array(
                    'title' => $related_post->post_title,
                    'numero' => get_post_meta($relation->related_norma_id, '_numero_norma', true),
                    'notes' => $relation->notes,
                );
            }
        }
        
        ob_start();
        ?>
<div class="norma-relaciones-pdf">
    <h3 class="relaciones-titulo-pdf"><?php _e('Relaciones normativas', 'ull-normativa'); ?></h3>
    <div class="relaciones-contenido-pdf">
        <?php foreach ($grouped as $type => $items): ?>
            <div class="relacion-grupo-pdf">
                <strong class="relacion-tipo-pdf"><?php echo esc_html(isset($relation_types[$type]) ? $relation_types[$type] : $type); ?>:</strong>
                <ul class="relacion-lista-pdf">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <?php echo esc_html($item['title']); ?>
                            <?php if (!empty($item['numero'])): ?>
                                <span class="relacion-numero-pdf">(<?php echo esc_html($item['numero']); ?>)</span>
                            <?php endif; ?>
                            <?php if (!empty($item['notes'])): ?>
                                <br><span class="relacion-nota-pdf"><?php echo esc_html($item['notes']); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generar XML de la norma (solo para normas)
     */
    private function generate_xml($post) {
        $numero = get_post_meta($post->ID, '_numero_norma', true);
        $filename = sanitize_file_name(($numero ? $numero : 'norma-' . $post->ID) . '.xml');
        
        $meta = array(
            'numero' => $numero,
            'fecha_aprobacion' => get_post_meta($post->ID, '_fecha_aprobacion', true),
            'fecha_publicacion' => get_post_meta($post->ID, '_fecha_publicacion', true),
            'fecha_vigencia' => get_post_meta($post->ID, '_fecha_vigencia', true),
            'estado' => get_post_meta($post->ID, '_estado_norma', true),
            'organo_emisor' => get_post_meta($post->ID, '_organo_emisor', true),
            'boletin_oficial' => get_post_meta($post->ID, '_boletin_oficial', true),
            'url_boletin' => get_post_meta($post->ID, '_url_boletin', true),
            'ambito_aplicacion' => get_post_meta($post->ID, '_ambito_aplicacion', true),
        );
        
        $tipo_terms = get_the_terms($post->ID, 'tipo_norma');
        $categoria_terms = get_the_terms($post->ID, 'categoria_norma');
        $materia_terms = get_the_terms($post->ID, 'materia_norma');
        $organo_terms = get_the_terms($post->ID, 'organo_norma');
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $norma = $xml->createElement('norma');
        $norma->setAttribute('id', $post->ID);
        $norma->setAttribute('fecha_exportacion', date('Y-m-d\TH:i:s'));
        $xml->appendChild($norma);
        
        $info = $xml->createElement('informacion');
        $norma->appendChild($info);
        
        $this->add_xml_element($xml, $info, 'titulo', $post->post_title);
        $this->add_xml_element($xml, $info, 'numero', $meta['numero']);
        $this->add_xml_element($xml, $info, 'estado', $meta['estado']);
        $this->add_xml_element($xml, $info, 'resumen', $post->post_excerpt);
        
        $fechas = $xml->createElement('fechas');
        $norma->appendChild($fechas);
        
        $this->add_xml_element($xml, $fechas, 'aprobacion', $meta['fecha_aprobacion']);
        $this->add_xml_element($xml, $fechas, 'publicacion', $meta['fecha_publicacion']);
        $this->add_xml_element($xml, $fechas, 'vigencia', $meta['fecha_vigencia']);
        $this->add_xml_element($xml, $fechas, 'creacion', $post->post_date);
        $this->add_xml_element($xml, $fechas, 'modificacion', $post->post_modified);
        
        $clasificacion = $xml->createElement('clasificacion');
        $norma->appendChild($clasificacion);
        
        if ($tipo_terms && !is_wp_error($tipo_terms)) {
            $tipos = $xml->createElement('tipos');
            $clasificacion->appendChild($tipos);
            foreach ($tipo_terms as $term) {
                $this->add_xml_element($xml, $tipos, 'tipo', $term->name, array('slug' => $term->slug));
            }
        }
        
        if ($categoria_terms && !is_wp_error($categoria_terms)) {
            $categorias = $xml->createElement('categorias');
            $clasificacion->appendChild($categorias);
            foreach ($categoria_terms as $term) {
                $this->add_xml_element($xml, $categorias, 'categoria', $term->name, array('slug' => $term->slug));
            }
        }
        
        if ($materia_terms && !is_wp_error($materia_terms)) {
            $materias = $xml->createElement('materias');
            $clasificacion->appendChild($materias);
            foreach ($materia_terms as $term) {
                $this->add_xml_element($xml, $materias, 'materia', $term->name, array('slug' => $term->slug));
            }
        }
        
        if ($organo_terms && !is_wp_error($organo_terms)) {
            $organos = $xml->createElement('organos');
            $clasificacion->appendChild($organos);
            foreach ($organo_terms as $term) {
                $this->add_xml_element($xml, $organos, 'organo', $term->name, array('slug' => $term->slug));
            }
        }
        
        $publicacion = $xml->createElement('publicacion_oficial');
        $norma->appendChild($publicacion);
        
        $this->add_xml_element($xml, $publicacion, 'organo_emisor', $meta['organo_emisor']);
        $this->add_xml_element($xml, $publicacion, 'boletin', $meta['boletin_oficial']);
        $this->add_xml_element($xml, $publicacion, 'url_boletin', $meta['url_boletin']);
        $this->add_xml_element($xml, $publicacion, 'ambito', $meta['ambito_aplicacion']);
        
        $contenido = $xml->createElement('contenido');
        $norma->appendChild($contenido);
        
        $cdata = $xml->createCDATASection($post->post_content);
        $contenido->appendChild($cdata);
        
        $this->add_xml_element($xml, $norma, 'url', get_permalink($post->ID));
        
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $xml->saveXML();
        exit;
    }
    
    /**
     * Añadir elemento XML
     */
    private function add_xml_element($xml, $parent, $name, $value, $attributes = array()) {
        if (empty($value) && $value !== '0') {
            return;
        }
        
        $element = $xml->createElement($name);
        $text = $xml->createTextNode($value);
        $element->appendChild($text);
        
        foreach ($attributes as $attr_name => $attr_value) {
            $element->setAttribute($attr_name, $attr_value);
        }
        
        $parent->appendChild($element);
    }
    
    /**
     * Renderizar página HTML para impresión (fallback)
     */
    private function render_html_page($html) {
        $html = str_replace('</body>', '
    <div style="position: fixed; top: 20px; right: 20px; z-index: 1000;" class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; background: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            🖨️ Imprimir / Guardar PDF
        </button>
    </div>
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</body>', $html);
        
        echo $html;
        exit;
    }
    
    /**
     * Obtener URL de exportación PDF
     */
    public static function get_pdf_url($post_id) {
        return add_query_arg('ull_export_pdf', $post_id, home_url('/'));
    }
    
    /**
     * Obtener URL de exportación XML
     */
    public static function get_xml_url($post_id) {
        return add_query_arg('ull_export_xml', $post_id, home_url('/'));
    }
}
