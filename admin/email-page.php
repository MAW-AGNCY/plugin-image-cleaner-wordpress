<?php if (!defined('ABSPATH')) exit;

// --- GUARDAR CONFIGURACIÓN ---
if (isset($_POST['save_settings']) && check_admin_referer('maw_email_settings', 'maw_nonce')) {
    
    // 1. Procesar Múltiples Emails
    $raw_emails = isset($_POST['recipient']) ? $_POST['recipient'] : '';
    $emails_array = explode(',', $raw_emails);
    $clean_emails = [];
    
    foreach ($emails_array as $email) {
        $sanitized = sanitize_email(trim($email));
        if (is_email($sanitized)) {
            $clean_emails[] = $sanitized;
        }
    }
    // Guardamos como string separado por comas para wp_mail
    $final_recipients = implode(',', $clean_emails);

    update_option('maw_email_recipient', $final_recipients);
    update_option('maw_email_freq', sanitize_text_field($_POST['frequency']));
    
    // Guardar preferencias checkboxes
    $prefs = isset($_POST['prefs']) ? array_map('sanitize_text_field', $_POST['prefs']) : [];
    update_option('maw_email_prefs', $prefs);

    echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
}

// --- ENVIAR TEST ---
if (isset($_POST['send_test']) && check_admin_referer('maw_email_settings', 'maw_nonce')) {
    $to = get_option('maw_email_recipient', get_option('admin_email')); // Usar lo guardado o el input
    
    // Enviamos usando la plantilla estándar de notificación
    if (MAW_Email_Manager::send($to, 'Test de Conexión MAW', 'notification', ['{{message}}' => '¡Hola! Esta es una prueba de tus notificaciones corporativas. Si ves esto, el sistema de envío funciona.'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Email de prueba enviado a: <strong>' . esc_html($to) . '</strong></p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error al enviar. Revisa la configuración SMTP de tu servidor.</p></div>';
    }
}

// --- OBTENER VALORES ---
$recipient = get_option('maw_email_recipient', get_option('admin_email'));
$freq = get_option('maw_email_freq', 'weekly');
$prefs = get_option('maw_email_prefs', ['summary', 'error', 'recovery']);
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-email-alt"></span> Centro de Notificaciones</h1>
        </div>
    </div>

    <div class="maw-dashboard-grid" style="grid-template-columns: 2fr 1fr; align-items: start;">
        
        <!-- COLUMNA IZQUIERDA: CONFIGURACIÓN -->
        <div class="maw-card">
            <h3>Ajustes de Envío</h3>
            <form method="post">
                <?php wp_nonce_field('maw_email_settings', 'maw_nonce'); ?>
                
                <table class="maw-table" style="border:none; box-shadow:none;">
                    <tr>
                        <th width="220">Destinatarios</th>
                        <td>
                            <input type="text" name="recipient" value="<?php echo esc_attr($recipient); ?>" class="regular-text" style="width:100%" placeholder="admin@empresa.com, socio@empresa.com">
                            <p class="description">Puedes introducir múltiples correos separados por comas ( , ).</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Tipos de Alerta</th>
                        <td>
                            <div style="background:#f9f9f9; padding:15px; border:1px solid #eee; border-radius:4px;">
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" name="prefs[]" value="summary" <?php checked(in_array('summary', $prefs)); ?>> 
                                    <strong>Resumen Periódico</strong> (Estado de la biblioteca y espacio)
                                </label>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" name="prefs[]" value="recovery" <?php checked(in_array('recovery', $prefs)); ?>> 
                                    <strong>Restauración</strong> (Aviso cuando alguien recupera un archivo)
                                </label>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" name="prefs[]" value="clean" <?php checked(in_array('clean', $prefs)); ?>> 
                                    <strong>Limpieza Manual</strong> (Aviso de borrados masivos)
                                </label>
                                <label style="display:block; margin-bottom:0;">
                                    <input type="checkbox" name="prefs[]" value="error" <?php checked(in_array('error', $prefs)); ?>> 
                                    <strong>Errores Críticos</strong> (Fallos del sistema - Recomendado)
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>Frecuencia del Resumen</th>
                        <td>
                            <select name="frequency" style="width:100%">
                                <option value="daily" <?php selected($freq, 'daily'); ?>>Diario (Cada mañana)</option>
                                <option value="weekly" <?php selected($freq, 'weekly'); ?>>Semanal (Lunes)</option>
                                <option value="monthly" <?php selected($freq, 'monthly'); ?>>Mensual (Día 1)</option>
                                <option value="never" <?php selected($freq, 'never'); ?>>Desactivar Resúmenes</option>
                            </select>
                            <p class="description">Define cuándo quieres recibir el informe de estado.</p>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:20px; display:flex; gap:10px; padding-top:20px; border-top:1px solid #eee;">
                    <button type="submit" name="save_settings" class="button button-primary button-large">Guardar Configuración</button>
                    <button type="submit" name="send_test" class="button button-secondary">Enviar Test de Conexión</button>
                </div>
            </form>
        </div>

        <!-- COLUMNA DERECHA: VISTA PREVIA VISUAL (MOCKUP) -->
        <div class="maw-card" style="background:#f0f0f1; border:none; box-shadow:none;">
            <h3 style="margin-bottom:15px;">Vista Previa</h3>
            
            <!-- Mockup Visual del Email -->
            <div style="background:#fff; border:1px solid #e1e1e1; border-radius:4px; overflow:hidden; font-family:Arial, sans-serif; font-size:12px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                <!-- Header Rojo -->
                <div style="border-top:4px solid #a81010; padding:20px; text-align:center; border-bottom:1px solid #eee;">
                    <img src="https://mondaysatwork.com/wp-content/uploads/2014/03/logo.mondaysatwork.png" alt="Logo" style="max-width:120px; height:auto;">
                </div>
                
                <!-- Body -->
                <div style="padding:20px; color:#444;">
                    <strong style="color:#a81010; font-size:14px; display:block; margin-bottom:10px;">Hola, Administrador</strong>
                    <p style="margin-bottom:15px; color:#555; line-height:1.4;">Esta es una vista previa de cómo recibirás las notificaciones. Usamos tu identidad corporativa.</p>
                    
                    <div style="background:#f9f9f9; padding:10px; border:1px solid #eee; margin-bottom:15px;">
                        <strong>Resumen:</strong><br>
                        - Papelera: 12 archivos<br>
                        - Estado: Optimizado
                    </div>
                    
                    <div style="text-align:center;">
                        <span style="background:#a81010; color:#fff; padding:5px 10px; border-radius:3px; font-size:10px; font-weight:bold;">Ir al Dashboard</span>
                    </div>
                </div>

                <!-- Footer -->
                <div style="background:#f4f4f4; padding:10px; text-align:center; color:#888; font-size:10px; border-top:1px solid #eee;">
                    &copy; <?php echo date('Y'); ?> Mondays at Work.<br>
                    <span style="color:#444;">Facebook | X | LinkedIn</span>
                </div>
            </div>

            <p class="description" style="margin-top:15px; text-align:center;">
                El sistema selecciona automáticamente la plantilla (Alerta, Éxito o Resumen) según el evento.
            </p>
        </div>

    </div>
</div>
