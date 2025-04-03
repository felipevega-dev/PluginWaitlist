<div class="wrap waitlist-container">
    <h1>Todos los Suscriptores</h1>
    
    <!-- Barra de acciones con búsqueda y exportación -->
    <div class="waitlist-actions-bar">
        <div class="waitlist-search-box">
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="waitlist-subscribers">
                <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Buscar por email o producto" class="waitlist-search-input">
                <button type="submit" class="button"><span class="dashicons dashicons-search"></span> Buscar</button>
                <?php if (!empty($search)): ?>
                <a href="<?php echo admin_url('admin.php?page=waitlist-subscribers'); ?>" class="button"><span class="dashicons dashicons-dismiss"></span> Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="waitlist-sort-box">
            <form method="get" class="sort-form">
                <input type="hidden" name="page" value="waitlist-subscribers">
                <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?php echo esc_attr($search); ?>">
                <?php endif; ?>
                <?php if (isset($_GET['paged'])): ?>
                <input type="hidden" name="paged" value="<?php echo intval($_GET['paged']); ?>">
                <?php endif; ?>
                
                <label for="orderby">Ordenar por:</label>
                <select name="orderby" id="orderby" onchange="this.form.submit()">
                    <option value="products" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'products'); ?>>Productos</option>
                    <option value="email" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : '', 'email'); ?>>Email</option>
                </select>
                
                <select name="order" id="order" onchange="this.form.submit()">
                    <option value="desc" <?php selected(isset($_GET['order']) ? $_GET['order'] : 'desc', 'desc'); ?>>Descendente</option>
                    <option value="asc" <?php selected(isset($_GET['order']) ? $_GET['order'] : '', 'asc'); ?>>Ascendente</option>
                </select>
            </form>
        </div>
        
        <div class="waitlist-export">
            <a href="<?php echo admin_url('admin-post.php?action=waitlist_export_csv&export_type=subscribers'); ?>" class="button excel-export-button">
                <span class="dashicons dashicons-media-spreadsheet"></span> Exportar a Excel
            </a>
        </div>
    </div>
    
    <?php
    // Obtener todos los recuentos de suscripción por email
    $all_email_counts = Waitlist_Model::get_email_subscription_counts();

    // Obtener un recuento de los emails y los productos que tienen en lista de espera
    // Nota: los suscriptores ya vienen paginados de la base de datos
    $email_counts = array();
    foreach ($subscribers as $subscriber) {
        $email = $subscriber->email;
        
        if (!isset($email_counts[$email])) {
            $email_counts[$email] = array(
                'count' => 0,
                'products' => array(),
                'ids' => array(),
                // Obtener el recuento total de productos diferentes desde la base de datos
                'total_products' => isset($all_email_counts[$email]) ? $all_email_counts[$email] : 0
            );
        }
        $email_counts[$email]['count']++;
        $email_counts[$email]['products'][] = $subscriber->product_id;
        $email_counts[$email]['ids'][] = $subscriber->id;
    }
    
    // Aplicar ordenamiento según los parámetros
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'products';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
    
    uasort($email_counts, function($a, $b) use ($orderby, $order, $email_counts) {
        if ($orderby === 'products') {
            // Ordenar por número total de productos diferentes
            $products_a = $a['total_products'];
            $products_b = $b['total_products'];
            $result = $products_a - $products_b;
        } else if ($orderby === 'email') {
            // Ordenar por email alfabéticamente
            $emails_a = key($a);
            $emails_b = key($b);
            $result = strcmp($emails_a, $emails_b);
        } else {
            // Ordenamiento por defecto (número de suscripciones)
            $result = $a['count'] - $b['count'];
        }
        
        // Aplicar dirección del ordenamiento
        return $order === 'asc' ? $result : -$result;
    });
    
    // Usar $email_counts_total para mostrar total de registros 
    // pero mantener la variable $email_counts solo para los actuales
    $email_unique_count = count($email_counts);
    ?>
    
    <!-- Tabla de suscriptores -->
    <table class="waitlist-excel-table widefat fixed striped">
        <thead>
            <tr>
                <th>Email</th>
                <th>Usuario</th>
                <th>Productos</th>
                <th width="100">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="4">No hay suscriptores en la lista de espera.</td>
                </tr>
            <?php else: ?>
                <?php 
                foreach ($email_counts as $email => $subscriber_data): 
                    // Obtener información de usuario si está registrado
                    $user = get_user_by('email', $email);
                    
                    // Obtener los productos para este email
                    $product_ids = $subscriber_data['products'];
                    $unique_product_ids = array_unique($product_ids);
                    $first_product = wc_get_product(reset($unique_product_ids));
                    $product_name = $first_product ? $first_product->get_name() : 'Producto #' . reset($unique_product_ids);
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($email); ?></strong>
                        </td>
                        <td>
                            <?php if ($user): ?>
                                <?php echo esc_html($user->display_name); ?>
                                <?php if ($user->ID): ?>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo get_edit_user_link($user->ID); ?>">Editar usuario</a>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <em>No registrado</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (count($unique_product_ids) > 1): ?>
                                <span class="badge"><?php echo isset($subscriber_data['total_products']) ? $subscriber_data['total_products'] : count($unique_product_ids); ?></span> productos diferentes
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="show-all-products" data-email="<?php echo esc_attr(md5($email)); ?>">Ver todos</a>
                                    </span>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo admin_url('admin.php?page=waitlist&product_id=' . reset($unique_product_ids)); ?>">
                                    <?php echo esc_html($product_name); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="#" class="delete-all-subscriptions button button-small" data-email="<?php echo esc_attr($email); ?>" data-nonce="<?php echo wp_create_nonce('delete_subscriber'); ?>">
                                <span class="dashicons dashicons-trash"></span> Eliminar
                            </a>
                        </td>
                    </tr>
                    <!-- Fila oculta para mostrar todos los productos -->
                    <tr id="products-<?php echo esc_attr(md5($email)); ?>" class="products-detail-row" style="display: none;">
                        <td colspan="4">
                            <div class="products-detail-content">
                                <h4>Productos en lista de espera para <?php echo esc_html($email); ?>:</h4>
                                <?php 
                                // Obtener todos los productos a los que está suscrito este email
                                // Incluso los que no están en la página actual
                                global $wpdb;
                                $table_name = $wpdb->prefix . 'waitlist';
                                $all_product_ids = $wpdb->get_col($wpdb->prepare(
                                    "SELECT DISTINCT product_id FROM $table_name WHERE user_email = %s ORDER BY product_id",
                                    $email
                                ));
                                
                                if (count($all_product_ids) > count($unique_product_ids)) {
                                    echo '<p><em>Mostrando ' . count($all_product_ids) . ' productos en total.</em></p>';
                                }
                                ?>
                                <ul class="products-list">
                                    <?php foreach ($all_product_ids as $pid): 
                                        $p = wc_get_product($pid);
                                        if (!$p) continue;
                                    ?>
                                        <li>
                                            <a href="<?php echo admin_url('admin.php?page=waitlist&product_id=' . $pid); ?>">
                                                <?php echo $p->get_image('thumbnail'); ?> 
                                                <?php echo esc_html($p->get_name()); ?>
                                                <?php 
                                                    // Si es una variación, mostrar los atributos
                                                    if ($p->is_type('variation')) {
                                                        echo ' - ' . $p->get_attribute_summary();
                                                    }
                                                ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Navegación de paginación -->
    <div style="background-color: #f0f0f0; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <p><strong>Depuración:</strong> Total emails únicos: <?php echo $total_items; ?> | 
           Emails en esta página: <?php echo $email_unique_count; ?> |
           Total suscripciones (total BD): <?php echo array_sum($all_email_counts); ?> |
           Total visible en esta página: <?php echo array_sum(array_column($email_counts, 'count')); ?> |
           Total páginas: <?php echo $total_pages; ?> | 
           Página actual: <?php echo $current_page; ?>
        </p>
    </div>
    
    <div class="waitlist-pagination">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total_items; ?> elementos</span>
            <span class="pagination-links">
                <?php
                // Enlace a primera página
                if ($current_page > 1):
                    $first_page_url = add_query_arg('paged', 1, remove_query_arg('paged', $_SERVER['REQUEST_URI']));
                ?>
                <a class="first-page button" href="<?php echo esc_url($first_page_url); ?>">
                    <span class="screen-reader-text">Primera página</span>
                    <span aria-hidden="true">«</span>
                </a>
                <?php else: ?>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                <?php endif; ?>
                
                <?php
                // Enlace a página anterior
                if ($current_page > 1):
                    $prev_page = max(1, $current_page - 1);
                    $prev_page_url = add_query_arg('paged', $prev_page, $_SERVER['REQUEST_URI']);
                ?>
                <a class="prev-page button" href="<?php echo esc_url($prev_page_url); ?>">
                    <span class="screen-reader-text">Página anterior</span>
                    <span aria-hidden="true">‹</span>
                </a>
                <?php else: ?>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                <?php endif; ?>
                
                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text">Página actual</label>
                    <input class="current-page" id="current-page-selector" type="text" name="paged" 
                           value="<?php echo esc_attr($current_page); ?>" size="1" aria-describedby="table-paging">
                    <span class="tablenav-paging-text"> de <span class="total-pages"><?php echo $total_pages; ?></span></span>
                </span>
                
                <?php
                // Enlace a página siguiente
                if ($current_page < $total_pages):
                    $next_page = min($total_pages, $current_page + 1);
                    $next_page_url = add_query_arg('paged', $next_page, $_SERVER['REQUEST_URI']);
                ?>
                <a class="next-page button" href="<?php echo esc_url($next_page_url); ?>">
                    <span class="screen-reader-text">Página siguiente</span>
                    <span aria-hidden="true">›</span>
                </a>
                <?php else: ?>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                <?php endif; ?>
                
                <?php
                // Enlace a última página
                if ($current_page < $total_pages):
                    $last_page_url = add_query_arg('paged', $total_pages, $_SERVER['REQUEST_URI']);
                ?>
                <a class="last-page button" href="<?php echo esc_url($last_page_url); ?>">
                    <span class="screen-reader-text">Última página</span>
                    <span aria-hidden="true">»</span>
                </a>
                <?php else: ?>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Mostrar/ocultar productos
    $('.show-all-products').on('click', function(e) {
        e.preventDefault();
        var emailHash = $(this).data('email');
        $('#products-' + emailHash).toggle();
    });
    
    // Eliminar todas las suscripciones de un email
    $('.delete-all-subscriptions').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('¿Estás seguro de eliminar todas las suscripciones para este email?')) {
            return;
        }
        
        var email = $(this).data('email');
        var nonce = $(this).data('nonce');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'waitlist_delete_email_subscriptions',
                email: email,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $row.next('.products-detail-row').remove();
                        $row.remove();
                    });
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error al procesar la solicitud');
            }
        });
    });
});
</script>

