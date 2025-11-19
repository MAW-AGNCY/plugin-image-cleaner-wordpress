<?php
if (!defined('ABSPATH')) {
    exit; 
}

// Manejo del POST action para recuperar
if (isset($_POST['action']) && $_POST['action'] === 'image_cleaner_recover') {
    if (!isset($_POST['image_cleaner_nonce']) || !wp_verify_nonce($_POST['image_cleaner_nonce'], 'image_cleaner_recover')) {
        wp_die('Error de seguridad (Nonce).');
    }
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos.');
    }

    $image_id = intval($_POST['image_id']);
    if ($image_id && wp_untrash_post($image_id)) {
        echo '<div class="notice notice-success is-dismissible"><p>Imagen recuperada con éxito.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error al recuperar la imagen.</p></div>';
    }
}
?>

<div class="wrap">
    <h1>Recuperación de Imágenes (Papelera)</h1>
    <p>Aquí se muestran las imágenes que has eliminado recientemente. Si vacías la papelera de WordPress, desaparecerán de aquí permanentemente.</p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Vista Previa</th>
                <th>Nombre de archivo</th>
                <th>Fecha de borrado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Buscar adjuntos en la papelera
            $trashed_images = get_posts([
                'post_type' => 'attachment',
                'post_status' => 'trash',
                'posts_per_page' => 50, // Limite por seguridad de memoria
            ]);

            if ($trashed_images) {
                foreach ($trashed_images as $image) {
                    $img_url = wp_get_attachment_url($image->ID);
                    ?>
                    <tr>
                        <td>
                            <?php if ($img_url): ?>
                                <img src="<?php echo esc_url($img_url); ?>" style="max-width: 50px; height: auto;">
                            <?php else: ?>
                                (Archivo no encontrado)
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($image->post_title); ?></td>
                        <td><?php echo esc_html($image->post_modified); ?></td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="image_cleaner_recover">
                                <input type="hidden" name="image_id" value="<?php echo esc_attr($image->ID); ?>">
                                <?php wp_nonce_field('image_cleaner_recover', 'image_cleaner_nonce'); ?>
                                <button type="submit" class="button button-small">Recuperar</button>
                            </form>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="4">La papelera está vacía. ¡Buen trabajo!</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
