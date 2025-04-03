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
        
        // Configuración de paginación
        $per_page = 20; // Mostrar 20 suscriptores por página por defecto
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Obtener los suscriptores según los filtros y la paginación
        $subscribers = Waitlist_Model::get_subscribers(0, $search, $per_page, $current_page);
        
        // Obtener el total de elementos para la paginación
        $total_items = Waitlist_Model::$total_items;
        $total_pages = ceil($total_items / $per_page);
        
        // Incluir la plantilla
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/subscribers-page.php';
    }
    
    /**
     * Renderiza la página de configuración
     */
    public function render_settings_page() {
        // Definir las opciones a guardar
        $options = array(
            'waitlist_email_subject' => 'email_subject',
            'waitlist_email_message' => 'email_message',
            'waitlist_email_from_name' => 'email_from_name',
            'waitlist_email_from_address' => 'email_from_address',
            'waitlist_show_variation_count' => 'show_variation_count',
            'waitlist_show_subscriber_emails' => 'show_subscriber_emails',
            'waitlist_max_emails_display' => 'max_emails_display',
            'waitlist_excel_header_color' => 'excel_header_color',
            'waitlist_excel_alternate_color' => 'excel_alternate_color',
            'waitlist_include_timestamp' => 'include_timestamp'
        );
        
        // Procesar migración desde YITH
        if (isset($_POST['migrate_from_yith']) && isset($_POST['waitlist_migration_nonce']) && 
            wp_verify_nonce($_POST['waitlist_migration_nonce'], 'waitlist_migration')) {
            $this->process_yith_migration();
        }
        
        // Procesar importación de suscriptores
        if (isset($_POST['import_subscribers']) && isset($_FILES['import_file'])) {
            $this->process_subscriber_import($_FILES['import_file']);
        }
        
        // Guardar cambios en la configuración
        if (isset($_POST['waitlist_settings_nonce']) && wp_verify_nonce($_POST['waitlist_settings_nonce'], 'waitlist_settings')) {
            // Guardar opciones de texto
            foreach ($options as $option_name => $post_key) {
                if (isset($_POST[$post_key])) {
                    if ($post_key === 'email_subject' || $post_key === 'email_message' || $post_key === 'email_from_name' || $post_key === 'email_from_address') {
                        update_option($option_name, sanitize_text_field($_POST[$post_key]));
                    } else if ($post_key === 'max_emails_display') {
                        update_option($option_name, intval($_POST[$post_key]));
                    } else if ($post_key === 'excel_header_color' || $post_key === 'excel_alternate_color') {
                        update_option($option_name, sanitize_hex_color($_POST[$post_key]));
                    } else {
                        // Opciones de checkbox
                        update_option($option_name, '1');
                    }
                } else if (strpos($post_key, 'show_') === 0 || $post_key === 'include_timestamp') {
                    // Si no está definido en el POST y es un checkbox, lo establecemos a '0'
                    update_option($option_name, '0');
                }
            }
            
            // Mostrar mensaje de éxito
            echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
        }
        
        // Incluir la plantilla
        include WAITLIST_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }
    
    /**
     * Procesa la migración de datos desde YITH WooCommerce Waitlist
     */
    private function process_yith_migration() {
        // Mostrar un mensaje de proceso iniciado
        echo '<div class="notice notice-info"><p>Iniciando migración de datos desde YITH WooCommerce Waitlist...</p></div>';
        
        // Realizar la migración
        $result = Waitlist_Model::migrate_from_yith();
        
        // Mostrar resultados
        if ($result['migrated'] > 0 || $result['already_exists'] > 0) {
            $message = sprintf(
                'Migración completada. %d suscriptores migrados exitosamente, %d ya existían, %d errores.',
                $result['migrated'],
                $result['already_exists'],
                $result['errors']
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        } else if ($result['total'] === 0) {
            echo '<div class="notice notice-warning is-dismissible"><p>No se encontraron datos para migrar desde YITH WooCommerce Waitlist.</p></div>';
        } else {
            $message = sprintf(
                'La migración ha encontrado problemas. 0 suscriptores migrados, %d errores.',
                $result['errors']
            );
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }
} 