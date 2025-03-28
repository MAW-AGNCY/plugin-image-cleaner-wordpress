<?php
/**
 * Plugin Name: Image Cleaner
 * Description: A plugin to clean up unused images in your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: image-cleaner
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('IMAGE_CLEANER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLEANER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files.
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-admin.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-ajax.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-recovery.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-reports.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-emails.php';

/**
 * Main plugin class.
 */
class Image_Cleaner_Pro {
    private static $instance;

    /**
     * Singleton instance.
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->initialize_classes();
    }

    /**
     * Initialize plugin classes.
     */
    private function initialize_classes() {
        if (is_admin()) {
            // Admin-specific classes.
            new Image_Cleaner_Admin();
            new Image_Cleaner_Reports();
            new Image_Cleaner_Recovery();
            new Image_Cleaner_Emails();
        }

        // Classes that run in the frontend or backend.
        new Image_Cleaner_Ajax();
    }

    /**
     * Activation hook.
     */
    public static function activate() {
        // Perform necessary setup on activation.
        Image_Cleaner_Emails::schedule_email_event();
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        // Perform necessary cleanup on deactivation.
        Image_Cleaner_Emails::clear_email_event();
        flush_rewrite_rules();
    }
}

// Initialize the plugin.
Image_Cleaner_Pro::get_instance();

// Register activation and deactivation hooks.
register_activation_hook(__FILE__, ['Image_Cleaner_Pro', 'activate']);
register_deactivation_hook(__FILE__, ['Image_Cleaner_Pro', 'deactivate']);