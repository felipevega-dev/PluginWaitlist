<?php
/**
 * Clase para manejar la parte administrativa del plugin
 */
class Waitlist_Admin {
    
    /**
     * Inicializa la parte administrativa
     */
    public function init() {
        // Registrar acciones y filtros para la admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Agregar páginas de admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Acción para exportar CSV
        add_action('admin_post_waitlist_export_csv', 'waitlist_export_csv');
        
        // Registro de opciones para la configuración
        $this->register_settings();
        
        // Inicializar valores predeterminados temprano
        add_action('admin_init', array($this, 'initialize_default_values'));
        
        // Agregar acción para procesar el formulario directamente
        add_action('admin_post_save_waitlist_settings', array($this, 'handle_form_submission'));
    }
    
    /**
     * Registra las opciones del plugin
     */
    public function register_settings() {
        // Opciones de visualización
        register_setting('waitlist_settings', 'waitlist_show_variation_count', 'sanitize_text_field');
        register_setting('waitlist_settings', 'waitlist_show_subscriber_emails', 'sanitize_text_field');
        register_setting('waitlist_settings', 'waitlist_max_emails_display', 'absint');
        
        // Opciones de exportación
        register_setting('waitlist_settings', 'waitlist_excel_header_color', 'sanitize_text_field');
        register_setting('waitlist_settings', 'waitlist_excel_alternate_color', 'sanitize_text_field');
        register_setting('waitlist_settings', 'waitlist_include_timestamp', 'sanitize_text_field');
        
        // Opciones de email
        register_setting('waitlist_settings', 'waitlist_email_subject', 'wp_kses_post');
        register_setting('waitlist_settings', 'waitlist_email_message', 'wp_kses_post');
        register_setting('waitlist_settings', 'waitlist_email_logo', 'esc_url_raw');
        register_setting('waitlist_settings', 'waitlist_email_color_header', 'sanitize_hex_color');
        register_setting('waitlist_settings', 'waitlist_email_color_button', 'sanitize_hex_color');
        register_setting('waitlist_settings', 'waitlist_email_from_name', 'sanitize_text_field');
        register_setting('waitlist_settings', 'waitlist_email_from_address', 'sanitize_email');
    }
    
    /**
     * Registra scripts y estilos para el admin
     */
    public function enqueue_admin_scripts($hook) {
        // Solo cargar en páginas específicas del plugin
        if (strpos($hook, 'waitlist') === false) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();
        
        wp_enqueue_style(
            'waitlist-admin-style',
            WAITLIST_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WAITLIST_VERSION
        );
        
        wp_enqueue_script(
            'waitlist-admin-script',
            WAITLIST_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'wp-color-picker'),
            WAITLIST_VERSION,
            true
        );
        
