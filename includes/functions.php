<?php
/**
 * Funciones auxiliares para el plugin
 */

/**
 * Exporta la lista de espera a Excel usando PhpSpreadsheet
 */
function waitlist_export_csv() {
    // Cargar autoload.php de Composer explícitamente desde la raíz del plugin
    $autoload_path = dirname(dirname(__FILE__)) . '/vendor/autoload.php';
    if (file_exists($autoload_path)) {
        require_once $autoload_path;
    }
    
    // Verificar que PhpSpreadsheet esté disponible
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        wp_die('La biblioteca PhpSpreadsheet no está disponible. Por favor, ejecuta "composer install" en el directorio del plugin.', 'Error de dependencia', array('back_link' => true));
        return;
    }
    
    // Verificar tipo de exportación
    $export_type = isset($_GET['export_type']) ? sanitize_text_field($_GET['export_type']) : 'products';
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'waitlist';
    
    // Crear nuevo objeto Spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Obtener colores de configuración
    $header_color = str_replace('#', '', get_option('waitlist_excel_header_color', '0066CC'));
    $alternate_color = str_replace('#', '', get_option('waitlist_excel_alternate_color', 'F2F2F2'));
    $include_timestamp = get_option('waitlist_include_timestamp', '1') === '1';
    
    // Configurar estilos para cabeceras
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => $header_color],
        ],
    ];
    
    // Configurar estilos para filas alternas
    $evenRowStyle = [
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => $alternate_color],
        ],
    ];
    
    if ($export_type === 'products') {
        // Exportar resumen de productos
        $results = Waitlist_Model::get_products_with_subscribers_grouped();
        
        if (empty($results)) {
            wp_die('No hay datos para exportar.', 'Lista de espera', array('back_link' => true));
            return;
        }
        
        // Configurar título de la hoja
        $sheet->setTitle('Productos con Lista de Espera');
        
        // Configurar cabeceras
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Producto');
        $sheet->setCellValue('C1', 'Total Suscriptores');
        $sheet->setCellValue('D1', 'Variaciones');
        $sheet->setCellValue('E1', 'Última Suscripción');
        
        // Aplicar estilo a cabeceras
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        // Configurar el formato de la tabla
        $sheet->calculateColumnWidths();
        
        // Escribir datos
        $row = 2;
        foreach ($results as $item) {
            $sheet->setCellValue('A' . $row, $item->main_product_id);
            $sheet->setCellValue('B' . $row, $item->product_name);
            $sheet->setCellValue('C' . $row, $item->subscribers_count);
            $sheet->setCellValue('D' . $row, $item->variations_count);
            $sheet->setCellValue('E' . $row, isset($item->last_subscription) ? date('d/m/Y H:i', strtotime($item->last_subscription)) : '');
            
            // Aplicar estilo a filas alternas
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($evenRowStyle);
            }
            
            $row++;
        }
        
        // Autoajustar columnas
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Nombre del archivo
        $filename = "productos_lista_espera_" . ($include_timestamp ? date('Y-m-d_H-i-s') : date('Y-m-d')) . ".xlsx";
    } else {
        // Exportar todos los suscriptores o de un producto específico
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        
        if ($export_type === 'variations' && isset($_GET['parent_id'])) {
            // Exportar variaciones de un producto
            $parent_id = intval($_GET['parent_id']);
            $variations = Waitlist_Model::get_product_variations_detail($parent_id);
            
            if (empty($variations)) {
                wp_die('No hay variaciones con suscriptores para exportar.', 'Lista de espera', array('back_link' => true));
                return;
            }
            
            // Obtener nombre del producto padre
            $parent_product = wc_get_product($parent_id);
            $parent_name = $parent_product ? $parent_product->get_name() : 'Producto #' . $parent_id;
            
            // Configurar título de la hoja
            $sheet->setTitle('Variaciones - ' . substr($parent_name, 0, 20));
            
            // Configurar cabeceras
            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Variación');
            $sheet->setCellValue('C1', 'Atributos');
            $sheet->setCellValue('D1', 'Suscriptores');
            $sheet->setCellValue('E1', 'Fecha Primera Suscripción');
            $sheet->setCellValue('F1', 'Fecha Última Suscripción');
            
            // Aplicar estilo a cabeceras
            $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
            
            // Escribir datos
            $row = 2;
            foreach ($variations as $variation) {
                $sheet->setCellValue('A' . $row, $variation->product_id);
                $sheet->setCellValue('B' . $row, $variation->variation_name);
                $sheet->setCellValue('C' . $row, isset($variation->attributes) ? $variation->attributes : 'N/A');
                $sheet->setCellValue('D' . $row, $variation->subscribers_count);
                $sheet->setCellValue('E' . $row, $variation->first_subscription);
                $sheet->setCellValue('F' . $row, $variation->last_subscription);
                
                // Aplicar estilo a filas alternas
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($evenRowStyle);
                }
                
                $row++;
            }
            
            // Autoajustar columnas
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Nombre del archivo
            $filename = "variaciones_" . sanitize_title($parent_name) . "_" . ($include_timestamp ? date('Y-m-d_H-i-s') : date('Y-m-d')) . ".xlsx";
        } else {
            // Exportar suscriptores (todos o de un producto específico)
            $subscribers = Waitlist_Model::get_subscribers($product_id);
            
            if (empty($subscribers)) {
                wp_die('No hay suscriptores para exportar.', 'Lista de espera', array('back_link' => true));
                return;
            }
            
            // Configurar título de la hoja
            if ($product_id > 0) {
                $product = wc_get_product($product_id);
                $product_name = $product ? $product->get_name() : 'Producto #' . $product_id;
                $sheet->setTitle('Suscriptores - ' . substr($product_name, 0, 20));
            } else {
                $sheet->setTitle('Todos los Suscriptores');
            }
            
            // Configurar cabeceras
            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Producto');
            $sheet->setCellValue('C1', 'Email');
            $sheet->setCellValue('D1', 'Fuente');
            
            // Aplicar estilo a cabeceras
            $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
            
            // Escribir datos
            $row = 2;
            foreach ($subscribers as $subscriber) {
                $product_name = isset($subscriber->product_name) ? $subscriber->product_name : 'Producto #' . $subscriber->product_id;
                
                $sheet->setCellValue('A' . $row, $subscriber->product_id);
                $sheet->setCellValue('B' . $row, $product_name);
                $sheet->setCellValue('C' . $row, $subscriber->email);
                
                // Determinar la fuente del suscriptor (YITH o nativa)
                $source = (strpos($subscriber->id, 'yith_') === 0) ? 'YITH' : 'Nativa';
                $sheet->setCellValue('D' . $row, $source);
                
                // Aplicar estilo a filas alternas
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($evenRowStyle);
                }
                
                $row++;
            }
            
            // Autoajustar columnas
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Nombre del archivo
            if ($product_id > 0) {
                $filename = "suscriptores_" . sanitize_title($product_name) . "_" . ($include_timestamp ? date('Y-m-d_H-i-s') : date('Y-m-d')) . ".xlsx";
            } else {
                $filename = "todos_suscriptores_" . ($include_timestamp ? date('Y-m-d_H-i-s') : date('Y-m-d')) . ".xlsx";
            }
        }
    }
    
    // Configurar propiedades del documento
    $spreadsheet->getProperties()
        ->setCreator(get_bloginfo('name'))
        ->setLastModifiedBy(get_bloginfo('name'))
        ->setTitle('Lista de Espera - ' . date('Y-m-d'))
        ->setSubject('Exportación de Lista de Espera')
        ->setDescription('Datos exportados de la Lista de Espera de WooCommerce')
        ->setKeywords('lista de espera, woocommerce, exportación')
        ->setCategory('Reportes');
    
    // Configurar cabecera para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Crear el escritor de Excel y enviar el archivo
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Envía notificaciones cuando el stock se repone
 */
