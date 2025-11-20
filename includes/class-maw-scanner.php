<?php
if (!defined('ABSPATH')) exit;

class MAW_Scanner {

    /**
     * Analiza el uso y devuelve el ORIGEN (source).
     */
    public function scan_usage($attachment_id) {
        global $wpdb;
        $locations = [];
        $found = false;
        $main_source = ''; // 'woo', 'elementor', 'post', etc.

        // 1. Imagen Destacada (Posts/Páginas)
        $featured = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_thumbnail_id' AND meta_value=%d", $attachment_id));
        foreach ($featured as $row) { 
            $found = true; 
            $locations[] = $this->fmt($row->post_id, 'Imagen Destacada');
            if(empty($main_source)) $main_source = 'wp'; 
        }

        // 2. WooCommerce (Galerías y Productos)
        // A. Galería de Producto
        $woo_gallery = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key='_product_image_gallery' AND meta_value LIKE %s", '%'.$attachment_id.'%'));
        foreach ($woo_gallery as $row) { 
            if(in_array($attachment_id, explode(',', $row->meta_value))) {
                $found = true; 
                $locations[] = $this->fmt($row->post_id, 'Galería Producto');
                $main_source = 'woo'; // Prioridad visual
            }
        }
        
        // B. Imagen Principal de Producto (Si es un 'product')
        if ($found && empty($main_source)) {
            foreach ($featured as $row) {
                if (get_post_type($row->post_id) === 'product') {
                    $main_source = 'woo';
                }
            }
        }

        // 3. Elementor / Page Builders
        $url = wp_get_attachment_url($attachment_id);
        if($url) {
            // Buscar en contenido JSON escapado o HTML
            $posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_type FROM $wpdb->posts WHERE post_status NOT IN ('inherit','trash','auto-draft') AND post_content LIKE %s", '%'.$wpdb->esc_like($url).'%'));
            foreach($posts as $post) { 
                $found = true; 
                $locations[] = $this->fmt($post->ID, 'Contenido / HTML');
                if(empty($main_source)) $main_source = 'content';
            }
            
            // Elementor Data
            $elem = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_elementor_data' AND meta_value LIKE %s", '%'.basename($url).'%'));
            if ($elem) {
                $found = true;
                $main_source = 'elementor';
            }
        }

        return [
            'found' => $found, 
            'source' => $main_source, // 'woo', 'wp', 'elementor', 'content', ''
            'locations' => array_unique($locations, SORT_REGULAR)
        ];
    }

    private function fmt($id, $type) {
        return ['id'=>$id, 'title'=>get_the_title($id)?:'Post #'.$id, 'type'=>$type, 'link'=>get_edit_post_link($id)];
    }
}
