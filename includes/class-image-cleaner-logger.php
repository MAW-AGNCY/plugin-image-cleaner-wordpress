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
            PRIMARY KEY  (id)
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
     * Renderiza la tabla con el NUEVO DISEÑO CSS Premium
     */
    public function render_logs_table() {
        global $wpdb;
        
        // Verificar tabla
        if($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
            echo '<div class="notice notice-warning"><p>Tabla de logs no encontrada. Reactiva el plugin.</p></div>';
            return;
        }

        // Paginación
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;

        $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} ORDER BY time DESC LIMIT %d OFFSET %d", $per_page, $offset));
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $pages = ceil($total / $per_page);

        if (empty($logs)) {
            echo '<p style="padding:20px; text-align:center; color:#888;">No hay actividad registrada aún.</p>';
            return;
        }

        // --- TABLA CON DISEÑO MAW-ADMIN-UI --- //
        echo '<table class="maw-table-view">';
        echo '<thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Archivo</th><th>Detalles</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($logs as $log) {
            $user = get_userdata($log->user_id);
            $username = $user ? $user->user_login : 'System';
            
            // Badges de acción
            $badge_class = ($log->action === 'deleted') ? 'unused' : (($log->action === 'recovered') ? 'in-use' : 'protected');
            $badge_label = ucfirst($log->action);

            echo '<tr>';
            echo '<td>' . esc_html($log->time) . '</td>';
            echo '<td>' . esc_html($username) . '</td>';
            echo '<td><span class="status-badge ' . $badge_class . '">' . esc_html($badge_label) . '</span></td>';
            echo '<td><strong>' . esc_html($log->image_name) . '</strong></td>';
            echo '<td style="color:#666;">' . esc_html($log->details) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Paginación
        if ($pages > 1) {
            echo '<div class="tablenav bottom"><div class="tablenav-pages">';
            for ($i = 1; $i <= min(10, $pages); $i++) {
                $active = ($page == $i) ? 'button-primary' : 'button-secondary';
                echo '<a href="' . add_query_arg('paged', $i) . '" class="button ' . $active . '">' . $i . '</a> ';
            }
            echo '</div></div>';
        }
    }
}
