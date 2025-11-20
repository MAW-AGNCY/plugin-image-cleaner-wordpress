<?php
if (!defined('ABSPATH')) {
    exit; 
}

class Image_Cleaner_Admin {
    public function __construct() {
        $this->add_admin_hooks();
    }

    private function add_admin_hooks() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function register_admin_menu() {
        // Menú Principal (Dashboard)
        add_menu_page(
            __('MAW Cleaner', 'image-cleaner'),
            __('MAW Cleaner', 'image-cleaner'),
            'manage_options',
            'image-cleaner-overview',
            [$this, 'render_overview_page'],
            'dashicons-performance',
            80
        );

        // Submenú: Dashboard (Repetido para que sea la primera opción)
        add_submenu_page(
            'image-cleaner-overview',
            __('Dashboard', 'image-cleaner'),
            __('Dashboard', 'image-cleaner'),
            'manage_options',
            'image-cleaner-overview',
            [$this, 'render_overview_page']
        );

        // Submenú: Filtros y Limpieza
        add_submenu_page(
            'image-cleaner-overview',
            __('Escanear y Limpiar', 'image-cleaner'),
            __('Escanear', 'image-cleaner'),
            'manage_options',
            'image-cleaner-filters',
            [$this, 'render_filters_page']
        );

        // Submenú: Reportes (Auditoría)
        add_submenu_page(
            'image-cleaner-overview',
            __('Reportes y Auditoría', 'image-cleaner'),
            __('Reportes', 'image-cleaner'),
            'manage_options',
            'image-cleaner-reports',
            [$this, 'render_reports_page']
        );

        // Submenú: Recuperación
        add_submenu_page(
            'image-cleaner-overview',
            __('Papelera / Recuperación', 'image-cleaner'),
            __('Recuperación', 'image-cleaner'),
            'manage_options',
            'image-cleaner-recovery',
            [$this, 'render_recovery_page']
        );

        // Submenú: Configuración Email
        add_submenu_page(
            'image-cleaner-overview',
            __('Notificaciones', 'image-cleaner'),
            __('Emails', 'image-cleaner'),
            'manage_options',
            'image-cleaner-emails',
            [$this, 'render_emails_page']
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'image-cleaner') === false) return;

        // Cargamos el CSS global de la interfaz
        wp_enqueue_style(
            'maw-admin-ui', 
            IMAGE_CLEANER_PLUGIN_URL . 'assets/css/maw-admin-ui.css', 
            [], 
            '1.2.0'
        );
        
        // Scripts generales si son necesarios
        wp_enqueue_script('jquery');
    }

    // --- Funciones de Renderizado --- //

    public function render_overview_page() {
        $this->load_view('overview-page.php');
    }

    public function render_filters_page() {
        $this->load_view('filters-page.php');
    }

    public function render_reports_page() {
        $this->load_view('reports-page.php');
    }

    public function render_recovery_page() {
        $this->load_view('recovery-page.php');
    }

    public function render_emails_page() {
        $this->load_view('email-page.php');
    }

    /**
     * Helper para cargar vistas de forma segura
     */
    private function load_view($filename) {
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/' . $filename;
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="notice notice-error"><p>Error: No se encuentra el archivo de vista: ' . esc_html($filename) . '</p></div>';
        }
    }
}
