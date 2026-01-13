/**
 * ULL Normativa - Admin JavaScript
 */

(function($) {
    'use strict';

    const ULLAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initDatepickers();
            this.initNormaSelector();
            this.initDocumentUploader();
            this.initImportExport();
        },
        
        bindEvents: function() {
            // Sanitizar HTML
            $(document).on('click', '#ull-sanitize-html', this.handleSanitizeHtml.bind(this));
            
            // Vista previa contenido
            $(document).on('click', '#ull-preview-content', this.handlePreviewContent.bind(this));
            
            // Gestión de versiones
            $(document).on('click', '.ull-view-version', this.handleViewVersion.bind(this));
            $(document).on('click', '.ull-restore-version', this.handleRestoreVersion.bind(this));
            $(document).on('click', '.ull-delete-version', this.handleDeleteVersion.bind(this));
            
            // Gestión de relaciones
            $(document).on('click', '#ull-add-relation', this.handleAddRelation.bind(this));
            $(document).on('click', '.ull-delete-relation', this.handleDeleteRelation.bind(this));
            
            // Documentos
            $(document).on('click', '#ull-add-document', this.handleAddDocument.bind(this));
            $(document).on('click', '.ull-remove-doc', this.handleRemoveDocument.bind(this));
            
            // Descargar plantilla
            $(document).on('click', '#ull-download-template', this.handleDownloadTemplate.bind(this));
        },
        
        initDatepickers: function() {
            if ($.fn.datepicker) {
                $('.ull-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '1900:+10'
                });
            }
        },
        
        initNormaSelector: function() {
            const select = $('#norma_relacionada');
            if (!select.length) return;
            
            // Crear un selector con búsqueda AJAX
            select.on('focus', function() {
                if (select.data('loaded')) return;
                select.data('loaded', true);
                
                // Cargar opciones dinámicamente al escribir
                const wrapper = $('<div class="ull-norma-search-wrapper"></div>');
                const input = $('<input type="text" class="ull-norma-search" placeholder="Buscar norma...">');
                const results = $('<div class="ull-norma-results"></div>');
                
                select.hide().after(wrapper);
                wrapper.append(input).append(results).append(select);
                
                let timeout;
                input.on('input', function() {
                    clearTimeout(timeout);
                    const query = $(this).val();
                    
                    if (query.length < 2) {
                        results.hide();
                        return;
                    }
                    
                    timeout = setTimeout(function() {
                        ULLAdmin.searchNormas(query, results, select);
                    }, 300);
                });
            });
        },
        
        searchNormas: function(query, results, select) {
            const postId = $('#post_ID').val();
            
            $.post(ullNormativaAdmin.ajaxUrl, {
                action: 'ull_search_normas',
                nonce: ullNormativaAdmin.nonce,
                search: query,
                exclude: postId
            }, function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(norma) {
                        html += '<div class="ull-norma-result" data-id="' + norma.id + '">' +
                            '<strong>' + norma.numero + '</strong> ' + norma.title +
                        '</div>';
                    });
                    results.html(html).show();
                    
                    results.find('.ull-norma-result').on('click', function() {
                        const id = $(this).data('id');
                        const text = $(this).text();
                        
                        select.find('option:selected').removeAttr('selected');
                        
                        if (!select.find('option[value="' + id + '"]').length) {
                            select.append('<option value="' + id + '">' + text + '</option>');
                        }
                        
                        select.val(id);
                        results.hide();
                        results.prev('.ull-norma-search').val('');
                    });
                } else {
                    results.html('<div class="ull-no-results">No se encontraron normas</div>').show();
                }
            });
        },
        
        initDocumentUploader: function() {
            // Nada especial por ahora, se usa el media uploader de WP
        },
        
        initImportExport: function() {
            // Formulario de importación
            $('#ull-import-form').on('submit', this.handleImportSubmit.bind(this));
            
            // Formulario de exportación
            $('#ull-export-form').on('submit', this.handleExportSubmit.bind(this));
        },
        
        handleSanitizeHtml: function(e) {
            e.preventDefault();
            
            // Obtener contenido del editor
            let content;
            if (typeof tinymce !== 'undefined' && tinymce.get('contenido_html')) {
                content = tinymce.get('contenido_html').getContent();
            } else {
                content = $('#contenido_html').val();
            }
            
            // Sanitizar localmente (básico)
            const temp = $('<div>').html(content);
            
            // Eliminar scripts y estilos
            temp.find('script, style, noscript, iframe, object, embed').remove();
            
            // Eliminar event handlers
            temp.find('*').each(function() {
                const attrs = this.attributes;
                for (let i = attrs.length - 1; i >= 0; i--) {
                    if (attrs[i].name.startsWith('on')) {
                        this.removeAttribute(attrs[i].name);
                    }
                }
            });
            
            // Eliminar estilos inline
            temp.find('[style]').removeAttr('style');
            
            content = temp.html();
            
            // Actualizar editor
            if (typeof tinymce !== 'undefined' && tinymce.get('contenido_html')) {
                tinymce.get('contenido_html').setContent(content);
            } else {
                $('#contenido_html').val(content);
            }
            
            alert('HTML limpiado correctamente');
        },
        
        handlePreviewContent: function(e) {
            e.preventDefault();
            
            let content;
            if (typeof tinymce !== 'undefined' && tinymce.get('contenido_html')) {
                content = tinymce.get('contenido_html').getContent();
            } else {
                content = $('#contenido_html').val();
            }
            
            $('#ull-content-preview').show().find('.ull-preview-content').html(content);
        },
        
        handleViewVersion: function(e) {
            e.preventDefault();
            
            const versionId = $(e.currentTarget).data('version-id');
            
            $.post(ullNormativaAdmin.ajaxUrl, {
                action: 'ull_get_version',
                nonce: ullNormativaAdmin.nonce,
                version_id: versionId
            }, function(response) {
                if (response.success) {
                    ULLAdmin.showVersionModal(response.data);
                } else {
                    alert(response.data || 'Error al cargar la versión');
                }
            });
        },
        
        showVersionModal: function(data) {
            // Crear modal simple
            const modal = $('<div class="ull-admin-modal">' +
                '<div class="ull-admin-modal-content">' +
                    '<h2>Versión ' + data.version_number + '</h2>' +
                    '<p><strong>Fecha:</strong> ' + data.version_date + '</p>' +
                    (data.changes_summary ? '<p><strong>Cambios:</strong> ' + data.changes_summary + '</p>' : '') +
                    '<div class="ull-version-content-preview">' + data.content + '</div>' +
                    '<button type="button" class="button ull-close-modal">Cerrar</button>' +
                '</div>' +
            '</div>');
            
            $('body').append(modal);
            
            modal.on('click', '.ull-close-modal, .ull-admin-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('ull-close-modal')) {
                    modal.remove();
                }
            });
        },
        
        handleRestoreVersion: function(e) {
            e.preventDefault();
            
            if (!confirm(ullNormativaAdmin.i18n.confirmRestore || '¿Está seguro de restaurar esta versión?')) {
                return;
            }
            
            const versionId = $(e.currentTarget).data('version-id');
            
            $.post(ullNormativaAdmin.ajaxUrl, {
                action: 'ull_restore_version',
                nonce: ullNormativaAdmin.nonce,
                version_id: versionId
            }, function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data || 'Error al restaurar');
                }
            });
        },
        
        handleDeleteVersion: function(e) {
            e.preventDefault();
            
            if (!confirm(ullNormativaAdmin.i18n.confirmDelete || '¿Está seguro de eliminar esta versión?')) {
                return;
            }
            
            const link = $(e.currentTarget);
            const versionId = link.data('version-id');
            
            $.post(ullNormativaAdmin.ajaxUrl, {
                action: 'ull_delete_version',
                nonce: ullNormativaAdmin.nonce,
                version_id: versionId
            }, function(response) {
                if (response.success) {
                    link.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || 'Error al eliminar');
                }
            });
        },
        
        handleAddRelation: function(e) {
            e.preventDefault();
            
            const normaId = $('#post_ID').val();
            const relatedId = $('#norma_relacionada').val();
            const relationType = $('#tipo_relacion').val();
            const notes = $('#notas_relacion').val();
            
            if (!relatedId) {
                alert('Seleccione una norma');
                return;
            }
            
            $.post(ullNormativaAdmin.ajaxUrl, {
                action: 'ull_add_relation',
                nonce: ullNormativaAdmin.nonce,
                norma_id: normaId,
                related_norma_id: relatedId,
                relation_type: relationType,
                notes: notes
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Error al crear relación');
                }
            });
        },
        
        handleDeleteRelation: function(e) {
            e.preventDefault();
            
            if (!confirm('¿Eliminar esta relación?')) {
                return;
            }
            
            const link = $(e.currentTarget);
            const relationId = link.data('relation-id');
            
            $.post(ullNormativaAdmin.ajaxUrl, {
                action: 'ull_delete_relation',
                nonce: ullNormativaAdmin.nonce,
                relation_id: relationId
            }, function(response) {
                if (response.success) {
                    link.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || 'Error al eliminar');
                }
            });
        },
        
        handleAddDocument: function(e) {
            e.preventDefault();
            
            const frame = wp.media({
                title: 'Seleccionar Documento',
                button: { text: 'Añadir' },
                multiple: true,
                library: { type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'] }
            });
            
            frame.on('select', function() {
                const attachments = frame.state().get('selection').toJSON();
                
                attachments.forEach(function(att) {
                    if ($('#ull-documentos-list').find('[data-id="' + att.id + '"]').length) {
                        return; // Ya existe
                    }
                    
                    const item = $('<div class="ull-documento-item" data-id="' + att.id + '">' +
                        '<span class="dashicons dashicons-media-document"></span>' +
                        '<a href="' + att.url + '" target="_blank">' + att.title + '</a>' +
                        '<button type="button" class="ull-remove-doc button-link" data-id="' + att.id + '">' +
                            '<span class="dashicons dashicons-no-alt"></span>' +
                        '</button>' +
                        '<input type="hidden" name="documentos_adjuntos[]" value="' + att.id + '">' +
                    '</div>');
                    
                    $('#ull-documentos-list').append(item);
                });
            });
            
            frame.open();
        },
        
        handleRemoveDocument: function(e) {
            e.preventDefault();
            $(e.currentTarget).closest('.ull-documento-item').remove();
        },
        
        handleDownloadTemplate: function(e) {
            e.preventDefault();
            window.location.href = ullNormativaAdmin.ajaxUrl + '?action=ull_get_import_template';
        },
        
        handleImportSubmit: function(e) {
            e.preventDefault();
            
            const form = $(e.currentTarget);
            const formData = new FormData(form[0]);
            formData.append('action', 'ull_import_normativa');
            
            const progress = $('#ull-import-progress');
            const results = $('#ull-import-results');
            
            progress.show();
            results.hide();
            
            progress.find('.ull-progress-text').text(ullNormativaAdmin.i18n.importing || 'Importando...');
            
            $.ajax({
                url: ullNormativaAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    progress.hide();
                    
                    if (response.success) {
                        const data = response.data;
                        results.html(
                            '<div class="notice notice-success">' +
                                '<p><strong>Importación completada:</strong></p>' +
                                '<ul>' +
                                    '<li>Total registros: ' + data.total + '</li>' +
                                    '<li>Importados: ' + data.imported + '</li>' +
                                    '<li>Actualizados: ' + data.updated + '</li>' +
                                    '<li>Omitidos: ' + data.skipped + '</li>' +
                                    '<li>Errores: ' + data.errors + '</li>' +
                                '</ul>' +
                                (data.error_log.length ? '<details><summary>Ver errores</summary><pre>' + data.error_log.join('\n') + '</pre></details>' : '') +
                            '</div>'
                        ).show();
                    } else {
                        results.html('<div class="notice notice-error"><p>' + response.data + '</p></div>').show();
                    }
                },
                error: function() {
                    progress.hide();
                    results.html('<div class="notice notice-error"><p>Error de conexión</p></div>').show();
                }
            });
        },
        
        handleExportSubmit: function(e) {
            e.preventDefault();
            
            const form = $(e.currentTarget);
            const formData = new FormData(form[0]);
            formData.append('action', 'ull_export_normativa');
            
            const btn = form.find('button[type="submit"]');
            const originalText = btn.text();
            
            btn.prop('disabled', true).text(ullNormativaAdmin.i18n.exporting || 'Exportando...');
            
            $.ajax({
                url: ullNormativaAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        // Descargar archivo
                        window.location.href = response.data.url;
                        alert('Exportación completada: ' + response.data.count + ' normas');
                    } else {
                        alert(response.data || 'Error al exportar');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).text(originalText);
                    alert('Error de conexión');
                }
            });
        }
    };

    $(document).ready(function() {
        ULLAdmin.init();
    });

    // Estilos adicionales para modales admin
    $('head').append('<style>' +
        '.ull-admin-modal{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:100000;display:flex;align-items:center;justify-content:center}' +
        '.ull-admin-modal-content{background:#fff;max-width:800px;width:90%;max-height:80vh;overflow-y:auto;padding:20px;border-radius:4px}' +
        '.ull-version-content-preview{background:#f9f9f9;padding:15px;border:1px solid #ddd;margin:15px 0;max-height:400px;overflow-y:auto}' +
        '.ull-norma-search-wrapper{position:relative}' +
        '.ull-norma-search{width:100%;padding:8px;margin-bottom:5px}' +
        '.ull-norma-results{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ddd;max-height:200px;overflow-y:auto;z-index:100;display:none}' +
        '.ull-norma-result{padding:8px 10px;cursor:pointer;border-bottom:1px solid #eee}' +
        '.ull-norma-result:hover{background:#f0f0f0}' +
        '.ull-no-results{padding:10px;color:#666;font-style:italic}' +
    '</style>');

})(jQuery);
