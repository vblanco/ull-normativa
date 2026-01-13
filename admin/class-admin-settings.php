<?php
/**
 * Página de configuración del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Admin_Settings {
    
    private $option_group = 'ull_normativa_settings';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Normativa ULL', 'ull-normativa'),
            __('Normativa', 'ull-normativa'),
            'manage_options',
            'ull-normativa',
            array($this, 'render_dashboard'),
            'dashicons-book-alt',
            25
        );
        
        add_submenu_page(
            'ull-normativa',
            __('Panel de Control', 'ull-normativa'),
            __('Panel de Control', 'ull-normativa'),
            'manage_options',
            'ull-normativa',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'ull-normativa',
            __('Importar Normativa', 'ull-normativa'),
            __('Importar', 'ull-normativa'),
            'manage_options',
            'ull-normativa-import',
            array($this, 'render_import_page')
        );
        
        add_submenu_page(
            'ull-normativa',
            __('Exportar Normativa', 'ull-normativa'),
            __('Exportar', 'ull-normativa'),
            'manage_options',
            'ull-normativa-export',
            array($this, 'render_export_page')
        );
        
        add_submenu_page(
            'ull-normativa',
            __('Configuración', 'ull-normativa'),
            __('Configuración', 'ull-normativa'),
            'manage_options',
            'ull-normativa-settings',
            array($this, 'render_settings_page')
        );
        
        // NOTA: La configuración PDF se ha movido a un submenú propio manejado por class-pdf-settings.php
        
        add_submenu_page(
            'ull-normativa',
            __('Herramientas', 'ull-normativa'),
            __('Herramientas', 'ull-normativa'),
            'manage_options',
            'ull-normativa-tools',
            array($this, 'render_tools_page')
        );
        
        add_submenu_page(
            'ull-normativa',
            __('Ayuda y Shortcodes', 'ull-normativa'),
            __('Ayuda', 'ull-normativa'),
            'manage_options',
            'ull-normativa-help',
            array($this, 'render_help_page')
        );
        
        add_submenu_page(
            'ull-normativa',
            __('Personalizar Estilos', 'ull-normativa'),
            __('Estilos', 'ull-normativa'),
            'manage_options',
            'ull-normativa-styles',
            array($this, 'render_styles_page')
        );
    }
    
    public function register_settings() {
        // Sección de visualización
        add_settings_section(
            'ull_normativa_display',
            __('Opciones de Visualización', 'ull-normativa'),
            array($this, 'render_display_section'),
            'ull-normativa-settings'
        );
        
        register_setting($this->option_group, 'ull_normativa_display_mode');
        add_settings_field(
            'ull_normativa_display_mode',
            __('Modo de visualización', 'ull-normativa'),
            array($this, 'render_display_mode_field'),
            'ull-normativa-settings',
            'ull_normativa_display'
        );
        
        register_setting($this->option_group, 'ull_normativa_items_per_page', array('type' => 'integer', 'default' => 20));
        add_settings_field(
            'ull_normativa_items_per_page',
            __('Elementos por página', 'ull-normativa'),
            array($this, 'render_items_per_page_field'),
            'ull-normativa-settings',
            'ull_normativa_display'
        );
        
        register_setting($this->option_group, 'ull_normativa_list_columns');
        add_settings_field(
            'ull_normativa_list_columns',
            __('Columnas del listado', 'ull-normativa'),
            array($this, 'render_list_columns_field'),
            'ull-normativa-settings',
            'ull_normativa_display'
        );
        
        register_setting($this->option_group, 'ull_normativa_ficha_sections', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_ficha_sections'),
            'default' => array('info', 'contenido', 'versiones', 'relaciones', 'documentos')
        ));
        add_settings_field(
            'ull_normativa_ficha_sections',
            __('Secciones en ficha individual', 'ull-normativa'),
            array($this, 'render_ficha_sections_field'),
            'ull-normativa-settings',
            'ull_normativa_display'
        );
        
        // Sección de búsqueda
        add_settings_section(
            'ull_normativa_search',
            __('Opciones de Búsqueda', 'ull-normativa'),
            array($this, 'render_search_section'),
            'ull-normativa-settings'
        );
        
        register_setting($this->option_group, 'ull_normativa_show_search', array('type' => 'boolean', 'default' => true));
        add_settings_field(
            'ull_normativa_show_search',
            __('Mostrar buscador', 'ull-normativa'),
            array($this, 'render_show_search_field'),
            'ull-normativa-settings',
            'ull_normativa_search'
        );
        
        register_setting($this->option_group, 'ull_normativa_search_fields');
        add_settings_field(
            'ull_normativa_search_fields',
            __('Campos de búsqueda', 'ull-normativa'),
            array($this, 'render_search_fields_field'),
            'ull-normativa-settings',
            'ull_normativa_search'
        );
        
        // Sección de funcionalidades
        add_settings_section(
            'ull_normativa_features',
            __('Funcionalidades', 'ull-normativa'),
            array($this, 'render_features_section'),
            'ull-normativa-settings'
        );
        
        register_setting($this->option_group, 'ull_normativa_enable_versions', array('type' => 'boolean', 'default' => true));
        add_settings_field(
            'ull_normativa_enable_versions',
            __('Control de versiones', 'ull-normativa'),
            array($this, 'render_enable_versions_field'),
            'ull-normativa-settings',
            'ull_normativa_features'
        );
        
        register_setting($this->option_group, 'ull_normativa_enable_relations', array('type' => 'boolean', 'default' => true));
        add_settings_field(
            'ull_normativa_enable_relations',
            __('Relaciones entre normas', 'ull-normativa'),
            array($this, 'render_enable_relations_field'),
            'ull-normativa-settings',
            'ull_normativa_features'
        );
        
        register_setting($this->option_group, 'ull_normativa_use_classic_editor', array('type' => 'boolean', 'default' => true));
        add_settings_field(
            'ull_normativa_use_classic_editor',
            __('Tipo de editor', 'ull-normativa'),
            array($this, 'render_editor_type_field'),
            'ull-normativa-settings',
            'ull_normativa_features'
        );
        
        // Sección de HTML
        add_settings_section(
            'ull_normativa_html',
            __('Sanitización HTML', 'ull-normativa'),
            array($this, 'render_html_section'),
            'ull-normativa-settings'
        );
        
        register_setting($this->option_group, 'ull_normativa_html_allowed_tags');
        add_settings_field(
            'ull_normativa_html_allowed_tags',
            __('Tags HTML permitidos', 'ull-normativa'),
            array($this, 'render_html_tags_field'),
            'ull-normativa-settings',
            'ull_normativa_html'
        );
        
        register_setting($this->option_group, 'ull_normativa_remove_inline_styles', array('type' => 'boolean', 'default' => true));
        add_settings_field(
            'ull_normativa_remove_inline_styles',
            __('Eliminar estilos inline', 'ull-normativa'),
            array($this, 'render_remove_styles_field'),
            'ull-normativa-settings',
            'ull_normativa_html'
        );
    }
    
    public function render_dashboard() {
        $total_normas = wp_count_posts('norma');
        $vigentes = $this->count_by_estado('vigente');
        $derogadas = $this->count_by_estado('derogada');
        
        global $wpdb;
        $versions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ull_norma_versions");
        $relations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ull_norma_relations");
        ?>
        <div class="wrap">
            <h1><?php _e('Panel de Control - Normativa ULL', 'ull-normativa'); ?></h1>
            
            <div class="ull-dashboard-stats">
                <div class="ull-stat-box">
                    <span class="ull-stat-number"><?php echo esc_html($total_normas->publish); ?></span>
                    <span class="ull-stat-label"><?php _e('Total Normas', 'ull-normativa'); ?></span>
                </div>
                <div class="ull-stat-box ull-stat-success">
                    <span class="ull-stat-number"><?php echo esc_html($vigentes); ?></span>
                    <span class="ull-stat-label"><?php _e('Vigentes', 'ull-normativa'); ?></span>
                </div>
                <div class="ull-stat-box ull-stat-warning">
                    <span class="ull-stat-number"><?php echo esc_html($derogadas); ?></span>
                    <span class="ull-stat-label"><?php _e('Derogadas', 'ull-normativa'); ?></span>
                </div>
                <div class="ull-stat-box">
                    <span class="ull-stat-number"><?php echo esc_html($versions_count ? $versions_count : 0); ?></span>
                    <span class="ull-stat-label"><?php _e('Versiones', 'ull-normativa'); ?></span>
                </div>
                <div class="ull-stat-box">
                    <span class="ull-stat-number"><?php echo esc_html($relations_count ? $relations_count : 0); ?></span>
                    <span class="ull-stat-label"><?php _e('Relaciones', 'ull-normativa'); ?></span>
                </div>
            </div>
            
            <div class="ull-dashboard-content">
                <div class="ull-dashboard-section">
                    <h2><?php _e('Acciones Rápidas', 'ull-normativa'); ?></h2>
                    <div class="ull-quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=norma'); ?>" class="button button-primary">
                            <?php _e('Nueva Norma', 'ull-normativa'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ull-normativa-import'); ?>" class="button">
                            <?php _e('Importar', 'ull-normativa'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ull-normativa-export'); ?>" class="button">
                            <?php _e('Exportar', 'ull-normativa'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ull-normativa-help'); ?>" class="button">
                            <?php _e('Ayuda', 'ull-normativa'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="ull-dashboard-section">
                    <h2><?php _e('Últimas Normas', 'ull-normativa'); ?></h2>
                    <?php
                    $recent = get_posts(array(
                        'post_type' => 'norma',
                        'posts_per_page' => 5,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ));
                    
                    if ($recent) {
                        echo '<ul>';
                        foreach ($recent as $norma) {
                            $numero = get_post_meta($norma->ID, '_numero_norma', true);
                            printf(
                                '<li><a href="%s">%s%s</a></li>',
                                get_edit_post_link($norma->ID),
                                $numero ? '<strong>' . esc_html($numero) . '</strong> - ' : '',
                                esc_html($norma->post_title)
                            );
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>' . __('No hay normas creadas aún.', 'ull-normativa') . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function count_by_estado($estado) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_estado_norma' AND pm.meta_value = %s 
             AND p.post_type = 'norma' AND p.post_status = 'publish'",
            $estado
        ));
    }
    
    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Importar Normativa', 'ull-normativa'); ?></h1>
            <?php
            $import_export = new ULL_Import_Export();
            $import_export->render_import_form();
            ?>
        </div>
        <?php
    }
    
    public function render_export_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Exportar Normativa', 'ull-normativa'); ?></h1>
            <?php
            $import_export = new ULL_Import_Export();
            $import_export->render_export_form();
            ?>
        </div>
        <?php
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de Normativa', 'ull-normativa'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_group);
                do_settings_sections('ull-normativa-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function render_help_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Ayuda - Normativa ULL', 'ull-normativa'); ?></h1>
            
            <div class="ull-help-container">
                
                <!-- Sección de Shortcodes -->
                <div class="ull-help-section">
                    <h2><?php _e('Shortcodes Disponibles', 'ull-normativa'); ?></h2>
                    <p><?php _e('Utiliza estos shortcodes para mostrar la normativa en cualquier página o entrada:', 'ull-normativa'); ?></p>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width:30%"><?php _e('Shortcode', 'ull-normativa'); ?></th>
                                <th style="width:40%"><?php _e('Descripción', 'ull-normativa'); ?></th>
                                <th style="width:30%"><?php _e('Parámetros', 'ull-normativa'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[ull_normativa_listado]</code></td>
                                <td><?php _e('Muestra un listado completo de normativa con filtros y buscador. Es el shortcode principal para mostrar toda la normativa.', 'ull-normativa'); ?></td>
                                <td>
                                    <code>tipo</code>, <code>estado</code>, <code>categoria</code>, <code>limit</code>, <code>modo</code>, <code>orden</code>, <code>mostrar_filtros</code>, <code>mostrar_buscador</code>, <code>columnas</code>
                                </td>
                            </tr>
                            <tr>
                                <td><code>[ull_normativa_buscador]</code></td>
                                <td><?php _e('Muestra un buscador avanzado independiente con filtros desplegables y sugerencias automáticas.', 'ull-normativa'); ?></td>
                                <td>
                                    <code>placeholder</code>, <code>ajax</code>, <code>mostrar_sugerencias</code>
                                </td>
                            </tr>
                            <tr>
                                <td><code>[ull_norma id="123"]</code></td>
                                <td><?php _e('Muestra la ficha completa de una norma específica con tabs para información, contenido, versiones, relaciones y documentos.', 'ull-normativa'); ?></td>
                                <td>
                                    <code>id</code> (requerido), <code>secciones</code>
                                </td>
                            </tr>
                            <tr>
                                <td><code>[ull_normativa_archivo]</code></td>
                                <td><?php _e('Muestra un archivo de normativa agrupado por años o categorías, ideal para páginas de consulta histórica.', 'ull-normativa'); ?></td>
                                <td>
                                    <code>agrupar_por</code> (year, tipo, categoria)
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Ejemplos de uso -->
                <div class="ull-help-section">
                    <h2><?php _e('Ejemplos de Uso', 'ull-normativa'); ?></h2>
                    
                    <h3><?php _e('Listado básico', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_normativa_listado]</code></pre>
                    <p class="description"><?php _e('Muestra todas las normas con la configuración por defecto.', 'ull-normativa'); ?></p>
                    
                    <h3><?php _e('Listado filtrado por tipo', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_normativa_listado tipo="reglamento" estado="vigente"]</code></pre>
                    <p class="description"><?php _e('Muestra solo los reglamentos vigentes.', 'ull-normativa'); ?></p>
                    
                    <h3><?php _e('Listado con vista de tarjetas', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_normativa_listado modo="cards" limit="12"]</code></pre>
                    <p class="description"><?php _e('Muestra 12 normas en formato de tarjetas.', 'ull-normativa'); ?></p>
                    
                    <h3><?php _e('Listado sin filtros', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_normativa_listado mostrar_filtros="false" mostrar_buscador="false"]</code></pre>
                    <p class="description"><?php _e('Muestra el listado sin los controles de filtrado ni búsqueda.', 'ull-normativa'); ?></p>
                    
                    <h3><?php _e('Ficha de norma específica', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_norma id="45"]</code></pre>
                    <p class="description"><?php _e('Muestra la ficha completa de la norma con ID 45.', 'ull-normativa'); ?></p>
                    
                    <h3><?php _e('Ficha con secciones específicas', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_norma id="45" secciones="info,contenido"]</code></pre>
                    <p class="description"><?php _e('Muestra solo las secciones de información y contenido.', 'ull-normativa'); ?></p>
                    
                    <h3><?php _e('Archivo por años', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_normativa_archivo agrupar_por="year"]</code></pre>
                    <p class="description"><?php _e('Muestra todas las normas agrupadas por año de aprobación.', 'ull-normativa'); ?></p>
                    
                    <h3><?php _e('Archivo por tipos', 'ull-normativa'); ?></h3>
                    <pre><code>[ull_normativa_archivo agrupar_por="tipo"]</code></pre>
                    <p class="description"><?php _e('Muestra todas las normas agrupadas por tipo de norma.', 'ull-normativa'); ?></p>
                </div>
                
                <!-- Parámetros detallados -->
                <div class="ull-help-section">
                    <h2><?php _e('Parámetros Detallados', 'ull-normativa'); ?></h2>
                    
                    <h3>[ull_normativa_listado]</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Parámetro', 'ull-normativa'); ?></th>
                                <th><?php _e('Valores', 'ull-normativa'); ?></th>
                                <th><?php _e('Por defecto', 'ull-normativa'); ?></th>
                                <th><?php _e('Descripción', 'ull-normativa'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>tipo</code></td>
                                <td>slug del tipo</td>
                                <td>todos</td>
                                <td><?php _e('Filtrar por tipo de norma (ej: reglamento, resolucion, ley)', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>estado</code></td>
                                <td>vigente, derogada, modificada, pendiente</td>
                                <td>todos</td>
                                <td><?php _e('Filtrar por estado de la norma', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>categoria</code></td>
                                <td>slug de categoría</td>
                                <td>todos</td>
                                <td><?php _e('Filtrar por categoría (ej: academica, personal)', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>limit</code></td>
                                <td>número</td>
                                <td>20</td>
                                <td><?php _e('Número de normas por página', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>modo</code></td>
                                <td>list, cards, table</td>
                                <td>list</td>
                                <td><?php _e('Modo de visualización inicial', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>orden</code></td>
                                <td>fecha_desc, fecha_asc, titulo_asc, titulo_desc, numero</td>
                                <td>fecha_desc</td>
                                <td><?php _e('Ordenación de resultados', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>mostrar_filtros</code></td>
                                <td>true, false</td>
                                <td>true</td>
                                <td><?php _e('Mostrar selectores de filtro', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>mostrar_buscador</code></td>
                                <td>true, false</td>
                                <td>true</td>
                                <td><?php _e('Mostrar campo de búsqueda', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>columnas</code></td>
                                <td>titulo, tipo, numero, fecha, estado, organo, categoria</td>
                                <td>titulo,tipo,numero,fecha,estado</td>
                                <td><?php _e('Columnas visibles (separadas por coma)', 'ull-normativa'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 style="margin-top:30px;">[ull_norma]</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Parámetro', 'ull-normativa'); ?></th>
                                <th><?php _e('Valores', 'ull-normativa'); ?></th>
                                <th><?php _e('Por defecto', 'ull-normativa'); ?></th>
                                <th><?php _e('Descripción', 'ull-normativa'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>número</td>
                                <td>-</td>
                                <td><?php _e('ID de la norma a mostrar (requerido)', 'ull-normativa'); ?></td>
                            </tr>
                            <tr>
                                <td><code>secciones</code></td>
                                <td>info, contenido, versiones, relaciones, documentos</td>
                                <td>todas</td>
                                <td><?php _e('Secciones a mostrar (separadas por coma)', 'ull-normativa'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Numeración Automática -->
                <div class="ull-help-section">
                    <h2><?php _e('Numeración Automática de Normas', 'ull-normativa'); ?></h2>
                    <p><?php _e('El número de norma se genera automáticamente con el formato <strong>XXX-0001</strong> donde:', 'ull-normativa'); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php _e('<strong>XXX</strong>: Prefijo de 3 letras según el tipo de norma', 'ull-normativa'); ?></li>
                        <li><?php _e('<strong>0001</strong>: Número secuencial de 4 dígitos', 'ull-normativa'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Prefijos por Tipo de Norma', 'ull-normativa'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Tipo', 'ull-normativa'); ?></th>
                                <th><?php _e('Prefijo', 'ull-normativa'); ?></th>
                                <th><?php _e('Ejemplo', 'ull-normativa'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Ley Orgánica</td><td><code>LOR</code></td><td>LOR-0001</td></tr>
                            <tr><td>Ley</td><td><code>LEY</code></td><td>LEY-0001</td></tr>
                            <tr><td>Real Decreto</td><td><code>RDE</code></td><td>RDE-0001</td></tr>
                            <tr><td>Decreto</td><td><code>DEC</code></td><td>DEC-0001</td></tr>
                            <tr><td>Orden</td><td><code>ORD</code></td><td>ORD-0001</td></tr>
                            <tr><td>Resolución</td><td><code>RES</code></td><td>RES-0001</td></tr>
                            <tr><td>Acuerdo</td><td><code>ACU</code></td><td>ACU-0001</td></tr>
                            <tr><td>Reglamento</td><td><code>REG</code></td><td>REG-0001</td></tr>
                            <tr><td>Estatuto</td><td><code>EST</code></td><td>EST-0001</td></tr>
                            <tr><td>Normativa Interna</td><td><code>NIN</code></td><td>NIN-0001</td></tr>
                            <tr><td>Instrucción</td><td><code>INS</code></td><td>INS-0001</td></tr>
                            <tr><td>Circular</td><td><code>CIR</code></td><td>CIR-0001</td></tr>
                            <tr><td>Convenio</td><td><code>CON</code></td><td>CON-0001</td></tr>
                        </tbody>
                    </table>
                    <p class="description" style="margin-top: 15px;">
                        <?php _e('El número se asigna automáticamente al guardar una norma con un tipo asignado. Una vez asignado, el número no cambia aunque se modifique el tipo.', 'ull-normativa'); ?>
                    </p>
                </div>
                
                <!-- Guía rápida -->
                <div class="ull-help-section">
                    <h2><?php _e('Guía Rápida', 'ull-normativa'); ?></h2>
                    
                    <div class="ull-help-columns">
                        <div class="ull-help-column">
                            <h3><?php _e('Crear una norma', 'ull-normativa'); ?></h3>
                            <ol>
                                <li><?php _e('Ve a Normativa → Añadir Nueva', 'ull-normativa'); ?></li>
                                <li><?php _e('Completa el título y los datos básicos', 'ull-normativa'); ?></li>
                                <li><?php _e('Añade el contenido HTML completo si lo tienes', 'ull-normativa'); ?></li>
                                <li><?php _e('Asigna tipo, categoría y órgano', 'ull-normativa'); ?></li>
                                <li><?php _e('Publica la norma', 'ull-normativa'); ?></li>
                            </ol>
                        </div>
                        
                        <div class="ull-help-column">
                            <h3><?php _e('Importar normativa', 'ull-normativa'); ?></h3>
                            <ol>
                                <li><?php _e('Ve a Normativa → Importar', 'ull-normativa'); ?></li>
                                <li><?php _e('Descarga la plantilla CSV', 'ull-normativa'); ?></li>
                                <li><?php _e('Completa los datos en el CSV', 'ull-normativa'); ?></li>
                                <li><?php _e('Sube el archivo y configura las opciones', 'ull-normativa'); ?></li>
                                <li><?php _e('Haz clic en Importar', 'ull-normativa'); ?></li>
                            </ol>
                        </div>
                        
                        <div class="ull-help-column">
                            <h3><?php _e('Gestionar versiones', 'ull-normativa'); ?></h3>
                            <ol>
                                <li><?php _e('Edita una norma existente', 'ull-normativa'); ?></li>
                                <li><?php _e('En "Control de Versiones", marca la casilla', 'ull-normativa'); ?></li>
                                <li><?php _e('Añade el número de versión y resumen', 'ull-normativa'); ?></li>
                                <li><?php _e('Guarda los cambios', 'ull-normativa'); ?></li>
                                <li><?php _e('Puedes ver y restaurar versiones anteriores', 'ull-normativa'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <!-- URLs y enlaces -->
                <div class="ull-help-section">
                    <h2><?php _e('URLs y Enlaces', 'ull-normativa'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Archivo de normativa', 'ull-normativa'); ?></strong></td>
                                <td><code><?php echo esc_url(get_post_type_archive_link('norma')); ?></code></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Feed RSS', 'ull-normativa'); ?></strong></td>
                                <td><code><?php echo esc_url(get_post_type_archive_feed_link('norma')); ?></code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
            
            <style>
                .ull-help-container { max-width: 1200px; }
                .ull-help-section { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                .ull-help-section h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #0073aa; color: #0073aa; }
                .ull-help-section h3 { color: #23282d; margin-top: 20px; }
                .ull-help-section pre { background: #f5f5f5; padding: 10px 15px; border-radius: 4px; overflow-x: auto; }
                .ull-help-section code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
                .ull-help-section pre code { background: transparent; padding: 0; }
                .ull-help-section table code { background: #e8f4fc; color: #0073aa; }
                .ull-help-section .description { color: #666; font-style: italic; margin-top: 5px; }
                .ull-help-columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
                .ull-help-column { background: #f9f9f9; padding: 15px; border-radius: 4px; }
                .ull-help-column h3 { margin-top: 0; }
                .ull-help-column ol { padding-left: 20px; margin-bottom: 0; }
                .ull-help-column li { margin-bottom: 8px; }
            </style>
        </div>
        <?php
    }
    
    // Callbacks de secciones
    public function render_display_section() {
        echo '<p>' . __('Configure cómo se mostrará la normativa en el frontend.', 'ull-normativa') . '</p>';
    }
    
    public function render_search_section() {
        echo '<p>' . __('Configure las opciones del buscador de normativa.', 'ull-normativa') . '</p>';
    }
    
    public function render_features_section() {
        echo '<p>' . __('Active o desactive funcionalidades del plugin.', 'ull-normativa') . '</p>';
    }
    
    public function render_html_section() {
        echo '<p>' . __('Configure cómo se sanitiza el contenido HTML de las normas.', 'ull-normativa') . '</p>';
    }
    
    // Callbacks de campos
    public function render_display_mode_field() {
        $value = get_option('ull_normativa_display_mode', 'list');
        ?>
        <select name="ull_normativa_display_mode">
            <option value="list" <?php selected($value, 'list'); ?>><?php _e('Lista', 'ull-normativa'); ?></option>
            <option value="cards" <?php selected($value, 'cards'); ?>><?php _e('Tarjetas', 'ull-normativa'); ?></option>
            <option value="table" <?php selected($value, 'table'); ?>><?php _e('Tabla', 'ull-normativa'); ?></option>
        </select>
        <p class="description"><?php _e('Modo de visualización por defecto del listado.', 'ull-normativa'); ?></p>
        <?php
    }
    
    public function render_items_per_page_field() {
        $value = get_option('ull_normativa_items_per_page', 20);
        ?>
        <input type="number" name="ull_normativa_items_per_page" value="<?php echo esc_attr($value); ?>" min="5" max="100" step="5">
        <?php
    }
    
    public function render_list_columns_field() {
        $value = get_option('ull_normativa_list_columns', array('titulo', 'tipo', 'numero', 'fecha', 'estado'));
        if (!is_array($value)) $value = array();
        
        $options = array(
            'titulo' => __('Título', 'ull-normativa'),
            'tipo' => __('Tipo', 'ull-normativa'),
            'numero' => __('Número', 'ull-normativa'),
            'fecha' => __('Fecha', 'ull-normativa'),
            'estado' => __('Estado', 'ull-normativa'),
            'organo' => __('Órgano', 'ull-normativa'),
            'categoria' => __('Categoría', 'ull-normativa'),
        );
        
        echo '<fieldset>';
        foreach ($options as $key => $label) {
            $checked = in_array($key, $value);
            echo '<label><input type="checkbox" name="ull_normativa_list_columns[]" value="' . esc_attr($key) . '" ' . checked($checked, true, false) . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
    }
    
    public function render_ficha_sections_field() {
        $value = get_option('ull_normativa_ficha_sections', array('info', 'contenido', 'versiones', 'relaciones', 'documentos'));
        if (!is_array($value)) $value = array();
        
        $options = array(
            'info' => __('Información General', 'ull-normativa'),
            'contenido' => __('Contenido', 'ull-normativa'),
            'versiones' => __('Versiones', 'ull-normativa'),
            'relaciones' => __('Relaciones', 'ull-normativa'),
            'documentos' => __('Documentos', 'ull-normativa'),
        );
        
        echo '<fieldset>';
        foreach ($options as $key => $label) {
            $checked = in_array($key, $value);
            echo '<label><input type="checkbox" name="ull_normativa_ficha_sections[]" value="' . esc_attr($key) . '" ' . checked($checked, true, false) . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Secciones a mostrar en la ficha individual de cada norma.', 'ull-normativa') . '</p>';
    }
    
    /**
     * Sanitizar array de secciones de ficha
     */
    public function sanitize_ficha_sections($input) {
        // Si $input no es array o está vacío, devolver array vacío
        if (!is_array($input)) {
            return array();
        }
        
        // Opciones válidas
        $valid_sections = array('info', 'contenido', 'versiones', 'relaciones', 'documentos');
        
        // Filtrar solo opciones válidas
        $sanitized = array();
        foreach ($input as $section) {
            if (in_array($section, $valid_sections)) {
                $sanitized[] = $section;
            }
        }
        
        return $sanitized;
    }
    
    public function render_show_search_field() {
        $value = get_option('ull_normativa_show_search', true);
        ?>
        <label>
            <input type="checkbox" name="ull_normativa_show_search" value="1" <?php checked($value, true); ?>>
            <?php _e('Mostrar buscador en el listado de normativa', 'ull-normativa'); ?>
        </label>
        <?php
    }
    
    public function render_search_fields_field() {
        $value = get_option('ull_normativa_search_fields', array('title', 'content', 'numero', 'fecha'));
        if (!is_array($value)) $value = array();
        
        $options = array(
            'title' => __('Título', 'ull-normativa'),
            'content' => __('Contenido', 'ull-normativa'),
            'numero' => __('Número de norma', 'ull-normativa'),
            'fecha' => __('Fecha', 'ull-normativa'),
            'organo' => __('Órgano emisor', 'ull-normativa'),
            'palabras_clave' => __('Palabras clave', 'ull-normativa'),
        );
        
        echo '<fieldset>';
        foreach ($options as $key => $label) {
            $checked = in_array($key, $value);
            echo '<label><input type="checkbox" name="ull_normativa_search_fields[]" value="' . esc_attr($key) . '" ' . checked($checked, true, false) . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
    }
    
    public function render_enable_versions_field() {
        $value = get_option('ull_normativa_enable_versions', true);
        ?>
        <label>
            <input type="checkbox" name="ull_normativa_enable_versions" value="1" <?php checked($value, true); ?>>
            <?php _e('Activar control de versiones de las normas', 'ull-normativa'); ?>
        </label>
        <?php
    }
    
    public function render_enable_relations_field() {
        $value = get_option('ull_normativa_enable_relations', true);
        ?>
        <label>
            <input type="checkbox" name="ull_normativa_enable_relations" value="1" <?php checked($value, true); ?>>
            <?php _e('Activar relaciones entre normas', 'ull-normativa'); ?>
        </label>
        <?php
    }
    
    public function render_editor_type_field() {
        $value = get_option('ull_normativa_use_classic_editor', true);
        ?>
        <label>
            <input type="checkbox" name="ull_normativa_use_classic_editor" value="1" <?php checked($value, true); ?>>
            <?php _e('Usar editor clásico (recomendado para ver todos los campos)', 'ull-normativa'); ?>
        </label>
        <p class="description">
            <?php _e('El editor clásico muestra los meta boxes de forma más clara. Desmarcar para usar Gutenberg.', 'ull-normativa'); ?>
        </p>
        <?php
    }
    
    public function render_html_tags_field() {
        $value = get_option('ull_normativa_html_allowed_tags', '');
        ?>
        <textarea name="ull_normativa_html_allowed_tags" rows="3" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('Ejemplo: &lt;p&gt;&lt;br&gt;&lt;strong&gt;&lt;em&gt;&lt;table&gt;. Dejar vacío para usar los tags por defecto.', 'ull-normativa'); ?>
        </p>
        <?php
    }
    
    public function render_remove_styles_field() {
        $value = get_option('ull_normativa_remove_inline_styles', true);
        ?>
        <label>
            <input type="checkbox" name="ull_normativa_remove_inline_styles" value="1" <?php checked($value, true); ?>>
            <?php _e('Eliminar estilos inline (atributo style) del contenido HTML', 'ull-normativa'); ?>
        </label>
        <?php
    }
    
    /**
     * Página de configuración de exportación PDF
     */
    public function render_pdf_settings_page() {
        // Cargar biblioteca de medios de WordPress
        wp_enqueue_media();
        
        // Guardar configuración
        if (isset($_POST['ull_pdf_save']) && check_admin_referer('ull_pdf_settings_nonce')) {
            $settings = array(
                'header_text' => sanitize_text_field($_POST['pdf_header_text']),
                'header_image' => esc_url_raw($_POST['pdf_header_image']),
                'footer_text' => sanitize_text_field($_POST['pdf_footer_text']),
                'show_date' => isset($_POST['pdf_show_date']),
                'show_metadata' => isset($_POST['pdf_show_metadata']),
                'primary_color' => sanitize_hex_color($_POST['pdf_primary_color']),
                'font_family' => sanitize_text_field($_POST['pdf_font_family']),
                'font_size' => sanitize_text_field($_POST['pdf_font_size']),
                'page_margins' => sanitize_text_field($_POST['pdf_page_margins']),
            );
            update_option('ull_normativa_pdf_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('Configuración de PDF guardada.', 'ull-normativa') . '</p></div>';
        }
        
        $options = get_option('ull_normativa_pdf_settings', array());
        $defaults = array(
            'header_text' => get_bloginfo('name'),
            'header_image' => '',
            'footer_text' => __('Documento generado desde el Portal de Normativa', 'ull-normativa'),
            'show_date' => true,
            'show_metadata' => true,
            'primary_color' => '#003366',
            'font_family' => 'Georgia, serif',
            'font_size' => '12pt',
            'page_margins' => '20mm',
        );
        $settings = wp_parse_args($options, $defaults);
        
        // Detectar librería PDF
        $pdf_library = $this->detect_pdf_library_status();
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de Exportación PDF', 'ull-normativa'); ?></h1>
            
            <!-- Estado de la librería PDF -->
            <div class="postbox" style="margin: 20px 0; padding: 0;">
                <h2 class="hndle" style="margin: 0; padding: 10px 15px; border-bottom: 1px solid #ccd0d4;">
                    <?php _e('Estado de la Librería PDF', 'ull-normativa'); ?>
                </h2>
                <div class="inside" style="padding: 15px;">
                    <?php if ($pdf_library['available']): ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="color: #46b450; font-size: 24px;">✓</span>
                            <div>
                                <strong style="color: #46b450;"><?php _e('Librería PDF activa:', 'ull-normativa'); ?></strong>
                                <code style="margin-left: 5px;"><?php echo esc_html($pdf_library['name']); ?></code>
                                <p class="description" style="margin: 5px 0 0;">
                                    <?php _e('Los PDFs se generarán automáticamente sin necesidad de imprimir.', 'ull-normativa'); ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="color: #dc3232; font-size: 24px;">✗</span>
                            <div>
                                <strong style="color: #dc3232;"><?php _e('No hay librería PDF instalada', 'ull-normativa'); ?></strong>
                                <p class="description" style="margin: 5px 0 0;">
                                    <?php _e('Se mostrará una página HTML optimizada para imprimir/guardar como PDF desde el navegador.', 'ull-normativa'); ?>
                                </p>
                                <p style="margin: 10px 0 0; padding: 10px; background: #f0f0f1; border-left: 4px solid #0073aa;">
                                    <strong><?php _e('Para habilitar generación nativa de PDF:', 'ull-normativa'); ?></strong><br>
                                    <?php _e('Descarga DOMPDF o mPDF y colócalo en:', 'ull-normativa'); ?><br>
                                    <code><?php echo esc_html(ULL_NORMATIVA_PLUGIN_DIR . 'vendor/dompdf/'); ?></code><br>
                                    <?php _e('o', 'ull-normativa'); ?><br>
                                    <code><?php echo esc_html(ULL_NORMATIVA_PLUGIN_DIR . 'vendor/mpdf/'); ?></code>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($pdf_library['paths_checked'])): ?>
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #0073aa;"><?php _e('Ver rutas comprobadas', 'ull-normativa'); ?></summary>
                        <ul style="margin: 10px 0; padding-left: 20px; font-size: 12px; color: #666;">
                            <?php foreach ($pdf_library['paths_checked'] as $path => $exists): ?>
                                <li>
                                    <?php echo $exists ? '✓' : '✗'; ?>
                                    <code><?php echo esc_html($path); ?></code>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                    <?php endif; ?>
                </div>
            </div>
            
            <p class="description">
                <?php _e('Configura el aspecto de los documentos PDF generados al exportar normas individuales.', 'ull-normativa'); ?>
            </p>
            
            <form method="post" action="">
                <?php wp_nonce_field('ull_pdf_settings_nonce'); ?>
                
                <div class="ull-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                    
                    <!-- Columna izquierda: Configuración -->
                    <div class="ull-admin-column">
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Cabecera del documento', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="pdf_header_text"><?php _e('Texto de cabecera', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <input type="text" name="pdf_header_text" id="pdf_header_text" 
                                                   value="<?php echo esc_attr($settings['header_text']); ?>" class="regular-text">
                                            <p class="description"><?php _e('Aparece en la parte superior del documento.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="pdf_header_image"><?php _e('Logo/Imagen', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <input type="text" name="pdf_header_image" id="pdf_header_image" 
                                                   value="<?php echo esc_url($settings['header_image']); ?>" class="regular-text">
                                            <button type="button" class="button" id="upload_header_image"><?php _e('Seleccionar imagen', 'ull-normativa'); ?></button>
                                            <button type="button" class="button" id="remove_header_image" <?php echo empty($settings['header_image']) ? 'style="display:none;"' : ''; ?>><?php _e('Eliminar', 'ull-normativa'); ?></button>
                                            <div id="header_image_preview" style="margin-top: 10px;">
                                                <?php if ($settings['header_image']) : ?>
                                                <img src="<?php echo esc_url($settings['header_image']); ?>" style="max-height: 60px;">
                                                <?php endif; ?>
                                            </div>
                                            <p class="description"><?php _e('Logo para mostrar en la cabecera del PDF.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="pdf_show_date"><?php _e('Mostrar fecha', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="pdf_show_date" id="pdf_show_date" value="1" <?php checked($settings['show_date']); ?>>
                                                <?php _e('Mostrar fecha de generación en la cabecera', 'ull-normativa'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Pie de página', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="pdf_footer_text"><?php _e('Texto del pie', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <input type="text" name="pdf_footer_text" id="pdf_footer_text" 
                                                   value="<?php echo esc_attr($settings['footer_text']); ?>" class="large-text">
                                            <p class="description"><?php _e('Se incluye automáticamente la URL de la norma.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Contenido', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="pdf_show_metadata"><?php _e('Metadatos', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="pdf_show_metadata" id="pdf_show_metadata" value="1" <?php checked($settings['show_metadata']); ?>>
                                                <?php _e('Mostrar caja con metadatos (tipo, fecha, órgano, etc.)', 'ull-normativa'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna derecha: Estilos -->
                    <div class="ull-admin-column">
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Estilos del documento', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="pdf_primary_color"><?php _e('Color principal', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <input type="color" name="pdf_primary_color" id="pdf_primary_color" 
                                                   value="<?php echo esc_attr($settings['primary_color']); ?>">
                                            <code><?php echo esc_html($settings['primary_color']); ?></code>
                                            <p class="description"><?php _e('Color para títulos, cabeceras y acentos.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="pdf_font_family"><?php _e('Tipografía', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <select name="pdf_font_family" id="pdf_font_family">
                                                <option value="Georgia, serif" <?php selected($settings['font_family'], 'Georgia, serif'); ?>>Georgia (Serif)</option>
                                                <option value="'Times New Roman', serif" <?php selected($settings['font_family'], "'Times New Roman', serif"); ?>>Times New Roman (Serif)</option>
                                                <option value="Arial, sans-serif" <?php selected($settings['font_family'], 'Arial, sans-serif'); ?>>Arial (Sans-serif)</option>
                                                <option value="Helvetica, sans-serif" <?php selected($settings['font_family'], 'Helvetica, sans-serif'); ?>>Helvetica (Sans-serif)</option>
                                                <option value="'Trebuchet MS', sans-serif" <?php selected($settings['font_family'], "'Trebuchet MS', sans-serif"); ?>>Trebuchet MS (Sans-serif)</option>
                                                <option value="Verdana, sans-serif" <?php selected($settings['font_family'], 'Verdana, sans-serif'); ?>>Verdana (Sans-serif)</option>
                                                <option value="'Courier New', monospace" <?php selected($settings['font_family'], "'Courier New', monospace"); ?>>Courier New (Monospace)</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="pdf_font_size"><?php _e('Tamaño de fuente', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <select name="pdf_font_size" id="pdf_font_size">
                                                <option value="10pt" <?php selected($settings['font_size'], '10pt'); ?>>10pt (Pequeño)</option>
                                                <option value="11pt" <?php selected($settings['font_size'], '11pt'); ?>>11pt</option>
                                                <option value="12pt" <?php selected($settings['font_size'], '12pt'); ?>>12pt (Normal)</option>
                                                <option value="13pt" <?php selected($settings['font_size'], '13pt'); ?>>13pt</option>
                                                <option value="14pt" <?php selected($settings['font_size'], '14pt'); ?>>14pt (Grande)</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="pdf_page_margins"><?php _e('Márgenes de página', 'ull-normativa'); ?></label></th>
                                        <td>
                                            <select name="pdf_page_margins" id="pdf_page_margins">
                                                <option value="15mm" <?php selected($settings['page_margins'], '15mm'); ?>>15mm (Estrecho)</option>
                                                <option value="20mm" <?php selected($settings['page_margins'], '20mm'); ?>>20mm (Normal)</option>
                                                <option value="25mm" <?php selected($settings['page_margins'], '25mm'); ?>>25mm (Amplio)</option>
                                                <option value="30mm" <?php selected($settings['page_margins'], '30mm'); ?>>30mm (Muy amplio)</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Vista previa', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <div style="border: 1px solid #ddd; padding: 20px; background: #fff; font-family: <?php echo esc_attr($settings['font_family']); ?>; font-size: <?php echo esc_attr($settings['font_size']); ?>;">
                                    <div style="border-bottom: 3px solid <?php echo esc_attr($settings['primary_color']); ?>; padding-bottom: 10px; margin-bottom: 15px;">
                                        <strong style="color: <?php echo esc_attr($settings['primary_color']); ?>;"><?php echo esc_html($settings['header_text']); ?></strong>
                                    </div>
                                    <h3 style="color: <?php echo esc_attr($settings['primary_color']); ?>; margin: 0 0 10px;"><?php _e('Título de ejemplo', 'ull-normativa'); ?></h3>
                                    <p style="background: #f5f5f5; padding: 10px; font-size: 10pt; margin: 0 0 15px;">
                                        <strong style="color: <?php echo esc_attr($settings['primary_color']); ?>;"><?php _e('Tipo:', 'ull-normativa'); ?></strong> Reglamento
                                    </p>
                                    <p style="margin: 0;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.</p>
                                </div>
                                <p class="description" style="margin-top: 10px;">
                                    <?php _e('Vista previa aproximada. El resultado final puede variar ligeramente.', 'ull-normativa'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="ull_pdf_save" class="button button-primary" value="<?php _e('Guardar configuración', 'ull-normativa'); ?>">
                </p>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                // Seleccionar imagen
                $('#upload_header_image').on('click', function(e) {
                    e.preventDefault();
                    var frame = wp.media({
                        title: '<?php _e('Seleccionar imagen', 'ull-normativa'); ?>',
                        button: { text: '<?php _e('Usar esta imagen', 'ull-normativa'); ?>' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#pdf_header_image').val(attachment.url);
                        $('#header_image_preview').html('<img src="' + attachment.url + '" style="max-height: 60px;">');
                        $('#remove_header_image').show();
                    });
                    frame.open();
                });
                
                // Eliminar imagen
                $('#remove_header_image').on('click', function(e) {
                    e.preventDefault();
                    $('#pdf_header_image').val('');
                    $('#header_image_preview').html('');
                    $(this).hide();
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Página de personalización de estilos
     */
    public function render_styles_page() {
        // Guardar configuración
        if (isset($_POST['ull_styles_save']) && check_admin_referer('ull_styles_settings_nonce')) {
            $styles = array(
                // Colores principales
                'color_primary' => $this->sanitize_color($_POST['color_primary']),
                'color_primary_dark' => $this->sanitize_color($_POST['color_primary_dark']),
                'color_secondary' => $this->sanitize_color($_POST['color_secondary']),
                'color_accent' => $this->sanitize_color($_POST['color_accent']),
                
                // Colores de texto
                'color_text' => $this->sanitize_color($_POST['color_text']),
                'color_text_muted' => $this->sanitize_color($_POST['color_text_muted']),
                'color_heading' => $this->sanitize_color($_POST['color_heading']),
                
                // Colores de fondo
                'color_background' => $this->sanitize_color($_POST['color_background']),
                'color_surface' => $this->sanitize_color($_POST['color_surface']),
                'color_border' => $this->sanitize_color($_POST['color_border']),
                
                // Estados
                'color_vigente' => $this->sanitize_color($_POST['color_vigente']),
                'color_derogada' => $this->sanitize_color($_POST['color_derogada']),
                'color_modificada' => $this->sanitize_color($_POST['color_modificada']),
                
                // Tipografía
                'font_family' => sanitize_text_field($_POST['font_family']),
                'font_size_base' => sanitize_text_field($_POST['font_size_base']),
                'line_height' => sanitize_text_field($_POST['line_height']),
                
                // Bordes y sombras
                'border_radius' => sanitize_text_field($_POST['border_radius']),
                'shadow_enabled' => isset($_POST['shadow_enabled']) ? 1 : 0,
                
                // Listado
                'list_style' => sanitize_text_field($_POST['list_style']),
                'list_hover_effect' => sanitize_text_field($_POST['list_hover_effect']),
                'list_show_badges' => isset($_POST['list_show_badges']) ? 1 : 0,
                'list_show_dates' => isset($_POST['list_show_dates']) ? 1 : 0,
                'list_item_padding' => sanitize_text_field($_POST['list_item_padding']),
                'card_padding' => sanitize_text_field($_POST['card_padding']),
                
                // Ficha individual
                'ficha_header_bg' => $this->sanitize_color($_POST['ficha_header_bg']),
                'ficha_header_bg_end' => $this->sanitize_color($_POST['ficha_header_bg_end']),
                'ficha_header_text' => $this->sanitize_color($_POST['ficha_header_text']),
                'ficha_header_style' => sanitize_text_field($_POST['ficha_header_style']),
                'ficha_header_gradient' => isset($_POST['ficha_header_gradient']) ? 1 : 0,
                'ficha_tabs_style' => sanitize_text_field($_POST['ficha_tabs_style']),
                'ficha_content_max_width' => sanitize_text_field($_POST['ficha_content_max_width']),
                'ficha_header_padding' => sanitize_text_field($_POST['ficha_header_padding']),
                'ficha_content_padding' => sanitize_text_field($_POST['ficha_content_padding']),
                
                // Nube de tags
                'tags_style' => sanitize_text_field($_POST['tags_style']),
                'tags_hover_effect' => sanitize_text_field($_POST['tags_hover_effect']),
                
                // Espaciado
                'spacing_unit' => sanitize_text_field($_POST['spacing_unit']),
            );
            
            update_option('ull_normativa_styles', $styles);
            
            // Regenerar CSS
            $this->generate_custom_css($styles);
            
            echo '<div class="notice notice-success"><p>' . __('Estilos guardados correctamente.', 'ull-normativa') . '</p></div>';
        }
        
        // Obtener estilos actuales
        $styles = get_option('ull_normativa_styles', array());
        $defaults = $this->get_default_styles();
        $s = wp_parse_args($styles, $defaults);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Personalizar Estilos', 'ull-normativa'); ?></h1>
            
            <p class="description">
                <?php _e('Personaliza los colores, tipografía y apariencia de los listados y fichas de normativa.', 'ull-normativa'); ?>
            </p>
            
            <form method="post" action="">
                <?php wp_nonce_field('ull_styles_settings_nonce'); ?>
                
                <div class="ull-styles-grid">
                    
                    <!-- Columna 1: Colores -->
                    <div class="ull-styles-column">
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Colores Principales', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Color Primario', 'ull-normativa'); ?></th>
                                        <td>
                                            <input type="color" name="color_primary" value="<?php echo esc_attr($s['color_primary']); ?>">
                                            <code><?php echo esc_html($s['color_primary']); ?></code>
                                            <p class="description"><?php _e('Color principal para cabeceras y acentos.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Color Primario Oscuro', 'ull-normativa'); ?></th>
                                        <td>
                                            <input type="color" name="color_primary_dark" value="<?php echo esc_attr($s['color_primary_dark']); ?>">
                                            <code><?php echo esc_html($s['color_primary_dark']); ?></code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Color Secundario', 'ull-normativa'); ?></th>
                                        <td>
                                            <input type="color" name="color_secondary" value="<?php echo esc_attr($s['color_secondary']); ?>">
                                            <code><?php echo esc_html($s['color_secondary']); ?></code>
                                            <p class="description"><?php _e('Color para enlaces y elementos interactivos.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Color Acento', 'ull-normativa'); ?></th>
                                        <td>
                                            <input type="color" name="color_accent" value="<?php echo esc_attr($s['color_accent']); ?>">
                                            <code><?php echo esc_html($s['color_accent']); ?></code>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Colores de Texto', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Texto Principal', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_text" value="<?php echo esc_attr($s['color_text']); ?>"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Texto Secundario', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_text_muted" value="<?php echo esc_attr($s['color_text_muted']); ?>"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Títulos', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_heading" value="<?php echo esc_attr($s['color_heading']); ?>"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Colores de Fondo', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Fondo General', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_background" value="<?php echo esc_attr($s['color_background']); ?>"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Superficie (Tarjetas)', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_surface" value="<?php echo esc_attr($s['color_surface']); ?>"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Bordes', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_border" value="<?php echo esc_attr($s['color_border']); ?>"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Colores de Estado', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Vigente', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_vigente" value="<?php echo esc_attr($s['color_vigente']); ?>"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Derogada', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_derogada" value="<?php echo esc_attr($s['color_derogada']); ?>"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Modificada', 'ull-normativa'); ?></th>
                                        <td><input type="color" name="color_modificada" value="<?php echo esc_attr($s['color_modificada']); ?>"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Columna 2: Tipografía y Diseño -->
                    <div class="ull-styles-column">
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Tipografía', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Familia de Fuente', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="font_family">
                                                <option value="system" <?php selected($s['font_family'], 'system'); ?>><?php _e('Sistema (San Francisco, Segoe UI)', 'ull-normativa'); ?></option>
                                                <option value="georgia" <?php selected($s['font_family'], 'georgia'); ?>>Georgia (Serif)</option>
                                                <option value="arial" <?php selected($s['font_family'], 'arial'); ?>>Arial (Sans-serif)</option>
                                                <option value="helvetica" <?php selected($s['font_family'], 'helvetica'); ?>>Helvetica (Sans-serif)</option>
                                                <option value="times" <?php selected($s['font_family'], 'times'); ?>>Times New Roman (Serif)</option>
                                                <option value="verdana" <?php selected($s['font_family'], 'verdana'); ?>>Verdana (Sans-serif)</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Tamaño Base', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="font_size_base">
                                                <option value="14px" <?php selected($s['font_size_base'], '14px'); ?>>14px (Pequeño)</option>
                                                <option value="15px" <?php selected($s['font_size_base'], '15px'); ?>>15px</option>
                                                <option value="16px" <?php selected($s['font_size_base'], '16px'); ?>>16px (Normal)</option>
                                                <option value="17px" <?php selected($s['font_size_base'], '17px'); ?>>17px</option>
                                                <option value="18px" <?php selected($s['font_size_base'], '18px'); ?>>18px (Grande)</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Altura de Línea', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="line_height">
                                                <option value="1.4" <?php selected($s['line_height'], '1.4'); ?>>1.4 (Compacto)</option>
                                                <option value="1.5" <?php selected($s['line_height'], '1.5'); ?>>1.5</option>
                                                <option value="1.6" <?php selected($s['line_height'], '1.6'); ?>>1.6 (Normal)</option>
                                                <option value="1.7" <?php selected($s['line_height'], '1.7'); ?>>1.7</option>
                                                <option value="1.8" <?php selected($s['line_height'], '1.8'); ?>>1.8 (Espaciado)</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Bordes y Sombras', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Radio de Bordes', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="border_radius">
                                                <option value="0" <?php selected($s['border_radius'], '0'); ?>><?php _e('Sin redondeo', 'ull-normativa'); ?></option>
                                                <option value="3px" <?php selected($s['border_radius'], '3px'); ?>>3px (Sutil)</option>
                                                <option value="6px" <?php selected($s['border_radius'], '6px'); ?>>6px (Normal)</option>
                                                <option value="10px" <?php selected($s['border_radius'], '10px'); ?>>10px (Redondeado)</option>
                                                <option value="15px" <?php selected($s['border_radius'], '15px'); ?>>15px (Muy redondeado)</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Sombras', 'ull-normativa'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="shadow_enabled" value="1" <?php checked($s['shadow_enabled'], 1); ?>>
                                                <?php _e('Habilitar sombras en tarjetas', 'ull-normativa'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Espaciado Base', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="spacing_unit">
                                                <option value="compact" <?php selected($s['spacing_unit'], 'compact'); ?>><?php _e('Compacto', 'ull-normativa'); ?></option>
                                                <option value="normal" <?php selected($s['spacing_unit'], 'normal'); ?>><?php _e('Normal', 'ull-normativa'); ?></option>
                                                <option value="spacious" <?php selected($s['spacing_unit'], 'spacious'); ?>><?php _e('Espacioso', 'ull-normativa'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Estilo del Listado', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Estilo de Items', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="list_style">
                                                <option value="bordered" <?php selected($s['list_style'], 'bordered'); ?>><?php _e('Con borde', 'ull-normativa'); ?></option>
                                                <option value="card" <?php selected($s['list_style'], 'card'); ?>><?php _e('Tarjeta elevada', 'ull-normativa'); ?></option>
                                                <option value="minimal" <?php selected($s['list_style'], 'minimal'); ?>><?php _e('Minimalista', 'ull-normativa'); ?></option>
                                                <option value="striped" <?php selected($s['list_style'], 'striped'); ?>><?php _e('Rayas alternas', 'ull-normativa'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Efecto Hover', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="list_hover_effect">
                                                <option value="none" <?php selected($s['list_hover_effect'], 'none'); ?>><?php _e('Ninguno', 'ull-normativa'); ?></option>
                                                <option value="highlight" <?php selected($s['list_hover_effect'], 'highlight'); ?>><?php _e('Resaltar', 'ull-normativa'); ?></option>
                                                <option value="lift" <?php selected($s['list_hover_effect'], 'lift'); ?>><?php _e('Elevar', 'ull-normativa'); ?></option>
                                                <option value="border" <?php selected($s['list_hover_effect'], 'border'); ?>><?php _e('Borde coloreado', 'ull-normativa'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Mostrar en Items', 'ull-normativa'); ?></th>
                                        <td>
                                            <label style="display:block;margin-bottom:5px;">
                                                <input type="checkbox" name="list_show_badges" value="1" <?php checked($s['list_show_badges'], 1); ?>>
                                                <?php _e('Badges de estado (Vigente/Derogada)', 'ull-normativa'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="list_show_dates" value="1" <?php checked($s['list_show_dates'], 1); ?>>
                                                <?php _e('Fechas', 'ull-normativa'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Ficha Individual', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Color Fondo Cabecera', 'ull-normativa'); ?></th>
                                        <td>
                                            <input type="color" name="ficha_header_bg" value="<?php echo esc_attr($s['ficha_header_bg']); ?>">
                                            <code><?php echo esc_html($s['ficha_header_bg']); ?></code>
                                            <p class="description"><?php _e('Color de fondo de la caja del título.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Color Fondo Cabecera (Degradado)', 'ull-normativa'); ?></th>
                                        <td>
                                            <input type="color" name="ficha_header_bg_end" value="<?php echo esc_attr($s['ficha_header_bg_end']); ?>">
                                            <code><?php echo esc_html($s['ficha_header_bg_end']); ?></code>
                                            <p class="description"><?php _e('Color final del degradado (si está activado).', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Color Texto Cabecera', 'ull-normativa'); ?></th>
                                        <td>
                                            <input type="color" name="ficha_header_text" value="<?php echo esc_attr($s['ficha_header_text']); ?>">
                                            <code><?php echo esc_html($s['ficha_header_text']); ?></code>
                                            <p class="description"><?php _e('Color del título y texto en la cabecera.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Estilo de Cabecera', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="ficha_header_style">
                                                <option value="solid" <?php selected($s['ficha_header_style'], 'solid'); ?>><?php _e('Color sólido', 'ull-normativa'); ?></option>
                                                <option value="gradient" <?php selected($s['ficha_header_style'], 'gradient'); ?>><?php _e('Degradado', 'ull-normativa'); ?></option>
                                                <option value="minimal" <?php selected($s['ficha_header_style'], 'minimal'); ?>><?php _e('Minimalista (sin fondo)', 'ull-normativa'); ?></option>
                                                <option value="bordered" <?php selected($s['ficha_header_style'], 'bordered'); ?>><?php _e('Con borde inferior', 'ull-normativa'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Degradado en Cabecera', 'ull-normativa'); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="ficha_header_gradient" value="1" <?php checked($s['ficha_header_gradient'], 1); ?>>
                                                <?php _e('Usar degradado entre los dos colores de fondo', 'ull-normativa'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Estilo de Pestañas', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="ficha_tabs_style">
                                                <option value="underline" <?php selected($s['ficha_tabs_style'], 'underline'); ?>><?php _e('Subrayado', 'ull-normativa'); ?></option>
                                                <option value="boxed" <?php selected($s['ficha_tabs_style'], 'boxed'); ?>><?php _e('Cajas', 'ull-normativa'); ?></option>
                                                <option value="pills" <?php selected($s['ficha_tabs_style'], 'pills'); ?>><?php _e('Píldoras', 'ull-normativa'); ?></option>
                                                <option value="minimal" <?php selected($s['ficha_tabs_style'], 'minimal'); ?>><?php _e('Minimalista', 'ull-normativa'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Ancho Máximo Contenido', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="ficha_content_max_width">
                                                <option value="none" <?php selected($s['ficha_content_max_width'], 'none'); ?>><?php _e('Sin límite', 'ull-normativa'); ?></option>
                                                <option value="800px" <?php selected($s['ficha_content_max_width'], '800px'); ?>>800px</option>
                                                <option value="900px" <?php selected($s['ficha_content_max_width'], '900px'); ?>>900px</option>
                                                <option value="1000px" <?php selected($s['ficha_content_max_width'], '1000px'); ?>>1000px</option>
                                                <option value="1200px" <?php selected($s['ficha_content_max_width'], '1200px'); ?>>1200px</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Padding Cabecera Ficha', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="ficha_header_padding">
                                                <option value="15px 20px" <?php selected($s['ficha_header_padding'], '15px 20px'); ?>><?php _e('Pequeño (15px 20px)', 'ull-normativa'); ?></option>
                                                <option value="20px 25px" <?php selected($s['ficha_header_padding'], '20px 25px'); ?>><?php _e('Normal (20px 25px)', 'ull-normativa'); ?></option>
                                                <option value="25px 30px" <?php selected($s['ficha_header_padding'], '25px 30px'); ?>><?php _e('Medio (25px 30px)', 'ull-normativa'); ?></option>
                                                <option value="30px 35px" <?php selected($s['ficha_header_padding'], '30px 35px'); ?>><?php _e('Grande (30px 35px)', 'ull-normativa'); ?></option>
                                                <option value="40px 45px" <?php selected($s['ficha_header_padding'], '40px 45px'); ?>><?php _e('Extra grande (40px 45px)', 'ull-normativa'); ?></option>
                                            </select>
                                            <p class="description"><?php _e('Espacio interior de la cabecera (arriba/abajo izquierda/derecha).', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Padding Contenido Ficha', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="ficha_content_padding">
                                                <option value="15px" <?php selected($s['ficha_content_padding'], '15px'); ?>><?php _e('Pequeño (15px)', 'ull-normativa'); ?></option>
                                                <option value="20px" <?php selected($s['ficha_content_padding'], '20px'); ?>><?php _e('Normal (20px)', 'ull-normativa'); ?></option>
                                                <option value="25px" <?php selected($s['ficha_content_padding'], '25px'); ?>><?php _e('Medio (25px)', 'ull-normativa'); ?></option>
                                                <option value="30px" <?php selected($s['ficha_content_padding'], '30px'); ?>><?php _e('Grande (30px)', 'ull-normativa'); ?></option>
                                                <option value="40px" <?php selected($s['ficha_content_padding'], '40px'); ?>><?php _e('Extra grande (40px)', 'ull-normativa'); ?></option>
                                            </select>
                                            <p class="description"><?php _e('Espacio interior del contenido y pestañas.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Padding del Listado', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Padding Items Listado', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="list_item_padding">
                                                <option value="10px 15px" <?php selected($s['list_item_padding'], '10px 15px'); ?>><?php _e('Pequeño (10px 15px)', 'ull-normativa'); ?></option>
                                                <option value="15px 20px" <?php selected($s['list_item_padding'], '15px 20px'); ?>><?php _e('Normal (15px 20px)', 'ull-normativa'); ?></option>
                                                <option value="20px 25px" <?php selected($s['list_item_padding'], '20px 25px'); ?>><?php _e('Medio (20px 25px)', 'ull-normativa'); ?></option>
                                                <option value="25px 30px" <?php selected($s['list_item_padding'], '25px 30px'); ?>><?php _e('Grande (25px 30px)', 'ull-normativa'); ?></option>
                                                <option value="30px 35px" <?php selected($s['list_item_padding'], '30px 35px'); ?>><?php _e('Extra grande (30px 35px)', 'ull-normativa'); ?></option>
                                            </select>
                                            <p class="description"><?php _e('Espacio interior de cada item del listado.', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Padding Tarjetas', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="card_padding">
                                                <option value="15px" <?php selected($s['card_padding'], '15px'); ?>><?php _e('Pequeño (15px)', 'ull-normativa'); ?></option>
                                                <option value="20px" <?php selected($s['card_padding'], '20px'); ?>><?php _e('Normal (20px)', 'ull-normativa'); ?></option>
                                                <option value="25px" <?php selected($s['card_padding'], '25px'); ?>><?php _e('Medio (25px)', 'ull-normativa'); ?></option>
                                                <option value="30px" <?php selected($s['card_padding'], '30px'); ?>><?php _e('Grande (30px)', 'ull-normativa'); ?></option>
                                                <option value="35px" <?php selected($s['card_padding'], '35px'); ?>><?php _e('Extra grande (35px)', 'ull-normativa'); ?></option>
                                            </select>
                                            <p class="description"><?php _e('Espacio interior de las tarjetas (vista cards).', 'ull-normativa'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Nube de Tags', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Estilo de Tags', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="tags_style">
                                                <option value="rounded" <?php selected($s['tags_style'], 'rounded'); ?>><?php _e('Redondeados', 'ull-normativa'); ?></option>
                                                <option value="pills" <?php selected($s['tags_style'], 'pills'); ?>><?php _e('Píldoras', 'ull-normativa'); ?></option>
                                                <option value="square" <?php selected($s['tags_style'], 'square'); ?>><?php _e('Cuadrados', 'ull-normativa'); ?></option>
                                                <option value="outline" <?php selected($s['tags_style'], 'outline'); ?>><?php _e('Solo borde', 'ull-normativa'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Efecto Hover', 'ull-normativa'); ?></th>
                                        <td>
                                            <select name="tags_hover_effect">
                                                <option value="fill" <?php selected($s['tags_hover_effect'], 'fill'); ?>><?php _e('Rellenar color', 'ull-normativa'); ?></option>
                                                <option value="lift" <?php selected($s['tags_hover_effect'], 'lift'); ?>><?php _e('Elevar', 'ull-normativa'); ?></option>
                                                <option value="scale" <?php selected($s['tags_hover_effect'], 'scale'); ?>><?php _e('Escalar', 'ull-normativa'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Columna 3: Vista previa -->
                    <div class="ull-styles-column ull-styles-preview">
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Vista Previa', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <p class="description"><?php _e('Vista previa aproximada de los estilos.', 'ull-normativa'); ?></p>
                                
                                <div id="ull-preview-container" style="margin-top: 15px;">
                                    <!-- Preview de item de listado -->
                                    <div class="ull-preview-section">
                                        <h4><?php _e('Item de Listado', 'ull-normativa'); ?></h4>
                                        <div class="ull-preview-item" id="preview-list-item">
                                            <div class="preview-meta">
                                                <span class="preview-tipo">Reglamento</span>
                                                <span class="preview-estado">Vigente</span>
                                            </div>
                                            <h3 class="preview-titulo">Reglamento de ejemplo</h3>
                                            <p class="preview-numero">REG-2024-001</p>
                                            <p class="preview-excerpt">Descripción breve del contenido de la norma...</p>
                                            <p class="preview-fecha">15/03/2024</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Preview de cabecera de ficha -->
                                    <div class="ull-preview-section">
                                        <h4><?php _e('Cabecera de Ficha', 'ull-normativa'); ?></h4>
                                        <div class="ull-preview-header" id="preview-ficha-header">
                                            <span class="preview-badge">Reglamento</span>
                                            <span class="preview-badge preview-badge-success">Vigente</span>
                                            <h2>Título de la Norma</h2>
                                            <p>REG-2024-001</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Preview de tags -->
                                    <div class="ull-preview-section">
                                        <h4><?php _e('Tags', 'ull-normativa'); ?></h4>
                                        <div class="ull-preview-tags" id="preview-tags">
                                            <span class="preview-tag">Académico <small>12</small></span>
                                            <span class="preview-tag">Económico <small>8</small></span>
                                            <span class="preview-tag">Personal <small>5</small></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Acciones', 'ull-normativa'); ?></h2>
                            <div class="inside">
                                <p>
                                    <button type="submit" name="ull_styles_save" class="button button-primary button-large"><?php _e('Guardar Estilos', 'ull-normativa'); ?></button>
                                </p>
                                <p>
                                    <button type="button" id="reset-styles" class="button"><?php _e('Restaurar Valores por Defecto', 'ull-normativa'); ?></button>
                                </p>
                            </div>
                        </div>
                        
                    </div>
                    
                </div>
                
            </form>
        </div>
        
        <style>
            .ull-styles-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 20px;
                margin-top: 20px;
            }
            @media (max-width: 1400px) {
                .ull-styles-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }
            @media (max-width: 1000px) {
                .ull-styles-grid {
                    grid-template-columns: 1fr;
                }
            }
            .ull-styles-column .postbox {
                margin-bottom: 15px;
            }
            .ull-styles-column .form-table th {
                width: 120px;
                padding: 10px 10px 10px 0;
            }
            .ull-styles-column .form-table td {
                padding: 10px 0;
            }
            .ull-preview-section {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #ddd;
            }
            .ull-preview-section:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }
            .ull-preview-section h4 {
                margin: 0 0 10px;
                font-size: 12px;
                text-transform: uppercase;
                color: #666;
            }
            .ull-preview-item {
                background: #fff;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 6px;
            }
            .ull-preview-item .preview-meta {
                display: flex;
                gap: 8px;
                margin-bottom: 8px;
            }
            .ull-preview-item .preview-tipo {
                background: #003366;
                color: #fff;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
            }
            .ull-preview-item .preview-estado {
                background: #d4edda;
                color: #155724;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
            }
            .ull-preview-item .preview-titulo {
                margin: 0 0 5px;
                font-size: 16px;
                color: #003366;
            }
            .ull-preview-item .preview-numero {
                margin: 0 0 8px;
                font-size: 13px;
                color: #666;
            }
            .ull-preview-item .preview-excerpt {
                margin: 0 0 8px;
                font-size: 13px;
                color: #555;
            }
            .ull-preview-item .preview-fecha {
                margin: 0;
                font-size: 12px;
                color: #888;
            }
            .ull-preview-header {
                background: linear-gradient(135deg, #003366 0%, #004488 100%);
                color: #fff;
                padding: 20px;
                border-radius: 6px;
            }
            .ull-preview-header h2 {
                margin: 10px 0 5px;
                font-size: 18px;
            }
            .ull-preview-header p {
                margin: 0;
                font-size: 13px;
                opacity: 0.8;
            }
            .ull-preview-header .preview-badge {
                display: inline-block;
                padding: 3px 10px;
                background: rgba(255,255,255,0.2);
                border-radius: 3px;
                font-size: 11px;
                margin-right: 5px;
            }
            .ull-preview-header .preview-badge-success {
                background: #28a745;
            }
            .ull-preview-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .ull-preview-tags .preview-tag {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 6px 12px;
                background: #f5f5f5;
                border: 1px solid #ddd;
                border-radius: 20px;
                font-size: 13px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .ull-preview-tags .preview-tag:hover {
                background: #003366;
                border-color: #003366;
                color: #fff;
            }
            .ull-preview-tags .preview-tag small {
                background: #ddd;
                padding: 1px 6px;
                border-radius: 10px;
                font-size: 10px;
            }
            .ull-preview-tags .preview-tag:hover small {
                background: rgba(255,255,255,0.2);
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Reset to defaults
            $('#reset-styles').on('click', function() {
                if (confirm('<?php _e('¿Restaurar todos los estilos a los valores por defecto?', 'ull-normativa'); ?>')) {
                    // Set default values
                    $('input[name="color_primary"]').val('#003366');
                    $('input[name="color_primary_dark"]').val('#002244');
                    $('input[name="color_secondary"]').val('#0066cc');
                    $('input[name="color_accent"]').val('#e63946');
                    $('input[name="color_text"]').val('#333333');
                    $('input[name="color_text_muted"]').val('#666666');
                    $('input[name="color_heading"]').val('#003366');
                    $('input[name="color_background"]').val('#f8f9fa');
                    $('input[name="color_surface"]').val('#ffffff');
                    $('input[name="color_border"]').val('#dee2e6');
                    $('input[name="color_vigente"]').val('#28a745');
                    $('input[name="color_derogada"]').val('#dc3545');
                    $('input[name="color_modificada"]').val('#ffc107');
                    $('select[name="font_family"]').val('system');
                    $('select[name="font_size_base"]').val('16px');
                    $('select[name="line_height"]').val('1.6');
                    $('select[name="border_radius"]').val('6px');
                    $('input[name="shadow_enabled"]').prop('checked', true);
                    $('select[name="list_style"]').val('bordered');
                    $('select[name="list_hover_effect"]').val('border');
                    $('input[name="list_show_badges"]').prop('checked', true);
                    $('input[name="list_show_dates"]').prop('checked', true);
                    $('select[name="ficha_header_style"]').val('gradient');
                    $('input[name="ficha_header_gradient"]').prop('checked', true);
                    $('select[name="ficha_tabs_style"]').val('underline');
                    $('select[name="ficha_content_max_width"]').val('none');
                    $('select[name="tags_style"]').val('rounded');
                    $('select[name="tags_hover_effect"]').val('fill');
                    $('select[name="spacing_unit"]').val('normal');
                }
            });
            
            // Live preview updates
            $('input[type="color"]').on('input', updatePreview);
            $('select').on('change', updatePreview);
            
            function updatePreview() {
                var primary = $('input[name="color_primary"]').val();
                var primaryDark = $('input[name="color_primary_dark"]').val();
                var text = $('input[name="color_text"]').val();
                var vigente = $('input[name="color_vigente"]').val();
                var radius = $('select[name="border_radius"]').val();
                
                // Colores de cabecera
                var headerBg = $('input[name="ficha_header_bg"]').val();
                var headerBgEnd = $('input[name="ficha_header_bg_end"]').val();
                var headerText = $('input[name="ficha_header_text"]').val();
                
                // Update preview item
                $('#preview-list-item').css('border-radius', radius);
                $('#preview-list-item .preview-tipo').css('background', primary);
                $('#preview-list-item .preview-titulo').css('color', primary);
                $('#preview-list-item .preview-estado').css({'background': vigente + '20', 'color': vigente});
                
                // Update preview header con colores personalizados
                var headerStyle = $('select[name="ficha_header_style"]').val();
                if (headerStyle === 'gradient' || $('input[name="ficha_header_gradient"]').is(':checked')) {
                    $('#preview-ficha-header').css({
                        'background': 'linear-gradient(135deg, ' + headerBg + ' 0%, ' + headerBgEnd + ' 100%)',
                        'color': headerText,
                        'border': 'none'
                    });
                } else if (headerStyle === 'solid') {
                    $('#preview-ficha-header').css({
                        'background': headerBg,
                        'color': headerText,
                        'border': 'none'
                    });
                } else if (headerStyle === 'minimal') {
                    $('#preview-ficha-header').css({
                        'background': '#fff',
                        'color': text,
                        'border-bottom': '3px solid ' + headerBg
                    });
                } else if (headerStyle === 'bordered') {
                    $('#preview-ficha-header').css({
                        'background': '#fff',
                        'color': text,
                        'border-left': '5px solid ' + headerBg
                    });
                }
                $('#preview-ficha-header').css('border-radius', radius);
                $('#preview-ficha-header h2, #preview-ficha-header p').css('color', headerStyle === 'minimal' || headerStyle === 'bordered' ? text : headerText);
                
                // Update preview tags
                var tagsStyle = $('select[name="tags_style"]').val();
                var tagRadius = tagsStyle === 'pills' ? '20px' : (tagsStyle === 'square' ? '3px' : '6px');
                $('.preview-tag').css('border-radius', tagRadius);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Obtener estilos por defecto
     */
    private function get_default_styles() {
        return array(
            'color_primary' => '#003366',
            'color_primary_dark' => '#002244',
            'color_secondary' => '#0066cc',
            'color_accent' => '#e63946',
            'color_text' => '#333333',
            'color_text_muted' => '#666666',
            'color_heading' => '#003366',
            'color_background' => '#f8f9fa',
            'color_surface' => '#ffffff',
            'color_border' => '#dee2e6',
            'color_vigente' => '#28a745',
            'color_derogada' => '#dc3545',
            'color_modificada' => '#ffc107',
            'font_family' => 'system',
            'font_size_base' => '16px',
            'line_height' => '1.6',
            'border_radius' => '6px',
            'shadow_enabled' => 1,
            'spacing_unit' => 'normal',
            'list_style' => 'bordered',
            'list_hover_effect' => 'border',
            'list_show_badges' => 1,
            'list_show_dates' => 1,
            'list_item_padding' => '15px 20px',
            'card_padding' => '20px',
            'ficha_header_bg' => '#003366',
            'ficha_header_bg_end' => '#002244',
            'ficha_header_text' => '#ffffff',
            'ficha_header_style' => 'gradient',
            'ficha_header_gradient' => 1,
            'ficha_tabs_style' => 'underline',
            'ficha_content_max_width' => 'none',
            'ficha_header_padding' => '25px 30px',
            'ficha_content_padding' => '25px',
            'tags_style' => 'rounded',
            'tags_hover_effect' => 'fill',
        );
    }
    
    /**
     * Generar CSS personalizado
     */
    private function generate_custom_css($styles) {
        $s = wp_parse_args($styles, $this->get_default_styles());
        
        // Mapeo de fuentes
        $fonts = array(
            'system' => "'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', Arial, sans-serif",
            'georgia' => "Georgia, 'Times New Roman', serif",
            'arial' => "Arial, Helvetica, sans-serif",
            'helvetica' => "'Helvetica Neue', Helvetica, Arial, sans-serif",
            'times' => "'Times New Roman', Times, serif",
            'verdana' => "Verdana, Geneva, sans-serif",
        );
        $font = isset($fonts[$s['font_family']]) ? $fonts[$s['font_family']] : $fonts['system'];
        
        // Espaciado
        $spacing = array(
            'compact' => '0.75rem',
            'normal' => '1rem',
            'spacious' => '1.5rem',
        );
        $space = isset($spacing[$s['spacing_unit']]) ? $spacing[$s['spacing_unit']] : $spacing['normal'];
        
        // Sombra
        $shadow = $s['shadow_enabled'] ? '0 2px 8px rgba(0,0,0,0.08)' : 'none';
        $shadow_hover = $s['shadow_enabled'] ? '0 4px 12px rgba(0,0,0,0.15)' : 'none';
        
        $css = "
/* ULL Normativa - Estilos Personalizados */
/* Generado automáticamente - No editar */

:root {
    --ull-primary: {$s['color_primary']};
    --ull-primary-dark: {$s['color_primary_dark']};
    --ull-secondary: {$s['color_secondary']};
    --ull-accent: {$s['color_accent']};
    --ull-text: {$s['color_text']};
    --ull-text-muted: {$s['color_text_muted']};
    --ull-heading: {$s['color_heading']};
    --ull-background: {$s['color_background']};
    --ull-surface: {$s['color_surface']};
    --ull-border: {$s['color_border']};
    --ull-vigente: {$s['color_vigente']};
    --ull-derogada: {$s['color_derogada']};
    --ull-modificada: {$s['color_modificada']};
    --ull-font: {$font};
    --ull-font-size: {$s['font_size_base']};
    --ull-line-height: {$s['line_height']};
    --ull-radius: {$s['border_radius']};
    --ull-shadow: {$shadow};
    --ull-shadow-hover: {$shadow_hover};
    --ull-spacing: {$space};
}

/* Base */
.ull-normativa-container,
.ull-norma-ficha,
.ull-nube-materias,
.ull-normativa-archivo {
    font-family: var(--ull-font);
    font-size: var(--ull-font-size);
    line-height: var(--ull-line-height);
    color: var(--ull-text);
}

/* Listado - Estilo base */
.ull-normativa-list .ull-item,
.ull-normativa-cards .ull-card {
    background: var(--ull-surface);
    border: 1px solid var(--ull-border);
    border-radius: var(--ull-radius);
    box-shadow: var(--ull-shadow);
    transition: all 0.2s ease;
}

/* Padding de items del listado */
.ull-normativa-list .ull-item {
    padding: {$s['list_item_padding']};
}

/* Padding de tarjetas */
.ull-normativa-cards .ull-card {
    padding: {$s['card_padding']};
}
";

        // Estilos de listado
        if ($s['list_style'] === 'card') {
            $css .= "
.ull-normativa-list .ull-item {
    box-shadow: var(--ull-shadow);
    border: none;
}
";
        } elseif ($s['list_style'] === 'minimal') {
            $css .= "
.ull-normativa-list .ull-item {
    border: none;
    border-bottom: 1px solid var(--ull-border);
    border-radius: 0;
    box-shadow: none;
}
";
        } elseif ($s['list_style'] === 'striped') {
            $css .= "
.ull-normativa-list .ull-item:nth-child(odd) {
    background: var(--ull-background);
}
";
        }

        // Efectos hover
        if ($s['list_hover_effect'] === 'highlight') {
            $css .= "
.ull-normativa-list .ull-item:hover {
    background: var(--ull-background);
}
";
        } elseif ($s['list_hover_effect'] === 'lift') {
            $css .= "
.ull-normativa-list .ull-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--ull-shadow-hover);
}
";
        } elseif ($s['list_hover_effect'] === 'border') {
            $css .= "
.ull-normativa-list .ull-item:hover {
    border-color: var(--ull-primary);
}
";
        }

        // Cabecera de ficha - Padding
        $css .= "
/* Padding de cabecera de ficha */
.ull-ficha-header {
    padding: {$s['ficha_header_padding']};
}

/* Padding de contenido de ficha */
.ull-tab-content {
    padding: {$s['ficha_content_padding']};
}

.ull-ficha-content {
    padding: {$s['ficha_content_padding']};
}
";

        // Cabecera de ficha - Estilos
        if ($s['ficha_header_style'] === 'gradient' || $s['ficha_header_gradient']) {
            $css .= "
.ull-ficha-header,
.ull-norma-header {
    background: linear-gradient(135deg, {$s['ficha_header_bg']} 0%, {$s['ficha_header_bg_end']} 100%);
    color: {$s['ficha_header_text']};
}
.ull-ficha-header .ull-ficha-titulo,
.ull-ficha-header .ull-ficha-numero,
.ull-norma-header .ull-norma-title {
    color: {$s['ficha_header_text']};
}
";
        } elseif ($s['ficha_header_style'] === 'solid') {
            $css .= "
.ull-ficha-header,
.ull-norma-header {
    background: {$s['ficha_header_bg']};
    color: {$s['ficha_header_text']};
}
.ull-ficha-header .ull-ficha-titulo,
.ull-ficha-header .ull-ficha-numero,
.ull-norma-header .ull-norma-title {
    color: {$s['ficha_header_text']};
}
";
        } elseif ($s['ficha_header_style'] === 'minimal') {
            $css .= "
.ull-ficha-header,
.ull-norma-header {
    background: var(--ull-surface);
    color: var(--ull-text);
    border-bottom: 3px solid {$s['ficha_header_bg']};
}
.ull-ficha-header .ull-ficha-titulo,
.ull-norma-header .ull-norma-title {
    color: var(--ull-heading);
}
.ull-ficha-header .ull-ficha-numero {
    color: {$s['ficha_header_bg']};
}
";
        } elseif ($s['ficha_header_style'] === 'bordered') {
            $css .= "
.ull-ficha-header,
.ull-norma-header {
    background: var(--ull-surface);
    color: var(--ull-text);
    border-left: 5px solid {$s['ficha_header_bg']};
    padding-left: 25px;
}
.ull-ficha-header .ull-ficha-titulo,
.ull-norma-header .ull-norma-title {
    color: var(--ull-heading);
}
";
        }

        // Estilos de pestañas
        if ($s['ficha_tabs_style'] === 'boxed') {
            $css .= "
.ull-ficha-tabs .ull-tab {
    background: var(--ull-background);
    border: 1px solid var(--ull-border);
    border-bottom: none;
    margin-right: 5px;
    border-radius: var(--ull-radius) var(--ull-radius) 0 0;
}
.ull-ficha-tabs .ull-tab.active {
    background: var(--ull-surface);
    border-bottom: 1px solid var(--ull-surface);
    margin-bottom: -1px;
}
";
        } elseif ($s['ficha_tabs_style'] === 'pills') {
            $css .= "
.ull-ficha-tabs {
    gap: 8px;
    border-bottom: none;
    padding: 10px;
}
.ull-ficha-tabs .ull-tab {
    border-radius: 20px;
    border: 1px solid var(--ull-border);
}
.ull-ficha-tabs .ull-tab.active {
    background: var(--ull-primary);
    color: #fff;
    border-color: var(--ull-primary);
}
";
        } elseif ($s['ficha_tabs_style'] === 'minimal') {
            $css .= "
.ull-ficha-tabs {
    background: transparent;
    border-bottom: none;
}
.ull-ficha-tabs .ull-tab {
    border: none;
    border-bottom: 2px solid transparent;
}
.ull-ficha-tabs .ull-tab.active {
    background: transparent;
    border-bottom-color: var(--ull-primary);
}
";
        }

        // Ancho máximo del contenido
        if ($s['ficha_content_max_width'] !== 'none') {
            $css .= "
.ull-tab-content,
.ull-contenido-norma {
    max-width: {$s['ficha_content_max_width']};
}
";
        }

        // Estilos de tags
        $tagRadius = '6px';
        if ($s['tags_style'] === 'pills') {
            $tagRadius = '20px';
        } elseif ($s['tags_style'] === 'square') {
            $tagRadius = '3px';
        }
        
        $css .= "
.ull-nube-tag,
.ull-term-link {
    border-radius: {$tagRadius};
}
";

        if ($s['tags_style'] === 'outline') {
            $css .= "
.ull-nube-tag {
    background: transparent;
    border: 2px solid var(--ull-border);
}
";
        }

        if ($s['tags_hover_effect'] === 'lift') {
            $css .= "
.ull-nube-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
";
        } elseif ($s['tags_hover_effect'] === 'scale') {
            $css .= "
.ull-nube-tag:hover {
    transform: scale(1.05);
}
";
        }

        // Estados
        $css .= "
.ull-estado-vigente {
    background: {$s['color_vigente']}20;
    color: {$s['color_vigente']};
}
.ull-estado-derogada {
    background: {$s['color_derogada']}20;
    color: {$s['color_derogada']};
}
.ull-estado-modificada {
    background: {$s['color_modificada']}20;
    color: {$s['color_modificada']};
}
";

        // Guardar CSS en archivo
        $upload_dir = wp_upload_dir();
        $css_dir = $upload_dir['basedir'] . '/ull-normativa';
        $css_file = $css_dir . '/custom-styles.css';
        
        if (!file_exists($css_dir)) {
            wp_mkdir_p($css_dir);
        }
        
        $result = file_put_contents($css_file, $css);
        
        if ($result === false) {
            error_log('ULL Normativa: No se pudo guardar el archivo CSS en ' . $css_file);
        }
        
        // Guardar versión para cache busting
        update_option('ull_normativa_styles_version', time());
    }
    
    /**
     * Sanitizar color hex
     */
    private function sanitize_color($color) {
        if (empty($color)) {
            return '';
        }
        
        // Asegurar que tiene el #
        $color = trim($color);
        if (strpos($color, '#') !== 0) {
            $color = '#' . $color;
        }
        
        // Validar formato hex
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return $color;
        }
        
        return '';
    }
    
    /**
     * Detectar estado de la librería PDF
     */
    private function detect_pdf_library_status() {
        $result = array(
            'available' => false,
            'name' => '',
            'paths_checked' => array()
        );
        
        // Solo comprobar autoload de Composer
        $composer_path = ULL_NORMATIVA_PLUGIN_DIR . 'vendor/autoload.php';
        $exists = file_exists($composer_path);
        $result['paths_checked']['vendor/autoload.php'] = $exists;
        
        if ($exists) {
            $result['available'] = true;
            $result['name'] = 'Composer (DOMPDF/mPDF)';
        }
        
        return $result;
    }
    
    /**
     * Página de herramientas
     */
    public function render_tools_page() {
        // Procesar acciones
        $message = '';
        $message_type = '';
        
        if (isset($_POST['ull_assign_numbers']) && check_admin_referer('ull_tools_nonce')) {
            $count = ULL_Post_Types::assign_numbers_to_unnumbered();
            $message = sprintf(__('Se han asignado números a %d normas.', 'ull-normativa'), $count);
            $message_type = 'success';
        }
        
        if (isset($_POST['ull_save_prefixes']) && check_admin_referer('ull_tools_nonce')) {
            if (!empty($_POST['custom_prefijos']) && is_array($_POST['custom_prefijos'])) {
                $custom_prefijos = array();
                foreach ($_POST['custom_prefijos'] as $slug => $prefijo) {
                    $prefijo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $prefijo), 0, 3));
                    if (strlen($prefijo) >= 2) {
                        $custom_prefijos[sanitize_key($slug)] = $prefijo;
                    }
                }
                update_option('ull_normativa_custom_prefijos', $custom_prefijos);
                $message = __('Prefijos guardados correctamente.', 'ull-normativa');
                $message_type = 'success';
            }
        }
        
        // Contar normas sin número
        global $wpdb;
        $sin_numero = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_numero_norma'
             WHERE p.post_type = 'norma'
             AND p.post_status IN ('publish', 'draft', 'pending')
             AND (pm.meta_value IS NULL OR pm.meta_value = '')"
        );
        
        $prefijo = get_option('ull_normativa_prefijo_numero', 'NOR');
        $siguiente = ULL_Post_Types::get_next_sequence_number($prefijo);
        
        // Obtener todos los tipos de norma
        $tipos_norma = get_terms(array(
            'taxonomy' => 'tipo_norma',
            'hide_empty' => false,
        ));
        $custom_prefijos = get_option('ull_normativa_custom_prefijos', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Herramientas de Normativa', 'ull-normativa'); ?></h1>
            
            <?php if ($message): ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="ull-tools-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px;">
                
                <!-- Herramienta: Asignar números -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('🔢 Asignar Números a Normas', 'ull-normativa'); ?></h2>
                    <div class="inside">
                        <p><?php _e('Asigna números automáticos a todas las normas que no tienen número asignado.', 'ull-normativa'); ?></p>
                        
                        <table class="form-table" style="margin: 0;">
                            <tr>
                                <th><?php _e('Normas sin número:', 'ull-normativa'); ?></th>
                                <td>
                                    <strong style="font-size: 1.5em; color: <?php echo $sin_numero > 0 ? '#dc3232' : '#46b450'; ?>">
                                        <?php echo intval($sin_numero); ?>
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Prefijo actual:', 'ull-normativa'); ?></th>
                                <td><code><?php echo esc_html($prefijo); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php _e('Siguiente número:', 'ull-normativa'); ?></th>
                                <td><code><?php echo esc_html($prefijo . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT)); ?></code></td>
                            </tr>
                        </table>
                        
                        <?php if ($sin_numero > 0): ?>
                        <form method="post" style="margin-top: 15px;">
                            <?php wp_nonce_field('ull_tools_nonce'); ?>
                            <p class="description" style="margin-bottom: 10px;">
                                <?php _e('⚠️ Esta acción asignará números secuenciales a todas las normas sin número. El orden será por fecha de creación.', 'ull-normativa'); ?>
                            </p>
                            <button type="submit" name="ull_assign_numbers" class="button button-primary">
                                <?php printf(__('Asignar números a %d normas', 'ull-normativa'), $sin_numero); ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <p style="margin-top: 15px; color: #46b450;">
                            <strong>✓ <?php _e('Todas las normas tienen número asignado.', 'ull-normativa'); ?></strong>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Herramienta: Información del sistema -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('📊 Estadísticas de Numeración', 'ull-normativa'); ?></h2>
                    <div class="inside">
                        <?php
                        $stats = $wpdb->get_results(
                            "SELECT 
                                SUBSTRING_INDEX(pm.meta_value, '-', 1) as prefijo,
                                COUNT(*) as total,
                                MAX(CAST(SUBSTRING_INDEX(pm.meta_value, '-', -1) AS UNSIGNED)) as max_num
                             FROM {$wpdb->postmeta} pm
                             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                             WHERE pm.meta_key = '_numero_norma'
                             AND pm.meta_value != ''
                             AND p.post_type = 'norma'
                             AND p.post_status != 'trash'
                             GROUP BY SUBSTRING_INDEX(pm.meta_value, '-', 1)
                             ORDER BY total DESC"
                        );
                        
                        if ($stats): ?>
                        <table class="widefat striped" style="margin-top: 0;">
                            <thead>
                                <tr>
                                    <th><?php _e('Prefijo', 'ull-normativa'); ?></th>
                                    <th><?php _e('Total normas', 'ull-normativa'); ?></th>
                                    <th><?php _e('Número más alto', 'ull-normativa'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats as $stat): ?>
                                <tr>
                                    <td><code><?php echo esc_html($stat->prefijo); ?></code></td>
                                    <td><?php echo intval($stat->total); ?></td>
                                    <td><?php echo intval($stat->max_num); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p><?php _e('No hay normas con número asignado.', 'ull-normativa'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Sección de prefijos -->
            <div style="margin-top: 30px;">
                <div class="postbox">
                    <h2 class="hndle"><?php _e('🏷️ Prefijos por Tipo de Norma', 'ull-normativa'); ?></h2>
                    <div class="inside">
                        <p><?php _e('Configura el prefijo (2-3 letras) que se usará para numerar cada tipo de norma. Por ejemplo, si "Texto Consolidado" tiene prefijo "TEX", las normas se numerarán TEX-0001, TEX-0002, etc.', 'ull-normativa'); ?></p>
                        
                        <form method="post">
                            <?php wp_nonce_field('ull_tools_nonce'); ?>
                            
                            <table class="widefat striped" style="max-width: 600px;">
                                <thead>
                                    <tr>
                                        <th><?php _e('Tipo de Norma', 'ull-normativa'); ?></th>
                                        <th style="width: 100px;"><?php _e('Prefijo', 'ull-normativa'); ?></th>
                                        <th style="width: 120px;"><?php _e('Siguiente Nº', 'ull-normativa'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($tipos_norma && !is_wp_error($tipos_norma)):
                                        foreach ($tipos_norma as $tipo):
                                            $prefijo_actual = ULL_Post_Types::get_tipo_prefix($tipo->slug);
                                            $siguiente_num = ULL_Post_Types::get_next_sequence_number($prefijo_actual);
                                            $es_personalizado = isset($custom_prefijos[$tipo->slug]);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($tipo->name); ?></strong>
                                            <br><small style="color: #666;"><?php echo esc_html($tipo->slug); ?></small>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="custom_prefijos[<?php echo esc_attr($tipo->slug); ?>]" 
                                                   value="<?php echo esc_attr($prefijo_actual); ?>" 
                                                   maxlength="3" 
                                                   style="width: 60px; text-transform: uppercase; font-family: monospace;">
                                            <?php if ($es_personalizado): ?>
                                            <span title="<?php _e('Personalizado', 'ull-normativa'); ?>" style="color: #0073aa;">✎</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code><?php echo esc_html($prefijo_actual . '-' . str_pad($siguiente_num, 4, '0', STR_PAD_LEFT)); ?></code>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="3"><?php _e('No hay tipos de norma definidos.', 'ull-normativa'); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <p style="margin-top: 15px;">
                                <button type="submit" name="ull_save_prefixes" class="button button-primary">
                                    <?php _e('Guardar Prefijos', 'ull-normativa'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

new ULL_Admin_Settings();
