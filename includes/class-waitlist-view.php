<?php
/**
 * Clase para manejar la parte visual del plugin
 */
class Waitlist_View {
    
    /**
     * Genera el HTML para el botón de notificación
     */
    public static function render_notify_button($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return '';
        }
        
        $user_email = '';
        $is_logged_in = is_user_logged_in();
        
        if ($is_logged_in) {
            $user_email = wp_get_current_user()->user_email;
        }
        
        ob_start();
        ?>
        <div class="waitlist-container">
            <button type="button" class="waitlist-button" data-product-id="<?php echo esc_attr($product_id); ?>">
                Notificarme cuando esté disponible
            </button>
            
            <!-- Overlay para el popup -->
            <div class="waitlist-popup-overlay" style="display: none;">
                <div class="waitlist-popup">
                    <div class="waitlist-popup-header">
                        <h3>Notificación de disponibilidad</h3>
                        <button type="button" class="waitlist-close-popup" aria-label="Cerrar">Cerrar</button>
                    </div>
                    <div class="waitlist-popup-body">
                        <p>Te enviaremos un correo cuando <strong><?php echo esc_html($product->get_name()); ?></strong> vuelva a estar disponible.</p>
                        
                        <div class="waitlist-form">
                            <div class="waitlist-form-group">
                                <label for="waitlist-email-<?php echo esc_attr($product_id); ?>">Correo electrónico:</label>
                                <input type="email" 
                                       id="waitlist-email-<?php echo esc_attr($product_id); ?>" 
                                       class="waitlist-email" 
                                       placeholder="tu@email.com" 
                                       value="<?php echo esc_attr($user_email); ?>"
                                       <?php echo $is_logged_in ? 'readonly="readonly"' : ''; ?>
                                       <?php echo $is_logged_in ? 'style="background-color: #f7f7f7;"' : ''; ?>>
                                <?php if ($is_logged_in): ?>
                                    <small class="waitlist-email-notice">Estás registrado como <?php echo esc_html($user_email); ?>. Las notificaciones se enviarán a este correo.</small>
                                <?php endif; ?>
                            </div>
                            <div class="waitlist-form-footer">
                                <button type="button" class="waitlist-subscribe" 
                                       data-product-id="<?php echo esc_attr($product_id); ?>"
                                       <?php echo $is_logged_in ? 'data-logged-in="1"' : ''; ?>>
                                    Suscribirme
                                </button>
                            </div>
                        </div>
                        
                        <div class="waitlist-message" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Genera el HTML para productos variables
     */
    public static function render_variable_button() {
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return '';
        }
        
        $user_email = '';
        $is_logged_in = is_user_logged_in();
        
        if ($is_logged_in) {
            $user_email = wp_get_current_user()->user_email;
        }
        
        ob_start();
        ?>
        <div class="waitlist-variable-container" style="display: none;">
            <button type="button" class="waitlist-button" data-product-id="">
                Notificarme cuando esté disponible
            </button>
            
            <!-- Overlay para el popup -->
            <div class="waitlist-popup-overlay" style="display: none;">
                <div class="waitlist-popup">
                    <div class="waitlist-popup-header">
                        <h3>Notificación de disponibilidad</h3>
                        <button type="button" class="waitlist-close-popup" aria-label="Cerrar">Cerrar</button>
                    </div>
                    <div class="waitlist-popup-body">
                        <p>Te enviaremos un correo cuando este producto vuelva a estar disponible.</p>
                        
                        <div class="waitlist-form">
                            <div class="waitlist-form-group">
                                <label for="waitlist-email-variable">Correo electrónico:</label>
                                <input type="email" 
                                       id="waitlist-email-variable" 
                                       class="waitlist-email" 
                                       placeholder="tu@email.com" 
                                       value="<?php echo esc_attr($user_email); ?>"
                                       <?php echo $is_logged_in ? 'readonly="readonly"' : ''; ?>
                                       <?php echo $is_logged_in ? 'style="background-color: #f7f7f7;"' : ''; ?>>
                                <?php if ($is_logged_in): ?>
                                    <small class="waitlist-email-notice">Estás registrado como <?php echo esc_html($user_email); ?>. Las notificaciones se enviarán a este correo.</small>
                                <?php endif; ?>
                            </div>
                            <div class="waitlist-form-footer">
                                <button type="button" class="waitlist-subscribe" 
                                       data-product-id=""
                                       <?php echo $is_logged_in ? 'data-logged-in="1"' : ''; ?>>
                                    Suscribirme
                                </button>
                            </div>
                        </div>
                        
                        <div class="waitlist-message" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
} 