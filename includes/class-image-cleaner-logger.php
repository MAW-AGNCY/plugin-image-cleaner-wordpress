<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Class MAW_Image_Logger
 * Gestiona el registro de auditoría de acciones realizadas por el plugin.
 */
class MAW_Image_Logger {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'maw_image_logs';
    }

    /**
     * Crea la tabla en la base de datos al activar el plugin.
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'maw_image_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            image_name varchar(255) NOT NULL,
            details text,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Registra un evento en el log.
     */
    public function log($action, $image_name, $details = '') {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'time'       => current_time('mysql'),
                'user_id'    => get_current_user_id(),
                'action'     => sanitize_text_field($action),
                'image_name' => sanitize_text_field($image_name),
                'details'    => sanitize_textarea_field($details)
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
    }

    /**
     * Obtiene los logs de la base de datos con paginación.
     */
    public function get_logs($per_page = 20, $page = 1) {
        global $wpdb;
        $offset = ($page - 1) * $per_page;
        
        // Verificar si la tabla existe antes de consultar (evita errores en primera carga)
        if($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
            return [];
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY time DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
    }

    /**
     * Obtiene el conteo total de logs.
     */
    public function get_total_logs() {
        global $wpdb;
        if($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
            return 0;
        }
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    /**
     * Renderiza la tabla HTML para el panel de administración.
     */
    public function render_logs_table() {
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $logs = $this->get_logs($per_page, $page);
        $total = $this->get_total_logs();
        $total_pages = ceil($total / $per_page);

        echo '<div class="card maw-module" style="margin-top:20px; padding:0;">';
        echo '<h3 style="padding:15px; margin:0; border-bottom:1px solid #ccd0d4; background:#f9f9f9;">' . __('Historial de Auditoría', 'image-cleaner') . '</h3>';
        
        if (empty($logs)) {
            echo '<p style="padding:20px;">' . __('No hay registros de actividad aún.', 'image-cleaner') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped" style="border:none;">';
            echo '<thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Archivo</th><th>Detalles</th></tr></thead>';
            echo '<tbody>';
            foreach ($logs as $log) {
                $user_info = get_userdata($log->user_id);
                $username = $user_info ? $user_info->user_login : 'Sistema';
                
                $color = 'black';
                if ($log->action === 'deleted') $color = '#d63638';
                if ($log->action === 'recovered') $color = '#00a32a';

                echo '<tr>';
                echo '<td>' . esc_html($log->time) . '</td>';
                echo '<td>' . esc_html($username) . '</td>';
                echo '<td style="color:'.esc_attr($color).'; font-weight:bold; text-transform:uppercase;">' . esc_html($log->action) . '</td>';
                echo '<td>' . esc_html($log->image_name) . '</td>';
                echo '<td>' . esc_html($log->details) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';

            if ($total_pages > 1) {
                echo '<div class="tablenav bottom" style="padding: 10px;"><div class="tablenav-pages">';
                for ($i = 1; $i <= min(10, $total_pages); $i++) {
                    $current = ($page == $i) ? 'current' : '';
                    echo '<a class="button '.$current.'" href="'.esc_url(add_query_arg('paged', $i)).'">' . $i . '</a> ';
                }
                echo '</div></div>';
            }
        }
        echo '</div>';
    }
}
