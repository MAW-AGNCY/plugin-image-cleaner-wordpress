<?php if (!defined('ABSPATH')) exit; 

// 1. RESTAURAR
if (isset($_POST['restore_id']) && check_admin_referer('maw_rest', 'maw_nonce')) {
    $id = intval($_POST['restore_id']);
    global $wpdb;
    if ($wpdb->update($wpdb->posts, ['post_status' => 'inherit'], ['ID' => $id])) {
        clean_post_cache($id);
        
        // Notificación Automática (Intuitiva: No se selecciona plantilla)
        $recipient = get_option('maw_email_recipient', get_option('admin_email'));
        MAW_Email_Manager::send(
            $recipient, 
            'Archivo Restaurado', 
            'recovery-notification-template', 
            ['{{message}}' => "El archivo ID #$id ha sido restaurado."],
            'recovery'
        );
        echo '<div class="notice notice-success is-dismissible"><p>Restaurado correctamente.</p></div>';
    }
}

// 2. VACIAR PAPELERA (ACCIÓN IRREVERSIBLE)
if (isset($_POST['empty_trash']) && check_admin_referer('maw_empty_trash', 'maw_nonce')) {
    global $wpdb;
    // Obtener todos los IDs en trash
    $ids_to_delete = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_status='trash'");
    $count = 0;
    
    foreach ($ids_to_delete as $del_id) {
        // wp_delete_attachment con force=true borra archivo del disco y filas DB
        if (wp_delete_attachment($del_id, true)) {
            $count++;
        }
    }
    
    if ($count > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>Papelera vaciada. <strong>' . $count . '</strong> archivos eliminados permanentemente.</p></div>';
        Image_Cleaner_Pro::get_instance()->get_logger()->log('deleted', 'Vaciado Masivo', "$count archivos borrados permanentemente.");
    }
}

// QUERY
global $wpdb;
$sql = "SELECT ID, post_title, post_date, guid FROM $wpdb->posts WHERE post_type='attachment' AND post_status='trash' ORDER BY post_modified DESC LIMIT 100";
$trashed = $wpdb->get_results($sql);
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title"><h1><span class="dashicons dashicons-undo"></span> Papelera</h1></div>
        <?php if ($trashed): ?>
        <form method="post" onsubmit="return confirm('⚠️ ¿ESTÁS SEGURO?\n\nEsta acción eliminará los archivos del servidor permanentemente.\nNo se podrá deshacer.');">
            <?php wp_nonce_field('maw_empty_trash', 'maw_nonce'); ?>
            <button type="submit" name="empty_trash" class="button button-link-delete" style="color:#d63638; border-color:#d63638;">
                <span class="dashicons dashicons-trash"></span> Vaciar Papelera Ahora
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="maw-card">
        <table class="maw-table">
            <thead><tr><th>Vista</th><th>Archivo</th><th>Fecha Borrado</th><th>Acción</th></tr></thead>
            <tbody>
                <?php if($trashed): foreach($trashed as $item): 
                    $id = $item->ID;
                    $thumb = wp_get_attachment_image_src($id, 'thumbnail');
                ?>
                <tr>
                    <td>
                        <?php if($thumb): ?><img src="<?php echo esc_url($thumb[0]); ?>" class="maw-img-preview" style="opacity:0.6">
                        <?php else: ?><div class="maw-img-preview" style="display:flex;justify-content:center;align-items:center;color:#ccc;">?</div><?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($item->post_title); ?></strong><br>
                        <small style="color:#888"><?php echo esc_html(basename($item->guid)); ?></small>
                    </td>
                    <td><?php echo $item->post_date; ?></td>
                    <td>
                        <form method="post">
                            <?php wp_nonce_field('maw_rest', 'maw_nonce'); ?>
                            <input type="hidden" name="restore_id" value="<?php echo $id; ?>">
                            <button type="submit" class="button button-primary">Restaurar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px;">Papelera vacía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
