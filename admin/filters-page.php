<?php if (!defined('ABSPATH')) exit;

// Inicializar componentes
$scanner = new MAW_Scanner();
$whitelist = Image_Cleaner_Pro::get_instance()->get_whitelist_manager();
$logger = Image_Cleaner_Pro::get_instance()->get_logger();

// --- GESTIÓN DE PARÁMETROS URL (FILTROS) ---
$paged      = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page   = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$filter_mime = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : '';
$view_mode  = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$offset     = ($paged - 1) * $per_page;

// --- LÓGICA DE BORRADO (Mover a Papelera) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_delete'])) {
    check_admin_referer('maw_clean_action', 'maw_nonce');
    $ids = isset($_POST['media_ids']) ? array_map('intval', $_POST['media_ids']) : [];
    $deleted_count = 0;

    foreach ($ids as $id) {
        if (!$whitelist->is_whitelisted($id)) {
            if (wp_delete_attachment($id, false)) { // FALSE = ENVIAR A PAPELERA
                $deleted_count++;
                $logger->log('deleted', basename(get_attached_file($id)), 'Manual Scan');
            }
        }
    }
    if ($deleted_count) echo '<div class="notice notice-success is-dismissible"><p>' . $deleted_count . ' imágenes movidas a la papelera.</p></div>';
}

// --- QUERY PRINCIPAL CON FILTROS ---
global $wpdb;
$sql_where = "WHERE post_type = 'attachment' AND post_status = 'inherit'";

if ($filter_mime) {
    $sql_where .= $wpdb->prepare(" AND post_mime_type LIKE %s", $filter_mime . '%');
}

// Obtener Total
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} $sql_where");

