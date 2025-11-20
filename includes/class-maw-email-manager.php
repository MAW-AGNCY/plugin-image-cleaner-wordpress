<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Class MAW_Email_Manager
 * Gestiona el envío de correos transaccionales en formato HTML.
 */
class MAW_Email_Manager {

    /**
     * Envía correo HTML.
     */
    public static function send($to, $subject, $template_name, $data = []) {
        
        // 1. Headers HTML Forzados
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];

        // 2. Buscar Plantilla
        $file = IMAGE_CLEANER_PLUGIN_DIR . 'templates/emails/' . $template_name . '.html';
        
        if (file_exists($file)) {
            $message = file_get_contents($file);
        } else {
            // Fallback HTML básico si no existe el archivo
            $message = '<html><body><h1>' . esc_html($subject) . '</h1><p>Contenido no disponible.</p></body></html>';
        }

        // 3. Variables Globales
        $data['{{site_name}}'] = get_bloginfo('name');
        $data['{{site_url}}'] = home_url();
        $data['{{year}}'] = date('Y');
        $data['{{plugin_name}}'] = 'MAW Image Cleaner';

        // 4. Reemplazo
        foreach ($data as $key => $value) {
            $message = str_replace($key, $value, $message);
        }

        // 5. Enviar
        return wp_mail($to, $subject, $message, $headers);
    }
}
