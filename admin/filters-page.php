<?php if (!defined('ABSPATH')) exit;

// Inicializar componentes
$scanner = new MAW_Scanner();
$whitelist = Image_Cleaner_Pro::get_instance()->get_whitelist_manager();
$logger = Image_Cleaner_Pro::get_instance()->get_logger();
global $wpdb;

// --- PARAMETROS URL ---
$paged      = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page   = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$view_mode  = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$offset     = ($paged - 1) * $per_page;

// Filtros
$filter_mime = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : '';
$filter_source = isset($_GET['fsource']) ? sanitize_text_field($_GET['fsource']) : '';
$date_start = isset($_GET['dstart']) ? sanitize_text_field($_GET['dstart']) : '';
$date_end   = isset($_GET['dend']) ? sanitize_text_field($_GET['dend']) : '';

// --- LÓGICA DE BORRADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_delete'])) {
    check_admin_referer('maw_clean_action', 'maw_nonce');
    $ids = isset($_POST['media_ids']) ? array_map('intval', $_POST['media_ids']) : [];
    $deleted_count = 0;

    foreach ($ids as $id) {
        if (!$whitelist->is_whitelisted($id)) {
            // Usamos update directo para forzar papelera
            if ($wpdb->update($wpdb->posts, ['post_status' => 'trash'], ['ID' => $id])) {
                clean_post_cache($id);
                $deleted_count++;
                $logger->log('deleted', basename(get_attached_file($id)), 'Manual Scan');
            }
        }
    }

    if ($deleted_count) {
        // 🚀 CRÍTICO: Borrar caché del Dashboard para que se actualice al instante
        delete_transient('maw_dashboard_stats_v25');
        
        echo '<div class="notice notice-success is-dismissible"><p>' . $deleted_count . ' archivos movidos a la papelera.</p></div>';
    }
}

// --- QUERY ---
$sql_base = "SELECT ID, post_title, post_mime_type, post_date FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit'";

// Filtros SQL
if ($filter_mime) {
    if ($filter_mime == 'video') $sql_base .= " AND post_mime_type LIKE 'video/%'";
    else $sql_base .= $wpdb->prepare(" AND post_mime_type LIKE %s", $filter_mime . '%');
}
if ($date_start) {
    $sql_base .= $wpdb->prepare(" AND post_date >= %s", $date_start . ' 00:00:00');
}
if ($date_end) {
    $sql_base .= $wpdb->prepare(" AND post_date <= %s", $date_end . ' 23:59:59');
}

