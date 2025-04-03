<div class="wrap waitlist-container">
    <h1>Productos con Lista de Espera</h1>
    
    <p>Esta página muestra todos los productos que tienen usuarios en lista de espera, agrupados por producto principal.</p>
    
    <?php
    // Verificar si se está viendo las variaciones de un producto específico
    $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
    
    // Si hay un ID de producto padre, mostrar sus variaciones
    if ($parent_id) {
        // Obtener el producto principal
        $parent_product = wc_get_product($parent_id);
        
        if (!$parent_product) {
            echo '<div class="notice notice-error"><p>Producto no encontrado.</p></div>';
        } else {
            // Obtener todas las variaciones con suscriptores y detalles mejorados
            $variations = Waitlist_Model::get_product_variations_detail($parent_id);
            
            // Filtrar solo las variaciones (eliminar el producto principal)
            $variations_only = array();
            foreach ($variations as $variation) {
                if (isset($variation->main_attribute_name) && $variation->main_attribute_name !== 'Producto') {
                    $variations_only[] = $variation;
                }
            }
            
            // Calcular el total de suscriptores
            $total_subscribers = 0;
            foreach ($variations_only as $variation) {
                $total_subscribers += $variation->subscribers_count;
            }
            
            // Buscar el atributo "Talla" o usar el primer atributo disponible
            $talla_variations = array();
            $talla_attribute_name = 'Talla';
            
            // Verificar si existe el atributo "Talla"
            $has_talla = false;
            foreach ($variations_only as $variation) {
                if (isset($variation->main_attribute_name) && $variation->main_attribute_name === 'Talla') {
                    $has_talla = true;
                    break;
                }
            }
            
            // Si no hay atributo "Talla", usar el primer atributo disponible
            if (!$has_talla && !empty($variations_only) && isset($variations_only[0]->main_attribute_name)) {
                $talla_attribute_name = $variations_only[0]->main_attribute_name;
            }
            
            // Agrupar por talla
            foreach ($variations_only as $variation) {
                if (isset($variation->main_attribute_name) && $variation->main_attribute_name === $talla_attribute_name) {
                    $talla_variations[] = $variation;
                }
            }
            
            // Calcular totales por talla
            $talla_totals = array();
            foreach ($talla_variations as $variation) {
                $talla_value = $variation->main_attribute_value;
                
                if (!isset($talla_totals[$talla_value])) {
                    $talla_totals[$talla_value] = 0;
                }
                
                $talla_totals[$talla_value] += $variation->subscribers_count;
            }
            
            // Ordenar de mayor a menor
            arsort($talla_totals);
            ?>
            <div class="waitlist-actions-bar">
                <div class="waitlist-navigation">
                    <a href="<?php echo admin_url('admin.php?page=waitlist'); ?>" class="button">&larr; Volver a todos los productos</a>
                </div>
            </div>
            
            <div class="waitlist-product-info">
                <div class="waitlist-product-thumbnail">
                    <?php echo $parent_product->get_image('thumbnail'); ?>
                </div>
                <div class="waitlist-product-details">
                    <h2><?php echo esc_html($parent_product->get_name()); ?></h2>
                    <p><strong>SKU:</strong> <?php echo $parent_product->get_sku() ? esc_html($parent_product->get_sku()) : 'N/A'; ?></p>
                    <p><strong>Total variaciones:</strong> <?php echo count($variations_only); ?></p>
                    <p><strong>Total suscriptores:</strong> <?php echo $total_subscribers; ?></p>
                    <p>
                        <a href="<?php echo get_permalink($parent_id); ?>" target="_blank" class="button button-small">Ver en tienda</a>
                        <a href="<?php echo get_edit_post_link($parent_id); ?>" target="_blank" class="button button-small">Editar producto</a>
                    </p>
                </div>
            </div>
            
            <?php if (empty($variations_only)): ?>
                <div class="notice notice-warning">
                    <p>Este producto no tiene variaciones con suscriptores.</p>
                </div>
            <?php else: ?>
                
                <!-- Sección única de Tallas -->
                <div class="waitlist-variation-group">
                    <h3><?php echo esc_html($talla_attribute_name); ?></h3>
                    
                    <!-- Gráfico de resumen -->
                    <div class="waitlist-summary-chart">
                        <table class="waitlist-excel-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html($talla_attribute_name); ?></th>
                                    <th class="num">Total Suscriptores</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Mostrar cada valor de talla con su total
                                foreach ($talla_totals as $talla_value => $count):
                                    $percentage = $total_subscribers > 0 ? round(($count / $total_subscribers) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($talla_value); ?></strong></td>
                                        <td class="num"><?php echo $count; ?></td>
                                        <td>
                                            <div class="percentage-bar">
                                                <div class="percentage-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                                <span class="percentage-text"><?php echo $percentage; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Detalles de variaciones -->
                    <h4 style="margin-top: 20px;">Detalle de variaciones</h4>
                    <table class="waitlist-excel-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?php echo esc_html($talla_attribute_name); ?></th>
                                <th class="num">Total Suscriptores</th>
                                <th>Emails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_count = 0;
                            foreach ($talla_variations as $variation): 
                                $row_class = ($row_count % 2 == 0) ? 'even-row' : '';
                                $row_count++;
                            ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td>
                                        <?php echo esc_html($variation->product_id); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($variation->main_attribute_value); ?></strong>
                                        <div class="row-actions">
                                            <span class="view">
                                                <a href="<?php echo admin_url('admin.php?page=waitlist&product_id=' . $variation->product_id); ?>">Ver suscriptores</a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="num">
                                        <strong><?php echo intval($variation->subscribers_count); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($variation->emails)) {
                                            $emails = explode(',', $variation->emails);
                                            $max_emails = get_option('waitlist_max_emails_display', '5');
                                            $display_emails = array_slice($emails, 0, $max_emails);
                                            
                                            foreach ($display_emails as $index => $email) {
                                                echo esc_html(trim($email));
                                                if ($index < count($display_emails) - 1) echo ', ';
                                            }
                                            
                                            if (count($emails) > count($display_emails)) {
                                                echo ' y <a href="#" class="view-all-emails" data-emails="' . esc_attr($variation->emails) . '">ver ' . (count($emails) - count($display_emails)) . ' más</a>';
                                            }
                                        } else {
                                            echo '<em>No hay emails registrados</em>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php
        }
    } else {
        // Mostrar todos los productos agrupados
        global $wpdb;
        $products = Waitlist_Model::get_products_with_subscribers_grouped();
        
        // Ordenamiento
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'subscribers';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Paginación
        $items_per_page = 20; // Número de productos por página
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Filtro por categorías
        $category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
        $product_type_id = isset($_GET['product_type']) ? intval($_GET['product_type']) : 0;
        
        // Lista de categorías de colegios (nombres)
        $school_names = array(
            'Alcázar de Las Condes',
            'Alianza Francesa',
            'Colegio Las Condes',
            'Collège La Girouette',
            'Compañía de María Apoquindo',
            'Compañía de María Seminario',
            'Craighouse School',
            'Dunalastair',
            'El Carmen Teresiano Vitacura',
            'Grace College',
            'La Abadía',
            'Leonardo Da Vinci',
            'Lincoln International Academy',
            'Madrigal',
            'Manquecura',
            'Manquecura Ñuñoa',
            'Mariano de Schoenstatt',
            'Nido de Aguilas',
            'Nuestra Señora de Loreto',
            'Nuestra Señora del Rosario',
            'Pumahue',
            'Rafael Sotomayor',
            'Sagrado Corazón Apoquindo',
            'San Francisco de Paine',
            'San Francisco del Alba',
            'San Nicolás de Myra',
            'San Pedro Nolasco',
            'Santa Cruz Chicureo',
            'Scuola Italiana',
            'Seminario Pontificio Menor',
            'Simón Bolívar',
            'The Kent School',
            'The Southern Cross School',
            'Uniforme Tradicional'
        );
        
        // Obtener todas las categorías de productos
        $all_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ));
        
        // Separar categorías en colegios y tipos de prendas
        $school_categories = array();
        $product_type_categories = array();
        
        foreach ($all_categories as $category) {
            if (in_array($category->name, $school_names)) {
                $school_categories[] = $category;
            } else {
                $product_type_categories[] = $category;
            }
        }
        
        // Ordenar las categorías alfabéticamente
        usort($school_categories, function($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
        
        usort($product_type_categories, function($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
        
        // Filtrar productos por categoría si se ha seleccionado una
        if ($category_id > 0 || $product_type_id > 0) {
            // Esta parte necesitaría implementarse en la clase Waitlist_Model
            // Por ahora, hacemos un filtrado básico aquí
            $filtered_products = array();
            foreach ($products as $product) {
                $product_categories = wp_get_post_terms($product->main_product_id, 'product_cat', array('fields' => 'ids'));
                
                $category_match = ($category_id <= 0 || in_array($category_id, $product_categories));
                $type_match = ($product_type_id <= 0 || in_array($product_type_id, $product_categories));
                
                if ($category_match && $type_match) {
                    $filtered_products[] = $product;
                }
            }
            $products = $filtered_products;
        }
        
        // Búsqueda
        if (!empty($search)) {
            $filtered_products = array();
            foreach ($products as $product) {
                if (stripos($product->product_name, $search) !== false || 
                    $product->main_product_id == $search) {
                    $filtered_products[] = $product;
                }
            }
            $products = $filtered_products;
        }
        
        // Ordenar productos
        usort($products, function($a, $b) use ($orderby, $order) {
            $result = 0;
            
            switch ($orderby) {
                case 'id':
                    $result = $a->main_product_id - $b->main_product_id;
                    break;
                case 'name':
                    $result = strcmp($a->product_name, $b->product_name);
                    break;
                case 'subscribers':
                    $result = $a->subscribers_count - $b->subscribers_count;
                    break;
                case 'variations':
                    $result = $a->variations_count - $b->variations_count;
                    break;
                default:
                    $result = $a->subscribers_count - $b->subscribers_count;
            }
            
            return $order === 'asc' ? $result : -$result;
        });
        
        // Contar el total de productos filtrados para la paginación
        $total_items = count($products);
        $total_pages = ceil($total_items / $items_per_page);
        
        // Asegurar que la página actual no sea mayor que el total de páginas
        $current_page = min($current_page, $total_pages);
        
        // Obtener productos para la página actual
        $offset = ($current_page - 1) * $items_per_page;
        $paged_products = array_slice($products, $offset, $items_per_page);
        
        // Construir URLs de ordenamiento
        function get_sort_url($column, $current_orderby, $current_order) {
            $params = $_GET;
            $params['orderby'] = $column;
            $params['order'] = ($current_orderby === $column && $current_order === 'desc') ? 'asc' : 'desc';
            return '?' . http_build_query($params);
        }
        
        // Obtener clase de ordenamiento
        function get_sort_class($column, $current_orderby, $current_order) {
            if ($current_orderby !== $column) {
                return '';
            }
            return $current_order === 'asc' ? 'sorted asc' : 'sorted desc';
        }
        ?>
        
        <!-- Barra de acciones con búsqueda, ordenamiento y exportación -->
        <div class="waitlist-actions-bar">
            <div class="waitlist-search-box">
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="waitlist">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar por nombre o ID" class="waitlist-search-input">
                    
                    <!-- Filtros de categoría con autoenvío -->
                    <select name="category" class="waitlist-category-filter" onchange="this.form.submit()">
                        <option value="0">Todos los colegios</option>
                        <?php foreach ($school_categories as $category): ?>
                            <option value="<?php echo $category->term_id; ?>" <?php selected($category_id, $category->term_id); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="product_type" class="waitlist-category-filter" onchange="this.form.submit()">
                        <option value="0">Todos los tipos de prenda</option>
                        <?php foreach ($product_type_categories as $type): ?>
                            <option value="<?php echo $type->term_id; ?>" <?php selected($product_type_id, $type->term_id); ?>>
                                <?php echo esc_html($type->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if (!empty($search) || $category_id > 0 || $product_type_id > 0): ?>
                    <a href="<?php echo admin_url('admin.php?page=waitlist'); ?>" class="button">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="waitlist-sort-box">
                <form method="get" class="sort-form">
                    <input type="hidden" name="page" value="waitlist">
                    <?php if (!empty($search)): ?>
                    <input type="hidden" name="s" value="<?php echo esc_attr($search); ?>">
                    <?php endif; ?>
                    <?php if ($category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                    <?php if ($product_type_id > 0): ?>
                    <input type="hidden" name="product_type" value="<?php echo $product_type_id; ?>">
                    <?php endif; ?>
                    
                    <label for="orderby">Ordenar por:</label>
                    <select name="orderby" id="orderby" onchange="this.form.submit()">
                        <option value="subscribers" <?php selected($orderby, 'subscribers'); ?>>Suscriptores</option>
                        <option value="name" <?php selected($orderby, 'name'); ?>>Nombre</option>
                        <option value="id" <?php selected($orderby, 'id'); ?>>ID</option>
                        <option value="variations" <?php selected($orderby, 'variations'); ?>>Variaciones</option>
                    </select>
                    
                    <select name="order" id="order" onchange="this.form.submit()">
                        <option value="desc" <?php selected($order, 'desc'); ?>>Descendente</option>
                        <option value="asc" <?php selected($order, 'asc'); ?>>Ascendente</option>
                    </select>
                </form>
            </div>
            
            <div class="waitlist-export">
                <a href="<?php echo admin_url('admin-post.php?action=waitlist_export_csv&export_type=products'); ?>" class="button excel-export-button">
                    <span class="dashicons dashicons-media-spreadsheet"></span> Exportar a Excel
                </a>
            </div>
        </div>
        
        <!-- Tabla de productos -->
        <table class="waitlist-excel-table">
            <thead>
                <tr>
                    <th class="column-id">ID</th>
                    <th class="column-image">Imagen</th>
                    <th class="column-name">Producto</th>
                    <th class="column-subscribers num">Total Suscriptores</th>
                    <th class="column-variations num">Variaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6">No hay productos con suscriptores en la lista de espera.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $row_count = 0;
                    
                    // Filtrar productos antes de la paginación para la búsqueda y categorías
                    $filtered_products = array();
                    foreach ($products as $product) {
                        $include = true;
                        
                        // Aplicar filtros de categoría
                        if ($category_id > 0 || $product_type_id > 0) {
                            $product_categories = wp_get_post_terms($product->main_product_id, 'product_cat', array('fields' => 'ids'));
                            
                            $category_match = ($category_id <= 0 || in_array($category_id, $product_categories));
                            $type_match = ($product_type_id <= 0 || in_array($product_type_id, $product_categories));
                            
                            if (!($category_match && $type_match)) {
                                $include = false;
                            }
                        }
                        
                        // Aplicar búsqueda
                        if (!empty($search)) {
                            if (stripos($product->product_name, $search) === false && 
                                $product->main_product_id != $search) {
                                $include = false;
                            }
                        }
                        
                        if ($include) {
                            $filtered_products[] = $product;
                        }
                    }
                    
                    // Ordenar productos
                    usort($filtered_products, function($a, $b) use ($orderby, $order) {
                        $result = 0;
                        
                        switch ($orderby) {
                            case 'id':
                                $result = $a->main_product_id - $b->main_product_id;
                                break;
                            case 'name':
                                $result = strcmp($a->product_name, $b->product_name);
                                break;
                            case 'subscribers':
                                $result = $a->subscribers_count - $b->subscribers_count;
                                break;
                            case 'variations':
                                $result = $a->variations_count - $b->variations_count;
                                break;
                            default:
                                $result = $a->subscribers_count - $b->subscribers_count;
                        }
                        
                        return $order === 'asc' ? $result : -$result;
                    });
                    
                    // Contar el total de productos filtrados para la paginación
                    $total_items = count($filtered_products);
                    $total_pages = ceil($total_items / $items_per_page);
                    
                    // Asegurar que la página actual no sea mayor que el total de páginas
                    $current_page = min($current_page, $total_pages);
                    
                    // Obtener productos para la página actual
                    $offset = ($current_page - 1) * $items_per_page;
                    $paged_products = array_slice($filtered_products, $offset, $items_per_page);
                    
                    foreach ($paged_products as $item): 
                        $row_class = ($row_count % 2 == 0) ? 'even-row' : '';
                        $row_count++;
                        
                        // Obtener imagen del producto
                        $product = wc_get_product($item->main_product_id);
                        $image = $product ? $product->get_image('thumbnail') : '';
                    ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td class="column-id">
                                <?php echo esc_html($item->main_product_id); ?>
                            </td>
                            <td class="column-image">
                                <?php echo $image; ?>
                            </td>
                            <td class="column-name">
                                <strong><?php echo esc_html($item->product_name); ?></strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url($item->product_link); ?>" target="_blank">Ver en tienda</a> | 
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo esc_url($item->edit_link); ?>" target="_blank">Editar producto</a>
                                    </span>
                                    | <span class="view-details">
                                        <a href="<?php echo admin_url('admin.php?page=waitlist&parent_id=' . $item->main_product_id); ?>" class="view-details-button">Ver detalle</a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-subscribers num">
                                <strong><?php echo intval($item->subscribers_count); ?></strong>
                            </td>
                            <td class="column-variations num">
                                <strong><?php echo intval($item->variations_count); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (!empty($filtered_products)): ?>
        <!-- Paginación -->
        <div class="waitlist-pagination">
            <?php
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $current_page,
                'type' => 'array'
            ));
            
            if (!empty($page_links)) {
                echo '<nav class="pagination-nav"><ul class="pagination">';
                foreach ($page_links as $link) {
                    echo '<li>' . $link . '</li>';
                }
                echo '</ul></nav>';
                
                echo '<div class="pagination-info">';
                echo 'Mostrando ' . (($current_page - 1) * $items_per_page + 1) . ' - ' . 
                     min($current_page * $items_per_page, $total_items) . ' de ' . $total_items . ' productos';
                echo '</div>';
            }
            ?>
        </div>
        <?php endif; ?>
    <?php } ?>
</div>

<style>
    .waitlist-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        padding: 20px 0;
    }
    
    .waitlist-excel-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        background: #fff;
        border: 1px solid #ddd;
    }
    
    .waitlist-excel-table thead th {
        background-color: #0066CC;
        color: white;
        font-weight: bold;
        text-align: left;
        padding: 10px;
        border: 1px solid #0055AA;
    }
    
    .waitlist-excel-table th.num,
    .waitlist-excel-table td.num {
        text-align: center;
    }
    
    .waitlist-excel-table tbody td {
        padding: 8px 10px;
        border: 1px solid #ddd;
        vertical-align: middle;
    }
    
    .waitlist-excel-table tbody tr.even-row {
        background-color: #F2F2F2;
    }
    
    .waitlist-excel-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .waitlist-excel-table .column-image {
        width: 60px;
        text-align: center;
    }
    
    .waitlist-excel-table .column-image img {
        max-width: 50px;
        height: auto;
        display: block;
        margin: 0 auto;
    }
    
    .waitlist-excel-table .column-id {
        width: 60px;
    }
    
    .waitlist-excel-table .column-subscribers,
    .waitlist-excel-table .column-variations {
        width: 120px;
    }
    
    .waitlist-product-info {
        display: flex;
        margin-bottom: 20px;
        padding: 15px;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        border-radius: 4px;
    }
    
    .waitlist-product-thumbnail {
        margin-right: 20px;
    }
    
    .waitlist-product-details {
        flex: 1;
    }
    
    .waitlist-product-details h2 {
        margin-top: 0;
        color: #23282d;
    }
    
    .waitlist-product-details p {
        margin: 5px 0;
        color: #444;
    }
    
    .waitlist-actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .waitlist-search-box {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .waitlist-search-input {
        min-width: 200px;
    }
    
    .waitlist-category-filter {
        min-width: 150px;
    }
    
    .waitlist-sort-box {
        display: flex;
        align-items: center;
    }
    
    .waitlist-sort-box .sort-form {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .waitlist-sort-box label {
        margin-right: 5px;
        font-weight: 500;
    }
    
    .waitlist-sort-box select {
        padding: 6px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .waitlist-export {
        white-space: nowrap;
    }
    
    .waitlist-variation-group h3 {
        margin: 20px 0 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #eee;
        color: #23282d;
    }
    
    .row-actions {
        color: #666;
        font-size: 12px;
        padding-top: 4px;
    }
    
    .row-actions a {
        text-decoration: none;
    }
    
    .row-actions a:hover {
        text-decoration: underline;
    }
    
    /* Estilos para la barra de búsqueda y exportación */
    .waitlist-search-input {
        width: 200px;
        max-width: 100%;
    }
    
    .excel-export-button {
        background-color: #217346 !important; /* Color verde de Excel */
        border-color: #165731 !important;
        color: white !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
    }
    
    .excel-export-button:hover {
        background-color: #165731 !important;
        border-color: #0e3b21 !important;
    }
    
    .excel-export-button .dashicons {
        font-size: 18px;
        height: 18px;
        width: 18px;
    }
    
    /* Responsive */
    @media screen and (max-width: 782px) {
        .waitlist-actions-bar {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .waitlist-search-box,
        .waitlist-sort-box,
        .waitlist-export {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .waitlist-search-box .search-form,
        .waitlist-sort-box .sort-form {
            flex-wrap: wrap;
        }
    }
    
    /* Estilos para el resumen de suscriptores por atributo */
    .waitlist-summary-section {
        margin-bottom: 20px;
    }
    
    .waitlist-summary-chart {
        padding: 15px;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        border-radius: 4px;
    }
    
    .percentage-bar {
        display: flex;
        align-items: center;
        height: 20px;
        border-radius: 4px;
        background-color: #f5f5f5;
        border: 1px solid #ddd;
    }
    
    .percentage-fill {
        background-color: #217346;
        height: 100%;
        border-radius: 4px 0 0 4px;
    }
    
    .percentage-text {
        margin-left: 5px;
        font-size: 12px;
        color: #666;
    }
    
    /* Estilos para el modal */
    .waitlist-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .waitlist-modal {
        background-color: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        width: 400px;
        max-width: 90%;
    }
    
    .waitlist-modal-header {
        padding: 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .waitlist-modal-header h3 {
        margin: 0;
        font-size: 16px;
    }
    
    .waitlist-modal-close {
        cursor: pointer;
        font-size: 20px;
    }
    
    .waitlist-modal-content {
        padding: 15px;
    }
    
    .view-all-emails {
        color: #0066CC;
        text-decoration: none;
        cursor: pointer;
    }
    
    .view-all-emails:hover {
        text-decoration: underline;
    }
    
    .view-details-button {
        background-color: #4CAF50;
        color: white;
        padding: 2px 8px;
        border-radius: 3px;
        text-decoration: none;
        font-size: 11px;
        display: inline-block;
    }
    
    .view-details-button:hover {
        background-color: #45a049;
        color: white;
    }
    
    /* Estilos para paginación */
    .waitlist-pagination {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .pagination-nav {
        flex: 1;
    }
    
    .pagination {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .pagination li {
        margin: 0 5px;
    }
    
    .pagination a, .pagination span {
        display: inline-block;
        padding: 5px 10px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        text-decoration: none;
        color: #333;
        border-radius: 3px;
    }
    
    .pagination span.current {
        background: #0066CC;
        color: white;
        border-color: #0055AA;
    }
    
    .pagination a:hover {
        background: #e5e5e5;
    }
    
    .pagination-info {
        color: #666;
        font-size: 13px;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Modal para ver todos los emails
        $('body').on('click', '.view-all-emails', function(e) {
            e.preventDefault();
            
            var emails = $(this).data('emails').split(',');
            var emailList = '<ul style="max-height: 300px; overflow-y: auto; margin: 0; padding: 0 0 0 20px;">';
            
            $.each(emails, function(index, email) {
                emailList += '<li>' + email.trim() + '</li>';
            });
            
            emailList += '</ul>';
            
            // Crear modal
            var modal = $('<div class="waitlist-modal-overlay"><div class="waitlist-modal"><div class="waitlist-modal-header"><h3>Todos los correos (' + emails.length + ')</h3><span class="waitlist-modal-close">&times;</span></div><div class="waitlist-modal-content">' + emailList + '</div></div></div>');
            
            $('body').append(modal);
            
            // Cerrar modal
            $('.waitlist-modal-close, .waitlist-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('.waitlist-modal-overlay').remove();
                }
            });
        });
    });
</script>