// Obtener Items
$sql = "SELECT ID, post_title, post_mime_type, post_date FROM {$wpdb->posts} $sql_where ORDER BY ID DESC LIMIT %d OFFSET %d";
$images = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-filter"></span> Escáner Avanzado</h1>
        </div>
        <div class="maw-badge">Mostrando <?php echo count($images); ?> de <?php echo $total_items; ?></div>
    </div>

    <form method="get" id="maw-filter-form">
        <input type="hidden" name="page" value="image-cleaner-filters">
        <input type="hidden" name="view" id="input_view_mode" value="<?php echo esc_attr($view_mode); ?>">

        <!-- TOOLBAR DE FILTROS -->
        <div class="maw-toolbar">
            <div class="maw-filters-group">
                <!-- Filtro Tipo -->
                <select name="ftype" onchange="this.form.submit()">
                    <option value="">Todos los tipos</option>
                    <option value="image/jpeg" <?php selected($filter_mime, 'image/jpeg'); ?>>JPG / JPEG</option>
                    <option value="image/png" <?php selected($filter_mime, 'image/png'); ?>>PNG</option>
                    <option value="application/pdf" <?php selected($filter_mime, 'application/pdf'); ?>>PDF Documentos</option>
                </select>

                <!-- Filtro Cantidad -->
                <select name="limit" onchange="this.form.submit()">
                    <option value="20" <?php selected($per_page, 20); ?>>20 por página</option>
                    <option value="50" <?php selected($per_page, 50); ?>>50 por página</option>
                    <option value="100" <?php selected($per_page, 100); ?>>100 por página</option>
                </select>
            </div>

            <div class="maw-filters-group">
                <!-- Switcher Vistas (CSS Corregido) -->
                <div class="maw-view-switcher">
                    <button type="button" class="button <?php echo $view_mode === 'list' ? 'active' : ''; ?>" id="maw-view-list-btn"><span class="dashicons dashicons-list-view"></span></button>
                    <button type="button" class="button <?php echo $view_mode === 'grid' ? 'active' : ''; ?>" id="maw-view-grid-btn"><span class="dashicons dashicons-grid-view"></span></button>
                </div>
            </div>
        </div>
    </form>

    <form method="post" id="maw-actions-form">
        <?php wp_nonce_field('maw_clean_action', 'maw_nonce'); ?>

        <!-- VISTA LISTA -->
        <div id="maw-list-view" style="<?php echo $view_mode === 'grid' ? 'display:none;' : ''; ?>">
            <table class="maw-table">
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="cb-select-all-1"></th>
                        <th width="80">Vista</th>
                        <th>Nombre de Archivo</th>
                        <th>Tipo / Peso</th>
                        <th>Estado de Uso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($images): foreach($images as $img): 
                        $usage = $scanner->scan_usage($img->ID);
                        $is_protected = $whitelist->is_whitelisted($img->ID);
                        $thumb = wp_get_attachment_image_src($img->ID, 'thumbnail');
                        $file_path = get_attached_file($img->ID);
                        $size = file_exists($file_path) ? size_format(filesize($file_path)) : 'N/A';
                        
                        // Clases Visuales
                        $status_class = $usage['found'] ? 'in-use' : ($is_protected ? 'protected' : 'unused');
                        $status_text = $usage['found'] ? 'EN USO' : ($is_protected ? 'PROTEGIDO' : 'SIN USO');
                    ?>
                    <tr>
                        <td><input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>"></td>
                        <td><img src="<?php echo $thumb ? esc_url($thumb[0]) : ''; ?>" class="maw-img-preview"></td>
                        <td>
                            <strong><?php echo esc_html($img->post_title); ?></strong><br>
                            <small style="color:#888">Subido: <?php echo date('Y-m-d', strtotime($img->post_date)); ?></small>
                        </td>
                        <td>
                            <span style="background:#eee; padding:2px 5px; border-radius:3px; font-size:10px;"><?php echo esc_html($img->post_mime_type); ?></span><br>
                            <strong><?php echo $size; ?></strong>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            <?php if($usage['found']): ?>
                                <div style="margin-top:5px; font-size:10px;">
                                    <a href="<?php echo $usage['locations'][0]['link']; ?>" target="_blank">Ver: <?php echo mb_strimwidth($usage['locations'][0]['title'], 0, 20, '...'); ?></a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small maw-whitelist-action" data-id="<?php echo $img->ID; ?>">
                                <?php echo $is_protected ? 'Desbloquear' : 'Bloquear'; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6">No se encontraron resultados con los filtros actuales.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- VISTA GRID -->
        <div id="maw-grid-view" class="maw-grid" style="<?php echo $view_mode !== 'grid' ? 'display:none;' : ''; ?>">
            <?php if($images): foreach($images as $img): 
                $usage = $scanner->scan_usage($img->ID);
                $is_protected = $whitelist->is_whitelisted($img->ID);
                $thumb = wp_get_attachment_image_src($img->ID, 'medium');
                $status_class = $usage['found'] ? 'in-use' : ($is_protected ? 'protected' : 'unused');
            ?>
            <div class="maw-grid-item">
                <div class="maw-grid-thumb">
                    <input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>" class="maw-checkbox-overlay">
                    <img src="<?php echo $thumb ? esc_url($thumb[0]) : ''; ?>">
                </div>
                <div class="maw-grid-meta">
                    <span class="maw-grid-title" title="<?php echo esc_attr($img->post_title); ?>"><?php echo esc_html($img->post_title); ?></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:5px;">
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $usage['found'] ? 'USO' : 'LIBRE'; ?></span>
                        <button type="button" class="button button-small maw-whitelist-action" data-id="<?php echo $img->ID; ?>">
                            <span class="dashicons dashicons-lock"></span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Acciones Bulk y Paginación -->
        <div class="maw-toolbar" style="margin-top: 20px;">
            <div>
                <button type="submit" name="do_delete" class="button button-primary">Mover Seleccionados a Papelera</button>
            </div>
            <div class="tablenav-pages">
                <?php 
                $total_pages = ceil($total_items / $per_page);
                $range = 2;
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == 1 || $i == $total_pages || ($i >= $paged - $range && $i <= $paged + $range)) {
                        $current = ($paged == $i) ? 'button-primary' : 'button-secondary';
                        echo '<a href="'.add_query_arg(['paged'=>$i]).'" class="button '.$current.'">'.$i.'</a> ';
                    } elseif ($i == $paged - $range - 1 || $i == $paged + $range + 1) {
                        echo '... ';
                    }
                }
                ?>
            </div>
        </div>
    </form>
</div>
