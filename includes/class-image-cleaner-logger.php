<?php
if (!defined('ABSPATH')) { exit; }

class MAW_Image_Logger {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'maw_image_logs';
    }

    /**
     * Crea tabla SQL al activar
     */
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'maw_image_logs';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            image_name varchar(255) NOT NULL,
            details text,
            PRIMARY KEY  (id),
            KEY action (action)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function log($action, $image_name, $details = '') {
        global $wpdb;
        $wpdb->insert($this->table_name, [
            'time' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'action' => sanitize_text_field($action),
            'image_name' => sanitize_text_field($image_name),
            'details' => sanitize_textarea_field($details)
        ]);
    }

    /**
     * Obtener logs con filtros
     */
    public function get_logs($per_page = 20, $page = 1, $filter = '') {
        global $wpdb;
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT * FROM {$this->table_name}";
        $args = [];

        if ($filter) {
            $sql .= " WHERE action = %s";
            $args[] = $filter;
        }

        $sql .= " ORDER BY time DESC LIMIT %d OFFSET %d";
        $args[] = $per_page;
        $args[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $args));
    }

    /**
     * Contar logs con filtros
     */
    public function get_total_logs($filter = '') {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$this->table_name}";
        if ($filter) {
            return $wpdb->get_var($wpdb->prepare($sql . " WHERE action = %s", $filter));
        }
        return $wpdb->get_var($sql);
    }

    /**
     * Obtener estadísticas rápidas para el dashboard de reportes
     */
    public function get_stats() {
        global $wpdb;
        // Verificar si la tabla existe primero
        if($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) return [];

        return $wpdb->get_results("SELECT action, COUNT(*) as count FROM {$this->table_name} GROUP BY action");
    }

    /**
     * Exportar CSV directo al navegador
     */
    public function export_csv() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT time, user_id, action, image_name, details FROM {$this->table_name} ORDER BY time DESC", ARRAY_A);

        if (empty($rows)) return;

        $filename = 'maw-audit-log-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Cabeceras CSV
        fputcsv($output, ['Fecha', 'Usuario', 'Acción', 'Archivo', 'Detalles']);

        foreach ($rows as $row) {
            $user = get_userdata($row['user_id']);
            $row['user_id'] = $user ? $user->user_login : 'System'; // Reemplazar ID por nombre
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
