<?php
if (!defined('ABSPATH')) exit;

class MAW_Email_Manager {

    /**
     * Verifica si un tipo de notificación está activo en la configuración.
     */
    public static function is_active($type) {
        $defaults = ['summary', 'recovery', 'error'];
        $prefs = get_option('maw_email_prefs', $defaults);
        
        // Si la opción no existe o es array vacío, asumimos que el usuario no quiere nada
        // a menos que sea la primera instalación (handled by defaults in get_option)
        if (!is_array($prefs)) return false;

        return in_array($type, $prefs);
    }

    /**
     * Envía correo HTML con Branding.
     * Soporta múltiples destinatarios (separados por coma).
     * 
     * @param string $to Destinatario(s)
     * @param string $subject Asunto
     * @param string $template_name Nombre del archivo template
     * @param array $data Datos a reemplazar
     * @param string $type (Opcional) Tipo de notificación para verificar preferencia.
     */
    public static function send($to, $subject, $template_name, $data = [], $type = null) {
        
        // 1. Verificar Preferencia
        if ($type !== null && !self::is_active($type)) {
            return false; // El usuario desactivó este tipo de alerta
        }

        // 2. Headers HTML
        add_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        // 3. Cargar plantilla
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'templates/emails/' . $template_name . '.html';
        
        if (file_exists($file)) {
            $body = file_get_contents($file);
        } else {
            $body = '<div style="padding:20px; font-family:sans-serif;"><h1>MAW Image Cleaner</h1><p>{{message}}</p></div>';
        }

        // 4. Variables Corporativas
        $defaults = [
            '{{site_name}}'    => get_bloginfo('name'),
            '{{site_url}}'     => home_url(),
            '{{year}}'         => date('Y'),
            '{{plugin_name}}'  => 'MAW Image Cleaner',
            '{{logo_url}}'     => 'https://mondaysatwork.com/wp-content/uploads/2014/03/logo.mondaysatwork.png',
            '{{primary_color}}'=> '#a81010',
            '{{dark_color}}'   => '#444444',
            '{{text_color}}'   => '#808080',
            '{{border_color}}' => '#e1e1e1',
            '{{social_fb}}'    => 'https://www.facebook.com/mondaysatwork',
            '{{social_x}}'     => 'https://x.com/mondaysatwork/',
            '{{social_in}}'    => 'https://www.linkedin.com/company/mondays-at-work',
            '{{social_gh}}'    => 'https://github.com/orgs/Mondays-at-work'
        ];

        if(!isset($data['{{message}}'])) $data['{{message}}'] = 'Notificación del sistema.';
        
        $final_data = array_merge($defaults, $data);

        // Reemplazo
        foreach ($final_data as $key => $val) {
            $body = str_replace($key, $val, $body);
        }

        // 5. Enviar
        // wp_mail acepta string "email1, email2" perfectamente
        $result = wp_mail($to, $subject, $body);
        
        // Limpiar filtro
        remove_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        return $result;
    }

    public static function set_html_content_type() {
        return 'text/html';
    }
}
