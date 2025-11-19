<?php
if (!defined('ABSPATH')) {
    exit;
}

// Seguridad: Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('Acceso denegado.'));
}

// Encolar estilos solo si es necesario
wp_enqueue_style('image-cleaner-email-page', plugins_url('css/email-page.css', __FILE__));

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Verificar NONCE (Protección CSRF)
    if (!isset($_POST['image_cleaner_email_nonce']) || !wp_verify_nonce($_POST['image_cleaner_email_nonce'], 'image_cleaner_save_email')) {
        wp_die(__('Error de seguridad. Intente recargar la página.', 'image-cleaner'));
    }

    if (isset($_POST['send_test_email'])) {
        $email_recipient = sanitize_email($_POST['email_recipient']);
        
        // Lógica de envío de prueba simple
        if (wp_mail($email_recipient, 'Test Image Cleaner', 'Este es un correo de prueba.')) {
            echo '<div class="notice notice-success is-dismissible"><p>Email de prueba enviado a ' . esc_html($email_recipient) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Error al enviar email.</p></div>';
        }

    } elseif (isset($_POST['reset_settings'])) {
        delete_option('image_cleaner_email_recipient');
        delete_option('image_cleaner_email_frequency');
        delete_option('image_cleaner_email_subject');
        delete_option('image_cleaner_email_message');
        echo '<div class="notice notice-success is-dismissible"><p>Configuración restablecida.</p></div>';

    } else {
        // Guardar configuración
        update_option('image_cleaner_email_recipient', sanitize_email($_POST['email_recipient']));
        update_option('image_cleaner_email_frequency', sanitize_text_field($_POST['email_frequency']));
        update_option('image_cleaner_email_subject', sanitize_text_field($_POST['email_subject']));
        update_option('image_cleaner_email_message', sanitize_textarea_field($_POST['email_message']));
        echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada.</p></div>';
    }
}

// Obtener valores
$email_recipient = get_option('image_cleaner_email_recipient', get_option('admin_email'));
$email_frequency = get_option('image_cleaner_email_frequency', 'weekly');
$email_subject = get_option('image_cleaner_email_subject', 'Reporte de limpieza');
$email_message = get_option('image_cleaner_email_message', 'Hola, aquí tienes tu reporte.');

?>
<div class="wrap">
    <h1>Configuración de Notificaciones</h1>

    <form method="post" action="">
        <!-- Campo oculto de seguridad -->
        <?php wp_nonce_field('image_cleaner_save_email', 'image_cleaner_email_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="email-recipient">Destinatario</label></th>
                <td><input type="email" id="email-recipient" name="email_recipient" value="<?php echo esc_attr($email_recipient); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="email-frequency">Frecuencia</label></th>
                <td>
                    <select id="email-frequency" name="email_frequency">
                        <option value="daily" <?php selected($email_frequency, 'daily'); ?>>Diaria</option>
                        <option value="weekly" <?php selected($email_frequency, 'weekly'); ?>>Semanal</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="email-subject">Asunto</label></th>
                <td><input type="text" id="email-subject" name="email_subject" value="<?php echo esc_attr($email_subject); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="email-message">Mensaje</label></th>
                <td><textarea id="email-message" name="email_message" rows="5" class="large-text"><?php echo esc_textarea($email_message); ?></textarea></td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">Guardar Cambios</button>
            <button type="submit" name="send_test_email" class="button button-secondary">Enviar Prueba</button>
            <button type="submit" name="reset_settings" class="button button-link" onclick="return confirm('¿Restablecer todo?');">Restablecer</button>
        </p>
    </form>
</div>
