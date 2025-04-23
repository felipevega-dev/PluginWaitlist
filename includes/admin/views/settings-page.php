<?php
// Protección contra acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Comprobar si se han guardado las opciones
$settings_updated = false;
if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
    $settings_updated = true;
}
?>

<div class="wrap">
    <h1>Lista de Espera - Configuración</h1>
    
    <?php if ($settings_updated): ?>
        <div class="notice notice-success is-dismissible">
            <p>¡Configuración guardada con éxito!</p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php">
        <?php settings_fields('waitlist_settings_group'); ?>
        
        <div class="waitlist-settings-section">
            <h2>Configuración General</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="max_emails_display">Máximo de emails a mostrar</label>
                    </th>
                    <td>
                        <input type="number" id="max_emails_display" name="max_emails_display" value="<?php echo esc_attr(get_option('waitlist_max_emails_display', '50')); ?>" min="1" max="500" class="small-text">
                        <p class="description">
                            Número máximo de emails que se mostrarán en la tabla de suscriptores. Un número muy alto puede ralentizar la página.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="show_subscriber_count">Mostrar contador de suscriptores</label>
                    </th>
                    <td>
                        <input type="checkbox" id="show_subscriber_count" name="show_subscriber_count" value="1" <?php checked(get_option('waitlist_show_subscriber_count', '1'), '1'); ?>>
                        <p class="description">
                            Mostrar el número total de suscriptores en la columna de WooCommerce.
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="waitlist-settings-section">
            <h2>Configuración de Exportación a Excel</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="excel_logo">Logo para los archivos Excel</label>
                    </th>
                    <td>
                        <div class="waitlist-logo-uploader">
                            <?php
                            $logo_url = get_option('waitlist_excel_logo', '');
                            ?>
                            <div class="logo-preview-wrapper" style="margin-bottom: 10px;">
                                <?php if (!empty($logo_url)): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo de Excel" style="max-width: 300px; max-height: 100px;" />
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="excel_logo" id="excel_logo" value="<?php echo esc_attr($logo_url); ?>" />
                            <button type="button" class="button upload-excel-logo-button">Seleccionar logo</button>
                            <?php if (!empty($logo_url)): ?>
                                <button type="button" class="button remove-excel-logo-button">Quitar logo</button>
                            <?php endif; ?>
                            <p class="description">
                                Logo que se mostrará en los archivos Excel exportados. Tamaño recomendado: 300x100 px.
                            </p>
                        </div>
                        <script>
                            jQuery(document).ready(function($) {
                                // Para el selector de logo Excel
                                var excelLogoUploader;
                                
                                $('.upload-excel-logo-button').click(function(e) {
                                    e.preventDefault();
                                    
                                    if (excelLogoUploader) {
                                        excelLogoUploader.open();
                                        return;
                                    }
                                    
                                    excelLogoUploader = wp.media({
                                        title: 'Seleccionar logo para Excel',
                                        button: {
                                            text: 'Usar este logo'
                                        },
                                        multiple: false
                                    });
                                    
                                    excelLogoUploader.on('select', function() {
                                        var attachment = excelLogoUploader.state().get('selection').first().toJSON();
                                        $('#excel_logo').val(attachment.url);
                                        $('.logo-preview-wrapper').html('<img src="' + attachment.url + '" alt="Logo de Excel" style="max-width: 300px; max-height: 100px;" />');
                                        $('.upload-excel-logo-button').after('<button type="button" class="button remove-excel-logo-button">Quitar logo</button>');
                                    });
                                    
                                    excelLogoUploader.open();
                                });
                                
                                $(document).on('click', '.remove-excel-logo-button', function(e) {
                                    e.preventDefault();
                                    $('#excel_logo').val('');
                                    $('.logo-preview-wrapper').html('');
                                    $(this).remove();
                                });
                            });
                        </script>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="excel_color_header">Color de cabecera</label>
                    </th>
                    <td>
                        <input type="color" id="excel_color_header" name="excel_color_header" value="<?php echo esc_attr(get_option('waitlist_excel_color_header', '#D50000')); ?>">
                        <p class="description">
                            Color de fondo de la cabecera en los archivos Excel.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="excel_color_text">Color del texto</label>
                    </th>
                    <td>
                        <input type="color" id="excel_color_text" name="excel_color_text" value="<?php echo esc_attr(get_option('waitlist_excel_color_text', '#FFFFFF')); ?>">
                        <p class="description">
                            Color del texto en la cabecera de los archivos Excel.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="excel_filename">Nombre del archivo</label>
                    </th>
                    <td>
                        <input type="text" id="excel_filename" name="excel_filename" value="<?php echo esc_attr(get_option('waitlist_excel_filename', 'lista-de-espera-{product_name}-{date}')); ?>" class="regular-text">
                        <p class="description">
                            Formato del nombre del archivo Excel. Puedes usar {product_name} para el nombre del producto y {date} para la fecha actual.
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
                        <label for="enable_email_notifications">Activar notificaciones</label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_email_notifications" name="enable_email_notifications" value="1" <?php checked(get_option('waitlist_enable_email_notifications', '1'), '1'); ?>>
                        <p class="description">
                            Activar/desactivar el envío automático de notificaciones cuando un producto vuelve a estar disponible.
                        </p>
                    </td>
                </tr>
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
                        
                        // Definir la plantilla por defecto
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
        <div style="background-color: #f5f8ff; border-left: 4px solid #D50000; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
            <h3 style="color: #D50000; margin: 0 0 10px 0; font-size: 20px;">{product_name}</h3>
            <p style="margin: 0; color: #666;">¡No esperes más para conseguirlo!</p>
        </div>

        <!-- Botón de Acción -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{product_url}" style="display: inline-block; background-color: #D50000; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px;">Ver Producto</a>
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
            <a href="{store_url}" style="color: #D50000; text-decoration: none;">Visitar nuestra tienda</a>
        </p>
    </div>
