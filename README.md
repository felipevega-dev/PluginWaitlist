# Lista de Espera para WooCommerce

Plugin personalizado para WooCommerce que permite a los clientes registrarse en una lista de espera para productos sin stock y recibir notificaciones por email cuando vuelvan a estar disponibles.

## Características

- **Sistema de suscripción independiente**: Permite a los clientes suscribirse para recibir notificaciones cuando un producto agotado esté nuevamente disponible.
- **Compatibilidad con variaciones**: Funciona tanto con productos simples como con productos variables (diferentes tallas, colores, etc.).
- **Panel de administración completo**:
  - Vista de productos con listas de espera y cantidad de suscriptores
  - Vista detallada de variaciones de productos
  - Gestión de suscriptores por producto
  - Exportación a Excel de productos, variaciones y suscriptores
- **Notificaciones automáticas**: Envía automáticamente correos electrónicos cuando los productos vuelven a tener stock.
- **Plantillas de email personalizables**: El administrador puede personalizar el asunto y contenido de los correos de notificación.
- **Funcionalidad de importación/exportación**: Permite importar y exportar datos de suscriptores en formato Excel.
- **Migración desde YITH**: Incluye herramientas para migrar datos desde el plugin YITH WooCommerce Waitlist.

## Requisitos

- WordPress 5.0 o superior
- WooCommerce 4.0 o superior
- PHP 7.4 o superior
- Composer (para la instalación de dependencias)

## Instalación

1. Descarga el plugin y súbelo a la carpeta `/wp-content/plugins/` de tu instalación de WordPress
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Ejecuta `composer install` en el directorio del plugin para instalar las dependencias necesarias

## Configuración

1. Ve a WooCommerce > Lista de Espera en el panel de administración
2. En la pestaña "Configuración" puedes personalizar:
   - Las plantillas de email para notificaciones
   - Opciones de visualización en la tienda
   - Configuración de exportación de datos

## Uso

### Para clientes
- Cuando un producto está agotado, aparecerá un formulario para suscribirse a la lista de espera
- Los clientes pueden introducir su correo electrónico para recibir una notificación cuando el producto esté disponible nuevamente
- No es necesario que el cliente esté registrado en la tienda para usar esta función

### Para administradores
- **Productos con Lista de Espera**: Muestra todos los productos que tienen suscriptores esperando, con filtros por nombre, categoría y opciones de exportación.
- **Suscriptores**: Permite ver y gestionar todos los suscriptores registrados.
- **Configuración**: Personaliza las opciones del plugin, incluidas las plantillas de email.
- **Migración desde YITH**: Si usabas el plugin YITH WooCommerce Waitlist, puedes migrar todos tus suscriptores al nuevo sistema.

## Estructura de la base de datos

El plugin crea una tabla personalizada en la base de datos llamada `{prefix}_waitlist` para almacenar los suscriptores con los siguientes campos:
- id (ID único del registro)
- product_id (ID del producto o variación)
- user_email (Email del suscriptor)
- created_at (Fecha y hora de la suscripción)

## Exportación a Excel

El plugin utiliza la biblioteca PhpSpreadsheet para generar exportaciones en formato Excel (.xlsx) con las siguientes opciones:
- Lista de productos con cantidad de suscriptores
- Detalle de suscriptores por producto
- Detalle de variaciones con suscriptores
- Exportación completa de todos los suscriptores

## Personalización

El plugin puede ser extendido y personalizado:
- Ganchos (hooks) de WordPress disponibles para personalizar la funcionalidad
- Plantillas que pueden ser sobrescritas en el tema
- Funciones de filtrado para personalizar el comportamiento del plugin

## Solución de problemas comunes

- Si las exportaciones a Excel no funcionan, asegúrate de que las dependencias de Composer estén instaladas correctamente ejecutando `composer install` en el directorio del plugin.
- Si las notificaciones automáticas no se envían, verifica la configuración de correo electrónico de WordPress.

## Licencia

Este plugin es software propietario desarrollado específicamente para uso interno.

## Créditos

Desarrollado por Felipe Vega.
