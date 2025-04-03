<div class="wrap waitlist-container">
    <h1>Configuración de Lista de Espera</h1>
    
    <div class="notice notice-info">
        <p><strong>Nota:</strong> Este sistema ahora gestiona de forma independiente toda la funcionalidad de lista de espera. La personalización de los correos electrónicos y otras opciones están disponibles aquí.</p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('waitlist_settings', 'waitlist_settings_nonce'); ?>
        
        <div class="waitlist-settings-section">
            <h2>Configuración de Visualización</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="show_variation_count">Mostrar conteo de variaciones</label>
                    </th>
                    <td>
                        <input type="checkbox" id="show_variation_count" name="show_variation_count" value="1" <?php checked(get_option('waitlist_show_variation_count', '1'), '1'); ?>>
                        <p class="description">
                            Mostrar el número de variaciones en la tabla de productos.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="show_subscriber_emails">Mostrar emails en la tabla</label>
                    </th>
                    <td>
                        <input type="checkbox" id="show_subscriber_emails" name="show_subscriber_emails" value="1" <?php checked(get_option('waitlist_show_subscriber_emails', '1'), '1'); ?>>
                        <p class="description">
                            Mostrar los emails de los suscriptores en la tabla de variaciones.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_emails_display">Máximo de emails a mostrar</label>
                    </th>
                    <td>
                        <input type="number" id="max_emails_display" name="max_emails_display" value="<?php echo esc_attr(get_option('waitlist_max_emails_display', '5')); ?>" min="1" max="20" class="small-text">
                        <p class="description">
                            Número máximo de emails a mostrar en la tabla antes de resumir.
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="waitlist-settings-section">
            <h2>Configuración de Exportación</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="excel_header_color">Color de cabecera en Excel</label>
                    </th>
                    <td>
                        <input type="color" id="excel_header_color" name="excel_header_color" value="<?php echo esc_attr(get_option('waitlist_excel_header_color', '#0066CC')); ?>" class="color-picker">
                        <p class="description">
                            Color de fondo para las cabeceras en los archivos Excel exportados.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="excel_alternate_color">Color de filas alternas</label>
                    </th>
                    <td>
                        <input type="color" id="excel_alternate_color" name="excel_alternate_color" value="<?php echo esc_attr(get_option('waitlist_excel_alternate_color', '#F2F2F2')); ?>" class="color-picker">
                        <p class="description">
                            Color de fondo para las filas alternas en los archivos Excel exportados.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="include_timestamp">Incluir fecha y hora en nombre de archivo</label>
                    </th>
                    <td>
                        <input type="checkbox" id="include_timestamp" name="include_timestamp" value="1" <?php checked(get_option('waitlist_include_timestamp', '1'), '1'); ?>>
                        <p class="description">
                            Añadir fecha y hora al nombre del archivo Excel exportado.
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="waitlist-settings-section">
            <h2>Configuración de Notificaciones por Email</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="email_subject">Asunto del correo</label>
                    </th>
                    <td>
                        <input type="text" id="email_subject" name="email_subject" value="<?php echo esc_attr(get_option('waitlist_email_subject', '¡{product_name} ya está disponible!')); ?>" class="regular-text">
                        <p class="description">
                            Asunto del correo electrónico enviado a los suscriptores cuando un producto vuelve a estar disponible.
                            Usa {product_name} para incluir el nombre del producto.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_message">Mensaje del correo</label>
                    </th>
                    <td>
                        <textarea id="email_message" name="email_message" class="large-text" rows="10"><?php echo esc_textarea(get_option('waitlist_email_message', 'Hola, {product_name} ya está disponible en nuestra tienda. ¡No te lo pierdas! Puedes verlo aquí: {product_url}')); ?></textarea>
                        <p class="description">
                            Mensaje del correo electrónico enviado a los suscriptores.
                            Usa {product_name} para el nombre del producto y {product_url} para el enlace al producto.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_from_name">Nombre del remitente</label>
                    </th>
                    <td>
                        <input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr(get_option('waitlist_email_from_name', get_bloginfo('name'))); ?>" class="regular-text">
                        <p class="description">
                            Nombre que aparecerá como remitente en los correos enviados.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_from_address">Email del remitente</label>
                    </th>
                    <td>
                        <input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr(get_option('waitlist_email_from_address', get_bloginfo('admin_email'))); ?>" class="regular-text">
                        <p class="description">
                            Dirección de correo electrónico del remitente.
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="waitlist-settings-section">
            <h2>Migración desde YITH WooCommerce Waitlist</h2>
            <p>Si estabas utilizando YITH WooCommerce Waitlist, puedes migrar los suscriptores a tu propio sistema con un solo clic.</p>
            
            <div class="waitlist-migration-box">
                <p>
                    <strong>Nota importante:</strong> Este proceso migra todos los suscriptores de YITH a tu sistema propio. 
                    Los datos originales en YITH se mantendrán, pero en adelante es recomendable utilizar solo este sistema.
                </p>
                <p>La migración puede tomar tiempo dependiendo del número de suscriptores.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('waitlist_migration', 'waitlist_migration_nonce'); ?>
                    <input type="submit" name="migrate_from_yith" class="button button-primary" value="Migrar datos desde YITH">
                </form>
            </div>
        </div>
        
        <div class="waitlist-settings-section">
            <h2>Opciones de Importación</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="import_file">Importar suscriptores desde CSV</label>
                    </th>
                    <td>
                        <input type="file" id="import_file" name="import_file">
                        <p class="description">
                            Selecciona un archivo CSV con los suscriptores a importar. El archivo debe tener las columnas 'product_id' y 'email'.
                        </p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="import_subscribers" id="import_subscribers" class="button button-secondary" value="Importar Suscriptores">
            </p>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Cambios">
        </p>
    </form>
</div>

<style>
    .waitlist-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        max-width: 800px;
    }
    
    .waitlist-settings-section {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
    }
    
    .waitlist-settings-section h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #23282d;
    }
    
    .color-picker {
        width: 70px;
        height: 30px;
        padding: 0;
        border: 1px solid #ddd;
    }
</style>