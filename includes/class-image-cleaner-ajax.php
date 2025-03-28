<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Cleaner_Ajax {
    public function __construct() {
        add_action('wp_ajax_image_cleaner_filter_images', [$this, 'filter_images']);
        add_action('wp_ajax_image_cleaner_generate_report', [$this, 'generate_report']);
    }

    /**
     * Handle AJAX request to filter images.
     */
    public function filter_images() {
        check_ajax_referer('image_cleaner_nonce', 'security');

        $filters = [
            'type' => sanitize_text_field($_POST['filter_type'] ?? 'all'),
            'size' => intval($_POST['filter_size'] ?? 0),
            'date' => sanitize_text_field($_POST['filter_date'] ?? ''),
            'status' => sanitize_text_field($_POST['filter_status'] ?? 'all'),
        ];

        // Simulate filtered results (replace with actual logic)
        wp_send_json_success([
            'message' => __('Images filtered successfully.', 'image-cleaner'),
            'data' => $filters,
        ]);
    }

    /**
     * Handle AJAX request to generate a report.
     */
    public function generate_report() {
        check_ajax_referer('image_cleaner_nonce', 'security');

        // Simulate report generation (replace with actual logic)
        wp_send_json_success([
            'message' => __('Report generated successfully.', 'image-cleaner'),
        ]);
    }
}
