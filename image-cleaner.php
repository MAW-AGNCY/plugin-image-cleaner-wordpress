<?php
/**
 * Plugin Name: Image Cleaner Pro (Secured)
 * Description: Limpia imágenes no utilizadas en WordPress de forma segura. Incluye auditoría de seguridad y soporte para papelera.
 * Version: 1.0.1
 * Author: MAW-AGNCY
 * Text Domain: image-cleaner
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IMAGE_CLEANER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLEANER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-admin.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-ajax.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-recovery.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-reports.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-emails.php';

class Image_Cleaner_Pro {
    private static $instance;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initialize_classes();
    }

    private function initialize_classes() {
        if (is_admin()) {
            new Image_Cleaner_Admin();
            new Image_Cleaner_Reports();
            new Image_Cleaner_Recovery();
            new Image_Cleaner_Emails();
        }
        new Image_Cleaner_Ajax();
    }

    public static function activate() {
        Image_Cleaner_Emails::schedule_email_event();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        Image_Cleaner_Emails::clear_email_event();
        flush_rewrite_rules();
    }
}

Image_Cleaner_Pro::get_instance();

register_activation_hook(__FILE__, ['Image_Cleaner_Pro', 'activate']);
register_deactivation_hook(__FILE__, ['Image_Cleaner_Pro', 'deactivate']);
