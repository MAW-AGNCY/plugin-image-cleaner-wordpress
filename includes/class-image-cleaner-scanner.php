/**
 * Función avanzada para verificar si una imagen está realmente en uso.
 * Escanea contenido de posts, JSON escapado (Page Builders) y meta campos.
 * 
 * @param int $attachment_id El ID de la imagen a verificar.
 * @return bool True si está en uso, False si parece huérfana.
 */
public function is_image_in_use_advanced($attachment_id) {
    global $wpdb;

    // 1. Verificación básica: Imagen destacada (Ya la tienes, pero la mantenemos)
    if (get_post_meta($attachment_id, '_wp_attachment_metadata', true)) {
         // Lógica existente...
    }

    // 2. Obtener la URL y el nombre del archivo para buscar strings
    $file_url = wp_get_attachment_url($attachment_id);
    $file_name = basename(get_attached_file($attachment_id));
    
    if (!$file_name) return false;

    // 3. Búsqueda en wp_posts (post_content)
    // Busca la URL completa O el nombre del archivo (común en shortcodes)
    // Usamos LIKE con comodines. Es pesado, requiere optimización futura.
    $found_in_content = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} 
         WHERE post_status NOT IN ('inherit', 'trash', 'auto-draft') 
         AND (post_content LIKE %s OR post_content LIKE %s) 
         LIMIT 1",
        '%' . $wpdb->esc_like($file_url) . '%',
        '%' . $wpdb->esc_like($file_name) . '%'
    ));

    if ($found_in_content) {
        return true; // Encontrada en el contenido de un post/página
    }

    // 4. Búsqueda en wp_postmeta (Para Elementor, Divi, ACF, Sliders)
    // Muchos page builders guardan IDs o URLs en postmeta.
    $found_in_meta = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
         WHERE meta_value LIKE %s OR meta_value LIKE %s 
         LIMIT 1",
        '%' . $wpdb->esc_like($file_url) . '%', // URL en campo de texto
        '%' . $attachment_id . '%'            // ID en campo serializado o JSON
    ));

    if ($found_in_meta) {
        return true; // Encontrada en un campo personalizado o configuración de tema
    }

    return false; // Probablemente segura para borrar
}
