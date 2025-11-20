<?php 
if (!defined('ABSPATH')) exit;

// Obtener instancia del Logger
$logger = Image_Cleaner_Pro::get_instance()->get_logger();

// --- 1. PROCESAR ACCIONES (POST) ---

// Vaciar Logs (Mantenimiento)
if (isset($_POST['clear_logs']) && check_admin_referer('maw_reports_action', 'maw_nonce')) {
    global $wpdb;
    $table = $wpdb->prefix . 'maw_image_logs';
    // Truncate vacía la tabla y resetea los IDs
    $wpdb->query("TRUNCATE TABLE $table");
    echo '<div class="notice notice-success is-dismissible"><p>Historial de auditoría vaciado correctamente.</p></div>';
}

// --- 2. PREPARAR DATOS PARA LA VISTA ---

// Filtros de URL (GET)
$current_filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// Obtener Logs filtrados y paginados
$logs = $logger->get_logs($per_page, $paged, $current_filter);
$total_logs = $logger->get_total_logs($current_filter);
$total_pages = ceil($total_logs / $per_page);

// Obtener Estadísticas Globales (Para las tarjetas superiores)
$stats_raw = $logger->get_stats();
// Inicializar array para evitar errores si está vacío
$stats = ['deleted' => 0, 'recovered' => 0, 'error' => 0];
foreach ($stats_raw as $s) {
    if(isset($stats[$s->action])) $stats[$s->action] = $s->count;
}
?>

