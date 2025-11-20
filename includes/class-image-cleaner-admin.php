<?php
if (!defined('ABSPATH')) exit;

class Image_Cleaner_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
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

        // CSS Premium
        wp_enqueue_style('maw-admin-ui', IMAGE_CLEANER_PLUGIN_URL . 'assets/css/maw-admin-ui.css', [], '2.1.0');
        // JS Interactivo
        wp_enqueue_script('maw-admin-js', IMAGE_CLEANER_PLUGIN_URL . 'assets/js/maw-admin.js', ['jquery'], '2.1.0', true);
    }

    // Funciones de renderizado (Vistas)
    public function render_overview_page() { $this->load_view('overview-page.php'); }
    public function render_filters_page() { $this->load_view('filters-page.php'); }
    public function render_reports_page() { $this->load_view('reports-page.php'); }
    public function render_recovery_page() { $this->load_view('recovery-page.php'); }
    public function render_emails_page() { $this->load_view('email-page.php'); }
    public function render_help_page() { $this->load_view('help-page.php'); }

    private function load_view($filename) {
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/' . $filename;
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="notice notice-error"><p>Error: Vista no encontrada (' . esc_html($filename) . ')</p></div>';
        }
    }
}
