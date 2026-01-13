<?php
/**
 * Gestión de relaciones entre normas
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Relations {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ull_norma_relations';
        
        add_action('wp_ajax_ull_add_relation', array($this, 'ajax_add_relation'));
        add_action('wp_ajax_ull_delete_relation', array($this, 'ajax_delete_relation'));
        add_action('wp_ajax_ull_search_normas', array($this, 'ajax_search_normas'));
        add_action('before_delete_post', array($this, 'delete_norma_relations'));
    }
    
    public function get_relation_types() {
        return array(
            'deroga' => __('Deroga a', 'ull-normativa'),
            'derogada_por' => __('Derogada por', 'ull-normativa'),
            'modifica' => __('Modifica a', 'ull-normativa'),
            'modificada_por' => __('Modificada por', 'ull-normativa'),
            'desarrolla' => __('Desarrolla a', 'ull-normativa'),
            'desarrollada_por' => __('Desarrollada por', 'ull-normativa'),
            'complementa' => __('Complementa a', 'ull-normativa'),
            'complementada_por' => __('Complementada por', 'ull-normativa'),
            'cita' => __('Cita a', 'ull-normativa'),
            'citada_por' => __('Citada por', 'ull-normativa'),
            'relacionada' => __('Relacionada con', 'ull-normativa'),
        );
    }
    
    public function get_inverse_relation($type) {
        $inverses = array(
            'deroga' => 'derogada_por',
            'derogada_por' => 'deroga',
            'modifica' => 'modificada_por',
            'modificada_por' => 'modifica',
            'desarrolla' => 'desarrollada_por',
            'desarrollada_por' => 'desarrolla',
            'complementa' => 'complementada_por',
            'complementada_por' => 'complementa',
            'cita' => 'citada_por',
            'citada_por' => 'cita',
            'relacionada' => 'relacionada',
        );
        
        return isset($inverses[$type]) ? $inverses[$type] : null;
    }
    
    public function add_relation($norma_id, $related_norma_id, $relation_type, $notes = '') {
        global $wpdb;
        
        if (!get_post($norma_id) || !get_post($related_norma_id)) {
            return null;
        }
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE norma_id = %d AND related_norma_id = %d AND relation_type = %s",
            $norma_id, $related_norma_id, $relation_type
        ));
        
        if ($exists) {
            return intval($exists);
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'norma_id' => $norma_id,
                'related_norma_id' => $related_norma_id,
                'relation_type' => $relation_type,
                'notes' => $notes,
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        if (!$result) {
            return null;
        }
        
        $relation_id = $wpdb->insert_id;
        
        // Crear relación inversa
        $inverse_type = $this->get_inverse_relation($relation_type);
        if ($inverse_type) {
            $this->add_inverse_relation($related_norma_id, $norma_id, $inverse_type, $notes);
        }
        
        return $relation_id;
    }
    
    private function add_inverse_relation($norma_id, $related_norma_id, $relation_type, $notes = '') {
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE norma_id = %d AND related_norma_id = %d AND relation_type = %s",
            $norma_id, $related_norma_id, $relation_type
        ));
        
        if (!$exists) {
            $wpdb->insert(
                $this->table_name,
                array(
                    'norma_id' => $norma_id,
                    'related_norma_id' => $related_norma_id,
                    'relation_type' => $relation_type,
                    'notes' => $notes,
                ),
                array('%d', '%d', '%s', '%s')
            );
        }
    }
    
    public function get_relations($norma_id, $type = null) {
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->table_name} WHERE norma_id = %d";
        $params = array($norma_id);
        
        if ($type) {
            $sql .= " AND relation_type = %s";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY relation_type, created_at DESC";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        return $results ? $results : array();
    }
    
    public function get_relations_grouped($norma_id) {
        $relations = $this->get_relations($norma_id);
        $grouped = array();
        $types = $this->get_relation_types();
        
        foreach ($relations as $relation) {
            $type = $relation->relation_type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = array(
                    'label' => isset($types[$type]) ? $types[$type] : $type,
                    'items' => array(),
                );
            }
            
            $related_post = get_post($relation->related_norma_id);
            if ($related_post) {
                $grouped[$type]['items'][] = array(
                    'id' => $relation->id,
                    'norma_id' => $relation->related_norma_id,
                    'title' => $related_post->post_title,
                    'numero' => get_post_meta($relation->related_norma_id, '_numero_norma', true),
                    'estado' => get_post_meta($relation->related_norma_id, '_estado_norma', true),
                    'url' => get_permalink($relation->related_norma_id),
                    'notes' => $relation->notes,
                );
            }
        }
        
        return $grouped;
    }
    
    public function delete_relation($relation_id) {
        global $wpdb;
        
        $relation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $relation_id
        ));
        
        if (!$relation) {
            return false;
        }
        
        $result = $wpdb->delete($this->table_name, array('id' => $relation_id), array('%d'));
        
        if ($result) {
            $inverse_type = $this->get_inverse_relation($relation->relation_type);
            if ($inverse_type) {
                $wpdb->delete(
                    $this->table_name,
                    array(
                        'norma_id' => $relation->related_norma_id,
                        'related_norma_id' => $relation->norma_id,
                        'relation_type' => $inverse_type,
                    ),
                    array('%d', '%d', '%s')
                );
            }
        }
        
        return (bool) $result;
    }
    
    public function delete_norma_relations($post_id) {
        if (get_post_type($post_id) !== 'norma') {
            return;
        }
        
        global $wpdb;
        $wpdb->delete($this->table_name, array('norma_id' => $post_id), array('%d'));
        $wpdb->delete($this->table_name, array('related_norma_id' => $post_id), array('%d'));
    }
    
    public function ajax_add_relation() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        $norma_id = isset($_POST['norma_id']) ? absint($_POST['norma_id']) : 0;
        $related_norma_id = isset($_POST['related_norma_id']) ? absint($_POST['related_norma_id']) : 0;
        $relation_type = isset($_POST['relation_type']) ? sanitize_text_field($_POST['relation_type']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if (!$norma_id || !$related_norma_id || !$relation_type) {
            wp_send_json_error(__('Datos incompletos', 'ull-normativa'));
        }
        
        if ($norma_id === $related_norma_id) {
            wp_send_json_error(__('Una norma no puede relacionarse consigo misma', 'ull-normativa'));
        }
        
        $result = $this->add_relation($norma_id, $related_norma_id, $relation_type, $notes);
        
        if ($result) {
            $related_post = get_post($related_norma_id);
            wp_send_json_success(array(
                'id' => $result,
                'norma_id' => $related_norma_id,
                'title' => $related_post->post_title,
                'relation_type' => $relation_type,
                'notes' => $notes,
            ));
        } else {
            wp_send_json_error(__('Error al crear la relación', 'ull-normativa'));
        }
    }
    
    public function ajax_delete_relation() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        $relation_id = isset($_POST['relation_id']) ? absint($_POST['relation_id']) : 0;
        
        if ($this->delete_relation($relation_id)) {
            wp_send_json_success(__('Relación eliminada', 'ull-normativa'));
        } else {
            wp_send_json_error(__('Error al eliminar la relación', 'ull-normativa'));
        }
    }
    
    public function ajax_search_normas() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $exclude = isset($_POST['exclude']) ? absint($_POST['exclude']) : 0;
        
        $args = array(
            'post_type' => 'norma',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search,
            'post__not_in' => $exclude ? array($exclude) : array(),
        );
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'numero' => get_post_meta(get_the_ID(), '_numero_norma', true),
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($results);
    }
}

new ULL_Relations();
