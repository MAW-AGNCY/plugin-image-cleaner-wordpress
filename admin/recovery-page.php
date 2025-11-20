<?php 
if (!defined('ABSPATH')) exit; 

/**
 * LÓGICA DE RESTAURACIÓN
 * ----------------------
 * Procesa la solicitud POST para sacar un elemento de la papelera.
 */
if (isset($_POST['restore_id']) && check_admin_referer('maw_rest', 'maw_nonce')) {
    $id = intval($_POST['restore_id']);
    
    // Intentar restaurar
    if (wp_untrash_post($id)) {
        // Registrar en auditoría
        Image_Cleaner_Pro::get_instance()->get_logger()->log('recovered', 'ID: ' . $id, 'Restaurado desde Papelera');
        echo '<div class="notice notice-success is-dismissible"><p><strong>Éxito:</strong> La imagen ha sido restaurada a la biblioteca.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> No se pudo restaurar. Verifica si el archivo físico aún existe en /uploads/.</p></div>';
    }
}

/**
 * CONSULTA DIRECTA A BASE DE DATOS (ROBUSTA)
 * ------------------------------------------
 * Usamos $wpdb en lugar de WP_Query para evitar que temas o plugins
 * de terceros filtren u oculten los resultados de la papelera.
 */
global $wpdb;

// Verificar configuración de WP
$trash_is_disabled = (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS == 0);

// SQL: Buscar adjuntos con status 'trash'
$sql = "SELECT ID, post_title, post_date, post_modified, guid 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_status = 'trash' 
        ORDER BY post_modified DESC 
        LIMIT 100"; // Límite de seguridad

$trashed_items = $wpdb->get_results($sql);
?>

<div class="maw-wrap">
    <!-- Cabecera -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-undo"></span> Recuperación de Archivos</h1>
        </div>
        <div>
            <span class="maw-badge" style="background:#666;">Papelera del Sistema</span>
        </div>
    </div>

    <!-- Advertencia Crítica si la papelera está desactivada en wp-config.php -->
    <?php if ($trash_is_disabled) : ?>
        <div class="notice notice-error inline">
            <p><strong>⚠️ ADVERTENCIA CRÍTICA:</strong> Tu WordPress tiene la papelera desactivada (`EMPTY_TRASH_DAYS = 0`). 
            Cualquier archivo que borres <strong>desaparecerá permanentemente</strong> y no aparecerá en esta lista. 
            Para arreglarlo, edita tu wp-config.php y elimina esa línea o ponle valor 30.</p>
        </div>
    <?php endif; ?>

    <!-- Tabla de Recuperación -->
    <div class="maw-card">
        <p class="description" style="margin-bottom:20px;">
            Aquí se muestran los archivos eliminados recientemente. Si vacías la papelera, se perderán para siempre.
        </p>

        <table class="maw-table-view">
            <thead>
                <tr>
                    <th width="80">Vista</th>
                    <th>Detalles del Archivo</th>
                    <th>Fecha de Eliminación</th>
                    <th width="150">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($trashed_items)) : ?>
                    <?php foreach ($trashed_items as $item) : 
                        $id = $item->ID;
                        // Intentar obtener miniatura (puede fallar si el archivo físico se borró, por eso el fallback)
                        $thumb = wp_get_attachment_image_src($id, 'thumbnail');
                        $filename = basename(get_attached_file($id)); 
                        if(!$filename) $filename = basename($item->guid); // Fallback
                    ?>
                    <tr>
                        <td>
                            <?php if ($thumb) : ?>
                                <img src="<?php echo esc_url($thumb[0]); ?>" class="maw-table-img" style="opacity:0.6;">
                            <?php else : ?>
                                <div class="maw-table-img" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#888;">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($item->post_title ?: '(Sin Título)'); ?></strong><br>
                            <small style="color:#888;">Archivo: <?php echo esc_html($filename); ?></small><br>
                            <small style="color:#888;">ID: <?php echo $id; ?></small>
                        </td>
                        <td>
                            <?php 
                            // post_modified suele ser la fecha en que se movió a papelera
                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->post_modified)); 
                            ?>
                        </td>
                        <td>
                            <form method="post">
                                <?php wp_nonce_field('maw_rest', 'maw_nonce'); ?>
                                <input type="hidden" name="restore_id" value="<?php echo $id; ?>">
                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-image-rotate"></span> Restaurar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <!-- Estado Vacío -->
                    <tr>
                        <td colspan="4" style="text-align:center; padding:40px 20px;">
                            <div style="color:#ccc; margin-bottom:10px;">
                                <span class="dashicons dashicons-trash" style="font-size:60px; width:60px; height:60px;"></span>
                            </div>
                            <h3 style="margin:0; color:#666;">La papelera está vacía</h3>
                            <p style="color:#888;">No hay archivos pendientes de recuperación.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
