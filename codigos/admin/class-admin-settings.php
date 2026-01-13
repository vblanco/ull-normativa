<?php
/**
 * Configuración del plugin ULL Códigos
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Codigos_Admin_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=codigo',
            __('Configuración', 'ull-normativa'),
            __('Configuración', 'ull-normativa'),
            'manage_options',
            'ull-codigos-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        // Sección General
        add_settings_section(
            'ull_codigos_general',
            __('Configuración General', 'ull-normativa'),
            null,
            'ull-codigos-settings'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_post_type');
        add_settings_field(
            'ull_codigos_post_type',
            __('Tipo de contenido de normas', 'ull-normativa'),
            array($this, 'render_post_type_field'),
            'ull-codigos-settings',
            'ull_codigos_general'
        );
        
        // Sección PDF
        add_settings_section(
            'ull_codigos_pdf',
            __('Configuración de Exportación PDF', 'ull-normativa'),
            array($this, 'render_pdf_section_description'),
            'ull-codigos-settings'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_pdf_logo');
        add_settings_field(
            'ull_codigos_pdf_logo',
            __('Logo para PDF', 'ull-normativa'),
            array($this, 'render_logo_field'),
            'ull-codigos-settings',
            'ull_codigos_pdf'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_pdf_header_color', array(
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#003366',
        ));
        add_settings_field(
            'ull_codigos_pdf_header_color',
            __('Color de cabecera', 'ull-normativa'),
            array($this, 'render_header_color_field'),
            'ull-codigos-settings',
            'ull_codigos_pdf'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_pdf_header_text_color', array(
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#ffffff',
        ));
        add_settings_field(
            'ull_codigos_pdf_header_text_color',
            __('Color de texto de cabecera', 'ull-normativa'),
            array($this, 'render_header_text_color_field'),
            'ull-codigos-settings',
            'ull_codigos_pdf'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_pdf_title_color', array(
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#003366',
        ));
        add_settings_field(
            'ull_codigos_pdf_title_color',
            __('Color de títulos', 'ull-normativa'),
            array($this, 'render_title_color_field'),
            'ull-codigos-settings',
            'ull_codigos_pdf'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_pdf_header_text');
        add_settings_field(
            'ull_codigos_pdf_header_text',
            __('Texto de cabecera', 'ull-normativa'),
            array($this, 'render_header_text_field'),
            'ull-codigos-settings',
            'ull_codigos_pdf'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_pdf_footer_text');
        add_settings_field(
            'ull_codigos_pdf_footer_text',
            __('Texto de pie de página', 'ull-normativa'),
            array($this, 'render_footer_text_field'),
            'ull-codigos-settings',
            'ull_codigos_pdf'
        );
        
        register_setting('ull_codigos_settings', 'ull_codigos_pdf_filename_pattern');
        add_settings_field(
            'ull_codigos_pdf_filename_pattern',
            __('Patrón de nombre de archivo', 'ull-normativa'),
            array($this, 'render_filename_field'),
            'ull-codigos-settings',
            'ull_codigos_pdf'
        );
    }
    
    public function render_pdf_section_description() {
        echo '<p>' . __('Configure la apariencia de los PDFs exportados. Los colores afectan a la cabecera, títulos y elementos destacados.', 'ull-normativa') . '</p>';
    }
    
    public function render_post_type_field() {
        $value = get_option('ull_codigos_post_type', 'norma');
        ?>
        <input type="text" name="ull_codigos_post_type" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('El slug del post type de normativas (por defecto: norma)', 'ull-normativa'); ?></p>
        <?php
    }
    
    public function render_logo_field() {
        $logo_id = get_option('ull_codigos_pdf_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        ?>
        <div class="ull-codigos-logo-field">
            <input type="hidden" name="ull_codigos_pdf_logo" id="ull_codigos_pdf_logo" value="<?php echo esc_attr($logo_id); ?>">
            
            <div id="ull-codigos-logo-preview" style="margin-bottom: 10px; <?php echo $logo_url ? '' : 'display:none;'; ?>">
                <img src="<?php echo esc_url($logo_url); ?>" style="max-height: 80px; border: 1px solid #ddd; padding: 5px; background: #f9f9f9;">
                <br>
                <button type="button" class="button" id="ull-codigos-remove-logo" style="margin-top: 5px;">
                    <?php _e('Eliminar logo', 'ull-normativa'); ?>
                </button>
            </div>
            
            <button type="button" class="button button-primary" id="ull-codigos-select-logo">
                <?php _e('Seleccionar imagen', 'ull-normativa'); ?>
            </button>
            
            <p class="description"><?php _e('Logo que aparecerá en la cabecera del PDF. Tamaño recomendado: 200x60 píxeles.', 'ull-normativa'); ?></p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            $('#ull-codigos-select-logo').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Seleccionar logo', 'ull-normativa'); ?>',
                    button: { text: '<?php _e('Usar esta imagen', 'ull-normativa'); ?>' },
                    library: { type: 'image' },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#ull_codigos_pdf_logo').val(attachment.id);
                    $('#ull-codigos-logo-preview img').attr('src', attachment.url);
                    $('#ull-codigos-logo-preview').show();
                });
                
                mediaUploader.open();
            });
            
            $('#ull-codigos-remove-logo').on('click', function(e) {
                e.preventDefault();
                $('#ull_codigos_pdf_logo').val('');
                $('#ull-codigos-logo-preview').hide();
            });
        });
        </script>
        <?php
    }
    
    public function render_header_color_field() {
        $value = get_option('ull_codigos_pdf_header_color', '#003366');
        ?>
        <input type="color" name="ull_codigos_pdf_header_color" value="<?php echo esc_attr($value); ?>">
        <input type="text" value="<?php echo esc_attr($value); ?>" class="small-text" readonly style="margin-left: 10px;">
        <p class="description"><?php _e('Color de fondo de la cabecera del PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    public function render_header_text_color_field() {
        $value = get_option('ull_codigos_pdf_header_text_color', '#ffffff');
        ?>
        <input type="color" name="ull_codigos_pdf_header_text_color" value="<?php echo esc_attr($value); ?>">
        <input type="text" value="<?php echo esc_attr($value); ?>" class="small-text" readonly style="margin-left: 10px;">
        <p class="description"><?php _e('Color del texto en la cabecera del PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    public function render_title_color_field() {
        $value = get_option('ull_codigos_pdf_title_color', '#003366');
        ?>
        <input type="color" name="ull_codigos_pdf_title_color" value="<?php echo esc_attr($value); ?>">
        <input type="text" value="<?php echo esc_attr($value); ?>" class="small-text" readonly style="margin-left: 10px;">
        <p class="description"><?php _e('Color de los títulos y encabezados en el PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    public function render_header_text_field() {
        $value = get_option('ull_codigos_pdf_header_text', get_bloginfo('name'));
        ?>
        <input type="text" name="ull_codigos_pdf_header_text" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Texto que aparece en la cabecera del PDF (normalmente el nombre de la institución).', 'ull-normativa'); ?></p>
        <?php
    }
    
    public function render_footer_text_field() {
        $value = get_option('ull_codigos_pdf_footer_text', '© ' . date('Y') . ' ' . get_bloginfo('name'));
        ?>
        <input type="text" name="ull_codigos_pdf_footer_text" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Texto del pie de página del PDF.', 'ull-normativa'); ?></p>
        <?php
    }
    
    public function render_filename_field() {
        $value = get_option('ull_codigos_pdf_filename_pattern', '{slug}');
        ?>
        <input type="text" name="ull_codigos_pdf_filename_pattern" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('Patrón para el nombre del archivo PDF. Variables disponibles:', 'ull-normativa'); ?><br>
            <code>{id}</code> - ID del código<br>
            <code>{slug}</code> - Slug del código<br>
            <code>{titulo}</code> - Título del código
        </p>
        <?php
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Cargar media uploader
        wp_enqueue_media();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ull_codigos_settings');
                do_settings_sections('ull-codigos-settings');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Vista previa de colores', 'ull-normativa'); ?></h2>
            <div id="ull-codigos-preview" style="max-width: 600px; border: 1px solid #ddd; margin-top: 20px;">
                <?php
                $header_color = get_option('ull_codigos_pdf_header_color', '#003366');
                $header_text_color = get_option('ull_codigos_pdf_header_text_color', '#ffffff');
                $title_color = get_option('ull_codigos_pdf_title_color', '#003366');
                $header_text = get_option('ull_codigos_pdf_header_text', get_bloginfo('name'));
                $logo_id = get_option('ull_codigos_pdf_logo', '');
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                ?>
                <div style="background: <?php echo esc_attr($header_color); ?>; color: <?php echo esc_attr($header_text_color); ?>; padding: 20px; text-align: center;">
                    <?php if ($logo_url) : ?>
                        <img src="<?php echo esc_url($logo_url); ?>" style="max-height: 50px; margin-bottom: 10px;"><br>
                    <?php endif; ?>
                    <span style="font-size: 14px;"><?php echo esc_html($header_text); ?></span>
                </div>
                <div style="padding: 20px;">
                    <h2 style="color: <?php echo esc_attr($title_color); ?>; margin-top: 0; padding-bottom: 10px; border-bottom: 3px solid <?php echo esc_attr($header_color); ?>;">
                        <?php _e('Título del Código', 'ull-normativa'); ?>
                    </h2>
                    <div style="background: #f5f5f5; padding: 15px; border-left: 4px solid <?php echo esc_attr($header_color); ?>; margin-bottom: 20px;">
                        <p style="margin: 0;"><?php _e('Descripción del código...', 'ull-normativa'); ?></p>
                    </div>
                    <h3 style="color: <?php echo esc_attr($title_color); ?>;">
                        <?php _e('Título de Norma', 'ull-normativa'); ?>
                        <span style="background: <?php echo esc_attr($header_color); ?>; color: <?php echo esc_attr($header_text_color); ?>; padding: 2px 8px; font-size: 10px; border-radius: 3px; margin-left: 10px;">NOR-0001</span>
                    </h3>
                    <p style="color: #666;"><?php _e('Contenido de ejemplo de la norma...', 'ull-normativa'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}

new ULL_Codigos_Admin_Settings();
