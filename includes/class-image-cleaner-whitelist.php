<?php
if (!defined('ABSPATH')) { exit; }

class MAW_Image_Whitelist {

    private $option_name = 'maw_image_cleaner_whitelist';

    /**
     * Devuelve array de IDs protegidos
     */
    public function get_whitelist() {
        return get_option($this->option_name, []);
    }

    /**
     * Verifica si un ID está protegido
     */
    public function is_whitelisted($image_id) {
        $list = $this->get_whitelist();
        return in_array($image_id, $list);
    }
}
