<?php
/**
 * Configuración de Tabla de Contenidos
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_TOC_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'), 20);
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Añadir página de configuración
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=norma',
            __('Configuración Tabla de Contenidos', 'ull-normativa'),
            __('⚙️ Config. TOC', 'ull-normativa'),
            'manage_options',
            'ull-normativa-toc-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        // Grupo de opciones
        register_setting('ull_toc_settings', 'ull_toc_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        // Sección: Comportamiento
        add_settings_section(
            'ull_toc_behavior',
            __('Comportamiento del Índice', 'ull-normativa'),
            array($this, 'section_behavior_callback'),
            'ull_toc_settings'
        );
        
        // Campo: Contraer por defecto
        add_settings_field(
            'contraer_defecto',
            __('Contraer por Defecto', 'ull-normativa'),
            array($this, 'field_contraer_callback'),
            'ull_toc_settings',
            'ull_toc_behavior'
        );
        
        // Campo: Estado inicial
        add_settings_field(
            'inicio_defecto',
            __('Estado Inicial', 'ull-normativa'),
            array($this, 'field_inicio_callback'),
            'ull_toc_settings',
            'ull_toc_behavior'
        );
        
        // Sección: Apariencia
        add_settings_section(
            'ull_toc_appearance',
            __('Apariencia del Índice', 'ull-normativa'),
            array($this, 'section_appearance_callback'),
            'ull_toc_settings'
        );
        
        // Campo: Título por defecto
        add_settings_field(
            'titulo_defecto',
            __('Título por Defecto', 'ull-normativa'),
            array($this, 'field_titulo_callback'),
            'ull_toc_settings',
            'ull_toc_appearance'
        );
        
        // Campo: Estilo por defecto
        add_settings_field(
            'estilo_defecto',
            __('Estilo por Defecto', 'ull-normativa'),
            array($this, 'field_estilo_callback'),
            'ull_toc_settings',
            'ull_toc_appearance'
        );
        
        // Campo: Niveles de encabezados
        add_settings_field(
            'niveles_defecto',
            __('Niveles de Encabezados', 'ull-normativa'),
            array($this, 'field_niveles_callback'),
            'ull_toc_settings',
            'ull_toc_appearance'
        );
    }
    
    /**
     * Sanitizar opciones
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        // Contraer
        $sanitized['contraer_defecto'] = isset($input['contraer_defecto']) 
            ? sanitize_text_field($input['contraer_defecto']) 
            : 'siempre';
        
        // Inicio
        $sanitized['inicio_defecto'] = isset($input['inicio_defecto']) 
            ? sanitize_text_field($input['inicio_defecto']) 
            : 'colapsado';
        
        // Título
        $sanitized['titulo_defecto'] = isset($input['titulo_defecto']) 
            ? sanitize_text_field($input['titulo_defecto']) 
            : __('Índice de contenidos', 'ull-normativa');
        
        // Estilo
        $sanitized['estilo_defecto'] = isset($input['estilo_defecto']) 
            ? sanitize_text_field($input['estilo_defecto']) 
            : 'lista';
        
        // Niveles
        $sanitized['niveles_defecto'] = isset($input['niveles_defecto']) 
            ? sanitize_text_field($input['niveles_defecto']) 
            : '2,3,4';
        
        return $sanitized;
    }
    
    /**
     * Callbacks de secciones
     */
    public function section_behavior_callback() {
        echo '<p>' . __('Configura cómo se comporta la tabla de contenidos por defecto.', 'ull-normativa') . '</p>';
    }
    
    public function section_appearance_callback() {
        echo '<p>' . __('Personaliza la apariencia de la tabla de contenidos.', 'ull-normativa') . '</p>';
    }
    
    /**
     * Callbacks de campos
     */
    public function field_contraer_callback() {
        $options = get_option('ull_toc_options', array());
        $value = isset($options['contraer_defecto']) ? $options['contraer_defecto'] : 'siempre';
        ?>
        <select name="ull_toc_options[contraer_defecto]" id="contraer_defecto">
            <option value="siempre" <?php selected($value, 'siempre'); ?>>
                <?php _e('Siempre mostrar botón contraer/expandir', 'ull-normativa'); ?>
            </option>
            <option value="auto" <?php selected($value, 'auto'); ?>>
                <?php _e('Automático (solo si más de 10 elementos)', 'ull-normativa'); ?>
            </option>
            <option value="no" <?php selected($value, 'no'); ?>>
                <?php _e('Nunca (índice siempre expandido)', 'ull-normativa'); ?>
            </option>
        </select>
        <p class="description">
            <?php _e('Determina si el índice puede contraerse/expandirse.', 'ull-normativa'); ?>
        </p>
        <?php
    }
    
    public function field_inicio_callback() {
        $options = get_option('ull_toc_options', array());
        $value = isset($options['inicio_defecto']) ? $options['inicio_defecto'] : 'colapsado';
        ?>
        <select name="ull_toc_options[inicio_defecto]" id="inicio_defecto">
            <option value="colapsado" <?php selected($value, 'colapsado'); ?>>
                <?php _e('Colapsado (oculto)', 'ull-normativa'); ?>
            </option>
            <option value="expandido" <?php selected($value, 'expandido'); ?>>
                <?php _e('Expandido (visible)', 'ull-normativa'); ?>
            </option>
        </select>
        <p class="description">
            <?php _e('Estado inicial cuando se carga la página.', 'ull-normativa'); ?>
        </p>
        <?php
    }
    
    public function field_titulo_callback() {
        $options = get_option('ull_toc_options', array());
        $value = isset($options['titulo_defecto']) ? $options['titulo_defecto'] : __('Índice de contenidos', 'ull-normativa');
        ?>
        <input type="text" 
               name="ull_toc_options[titulo_defecto]" 
               id="titulo_defecto" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description">
            <?php _e('Título que aparece en la tabla de contenidos.', 'ull-normativa'); ?>
        </p>
        <?php
    }
    
    public function field_estilo_callback() {
        $options = get_option('ull_toc_options', array());
        $value = isset($options['estilo_defecto']) ? $options['estilo_defecto'] : 'lista';
        ?>
        <select name="ull_toc_options[estilo_defecto]" id="estilo_defecto">
            <option value="lista" <?php selected($value, 'lista'); ?>>
                <?php _e('Lista con viñetas', 'ull-normativa'); ?>
            </option>
            <option value="numerado" <?php selected($value, 'numerado'); ?>>
                <?php _e('Lista numerada', 'ull-normativa'); ?>
            </option>
        </select>
        <p class="description">
            <?php _e('Estilo visual de la lista de contenidos.', 'ull-normativa'); ?>
        </p>
        <?php
    }
    
    public function field_niveles_callback() {
        $options = get_option('ull_toc_options', array());
        $value = isset($options['niveles_defecto']) ? $options['niveles_defecto'] : '2,3,4';
        ?>
        <fieldset>
            <label>
                <input type="checkbox" name="ull_toc_niveles[]" value="1" 
                    <?php echo (strpos($value, '1') !== false) ? 'checked' : ''; ?>>
                H1
            </label><br>
            <label>
                <input type="checkbox" name="ull_toc_niveles[]" value="2" 
                    <?php echo (strpos($value, '2') !== false) ? 'checked' : ''; ?>>
                H2
            </label><br>
            <label>
                <input type="checkbox" name="ull_toc_niveles[]" value="3" 
                    <?php echo (strpos($value, '3') !== false) ? 'checked' : ''; ?>>
                H3
            </label><br>
            <label>
                <input type="checkbox" name="ull_toc_niveles[]" value="4" 
                    <?php echo (strpos($value, '4') !== false) ? 'checked' : ''; ?>>
                H4
            </label><br>
            <label>
                <input type="checkbox" name="ull_toc_niveles[]" value="5" 
                    <?php echo (strpos($value, '5') !== false) ? 'checked' : ''; ?>>
                H5
            </label><br>
            <label>
                <input type="checkbox" name="ull_toc_niveles[]" value="6" 
                    <?php echo (strpos($value, '6') !== false) ? 'checked' : ''; ?>>
                H6
            </label>
        </fieldset>
        <input type="hidden" 
               name="ull_toc_options[niveles_defecto]" 
               id="niveles_defecto" 
               value="<?php echo esc_attr($value); ?>">
        <p class="description">
            <?php _e('Qué niveles de encabezados incluir en el índice (recomendado: H2, H3, H4).', 'ull-normativa'); ?>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('input[name="ull_toc_niveles[]"]').on('change', function() {
                var niveles = [];
                $('input[name="ull_toc_niveles[]"]:checked').each(function() {
                    niveles.push($(this).val());
                });
                $('#niveles_defecto').val(niveles.join(','));
            });
        });
        </script>
        <?php
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Guardar mensaje
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'ull_toc_messages',
                'ull_toc_message',
                __('Configuración guardada correctamente.', 'ull-normativa'),
                'updated'
            );
        }
        
        settings_errors('ull_toc_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ull-settings-intro" style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h2>📋 Configuración de Tabla de Contenidos</h2>
                <p>Personaliza cómo se muestra la tabla de contenidos en tus normativas.</p>
                <p><strong>Nota:</strong> Estas son las opciones por defecto. Puedes sobrescribirlas usando parámetros en el shortcode:</p>
                <code>[ull_tabla_contenidos contraer="no" inicio="expandido"]</code>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('ull_toc_settings');
                do_settings_sections('ull_toc_settings');
                submit_button(__('Guardar Configuración', 'ull-normativa'));
                ?>
            </form>
            
            <div class="ull-settings-help" style="background: #f0f6fc; padding: 20px; margin: 20px 0; border-left: 4px solid #0066cc;">
                <h2>💡 Ejemplos de Uso</h2>
                
                <h3>Usar configuración por defecto:</h3>
                <pre style="background: #fff; padding: 10px; border-radius: 4px;">[ull_tabla_contenidos]</pre>
                
                <h3>Personalizar para una norma específica:</h3>
                <pre style="background: #fff; padding: 10px; border-radius: 4px;">[ull_tabla_contenidos contraer="no" titulo="Índice" estilo="numerado"]</pre>
                
                <h3>Solo encabezados H2 y H3:</h3>
                <pre style="background: #fff; padding: 10px; border-radius: 4px;">[ull_tabla_contenidos niveles="2,3"]</pre>
                
                <h3>Parámetros disponibles:</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><code>titulo</code> - Título del índice</li>
                    <li><code>contraer</code> - siempre / auto / no</li>
                    <li><code>inicio</code> - expandido / colapsado</li>
                    <li><code>estilo</code> - lista / numerado</li>
                    <li><code>niveles</code> - 1,2,3,4,5,6</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Obtener opciones con valores por defecto
     */
    public static function get_options() {
        $defaults = array(
            'contraer_defecto' => 'siempre',
            'inicio_defecto' => 'colapsado',
            'titulo_defecto' => __('Índice de contenidos', 'ull-normativa'),
            'estilo_defecto' => 'lista',
            'niveles_defecto' => '2,3,4',
        );
        
        $options = get_option('ull_toc_options', array());
        
        return wp_parse_args($options, $defaults);
    }
}

new ULL_TOC_Settings();
