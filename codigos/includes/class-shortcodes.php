<?php
/**
 * Shortcodes para mostrar códigos
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Codigos_Shortcodes {
    
    public function __construct() {
        add_shortcode('ull_codigo', array($this, 'render_codigo'));
        add_shortcode('ull_codigos_lista', array($this, 'render_lista'));
    }
    
    public function render_codigo($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'slug' => '',
            'estilo' => '',
            'mostrar_indice' => '',
            'mostrar_fechas' => '',
            'permitir_pdf' => '',
        ), $atts);
        
        // Obtener código
        if (!empty($atts['id'])) {
            $codigo = get_post(intval($atts['id']));
        } elseif (!empty($atts['slug'])) {
            $codigo = get_page_by_path($atts['slug'], OBJECT, 'codigo');
        } else {
            return '<p>' . __('No se especificó un código válido.', 'ull-normativa') . '</p>';
        }
        
        if (!$codigo || $codigo->post_type !== 'codigo') {
            return '<p>' . __('Código no encontrado.', 'ull-normativa') . '</p>';
        }
        
        // Obtener configuración
        $estilo = !empty($atts['estilo']) ? $atts['estilo'] : get_post_meta($codigo->ID, '_codigo_estilo', true);
        $mostrar_indice = $atts['mostrar_indice'] !== '' ? $atts['mostrar_indice'] : get_post_meta($codigo->ID, '_codigo_mostrar_indice', true);
        $mostrar_fechas = $atts['mostrar_fechas'] !== '' ? $atts['mostrar_fechas'] : get_post_meta($codigo->ID, '_codigo_mostrar_fechas', true);
        $permitir_pdf = $atts['permitir_pdf'] !== '' ? $atts['permitir_pdf'] : get_post_meta($codigo->ID, '_codigo_permitir_pdf', true);
        
        if (!$estilo) $estilo = 'accordion';
        
        // Obtener normas
        $normas = get_post_meta($codigo->ID, '_codigo_normas', true);
        if (!is_array($normas) || empty($normas)) {
            return '<p>' . __('Este código no contiene normas.', 'ull-normativa') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="ull-codigo-container ull-codigo-estilo-<?php echo esc_attr($estilo); ?>">
            <div class="ull-codigo-header">
                <h2 class="ull-codigo-titulo"><?php echo esc_html($codigo->post_title); ?></h2>
                
                <?php if ($codigo->post_content) : ?>
                    <div class="ull-codigo-descripcion">
                        <?php echo wp_kses_post($codigo->post_content); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($permitir_pdf) : ?>
                    <div class="ull-codigo-actions">
                        <a href="<?php echo esc_url(ULL_Unified_PDF_Export::get_pdf_url($codigo->ID)); ?>" 
                           class="ull-codigo-btn-pdf" target="_blank">
                            📄 <?php _e('Exportar PDF', 'ull-normativa'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
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
                        <div class="ull-codigo-norma-header" onclick="this.parentElement.classList.toggle('active')">
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
                    <?php else : ?>
                        <h3><?php echo esc_html($norma->post_title); ?></h3>
                        <?php if ($numero) : ?>
                            <p class="ull-codigo-norma-numero"><?php echo esc_html($numero); ?></p>
                        <?php endif; ?>
                        <?php if ($mostrar_fechas && $fecha) : ?>
                            <p class="ull-codigo-norma-fecha"><?php _e('Fecha:', 'ull-normativa'); ?> <?php echo esc_html($fecha); ?></p>
                        <?php endif; ?>
                        <div class="ull-codigo-norma-texto">
                            <?php echo wp_kses_post($norma->post_content); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php endforeach; ?>
                <?php if ($seccion_actual) echo '</div>'; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_lista($atts) {
        $atts = shortcode_atts(array(
            'limite' => 6,
            'columnas' => 3,
            'orderby' => 'title',
            'order' => 'ASC',
            'estilo' => 'tarjetas',
            'mostrar_extracto' => 'no',
            'mostrar_contador' => 'si',
            'ids' => '',
        ), $atts);
        
        $args = array(
            'post_type' => 'codigo',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limite']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );
        
        if (!empty($atts['ids'])) {
            $args['post__in'] = array_map('intval', explode(',', $atts['ids']));
            $args['orderby'] = 'post__in';
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('No hay códigos disponibles.', 'ull-normativa') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="ull-codigos-lista ull-codigos-cols-<?php echo intval($atts['columnas']); ?> ull-codigos-estilo-<?php echo esc_attr($atts['estilo']); ?>">
            <?php while ($query->have_posts()) : $query->the_post(); 
                $normas = get_post_meta(get_the_ID(), '_codigo_normas', true);
                $count = is_array($normas) ? count($normas) : 0;
            ?>
                <a href="<?php the_permalink(); ?>" class="ull-codigos-item">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="ull-codigos-item-imagen">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php else : ?>
                        <div class="ull-codigos-item-imagen ull-codigos-item-placeholder">
                            <span>📚</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ull-codigos-item-info">
                        <h3 class="ull-codigos-item-titulo"><?php the_title(); ?></h3>
                        
                        <?php if ($atts['mostrar_extracto'] === 'si' && has_excerpt()) : ?>
                            <p class="ull-codigos-item-extracto"><?php the_excerpt(); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($atts['mostrar_contador'] === 'si') : ?>
                            <span class="ull-codigos-item-contador">
                                <?php printf(_n('%d norma', '%d normas', $count, 'ull-normativa'), $count); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}

new ULL_Codigos_Shortcodes();
