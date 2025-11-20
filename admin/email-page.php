<?php if (!defined('ABSPATH')) exit; 

if(isset($_POST['send_test']) && check_admin_referer('maw_email', 'maw_nonce')) {
    $to = sanitize_email($_POST['recipient']);
    if(MAW_Email_Manager::send($to, 'Test MAW Cleaner', 'notification', ['{{message}}'=>'Esto es una prueba HTML.'])) {
        echo '<div class="notice notice-success"><p>Email enviado.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Fallo al enviar.</p></div>';
    }
}
$recipient = get_option('maw_email_recipient', get_option('admin_email'));
?>
<div class="maw-wrap">
    <div class="maw-header"><div class="maw-title"><h1>Notificaciones</h1></div></div>
    
    <div class="maw-card">
        <form method="post">
            <?php wp_nonce_field('maw_email', 'maw_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Destinatario</th>
                    <td><input type="email" name="recipient" value="<?php echo esc_attr($recipient); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Guardar</button>
                <button type="submit" name="send_test" class="button button-secondary">Enviar Test HTML</button>
            </p>
        </form>
    </div>
</div>
