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
        add_action('admin_init', array($this, 'register_settings'));
        
        // Inicializar valores predeterminados temprano
        add_action('admin_init', array($this, 'initialize_default_values'));
        
        // Agregar acción para procesar el formulario directamente
        add_action('admin_post_save_waitlist_settings', array($this, 'handle_form_submission'));
        
        // AJAX para email de prueba
        add_action('wp_ajax_waitlist_send_test_email', array($this, 'send_test_email'));
        
        // AJAX para importar CSV
        add_action('wp_ajax_waitlist_import_csv', array($this, 'import_csv'));
        
        // AJAX para migrar desde YITH
        add_action('wp_ajax_waitlist_migrate_from_yith', array($this, 'migrate_from_yith'));
    }
    
    /**
     * Registra las opciones del plugin
     */
    public function register_settings() {
        // Registrar el grupo de opciones
        register_setting(
            'waitlist_settings_group', // Option group
            'waitlist_settings_group', // Option name
            null // Sanitization callback
        );
        
        // Opciones de visualización
        register_setting('waitlist_settings_group', 'waitlist_max_emails_display', 'absint');
        register_setting('waitlist_settings_group', 'waitlist_show_subscriber_count', 'sanitize_text_field');
        
        // Opciones de Excel
        register_setting('waitlist_settings_group', 'waitlist_excel_logo', 'esc_url_raw');
        register_setting('waitlist_settings_group', 'waitlist_excel_color_header', 'sanitize_hex_color');
        register_setting('waitlist_settings_group', 'waitlist_excel_color_text', 'sanitize_hex_color');
        register_setting('waitlist_settings_group', 'waitlist_excel_filename', 'sanitize_text_field');
        
        // Opciones de email
        register_setting('waitlist_settings_group', 'waitlist_enable_email_notifications', 'sanitize_text_field');
        register_setting('waitlist_settings_group', 'waitlist_email_subject', 'wp_kses_post');
        register_setting('waitlist_settings_group', 'waitlist_email_message', 'wp_kses_post');
        register_setting('waitlist_settings_group', 'waitlist_email_logo', 'esc_url_raw');
        register_setting('waitlist_settings_group', 'waitlist_email_color_header', 'sanitize_hex_color');
        register_setting('waitlist_settings_group', 'waitlist_email_color_button', 'sanitize_hex_color');
        register_setting('waitlist_settings_group', 'waitlist_email_from_name', 'sanitize_text_field');
        register_setting('waitlist_settings_group', 'waitlist_email_from_address', 'sanitize_email');
        
        // Opción de ocultar migración
        register_setting('waitlist_settings_group', 'waitlist_hide_migration', 'sanitize_text_field');
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
        
        // Configuración General
        update_option('waitlist_max_emails_display', isset($_POST['max_emails_display']) ? absint($_POST['max_emails_display']) : 50);
        update_option('waitlist_show_subscriber_count', isset($_POST['show_subscriber_count']) ? '1' : '0');
        
        // Configuración de Excel
        update_option('waitlist_excel_logo', isset($_POST['excel_logo']) ? esc_url_raw($_POST['excel_logo']) : '');
        update_option('waitlist_excel_color_header', isset($_POST['excel_color_header']) ? sanitize_hex_color($_POST['excel_color_header']) : '#D50000');
        update_option('waitlist_excel_color_text', isset($_POST['excel_color_text']) ? sanitize_hex_color($_POST['excel_color_text']) : '#FFFFFF');
        update_option('waitlist_excel_filename', isset($_POST['excel_filename']) ? sanitize_text_field($_POST['excel_filename']) : 'lista-de-espera-{product_name}-{date}');
        
        // Configuración de Email
        update_option('waitlist_enable_email_notifications', isset($_POST['enable_email_notifications']) ? '1' : '0');
        update_option('waitlist_email_subject', isset($_POST['email_subject']) ? wp_kses_post($_POST['email_subject']) : '');
        
        // Guardar mensaje de email si está presente
        if (isset($_POST['email_message'])) {
            update_option('waitlist_email_message', wp_kses_post($_POST['email_message']));
        }
        
        update_option('waitlist_email_logo', isset($_POST['email_logo']) ? esc_url_raw($_POST['email_logo']) : '');
        update_option('waitlist_email_color_header', isset($_POST['email_color_header']) ? sanitize_hex_color($_POST['email_color_header']) : '#D50000');
        update_option('waitlist_email_color_button', isset($_POST['email_color_button']) ? sanitize_hex_color($_POST['email_color_button']) : '#D50000');
        update_option('waitlist_email_from_name', isset($_POST['email_from_name']) ? sanitize_text_field($_POST['email_from_name']) : get_bloginfo('name'));
        update_option('waitlist_email_from_address', isset($_POST['email_from_address']) ? sanitize_email($_POST['email_from_address']) : get_bloginfo('admin_email'));
        
        // Opción de migración
        update_option('waitlist_hide_migration', isset($_POST['hide_migration']) ? '1' : '0');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Waitlist - Configuración guardada correctamente');
        }
        
        // Redirigir de vuelta a la página de configuración con mensaje de éxito
        wp_redirect(add_query_arg(
            array(
                'page' => 'waitlist-settings',
                'settings-updated' => 'true',
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
        $settings_updated = false;
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            $settings_updated = true;
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
    
    /**
     * Envía un correo electrónico de prueba
     */
    public function send_test_email() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'waitlist_test_email')) {
            wp_send_json_error('Error de seguridad. Por favor, recarga la página e intenta de nuevo.');
            return;
        }
        
        // Verificar dirección de correo
        if (!isset($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json_error('Por favor, ingresa una dirección de correo electrónico válida.');
            return;
        }
        
        $to = sanitize_email($_POST['email']);
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '¡Producto de prueba ya está disponible!';
        $message = isset($_POST['message']) ? wp_kses_post($_POST['message']) : 'Este es un mensaje de prueba.';
        
        // Obtener opciones de personalización
        $logo_url = isset($_POST['logo']) ? esc_url_raw($_POST['logo']) : '';
        $header_color = isset($_POST['header_color']) ? sanitize_hex_color($_POST['header_color']) : '#D50000';
        $button_color = isset($_POST['button_color']) ? sanitize_hex_color($_POST['button_color']) : '#D50000';
        $from_name = isset($_POST['from_name']) ? sanitize_text_field($_POST['from_name']) : get_bloginfo('name');
        $from_email = isset($_POST['from_email']) ? sanitize_email($_POST['from_email']) : get_option('admin_email');
        
        // Reemplazar variables de muestra
        $store_name = get_bloginfo('name');
        $store_url = get_bloginfo('url');
        $date = date_i18n(get_option('date_format'));
        
        $subject = str_replace(
            array('{product_name}', '{product_url}', '{store_name}', '{store_url}', '{customer_email}', '{date}'),
            array('Producto de Prueba', $store_url, $store_name, $store_url, $to, $date),
            $subject
        );
        
        $message = str_replace(
            array('{product_name}', '{product_url}', '{store_name}', '{store_url}', '{customer_email}', '{date}'),
            array('Producto de Prueba', $store_url, $store_name, $store_url, $to, $date),
            $message
        );
        
        // Crear plantilla de correo con HTML y CSS
        $email_template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>' . esc_html($subject) . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
                    color: #333333;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    border: 1px solid #dddddd;
                    border-radius: 8px;
                    overflow: hidden;
                }
                .email-header {
                    background-color: ' . $header_color . ';
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .logo-container {
                    text-align: center;
                    margin-bottom: 10px;
                }
                .logo {
                    max-width: 150px;
                    height: auto;
                }
                .email-body {
                    padding: 30px 20px;
                    background-color: #ffffff;
                }
                .email-footer {
                    background-color: #f7f7f7;
                    padding: 15px;
                    text-align: center;
                    font-size: 12px;
                    color: #888888;
                }
                .button {
                    display: inline-block;
                    background-color: ' . $button_color . ';
                    color: white;
                    text-decoration: none;
                    padding: 12px 25px;
                    border-radius: 4px;
                    margin-top: 20px;
                    margin-bottom: 20px;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    ' . ($logo_url ? '<div class="logo-container"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($store_name) . '" class="logo"></div>' : '') . '
                    <h1>' . esc_html($store_name) . '</h1>
                </div>
                <div class="email-body">
                    ' . $message . '
                </div>
                <div class="email-footer">
                    &copy; ' . date('Y') . ' ' . esc_html($store_name) . '. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>';
        
        // Configurar encabezados del correo
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        // Enviar correo
        $sent = wp_mail($to, $subject, $email_template, $headers);
        
        if ($sent) {
            wp_send_json_success('Correo de prueba enviado correctamente a ' . $to);
        } else {
            wp_send_json_error('No se pudo enviar el correo de prueba. Por favor, verifica la configuración de tu servidor de correo.');
        }
    }
    
    /**
     * Importa suscriptores desde un archivo CSV
     */
    public function import_csv() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'waitlist_import_csv')) {
            wp_send_json_error('Error de seguridad. Por favor, recarga la página e intenta de nuevo.');
            return;
        }
        
        // Verificar archivo
        if (!isset($_FILES['csv_file']) || empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error('No se ha enviado ningún archivo CSV.');
            return;
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        
        // Verificar que el archivo sea un CSV
        $file_info = pathinfo($_FILES['csv_file']['name']);
        if (strtolower($file_info['extension']) !== 'csv') {
            wp_send_json_error('El archivo debe ser un CSV válido.');
            return;
        }
        
        // Abrir el archivo
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error('No se pudo abrir el archivo CSV.');
            return;
        }
        
        // Leer la primera línea para obtener los encabezados
        $headers = fgetcsv($handle);
        
        // Verificar que los encabezados necesarios estén presentes
        $required_headers = array('product_id', 'email');
        foreach ($required_headers as $required) {
            if (!in_array($required, $headers)) {
                fclose($handle);
                wp_send_json_error('El archivo CSV debe contener las columnas "product_id" y "email".');
                return;
            }
        }
        
        // Índices de las columnas
        $product_id_index = array_search('product_id', $headers);
        $email_index = array_search('email', $headers);
        $variation_id_index = array_search('variation_id', $headers);
        
        // Preparar para insertar en la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'waitlist';
        
        $imported = 0;
        $errors = 0;
        
        // Procesar cada línea
        while (($data = fgetcsv($handle)) !== false) {
            // Verificar que haya suficientes columnas
            if (count($data) < 2) {
                $errors++;
                continue;
            }
            
            $product_id = intval($data[$product_id_index]);
            $email = sanitize_email($data[$email_index]);
            $variation_id = ($variation_id_index !== false && isset($data[$variation_id_index])) ? intval($data[$variation_id_index]) : 0;
            
            // Validar datos
            if ($product_id <= 0 || !is_email($email)) {
                $errors++;
                continue;
            }
            
            // Si hay un ID de variación, usar ese como product_id
            $target_id = ($variation_id > 0) ? $variation_id : $product_id;
            
            // Verificar si el producto existe
            $product = wc_get_product($target_id);
            if (!$product) {
                $errors++;
                continue;
            }
            
            // Verificar si el email ya está suscrito a este producto
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE product_id = %d AND email = %s",
                $target_id,
                $email
            ));
            
            if ($existing > 0) {
                // Ya existe, no duplicar
                continue;
            }
            
            // Insertar en la base de datos
            $result = $wpdb->insert(
                $table_name,
                array(
                    'product_id' => $target_id,
                    'email' => $email,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s')
            );
            
            if ($result) {
                $imported++;
            } else {
                $errors++;
            }
        }
        
        fclose($handle);
        
        if ($imported > 0) {
            wp_send_json_success('Importación completada. Se importaron ' . $imported . ' suscriptores' . ($errors > 0 ? ' con ' . $errors . ' errores.' : '.'));
        } else {
            wp_send_json_error('No se importaron suscriptores. Verifique el formato del archivo CSV y que los datos sean válidos.');
        }
    }
    
    /**
     * Migra suscriptores desde YITH WooCommerce Waitlist
     */
    public function migrate_from_yith() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'waitlist_migrate_from_yith')) {
            wp_send_json_error('Error de seguridad. Por favor, recarga la página e intenta de nuevo.');
            return;
        }
        
        // Verificar si YITH WooCommerce Waitlist está activo
        if (!class_exists('YITH_WCWTL')) {
            wp_send_json_error('YITH WooCommerce Waitlist no está instalado o activado.');
            return;
        }
        
        global $wpdb;
        $our_table = $wpdb->prefix . 'waitlist';
        
        // Consultar productos con lista de espera en YITH
        $products_query = "
            SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_yith_wcwtl_users' 
            AND meta_value != '' 
            AND meta_value != 'a:0:{}'
        ";
        
        $products = $wpdb->get_col($products_query);
        
        if (empty($products)) {
            wp_send_json_error('No se encontraron suscriptores para migrar desde YITH WooCommerce Waitlist.');
            return;
        }
        
        $migrated = 0;
        
        foreach ($products as $product_id) {
            // Obtener suscriptores de YITH
            $subscribers = get_post_meta($product_id, '_yith_wcwtl_users', true);
            
            if (empty($subscribers) || !is_array($subscribers)) {
                continue;
            }
            
            foreach ($subscribers as $user_id) {
                // Obtener email del usuario
                $user_info = get_userdata($user_id);
                
                if (!$user_info || empty($user_info->user_email)) {
                    continue;
                }
                
                $email = $user_info->user_email;
                
                // Verificar si ya existe esta suscripción
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $our_table WHERE product_id = %d AND email = %s",
                    $product_id,
                    $email
                ));
                
                if ($existing > 0) {
                    // Ya existe, no duplicar
                    continue;
                }
                
                // Insertar en nuestra tabla
                $result = $wpdb->insert(
                    $our_table,
                    array(
                        'product_id' => $product_id,
                        'email' => $email,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s')
                );
                
                if ($result) {
                    $migrated++;
                }
            }
        }
        
        if ($migrated > 0) {
            wp_send_json_success(['count' => $migrated, 'message' => 'Migración completada con éxito. Se migraron ' . $migrated . ' suscriptores.']);
        } else {
            wp_send_json_error('No se pudo migrar ningún suscriptor. Verifica que haya datos disponibles en YITH WooCommerce Waitlist.');
        }
    }
}