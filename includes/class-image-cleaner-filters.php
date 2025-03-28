<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Cleaner_Admin {
    public function __construct() {
        $this->add_admin_hooks();
    }

    /**
     * Add hooks for the admin panel.
     */
    private function add_admin_hooks() {
        if (!has_action('admin_menu', [$this, 'register_admin_menu'])) {
            add_action('admin_menu', [$this, 'register_admin_menu']);
        }
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Register admin menu pages.
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Image Cleaner', 'image-cleaner'),
            __('Image Cleaner', 'image-cleaner'),
            'manage_options',
            'image-cleaner-overview',
            [$this, 'render_overview_page'],
            'dashicons-admin-media'
        );

        add_submenu_page(
            'image-cleaner-overview',
            __('Filters', 'image-cleaner'),
            __('Filters', 'image-cleaner'),
            'manage_options',
            'image-cleaner-filters',
            [$this, 'render_filters_page']
        );

        add_submenu_page(
            'image-cleaner-overview',
            __('Reports', 'image-cleaner'),
            __('Reports', 'image-cleaner'),
            'manage_options',
            'image-cleaner-reports',
            [$this, 'render_reports_page']
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'image-cleaner') === false) {
            return;
        }

        wp_enqueue_style(
            'image-cleaner-admin-style',
            IMAGE_CLEANER_PLUGIN_URL . 'assets/css/image-cleaner-admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'image-cleaner-admin-script',
            IMAGE_CLEANER_PLUGIN_URL . 'assets/js/image-cleaner-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    /**
     * Render the overview page.
     */
    public function render_overview_page() {
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/overview-page.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="error"><p>' . esc_html__('Overview page file not found.', 'image-cleaner') . '</p></div>';
        }
    }

    /**
     * Render the filters page.
     */
    public function render_filters_page() {
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/filters-page.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="error"><p>' . esc_html__('Filters page file not found.', 'image-cleaner') . '</p></div>';
        }
    }

    /**
     * Render the reports page.
     */
    public function render_reports_page() {
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'admin/reports-page.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="error"><p>' . esc_html__('Reports page file not found.', 'image-cleaner') . '</p></div>';
        }
    }
}