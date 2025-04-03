<?php
/**
 * Modelo para manejar los datos
 */
class Waitlist_Model {
    
    /**
     * Variable estática para almacenar el total de elementos para la paginación
     */
    public static $total_items = 0;
    
    /**
     * Crea la tabla en la base de datos
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'waitlist';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            user_email varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email_product (user_email, product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Añade un suscriptor a la lista de espera
     */
    public static function add_subscriber($product_id, $email) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'waitlist';
        
        // Verificar si ya existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE product_id = %d AND user_email = %s",
            $product_id,
            $email
        ));
        
        if ($exists) {
            return new WP_Error('already_exists', 'Ya estás registrado en la lista de espera para este producto.');
        }
        
        // Insertar nuevo registro
        $result = $wpdb->insert(
            $table_name,
            array(
                'product_id' => $product_id,
                'user_email' => $email,
            ),
            array('%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'No se pudo guardar tu suscripción. Por favor, inténtalo de nuevo.');
        }
        
        return true;
    }
    
    /**
     * Obtiene suscriptores de un producto o todos los suscriptores
     */
    public static function get_subscribers($product_id = 0, $search = '', $per_page = 0, $page_number = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'waitlist';
        
        $subscribers = array();
        
        // Consulta para obtener emails únicos primero
        $email_query = "SELECT DISTINCT user_email FROM $table_name WHERE 1=1";
        
        $args = array();
        
        // Si hay un ID de producto, filtrar por ese producto
        if ($product_id > 0) {
            $email_query .= " AND product_id = %d";
            $args[] = $product_id;
        }
        
        // Filtrar por búsqueda si es necesario
        if (!empty($search)) {
            $email_query .= " AND (user_email LIKE %s";
            $args[] = '%' . $wpdb->esc_like($search) . '%';
            
            // También buscar en productos por nombre si no se especificó un producto_id
            if ($product_id <= 0) {
                $email_query .= " OR product_id IN (
                    SELECT ID FROM {$wpdb->posts}
                    WHERE post_type IN ('product', 'product_variation')
                    AND post_title LIKE %s
                )";
                $args[] = '%' . $wpdb->esc_like($search) . '%';
            }
            
            $email_query .= ")";
        }
        
        // Contar el total de emails únicos para la paginación
        $count_query = "SELECT COUNT(DISTINCT user_email) FROM $table_name WHERE 1=1";
        
        if (!empty($args)) {
            // Recrear la parte WHERE para la consulta de conteo
            if ($product_id > 0) {
                $count_query .= " AND product_id = %d";
            }
            
            if (!empty($search)) {
                $count_query .= " AND (user_email LIKE %s";
                
                if ($product_id <= 0) {
                    $count_query .= " OR product_id IN (
                        SELECT ID FROM {$wpdb->posts}
                        WHERE post_type IN ('product', 'product_variation')
                        AND post_title LIKE %s
                    )";
                }
                
                $count_query .= ")";
            }
        }
        
        self::$total_items = $wpdb->get_var($wpdb->prepare($count_query, $args));
        
        // Debug - Verificar el total de items
        error_log('Waitlist total unique emails: ' . self::$total_items);
        
        // Aplicar ordenamiento por email
        $email_query .= " ORDER BY user_email ASC";
        
        // Aplicar paginación a los emails únicos
        if ($per_page > 0) {
            $offset = ($page_number - 1) * $per_page;
            $email_query .= " LIMIT %d OFFSET %d";
            $args[] = $per_page;
            $args[] = $offset;
        }
        
        // Ejecutar la consulta para obtener los emails de esta página
        $emails = $wpdb->get_col($wpdb->prepare($email_query, $args));
        
        // Si no hay emails, devolver array vacío
        if (empty($emails)) {
            return array();
        }
        
        // Crear placeholders para la consulta IN
        $placeholders = implode(',', array_fill(0, count($emails), '%s'));
        
        // Obtener todos los registros de suscripción para estos emails
        $query = "SELECT 
            id, 
            product_id, 
            user_email, 
            created_at
        FROM 
            $table_name
        WHERE 
            user_email IN ($placeholders)
        ORDER BY 
            created_at DESC";
        
        // Ejecutar la consulta con todos los emails de esta página
        $results = $wpdb->get_results($wpdb->prepare($query, $emails));
        
        foreach ($results as $result) {
            $subscriber = new stdClass();
            $subscriber->id = $result->id;
            $subscriber->product_id = $result->product_id;
            $subscriber->user_email = $result->user_email;
            $subscriber->email = $result->user_email; // Alias para compatibilidad
            $subscriber->created_at = $result->created_at;
            
            // Obtener información del producto
            $product = wc_get_product($result->product_id);
            if ($product) {
                $subscriber->product_name = $product->get_name();
                $subscriber->product_link = get_permalink($result->product_id);
            } else {
                $subscriber->product_name = 'Producto #' . $result->product_id;
                $subscriber->product_link = '#';
            }
            
            $subscribers[] = $subscriber;
        }
        
        return $subscribers;
    }
    
    /**
     * Obtiene los suscriptores para un producto
     * Ahora compatible con YITH WooCommerce Waitlist
     */
    public static function get_subscribers_yith($product_id = 0, $search = '', $per_page = 0, $page_number = 1) {
        global $wpdb;
        
        $subscribers = array();
        
        // Si hay un ID de producto, obtener solo los suscriptores de ese producto
        if ($product_id > 0) {
            // Obtener suscriptores de YITH para este producto
            $yith_subscribers = self::get_yith_subscribers($product_id);
            
            if (!empty($yith_subscribers)) {
                $subscribers = array_merge($subscribers, $yith_subscribers);
            }
        } else {
            // Obtener todos los suscriptores de YITH
            $all_yith_subscribers = self::get_all_yith_subscribers();
            
            if (!empty($all_yith_subscribers)) {
                $subscribers = array_merge($subscribers, $all_yith_subscribers);
            }
        }
        
        // Filtrar por búsqueda si es necesario
        if (!empty($search)) {
            $filtered = array();
            foreach ($subscribers as $subscriber) {
                if (stripos($subscriber->email, $search) !== false || 
                    (isset($subscriber->product_name) && stripos($subscriber->product_name, $search) !== false)) {
                    $filtered[] = $subscriber;
                }
            }
            $subscribers = $filtered;
        }
        
        // Obtener el total de suscriptores para la paginación
        $total_subscribers = count($subscribers);
        
        // Añadir el total de suscriptores como propiedad estática para usarlo en la paginación
        self::$total_items = $total_subscribers;
        
        // Aplicar paginación si se solicita
        if ($per_page > 0 && $total_subscribers > 0) {
            $offset = ($page_number - 1) * $per_page;
            // Asegurarse de que el offset no sea mayor que el total de elementos
            if ($offset >= $total_subscribers) {
                $page_number = 1;
                $offset = 0;
            }
            $subscribers = array_slice($subscribers, $offset, $per_page);
        }
        
        return $subscribers;
    }
    
    /**
     * Obtiene los suscriptores de YITH para un producto específico
     */
    private static function get_yith_subscribers($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return array();
        }
        
        $meta_value = get_post_meta($product_id, '_yith_wcwtl_users_list', true);
        if (empty($meta_value) || $meta_value == 'a:0:{}') {
            return array();
        }
        
        $subscribers = array();
        $yith_users = maybe_unserialize($meta_value);
        
        if (is_array($yith_users)) {
            foreach ($yith_users as $timestamp => $email) {
                $subscriber = new stdClass();
                $subscriber->id = 'yith_' . md5($email . $product_id);
                $subscriber->product_id = $product_id;
                $subscriber->email = $email;
                
                // Corregir el procesamiento de timestamps
                if (is_numeric($timestamp) && $timestamp > 946684800) { // Timestamp válido (después del año 2000)
                    $subscriber->created_at = date('Y-m-d H:i:s', $timestamp);
                } else {
                    // Si no hay timestamp válido, usar la fecha actual
                    $subscriber->created_at = current_time('mysql');
                }
                
                $subscriber->product_name = $product->get_name();
                $subscriber->product_link = get_permalink($product_id);
                $subscriber->source = 'yith';
                
                $subscribers[] = $subscriber;
            }
        }
        
        return $subscribers;
    }
    
    /**
     * Obtiene todos los suscriptores de YITH
     */
    private static function get_all_yith_subscribers() {
        global $wpdb;
        
        $subscribers = array();
        
        // Obtener todos los meta valores de YITH
        $query = "
            SELECT 
                pm.post_id,
                pm.meta_value
            FROM 
                {$wpdb->postmeta} pm
            WHERE 
                pm.meta_key = '_yith_wcwtl_users_list'
                AND pm.meta_value IS NOT NULL 
                AND pm.meta_value != '' 
                AND pm.meta_value != 'a:0:{}'
        ";
        
        $results = $wpdb->get_results($query);
        
        foreach ($results as $result) {
            $product_id = $result->post_id;
            $product = wc_get_product($product_id);
            
            if (!$product) {
                continue;
            }
            
            $yith_users = maybe_unserialize($result->meta_value);
            
            if (is_array($yith_users)) {
                foreach ($yith_users as $timestamp => $email) {
                    $subscriber = new stdClass();
                    $subscriber->id = 'yith_' . md5($email . $product_id);
                    $subscriber->product_id = $product_id;
                    $subscriber->email = $email;
                    
                    // Corregir el procesamiento de timestamps
                    if (is_numeric($timestamp) && $timestamp > 946684800) { // Timestamp válido (después del año 2000)
                        $subscriber->created_at = date('Y-m-d H:i:s', $timestamp);
                    } else {
                        // Si no hay timestamp válido, usar la fecha actual
                        $subscriber->created_at = current_time('mysql');
                    }
                    
                    $subscriber->product_name = $product->get_name();
                    $subscriber->product_link = get_permalink($product_id);
                    $subscriber->source = 'yith';
                    
                    $subscribers[] = $subscriber;
                }
            }
        }
        
        return $subscribers;
    }
    
    /**
     * Obtiene las variaciones de un producto principal con mejor detalle y suscriptores
     */
    public static function get_product_variations_detail($parent_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'waitlist';
        
        // Obtener todas las variaciones del producto (incluso las que no tienen suscriptores)
        $variations_query = "
            SELECT 
                p.ID as product_id,
                p.post_title as variation_name
            FROM 
                {$wpdb->posts} p
            WHERE 
                p.post_parent = %d
                AND p.post_type = 'product_variation'
                AND p.post_status = 'publish'
        ";
        
        $variations_query = $wpdb->prepare($variations_query, $parent_id);
        $all_variations = $wpdb->get_results($variations_query);
        
        // Si no hay variaciones, devolver un array vacío
        if (empty($all_variations)) {
            return array();
        }
        
        // Obtener los IDs de todas las variaciones
        $variation_ids = array();
        foreach ($all_variations as $variation) {
            $variation_ids[] = $variation->product_id;
        }
        
        // Añadir el producto principal al array de IDs
        $all_product_ids = array_merge(array($parent_id), $variation_ids);
        
        // Preparar placeholders para la consulta SQL
        $placeholders = implode(',', array_fill(0, count($all_product_ids), '%d'));
        
        // Obtener los suscriptores de nuestra tabla para estas variaciones
        $query = "
            SELECT 
                p.ID as product_id,
                p.post_title as product_name,
                COUNT(w.id) as subscribers_count,
                GROUP_CONCAT(DISTINCT w.user_email ORDER BY w.created_at DESC SEPARATOR ',') as emails,
                MIN(w.created_at) as first_subscription,
                MAX(w.created_at) as last_subscription
            FROM 
                {$wpdb->posts} p
            LEFT JOIN 
                {$table_name} w ON p.ID = w.product_id
            WHERE 
                p.ID IN ($placeholders)
                AND p.post_type IN ('product', 'product_variation')
            GROUP BY 
                p.ID
            ORDER BY 
                subscribers_count DESC
        ";
        
        // Preparar la consulta con los IDs de productos
        $query = $wpdb->prepare($query, $all_product_ids);
        $results = $wpdb->get_results($query);
        
        // Crear un mapa de productos para facilitar el acceso
        $products_map = array();
        foreach ($results as $result) {
            $products_map[$result->product_id] = $result;
        }
        
        // Asegurarse de que todos los productos estén en el resultado, incluso si no tienen suscriptores
        $final_results = array();
        foreach ($all_product_ids as $pid) {
            if (isset($products_map[$pid])) {
                $final_results[$pid] = $products_map[$pid];
            } else {
                $product = new stdClass();
                $product->product_id = $pid;
                $product->product_name = '';
                $product->subscribers_count = 0;
                $product->emails = '';
                $product->first_subscription = '';
                $product->last_subscription = '';
                
                $final_results[$pid] = $product;
            }
        }
        
        // Para cada variación, añadir información de WooCommerce (atributos, etc.)
        foreach ($final_results as $key => $variation) {
            $variation_product = wc_get_product($variation->product_id);
            
            if ($variation_product) {
                if ($variation_product->is_type('variation')) {
                    // Obtener los atributos de la variación
                    $attributes = $variation_product->get_attributes();
                    $attribute_text = array();
                    $attribute_details = array();
                    
                    foreach ($attributes as $attr_name => $attr_value) {
                        $taxonomy = str_replace('attribute_', '', $attr_name);
                        $term_name = get_term_by('slug', $attr_value, $taxonomy);
                        $attr_label = wc_attribute_label($taxonomy);
                        
                        if ($term_name) {
                            $attribute_text[] = $attr_label . ': ' . $term_name->name;
                            $attribute_details[$attr_label] = $term_name->name;
                        } else {
                            $attribute_text[] = $attr_label . ': ' . $attr_value;
                            $attribute_details[$attr_label] = $attr_value;
                        }
                    }
                    
                    $final_results[$key]->attributes = implode(', ', $attribute_text);
                    $final_results[$key]->attribute_details = $attribute_details;
                    
                    // Determinar la clave principal de agrupación (normalmente "Talla" o "Color")
                    if (!empty($attribute_details)) {
                        $first_attr = array_key_first($attribute_details);
                        $final_results[$key]->main_attribute_name = $first_attr;
                        $final_results[$key]->main_attribute_value = $attribute_details[$first_attr];
                    }
                    
                    // Obtener información complementaria
                    $final_results[$key]->stock_status = $variation_product->get_stock_status();
                    $final_results[$key]->is_in_stock = $variation_product->is_in_stock();
                    $final_results[$key]->sku = $variation_product->get_sku();
                    
                    // Si la variación no tiene nombre, usar el nombre del producto principal
                    if (empty($final_results[$key]->product_name)) {
                        $parent = wc_get_product($parent_id);
                        if ($parent) {
                            $final_results[$key]->product_name = $parent->get_name() . ' - ' . implode(', ', $attribute_text);
                        }
                    }
                } else {
                    // Es el producto principal, no una variación
                    $final_results[$key]->main_attribute_name = 'Producto';
                    $final_results[$key]->main_attribute_value = 'Principal';
                    $final_results[$key]->attributes = '';
                    $final_results[$key]->stock_status = $variation_product->get_stock_status();
                    $final_results[$key]->is_in_stock = $variation_product->is_in_stock();
                    $final_results[$key]->sku = $variation_product->get_sku();
                }
            }
            
            // Obtener los detalles de los suscriptores
            if ($variation->emails) {
                $emails = explode(',', $variation->emails);
                $detailed_subscribers = array();
                
                foreach ($emails as $email) {
                    $subscriber = new stdClass();
                    $subscriber->email = $email;
                    $subscriber->id = ''; // No tenemos un ID exacto aquí
                    $detailed_subscribers[] = $subscriber;
                }
                
                $final_results[$key]->detailed_subscribers = $detailed_subscribers;
            } else {
                $final_results[$key]->detailed_subscribers = array();
            }
        }
        
        return array_values($final_results);
    }
    
    /**
     * Obtiene todos los productos con suscriptores agrupados por producto principal
     */
    public static function get_products_with_subscribers_grouped() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'waitlist';
        
        // Consulta que agrupa por producto principal, usando nuestra propia tabla
        $query = "
            SELECT 
                p.post_parent as parent_id,
                CASE 
                    WHEN p.post_parent = 0 THEN p.ID
                    ELSE p.post_parent
                END AS main_product_id,
                COUNT(DISTINCT w.id) as subscribers_count,
                COUNT(DISTINCT w.product_id) as variations_count
            FROM 
                {$table_name} w
            JOIN 
                {$wpdb->posts} p ON w.product_id = p.ID
            WHERE 
                p.post_type IN ('product', 'product_variation')
            GROUP BY 
                main_product_id
            ORDER BY 
                subscribers_count DESC
        ";
        
        $results = $wpdb->get_results($query);
        
        // Agregar información adicional a cada producto
        foreach ($results as $key => $item) {
            // Si es un producto principal (no una variación)
            if ($item->parent_id == 0) {
                $product = wc_get_product($item->main_product_id);
            } else {
                // Es una variación, obtener el producto principal
                $product = wc_get_product($item->main_product_id);
            }
            
            if ($product) {
                $results[$key]->product_name = $product->get_name();
                $results[$key]->product_image = $product->get_image();
                $results[$key]->product_link = get_permalink($item->main_product_id);
                $results[$key]->edit_link = get_edit_post_link($item->main_product_id);
            }
        }
        
        return $results;
    }
    
    /**
     * Elimina un suscriptor
     */
    public static function delete_subscriber($id) {
        // Si es un ID de YITH, manejarlo de manera diferente
        if (strpos($id, 'yith_') === 0) {
            return self::delete_yith_subscriber($id);
        }
        
        // Eliminar de nuestra tabla personalizada
        global $wpdb;
        $table_name = $wpdb->prefix . 'waitlist';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Elimina un suscriptor de YITH WooCommerce Waitlist
     */
    public static function delete_yith_subscriber($composite_id) {
        // El formato es 'yith_' + md5(email + product_id)
        if (strpos($composite_id, 'yith_') !== 0) {
            return false;
        }
        
        $hash_part = substr($composite_id, 5); // Quitar el prefijo 'yith_'
        
        global $wpdb;
        
        // Buscar en todos los productos que tienen suscriptores YITH
        $query = "
            SELECT 
                pm.post_id,
                pm.meta_value
            FROM 
                {$wpdb->postmeta} pm
            WHERE 
                pm.meta_key = '_yith_wcwtl_users_list'
                AND pm.meta_value IS NOT NULL 
                AND pm.meta_value != '' 
                AND pm.meta_value != 'a:0:{}'
        ";
        
        $results = $wpdb->get_results($query);
        
        foreach ($results as $result) {
            $product_id = $result->post_id;
            $users_list = maybe_unserialize($result->meta_value);
            
            if (!is_array($users_list) || empty($users_list)) {
                continue;
            }
            
            // Buscar el email que coincide con el hash
            $email_to_remove = null;
            foreach ($users_list as $timestamp => $email) {
                if (md5($email . $product_id) === $hash_part) {
                    $email_to_remove = $email;
                    $timestamp_to_remove = $timestamp;
                    break;
                }
            }
            
            if ($email_to_remove) {
                // Eliminar el email de la lista
                unset($users_list[$timestamp_to_remove]);
                
                // Actualizar el meta_value
                update_post_meta($product_id, '_yith_wcwtl_users_list', $users_list);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Elimina todos los suscriptores de un producto
     */
    public static function delete_product_subscribers($product_id) {
        global $wpdb;
        
        // 1. Eliminar de nuestra tabla personalizada
        $table_name = $wpdb->prefix . 'waitlist';
        $wpdb->delete(
            $table_name,
            array('product_id' => $product_id),
            array('%d')
        );
        
        // 2. Eliminar de YITH WooCommerce Waitlist
        $wpdb->update(
            $wpdb->postmeta,
            array('meta_value' => 'a:0:{}'),
            array(
                'post_id' => $product_id,
                'meta_key' => '_yith_wcwtl_users_list'
            ),
            array('%s'),
            array('%d', '%s')
        );
        
        return true;
    }
    
    /**
     * Verifica si un producto tiene suscriptores
     */
    public static function has_subscribers($product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'waitlist';
        
        // Verificar en nuestra tabla personalizada
        $custom_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE product_id = %d",
            $product_id
        ));
        
        if ($custom_count > 0) {
            return true;
        }
        
        // Verificar en YITH WooCommerce Waitlist
        $yith_data = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} 
            WHERE post_id = %d AND meta_key = '_yith_wcwtl_users_list'
            AND meta_value IS NOT NULL 
            AND meta_value != '' 
            AND meta_value != 'a:0:{}'",
            $product_id
        ));
        
        return !empty($yith_data);
    }
    
    /**
     * Obtiene los suscriptores de un producto agrupados por variación (talla)
     * 
     * @param int $product_id ID del producto
     * @return array Array con los suscriptores agrupados por variación
     */
    public static function get_subscribers_grouped_by_variation($product_id) {
        global $wpdb;
        
        // Verificar si es un producto variable
        $product = wc_get_product($product_id);
        if (!$product) {
            return array();
        }
        
        $is_variable = $product->is_type('variable');
        $variations = array();
        $variation_ids = array();
        
        // Si es un producto variable, obtener todas sus variaciones
        if ($is_variable) {
            $variation_ids = $product->get_children();
        } else {
            // Si es una variación, obtener el producto padre y todas sus variaciones hermanas
            if ($product->is_type('variation')) {
                $parent_id = $product->get_parent_id();
                $parent_product = wc_get_product($parent_id);
                if ($parent_product) {
                    $variation_ids = $parent_product->get_children();
                }
            }
        }
        
        // Incluir el producto principal en la búsqueda
        $all_product_ids = array_merge(array($product_id), $variation_ids);
        $all_product_ids = array_unique($all_product_ids);
        
        // Preparar placeholders para la consulta SQL
        $placeholders = implode(',', array_fill(0, count($all_product_ids), '%d'));
        
        // Consulta para obtener suscriptores de YITH agrupados por producto/variación
        $query = "
            SELECT 
                p.ID as product_id,
                p.post_title as product_name,
                COUNT(yith_data.email) as subscribers_count,
                GROUP_CONCAT(DISTINCT yith_data.email ORDER BY yith_data.timestamp DESC SEPARATOR ',') as emails
            FROM 
                {$wpdb->posts} p
            JOIN (
                SELECT 
                    pm.post_id,
                    SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, '\"', -2), '\"', 1) as email,
                    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, ';', 2), ':', -1) as UNSIGNED) as timestamp
                FROM 
                    {$wpdb->postmeta} pm
                WHERE 
                    pm.meta_key = '_yith_wcwtl_users_list'
                    AND pm.meta_value IS NOT NULL 
                    AND pm.meta_value != '' 
                    AND pm.meta_value != 'a:0:{}'
                    AND pm.post_id IN ($placeholders)
            ) as yith_data ON p.ID = yith_data.post_id
            WHERE 
                p.post_type IN ('product', 'product_variation')
            GROUP BY 
                p.ID
            ORDER BY 
                subscribers_count DESC
        ";
        
        // Preparar la consulta con los IDs de productos
        $query = $wpdb->prepare($query, $all_product_ids);
        $results = $wpdb->get_results($query);
        
        // Agregar información adicional a cada variación
        foreach ($results as $key => $variation) {
            $variation_product = wc_get_product($variation->product_id);
            
            if ($variation_product) {
                // Obtener los atributos de la variación si es una variación
                if ($variation_product->is_type('variation')) {
                    $attributes = $variation_product->get_attributes();
                    $attribute_text = array();
                    
                    foreach ($attributes as $attr_name => $attr_value) {
                        $taxonomy = str_replace('attribute_', '', $attr_name);
                        $term_name = get_term_by('slug', $attr_value, $taxonomy);
                        $attr_label = wc_attribute_label($taxonomy);
                        
                        if ($term_name) {
                            $attribute_text[] = $attr_label . ': ' . $term_name->name;
                        } else {
                            $attribute_text[] = $attr_label . ': ' . $attr_value;
                        }
                    }
                    
                    $results[$key]->attributes = implode(', ', $attribute_text);
                    $results[$key]->attribute_details = $attributes;
                } else {
                    // Si es un producto simple
                    $results[$key]->attributes = 'Producto principal';
                }
                
                // Obtener los suscriptores detallados para esta variación
                $subscribers = self::get_subscribers($variation->product_id);
                $results[$key]->detailed_subscribers = $subscribers;
            }
        }
        
        return $results;
    }
    
    /**
     * Migra todos los suscriptores desde YITH WooCommerce Waitlist a nuestro sistema
     * 
     * @return array Resultados de la migración
     */
    public static function migrate_from_yith() {
        global $wpdb;
        
        $migrated = 0;
        $errors = 0;
        $already_exists = 0;
        
        // Obtener todos los meta valores de YITH
        $query = "
            SELECT 
                pm.post_id,
                pm.meta_value
            FROM 
                {$wpdb->postmeta} pm
            WHERE 
                pm.meta_key = '_yith_wcwtl_users_list'
                AND pm.meta_value IS NOT NULL 
                AND pm.meta_value != '' 
                AND pm.meta_value != 'a:0:{}'
        ";
        
        $results = $wpdb->get_results($query);
        
        foreach ($results as $result) {
            $product_id = $result->post_id;
            $product = wc_get_product($product_id);
            
            if (!$product) {
                $errors++;
                continue;
            }
            
            $yith_users = maybe_unserialize($result->meta_value);
            
            if (!is_array($yith_users)) {
                $errors++;
                continue;
            }
            
            foreach ($yith_users as $timestamp => $email) {
                // Validar el email
                if (empty($email) || !is_email($email)) {
                    $errors++;
                    continue;
                }
                
                // Añadir a nuestro sistema
                $result = self::add_subscriber($product_id, $email);
                
                if (is_wp_error($result)) {
                    if ($result->get_error_code() === 'already_exists') {
                        $already_exists++;
                    } else {
                        $errors++;
                    }
                } else {
                    $migrated++;
                }
            }
        }
        
        return array(
            'migrated' => $migrated,
            'errors' => $errors,
            'already_exists' => $already_exists,
            'total' => $migrated + $errors + $already_exists
        );
    }
    
    /**
     * Obtiene el recuento de suscripciones por email de todos los suscriptores
     * agrupados por dirección de email
     * 
     * @return array Array con emails como claves y recuentos como valores
     */
    public static function get_email_subscription_counts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'waitlist';
        
        // Esta consulta cuenta cuántos productos diferentes tiene cada email
        $query = "
            SELECT 
                user_email,
                COUNT(DISTINCT product_id) as product_count
            FROM 
                $table_name
            GROUP BY 
                user_email
            ORDER BY 
                product_count DESC
        ";
        
        $results = $wpdb->get_results($query);
        
        // Crear un array con email => recuento de productos
        $email_counts = array();
        foreach ($results as $result) {
            $email_counts[$result->user_email] = $result->product_count;
        }
        
        return $email_counts;
    }
}