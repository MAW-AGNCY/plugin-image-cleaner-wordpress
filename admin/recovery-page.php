<?php if (!defined('ABSPATH')) exit;

if (isset($_POST['restore_id']) && check_admin_referer('maw_rest', 'maw_nonce')) {
    $id = intval($_POST['restore_id']);
    if (wp_untrash_post($id)) {
        echo '<div class="notice notice-success is-dismissible"><p>Restaurado correctamente.</p></div>';
    }
}

// QUERY FORZADA A PAPELERA
$query = new WP_Query([
    'post_type' => 'attachment',
    'post_status' => 'trash', // CRÍTICO PARA VER ITEMS BORRADOS
    'posts_per_page' => 50
]);
?>
<div class="maw-wrap">
    <div class="maw-header"><div class="maw-title"><h1>Recuperación</h1></div></div>
    
    <?php if(!defined('EMPTY_TRASH_DAYS') || EMPTY_TRASH_DAYS==0): ?>
        <div class="notice notice-error inline"><p>ALERTA: Papelera desactivada en wp-config.php.</p></div>
    <?php endif; ?>

    <div class="maw-card">
        <table class="maw-table-view">
            <thead><tr><th>Imagen</th><th>Nombre</th><th>Acción</th></tr></thead>
            <tbody>
                <?php if($query->have_posts()): while($query->have_posts()): $query->the_post(); 
                    $id = get_the_ID();
                    $thumb = wp_get_attachment_image_src($id, 'thumbnail');
                ?>
                <tr>
                    <td><img src="<?php echo $thumb?$thumb[0]:''; ?>" class="maw-table-img"></td>
                    <td><?php the_title(); ?><br><small><?php echo basename(get_attached_file($id)); ?></small></td>
                    <td>
                        <form method="post">
                            <?php wp_nonce_field('maw_rest', 'maw_nonce'); ?>
                            <input type="hidden" name="restore_id" value="<?php echo $id; ?>">
                            <button class="button button-primary">Restaurar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3">Papelera vacía.</td></tr>
                <?php endif; wp_reset_postdata(); ?>
            </tbody>
        </table>
    </div>
</div>
