<?php
if (!defined('ABSPATH')) {
    exit; 
}

class Image_Cleaner_Reports {

    public function __construct() {
        // No registramos menús aquí. Lo hace Image_Cleaner_Admin.
    }

    /**
     * Obtener datos de imágenes no usadas (Lógica antigua restaurada).
     */
    public function get_unused_images_data() {
        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 100, // Limitado por rendimiento, idealmente usar paginación
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_thumbnail_id',
                    'value'   => '',
                    'compare' => '=',
                ],
            ],
        ];

        $query = new WP_Query($query_args);
        $data = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $file = get_attached_file($id);
                
                if ($file && file_exists($file)) {
                    $data[] = [
                        'ID'       => $id,
                        'title'    => get_the_title(),
                        'filename' => basename($file),
                        'url'      => wp_get_attachment_url($id),
                        'size'     => size_format(filesize($file)),
                        'date'     => get_the_date('Y-m-d'),
                    ];
                }
            }
        }
        wp_reset_postdata();
        return $data;
    }

    /**
     * Obtener datos de imágenes grandes (> 500KB).
     */
    public function get_large_images_data() {
        $size_threshold = 500000; // 500 KB
        
        // Consulta optimizada: solo traer IDs para procesar tamaño después
        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1, 
            'fields'         => 'ids' // Mucho más rápido
        ];

        $query = new WP_Query($query_args);
        $data = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $image_id) {
                $file = get_attached_file($image_id);
                if ($file && file_exists($file)) {
                    $filesize = filesize($file);
                    if ($filesize > $size_threshold) {
                        $data[] = [
                            'ID'       => $image_id,
                            'title'    => get_the_title($image_id),
                            'filename' => basename($file),
                            'url'      => wp_get_attachment_url($image_id),
                            'size'     => size_format($filesize),
                            'raw_size' => $filesize, // Para ordenar si fuera necesario
                            'date'     => get_the_date('Y-m-d', $image_id),
                        ];
                    }
                }
            }
        }
        
        // Ordenar por tamaño descendente (las más grandes primero)
        usort($data, function($a, $b) {
            return $b['raw_size'] - $a['raw_size'];
        });

        return $data;
    }
}
