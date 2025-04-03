(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Waitlist JS loaded - Fixed Version');
        
        // Limpiar cualquier evento previo para evitar duplicados
        $(document).off('click', '.waitlist-button');
        $(document).off('click', '.waitlist-close-popup');
        $(document).off('click', '.waitlist-popup-overlay');
        
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
        
        // Cerrar el popup con la X - Versión mejorada
        $(document).on('click', '.waitlist-close-popup', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Close button clicked');
            
            // Cerrar todos los popups
            $('.waitlist-popup-overlay').fadeOut(300);
            $('body').css('overflow', '');
            
            return false; // Detener propagación adicional
        });
        
        // Cerrar el popup al hacer clic en el overlay
        $(document).on('click', '.waitlist-popup-overlay', function(e) {
            if (e.target === this) {
                console.log('Overlay clicked');
                $(this).fadeOut(300);
                $('body').css('overflow', '');
            }
        });
        
        // Para productos variables - SOLO mostrar el BOTÓN, asegurándose de que el popup está oculto
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
            // Ocultar todo cuando no hay variación seleccionada
            $('.waitlist-variable-container').hide();
            $('.waitlist-popup-overlay').hide();
        });
        
        // Suscribirse a la lista de espera
        $(document).on('click', '.waitlist-subscribe', function() {
            console.log('Subscribe button clicked');
            var $this = $(this);
            var $popup = $this.closest('.waitlist-popup');
            var $message = $popup.find('.waitlist-message');
            var productId = $this.data('product-id');
            var email = $popup.find('.waitlist-email').val();
            
            console.log('Product ID:', productId);
            console.log('Email:', email);
            
            // Validar el correo electrónico
            if (!isValidEmail(email)) {
                $message.removeClass('success').addClass('error')
                        .text('Por favor, ingresa un correo electrónico válido')
                        .fadeIn();
                return;
            }
            
            // Deshabilitar el botón mientras se procesa
            $this.prop('disabled', true).text('Procesando...');
            
            // Enviar solicitud AJAX
            $.ajax({
                url: waitlist_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'waitlist_subscribe',
                    product_id: productId,
                    email: email
                },
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        $message.removeClass('error').addClass('success')
                                .text(response.data)
                                .fadeIn();
                                
                        // Ocultar el formulario después de un tiempo
                        setTimeout(function() {
                            $('.waitlist-popup-overlay').fadeOut(300);
                            $('body').css('overflow', '');
                        }, 3000);
                    } else {
                        $message.removeClass('success').addClass('error')
                                .text(response.data)
                                .fadeIn();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                    $message.removeClass('success').addClass('error')
                            .text('Error al procesar la solicitud. Inténtalo de nuevo.')
                            .fadeIn();
                },
                complete: function() {
                    $this.prop('disabled', false).text('Suscribirme');
                }
            });
        });
        
        // Función para validar el email
        function isValidEmail(email) {
            var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return pattern.test(email);
        }
    });
    
})(jQuery);