</div>';

                        // Obtener el contenido guardado o usar la plantilla por defecto
                        $email_content = get_option('waitlist_email_message', $default_email_template);
                        
                        // Asegurarse de que nunca sea vacío
                        if (empty($email_content)) {
                            $email_content = $default_email_template;
                        }

                        wp_editor($email_content, 'email_message', $settings);
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
                        <input type="color" id="email_color_header" name="email_color_header" value="<?php echo esc_attr(get_option('waitlist_email_color_header', '#D50000')); ?>">
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
                        <input type="color" id="email_color_button" name="email_color_button" value="<?php echo esc_attr(get_option('waitlist_email_color_button', '#D50000')); ?>">
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
                        <label for="email_test">Enviar email de prueba</label>
                    </th>
                    <td>
                        <input type="email" id="test_email_address" placeholder="Ingresa un correo para enviar prueba" class="regular-text">
                        <button type="button" class="button button-secondary send-test-email">Enviar prueba</button>
                        <p class="description">
                            Envía un correo de prueba con la configuración actual para verificar que funciona correctamente.
                        </p>
                        <div id="test-email-result" style="margin-top: 10px; display: none;"></div>
                        <script>
                            jQuery(document).ready(function($) {
                                $('.send-test-email').on('click', function() {
                                    var testEmail = $('#test_email_address').val();
                                    if (!testEmail || !testEmail.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                                        $('#test-email-result').html('<div class="notice notice-error inline"><p>Por favor, ingresa un correo electrónico válido.</p></div>').show();
                                        return;
                                    }
                                    
                                    $(this).prop('disabled', true).text('Enviando...');
                                    $('#test-email-result').html('<div class="notice notice-info inline"><p>Enviando correo de prueba...</p></div>').show();
                                    
                                    // Recopilar datos del formulario para la plantilla del correo
                                    var logo = $('#email_logo').val();
                                    var header_color = $('#email_color_header').val();
                                    var button_color = $('#email_color_button').val();
                                    var subject = $('#email_subject').val();
                                    var message = tinyMCE.get('email_message') ? tinyMCE.get('email_message').getContent() : $('#email_message').val();
                                    var from_name = $('#email_from_name').val();
                                    var from_email = $('#email_from_address').val();
                                    
                                    // Enviar solicitud AJAX
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'waitlist_send_test_email',
                                            email: testEmail,
                                            subject: subject,
                                            message: message,
                                            logo: logo,
                                            header_color: header_color,
                                            button_color: button_color,
                                            from_name: from_name,
                                            from_email: from_email,
                                            nonce: '<?php echo wp_create_nonce("waitlist_test_email"); ?>'
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                $('#test-email-result').html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                                            } else {
                                                $('#test-email-result').html('<div class="notice notice-error inline"><p>Error: ' + response.data + '</p></div>');
                                            }
                                        },
                                        error: function() {
                                            $('#test-email-result').html('<div class="notice notice-error inline"><p>Error al enviar la solicitud.</p></div>');
                                        },
                                        complete: function() {
                                            $('.send-test-email').prop('disabled', false).text('Enviar prueba');
                                        }
                                    });
                                });
                            });
                        </script>
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
        
        <?php 
        // Comprobar si la opción de ocultar migración está activa
        $hide_migration = get_option('waitlist_hide_migration', '0');
        if ($hide_migration !== '1'):
        ?>
        <div class="waitlist-settings-section">
            <h2>Migración desde YITH WooCommerce Waitlist</h2>
            <p>Si estabas utilizando YITH WooCommerce Waitlist, puedes migrar los suscriptores a tu propio sistema con un solo clic.</p>
            
            <div class="waitlist-migration-box">
                <p>
                    <strong>Nota importante:</strong> Este proceso migra todos los suscriptores de YITH a tu sistema propio. 
                    Los datos originales en YITH se mantendrán, pero en adelante es recomendable utilizar solo este sistema.
                </p>
                <p>La migración puede tomar tiempo dependiendo del número de suscriptores.</p>
                
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="#" id="migrate-yith-link" class="button button-primary">Migrar datos desde YITH</a>
                    <label><input type="checkbox" name="hide_migration" value="1"> Ocultar esta sección después de guardar</label>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="waitlist-settings-section">
            <h2>Opciones de Importación</h2>
            <p>Importa suscriptores desde un archivo CSV.</p>
            
            <div class="waitlist-import-box">
                <p>
                    <strong>Formato del CSV:</strong> El archivo debe tener encabezados y contener las columnas <code>product_id</code>, <code>variation_id</code> (opcional, usar 0 si no aplica), y <code>email</code>.
                </p>
                <p>Ejemplo:</p>
                <pre>product_id,variation_id,email
