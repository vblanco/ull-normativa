/**
 * ULL Normativa - Tabla de Contenidos - Navegación
 * Script simplificado para navegación desde TOC
 * v2.2.7 - Añade IDs desde JavaScript si no existen
 */

(function() {
    'use strict';
    
    /**
     * Generar ID único para un encabezado
     */
    function generateHeadingId(text, counter) {
        // Limpiar texto
        text = text.toLowerCase();
        text = text.replace(/[áàäâ]/g, 'a');
        text = text.replace(/[éèëê]/g, 'e');
        text = text.replace(/[íìïî]/g, 'i');
        text = text.replace(/[óòöô]/g, 'o');
        text = text.replace(/[úùüû]/g, 'u');
        text = text.replace(/ñ/g, 'n');
        text = text.replace(/[^a-z0-9]+/g, '-');
        text = text.replace(/^-+|-+$/g, '');
        
        // Limitar longitud
        if (text.length > 50) {
            text = text.substring(0, 50);
        }
        
        return 'toc-' + text + '-' + counter;
    }
    
    /**
     * Añadir IDs a los encabezados si no los tienen
     */
    function addHeadingIds() {
        var headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
        var counter = 0;
        var idsAdded = 0;
        
        headings.forEach(function(heading) {
            counter++;
            
            // Si ya tiene ID, saltar
            if (heading.id) {
                console.log('ULL TOC: Encabezado ya tiene ID:', heading.id);
                return;
            }
            
            // Generar ID basado en el texto del encabezado
            var text = heading.textContent || heading.innerText;
            text = text.trim();
            
            if (!text) {
                return;
            }
            
            var newId = generateHeadingId(text, counter);
            heading.id = newId;
            idsAdded++;
            
            console.log('ULL TOC: ✓ ID añadido "' + newId + '" a ' + heading.tagName + ': ' + text.substring(0, 40));
        });
        
        console.log('ULL TOC: Total de ' + idsAdded + ' IDs añadidos desde JavaScript');
        
        return idsAdded;
    }
    
    /**
     * Actualizar enlaces del TOC para que coincidan con los IDs de los encabezados
     */
    function updateTocLinks() {
        var tocLinks = document.querySelectorAll('.ull-toc-link');
        var headings = document.querySelectorAll('h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]');
        
        // Crear array de encabezados con su texto e ID
        var headingMap = [];
        headings.forEach(function(heading) {
            var text = (heading.textContent || heading.innerText).trim();
            headingMap.push({
                text: text,
                id: heading.id,
                element: heading
            });
        });
        
        console.log('ULL TOC: Encabezados disponibles:', headingMap.length);
        
        // Actualizar cada enlace del TOC
        var linksUpdated = 0;
        tocLinks.forEach(function(link) {
            var linkText = (link.textContent || link.innerText).trim();
            
            // Buscar encabezado que coincida con el texto del enlace
            for (var i = 0; i < headingMap.length; i++) {
                var heading = headingMap[i];
                
                // Normalizar textos para comparación
                var normalizedLinkText = linkText.toLowerCase().replace(/[^a-z0-9]/g, '');
                var normalizedHeadingText = heading.text.toLowerCase().replace(/[^a-z0-9]/g, '');
                
                // Si coinciden o el texto del enlace está contenido en el encabezado
                if (normalizedHeadingText.indexOf(normalizedLinkText) === 0 || 
                    normalizedLinkText.indexOf(normalizedHeadingText) === 0) {
                    
                    // Actualizar href del enlace
                    link.setAttribute('href', '#' + heading.id);
                    linksUpdated++;
                    
                    console.log('ULL TOC: Enlace actualizado "' + linkText + '" → #' + heading.id);
                    break;
                }
            }
        });
        
        console.log('ULL TOC: Total de ' + linksUpdated + ' enlaces actualizados');
    }
    
    /**
     * Función para hacer scroll suave a un elemento
     */
    function smoothScrollTo(element, offset) {
        if (!element) return;
        
        offset = offset || 100;
        var elementPosition = element.getBoundingClientRect().top;
        var offsetPosition = elementPosition + window.pageYOffset - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
    
    /**
     * Inicializar navegación de TOC
     */
    function initTOCNavigation() {
        // Buscar todos los enlaces del TOC
        var tocLinks = document.querySelectorAll('.ull-toc-link');
        
        if (tocLinks.length === 0) {
            console.log('ULL TOC: No se encontraron enlaces en el TOC');
            return;
        }
        
        console.log('ULL TOC: Encontrados ' + tocLinks.length + ' enlaces en el TOC');
        
        // PRIMERO: Añadir IDs a encabezados que no los tengan
        var idsAdded = addHeadingIds();
        
        // SEGUNDO: Si añadimos IDs, actualizar los enlaces del TOC
        if (idsAdded > 0) {
            updateTocLinks();
        }
        
        // Añadir event listener a cada enlace
        tocLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                var href = this.getAttribute('href');
                console.log('ULL TOC: Click en enlace:', href);
                
                if (!href || href.charAt(0) !== '#') {
                    console.warn('ULL TOC: Enlace no válido:', href);
                    return;
                }
                
                var targetId = href.substring(1);
                var targetElement = document.getElementById(targetId);
                
                console.log('ULL TOC: Buscando elemento con ID:', targetId);
                console.log('ULL TOC: Elemento encontrado:', targetElement);
                
                if (targetElement) {
                    // Hacer scroll suave
                    smoothScrollTo(targetElement, 100);
                    
                    // Actualizar URL
                    if (window.history && window.history.pushState) {
                        window.history.pushState(null, null, href);
                    }
                    
                    // Resaltar temporalmente
                    targetElement.classList.add('ull-toc-highlight');
                    setTimeout(function() {
                        targetElement.classList.remove('ull-toc-highlight');
                    }, 2000);
                    
                    console.log('ULL TOC: ✓ Navegado a:', targetId);
                } else {
                    console.error('ULL TOC: ✗ No se encontró elemento con ID:', targetId);
                    
                    // Mostrar todos los IDs disponibles
                    var allHeadings = document.querySelectorAll('h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]');
                    var ids = [];
                    allHeadings.forEach(function(h) {
                        ids.push(h.getAttribute('id'));
                    });
                    console.log('ULL TOC: IDs disponibles:', ids);
                }
            });
        });
        
        console.log('ULL TOC: Navegación inicializada correctamente');
    }
    
    /**
     * Función global para alternar TOC
     */
    window.ullToggleTOC = function(button) {
        var container = button.closest('.ull-tabla-contenidos');
        if (!container) return;
        
        var lista = container.querySelector('.ull-toc-lista');
        if (!lista) return;
        
        lista.classList.toggle('ull-toc-collapsed');
        
        var isCollapsed = lista.classList.contains('ull-toc-collapsed');
        button.setAttribute('aria-expanded', !isCollapsed);
    };
    
    /**
     * Inicializar cuando el DOM esté listo
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTOCNavigation);
    } else {
        initTOCNavigation();
    }
    
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(initTOCNavigation);
    }
    
})();
