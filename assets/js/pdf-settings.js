/**
 * Configuración PDF - Admin JavaScript
 */

(function($) {
    'use strict';
    
    var ullPdf = {
        
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Vincular eventos
         */
        bindEvents: function() {
            $('#ull-install-dompdf').on('click', this.installDompdf.bind(this));
            $('#ull-uninstall-dompdf').on('click', this.uninstallDompdf.bind(this));
        },
        
        /**
         * Instalar DOMPDF
         */
        installDompdf: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var $spinner = $('#ull-install-spinner');
            
            // Confirmar
            if (!confirm(ullPdfSettings.strings.installing + '\n\n' + 
                'Este proceso puede tardar 1-2 minutos. ¿Desea continuar?')) {
                return;
            }
            
            // Deshabilitar botón y mostrar spinner
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Crear mensaje de progreso
            var $progress = this.createProgressMessage(ullPdfSettings.strings.installing);
            $button.after($progress);
            
            // AJAX
            $.ajax({
                url: ullPdfSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'ull_install_dompdf',
                    nonce: ullPdfSettings.nonce
                },
                timeout: 300000, // 5 minutos de timeout
                success: function(response) {
                    $spinner.removeClass('is-active');
                    $progress.remove();
                    
                    if (response.success) {
                        var message = response.data.message || ullPdfSettings.strings.install_success;
                        
                        // Mostrar log si está disponible (para debug)
                        if (response.data.log && console && console.log) {
                            console.log('DOMPDF Installation Log:', response.data.log);
                        }
                        
                        ullPdf.showMessage('success', message);
                        // Recargar la página después de 2 segundos
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        var errorMsg = response.data.message || ullPdfSettings.strings.install_error;
                        
                        // Mostrar log detallado si está disponible
                        if (response.data.log && response.data.log.length > 0) {
                            errorMsg += '<br><br><strong>Log de instalación:</strong><br>';
                            errorMsg += '<div style="background:#f5f5f5; padding:10px; margin-top:10px; max-height:200px; overflow-y:auto; font-family:monospace; font-size:12px;">';
                            $.each(response.data.log, function(i, line) {
                                errorMsg += line + '<br>';
                            });
                            errorMsg += '</div>';
                        }
                        
                        ullPdf.showMessage('error', errorMsg);
                        $button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $spinner.removeClass('is-active');
                    $progress.remove();
                    $button.prop('disabled', false);
                    
                    var errorMsg = ullPdfSettings.strings.install_error;
                    if (status === 'timeout') {
                        errorMsg += ' (Timeout: La descarga tardó demasiado. Verifique su conexión.)';
                    } else if (error) {
                        errorMsg += ' (' + error + ')';
                    }
                    
                    // Intentar obtener mensaje del servidor
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    
                    ullPdf.showMessage('error', errorMsg);
                }
            });
        },
        
        /**
         * Desinstalar DOMPDF
         */
        uninstallDompdf: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            
            // Confirmar
            if (!confirm(ullPdfSettings.strings.confirm_uninstall)) {
                return;
            }
            
            // Deshabilitar botón
            $button.prop('disabled', true).text(ullPdfSettings.strings.uninstalling);
            
            // AJAX
            $.ajax({
                url: ullPdfSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'ull_uninstall_dompdf',
                    nonce: ullPdfSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ullPdf.showMessage('success', response.data.message || ullPdfSettings.strings.uninstall_success);
                        // Recargar la página después de 2 segundos
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        ullPdf.showMessage('error', response.data.message || ullPdfSettings.strings.uninstall_error);
                        $button.prop('disabled', false).text('Desinstalar DOMPDF');
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('Desinstalar DOMPDF');
                    ullPdf.showMessage('error', ullPdfSettings.strings.uninstall_error + ' (' + error + ')');
                }
            });
        },
        
        /**
         * Crear mensaje de progreso
         */
        createProgressMessage: function(text) {
            var $progress = $('<div>', {
                'class': 'ull-pdf-progress is-visible'
            });
            
            var $bar = $('<div>', {
                'class': 'ull-pdf-progress-bar'
            }).append($('<div>', {
                'class': 'ull-pdf-progress-fill',
                'css': { 'width': '0%' }
            }));
            
            var $text = $('<div>', {
                'class': 'ull-pdf-progress-text',
                'text': text
            });
            
            $progress.append($bar).append($text);
            
            // Animar la barra de progreso
            setTimeout(function() {
                $progress.find('.ull-pdf-progress-fill').css('width', '30%');
            }, 100);
            
            setTimeout(function() {
                $progress.find('.ull-pdf-progress-fill').css('width', '60%');
            }, 5000);
            
            setTimeout(function() {
                $progress.find('.ull-pdf-progress-fill').css('width', '80%');
            }, 15000);
            
            return $progress;
        },
        
        /**
         * Mostrar mensaje de estado
         */
        showMessage: function(type, message) {
            // Eliminar mensajes previos
            $('.ull-status-message').remove();
            
            var $message = $('<div>', {
                'class': 'ull-status-message ' + type + ' is-visible',
                'html': '<p>' + message + '</p>'
            });
            
            $('#ull-dompdf-status').prepend($message);
            
            // Auto-ocultar después de 10 segundos
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 10000);
        },
        
        /**
         * Verificar estado de DOMPDF
         */
        checkStatus: function() {
            $.ajax({
                url: ullPdfSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'ull_check_dompdf_status',
                    nonce: ullPdfSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('DOMPDF Status:', response.data);
                    }
                }
            });
        }
    };
    
    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        ullPdf.init();
    });
    
})(jQuery);
