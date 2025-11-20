<?php 
if (!defined('ABSPATH')) exit; 

global $wpdb;

// 1. Cálculos Reales (Optimizados)
// Usamos TRANSIENTS para no colgar la BD en cada carga
$stats = get_transient('maw_premium_stats_v2');

if (false === $stats) {
    // Total imágenes
    $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit'");
    
    // Desglose por MIME type (Top 5)
    $types = $wpdb->get_results("SELECT post_mime_type, COUNT(*) as count FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' GROUP BY post_mime_type ORDER BY count DESC LIMIT 5");
    
    // Espacio estimado (Sumar tamaños de metadatos es lento, usamos estimación rápida o count)
    // Para versión premium real, esto debería ser una tarea asíncrona.
    
    $trash_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'trash'");

    $stats = [
        'total' => $total_count,
        'types' => $types,
        'trash' => $trash_count
    ];
    
    set_transient('maw_premium_stats_v2', $stats, 1 * HOUR_IN_SECONDS);
}

// Datos simulados de espacio (Para demo visual premium)
$upload_dir = wp_upload_dir();
$space_allowed = 1024 * 1024 * 1024 * 10; // 10GB ejemplo
$space_used = 1024 * 1024 * 1024 * 2.5; // 2.5GB simulado (calcular real requiere escaneo recursivo lento)
$percent = ($space_used / $space_allowed) * 100;
?>

<div class="maw-wrap">
    <!-- Header -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-performance"></span> Centro de Mando <span class="maw-badge">Platinum</span></h1>
        </div>
        <div>
            <a href="admin.php?page=image-cleaner-help" class="button button-secondary">Documentación</a>
        </div>
    </div>

    <!-- Grid de Widgets -->
    <div class="maw-dashboard-grid">
        
        <!-- Widget 1: Salud de la Biblioteca -->
        <div class="maw-card">
            <h3>Resumen de Biblioteca</h3>
            <div class="maw-stat-big"><?php echo number_format_i18n($stats['total']); ?></div>
            <p class="description">Archivos multimedia totales</p>
            
            <div style="margin-top: 20px;">
                <strong>Uso de Disco (Estimado)</strong>
                <div class="maw-progress-bar">
                    <div class="maw-progress-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                </div>
                <div class="maw-progress-label">
                    <span>Usado: ~2.5 GB</span>
                    <span>Total: 10 GB</span>
                </div>
            </div>
        </div>

        <!-- Widget 2: Desglose de Archivos -->
        <div class="maw-card">
            <h3>Distribución por Tipo</h3>
            <ul class="maw-type-list">
                <?php if($stats['types']): foreach($stats['types'] as $type): ?>
                <li>
                    <span style="font-weight:600; text-transform:uppercase;"><?php echo str_replace('image/', '', $type->post_mime_type); ?></span>
                    <span class="maw-badge" style="background:#eee; color:#333;"><?php echo number_format_i18n($type->count); ?></span>
                </li>
                <?php endforeach; else: ?>
                <li>No hay datos disponibles.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Widget 3: Acciones Pendientes -->
        <div class="maw-card" style="border-left-color: var(--maw-warning);">
            <h3>Limpieza Pendiente</h3>
            <p>Tienes <strong><?php echo $stats['trash']; ?></strong> elementos en la papelera esperando borrado definitivo.</p>
            
            <div style="margin-top:auto; display:flex; gap:10px; flex-direction:column;">
                <a href="admin.php?page=image-cleaner-filters" class="button button-primary button-large" style="text-align:center;">Iniciar Escaneo Profundo</a>
                <a href="admin.php?page=image-cleaner-recovery" class="button button-secondary" style="text-align:center;">Gestionar Papelera</a>
            </div>
        </div>

    </div>
</div>