<style>
    .waitlist-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        max-width: 100%;
        padding: 20px 0;
    }
    
    .waitlist-excel-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        background: #fff;
    }
    
    .waitlist-excel-table thead th {
        background-color: #0066CC;
        color: white;
        font-weight: bold;
        text-align: left;
        padding: 12px 10px;
    }
    
    .waitlist-excel-table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
    }
    
    .waitlist-actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
    }
    
    .waitlist-search-box .search-form {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .waitlist-search-input {
        min-width: 300px;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .excel-export-button {
        background-color: #217346 !important;
        border-color: #165731 !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        gap: 5px;
    }
    
    .excel-export-button:hover {
        background-color: #165731 !important;
    }
    
    .products-detail-row {
        background-color: #f9f9f9;
    }
    
    .products-detail-content {
        background: #f9f9f9;
        padding: 15px;
        border: 1px solid #e5e5e5;
        margin: 5px 0;
        border-radius: 4px;
    }
    
    .products-detail-content h4 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #23282d;
        border-bottom: 1px solid #eee;
        padding-bottom: 8px;
    }
    
    .products-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 12px;
        margin: 0;
    }
    
    .products-list li {
        margin: 0;
        padding: 10px;
        border: 1px solid #eee;
        background: #fff;
        list-style: none;
        border-radius: 4px;
        transition: transform 0.2s;
    }
    
    .products-list li:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .products-list li a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #0073aa;
    }
    
    .products-list li img {
        margin-right: 10px;
        width: 50px;
        height: auto;
        border-radius: 4px;
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
    
    .delete-all-subscriptions {
        color: #a00 !important;
        border-color: #a00 !important;
    }
    
    .delete-all-subscriptions:hover {
        background-color: #a00 !important;
        color: #fff !important;
    }
    
    .badge {
        display: inline-block;
        min-width: 10px;
        padding: 3px 7px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        background-color: #0073aa;
        border-radius: 10px;
        margin-right: 5px;
    }
    
    .subscriptions-count {
        display: inline-block;
        background-color: #0073aa;
        color: white;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        line-height: 28px;
        text-align: center;
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
            margin: 0 0 10px 0;
        }
        
        .waitlist-search-box .search-form {
            flex-wrap: wrap;
        }
        
        .waitlist-search-input {
            width: 100%;
            min-width: auto;
        }
    }
    
    /* Estilos para encabezados blancos */
    .waitlist-excel-table thead th {
        background-color: #0066CC !important;
        color: #FFFFFF !important;
        font-weight: bold;
    }
    
    /* Ajustar el estilo de la tabla */
    .waitlist-excel-table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 15px;
    }
    
    .waitlist-excel-table th, 
    .waitlist-excel-table td {
        padding: 8px;
        border: 1px solid #ddd;
    }
    
    .waitlist-excel-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    
    /* Ajustes para la paginación */
    .waitlist-pagination {
        margin-top: 20px;
        padding: 15px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        display: flex;
        justify-content: flex-end;
    }
    
    .tablenav-pages {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .displaying-num {
        font-weight: bold;
        color: #555;
    }
    
    .pagination-links {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .tablenav-pages-navspan.button,
    .pagination-links a.button {
        padding: 0 10px;
        line-height: 30px;
        height: 30px;
        min-width: 30px;
        text-align: center;
    }
    
    .pagination-links a.button {
        background-color: #0073aa;
        color: white;
        border-color: #006799;
    }
    
    .pagination-links a.button:hover {
        background-color: #006799;
    }
    
    .paging-input {
        margin: 0 5px;
        display: flex;
        align-items: center;
    }
    
    .current-page {
        width: 40px;
        height: 30px;
        text-align: center;
    }
    
    .waitlist-sort-box {
        margin: 0 15px;
        display: flex;
        align-items: center;
    }
    
    .waitlist-sort-box label {
        margin-right: 8px;
        font-weight: 500;
    }
    
    .waitlist-sort-box select {
        margin-right: 5px;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
</style>