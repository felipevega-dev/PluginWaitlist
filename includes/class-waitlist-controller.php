<?php
/**
 * Controlador principal del plugin
 */
class Waitlist_Controller {
    
    /**
     * Inicializa el controlador
     */
    public function init() {
        // Registrar acciones y filtros
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Registrar shortcode
        add_shortcode('notificar_stock', array($this, 'shortcode_callback'));
        
        // Registro de AJAX
        add_action('wp_ajax_waitlist_subscribe', array($this, 'ajax_subscribe'));
        add_action('wp_ajax_nopriv_waitlist_subscribe', array($this, 'ajax_subscribe'));
        
        // AJAX para eliminar suscriptores
        add_action('wp_ajax_waitlist_delete_subscriber', array($this, 'ajax_delete_subscriber'));
        
        // AJAX para eliminar todas las suscripciones de un email
        add_action('wp_ajax_waitlist_delete_email_subscriptions', array($this, 'ajax_delete_email_subscriptions'));
        
        // Añadir el botón automáticamente a productos sin stock
        add_action('woocommerce_single_product_summary', array($this, 'add_waitlist_button_to_product'), 31);
        
        // Para productos variables, añadir el contenedor para el botón
        add_action('woocommerce_after_single_variation', array($this, 'add_waitlist_button_to_variation'));
    }
    
    /**
     * Registra scripts y estilos
     */
    public function enqueue_scripts() {
        // Versión para evitar caché
        $version = time();
        
        // Registrar y encolar el CSS
        wp_enqueue_style(
            'waitlist-fixed-style',
            WAITLIST_PLUGIN_URL . 'assets/css/waitlist-fixed.css',
            array(),
            $version
        );
        
        // Registrar y encolar el JS para la UI
        wp_enqueue_script(
            'waitlist-fixed-script',
            WAITLIST_PLUGIN_URL . 'assets/js/waitlist-fixed.js',
            array('jquery'),
            $version,
            true
        );
        
        // Registrar y encolar el JS específico para la suscripción
        wp_enqueue_script(
            'waitlist-subscribe-script',
            WAITLIST_PLUGIN_URL . 'assets/js/waitlist-subscribe.js',
            array('jquery'),
            $version,
            true
        );
        
        // Localizar el script con información necesaria para AJAX
        wp_localize_script('waitlist-subscribe-script', 'waitlist_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('waitlist_subscribe_nonce')
        ));
    }
    
    /**
     * Añade el botón de notificación a productos simples que no tienen stock
     */
    public function add_waitlist_button_to_product() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Solo mostrar en productos simples sin stock
        if ($product->is_type('simple') && !$product->is_in_stock()) {
            echo Waitlist_View::render_notify_button($product->get_id());
        }
        
        // Para productos variables, añadir el botón para variaciones
        if ($product->is_type('variable')) {
            echo Waitlist_View::render_variable_button();
        }
    }
    
    /**
     * Añade el botón de notificación a variaciones sin stock
     */
    public function add_waitlist_button_to_variation() {
        // El JS en frontend.js ya se encarga de mostrar/ocultar el botón
        // según el estado de stock de la variación seleccionada
    }
    
    /**
     * Callback para el shortcode
     */
    public function shortcode_callback($atts) {
        $atts = shortcode_atts(array(
            'id' => null,
        ), $atts, 'notificar_stock');
        
        if (!$atts['id']) {
            global $product;
            if ($product) {
                $atts['id'] = $product->get_id();
            }
        }
        
        if ($atts['id']) {
            return Waitlist_View::render_notify_button($atts['id']);
        }
        
        return '';
    }
    
    /**
     * Maneja la suscripción por AJAX
     */
    public function ajax_subscribe() {
        // Verificar datos requeridos
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $is_logged_in = isset($_POST['is_logged_in']) ? (bool)$_POST['is_logged_in'] : false;
        
        // Registro para depuración
        error_log('AJAX Subscribe Request: Product ID=' . $product_id . ', Email=' . $email . ', Logged In=' . ($is_logged_in ? 'Yes' : 'No'));
        
        if (!$product_id || !$email) {
            error_log('Waitlist AJAX: Datos incompletos o inválidos');
            wp_send_json_error('Datos incompletos o inválidos.');
            return;
        }
        
        // Validar producto
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('Waitlist AJAX: Producto no válido');
            wp_send_json_error('Producto no válido.');
            return;
        }
        
        // Validar email para usuarios logueados
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email;
            
            if ($email !== $user_email) {
                error_log('Waitlist AJAX: Intento de usar email diferente al del usuario logueado: ' . $email . ' vs ' . $user_email);
                wp_send_json_error('Como usuario registrado, solo puedes utilizar el correo electrónico asociado a tu cuenta: ' . $user_email);
                return;
            }
        }
        
        // Validar email
        if (!is_email($email)) {
            error_log('Waitlist AJAX: Email no válido');
            wp_send_json_error('Por favor, ingresa un correo electrónico válido.');
            return;
        }
        
        // Intentar añadir el suscriptor
        $result = Waitlist_Model::add_subscriber($product_id, $email);
        
        if (is_wp_error($result)) {
            error_log('Waitlist AJAX Error: ' . $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        } else {
            error_log('Waitlist AJAX: Suscripción exitosa para producto ' . $product_id . ' y email ' . $email);
            wp_send_json_success('¡Te notificaremos cuando este producto esté disponible!');
        }
        
        wp_die();
    }
    
    /**
     * Maneja la eliminación de suscriptores vía AJAX
     */
    public function ajax_delete_subscriber() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_subscriber')) {
            wp_send_json_error('Error de seguridad.');
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
            return;
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error('ID inválido.');
            return;
        }
        
        $result = Waitlist_Model::delete_subscriber($id);
        
        if ($result) {
            wp_send_json_success('Suscriptor eliminado correctamente.');
        } else {
            wp_send_json_error('Error al eliminar el suscriptor.');
        }
        
        wp_die();
    }
    
    /**
     * Maneja la eliminación de todas las suscripciones de un email vía AJAX
     */
    public function ajax_delete_email_subscriptions() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_subscriber')) {
            wp_send_json_error('Error de seguridad.');
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción.');
            return;
        }
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (!$email || !is_email($email)) {
            wp_send_json_error('Email inválido.');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'waitlist';
        
        // Eliminar todos los registros con ese email
        $result = $wpdb->delete(
            $table_name,
            array('user_email' => $email),
            array('%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Error al eliminar las suscripciones.');
        } else {
            wp_send_json_success(array(
                'message' => 'Se han eliminado todas las suscripciones para ' . $email,
                'count' => $result
            ));
        }
        
        wp_die();
    }
}