function waitlist_process_stock_change($product_id) {
    $product = wc_get_product($product_id);
    
    if (!$product || !$product->is_in_stock()) {
        return;
    }
    
    $subscribers = Waitlist_Model::get_subscribers($product_id);
    
    if (empty($subscribers)) {
        return;
    }
    
    // Configuración de email
    $subject = get_option('waitlist_email_subject', '¡{product_name} ya está disponible!');
    $message = get_option('waitlist_email_message', 'Hola, {product_name} ya está disponible en nuestra tienda. ¡No te lo pierdas!');
    
    // Reemplazar variables
    $subject = str_replace('{product_name}', $product->get_name(), $subject);
    $message = str_replace('{product_name}', $product->get_name(), $message);
    $message = str_replace('{product_url}', get_permalink($product_id), $message);
    
    // Enviar emails
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
    );
    
    foreach ($subscribers as $subscriber) {
        wp_mail($subscriber->user_email, $subject, $message, $headers);
    }
    
    // Eliminar suscriptores notificados
    Waitlist_Model::delete_product_subscribers($product_id);
}

// Conectar con cambios de stock
add_action('woocommerce_product_set_stock_status', 'waitlist_process_stock_change', 10, 1);
add_action('woocommerce_variation_set_stock_status', 'waitlist_process_stock_change', 10, 1);

/**
 * Maneja la solicitud AJAX para eliminar un suscriptor
 */
function waitlist_delete_subscriber_ajax() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_subscriber')) {
        wp_send_json_error('Verificación de seguridad fallida.');
        return;
    }
    
    // Verificar ID de suscriptor
    if (!isset($_POST['subscriber_id']) || empty($_POST['subscriber_id'])) {
        wp_send_json_error('ID de suscriptor no válido.');
        return;
    }
    
    $subscriber_id = sanitize_text_field($_POST['subscriber_id']);
    
    // Intentar eliminar el suscriptor
    $result = Waitlist_Model::delete_subscriber($subscriber_id);
    
    if ($result) {
        wp_send_json_success('Suscriptor eliminado correctamente.');
    } else {
        wp_send_json_error('No se pudo eliminar el suscriptor. Por favor, inténtalo de nuevo.');
    }
}
add_action('wp_ajax_delete_subscriber', 'waitlist_delete_subscriber_ajax');