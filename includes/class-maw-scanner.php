<?php
if (!defined('ABSPATH')) exit;

class MAW_Scanner {
    public function scan_usage($attachment_id) {
        global $wpdb;
        $locations = [];
        $found = false;

        // 1. Imagen Destacada
        $featured = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_thumbnail_id' AND meta_value=%d", $attachment_id));
        foreach ($featured as $row) { $found=true; $locations[] = $this->fmt($row->post_id, 'Imagen Destacada'); }

        // 2. Woo Gallery
        $woo = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key='_product_image_gallery' AND meta_value LIKE %s", '%'.$attachment_id.'%'));
        foreach ($woo as $row) { 
            if(in_array($attachment_id, explode(',', $row->meta_value))) {
                $found=true; $locations[] = $this->fmt($row->post_id, 'WooCommerce'); 
            }
        }

        // 3. Contenido y JSON (Elementor)
        $url = wp_get_attachment_url($attachment_id);
        if($url) {
            $posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_type FROM $wpdb->posts WHERE post_status NOT IN ('inherit','trash','auto-draft') AND post_content LIKE %s", '%'.$wpdb->esc_like($url).'%'));
            foreach($posts as $post) { $found=true; $locations[] = $this->fmt($post->ID, 'Contenido / HTML'); }
        }

        return ['found' => $found, 'locations' => array_unique($locations, SORT_REGULAR)];
    }

    private function fmt($id, $type) {
        return ['id'=>$id, 'title'=>get_the_title($id)?:'Post #'.$id, 'type'=>$type, 'link'=>get_edit_post_link($id)];
    }
}
