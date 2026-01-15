<?php
/**
 * Exportador de normas a PDF y XML
 * Usa Composer autoload para DOMPDF/mPDF, sino genera HTML para impresión
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_PDF_Export {
    
    private $pdf_library = null;
    private $dompdf_installer = null;
    
    public function __construct() {
        add_action('init', array($this, 'handle_pdf_export'));
        add_action('init', array($this, 'handle_xml_export'));
        $this->detect_pdf_library();
    }
    
    /**
     * Detectar si hay librería PDF disponible
     */
    private function detect_pdf_library() {
        // Primero verificar si existe Composer (para compatibilidad con versiones anteriores)
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
            // Usar Composer si está disponible
            require_once ULL_NORMATIVA_PLUGIN_DIR . 'vendor/autoload.php';
            return new \Dompdf\Dompdf();
        } elseif ($this->pdf_library === 'dompdf') {
            // Usar DOMPDF instalado
            if (!$this->dompdf_installer) {
                require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-dompdf-installer.php';
                $this->dompdf_installer = new ULL_DOMPDF_Installer();
            }
            
            // CARGA FORZADA PARA MULTISITE
            $autoload = $this->dompdf_installer->get_autoload_path();
            if ($autoload && file_exists($autoload)) {
                require_once $autoload;
            }

            return $this->dompdf_installer->get_dompdf_instance();
        }
        
        return false;
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
        
        if (!$post || $post->post_type !== 'norma' || $post->post_status !== 'publish') {
            wp_die(__('Norma no encontrada.', 'ull-normativa'));
        }
        
        $this->generate_xml($post);
    }
    
    /**
     * Generar XML de la norma
     */
    private function generate_xml($post) {
        $numero = get_post_meta($post->ID, '_numero_norma', true);
        $filename = sanitize_file_name(($numero ? $numero : 'norma-' . $post->ID) . '.xml');
        
        // Obtener todos los metadatos
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
        
        // Obtener taxonomías
        $tipo_terms = get_the_terms($post->ID, 'tipo_norma');
        $categoria_terms = get_the_terms($post->ID, 'categoria_norma');
        $materia_terms = get_the_terms($post->ID, 'materia_norma');
        $organo_terms = get_the_terms($post->ID, 'organo_norma');
        
        // Crear XML
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Elemento raíz
        $norma = $xml->createElement('norma');
        $norma->setAttribute('id', $post->ID);
        $norma->setAttribute('fecha_exportacion', date('Y-m-d\TH:i:s'));
        $xml->appendChild($norma);
        
        // Información básica
        $info = $xml->createElement('informacion');
        $norma->appendChild($info);
        
        $this->add_xml_element($xml, $info, 'titulo', $post->post_title);
        $this->add_xml_element($xml, $info, 'numero', $meta['numero']);
        $this->add_xml_element($xml, $info, 'estado', $meta['estado']);
        $this->add_xml_element($xml, $info, 'resumen', $post->post_excerpt);
        
        // Fechas
        $fechas = $xml->createElement('fechas');
        $norma->appendChild($fechas);
        
        $this->add_xml_element($xml, $fechas, 'aprobacion', $meta['fecha_aprobacion']);
        $this->add_xml_element($xml, $fechas, 'publicacion', $meta['fecha_publicacion']);
        $this->add_xml_element($xml, $fechas, 'vigencia', $meta['fecha_vigencia']);
        $this->add_xml_element($xml, $fechas, 'creacion', $post->post_date);
        $this->add_xml_element($xml, $fechas, 'modificacion', $post->post_modified);
        
        // Clasificación
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
        
        // Publicación oficial
        $publicacion = $xml->createElement('publicacion_oficial');
        $norma->appendChild($publicacion);
        
        $this->add_xml_element($xml, $publicacion, 'organo_emisor', $meta['organo_emisor']);
        $this->add_xml_element($xml, $publicacion, 'boletin', $meta['boletin_oficial']);
        $this->add_xml_element($xml, $publicacion, 'url_boletin', $meta['url_boletin']);
        $this->add_xml_element($xml, $publicacion, 'ambito', $meta['ambito_aplicacion']);
        
        // Contenido
        $contenido = $xml->createElement('contenido');
        $norma->appendChild($contenido);
        
        // El contenido HTML va en CDATA
        $cdata = $xml->createCDATASection($post->post_content);
        $contenido->appendChild($cdata);
        
        // URL
        $this->add_xml_element($xml, $norma, 'url', get_permalink($post->ID));
        
        // Headers
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        echo $xml->saveXML();
        exit;
    }
    
    /**
     * Añadir elemento XML con texto
     */
    private function add_xml_element($xml, $parent, $name, $value, $attributes = array()) {
        $element = $xml->createElement($name);
        if (!empty($value)) {
            $element->appendChild($xml->createTextNode($value));
        }
        foreach ($attributes as $attr_name => $attr_value) {
            $element->setAttribute($attr_name, $attr_value);
        }
        $parent->appendChild($element);
        return $element;
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
        
        if (!$post || $post->post_type !== 'norma' || $post->post_status !== 'publish') {
            wp_die(__('Norma no encontrada.', 'ull-normativa'));
        }
        
        // DEBUG: Mostrar información de detección si hay parámetro debug
        $debug = isset($_GET['debug']);
        
        if ($debug) {
            echo '<pre style="background: #f5f5f5; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
            echo '<strong>DEBUG: Información de exportación PDF</strong><br><br>';
            echo 'PDF Library detectada: ' . ($this->pdf_library ? $this->pdf_library : 'NULL') . '<br>';
            echo 'has_pdf_library(): ' . ($this->has_pdf_library() ? 'TRUE' : 'FALSE') . '<br><br>';
            
            if ($this->pdf_library === 'dompdf') {
                echo 'Intentando obtener instancia de DOMPDF...<br>';
                try {
                    $test_dompdf = $this->get_dompdf_instance();
                    echo 'get_dompdf_instance() devolvió: ' . (is_object($test_dompdf) ? get_class($test_dompdf) : gettype($test_dompdf)) . '<br>';
                } catch (Throwable $e) {
                    echo 'ERROR en get_dompdf_instance(): ' . $e->getMessage() . '<br>';
                    echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . '<br>';
                }
            }
            
            echo '</pre>';
        }
        
        if ($this->has_pdf_library()) {
            if ($debug) {
                echo '<pre>Llamando a generate_real_pdf()...</pre>';
            }
            $this->generate_real_pdf($post);
        } else {
            if ($debug) {
                echo '<pre>has_pdf_library() es FALSE, mostrando HTML...</pre>';
            }
            $this->render_html_page($post);
        }
        exit;
    }
    
    /**
     * Generar PDF real con DOMPDF o mPDF
     */
    private function generate_real_pdf($post) {
        $debug = isset($_GET['debug']);
        
        if ($debug) echo '<pre>Dentro de generate_real_pdf()...</pre>';
        
        $html = $this->get_pdf_html($post);
        $filename = $this->get_pdf_filename($post);
        
        if ($debug) echo '<pre>HTML generado, filename: ' . $filename . '</pre>';
        
        try {
            if ($debug) echo '<pre>Obteniendo instancia de DOMPDF...</pre>';
            
            $dompdf = $this->get_dompdf_instance();
            
            if ($debug) {
                echo '<pre>Instancia obtenida: ' . (is_object($dompdf) ? get_class($dompdf) : gettype($dompdf)) . '</pre>';
            }
            
            if ($dompdf) {
                if ($debug) echo '<pre>Llamando a generate_with_dompdf_instance()...</pre>';
                $this->generate_with_dompdf_instance($dompdf, $html, $filename);
            } else {
                if ($debug) echo '<pre>ERROR: $dompdf es FALSE, mostrando HTML...</pre>';
                $this->render_html_page($post);
            }
        } catch (Exception $e) {
            // Log detallado del error
            error_log('ULL Normativa PDF Error: ' . $e->getMessage());
            error_log('ULL Normativa PDF Error Trace: ' . $e->getTraceAsString());
            
            if ($debug) {
                echo '<pre style="background: #f8d7da; padding: 15px; border: 2px solid #dc3545;">';
                echo '<strong>EXCEPTION CAPTURADA:</strong><br>';
                echo 'Mensaje: ' . $e->getMessage() . '<br>';
                echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . '<br>';
                echo 'Trace:<br>' . $e->getTraceAsString();
                echo '</pre>';
            }
            
            // Mostrar página HTML como fallback
            $this->render_html_page($post);
        } catch (Error $e) {
            // Capturar errores fatales de PHP 7+
            error_log('ULL Normativa PDF Fatal Error: ' . $e->getMessage());
            error_log('ULL Normativa PDF Error Trace: ' . $e->getTraceAsString());
            
            if ($debug) {
                echo '<pre style="background: #f8d7da; padding: 15px; border: 2px solid #dc3545;">';
                echo '<strong>ERROR FATAL CAPTURADO:</strong><br>';
                echo 'Mensaje: ' . $e->getMessage() . '<br>';
                echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . '<br>';
                echo 'Trace:<br>' . $e->getTraceAsString();
                echo '</pre>';
            }
            
            // Mostrar página HTML como fallback
            $this->render_html_page($post);
        }
    }
    
    /**
     * Generar PDF con instancia de DOMPDF
     */
    private function generate_with_dompdf_instance($dompdf, $html, $filename) {
        $debug = isset($_GET['debug']);
        
        if ($debug) echo '<pre>Dentro de generate_with_dompdf_instance()...</pre>';
        
        // Verificar que la instancia es válida
        if (!$dompdf || !($dompdf instanceof \Dompdf\Dompdf)) {
            throw new Exception('Instancia de DOMPDF no válida');
        }
        
        if ($debug) echo '<pre>Instancia de DOMPDF válida</pre>';
        
        // Obtener configuración personalizada
        $pdf_options = get_option('ull_pdf_options', array());
        
        // Verificar que la clase Options existe
        if (!class_exists('Dompdf\Options')) {
            throw new Exception('Clase Dompdf\Options no encontrada. Reinstale DOMPDF.');
        }
        
        if ($debug) echo '<pre>Clase Dompdf\Options existe</pre>';
        
        try {
            if ($debug) echo '<pre>Configurando opciones de DOMPDF...</pre>';
            
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('isPhpEnabled', false);
            
            $upload_dir = wp_upload_dir();
            $cache_dir = $upload_dir['basedir'] . '/ull-normativa/dompdf-cache';
            if (!file_exists($cache_dir)) {
                wp_mkdir_p($cache_dir);
            }
            $options->set('fontCache', $cache_dir);
            
            // IMPORTANTE: Configurar base URI como vacío para que los enlaces #id funcionen
            $options->set('chroot', '');
            
            $dompdf->setOptions($options);
            
            if ($debug) echo '<pre>Opciones configuradas correctamente</pre>';
            
        } catch (Exception $e) {
            error_log('Error configurando opciones de DOMPDF: ' . $e->getMessage());
            if ($debug) {
                echo '<pre style="background: #fff3cd; padding: 10px; border: 2px solid #ffc107;">';
                echo 'ADVERTENCIA: Error configurando opciones: ' . $e->getMessage();
                echo '</pre>';
            }
            // Continuar sin opciones personalizadas
        }
        
        // Cargar HTML
        if ($debug) echo '<pre>Cargando HTML (' . strlen($html) . ' caracteres)...</pre>';
        
        $dompdf->loadHtml($html, 'UTF-8');
        
        if ($debug) echo '<pre>HTML cargado correctamente</pre>';
        
        // Configurar papel y orientación desde opciones
        $paper_size = isset($pdf_options['paper_size']) ? $pdf_options['paper_size'] : 'A4';
        $orientation = isset($pdf_options['orientation']) ? $pdf_options['orientation'] : 'portrait';
        
        if ($debug) echo '<pre>Configurando papel: ' . $paper_size . ' ' . $orientation . '</pre>';
        
        $dompdf->setPaper($paper_size, $orientation);
        
        if ($debug) echo '<pre>Renderizando PDF...</pre>';
        
        // Renderizar
        $dompdf->render();
        
        if ($debug) echo '<pre>PDF renderizado correctamente</pre>';
        
        // Añadir números de página si está habilitado
        if (isset($pdf_options['show_page_numbers']) && $pdf_options['show_page_numbers']) {
            try {
                $this->add_page_numbers($dompdf);
                if ($debug) echo '<pre>Números de página añadidos</pre>';
            } catch (Exception $e) {
                error_log('Error agregando números de página: ' . $e->getMessage());
                if ($debug) echo '<pre>Error agregando números de página (continuando sin ellos)</pre>';
            } catch (Error $e) {
                error_log('Error fatal agregando números de página: ' . $e->getMessage());
                if ($debug) echo '<pre>Error fatal agregando números de página (continuando sin ellos)</pre>';
            }
        }
        
        if ($debug) {
            echo '<pre>Enviando PDF al navegador...</pre>';
            echo '<pre style="background: #d4edda; padding: 15px; border: 2px solid #28a745;">';
            echo '<strong>✓ TODO OK - Llamando a $dompdf->stream()...</strong>';
            echo '</pre>';
        }
        
        // Enviar al navegador
        $dompdf->stream($filename, array('Attachment' => false));
    }
    
    /**
     * Añadir números de página al PDF
     */
    private function add_page_numbers($dompdf) {
        $canvas = $dompdf->getCanvas();
        $font = 'DejaVu Sans';
        $size = 10;
        $color = array(0, 0, 0);
        
        // En lugar de un closure, pasamos el script como string
        $canvas->page_script('
            $text = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
            $font = "' . $font . '";
            $size = ' . $size . ';
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $text_width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $width - $text_width - 30;
            $y = $height - 20;
            $pdf->text($x, $y, $text, $font, $size, array(0,0,0));
        ');
    }
    
    /**
     * Generar PDF con mPDF
     */
    private function generate_with_mpdf($html, $filename) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ull-normativa/mpdf-temp';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'default_font' => 'dejavusans',
            'tempDir' => $temp_dir
        ]);
        
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    }
    
    /**
     * Obtener nombre de archivo para el PDF
     */
    private function get_pdf_filename($post) {
        $numero = get_post_meta($post->ID, '_numero_norma', true);
        if ($numero) {
            return sanitize_file_name($numero) . '.pdf';
        }
        return sanitize_file_name($post->post_title) . '.pdf';
    }
    
    /**
     * Generar HTML para el PDF
     */
    private function get_pdf_html($norma_post) {
        // Obtener configuraciones personalizadas
        $pdf_options = get_option('ull_pdf_options', array());
        $old_options = get_option('ull_normativa_pdf_settings', array());
        
        // Valores por defecto
        $defaults = array(
            'header_text' => get_bloginfo('name'),
            'header_image' => '',
            'footer_text' => __('Documento generado desde el Portal de Normativa', 'ull-normativa'),
            'show_date' => true,
            'show_metadata' => true,
            'show_toc' => true,
            'show_page_numbers' => true,
            'primary_color' => '#003366',
            'font_family' => 'DejaVu Sans, sans-serif',
            'font_size' => 12,
            'margin_top' => 20,
            'margin_right' => 20,
            'margin_bottom' => 20,
            'margin_left' => 20,
        );
        
        // Combinar configuraciones antiguas y nuevas
        $settings = array_merge($defaults, $old_options, $pdf_options);
        
        // Convertir tamaño de fuente a pt
        $font_size = isset($settings['font_size']) ? intval($settings['font_size']) : 12;
        $settings['font_size_pt'] = $font_size . 'pt';
        
        // Convertir márgenes a mm
        $margin_top = isset($settings['margin_top']) ? intval($settings['margin_top']) : 20;
        $margin_right = isset($settings['margin_right']) ? intval($settings['margin_right']) : 20;
        $margin_bottom = isset($settings['margin_bottom']) ? intval($settings['margin_bottom']) : 20;
        $margin_left = isset($settings['margin_left']) ? intval($settings['margin_left']) : 20;
        
        // Obtener datos de la norma
        $numero = get_post_meta($norma_post->ID, '_numero_norma', true);
        $fecha_aprobacion = get_post_meta($norma_post->ID, '_fecha_aprobacion', true);
        $fecha_publicacion = get_post_meta($norma_post->ID, '_fecha_publicacion', true);
        $fecha_vigencia = get_post_meta($norma_post->ID, '_fecha_vigencia', true);
        $organo = get_post_meta($norma_post->ID, '_organo_emisor', true);
        $estado = get_post_meta($norma_post->ID, '_estado_norma', true);
        $boletin = get_post_meta($norma_post->ID, '_boletin_oficial', true);
        
        $tipos = get_the_terms($norma_post->ID, 'tipo_norma');
        $tipo = $tipos && !is_wp_error($tipos) ? $tipos[0]->name : '';
        
        // IMPORTANTE: Establecer $post global para que los shortcodes funcionen correctamente
        global $post;
        $old_post = $post;
        $post = $norma_post;
        
        // Procesar shortcodes primero (incluye [ull_tabla_contenidos])
        $content = do_shortcode($norma_post->post_content);
        
        // Restaurar $post global anterior
        $post = $old_post;
        
        // IMPORTANTE: Limpiar URLs absolutas de los enlaces de tabla de contenidos para PDF
        // Las librerías PDF a veces convierten href="#toc-id" en URLs absolutas
        // Esto asegura que todos los enlaces internos usen solo anclas
        
        // Paso 1: Convertir href="http://dominio.com/#toc-id" a href="#toc-id"
        $content = preg_replace('/href=["\']https?:\/\/[^"\']*?(#[^"\']+)["\']/i', 'href="$1"', $content);
        
        // Paso 2: Asegurar que href="#toc-" no se convierta en URL externa
        // Obtener dominio actual
        $site_url = get_site_url();
        $content = str_replace('href="' . $site_url . '/#', 'href="#', $content);
        $content = str_replace("href='" . $site_url . "/#", "href='#", $content);
        
        // Paso 3: Eliminar cualquier URL del sitio actual que apunte a la misma página con ancla
        $permalink = get_permalink($norma_post->ID);
        $content = str_replace('href="' . $permalink . '#', 'href="#', $content);
        $content = str_replace("href='" . $permalink . "#", "href='#", $content);
        
        // Añadir IDs a los encabezados para enlaces de tabla de contenidos
        $content = $this->add_heading_ids_for_pdf($content);
        
        // IMPORTANTE: Normalizar atributos de tabla para mejor compatibilidad con PDF
        // Convertir bgcolor="#color" a style="background-color: #color"
        $content = preg_replace_callback(
            '/(<(?:td|th)[^>]*)\s+bgcolor=(["\'])([^"\']+)\2([^>]*>)/i',
            function($matches) {
                $tag_start = $matches[1];
                $color = $matches[3];
                $tag_end = $matches[4];
                
                // Si ya tiene style, agregar al final
                if (preg_match('/style=(["\'])([^"\']*)\1/', $tag_start, $style_match)) {
                    $existing_style = $style_match[2];
                    // Agregar background-color si no existe
                    if (strpos($existing_style, 'background') === false) {
                        $new_style = $existing_style . '; background-color: ' . $color;
                        $tag_start = str_replace($style_match[0], 'style="' . $new_style . '"', $tag_start);
                    }
                } else {
                    // Agregar atributo style nuevo
                    $tag_start .= ' style="background-color: ' . $color . '"';
                }
                
                return $tag_start . $tag_end;
            },
            $content
        );
        
        // Normalizar atributos width de tablas para DOMPDF
        $content = preg_replace_callback(
            '/(<(?:table|td|th)[^>]*)\s+width=(["\'])([^"\']+)\2([^>]*>)/i',
            function($matches) {
                $tag_start = $matches[1];
                $width = $matches[3];
                $tag_end = $matches[4];
                
                // Convertir width numérico a píxeles
                if (is_numeric($width)) {
                    $width .= 'px';
                }
                
                // Si ya tiene style, agregar al final
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
        
        // Aplicar formato de párrafos
        $content = wpautop($content);
        
        $estados_labels = array(
            'vigente' => __('Vigente', 'ull-normativa'),
            'derogada' => __('Derogada', 'ull-normativa'),
            'modificada' => __('Modificada', 'ull-normativa'),
        );
        $estado_label = isset($estados_labels[$estado]) ? $estados_labels[$estado] : $estado;
        
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($norma_post->post_title); ?></title>
    <style>
        @page { 
            margin-top: <?php echo $margin_top; ?>mm;
            margin-right: <?php echo $margin_right; ?>mm;
            margin-bottom: <?php echo $margin_bottom; ?>mm;
            margin-left: <?php echo $margin_left; ?>mm;
        }
        body {
            font-family: <?php echo esc_attr($settings['font_family']); ?>;
            font-size: <?php echo esc_attr($settings['font_size_pt']); ?>;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 3px solid <?php echo esc_attr($settings['primary_color']); ?>;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-text {
            font-size: <?php echo ($font_size + 2); ?>pt;
            font-weight: bold;
            color: <?php echo esc_attr($settings['primary_color']); ?>;
        }
        .header-image { max-height: 60px; margin-bottom: 10px; }
        .document-title {
            font-size: <?php echo ($font_size + 4); ?>pt;
            font-weight: bold;
            color: <?php echo esc_attr($settings['primary_color']); ?>;
            margin: 20px 0 10px;
        }
        .document-number { font-size: <?php echo $font_size; ?>pt; color: #666; margin-bottom: 20px; }
        .metadata {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid <?php echo esc_attr($settings['primary_color']); ?>;
        }
        .metadata table { width: 100%; border-collapse: collapse; }
        .metadata td { padding: 5px 10px; vertical-align: top; }
        .metadata td:first-child { font-weight: bold; width: 150px; color: #555; }
        .estado { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: <?php echo ($font_size - 2); ?>pt; font-weight: bold; }
        .estado-vigente { background: #d4edda; color: #155724; }
        .estado-derogada { background: #f8d7da; color: #721c24; }
        .estado-modificada { background: #fff3cd; color: #856404; }
        .content { text-align: justify; }
        .content h1, .content h2, .content h3 { color: <?php echo esc_attr($settings['primary_color']); ?>; margin-top: 20px; }
        .content h1 { font-size: <?php echo ($font_size + 4); ?>pt; }
        .content h2 { font-size: <?php echo ($font_size + 2); ?>pt; }
        .content h3 { font-size: <?php echo $font_size; ?>pt; }
        .content p { margin: 10px 0; }
        
        /* Estilos mejorados para tablas con diseños personalizados */
        .content table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
            page-break-inside: avoid;
        }
        .content table th, 
        .content table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        /* Solo aplicar color a th SIN estilo inline */
        .content table th:not([style*="background"]):not([bgcolor]) { 
            background: #f5f5f5; 
        }
        /* Preservar colores y estilos personalizados */
        .content table th[style],
        .content table td[style],
        .content table th[bgcolor],
        .content table td[bgcolor] {
            /* Los estilos inline se aplicarán automáticamente */
        }
        /* Soportar anchos personalizados */
        .content table th[width],
        .content table td[width] {
            /* Los anchos inline se aplicarán automáticamente */
        }
        
        .content ul, .content ol { margin: 10px 0; padding-left: 30px; }
        .content li { margin: 5px 0; }
        
        /* Estilos para Tabla de Contenidos (v2.0) - MEJORADOS para PDF */
        .ull-tabla-contenidos {
            background: #f9f9f9;
            border: 2px solid <?php echo esc_attr($settings['primary_color']); ?>;
            padding: 15px;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        .ull-toc-header {
            border-bottom: 2px solid <?php echo esc_attr($settings['primary_color']); ?>;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .ull-toc-titulo {
            font-size: <?php echo ($font_size + 2); ?>pt;
            font-weight: bold;
            color: <?php echo esc_attr($settings['primary_color']); ?>;
            margin: 0;
        }
        .ull-toc-toggle { display: none; }
        .ull-toc-list, .ull-toc-numbered {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .ull-toc-numbered {
            list-style: decimal;
            padding-left: 20px;
        }
        .ull-toc-sublist, .ull-toc-subnumbered {
            list-style: none;
            margin-left: 15px;
            margin-top: 5px;
            padding-left: 15px;
        }
        .ull-toc-subnumbered {
            list-style: lower-alpha;
        }
        .ull-toc-item { 
            margin: 5px 0; 
            page-break-inside: avoid; /* Evitar cortes en medio de un ítem */
            line-height: 1.4; /* Más espacio entre líneas */
        }
        .ull-toc-link {
            color: #333;
            text-decoration: none;
            display: block; /* Cambiar a block para mejor control */
            padding: 2px 0;
            word-wrap: break-word; /* Permitir saltos de línea en textos largos */
            overflow-wrap: break-word;
            white-space: normal; /* Permitir múltiples líneas */
            max-width: 100%; /* Asegurar que no se salga */
        }
        .ull-toc-level-1 .ull-toc-link { font-weight: bold; font-size: 11pt; }
        .ull-toc-level-2 .ull-toc-link { font-weight: 600; font-size: 10.5pt; }
        .ull-toc-level-3 .ull-toc-link { font-weight: 500; font-size: 10pt; }
        .ull-toc-level-4 .ull-toc-link,
        .ull-toc-level-5 .ull-toc-link,
        .ull-toc-level-6 .ull-toc-link { font-weight: normal; font-size: 9.5pt; color: #555; }
        
        /* Estilos para las relaciones normativas */
        .norma-relaciones-pdf {
            margin: 20px 0;
            padding: 15px;
            background: #fff8e1;
            border-left: 4px solid <?php echo esc_attr($settings['primary_color']); ?>;
            border-radius: 3px;
            page-break-inside: avoid;
        }
        .relaciones-titulo-pdf {
            color: <?php echo esc_attr($settings['primary_color']); ?>;
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 12px 0;
        }
        .relaciones-contenido-pdf {
            font-size: 10pt;
        }
        .relacion-grupo-pdf {
            margin-bottom: 12px;
        }
        .relacion-tipo-pdf {
            color: <?php echo esc_attr($settings['primary_color']); ?>;
            font-size: 11pt;
            display: block;
            margin-bottom: 6px;
        }
        .relacion-lista-pdf {
            margin: 5px 0 0 20px;
            padding: 0;
            list-style: disc;
        }
        .relacion-lista-pdf li {
            margin: 4px 0;
            line-height: 1.5;
        }
        .relacion-numero-pdf {
            color: #666;
            font-style: italic;
            font-size: 9pt;
        }
        .relacion-nota-pdf {
            color: #555;
            font-size: 9pt;
            font-style: italic;
            display: block;
            margin-top: 3px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <?php 
        // Solo mostrar imagen si hay URL válida y no está vacía
        $header_image = isset($settings['header_image']) ? trim($settings['header_image']) : '';
        if (!empty($header_image) && filter_var($header_image, FILTER_VALIDATE_URL)): 
        ?>
            <img src="<?php echo esc_url($header_image); ?>" class="header-image" alt="Logo">
        <?php endif; ?>
        <div class="header-text"><?php echo esc_html($settings['header_text']); ?></div>
    </div>
    
    <h1 class="document-title"><?php echo esc_html($norma_post->post_title); ?></h1>
    
    <?php if ($numero): ?>
        <div class="document-number"><?php echo esc_html($numero); ?></div>
    <?php endif; ?>
    
    <?php if ($settings['show_metadata']): ?>
    <div class="metadata">
        <table>
            <?php if ($tipo): ?>
            <tr><td><?php _e('Tipo:', 'ull-normativa'); ?></td><td><?php echo esc_html($tipo); ?></td></tr>
            <?php endif; ?>
            <?php if ($estado): ?>
            <tr><td><?php _e('Estado:', 'ull-normativa'); ?></td><td><span class="estado estado-<?php echo esc_attr($estado); ?>"><?php echo esc_html($estado_label); ?></span></td></tr>
            <?php endif; ?>
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
        </table>
    </div>
    <?php endif; ?>
    
    <?php
    // Mostrar relaciones normativas si existen
    $relaciones_html = $this->obtener_relaciones_norma_html($norma_post->ID);
    if (!empty($relaciones_html)) {
        echo $relaciones_html;
    }
    ?>
    
    <div class="content">
        <?php echo $content; ?>
    </div>
    
    <div class="footer">
        <?php echo esc_html($settings['footer_text']); ?>
        <?php if ($settings['show_date']): ?>
            <br><?php printf(__('Generado el %s', 'ull-normativa'), date_i18n('j \d\e F \d\e Y')); ?>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Renderizar página HTML para impresión (fallback)
     */
    private function render_html_page($post) {
        $html = $this->get_pdf_html($post);
        
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
    
    /**
     * Añadir IDs a los encabezados para enlaces de tabla de contenidos en PDF
     * 
     * @param string $content Contenido HTML
     * @return string Contenido con IDs añadidos a los encabezados
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
     * 
     * @param string $text Texto del encabezado
     * @param int $counter Contador para unicidad
     * @return string ID generado
     */
    private function generate_heading_id($text, $counter) {
        // Crear slug del texto
        $slug = sanitize_title($text);
        
        // Si el slug está vacío, usar contador
        if (empty($slug)) {
            $slug = 'seccion';
        }
        
        // Añadir contador para garantizar unicidad
        return 'toc-' . $slug . '-' . $counter;
    }
    
    /**
     * Obtener las relaciones de una norma formateadas para el PDF
     */
    private function obtener_relaciones_norma_html($norma_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ull_norma_relations';
        
        // Verificar que la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        if (!$table_exists) {
            return '';
        }
        
        // Obtener todas las relaciones de esta norma
        $relations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE norma_id = %d ORDER BY relation_type, created_at DESC",
            $norma_id
        ));
        
        if (empty($relations)) {
            return '';
        }
        
        // Tipos de relaciones con sus etiquetas
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
        
        // Agrupar por tipo
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
        
        // Generar HTML
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
}
