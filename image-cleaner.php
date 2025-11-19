<?php
/**
 * Plugin Name: MAW Image Cleaner Pro
 * Description: Herramienta profesional para auditar, limpiar y optimizar la biblioteca de medios de WordPress. Incluye auditoría (logs), protección (whitelist) y recuperación.
 * Version: 1.1.0
 * Author: Mondays at Work
 * Author URI: https://www.mondaysatwork.com
 * Text Domain: image-cleaner
 */

// Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin.
define('IMAGE_CLEANER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLEANER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios (Orden de carga importante).
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-logger.php';    // Logger (Nuevo)
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-whitelist.php'; // Whitelist (Nuevo)
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-admin.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-ajax.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-recovery.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-reports.php';
require_once IMAGE_CLEANER_PLUGIN_DIR . 'includes/class-image-cleaner-emails.php';

/**
 * Clase Principal del Plugin (Singleton).
 */
class Image_Cleaner_Pro {
    private static $instance;

    // Instancias de componentes principales
    public $logger;
    public $whitelist_manager;

    /**
     * Obtener instancia única.
     */
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado.
     */
    private function __construct() {
        $this->initialize_classes();
    }

    /**
     * Inicializar clases y componentes.
     */
    private function initialize_classes() {
        // 1. Instanciar Utilidades Globales primero
        $this->logger = new MAW_Image_Logger();
        $this->whitelist_manager = new MAW_Image_Whitelist();

        // 2. Instanciar Lógica de Administración (Solo en backend)
        if (is_admin()) {
            // Pasamos las instancias globales al Admin si fuera necesario, 
            // aunque ahora usamos el Singleton para acceso global.
            new Image_Cleaner_Admin();
            new Image_Cleaner_Reports(); // Usa el logger
            new Image_Cleaner_Recovery();
            new Image_Cleaner_Emails();
        }

        // 3. Clases que corren en frontend o backend (AJAX)
        new Image_Cleaner_Ajax();
    }

    /**
     * Accesor público para el Logger.
     * @return MAW_Image_Logger
     */
    public function get_logger() {
        return $this->logger;
    }

    /**
     * Accesor público para la Whitelist.
     * @return MAW_Image_Whitelist
     */
    public function get_whitelist_manager() {
        return $this->whitelist_manager;
    }

    /**
     * Hook de Activación.
     */
    public static function activate() {
        // Crear tabla de base de datos para Logs
        MAW_Image_Logger::create_table();
        
        // Programar tareas cron
        Image_Cleaner_Emails::schedule_email_event();
        
        flush_rewrite_rules();
    }

    /**
     * Hook de Desactivación.
     */
    public static function deactivate() {
        Image_Cleaner_Emails::clear_email_event();
        flush_rewrite_rules();
    }
}

// Inicializar el plugin.
Image_Cleaner_Pro::get_instance();

// Registrar hooks de activación y desactivación.
register_activation_hook(__FILE__, ['Image_Cleaner_Pro', 'activate']);
register_deactivation_hook(__FILE__, ['Image_Cleaner_Pro', 'deactivate']);
