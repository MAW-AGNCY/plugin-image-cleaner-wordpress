<?php if (!defined('ABSPATH')) exit; 

// RESTAURAR: Usamos lógica inversa. Cambiamos status a 'inherit' manualmente.
if (isset($_POST['restore_id']) && check_admin_referer('maw_rest', 'maw_nonce')) {
    $id = intval($_POST['restore_id']);
    global $wpdb;
    
    // Forzar restauración manual para asegurar éxito
    $restored = $wpdb->update(
        $wpdb->posts,
        ['post_status' => 'inherit'], // Estado normal de adjuntos
        ['ID' => $id]
    );

    if ($restored !== false) {
        clean_post_cache($id);
        echo '<div class="notice notice-success is-dismissible"><p>Imagen restaurada correctamente.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error al restaurar en base de datos.</p></div>';
    }
}

// CONSULTA DIRECTA SQL (No WP_Query)
global $wpdb;
$sql = "SELECT ID, post_title, post_date, guid FROM $wpdb->posts WHERE post_type='attachment' AND post_status='trash' ORDER BY post_modified DESC LIMIT 100";
$trashed = $wpdb->get_results($sql);
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-undo"></span> Recuperación de Archivos</h1>
        </div>
    </div>

    <div class="maw-card">
        <p>Aquí están los archivos marcados como <code>trash</code> en tu base de datos. Al restaurarlos, volverán a ser visibles en la biblioteca.</p>
        
        <table class="maw-table">
            <thead>
                <tr>
                    <th width="80">Vista</th>
                    <th>Detalles</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if($trashed): foreach($trashed as $item): 
                    $id = $item->ID;
                    $thumb = wp_get_attachment_image_src($id, 'thumbnail');
                    $filename = basename($item->guid);
                ?>
                <tr>
                    <td>
                        <?php if($thumb): ?>
                            <img src="<?php echo esc_url($thumb[0]); ?>" class="maw-img-preview" style="opacity:0.6">
                        <?php else: ?>
                            <div class="maw-img-preview" style="display:flex;align-items:center;justify-content:center;">?</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($item->post_title); ?></strong><br>
                        <small style="color:#888"><?php echo esc_html($filename); ?></small>
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
                <tr><td colspan="4" style="text-align:center; padding:30px;">La papelera está vacía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
