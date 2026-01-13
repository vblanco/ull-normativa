<?php
/**
 * Cargador de plantillas para el frontend
 * Compatible con temas clásicos y FSE (Full Site Editing)
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Template_Loader {
    
    public function __construct() {
        // Solo cargar plantillas del plugin si el tema no es FSE o no tiene plantillas propias
        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));
        add_filter('taxonomy_template', array($this, 'taxonomy_template'));
        
        // Para temas FSE, usar el contenido del shortcode automáticamente
        add_filter('the_content', array($this, 'auto_ficha_content'), 5);
    }
    
    /**
     * Verificar si el tema actual es un tema de bloques (FSE)
     */
    private function is_block_theme() {
        return function_exists('wp_is_block_theme') && wp_is_block_theme();
    }
    
    /**
     * Verificar si el tema FSE tiene una plantilla específica
     */
    private function theme_has_block_template($template_name) {
        if (!$this->is_block_theme()) {
            return false;
        }
        
        $theme_dir = get_stylesheet_directory();
        $parent_dir = get_template_directory();
        
        // Buscar en tema hijo y padre
        $templates_to_check = array(
            $theme_dir . '/templates/' . $template_name . '.html',
            $parent_dir . '/templates/' . $template_name . '.html',
        );
        
        foreach ($templates_to_check as $template_path) {
            if (file_exists($template_path)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function single_template($template) {
        if (!is_singular('norma')) {
            return $template;
        }
        
        // Si es tema FSE con plantilla single-norma.html, dejar que WordPress la use
        if ($this->theme_has_block_template('single-norma')) {
            return $template;
        }
        
        // Si es tema FSE sin plantilla específica, usar single.html del tema
        if ($this->is_block_theme()) {
            return $template;
        }
        
        // Para temas clásicos, buscar plantilla PHP en el tema
        $theme_template = locate_template('single-norma.php');
        if ($theme_template) {
            return $theme_template;
        }
        
        // Fallback a plantilla del plugin
        $plugin_template = ULL_NORMATIVA_PLUGIN_DIR . 'templates/single-norma.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return $template;
    }
    
    public function archive_template($template) {
        if (!is_post_type_archive('norma')) {
            return $template;
        }
        
        // Si es tema FSE con plantilla archive-norma.html, dejar que WordPress la use
        if ($this->theme_has_block_template('archive-norma')) {
            return $template;
        }
        
        // Si es tema FSE sin plantilla específica, usar archive.html o index.html del tema
        if ($this->is_block_theme()) {
            return $template;
        }
        
        // Para temas clásicos, buscar plantilla PHP en el tema
        $theme_template = locate_template('archive-norma.php');
        if ($theme_template) {
            return $theme_template;
        }
        
        // Fallback a plantilla del plugin
        $plugin_template = ULL_NORMATIVA_PLUGIN_DIR . 'templates/archive-norma.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return $template;
    }
    
    public function taxonomy_template($template) {
        $taxonomies = array('tipo_norma', 'categoria_norma', 'materia_norma', 'organo_norma');
        $current_tax = null;
        
        foreach ($taxonomies as $taxonomy) {
            if (is_tax($taxonomy)) {
                $current_tax = $taxonomy;
                break;
            }
        }
        
        if (!$current_tax) {
            return $template;
        }
        
        // Si es tema FSE, dejar que use sus plantillas
        if ($this->is_block_theme()) {
            return $template;
        }
        
        // Para temas clásicos
        $theme_template = locate_template(array(
            'taxonomy-' . $current_tax . '.php',
            'taxonomy-norma.php',
            'archive-norma.php',
        ));
        
        if ($theme_template) {
            return $theme_template;
        }
        
        $plugin_template = ULL_NORMATIVA_PLUGIN_DIR . 'templates/archive-norma.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return $template;
    }
    
    /**
     * Insertar automáticamente el shortcode de ficha en el contenido de normas
     * Solo para temas FSE que no tengan el shortcode ya incluido
     */
    public function auto_ficha_content($content) {
        // Solo en singular de norma y en el loop principal
        if (!is_singular('norma')) {
            return $content;
        }
        
        // Evitar ejecución múltiple
        static $already_run = false;
        if ($already_run) {
            return $content;
        }
        
        // Verificar que estamos en el loop principal
        if (!in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        // Si el contenido ya tiene el shortcode, no hacer nada
        if (has_shortcode($content, 'ull_norma')) {
            return $content;
        }
        
        // Si es tema FSE, generar la ficha completa
        if ($this->is_block_theme()) {
            $already_run = true;
            $post_id = get_the_ID();
            
            // Usar do_shortcode directamente
            return do_shortcode('[ull_norma id="' . $post_id . '"]');
        }
        
        return $content;
    }
    
    /**
     * Helper para cargar partes de plantilla
     */
    public static function get_template_part($slug, $name = '', $args = array()) {
        $templates = array();
        
        if ($name) {
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";
        
        $template = locate_template($templates);
        
        if (!$template) {
            foreach ($templates as $template_name) {
                $plugin_template = ULL_NORMATIVA_PLUGIN_DIR . 'templates/' . $template_name;
                if (file_exists($plugin_template)) {
                    $template = $plugin_template;
                    break;
                }
            }
        }
        
        if ($template) {
            if (!empty($args) && is_array($args)) {
                extract($args);
            }
            include $template;
        }
    }
}

new ULL_Template_Loader();
