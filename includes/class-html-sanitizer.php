<?php
/**
 * Sanitizador de HTML para el contenido de las normas
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_HTML_Sanitizer {
    
    private $allowed_tags;
    
    public function __construct() {
        $this->allowed_tags = $this->get_allowed_tags();
    }
    
    public function sanitize($html) {
        if (empty($html)) {
            return '';
        }
        
        // Eliminar scripts y estilos
        $html = $this->remove_dangerous_content($html);
        
        // Limpiar tags
        $html = $this->filter_tags($html);
        
        // Limpiar atributos peligrosos
        $html = $this->filter_attributes($html);
        
        // Eliminar estilos inline
        if (get_option('ull_normativa_remove_inline_styles', true)) {
            $html = $this->remove_inline_styles($html);
        }
        
        // Normalizar espacios
        $html = $this->normalize_whitespace($html);
        
        return $html;
    }
    
    private function get_allowed_tags() {
        $custom_tags = get_option('ull_normativa_html_allowed_tags', '');
        
        if (!empty($custom_tags)) {
            preg_match_all('/<([a-z0-9]+)/i', $custom_tags, $matches);
            return array_map('strtolower', isset($matches[1]) ? $matches[1] : array());
        }
        
        return array(
            'div', 'span', 'p', 'br', 'hr',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'strong', 'b', 'em', 'i', 'u',
            'ul', 'ol', 'li',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'a', 'blockquote', 'pre', 'code',
        );
    }
    
    private function remove_dangerous_content($html) {
        // Eliminar scripts
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        
        // Eliminar estilos en bloque
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        // Eliminar comentarios
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Eliminar iframes
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
        
        // Eliminar object y embed
        $html = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $html);
        $html = preg_replace('/<embed\b[^>]*\/?>/is', '', $html);
        
        return $html;
    }
    
    private function filter_tags($html) {
        $kses_allowed = array();
        
        foreach ($this->allowed_tags as $tag) {
            $kses_allowed[$tag] = array(
                'id' => array(),
                'class' => array(),
                'title' => array(),
                'href' => array(),
                'target' => array(),
                'rel' => array(),
                'src' => array(),
                'alt' => array(),
                'colspan' => array(),
                'rowspan' => array(),
            );
        }
        
        return wp_kses($html, $kses_allowed);
    }
    
    private function filter_attributes($html) {
        // Eliminar event handlers
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/is', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/is', '', $html);
        
        // Eliminar javascript: en href
        $html = preg_replace('/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/is', 'href="#"', $html);
        
        return $html;
    }
    
    private function remove_inline_styles($html) {
        $html = preg_replace('/\s+style\s*=\s*["\'][^"\']*["\']/is', '', $html);
        $html = preg_replace('/\s+style\s*=\s*[^\s>]+/is', '', $html);
        
        return $html;
    }
    
    private function normalize_whitespace($html) {
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/(\r?\n){3,}/', "\n\n", $html);
        
        return trim($html);
    }
    
    public function extract_text($html) {
        $html = $this->sanitize($html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        $text = strip_tags($html);
        
        return trim($text);
    }
}
