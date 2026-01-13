<?php
/**
 * Meta Boxes para el editor de normas
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Meta_Boxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_norma', array($this, 'save_meta_boxes'), 10, 2);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'ull_norma_datos',
            __('Datos de la Norma', 'ull-normativa'),
            array($this, 'render_datos_meta_box'),
            'norma',
            'normal',
            'high'
        );
        
        if (get_option('ull_normativa_enable_versions', true)) {
            add_meta_box(
                'ull_norma_versiones',
                __('Control de Versiones', 'ull-normativa'),
                array($this, 'render_versiones_meta_box'),
                'norma',
                'normal',
                'default'
            );
        }
        
        if (get_option('ull_normativa_enable_relations', true)) {
            add_meta_box(
                'ull_norma_relaciones',
                __('Relaciones con otras Normas', 'ull-normativa'),
                array($this, 'render_relaciones_meta_box'),
                'norma',
                'normal',
                'default'
            );
        }
        
        add_meta_box(
            'ull_norma_documentos',
            __('Documentos Adjuntos', 'ull-normativa'),
            array($this, 'render_documentos_meta_box'),
            'norma',
            'side',
            'default'
        );
    }
    
    public function render_datos_meta_box($post) {
        wp_nonce_field('ull_norma_meta_box', 'ull_norma_nonce');
        
        $fields = ULL_Post_Types::get_meta_fields();
        ?>
        <div class="ull-meta-box-container">
            <div class="ull-meta-grid">
                <?php foreach ($fields as $key => $field) : 
                    if (in_array($field['type'], array('wysiwyg'))) continue;
                    $value = get_post_meta($post->ID, '_' . $key, true);
                    $is_readonly = !empty($field['readonly']);
                ?>
                <div class="ull-meta-field <?php echo $field['type'] === 'textarea' ? 'ull-full-width' : ''; ?>">
                    <label for="<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($field['label']); ?>
                        <?php if (!empty($field['required'])) : ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                        <?php if ($is_readonly) : ?>
                            <span class="ull-auto-badge"><?php _e('(Automático)', 'ull-normativa'); ?></span>
                        <?php endif; ?>
                    </label>
                    
                    <?php if ($field['type'] === 'text' || $field['type'] === 'url') : ?>
                        <input type="<?php echo $field['type'] === 'url' ? 'url' : 'text'; ?>" 
                               id="<?php echo esc_attr($key); ?>" 
                               name="<?php echo esc_attr($key); ?>" 
                               value="<?php echo esc_attr($value); ?>"
                               class="widefat<?php echo $is_readonly ? ' ull-readonly' : ''; ?>"
                               <?php echo $is_readonly ? 'readonly' : ''; ?>>
                        <?php if ($key === 'numero_norma' && empty($value)) : ?>
                            <p class="description ull-notice"><?php _e('El número se generará automáticamente al seleccionar un tipo de norma y guardar.', 'ull-normativa'); ?></p>
                        <?php endif; ?>
                    <?php elseif ($field['type'] === 'date') : ?>
                        <input type="date" 
                               id="<?php echo esc_attr($key); ?>" 
                               name="<?php echo esc_attr($key); ?>" 
                               value="<?php echo esc_attr($value); ?>"
                               class="widefat">
                    <?php elseif ($field['type'] === 'select') : ?>
                        <select id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" class="widefat">
                            <option value=""><?php _e('Seleccionar...', 'ull-normativa'); ?></option>
                            <?php foreach ($field['options'] as $opt_value => $opt_label) : ?>
                                <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($value, $opt_value); ?>>
                                    <?php echo esc_html($opt_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['type'] === 'textarea') : ?>
                        <textarea id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" class="widefat" rows="4"><?php echo esc_textarea($value); ?></textarea>
                    <?php endif; ?>
                    
                    <?php if (!empty($field['description']) && $key !== 'numero_norma') : ?>
                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
            .ull-meta-box-container { padding: 10px 0; }
            .ull-meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
            .ull-meta-field { margin-bottom: 10px; }
            .ull-meta-field.ull-full-width { grid-column: 1 / -1; }
            .ull-meta-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            .ull-meta-field .required { color: #dc3232; }
            .ull-meta-field .ull-auto-badge { font-weight: normal; font-size: 11px; color: #0073aa; }
            .ull-meta-field .ull-readonly { background: #f0f0f0; color: #333; font-weight: bold; }
            .ull-meta-field .ull-notice { color: #0073aa; font-style: italic; }
            @media (max-width: 782px) { .ull-meta-grid { grid-template-columns: 1fr; } }
        </style>
        <?php
    }
    
    public function render_versiones_meta_box($post) {
        $version_control = new ULL_Version_Control();
        $versions = $version_control->get_versions($post->ID);
        ?>
        <div class="ull-versiones-container">
            <div class="ull-nueva-version">
                <h4><?php _e('Crear Nueva Versión', 'ull-normativa'); ?></h4>
                <p>
                    <label for="nueva_version"><?php _e('Número de Versión:', 'ull-normativa'); ?></label>
                    <input type="text" id="nueva_version" name="nueva_version" value="" placeholder="ej: 2.0" style="width:100px;">
                </p>
                <p>
                    <label for="resumen_cambios"><?php _e('Resumen de Cambios:', 'ull-normativa'); ?></label>
                    <textarea id="resumen_cambios" name="resumen_cambios" rows="2" style="width:100%;"></textarea>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="crear_version" value="1">
                        <?php _e('Guardar versión actual antes de los cambios', 'ull-normativa'); ?>
                    </label>
                </p>
            </div>
            
            <div class="ull-historial-versiones">
                <h4><?php _e('Historial de Versiones', 'ull-normativa'); ?></h4>
                <?php if (empty($versions)) : ?>
                    <p><?php _e('No hay versiones anteriores registradas.', 'ull-normativa'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Versión', 'ull-normativa'); ?></th>
                                <th><?php _e('Fecha', 'ull-normativa'); ?></th>
                                <th><?php _e('Estado', 'ull-normativa'); ?></th>
                                <th><?php _e('Acciones', 'ull-normativa'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($versions as $version) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($version->version_number); ?></strong></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($version->version_date))); ?></td>
                                    <td>
                                        <?php if ($version->is_current) : ?>
                                            <span class="ull-badge ull-badge-success"><?php _e('Vigente', 'ull-normativa'); ?></span>
                                        <?php else : ?>
                                            <span class="ull-badge"><?php _e('Histórica', 'ull-normativa'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="#" class="ull-view-version" data-version-id="<?php echo esc_attr($version->id); ?>">
                                            <?php _e('Ver', 'ull-normativa'); ?>
                                        </a>
                                        <?php if (!$version->is_current) : ?>
                                            | <a href="#" class="ull-restore-version" data-version-id="<?php echo esc_attr($version->id); ?>">
                                                <?php _e('Restaurar', 'ull-normativa'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function render_relaciones_meta_box($post) {
        $relations = new ULL_Relations();
        $current_relations = $relations->get_relations($post->ID);
        $relation_types = $relations->get_relation_types();
        ?>
        <div class="ull-relaciones-container">
            <div class="ull-nueva-relacion">
                <h4><?php _e('Añadir Relación', 'ull-normativa'); ?></h4>
                <p>
                    <label for="norma_relacionada"><?php _e('Norma:', 'ull-normativa'); ?></label>
                    <select id="norma_relacionada" name="norma_relacionada" class="widefat">
                        <option value=""><?php _e('Buscar norma...', 'ull-normativa'); ?></option>
                    </select>
                </p>
                <p>
                    <label for="tipo_relacion"><?php _e('Tipo:', 'ull-normativa'); ?></label>
                    <select id="tipo_relacion" name="tipo_relacion" class="widefat">
                        <?php foreach ($relation_types as $type => $label) : ?>
                            <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="notas_relacion"><?php _e('Notas:', 'ull-normativa'); ?></label>
                    <textarea id="notas_relacion" name="notas_relacion" rows="2" class="widefat"></textarea>
                </p>
                <p>
                    <button type="button" class="button button-primary" id="ull-add-relation">
                        <?php _e('Añadir Relación', 'ull-normativa'); ?>
                    </button>
                </p>
            </div>
            
            <?php if (!empty($current_relations)) : ?>
            <div class="ull-relaciones-lista">
                <h4><?php _e('Relaciones Existentes', 'ull-normativa'); ?></h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Norma', 'ull-normativa'); ?></th>
                            <th><?php _e('Tipo', 'ull-normativa'); ?></th>
                            <th width="80"><?php _e('Acciones', 'ull-normativa'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_relations as $rel) : 
                            $related_post = get_post($rel->related_norma_id);
                            if (!$related_post) continue;
                        ?>
                        <tr data-relation-id="<?php echo esc_attr($rel->id); ?>">
                            <td>
                                <a href="<?php echo get_edit_post_link($related_post->ID); ?>" target="_blank">
                                    <?php echo esc_html($related_post->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(isset($relation_types[$rel->relation_type]) ? $relation_types[$rel->relation_type] : $rel->relation_type); ?></td>
                            <td>
                                <a href="#" class="ull-delete-relation" data-relation-id="<?php echo esc_attr($rel->id); ?>">
                                    <?php _e('Eliminar', 'ull-normativa'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function render_documentos_meta_box($post) {
        $documents = get_post_meta($post->ID, '_documentos_adjuntos', true);
        if (!is_array($documents)) {
            $documents = array();
        }
        ?>
        <div class="ull-documentos-container">
            <div id="ull-documentos-list">
                <?php foreach ($documents as $doc_id) : 
                    $doc_url = wp_get_attachment_url($doc_id);
                    $doc_title = get_the_title($doc_id);
                    if (!$doc_url) continue;
                ?>
                    <div class="ull-documento-item" data-id="<?php echo esc_attr($doc_id); ?>">
                        <span class="dashicons dashicons-media-document"></span>
                        <a href="<?php echo esc_url($doc_url); ?>" target="_blank"><?php echo esc_html($doc_title); ?></a>
                        <button type="button" class="ull-remove-doc button-link" data-id="<?php echo esc_attr($doc_id); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                        <input type="hidden" name="documentos_adjuntos[]" value="<?php echo esc_attr($doc_id); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="button" id="ull-add-document">
                <?php _e('Añadir Documento', 'ull-normativa'); ?>
            </button>
        </div>
        <?php
    }
    
    public function save_meta_boxes($post_id, $post) {
        if (!isset($_POST['ull_norma_nonce']) || !wp_verify_nonce($_POST['ull_norma_nonce'], 'ull_norma_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = ULL_Post_Types::get_meta_fields();
        $sanitizer = new ULL_HTML_Sanitizer();
        
        foreach ($fields as $key => $field) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
                
                switch ($field['type']) {
                    case 'url':
                        $value = esc_url_raw($value);
                        break;
                    case 'wysiwyg':
                        if (!empty($field['sanitize'])) {
                            $value = $sanitizer->sanitize($value);
                        } else {
                            $value = wp_kses_post($value);
                        }
                        break;
                    case 'textarea':
                        $value = sanitize_textarea_field($value);
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }
                
                update_post_meta($post_id, '_' . $key, $value);
            }
        }
        
        // Guardar documentos adjuntos
        if (isset($_POST['documentos_adjuntos'])) {
            $docs = array_map('absint', $_POST['documentos_adjuntos']);
            update_post_meta($post_id, '_documentos_adjuntos', $docs);
        } else {
            delete_post_meta($post_id, '_documentos_adjuntos');
        }
        
        // Verificar si cambió el tipo de norma y renumerar si es necesario
        $this->check_and_renumber_on_type_change($post_id);
        
        // Control de versiones
        if (!empty($_POST['crear_version']) && get_option('ull_normativa_enable_versions', true)) {
            $version_control = new ULL_Version_Control();
            $nueva_version = isset($_POST['nueva_version']) ? sanitize_text_field($_POST['nueva_version']) : '';
            $resumen = isset($_POST['resumen_cambios']) ? sanitize_textarea_field($_POST['resumen_cambios']) : '';
            
            $version_control->create_version($post_id, $nueva_version, $resumen);
        }
    }
    
    /**
     * Verificar si cambió el tipo y renumerar
     */
    private function check_and_renumber_on_type_change($post_id) {
        // Obtener el tipo actual de la norma
        $tipos = get_the_terms($post_id, 'tipo_norma');
        if (!$tipos || is_wp_error($tipos)) {
            return;
        }
        
        $tipo = $tipos[0];
        $nuevo_prefijo = ULL_Post_Types::get_tipo_prefix($tipo->slug);
        
        // Obtener el número actual
        $numero_actual = get_post_meta($post_id, '_numero_norma', true);
        
        // Si no tiene número, asignar uno nuevo
        if (empty($numero_actual)) {
            $siguiente = ULL_Post_Types::get_next_sequence_number($nuevo_prefijo);
            $nuevo_numero = $nuevo_prefijo . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
            update_post_meta($post_id, '_numero_norma', $nuevo_numero);
            return;
        }
        
        // Extraer el prefijo del número actual
        $partes = explode('-', $numero_actual);
        $prefijo_actual = $partes[0];
        
        // Si el prefijo cambió, renumerar
        if ($prefijo_actual !== $nuevo_prefijo) {
            $siguiente = ULL_Post_Types::get_next_sequence_number($nuevo_prefijo);
            $nuevo_numero = $nuevo_prefijo . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
            update_post_meta($post_id, '_numero_norma', $nuevo_numero);
        }
    }
}

new ULL_Meta_Boxes();
