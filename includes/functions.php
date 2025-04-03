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
        
        // Aplicar estilo a cabeceras
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        
        // Configurar el formato de la tabla
        $sheet->calculateColumnWidths();
        
        // Escribir datos
        $row = 2;
        foreach ($results as $item) {
            $sheet->setCellValue('A' . $row, $item->main_product_id);
            $sheet->setCellValue('B' . $row, $item->product_name);
            $sheet->setCellValue('C' . $row, $item->subscribers_count);
            $sheet->setCellValue('D' . $row, $item->variations_count);
            
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
            
            // Agrupar suscriptores por email para la exportación
            $email_counts = array();
            foreach ($subscribers as $subscriber) {
                $email = $subscriber->email;
                
                if (!isset($email_counts[$email])) {
                    $email_counts[$email] = array(
                        'user' => '',
                        'products' => array(),
                        'count' => 0
                    );
                    
                    // Obtener información de usuario si está registrado
                    $user = get_user_by('email', $email);
                    if ($user) {
                        $email_counts[$email]['user'] = $user->display_name;
                    } else {
                        $email_counts[$email]['user'] = 'No registrado';
                    }
                }
                
                $email_counts[$email]['count']++;
                $email_counts[$email]['products'][] = $subscriber->product_id;
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
            $sheet->setCellValue('A1', 'Email');
            $sheet->setCellValue('B1', 'Usuario');
            $sheet->setCellValue('C1', 'Productos Diferentes');
            
            // Aplicar estilo a cabeceras
            $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
            
            // Escribir datos
            $row = 2;
            foreach ($email_counts as $email => $data) {
                $unique_product_ids = array_unique($data['products']);
                
                $sheet->setCellValue('A' . $row, $email);
                $sheet->setCellValue('B' . $row, $data['user']);
                $sheet->setCellValue('C' . $row, count($unique_product_ids));
                
                // Aplicar estilo a filas alternas
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($evenRowStyle);
                }
                
                $row++;
            }
            
            // Autoajustar columnas
            foreach (range('A', 'C') as $col) {
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
 * Procesa los cambios de stock y envía notificaciones
 * 
 * @param int $product_id ID del producto
 * @param string $status Estado del stock
 * @param int $variation_id ID de la variación (opcional)
 */
function waitlist_process_stock_change($product_id, $status, $variation_id = 0) {
    global $wpdb;
    
    // Solo procesar si el producto está en stock
    if ($status === 'instock') {
        $table_name = $wpdb->prefix . 'waitlist';
        $target_id = ($variation_id > 0) ? $variation_id : $product_id;
        
        // Obtener suscriptores para este producto/variación
        $subscribers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE product_id = %d",
                $target_id
            )
        );
        
        if (empty($subscribers)) {
            return;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        // Si es una variación, obtener el producto padre para el título
        $product_title = $product->get_title();
        if ($variation_id > 0) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $product_title = $variation->get_formatted_name();
            }
        }
        
        $product_url = get_permalink($product_id);
        $store_name = get_bloginfo('name');
        $store_url = get_bloginfo('url');
        $current_date = date_i18n(get_option('date_format'));
        
        // Obtener configuración de correo
        $subject = get_option('waitlist_email_subject', '¡{product_name} está disponible!');
        $message = get_option('waitlist_email_message', 'Hola, te informamos que {product_name} ya está disponible. Puedes comprarlo haciendo clic en el siguiente enlace: {product_url}');
        $from_name = get_option('waitlist_email_from_name', get_bloginfo('name'));
        $from_email = get_option('waitlist_email_from_address', get_option('admin_email'));
        
        // Obtener opciones de personalización
        $logo_url = get_option('waitlist_email_logo', '');
        $header_color = get_option('waitlist_email_color_header', '#0066CC');
        $button_color = get_option('waitlist_email_color_button', '#4CAF50');
        
        // Crear plantilla de correo con HTML y CSS
        $email_template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>{email_subject}</title>
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
                    <h1>{store_name}</h1>
                </div>
                <div class="email-body">
                    {email_message}
                    <div style="text-align: center;">
                        <a href="{product_url}" class="button">Ver Producto</a>
                    </div>
                </div>
                <div class="email-footer">
                    &copy; ' . date('Y') . ' {store_name}. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>';
        
        foreach ($subscribers as $subscriber) {
            // Personalizar el mensaje para cada suscriptor
            $personalized_subject = str_replace(
                array('{product_name}', '{product_url}', '{store_name}', '{store_url}', '{customer_email}', '{date}'),
                array($product_title, $product_url, $store_name, $store_url, $subscriber->user_email, $current_date),
                $subject
            );
            
            // Procesar el contenido del mensaje
            $personalized_message = str_replace(
                array('{product_name}', '{product_url}', '{store_name}', '{store_url}', '{customer_email}', '{date}'),
                array($product_title, $product_url, $store_name, $store_url, $subscriber->user_email, $current_date),
                $message
            );
            
            // Insertar mensaje personalizado en plantilla
            $personalized_html = str_replace(
                array('{email_subject}', '{email_message}', '{product_url}', '{store_name}'),
                array($personalized_subject, $personalized_message, $product_url, $store_name),
                $email_template
            );
            
            // Configurar encabezados del correo
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );
            
            // Agregar registro de depuración
            error_log('Enviando correo de lista de espera a: ' . $subscriber->user_email . ' para el producto ID: ' . $target_id);
            
            // Enviar correo
            wp_mail($subscriber->user_email, $personalized_subject, $personalized_html, $headers);
            
            // Eliminar al suscriptor de la lista de espera
            $wpdb->delete(
                $table_name,
                array('id' => $subscriber->id),
                array('%d')
            );
        }
    }
}

// Conectar con cambios de stock
add_action('woocommerce_product_set_stock_status', 'waitlist_process_stock_change', 10, 3);
add_action('woocommerce_variation_set_stock_status', 'waitlist_process_stock_change', 10, 3);

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