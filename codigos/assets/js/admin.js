/* ULL Códigos - Admin Scripts */

jQuery(document).ready(function($) {
    
    // Búsqueda de normas
    var searchTimeout;
    var $search = $('#ull-codigos-search');
    var $results = $('#ull-codigos-search-results');
    var $list = $('#ull-codigos-normas-list');
    
    $search.on('input', function() {
        var query = $(this).val();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $results.removeClass('active').empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: ullCodigosAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ull_codigos_search_normas',
                    nonce: ullCodigosAdmin.nonce,
                    search: query
                },
                beforeSend: function() {
                    $results.html('<div class="ull-codigos-search-item">' + ullCodigosAdmin.i18n.searching + '</div>').addClass('active');
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        $.each(response.data, function(i, item) {
                            // Verificar si ya está añadida
                            if ($list.find('[data-id="' + item.id + '"]').length === 0) {
                                html += '<div class="ull-codigos-search-item" data-id="' + item.id + '" data-title="' + item.title + '" data-numero="' + (item.numero || '') + '">';
                                html += '<span class="ull-codigos-search-item-title">' + item.title + '</span>';
                                if (item.numero) {
                                    html += '<span class="ull-codigos-search-item-numero">' + item.numero + '</span>';
                                }
                                html += '</div>';
                            }
                        });
                        
                        if (html) {
                            $results.html(html).addClass('active');
                        } else {
                            $results.html('<div class="ull-codigos-search-item">' + ullCodigosAdmin.i18n.noResults + '</div>');
                        }
                    } else {
                        $results.html('<div class="ull-codigos-search-item">' + ullCodigosAdmin.i18n.noResults + '</div>');
                    }
                }
            });
        }, 300);
    });
    
    // Añadir norma al hacer clic
    $results.on('click', '.ull-codigos-search-item[data-id]', function() {
        var $item = $(this);
        var id = $item.data('id');
        var title = $item.data('title');
        var numero = $item.data('numero');
        
        var index = $list.find('.ull-codigos-norma-item').length;
        
        var html = '<div class="ull-codigos-norma-item" data-id="' + id + '">';
        html += '<div class="ull-codigos-norma-handle">☰</div>';
        html += '<div class="ull-codigos-norma-info">';
        html += '<strong>' + title + '</strong>';
        if (numero) {
            html += '<span class="ull-codigos-norma-numero">' + numero + '</span>';
        }
        html += '</div>';
        html += '<div class="ull-codigos-norma-fields">';
        html += '<input type="text" name="codigo_normas[' + index + '][seccion]" placeholder="Sección (opcional)">';
        html += '<textarea name="codigo_normas[' + index + '][nota]" placeholder="Nota (opcional)"></textarea>';
        html += '</div>';
        html += '<input type="hidden" name="codigo_normas[' + index + '][id]" value="' + id + '">';
        html += '<button type="button" class="ull-codigos-remove-norma" title="' + ullCodigosAdmin.i18n.remove + '">×</button>';
        html += '</div>';
        
        $list.append(html);
        $search.val('');
        $results.removeClass('active').empty();
        
        updateIndexes();
    });
    
    // Cerrar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.ull-codigos-search-box').length) {
            $results.removeClass('active');
        }
    });
    
    // Eliminar norma
    $list.on('click', '.ull-codigos-remove-norma', function() {
        $(this).closest('.ull-codigos-norma-item').fadeOut(200, function() {
            $(this).remove();
            updateIndexes();
        });
    });
    
    // Ordenar con drag & drop
    $list.sortable({
        handle: '.ull-codigos-norma-handle',
        placeholder: 'ull-codigos-sortable-placeholder',
        update: function() {
            updateIndexes();
        }
    });
    
    // Actualizar índices de los campos
    function updateIndexes() {
        $list.find('.ull-codigos-norma-item').each(function(index) {
            $(this).find('input, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', name);
                }
            });
        });
    }
    
    // Sincronizar color picker con texto
    $('input[type="color"]').on('input', function() {
        $(this).next('input[type="text"]').val($(this).val());
    });
});
