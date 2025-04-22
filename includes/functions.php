<?php
/**
 * Funciones auxiliares para el plugin
 */

/**
 * Exporta la lista de espera a Excel usando PhpSpreadsheet
 */
function waitlist_export_csv() {
    try {
        // Cargar autoload.php de Composer explícitamente desde la raíz del plugin
        $autoload_path = dirname(dirname(__FILE__)) . '/vendor/autoload.php';
        error_log('Intentando cargar autoload.php desde: ' . $autoload_path);
        
        if (file_exists($autoload_path)) {
            require_once $autoload_path;
            error_log('Autoload cargado correctamente');
        } else {
            error_log('Autoload.php no encontrado en: ' . $autoload_path);
            // Intentar cargarlo desde otras ubicaciones posibles
            $alt_paths = [
                WP_PLUGIN_DIR . '/Lista de Espera/vendor/autoload.php',
                WP_CONTENT_DIR . '/plugins/Lista de Espera/vendor/autoload.php',
                dirname(plugin_dir_path(__FILE__)) . '/vendor/autoload.php'
            ];
            
            foreach ($alt_paths as $path) {
                error_log('Intentando ubicación alternativa: ' . $path);
                if (file_exists($path)) {
                    require_once $path;
                    error_log('Autoload cargado correctamente desde ubicación alternativa: ' . $path);
                    break;
                }
            }
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
        
        // Estilo para encabezados de datos
        $dataHeaderStyle = [
            'font' => [
                'bold' => true,
            ],
        ];
        
        if ($export_type === 'product_detail' && isset($_GET['parent_id'])) {
            try {
                // Exportar detalle de producto por talla
                $parent_id = intval($_GET['parent_id']);
                
                // Depuración
                error_log('Exportando detalle por talla para producto ID: ' . $parent_id);
                
                $parent_product = wc_get_product($parent_id);
                
                if (!$parent_product) {
                    wp_die('Producto no encontrado.', 'Lista de espera', array('back_link' => true));
                    return;
                }
                
                // Obtener todas las variaciones con suscriptores
                $variations = Waitlist_Model::get_product_variations_detail($parent_id);
                
                // Depuración
                error_log('Variaciones encontradas: ' . count($variations));
                
                // Calcular el total de suscriptores
                $total_subscribers = 0;
                foreach ($variations as $variation) {
                    $total_subscribers += $variation->subscribers_count;
                }
                
                // Buscar el atributo "Talla" o usar el primer atributo disponible
                $talla_variations = array();
                $talla_attribute_name = 'Talla';
                
                // Verificar si existe el atributo "Talla"
                $has_talla = false;
                foreach ($variations as $variation) {
                    if (isset($variation->main_attribute_name) && $variation->main_attribute_name === 'Talla') {
                        $has_talla = true;
                        break;
                    }
                }
                
                // Si no hay atributo "Talla", usar el primer atributo disponible
                if (!$has_talla && !empty($variations) && isset($variations[0]->main_attribute_name)) {
                    $talla_attribute_name = $variations[0]->main_attribute_name;
                }
                
                // Depuración
                error_log('Atributo usado: ' . $talla_attribute_name);
                
                // Agrupar por talla
                foreach ($variations as $variation) {
                    if (isset($variation->main_attribute_name) && $variation->main_attribute_name === $talla_attribute_name) {
                        $talla_variations[] = $variation;
                    }
                }
                
                // Depuración
                error_log('Variaciones por talla encontradas: ' . count($talla_variations));
                
                // Calcular totales por talla
                $talla_totals = array();
                foreach ($talla_variations as $variation) {
                    if (!isset($variation->main_attribute_value)) {
                        // Depuración
                        error_log('Variación sin valor de atributo: ' . print_r($variation, true));
                        continue;
                    }
                    
                    $talla_value = $variation->main_attribute_value;
                    
                    if (!isset($talla_totals[$talla_value])) {
                        $talla_totals[$talla_value] = 0;
                    }
                    
                    $talla_totals[$talla_value] += $variation->subscribers_count;
                }
                
                // Ordenar de mayor a menor
                arsort($talla_totals);
                
                // Configurar título de la hoja
                $sheet->setTitle('Detalle por Talla');
                
                // Sección 1: Encabezado del producto
                // Agregar un encabezado mejorado con título más visible
                $sheet->mergeCells('A1:C1');
                $sheet->setCellValue('A1', 'REPORTE DE SUSCRIPTORES POR TALLA');
                
                // Estilo para el título principal
                $titleStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D50000'], // Rojo corporativo
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        ],
                    ],
                ];
                $sheet->getStyle('A1:C1')->applyFromArray($titleStyle);
                $sheet->getRowDimension(1)->setRowHeight(30);
                
                // Información del producto
                $sheet->setCellValue('A3', 'Nombre del producto');
                $sheet->setCellValue('B3', $parent_product->get_name());
                
                $sheet->setCellValue('A4', 'SKU');
                $sheet->setCellValue('B4', $parent_product->get_sku() ? $parent_product->get_sku() : 'N/A');
                
                $sheet->setCellValue('A5', 'Total variaciones');
                $sheet->setCellValue('B5', count($variations));
                
                $sheet->setCellValue('A6', 'Total suscriptores');
                $sheet->setCellValue('B6', $total_subscribers);
                
                // Aplicar estilo a encabezados de datos y mejorar apariencia
                $infoHeaderStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFCB05'], // Amarillo corporativo
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];
                
                $infoDataStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9F9F9'], // Gris muy claro
                    ],
                ];
                
                $sheet->getStyle('A3:A6')->applyFromArray($infoHeaderStyle);
                $sheet->getStyle('B3:B6')->applyFromArray($infoDataStyle);
                
                // Intentar agregar un logotipo - si existe el archivo correspondiente
                try {
                    $logoPath = plugin_dir_path(dirname(__FILE__)) . 'assets/img/scolari.jpg';
                    if (file_exists($logoPath)) {
                        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                        $drawing->setName('Logo');
                        $drawing->setDescription('Logo');
                        $drawing->setPath($logoPath);
                        $drawing->setCoordinates('C3');
                        $drawing->setWidth(100);
                        $drawing->setWorksheet($sheet);
                        
                        // Fusionar celdas para el logo
                        $sheet->mergeCells('C3:C6');
                    }
                } catch (\Exception $e) {
                    error_log('Error al agregar el logo: ' . $e->getMessage());
                }
                
                // Ajustar ancho para mejor presentación
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(20);
                
                // Sección 2: Tabla de suscriptores por talla
                $sheet->setCellValue('A8', $talla_attribute_name);
                $sheet->setCellValue('B8', 'Total Suscriptores');
                $sheet->setCellValue('C8', 'Porcentaje');
                
                // Aplicar estilo a cabeceras de tabla - usar rojo corporativo
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
                        'startColor' => ['rgb' => 'D50000'], // Rojo corporativo
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];
                
                $sheet->getStyle('A8:C8')->applyFromArray($headerStyle);
                $sheet->getRowDimension(8)->setRowHeight(20);
                
                // Alineación centrada para todas las celdas
                $sheet->getStyle('A8:C30')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Escribir datos
                $row = 9;
                $row_total = 0;
                foreach ($talla_totals as $talla_value => $count) {
                    $percentage = $total_subscribers > 0 ? round(($count / $total_subscribers) * 100, 1) : 0;
                    
                    $sheet->setCellValue('A' . $row, $talla_value);
                    $sheet->setCellValue('B' . $row, $count);
                    $sheet->setCellValue('C' . $row, $percentage . '%');
                    
                    // Aplicar estilo a filas alternas
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($evenRowStyle);
                    }
                    
                    $row++;
                    $row_total++;
                }
                
                // Agregar fila de total
                $totalRowStyle = [
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFCB05'], // Amarillo corporativo
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];
                
                $sheet->setCellValue('A' . $row, 'Total');
                $sheet->setCellValue('B' . $row, $total_subscribers);
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($totalRowStyle);
                
                try {
                    // Eliminamos la parte del formato condicional que puede estar causando problemas
                    // Solo aplicaremos un color de fondo a la columna de porcentaje para simular visualmente
                    // una barra de datos sin usar el formato condicional
                    $row_start = 9;
                    for ($i = $row_start; $i < $row; $i++) {
                        $percentage_cell = $sheet->getCell('C' . $i);
                        $percentage_val = floatval(str_replace('%', '', $percentage_cell->getValue()));
                        
                        // Usar degradado de amarillo a rojo según el porcentaje
                        if ($percentage_val <= 0) {
                            $rgb = 'F0F0F0'; // Gris claro para 0%
                        } elseif ($percentage_val < 20) {
                            $rgb = 'FFE0E0'; // Rosa muy claro para porcentajes bajos
                        } elseif ($percentage_val < 40) {
                            $rgb = 'FFD6AD'; // Naranja claro
                        } else {
                            // Calcular degradado entre amarillo y rojo
                            $red = 255;
                            $green = max(0, min(203, 203 - (($percentage_val - 40) * 2)));
                            $blue = max(0, min(5, 5 - ($percentage_val - 40) / 10));
                            $rgb = sprintf('%02X%02X%02X', $red, $green, $blue);
                        }
                        
                        $sheet->getStyle('C' . $i)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $rgb],
                            ],
                        ]);
                    }
                } catch (\Exception $e) {
                    // Si hay error en el formato condicional, lo registramos pero continuamos
                    error_log('Error al aplicar formato condicional: ' . $e->getMessage());
                }
                
                // Autoajustar columnas
                foreach (range('A', 'C') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Agregar bordes a la tabla
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ];
                
                // Aplicar solo si hay datos
                if ($row > 8) {
                    $sheet->getStyle('A8:C' . $row)->applyFromArray($styleArray);
                }
                
                // Nombre del archivo
                $filename = "detalle_tallas_" . sanitize_title($parent_product->get_name()) . "_" . ($include_timestamp ? date('Y-m-d_H-i-s') : date('Y-m-d')) . ".xlsx";
            } catch (\Exception $e) {
                error_log('Error en exportación de detalle por talla: ' . $e->getMessage());
                wp_die('Error al crear el Excel: ' . esc_html($e->getMessage()), 'Error de exportación', array('back_link' => true));
                return;
            }
        }
        else if ($export_type === 'products') {
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
    } catch (\Exception $e) {
        // Capturar cualquier error no controlado
        error_log('Error general en waitlist_export_csv: ' . $e->getMessage());
        wp_die('Error al generar el archivo Excel: ' . esc_html($e->getMessage()), 'Error de exportación', array('back_link' => true));
    }
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