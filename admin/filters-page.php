<?php if (!defined('ABSPATH')) exit;

$scanner = new MAW_Scanner();
$whitelist = Image_Cleaner_Pro::get_instance()->get_whitelist_manager();
$logger = Image_Cleaner_Pro::get_instance()->get_logger();
global $wpdb;

$paged      = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page   = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$filter_mime = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : '';
$filter_source = isset($_GET['fsource']) ? sanitize_text_field($_GET['fsource']) : '';
$view_mode  = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$offset     = ($paged - 1) * $per_page;

// Borrado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_delete'])) {
    check_admin_referer('maw_clean_action', 'maw_nonce');
    $ids = isset($_POST['media_ids']) ? array_map('intval', $_POST['media_ids']) : [];
    $deleted_count = 0;
    foreach ($ids as $id) {
        if (!$whitelist->is_whitelisted($id)) {
            if ($wpdb->update($wpdb->posts, ['post_status' => 'trash'], ['ID' => $id])) {
                clean_post_cache($id);
                $deleted_count++;
                $logger->log('deleted', basename(get_attached_file($id)), 'Manual Scan');
            }
        }
    }
    if ($deleted_count) echo '<div class="notice notice-success is-dismissible"><p>' . $deleted_count . ' archivos movidos a la papelera.</p></div>';
}

// Query
$sql_base = "SELECT ID, post_title, post_mime_type, post_date FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit'";

if ($filter_mime) {
    if ($filter_mime == 'video') {
        $sql_base .= " AND post_mime_type LIKE 'video/%'";
    } else {
        $sql_base .= $wpdb->prepare(" AND post_mime_type LIKE %s", $filter_mime . '%');
    }
}

