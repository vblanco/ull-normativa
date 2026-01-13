<?php
/**
 * Exportador de códigos a PDF
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Codigos_PDF_Export {
    
    public function __construct() {
        add_action('init', array($this, 'handle_pdf_export'));
        // Hook para generar TOC automáticamente al guardar norma
        add_action('save_post_norma', array($this, 'generar_toc_automatico'), 20, 1);
    }
    
    /**
     * Obtener o generar la tabla de contenidos de una norma
     */
    private function obtener_toc_normativa($normativa_id) {
        // Verificar que es una norma válida
        if (get_post_type($normativa_id) !== 'norma') {
            return '';
        }
        
        // Obtener TOC guardada en meta
        $toc_guardada = get_post_meta($normativa_id, '_ull_normativa_toc', true);
        
        if (!empty($toc_guardada)) {
            return $toc_guardada;
        }
        
        // Si no hay TOC guardada, generarla del contenido
        $content = get_post_field('post_content', $normativa_id);
        
        if (empty($content)) {
            return '';
        }
        
        // Generar y guardar TOC
        $toc = $this->generar_toc_desde_contenido($content);
        
        if (!empty($toc)) {
            update_post_meta($normativa_id, '_ull_normativa_toc', $toc);
        }
        
        return $toc;
    }
    
    /**
     * Generar tabla de contenidos desde el contenido HTML
     */
    private function generar_toc_desde_contenido($content) {
        // IMPORTANTE: Eliminar el shortcode ANTES de buscar encabezados
        // para que no aparezca "1 [ull_tabla_contenidos]" en el índice
        
        // Eliminar variantes del shortcode con espacios opcionales
        $content = preg_replace('/\[\s*ull_tabla_contenidos[^\]]*\]/i', '', $content);
        $content = preg_replace('/\[\s*tabla_contenidos[^\]]*\]/i', '', $content);
        
        // Limpiar espacios en blanco múltiples que puedan quedar
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Buscar encabezados en el contenido (H2 a H6)
        preg_match_all('/<h([2-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);
        
        if (empty($matches)) {
            return '';
        }
        
        // Construir HTML de la tabla de contenidos
        $toc_html = '<div class="toc-normativa">';
        $toc_html .= '<h4 class="toc-titulo">Índice de contenido</h4>';
        $toc_html .= '<ul class="toc-lista">';
        
        $current_level = 2;
        $counter = array();
        
        foreach ($matches as $match) {
            $level = intval($match[1]);
            $title = strip_tags($match[2]);
            
            // IMPORTANTE: Ignorar encabezados vacíos o que solo tengan espacios/saltos de línea
            // Esto evita que aparezca "1." sin texto si había un shortcode en un H2
            $title = trim($title);
            if (empty($title)) {
                continue; // Saltar este encabezado
            }
            
            // Inicializar contadores
            if (!isset($counter[$level])) {
                $counter[$level] = 0;
            }
            
            // Resetear niveles inferiores cuando subimos de nivel
            if ($level < $current_level) {
                for ($i = $level + 1; $i <= 6; $i++) {
                    $counter[$i] = 0;
                }
            }
            
            $counter[$level]++;
            $current_level = $level;
            
            // Calcular indentación
            $indent_class = 'toc-level-' . $level;
            
            // Generar numeración
            $numero = '';
            for ($i = 2; $i <= $level; $i++) {
                if (isset($counter[$i]) && $counter[$i] > 0) {
                    $numero .= $counter[$i] . '.';
                }
            }
            
            $toc_html .= '<li class="' . $indent_class . '">';
            $toc_html .= '<span class="toc-numero">' . rtrim($numero, '.') . '</span> ';
            $toc_html .= '<span class="toc-texto">' . esc_html($title) . '</span>';
            $toc_html .= '</li>';
        }
        
        $toc_html .= '</ul></div>';
        
        return $toc_html;
    }
    
    /**
     * Generar TOC automáticamente al guardar una norma
     */
    public function generar_toc_automatico($post_id) {
        // Evitar auto-guardados y revisiones
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Generar y guardar TOC
        $content = get_post_field('post_content', $post_id);
        
        if (!empty($content)) {
            $toc = $this->generar_toc_desde_contenido($content);
            
            if (!empty($toc)) {
                update_post_meta($post_id, '_ull_normativa_toc', $toc);
            }
        }
    }
    
    /**
     * Obtener las relaciones de una norma formateadas para el PDF
     */
    private function obtener_relaciones_norma($norma_id) {
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
            'deroga' => 'Deroga a',
            'derogada_por' => 'Derogada por',
            'modifica' => 'Modifica a',
            'modificada_por' => 'Modificada por',
            'desarrolla' => 'Desarrolla a',
            'desarrollada_por' => 'Desarrollada por',
            'complementa' => 'Complementa a',
            'complementada_por' => 'Complementada por',
            'cita' => 'Cita a',
            'citada_por' => 'Citada por',
            'relacionada' => 'Relacionada con',
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
        $html = '<div class="norma-relaciones">';
        $html .= '<h4 class="relaciones-titulo">Relaciones normativas</h4>';
        $html .= '<div class="relaciones-contenido">';
        
        foreach ($grouped as $type => $items) {
            $label = isset($relation_types[$type]) ? $relation_types[$type] : $type;
            
            $html .= '<div class="relacion-grupo">';
            $html .= '<strong class="relacion-tipo">' . esc_html($label) . ':</strong>';
            $html .= '<ul class="relacion-lista">';
            
            foreach ($items as $item) {
                $html .= '<li>';
                $html .= esc_html($item['title']);
                
                if (!empty($item['numero'])) {
                    $html .= ' <span class="relacion-numero">(' . esc_html($item['numero']) . ')</span>';
                }
                
                if (!empty($item['notes'])) {
                    $html .= '<br><span class="relacion-nota">' . esc_html($item['notes']) . '</span>';
                }
                
                $html .= '</li>';
            }
            
            $html .= '</ul></div>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    public function handle_pdf_export() {
        if (!isset($_GET['ull_codigo_pdf']) || empty($_GET['ull_codigo_pdf'])) {
            return;
        }
        
        $codigo_id = intval($_GET['ull_codigo_pdf']);
        $codigo = get_post($codigo_id);
        
        if (!$codigo || $codigo->post_type !== 'codigo' || $codigo->post_status !== 'publish') {
            wp_die(__('Código no encontrado.', 'ull-normativa'));
        }
        
        // Manejo de errores robusto
        try {
            $this->generate_pdf($codigo);
        } catch (Exception $e) {
            // Log del error
            error_log('Error exportando código a PDF: ' . $e->getMessage());
            
            // Mensaje amigable al usuario
            wp_die(
                '<h1>' . __('Error al generar PDF', 'ull-normativa') . '</h1>' .
                '<p>' . __('No se pudo generar el PDF del código. Por favor, inténtelo de nuevo.', 'ull-normativa') . '</p>' .
                '<p><a href="' . get_permalink($codigo_id) . '">' . __('Volver al código', 'ull-normativa') . '</a></p>',
                __('Error de exportación', 'ull-normativa'),
                array('response' => 500)
            );
        }
    }
    
    private function generate_pdf($codigo) {
        // Obtener configuración
        $logo_id = get_option('ull_codigos_pdf_logo', '');
        $header_color = get_option('ull_codigos_pdf_header_color', '#003366');
        $header_text_color = get_option('ull_codigos_pdf_header_text_color', '#ffffff');
        $title_color = get_option('ull_codigos_pdf_title_color', '#003366');
        $header_text = get_option('ull_codigos_pdf_header_text', get_bloginfo('name'));
        $footer_text = get_option('ull_codigos_pdf_footer_text', '© ' . date('Y') . ' ' . get_bloginfo('name'));
        $filename_pattern = get_option('ull_codigos_pdf_filename_pattern', '{slug}');
        
        // Generar nombre de archivo
        $filename = str_replace(
            array('{id}', '{slug}', '{titulo}', '{fecha}'),
            array($codigo->ID, $codigo->post_name, sanitize_file_name($codigo->post_title), date('Y-m-d')),
            $filename_pattern
        );
        $filename = $filename . '.pdf';
        
        // Logo - convertir a base64 para mejor compatibilidad con DOMPDF y navegadores
        $logo_data_url = '';
        
        if ($logo_id) {
            $logo_path = get_attached_file($logo_id);
            
            // Debug: Log para verificar
            error_log('ULL Codigos PDF - Logo ID: ' . $logo_id);
            error_log('ULL Codigos PDF - Logo Path: ' . $logo_path);
            
            if ($logo_path && file_exists($logo_path)) {
                // Leer el archivo y convertirlo a base64
                $image_data = file_get_contents($logo_path);
                
                if ($image_data !== false) {
                    // Detectar el tipo MIME - con fallback si finfo no está disponible
                    $mime_type = 'image/png'; // Default
                    
                    if (class_exists('finfo')) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $detected_mime = $finfo->file($logo_path);
                        if ($detected_mime) {
                            $mime_type = $detected_mime;
                        }
                    } else {
                        // Fallback: detectar por extensión
                        $extension = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
                        $mime_types = array(
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'svg' => 'image/svg+xml',
                            'webp' => 'image/webp',
                        );
                        if (isset($mime_types[$extension])) {
                            $mime_type = $mime_types[$extension];
                        }
                    }
                    
                    // Crear data URL
                    $logo_data_url = 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
                    
                    error_log('ULL Codigos PDF - Logo MIME: ' . $mime_type);
                    error_log('ULL Codigos PDF - Data URL length: ' . strlen($logo_data_url));
                } else {
                    error_log('ULL Codigos PDF - Error: No se pudo leer el archivo');
                }
            } else {
                error_log('ULL Codigos PDF - Error: El archivo no existe en: ' . $logo_path);
            }
            
            // Si falla la conversión a base64, usar URL normal como último recurso
            if (empty($logo_data_url)) {
                $logo_data_url = wp_get_attachment_url($logo_id);
                error_log('ULL Codigos PDF - Usando URL fallback: ' . $logo_data_url);
            }
        } else {
            error_log('ULL Codigos PDF - Warning: Logo ID está vacío');
        }
        
        // Obtener normas
        $normas = get_post_meta($codigo->ID, '_codigo_normas', true);
        
        // Validar que normas sea un array
        if (!is_array($normas)) {
            $normas = array();
        }
        
        // Generar HTML
        $html = $this->generate_html($codigo, $normas, array(
            'logo_url' => $logo_data_url,  // Usando data URL (base64)
            'header_color' => $header_color,
            'header_text_color' => $header_text_color,
            'title_color' => $title_color,
            'header_text' => $header_text,
            'footer_text' => $footer_text,
        ));
        
        // Intentar usar DOMPDF del plugin principal
        $composer_autoload = ULL_NORMATIVA_PLUGIN_DIR . 'vendor/autoload.php';
        if (file_exists($composer_autoload)) {
            require_once $composer_autoload;
            
            if (class_exists('Dompdf\Dompdf')) {
                $this->render_with_dompdf($html, $filename);
                return;
            }
        }
        
        // Fallback: HTML para imprimir
        $this->render_html_fallback($html, $codigo->post_title);
    }
    
    private function generate_html($codigo, $normas, $config) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($codigo->post_title) . '</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
        }
        .header {
            background-color: ' . esc_attr($config['header_color']) . ';
            color: ' . esc_attr($config['header_text_color']) . ';
            padding: 20px;
            margin: 0 0 20px 0;
            text-align: center;
        }
        .header-logo {
            height: auto;
            width: auto;
            margin-bottom: 10px;
        }
        .header-text {
            font-size: 12pt;
            margin: 0;
        }
        .titulo-codigo {
            color: ' . esc_attr($config['title_color']) . ';
            font-size: 20pt;
            font-weight: bold;
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 3px solid ' . esc_attr($config['header_color']) . ';
        }
        .descripcion {
            margin-bottom: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-left: 4px solid ' . esc_attr($config['header_color']) . ';
        }
        .indice {
            margin: 30px 0;
            padding: 20px;
            background: #fafafa;
            border: 1px solid #ddd;
        }
        .indice h3 {
            color: ' . esc_attr($config['title_color']) . ';
            margin-top: 0;
        }
        .indice ol {
            margin: 0;
            padding-left: 20px;
        }
        .indice li {
            margin: 5px 0;
        }
        .seccion-titulo {
            color: ' . esc_attr($config['title_color']) . ';
            font-size: 14pt;
            font-weight: bold;
            margin: 30px 0 15px 0;
            padding: 10px;
            background: #f0f0f0;
            border-left: 4px solid ' . esc_attr($config['header_color']) . ';
        }
        .norma {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            page-break-inside: avoid;
        }
        .norma-titulo {
            color: ' . esc_attr($config['title_color']) . ';
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .norma-numero {
            display: inline-block;
            background: ' . esc_attr($config['header_color']) . ';
            color: ' . esc_attr($config['header_text_color']) . ';
            padding: 2px 8px;
            font-size: 9pt;
            border-radius: 3px;
            margin-left: 10px;
        }
        .norma-fecha {
            color: #666;
            font-size: 10pt;
            margin-bottom: 10px;
        }
        .norma-nota {
            background: #fffde7;
            padding: 10px;
            border-left: 3px solid #ffc107;
            margin-bottom: 15px;
            font-style: italic;
        }
        .norma-contenido {
            text-align: justify;
        }
        .norma-contenido h1, .norma-contenido h2, .norma-contenido h3 {
            color: ' . esc_attr($config['title_color']) . ';
        }
        /* Estilos para la tabla de contenidos */
        .toc-normativa {
            margin: 15px 0;
            padding: 15px;
            background: #f0f8ff;
            border-left: 4px solid ' . esc_attr($config['header_color']) . ';
            border-radius: 3px;
        }
        .toc-titulo {
            color: ' . esc_attr($config['title_color']) . ';
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .toc-lista {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .toc-lista li {
            margin: 4px 0;
            padding: 2px 0;
            font-size: 9pt;
            line-height: 1.4;
        }
        .toc-level-2 {
            margin-left: 0;
        }
        .toc-level-3 {
            margin-left: 15px;
        }
        .toc-level-4 {
            margin-left: 30px;
        }
        .toc-level-5 {
            margin-left: 45px;
        }
        .toc-level-6 {
            margin-left: 60px;
        }
        .toc-numero {
            color: #666;
            font-weight: 600;
            margin-right: 5px;
        }
        .toc-texto {
            color: #333;
        }
        /* Estilos para las relaciones normativas */
        .norma-relaciones {
            margin: 15px 0;
            padding: 15px;
            background: #fff8e1;
            border-left: 4px solid ' . esc_attr($config['header_color']) . ';
            border-radius: 3px;
        }
        .relaciones-titulo {
            color: ' . esc_attr($config['title_color']) . ';
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .relaciones-contenido {
            font-size: 9pt;
        }
        .relacion-grupo {
            margin-bottom: 10px;
        }
        .relacion-tipo {
            color: ' . esc_attr($config['title_color']) . ';
            font-size: 10pt;
            display: block;
            margin-bottom: 5px;
        }
        .relacion-lista {
            margin: 5px 0;
            padding-left: 20px;
            list-style: disc;
        }
        .relacion-lista li {
            margin: 3px 0;
            line-height: 1.4;
        }
        .relacion-numero {
            color: #666;
            font-style: italic;
        }
        .relacion-nota {
            color: #555;
            font-size: 8pt;
            font-style: italic;
            display: block;
            margin-top: 2px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        /* Estilos base para tablas - NO sobrescribir estilos inline */
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        /* Solo aplicar color a th SIN estilo inline */
        th:not([style*="background"]):not([bgcolor]) {
            background: ' . esc_attr($config['header_color']) . ';
            color: ' . esc_attr($config['header_text_color']) . ';
        }
        /* Soporte para tablas del editor de WordPress */
        .norma-contenido table {
            page-break-inside: avoid;
        }
        /* Preservar colores personalizados de celdas */
        .norma-contenido th[style],
        .norma-contenido td[style],
        .norma-contenido th[bgcolor],
        .norma-contenido td[bgcolor] {
            /* Los estilos inline se aplicarán automáticamente */
        }
        /* Soporte para anchos de columna personalizados */
        .norma-contenido th[width],
        .norma-contenido td[width] {
            /* Los anchos inline se aplicarán automáticamente */
        }
        /* Tablas con clase personalizada */
        .norma-contenido table[class] th,
        .norma-contenido table[class] td {
            /* Preservar estilos de tablas con clases */
        }
    </style>
</head>
<body>
    <div class="header">';
        
        if (!empty($config['logo_url'])) {
            $html .= '<img src="' . esc_attr($config['logo_url']) . '" class="header-logo" alt="Logo Universidad"><br>';
        } else {
            // Debug: Mostrar mensaje si no hay logo
            error_log('ULL Codigos PDF - Config logo_url está vacío en generate_html');
        }
        
        $html .= '<p class="header-text">' . esc_html($config['header_text']) . '</p>
    </div>
    
    <h1 class="titulo-codigo">' . esc_html($codigo->post_title) . '</h1>';
        
        if ($codigo->post_content) {
            $html .= '<div class="descripcion">' . $this->process_content($codigo->post_content) . '</div>';
        }
        
        // Índice
        if (!empty($normas)) {
            $html .= '<div class="indice">
                <h3>' . __('Índice', 'ull-normativa') . '</h3>
                <ol>';
            
            $seccion_actual = '';
            foreach ($normas as $norma_data) {
                $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
                $norma = get_post($norma_id);
                if (!$norma) continue;
                
                $seccion = isset($norma_data['seccion']) ? $norma_data['seccion'] : '';
                
                if ($seccion && $seccion !== $seccion_actual) {
                    if ($seccion_actual) $html .= '</ol></li>';
                    $seccion_actual = $seccion;
                    $html .= '<li><strong>' . esc_html($seccion) . '</strong><ol>';
                }
                
                $html .= '<li>' . esc_html($norma->post_title) . '</li>';
            }
            if ($seccion_actual) $html .= '</ol></li>';
            
            $html .= '</ol></div>';
            
            // Contenido de normas
            $seccion_actual = '';
            foreach ($normas as $norma_data) {
                $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
                $norma = get_post($norma_id);
                if (!$norma) continue;
                
                $numero = get_post_meta($norma_id, '_numero_norma', true);
                $fecha = get_post_meta($norma_id, '_fecha_aprobacion', true);
                $seccion = isset($norma_data['seccion']) ? $norma_data['seccion'] : '';
                $nota = isset($norma_data['nota']) ? $norma_data['nota'] : '';
                
                if ($seccion && $seccion !== $seccion_actual) {
                    $seccion_actual = $seccion;
                    $html .= '<h2 class="seccion-titulo">' . esc_html($seccion) . '</h2>';
                }
                
                $html .= '<div class="norma">
                    <h3 class="norma-titulo">' . esc_html($norma->post_title);
                
                if ($numero) {
                    $html .= '<span class="norma-numero">' . esc_html($numero) . '</span>';
                }
                
                $html .= '</h3>';
                
                if ($fecha) {
                    $html .= '<p class="norma-fecha">' . __('Fecha de aprobación:', 'ull-normativa') . ' ' . esc_html($fecha) . '</p>';
                }
                
                if ($nota) {
                    $html .= '<div class="norma-nota">' . $this->process_content($nota) . '</div>';
                }
                
                // AGREGAR TABLA DE CONTENIDOS SI EXISTE
                $toc = $this->obtener_toc_normativa($norma_id);
                if (!empty($toc)) {
                    $html .= $toc;
                }
                
                // AGREGAR RELACIONES NORMATIVAS SI EXISTEN
                $relaciones = $this->obtener_relaciones_norma($norma_id);
                if (!empty($relaciones)) {
                    $html .= $relaciones;
                }
                
                $html .= '<div class="norma-contenido">' . $this->process_content($norma->post_content) . '</div>
                </div>';
            }
        }
        
        $html .= '<div class="footer">' . esc_html($config['footer_text']) . ' | ' . __('Generado el', 'ull-normativa') . ' ' . date('d/m/Y H:i') . '</div>
</body>
</html>';
        
        return $html;
    }
    
    private function render_with_dompdf($html, $filename) {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream($filename, array('Attachment' => true));
        exit;
    }
    
    /**
     * Procesar contenido HTML para PDF
     * Versión mejorada que preserva estilos de tablas
     */
    private function process_content($content) {
        if (empty($content)) {
            return '';
        }
        
        // Decodificar entidades HTML que puedan estar codificadas
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // ELIMINAR el shortcode [ull_tabla_contenidos] porque el TOC se inserta por separado
        // Esto evita que aparezca duplicado o como texto literal
        $content = preg_replace('/\[ull_tabla_contenidos[^\]]*\]/i', '', $content);
        
        // También eliminar variantes comunes del shortcode
        $content = preg_replace('/\[tabla_contenidos[^\]]*\]/i', '', $content);
        
        // Aplicar solo formato básico de párrafos
        $content = wpautop($content);
        
        // Limpiar todos los demás shortcodes
        $content = strip_shortcodes($content);
        
        // Eliminar scripts (MANTENER estilos inline para tablas)
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        
        // Eliminar solo etiquetas <style> globales, NO atributos style=""
        // Esto preserva los estilos inline de tablas y celdas
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
        
        // IMPORTANTE: Normalizar atributos bgcolor a style para mejor compatibilidad
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
                
                // Convertir width numérico a porcentaje o píxeles
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
        
        return $content;
    }
    
    private function render_html_fallback($html, $title) {
        // Añadir botón de impresión
        $print_button = '<div style="position: fixed; top: 10px; right: 10px; z-index: 9999; background: #003366; padding: 10px 20px; border-radius: 5px;">
            <button onclick="window.print()" style="background: none; border: none; color: white; font-size: 14px; cursor: pointer;">
                🖨️ ' . __('Imprimir / Guardar como PDF', 'ull-normativa') . '
            </button>
        </div>
        <style>
            @media print {
                body > div:first-child { display: none !important; }
            }
        </style>';
        
        $html = str_replace('<body>', '<body>' . $print_button, $html);
        
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}

new ULL_Codigos_PDF_Export();
