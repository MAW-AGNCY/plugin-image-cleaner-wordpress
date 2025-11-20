<?php if (!defined('ABSPATH')) exit;

// Guardar Configuración
if (isset($_POST['save_settings']) && check_admin_referer('maw_email_settings', 'maw_nonce')) {
    update_option('maw_email_recipient', sanitize_email($_POST['recipient']));
    update_option('maw_email_freq', sanitize_text_field($_POST['frequency']));
    update_option('maw_email_template', sanitize_text_field($_POST['template']));
    echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada.</p></div>';
}

// Enviar Test
if (isset($_POST['send_test']) && check_admin_referer('maw_email_settings', 'maw_nonce')) {
    $to = sanitize_email($_POST['recipient']);
    $tpl = sanitize_text_field($_POST['template']);
    
    if (MAW_Email_Manager::send($to, 'Test de Notificación MAW', $tpl, ['{{message}}' => '¡Hola! Si lees esto, el sistema de plantillas HTML funciona correctamente.'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Email de prueba enviado correctamente a ' . $to . '.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error al enviar el email. Revisa tu servidor SMTP.</p></div>';
    }
}

// Valores Actuales
$recipient = get_option('maw_email_recipient', get_option('admin_email'));
$freq = get_option('maw_email_freq', 'weekly');
$template = get_option('maw_email_template', 'notification');

// Escanear Templates Disponibles (En carpeta)
$templates_found = glob(IMAGE_CLEANER_PLUGIN_DIR . 'templates/emails/*.html');
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-email-alt"></span> Configuración de Notificaciones</h1>
        </div>
    </div>

    <div class="maw-dashboard-grid" style="grid-template-columns: 2fr 1fr;">
        
        <!-- Configuración -->
        <div class="maw-card">
            <h3>Ajustes de Envío</h3>
            <form method="post">
                <?php wp_nonce_field('maw_email_settings', 'maw_nonce'); ?>
                
                <table class="form-table maw-settings-table">
                    <tr>
                        <th>Destinatario de Alertas</th>
                        <td>
                            <input type="email" name="recipient" value="<?php echo esc_attr($recipient); ?>" class="regular-text" required>
                            <p class="description">Quien recibirá los resúmenes de limpieza.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Frecuencia de Resumen</th>
                        <td>
                            <select name="frequency">
                                <option value="daily" <?php selected($freq, 'daily'); ?>>Diario</option>
                                <option value="weekly" <?php selected($freq, 'weekly'); ?>>Semanal</option>
                                <option value="monthly" <?php selected($freq, 'monthly'); ?>>Mensual</option>
                                <option value="never" <?php selected($freq, 'never'); ?>>Nunca (Manual)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Plantilla de Diseño</th>
                        <td>
                            <select name="template">
                                <?php foreach($templates_found as $tpl_file): 
                                    $name = basename($tpl_file, '.html'); 
                                ?>
                                    <option value="<?php echo esc_attr($name); ?>" <?php selected($template, $name); ?>>
                                        <?php echo ucfirst($name); ?> Template
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Selecciona el diseño visual del correo.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="save_settings" class="button button-primary button-large">Guardar Cambios</button>
                    <button type="submit" name="send_test" class="button button-secondary">Enviar Test Ahora</button>
                </p>
            </form>
        </div>

        <!-- Preview (Instrucciones) -->
        <div class="maw-card" style="background:#f9f9f9;">
            <h3>Sobre las Notificaciones</h3>
            <p>El sistema enviará un reporte automático con:</p>
            <ul style="list-style:disc; margin-left:20px;">
                <li>Espacio liberado recientemente.</li>
                <li>Número de archivos en papelera.</li>
                <li>Alertas de seguridad.</li>
            </ul>
            <hr>
            <p><strong>Nota:</strong> Asegúrate de que tu WordPress puede enviar correos (SMTP configurado).</p>
        </div>

    </div>
</div>
