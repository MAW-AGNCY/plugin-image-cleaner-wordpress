<?php
if (!defined('ABSPATH')) { exit; }

class Image_Cleaner_Ajax {

    public function __construct() {
        // Registrar acciones AJAX para usuarios logueados
        add_action('wp_ajax_maw_toggle_whitelist', [$this, 'handle_whitelist_toggle']);
    }

    /**
     * Maneja el bloqueo/desbloqueo de imágenes desde los botones pequeños
     */
    public function handle_whitelist_toggle() {
        // 1. Verificar Permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos denegados']);
        }

        // 2. Obtener datos
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        
        if (!$image_id) {
            wp_send_json_error(['message' => 'ID inválido']);
        }

        // 3. Instanciar Manager
        $whitelist_manager = Image_Cleaner_Pro::get_instance()->get_whitelist_manager();
        $current_list = $whitelist_manager->get_whitelist();

        // 4. Lógica Toggle (Si existe quita, si no existe añade)
        if (in_array($image_id, $current_list)) {
            $new_list = array_diff($current_list, [$image_id]);
            $status = 'removed';
        } else {
            $current_list[] = $image_id;
            $new_list = $current_list;
            $status = 'added';
        }

        // 5. Guardar
        update_option('maw_image_cleaner_whitelist', array_values($new_list));

        // 6. Respuesta JSON
        wp_send_json_success(['status' => $status, 'id' => $image_id]);
    }
}
