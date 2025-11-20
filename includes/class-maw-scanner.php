<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Class MAW_Scanner
 * Motor de análisis profundo para detectar uso de imágenes en:
 * - Contenido estándar (wp_posts)
 * - Campos meta (ACF, Yoast)
 * - Page Builders (Elementor JSON, Divi Shortcodes)
 * - WooCommerce (Galerías)
 */
class MAW_Scanner {

    /**
     * Analiza una imagen y devuelve dónde se está usando.
     * 
     * @param int $attachment_id ID del adjunto.
     * @return array Array de lugares de uso ['found' => bool, 'locations' => array].
     */
    public function scan_usage($attachment_id) {
        global $wpdb;
        $locations = [];
        $found = false;

        // 1. Imagen Destacada (Estándar WP)
        $featured_in = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d",
            $attachment_id
        ));
        foreach ($featured_in as $row) {
            $found = true;
            $locations[] = $this->format_location($row->post_id, 'Imagen Destacada');
        }

        // 2. Galería WooCommerce (_product_image_gallery)
        // Woo guarda los IDs separados por comas: "123,456,789"
        $woo_galleries = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_image_gallery' AND meta_value LIKE %s",
            '%' . $attachment_id . '%'
        ));
        foreach ($woo_galleries as $row) {
            $ids = explode(',', $row->meta_value);
            if (in_array($attachment_id, $ids)) {
                $found = true;
                $locations[] = $this->format_location($row->post_id, 'Galería WooCommerce');
            }
        }

        // 3. Obtener URL y Nombre de archivo para búsquedas de texto
        $file_url = wp_get_attachment_url($attachment_id);
        $file_name = basename(get_attached_file($attachment_id));
        
        if (!$file_url) return ['found' => $found, 'locations' => $locations];

        // 4. Búsqueda en Contenido (Elementor, Divi, Gutenberg)
        // Elementor guarda datos en postmeta `_elementor_data` (JSON escapado)
        // Divi y Gutenberg guardan en `post_content`
        $sql_content = "SELECT ID, post_title, post_type FROM {$wpdb->posts} 
                        WHERE post_status NOT IN ('inherit', 'trash', 'auto-draft') 
                        AND (post_content LIKE %s OR post_content LIKE %s)";
        
        $results_content = $wpdb->get_results($wpdb->prepare($sql_content, '%' . $wpdb->esc_like($file_url) . '%', '%' . $wpdb->esc_like($file_name) . '%'));

        foreach ($results_content as $post) {
            $found = true;
            $type = ($post->post_type == 'product') ? 'Descripción Producto' : 'Contenido / Shortcode';
            $locations[] = $this->format_location($post->ID, $type);
        }

        // 5. Búsqueda específica Elementor (Data JSON)
        // A veces Elementor guarda el ID o la URL en meta
        $sql_meta = "SELECT post_id FROM {$wpdb->postmeta} 
                     WHERE meta_key = '_elementor_data' 
                     AND (meta_value LIKE %s OR meta_value LIKE %s)";
        
        $results_meta = $wpdb->get_results($wpdb->prepare($sql_meta, '%' . $wpdb->esc_like($file_url) . '%', '%"id":' . $attachment_id . '%'));

        foreach ($results_meta as $row) {
            // Evitar duplicados si ya salió en post_content
            if (!$this->is_in_array($row->post_id, $locations)) {
                $found = true;
                $locations[] = $this->format_location($row->post_id, 'Elementor Builder');
            }
        }

        return ['found' => $found, 'locations' => array_unique($locations, SORT_REGULAR)];
    }

    /**
     * Formatea la salida para la vista.
     */
    private function format_location($post_id, $type) {
        return [
            'id' => $post_id,
            'title' => get_the_title($post_id) ?: 'Post #' . $post_id,
            'type' => $type,
            'link' => get_edit_post_link($post_id)
        ];
    }

    private function is_in_array($id, $array) {
        foreach ($array as $item) {
            if ($item['id'] == $id) return true;
        }
        return false;
    }
}
