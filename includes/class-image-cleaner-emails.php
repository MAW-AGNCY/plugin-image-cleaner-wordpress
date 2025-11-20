<?php
if (!defined('ABSPATH')) exit;

class MAW_Email_Manager {
    /**
     * Envía correo HTML forzando content-type.
     */
    public static function send($to, $subject, $template_name, $data = []) {
        
        // 1. Forzar Header HTML
        add_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        // 2. Cargar o crear plantilla
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'templates/emails/' . $template_name . '.html';
        if (file_exists($file)) {
            $body = file_get_contents($file);
        } else {
            $body = '<div style="background:#f5f5f5; padding:20px;"><div style="background:#fff; padding:20px;"><h1>MAW Cleaner</h1><p>{{message}}</p></div></div>';
        }

        // 3. Reemplazar variables
        $data['{{site_name}}'] = get_bloginfo('name');
        $data['{{year}}'] = date('Y');
        if(!isset($data['{{message}}'])) $data['{{message}}'] = 'Notificación del sistema.';

        foreach ($data as $key => $val) {
            $body = str_replace($key, $val, $body);
        }

        // 4. Enviar
        $result = wp_mail($to, $subject, $body);

        // 5. Limpiar filtro
        remove_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        return $result;
    }

    public static function set_html_content_type() {
        return 'text/html';
    }
}
