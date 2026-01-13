<?php
/**
 * Control de versiones para las normas
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Version_Control {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ull_norma_versions';
        
        add_action('wp_ajax_ull_get_version', array($this, 'ajax_get_version'));
        add_action('wp_ajax_ull_restore_version', array($this, 'ajax_restore_version'));
        add_action('wp_ajax_ull_delete_version', array($this, 'ajax_delete_version'));
    }
    
    public function create_version($norma_id, $version_number = '', $changes_summary = '') {
        global $wpdb;
        
        $post = get_post($norma_id);
        if (!$post || $post->post_type !== 'norma') {
            return null;
        }
        
        $content = $post->post_content;
        
        $current_version = get_post_meta($norma_id, '_version_actual', true);
        if (empty($current_version)) {
            $current_version = '1.0';
        }
        
        if (empty($version_number)) {
            $version_number = $this->increment_version($current_version);
        }
        
        // Marcar versiones anteriores como no actuales
        $wpdb->update(
            $this->table_name,
            array('is_current' => 0),
            array('norma_id' => $norma_id, 'is_current' => 1),
            array('%d'),
            array('%d', '%d')
        );
        
        // Insertar nueva versión
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'norma_id' => $norma_id,
                'version_number' => $version_number,
                'version_date' => current_time('Y-m-d'),
                'content' => $content,
                'changes_summary' => $changes_summary,
                'is_current' => 1,
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            update_post_meta($norma_id, '_version_actual', $version_number);
            return $wpdb->insert_id;
        }
        
        return null;
    }
    
    public function get_versions($norma_id, $include_content = false) {
        global $wpdb;
        
        $columns = $include_content 
            ? '*' 
            : 'id, norma_id, version_number, version_date, changes_summary, is_current, created_by, created_at';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT $columns FROM {$this->table_name} 
             WHERE norma_id = %d 
             ORDER BY version_date DESC, id DESC",
            $norma_id
        ));
        
        return $results ? $results : array();
    }
    
    public function get_version($version_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $version_id
        ));
    }
    
    public function restore_version($version_id) {
        global $wpdb;
        
        $version = $this->get_version($version_id);
        if (!$version) {
            return false;
        }
        
        // Guardar versión actual antes de restaurar
        $this->create_version(
            $version->norma_id, 
            '', 
            sprintf(__('Respaldo antes de restaurar versión %s', 'ull-normativa'), $version->version_number)
        );
        
        // Actualizar contenido del post
        wp_update_post(array(
            'ID' => $version->norma_id,
            'post_content' => $version->content,
        ));
        
        // Marcar como actual
        $wpdb->update(
            $this->table_name,
            array('is_current' => 0),
            array('norma_id' => $version->norma_id),
            array('%d'),
            array('%d')
        );
        
        $wpdb->update(
            $this->table_name,
            array('is_current' => 1),
            array('id' => $version_id),
            array('%d'),
            array('%d')
        );
        
        update_post_meta($version->norma_id, '_version_actual', $version->version_number);
        
        return true;
    }
    
    public function delete_version($version_id) {
        global $wpdb;
        
        $version = $this->get_version($version_id);
        if (!$version || $version->is_current) {
            return false;
        }
        
        return (bool) $wpdb->delete(
            $this->table_name,
            array('id' => $version_id),
            array('%d')
        );
    }
    
    private function increment_version($version) {
        $parts = explode('.', $version);
        
        if (count($parts) >= 2) {
            $parts[count($parts) - 1] = intval(end($parts)) + 1;
        } else {
            $parts[] = '1';
        }
        
        return implode('.', $parts);
    }
    
    public function ajax_get_version() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        $version_id = isset($_POST['version_id']) ? absint($_POST['version_id']) : 0;
        $version = $this->get_version($version_id);
        
        if (!$version) {
            wp_send_json_error(__('Versión no encontrada', 'ull-normativa'));
        }
        
        wp_send_json_success(array(
            'id' => $version->id,
            'version_number' => $version->version_number,
            'version_date' => $version->version_date,
            'changes_summary' => $version->changes_summary,
            'content' => $version->content,
            'is_current' => (bool) $version->is_current,
        ));
    }
    
    public function ajax_restore_version() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        $version_id = isset($_POST['version_id']) ? absint($_POST['version_id']) : 0;
        
        if ($this->restore_version($version_id)) {
            wp_send_json_success(__('Versión restaurada correctamente', 'ull-normativa'));
        } else {
            wp_send_json_error(__('Error al restaurar la versión', 'ull-normativa'));
        }
    }
    
    public function ajax_delete_version() {
        check_ajax_referer('ull_normativa_admin_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(__('Permiso denegado', 'ull-normativa'));
        }
        
        $version_id = isset($_POST['version_id']) ? absint($_POST['version_id']) : 0;
        
        if ($this->delete_version($version_id)) {
            wp_send_json_success(__('Versión eliminada correctamente', 'ull-normativa'));
        } else {
            wp_send_json_error(__('No se puede eliminar esta versión', 'ull-normativa'));
        }
    }
}

new ULL_Version_Control();