<div class="maw-wrap">
    
    <!-- CABECERA -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-analytics"></span> Auditoría y Reportes</h1>
        </div>
        <div class="maw-badge">Total Registros: <?php echo number_format_i18n($total_logs); ?></div>
    </div>

    <!-- 1. SECCIÓN DE MÉTRICAS (KPIs) -->
    <div class="maw-metrics-row">
        <!-- Tarjeta Borrados -->
        <div class="maw-metric-card">
            <div class="maw-metric-icon danger">
                <span class="dashicons dashicons-trash"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Archivos Borrados</h4>
                <div class="value"><?php echo number_format_i18n($stats['deleted']); ?></div>
            </div>
        </div>

        <!-- Tarjeta Recuperados -->
        <div class="maw-metric-card">
            <div class="maw-metric-icon" style="background:#eef9ef; color:var(--maw-success);">
                <span class="dashicons dashicons-undo"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Recuperados</h4>
                <div class="value"><?php echo number_format_i18n($stats['recovered']); ?></div>
            </div>
        </div>

        <!-- Tarjeta Errores/Alertas -->
        <div class="maw-metric-card">
            <div class="maw-metric-icon warning">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Alertas Sistema</h4>
                <div class="value"><?php echo number_format_i18n($stats['error']); ?></div>
            </div>
        </div>
    </div>

    <!-- 2. BARRA DE HERRAMIENTAS (Filtros y Botones) -->
    <div class="maw-toolbar">
        
        <!-- IZQUIERDA: Filtros (Formulario GET para mantener estado en URL) -->
        <form method="get" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="page" value="image-cleaner-reports">
            
            <strong style="color:var(--maw-dark); font-size:13px;">Filtrar por acción:</strong>
            <select name="filter" onchange="this.form.submit()" style="min-width:150px;">
                <option value="">Mostrar Todo</option>
                <option value="deleted" <?php selected($current_filter, 'deleted'); ?>>Borrados</option>
                <option value="recovered" <?php selected($current_filter, 'recovered'); ?>>Recuperados</option>
                <option value="error" <?php selected($current_filter, 'error'); ?>>Errores</option>
            </select>
        </form>

        <!-- DERECHA: Acciones Globales -->
        <div style="display:flex; gap:10px;">
            
            <!-- Exportar CSV (Formulario a admin-post.php para descarga limpia) -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="maw_export_csv">
                <?php wp_nonce_field('maw_reports_action', 'maw_nonce'); ?>
                <button type="submit" class="button button-secondary">
                    <span class="dashicons dashicons-download" style="margin-top:3px;"></span> Exportar CSV
                </button>
            </form>
            
            <!-- Vaciar Logs -->
            <form method="post" onsubmit="return confirm('¿Estás seguro? Esto borrará todo el historial de auditoría permanentemente.');">
                <?php wp_nonce_field('maw_reports_action', 'maw_nonce'); ?>
                <button type="submit" name="clear_logs" class="button button-link-delete" style="color:var(--maw-danger); border-color:var(--maw-danger);">
                    Vaciar Historial
                </button>
            </form>

        </div>
    </div>

    <!-- 3. TABLA DE DATOS -->
    <div class="maw-card">
        <?php if (empty($logs)) : ?>
            <!-- Estado Vacío -->
            <div style="text-align:center; padding:50px 20px; color:#999;">
                <span class="dashicons dashicons-clipboard" style="font-size:60px; margin-bottom:15px; width:60px; height:60px;"></span>
                <h3 style="margin:0; font-weight:400;">No hay registros disponibles</h3>
                <p>No se han encontrado eventos que coincidan con tus filtros.</p>
            </div>
        <?php else : ?>
            <!-- Tabla Premium -->
            <table class="maw-table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario Responsable</th>
                        <th>Tipo de Acción</th>
                        <th>Archivo Afectado</th>
                        <th>Detalles Técnicos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : 
                        // Obtener nombre de usuario
                        $user = get_userdata($log->user_id);
                        $username = $user ? $user->user_login : 'Sistema (Automático)';
                        $user_avatar = get_avatar($log->user_id, 24);
                        
                        // Estilos de Badges según acción
                        $badge_class = 'protected'; // Default (Gris/Amarillo)
                        $badge_label = ucfirst($log->action);

                        if ($log->action == 'deleted') {
                            $badge_class = 'unused'; // Rojo
                            $badge_label = 'Borrado';
                        }
                        if ($log->action == 'recovered') {
                            $badge_class = 'in-use'; // Verde
                            $badge_label = 'Restaurado';
                        }
                        if ($log->action == 'error') {
                            $badge_class = 'warning'; // Naranja
                            $badge_label = 'Error';
                        }
                    ?>
                    <tr>
                        <td>
                            <?php echo date_i18n(get_option('date_format'), strtotime($log->time)); ?>
                            <br>
                            <small style="color:#999;"><?php echo date_i18n(get_option('time_format'), strtotime($log->time)); ?></small>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <?php echo $user_avatar; ?>
                                <span style="font-weight:600; color:var(--maw-dark);"><?php echo esc_html($username); ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <?php echo esc_html($badge_label); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo esc_html($log->image_name); ?></strong>
                        </td>
                        <td style="color:#666; font-size:12px; max-width:300px;">
                            <?php echo esc_html($log->details); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 4. PAGINACIÓN -->
            <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    // Mantener el filtro actual en los enlaces de paginación
                    $base_url = add_query_arg('filter', $current_filter);
                    
                    // Enlace Anterior
                    if ($paged > 1) {
                        echo '<a href="' . esc_url(add_query_arg('paged', $paged - 1, $base_url)) . '" class="button">«</a> ';
                    }

                    // Números de página
                    for ($i = 1; $i <= min(10, $total_pages); $i++) {
                        $class = ($paged == $i) ? 'button-primary' : 'button-secondary';
                        echo '<a href="' . esc_url(add_query_arg('paged', $i, $base_url)) . '" class="button ' . $class . '">' . $i . '</a> ';
                    }

                    // Enlace Siguiente
                    if ($paged < $total_pages) {
                        echo '<a href="' . esc_url(add_query_arg('paged', $paged + 1, $base_url)) . '" class="button">»</a> ';
                    }
                    ?>
                </div>
                <div class="alignright actions">
                    <span class="displaying-num">Página <?php echo $paged; ?> de <?php echo $total_pages; ?></span>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
