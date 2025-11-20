<?php 
if (!defined('ABSPATH')) exit; 

global $wpdb;

// --- LÓGICA DE DATOS (CON CACHÉ) ---

// 1. Verificar si se solicita recálculo forzado
if (isset($_GET['recalc']) && $_GET['recalc'] == '1') {
    delete_transient('maw_dashboard_stats');
}

// 2. Intentar obtener datos de la caché (Transient) para rendimiento
$stats = get_transient('maw_dashboard_stats');

if (false === $stats) {
    // A. Contadores Básicos (Rápidos)
    $total_files = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit'");
    $trash_files = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'trash'");
    
    // B. Huérfanos (Aproximación rápida: sin padre asignado)
    // Nota: El escáner profundo en la otra pestaña es más preciso, esto es para estadísticas rápidas.
    $orphan_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_parent = 0");

    // C. Desglose por Tipos
    $types = $wpdb->get_results("
        SELECT post_mime_type, COUNT(*) as count 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' AND post_status = 'inherit' 
        GROUP BY post_mime_type 
        ORDER BY count DESC 
        LIMIT 5
    ");

    // D. Cálculo de Peso Total (Pesado - Iterativo)
    // Limitamos a 5000 para evitar timeout en servidores compartidos si no es background process
    $all_attachments = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' LIMIT 5000");
    $total_size = 0;
    foreach ($all_attachments as $id) {
        $file_path = get_attached_file($id);
        if (file_exists($file_path)) {
            $total_size += filesize($file_path);
        }
    }

    // Guardar en array
    $stats = [
        'total' => $total_files,
        'trash' => $trash_files,
        'orphans' => $orphan_count,
        'types' => $types,
        'size' => $total_size,
        'timestamp' => current_time('mysql')
    ];

    // Guardar caché por 12 horas
    set_transient('maw_dashboard_stats', $stats, 12 * HOUR_IN_SECONDS);
}

// Formateo para vista
$formatted_size = size_format($stats['size']);
$orphan_percent = ($stats['total'] > 0) ? round(($stats['orphans'] / $stats['total']) * 100) : 0;

// Mensaje de advertencia si hay mucho en papelera
$trash_warning = ($stats['trash'] > 0) ? true : false;
?>

<div class="maw-wrap">
    <!-- Header -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-dashboard"></span> Visión General <span class="maw-badge">Premium</span></h1>
        </div>
        <div>
            <span style="color:#888; font-size:12px; margin-right:10px;">Última actualización: <?php echo date('H:i', strtotime($stats['timestamp'])); ?></span>
            <a href="admin.php?page=image-cleaner-overview&recalc=1" class="button button-small"><span class="dashicons dashicons-update"></span> Recalcular</a>
        </div>
    </div>

    <!-- 1. Fila de Métricas Principales -->
    <div class="maw-metrics-row">
        
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
                <div class="value"><?php echo $formatted_size; ?></div>
            </div>
        </div>

        <!-- Huérfanos -->
        <div class="maw-metric-card">
            <div class="maw-metric-icon danger">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="maw-metric-data">
                <h4>Posibles Huérfanos</h4>
                <div class="value"><?php echo number_format_i18n($stats['orphans']); ?> <small style="font-size:12px; font-weight:normal; color:#888">(<?php echo $orphan_percent; ?>%)</small></div>
            </div>
        </div>

        <!-- Papelera -->
        <div class="maw-metric-card" style="<?php echo $trash_warning ? 'border-color:var(--maw-danger);' : ''; ?>">
            <div class="maw-metric-icon">
                <span class="dashicons dashicons-trash"></span>
            </div>
            <div class="maw-metric-data">
                <h4>En Papelera</h4>
                <div class="value"><?php echo number_format_i18n($stats['trash']); ?></div>
            </div>
        </div>

    </div>

    <?php if($trash_warning): ?>
        <div class="maw-alert warning">
            <p><strong>Atención:</strong> Tienes <strong><?php echo $stats['trash']; ?></strong> archivos en la papelera ocupando espacio. <a href="admin.php?page=image-cleaner-recovery">Revisar ahora</a>.</p>
        </div>
    <?php endif; ?>

    <!-- 2. Grid de Contenido (Detalles + Accesos) -->
    <div class="maw-content-grid">
        
        <!-- Columna Izquierda: Desglose y Gráfica -->
        <div class="maw-card">
            <h3><span class="dashicons dashicons-chart-pie"></span> Distribución por Tipo de Archivo</h3>
            <div style="margin-top:20px;">
                <?php if($stats['types']): foreach($stats['types'] as $type): 
                    $mime = $type->post_mime_type;
                    $count = $type->count;
                    $percent_bar = ($stats['total'] > 0) ? ($count / $stats['total']) * 100 : 0;
                    
                    // Clase color para barra
                    $bar_class = '';
                    if(strpos($mime, 'png') !== false) $bar_class = 'png';
                    if(strpos($mime, 'pdf') !== false) $bar_class = 'pdf';
                ?>
                <div class="maw-type-row">
                    <div style="width:60px; font-weight:600; text-transform:uppercase;"><?php echo str_replace(['image/', 'application/'], '', $mime); ?></div>
                    <div class="maw-type-bar-bg">
                        <div class="maw-type-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $percent_bar; ?>%;"></div>
                    </div>
                    <div style="width:50px; text-align:right;"><?php echo number_format_i18n($count); ?></div>
                </div>
                <?php endforeach; else: ?>
                    <p>No hay datos suficientes.</p>
                <?php endif; ?>
            </div>
            
            <div style="margin-top:20px; padding-top:20px; border-top:1px dashed #eee; font-size:12px; color:#666;">
                <p><span class="dashicons dashicons-info"></span> <em>Nota: El "Peso Librería" es una estimación basada en los archivos originales. El tamaño real en disco puede ser mayor debido a las miniaturas generadas por WordPress.</em></p>
            </div>
        </div>

        <!-- Columna Derecha: Accesos Rápidos -->
        <div>
            <h3 style="margin-bottom:15px; color:#333;">Accesos Rápidos</h3>
            <div class="maw-actions-grid">
                
                <a href="admin.php?page=image-cleaner-filters" class="maw-action-btn">
                    <span class="dashicons dashicons-filter"></span>
                    <span class="maw-action-title">Escanear</span>
                    <span class="maw-action-desc">Detectar y limpiar</span>
                </a>

                <a href="admin.php?page=image-cleaner-recovery" class="maw-action-btn">
                    <span class="dashicons dashicons-undo"></span>
                    <span class="maw-action-title">Recuperar</span>
                    <span class="maw-action-desc">Gestionar papelera</span>
                </a>

                <a href="admin.php?page=image-cleaner-reports" class="maw-action-btn">
                    <span class="dashicons dashicons-analytics"></span>
                    <span class="maw-action-title">Logs</span>
                    <span class="maw-action-desc">Auditoría forense</span>
                </a>

                <a href="admin.php?page=image-cleaner-emails" class="maw-action-btn">
                    <span class="dashicons dashicons-email"></span>
                    <span class="maw-action-title">Alertas</span>
                    <span class="maw-action-desc">Configurar emails</span>
                </a>

            </div>

            <div class="maw-card" style="margin-top:20px; background:#f9f9f9; border:none;">
                <h3>Estado del Plugin</h3>
                <ul style="margin:0; font-size:12px; line-height:1.8;">
                    <li><strong>Versión:</strong> 2.2.0 Premium</li>
                    <li><strong>Escáner:</strong> Activo (Woo/Elementor/Divi)</li>
                    <li><strong>Papelera WP:</strong> <?php echo (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS==0) ? '<span style="color:red">Desactivada</span>' : '<span style="color:green">Protegida</span>'; ?></li>
                </ul>
            </div>
        </div>

    </div>
</div>