123,456,cliente1@ejemplo.com
123,0,cliente2@ejemplo.com</pre>
                
                <div class="import-form">
                    <input type="file" id="csv-import-file" accept=".csv" />
                    <button type="button" class="button button-secondary" id="import-csv-button">Importar CSV</button>
                </div>
                <div id="import-result" style="margin-top: 15px;"></div>
                
                <script>
                    jQuery(document).ready(function($) {
                        $('#import-csv-button').click(function() {
                            var fileInput = $('#csv-import-file')[0];
                            
                            if (fileInput.files.length === 0) {
                                $('#import-result').html('<div class="notice notice-error inline"><p>Por favor, selecciona un archivo CSV.</p></div>');
                                return;
                            }
                            
                            var file = fileInput.files[0];
                            var formData = new FormData();
                            formData.append('action', 'waitlist_import_csv');
                            formData.append('csv_file', file);
                            formData.append('nonce', '<?php echo wp_create_nonce("waitlist_import_csv"); ?>');
                            
                            $('#import-result').html('<div class="notice notice-info inline"><p>Importando suscriptores... Por favor, espera.</p></div>');
                            
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    if (response.success) {
                                        $('#import-result').html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                                    } else {
                                        $('#import-result').html('<div class="notice notice-error inline"><p>Error: ' + response.data + '</p></div>');
                                    }
                                },
                                error: function() {
                                    $('#import-result').html('<div class="notice notice-error inline"><p>Ha ocurrido un error durante la importación.</p></div>');
                                }
                            });
                        });
                    });
                </script>
            </div>
        </div>
        
        <?php submit_button('Guardar Configuración'); ?>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Inicializar pestañas si se decide agregar
        
        // Para el enlace de migrar desde YITH
        $('#migrate-yith-link').click(function(e) {
            e.preventDefault();
            
            if (confirm('¿Estás seguro de que deseas migrar los datos desde YITH WooCommerce Waitlist? Este proceso puede tomar tiempo dependiendo del número de suscriptores.')) {
                $(this).text('Migrando datos...').addClass('button-disabled');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'waitlist_migrate_from_yith',
                        nonce: '<?php echo wp_create_nonce("waitlist_migrate_from_yith"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('¡Migración completada con éxito! Se han migrado ' + response.data.count + ' suscriptores.');
                        } else {
                            alert('Error durante la migración: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Ha ocurrido un error durante la migración.');
                    },
                    complete: function() {
                        $('#migrate-yith-link').text('Migrar datos desde YITH').removeClass('button-disabled');
                    }
                });
            }
        });
    });
</script>