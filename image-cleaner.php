<?php
/**
 * Plugin Name: MAW Image Cleaner Pro
 * Description: Suite profesional de optimización de medios. Auditoría, Limpieza, Recuperación y Whitelist.
 * Version: 1.2.0
 * Author: Mondays at Work
 * Author URI: https://www.mondaysatwork.com
 * Text Domain: image-cleaner
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('IMAGE_CLEANER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLEANER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Includes
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-logger.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-whitelist.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-maw-email-manager.php'; // Nuevo sistema de email
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-admin.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-ajax.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-recovery.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-reports.php';

class Image_Cleaner_Pro {
    private static $instance;
    public $logger;
    public $whitelist_manager;

    public static function get_instance() {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Cargar traducciones
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Inicializar Clases Globales
        $this->logger = new MAW_Image_Logger();
        $this->whitelist_manager = new MAW_Image_Whitelist();

        if (is_admin()) {
            new Image_Cleaner_Admin();
            new Image_Cleaner_Reports();
            new Image_Cleaner_Recovery();
            
            // Encolar CSS Global del Dashboard
            add_action('admin_enqueue_scripts', [$this, 'enqueue_global_assets']);
        }
        new Image_Cleaner_Ajax();
    }

    public function load_textdomain() {
        load_plugin_textdomain('image-cleaner', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_global_assets($hook) {
        // Solo cargar en nuestras páginas
        if (strpos($hook, 'image-cleaner') !== false) {
            wp_enqueue_style(
                'maw-admin-ui', 
                IMAGE_CLEANER_PLUGIN_URL . 'assets/css/maw-admin-ui.css', 
                [], 
                '1.2.0'
            );
        }
    }

    // Getters públicos
    public function get_logger() { return $this->logger; }
    public function get_whitelist_manager() { return $this->whitelist_manager; }

    public static function activate() {
        MAW_Image_Logger::create_table();
        // Borrar transient al activar para forzar recálculo
        delete_transient('maw_image_cleaner_stats'); 
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}

Image_Cleaner_Pro::get_instance();
register_activation_hook(__FILE__, ['Image_Cleaner_Pro', 'activate']);
register_deactivation_hook(__FILE__, ['Image_Cleaner_Pro', 'deactivate']);
