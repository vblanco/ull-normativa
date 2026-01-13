<?php
/**
 * Shortcodes del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Shortcodes {
    
    public function __construct() {
        add_shortcode('ull_normativa_listado', array($this, 'listado_shortcode'));
        add_shortcode('ull_normativa_buscador', array($this, 'buscador_shortcode'));
        add_shortcode('ull_norma', array($this, 'ficha_shortcode'));
        add_shortcode('ull_normativa_archivo', array($this, 'archivo_shortcode'));
        add_shortcode('ull_nube_materias', array($this, 'nube_materias_shortcode'));
        add_shortcode('ull_boton_normativa', array($this, 'boton_normativa_shortcode'));
        add_shortcode('ull_boton_categoria', array($this, 'boton_categoria_shortcode'));
        
        // NUEVO v2.0: Tabla de contenidos automática
        add_shortcode('ull_tabla_contenidos', array($this, 'tabla_contenidos_shortcode'));
        
        // DESACTIVADO: El filtro causaba problemas de contenido
        // JavaScript añade los IDs automáticamente (ver toc-navigation.js)
        // add_filter('the_content', array($this, 'add_heading_ids'), 100);
        
        // Extender búsqueda de WordPress para incluir metadatos
        add_action('pre_get_posts', array($this, 'extend_search'));
        add_filter('posts_join', array($this, 'search_join'), 10, 2);
        add_filter('posts_where', array($this, 'search_where'), 10, 2);
        add_filter('posts_distinct', array($this, 'search_distinct'), 10, 2);
    }
    
    /**
     * Obtener URL del archivo de normativa de forma segura
     */
    private function get_normativa_url() {
        // Siempre construir manualmente usando home_url para garantizar la URL correcta
        return home_url('/normativa/');
    }
    
    /**
     * Shortcode para botón de ver toda la normativa
     */
    public function boton_normativa_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'texto' => __('Ver toda la normativa', 'ull-normativa'),
            'clase' => '',
        ), $atts);
        
        $archive_url = $this->get_normativa_url();
        
        $clase = 'wp-block-button';
        if (!empty($atts['clase'])) {
            $clase .= ' ' . esc_attr($atts['clase']);
        }
        
        return '<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50);justify-content:center;display:flex;">
            <div class="' . $clase . '">
                <a class="wp-block-button__link wp-element-button" href="' . esc_url($archive_url) . '">' . esc_html($atts['texto']) . '</a>
            </div>
        </div>';
    }
    
    /**
     * Shortcode para botón de categoría/tipo
     */
    public function boton_categoria_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'tipo' => '',
            'categoria' => '',
            'estado' => '',
            'texto' => __('Ver más', 'ull-normativa'),
            'clase' => 'is-style-outline has-small-font-size',
        ), $atts);
        
        $archive_url = $this->get_normativa_url();
        
        // Añadir parámetros de filtro
        $params = array();
        if (!empty($atts['tipo'])) {
            $params['ull_tipo'] = $atts['tipo'];
        }
        if (!empty($atts['categoria'])) {
            $params['ull_categoria'] = $atts['categoria'];
        }
        if (!empty($atts['estado'])) {
            $params['ull_estado'] = $atts['estado'];
        }
        
        if (!empty($params)) {
            $archive_url = add_query_arg($params, $archive_url);
        }
        
        return '<div class="wp-block-buttons">
            <div class="wp-block-button has-custom-font-size ' . esc_attr($atts['clase']) . '">
                <a class="wp-block-button__link wp-element-button" href="' . esc_url($archive_url) . '">' . esc_html($atts['texto']) . '</a>
            </div>
        </div>';
    }
    
    /**
     * Extender búsqueda para normas
     */
    public function extend_search($query) {
        if (!$query->is_search() || is_admin()) {
            return;
        }
        
        if ($query->get('post_type') === 'norma' || (isset($_GET['post_type']) && $_GET['post_type'] === 'norma')) {
            $query->set('ull_search_extended', true);
        }
    }
    
    public function search_join($join, $query) {
        global $wpdb;
        
        if (!$query->get('ull_search_extended')) {
            return $join;
        }
        
        $join .= " LEFT JOIN {$wpdb->postmeta} AS ull_meta ON ({$wpdb->posts}.ID = ull_meta.post_id) ";
        
        return $join;
    }
    
    public function search_where($where, $query) {
        global $wpdb;
        
        if (!$query->get('ull_search_extended')) {
            return $where;
        }
        
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $where;
        }
        
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Obtener campos de búsqueda configurados
        $search_fields_config = get_option('ull_normativa_search_fields', array('title', 'content', 'numero', 'fecha'));
        
        // Construir condiciones de búsqueda según configuración
        $search_conditions = array();
        
        // Campos estándar de WordPress
        if (in_array('title', $search_fields_config)) {
            $search_conditions[] = "{$wpdb->posts}.post_title LIKE '$like'";
        }
        if (in_array('content', $search_fields_config)) {
            $search_conditions[] = "{$wpdb->posts}.post_content LIKE '$like'";
        }
        if (in_array('excerpt', $search_fields_config)) {
            $search_conditions[] = "{$wpdb->posts}.post_excerpt LIKE '$like'";
        }
        
        // Campos meta personalizados
        $meta_keys = array();
        if (in_array('numero', $search_fields_config)) {
            $meta_keys[] = '_numero_norma';
        }
        if (in_array('fecha', $search_fields_config)) {
            $meta_keys[] = '_fecha_aprobacion';
            $meta_keys[] = '_fecha_publicacion';
        }
        if (in_array('organo', $search_fields_config)) {
            $meta_keys[] = '_organo_emisor';
        }
        if (in_array('resumen', $search_fields_config)) {
            $meta_keys[] = '_resumen';
        }
        if (in_array('palabras_clave', $search_fields_config)) {
            $meta_keys[] = '_palabras_clave';
        }
        if (in_array('ambito', $search_fields_config)) {
            $meta_keys[] = '_ambito_aplicacion';
        }
        
        // Añadir búsqueda en campos meta si hay alguno configurado
        if (!empty($meta_keys)) {
            $meta_keys_sql = "'" . implode("','", array_map('esc_sql', $meta_keys)) . "'";
            $meta_search = $wpdb->prepare(
                "(ull_meta.meta_key IN ({$meta_keys_sql}) AND ull_meta.meta_value LIKE %s)",
                $like
            );
            $search_conditions[] = $meta_search;
        }
        
        // Combinar todas las condiciones
        if (!empty($search_conditions)) {
            $combined_search = implode(' OR ', $search_conditions);
            
            // Reemplazar la condición de búsqueda por defecto de WordPress
            $where = preg_replace(
                "/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                "({$combined_search})",
                $where
            );
        }
        
        return $where;
    }
    
    public function search_distinct($distinct, $query) {
        if ($query->get('ull_search_extended')) {
            return 'DISTINCT';
        }
        return $distinct;
    }
    
    public function listado_shortcode($atts = array()) {
        // Obtener configuración guardada
        $items_per_page_config = get_option('ull_normativa_items_per_page', 20);
        
        $atts = shortcode_atts(array(
            'tipo' => '',
            'estado' => '',
            'categoria' => '',
            'limit' => $items_per_page_config, // Usar configuración guardada
            'modo' => 'list',
            'orden' => 'fecha_desc',
            'order' => '', // Soporte para "order" además de "orden"
            'mostrar_filtros' => 'true',
            'mostrar_buscador' => 'true',
            'paginacion' => 'true', // NUEVO: controlar paginación
        ), $atts);
        
        // Si se usa 'order', sobrescribir 'orden'
        if (!empty($atts['order'])) {
            // Convertir ASC/DESC a formato interno
            if (strtoupper($atts['order']) === 'ASC') {
                $atts['orden'] = 'fecha_asc';
            } elseif (strtoupper($atts['order']) === 'DESC') {
                $atts['orden'] = 'fecha_desc';
            }
        }
        
        $mostrar_filtros = filter_var($atts['mostrar_filtros'], FILTER_VALIDATE_BOOLEAN);
        $mostrar_buscador = filter_var($atts['mostrar_buscador'], FILTER_VALIDATE_BOOLEAN);
        $mostrar_paginacion = filter_var($atts['paginacion'], FILTER_VALIDATE_BOOLEAN);
        
        // Obtener página actual - usar nuestro propio parámetro ull_pag
        // Si paginacion="no", siempre usar página 1
        $paged = 1;
        if ($mostrar_paginacion) {
            if (isset($_GET['ull_pag']) && intval($_GET['ull_pag']) > 0) {
                $paged = intval($_GET['ull_pag']);
            } elseif (get_query_var('paged')) {
                $paged = get_query_var('paged');
            } elseif (get_query_var('page')) {
                $paged = get_query_var('page');
            }
        }
        
        // Obtener término de búsqueda
        $search_term = '';
        if (isset($_GET['ull_search']) && !empty($_GET['ull_search'])) {
            $search_term = sanitize_text_field($_GET['ull_search']);
        } elseif (isset($_GET['s']) && !empty($_GET['s'])) {
            $search_term = sanitize_text_field($_GET['s']);
        }
        
        $args = array(
            'post_type' => 'norma',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'paged' => $paged,
        );
        
        // Ordenación
        switch ($atts['orden']) {
            case 'fecha_asc':
                $args['meta_key'] = '_fecha_aprobacion';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'ASC';
                break;
            case 'titulo_asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'titulo_desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'numero':
                $args['meta_key'] = '_numero_norma';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'ASC';
                break;
            default: // fecha_desc
                $args['meta_key'] = '_fecha_aprobacion';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'DESC';
        }
        
        $tax_query = array();
        $meta_query = array();
        
        // Filtros del shortcode
        if (!empty($atts['tipo'])) {
            $tax_query[] = array('taxonomy' => 'tipo_norma', 'field' => 'slug', 'terms' => $atts['tipo']);
        }
        if (!empty($atts['categoria'])) {
            $tax_query[] = array('taxonomy' => 'categoria_norma', 'field' => 'slug', 'terms' => $atts['categoria']);
        }
        if (!empty($atts['estado'])) {
            $meta_query[] = array('key' => '_estado_norma', 'value' => $atts['estado']);
        }
        
        // Filtros de URL
        if (isset($_GET['ull_tipo']) && !empty($_GET['ull_tipo'])) {
            $tax_query[] = array('taxonomy' => 'tipo_norma', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['ull_tipo']));
        }
        if (isset($_GET['ull_estado']) && !empty($_GET['ull_estado'])) {
            $meta_query[] = array('key' => '_estado_norma', 'value' => sanitize_text_field($_GET['ull_estado']));
        }
        if (isset($_GET['ull_categoria']) && !empty($_GET['ull_categoria'])) {
            $tax_query[] = array('taxonomy' => 'categoria_norma', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['ull_categoria']));
        }
        if (isset($_GET['ull_materia']) && !empty($_GET['ull_materia'])) {
            $tax_query[] = array('taxonomy' => 'materia_norma', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['ull_materia']));
        }
        if (isset($_GET['ull_organo']) && !empty($_GET['ull_organo'])) {
            $tax_query[] = array('taxonomy' => 'organo_norma', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['ull_organo']));
        }
        
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }
        
        // Búsqueda mejorada
        if (!empty($search_term)) {
            $args = $this->apply_extended_search($args, $search_term);
        }
        
        $query = new WP_Query($args);
        
        // Construir URL base para filtros
        $current_url = remove_query_arg(array('paged'));
        
        // Obtener URL del archivo de normativa
        $archive_url = $this->get_normativa_url();
        
        ob_start();
        ?>
        <div class="ull-normativa-container" data-mode="<?php echo esc_attr($atts['modo']); ?>">
            <?php if ($mostrar_buscador || $mostrar_filtros) : ?>
            <div class="ull-normativa-header">
                <?php if ($mostrar_buscador) : ?>
                <div class="ull-search-box">
                    <form method="get" action="<?php echo esc_url($archive_url); ?>" class="ull-search-form" target="_self">
                        <?php 
                        // Mantener filtros actuales en la búsqueda
                        if (isset($_GET['ull_tipo']) && !empty($_GET['ull_tipo'])) : ?>
                            <input type="hidden" name="ull_tipo" value="<?php echo esc_attr($_GET['ull_tipo']); ?>">
                        <?php endif;
                        if (isset($_GET['ull_estado']) && !empty($_GET['ull_estado'])) : ?>
                            <input type="hidden" name="ull_estado" value="<?php echo esc_attr($_GET['ull_estado']); ?>">
                        <?php endif;
                        if (isset($_GET['ull_categoria']) && !empty($_GET['ull_categoria'])) : ?>
                            <input type="hidden" name="ull_categoria" value="<?php echo esc_attr($_GET['ull_categoria']); ?>">
                        <?php endif; ?>
                        <input type="text" name="ull_search" value="<?php echo esc_attr($search_term); ?>" placeholder="<?php esc_attr_e('Buscar por título, número, palabras clave...', 'ull-normativa'); ?>" class="ull-search-input">
                        <button type="submit" class="ull-search-btn"><?php _e('Buscar', 'ull-normativa'); ?></button>
                        <?php if (!empty($search_term)) : ?>
                            <a href="<?php echo esc_url($archive_url); ?>" class="ull-search-clear" title="<?php esc_attr_e('Limpiar búsqueda', 'ull-normativa'); ?>">&times;</a>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if ($mostrar_filtros) : ?>
                <div class="ull-filters">
                    <form method="get" action="<?php echo esc_url($archive_url); ?>" class="ull-filters-form" target="_self">
                        <?php if (!empty($search_term)) : ?>
                            <input type="hidden" name="ull_search" value="<?php echo esc_attr($search_term); ?>">
                        <?php endif; ?>
                        <?php echo $this->render_taxonomy_select('ull_tipo', 'tipo_norma', __('Todos los tipos', 'ull-normativa')); ?>
                        <?php echo $this->render_taxonomy_select('ull_categoria', 'categoria_norma', __('Todas las categorías', 'ull-normativa')); ?>
                        <?php echo $this->render_estado_select('ull_estado'); ?>
                        <button type="submit" class="ull-filter-btn"><?php _e('Filtrar', 'ull-normativa'); ?></button>
                        <?php if (!empty($_GET['ull_tipo']) || !empty($_GET['ull_estado']) || !empty($_GET['ull_categoria'])) : ?>
                            <a href="<?php echo esc_url($archive_url); ?>" class="ull-filter-clear"><?php _e('Limpiar filtros', 'ull-normativa'); ?></a>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($search_term) || !empty($_GET['ull_tipo']) || !empty($_GET['ull_estado']) || !empty($_GET['ull_categoria'])) : ?>
            <div class="ull-results-info">
                <?php 
                printf(
                    _n('%d norma encontrada', '%d normas encontradas', $query->found_posts, 'ull-normativa'),
                    $query->found_posts
                );
                if (!empty($search_term)) {
                    printf(' ' . __('para "%s"', 'ull-normativa'), esc_html($search_term));
                }
                ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <div class="ull-normativa-results">
                <?php if ($query->have_posts()) : ?>
                    <div class="ull-normativa-list">
                        <?php while ($query->have_posts()) : $query->the_post(); 
                            $estado = get_post_meta(get_the_ID(), '_estado_norma', true);
                            $numero = get_post_meta(get_the_ID(), '_numero_norma', true);
                            $fecha = get_post_meta(get_the_ID(), '_fecha_aprobacion', true);
                            $tipos = get_the_terms(get_the_ID(), 'tipo_norma');
                            $tipo = $tipos && !is_wp_error($tipos) ? $tipos[0]->name : '';
                        ?>
                        <div class="ull-normativa-item">
                            <div class="ull-item-content">
                                <h3 class="ull-item-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <div class="ull-item-meta">
                                    <?php if ($tipo) : ?><span class="ull-meta-tipo"><?php echo esc_html($tipo); ?></span><?php endif; ?>
                                    <?php if ($numero) : ?><span class="ull-meta-numero"><?php echo esc_html($numero); ?></span><?php endif; ?>
                                    <?php if ($fecha) : ?><span class="ull-meta-fecha"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($fecha))); ?></span><?php endif; ?>
                                </div>
                                <?php 
                                $excerpt = get_the_excerpt();
                                if ($excerpt) : ?>
                                <p class="ull-item-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 20)); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($estado) : ?>
                            <span class="ull-item-estado ull-estado-<?php echo esc_attr($estado); ?>"><?php echo esc_html(ucfirst($estado)); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                    
                    <?php if ($mostrar_paginacion && $query->max_num_pages > 1) : 
                        // Construir URL base para paginación
                        $base_url = $archive_url;
                        $query_args = array();
                        
                        // Mantener filtros actuales
                        if (!empty($_GET['ull_search'])) $query_args['ull_search'] = $_GET['ull_search'];
                        if (!empty($_GET['ull_tipo'])) $query_args['ull_tipo'] = $_GET['ull_tipo'];
                        if (!empty($_GET['ull_estado'])) $query_args['ull_estado'] = $_GET['ull_estado'];
                        if (!empty($_GET['ull_categoria'])) $query_args['ull_categoria'] = $_GET['ull_categoria'];
                        if (!empty($_GET['ull_materia'])) $query_args['ull_materia'] = $_GET['ull_materia'];
                        if (!empty($_GET['ull_organo'])) $query_args['ull_organo'] = $_GET['ull_organo'];
                        
                        if (!empty($query_args)) {
                            $base_url = add_query_arg($query_args, $base_url);
                        }
                    ?>
                    <div class="ull-pagination">
                        <?php 
                        echo paginate_links(array(
                            'base' => $base_url . '%_%',
                            'format' => (strpos($base_url, '?') !== false ? '&' : '?') . 'ull_pag=%#%',
                            'total' => $query->max_num_pages, 
                            'current' => $paged,
                            'prev_text' => '&laquo; ' . __('Anterior', 'ull-normativa'),
                            'next_text' => __('Siguiente', 'ull-normativa') . ' &raquo;',
                        )); 
                        ?>
                    </div>
                    <?php endif; ?>
                    
                <?php else : ?>
                    <div class="ull-no-results">
                        <p><?php _e('No se encontraron normas con los criterios especificados.', 'ull-normativa'); ?></p>
                        <?php if (!empty($search_term) || !empty($_GET['ull_tipo']) || !empty($_GET['ull_estado'])) : ?>
                        <p><a href="<?php echo esc_url($archive_url); ?>"><?php _e('Ver todas las normas', 'ull-normativa'); ?></a></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Aplicar búsqueda extendida en metadatos
     */
    private function apply_extended_search($args, $search_term) {
        global $wpdb;
        
        // Usar búsqueda personalizada en vez de 's' para tener más control
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Buscar IDs que coincidan en título, contenido o metadatos
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'norma' 
             AND p.post_status = 'publish'
             AND (
                 p.post_title LIKE %s 
                 OR p.post_content LIKE %s
                 OR p.post_excerpt LIKE %s
                 OR (pm.meta_key = '_numero_norma' AND pm.meta_value LIKE %s)
                 OR (pm.meta_key = '_palabras_clave' AND pm.meta_value LIKE %s)
                 OR (pm.meta_key = '_organo_emisor' AND pm.meta_value LIKE %s)
                 OR (pm.meta_key = '_resumen' AND pm.meta_value LIKE %s)
                 OR (pm.meta_key = '_ambito_aplicacion' AND pm.meta_value LIKE %s)
             )",
            $like, $like, $like, $like, $like, $like, $like, $like
        ));
        
        if (!empty($post_ids)) {
            $args['post__in'] = $post_ids;
        } else {
            // Si no hay resultados, forzar query vacía
            $args['post__in'] = array(0);
        }
        
        return $args;
    }
    
    public function buscador_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Buscar por título, número, palabras clave...', 'ull-normativa'),
            'mostrar_filtros' => 'true',
        ), $atts);
        
        $mostrar_filtros = filter_var($atts['mostrar_filtros'], FILTER_VALIDATE_BOOLEAN);
        $archive_url = $this->get_normativa_url();
        
        ob_start();
        ?>
        <div class="ull-buscador-avanzado">
            <form method="get" action="<?php echo esc_url($archive_url); ?>" class="ull-buscador-form" target="_self">
                <div class="ull-buscador-main">
                    <input type="text" name="ull_search" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" class="ull-buscador-input">
                    <button type="submit" class="ull-buscador-submit"><?php _e('Buscar', 'ull-normativa'); ?></button>
                </div>
                
                <?php if ($mostrar_filtros) : ?>
                <div class="ull-buscador-filtros">
                    <?php echo $this->render_taxonomy_select('ull_tipo', 'tipo_norma', __('Todos los tipos', 'ull-normativa')); ?>
                    <?php echo $this->render_taxonomy_select('ull_categoria', 'categoria_norma', __('Todas las categorías', 'ull-normativa')); ?>
                    <?php echo $this->render_estado_select('ull_estado'); ?>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function ficha_shortcode($atts = array()) {
        // Obtener configuración por defecto de secciones desde settings
        $default_sections = get_option('ull_normativa_ficha_sections', array('info', 'contenido', 'versiones', 'relaciones', 'documentos'));
        $default_sections_str = is_array($default_sections) ? implode(',', $default_sections) : 'info,contenido,versiones,relaciones,documentos';
        
        $atts = shortcode_atts(array(
            'id' => 0,
            'secciones' => $default_sections_str,
        ), $atts);
        
        $post_id = intval($atts['id']);
        if (!$post_id && is_singular('norma')) {
            $post_id = get_the_ID();
        }
        if (!$post_id) {
            return '<p class="ull-error">' . __('ID de norma no especificado.', 'ull-normativa') . '</p>';
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'norma') {
            return '<p class="ull-error">' . __('Norma no encontrada.', 'ull-normativa') . '</p>';
        }
        
        // Comprobar si se está viendo una versión anterior
        $viewing_version = null;
        $version_data = null;
        if (isset($_GET['version']) && !empty($_GET['version'])) {
            $version_id = absint($_GET['version']);
            global $wpdb;
            $table = $wpdb->prefix . 'ull_norma_versions';
            $version_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND norma_id = %d AND is_current = 0",
                $version_id,
                $post_id
            ));
            if ($version_data) {
                $viewing_version = $version_id;
            }
        }
        
        $secciones = array_map('trim', explode(',', $atts['secciones']));
        $estado = get_post_meta($post_id, '_estado_norma', true);
        $numero = get_post_meta($post_id, '_numero_norma', true);
        $tipos = get_the_terms($post_id, 'tipo_norma');
        $tipo = $tipos && !is_wp_error($tipos) ? $tipos[0]->name : '';
        
        // Obtener versión actual
        $current_version = null;
        if (!$viewing_version) {
            global $wpdb;
            $table = $wpdb->prefix . 'ull_norma_versions';
            $current_version = $wpdb->get_row($wpdb->prepare(
                "SELECT version_number, version_date FROM $table WHERE norma_id = %d AND is_current = 1",
                $post_id
            ));
        }
        
        ob_start();
        ?>
        <div class="ull-norma-ficha <?php echo $viewing_version ? 'ull-viewing-old-version' : ''; ?>">
            
            <?php if ($viewing_version && $version_data) : ?>
            <div class="ull-version-notice">
                <div class="ull-version-notice-content">
                    <strong><?php _e('Estás viendo una versión anterior', 'ull-normativa'); ?></strong>
                    <span class="ull-version-notice-info">
                        <?php printf(
                            __('Versión %s del %s', 'ull-normativa'),
                            esc_html($version_data->version_number),
                            esc_html(date_i18n(get_option('date_format'), strtotime($version_data->version_date)))
                        ); ?>
                    </span>
                </div>
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="ull-version-notice-link">
                    <?php _e('← Ver versión actual', 'ull-normativa'); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="ull-ficha-header">
                <div class="ull-ficha-meta">
                    <?php if ($tipo) : ?><span class="ull-ficha-tipo"><?php echo esc_html($tipo); ?></span><?php endif; ?>
                    <?php if ($estado) : ?><span class="ull-ficha-estado ull-estado-<?php echo esc_attr($estado); ?>"><?php echo esc_html(ucfirst($estado)); ?></span><?php endif; ?>
                    <?php if ($viewing_version) : ?>
                        <span class="ull-ficha-version-old"><?php _e('Versión Histórica', 'ull-normativa'); ?></span>
                    <?php elseif ($current_version) : ?>
                        <span class="ull-ficha-version-current" title="<?php echo esc_attr(date_i18n(get_option('date_format'), strtotime($current_version->version_date))); ?>">
                            <?php printf(__('Versión %s', 'ull-normativa'), esc_html($current_version->version_number)); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php
                // NUEVO v2.0: Hook para badge de versión mejorado
                do_action('ull_normativa_before_title');
                ?>
                
                <h1 class="ull-ficha-titulo"><?php echo esc_html($post->post_title); ?></h1>
                <?php if ($numero) : ?><p class="ull-ficha-numero"><?php echo esc_html($numero); ?></p><?php endif; ?>
                
                <?php
                // NUEVO v2.0: Hook para banner de versión histórica
                do_action('ull_normativa_after_title');
                ?>
                
                <?php if (!$viewing_version) : ?>
                <div class="ull-ficha-actions">
                    <a href="<?php echo esc_url(ULL_Unified_PDF_Export::get_pdf_url($post_id)); ?>" class="ull-btn-pdf" target="_blank" title="<?php esc_attr_e('Exportar a PDF', 'ull-normativa'); ?>">
                        <span class="ull-pdf-icon">📄</span>
                        <?php _e('PDF', 'ull-normativa'); ?>
                    </a>
                    <a href="<?php echo esc_url(ULL_Unified_PDF_Export::get_xml_url($post_id)); ?>" class="ull-btn-xml" target="_blank" title="<?php esc_attr_e('Exportar a XML', 'ull-normativa'); ?>">
                        <span class="ull-xml-icon">📋</span>
                        <?php _e('XML', 'ull-normativa'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($viewing_version && $version_data) : ?>
                <!-- Mostrar solo el contenido de la versión antigua -->
                <div class="ull-ficha-content">
                    <div class="ull-tab-content active">
                        <div class="ull-contenido-norma ull-contenido-version-antigua">
                            <?php 
                            // Aplicar los mismos filtros que al contenido actual para preservar formato HTML
                            $version_content = $version_data->content;
                            $version_content = wpautop($version_content);
                            $version_content = do_shortcode($version_content);
                            $version_content = wptexturize($version_content);
                            echo $version_content;
                            ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($version_data->changes_summary) : ?>
                <div class="ull-version-changes">
                    <strong><?php _e('Cambios en esta versión:', 'ull-normativa'); ?></strong>
                    <p><?php echo esc_html($version_data->changes_summary); ?></p>
                </div>
                <?php endif; ?>
                
            <?php else : ?>
                <!-- Mostrar la ficha normal con tabs -->
                <?php if (count($secciones) > 1) : ?>
                <div class="ull-ficha-tabs">
                    <?php foreach ($secciones as $i => $sec) : ?>
                    <button type="button" class="ull-tab <?php echo $i === 0 ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($sec); ?>"><?php echo esc_html($this->get_section_label($sec)); ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="ull-ficha-content">
                    <?php foreach ($secciones as $i => $sec) : ?>
                    <div class="ull-tab-content <?php echo $i === 0 ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($sec); ?>">
                        <?php echo $this->render_section($post_id, $sec); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode para nube de materias
     * 
     * Parámetros:
     * - taxonomy: materia_norma, tipo_norma, categoria_norma, organo_norma (por defecto: materia_norma)
     * - titulo: Título de la nube (por defecto: "Materias")
     * - numero: Número máximo de términos a mostrar (por defecto: 50)
     * - ordenar: nombre o count (por defecto: count)
     * - mostrar_vacio: si/no - Mostrar mensaje si no hay términos (por defecto: si)
     */
    public function nube_materias_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'taxonomy' => 'materia_norma',
            'titulo' => __('Materias', 'ull-normativa'),
            'numero' => 50,
            'ordenar' => 'count',
            'mostrar_vacio' => 'si',
        ), $atts);
        
        // Determinar orden
        $orderby = ($atts['ordenar'] === 'nombre' || $atts['ordenar'] === 'name') ? 'name' : 'count';
        $order = ($orderby === 'count') ? 'DESC' : 'ASC';
        
        $terms = get_terms(array(
            'taxonomy' => $atts['taxonomy'],
            'hide_empty' => true,
            'orderby' => $orderby,
            'order' => $order,
            'number' => intval($atts['numero']),
        ));
        
        // Manejar errores
        if (is_wp_error($terms)) {
            if ($atts['mostrar_vacio'] === 'si') {
                return '<div class="ull-nube-materias ull-nube-error"><p>' . 
                       __('Error al cargar las materias.', 'ull-normativa') . 
                       '</p></div>';
            }
            return '';
        }
        
        // Si no hay términos
        if (empty($terms)) {
            if ($atts['mostrar_vacio'] === 'si') {
                $taxonomy_obj = get_taxonomy($atts['taxonomy']);
                $taxonomy_name = $taxonomy_obj ? $taxonomy_obj->labels->name : __('términos', 'ull-normativa');
                
                return '<div class="ull-nube-materias ull-nube-vacia">
                    <p><em>' . sprintf(__('No hay %s disponibles.', 'ull-normativa'), strtolower($taxonomy_name)) . '</em></p>
                </div>';
            }
            return '';
        }
        
        $archive_url = $this->get_normativa_url();
        $param_map = array(
            'materia_norma' => 'ull_materia',
            'tipo_norma' => 'ull_tipo',
            'categoria_norma' => 'ull_categoria',
            'organo_norma' => 'ull_organo',
        );
        $param = isset($param_map[$atts['taxonomy']]) ? $param_map[$atts['taxonomy']] : 'ull_materia';
        
        // Calcular tamaños de fuente relativos
        $counts = wp_list_pluck($terms, 'count');
        $max_count = max($counts);
        $min_count = min($counts);
        
        ob_start();
        ?>
        <div class="ull-nube-materias">
            <?php if ($atts['titulo']) : ?>
            <h3 class="ull-nube-titulo"><?php echo esc_html($atts['titulo']); ?></h3>
            <?php endif; ?>
            <div class="ull-nube-tags">
                <?php foreach ($terms as $term) : 
                    $size = $max_count > $min_count 
                        ? 0.8 + (($term->count - $min_count) / ($max_count - $min_count)) * 0.8
                        : 1;
                    $term_url = add_query_arg($param, $term->slug, $archive_url);
                ?>
                <a href="<?php echo esc_url($term_url); ?>" 
                   class="ull-nube-tag" 
                   style="font-size: <?php echo esc_attr($size); ?>rem;"
                   title="<?php echo esc_attr(sprintf(__('%d normas', 'ull-normativa'), $term->count)); ?>">
                    <?php echo esc_html($term->name); ?>
                    <span class="ull-nube-count"><?php echo esc_html($term->count); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function archivo_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'agrupar_por' => 'year', // year, tipo, organo, categoria, materia
            'mostrar_nube' => 'true',
            'taxonomia_nube' => 'materia_norma',
        ), $atts);
        
        $mostrar_nube = filter_var($atts['mostrar_nube'], FILTER_VALIDATE_BOOLEAN);
        $archive_url = $this->get_normativa_url();
        
        ob_start();
        ?>
        <div class="ull-normativa-archivo">
            
            <?php if ($mostrar_nube) : ?>
                <?php echo $this->nube_materias_shortcode(array('taxonomy' => $atts['taxonomia_nube'])); ?>
            <?php endif; ?>
            
            <div class="ull-archivo-controles">
                <span class="ull-archivo-label"><?php _e('Agrupar por:', 'ull-normativa'); ?></span>
                <div class="ull-archivo-tabs">
                    <?php
                    $grupos = array(
                        'year' => __('Año', 'ull-normativa'),
                        'tipo' => __('Tipo', 'ull-normativa'),
                        'organo' => __('Órgano', 'ull-normativa'),
                        'materia' => __('Materia', 'ull-normativa'),
                    );
                    foreach ($grupos as $key => $label) :
                        $is_active = ($atts['agrupar_por'] === $key);
                        $tab_url = add_query_arg('agrupar', $key, $archive_url);
                    ?>
                    <a href="<?php echo esc_url($tab_url); ?>" 
                       class="ull-archivo-tab <?php echo $is_active ? 'active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="ull-archivo-contenido">
                <?php
                // Detectar agrupación desde URL
                $agrupar = isset($_GET['agrupar']) ? sanitize_text_field($_GET['agrupar']) : $atts['agrupar_por'];
                
                switch ($agrupar) {
                    case 'tipo':
                        $this->render_archivo_por_taxonomia('tipo_norma', 'ull_tipo');
                        break;
                    case 'organo':
                        $this->render_archivo_por_taxonomia('organo_norma', 'ull_organo');
                        break;
                    case 'materia':
                        $this->render_archivo_por_taxonomia('materia_norma', 'ull_materia');
                        break;
                    case 'categoria':
                        $this->render_archivo_por_taxonomia('categoria_norma', 'ull_categoria');
                        break;
                    default:
                        $this->render_archivo_por_year();
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Renderizar archivo agrupado por año
     */
    private function render_archivo_por_year() {
        global $wpdb;
        $years = $wpdb->get_col("
            SELECT DISTINCT YEAR(meta_value) as year 
            FROM {$wpdb->postmeta} pm 
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE pm.meta_key = '_fecha_aprobacion' 
            AND p.post_type = 'norma' AND p.post_status = 'publish' AND meta_value != ''
            ORDER BY year DESC
        ");
        
        foreach ($years as $year) {
            if (!$year) continue;
            $args = array(
                'post_type' => 'norma',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array(array('key' => '_fecha_aprobacion', 'value' => array($year.'-01-01', $year.'-12-31'), 'compare' => 'BETWEEN', 'type' => 'DATE')),
                'orderby' => 'meta_value',
                'meta_key' => '_fecha_aprobacion',
                'order' => 'DESC',
            );
            $q = new WP_Query($args);
            if ($q->have_posts()) {
                $this->render_archivo_grupo($year, $q);
                wp_reset_postdata();
            }
        }
    }
    
    /**
     * Renderizar archivo agrupado por taxonomía
     */
    private function render_archivo_por_taxonomia($taxonomy, $param) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            echo '<p>' . __('No hay normas clasificadas en esta categoría.', 'ull-normativa') . '</p>';
            return;
        }
        
        foreach ($terms as $term) {
            $args = array(
                'post_type' => 'norma',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    ),
                ),
                'meta_key' => '_fecha_aprobacion',
                'orderby' => 'meta_value',
                'order' => 'DESC',
            );
            $q = new WP_Query($args);
            if ($q->have_posts()) {
                $this->render_archivo_grupo($term->name, $q, $param, $term->slug);
                wp_reset_postdata();
            }
        }
    }
    
    /**
     * Renderizar un grupo del archivo
     */
    private function render_archivo_grupo($titulo, $query, $filter_param = '', $filter_value = '') {
        $archive_url = $this->get_normativa_url();
        ?>
        <div class="ull-archivo-grupo">
            <h2 class="ull-archivo-header">
                <?php if ($filter_param && $filter_value) : ?>
                <a href="<?php echo esc_url(add_query_arg($filter_param, $filter_value, $archive_url)); ?>" class="ull-archivo-header-link">
                    <?php echo esc_html($titulo); ?>
                    <span class="ull-archivo-count">(<?php echo esc_html($query->found_posts); ?>)</span>
                </a>
                <?php else : ?>
                <span>
                    <?php echo esc_html($titulo); ?>
                    <span class="ull-archivo-count">(<?php echo esc_html($query->found_posts); ?>)</span>
                </span>
                <?php endif; ?>
            </h2>
            <ul class="ull-archivo-lista">
                <?php while ($query->have_posts()) : $query->the_post(); 
                    $num = get_post_meta(get_the_ID(), '_numero_norma', true);
                    $estado = get_post_meta(get_the_ID(), '_estado_norma', true);
                    $fecha = get_post_meta(get_the_ID(), '_fecha_aprobacion', true);
                ?>
                <li class="ull-archivo-item">
                    <a href="<?php echo get_permalink(); ?>">
                        <?php if ($num) : ?><span class="ull-archivo-numero"><?php echo esc_html($num); ?></span><?php endif; ?>
                        <span class="ull-archivo-titulo"><?php echo esc_html(get_the_title()); ?></span>
                        <?php if ($fecha) : ?><span class="ull-archivo-fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($fecha))); ?></span><?php endif; ?>
                    </a>
                    <?php if ($estado) : ?>
                    <span class="ull-estado-badge ull-estado-<?php echo esc_attr($estado); ?>"><?php echo esc_html(ucfirst($estado)); ?></span>
                    <?php endif; ?>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <?php
    }
    
    private function render_taxonomy_select($name, $taxonomy, $default_label) {
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => true));
        if (is_wp_error($terms) || empty($terms)) return '';
        
        $selected = isset($_GET[$name]) ? sanitize_text_field($_GET[$name]) : '';
        $html = '<select name="' . esc_attr($name) . '" class="ull-filter-select">';
        $html .= '<option value="">' . esc_html($default_label) . '</option>';
        foreach ($terms as $term) {
            $html .= '<option value="' . esc_attr($term->slug) . '" ' . selected($selected, $term->slug, false) . '>' . esc_html($term->name) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
    
    private function render_estado_select($name = 'ull_estado') {
        $estados = array(
            'vigente' => __('Vigente', 'ull-normativa'), 
            'derogada' => __('Derogada', 'ull-normativa'), 
            'modificada' => __('Modificada', 'ull-normativa'), 
            'pendiente' => __('Pendiente', 'ull-normativa')
        );
        $selected = isset($_GET[$name]) ? sanitize_text_field($_GET[$name]) : '';
        $html = '<select name="' . esc_attr($name) . '" class="ull-filter-select">';
        $html .= '<option value="">' . __('Todos los estados', 'ull-normativa') . '</option>';
        foreach ($estados as $val => $lbl) {
            $html .= '<option value="' . esc_attr($val) . '" ' . selected($selected, $val, false) . '>' . esc_html($lbl) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
    
    private function get_section_label($sec) {
        $labels = array(
            'info' => __('Información', 'ull-normativa'), 
            'contenido' => __('Contenido', 'ull-normativa'), 
            'versiones' => __('Versiones', 'ull-normativa'), 
            'relaciones' => __('Relaciones', 'ull-normativa'), 
            'documentos' => __('Documentos', 'ull-normativa')
        );
        return isset($labels[$sec]) ? $labels[$sec] : ucfirst($sec);
    }
    
    private function render_section($post_id, $sec) {
        switch ($sec) {
            case 'info':
                return $this->render_info($post_id);
            case 'contenido':
                $post = get_post($post_id);
                // No usar apply_filters('the_content') para evitar loop infinito
                // Aplicar solo los filtros esenciales de formato
                $content = $post->post_content;
                if ($content) {
                    $content = wpautop($content);
                    $content = do_shortcode($content);
                    $content = wptexturize($content);
                    return '<div class="ull-contenido-norma">' . $content . '</div>';
                }
                return '<p>' . __('No hay contenido disponible.', 'ull-normativa') . '</p>';
            case 'versiones':
                return $this->render_versiones($post_id);
            case 'relaciones':
                return $this->render_relaciones($post_id);
            case 'documentos':
                return $this->render_documentos($post_id);
            default:
                return '';
        }
    }
    
    private function render_info($post_id) {
        $fields = array(
            '_numero_norma' => __('Número', 'ull-normativa'), 
            '_fecha_aprobacion' => __('Fecha Aprobación', 'ull-normativa'),
            '_fecha_publicacion' => __('Fecha Publicación', 'ull-normativa'), 
            '_fecha_vigencia' => __('Entrada en Vigor', 'ull-normativa'),
            '_fecha_derogacion' => __('Fecha Derogación', 'ull-normativa'),
            '_organo_emisor' => __('Órgano Emisor', 'ull-normativa'), 
            '_boletin_oficial' => __('Boletín Oficial', 'ull-normativa'),
            '_ambito_aplicacion' => __('Ámbito de Aplicación', 'ull-normativa'), 
            '_palabras_clave' => __('Palabras Clave', 'ull-normativa'),
        );
        
        $html = '<div class="ull-info-grid">';
        foreach ($fields as $key => $label) {
            $val = get_post_meta($post_id, $key, true);
            if (!$val) continue;
            if (strpos($key, 'fecha') !== false) {
                $val = date_i18n(get_option('date_format'), strtotime($val));
            }
            $html .= '<div class="ull-info-item"><strong>' . esc_html($label) . ':</strong> <span>' . esc_html($val) . '</span></div>';
        }
        
        // URL del boletín
        $url = get_post_meta($post_id, '_url_boletin', true);
        if ($url) {
            $html .= '<div class="ull-info-item"><strong>' . __('Enlace Oficial', 'ull-normativa') . ':</strong> <a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . __('Ver documento oficial', 'ull-normativa') . ' ↗</a></div>';
        }
        
        // Taxonomías con enlaces
        $archive_url = $this->get_normativa_url();
        $taxonomies = array(
            'tipo_norma' => array(
                'label' => __('Tipo', 'ull-normativa'),
                'param' => 'ull_tipo',
            ),
            'categoria_norma' => array(
                'label' => __('Categoría', 'ull-normativa'),
                'param' => 'ull_categoria',
            ),
            'materia_norma' => array(
                'label' => __('Materia', 'ull-normativa'),
                'param' => 'ull_materia',
            ),
            'organo_norma' => array(
                'label' => __('Órgano', 'ull-normativa'),
                'param' => 'ull_organo',
            ),
        );
        
        foreach ($taxonomies as $tax => $config) {
            $terms = get_the_terms($post_id, $tax);
            if ($terms && !is_wp_error($terms)) {
                $links = array();
                foreach ($terms as $term) {
                    $term_url = add_query_arg($config['param'], $term->slug, $archive_url);
                    $links[] = '<a href="' . esc_url($term_url) . '" class="ull-term-link">' . esc_html($term->name) . '</a>';
                }
                $html .= '<div class="ull-info-item"><strong>' . esc_html($config['label']) . ':</strong> <span>' . implode(', ', $links) . '</span></div>';
            }
        }
        
        $html .= '</div>';
        
        // NUEVO v2.0: Hook para información detallada de versión
        ob_start();
        do_action('ull_normativa_info_tab_content');
        $version_info = ob_get_clean();
        if ($version_info) {
            $html .= $version_info;
        }
        
        return $html;
    }
    
    private function render_versiones($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ull_norma_versions';
        $versions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE norma_id = %d AND is_current = 0 ORDER BY version_date DESC, id DESC", $post_id));
        
        if (empty($versions)) {
            return '<p>' . __('No hay versiones anteriores registradas. El contenido actual es la única versión disponible.', 'ull-normativa') . '</p>';
        }
        
        $norma_url = get_permalink($post_id);
        
        $html = '<p class="ull-versiones-intro">' . __('A continuación se muestran las versiones anteriores de esta norma. Haz clic en cualquiera para ver su contenido.', 'ull-normativa') . '</p>';
        $html .= '<div class="ull-versiones-lista">';
        foreach ($versions as $v) {
            $version_url = add_query_arg('version', $v->id, $norma_url);
            
            $html .= '<div class="ull-version-item">';
            $html .= '<a href="' . esc_url($version_url) . '" class="ull-version-link">';
            $html .= '<div class="ull-version-header">';
            $html .= '<strong class="ull-version-number">v' . esc_html($v->version_number) . '</strong>';
            $html .= '<span class="ull-version-date">' . esc_html(date_i18n(get_option('date_format'), strtotime($v->version_date))) . '</span>';
            $html .= '</div>';
            if ($v->changes_summary) {
                $html .= '<p class="ull-version-summary">' . esc_html($v->changes_summary) . '</p>';
            }
            $html .= '</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    private function render_relaciones($post_id) {
        $relations = new ULL_Relations();
        $grouped = $relations->get_relations_grouped($post_id);
        
        if (empty($grouped)) {
            return '<p>' . __('Esta norma no tiene relaciones con otras normas.', 'ull-normativa') . '</p>';
        }
        
        $html = '<div class="ull-relaciones-lista">';
        foreach ($grouped as $type => $data) {
            $html .= '<div class="ull-relacion-grupo">';
            $html .= '<h4 class="ull-relacion-tipo">' . esc_html($data['label']) . '</h4>';
            $html .= '<ul class="ull-relacion-items">';
            foreach ($data['items'] as $item) {
                $html .= '<li class="ull-relacion-item">';
                $html .= '<a href="' . esc_url($item['url']) . '">';
                if ($item['numero']) {
                    $html .= '<span class="ull-relacion-numero">' . esc_html($item['numero']) . '</span> ';
                }
                $html .= '<span class="ull-relacion-titulo">' . esc_html($item['title']) . '</span>';
                $html .= '</a>';
                if ($item['estado']) {
                    $html .= ' <span class="ull-estado-badge ull-estado-' . esc_attr($item['estado']) . '">' . esc_html(ucfirst($item['estado'])) . '</span>';
                }
                $html .= '</li>';
            }
            $html .= '</ul></div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    private function render_documentos($post_id) {
        $docs = get_post_meta($post_id, '_documentos_adjuntos', true);
        
        if (empty($docs) || !is_array($docs)) {
            return '<p>' . __('No hay documentos adjuntos.', 'ull-normativa') . '</p>';
        }
        
        $html = '<ul class="ull-documentos-lista">';
        foreach ($docs as $id) {
            $url = wp_get_attachment_url($id);
            if (!$url) continue;
            
            $title = get_the_title($id);
            $filetype = wp_check_filetype($url);
            $filesize = filesize(get_attached_file($id));
            $size_formatted = $filesize ? size_format($filesize) : '';
            
            $html .= '<li class="ull-documento-item">';
            $html .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener" class="ull-documento-link">';
            $html .= '<span class="ull-documento-icon">📄</span>';
            $html .= '<span class="ull-documento-info">';
            $html .= '<span class="ull-documento-nombre">' . esc_html($title) . '</span>';
            if ($filetype['ext'] || $size_formatted) {
                $html .= '<span class="ull-documento-meta">';
                if ($filetype['ext']) $html .= strtoupper($filetype['ext']);
                if ($filetype['ext'] && $size_formatted) $html .= ' • ';
                if ($size_formatted) $html .= $size_formatted;
                $html .= '</span>';
            }
            $html .= '</span>';
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
    
    /**
     * Shortcode: Tabla de contenidos automática
     * Uso: [ull_tabla_contenidos]
     * 
     * Genera una tabla de contenidos basada en los encabezados (h1-h6) del contenido
     * 
     * Atributos:
     * - titulo: Título de la tabla (por defecto: "Índice de contenidos")
     * - niveles: Niveles de encabezados a incluir (por defecto: "1,2,3,4" = h1,h2,h3,h4)
     * - estilo: "lista" o "numerado" (por defecto: "lista")
     * - contraer: "siempre" (siempre contraíble), "auto" (contraíble si >10 items), "no" (nunca contraíble) (por defecto: "siempre")
     * - inicio: "expandido" o "colapsado" - estado inicial (por defecto: "expandido")
     */
    public function tabla_contenidos_shortcode($atts = array()) {
        // Obtener opciones guardadas directamente (sin depender de la clase)
        $toc_options = get_option('ull_toc_options', array(
            'contraer_defecto' => 'siempre',
            'inicio_defecto' => 'colapsado',
            'titulo_defecto' => __('Índice de contenidos', 'ull-normativa'),
            'estilo_defecto' => 'lista',
            'niveles_defecto' => '2,3,4',
        ));
        
        // Usar opciones guardadas como valores por defecto
        $defaults = array(
            'titulo' => isset($toc_options['titulo_defecto']) ? $toc_options['titulo_defecto'] : __('Índice de contenidos', 'ull-normativa'),
            'niveles' => isset($toc_options['niveles_defecto']) ? $toc_options['niveles_defecto'] : '2,3,4',
            'estilo' => isset($toc_options['estilo_defecto']) ? $toc_options['estilo_defecto'] : 'lista',
            'contraer' => isset($toc_options['contraer_defecto']) ? $toc_options['contraer_defecto'] : 'siempre',
            'inicio' => isset($toc_options['inicio_defecto']) ? $toc_options['inicio_defecto'] : 'colapsado',
        );
        
        $atts = shortcode_atts($defaults, $atts);
        
        // Obtener el contenido de la norma actual
        global $post;
        if (!$post || $post->post_type !== 'norma') {
            return '';
        }
        
        $content = $post->post_content;
        if (empty($content)) {
            return '';
        }
        
        // IMPORTANTE: Remover el propio shortcode de tabla de contenidos para evitar bucle infinito
        $content = preg_replace('/\[ull_tabla_contenidos[^\]]*\]/', '', $content);
        
        // Aplicar formato básico (solo wpautop, NO do_shortcode para evitar bucles)
        $content = wpautop($content);
        
        // Niveles permitidos
        $niveles_array = array_map('intval', explode(',', $atts['niveles']));
        $niveles_array = array_filter($niveles_array, function($n) {
            return $n >= 1 && $n <= 6;
        });
        
        if (empty($niveles_array)) {
            $niveles_array = array(1, 2, 3, 4);
        }
        
        // Extraer encabezados
        $headings = $this->extract_headings($content, $niveles_array);
        
        if (empty($headings)) {
            return '<div class="ull-toc-empty"><p><em>' . __('No se encontraron encabezados para generar el índice.', 'ull-normativa') . '</em></p></div>';
        }
        
        // Determinar si debe ser contraíble
        $es_contraible = false;
        $inicia_colapsado = false;
        $total_items = count($headings);
        
        if ($atts['contraer'] === 'siempre' || $atts['contraer'] === 'si') {
            $es_contraible = true;
        } elseif ($atts['contraer'] === 'auto') {
            $es_contraible = $total_items > 10;
            $inicia_colapsado = $total_items > 15;  // Auto-colapsar si >15 items
        }
        
        // Sobrescribir estado inicial si se especificó explícitamente
        if ($atts['inicio'] === 'colapsado' && $es_contraible) {
            $inicia_colapsado = true;
        } elseif ($atts['inicio'] === 'expandido') {
            $inicia_colapsado = false;
        }
        
        // Generar HTML de la tabla de contenidos
        $clases = array('ull-tabla-contenidos', 'ull-toc-' . esc_attr($atts['estilo']));
        if ($es_contraible) {
            $clases[] = 'ull-toc-contraible';
        }
        if ($inicia_colapsado) {
            $clases[] = 'ull-toc-inicialmente-colapsado';
        }
        
        $html = '<div class="' . esc_attr(implode(' ', $clases)) . '" data-total-items="' . esc_attr($total_items) . '">';
        
        if (!empty($atts['titulo'])) {
            $html .= '<div class="ull-toc-header">';
            $html .= '<h2 class="ull-toc-titulo">' . esc_html($atts['titulo']) . '</h2>';
            
            if ($es_contraible) {
                $estado_aria = $inicia_colapsado ? __('Expandir índice', 'ull-normativa') : __('Contraer índice', 'ull-normativa');
                $html .= '<button class="ull-toc-toggle" 
                    onclick="ullToggleTOC(this)" 
                    aria-label="' . esc_attr($estado_aria) . '" 
                    aria-expanded="' . ($inicia_colapsado ? 'false' : 'true') . '">';
                $html .= '<span class="ull-toc-toggle-icon">';
                $html .= '<svg class="ull-toc-icon-expand" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
                $html .= '<path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>';
                $html .= '</svg>';
                $html .= '<svg class="ull-toc-icon-collapse" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
                $html .= '<path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>';
                $html .= '</svg>';
                $html .= '</span>';
                $html .= '<span class="ull-toc-toggle-text">';
                $html .= '<span class="ull-toc-text-expand">' . __('Mostrar', 'ull-normativa') . '</span>';
                $html .= '<span class="ull-toc-text-collapse">' . __('Ocultar', 'ull-normativa') . '</span>';
                $html .= '</span>';
                if ($total_items > 0) {
                    $html .= '<span class="ull-toc-counter">(' . sprintf(_n('%d sección', '%d secciones', $total_items, 'ull-normativa'), $total_items) . ')</span>';
                }
                $html .= '</button>';
            }
            
            $html .= '</div>';
        }
        
        $lista_clases = array('ull-toc-lista');
        if ($inicia_colapsado) {
            $lista_clases[] = 'ull-toc-collapsed';
        }
        
        $html .= '<div class="' . esc_attr(implode(' ', $lista_clases)) . '">';
        
        if ($atts['estilo'] === 'numerado') {
            $html .= $this->generate_toc_numbered($headings);
        } else {
            $html .= $this->generate_toc_list($headings);
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Extraer encabezados del contenido
     */
    private function extract_headings($content, $niveles) {
        $headings = array();
        
        // Construir patrón regex para los niveles solicitados
        $niveles_str = implode('|', $niveles);
        $pattern = '/<h([' . $niveles_str . '])[^>]*>(.*?)<\/h\1>/i';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            $counter = 0;
            foreach ($matches as $match) {
                $level = intval($match[1]);
                $text = strip_tags($match[2]);
                $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                $text = trim($text);
                
                if (empty($text)) {
                    continue;
                }
                
                $counter++;
                $id = $this->generate_heading_id($text, $counter);
                
                $headings[] = array(
                    'level' => $level,
                    'text' => $text,
                    'id' => $id,
                );
            }
        }
        
        return $headings;
    }
    
    /**
     * Generar ID único para un encabezado
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
     * Generar tabla de contenidos estilo lista
     */
    private function generate_toc_list($headings) {
        $html = '<ul class="ull-toc-list">';
        $current_level = 0;
        
        foreach ($headings as $heading) {
            $level = $heading['level'];
            
            // Cerrar niveles anteriores si bajamos de nivel
            while ($current_level > $level) {
                $html .= '</ul></li>';
                $current_level--;
            }
            
            // Abrir nuevo nivel si subimos
            while ($current_level < $level) {
                if ($current_level > 0) {
                    $html .= '<ul class="ull-toc-sublist">';
                }
                $current_level++;
            }
            
            $html .= '<li class="ull-toc-item ull-toc-level-' . esc_attr($level) . '">';
            $html .= '<a href="#' . esc_attr($heading['id']) . '" class="ull-toc-link">';
            $html .= esc_html($heading['text']);
            $html .= '</a>';
            $html .= '</li>';
        }
        
        // Cerrar todos los niveles abiertos
        while ($current_level > 1) {
            $html .= '</ul></li>';
            $current_level--;
        }
        
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Generar tabla de contenidos estilo numerado
     */
    private function generate_toc_numbered($headings) {
        $html = '<ol class="ull-toc-numbered">';
        $current_level = 0;
        
        foreach ($headings as $heading) {
            $level = $heading['level'];
            
            // Cerrar niveles anteriores si bajamos de nivel
            while ($current_level > $level) {
                $html .= '</ol></li>';
                $current_level--;
            }
            
            // Abrir nuevo nivel si subimos
            while ($current_level < $level) {
                if ($current_level > 0) {
                    $html .= '<ol class="ull-toc-subnumbered">';
                }
                $current_level++;
            }
            
            $html .= '<li class="ull-toc-item ull-toc-level-' . esc_attr($level) . '">';
            $html .= '<a href="#' . esc_attr($heading['id']) . '" class="ull-toc-link">';
            $html .= esc_html($heading['text']);
            $html .= '</a>';
            $html .= '</li>';
        }
        
        // Cerrar todos los niveles abiertos
        while ($current_level > 1) {
            $html .= '</ol></li>';
            $current_level--;
        }
        
        $html .= '</ol>';
        
        return $html;
    }
    
    /**
     * Filtro: Añadir IDs a los encabezados del contenido
     * Prioridad 100 = Se ejecuta DESPUÉS de wpautop y do_shortcode
     */
    public function add_heading_ids($content) {
        // Solo aplicar en post type 'norma'
        if (!is_singular('norma')) {
            return $content;
        }
        
        // Verificar si hay tabla de contenidos en el contenido procesado
        $has_toc = (strpos($content, 'ull-tabla-contenidos') !== false || 
                    strpos($content, 'ull-toc-link') !== false);
        
        if (!$has_toc) {
            return $content;
        }
        
        // Debug solo si WP_DEBUG está activo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ULL TOC: Procesando contenido para añadir IDs en norma ID ' . get_the_ID());
        }
        
        // Obtener encabezados y añadir IDs
        $counter = 0;
        $pattern = '/<h([1-6])([^>]*)>(.*?)<\/h\1>/is';
        
        $content = preg_replace_callback($pattern, function($matches) use (&$counter) {
            $level = $matches[1];
            $attributes = $matches[2];
            $text = $matches[3];
            
            // Si ya tiene ID, no sobrescribir
            if (preg_match('/id\s*=\s*["\']([^"\']+)["\']/i', $attributes)) {
                return $matches[0];
            }
            
            $counter++;
            $text_plain = strip_tags($text);
            $text_plain = html_entity_decode($text_plain, ENT_QUOTES, 'UTF-8');
            $text_plain = trim($text_plain);
            $id = $this->generate_heading_id($text_plain, $counter);
            
            // Debug solo si WP_DEBUG está activo
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ULL TOC: ✓ Añadiendo ID "' . $id . '" a H' . $level . ': ' . substr($text_plain, 0, 40));
            }
            
            // Añadir el ID al encabezado
            $new_heading = '<h' . $level . ' id="' . esc_attr($id) . '"' . $attributes . '>' . $text . '</h' . $level . '>';
            
            return $new_heading;
        }, $content);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ULL TOC: ✓ Total de ' . $counter . ' IDs añadidos');
        }
        
        return $content;
    }
}

new ULL_Shortcodes();
