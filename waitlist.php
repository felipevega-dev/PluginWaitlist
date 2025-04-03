<?php
/*
Plugin Name: Lista de Espera WooCommerce
Description: Permite a los clientes registrarse en una lista de espera para productos sin stock y recibir una notificación por email cuando vuelvan a estar disponibles.
Version: 3.2.1
Author: Gearlabs
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Definir constantes
define('WAITLIST_VERSION', '2.0.0');
define('WAITLIST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAITLIST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar Composer Autoloader si existe
$composer_autoload_paths = array(
    WAITLIST_PLUGIN_DIR . 'vendor/autoload.php', // Ruta estándar
    dirname(WAITLIST_PLUGIN_DIR) . '/vendor/autoload.php', // Un nivel arriba
    WP_PLUGIN_DIR . '/vendor/autoload.php', // En la carpeta plugins
);

$autoloader_loaded = false;
foreach ($composer_autoload_paths as $autoload_path) {
    if (file_exists($autoload_path)) {
        require_once $autoload_path;
        $autoloader_loaded = true;
        break;
    }
}

// Verificar que PhpSpreadsheet esté disponible después de cargar el autoloader
if ($autoloader_loaded && !class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>Lista de Espera:</strong> PhpSpreadsheet no se cargó correctamente a pesar de que el autoloader de Composer está presente. 
            Esto puede afectar la exportación a Excel. Si encuentras problemas, por favor contacta al soporte.</p>
        </div>
        <?php
    });
}

// Incluir archivos necesarios
require_once WAITLIST_PLUGIN_DIR . 'includes/functions.php';
require_once WAITLIST_PLUGIN_DIR . 'includes/class-waitlist-model.php';
require_once WAITLIST_PLUGIN_DIR . 'includes/class-waitlist-view.php';
require_once WAITLIST_PLUGIN_DIR . 'includes/class-waitlist-controller.php';

// Inicializar el plugin
function waitlist_init() {
    // Inicializar controlador
    $controller = new Waitlist_Controller();
    $controller->init();
    
    // Inicializar admin si es necesario
    if (is_admin()) {
        require_once WAITLIST_PLUGIN_DIR . 'includes/admin/class-waitlist-admin.php';
        $admin = new Waitlist_Admin();
        $admin->init();
    }
}
add_action('plugins_loaded', 'waitlist_init');

// Registrar la activación
register_activation_hook(__FILE__, 'waitlist_activate');
function waitlist_activate() {
    // Crear tabla en la base de datos
    Waitlist_Model::create_table();
    
    // Otras acciones de activación
    flush_rewrite_rules();
}

// Registrar la desactivación
register_deactivation_hook(__FILE__, 'waitlist_deactivate');
function waitlist_deactivate() {
    // Limpiar opciones si es necesario
    flush_rewrite_rules();
}

// Hooks para cambios de stock
// Disparar cuando un producto simple cambia de estado
remove_action('woocommerce_product_set_stock_status', 'waitlist_process_stock_change', 10, 3);
add_action('woocommerce_product_set_stock_status', function($product_id, $status, $product) {
    waitlist_process_stock_change($product_id, $status, 0);
}, 10, 3);

// Disparar cuando una variación cambia de estado
remove_action('woocommerce_variation_set_stock_status', 'waitlist_process_stock_change', 10, 3);
add_action('woocommerce_variation_set_stock_status', function($variation_id, $status, $product) {
    $parent_id = wp_get_post_parent_id($variation_id);
    waitlist_process_stock_change($parent_id, $status, $variation_id);
}, 10, 3);
