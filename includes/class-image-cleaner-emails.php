<?php
if (!defined('ABSPATH')) exit;

/**
 * Class Image_Cleaner_Emails
 * Maneja la programación (Cron) y el envío de reportes automáticos.
 */
class Image_Cleaner_Emails {

    public function __construct() {
        // Hook del cron job personalizado
        add_action('image_cleaner_daily_event', [$this, 'process_scheduled_email']);
    }

    /**
     * Programa el evento cron si no existe.
     * Se llama al activar el plugin.
     */
    public static function schedule_email_event() {
        if (!wp_next_scheduled('image_cleaner_daily_event')) {
            wp_schedule_event(time(), 'daily', 'image_cleaner_daily_event');
        }
    }

    /**
     * Elimina el evento cron.
     * Se llama al desactivar el plugin.
     */
    public static function clear_email_event() {
        wp_clear_scheduled_hook('image_cleaner_daily_event');
    }

    /**
     * Procesa el envío del email programado.
     * Verifica la configuración de frecuencia antes de enviar.
     */
    public function process_scheduled_email() {
        // 1. Obtener configuración
        $freq = get_option('maw_email_freq', 'weekly');
        
        if ($freq === 'never') {
            return; // El usuario desactivó los envíos automáticos
        }

        // 2. Verificar Frecuencia (El cron corre a diario, nosotros filtramos)
        $should_send = false;
        $today_day = date('N'); // 1 (Lunes) a 7 (Domingo)
        $today_date = date('j'); // 1 a 31

        switch ($freq) {
            case 'daily':
                $should_send = true;
                break;
            case 'weekly':
                // Enviar solo los Lunes (1)
                if ($today_day == 1) $should_send = true;
                break;
            case 'monthly':
                // Enviar solo el día 1 del mes
                if ($today_date == 1) $should_send = true;
                break;
        }

        if (!$should_send) {
            return; // Hoy no toca
        }

        // 3. Recopilar Estadísticas para el Reporte
        global $wpdb;
        
        // Contar elementos en papelera
        $trash_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='attachment' AND post_status='trash'");
        
        // Contar elementos protegidos
        $whitelist = get_option('maw_image_cleaner_whitelist', []);
        $protected_count = count($whitelist);

        // Construir mensaje del reporte
        $message_content = "Resumen automático de estado de tu biblioteca:<br><br>";
        $message_content .= "<ul>";
        $message_content .= "<li><strong>Archivos en Papelera:</strong> $trash_count (Ocupando espacio)</li>";
        $message_content .= "<li><strong>Archivos Protegidos (Whitelist):</strong> $protected_count</li>";
        
        if ($trash_count > 0) {
            $message_content .= "<li><strong>Acción recomendada:</strong> Revisa la papelera si deseas liberar espacio definitivamente.</li>";
        } else {
            $message_content .= "<li><strong>Estado:</strong> Tu sistema está limpio y optimizado.</li>";
        }
        $message_content .= "</ul>";

        // 4. Enviar Correo
        $recipient = get_option('maw_email_recipient', get_option('admin_email'));
        $template = get_option('maw_email_template', 'report-summary-template');

        // Pasamos el tipo 'summary' para que MAW_Email_Manager verifique si el usuario
        // tiene activada la casilla de "Resumen Periódico" en las preferencias.
        MAW_Email_Manager::send(
            $recipient, 
            'Resumen Periódico - MAW Cleaner', 
            $template, 
            ['{{message}}' => $message_content],
            'summary' // <--- TIPO DE NOTIFICACIÓN (Preferencia de usuario)
        );
    }
}
