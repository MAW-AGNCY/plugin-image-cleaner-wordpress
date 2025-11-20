<?php 
if (!defined('ABSPATH')) exit; 

global $wpdb;

// --- LÓGICA DE CÁLCULO DE DATOS (CON CACHÉ) ---

// Forzar recálculo si el usuario pulsa el botón
if (isset($_GET['recalc']) && $_GET['recalc'] == '1') {
    delete_transient('maw_dashboard_stats_v25');
}

$stats = get_transient('maw_dashboard_stats_v25');

if (false === $stats) {
    // 1. Conteos Básicos
    $total_files = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit'");
    $trash_files = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'trash'");
    
    // 2. Análisis de Huérfanos (Aproximación Rápida)
    // Buscamos archivos sin padre y que no sean thumbnails de nadie
    $orphans_query = "
        SELECT ID FROM $wpdb->posts p
        WHERE p.post_type = 'attachment' 
        AND p.post_status = 'inherit' 
        AND p.post_parent = 0
        AND NOT EXISTS (
            SELECT * FROM $wpdb->postmeta pm 
            WHERE pm.meta_key = '_thumbnail_id' 
            AND pm.meta_value = p.ID
        )
        LIMIT 2000"; // Límite de seguridad para rendimiento

    $orphan_ids = $wpdb->get_col($orphans_query);
    $orphan_count = count($orphan_ids);
    
    // 3. Cálculo de Pesos (Iterativo)
    // Nota: Esto puede ser pesado en sitios gigantes, por eso usamos Transients y un límite
    $total_library_size = 0;
    $recoverable_size = 0;
    
    // A. Peso Recuperable (De los huérfanos detectados)
    foreach ($orphan_ids as $oid) {
        $path = get_attached_file($oid);
        if (file_exists($path)) {
            $recoverable_size += filesize($path);
        }
    }

    // B. Peso Total Librería (Muestreo de los últimos 1000 para estimar o cálculo real si son pocos)
    // Para ser exactos sin colgar el servidor, sumamos meta '_wp_attached_file' size si existe, 
    // o hacemos un cálculo aproximado. Aquí hacemos un cálculo real sobre los IDs obtenidos.
    // En una versión Enterprise real, esto se haría en background.
    // Aquí obtenemos una muestra representativa para no colgar PHP.
    $all_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' LIMIT 5000");
    foreach ($all_ids as $aid) {
        $path = get_attached_file($aid);
        if (file_exists($path)) {
            $total_library_size += filesize($path);
        }
    }
    // Si hay más de 5000, hacemos una proyección simple
    if ($total_files > 5000) {
        $avg_size = $total_library_size / 5000;
        $total_library_size = $avg_size * $total_files;
    }

    // 4. Desglose por Tipos
    $types = $wpdb->get_results("SELECT post_mime_type, COUNT(*) as count FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' GROUP BY post_mime_type ORDER BY count DESC LIMIT 5");

    $stats = [
        'total' => $total_files,
        'trash' => $trash_files,
        'orphans' => $orphan_count,
        'total_size' => $total_library_size,
        'savings' => $recoverable_size,
        'types' => $types,
        'timestamp' => current_time('mysql')
    ];
    
    set_transient('maw_dashboard_stats_v25', $stats, 12 * HOUR_IN_SECONDS);
}

// Formateo visual
$formatted_total_size = size_format($stats['total_size']);
$formatted_savings = size_format($stats['savings']);
$trash_warning = ($stats['trash'] > 0);
?>

