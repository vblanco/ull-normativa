<?php
/**
 * Configuración de PDF y gestión de DOMPDF
 * 
 * @package ULL_Normativa
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_PDF_Settings {
    
    /**
     * Instalador de dompdf
     */
    private $installer;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once ULL_NORMATIVA_PLUGIN_DIR . 'includes/class-dompdf-installer.php';
        $this->installer = new ULL_DOMPDF_Installer();
        
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_ull_install_dompdf', array($this, 'ajax_install_dompdf'));
        add_action('wp_ajax_ull_uninstall_dompdf', array($this, 'ajax_uninstall_dompdf'));
        add_action('wp_ajax_ull_check_dompdf_status', array($this, 'ajax_check_status'));
    }
    
    /**
     * Añadir página de menú
     */
    public function add_menu_page() {
        // Añadir como submenú de Normativa principal (unificado para normas y códigos)
        add_submenu_page(
            'ull-normativa',
            __('Configuración PDF', 'ull-normativa'),
            __('Configuración PDF', 'ull-normativa'),
            'manage_options',
            'ull-pdf-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('ull_pdf_settings', 'ull_pdf_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        // Sección de generación de PDF
        add_settings_section(
            'ull_pdf_generation',
            __('Generación de PDF', 'ull-normativa'),
            array($this, 'render_generation_section'),
            'ull_pdf_settings'
        );
        
        add_settings_field(
            'pdf_orientation',
            __('Orientación', 'ull-normativa'),
            array($this, 'render_orientation_field'),
            'ull_pdf_settings',
            'ull_pdf_generation'
        );
        
        add_settings_field(
            'pdf_paper_size',
            __('Tamaño de papel', 'ull-normativa'),
            array($this, 'render_paper_size_field'),
            'ull_pdf_settings',
            'ull_pdf_generation'
        );
        
        add_settings_field(
            'pdf_font_size',
            __('Tamaño de fuente', 'ull-normativa'),
            array($this, 'render_font_size_field'),
            'ull_pdf_settings',
            'ull_pdf_generation'
        );
        
        add_settings_field(
            'pdf_font_family',
            __('Tipo de fuente', 'ull-normativa'),
            array($this, 'render_font_family_field'),
            'ull_pdf_settings',
            'ull_pdf_generation'
        );
        
        add_settings_field(
            'pdf_margins',
            __('Márgenes (mm)', 'ull-normativa'),
            array($this, 'render_margins_field'),
            'ull_pdf_settings',
            'ull_pdf_generation'
        );
        
        // Sección de personalización
        add_settings_section(
            'ull_pdf_customization',
            __('Personalización', 'ull-normativa'),
            array($this, 'render_customization_section'),
            'ull_pdf_settings'
        );
        
        add_settings_field(
            'show_toc',
            __('Tabla de contenidos', 'ull-normativa'),
            array($this, 'render_toc_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'show_page_numbers',
            __('Números de página', 'ull-normativa'),
            array($this, 'render_page_numbers_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        // Cabecera
        add_settings_field(
            'header_logo',
            __('Logo de cabecera', 'ull-normativa'),
            array($this, 'render_header_logo_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'header_text',
            __('Texto de encabezado', 'ull-normativa'),
            array($this, 'render_header_text_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'header_bg_color',
            __('Color de fondo de cabecera', 'ull-normativa'),
            array($this, 'render_header_bg_color_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'header_text_color',
            __('Color de texto de cabecera', 'ull-normativa'),
            array($this, 'render_header_text_color_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'title_color',
            __('Color de títulos', 'ull-normativa'),
            array($this, 'render_title_color_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'index_bg_color',
            __('Color de fondo de índices', 'ull-normativa'),
            array($this, 'render_index_bg_color_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'norma_title_color',
            __('Color de títulos de normas', 'ull-normativa'),
            array($this, 'render_norma_title_color_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        // Pie de página
        add_settings_field(
            'footer_text',
            __('Texto de pie de página', 'ull-normativa'),
            array($this, 'render_footer_text_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
        
        add_settings_field(
            'show_generation_date',
            __('Mostrar fecha de generación', 'ull-normativa'),
            array($this, 'render_show_generation_date_field'),
            'ull_pdf_settings',
            'ull_pdf_customization'
        );
    }
    
    /**
     * Sanitizar opciones
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        // Orientación
        $valid_orientations = array('portrait', 'landscape');
        $sanitized['pdf_orientation'] = isset($input['pdf_orientation']) && in_array($input['pdf_orientation'], $valid_orientations)
            ? $input['pdf_orientation']
            : 'portrait';
        
        // Tamaño de papel
        $valid_sizes = array('A4', 'A3', 'Letter', 'Legal');
        $sanitized['pdf_paper_size'] = isset($input['pdf_paper_size']) && in_array($input['pdf_paper_size'], $valid_sizes)
            ? $input['pdf_paper_size']
            : 'A4';
        
        // Tamaño de fuente
        $sanitized['pdf_font_size'] = isset($input['pdf_font_size']) ? absint($input['pdf_font_size']) : 11;
        $sanitized['pdf_font_size'] = max(8, min(20, $sanitized['pdf_font_size']));
        
        // Tipo de fuente
        $valid_fonts = array('DejaVu Sans', 'DejaVu Serif', 'DejaVu Sans Mono', 'Times', 'Helvetica', 'Courier');
        $sanitized['pdf_font_family'] = isset($input['pdf_font_family']) && in_array($input['pdf_font_family'], $valid_fonts)
            ? $input['pdf_font_family']
            : 'DejaVu Sans';
        
        // Márgenes
        $sanitized['pdf_margins'] = array(
            'top' => isset($input['margin_top']) ? absint($input['margin_top']) : 20,
            'right' => isset($input['margin_right']) ? absint($input['margin_right']) : 15,
            'bottom' => isset($input['margin_bottom']) ? absint($input['margin_bottom']) : 20,
            'left' => isset($input['margin_left']) ? absint($input['margin_left']) : 15,
        );
        
        // Opciones booleanas - los checkboxes solo envían valor si están marcados
        $sanitized['show_toc'] = isset($input['show_toc']) ? true : false;
        $sanitized['show_page_numbers'] = isset($input['show_page_numbers']) ? true : false;
        $sanitized['show_generation_date'] = isset($input['show_generation_date']) ? true : false;
        
        // Textos del encabezado y pie de página
        $sanitized['header_text'] = isset($input['header_text']) ? sanitize_text_field($input['header_text']) : __('Universidad de La Laguna', 'ull-normativa');
        $sanitized['footer_text'] = isset($input['footer_text']) ? sanitize_text_field($input['footer_text']) : __('Universidad de La Laguna - Normativa', 'ull-normativa');
        
        // Logo
        $sanitized['header_logo'] = isset($input['header_logo']) ? absint($input['header_logo']) : 0;
        
        // Colores
        $sanitized['header_bg_color'] = isset($input['header_bg_color']) ? sanitize_hex_color($input['header_bg_color']) : '#003366';
        $sanitized['header_text_color'] = isset($input['header_text_color']) ? sanitize_hex_color($input['header_text_color']) : '#ffffff';
        $sanitized['title_color'] = isset($input['title_color']) ? sanitize_hex_color($input['title_color']) : '#003366';
        $sanitized['index_bg_color'] = isset($input['index_bg_color']) ? sanitize_hex_color($input['index_bg_color']) : '#f9f9f9';
        $sanitized['norma_title_color'] = isset($input['norma_title_color']) ? sanitize_hex_color($input['norma_title_color']) : '#003366';
        
        return $sanitized;
    }
    
    /**
     * Renderizar sección de generación
     */
    public function render_generation_section() {
        echo '<p>' . __('Configure los parámetros de generación de archivos PDF.', 'ull-normativa') . '</p>';
    }
    
    /**
     * Renderizar sección de personalización
     */
    public function render_customization_section() {
        echo '<p>' . __('Personalice el aspecto de los archivos PDF generados.', 'ull-normativa') . '</p>';
    }
    
    /**
     * Renderizar campo de orientación
     */
    public function render_orientation_field() {
        $options = get_option('ull_pdf_options', array());
        // Compatibilidad con versiones anteriores
        $value = isset($options['pdf_orientation']) ? $options['pdf_orientation'] : (isset($options['orientation']) ? $options['orientation'] : 'portrait');
        ?>
        <select name="ull_pdf_options[pdf_orientation]">
            <option value="portrait" <?php selected($value, 'portrait'); ?>><?php _e('Vertical', 'ull-normativa'); ?></option>
            <option value="landscape" <?php selected($value, 'landscape'); ?>><?php _e('Horizontal', 'ull-normativa'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Renderizar campo de tamaño de papel
     */
    public function render_paper_size_field() {
        $options = get_option('ull_pdf_options', array());
        // Compatibilidad con versiones anteriores
        $value = isset($options['pdf_paper_size']) ? $options['pdf_paper_size'] : (isset($options['paper_size']) ? $options['paper_size'] : 'A4');
        ?>
        <select name="ull_pdf_options[pdf_paper_size]">
            <option value="A4" <?php selected($value, 'A4'); ?>>A4 (210 × 297 mm)</option>
            <option value="A3" <?php selected($value, 'A3'); ?>>A3 (297 × 420 mm)</option>
            <option value="Letter" <?php selected($value, 'Letter'); ?>>Letter (8.5 × 11 in)</option>
            <option value="Legal" <?php selected($value, 'Legal'); ?>>Legal (8.5 × 14 in)</option>
        </select>
        <?php
    }
    
    /**
     * Renderizar campo de tamaño de fuente
     */
    public function render_font_size_field() {
        $options = get_option('ull_pdf_options', array());
        // Compatibilidad con versiones anteriores
        $value = isset($options['pdf_font_size']) ? $options['pdf_font_size'] : (isset($options['font_size']) ? $options['font_size'] : 11);
        ?>
        <input type="number" name="ull_pdf_options[pdf_font_size]" value="<?php echo esc_attr($value); ?>" min="8" max="20" step="1">
        <p class="description"><?php _e('Tamaño base de la fuente en puntos (8-20).', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de tipo de fuente
     */
    public function render_font_family_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['pdf_font_family']) ? $options['pdf_font_family'] : 'DejaVu Sans';
        
        $fonts = array(
            'DejaVu Sans' => 'DejaVu Sans (Por defecto)',
            'DejaVu Serif' => 'DejaVu Serif',
            'DejaVu Sans Mono' => 'DejaVu Sans Mono',
            'Times' => 'Times New Roman',
            'Helvetica' => 'Helvetica / Arial',
            'Courier' => 'Courier',
        );
        ?>
        <select name="ull_pdf_options[pdf_font_family]">
            <?php foreach ($fonts as $font_value => $font_label): ?>
                <option value="<?php echo esc_attr($font_value); ?>" <?php selected($value, $font_value); ?>>
                    <?php echo esc_html($font_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Tipo de fuente para el documento PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de márgenes
     */
    public function render_margins_field() {
        $options = get_option('ull_pdf_options', array());
        // Compatibilidad con versiones anteriores - soportar ambas estructuras
        if (isset($options['pdf_margins']) && is_array($options['pdf_margins'])) {
            $top = $options['pdf_margins']['top'];
            $right = $options['pdf_margins']['right'];
            $bottom = $options['pdf_margins']['bottom'];
            $left = $options['pdf_margins']['left'];
        } else {
            $top = isset($options['margin_top']) ? $options['margin_top'] : 20;
            $right = isset($options['margin_right']) ? $options['margin_right'] : 15;
            $bottom = isset($options['margin_bottom']) ? $options['margin_bottom'] : 20;
            $left = isset($options['margin_left']) ? $options['margin_left'] : 15;
        }
        ?>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; max-width: 400px;">
            <div>
                <label><?php _e('Superior', 'ull-normativa'); ?></label>
                <input type="number" name="ull_pdf_options[margin_top]" value="<?php echo esc_attr($top); ?>" min="0" max="100" step="1">
            </div>
            <div>
                <label><?php _e('Derecho', 'ull-normativa'); ?></label>
                <input type="number" name="ull_pdf_options[margin_right]" value="<?php echo esc_attr($right); ?>" min="0" max="100" step="1">
            </div>
            <div>
                <label><?php _e('Inferior', 'ull-normativa'); ?></label>
                <input type="number" name="ull_pdf_options[margin_bottom]" value="<?php echo esc_attr($bottom); ?>" min="0" max="100" step="1">
            </div>
            <div>
                <label><?php _e('Izquierdo', 'ull-normativa'); ?></label>
                <input type="number" name="ull_pdf_options[margin_left]" value="<?php echo esc_attr($left); ?>" min="0" max="100" step="1">
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar campo de tabla de contenidos
     */
    public function render_toc_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['show_toc']) ? $options['show_toc'] : true;
        ?>
        <label>
            <input type="checkbox" name="ull_pdf_options[show_toc]" value="1" <?php checked($value, true); ?>>
            <?php _e('Incluir tabla de contenidos en el PDF', 'ull-normativa'); ?>
        </label>
        <?php
    }
    
    /**
     * Renderizar campo de números de página
     */
    public function render_page_numbers_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['show_page_numbers']) ? $options['show_page_numbers'] : true;
        ?>
        <label>
            <input type="checkbox" name="ull_pdf_options[show_page_numbers]" value="1" <?php checked($value, true); ?>>
            <?php _e('Mostrar números de página', 'ull-normativa'); ?>
        </label>
        <?php
    }
    
    /**
     * Renderizar campo de texto de encabezado
     */
    public function render_header_text_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['header_text']) ? $options['header_text'] : __('Universidad de La Laguna', 'ull-normativa');
        ?>
        <input type="text" name="ull_pdf_options[header_text]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Texto a mostrar en el encabezado de cada página.', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de texto de pie de página
     */
    public function render_footer_text_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['footer_text']) ? $options['footer_text'] : __('Universidad de La Laguna - Normativa', 'ull-normativa');
        ?>
        <input type="text" name="ull_pdf_options[footer_text]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Texto a mostrar en el pie de página de cada página.', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de logo de cabecera
     */
    public function render_header_logo_field() {
        $options = get_option('ull_pdf_options', array());
        $logo_id = isset($options['header_logo']) ? $options['header_logo'] : '';
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        ?>
        <div class="ull-pdf-logo-upload">
            <input type="hidden" name="ull_pdf_options[header_logo]" id="ull_pdf_header_logo" value="<?php echo esc_attr($logo_id); ?>">
            
            <div id="ull-pdf-logo-preview" style="margin-bottom: 10px; <?php echo $logo_url ? '' : 'display:none;'; ?>">
                <img src="<?php echo esc_url($logo_url); ?>" style="max-height: 80px; border: 1px solid #ddd; padding: 5px; background: #f9f9f9;">
                <br>
                <button type="button" class="button" id="ull-pdf-remove-logo" style="margin-top: 5px;">
                    <?php _e('Eliminar logo', 'ull-normativa'); ?>
                </button>
            </div>
            
            <button type="button" class="button" id="ull-pdf-upload-logo">
                <?php _e('Seleccionar logo', 'ull-normativa'); ?>
            </button>
            
            <p class="description">
                <?php _e('Logo a mostrar en la cabecera del PDF. Tamaño recomendado: altura máxima 80px.', 'ull-normativa'); ?>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            $('#ull-pdf-upload-logo').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Seleccionar logo', 'ull-normativa'); ?>',
                    button: {
                        text: '<?php _e('Usar este logo', 'ull-normativa'); ?>'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#ull_pdf_header_logo').val(attachment.id);
                    $('#ull-pdf-logo-preview img').attr('src', attachment.url);
                    $('#ull-pdf-logo-preview').show();
                });
                
                mediaUploader.open();
            });
            
            $('#ull-pdf-remove-logo').on('click', function(e) {
                e.preventDefault();
                $('#ull_pdf_header_logo').val('');
                $('#ull-pdf-logo-preview').hide();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Renderizar campo de color de fondo de cabecera
     */
    public function render_header_bg_color_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['header_bg_color']) ? $options['header_bg_color'] : '#003366';
        ?>
        <input type="color" name="ull_pdf_options[header_bg_color]" value="<?php echo esc_attr($value); ?>">
        <p class="description"><?php _e('Color de fondo de la cabecera del PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de color de texto de cabecera
     */
    public function render_header_text_color_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['header_text_color']) ? $options['header_text_color'] : '#ffffff';
        ?>
        <input type="color" name="ull_pdf_options[header_text_color]" value="<?php echo esc_attr($value); ?>">
        <p class="description">
            <?php _e('Color del texto en la cabecera del PDF.', 'ull-normativa'); ?>
            <strong><?php _e('⚠️ Asegúrate de que contraste con el color de fondo de la cabecera.', 'ull-normativa'); ?></strong>
        </p>
        <?php
    }
    
    /**
     * Renderizar campo de color de títulos
     */
    public function render_title_color_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['title_color']) ? $options['title_color'] : '#003366';
        ?>
        <input type="color" name="ull_pdf_options[title_color]" value="<?php echo esc_attr($value); ?>">
        <p class="description"><?php _e('Color de los títulos y encabezados en el contenido del PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de color de fondo de índices
     */
    public function render_index_bg_color_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['index_bg_color']) ? $options['index_bg_color'] : '#f9f9f9';
        ?>
        <input type="color" name="ull_pdf_options[index_bg_color]" value="<?php echo esc_attr($value); ?>">
        <p class="description"><?php _e('Color de fondo de los índices y tablas de contenido en el PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de color de títulos de normas
     */
    public function render_norma_title_color_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['norma_title_color']) ? $options['norma_title_color'] : '#003366';
        ?>
        <input type="color" name="ull_pdf_options[norma_title_color]" value="<?php echo esc_attr($value); ?>">
        <p class="description"><?php _e('Color de los títulos de las normas en PDFs de códigos (colecciones).', 'ull-normativa'); ?></p>
        <?php
    }
    
    /**
     * Renderizar campo de mostrar fecha de generación
     */
    public function render_show_generation_date_field() {
        $options = get_option('ull_pdf_options', array());
        $value = isset($options['show_generation_date']) ? $options['show_generation_date'] : true;
        ?>
        <label>
            <input type="checkbox" name="ull_pdf_options[show_generation_date]" value="1" <?php checked($value, true); ?>>
            <?php _e('Mostrar fecha y hora de generación en el pie de página', 'ull-normativa'); ?>
        </label>
        <?php
    }
    
    /**
     * Cargar assets de admin
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'normativa_page_ull-pdf-settings') {
            return;
        }
        
        // Enqueue media uploader para el logo
        wp_enqueue_media();
        
        wp_enqueue_style(
            'ull-pdf-settings',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/css/pdf-settings.css',
            array(),
            ULL_NORMATIVA_VERSION
        );
        
        wp_enqueue_script(
            'ull-pdf-settings',
            ULL_NORMATIVA_PLUGIN_URL . 'assets/js/pdf-settings.js',
            array('jquery'),
            ULL_NORMATIVA_VERSION,
            true
        );
        
        wp_localize_script('ull-pdf-settings', 'ullPdfSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ull_pdf_settings'),
            'strings' => array(
                'installing' => __('Instalando...', 'ull-normativa'),
                'uninstalling' => __('Desinstalando...', 'ull-normativa'),
                'checking' => __('Verificando...', 'ull-normativa'),
                'install_success' => __('DOMPDF instalado correctamente.', 'ull-normativa'),
                'install_error' => __('Error al instalar DOMPDF.', 'ull-normativa'),
                'uninstall_success' => __('DOMPDF desinstalado correctamente.', 'ull-normativa'),
                'uninstall_error' => __('Error al desinstalar DOMPDF.', 'ull-normativa'),
                'confirm_uninstall' => __('¿Está seguro de que desea desinstalar DOMPDF?', 'ull-normativa'),
            )
        ));
    }
    
    /**
     * AJAX: Instalar dompdf
     */
    public function ajax_install_dompdf() {
        check_ajax_referer('ull_pdf_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'ull-normativa')));
        }
        
        $result = $this->installer->install();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Desinstalar dompdf
     */
    public function ajax_uninstall_dompdf() {
        check_ajax_referer('ull_pdf_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'ull-normativa')));
        }
        
        $success = $this->installer->uninstall();
        
        if ($success) {
            wp_send_json_success(array('message' => __('DOMPDF desinstalado correctamente.', 'ull-normativa')));
        } else {
            wp_send_json_error(array('message' => __('Error al desinstalar DOMPDF.', 'ull-normativa')));
        }
    }
    
    /**
     * AJAX: Verificar estado
     */
    public function ajax_check_status() {
        check_ajax_referer('ull_pdf_settings', 'nonce');
        
        $status = $this->installer->get_status();
        wp_send_json_success($status);
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        $status = $this->installer->get_status();
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración PDF', 'ull-normativa'); ?></h1>
            
            <!-- Estado de DOMPDF -->
            <div class="ull-dompdf-status-card" id="ull-dompdf-status">
                <h2><?php _e('Estado de DOMPDF', 'ull-normativa'); ?></h2>
                
                <?php if ($status['installed']): ?>
                    <div class="notice notice-success inline">
                        <p>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('DOMPDF está instalado y listo para generar PDFs de alta calidad.', 'ull-normativa'); ?>
                        </p>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Versión:', 'ull-normativa'); ?></th>
                            <td><code><?php echo esc_html($status['version']); ?></code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Ubicación:', 'ull-normativa'); ?></th>
                            <td><code><?php echo esc_html($status['install_dir']); ?></code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Tamaño:', 'ull-normativa'); ?></th>
                            <td><?php echo esc_html(ULL_DOMPDF_Installer::format_bytes($status['size'])); ?></td>
                        </tr>
                    </table>
                    
                    <p>
                        <button type="button" class="button button-secondary" id="ull-uninstall-dompdf">
                            <?php _e('Desinstalar DOMPDF', 'ull-normativa'); ?>
                        </button>
                    </p>
                    
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('DOMPDF no está instalado. Se generarán PDFs básicos mediante impresión HTML.', 'ull-normativa'); ?>
                        </p>
                    </div>
                    
                    <p><?php _e('Para generar PDFs de alta calidad con mejor formato y funcionalidades avanzadas, instale DOMPDF.', 'ull-normativa'); ?></p>
                    
                    <div class="ull-install-info">
                        <h4><?php _e('Información de instalación:', 'ull-normativa'); ?></h4>
                        <ul>
                            <li><?php _e('Se descargará desde GitHub (aprox. 5-10 MB)', 'ull-normativa'); ?></li>
                            <li><?php printf(__('Se instalará en: <code>%s</code>', 'ull-normativa'), esc_html($status['install_dir'])); ?></li>
                            <li><?php _e('El proceso puede tardar 1-2 minutos', 'ull-normativa'); ?></li>
                            <li><?php _e('Requiere conexión a internet', 'ull-normativa'); ?></li>
                        </ul>
                    </div>
                    
                    <p>
                        <button type="button" class="button button-primary" id="ull-install-dompdf">
                            <?php _e('Instalar DOMPDF', 'ull-normativa'); ?>
                        </button>
                        <span class="spinner" id="ull-install-spinner"></span>
                    </p>
                <?php endif; ?>
            </div>
            
            <hr>
            
            <!-- Formulario de configuración -->
            <form method="post" action="options.php">
                <?php
                settings_fields('ull_pdf_settings');
                do_settings_sections('ull_pdf_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
