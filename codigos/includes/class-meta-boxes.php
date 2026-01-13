<?php
/**
 * Meta Boxes para gestionar normas en códigos
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Codigos_Meta_Boxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_codigo', array($this, 'save_meta_boxes'), 10, 2);
        add_action('wp_ajax_ull_codigos_search_normas', array($this, 'ajax_search_normas'));
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'ull_codigo_normas',
            __('Normas incluidas en el Código', 'ull-normativa'),
            array($this, 'render_normas_meta_box'),
            'codigo',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ull_codigo_opciones',
            __('Opciones de visualización', 'ull-normativa'),
            array($this, 'render_opciones_meta_box'),
            'codigo',
            'side',
            'default'
        );
    }
    
    public function render_normas_meta_box($post) {
        wp_nonce_field('ull_codigo_normas', 'ull_codigo_normas_nonce');
        
        $normas = get_post_meta($post->ID, '_codigo_normas', true);
        if (!is_array($normas)) {
            $normas = array();
        }
        ?>
        <div class="ull-codigos-normas-wrapper">
            <div class="ull-codigos-search-box">
                <input type="text" id="ull-codigos-search" placeholder="<?php esc_attr_e('Buscar normas por título o número...', 'ull-normativa'); ?>">
                <div id="ull-codigos-search-results"></div>
            </div>
            
            <div class="ull-codigos-normas-list" id="ull-codigos-normas-list">
                <?php if (!empty($normas)) : ?>
                    <?php foreach ($normas as $index => $norma_data) : 
                        $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
                        $norma_post = get_post($norma_id);
                        if (!$norma_post) continue;
                        $numero = get_post_meta($norma_id, '_numero_norma', true);
                        $seccion = isset($norma_data['seccion']) ? $norma_data['seccion'] : '';
                        $nota = isset($norma_data['nota']) ? $norma_data['nota'] : '';
                    ?>
                        <div class="ull-codigos-norma-item" data-id="<?php echo esc_attr($norma_id); ?>">
                            <div class="ull-codigos-norma-handle">☰</div>
                            <div class="ull-codigos-norma-info">
                                <strong><?php echo esc_html($norma_post->post_title); ?></strong>
                                <?php if ($numero) : ?>
                                    <span class="ull-codigos-norma-numero"><?php echo esc_html($numero); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ull-codigos-norma-fields">
                                <input type="text" name="codigo_normas[<?php echo $index; ?>][seccion]" 
                                       value="<?php echo esc_attr($seccion); ?>" 
                                       placeholder="<?php esc_attr_e('Sección (opcional)', 'ull-normativa'); ?>">
                                <textarea name="codigo_normas[<?php echo $index; ?>][nota]" 
                                          placeholder="<?php esc_attr_e('Nota (opcional)', 'ull-normativa'); ?>"><?php echo esc_textarea($nota); ?></textarea>
                            </div>
                            <input type="hidden" name="codigo_normas[<?php echo $index; ?>][id]" value="<?php echo esc_attr($norma_id); ?>">
                            <button type="button" class="ull-codigos-remove-norma" title="<?php esc_attr_e('Eliminar', 'ull-normativa'); ?>">×</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <p class="description">
                <?php _e('Busque y añada normas. Arrastre para reordenar. Puede asignar secciones y notas a cada norma.', 'ull-normativa'); ?>
            </p>
        </div>
        <?php
    }
    
    public function render_opciones_meta_box($post) {
        $estilo = get_post_meta($post->ID, '_codigo_estilo', true) ?: 'accordion';
        $mostrar_indice = get_post_meta($post->ID, '_codigo_mostrar_indice', true);
        $mostrar_fechas = get_post_meta($post->ID, '_codigo_mostrar_fechas', true);
        $permitir_pdf = get_post_meta($post->ID, '_codigo_permitir_pdf', true);
        
        if ($mostrar_indice === '') $mostrar_indice = '1';
        if ($permitir_pdf === '') $permitir_pdf = '1';
        ?>
        <p>
            <label for="codigo_estilo"><strong><?php _e('Estilo de presentación:', 'ull-normativa'); ?></strong></label>
            <select name="codigo_estilo" id="codigo_estilo" style="width: 100%; margin-top: 5px;">
                <option value="accordion" <?php selected($estilo, 'accordion'); ?>><?php _e('Acordeón', 'ull-normativa'); ?></option>
                <option value="list" <?php selected($estilo, 'list'); ?>><?php _e('Lista', 'ull-normativa'); ?></option>
                <option value="cards" <?php selected($estilo, 'cards'); ?>><?php _e('Tarjetas', 'ull-normativa'); ?></option>
                <option value="full" <?php selected($estilo, 'full'); ?>><?php _e('Contenido completo', 'ull-normativa'); ?></option>
            </select>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="codigo_mostrar_indice" value="1" <?php checked($mostrar_indice, '1'); ?>>
                <?php _e('Mostrar índice', 'ull-normativa'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="codigo_mostrar_fechas" value="1" <?php checked($mostrar_fechas, '1'); ?>>
                <?php _e('Mostrar fechas', 'ull-normativa'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="codigo_permitir_pdf" value="1" <?php checked($permitir_pdf, '1'); ?>>
                <?php _e('Permitir exportar a PDF', 'ull-normativa'); ?>
            </label>
        </p>
        
        <hr>
        <p><strong><?php _e('Shortcode:', 'ull-normativa'); ?></strong></p>
        <code style="display: block; padding: 8px; background: #f5f5f5; font-size: 12px;">
            [ull_codigo id="<?php echo $post->ID; ?>"]
        </code>
        <?php
    }
    
    public function save_meta_boxes($post_id, $post) {
        if (!isset($_POST['ull_codigo_normas_nonce']) || 
            !wp_verify_nonce($_POST['ull_codigo_normas_nonce'], 'ull_codigo_normas')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Guardar normas
        $normas = array();
        if (isset($_POST['codigo_normas']) && is_array($_POST['codigo_normas'])) {
            foreach ($_POST['codigo_normas'] as $norma_data) {
                if (!empty($norma_data['id'])) {
                    $normas[] = array(
                        'id' => intval($norma_data['id']),
                        'seccion' => sanitize_text_field($norma_data['seccion'] ?? ''),
                        'nota' => sanitize_textarea_field($norma_data['nota'] ?? ''),
                    );
                }
            }
        }
        update_post_meta($post_id, '_codigo_normas', $normas);
        
        // Guardar opciones
        update_post_meta($post_id, '_codigo_estilo', sanitize_text_field($_POST['codigo_estilo'] ?? 'accordion'));
        update_post_meta($post_id, '_codigo_mostrar_indice', isset($_POST['codigo_mostrar_indice']) ? '1' : '0');
        update_post_meta($post_id, '_codigo_mostrar_fechas', isset($_POST['codigo_mostrar_fechas']) ? '1' : '0');
        update_post_meta($post_id, '_codigo_permitir_pdf', isset($_POST['codigo_permitir_pdf']) ? '1' : '0');
    }
    
    public function ajax_search_normas() {
        check_ajax_referer('ull_codigos_nonce', 'nonce');
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $post_type = get_option('ull_codigos_post_type', 'norma');
        
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search,
        );
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $numero = get_post_meta(get_the_ID(), '_numero_norma', true);
                $results[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'numero' => $numero,
                );
            }
        }
        wp_reset_postdata();
        
        wp_send_json_success($results);
    }
}

new ULL_Codigos_Meta_Boxes();