$total_items = $wpdb->get_var(str_replace('SELECT ID, post_title, post_mime_type, post_date', 'SELECT COUNT(*)', $sql_base));
$images_raw = $wpdb->get_results($sql_base . $wpdb->prepare(" ORDER BY ID DESC LIMIT %d OFFSET %d", $per_page, $offset));
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-filter"></span> Escáner Inteligente</h1>
        </div>
        <div class="maw-badge">Resultados: <?php echo $total_items; ?></div>
    </div>

    <form method="get" id="maw-filter-form">
        <input type="hidden" name="page" value="image-cleaner-filters">
        <input type="hidden" name="view" id="input_view_mode" value="<?php echo esc_attr($view_mode); ?>">

        <div class="maw-toolbar">
            <!-- GRUPO 1: Filtros Principales -->
            <div class="maw-filters-group">
                <select name="ftype" onchange="this.form.submit()">
                    <option value="">Todos los Tipos</option>
                    <option value="image/jpeg" <?php selected($filter_mime, 'image/jpeg'); ?>>JPG / JPEG</option>
                    <option value="image/png" <?php selected($filter_mime, 'image/png'); ?>>PNG</option>
                    <option value="application/pdf" <?php selected($filter_mime, 'application/pdf'); ?>>PDF</option>
                    <option value="video" <?php selected($filter_mime, 'video'); ?>>Vídeos</option>
                </select>

                <select name="fsource" onchange="this.form.submit()" style="border-color:var(--maw-primary);">
                    <option value="">Todos los Estados</option>
                    <option value="woo" <?php selected($filter_source, 'woo'); ?>>WooCommerce</option>
                    <option value="unused" <?php selected($filter_source, 'unused'); ?>>Solo Huérfanos</option>
                </select>
            </div>
            
            <!-- GRUPO 2: Fechas Branding -->
            <div class="maw-filters-group">
                <input type="date" name="dstart" value="<?php echo esc_attr($date_start); ?>" class="maw-date-input" title="Fecha Inicio">
                <span style="color:#ccc">-</span>
                <input type="date" name="dend" value="<?php echo esc_attr($date_end); ?>" class="maw-date-input" title="Fecha Fin">
                
                <button type="submit" class="button button-secondary">Filtrar</button>
                
                <!-- BOTÓN RESET NUEVO -->
                <a href="admin.php?page=image-cleaner-filters" class="button" title="Limpiar Filtros / Recargar">
                    <span class="dashicons dashicons-image-rotate" style="margin-top:4px;"></span>
                </a>
            </div>

            <!-- GRUPO 3: Vistas -->
            <div class="maw-filters-group">
                <select name="limit" onchange="this.form.submit()">
                    <option value="20" <?php selected($per_page, 20); ?>>20</option>
                    <option value="50" <?php selected($per_page, 50); ?>>50</option>
                    <option value="100" <?php selected($per_page, 100); ?>>100</option>
                </select>
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
                        <th>Subido el</th>
                        <th>Estado</th>
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
                        
                        $is_video = strpos($img->post_mime_type, 'video') !== false;
                        $preview = $thumb ? '<img src="'.esc_url($thumb[0]).'" class="maw-img-preview">' : ($is_video ? '<div class="maw-img-preview" style="display:flex;justify-content:center;align-items:center;"><span class="dashicons dashicons-video-alt3"></span></div>' : '<div class="maw-img-preview"></div>');

                        $badge_html = '<span class="status-badge unused">SIN USO</span>';
                        if ($is_protected) $badge_html = '<span class="status-badge protected">PROTEGIDO</span>';
                        elseif ($usage['found']) {
                             $badge_html = ($usage['source'] === 'woo') ? '<span class="status-badge woo">WOOCOMMERCE</span>' : '<span class="status-badge in-use">EN USO</span>';
                        }
                    ?>
                    <tr>
                        <td><input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>"></td>
                        <td><?php echo $preview; ?></td>
                        <td>
                            <strong><?php echo esc_html($img->post_title); ?></strong><br>
                            <small style="color:#888"><?php echo esc_html(basename($file_path)); ?> - <strong><?php echo $size; ?></strong></small>
                        </td>
                        <td style="font-size:12px; color:#666;">
                            <?php echo date('d/m/Y', strtotime($img->post_date)); ?><br>
                            <small><?php echo human_time_diff(strtotime($img->post_date), current_time('timestamp')) . ' atrás'; ?></small>
                        </td>
                        <td>
                            <?php echo $badge_html; ?>
                            <?php if($usage['found']): ?>
                                <div style="margin-top:6px;">
                                    <a href="<?php echo $usage['locations'][0]['link']; ?>" target="_blank" class="maw-btn-view">
                                        <span class="dashicons dashicons-external"></span> Ver uso
                                    </a>
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
                        <tr><td colspan="6" style="text-align:center; padding:30px;">No se encontraron archivos con estos criterios.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- VISTA GRID (Simplificada) -->
        <div id="maw-grid-view" class="maw-grid" style="<?php echo $view_mode !== 'grid' ? 'display:none;' : ''; ?>">
            <?php if($images_raw): foreach($images_raw as $img): 
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
                    <?php if($thumb): ?><img src="<?php echo esc_url($thumb[0]); ?>"><?php elseif($is_video): ?><span class="dashicons dashicons-video-alt3" style="font-size:40px; color:#ccc;"></span><?php endif; ?>
                </div>
                <div class="maw-grid-meta">
                    <span class="maw-grid-title"><?php echo esc_html($img->post_title); ?></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:5px;">
                        <span class="status-badge <?php echo $cls; ?>"><?php echo $usage['found'] ? 'USO' : 'LIBRE'; ?></span>
                        <small><?php echo date('d/m/y', strtotime($img->post_date)); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="maw-toolbar" style="margin-top:20px;">
            <button type="submit" name="do_delete" class="button button-primary button-large">Mover a Papelera</button>
            <div class="tablenav-pages">
                <?php 
                $total_pages = ceil($total_items / $per_page);
                for($i=1; $i<=min(5, $total_pages); $i++) echo '<a href="'.add_query_arg(['paged'=>$i]).'" class="button">'.$i.'</a> ';
                ?>
            </div>
        </div>
    </form>
</div>
