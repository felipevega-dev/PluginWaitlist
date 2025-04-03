<div class="wrap">
    <h1>
        Suscriptores para <?php echo esc_html($product->get_name()); ?>
        <a href="<?php echo admin_url('admin.php?page=waitlist'); ?>" class="page-title-action">← Volver a la lista</a>
    </h1>
    
    <div class="waitlist-product-info">
        <div class="waitlist-product-thumbnail">
            <?php echo $product->get_image('thumbnail'); ?>
        </div>
        <div class="waitlist-product-details">
            <p><strong>Producto:</strong> <?php echo esc_html($product->get_name()); ?></p>
            <p><strong>SKU:</strong> <?php echo $product->get_sku() ? esc_html($product->get_sku()) : 'N/A'; ?></p>
            <p><strong>Estado de stock:</strong> <?php echo ucfirst($product->get_stock_status()); ?></p>
            <p><strong>Total de suscriptores:</strong> <?php echo count($subscribers); ?></p>
            <?php if ($product->is_type('variable')): ?>
            <p><strong>Tipo:</strong> Producto variable</p>
            <?php 
                $variation_attributes = $product->get_variation_attributes();
                if (!empty($variation_attributes)): 
                    foreach ($variation_attributes as $attribute_name => $attribute_values): 
                        $attribute_label = wc_attribute_label($attribute_name);
            ?>
                <p><strong><?php echo esc_html($attribute_label); ?>:</strong> 
                    <?php echo esc_html(implode(', ', $attribute_values)); ?>
                </p>
            <?php 
                    endforeach;
                endif;
            ?>
            <?php else: ?>
            <p><strong>Tipo:</strong> Producto simple</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div class="waitlist-actions-bar">
        <!-- Barra de búsqueda -->
        <div class="waitlist-search-box">
            <input type="text" id="waitlist-search-input" placeholder="Buscar por email..." class="waitlist-search-input">
        </div>
    </div>
    
    <h2>Lista de Suscriptores</h2>
    
    <!-- Tabla de suscriptores -->
    <table class="wp-list-table widefat fixed striped" id="waitlist-subscribers-table">
        <thead>
            <tr>
                <th>Email</th>
                <th width="180">Fecha de suscripción</th>
                <?php if (!empty($variations_data) && count($variations_data) > 1): ?>
                <th width="180">Variación</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="<?php echo (!empty($variations_data) && count($variations_data) > 1) ? '3' : '2'; ?>">No hay suscriptores para este producto.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($subscribers as $subscriber): ?>
                    <?php
                    // Obtener información de la variación si está disponible
                    $variation_info = '';
                    if (!empty($variations_data)) {
                        foreach ($variations_data as $variation) {
                            if (isset($variation->detailed_subscribers)) {
                                foreach ($variation->detailed_subscribers as $var_subscriber) {
                                    if ($var_subscriber->email === $subscriber->email && $var_subscriber->id === $subscriber->id) {
                                        $variation_info = isset($variation->attributes) ? $variation->attributes : '';
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html($subscriber->email); ?></td>
                        <td><?php 
                            // Formatear la fecha si está en timestamp Unix
                            if (is_numeric($subscriber->created_at) && $subscriber->created_at > 946684800) {
                                echo date('Y-m-d H:i:s', $subscriber->created_at);
                            } else if (strtotime($subscriber->created_at) > 946684800) {
                                echo date('Y-m-d H:i:s', strtotime($subscriber->created_at));
                            } else {
                                echo 'Fecha no disponible';
                            }
                        ?></td>
                        <?php if (!empty($variations_data) && count($variations_data) > 1): ?>
                        <td><?php echo esc_html($variation_info); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .waitlist-product-info {
        display: flex;
        margin-bottom: 20px;
        padding: 15px;
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    .waitlist-product-thumbnail {
        margin-right: 20px;
    }
    .waitlist-product-details {
        flex: 1;
    }
    .waitlist-product-details p {
        margin: 5px 0;
    }
    .waitlist-search-input {
        width: 300px;
        padding: 6px 10px;
        margin-bottom: 10px;
    }
    .excel-export-button {
        display: flex;
        align-items: center;
    }
    .excel-export-button .dashicons {
        margin-right: 5px;
    }
    
    .waitlist-actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Búsqueda en tiempo real
    $('#waitlist-search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#waitlist-subscribers-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>