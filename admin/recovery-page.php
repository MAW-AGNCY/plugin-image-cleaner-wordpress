<?php 
if (!defined('ABSPATH')) exit; 

/**
 * LÓGICA DE ACCIÓN: RESTAURAR ARCHIVO
 * ----------------------------------------------------------------
 * Se ejecuta cuando el usuario pulsa el botón "Restaurar".
 * Cambia el estado del post de 'trash' a 'inherit' (estado por defecto de adjuntos).
 */
if (isset($_POST['restore_id']) && check_admin_referer('maw_rest', 'maw_nonce')) {
    global $wpdb;
    $id = intval($_POST['restore_id']);
    
    // 1. Restauración Directa en Base de Datos (Bypass de filtros de WP)
    $restored = $wpdb->update(
        $wpdb->posts,
        ['post_status' => 'inherit'], // Estado normal
        ['ID' => $id],
        ['%s'],
        ['%d']
    );

    if ($restored !== false) {
        // Limpiar caché del objeto para que WP reconozca el cambio inmediatamente
        clean_post_cache($id);

        // 2. Registrar en Auditoría (Logs)
        $filename = basename(get_attached_file($id));
        Image_Cleaner_Pro::get_instance()->get_logger()->log('recovered', $filename, 'Restaurado manualmente desde panel');

        // 3. Enviar Notificación (Si el usuario la tiene activa en preferencias)
        // El parámetro 'recovery' al final le dice al Manager que verifique si este tipo de alerta está activo.
        $recipient = get_option('maw_email_recipient', get_option('admin_email'));
        
        MAW_Email_Manager::send(
            $recipient, 
            'Archivo Restaurado - MAW Cleaner', 
            'recovery-notification-template', 
            [
                '{{message}}' => "El archivo <strong>$filename</strong> (ID: $id) ha sido recuperado exitosamente de la papelera y vuelve a estar visible en la biblioteca."
            ],
            'recovery' // <--- TIPO DE NOTIFICACIÓN (Check de preferencias)
        );

        echo '<div class="notice notice-success is-dismissible"><p><strong>Éxito:</strong> La imagen ha sido restaurada a la biblioteca.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> No se pudo actualizar la base de datos. Verifica los permisos.</p></div>';
    }
}

/**
 * CONSULTA DE DATOS: LISTAR PAPELERA
 * ----------------------------------------------------------------
 * Usamos SQL directo para garantizar que vemos TODO lo que está en la papelera,
 * sin importar si el usuario actual es el autor o si hay filtros activos.
 */
global $wpdb;
$trash_disabled = (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS == 0);

$sql = "SELECT ID, post_title, post_date, post_modified, guid 
        FROM $wpdb->posts 
        WHERE post_type = 'attachment' 
        AND post_status = 'trash' 
        ORDER BY post_modified DESC 
        LIMIT 100";

$trashed_items = $wpdb->get_results($sql);
?>

<div class="maw-wrap">
    <!-- Cabecera Corporativa -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-undo"></span> Recuperación de Archivos</h1>
        </div>
        <div>
            <span class="maw-badge" style="background:#666;">Papelera del Sistema</span>
        </div>
    </div>

    <!-- Alerta Técnica: Papelera Desactivada -->
    <?php if ($trash_disabled) : ?>
        <div class="maw-alert warning" style="border-left-color:var(--maw-danger);">
            <p><strong>⚠️ ADVERTENCIA CRÍTICA:</strong> Tu configuración de WordPress (`EMPTY_TRASH_DAYS = 0`) elimina los archivos permanentemente al instante. Esta herramienta de recuperación no funcionará con nuevos borrados hasta que cambies esa configuración en `wp-config.php`.</p>
        </div>
    <?php endif; ?>

    <!-- Tabla de Datos -->
    <div class="maw-card">
        <p class="description" style="margin-bottom:20px;">
            Los siguientes archivos están marcados como eliminados pero aún existen en la base de datos. Puedes restaurarlos o dejarlos; WordPress los borrará definitivamente pasados 30 días (según configuración).
        </p>

        <table class="maw-table">
            <thead>
                <tr>
                    <th width="80">Vista Previa</th>
                    <th>Detalles del Archivo</th>
                    <th>Fecha de Eliminación</th>
                    <th width="150">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($trashed_items)) : ?>
                    <?php foreach ($trashed_items as $item) : 
                        $id = $item->ID;
                        $thumb = wp_get_attachment_image_src($id, 'thumbnail');
                        // Intentar obtener nombre real, fallback a GUID si el archivo físico ya no está
                        $file_path = get_attached_file($id);
                        $filename = $file_path ? basename($file_path) : basename($item->guid);
                    ?>
                    <tr>
                        <td>
                            <?php if ($thumb) : ?>
                                <img src="<?php echo esc_url($thumb[0]); ?>" class="maw-img-preview" style="opacity:0.6; border:1px dashed #ccc;">
                            <?php else : ?>
                                <div class="maw-img-preview" style="display:flex; align-items:center; justify-content:center; color:#ccc; border:1px dashed #ccc;">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color:var(--maw-dark);"><?php echo esc_html($item->post_title ?: '(Sin Título)'); ?></strong><br>
                            <small style="color:#888;">Archivo: <?php echo esc_html($filename); ?></small><br>
                            <small style="color:#aaa;">ID Ref: <?php echo $id; ?></small>
                        </td>
                        <td>
                            <?php 
                            // post_modified es la fecha cuando se movió a la papelera
                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->post_modified)); 
                            ?>
                        </td>
                        <td>
                            <form method="post">
                                <?php wp_nonce_field('maw_rest', 'maw_nonce'); ?>
                                <input type="hidden" name="restore_id" value="<?php echo $id; ?>">
                                <button type="submit" class="button button-primary button-small">
                                    <span class="dashicons dashicons-image-rotate"></span> Restaurar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <!-- Estado Vacío -->
                    <tr>
                        <td colspan="4" style="text-align:center; padding:50px 20px;">
                            <div style="color:#e1e1e1; margin-bottom:15px;">
                                <span class="dashicons dashicons-trash" style="font-size:60px; width:60px; height:60px;"></span>
                            </div>
                            <h3 style="margin:0 0 10px 0; color:var(--maw-text);">La papelera está vacía</h3>
                            <p style="color:#888; margin:0;">No hay archivos pendientes de recuperación en este momento.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
