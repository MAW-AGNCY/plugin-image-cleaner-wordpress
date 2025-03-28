<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Cleaner_Recovery {
    public function __construct() {
        add_action('admin_post_image_cleaner_recover', [$this, 'process_recovery_request']);
    }

    /**
     * Process recovery requests for deleted images.
     */
    public function process_recovery_request() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'image-cleaner'));
        }

        // Validate nonce for security.
        if (!isset($_POST['image_cleaner_nonce']) || !wp_verify_nonce($_POST['image_cleaner_nonce'], 'image_cleaner_recover')) {
            wp_die(__('Invalid nonce specified.', 'image-cleaner'));
        }

        // Process recovery logic.
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        if ($image_id) {
            $restored = $this->restore_image($image_id);

            if ($restored) {
                wp_redirect(admin_url('admin.php?page=image-cleaner-recovery&recovered=1'));
                exit;
            }
        }

        wp_redirect(admin_url('admin.php?page=image-cleaner-recovery&recovered=0'));
        exit;
    }

    /**
     * Restore an image from backup.
     *
     * @param int $image_id The ID of the image to restore.
     * @return bool True if the image was restored successfully, false otherwise.
     */
    private function restore_image($image_id) {
        // Assume backups are stored in a predefined directory.
        $backup_dir = wp_upload_dir()['basedir'] . '/image-cleaner-backups/';
        $original_file = get_attached_file($image_id);

        if (!$original_file || !file_exists($backup_dir . basename($original_file))) {
            return false;
        }

        // Restore the file from the backup.
        return copy($backup_dir . basename($original_file), $original_file);
    }
}
