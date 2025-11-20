<?php
if (!defined('ABSPATH')) { exit; }

class MAW_Email_Manager {

    /**
     * Envía un correo utilizando una plantilla HTML.
     *
     * @param string $to       Destinatario.
     * @param string $subject  Asunto.
     * @param string $template Nombre del archivo de plantilla (sin .html) en templates/emails/.
     * @param array  $data     Array asociativo de datos ['{{key}}' => 'value'].
     * @return bool            Resultado de wp_mail.
     */
    public static function send($to, $subject, $template, $data = []) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // 1. Cargar Plantilla
        $template_path = IMAGE_CLEANER_PLUGIN_DIR . 'templates/emails/' . $template . '.html';
        
        if (!file_exists($template_path)) {
            // Fallback si no existe la plantilla
            return wp_mail($to, $subject, print_r($data, true), $headers);
        }

        $body = file_get_contents($template_path);

        // 2. Datos globales (Logo, Colores, Año)
        $data['{{year}}'] = date('Y');
        $data['{{site_name}}'] = get_bloginfo('name');
        $data['{{site_url}}'] = home_url();
        $data['{{plugin_name}}'] = 'MAW Image Cleaner';

        // 3. Reemplazar variables
        foreach ($data as $key => $value) {
            $body = str_replace($key, $value, $body);
        }

        return wp_mail($to, $subject, $body, $headers);
    }
}
