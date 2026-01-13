<?php
/**
 * Columnas personalizadas en el listado de normas
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Admin_Columns {
    
    public function __construct() {
        add_filter('manage_norma_posts_columns', array($this, 'set_columns'));
        add_action('manage_norma_posts_custom_column', array($this, 'render_column'), 10, 2);
        add_filter('manage_edit-norma_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'orderby_columns'));
        add_action('restrict_manage_posts', array($this, 'add_filters'));
        add_action('pre_get_posts', array($this, 'filter_query'));
    }
    
    public function set_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Título', 'ull-normativa');
        $new_columns['numero'] = __('Número', 'ull-normativa');
        $new_columns['tipo'] = __('Tipo', 'ull-normativa');
        $new_columns['estado'] = __('Estado', 'ull-normativa');
        $new_columns['fecha_aprobacion'] = __('Fecha Aprobación', 'ull-normativa');
        $new_columns['version'] = __('Versión', 'ull-normativa');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    public function render_column($column, $post_id) {
        switch ($column) {
            case 'numero':
                $numero = get_post_meta($post_id, '_numero_norma', true);
                echo $numero ? esc_html($numero) : '—';
                break;
                
            case 'tipo':
                $terms = get_the_terms($post_id, 'tipo_norma');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = wp_list_pluck($terms, 'name');
                    echo esc_html(implode(', ', $term_names));
                } else {
                    echo '—';
                }
                break;
                
            case 'estado':
                $estado = get_post_meta($post_id, '_estado_norma', true);
                $estados = array(
                    'vigente' => array('label' => __('Vigente', 'ull-normativa'), 'class' => 'ull-status-vigente'),
                    'derogada' => array('label' => __('Derogada', 'ull-normativa'), 'class' => 'ull-status-derogada'),
                    'modificada' => array('label' => __('Modificada', 'ull-normativa'), 'class' => 'ull-status-modificada'),
                    'pendiente' => array('label' => __('Pendiente', 'ull-normativa'), 'class' => 'ull-status-pendiente'),
                );
                if (isset($estados[$estado])) {
                    printf('<span class="ull-status-badge %s">%s</span>', esc_attr($estados[$estado]['class']), esc_html($estados[$estado]['label']));
                } else {
                    echo '—';
                }
                break;
                
            case 'fecha_aprobacion':
                $fecha = get_post_meta($post_id, '_fecha_aprobacion', true);
                echo $fecha ? esc_html(date_i18n(get_option('date_format'), strtotime($fecha))) : '—';
                break;
                
            case 'version':
                $version = get_post_meta($post_id, '_version_actual', true);
                echo $version ? esc_html($version) : '1.0';
                break;
        }
    }
    
    public function sortable_columns($columns) {
        $columns['numero'] = 'numero';
        $columns['fecha_aprobacion'] = 'fecha_aprobacion';
        $columns['estado'] = 'estado';
        return $columns;
    }
    
    public function orderby_columns($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'norma') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'numero':
                $query->set('meta_key', '_numero_norma');
                $query->set('orderby', 'meta_value');
                break;
            case 'fecha_aprobacion':
                $query->set('meta_key', '_fecha_aprobacion');
                $query->set('orderby', 'meta_value');
                break;
            case 'estado':
                $query->set('meta_key', '_estado_norma');
                $query->set('orderby', 'meta_value');
                break;
        }
    }
    
    public function add_filters($post_type) {
        if ($post_type !== 'norma') {
            return;
        }
        
        // Filtro por tipo
        $tipos = get_terms(array(
            'taxonomy' => 'tipo_norma',
            'hide_empty' => true,
        ));
        
        if (!empty($tipos) && !is_wp_error($tipos)) {
            $selected = isset($_GET['tipo_norma']) ? sanitize_text_field($_GET['tipo_norma']) : '';
            echo '<select name="tipo_norma">';
            echo '<option value="">' . esc_html__('Todos los tipos', 'ull-normativa') . '</option>';
            foreach ($tipos as $tipo) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($tipo->slug),
                    selected($selected, $tipo->slug, false),
                    esc_html($tipo->name)
                );
            }
            echo '</select>';
        }
        
        // Filtro por estado
        $estados = array(
            'vigente' => __('Vigente', 'ull-normativa'),
            'derogada' => __('Derogada', 'ull-normativa'),
            'modificada' => __('Modificada', 'ull-normativa'),
            'pendiente' => __('Pendiente', 'ull-normativa'),
        );
        
        $selected_estado = isset($_GET['estado_norma']) ? sanitize_text_field($_GET['estado_norma']) : '';
        echo '<select name="estado_norma">';
        echo '<option value="">' . esc_html__('Todos los estados', 'ull-normativa') . '</option>';
        foreach ($estados as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($selected_estado, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
        
        // Filtro por año
        global $wpdb;
        $years = $wpdb->get_col("
            SELECT DISTINCT YEAR(meta_value) as year 
            FROM {$wpdb->postmeta} pm 
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE pm.meta_key = '_fecha_aprobacion' 
            AND p.post_type = 'norma' 
            AND meta_value != '' 
            ORDER BY year DESC
        ");
        
        if (!empty($years)) {
            $selected_year = isset($_GET['year_norma']) ? sanitize_text_field($_GET['year_norma']) : '';
            echo '<select name="year_norma">';
            echo '<option value="">' . esc_html__('Todos los años', 'ull-normativa') . '</option>';
            foreach ($years as $year) {
                if (!$year) continue;
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($year),
                    selected($selected_year, $year, false),
                    esc_html($year)
                );
            }
            echo '</select>';
        }
    }
    
    public function filter_query($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'norma') {
            return;
        }
        
        $meta_query = array();
        
        if (!empty($_GET['estado_norma'])) {
            $meta_query[] = array(
                'key' => '_estado_norma',
                'value' => sanitize_text_field($_GET['estado_norma']),
            );
        }
        
        if (!empty($_GET['year_norma'])) {
            $year = intval($_GET['year_norma']);
            $meta_query[] = array(
                'key' => '_fecha_aprobacion',
                'value' => array($year . '-01-01', $year . '-12-31'),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            );
        }
        
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }
}

new ULL_Admin_Columns();
