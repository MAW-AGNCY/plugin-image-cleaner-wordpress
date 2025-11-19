<?php
if (!defined('ABSPATH')) {
    exit; 
}

global $wpdb;

// 1. Seguridad: Verificar permisos antes de nada
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'image-cleaner'));
}

$images_per_page_options = [20, 50, 100];
$images_per_page = isset($_GET['images_per_page']) && in_array(intval($_GET['images_per_page']), $images_per_page_options) 
    ? intval($_GET['images_per_page']) 
    : 20;

$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $images_per_page;

$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'all';
$filter_date_start = isset($_GET['filter_date_start']) ? sanitize_text_field($_GET['filter_date_start']) : '';
$filter_date_end = isset($_GET['filter_date_end']) ? sanitize_text_field($_GET['filter_date_end']) : '';

// 2. Procesar eliminación (Enviar a papelera)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['images_to_delete'])) {
    // Verificar Nonce (Seguridad CSRF)
    check_admin_referer('image_cleaner_delete_images', 'image_cleaner_nonce');

    $images_to_delete = array_map('intval', $_POST['images_to_delete']);
    $count = 0;
    
    foreach ($images_to_delete as $image_id) {
        // IMPORTANTE: false = enviar a papelera (recuperable). true = borrar para siempre.
        if (wp_delete_attachment($image_id, false)) { 
            $count++;
        }
    }
    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d imágenes movidas a la papelera. Puedes recuperarlas en la pestaña Recuperación.', 'image-cleaner'), $count) . '</p></div>';
}

// Construir consulta
$query = "SELECT ID, post_author, post_title, post_mime_type, post_date FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_status = 'inherit'";

if ($filter_type !== 'all') {
    $query .= $wpdb->prepare(" AND post_mime_type LIKE %s", $filter_type . '%');
}
if ($filter_date_start) {
    $query .= $wpdb->prepare(" AND post_date >= %s", $filter_date_start);
}
if ($filter_date_end) {
    $query .= $wpdb->prepare(" AND post_date <= %s", $filter_date_end);
}

// Contar total para paginación
$count_query = str_replace("SELECT ID, post_author, post_title, post_mime_type, post_date", "SELECT COUNT(*)", $query);
$total_images = $wpdb->get_var($count_query);

// Obtener resultados
$query .= $wpdb->prepare(" ORDER BY post_date DESC LIMIT %d OFFSET %d", $images_per_page, $offset);
$images = $wpdb->get_results($query);

