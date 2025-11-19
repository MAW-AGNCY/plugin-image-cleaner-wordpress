<?php
if (!defined('ABSPATH')) {
    exit; 
}

global $wpdb;

// 1. Seguridad y Dependencias
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'image-cleaner'));
}

// Obtener instancias de las clases principales a través del Singleton
$main_instance = Image_Cleaner_Pro::get_instance();
$logger = $main_instance->get_logger();
$whitelist_manager = $main_instance->get_whitelist_manager();

// Configuración de Paginación
$images_per_page_options = [20, 50, 100];
$images_per_page = isset($_GET['images_per_page']) && in_array(intval($_GET['images_per_page']), $images_per_page_options) 
    ? intval($_GET['images_per_page']) 
    : 20;

$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $images_per_page;

// Filtros
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'all';
$filter_date_start = isset($_GET['filter_date_start']) ? sanitize_text_field($_GET['filter_date_start']) : '';
$filter_date_end = isset($_GET['filter_date_end']) ? sanitize_text_field($_GET['filter_date_end']) : '';

// ---------------------------------------------------------
// 2. Procesar Acciones (POST) - Borrado Seguro y Auditado
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['images_to_delete'])) {
    check_admin_referer('image_cleaner_delete_images', 'image_cleaner_nonce');

    $images_to_delete = array_map('intval', $_POST['images_to_delete']);
    $count_deleted = 0;
    $count_skipped = 0;
    
    foreach ($images_to_delete as $image_id) {
        // A. Verificar Whitelist (Protección)
        if ($whitelist_manager->is_whitelisted($image_id)) {
            $count_skipped++;
            continue; // Saltar esta imagen
        }

        // Obtener datos para el Log antes de borrar
        $file_name = basename(get_attached_file($image_id));

        // B. Borrado Soft (A papelera)
        if (wp_delete_attachment($image_id, false)) { 
            $count_deleted++;
            // C. Registrar en Auditoría
            $logger->log('deleted', $file_name, 'Borrado manual desde Filtros');
        }
    }

    if ($count_deleted > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d imágenes movidas a la papelera.', 'image-cleaner'), $count_deleted) . '</p></div>';
    }
    if ($count_skipped > 0) {
        echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf(esc_html__('%d imágenes fueron ignoradas porque están bloqueadas (Whitelist).', 'image-cleaner'), $count_skipped) . '</p></div>';
    }
}

// ---------------------------------------------------------
// 3. Construir Consulta SQL
// ---------------------------------------------------------
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

