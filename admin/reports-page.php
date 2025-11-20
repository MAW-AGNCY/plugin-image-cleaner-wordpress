<?php
if (!defined('ABSPATH')) {
    exit;
}

// Obtener instancia del Logger
$logger = Image_Cleaner_Pro::get_instance()->get_logger();

// Lógica para borrar logs antiguos (Mantenimiento)
if (isset($_POST['maw_clear_logs']) && check_admin_referer('maw_clear_logs_action', 'maw_nonce')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'maw_image_logs';
    $wpdb->query("TRUNCATE TABLE $table_name");
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Historial de auditoría vaciado correctamente.', 'image-cleaner') . '</p></div>';
}
?>

<div class="wrap maw-dashboard-wrapper">
    <div class="maw-header">
        <div class="maw-logo">
            <span class="dashicons dashicons-clipboard"></span>
            <?php esc_html_e('Reportes y Auditoría', 'image-cleaner'); ?>
        </div>
    </div>

    <p class="description">
        <?php esc_html_e('Este registro muestra todas las acciones críticas realizadas por el plugin. Úsalo para auditar qué usuarios han eliminado o recuperado archivos.', 'image-cleaner'); ?>
    </p>

    <!-- Tarjetas de Resumen de Reportes -->
    <div class="maw-stats-grid" style="margin-top: 20px;">
        <?php 
            // Obtener estadísticas rápidas de los logs
            global $wpdb;
            $table = $wpdb->prefix . 'maw_image_logs';
            // Verificar si la tabla existe para evitar errores fatales en instalación limpia
            if($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $deleted_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE action = 'deleted'");
                $recovered_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE action = 'recovered'");
            } else {
                $deleted_count = 0;
                $recovered_count = 0;
            }
        ?>
        
        <div class="maw-card" style="border-left: 4px solid var(--maw-danger);">
            <span class="maw-stat-label"><?php esc_html_e('Imágenes Borradas', 'image-cleaner'); ?></span>
            <div class="maw-stat-number"><?php echo intval($deleted_count); ?></div>
        </div>

        <div class="maw-card" style="border-left: 4px solid var(--maw-success);">
            <span class="maw-stat-label"><?php esc_html_e('Imágenes Recuperadas', 'image-cleaner'); ?></span>
            <div class="maw-stat-number"><?php echo intval($recovered_count); ?></div>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="maw-module">
        <?php 
            // Renderizamos la tabla usando la clase Logger
            $logger->render_logs_table(); 
        ?>
    </div>

    <!-- Zona de Peligro / Mantenimiento -->
    <div style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px;">
        <h3><?php esc_html_e('Mantenimiento de Logs', 'image-cleaner'); ?></h3>
        <form method="post" onsubmit="return confirm('<?php esc_html_e('¿Estás seguro de que quieres borrar todo el historial?', 'image-cleaner'); ?>');">
            <?php wp_nonce_field('maw_clear_logs_action', 'maw_nonce'); ?>
            <button type="submit" name="maw_clear_logs" class="button button-link-delete">
                <?php esc_html_e('Vaciar historial de auditoría', 'image-cleaner'); ?>
            </button>
        </form>
    </div>
</div>
