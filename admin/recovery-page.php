<?php if (!defined('ABSPATH')) exit;

// Lógica Restaurar
if (isset($_POST['restore_id']) && check_admin_referer('maw_rest', 'maw_nonce')) {
    $id = intval($_POST['restore_id']);
    if (wp_untrash_post($id)) {
        echo '<div class="notice notice-success is-dismissible"><p>Imagen restaurada a la biblioteca.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error al restaurar. Verifica permisos.</p></div>';
    }
}

// Query Específica: Forzar status trash
$args = [
    'post_type'      => 'attachment',
    'post_status'    => 'trash', // CRUCIAL
    'posts_per_page' => 50,
    'orderby'        => 'modified',
    'order'          => 'DESC'
];
$query = new WP_Query($args);
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-undo"></span> Recuperación de Archivos</h1>
        </div>
    </div>

    <?php if (!defined('EMPTY_TRASH_DAYS') || EMPTY_TRASH_DAYS == 0) : ?>
        <div class="notice notice-error inline">
            <p><strong>ADVERTENCIA CRÍTICA:</strong> La papelera está desactivada en tu instalación (`EMPTY_TRASH_DAYS=0`). Los archivos borrados en la pestaña Escáner desaparecerán permanentemente.</p>
        </div>
    <?php endif; ?>

    <div class="maw-card">
        <table class="maw-table">
            <thead>
                <tr>
                    <th width="60">Vista</th>
                    <th>Nombre de Archivo</th>
                    <th>Fecha de Eliminación</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); 
                    $id = get_the_ID();
                    $thumb = wp_get_attachment_image_src($id, 'thumbnail');
                    $filename = basename(get_attached_file($id));
                ?>
                <tr>
                    <td>
                        <?php if($thumb): ?>
                            <img src="<?php echo esc_url($thumb[0]); ?>" class="maw-img-preview">
                        <?php else: ?>
                            <div class="maw-img-preview" style="display:flex;align-items:center;justify-content:center;">?</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html(get_the_title()); ?></strong><br>
                        <small style="color:#888"><?php echo esc_html($filename); ?></small>
                    </td>
                    <td><?php echo get_the_modified_date('Y-m-d H:i'); ?></td>
                    <td>
                        <form method="post">
                            <?php wp_nonce_field('maw_rest', 'maw_nonce'); ?>
                            <input type="hidden" name="restore_id" value="<?php echo $id; ?>">
                            <button type="submit" class="button button-primary">Restaurar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:30px;">
                            <span class="dashicons dashicons-yes" style="font-size:40px; color:var(--maw-success); height:40px; width:40px;"></span>
                            <p>La papelera está vacía. ¡Buen trabajo!</p>
                        </td>
                    </tr>
                <?php endif; wp_reset_postdata(); ?>
            </tbody>
        </table>
    </div>
</div>
