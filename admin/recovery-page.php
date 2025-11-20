<?php
if (!defined('ABSPATH')) exit;

// Lógica de Restauración
if (isset($_POST['restore_id']) && check_admin_referer('maw_restore_action', 'maw_nonce')) {
    $id = intval($_POST['restore_id']);
    if (wp_untrash_post($id)) {
        Image_Cleaner_Pro::get_instance()->get_logger()->log('recovered', 'ID: '.$id, 'Restaurado desde Papelera');
        echo '<div class="notice notice-success is-dismissible"><p>Imagen restaurada.</p></div>';
    }
}

// IMPORTANTE: Verificar estado de papelera
if (!defined('EMPTY_TRASH_DAYS') || EMPTY_TRASH_DAYS == 0) {
    echo '<div class="notice notice-error"><p><strong>ADVERTENCIA:</strong> La papelera está desactivada en tu instalación de WordPress (EMPTY_TRASH_DAYS = 0). Las imágenes se borrarán permanentemente.</p></div>';
}

// Consulta Específica para Papelera
$query = new WP_Query([
    'post_type' => 'attachment',
    'post_status' => 'trash',
    'posts_per_page' => 50
]);
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-undo"></span> Zona de Recuperación</h1>
        </div>
    </div>

    <p>Aquí aparecen los elementos eliminados recientemente. Si vacías la papelera, desaparecerán para siempre.</p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="80">Vista</th>
                <th>Nombre</th>
                <th>Fecha Borrado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); 
                $id = get_the_ID();
                $thumb = wp_get_attachment_image_src($id, 'thumbnail');
            ?>
            <tr>
                <td>
                    <?php if($thumb): ?><img src="<?php echo esc_url($thumb[0]); ?>" width="50"><?php endif; ?>
                </td>
                <td>
                    <strong><?php the_title(); ?></strong><br>
                    <small><?php echo basename(get_attached_file($id)); ?></small>
                </td>
                <td><?php echo get_the_modified_date(); ?></td>
                <td>
                    <form method="post">
                        <?php wp_nonce_field('maw_restore_action', 'maw_nonce'); ?>
                        <input type="hidden" name="restore_id" value="<?php echo $id; ?>">
                        <button class="button button-primary">Restaurar</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="4">La papelera está vacía.</td></tr>
            <?php endif; wp_reset_postdata(); ?>
        </tbody>
    </table>
</div>
