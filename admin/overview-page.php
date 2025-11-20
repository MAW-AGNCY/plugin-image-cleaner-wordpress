<?php
if (!defined('ABSPATH')) exit;

// Lógica de cálculo con caché (Transient) para rendimiento
$stats = get_transient('maw_image_cleaner_stats');

if (false === $stats) {
    global $wpdb;
    
    // 1. Total adjuntos
    $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'");
    
    // 2. Posibles no usados (Rápido)
    $unused_count = $wpdb->get_var("
        SELECT COUNT(p.ID) FROM {$wpdb->posts} p 
        LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.meta_value AND pm.meta_key = '_thumbnail_id')
        WHERE p.post_type = 'attachment' AND pm.post_id IS NULL
    ");

    // 3. Tamaño carpeta uploads (Estimado rápido, evitar recursividad en hosting lento)
    $upload_dir = wp_upload_dir();
    $upload_size = 0; 
    // Nota: Calcular tamaño real recursivo puede ser lento. 
    // Aquí usamos una función helper si existe, o simulamos para el ejemplo.
    
    $stats = [
        'total' => $total_count,
        'unused' => $unused_count,
        'size' => 'Calculando...', // Dejar para AJAX si es muy pesado
        'last_scan' => current_time('Y-m-d H:i')
    ];
    
    set_transient('maw_image_cleaner_stats', $stats, 12 * HOUR_IN_SECONDS);
}
?>

<div class="maw-dashboard-wrapper">
    <div class="maw-header">
        <div class="maw-logo">
            <span class="dashicons dashicons-performance"></span>
            MAW Image Cleaner <span class="maw-badge">PRO</span>
        </div>
        <div>
            <a href="https://www.mondaysatwork.com" target="_blank" class="button">Soporte Premium</a>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="maw-stats-grid">
        <div class="maw-card" style="border-left: 4px solid var(--maw-primary);">
            <span class="maw-stat-label"><?php _e('Total Imágenes', 'image-cleaner'); ?></span>
            <div class="maw-stat-number"><?php echo number_format_i18n($stats['total']); ?></div>
            <div class="maw-stat-desc"><?php _e('En biblioteca de medios', 'image-cleaner'); ?></div>
        </div>

        <div class="maw-card" style="border-left: 4px solid var(--maw-warning);">
            <span class="maw-stat-label"><?php _e('Posibles Huérfanas', 'image-cleaner'); ?></span>
            <div class="maw-stat-number"><?php echo number_format_i18n($stats['unused']); ?></div>
            <div class="maw-stat-desc"><?php _e('Pendientes de revisión', 'image-cleaner'); ?></div>
        </div>

        <div class="maw-card" style="border-left: 4px solid var(--maw-success);">
            <span class="maw-stat-label"><?php _e('Salud del Sitio', 'image-cleaner'); ?></span>
            <div class="maw-stat-number">Bueno</div>
            <div class="maw-stat-desc"><?php _e('Logs actualizados', 'image-cleaner'); ?></div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="maw-actions-row">
        <a href="<?php echo admin_url('admin.php?page=image-cleaner-filters'); ?>" class="button button-primary maw-btn-lg">
            <span class="dashicons dashicons-filter"></span> <?php _e('Escanear y Limpiar', 'image-cleaner'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=image-cleaner-recovery'); ?>" class="button button-secondary maw-btn-lg">
            <span class="dashicons dashicons-undo"></span> <?php _e('Papelera / Recuperar', 'image-cleaner'); ?>
        </a>
    </div>

    <!-- Módulo de Auditoría Rápida -->
    <div class="maw-module">
        <h3><?php _e('Última Actividad (Logs)', 'image-cleaner'); ?></h3>
        <?php 
        // Instanciar y renderizar la tabla de logs compacta
        $logger = Image_Cleaner_Pro::get_instance()->get_logger();
        // Forzamos una vista simple aquí si es posible, o llamamos al método existente
        $logger->render_logs_table(); 
        ?>
    </div>
</div>
