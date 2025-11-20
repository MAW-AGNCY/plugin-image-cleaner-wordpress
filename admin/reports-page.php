<?php
if (!defined('ABSPATH')) {
    exit;
}

// 1. Obtener instancias
$main_instance = Image_Cleaner_Pro::get_instance();
$logger = $main_instance->get_logger();
// Instanciamos la clase de reportes localmente (o podrías añadirla al Singleton principal)
$reporter = new Image_Cleaner_Reports(); 

// 2. Gestión de Pestañas
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'audit_log';

// 3. Gestión de acciones (Vaciar Logs)
if (isset($_POST['maw_clear_logs']) && check_admin_referer('maw_clear_logs_action', 'maw_nonce')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'maw_image_logs';
    $wpdb->query("TRUNCATE TABLE $table_name");
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Historial de auditoría vaciado.', 'image-cleaner') . '</p></div>';
}
?>

<div class="wrap maw-dashboard-wrapper">
    <div class="maw-header">
        <div class="maw-logo">
            <span class="dashicons dashicons-chart-area"></span>
            <?php esc_html_e('Centro de Reportes', 'image-cleaner'); ?>
        </div>
    </div>

    <!-- Navegación por Pestañas -->
    <h2 class="nav-tab-wrapper">
        <a href="?page=image-cleaner-reports&tab=audit_log" class="nav-tab <?php echo $active_tab == 'audit_log' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('🔍 Auditoría (Logs)', 'image-cleaner'); ?>
        </a>
        <a href="?page=image-cleaner-reports&tab=large_images" class="nav-tab <?php echo $active_tab == 'large_images' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('⚖️ Imágenes Pesadas', 'image-cleaner'); ?>
        </a>
        <a href="?page=image-cleaner-reports&tab=unused_images" class="nav-tab <?php echo $active_tab == 'unused_images' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('🗑️ Posibles Huérfanas', 'image-cleaner'); ?>
        </a>
    </h2>

    <div class="maw-module" style="margin-top: 20px;">
        
        <!-- PESTAÑA 1: AUDITORÍA -->
        <?php if ($active_tab == 'audit_log') : ?>
            <h3><?php esc_html_e('Registro de Actividad', 'image-cleaner'); ?></h3>
            <p class="description"><?php esc_html_e('Historial de seguridad de archivos borrados y recuperados.', 'image-cleaner'); ?></p>
            <?php $logger->render_logs_table(); ?>
            
            <div style="margin-top: 20px; text-align: right;">
                <form method="post" onsubmit="return confirm('¿Borrar historial?');" style="display:inline;">
                    <?php wp_nonce_field('maw_clear_logs_action', 'maw_nonce'); ?>
                    <button type="submit" name="maw_clear_logs" class="button button-link-delete"><?php esc_html_e('Vaciar Logs', 'image-cleaner'); ?></button>
                </form>
            </div>

        <!-- PESTAÑA 2: IMÁGENES PESADAS -->
        <?php elseif ($active_tab == 'large_images') : ?>
            <?php $large_images = $reporter->get_large_images_data(); ?>
            <h3><?php esc_html_e('Imágenes que ocupan mucho espacio (>500KB)', 'image-cleaner'); ?></h3>
            
            <div class="maw-actions-row" style="justify-content: flex-end; margin-bottom: 10px;">
                <button class="button" onclick="window.print()"><span class="dashicons dashicons-printer"></span> Imprimir / PDF</button>
            </div>

            <?php if (empty($large_images)) : ?>
                <p><?php esc_html_e('¡Genial! No tienes imágenes excesivamente pesadas.', 'image-cleaner'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Archivo</th>
                            <th>Tamaño</th>
                            <th>Subida</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($large_images as $img) : ?>
                        <tr>
                            <td><img src="<?php echo esc_url($img['url']); ?>" style="max-width:60px; height:auto;"></td>
                            <td>
                                <strong><?php echo esc_html($img['title']); ?></strong><br>
                                <small><?php echo esc_html($img['filename']); ?></small>
                            </td>
                            <td style="color:#d63638; font-weight:bold;"><?php echo esc_html($img['size']); ?></td>
                            <td><?php echo esc_html($img['date']); ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($img['ID']); ?>" class="button button-small" target="_blank">Editar/Optimizar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <!-- PESTAÑA 3: NO USADAS (Old Logic) -->
        <?php elseif ($active_tab == 'unused_images') : ?>
            <?php $unused_images = $reporter->get_unused_images_data(); ?>
            <h3><?php esc_html_e('Imágenes sin uso detectado (Análisis Rápido)', 'image-cleaner'); ?></h3>
            <div class="notice notice-warning inline">
                <p>Nota: Este reporte busca imágenes que no son "Imagen Destacada". Para un análisis profundo y borrado seguro, usa la pestaña <a href="admin.php?page=image-cleaner-filters">Escanear y Limpiar</a>.</p>
            </div>

            <div class="maw-actions-row" style="justify-content: flex-end; margin-bottom: 10px;">
                <button class="button" onclick="window.print()"><span class="dashicons dashicons-printer"></span> Imprimir / PDF</button>
            </div>

            <?php if (empty($unused_images)) : ?>
                <p><?php esc_html_e('No se encontraron imágenes huérfanas obvias.', 'image-cleaner'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Vista</th>
                            <th>Nombre</th>
                            <th>URL</th>
                            <th>Tamaño</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unused_images as $img) : ?>
                        <tr>
                            <td><img src="<?php echo esc_url($img['url']); ?>" style="max-width:50px;"></td>
                            <td><?php echo esc_html($img['filename']); ?></td>
                            <td><input type="text" value="<?php echo esc_url($img['url']); ?>" readonly class="regular-text" style="width:100%"></td>
                            <td><?php echo esc_html($img['size']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
