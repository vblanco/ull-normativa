<?php
/**
 * Template Loader para mostrar códigos en el frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Codigos_Template_Loader {
    
    public function __construct() {
        add_filter('the_content', array($this, 'filter_content'), 20);
        add_filter('template_include', array($this, 'template_include'));
    }
    
    /**
     * Filtrar contenido para single de código
     */
    public function filter_content($content) {
        if (!is_singular('codigo') || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        global $post;
        
        // Obtener configuración
        $estilo = get_post_meta($post->ID, '_codigo_estilo', true) ?: 'accordion';
        $mostrar_indice = get_post_meta($post->ID, '_codigo_mostrar_indice', true);
        $mostrar_fechas = get_post_meta($post->ID, '_codigo_mostrar_fechas', true);
        $permitir_pdf = get_post_meta($post->ID, '_codigo_permitir_pdf', true);
        
        if ($mostrar_indice === '') $mostrar_indice = '1';
        if ($permitir_pdf === '') $permitir_pdf = '1';
        
        // Obtener normas
        $normas = get_post_meta($post->ID, '_codigo_normas', true);
        
        ob_start();
        ?>
        <div class="ull-codigo-container ull-codigo-estilo-<?php echo esc_attr($estilo); ?>">
            
            <?php if ($content) : ?>
                <div class="ull-codigo-descripcion">
                    <?php echo wp_kses_post($content); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($permitir_pdf) : ?>
                <div class="ull-codigo-actions">
                    <a href="<?php echo esc_url(ULL_Unified_PDF_Export::get_pdf_url($post->ID)); ?>" 
                       class="ull-codigo-btn-pdf" target="_blank">
                        📄 <?php _e('Exportar PDF', 'ull-normativa'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($normas) && is_array($normas)) : ?>
                
                <?php if ($mostrar_indice) : ?>
                    <div class="ull-codigo-indice">
                        <h3><?php _e('Índice', 'ull-normativa'); ?></h3>
                        <ol>
                            <?php 
                            $seccion_actual = '';
                            foreach ($normas as $norma_data) : 
                                $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
                                $norma = get_post($norma_id);
                                if (!$norma) continue;
                                $seccion = isset($norma_data['seccion']) ? $norma_data['seccion'] : '';
                                
                                if ($seccion && $seccion !== $seccion_actual) :
                                    if ($seccion_actual) echo '</ol></li>';
                                    $seccion_actual = $seccion;
                            ?>
                                    <li class="ull-codigo-indice-seccion">
                                        <strong><?php echo esc_html($seccion); ?></strong>
                                        <ol>
                            <?php endif; ?>
                            
                            <li>
                                <a href="#norma-<?php echo $norma_id; ?>"><?php echo esc_html($norma->post_title); ?></a>
                            </li>
                            <?php endforeach; ?>
                            <?php if ($seccion_actual) echo '</ol></li>'; ?>
                        </ol>
                    </div>
                <?php endif; ?>
                
                <div class="ull-codigo-normas ull-codigo-normas-<?php echo esc_attr($estilo); ?>">
                    <?php 
                    $seccion_actual = '';
                    foreach ($normas as $norma_data) : 
                        $norma_id = isset($norma_data['id']) ? $norma_data['id'] : $norma_data;
                        $norma = get_post($norma_id);
                        if (!$norma) continue;
                        
                        $numero = get_post_meta($norma_id, '_numero_norma', true);
                        $fecha = get_post_meta($norma_id, '_fecha_aprobacion', true);
                        $seccion = isset($norma_data['seccion']) ? $norma_data['seccion'] : '';
                        $nota = isset($norma_data['nota']) ? $norma_data['nota'] : '';
                        
                        if ($seccion && $seccion !== $seccion_actual) :
                            if ($seccion_actual) echo '</div>';
                            $seccion_actual = $seccion;
                    ?>
                        <div class="ull-codigo-seccion">
                            <h3 class="ull-codigo-seccion-titulo"><?php echo esc_html($seccion); ?></h3>
                    <?php endif; ?>
                    
                    <div class="ull-codigo-norma-item" id="norma-<?php echo $norma_id; ?>">
                        <?php if ($estilo === 'accordion') : ?>
                            <div class="ull-codigo-norma-header">
                                <span class="ull-codigo-norma-titulo"><?php echo esc_html($norma->post_title); ?></span>
                                <?php if ($numero) : ?>
                                    <span class="ull-codigo-norma-numero"><?php echo esc_html($numero); ?></span>
                                <?php endif; ?>
                                <span class="ull-codigo-norma-toggle">▼</span>
                            </div>
                            <div class="ull-codigo-norma-content">
                                <?php if ($nota) : ?>
                                    <div class="ull-codigo-norma-nota"><?php echo wp_kses_post($nota); ?></div>
                                <?php endif; ?>
                                <?php if ($mostrar_fechas && $fecha) : ?>
                                    <p class="ull-codigo-norma-fecha"><?php _e('Fecha:', 'ull-normativa'); ?> <?php echo esc_html($fecha); ?></p>
                                <?php endif; ?>
                                <div class="ull-codigo-norma-texto">
                                    <?php echo wp_kses_post($norma->post_content); ?>
                                </div>
                                <a href="<?php echo get_permalink($norma_id); ?>" class="ull-codigo-norma-link">
                                    <?php _e('Ver norma completa', 'ull-normativa'); ?> →
                                </a>
                            </div>
                            
                        <?php elseif ($estilo === 'list') : ?>
                            <a href="<?php echo get_permalink($norma_id); ?>">
                                <?php echo esc_html($norma->post_title); ?>
                                <?php if ($numero) : ?>
                                    <span class="ull-codigo-norma-numero"><?php echo esc_html($numero); ?></span>
                                <?php endif; ?>
                            </a>
                            <?php if ($mostrar_fechas && $fecha) : ?>
                                <span class="ull-codigo-norma-fecha"><?php echo esc_html($fecha); ?></span>
                            <?php endif; ?>
                            
                        <?php elseif ($estilo === 'cards') : ?>
                            <div class="ull-codigo-card">
                                <h4><a href="<?php echo get_permalink($norma_id); ?>"><?php echo esc_html($norma->post_title); ?></a></h4>
                                <?php if ($numero) : ?>
                                    <p class="ull-codigo-norma-numero"><?php echo esc_html($numero); ?></p>
                                <?php endif; ?>
                                <?php if ($mostrar_fechas && $fecha) : ?>
                                    <p class="ull-codigo-norma-fecha"><?php echo esc_html($fecha); ?></p>
                                <?php endif; ?>
                                <?php if ($norma->post_excerpt) : ?>
                                    <p><?php echo esc_html($norma->post_excerpt); ?></p>
                                <?php endif; ?>
                            </div>
                            
                        <?php else : // full ?>
                            <h3 class="ull-codigo-norma-titulo-full">
                                <?php echo esc_html($norma->post_title); ?>
                                <?php if ($numero) : ?>
                                    <span class="ull-codigo-norma-numero"><?php echo esc_html($numero); ?></span>
                                <?php endif; ?>
                            </h3>
                            <?php if ($nota) : ?>
                                <div class="ull-codigo-norma-nota"><?php echo wp_kses_post($nota); ?></div>
                            <?php endif; ?>
                            <?php if ($mostrar_fechas && $fecha) : ?>
                                <p class="ull-codigo-norma-fecha"><?php _e('Fecha:', 'ull-normativa'); ?> <?php echo esc_html($fecha); ?></p>
                            <?php endif; ?>
                            <div class="ull-codigo-norma-texto">
                                <?php echo wp_kses_post($norma->post_content); ?>
                            </div>
                            <a href="<?php echo get_permalink($norma_id); ?>" class="ull-codigo-norma-link">
                                <?php _e('Ver norma completa', 'ull-normativa'); ?> →
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php endforeach; ?>
                    <?php if ($seccion_actual) echo '</div>'; ?>
                </div>
                
            <?php else : ?>
                <p class="ull-codigo-empty"><?php _e('Este código no contiene normas.', 'ull-normativa'); ?></p>
            <?php endif; ?>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Usar template del tema si existe
     */
    public function template_include($template) {
        if (is_singular('codigo')) {
            // Buscar template en el tema
            $theme_template = locate_template(array(
                'single-codigo.php',
                'ull-codigos/single-codigo.php',
            ));
            
            if ($theme_template) {
                return $theme_template;
            }
        }
        
        if (is_post_type_archive('codigo')) {
            $theme_template = locate_template(array(
                'archive-codigo.php',
                'ull-codigos/archive-codigo.php',
            ));
            
            if ($theme_template) {
                return $theme_template;
            }
        }
        
        return $template;
    }
}

new ULL_Codigos_Template_Loader();
