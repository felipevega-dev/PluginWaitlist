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
            
            <div class="waitlist-email-variables-guide">
                <h3>Variables disponibles para personalizar el correo</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Variable</th>
                            <th>Descripción</th>
                            <th>Ejemplo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>{product_name}</code></td>
                            <td>Nombre del producto que vuelve a estar disponible</td>
                            <td>Polera Manga Corta</td>
                        </tr>
                        <tr>
                            <td><code>{product_url}</code></td>
                            <td>URL del producto en la tienda</td>
                            <td>https://tutienda.com/producto/polera-manga-corta</td>
                        </tr>
                        <tr>
                            <td><code>{store_name}</code></td>
                            <td>Nombre de la tienda</td>
                            <td><?php echo get_bloginfo('name'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{store_url}</code></td>
                            <td>URL de la tienda</td>
                            <td><?php echo get_bloginfo('url'); ?></td>
                        </tr>
                        <tr>
                            <td><code>{customer_email}</code></td>
                            <td>Email del cliente</td>
                            <td>cliente@ejemplo.com</td>
                        </tr>
                        <tr>
                            <td><code>{date}</code></td>
                            <td>Fecha actual</td>
                            <td><?php echo date_i18n(get_option('date_format')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
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
                        <?php
                        $settings = array(
                            'media_buttons' => true,
                            'textarea_name' => 'email_message',
                            'textarea_rows' => 15,
                            'teeny' => false
                        );
                        wp_editor(get_option('waitlist_email_message', '<p>Hola,</p><p>Nos complace informarte que <strong>{product_name}</strong> ya está disponible en nuestra tienda.</p><p>Puedes verlo aquí: <a href="{product_url}">Ver producto</a></p><p>Saludos,<br>{store_name}</p>'), 'email_message', $settings);
                        ?>
                        <p class="description">
                            Mensaje del correo electrónico enviado a los suscriptores.
                            Puede incluir HTML para dar formato al mensaje.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_logo">Logo para los correos</label>
                    </th>
                    <td>
                        <div class="waitlist-logo-uploader">
                            <?php
                            $logo_url = get_option('waitlist_email_logo', '');
                            ?>
                            <div class="logo-preview-wrapper" style="margin-bottom: 10px;">
                                <?php if (!empty($logo_url)): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo de email" style="max-width: 300px; max-height: 100px;" />
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="email_logo" id="email_logo" value="<?php echo esc_attr($logo_url); ?>" />
                            <button type="button" class="button upload-logo-button">Seleccionar logo</button>
                            <?php if (!empty($logo_url)): ?>
                                <button type="button" class="button remove-logo-button">Quitar logo</button>
                            <?php endif; ?>
                            <p class="description">
                                Logo que se mostrará en la parte superior del correo. Tamaño recomendado: 300x100 px.
                            </p>
                        </div>
                        <script>
                            jQuery(document).ready(function($) {
                                // Para el selector de logo
                                var logoUploader;
                                
                                $('.upload-logo-button').click(function(e) {
                                    e.preventDefault();
                                    
                                    if (logoUploader) {
                                        logoUploader.open();
                                        return;
                                    }
                                    
                                    logoUploader = wp.media({
                                        title: 'Seleccionar logo para correos',
                                        button: {
                                            text: 'Usar este logo'
                                        },
                                        multiple: false
                                    });
                                    
                                    logoUploader.on('select', function() {
                                        var attachment = logoUploader.state().get('selection').first().toJSON();
                                        $('#email_logo').val(attachment.url);
                                        $('.logo-preview-wrapper').html('<img src="' + attachment.url + '" alt="Logo de email" style="max-width: 300px; max-height: 100px;" />');
                                        $('.upload-logo-button').after('<button type="button" class="button remove-logo-button">Quitar logo</button>');
                                    });
                                    
                                    logoUploader.open();
                                });
                                
                                $(document).on('click', '.remove-logo-button', function(e) {
                                    e.preventDefault();
                                    $('#email_logo').val('');
                                    $('.logo-preview-wrapper').html('');
                                    $(this).remove();
                                });
                            });
                        </script>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_color_header">Color de encabezado</label>
                    </th>
                    <td>
                        <input type="color" id="email_color_header" name="email_color_header" value="<?php echo esc_attr(get_option('waitlist_email_color_header', '#0066CC')); ?>">
                        <p class="description">
                            Color del encabezado en los correos electrónicos.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_color_button">Color de botones</label>
                    </th>
                    <td>
                        <input type="color" id="email_color_button" name="email_color_button" value="<?php echo esc_attr(get_option('waitlist_email_color_button', '#4CAF50')); ?>">
                        <p class="description">
                            Color de los botones en los correos electrónicos.
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
                <tr>
                    <th scope="row">
                        <label for="email_preview">Vista previa del correo</label>
                    </th>
                    <td>
                        <button type="button" class="button button-secondary email-preview-button">Vista previa del correo</button>
                        <p class="description">
                            Ver cómo se verá el correo electrónico con la configuración actual.
                        </p>
                        <div id="email-preview-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4);">
                            <div style="background-color:#fefefe; margin:5% auto; padding:20px; border:1px solid #888; width:80%; max-width:800px; position:relative;">
                                <span style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;" class="close-preview">&times;</span>
                                <h3>Vista previa del correo</h3>
                                <iframe id="email-preview-frame" style="width:100%; height:500px; border:1px solid #ddd;"></iframe>
                            </div>
                        </div>
                        <script>
                            jQuery(document).ready(function($) {
                                $('.email-preview-button').click(function() {
                                    var logo = $('#email_logo').val();
                                    var header_color = $('#email_color_header').val();
                                    var button_color = $('#email_color_button').val();
                                    var message = tinyMCE.get('email_message') ? tinyMCE.get('email_message').getContent() : $('#email_message').val();
                                    
                                    // Reemplazar variables de muestra
                                    message = message.replace(/{product_name}/g, 'Producto de Ejemplo');
                                    message = message.replace(/{product_url}/g, '<?php echo esc_js(get_bloginfo('url')); ?>');
                                    message = message.replace(/{store_name}/g, '<?php echo esc_js(get_bloginfo('name')); ?>');
                                    message = message.replace(/{store_url}/g, '<?php echo esc_js(get_bloginfo('url')); ?>');
                                    message = message.replace(/{customer_email}/g, 'cliente@ejemplo.com');
                                    message = message.replace(/{date}/g, '<?php echo date_i18n(get_option('date_format')); ?>');
                                    
                                    // Crear una plantilla simple de email
                                    var template = `
                                        <html>
                                        <head>
                                            <style>
                                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                                            .container { max-width: 600px; margin: 0 auto; }
                                            .header { background-color: ${header_color}; padding: 20px; text-align: center; }
                                            .header img { max-width: 200px; max-height: 80px; }
                                            .content { padding: 20px; background-color: #fff; }
                                            .footer { padding: 20px; text-align: center; font-size: 12px; color: #777; background-color: #f7f7f7; }
                                            .button { display: inline-block; padding: 10px 20px; background-color: ${button_color}; color: white; text-decoration: none; border-radius: 4px; }
                                            </style>
                                        </head>
                                        <body>
                                            <div class="container">
                                                <div class="header">
                                                    ${logo ? '<img src="' + logo + '" alt="Logo">' : '<h1 style="color: white;"><?php echo esc_js(get_bloginfo('name')); ?></h1>'}
                                                </div>
                                                <div class="content">
                                                    ${message}
                                                </div>
                                                <div class="footer">
                                                    &copy; <?php echo date('Y'); ?> <?php echo esc_js(get_bloginfo('name')); ?>. Todos los derechos reservados.
                                                </div>
                                            </div>
                                        </body>
                                        </html>
                                    `;
                                    
                                    // Mostrar la vista previa
                                    $('#email-preview-modal').show();
                                    var iframe = document.getElementById('email-preview-frame');
                                    iframe = iframe.contentWindow || (iframe.contentDocument.document || iframe.contentDocument);
                                    iframe.document.open();
                                    iframe.document.write(template);
                                    iframe.document.close();
                                });
                                
                                $('.close-preview').click(function() {
                                    $('#email-preview-modal').hide();
                                });
                                
                                $(window).click(function(event) {
                                    if (event.target == document.getElementById('email-preview-modal')) {
                                        $('#email-preview-modal').hide();
                                    }
                                });
                            });
                        </script>
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

<!-- Script para selector de colores, carga de logo y vista previa del email -->
<script>
jQuery(document).ready(function($) {
    // Inicializar selectores de color
    $('.color-picker').wpColorPicker();
    
    // Inicializar Media Uploader para el logo
    var mediaUploader;
    $('#upload_logo_button').click(function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: 'Seleccionar Logo',
            button: {
                text: 'Usar este logo'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#email_logo').val(attachment.url);
            $('#logo_preview').attr('src', attachment.url).show();
            $('#remove_logo_button').show();
        });
        
        mediaUploader.open();
    });
    
    // Botón para eliminar logo
    $('#remove_logo_button').click(function(e) {
        e.preventDefault();
        $('#email_logo').val('');
        $('#logo_preview').attr('src', '').hide();
        $(this).hide();
    });
    
    // Mostrar logo si ya hay uno guardado
    if ($('#email_logo').val()) {
        $('#logo_preview').attr('src', $('#email_logo').val()).show();
        $('#remove_logo_button').show();
    } else {
        $('#logo_preview').hide();
        $('#remove_logo_button').hide();
    }
    
    // Prevenir que el formulario se envíe al hacer clic en vista previa
    $('#preview_email_button').click(function(e) {
        e.preventDefault();
        
        // Recopilar datos para la vista previa
        var logoUrl = $('#email_logo').val();
        var headerColor = $('#email_color_header').val() || '#0066CC';
        var buttonColor = $('#email_color_button').val() || '#4CAF50';
        var emailMessage = $('#email_message').val();
        var storeName = '<?php echo esc_js(get_bloginfo('name')); ?>';
        
        // Crear plantilla de correo con HTML y CSS
        var emailTemplate = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>Vista Previa Email</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
                    color: #333333;
                }
                .email-container {
                    max-width: 100%;
                    margin: 0 auto;
                    border: 1px solid #dddddd;
                    border-radius: 8px;
                    overflow: hidden;
                }
                .email-header {
                    background-color: ${headerColor};
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
                    background-color: ${buttonColor};
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
                    ${logoUrl ? '<div class="logo-container"><img src="' + logoUrl + '" alt="' + storeName + '" class="logo"></div>' : ''}
                    <h1>${storeName}</h1>
                </div>
                <div class="email-body">
                    ${emailMessage.replace('{product_name}', 'Producto de Ejemplo').replace('{product_url}', '#').replace('{store_name}', storeName).replace('{store_url}', '#').replace('{customer_email}', 'cliente@ejemplo.com').replace('{date}', '<?php echo date_i18n(get_option('date_format')); ?>')}
                    <div style="text-align: center;">
                        <a href="#" class="button">Ver Producto</a>
                    </div>
                </div>
                <div class="email-footer">
                    &copy; <?php echo date('Y'); ?> ${storeName}. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>`;
        
        // Mostrar la vista previa en el modal
        $('#email_preview_modal_content').html(emailTemplate);
        $('#email_preview_modal').fadeIn();
    });
    
    // Cerrar el modal al hacer clic fuera o en el botón cerrar
    $('.modal-close, .modal-overlay').click(function() {
        $('#email_preview_modal').fadeOut();
    });
});
</script>

<!-- Estilo para el modal de vista previa -->
<style>
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
}

.modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    width: 70%;
    max-width: 800px;
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
    animation-name: animatetop;
    animation-duration: 0.4s;
    border-radius: 8px;
    overflow: hidden;
}

.modal-header {
    padding: 15px;
    background-color: #0066CC;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-close {
    color: white;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

@keyframes animatetop {
    from {top: -300px; opacity: 0}
    to {top: 0; opacity: 1}
}
</style>

<!-- Modal para la vista previa del email -->
<div id="email_preview_modal" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Vista Previa del Email</h2>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="email_preview_modal_content"></div>
        </div>
    </div>
</div>