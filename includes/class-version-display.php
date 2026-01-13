<?php
/**
 * Clase para visualización mejorada de versiones
 *
 * @package ULL_Normativa
 * @subpackage Includes
 * @since 2.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase ULL_Normativa_Version_Display
 * 
 * Maneja la visualización mejorada de versiones en el frontend:
 * - Badge de versión con gradientes y estados visuales
 * - Banner de versión histórica con mejor UX
 * - Información detallada de versiones
 */
class ULL_Normativa_Version_Display {
    
    /**
     * Inicializar la clase
     */
    public static function init() {
        $instance = new self();
        $instance->setup_hooks();
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        // Hook para el badge de versión (antes del título)
        add_action('ull_normativa_before_title', array($this, 'display_version_badge'));
        
        // Hook para el banner histórico (después del título)
        add_action('ull_normativa_after_title', array($this, 'display_historical_banner'));
        
        // Hook para información de versión en la pestaña Información
        add_action('ull_normativa_info_tab_content', array($this, 'display_version_info'), 5);
    }
    
    /**
     * Mostrar badge de versión mejorado
     */
    public function display_version_badge() {
        if (!is_singular('normativa')) {
            return;
        }
        
        global $post;
        $current_version = get_post_meta($post->ID, '_normativa_version', true);
        $is_historical = isset($_GET['version']) && !empty($_GET['version']);
        
        if (empty($current_version)) {
            return;
        }
        
        // Determinar el estado de la versión
        if ($is_historical) {
            $viewing_version = sanitize_text_field($_GET['version']);
            $version_status = __('Versión Histórica', 'ull-normativa');
            $version_class = 'historical';
            $display_version = $viewing_version;
        } else {
            $version_status = __('Versión Vigente', 'ull-normativa');
            $version_class = 'current';
            $display_version = $current_version;
        }
        
        ?>
        <div class="normativa-version-badge-wrapper">
            <span class="normativa-version-badge <?php echo esc_attr($version_class); ?>">
                <span class="version-status"><?php echo esc_html($version_status); ?></span>
                <span class="version-number"><?php echo esc_html($display_version); ?></span>
            </span>
        </div>
        <?php
    }
    
    /**
     * Mostrar banner de versión histórica mejorado
     */
    public function display_historical_banner() {
        if (!is_singular('normativa')) {
            return;
        }
        
        if (!isset($_GET['version']) || empty($_GET['version'])) {
            return;
        }
        
        global $post;
        $viewing_version = sanitize_text_field($_GET['version']);
        $current_version = get_post_meta($post->ID, '_normativa_version', true);
        $current_url = get_permalink($post->ID);
        
        // Obtener fecha de la versión histórica si está disponible
        $version_date = '';
        $versions = get_post_meta($post->ID, '_normativa_versions_history', true);
        if (is_array($versions)) {
            foreach ($versions as $version) {
                if (isset($version['version']) && $version['version'] === $viewing_version) {
                    $version_date = isset($version['date']) ? date_i18n('d \d\e F \d\e Y', strtotime($version['date'])) : '';
                    break;
                }
            }
        }
        
        ?>
        <div class="normativa-historical-banner">
            <div class="historical-banner-content">
                <div class="banner-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="banner-text">
                    <strong><?php _e('Está visualizando una versión anterior', 'ull-normativa'); ?></strong>
                    <span class="banner-details">
                        <?php 
                        printf(
                            __('Versión %s', 'ull-normativa'),
                            esc_html($viewing_version)
                        );
                        ?>
                        <?php if ($version_date) : ?>
                            · <?php 
                            printf(
                                __('Vigente desde el %s', 'ull-normativa'),
                                esc_html($version_date)
                            );
                            ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="banner-action">
                    <a href="<?php echo esc_url($current_url); ?>" class="btn-view-current">
                        <span><?php _e('Ver versión vigente', 'ull-normativa'); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
            <?php if ($current_version !== $viewing_version) : ?>
            <button class="banner-dismiss" onclick="this.parentElement.style.display='none'" aria-label="<?php esc_attr_e('Cerrar aviso', 'ull-normativa'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Mostrar información detallada de versión
     */
    public function display_version_info() {
        global $post;
        
        if (!is_singular('normativa')) {
            return;
        }
        
        $current_version = get_post_meta($post->ID, '_normativa_version', true);
        $is_historical = isset($_GET['version']) && !empty($_GET['version']);
        $viewing_version = $is_historical ? sanitize_text_field($_GET['version']) : $current_version;
        
        // Obtener historial de versiones
        $versions = get_post_meta($post->ID, '_normativa_versions_history', true);
        $version_count = is_array($versions) ? count($versions) : 0;
        
        if (empty($current_version)) {
            return;
        }
        
        ?>
        <div class="normativa-version-info">
            <div class="version-info-row">
                <span class="info-label"><?php _e('Versión actual:', 'ull-normativa'); ?></span>
                <span class="info-value version-number-display">
                    <?php echo esc_html($viewing_version); ?>
                    <?php if ($is_historical) : ?>
                        <span class="historical-indicator"><?php _e('(histórica)', 'ull-normativa'); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if ($version_count > 1) : ?>
            <div class="version-info-row">
                <span class="info-label"><?php _e('Versiones disponibles:', 'ull-normativa'); ?></span>
                <span class="info-value">
                    <?php 
                    printf(
                        _n('%s versión', '%s versiones', $version_count, 'ull-normativa'),
                        number_format_i18n($version_count)
                    );
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (is_array($versions) && !empty($versions)) : ?>
            <div class="version-info-row version-history-link">
                <a href="#versiones" class="link-to-versions">
                    <?php _e('Ver historial completo de versiones', 'ull-normativa'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
