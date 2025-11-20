<?php if (!defined('ABSPATH')) exit; 
global $wpdb;
$total = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='attachment'");
$trash = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='attachment' AND post_status='trash'");
?>
<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title"><h1><span class="dashicons dashicons-dashboard"></span> Dashboard <span class="maw-badge">PRO</span></h1></div>
    </div>

    <div class="maw-stats-grid">
        <div class="maw-card" style="border-top:3px solid var(--maw-primary);">
            <h3>Biblioteca</h3>
            <div class="stat-number"><?php echo $total; ?></div>
            <div class="stat-desc">Archivos totales</div>
        </div>
        <div class="maw-card" style="border-top:3px solid var(--maw-danger);">
            <h3>Papelera</h3>
            <div class="stat-number"><?php echo $trash; ?></div>
            <div class="stat-desc">Pendientes de borrado</div>
            <a href="admin.php?page=image-cleaner-recovery">Ver Papelera</a>
        </div>
        <div class="maw-card" style="border-top:3px solid var(--maw-success);">
            <h3>Acciones</h3>
            <a href="admin.php?page=image-cleaner-filters" class="button button-primary">Escanear Ahora</a>
        </div>
    </div>
</div>
