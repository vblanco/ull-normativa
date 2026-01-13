<?php
/**
 * Importación y exportación de normativa
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Import_Export {
    
    private $imports_table;
    
    public function __construct() {
        global $wpdb;
        $this->imports_table = $wpdb->prefix . 'ull_norma_imports';
        
        add_action('wp_ajax_ull_import_normativa', array($this, 'ajax_import'));
        add_action('wp_ajax_ull_export_normativa', array($this, 'ajax_export'));
        add_action('wp_ajax_ull_export_codigos', array($this, 'ajax_export_codigos'));
        add_action('wp_ajax_ull_export_relaciones', array($this, 'ajax_export_relaciones'));
        add_action('wp_ajax_ull_get_import_template', array($this, 'ajax_get_template'));
        add_action('admin_init', array($this, 'handle_template_download'));
    }
    
    /**
     * Manejar descarga directa de plantilla
     */
    public function handle_template_download() {
        if (!isset($_GET['ull_download_template']) || $_GET['ull_download_template'] !== '1') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'ull-normativa'));
        }
        
        check_admin_referer('ull_download_template');
        
        $headers = array(
            'titulo', 'numero', 'fecha_aprobacion', 'fecha_publicacion', 
            'fecha_vigencia', 'estado', 'tipo', 'categoria', 'organo',
            'organo_emisor', 'boletin_oficial', 'url_boletin',
            'ambito_aplicacion', 'resumen', 'palabras_clave', 'contenido'
        );
        
        // Generar CSV correctamente formateado
        $handle = fopen('php://temp', 'r+');
        
        // Cabeceras
        fputcsv($handle, $headers, ';', '"');
        
        // Fila de ejemplo con contenido HTML real
        $ejemplo = array(
            'Reglamento de Régimen Académico',
            'NOR-2024-001',
            '2024-01-15',
            '2024-01-20',
            '2024-02-01',
            'vigente',
            'reglamento',
            'academica',
            'consejo-gobierno',
            'Consejo de Gobierno',
            'BOC 2024/15',
            'https://boc.ejemplo.com/2024/15',
            'Universidad de La Laguna',
            'Este reglamento regula el régimen académico de la Universidad.',
            'academico, estudiantes, normativa',
            '<h2>Artículo 1. Objeto</h2><p>El presente reglamento tiene por objeto regular el régimen académico de los estudios oficiales.</p><h2>Artículo 2. Ámbito de aplicación</h2><p>Este reglamento será de aplicación a todos los estudiantes matriculados.</p>'
        );
        fputcsv($handle, $ejemplo, ';', '"');
        
        // Obtener contenido
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla-normativa.csv"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $content;
        exit;
    }
    
    public function render_import_form() {
        $template_url = wp_nonce_url(admin_url('?ull_download_template=1'), 'ull_download_template');
        ?>
        <div class="ull-import-container">
            <div class="ull-import-section">
                <h2><?php _e('Importar desde archivo', 'ull-normativa'); ?></h2>
                
                <form id="ull-import-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('ull_normativa_admin_nonce', 'nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_file"><?php _e('Archivo', 'ull-normativa'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".csv,.json" required>
                                <p class="description"><?php _e('Formatos aceptados: CSV, JSON', 'ull-normativa'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Modo de importación', 'ull-normativa'); ?></th>
                            <td>
                                <label><input type="radio" name="import_mode" value="create" checked> <?php _e('Solo crear nuevas', 'ull-normativa'); ?></label><br>
                                <label><input type="radio" name="import_mode" value="update"> <?php _e('Actualizar existentes por número', 'ull-normativa'); ?></label><br>
                                <label><input type="radio" name="import_mode" value="skip"> <?php _e('Saltar duplicados', 'ull-normativa'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Opciones', 'ull-normativa'); ?></th>
                            <td>
                                <label><input type="checkbox" name="create_terms" value="1" checked> <?php _e('Crear términos de taxonomía si no existen', 'ull-normativa'); ?></label><br>
                                <label><input type="checkbox" name="sanitize_html" value="1" checked> <?php _e('Sanitizar contenido HTML (recomendado)', 'ull-normativa'); ?></label>
                                <p class="description"><?php _e('Desmarcar si el HTML se importa incorrectamente.', 'ull-normativa'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="ull-import-help" style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 12px 15px; margin: 15px 0;">
                        <strong><?php _e('💡 Consejos para el campo "contenido":', 'ull-normativa'); ?></strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li><?php _e('Usa un editor de texto plano (Notepad, VS Code) para editar el CSV, no Excel.', 'ull-normativa'); ?></li>
                            <li><?php _e('El HTML debe ir entre comillas dobles: <code>"&lt;p&gt;Texto&lt;/p&gt;"</code>', 'ull-normativa'); ?></li>
                            <li><?php _e('Si el contenido tiene comillas, escápalas duplicándolas: <code>""</code>', 'ull-normativa'); ?></li>
                            <li><?php _e('Alternativa: usa formato JSON en lugar de CSV para contenido complejo.', 'ull-normativa'); ?></li>
                        </ul>
                    </div>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Importar', 'ull-normativa'); ?></button>
                        <a href="<?php echo esc_url($template_url); ?>" class="button"><?php _e('Descargar plantilla CSV', 'ull-normativa'); ?></a>
                    </p>
                </form>
                
                <div id="ull-import-progress" style="display:none;">
                    <div class="ull-progress-bar"><div class="ull-progress-fill"></div></div>
                    <p class="ull-progress-text"></p>
                </div>
                
                <div id="ull-import-results" style="display:none;"></div>
            </div>
            
            <div class="ull-import-section">
                <h2><?php _e('Importaciones recientes', 'ull-normativa'); ?></h2>
                <?php $this->render_recent_imports(); ?>
            </div>
        </div>
        <?php
    }
    
    private function render_recent_imports() {
        $imports = $this->get_recent_imports(10);
        
        if (empty($imports)) {
            echo '<p>' . __('No hay importaciones registradas.', 'ull-normativa') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Fecha', 'ull-normativa') . '</th>';
        echo '<th>' . __('Archivo', 'ull-normativa') . '</th>';
        echo '<th>' . __('Importadas', 'ull-normativa') . '</th>';
        echo '<th>' . __('Actualizadas', 'ull-normativa') . '</th>';
        echo '<th>' . __('Errores', 'ull-normativa') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($imports as $import) {
            echo '<tr>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($import->import_date))) . '</td>';
            echo '<td>' . esc_html($import->file_name) . '</td>';
            echo '<td>' . intval($import->imported) . '</td>';
            echo '<td>' . intval($import->updated) . '</td>';
            echo '<td>' . intval($import->errors) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    public function render_export_form() {
        $count_normas = wp_count_posts('norma');
        $total_normas = isset($count_normas->publish) ? $count_normas->publish : 0;
        
        $count_codigos = wp_count_posts('codigo');
        $total_codigos = isset($count_codigos->publish) ? $count_codigos->publish : 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ull_norma_relations';
        $total_relaciones = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name) {
            $total_relaciones = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        }
        ?>
        <style>
        .ull-export-tabs {
            border-bottom: 1px solid #ccc;
            margin-bottom: 20px;
        }
        .ull-export-tabs a {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border: 1px solid #ccc;
            border-bottom: none;
            background: #f0f0f1;
            margin-right: 5px;
            border-radius: 3px 3px 0 0;
        }
        .ull-export-tabs a.active {
            background: #fff;
            font-weight: bold;
        }
        .ull-export-tab-content {
            display: none;
        }
        .ull-export-tab-content.active {
            display: block;
        }
        </style>
        
        <div class="ull-export-container">
            <div class="ull-export-tabs">
                <a href="#" class="ull-export-tab active" data-tab="normas"><?php _e('Exportar Normas', 'ull-normativa'); ?> (<?php echo $total_normas; ?>)</a>
                <a href="#" class="ull-export-tab" data-tab="codigos"><?php _e('Exportar Códigos', 'ull-normativa'); ?> (<?php echo $total_codigos; ?>)</a>
                <a href="#" class="ull-export-tab" data-tab="relaciones"><?php _e('Exportar Relaciones', 'ull-normativa'); ?> (<?php echo $total_relaciones; ?>)</a>
            </div>
            
            <!-- Tab: Normas -->
            <div id="tab-normas" class="ull-export-tab-content active">
                <div class="ull-export-section">
                    <h2><?php _e('Exportar normativa', 'ull-normativa'); ?></h2>
                    <p><?php printf(__('Total de normas publicadas: %d', 'ull-normativa'), $total_normas); ?></p>
                    
                    <form id="ull-export-form">
                        <?php wp_nonce_field('ull_normativa_admin_nonce', 'nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Formato', 'ull-normativa'); ?></th>
                                <td>
                                    <label><input type="radio" name="export_format" value="csv" checked> CSV</label><br>
                                    <label><input type="radio" name="export_format" value="json"> JSON</label><br>
                                    <label><input type="radio" name="export_format" value="xml"> XML</label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="export_tipo"><?php _e('Filtrar por tipo', 'ull-normativa'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    $tipos = get_terms(array('taxonomy' => 'tipo_norma', 'hide_empty' => false));
                                    echo '<select name="export_tipo" id="export_tipo">';
                                    echo '<option value="">' . __('Todos', 'ull-normativa') . '</option>';
                                    if (!is_wp_error($tipos)) {
                                        foreach ($tipos as $tipo) {
                                            printf('<option value="%s">%s</option>', esc_attr($tipo->slug), esc_html($tipo->name));
                                        }
                                    }
                                    echo '</select>';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="export_estado"><?php _e('Filtrar por estado', 'ull-normativa'); ?></label>
                                </th>
                                <td>
                                    <select name="export_estado" id="export_estado">
                                        <option value=""><?php _e('Todos', 'ull-normativa'); ?></option>
                                        <option value="vigente"><?php _e('Vigente', 'ull-normativa'); ?></option>
                                        <option value="derogada"><?php _e('Derogada', 'ull-normativa'); ?></option>
                                        <option value="modificada"><?php _e('Modificada', 'ull-normativa'); ?></option>
                                        <option value="pendiente"><?php _e('Pendiente', 'ull-normativa'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Incluir', 'ull-normativa'); ?></th>
                                <td>
                                    <label><input type="checkbox" name="include_content" value="1" checked> <?php _e('Contenido HTML completo', 'ull-normativa'); ?></label><br>
                                    <label><input type="checkbox" name="include_meta" value="1" checked> <?php _e('Todos los metadatos', 'ull-normativa'); ?></label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Exportar Normas', 'ull-normativa'); ?></button>
                        </p>
                    </form>
                    
                    <div id="ull-export-progress" style="display:none;">
                        <p class="ull-progress-text"><?php _e('Generando archivo...', 'ull-normativa'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Códigos -->
            <div id="tab-codigos" class="ull-export-tab-content">
                <div class="ull-export-section">
                    <h2><?php _e('Exportar códigos (colecciones)', 'ull-normativa'); ?></h2>
                    <p><?php printf(__('Total de códigos publicados: %d', 'ull-normativa'), $total_codigos); ?></p>
                    <p class="description"><?php _e('Exporta los códigos con información sobre las normas que contienen.', 'ull-normativa'); ?></p>
                    
                    <form id="ull-export-codigos-form" method="post" action="">
                        <?php wp_nonce_field('ull_normativa_admin_nonce', 'nonce'); ?>
                        <input type="hidden" name="action" value="ull_export_codigos">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Formato', 'ull-normativa'); ?></th>
                                <td>
                                    <label><input type="radio" name="export_format" value="csv" checked> CSV</label><br>
                                    <label><input type="radio" name="export_format" value="json"> JSON</label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="description">
                            <?php _e('Campos exportados: ID, Título, Slug, Descripción, Contenido, Cantidad de normas, Títulos de normas, Números de normas, Fecha de publicación, Fecha de modificación', 'ull-normativa'); ?>
                        </p>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Exportar Códigos', 'ull-normativa'); ?></button>
                        </p>
                    </form>
                    
                    <div id="ull-export-codigos-progress" style="display:none;">
                        <p class="ull-progress-text"><?php _e('Generando archivo...', 'ull-normativa'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Relaciones -->
            <div id="tab-relaciones" class="ull-export-tab-content">
                <div class="ull-export-section">
                    <h2><?php _e('Exportar relaciones entre normas', 'ull-normativa'); ?></h2>
                    <p><?php printf(__('Total de relaciones: %d', 'ull-normativa'), $total_relaciones); ?></p>
                    <p class="description"><?php _e('Exporta todas las relaciones entre normas (deroga, modifica, desarrolla, etc.).', 'ull-normativa'); ?></p>
                    
                    <?php if ($total_relaciones == 0): ?>
                        <div class="notice notice-warning">
                            <p><?php _e('No existen relaciones de normas para exportar.', 'ull-normativa'); ?></p>
                        </div>
                    <?php else: ?>
                    
                    <form id="ull-export-relaciones-form">
                        <?php wp_nonce_field('ull_normativa_admin_nonce', 'nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Formato', 'ull-normativa'); ?></th>
                                <td>
                                    <label><input type="radio" name="export_format" value="csv" checked> CSV</label><br>
                                    <label><input type="radio" name="export_format" value="json"> JSON</label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="description">
                            <?php _e('Campos exportados: ID norma, Título norma, Número norma, Tipo de relación, ID norma relacionada, Título norma relacionada, Número norma relacionada, Notas, Fecha de creación', 'ull-normativa'); ?>
                        </p>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Exportar Relaciones', 'ull-normativa'); ?></button>
                        </p>
                    </form>
                    
                    <div id="ull-export-relaciones-progress" style="display:none;">
                        <p class="ull-progress-text"><?php _e('Generando archivo...', 'ull-normativa'); ?></p>
                    </div>
                    
                    <?php endif; ?>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Tabs
                $('.ull-export-tab').on('click', function(e) {
                    e.preventDefault();
                    var tab = $(this).data('tab');
                    
                    $('.ull-export-tab').removeClass('active');
                    $(this).addClass('active');
                    
                    $('.ull-export-tab-content').removeClass('active');
                    $('#tab-' + tab).addClass('active');
                });
                
                // Exportar códigos
                $('#ull-export-codigos-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    $('#ull-export-codigos-progress').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ull_export_codigos',
                            export_format: $('input[name="export_format"]:checked', this).val(),
                            nonce: $('input[name="nonce"]', this).val()
                        },
                        success: function(response) {
                            $('#ull-export-codigos-progress').hide();
                            
                            if (response.success) {
                                // Descargar archivo
                                window.location.href = response.data.url;
                                alert('<?php _e('Códigos exportados correctamente', 'ull-normativa'); ?>: ' + response.data.count + ' <?php _e('códigos', 'ull-normativa'); ?>');
                            } else {
                                alert('<?php _e('Error:', 'ull-normativa'); ?> ' + response.data);
                            }
                        },
                        error: function() {
                            $('#ull-export-codigos-progress').hide();
                            alert('<?php _e('Error al exportar códigos', 'ull-normativa'); ?>');
                        }
                    });
                });
                
                // Exportar relaciones
                $('#ull-export-relaciones-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    $('#ull-export-relaciones-progress').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ull_export_relaciones',
                            export_format: $('input[name="export_format"]:checked', this).val(),
                            nonce: $('input[name="nonce"]', this).val()
                        },
                        success: function(response) {
                            $('#ull-export-relaciones-progress').hide();
                            
                            if (response.success) {
                                // Descargar archivo
                                window.location.href = response.data.url;
                                alert('<?php _e('Relaciones exportadas correctamente', 'ull-normativa'); ?>: ' + response.data.count + ' <?php _e('relaciones', 'ull-normativa'); ?>');
                            } else {
                                alert('<?php _e('Error:', 'ull-normativa'); ?> ' + response.data);
                            }
                        },
                        error: function() {
                            $('#ull-export-relaciones-progress').hide();
                            alert('<?php _e('Error al exportar relaciones', 'ull-normativa'); ?>');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    public function ajax_import() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        if (empty($_FILES['import_file'])) {
            wp_send_json_error(__('No se ha proporcionado archivo', 'ull-normativa'));
        }
        
        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, array('csv', 'json'))) {
            wp_send_json_error(__('Formato de archivo no válido', 'ull-normativa'));
        }
        
        $content = file_get_contents($file['tmp_name']);
        
        if ($ext === 'csv') {
            $data = $this->parse_csv($content);
        } else {
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(__('Error al parsear JSON', 'ull-normativa'));
            }
        }
        
        if (empty($data)) {
            wp_send_json_error(__('El archivo está vacío o tiene formato incorrecto', 'ull-normativa'));
        }
        
        // Detectar tipo de archivo automáticamente
        $file_type = $this->detect_file_type($data);
        
        $options = array(
            'mode' => isset($_POST['import_mode']) ? sanitize_text_field($_POST['import_mode']) : 'create',
            'create_terms' => !empty($_POST['create_terms']),
            'sanitize_html' => !empty($_POST['sanitize_html']),
        );
        
        // Procesar según el tipo detectado
        switch ($file_type) {
            case 'relaciones':
                $result = $this->process_import_relaciones($data);
                break;
            case 'codigos':
                $result = $this->process_import_codigos($data);
                break;
            case 'normas':
            default:
                $result = $this->process_import($data, $options);
                break;
        }
        
        $this->log_import($file['name'], $result);
        
        wp_send_json_success($result);
    }
    
    /**
     * Detectar tipo de archivo basándose en las columnas
     */
    private function detect_file_type($data) {
        if (empty($data) || !is_array($data)) {
            return 'normas';
        }
        
        $first_item = reset($data);
        if (!is_array($first_item)) {
            return 'normas';
        }
        
        $keys = array_keys($first_item);
        
        // Detectar relaciones (tienen campos específicos de relaciones)
        // Acepta tanto IDs como números
        $relacion_fields_ids = array('norma_id', 'norma_relacionada_id', 'tipo_relacion');
        $relacion_fields_numeros = array('norma_numero', 'norma_relacionada_numero', 'tipo_relacion');
        
        $relacion_match_ids = 0;
        $relacion_match_numeros = 0;
        
        foreach ($relacion_fields_ids as $field) {
            if (in_array($field, $keys)) {
                $relacion_match_ids++;
            }
        }
        
        foreach ($relacion_fields_numeros as $field) {
            if (in_array($field, $keys)) {
                $relacion_match_numeros++;
            }
        }
        
        // Si tiene al menos 2 campos de relaciones (con IDs o con números), es relación
        if ($relacion_match_ids >= 2 || $relacion_match_numeros >= 2) {
            return 'relaciones';
        }
        
        // Detectar códigos (tienen campos específicos de códigos)
        $codigo_fields = array('cantidad_normas', 'normas_titulos', 'normas_numeros');
        $codigo_match = 0;
        foreach ($codigo_fields as $field) {
            if (in_array($field, $keys)) {
                $codigo_match++;
            }
        }
        if ($codigo_match >= 2) {
            return 'codigos';
        }
        
        // Por defecto, asumir que son normas
        return 'normas';
    }
    
    /**
     * Procesar importación de códigos
     */
    private function process_import_codigos($data) {
        $created = 0;
        $updated = 0;
        $errors = array();
        
        foreach ($data as $index => $item) {
            try {
                // Buscar si el código ya existe
                $existing = null;
                if (!empty($item['id'])) {
                    $existing = get_post($item['id']);
                    if (!$existing || $existing->post_type !== 'codigo') {
                        $existing = null;
                    }
                }
                
                // Si no existe por ID, buscar por slug
                if (!$existing && !empty($item['slug'])) {
                    $existing = get_page_by_path($item['slug'], OBJECT, 'codigo');
                }
                
                // Preparar datos del código
                $post_data = array(
                    'post_title' => sanitize_text_field($item['titulo']),
                    'post_type' => 'codigo',
                    'post_status' => 'publish',
                    'post_excerpt' => isset($item['descripcion']) ? sanitize_textarea_field($item['descripcion']) : '',
                    'post_content' => isset($item['contenido']) ? wp_kses_post($item['contenido']) : '',
                );
                
                if (!empty($item['slug'])) {
                    $post_data['post_name'] = sanitize_title($item['slug']);
                }
                
                if ($existing) {
                    // Actualizar código existente
                    $post_data['ID'] = $existing->ID;
                    $codigo_id = wp_update_post($post_data);
                    $updated++;
                } else {
                    // Crear nuevo código
                    $codigo_id = wp_insert_post($post_data);
                    $created++;
                }
                
                if (is_wp_error($codigo_id)) {
                    $errors[] = sprintf(__('Fila %d: %s', 'ull-normativa'), $index + 1, $codigo_id->get_error_message());
                    continue;
                }
                
                // Procesar normas asociadas si hay números de normas
                if (!empty($item['normas_numeros'])) {
                    $normas_numeros = explode('|', $item['normas_numeros']);
                    $normas_ids = array();
                    
                    foreach ($normas_numeros as $numero) {
                        $numero = trim($numero);
                        if (empty($numero)) continue;
                        
                        // Buscar norma por número
                        $norma_query = new WP_Query(array(
                            'post_type' => 'norma',
                            'meta_key' => '_numero_norma',
                            'meta_value' => $numero,
                            'posts_per_page' => 1,
                        ));
                        
                        if ($norma_query->have_posts()) {
                            $norma_query->the_post();
                            $normas_ids[] = array('id' => get_the_ID());
                            wp_reset_postdata();
                        }
                    }
                    
                    if (!empty($normas_ids)) {
                        update_post_meta($codigo_id, '_codigo_normas', $normas_ids);
                    }
                }
                
            } catch (Exception $e) {
                $errors[] = sprintf(__('Fila %d: %s', 'ull-normativa'), $index + 1, $e->getMessage());
            }
        }
        
        return array(
            'type' => 'codigos',
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'total' => count($data),
        );
    }
    
    /**
     * Procesar importación de relaciones
     */
    private function process_import_relaciones($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ull_norma_relations';
        
        // DEBUG: Verificar datos recibidos
        if (empty($data)) {
            return array(
                'type' => 'relaciones',
                'created' => 0,
                'skipped' => 0,
                'errors' => array(__('No hay datos para procesar. El archivo puede estar vacío.', 'ull-normativa')),
                'total' => 0,
            );
        }
        
        // Verificar que existe la tabla
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        if (!$table_exists) {
            return array(
                'type' => 'relaciones',
                'created' => 0,
                'updated' => 0,
                'errors' => array(__('La tabla de relaciones no existe. Verifica que el plugin de relaciones esté activado.', 'ull-normativa')),
                'total' => 0,
            );
        }
        
        $created = 0;
        $skipped = 0;
        $errors = array();
        
        // DEBUG: Log de columnas detectadas
        $first_item = reset($data);
        $columns = is_array($first_item) ? array_keys($first_item) : array();
        
        foreach ($data as $index => $item) {
            // Saltar filas vacías
            if (empty($item) || !is_array($item)) {
                continue;
            }
            
            try {
                // Obtener IDs de normas
                $norma_id = null;
                $related_id = null;
                
                // NORMA ORIGEN: Primero intentar por número, luego por ID
                // Los números son portables entre sistemas, los IDs no
                if (!empty($item['norma_numero'])) {
                    $norma_query = new WP_Query(array(
                        'post_type' => 'norma',
                        'post_status' => 'publish',
                        'meta_key' => '_numero_norma',
                        'meta_value' => $item['norma_numero'],
                        'posts_per_page' => 1,
                        'fields' => 'ids'
                    ));
                    
                    if ($norma_query->have_posts()) {
                        $norma_id = $norma_query->posts[0];
                    } else {
                        $errors[] = sprintf(
                            __('Fila %d: No se encontró norma con número "%s"', 'ull-normativa'), 
                            $index + 1, 
                            $item['norma_numero']
                        );
                        continue;
                    }
                    wp_reset_postdata();
                }
                elseif (!empty($item['norma_id'])) {
                    // Verificar que el post existe y es una norma
                    $post = get_post($item['norma_id']);
                    if ($post && $post->post_type === 'norma' && $post->post_status === 'publish') {
                        $norma_id = intval($item['norma_id']);
                    } else {
                        $errors[] = sprintf(
                            __('Fila %d: La norma con ID %d no existe o no está publicada', 'ull-normativa'), 
                            $index + 1, 
                            $item['norma_id']
                        );
                        continue;
                    }
                }
                else {
                    $errors[] = sprintf(
                        __('Fila %d: Falta norma_id o norma_numero', 'ull-normativa'), 
                        $index + 1
                    );
                    continue;
                }
                
                // NORMA RELACIONADA: Primero intentar por número, luego por ID
                if (!empty($item['norma_relacionada_numero'])) {
                    $related_query = new WP_Query(array(
                        'post_type' => 'norma',
                        'post_status' => 'publish',
                        'meta_key' => '_numero_norma',
                        'meta_value' => $item['norma_relacionada_numero'],
                        'posts_per_page' => 1,
                        'fields' => 'ids'
                    ));
                    
                    if ($related_query->have_posts()) {
                        $related_id = $related_query->posts[0];
                    } else {
                        $errors[] = sprintf(
                            __('Fila %d: No se encontró norma relacionada con número "%s"', 'ull-normativa'), 
                            $index + 1, 
                            $item['norma_relacionada_numero']
                        );
                        continue;
                    }
                    wp_reset_postdata();
                }
                elseif (!empty($item['norma_relacionada_id'])) {
                    // Verificar que el post existe y es una norma
                    $post = get_post($item['norma_relacionada_id']);
                    if ($post && $post->post_type === 'norma' && $post->post_status === 'publish') {
                        $related_id = intval($item['norma_relacionada_id']);
                    } else {
                        $errors[] = sprintf(
                            __('Fila %d: La norma relacionada con ID %d no existe o no está publicada', 'ull-normativa'), 
                            $index + 1, 
                            $item['norma_relacionada_id']
                        );
                        continue;
                    }
                } else {
                    $errors[] = sprintf(
                        __('Fila %d: Falta norma_relacionada_id o norma_relacionada_numero', 'ull-normativa'), 
                        $index + 1
                    );
                    continue;
                }
                
                // Validar que tenemos ambas normas
                if (!$norma_id || !$related_id) {
                    $errors[] = sprintf(__('Fila %d: No se pudieron obtener los IDs de las normas', 'ull-normativa'), $index + 1);
                    continue;
                }
                
                // Validar tipo de relación
                if (empty($item['tipo_relacion'])) {
                    $errors[] = sprintf(__('Fila %d: Falta tipo_relacion', 'ull-normativa'), $index + 1);
                    continue;
                }
                
                $tipo_relacion = sanitize_text_field($item['tipo_relacion']);
                
                // Verificar si ya existe la relación
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE norma_id = %d AND related_norma_id = %d AND relation_type = %s",
                    $norma_id,
                    $related_id,
                    $tipo_relacion
                ));
                
                if ($exists) {
                    $skipped++;
                    continue;
                }
                
                // Insertar relación
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'norma_id' => $norma_id,
                        'related_norma_id' => $related_id,
                        'relation_type' => $tipo_relacion,
                        'notes' => isset($item['notas']) ? sanitize_textarea_field($item['notas']) : '',
                        'created_at' => current_time('mysql'),
                    ),
                    array('%d', '%d', '%s', '%s', '%s')
                );
                
                if ($result) {
                    $created++;
                } else {
                    $errors[] = sprintf(
                        __('Fila %d: Error de base de datos al crear relación - %s', 'ull-normativa'), 
                        $index + 1,
                        $wpdb->last_error
                    );
                }
                
            } catch (Exception $e) {
                $errors[] = sprintf(__('Fila %d: %s', 'ull-normativa'), $index + 1, $e->getMessage());
            }
        }
        
        return array(
            'type' => 'relaciones',
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count($data),
            'message' => sprintf(
                __('Relaciones procesadas: %d creadas, %d omitidas (ya existían), %d errores de %d filas totales', 'ull-normativa'),
                $created,
                $skipped,
                count($errors),
                count($data)
            ),
        );
    }
    
    private function parse_csv($content) {
        // Normalizar saltos de línea
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        
        $data = array();
        $headers = null;
        
        // Usar fgetcsv con un stream temporal para mejor manejo de campos entrecomillados
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        
        while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            // Saltar líneas vacías
            if (count($row) === 1 && empty(trim($row[0]))) {
                continue;
            }
            
            if ($headers === null) {
                $headers = array_map('trim', $row);
                continue;
            }
            
            // Si la fila tiene menos columnas que cabeceras, rellenar con vacío
            while (count($row) < count($headers)) {
                $row[] = '';
            }
            
            // Si tiene más columnas, puede ser que el contenido HTML tenga ; sin entrecomillar
            // En ese caso, unir las columnas extra en la última
            if (count($row) > count($headers)) {
                $extra = array_splice($row, count($headers) - 1);
                $row[] = implode(';', $extra);
            }
            
            $item = array();
            foreach ($headers as $i => $header) {
                $item[$header] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            $data[] = $item;
        }
        
        fclose($handle);
        
        return $data;
    }
    
    private function process_import($data, $options) {
        $result = array(
            'total' => count($data),
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_messages' => array(),
        );
        
        $sanitizer = new ULL_HTML_Sanitizer();
        
        foreach ($data as $index => $row) {
            $titulo = isset($row['titulo']) ? sanitize_text_field($row['titulo']) : '';
            $numero = isset($row['numero']) ? sanitize_text_field($row['numero']) : '';
            
            if (empty($titulo)) {
                $result['errors']++;
                $result['error_messages'][] = sprintf(__('Fila %d: Título vacío', 'ull-normativa'), $index + 2);
                continue;
            }
            
            $existing = null;
            if (!empty($numero) && $options['mode'] !== 'create') {
                $existing = $this->find_existing($row, 'numero');
            }
            
            if ($existing && $options['mode'] === 'skip') {
                $result['skipped']++;
                continue;
            }
            
            $post_data = array(
                'post_title' => $titulo,
                'post_type' => 'norma',
                'post_status' => 'publish',
            );
            
            if (isset($row['resumen'])) {
                $post_data['post_excerpt'] = sanitize_textarea_field($row['resumen']);
            }
            
            // Contenido de la norma
            if (isset($row['contenido']) && !empty($row['contenido'])) {
                $contenido = $row['contenido'];
                
                // Restaurar entidades HTML que puedan haber sido escapadas
                $contenido = html_entity_decode($contenido, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                // Si el contenido parece ser texto plano (sin etiquetas HTML), convertir saltos de línea a párrafos
                if (strip_tags($contenido) === $contenido) {
                    // Es texto plano, convertir a HTML
                    $contenido = wpautop($contenido);
                }
                
                if ($options['sanitize_html']) {
                    $contenido = $sanitizer->sanitize($contenido);
                } else {
                    $contenido = wp_kses_post($contenido);
                }
                $post_data['post_content'] = $contenido;
            }
            
            if ($existing) {
                $post_data['ID'] = $existing;
                $post_id = wp_update_post($post_data, true);
                if (!is_wp_error($post_id)) {
                    $result['updated']++;
                }
            } else {
                $post_id = wp_insert_post($post_data, true);
                if (!is_wp_error($post_id)) {
                    $result['imported']++;
                }
            }
            
            if (is_wp_error($post_id)) {
                $result['errors']++;
                $result['error_messages'][] = sprintf(__('Fila %d: %s', 'ull-normativa'), $index + 2, $post_id->get_error_message());
                continue;
            }
            
            // Guardar metadatos
            $meta_fields = array(
                'numero' => '_numero_norma',
                'fecha_aprobacion' => '_fecha_aprobacion',
                'fecha_publicacion' => '_fecha_publicacion',
                'fecha_vigencia' => '_fecha_vigencia',
                'fecha_derogacion' => '_fecha_derogacion',
                'estado' => '_estado_norma',
                'organo_emisor' => '_organo_emisor',
                'boletin_oficial' => '_boletin_oficial',
                'url_boletin' => '_url_boletin',
                'ambito_aplicacion' => '_ambito_aplicacion',
                'palabras_clave' => '_palabras_clave',
            );
            
            foreach ($meta_fields as $csv_key => $meta_key) {
                if (isset($row[$csv_key]) && $row[$csv_key] !== '') {
                    $value = $meta_key === '_url_boletin' 
                        ? esc_url_raw($row[$csv_key]) 
                        : sanitize_text_field($row[$csv_key]);
                    update_post_meta($post_id, $meta_key, $value);
                }
            }
            
            // Taxonomías
            if (isset($row['tipo']) && !empty($row['tipo'])) {
                $this->set_term($post_id, 'tipo_norma', $row['tipo'], $options['create_terms']);
            }
            
            if (isset($row['categoria']) && !empty($row['categoria'])) {
                $this->set_term($post_id, 'categoria_norma', $row['categoria'], $options['create_terms']);
            }
            
            // Órgano: usar campo 'organo' o 'organo_emisor' para la taxonomía
            $organo_value = '';
            if (isset($row['organo']) && !empty($row['organo'])) {
                $organo_value = $row['organo'];
            } elseif (isset($row['organo_emisor']) && !empty($row['organo_emisor'])) {
                $organo_value = $row['organo_emisor'];
            }
            if (!empty($organo_value)) {
                $this->set_term($post_id, 'organo_norma', $organo_value, $options['create_terms']);
            }
        }
        
        return $result;
    }
    
    private function find_existing($row, $field) {
        global $wpdb;
        
        $value = isset($row[$field]) ? $row[$field] : '';
        if (empty($value)) return null;
        
        $meta_key = '_numero_norma';
        
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = %s AND pm.meta_value = %s 
             AND p.post_type = 'norma' AND p.post_status != 'trash'
             LIMIT 1",
            $meta_key, $value
        ));
        
        return $post_id ? intval($post_id) : null;
    }
    
    private function set_term($post_id, $tax, $value, $create) {
        if (empty($value)) {
            return;
        }
        
        $value = trim($value);
        
        // Primero intentar buscar por slug
        $slug = sanitize_title($value);
        $term = get_term_by('slug', $slug, $tax);
        
        // Si no encuentra por slug, buscar por nombre exacto
        if (!$term) {
            $term = get_term_by('name', $value, $tax);
        }
        
        // Si aún no encuentra, buscar comparando nombres normalizados
        if (!$term) {
            $terms = get_terms(array(
                'taxonomy' => $tax,
                'hide_empty' => false,
            ));
            
            $value_normalized = $this->normalize_string($value);
            
            foreach ($terms as $t) {
                // Comparar ignorando mayúsculas
                if (strcasecmp($t->name, $value) === 0) {
                    $term = $t;
                    break;
                }
                // Comparar normalizando (sin acentos, minúsculas)
                if ($this->normalize_string($t->name) === $value_normalized) {
                    $term = $t;
                    break;
                }
                // Comparar por slug
                if ($t->slug === $slug) {
                    $term = $t;
                    break;
                }
            }
        }
        
        // Si no existe y se permite crear, crear el término
        if (!$term && $create) {
            $result = wp_insert_term($value, $tax, array('slug' => $slug));
            if (!is_wp_error($result)) {
                $term = get_term($result['term_id'], $tax);
            }
        }
        
        if ($term) {
            wp_set_object_terms($post_id, array($term->term_id), $tax);
        }
    }
    
    /**
     * Normalizar string para comparación (quitar acentos, minúsculas)
     */
    private function normalize_string($str) {
        $str = mb_strtolower($str, 'UTF-8');
        $str = remove_accents($str); // Función de WordPress
        $str = preg_replace('/[^a-z0-9\s]/', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }
    
    private function log_import($file, $result) {
        global $wpdb;
        
        $wpdb->insert(
            $this->imports_table,
            array(
                'file_name' => $file,
                'total_records' => $result['total'],
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'errors' => $result['errors'],
                'error_log' => !empty($result['error_messages']) ? implode("\n", $result['error_messages']) : '',
                'user_id' => get_current_user_id(),
            ),
            array('%s', '%d', '%d', '%d', '%d', '%s', '%d')
        );
    }
    
    private function get_recent_imports($limit) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->imports_table} ORDER BY import_date DESC LIMIT %d",
            $limit
        ));
        
        return $results ? $results : array();
    }
    
    public function ajax_export() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        $format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';
        $tipo = isset($_POST['export_tipo']) ? sanitize_text_field($_POST['export_tipo']) : '';
        $estado = isset($_POST['export_estado']) ? sanitize_text_field($_POST['export_estado']) : '';
        $include_content = !empty($_POST['include_content']);
        $include_meta = !empty($_POST['include_meta']);
        
        $args = array(
            'post_type' => 'norma',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        
        if (!empty($tipo)) {
            $args['tax_query'] = array(
                array('taxonomy' => 'tipo_norma', 'field' => 'slug', 'terms' => $tipo),
            );
        }
        
        if (!empty($estado)) {
            $args['meta_query'] = array(
                array('key' => '_estado_norma', 'value' => $estado),
            );
        }
        
        $posts = get_posts($args);
        $data = array();
        
        foreach ($posts as $post) {
            $item = array(
                'id' => $post->ID,
                'titulo' => $post->post_title,
                'resumen' => $post->post_excerpt,
                'numero' => get_post_meta($post->ID, '_numero_norma', true),
                'fecha_aprobacion' => get_post_meta($post->ID, '_fecha_aprobacion', true),
                'estado' => get_post_meta($post->ID, '_estado_norma', true),
            );
            
            if ($include_meta) {
                $item['fecha_publicacion'] = get_post_meta($post->ID, '_fecha_publicacion', true);
                $item['fecha_vigencia'] = get_post_meta($post->ID, '_fecha_vigencia', true);
                $item['fecha_derogacion'] = get_post_meta($post->ID, '_fecha_derogacion', true);
                $item['organo_emisor'] = get_post_meta($post->ID, '_organo_emisor', true);
                $item['boletin_oficial'] = get_post_meta($post->ID, '_boletin_oficial', true);
                $item['url_boletin'] = get_post_meta($post->ID, '_url_boletin', true);
                $item['ambito_aplicacion'] = get_post_meta($post->ID, '_ambito_aplicacion', true);
                $item['palabras_clave'] = get_post_meta($post->ID, '_palabras_clave', true);
                $item['version'] = get_post_meta($post->ID, '_version_actual', true);
            }
            
            $tipos = get_the_terms($post->ID, 'tipo_norma');
            $item['tipo'] = $tipos && !is_wp_error($tipos) ? $tipos[0]->slug : '';
            
            $cats = get_the_terms($post->ID, 'categoria_norma');
            $item['categoria'] = $cats && !is_wp_error($cats) ? $cats[0]->slug : '';
            
            $organos = get_the_terms($post->ID, 'organo_norma');
            $item['organo'] = $organos && !is_wp_error($organos) ? $organos[0]->slug : '';
            
            if ($include_content) {
                $item['contenido'] = $post->post_content;
            }
            
            $data[] = $item;
        }
        
        // Limpiar archivos antiguos (más de 1 hora)
        $this->cleanup_old_exports();
        
        // Guardar archivo temporalmente
        $upload_dir = wp_upload_dir();
        $filename = 'normativa-export-' . date('Y-m-d-His') . '.' . $format;
        $filepath = $upload_dir['basedir'] . '/' . $filename;
        
        switch ($format) {
            case 'json':
                file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            case 'xml':
                file_put_contents($filepath, $this->to_xml($data));
                break;
            default:
                $this->write_csv($filepath, $data);
        }
        
        wp_send_json_success(array(
            'url' => $upload_dir['baseurl'] . '/' . $filename,
            'count' => count($data),
        ));
    }
    
    private function write_csv($path, $data) {
        $fp = fopen($path, 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        
        if (!empty($data)) {
            fputcsv($fp, array_keys($data[0]), ';');
            foreach ($data as $row) {
                fputcsv($fp, $row, ';');
            }
        }
        
        fclose($fp);
    }
    
    private function to_xml($data) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<normativa>' . "\n";
        
        foreach ($data as $item) {
            $xml .= '  <norma>' . "\n";
            foreach ($item as $key => $value) {
                $xml .= '    <' . $key . '><![CDATA[' . $value . ']]></' . $key . '>' . "\n";
            }
            $xml .= '  </norma>' . "\n";
        }
        
        $xml .= '</normativa>';
        return $xml;
    }
    
    public function ajax_get_template() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        $headers = array(
            'titulo', 'numero', 'fecha_aprobacion', 'fecha_publicacion', 
            'fecha_vigencia', 'estado', 'tipo', 'categoria', 'organo',
            'organo_emisor', 'boletin_oficial', 'url_boletin',
            'ambito_aplicacion', 'resumen', 'palabras_clave', 'contenido'
        );
        
        $content = implode(';', $headers) . "\n";
        $content .= 'Ejemplo de norma;NOR-2024-001;2024-01-15;2024-01-20;2024-02-01;vigente;reglamento;academica;consejo-gobierno;Consejo de Gobierno;BOC 2024/15;https://boc.ejemplo.com/2024/15;Universidad de La Laguna;Resumen de ejemplo;palabra1, palabra2;<p>Contenido HTML</p>';
        
        wp_send_json_success(array(
            'content' => $content,
            'filename' => 'plantilla-normativa.csv',
        ));
    }
    
    /**
     * Exportar códigos (colecciones de normas)
     */
    public function ajax_export_codigos() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        $format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';
        
        // Obtener todos los códigos
        $args = array(
            'post_type' => 'codigo',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $codigos = get_posts($args);
        $data = array();
        
        foreach ($codigos as $codigo) {
            // Obtener normas del código
            $normas_ids = get_post_meta($codigo->ID, '_codigo_normas', true);
            if (!is_array($normas_ids)) {
                $normas_ids = array();
            }
            
            // Obtener títulos de normas
            $normas_titulos = array();
            $normas_numeros = array();
            foreach ($normas_ids as $norma_data) {
                $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
                $norma = get_post($norma_id);
                if ($norma) {
                    $normas_titulos[] = $norma->post_title;
                    $numero = get_post_meta($norma_id, '_numero_norma', true);
                    if ($numero) {
                        $normas_numeros[] = $numero;
                    }
                }
            }
            
            $item = array(
                'id' => $codigo->ID,
                'titulo' => $codigo->post_title,
                'slug' => $codigo->post_name,
                'descripcion' => $codigo->post_excerpt,
                'contenido' => strip_tags($codigo->post_content),
                'cantidad_normas' => count($normas_ids),
                'normas_titulos' => implode(' | ', $normas_titulos),
                'normas_numeros' => implode(' | ', $normas_numeros),
                'fecha_publicacion' => $codigo->post_date,
                'fecha_modificacion' => $codigo->post_modified,
            );
            
            $data[] = $item;
        }
        
        // Limpiar archivos antiguos
        $this->cleanup_old_exports();
        
        // Guardar archivo temporalmente
        $upload_dir = wp_upload_dir();
        $filename = 'codigos-export-' . date('Y-m-d-His') . '.' . $format;
        $filepath = $upload_dir['basedir'] . '/' . $filename;
        
        switch ($format) {
            case 'json':
                file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            default:
                $this->write_csv($filepath, $data);
        }
        
        wp_send_json_success(array(
            'url' => $upload_dir['baseurl'] . '/' . $filename,
            'count' => count($data),
        ));
    }
    
    /**
     * Exportar relaciones entre normas
     */
    public function ajax_export_relaciones() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        global $wpdb;
        $format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';
        $table_name = $wpdb->prefix . 'ull_norma_relations';
        
        // Verificar si existe la tabla
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            wp_send_json_error(__('No existen relaciones de normas en la base de datos', 'ull-normativa'));
        }
        
        // Obtener todas las relaciones
        $relations = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY norma_id, relation_type"
        );
        
        $data = array();
        
        foreach ($relations as $relation) {
            $norma = get_post($relation->norma_id);
            $related_norma = get_post($relation->related_norma_id);
            
            if (!$norma || !$related_norma) {
                continue;
            }
            
            $item = array(
                'norma_id' => $relation->norma_id,
                'norma_titulo' => $norma->post_title,
                'norma_numero' => get_post_meta($relation->norma_id, '_numero_norma', true),
                'tipo_relacion' => $relation->relation_type,
                'norma_relacionada_id' => $relation->related_norma_id,
                'norma_relacionada_titulo' => $related_norma->post_title,
                'norma_relacionada_numero' => get_post_meta($relation->related_norma_id, '_numero_norma', true),
                'notas' => $relation->notes,
                'fecha_creacion' => $relation->created_at,
            );
            
            // Traducir tipo de relación
            $tipos_relacion = array(
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
            
            $item['tipo_relacion_texto'] = isset($tipos_relacion[$relation->relation_type]) 
                ? $tipos_relacion[$relation->relation_type] 
                : $relation->relation_type;
            
            $data[] = $item;
        }
        
        // Limpiar archivos antiguos
        $this->cleanup_old_exports();
        
        // Guardar archivo temporalmente
        $upload_dir = wp_upload_dir();
        $filename = 'relaciones-normas-export-' . date('Y-m-d-His') . '.' . $format;
        $filepath = $upload_dir['basedir'] . '/' . $filename;
        
        switch ($format) {
            case 'json':
                file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            default:
                $this->write_csv($filepath, $data);
        }
        
        wp_send_json_success(array(
            'url' => $upload_dir['baseurl'] . '/' . $filename,
            'count' => count($data),
        ));
    }
    
    /**
     * Descargar archivo directamente sin guardarlo en el servidor
     */
    /**
     * Limpiar archivos de exportación antiguos (más de 1 hora)
     */
    private function cleanup_old_exports() {
        $upload_dir = wp_upload_dir();
        $files = glob($upload_dir['basedir'] . '/*-export-*.{csv,json,xml}', GLOB_BRACE);
        
        if (!$files) {
            return;
        }
        
        $one_hour_ago = time() - 3600; // 1 hora
        
        foreach ($files as $file) {
            if (file_exists($file) && filemtime($file) < $one_hour_ago) {
                @unlink($file);
            }
        }
    }
}

new ULL_Import_Export();