$total_items = $wpdb->get_var(str_replace('SELECT ID, post_title, post_mime_type, post_date', 'SELECT COUNT(*)', $sql_base));
$images_raw = $wpdb->get_results($sql_base . $wpdb->prepare(" ORDER BY ID DESC LIMIT %d OFFSET %d", $per_page, $offset));
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title"><h1><span class="dashicons dashicons-filter"></span> Escáner Inteligente</h1></div>
        <div class="maw-badge">Total: <?php echo $total_items; ?></div>
    </div>

    <form method="get" id="maw-filter-form">
        <input type="hidden" name="page" value="image-cleaner-filters">
        <input type="hidden" name="view" id="input_view_mode" value="<?php echo esc_attr($view_mode); ?>">

        <div class="maw-toolbar">
            <div class="maw-filters-group">
                <!-- Filtro Tipo: AÑADIDO VIDEO -->
                <select name="ftype" onchange="this.form.submit()">
                    <option value="">Todos los Tipos</option>
                    <option value="image/jpeg" <?php selected($filter_mime, 'image/jpeg'); ?>>Imágenes (JPG)</option>
                    <option value="image/png" <?php selected($filter_mime, 'image/png'); ?>>Imágenes (PNG)</option>
                    <option value="application/pdf" <?php selected($filter_mime, 'application/pdf'); ?>>Documentos (PDF)</option>
                    <option value="video" <?php selected($filter_mime, 'video'); ?>>Vídeos (MP4, MOV...)</option>
                </select>

                <select name="fsource" onchange="this.form.submit()" style="border-color:var(--maw-primary);">
                    <option value="">Cualquier Uso</option>
                    <option value="woo" <?php selected($filter_source, 'woo'); ?>>WooCommerce</option>
                    <option value="unused" <?php selected($filter_source, 'unused'); ?>>Solo Huérfanos</option>
                </select>

                <select name="limit" onchange="this.form.submit()">
                    <option value="20" <?php selected($per_page, 20); ?>>20 items</option>
                    <option value="50" <?php selected($per_page, 50); ?>>50 items</option>
                    <option value="100" <?php selected($per_page, 100); ?>>100 items</option>
                </select>
            </div>

            <div class="maw-filters-group">
                <div class="maw-view-switcher">
                    <button type="button" class="button <?php echo $view_mode === 'list' ? 'active' : ''; ?>" id="maw-view-list-btn"><span class="dashicons dashicons-list-view"></span></button>
                    <button type="button" class="button <?php echo $view_mode === 'grid' ? 'active' : ''; ?>" id="maw-view-grid-btn"><span class="dashicons dashicons-grid-view"></span></button>
                </div>
            </div>
        </div>
    </form>

    <form method="post">
        <?php wp_nonce_field('maw_clean_action', 'maw_nonce'); ?>
        
        <!-- VISTA LISTA -->
        <div id="maw-list-view" style="<?php echo $view_mode === 'grid' ? 'display:none;' : ''; ?>">
            <table class="maw-table">
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="cb-select-all-1"></th>
                        <th width="60">Vista</th>
                        <th>Archivo</th>
                        <th>Peso</th>
                        <th>Uso Detectado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($images_raw): foreach($images_raw as $img): 
                        $usage = $scanner->scan_usage($img->ID);
                        if ($filter_source === 'woo' && $usage['source'] !== 'woo') continue;
                        if ($filter_source === 'unused' && $usage['found'] === true) continue;
                        
                        $is_protected = $whitelist->is_whitelisted($img->ID);
                        $thumb = wp_get_attachment_image_src($img->ID, 'thumbnail');
                        $file_path = get_attached_file($img->ID);
                        $size = file_exists($file_path) ? size_format(filesize($file_path)) : '-';
                        
                        // Icono para Video
                        $is_video = strpos($img->post_mime_type, 'video') !== false;
                        $preview = $thumb ? '<img src="'.esc_url($thumb[0]).'" class="maw-img-preview">' : ($is_video ? '<div class="maw-img-preview" style="display:flex;justify-content:center;align-items:center;"><span class="dashicons dashicons-video-alt3"></span></div>' : '<div class="maw-img-preview"></div>');

                        $badge_html = '<span class="status-badge unused">SIN USO</span>';
                        if ($is_protected) $badge_html = '<span class="status-badge protected">PROTEGIDO</span>';
                        elseif ($usage['found']) $badge_html = '<span class="status-badge in-use">EN USO</span>';
                    ?>
                    <tr>
                        <td><input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>"></td>
                        <td><?php echo $preview; ?></td>
                        <td>
                            <strong><?php echo esc_html($img->post_title); ?></strong><br>
                            <small style="color:#888"><?php echo esc_html(basename($file_path)); ?></small>
                        </td>
                        <td><?php echo $size; ?></td>
                        <td>
                            <?php echo $badge_html; ?>
                            <?php if($usage['found']): ?><br><small><a href="<?php echo $usage['locations'][0]['link']; ?>" target="_blank">Ver</a></small><?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small maw-whitelist-action" data-id="<?php echo $img->ID; ?>"><?php echo $is_protected ? 'Desbloquear' : 'Bloquear'; ?></button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" style="padding:20px; text-align:center;">No hay resultados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- VISTA GRID (Soporte Video) -->
        <div id="maw-grid-view" class="maw-grid" style="<?php echo $view_mode !== 'grid' ? 'display:none;' : ''; ?>">
            <?php if($images_raw): foreach($images_raw as $img): 
                 // ... misma lógica de filtrado ...
                 $usage = $scanner->scan_usage($img->ID);
                 if ($filter_source === 'woo' && $usage['source'] !== 'woo') continue;
                 if ($filter_source === 'unused' && $usage['found'] === true) continue;
                 
                 $is_protected = $whitelist->is_whitelisted($img->ID);
                 $thumb = wp_get_attachment_image_src($img->ID, 'medium');
                 $is_video = strpos($img->post_mime_type, 'video') !== false;
                 $cls = $usage['found'] ? 'in-use' : ($is_protected ? 'protected' : 'unused');
            ?>
            <div class="maw-grid-item <?php echo $cls; ?>">
                <div class="maw-grid-thumb">
                    <input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>" class="maw-checkbox-overlay">
                    <?php if($thumb): ?>
                        <img src="<?php echo esc_url($thumb[0]); ?>">
                    <?php elseif($is_video): ?>
                        <span class="dashicons dashicons-video-alt3" style="font-size:40px; color:#ccc;"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-media-default" style="font-size:40px; color:#ccc;"></span>
                    <?php endif; ?>
                </div>
                <div class="maw-grid-meta">
                    <span class="maw-grid-title"><?php echo esc_html($img->post_title); ?></span>
                    <span class="status-badge <?php echo $cls; ?>"><?php echo $usage['found'] ? 'EN USO' : 'LIBRE'; ?></span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        
        <div class="maw-toolbar" style="margin-top:20px;">
            <button type="submit" name="do_delete" class="button button-primary">Mover a Papelera</button>
            <div class="tablenav-pages">
                <?php for($i=1; $i<=min(5, ceil($total_items/$per_page)); $i++) echo '<a href="'.add_query_arg(['paged'=>$i]).'" class="button">'.$i.'</a> '; ?>
            </div>
        </div>
    </form>
</div>
