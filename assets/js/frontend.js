/**
 * ULL Normativa - Frontend JavaScript
 */

(function($) {
    'use strict';

    var ULLNormativa = {
        
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initViewToggles();
            this.initTableOfContents();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Tabs en ficha individual - usando delegación de eventos
            $(document).on('click', '.ull-ficha-tabs .ull-tab', function(e) {
                e.preventDefault();
                self.handleTabClick($(this));
            });
            
            // Cambio de vista
            $(document).on('click', '.ull-view-toggle', function(e) {
                e.preventDefault();
                self.handleViewChange($(this));
            });
            
            // Ver contenido de versión
            $(document).on('click', '.ull-ver-version', function(e) {
                e.preventDefault();
                self.handleVersionView($(this));
            });
            
            // Filtros - submit del form
            $(document).on('change', '.ull-filters select', function() {
                $(this).closest('form').submit();
            });
        },
        
        initTabs: function() {
            // Verificar que hay tabs y contenido
            var tabsContainer = $('.ull-ficha-tabs');
            if (tabsContainer.length === 0) {
                return;
            }
            
            // Asegurar que el primer tab está activo
            var firstTab = tabsContainer.find('.ull-tab').first();
            var firstTabName = firstTab.data('tab');
            
            if (firstTab.length && !tabsContainer.find('.ull-tab.active').length) {
                firstTab.addClass('active');
            }
            
            // Asegurar que el primer contenido está visible
            var contentContainer = $('.ull-ficha-content');
            if (contentContainer.length && !contentContainer.find('.ull-tab-content.active').length) {
                contentContainer.find('.ull-tab-content').first().addClass('active');
            }
        },
        
        initViewToggles: function() {
            var container = $('.ull-normativa-container');
            if (container.length === 0) {
                return;
            }
            
            var savedMode = localStorage.getItem('ull_view_mode');
            if (savedMode) {
                this.setViewMode(container, savedMode);
            }
        },
        
        handleTabClick: function(btn) {
            var tab = btn.data('tab');
            var ficha = btn.closest('.ull-norma-ficha');
            
            // Cambiar tab activo
            ficha.find('.ull-ficha-tabs .ull-tab').removeClass('active');
            btn.addClass('active');
            
            // Cambiar contenido activo
            ficha.find('.ull-tab-content').removeClass('active');
            ficha.find('.ull-tab-content[data-tab="' + tab + '"]').addClass('active');
        },
        
        handleViewChange: function(btn) {
            var mode = btn.data('view');
            var container = btn.closest('.ull-normativa-container');
            
            this.setViewMode(container, mode);
            localStorage.setItem('ull_view_mode', mode);
        },
        
        setViewMode: function(container, mode) {
            // Cambiar botón activo
            container.find('.ull-view-toggle').removeClass('active');
            container.find('.ull-view-toggle[data-view="' + mode + '"]').addClass('active');
            
            // Cambiar vista activa
            container.find('.ull-normativa-list, .ull-normativa-cards, .ull-normativa-table').removeClass('active');
            container.find('.ull-normativa-' + mode).addClass('active');
        },
        
        handleVersionView: function(btn) {
            var self = this;
            var versionId = btn.data('version-id');
            var originalText = btn.text();
            
            btn.prop('disabled', true).text(ullNormativa.i18n.loading || 'Cargando...');
            
            $.ajax({
                url: ullNormativa.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ull_get_version_content',
                    nonce: ullNormativa.nonce,
                    version_id: versionId
                },
                success: function(response) {
                    btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        self.showVersionModal(response.data);
                    } else {
                        alert(response.data || 'Error al cargar la versión');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).text(originalText);
                    alert(ullNormativa.i18n.error || 'Error de conexión');
                }
            });
        },
        
        showVersionModal: function(data) {
            // Eliminar modal existente
            $('#ull-version-modal').remove();
            
            var modalHtml = '<div id="ull-version-modal" class="ull-modal">' +
                '<div class="ull-modal-overlay"></div>' +
                '<div class="ull-modal-content">' +
                    '<div class="ull-modal-header">' +
                        '<h3>Versión ' + data.version_number + ' - ' + data.version_date + '</h3>' +
                        '<button type="button" class="ull-modal-close">&times;</button>' +
                    '</div>' +
                    '<div class="ull-modal-body">' +
                        (data.changes_summary ? '<p class="ull-version-summary"><strong>Cambios:</strong> ' + data.changes_summary + '</p>' : '') +
                        '<div class="ull-version-content">' + data.content + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            var modal = $(modalHtml);
            $('body').append(modal);
            
            // Cerrar modal
            modal.on('click', '.ull-modal-close, .ull-modal-overlay', function() {
                modal.remove();
            });
            
            // Cerrar con ESC
            $(document).on('keyup.ullmodal', function(e) {
                if (e.key === 'Escape') {
                    modal.remove();
                    $(document).off('keyup.ullmodal');
                }
            });
            
            // Añadir estilos del modal si no existen
            if ($('#ull-modal-styles').length === 0) {
                var styles = '<style id="ull-modal-styles">' +
                    '.ull-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; display: flex; align-items: center; justify-content: center; }' +
                    '.ull-modal-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); }' +
                    '.ull-modal-content { position: relative; background: #fff; max-width: 900px; width: 90%; max-height: 90vh; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 5px 30px rgba(0,0,0,0.3); }' +
                    '.ull-modal-header { padding: 20px; background: #f5f5f5; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }' +
                    '.ull-modal-header h3 { margin: 0; font-size: 18px; }' +
                    '.ull-modal-close { border: none; background: none; font-size: 28px; cursor: pointer; padding: 0 10px; line-height: 1; color: #666; }' +
                    '.ull-modal-close:hover { color: #000; }' +
                    '.ull-modal-body { padding: 20px; overflow-y: auto; flex: 1; }' +
                    '.ull-version-summary { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #0073aa; }' +
                    '.ull-version-content { line-height: 1.7; }' +
                '</style>';
                $('head').append(styles);
            }
        },
        
        /**
         * Inicializar funcionalidad de Tabla de Contenidos
         */
        initTableOfContents: function() {
            var self = this;
            
            // Verificar que existe una tabla de contenidos
            if ($('.ull-tabla-contenidos').length === 0) {
                return;
            }
            
            console.log('ULL TOC: Inicializando tabla de contenidos');
            
            // Mejorar navegación en enlaces del TOC
            $('.ull-toc-link').on('click', function(e) {
                var href = $(this).attr('href');
                
                console.log('ULL TOC: Click en enlace', href);
                
                // Verificar que es un enlace de ancla
                if (href && href.indexOf('#') === 0) {
                    var targetId = href.substring(1);
                    var targetElement = document.getElementById(targetId);
                    
                    console.log('ULL TOC: Buscando elemento con ID:', targetId);
                    console.log('ULL TOC: Elemento encontrado:', targetElement);
                    
                    if (targetElement) {
                        e.preventDefault();
                        
                        // Scroll suave al elemento
                        self.smoothScrollTo($(targetElement));
                        
                        // Marcar enlace como activo temporalmente
                        $('.ull-toc-link').removeClass('ull-toc-active');
                        $(this).addClass('ull-toc-active');
                        
                        // Actualizar URL sin hacer scroll
                        if (window.history && window.history.pushState) {
                            window.history.pushState(null, null, href);
                        } else {
                            window.location.hash = href;
                        }
                        
                        console.log('ULL TOC: Navegado a', targetId);
                    } else {
                        console.warn('ULL TOC: No se encontró elemento con ID:', targetId);
                    }
                }
            });
            
            // Resaltar sección actual mientras se hace scroll
            self.initScrollSpy();
            
            // Si hay hash en la URL al cargar, navegar a él
            if (window.location.hash) {
                var targetElement = document.getElementById(window.location.hash.substring(1));
                if (targetElement) {
                    setTimeout(function() {
                        self.smoothScrollTo($(targetElement));
                    }, 300);
                }
            }
        },
        
        /**
         * Scroll suave a un elemento
         */
        smoothScrollTo: function(element) {
            var offset = 100; // Ajustar según altura de cabecera fija
            var targetPosition = element.offset().top - offset;
            
            $('html, body').animate({
                scrollTop: targetPosition
            }, 600, 'swing');
        },
        
        /**
         * Scroll Spy - Resaltar sección activa
         */
        initScrollSpy: function() {
            var headings = [];
            var links = $('.ull-toc-link');
            
            // Recopilar todos los encabezados con ID
            links.each(function() {
                var href = $(this).attr('href');
                if (href && href.startsWith('#')) {
                    var targetId = href.substring(1);
                    var targetElement = $('#' + targetId);
                    if (targetElement.length) {
                        headings.push({
                            id: targetId,
                            element: targetElement,
                            link: $(this)
                        });
                    }
                }
            });
            
            if (headings.length === 0) {
                return;
            }
            
            // Función para actualizar el enlace activo
            var updateActiveLink = function() {
                var scrollPos = $(window).scrollTop() + 150; // Offset
                
                var currentHeading = null;
                for (var i = 0; i < headings.length; i++) {
                    var headingTop = headings[i].element.offset().top;
                    if (scrollPos >= headingTop) {
                        currentHeading = headings[i];
                    } else {
                        break;
                    }
                }
                
                // Actualizar clases
                links.removeClass('ull-toc-active');
                if (currentHeading) {
                    currentHeading.link.addClass('ull-toc-active');
                }
            };
            
            // Ejecutar al hacer scroll (con throttle)
            var scrollTimeout;
            $(window).on('scroll', function() {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(updateActiveLink, 100);
            });
            
            // Ejecutar una vez al inicio
            updateActiveLink();
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        ULLNormativa.init();
    });

})(jQuery);

/**
 * Función global para toggle del índice de contenidos
 * Se llama desde el onclick del botón para mejor compatibilidad
 */
function ullToggleTOC(button) {
    // Encontrar el contenedor de la tabla
    var container = button.closest('.ull-tabla-contenidos');
    if (!container) return;
    
    // Encontrar la lista de contenidos
    var lista = container.querySelector('.ull-toc-lista');
    if (!lista) return;
    
    // Toggle de la clase collapsed
    var isCollapsed = lista.classList.contains('ull-toc-collapsed');
    
    if (isCollapsed) {
        // Expandir
        lista.classList.remove('ull-toc-collapsed');
        button.setAttribute('aria-expanded', 'true');
        button.setAttribute('aria-label', button.getAttribute('data-collapse-label') || 'Contraer índice');
    } else {
        // Contraer
        lista.classList.add('ull-toc-collapsed');
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-label', button.getAttribute('data-expand-label') || 'Expandir índice');
    }
}
