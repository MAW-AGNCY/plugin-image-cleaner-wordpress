<?php
if (!defined('ABSPATH')) exit;

class Image_Cleaner_Emails {

    public function __construct() {
        add_action('image_cleaner_daily_event', [$this, 'process_scheduled_email']);
    }

    public static function schedule_email_event() {
        if (!wp_next_scheduled('image_cleaner_daily_event')) {
            wp_schedule_event(time(), 'daily', 'image_cleaner_daily_event');
        }
    }

    public static function clear_email_event() {
        wp_clear_scheduled_hook('image_cleaner_daily_event');
    }

    public function process_scheduled_email() {
        // 1. Leer Configuración
        $freq = get_option('maw_email_freq', 'weekly');
        if ($freq === 'never') return;

        // 2. Lógica de Frecuencia (Simplificada para ejemplo)
        // Aquí comprobaríamos si hoy toca enviar según la opción (daily, weekly, monthly)
        // Por ahora, asumimos que si el cron corre, enviamos si es 'daily'.
        
        $recipient = get_option('maw_email_recipient', get_option('admin_email'));
        $template = get_option('maw_email_template', 'notification');

        // 3. Obtener Datos Reales para el Email
        global $wpdb;
        $trash_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='attachment' AND post_status='trash'");
        
        $data = [
            '{{message}}' => "Resumen automático: Tienes $trash_count imágenes en la papelera. Tu sistema está optimizado."
        ];

        // 4. Enviar
        MAW_Email_Manager::send($recipient, 'Reporte Automático MAW', $template, $data);
    }
}
