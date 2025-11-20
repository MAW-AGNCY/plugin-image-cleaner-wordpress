<?php 
if (!defined('ABSPATH')) exit;

$logger = Image_Cleaner_Pro::get_instance()->get_logger();

// --- 1. PROCESAR ACCIONES (Antes de pintar HTML) ---

// Exportar CSV
if (isset($_POST['export_csv']) && check_admin_referer('maw_reports_action', 'maw_nonce')) {
    $logger->export_csv(); // Esto detiene la ejecución y descarga el archivo
}

// Vaciar Logs
if (isset($_POST['clear_logs']) && check_admin_referer('maw_reports_action', 'maw_nonce')) {
    global $wpdb;
    $table = $wpdb->prefix . 'maw_image_logs';
    $wpdb->query("TRUNCATE TABLE $table");
    echo '<div class="notice notice-success is-dismissible"><p>Historial de auditoría vaciado correctamente.</p></div>';
}

// --- 2. PREPARAR DATOS ---

// Filtros
$current_filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// Obtener Logs
$logs = $logger->get_logs($per_page, $paged, $current_filter);
$total_logs = $logger->get_total_logs($current_filter);
$total_pages = ceil($total_logs / $per_page);

// Estadísticas
$stats_raw = $logger->get_stats();
$stats = ['deleted' => 0, 'recovered' => 0, 'error' => 0];
foreach ($stats_raw as $s) {
    if(isset($stats[$s->action])) $stats[$s->action] = $s->count;
}
?>

<div class="maw-wrap">
    <!-- Header -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-analytics"></span> Auditoría y Reportes</h1>
        </div>
        <div class="maw-badge">Registros Totales: <?php echo $total_logs; ?></div>
    </div>

    <!-- Métricas Principales -->
    <div class="maw-metrics-row">
        <div class="maw-metric-card">
            <div class="maw-metric-icon danger">
                <span class="dashicons dashicons-trash"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Archivos Borrados</h4>
                <div class="value"><?php echo number_format_i18n($stats['deleted']); ?></div>
            </div>
        </div>

        <div class="maw-metric-card">
            <div class="maw-metric-icon" style="background:#eef9ef; color:var(--maw-success);">
                <span class="dashicons dashicons-image-rotate"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Recuperados</h4>
                <div class="value"><?php echo number_format_i18n($stats['recovered']); ?></div>
            </div>
        </div>

        <div class="maw-metric-card">
            <div class="maw-metric-icon warning">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Errores / Alertas</h4>
                <div class="value"><?php echo number_format_i18n($stats['error']); ?></div>
            </div>
        </div>
    </div>

    <!-- Barra de Herramientas -->
    <div class="maw-toolbar">
        <form method="get" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="page" value="image-cleaner-reports">
            <strong style="color:var(--maw-dark);">Filtrar por:</strong>
            <select name="filter" onchange="this.form.submit()">
                <option value="">Todas las acciones</option>
                <option value="deleted" <?php selected($current_filter, 'deleted'); ?>>Borrados</option>
                <option value="recovered" <?php selected($current_filter, 'recovered'); ?>>Recuperados</option>
                <option value="error" <?php selected($current_filter, 'error'); ?>>Errores</option>
            </select>
        </form>

        <form method="post" style="display:flex; gap:10px;">
            <?php wp_nonce_field('maw_reports_action', 'maw_nonce'); ?>
            <button type="submit" name="export_csv" class="button button-secondary">
                <span class="dashicons dashicons-download" style="margin-top:3px;"></span> Exportar CSV
            </button>
            <button type="submit" name="clear_logs" class="button button-link-delete" onclick="return confirm('¿Seguro que quieres borrar todo el historial?');">
                Vaciar Logs
            </button>
        </form>
    </div>

    <!-- Tabla de Logs -->
    <div class="maw-card">
        <?php if (empty($logs)) : ?>
            <div style="text-align:center; padding:40px; color:#888;">
                <span class="dashicons dashicons-clipboard" style="font-size:48px; margin-bottom:10px;"></span>
                <p>No hay registros que coincidan con tu búsqueda.</p>
            </div>
        <?php else : ?>
            <table class="maw-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Archivo</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : 
                        $user = get_userdata($log->user_id);
                        $username = $user ? $user->user_login : 'Sistema (Cron)';
                        
                        // Estilos de Badges
                        $badge_class = 'protected'; // Default gris/amarillo
                        if ($log->action == 'deleted') $badge_class = 'unused'; // Rojo
                        if ($log->action == 'recovered') $badge_class = 'in-use'; // Verde
                        if ($log->action == 'error') $badge_class = 'warning';
                    ?>
                    <tr>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->time)); ?></td>
                        <td>
                            <span style="font-weight:600; color:var(--maw-dark);">
                                <span class="dashicons dashicons-admin-users" style="font-size:14px; vertical-align:middle;"></span> 
                                <?php echo esc_html($username); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst($log->action); ?>
                            </span>
                        </td>
                        <td><strong><?php echo esc_html($log->image_name); ?></strong></td>
                        <td style="color:#666; font-size:12px;"><?php echo esc_html($log->details); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $base_url = add_query_arg('filter', $current_filter);
                    for ($i = 1; $i <= min(10, $total_pages); $i++) {
                        $class = ($paged == $i) ? 'button-primary' : 'button-secondary';
                        echo '<a href="' . esc_url(add_query_arg('paged', $i, $base_url)) . '" class="button ' . $class . '">' . $i . '</a> ';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