?>
<div class="wrap">
    <h1><?php esc_html_e('Filtrar y Limpiar Imágenes', 'image-cleaner'); ?></h1>
    
    <!-- Estilos CSS (Inline para asegurar carga) -->
    <style>
        .tablenav-pages a, .tablenav-pages span { padding: 5px 10px; border: 1px solid #ccc; margin: 2px; text-decoration: none; background: #fff; }
        .tablenav-pages .current { background: #0073aa; color: white; border-color: #0073aa; }
        .image-filters-form { background: #fff; padding: 15px; margin-bottom: 20px; border: 1px solid #ccd0d4; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .in-use-yes { color: green; font-weight: bold; }
        .in-use-no { color: red; font-weight: bold; }
        .in-use-maybe { color: orange; font-weight: bold; }
    </style>

    <!-- Formulario de filtros -->
    <form method="get" class="image-filters-form">
        <input type="hidden" name="page" value="image-cleaner-filters">
        <div>
            <label for="filter_type"><?php esc_html_e('Tipo de archivo', 'image-cleaner'); ?></label><br>
            <select name="filter_type" id="filter_type">
                <option value="all" <?php selected($filter_type, 'all'); ?>><?php esc_html_e('Todos', 'image-cleaner'); ?></option>
                <option value="image/jpeg" <?php selected($filter_type, 'image/jpeg'); ?>>JPEG</option>
                <option value="image/png" <?php selected($filter_type, 'image/png'); ?>>PNG</option>
                <option value="image/webp" <?php selected($filter_type, 'image/webp'); ?>>WEBP</option>
            </select>
        </div>
        <div>
            <label><?php esc_html_e('Fecha Inicio', 'image-cleaner'); ?></label><br>
            <input type="date" name="filter_date_start" value="<?php echo esc_attr($filter_date_start); ?>">
        </div>
        <div>
            <label><?php esc_html_e('Fecha Fin', 'image-cleaner'); ?></label><br>
            <input type="date" name="filter_date_end" value="<?php echo esc_attr($filter_date_end); ?>">
        </div>
        <div>
            <button type="submit" class="button button-secondary"><?php esc_html_e('Filtrar', 'image-cleaner'); ?></button>
        </div>
    </form>

    <!-- Tabla de imágenes -->
    <form method="post" onsubmit="return confirm('<?php esc_html_e('¿Mover a la papelera las imágenes seleccionadas?', 'image-cleaner'); ?>');">
        <?php wp_nonce_field('image_cleaner_delete_images', 'image_cleaner_nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="select-all"></td>
                    <th><?php esc_html_e('Imagen', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Nombre', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('¿En uso?', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Fecha', 'image-cleaner'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($images)) : ?>
                    <?php foreach ($images as $image) : 
                        // -- Lógica Mejorada de Detección de Uso --
                        $is_in_use = false;
                        $usage_text = '<span class="in-use-no">No detectado</span>';

                        // 1. Imagen Destacada
                        $featured = get_posts(['post_type' => 'any', 'meta_key' => '_thumbnail_id', 'meta_value' => $image->ID, 'posts_per_page' => 1]);
                        
                        // 2. Galería WooCommerce (Búsqueda aproximada en meta value serializado o CSV)
                        // Nota: Esto es intensivo, se simplifica para el ejemplo
                        $woo_gallery = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_product_image_gallery' AND meta_value LIKE %s LIMIT 1", '%' . $image->ID . '%'));

                        if (!empty($featured)) {
                            $is_in_use = true;
                            $usage_text = '<span class="in-use-yes">Imagen Destacada</span> <a href="'.get_edit_post_link($featured[0]->ID).'" target="_blank">(Ver)</a>';
                        } elseif ($woo_gallery) {
                            $is_in_use = true;
                            $usage_text = '<span class="in-use-yes">Galería Producto</span> <a href="'.get_edit_post_link($woo_gallery).'" target="_blank">(Ver)</a>';
                        }
                        
                        // ADVERTENCIA: No podemos estar 100% seguros sin escanear post_content
                        if (!$is_in_use) {
                            $usage_text .= ' <small style="color:#666;">(Precaución: podría estar en contenido HTML)</small>';
                        }
                    ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="images_to_delete[]" value="<?php echo esc_attr($image->ID); ?>">
                            </th>
                            <td><?php echo wp_get_attachment_image($image->ID, [50, 50]); ?></td>
                            <td>
                                <strong><?php echo esc_html($image->post_title); ?></strong><br>
                                <small><?php echo esc_html($image->post_mime_type); ?></small>
                            </td>
                            <td><?php echo $usage_text; ?></td>
                            <td><?php echo esc_html(date('Y-m-d', strtotime($image->post_date))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5"><?php esc_html_e('No se encontraron imágenes.', 'image-cleaner'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions">
                <button type="submit" class="button button-primary"><?php esc_html_e('Borrar Seleccionadas', 'image-cleaner'); ?></button>
            </div>
            <div class="tablenav-pages">
                <?php
                // Paginación simple
                $total_pages = ceil($total_images / $images_per_page);
                for ($i = 1; $i <= min(10, $total_pages); $i++) {
                    $active = ($current_page == $i) ? 'current' : '';
                    echo '<a class="'.$active.'" href="' . esc_url(add_query_arg(['paged' => $i])) . '">' . $i . '</a>';
                }
                ?>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('select-all').addEventListener('click', function() {
        var checkboxes = document.querySelectorAll('input[name="images_to_delete[]"]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });
</script>