// Paginación
$count_query = str_replace("SELECT ID, post_author, post_title, post_mime_type, post_date", "SELECT COUNT(*)", $query);
$total_images = $wpdb->get_var($count_query);
$query .= $wpdb->prepare(" ORDER BY post_date DESC LIMIT %d OFFSET %d", $images_per_page, $offset);
$images = $wpdb->get_results($query);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Filtrar y Limpiar Imágenes', 'image-cleaner'); ?></h1>
    
    <!-- CSS Embebido para la Grid View y Whitelist -->
    <style>
        /* Controles */
        .maw-controls-bar { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; background: #fff; padding: 10px; border: 1px solid #ccd0d4; }
        .maw-view-toggle .button.active { background: #0073aa; color: white; border-color: #0073aa; }
        
        /* Grid View Styles */
        .maw-grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; margin-top: 20px; }
        .maw-grid-item { border: 1px solid #ddd; background: #fff; padding: 0; border-radius: 4px; position: relative; transition: all 0.2s; overflow: hidden; }
        .maw-grid-item:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .maw-grid-item.selected { border-color: #d63638; background-color: #ffecec; }
        .maw-grid-item.whitelisted { border-color: #00a32a; opacity: 0.6; } /* Estilo visual para protegidos */
        
        .maw-grid-thumb { height: 120px; width: 100%; object-fit: cover; background: #eee; display: block; }
        .maw-grid-details { padding: 10px; font-size: 12px; }
        .maw-grid-filename { font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; margin-bottom: 5px; }
        
        .maw-grid-checkbox { position: absolute; top: 10px; left: 10px; transform: scale(1.3); z-index: 10; }
        .maw-grid-actions { padding: 0 10px 10px; text-align: center; }
        
        /* Table Whitelist Button */
        .maw-whitelist-btn.active { background-color: #00a32a; color: white; border-color: #008a20; }
    </style>

    <!-- Formulario de Filtros -->
    <form method="get" class="image-filters-form" style="margin-top: 20px; background: #fff; padding: 15px; border: 1px solid #ddd;">
        <input type="hidden" name="page" value="image-cleaner-filters">
        <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label for="filter_type">Tipo:</label>
                <select name="filter_type">
                    <option value="all" <?php selected($filter_type, 'all'); ?>>Todos</option>
                    <option value="image/jpeg" <?php selected($filter_type, 'image/jpeg'); ?>>JPEG</option>
                    <option value="image/png" <?php selected($filter_type, 'image/png'); ?>>PNG</option>
                </select>
            </div>
            <div>
                <button type="submit" class="button button-secondary">Filtrar</button>
            </div>
        </div>
    </form>

    <!-- Barra de Herramientas y Vistas -->
    <div class="maw-controls-bar">
        <div class="maw-pagination-info">
            Mostrando <?php echo count($images); ?> de <?php echo $total_images; ?> imágenes.
        </div>
        <div class="maw-view-toggle">
            <button type="button" class="button active" id="view-list-btn"><span class="dashicons dashicons-list-view"></span> Lista</button>
            <button type="button" class="button" id="view-grid-btn"><span class="dashicons dashicons-grid-view"></span> Cuadrícula</button>
        </div>
    </div>

    <!-- Formulario Principal de Acciones -->
    <form method="post" id="maw-bulk-action-form" onsubmit="return confirm('¿Mover a la papelera las imágenes seleccionadas? (Las protegidas serán ignoradas)');">
        <?php wp_nonce_field('image_cleaner_delete_images', 'image_cleaner_nonce'); ?>
        
        <!-- VISTA LISTA (Tabla Tradicional) -->
        <div id="maw-list-view-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column"><input type="checkbox" id="select-all-list"></td>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Protección (Whitelist)</th> <!-- Columna Nueva -->
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($images)) : ?>
                        <?php foreach ($images as $image) : 
                            $is_whitelisted = $whitelist_manager->is_whitelisted($image->ID);
                            $thumb_url = wp_get_attachment_image_src($image->ID, 'thumbnail');
                            $src = $thumb_url ? $thumb_url[0] : '';
                        ?>
                            <tr id="row-<?php echo $image->ID; ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="images_to_delete[]" value="<?php echo esc_attr($image->ID); ?>">
                                </th>
                                <td>
                                    <?php if($src): ?><img src="<?php echo esc_url($src); ?>" width="50" height="50" style="object-fit:cover"><?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($image->post_title); ?></strong><br>
                                    <small><?php echo esc_html($image->post_mime_type); ?> - <?php echo size_format(filesize(get_attached_file($image->ID))); ?></small>
                                </td>
                                <td>
                                    <!-- SNIPPET SOLICITADO: Botón de bloqueo para Tabla -->
                                    <button type="button" class="button button-small maw-whitelist-btn <?php echo $is_whitelisted ? 'active' : ''; ?>" data-id="<?php echo $image->ID; ?>">
                                        <?php echo $is_whitelisted ? 'Desbloquear' : 'Bloquear (Ignorar)'; ?>
                                    </button>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($image->post_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="5">No se encontraron imágenes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- VISTA GRID (Cuadrícula Visual) -->
        <div id="maw-grid-view-container" class="maw-grid-container" style="display:none;">
            <?php if (!empty($images)) : ?>
                <?php foreach ($images as $image) : 
                    $is_whitelisted = $whitelist_manager->is_whitelisted($image->ID);
                    $thumb_url = wp_get_attachment_image_src($image->ID, 'medium'); // Resolución más alta para grid
                    $src = $thumb_url ? $thumb_url[0] : '';
                ?>
                    <div class="maw-grid-item <?php echo $is_whitelisted ? 'whitelisted' : ''; ?>" id="grid-item-<?php echo $image->ID; ?>" onclick="toggleGridSelection(this)">
                        <input type="checkbox" name="images_to_delete[]" value="<?php echo $image->ID; ?>" class="maw-grid-checkbox">
                        
                        <?php if($src): ?>
                            <img src="<?php echo esc_url($src); ?>" class="maw-grid-thumb" alt="">
                        <?php else: ?>
                            <div class="maw-grid-thumb" style="display:flex;align-items:center;justify-content:center;">Sin IMG</div>
                        <?php endif; ?>

                        <div class="maw-grid-details">
                            <span class="maw-grid-filename"><?php echo esc_html($image->post_title); ?></span>
                            
                            <!-- Botón Whitelist Compacto para Grid -->
                            <button type="button" class="button button-small maw-whitelist-btn <?php echo $is_whitelisted ? 'active' : ''; ?>" 
                                    style="width:100%; margin-top:5px;" 
                                    data-id="<?php echo $image->ID; ?>"
                                    onclick="event.stopPropagation();"> <!-- Stop Propagation vital para no activar el checkbox -->
                                <?php echo $is_whitelisted ? 'Desbloquear' : 'Bloquear'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Botones de Acción Global -->
        <div class="tablenav bottom" style="margin-top: 20px;">
            <div class="alignleft actions">
                <button type="submit" class="button button-primary button-large">Mover Seleccionadas a Papelera</button>
            </div>
            
            <!-- Paginación -->
            <div class="tablenav-pages">
                <?php
                $total_pages = ceil($total_images / $images_per_page);
                for ($i = 1; $i <= min(10, $total_pages); $i++) {
                    $active = ($current_page == $i) ? 'current' : '';
                    echo '<a class="button '.$active.'" href="' . esc_url(add_query_arg(['paged' => $i])) . '">' . $i . '</a> ';
                }
                ?>
            </div>
        </div>
    </form>
</div>

<!-- JAVASCRIPT PARA INTERACTIVIDAD (Toggle View & AJAX Whitelist) -->
<script>
jQuery(document).ready(function($) {
    
    // 1. Lógica de Cambio de Vista (Lista / Grid)
    $('#view-grid-btn').click(function() {
        $('#maw-list-view-container').hide();
        $('#maw-grid-view-container').css('display', 'grid');
        $(this).addClass('active');
        $('#view-list-btn').removeClass('active');
    });
    
    $('#view-list-btn').click(function() {
        $('#maw-grid-view-container').hide();
        $('#maw-list-view-container').show();
        $(this).addClass('active');
        $('#view-grid-btn').removeClass('active');
    });

    // 2. Lógica de Selección en Grid
    window.toggleGridSelection = function(element) {
        var checkbox = $(element).find('input[type="checkbox"]');
        checkbox.prop('checked', !checkbox.prop('checked'));
        
        if (checkbox.prop('checked')) {
            $(element).addClass('selected');
        } else {
            $(element).removeClass('selected');
        }
    };
    
    $('.maw-grid-checkbox').click(function(e) {
        e.stopPropagation();
        var item = $(this).closest('.maw-grid-item');
        item.toggleClass('selected', this.checked);
    });

    // 3. AJAX: Whitelist (Bloquear/Desbloquear)
    $(document).on('click', '.maw-whitelist-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var imageId = button.data('id');
        
        // Feedback visual inmediato
        button.prop('disabled', true).text('...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'maw_toggle_whitelist',
                image_id: imageId,
                nonce: '<?php echo wp_create_nonce("image_cleaner_nonce"); ?>' // Idealmente pasar via wp_localize_script
            },
            success: function(response) {
                button.prop('disabled', false);
                if (response.success) {
                    // Actualizar botones en AMBAS vistas (Lista y Grid) para este ID
                    var buttons = $('.maw-whitelist-btn[data-id="' + imageId + '"]');
                    var gridItem = $('#grid-item-' + imageId);

                    if (response.data.status === 'added') {
                        buttons.addClass('active').text('Desbloquear');
                        gridItem.addClass('whitelisted');
                    } else {
                        buttons.removeClass('active');
                        // Texto diferente según vista (Full o Compacto)
                        buttons.each(function() {
                            if($(this).parents('.maw-grid-item').length) {
                                $(this).text('Bloquear');
                            } else {
                                $(this).text('Bloquear (Ignorar)');
                            }
                        });
                        gridItem.removeClass('whitelisted');
                    }
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                button.prop('disabled', false).text('Error');
            }
        });
    });

    // 4. Seleccionar Todo
    $('#select-all-list').click(function() {
        var checked = this.checked;
        $('input[name="images_to_delete[]"]').prop('checked', checked);
        $('.maw-grid-item').toggleClass('selected', checked);
    });
});
</script>
