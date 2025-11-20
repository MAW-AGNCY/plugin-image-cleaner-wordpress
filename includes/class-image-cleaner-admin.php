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
        // 1. Menú Principal (Dashboard)
        add_menu_page(
            __('MAW Cleaner', 'image-cleaner'), // Título página
            __('MAW Cleaner', 'image-cleaner'), // Título menú
            'manage_options',
            'image-cleaner-overview',
            [$this, 'render_overview_page'],
            'dashicons-performance',
            80
        );

        // 2. Submenú: Dashboard (Repetido para UX estándar de WP)
        add_submenu_page(
            'image-cleaner-overview',
            __('Dashboard', 'image-cleaner'),
            __('Dashboard', 'image-cleaner'),
            'manage_options',
            'image-cleaner-overview',
            [$this, 'render_overview_page']
        );

        // 3. Submenú: Filtros (Escáner)
        add_submenu_page(
            'image-cleaner-overview',
            __('Escanear y Limpiar', 'image-cleaner'),
            __('Escanear', 'image-cleaner'),
            'manage_options',
            'image-cleaner-filters',
            [$this, 'render_filters_page']
        );

        // 4. Submenú: Reportes (Auditoría)
        add_submenu_page(
            'image-cleaner-overview',
            __('Reportes y Auditoría', 'image-cleaner'),
            __('Reportes', 'image-cleaner'),
            'manage_options',
            'image-cleaner-reports',
            [$this, 'render_reports_page']
        );

        // 5. Submenú: Recuperación (Papelera)
        add_submenu_page(
            'image-cleaner-overview',
            __('Papelera / Recuperación', 'image-cleaner'),
            __('Recuperación', 'image-cleaner'),
            'manage_options',
            'image-cleaner-recovery',
            [$this, 'render_recovery_page']
        );

        // 6. Submenú: Configuración Email
        add_submenu_page(
            'image-cleaner-overview',
            __('Notificaciones', 'image-cleaner'),
            __('Notificaciones', 'image-cleaner'), // Nombre más corto para menú
            'manage_options',
            'image-cleaner-emails',
            [$this, 'render_emails_page']
        );

        // 7. NUEVO: Submenú Ayuda y Soporte
        add_submenu_page(
            'image-cleaner-overview',
            __('Centro de Ayuda', 'image-cleaner'),
            __('Ayuda', 'image-cleaner'),
            'manage_options',
            'image-cleaner-help',
            [$this, 'render_help_page']
        );
    }

    public function enqueue_admin_scripts($hook) {
        // Solo cargar assets en páginas que contengan 'image-cleaner'
        if (strpos($hook, 'image-cleaner') === false) return;

        // CSS UI Premium
        wp_enqueue_style(
            'maw-admin-ui', 
            IMAGE_CLEANER_PLUGIN_URL . 'assets/css/maw-admin-ui.css', 
            [], 
            '2.0.0' // Versión actualizada
        );
        
        // jQuery es necesario para los scripts de UI
        wp_enqueue_script('jquery');
    }

    // --- Funciones de Renderizado de Vistas --- //

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
     * Renderiza la página de ayuda.
     */
    public function render_help_page() {
        $this->load_view('help-page.php');
    }

    /**
     * Helper privado para cargar vistas de forma segura y DRY.
     */
    private function load_view($filename) {
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/' . $filename;
        if (file_exists($file)) {
            include $file;
        } else {
            // Mensaje de error amigable en el admin
            echo '<div class="maw-wrap"><div class="notice notice-error inline"><p>';
            printf(
                esc_html__('Error crítico: No se encuentra el archivo de vista "%s". Por favor, verifica la instalación del plugin.', 'image-cleaner'),
                esc_html($filename)
            );
            echo '</p></div></div>';
        }
    }
}
