(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Abrir el popup
        $(document).on('click', '.waitlist-button', function(e) {
            e.preventDefault();
            
            var $container = $(this).closest('.waitlist-container, .waitlist-variable-container');
            $container.find('.waitlist-popup-overlay').fadeIn(300);
            $container.find('.waitlist-email').focus();
            
            // Prevenir que el scroll de la página funcione mientras el popup está abierto
            $('body').css('overflow', 'hidden');
        });
        
        // Cerrar el popup
        $(document).on('click', '.waitlist-close-popup', function() {
            $('.waitlist-popup-overlay').fadeOut(300);
            $('body').css('overflow', '');
        });
        
        // Cerrar el popup al hacer clic fuera de él
        $(document).on('click', '.waitlist-popup-overlay', function(e) {
            if ($(e.target).hasClass('waitlist-popup-overlay')) {
                $('.waitlist-popup-overlay').fadeOut(300);
                $('body').css('overflow', '');
            }
        });
        
        // Para productos variables
        $('form.variations_form').on('show_variation', function(event, variation) {
            var $container = $('.waitlist-variable-container');
            
            if (variation.is_in_stock === false || variation.availability_html.indexOf('Sin existencias') > -1) {
                $container.show();
                $container.find('.waitlist-button, .waitlist-subscribe').data('product-id', variation.variation_id);
            } else {
                $container.hide();
            }
        }).on('hide_variation', function() {
            $('.waitlist-variable-container').hide();
        });
        
        // Suscribirse a la lista de espera
        $(document).on('click', '.waitlist-subscribe', function() {
            var $this = $(this);
            var $popup = $this.closest('.waitlist-popup');
            var $message = $popup.find('.waitlist-message');
            var productId = $this.data('product-id');
            var email = $popup.find('.waitlist-email').val();
            
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
                error: function() {
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