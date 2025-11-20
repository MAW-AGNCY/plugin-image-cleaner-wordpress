<?php
/**
 * Plugin Name: MAW Image Cleaner Pro
 * Description: Suite profesional de optimización de medios. Auditoría, Limpieza, Recuperación y Whitelist. Integración nativa con Woo, Elementor y Divi.
 * Version: 2.0.1
 * Author: Mondays at Work
 * Author URI: https://www.mondaysatwork.com
 * Text Domain: image-cleaner
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Definición de constantes de rutas
define('IMAGE_CLEANER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLEANER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Carga de módulos del núcleo (Orden de dependencia importante)
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-logger.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-whitelist.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-maw-scanner.php'; // Motor de escaneo
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-maw-email-manager.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-admin.php'; // <--- Aquí está el registro de menús
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-ajax.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-recovery.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-reports.php';

class Image_Cleaner_Pro {
    private static $instance;
    
    // Componentes globales accesibles vía Singleton
    public $logger;
    public $whitelist_manager;

    public static function get_instance() {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Cargar traducciones
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Inicializar componentes lógicos
        $this->logger = new MAW_Image_Logger();
        $this->whitelist_manager = new MAW_Image_Whitelist();

        // Inicializar interfaz de administración
        if (is_admin()) {
            // Esta clase es la que llama a register_admin_menu
            new Image_Cleaner_Admin();
            new Image_Cleaner_Reports();
            new Image_Cleaner_Recovery();
        }
        
        // Inicializar listeners AJAX (frontend/backend)
        new Image_Cleaner_Ajax();
    }

    public function load_textdomain() {
        load_plugin_textdomain('image-cleaner', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    // Getters públicos para acceso global
    public function get_logger() { return $this->logger; }
    public function get_whitelist_manager() { return $this->whitelist_manager; }

    public static function activate() {
        MAW_Image_Logger::create_table();
        delete_transient('maw_image_cleaner_stats');
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Arrancar el motor
Image_Cleaner_Pro::get_instance();

// Registrar hooks de instalación
register_activation_hook(__FILE__, ['Image_Cleaner_Pro', 'activate']);
register_deactivation_hook(__FILE__, ['Image_Cleaner_Pro', 'deactivate']);
