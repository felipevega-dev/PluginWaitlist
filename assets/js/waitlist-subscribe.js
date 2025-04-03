(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Waitlist Subscribe JS loaded');
        
        // Suscribirse a la lista de espera
        $(document).on('click', '.waitlist-subscribe', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Subscribe button clicked');
            var $this = $(this);
            var $popup = $this.closest('.waitlist-popup');
            var $message = $popup.find('.waitlist-message');
            var productId = $this.data('product-id');
            var isLoggedIn = $this.data('logged-in') === 1;
            var email = $popup.find('.waitlist-email').val();
            
            console.log('Product ID:', productId);
            console.log('Email:', email);
            console.log('Is Logged In:', isLoggedIn);
            
            // Validar el correo electrónico
            if (!isValidEmail(email)) {
                $message.removeClass('success').addClass('error')
                        .text('Por favor, ingresa un correo electrónico válido')
                        .fadeIn();
                return;
            }
            
            // Deshabilitar el botón mientras se procesa
            $this.prop('disabled', true).text('Procesando...');
            
            // Mostrar mensaje de procesamiento
            $message.removeClass('success error').addClass('info')
                    .text('Procesando tu solicitud...')
                    .fadeIn();
            
            // Enviar solicitud AJAX
            $.ajax({
                url: waitlist_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'waitlist_subscribe',
                    product_id: productId,
                    email: email,
                    is_logged_in: isLoggedIn ? 1 : 0,
                    security: waitlist_ajax.nonce
                },
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        $message.removeClass('error info').addClass('success')
                                .text(response.data)
                                .fadeIn();
                                
                        // Ocultar el formulario después de un tiempo
                        setTimeout(function() {
                            $('.waitlist-popup-overlay').fadeOut(300);
                            $('body').css('overflow', '');
                        }, 3000);
                    } else {
                        $message.removeClass('success info').addClass('error')
                                .text(response.data)
                                .fadeIn();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                    console.log('Response:', xhr.responseText);
                    $message.removeClass('success info').addClass('error')
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