<div class="maw-wrap">
    <!-- Header -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-dashboard"></span> Dashboard <span class="maw-badge">Enterprise</span></h1>
        </div>
        <div>
            <span style="color:#888; font-size:12px; margin-right:10px;">Datos: <?php echo date('H:i d/m', strtotime($stats['timestamp'])); ?></span>
            <a href="admin.php?page=image-cleaner-overview&recalc=1" class="button button-small"><span class="dashicons dashicons-update"></span> Actualizar Datos</a>
        </div>
    </div>

    <!-- 1. FILA DE MÉTRICAS (4 COLUMNAS) -->
    <div class="maw-metrics-row" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        
        <!-- Total Archivos -->
        <div class="maw-metric-card">
            <div class="maw-metric-icon">
                <span class="dashicons dashicons-images-alt2"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Total Archivos</h4>
                <div class="value"><?php echo number_format_i18n($stats['total']); ?></div>
            </div>
        </div>

        <!-- Peso Total -->
        <div class="maw-metric-card">
            <div class="maw-metric-icon warning">
                <span class="dashicons dashicons-database"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Peso Librería</h4>
                <div class="value"><?php echo $formatted_total_size; ?></div>
            </div>
        </div>

        <!-- Espacio Recuperable -->
        <div class="maw-metric-card maw-saving-card">
            <div class="maw-metric-icon" style="background:#eef9ef; color:var(--maw-success);">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Recuperable</h4>
                <div class="value maw-saving-value"><?php echo $formatted_savings; ?></div>
                <div style="font-size:11px; color:#666;"><?php echo $stats['orphans']; ?> archivos huérfanos</div>
            </div>
        </div>

        <!-- Papelera -->
        <div class="maw-metric-card" style="<?php echo $trash_warning ? 'border-color:var(--maw-danger);' : ''; ?>">
            <div class="maw-metric-icon danger">
                <span class="dashicons dashicons-trash"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Papelera</h4>
                <div class="value"><?php echo number_format_i18n($stats['trash']); ?></div>
                <div style="font-size:11px; color:#666;">Pendientes borrado</div>
            </div>
        </div>

    </div>

    <?php if($trash_warning): ?>
        <div class="maw-alert warning">
            <p><strong>Consejo de optimización:</strong> Tienes <strong><?php echo $stats['trash']; ?></strong> archivos en la papelera. Vacíala para liberar espacio real en el servidor. <a href="admin.php?page=image-cleaner-recovery">Ir a Papelera</a>.</p>
        </div>
    <?php endif; ?>

    <!-- 2. GRID DE CONTENIDO Y ACCIONES -->
    <div class="maw-content-grid">
        
        <!-- Columna Izquierda: Desglose -->
        <div class="maw-card">
            <h3><span class="dashicons dashicons-chart-pie"></span> Distribución por Tipo</h3>
            <div style="margin-top:20px;">
                <?php if($stats['types']): foreach($stats['types'] as $type): 
                    $mime = $type->post_mime_type;
                    $count = $type->count;
                    // Evitar división por cero
                    $percent_bar = ($stats['total'] > 0) ? ($count / $stats['total']) * 100 : 0;
                    
                    $bar_class = '';
                    if(strpos($mime, 'png') !== false) $bar_class = 'png';
                    if(strpos($mime, 'pdf') !== false) $bar_class = 'pdf';
                ?>
                <div class="maw-type-row">
                    <div style="width:80px; font-weight:600; text-transform:uppercase; font-size:11px; color:#555;">
                        <?php echo str_replace(['image/', 'application/'], '', $mime); ?>
                    </div>
                    <div class="maw-type-bar-bg">
                        <div class="maw-type-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $percent_bar; ?>%;"></div>
                    </div>
                    <div style="width:60px; text-align:right; font-weight:bold; color:#444;">
                        <?php echo number_format_i18n($count); ?>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <p style="color:#999;">No hay datos suficientes para mostrar el desglose.</p>
                <?php endif; ?>
            </div>
            <div style="margin-top:30px; padding-top:20px; border-top:1px dashed #eee;">
                <p class="description">Este gráfico muestra qué tipo de archivos consumen más "slots" en tu base de datos media.</p>
            </div>
        </div>

        <!-- Columna Derecha: Accesos Rápidos -->
        <div>
            <h3 style="margin-bottom:15px; color:#333;">Accesos Directos</h3>
            <div class="maw-actions-grid">
                
                <a href="admin.php?page=image-cleaner-filters" class="maw-action-btn">
                    <span class="dashicons dashicons-filter"></span>
                    <span class="maw-action-title">Escanear</span>
                    <span class="maw-action-desc">Limpiar <?php echo $formatted_savings; ?></span>
                </a>

                <a href="admin.php?page=image-cleaner-recovery" class="maw-action-btn">
                    <span class="dashicons dashicons-undo"></span>
                    <span class="maw-action-title">Recuperar</span>
                    <span class="maw-action-desc">Gestión Papelera</span>
                </a>

                <a href="admin.php?page=image-cleaner-reports" class="maw-action-btn">
                    <span class="dashicons dashicons-clipboard"></span>
                    <span class="maw-action-title">Logs</span>
                    <span class="maw-action-desc">Ver auditoría</span>
                </a>

                <a href="admin.php?page=image-cleaner-emails" class="maw-action-btn">
                    <span class="dashicons dashicons-email-alt"></span>
                    <span class="maw-action-title">Alertas</span>
                    <span class="maw-action-desc">Configurar avisos</span>
                </a>

            </div>
            
            <!-- Estado del Sistema -->
            <div class="maw-card" style="margin-top:20px; background:#f9f9f9; border:none;">
                <h4 style="margin:0 0 10px 0;">Estado del Sistema</h4>
                <ul style="margin:0; font-size:12px; color:#666; line-height:1.6;">
                    <li><strong>PHP Memory:</strong> <?php echo ini_get('memory_limit'); ?></li>
                    <li><strong>Escáner:</strong> Deep Scan Activo</li>
                    <li><strong>Papelera WP:</strong> <?php echo (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS==0) ? '<span style="color:red">Desactivada</span>' : '<span style="color:green">Protegida</span>'; ?></li>
                </ul>
            </div>
        </div>

    </div>
</div>
