<?php
if (!defined('ABSPATH')) { exit; }

class MAW_Image_Whitelist {

    private $option_name = 'maw_image_cleaner_whitelist';

    public function __construct() {
        // Registrar AJAX para añadir/quitar de whitelist desde la tabla
        add_action('wp_ajax_maw_toggle_whitelist', [$this, 'ajax_toggle_whitelist']);
    }

    /**
     * Obtiene la lista de IDs ignorados.
     */
    public function get_whitelist() {
        return get_option($this->option_name, []);
    }

    /**
     * Verifica si una imagen está en la whitelist.
     */
    public function is_whitelisted($image_id) {
        $list = $this->get_whitelist();
        return in_array($image_id, $list);
    }

    /**
     * AJAX: Añadir o quitar de la lista.
     */
    public function ajax_toggle_whitelist() {
        // Verificar nonce y permisos
        check_ajax_referer('image_cleaner_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }

        $image_id = intval($_POST['image_id']);
        $list = $this->get_whitelist();

        if (in_array($image_id, $list)) {
            // Quitar
            $list = array_diff($list, [$image_id]);
            $action = 'removed';
        } else {
            // Añadir
            $list[] = $image_id;
            $action = 'added';
        }

        update_option($this->option_name, array_values($list));
        wp_send_json_success(['status' => $action]);
    }
}