        wp_localize_script('waitlist-admin-script', 'waitlist_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waitlist_admin_nonce')
        ));
    }
    
    /**
     * Agrega el menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            'Lista de Espera',
            'Lista de Espera',
            'manage_options',
            'waitlist',
            array($this, 'render_products_page'),
            'dashicons-email-alt',
            56
        );
        
        add_submenu_page(
            'waitlist',
            'Productos',
            'Productos',
            'manage_options',
            'waitlist',
            array($this, 'render_products_page')
        );
        
        add_submenu_page(
            'waitlist',
            'Todos los suscriptores',
            'Suscriptores',
            'manage_options',
            'waitlist-subscribers',
            array($this, 'render_subscribers_page')
        );
        
        add_submenu_page(
            'waitlist',
            'Configuración',
            'Configuración',
            'manage_options',
            'waitlist-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'waitlist',
            'Acerca de',
            'Acerca de',
            'manage_options',
            'waitlist-about',
            array($this, 'render_about_page')
        );
    }
    
    /**
     * Renderiza la página principal de productos
     */
    public function render_products_page() {
        // Verificar si se está visualizando un producto específico
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
        
        if ($product_id) {
            $this->render_product_subscribers($product_id);
            return;
        }
        
        // Incluir la plantilla
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/products-page.php';
    }
    
    /**
     * Renderiza la página de suscriptores de un producto específico
     */
    private function render_product_subscribers($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            echo '<div class="notice notice-error"><p>Producto no encontrado.</p></div>';
            return;
        }
        
        $subscribers = Waitlist_Model::get_subscribers($product_id);
        
        // Incluir la plantilla
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/product-subscribers.php';
    }
    
    /**
     * Renderiza la página de todos los suscriptores con información mejorada
     */
    public function render_subscribers_page() {
        // Obtener los filtros
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        // Obtener los parámetros de ordenación
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'products';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
        
        // Configuración de paginación
        $per_page = 30; // Mostrar 30 suscriptores por página
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Obtener los suscriptores según los filtros, ordenación y la paginación
        $subscribers = Waitlist_Model::get_subscribers(0, $search, $per_page, $current_page, $orderby, $order);
        
        // Obtener el total de elementos para la paginación
        $total_items = Waitlist_Model::$total_items;
        $total_pages = ceil($total_items / $per_page);
        
        // Asegurar que haya al menos una página
        if ($total_pages < 1) {
            $total_pages = 1;
        }
        
        // Incluir la plantilla
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/subscribers-page.php';
    }
    
    /**
     * Maneja el envío del formulario a través de admin-post.php
     */
    public function handle_form_submission() {
        // Habilitar depuración si es necesario
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Waitlist - Procesando envío del formulario de configuración');
        }
        
        // Verificar nonce
        if (!isset($_POST['waitlist_settings_nonce']) || !wp_verify_nonce($_POST['waitlist_settings_nonce'], 'waitlist_settings')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Waitlist - Error de verificación del nonce');
            }
            wp_redirect(add_query_arg(
                array(
                    'page' => 'waitlist-settings',
                    'error' => 'nonce_verification_failed'
                ),
                admin_url('admin.php')
            ));
            exit;
        }
        
        // Guardar las opciones
        update_option('waitlist_show_variation_count', isset($_POST['show_variation_count']) ? '1' : '0');
        update_option('waitlist_show_subscriber_emails', isset($_POST['show_subscriber_emails']) ? '1' : '0');
        update_option('waitlist_max_emails_display', absint($_POST['max_emails_display']));
        update_option('waitlist_excel_header_color', sanitize_text_field($_POST['excel_header_color']));
        update_option('waitlist_excel_alternate_color', sanitize_text_field($_POST['excel_alternate_color']));
        update_option('waitlist_include_timestamp', isset($_POST['include_timestamp']) ? '1' : '0');
        update_option('waitlist_email_subject', wp_kses_post($_POST['email_subject']));
        
        // Guardar mensaje de email si está presente
        if (isset($_POST['email_message'])) {
            update_option('waitlist_email_message', wp_kses_post($_POST['email_message']));
        }
        
        update_option('waitlist_email_logo', esc_url_raw($_POST['email_logo']));
        update_option('waitlist_email_color_header', sanitize_hex_color($_POST['email_color_header']));
        update_option('waitlist_email_color_button', sanitize_hex_color($_POST['email_color_button']));
        update_option('waitlist_email_from_name', sanitize_text_field($_POST['email_from_name']));
        update_option('waitlist_email_from_address', sanitize_email($_POST['email_from_address']));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Waitlist - Configuración guardada correctamente');
        }
        
        // Redirigir de vuelta a la página de configuración con mensaje de éxito
        wp_redirect(add_query_arg(
            array(
                'page' => 'waitlist-settings',
                'message' => 'saved',
            ),
            admin_url('admin.php')
        ));
        exit;
    }
    
    /**
     * Procesa el formulario de configuración (método antiguo, mantenido por compatibilidad)
     */
    public function process_settings_form() {
        // Verificar si se está procesando nuestro formulario
        if (!isset($_POST['waitlist_settings_nonce'])) {
            return;
        }

        // Verificar el nonce de seguridad
        if (!wp_verify_nonce($_POST['waitlist_settings_nonce'], 'waitlist_settings')) {
            add_settings_error(
                'waitlist_messages',
                'waitlist_error',
                'Error de seguridad. Por favor, intenta nuevamente.',
                'error'
            );
            return;
        }

        // Guardar las opciones de visualización
        update_option('waitlist_show_variation_count', isset($_POST['show_variation_count']) ? '1' : '0');
        update_option('waitlist_show_subscriber_emails', isset($_POST['show_subscriber_emails']) ? '1' : '0');
        update_option('waitlist_max_emails_display', absint($_POST['max_emails_display']));

        // Guardar las opciones de exportación
        update_option('waitlist_excel_header_color', sanitize_text_field($_POST['excel_header_color']));
        update_option('waitlist_excel_alternate_color', sanitize_text_field($_POST['excel_alternate_color']));
        update_option('waitlist_include_timestamp', isset($_POST['include_timestamp']) ? '1' : '0');

        // Guardar las opciones de email
        update_option('waitlist_email_subject', wp_kses_post($_POST['email_subject']));
        update_option('waitlist_email_message', wp_kses_post($_POST['email_message']));
        update_option('waitlist_email_logo', esc_url_raw($_POST['email_logo']));
        update_option('waitlist_email_color_header', sanitize_hex_color($_POST['email_color_header']));
        update_option('waitlist_email_color_button', sanitize_hex_color($_POST['email_color_button']));
        update_option('waitlist_email_from_name', sanitize_text_field($_POST['email_from_name']));
        update_option('waitlist_email_from_address', sanitize_email($_POST['email_from_address']));

        // Mostrar mensaje de éxito
        add_settings_error(
            'waitlist_messages',
            'waitlist_message',
            'La configuración se ha guardado correctamente.',
            'updated'
        );
    }
    
    /**
     * Inicializa los valores predeterminados si no existen
     */
    public function initialize_default_values() {
        // Plantilla predeterminada para correos electrónicos
        $default_email_template = '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <!-- Sección de Saludo -->
    <div style="padding: 20px; background-color: #f9f9f9; border-radius: 8px; margin-bottom: 20px;">
        <h2 style="color: #333; margin: 0;">¡Buenas noticias! 🎉</h2>
    </div>

    <!-- Contenido Principal -->
    <div style="background-color: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 20px;">
        <p style="font-size: 16px; line-height: 1.6; color: #444; margin-top: 0;">
            Nos complace informarte que el producto que estabas esperando ya está disponible:
        </p>
        
        <!-- Destacado del Producto -->
        <div style="background-color: #f5f8ff; border-left: 4px solid #0066CC; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
            <h3 style="color: #0066CC; margin: 0 0 10px 0; font-size: 20px;">{product_name}</h3>
            <p style="margin: 0; color: #666;">¡No esperes más para conseguirlo!</p>
        </div>

        <!-- Botón de Acción -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{product_url}" style="display: inline-block; background-color: #0066CC; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px;">Ver Producto</a>
        </div>

        <p style="font-size: 14px; color: #666; margin-bottom: 0;">
            Si tienes alguna pregunta, no dudes en contactarnos. Estamos aquí para ayudarte.
        </p>
    </div>

    <!-- Firma -->
    <div style="text-align: center; padding: 20px;">
        <p style="margin: 0; color: #888; font-size: 14px;">
            Saludos cordiales,<br>
            <strong style="color: #333;">{store_name}</strong>
        </p>
    </div>

    <!-- Pie de Página -->
    <div style="border-top: 2px solid #f0f0f0; padding-top: 20px; text-align: center; font-size: 12px; color: #999;">
        <p style="margin: 0;">
            Este correo fue enviado a {customer_email}<br>
            Fecha: {date}
        </p>
        <p style="margin: 5px 0 0 0;">
            <a href="{store_url}" style="color: #0066CC; text-decoration: none;">Visitar nuestra tienda</a>
        </p>
    </div>
</div>';

        // Inicializar opciones solo si no existen
        if (get_option('waitlist_email_message') === false) {
            update_option('waitlist_email_message', $default_email_template);
        }
        
        if (get_option('waitlist_email_subject') === false) {
            update_option('waitlist_email_subject', '¡{product_name} ya está disponible!');
        }
        
        if (get_option('waitlist_email_color_header') === false) {
            update_option('waitlist_email_color_header', '#0066CC');
        }
        
        if (get_option('waitlist_email_color_button') === false) {
            update_option('waitlist_email_color_button', '#4CAF50');
        }
        
        if (get_option('waitlist_email_from_name') === false) {
            update_option('waitlist_email_from_name', get_bloginfo('name'));
        }
        
        if (get_option('waitlist_email_from_address') === false) {
            update_option('waitlist_email_from_address', get_bloginfo('admin_email'));
        }
        
        if (get_option('waitlist_show_variation_count') === false) {
            update_option('waitlist_show_variation_count', '1');
        }
        
        if (get_option('waitlist_show_subscriber_emails') === false) {
            update_option('waitlist_show_subscriber_emails', '1');
        }
        
        if (get_option('waitlist_max_emails_display') === false) {
            update_option('waitlist_max_emails_display', '5');
        }
        
        if (get_option('waitlist_excel_header_color') === false) {
            update_option('waitlist_excel_header_color', '#0066CC');
        }
        
        if (get_option('waitlist_excel_alternate_color') === false) {
            update_option('waitlist_excel_alternate_color', '#F2F2F2');
        }
        
        if (get_option('waitlist_include_timestamp') === false) {
            update_option('waitlist_include_timestamp', '1');
        }
    }
    
    /**
     * Renderiza la página de configuración
     */
    public function render_settings_page() {
        // Asegurarse de que existan valores predeterminados
        $this->initialize_default_values();
        
        // Verificar si se está mostrando después de una redirección
        if (isset($_GET['message']) && $_GET['message'] === 'saved') {
            add_settings_error(
                'waitlist_messages',
                'waitlist_message',
                'La configuración se ha guardado correctamente.',
                'updated'
            );
        }
        
        // Verificar si hay errores
        if (isset($_GET['error'])) {
            $error_message = 'Ha ocurrido un error al guardar la configuración.';
            
            if ($_GET['error'] === 'nonce_verification_failed') {
                $error_message = 'Error de seguridad. Por favor, intenta nuevamente.';
            }
            
            add_settings_error(
                'waitlist_messages',
                'waitlist_error',
                $error_message,
                'error'
            );
        }
        
        // Procesar el formulario si se envió (método antiguo)
        $this->process_settings_form();
        
        // Mostrar mensajes de error/éxito
        settings_errors('waitlist_messages');
        
        // Incluir la vista
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }
    
    /**
     * Renderiza la página "Acerca de" con información del plugin
     */
    public function render_about_page() {
        // Incluir la plantilla
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/about-page.php';
    }
}