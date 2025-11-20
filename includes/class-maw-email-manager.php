<?php
if (!defined('ABSPATH')) exit;

class MAW_Email_Manager {

    /**
     * Envía correo HTML con Branding de Mondays at Work.
     */
    public static function send($to, $subject, $template_name, $data = []) {
        
        // 1. Forzar Header HTML
        add_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        // 2. Cargar plantilla
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'templates/emails/' . $template_name . '.html';
        
        if (file_exists($file)) {
            $body = file_get_contents($file);
        } else {
            // Fallback por si no existe el archivo
            $body = '<h1>MAW Image Cleaner</h1><p>{{message}}</p>';
        }

        // 3. VARIABLES CORPORATIVAS (Branding)
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
            
            // Redes Sociales
            '{{social_fb}}'    => 'https://www.facebook.com/mondaysatwork',
            '{{social_x}}'     => 'https://x.com/mondaysatwork/',
            '{{social_in}}'    => 'https://www.linkedin.com/company/mondays-at-work',
            '{{social_gh}}'    => 'https://github.com/orgs/Mondays-at-work'
        ];

        // Mensaje por defecto si no se pasa
        if(!isset($data['{{message}}'])) $data['{{message}}'] = 'Notificación del sistema.';

        // Fusionar datos
        $final_data = array_merge($defaults, $data);

        // 4. Reemplazo de variables en el HTML
        foreach ($final_data as $key => $val) {
            $body = str_replace($key, $val, $body);
        }

        // 5. Enviar
        $result = wp_mail($to, $subject, $body);

        // 6. Limpiar filtro
        remove_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        return $result;
    }

    public static function set_html_content_type() {
        return 'text/html';
    }
}
