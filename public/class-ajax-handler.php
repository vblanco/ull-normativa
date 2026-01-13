<?php
/**
 * Manejador de peticiones AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Ajax_Handler {
    
    public function __construct() {
        add_action('wp_ajax_ull_search_normas', array($this, 'search_normas'));
        add_action('wp_ajax_nopriv_ull_search_normas', array($this, 'search_normas'));
        
        add_action('wp_ajax_ull_search_suggestions', array($this, 'search_suggestions'));
        add_action('wp_ajax_nopriv_ull_search_suggestions', array($this, 'search_suggestions'));
        
        add_action('wp_ajax_ull_get_version_content', array($this, 'get_version_content'));
        add_action('wp_ajax_nopriv_ull_get_version_content', array($this, 'get_version_content'));
        
        add_action('wp_ajax_ull_filter_normativa', array($this, 'filter_normativa'));
        add_action('wp_ajax_nopriv_ull_filter_normativa', array($this, 'filter_normativa'));
    }
    
    public function search_normas() {
        check_ajax_referer('ull_normativa_nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        
        $args = array(
            'post_type' => 'norma',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $search,
        );
        
        $tax_query = array();
        $meta_query = array();
        
        if (!empty($tipo)) {
            $tax_query[] = array(
                'taxonomy' => 'tipo_norma',
                'field' => 'slug',
                'terms' => $tipo,
            );
        }
        
        if (!empty($categoria)) {
            $tax_query[] = array(
                'taxonomy' => 'categoria_norma',
                'field' => 'slug',
                'terms' => $categoria,
            );
        }
        
        if (!empty($estado)) {
            $meta_query[] = array(
                'key' => '_estado_norma',
                'value' => $estado,
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = $this->format_result(get_post());
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'results' => $results,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ));
    }
    
    public function search_suggestions() {
        check_ajax_referer('ull_normativa_nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        if (strlen($search) < 2) {
            wp_send_json_success(array());
        }
        
        global $wpdb;
        
        $suggestions = array();
        
        // Buscar en títulos
        $titles = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_type = 'norma' AND post_status = 'publish' 
             AND post_title LIKE %s 
             LIMIT 5",
            '%' . $wpdb->esc_like($search) . '%'
        ));
        
        foreach ($titles as $post) {
            $suggestions[] = array(
                'id' => $post->ID,
                'text' => $post->post_title,
                'type' => 'title',
                'url' => get_permalink($post->ID),
            );
        }
        
        // Buscar en números
        $numeros = $wpdb->get_results($wpdb->prepare(
            "SELECT pm.post_id, pm.meta_value, p.post_title 
             FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_numero_norma' 
             AND pm.meta_value LIKE %s 
             AND p.post_type = 'norma' AND p.post_status = 'publish' 
             LIMIT 5",
            '%' . $wpdb->esc_like($search) . '%'
        ));
        
        foreach ($numeros as $row) {
            $suggestions[] = array(
                'id' => $row->post_id,
                'text' => $row->meta_value . ' - ' . $row->post_title,
                'type' => 'numero',
                'url' => get_permalink($row->post_id),
            );
        }
        
        // Buscar en palabras clave
        $keywords = $wpdb->get_results($wpdb->prepare(
            "SELECT pm.post_id, pm.meta_value, p.post_title 
             FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_palabras_clave' 
             AND pm.meta_value LIKE %s 
             AND p.post_type = 'norma' AND p.post_status = 'publish' 
             LIMIT 3",
            '%' . $wpdb->esc_like($search) . '%'
        ));
        
        foreach ($keywords as $row) {
            $suggestions[] = array(
                'id' => $row->post_id,
                'text' => $row->post_title,
                'type' => 'keyword',
                'url' => get_permalink($row->post_id),
            );
        }
        
        wp_send_json_success(array_slice($suggestions, 0, 10));
    }
    
    public function get_version_content() {
        check_ajax_referer('ull_normativa_nonce', 'nonce');
        
        $version_id = isset($_POST['version_id']) ? absint($_POST['version_id']) : 0;
        
        if (!$version_id) {
            wp_send_json_error(__('ID de versión no válido', 'ull-normativa'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ull_norma_versions';
        
        $version = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $version_id
        ));
        
        if (!$version) {
            wp_send_json_error(__('Versión no encontrada', 'ull-normativa'));
        }
        
        wp_send_json_success(array(
            'version_number' => $version->version_number,
            'version_date' => date_i18n(get_option('date_format'), strtotime($version->version_date)),
            'content' => $version->content,
            'changes_summary' => $version->changes_summary,
        ));
    }
    
    public function filter_normativa() {
        check_ajax_referer('ull_normativa_nonce', 'nonce');
        
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $year = isset($_POST['year']) ? absint($_POST['year']) : 0;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'list';
        
        $args = array(
            'post_type' => 'norma',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $tax_query = array();
        $meta_query = array();
        
        if (!empty($tipo)) {
            $tax_query[] = array(
                'taxonomy' => 'tipo_norma',
                'field' => 'slug',
                'terms' => $tipo,
            );
        }
        
        if (!empty($categoria)) {
            $tax_query[] = array(
                'taxonomy' => 'categoria_norma',
                'field' => 'slug',
                'terms' => $categoria,
            );
        }
        
        if (!empty($estado)) {
            $meta_query[] = array(
                'key' => '_estado_norma',
                'value' => $estado,
            );
        }
        
        if (!empty($year)) {
            $meta_query[] = array(
                'key' => '_fecha_aprobacion',
                'value' => array($year . '-01-01', $year . '-12-31'),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = $this->format_result(get_post());
            }
            wp_reset_postdata();
        }
        
        $html = $this->render_html($results, $mode);
        
        wp_send_json_success(array(
            'html' => $html,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ));
    }
    
    private function format_result($post) {
        $tipos = get_the_terms($post->ID, 'tipo_norma');
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'url' => get_permalink($post->ID),
            'numero' => get_post_meta($post->ID, '_numero_norma', true),
            'fecha' => get_post_meta($post->ID, '_fecha_aprobacion', true),
            'estado' => get_post_meta($post->ID, '_estado_norma', true),
            'tipo' => $tipos && !is_wp_error($tipos) ? $tipos[0]->name : '',
            'resumen' => $post->post_excerpt,
        );
    }
    
    private function render_html($results, $mode) {
        if (empty($results)) {
            return '<p class="ull-no-results">' . __('No se encontraron normas.', 'ull-normativa') . '</p>';
        }
        
        $html = '';
        
        foreach ($results as $item) {
            $estado_class = 'ull-estado-' . sanitize_html_class($item['estado']);
            
            if ($mode === 'cards') {
                $html .= '<div class="ull-normativa-card">';
                $html .= '<div class="ull-card-header">';
                $html .= '<span class="ull-card-tipo">' . esc_html($item['tipo']) . '</span>';
                $html .= '<span class="ull-card-estado ' . $estado_class . '">' . esc_html(ucfirst($item['estado'])) . '</span>';
                $html .= '</div>';
                $html .= '<h3 class="ull-card-title"><a href="' . esc_url($item['url']) . '">' . esc_html($item['title']) . '</a></h3>';
                if ($item['numero']) {
                    $html .= '<p class="ull-card-numero">' . esc_html($item['numero']) . '</p>';
                }
                if ($item['fecha']) {
                    $html .= '<p class="ull-card-fecha">' . esc_html(date_i18n(get_option('date_format'), strtotime($item['fecha']))) . '</p>';
                }
                $html .= '</div>';
            } else {
                $html .= '<div class="ull-normativa-item">';
                $html .= '<div class="ull-item-content">';
                $html .= '<h3 class="ull-item-title"><a href="' . esc_url($item['url']) . '">' . esc_html($item['title']) . '</a></h3>';
                $html .= '<div class="ull-item-meta">';
                if ($item['tipo']) {
                    $html .= '<span class="ull-meta-tipo">' . esc_html($item['tipo']) . '</span>';
                }
                if ($item['numero']) {
                    $html .= '<span class="ull-meta-numero">' . esc_html($item['numero']) . '</span>';
                }
                if ($item['fecha']) {
                    $html .= '<span class="ull-meta-fecha">' . esc_html(date_i18n(get_option('date_format'), strtotime($item['fecha']))) . '</span>';
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<span class="ull-item-estado ' . $estado_class . '">' . esc_html(ucfirst($item['estado'])) . '</span>';
                $html .= '</div>';
            }
        }
        
        return $html;
    }
}

new ULL_Ajax_Handler();
