/* ULL Códigos - Frontend Scripts */

jQuery(document).ready(function($) {
    
    // Acordeón - toggle
    $('.ull-codigo-norma-header').on('click', function() {
        $(this).parent('.ull-codigo-norma-item').toggleClass('active');
    });
    
    // Scroll suave al índice
    $('.ull-codigo-indice a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
            
            // Abrir si es acordeón
            target.addClass('active');
        }
    });
    
});
