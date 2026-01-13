<?php
/**
 * Página de Ayuda - Shortcodes del Plugin ULL Normativa
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULL_Normativa_Help_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_help_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_help_styles'));
    }
    
    public function add_help_page() {
        add_submenu_page(
            'edit.php?post_type=norma',
            __('Ayuda - Shortcodes', 'ull-normativa'),
            __('📖 Ayuda', 'ull-normativa'),
            'edit_posts',
            'ull-normativa-help',
            array($this, 'render_help_page')
        );
    }
    
    public function enqueue_help_styles($hook) {
        if ($hook !== 'norma_page_ull-normativa-help') {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .ull-help-container {
                max-width: 1200px;
                margin: 20px auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .ull-help-header {
                border-bottom: 3px solid #2271b1;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .ull-help-header h1 {
                color: #2271b1;
                margin: 0 0 10px 0;
            }
            .ull-help-toc {
                background: #f6f7f7;
                border-left: 4px solid #2271b1;
                padding: 20px;
                margin-bottom: 30px;
            }
            .ull-help-toc h2 {
                margin-top: 0;
                font-size: 18px;
            }
            .ull-help-toc ul {
                margin: 0;
                padding-left: 20px;
            }
            .ull-help-toc li {
                margin: 8px 0;
            }
            .ull-help-toc a {
                text-decoration: none;
                color: #2271b1;
            }
            .ull-help-toc a:hover {
                color: #135e96;
                text-decoration: underline;
            }
            .ull-shortcode-section {
                margin-bottom: 50px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .ull-shortcode-section h2 {
                color: #2271b1;
                border-bottom: 2px solid #2271b1;
                padding-bottom: 10px;
                margin-top: 0;
            }
            .ull-shortcode-code {
                background: #f1f1f1;
                padding: 15px;
                border-left: 4px solid #2271b1;
                margin: 15px 0;
                font-family: monospace;
                font-size: 14px;
                overflow-x: auto;
            }
            .ull-shortcode-params {
                background: #fff8e1;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #ffc107;
            }
            .ull-shortcode-params h4 {
                margin-top: 0;
                color: #856404;
            }
            .ull-shortcode-params table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            .ull-shortcode-params th,
            .ull-shortcode-params td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            .ull-shortcode-params th {
                background: #fff3cd;
                font-weight: 600;
            }
            .ull-shortcode-example {
                background: #e7f5ff;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #0073aa;
            }
            .ull-shortcode-example h4 {
                margin-top: 0;
                color: #0073aa;
            }
            .ull-note {
                background: #f0f6fc;
                border-left: 4px solid #0969da;
                padding: 15px;
                margin: 15px 0;
            }
            .ull-note-warning {
                background: #fff3cd;
                border-left: 4px solid #856404;
            }
            .ull-note-success {
                background: #d1ecf1;
                border-left: 4px solid #0c5460;
            }
        ');
    }
    
    public function render_help_page() {
        ?>
        <div class="wrap ull-help-container">
            
            <div class="ull-help-header">
                <h1>📖 Guía de Shortcodes - ULL Normativa</h1>
                <p style="font-size: 16px; color: #666;">
                    Los shortcodes te permiten mostrar contenido de normativas en cualquier página o entrada de WordPress.
                </p>
            </div>
            
            <!-- Índice de Contenidos -->
            <div class="ull-help-toc">
                <h2>📋 Índice de Contenidos</h2>
                <ul>
                    <li><a href="#normativas">Shortcodes de Normativas</a>
                        <ul>
                            <li><a href="#ull_normativa_listado">1. [ull_normativa_listado]</a></li>
                            <li><a href="#ull_normativa_buscador">2. [ull_normativa_buscador]</a></li>
                            <li><a href="#ull_norma">3. [ull_norma]</a></li>
                            <li><a href="#ull_normativa_archivo">4. [ull_normativa_archivo]</a></li>
                            <li><a href="#ull_nube_materias">5. [ull_nube_materias]</a></li>
                            <li><a href="#ull_boton_normativa">6. [ull_boton_normativa]</a></li>
                            <li><a href="#ull_boton_categoria">7. [ull_boton_categoria]</a></li>
                            <li><a href="#ull_tabla_contenidos">8. [ull_tabla_contenidos]</a></li>
                        </ul>
                    </li>
                    <li><a href="#codigos">Shortcodes de Códigos</a>
                        <ul>
                            <li><a href="#ull_codigo">9. [ull_codigo]</a></li>
                            <li><a href="#ull_codigos_lista">10. [ull_codigos_lista]</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <!-- SECCIÓN: NORMATIVAS -->
            <h2 id="normativas" style="font-size: 24px; color: #2271b1; margin-top: 40px;">📚 Shortcodes de Normativas</h2>
            
            <!-- 1. ULL_NORMATIVA_LISTADO -->
            <div id="ull_normativa_listado" class="ull-shortcode-section">
                <h2>1. Listado de Normativas</h2>
                <p><strong>Shortcode:</strong> <code>[ull_normativa_listado]</code></p>
                <p>Muestra un listado de normativas con opciones de filtrado y paginación.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>numero</code></td>
                                <td>número</td>
                                <td>10</td>
                                <td>Número de normativas a mostrar</td>
                            </tr>
                            <tr>
                                <td><code>tipo</code></td>
                                <td>slug</td>
                                <td>-</td>
                                <td>Filtrar por tipo de norma</td>
                            </tr>
                            <tr>
                                <td><code>categoria</code></td>
                                <td>slug</td>
                                <td>-</td>
                                <td>Filtrar por categoría</td>
                            </tr>
                            <tr>
                                <td><code>materia</code></td>
                                <td>slug</td>
                                <td>-</td>
                                <td>Filtrar por materia</td>
                            </tr>
                            <tr>
                                <td><code>organo</code></td>
                                <td>slug</td>
                                <td>-</td>
                                <td>Filtrar por órgano</td>
                            </tr>
                            <tr>
                                <td><code>estado</code></td>
                                <td>vigente/derogada/modificada</td>
                                <td>-</td>
                                <td>Filtrar por estado</td>
                            </tr>
                            <tr>
                                <td><code>ordenar</code></td>
                                <td>fecha/titulo/numero</td>
                                <td>fecha</td>
                                <td>Campo por el que ordenar</td>
                            </tr>
                            <tr>
                                <td><code>orden</code></td>
                                <td>ASC/DESC</td>
                                <td>DESC</td>
                                <td>Orden ascendente o descendente</td>
                            </tr>
                            <tr>
                                <td><code>mostrar</code></td>
                                <td>lista/tarjetas/tabla</td>
                                <td>lista</td>
                                <td>Formato de visualización</td>
                            </tr>
                            <tr>
                                <td><code>filtros</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar filtros de búsqueda</td>
                            </tr>
                            <tr>
                                <td><code>paginacion</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar paginación</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Listado básico:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_listado]</div>
                    
                    <p><strong>Reglamentos vigentes en formato tarjetas:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_listado tipo="reglamento" estado="vigente" mostrar="tarjetas"]</div>
                    
                    <p><strong>Últimas 5 normativas sin filtros:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_listado numero="5" filtros="no"]</div>
                    
                    <p><strong>Normativas de una materia específica en tabla:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_listado materia="estudiantes" mostrar="tabla" numero="20"]</div>
                </div>
                
                <div class="ull-note">
                    <strong>📌 Nota:</strong> Para obtener el slug de tipos, categorías o materias, ve a la sección correspondiente en el menú de WordPress.
                </div>
            </div>
            
            <!-- 2. ULL_NORMATIVA_BUSCADOR -->
            <div id="ull_normativa_buscador" class="ull-shortcode-section">
                <h2>2. Buscador de Normativas</h2>
                <p><strong>Shortcode:</strong> <code>[ull_normativa_buscador]</code></p>
                <p>Muestra un formulario de búsqueda avanzada con filtros múltiples.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>mostrar_tipo</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar filtro por tipo</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_categoria</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar filtro por categoría</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_materia</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar filtro por materia</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_estado</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar filtro por estado</td>
                            </tr>
                            <tr>
                                <td><code>placeholder</code></td>
                                <td>texto</td>
                                <td>"Buscar normativa..."</td>
                                <td>Texto del campo de búsqueda</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Buscador completo:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_buscador]</div>
                    
                    <p><strong>Buscador simple (solo texto y tipo):</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_buscador mostrar_categoria="no" mostrar_materia="no" mostrar_estado="no"]</div>
                    
                    <p><strong>Buscador personalizado:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_buscador placeholder="Buscar reglamento..." mostrar_tipo="si" mostrar_estado="si"]</div>
                </div>
            </div>
            
            <!-- 3. ULL_NORMA -->
            <div id="ull_norma" class="ull-shortcode-section">
                <h2>3. Ficha de Norma Individual</h2>
                <p><strong>Shortcode:</strong> <code>[ull_norma]</code></p>
                <p>Muestra la información completa de una norma específica.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>número</td>
                                <td>-</td>
                                <td><strong>Requerido.</strong> ID de la norma</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_metadatos</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar información adicional</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_contenido</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar contenido completo</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_relaciones</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar normas relacionadas</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Ficha completa de norma con ID 123:</strong></p>
                    <div class="ull-shortcode-code">[ull_norma id="123"]</div>
                    
                    <p><strong>Solo metadatos sin contenido:</strong></p>
                    <div class="ull-shortcode-code">[ull_norma id="123" mostrar_contenido="no"]</div>
                    
                    <p><strong>Norma sin relaciones:</strong></p>
                    <div class="ull-shortcode-code">[ull_norma id="123" mostrar_relaciones="no"]</div>
                </div>
                
                <div class="ull-note ull-note-warning">
                    <strong>⚠️ Importante:</strong> El parámetro <code>id</code> es obligatorio. Para encontrar el ID de una norma, ve a Normativas y pasa el cursor sobre el título.
                </div>
            </div>
            
            <!-- 4. ULL_NORMATIVA_ARCHIVO -->
            <div id="ull_normativa_archivo" class="ull-shortcode-section">
                <h2>4. Archivo de Normativas (Vista Completa)</h2>
                <p><strong>Shortcode:</strong> <code>[ull_normativa_archivo]</code></p>
                <p>Muestra el archivo completo con filtros, búsqueda y vista de resultados. Similar al archivo por defecto pero insertable en cualquier página.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>por_pagina</code></td>
                                <td>número</td>
                                <td>20</td>
                                <td>Normativas por página</td>
                            </tr>
                            <tr>
                                <td><code>vista</code></td>
                                <td>lista/tarjetas/tabla</td>
                                <td>lista</td>
                                <td>Vista por defecto</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Archivo estándar:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_archivo]</div>
                    
                    <p><strong>Archivo con vista de tarjetas:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_archivo vista="tarjetas"]</div>
                    
                    <p><strong>Archivo con 50 elementos por página:</strong></p>
                    <div class="ull-shortcode-code">[ull_normativa_archivo por_pagina="50"]</div>
                </div>
            </div>
            
            <!-- 5. ULL_NUBE_MATERIAS -->
            <div id="ull_nube_materias" class="ull-shortcode-section">
                <h2>5. Nube de Materias</h2>
                <p><strong>Shortcode:</strong> <code>[ull_nube_materias]</code></p>
                <p>Muestra una nube de etiquetas con las materias normativas, con tamaños proporcionales a la cantidad de normas.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>taxonomy</code></td>
                                <td>materia_norma/tipo_norma/categoria_norma/organo_norma</td>
                                <td>materia_norma</td>
                                <td>Taxonomía a mostrar</td>
                            </tr>
                            <tr>
                                <td><code>titulo</code></td>
                                <td>texto</td>
                                <td>"Materias"</td>
                                <td>Título de la nube</td>
                            </tr>
                            <tr>
                                <td><code>numero</code></td>
                                <td>número</td>
                                <td>50</td>
                                <td>Número máximo de términos</td>
                            </tr>
                            <tr>
                                <td><code>ordenar</code></td>
                                <td>nombre/count</td>
                                <td>count</td>
                                <td>Ordenar por nombre o cantidad</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_vacio</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar mensaje si no hay términos</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Nube estándar de materias:</strong></p>
                    <div class="ull-shortcode-code">[ull_nube_materias]</div>
                    
                    <p><strong>Top 20 materias más usadas:</strong></p>
                    <div class="ull-shortcode-code">[ull_nube_materias numero="20" ordenar="count"]</div>
                    
                    <p><strong>Materias ordenadas alfabéticamente:</strong></p>
                    <div class="ull-shortcode-code">[ull_nube_materias ordenar="nombre"]</div>
                    
                    <p><strong>Nube de tipos de norma:</strong></p>
                    <div class="ull-shortcode-code">[ull_nube_materias taxonomy="tipo_norma" titulo="Tipos de Normativa"]</div>
                    
                    <p><strong>Nube de categorías sin mensaje vacío:</strong></p>
                    <div class="ull-shortcode-code">[ull_nube_materias taxonomy="categoria_norma" titulo="Categorías" mostrar_vacio="no"]</div>
                    
                    <p><strong>Top 10 órganos más activos:</strong></p>
                    <div class="ull-shortcode-code">[ull_nube_materias taxonomy="organo_norma" titulo="Órganos" numero="10"]</div>
                </div>
                
                <div class="ull-note">
                    <strong>📌 Nota sobre taxonomías:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li><strong>materia_norma:</strong> Materias o temas de las normativas</li>
                        <li><strong>tipo_norma:</strong> Tipos de documentos (reglamento, acuerdo, etc.)</li>
                        <li><strong>categoria_norma:</strong> Categorías generales</li>
                        <li><strong>organo_norma:</strong> Órganos que aprueban las normas</li>
                    </ul>
                </div>
                
                <div class="ull-note ull-note-warning">
                    <strong>⚠️ Si no aparece nada:</strong>
                    <ol style="margin: 10px 0 0 20px;">
                        <li>Verifica que existan términos en la taxonomía (Normativas → Materias)</li>
                        <li>Verifica que haya normas asignadas a esos términos</li>
                        <li>Si no hay términos, verás un mensaje: "No hay materias disponibles"</li>
                        <li>Usa <code>mostrar_vacio="si"</code> para ver mensajes de depuración</li>
                    </ol>
                </div>
            </div>
            
            <!-- 6. ULL_BOTON_NORMATIVA -->
            <div id="ull_boton_normativa" class="ull-shortcode-section">
                <h2>6. Botón a Archivo de Normativas</h2>
                <p><strong>Shortcode:</strong> <code>[ull_boton_normativa]</code></p>
                <p>Crea un botón que enlaza al archivo completo de normativas.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>texto</code></td>
                                <td>texto</td>
                                <td>"Ver toda la normativa"</td>
                                <td>Texto del botón</td>
                            </tr>
                            <tr>
                                <td><code>clase</code></td>
                                <td>clases CSS</td>
                                <td>-</td>
                                <td>Clases CSS adicionales</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Botón estándar:</strong></p>
                    <div class="ull-shortcode-code">[ull_boton_normativa]</div>
                    
                    <p><strong>Botón personalizado:</strong></p>
                    <div class="ull-shortcode-code">[ull_boton_normativa texto="Consultar normativa completa"]</div>
                    
                    <p><strong>Botón con estilo outline:</strong></p>
                    <div class="ull-shortcode-code">[ull_boton_normativa texto="Ver más" clase="is-style-outline"]</div>
                </div>
            </div>
            
            <!-- 7. ULL_BOTON_CATEGORIA -->
            <div id="ull_boton_categoria" class="ull-shortcode-section">
                <h2>7. Botón a Categoría Específica</h2>
                <p><strong>Shortcode:</strong> <code>[ull_boton_categoria]</code></p>
                <p>Crea un botón que enlaza al archivo filtrado por tipo, categoría o estado.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>tipo</code></td>
                                <td>slug</td>
                                <td>-</td>
                                <td>Filtrar por tipo</td>
                            </tr>
                            <tr>
                                <td><code>categoria</code></td>
                                <td>slug</td>
                                <td>-</td>
                                <td>Filtrar por categoría</td>
                            </tr>
                            <tr>
                                <td><code>estado</code></td>
                                <td>vigente/derogada/modificada</td>
                                <td>-</td>
                                <td>Filtrar por estado</td>
                            </tr>
                            <tr>
                                <td><code>texto</code></td>
                                <td>texto</td>
                                <td>"Ver más"</td>
                                <td>Texto del botón</td>
                            </tr>
                            <tr>
                                <td><code>clase</code></td>
                                <td>clases CSS</td>
                                <td>"is-style-outline has-small-font-size"</td>
                                <td>Clases CSS adicionales</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Ver todos los reglamentos:</strong></p>
                    <div class="ull-shortcode-code">[ull_boton_categoria tipo="reglamento" texto="Ver todos los reglamentos"]</div>
                    
                    <p><strong>Ver normativas vigentes:</strong></p>
                    <div class="ull-shortcode-code">[ull_boton_categoria estado="vigente" texto="Normativa vigente"]</div>
                    
                    <p><strong>Ver categoría específica:</strong></p>
                    <div class="ull-shortcode-code">[ull_boton_categoria categoria="academica" texto="Normativa académica"]</div>
                </div>
            </div>
            
            <!-- 8. ULL_TABLA_CONTENIDOS -->
            <div id="ull_tabla_contenidos" class="ull-shortcode-section">
                <h2>8. Tabla de Contenidos Automática</h2>
                <p><strong>Shortcode:</strong> <code>[ull_tabla_contenidos]</code></p>
                <p>Genera automáticamente una tabla de contenidos basada en los encabezados (H1-H6) de la norma. <span style="color: #d63638;">Versión 2.0+</span></p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>titulo</code></td>
                                <td>texto</td>
                                <td>"Índice de contenidos"</td>
                                <td>Título de la tabla</td>
                            </tr>
                            <tr>
                                <td><code>niveles</code></td>
                                <td>números separados por comas</td>
                                <td>"1,2,3,4"</td>
                                <td>Niveles de encabezado (1=H1, 2=H2, etc.)</td>
                            </tr>
                            <tr>
                                <td><code>estilo</code></td>
                                <td>lista/numerado</td>
                                <td>lista</td>
                                <td>Estilo de visualización</td>
                            </tr>
                            <tr>
                                <td><code>contraer</code></td>
                                <td>siempre/auto/no</td>
                                <td>siempre</td>
                                <td>Capacidad de contraer/expandir</td>
                            </tr>
                            <tr>
                                <td><code>inicio</code></td>
                                <td>expandido/colapsado</td>
                                <td>expandido</td>
                                <td>Estado inicial del índice</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Tabla básica (siempre contraíble, expandida):</strong></p>
                    <div class="ull-shortcode-code">[ull_tabla_contenidos]</div>
                    
                    <p><strong>Índice que inicia colapsado (útil para índices muy largos):</strong></p>
                    <div class="ull-shortcode-code">[ull_tabla_contenidos inicio="colapsado"]</div>
                    
                    <p><strong>Solo H2 y H3, con numeración:</strong></p>
                    <div class="ull-shortcode-code">[ull_tabla_contenidos niveles="2,3" estilo="numerado"]</div>
                    
                    <p><strong>Contraíble automático (solo si hay más de 10 secciones):</strong></p>
                    <div class="ull-shortcode-code">[ull_tabla_contenidos contraer="auto"]</div>
                    
                    <p><strong>Índice NO contraíble (siempre visible):</strong></p>
                    <div class="ull-shortcode-code">[ull_tabla_contenidos contraer="no"]</div>
                    
                    <p><strong>Todos los niveles, numerados, inicia colapsado:</strong></p>
                    <div class="ull-shortcode-code">[ull_tabla_contenidos niveles="1,2,3,4,5,6" estilo="numerado" inicio="colapsado"]</div>
                    
                    <p><strong>Título personalizado con inicio colapsado:</strong></p>
                    <div class="ull-shortcode-code">[ull_tabla_contenidos titulo="Índice del Reglamento" inicio="colapsado"]</div>
                </div>
                
                <div class="ull-note ull-note-success">
                    <strong>✅ Consejo:</strong> Este shortcode solo funciona en el contenido de una norma individual. Los encabezados deben usar las etiquetas HTML estándar (H1, H2, H3, etc.).
                </div>
                
                <div class="ull-note">
                    <strong>📌 Nota sobre el parámetro "contraer":</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li><strong>siempre:</strong> El índice siempre tendrá botón para contraer/expandir (recomendado)</li>
                        <li><strong>auto:</strong> Solo será contraíble si tiene más de 10 secciones. Auto-colapsa si tiene más de 15</li>
                        <li><strong>no:</strong> El índice nunca será contraíble, siempre visible</li>
                    </ul>
                </div>
                
                <div class="ull-note ull-note-warning">
                    <strong>⚠️ Para índices muy largos:</strong>
                    <p style="margin: 10px 0 0 0;">Si tu norma tiene muchas secciones (20+), se recomienda usar:</p>
                    <div class="ull-shortcode-code" style="margin-top: 10px;">[ull_tabla_contenidos inicio="colapsado"]</div>
                    <p style="margin: 10px 0 0 0;">Esto mejora la experiencia del usuario al no ocupar todo el espacio inicial de la página.</p>
                </div>
            </div>
            
            <!-- SECCIÓN: CÓDIGOS -->
            <h2 id="codigos" style="font-size: 24px; color: #2271b1; margin-top: 60px;">📘 Shortcodes de Códigos</h2>
            
            <!-- 9. ULL_CODIGO -->
            <div id="ull_codigo" class="ull-shortcode-section">
                <h2>9. Mostrar Código Individual</h2>
                <p><strong>Shortcode:</strong> <code>[ull_codigo]</code></p>
                <p>Muestra un código completo con todas sus normativas.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>número</td>
                                <td>-</td>
                                <td>ID del código (id o slug requerido)</td>
                            </tr>
                            <tr>
                                <td><code>slug</code></td>
                                <td>texto</td>
                                <td>-</td>
                                <td>Slug del código (alternativa a id)</td>
                            </tr>
                            <tr>
                                <td><code>estilo</code></td>
                                <td>accordion/list/compact</td>
                                <td>accordion</td>
                                <td>Modo de visualización</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_indice</code></td>
                                <td>true/false/1/0</td>
                                <td>true</td>
                                <td>Mostrar índice de normativas</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_fechas</code></td>
                                <td>true/false/1/0</td>
                                <td>true</td>
                                <td>Mostrar fechas de publicación</td>
                            </tr>
                            <tr>
                                <td><code>permitir_pdf</code></td>
                                <td>true/false/1/0</td>
                                <td>true</td>
                                <td>Mostrar botón de exportar PDF</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Código completo con ID 45:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigo id="45"]</div>
                    
                    <p><strong>Código por slug:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigo slug="codigo-estudiantes"]</div>
                    
                    <p><strong>Vista lista sin índice:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigo id="45" estilo="list" mostrar_indice="false"]</div>
                    
                    <p><strong>Vista compacta sin PDF:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigo id="45" estilo="compact" permitir_pdf="false"]</div>
                    
                    <p><strong>Acordeón sin fechas:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigo id="45" estilo="accordion" mostrar_fechas="false"]</div>
                </div>
                
                <div class="ull-note">
                    <strong>📌 Nota sobre estilos:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li><strong>accordion:</strong> Las normativas se muestran como acordeón expandible/contraíble</li>
                        <li><strong>list:</strong> Lista completa con todas las normativas expandidas</li>
                        <li><strong>compact:</strong> Vista compacta solo con títulos y enlaces</li>
                    </ul>
                </div>
            </div>
            
            <!-- 10. ULL_CODIGOS_LISTA -->
            <div id="ull_codigos_lista" class="ull-shortcode-section">
                <h2>10. Listado de Códigos</h2>
                <p><strong>Shortcode:</strong> <code>[ull_codigos_lista]</code></p>
                <p>Muestra un listado de códigos disponibles en formato de tarjetas o lista.</p>
                
                <div class="ull-shortcode-params">
                    <h4>📝 Parámetros</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valores</th>
                                <th>Por defecto</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>limite</code></td>
                                <td>número</td>
                                <td>6</td>
                                <td>Número de códigos a mostrar</td>
                            </tr>
                            <tr>
                                <td><code>columnas</code></td>
                                <td>número (1-4)</td>
                                <td>3</td>
                                <td>Número de columnas en vista tarjetas</td>
                            </tr>
                            <tr>
                                <td><code>orderby</code></td>
                                <td>title/date/modified</td>
                                <td>title</td>
                                <td>Campo por el que ordenar</td>
                            </tr>
                            <tr>
                                <td><code>order</code></td>
                                <td>ASC/DESC</td>
                                <td>ASC</td>
                                <td>Orden ascendente o descendente</td>
                            </tr>
                            <tr>
                                <td><code>estilo</code></td>
                                <td>tarjetas/lista</td>
                                <td>tarjetas</td>
                                <td>Formato de visualización</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_extracto</code></td>
                                <td>si/no</td>
                                <td>no</td>
                                <td>Mostrar descripción breve</td>
                            </tr>
                            <tr>
                                <td><code>mostrar_contador</code></td>
                                <td>si/no</td>
                                <td>si</td>
                                <td>Mostrar número de normativas</td>
                            </tr>
                            <tr>
                                <td><code>ids</code></td>
                                <td>IDs separados por comas</td>
                                <td>-</td>
                                <td>Mostrar solo códigos específicos</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="ull-shortcode-example">
                    <h4>💡 Ejemplos de Uso</h4>
                    
                    <p><strong>Listado básico en 3 columnas:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigos_lista]</div>
                    
                    <p><strong>Últimos 4 códigos en 2 columnas:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigos_lista limite="4" columnas="2" orderby="date" order="DESC"]</div>
                    
                    <p><strong>Lista con extractos:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigos_lista estilo="lista" mostrar_extracto="si"]</div>
                    
                    <p><strong>Códigos específicos (por ID):</strong></p>
                    <div class="ull-shortcode-code">[ull_codigos_lista ids="12,45,78"]</div>
                    
                    <p><strong>4 columnas sin contador:</strong></p>
                    <div class="ull-shortcode-code">[ull_codigos_lista columnas="4" mostrar_contador="no"]</div>
                </div>
                
                <div class="ull-note">
                    <strong>📌 Nota sobre columnas:</strong>
                    El número de columnas solo aplica a la vista de tarjetas. En dispositivos móviles se ajusta automáticamente a 1 columna.
                </div>
            </div>
            
            <!-- SECCIÓN FINAL -->
            <div class="ull-note" style="margin-top: 40px; font-size: 14px;">
                <h3 style="margin-top: 0;">🎓 Recursos Adicionales</h3>
                <ul>
                    <li><strong>Soporte:</strong> Para ayuda adicional, contacta con el administrador del sistema.</li>
                    <li><strong>Documentación completa:</strong> Consulta los archivos README incluidos en el plugin.</li>
                    <li><strong>Versión actual:</strong> ULL Normativa v2.2</li>
                </ul>
            </div>
            
        </div>
        <?php
    }
}

new ULL_Normativa_Help_Page();
