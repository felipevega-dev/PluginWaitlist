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
    }
    
    /**
     * Registra scripts y estilos para el admin
     */
    public function enqueue_admin_scripts($hook) {
        // Solo cargar en páginas específicas del plugin
        if (strpos($hook, 'waitlist') === false) {
            return;
        }
        
        wp_enqueue_style(
            'waitlist-admin-style',
            WAITLIST_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WAITLIST_VERSION
        );
        
        wp_enqueue_script(
            'waitlist-admin-script',
            WAITLIST_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WAITLIST_VERSION,
            true
        );
        
        wp_localize_script('waitlist-admin-script', 'waitlist_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
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
        
        // Debug de paginación
        error_log("Waitlist Paginación: Items: {$total_items}, Por página: {$per_page}, Total páginas: {$total_pages}, Página actual: {$current_page}");
        
        // Incluir la plantilla
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/subscribers-page.php';
    }
    
    /**
     * Procesa el formulario de configuración
     */
    public function process_settings_form() {
        // Verificar si se envió el formulario
        if (!isset($_POST['submit']) || !isset($_POST['waitlist_settings_nonce'])) {
            return;
        }

        // Verificar el nonce
        if (!wp_verify_nonce($_POST['waitlist_settings_nonce'], 'waitlist_settings')) {
            wp_die('No tienes permiso para realizar esta acción.');
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
     * Renderiza la página de configuración
     */
    public function render_settings_page() {
        // Procesar el formulario si se envió
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