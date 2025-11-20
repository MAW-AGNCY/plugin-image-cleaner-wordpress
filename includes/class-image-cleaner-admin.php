<?php
if (!defined('ABSPATH')) exit;

class Image_Cleaner_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // HOOK PARA EXPORTACIÓN CSV LIMPIA (Sin HTML)
        add_action('admin_post_maw_export_csv', [$this, 'handle_csv_export']);
    }

    /**
     * Manejador de descarga CSV
     */
    public function handle_csv_export() {
        // 1. Seguridad
        if (!current_user_can('manage_options')) wp_die('Acceso denegado');
        check_admin_referer('maw_reports_action', 'maw_nonce');

        // 2. Generar CSV
        $logger = Image_Cleaner_Pro::get_instance()->get_logger();
        $logger->export_csv();
        // La función export_csv termina con exit, así que no sigue cargando HTML
    }

    public function register_admin_menu() {
        add_menu_page('MAW Cleaner', 'MAW Cleaner', 'manage_options', 'image-cleaner-overview', [$this, 'render_overview_page'], 'dashicons-performance', 80);
        add_submenu_page('image-cleaner-overview', 'Dashboard', 'Dashboard', 'manage_options', 'image-cleaner-overview', [$this, 'render_overview_page']);
        add_submenu_page('image-cleaner-overview', 'Escanear y Limpiar', 'Escanear', 'manage_options', 'image-cleaner-filters', [$this, 'render_filters_page']);
        add_submenu_page('image-cleaner-overview', 'Reportes y Auditoría', 'Reportes', 'manage_options', 'image-cleaner-reports', [$this, 'render_reports_page']);
        add_submenu_page('image-cleaner-overview', 'Papelera / Recuperación', 'Recuperación', 'manage_options', 'image-cleaner-recovery', [$this, 'render_recovery_page']);
        add_submenu_page('image-cleaner-overview', 'Notificaciones', 'Notificaciones', 'manage_options', 'image-cleaner-emails', [$this, 'render_emails_page']);
        add_submenu_page('image-cleaner-overview', 'Centro de Ayuda', 'Ayuda', 'manage_options', 'image-cleaner-help', [$this, 'render_help_page']);
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'image-cleaner') === false) return;
        wp_enqueue_style('maw-admin-ui', IMAGE_CLEANER_PLUGIN_URL . 'assets/css/maw-admin-ui.css', [], '2.3.0');
        wp_enqueue_script('maw-admin-js', IMAGE_CLEANER_PLUGIN_URL . 'assets/js/maw-admin.js', ['jquery'], '2.3.0', true);
    }

    // Renders
    public function render_overview_page() { $this->load_view('overview-page.php'); }
    public function render_filters_page() { $this->load_view('filters-page.php'); }
    public function render_reports_page() { $this->load_view('reports-page.php'); }
    public function render_recovery_page() { $this->load_view('recovery-page.php'); }
    public function render_emails_page() { $this->load_view('email-page.php'); }
    public function render_help_page() { $this->load_view('help-page.php'); }

    private function load_view($filename) {
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/' . $filename;
        if (file_exists($file)) include $file;
    }
}
