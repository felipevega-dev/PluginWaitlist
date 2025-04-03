(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Waitlist Fixed JS loaded');
        
        // HACK: Ocultar cualquier popup que esté visible al cargar
        $('.waitlist-popup-overlay').hide();
        
        // Abrir el popup SOLO al hacer clic en el botón
        $(document).on('click', '.waitlist-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Button clicked');
            
            var $container = $(this).closest('.waitlist-container, .waitlist-variable-container');
            $container.find('.waitlist-popup-overlay').fadeIn(300);
            $container.find('.waitlist-email').focus();
            
            // Prevenir que el scroll de la página funcione mientras el popup está abierto
            $('body').css('overflow', 'hidden');
        });
        
        // Cerrar el popup con la X
        $(document).on('click', '.waitlist-close-popup', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Close button clicked - fixed version');
            
            // Cerrar TODOS los popups
            $('.waitlist-popup-overlay').fadeOut(300);
            $('body').css('overflow', '');
            
            return false;
        });
        
        // Cerrar el popup al hacer clic en el overlay
        $(document).on('click', '.waitlist-popup-overlay', function(e) {
            if (e.target === this) {
                console.log('Overlay clicked');
                $(this).fadeOut(300);
                $('body').css('overflow', '');
            }
        });
        
        // Para productos variables - SOLO mostrar el BOTÓN
        $('form.variations_form').on('show_variation', function(event, variation) {
            console.log('Variation shown', variation);
            var $container = $('.waitlist-variable-container');
            
            if (variation.is_in_stock === false || variation.availability_html.indexOf('Sin existencias') > -1) {
                // Solo mostrar el contenedor, NO el popup
                $container.show();
                // IMPORTANTE: Asegurarse que el popup está OCULTO
                $container.find('.waitlist-popup-overlay').hide();
                // Actualizar el ID del producto en los botones
                $container.find('.waitlist-button, .waitlist-subscribe').data('product-id', variation.variation_id);
            } else {
                $container.hide();
            }
        }).on('hide_variation', function() {
            $('.waitlist-variable-container').hide();
        });
    });
    
})(jQuery); 