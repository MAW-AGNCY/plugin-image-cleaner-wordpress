<?php if (!defined('ABSPATH')) exit;

// --- GUARDAR CONFIGURACIÓN ---
if (isset($_POST['save_settings']) && check_admin_referer('maw_email_settings', 'maw_nonce')) {
    update_option('maw_email_recipient', sanitize_email($_POST['recipient']));
    update_option('maw_email_freq', sanitize_text_field($_POST['frequency']));
    update_option('maw_email_template', sanitize_text_field($_POST['template']));
    
    // Guardar preferencias (Checkboxes)
    $prefs = isset($_POST['prefs']) ? array_map('sanitize_text_field', $_POST['prefs']) : [];
    update_option('maw_email_prefs', $prefs);

    echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
}

// --- ENVIAR TEST ---
if (isset($_POST['send_test']) && check_admin_referer('maw_email_settings', 'maw_nonce')) {
    $to = sanitize_email($_POST['recipient']);
    $tpl = sanitize_text_field($_POST['template']);
    
    // Forzamos el envío (sin pasar tipo) para que el test siempre llegue
    if (MAW_Email_Manager::send($to, 'Test de Notificación MAW', $tpl, ['{{message}}' => '¡Hola! Esta es una prueba de tus notificaciones corporativas.'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Email de prueba enviado a ' . $to . '.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error al enviar. Revisa la configuración SMTP de tu servidor.</p></div>';
    }
}

// --- OBTENER VALORES ---
$recipient = get_option('maw_email_recipient', get_option('admin_email'));
$freq = get_option('maw_email_freq', 'weekly');
$template = get_option('maw_email_template', 'notification');
$prefs = get_option('maw_email_prefs', ['summary', 'error', 'recovery']); // Default: activas principales

// Escanear templates
$templates_found = glob(IMAGE_CLEANER_PLUGIN_DIR . 'templates/emails/*.html');
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-email-alt"></span> Centro de Notificaciones</h1>
        </div>
    </div>

    <div class="maw-dashboard-grid" style="grid-template-columns: 2fr 1fr;">
        
        <!-- FORMULARIO -->
        <div class="maw-card">
            <h3>Configuración de Alertas</h3>
            <form method="post">
                <?php wp_nonce_field('maw_email_settings', 'maw_nonce'); ?>
                
                <table class="maw-table" style="border:none; box-shadow:none;">
                    <tr>
                        <th width="200">Destinatario</th>
                        <td>
                            <input type="email" name="recipient" value="<?php echo esc_attr($recipient); ?>" class="regular-text" style="width:100%" required>
                            <p class="description">Email donde llegarán los reportes.</p>
                        </td>
                    </tr>
                    
                    <!-- NUEVO: SELECCIÓN DE NOTIFICACIONES -->
                    <tr>
                        <th>Tipos de Alerta</th>
                        <td>
                            <fieldset>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" name="prefs[]" value="summary" <?php checked(in_array('summary', $prefs)); ?>> 
                                    <strong>Resumen Periódico</strong> (Estado de la biblioteca)
                                </label>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" name="prefs[]" value="recovery" <?php checked(in_array('recovery', $prefs)); ?>> 
                                    <strong>Restauración</strong> (Cuando alguien recupera un archivo)
                                </label>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" name="prefs[]" value="clean" <?php checked(in_array('clean', $prefs)); ?>> 
                                    <strong>Limpieza Manual</strong> (Cuando se borran archivos)
                                </label>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" name="prefs[]" value="error" <?php checked(in_array('error', $prefs)); ?>> 
                                    <strong>Errores Críticos</strong> (Fallos en escaneo o sistema)
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th>Frecuencia del Resumen</th>
                        <td>
                            <select name="frequency" style="width:100%">
                                <option value="daily" <?php selected($freq, 'daily'); ?>>Diario</option>
                                <option value="weekly" <?php selected($freq, 'weekly'); ?>>Semanal</option>
                                <option value="monthly" <?php selected($freq, 'monthly'); ?>>Mensual</option>
                                <option value="never" <?php selected($freq, 'never'); ?>>Nunca enviar resúmenes</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Diseño de Plantilla</th>
                        <td>
                            <select name="template" style="width:100%">
                                <?php foreach($templates_found as $tpl_file): 
                                    $name = basename($tpl_file, '.html'); 
                                ?>
                                    <option value="<?php echo esc_attr($name); ?>" <?php selected($template, $name); ?>>
                                        <?php echo ucfirst(str_replace('-', ' ', $name)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" name="save_settings" class="button button-primary button-large">Guardar Preferencias</button>
                    <button type="submit" name="send_test" class="button button-secondary">Enviar Prueba</button>
                </div>
            </form>
        </div>

        <!-- INFO -->
        <div class="maw-card" style="background:#fcfcfc;">
            <h3>Vista Previa</h3>
            <p>Tus correos usarán la identidad de <strong>Mondays at Work</strong>:</p>
            <ul style="margin-left:20px; list-style:square; color:#666; font-size:13px;">
                <li>Logo en cabecera.</li>
                <li>Color corporativo <span style="color:#a81010">#a81010</span>.</li>
                <li>Enlaces a redes sociales en el pie.</li>
            </ul>
            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
            <p class="description">Nota: Los "Errores Críticos" se envían inmediatamente cuando ocurren, independientemente de la frecuencia del resumen.</p>
        </div>

    </div>
</div>
