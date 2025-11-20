<?php if (!defined('ABSPATH')) exit;

// Inicializar componentes
$scanner = new MAW_Scanner();
$whitelist = Image_Cleaner_Pro::get_instance()->get_whitelist_manager();
$logger = Image_Cleaner_Pro::get_instance()->get_logger();
global $wpdb;

// --- PARAMETROS URL ---
$paged      = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page   = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$filter_mime = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : '';
$filter_source = isset($_GET['fsource']) ? sanitize_text_field($_GET['fsource']) : ''; // Nuevo filtro
$view_mode  = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$offset     = ($paged - 1) * $per_page;

// --- LÓGICA DE BORRADO SEGURO (FORZAR PAPELERA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_delete'])) {
    check_admin_referer('maw_clean_action', 'maw_nonce');
    $ids = isset($_POST['media_ids']) ? array_map('intval', $_POST['media_ids']) : [];
    $deleted_count = 0;

    foreach ($ids as $id) {
        if (!$whitelist->is_whitelisted($id)) {
            // TRUCO: No usamos wp_delete_attachment porque si MEDIA_TRASH es false, lo borra para siempre.
            // Forzamos el estado 'trash' en la base de datos manualmente.
            $updated = $wpdb->update(
                $wpdb->posts,
                ['post_status' => 'trash'],
                ['ID' => $id],
                ['%s'],
                ['%d']
            );

            if ($updated !== false) {
                // También disparamos el hook para que plugins de caché sepan que cambió
                clean_post_cache($id);
                $deleted_count++;
                $logger->log('deleted', basename(get_attached_file($id)), 'Enviado a Papelera (Forzado)');
            }
        }
    }
    if ($deleted_count) echo '<div class="notice notice-success is-dismissible"><p>' . $deleted_count . ' imágenes movidas a la papelera de recuperación.</p></div>';
}

// --- QUERY PRINCIPAL ---
// Construimos la query SQL manualmente para poder filtrar por "Uso" si es necesario
// Nota: Filtrar por "Uso WooCommerce" en SQL puro es muy complejo (JOINs masivos). 
// Para mantener rendimiento, obtenemos los resultados y filtramos en PHP si es una página específica, 
// o hacemos un filtrado básico.

$sql_base = "SELECT ID, post_title, post_mime_type, post_date FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit'";

if ($filter_mime) {
    $sql_base .= $wpdb->prepare(" AND post_mime_type LIKE %s", $filter_mime . '%');
}

// Total y Paginación
$total_items = $wpdb->get_var(str_replace('SELECT ID, post_title, post_mime_type, post_date', 'SELECT COUNT(*)', $sql_base));
$sql_final = $sql_base . $wpdb->prepare(" ORDER BY ID DESC LIMIT %d OFFSET %d", $per_page, $offset);
$images_raw = $wpdb->get_results($sql_final);

?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-filter"></span> Escáner Inteligente</h1>
        </div>
        <div class="maw-badge">Total Biblioteca: <?php echo $total_items; ?></div>
    </div>

    <form method="get" id="maw-filter-form">
        <input type="hidden" name="page" value="image-cleaner-filters">
        <input type="hidden" name="view" id="input_view_mode" value="<?php echo esc_attr($view_mode); ?>">

        <div class="maw-toolbar">
            <div class="maw-filters-group">
                
                <!-- Filtro 1: Tipo Archivo -->
                <select name="ftype" onchange="this.form.submit()">
                    <option value="">Todos los Tipos</option>
                    <option value="image/jpeg" <?php selected($filter_mime, 'image/jpeg'); ?>>JPG / JPEG</option>
                    <option value="image/png" <?php selected($filter_mime, 'image/png'); ?>>PNG</option>
                    <option value="application/pdf" <?php selected($filter_mime, 'application/pdf'); ?>>PDF</option>
                </select>

                <!-- Filtro 2: Origen de Uso (Visual) -->
                <!-- Nota: Este filtro visual solo afecta al renderizado actual por limitaciones SQL de WP -->
                <select name="fsource" onchange="this.form.submit()" style="border-color:var(--maw-primary);">
                    <option value="">Mostrar Todo</option>
                    <option value="woo" <?php selected($filter_source, 'woo'); ?>>Solo WooCommerce</option>
                    <option value="unused" <?php selected($filter_source, 'unused'); ?>>Solo Huérfanos (Borrar)</option>
                </select>

                <!-- Filtro 3: Límite -->
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
                    <?php 
                    $display_count = 0;
                    if($images_raw): foreach($images_raw as $img): 
                        $usage = $scanner->scan_usage($img->ID);
                        
                        // FILTRADO PHP (Post-Query)
                        if ($filter_source === 'woo' && $usage['source'] !== 'woo') continue;
                        if ($filter_source === 'unused' && $usage['found'] === true) continue;
                        
                        $display_count++;
                        $is_protected = $whitelist->is_whitelisted($img->ID);
                        $thumb = wp_get_attachment_image_src($img->ID, 'thumbnail');
                        $file_path = get_attached_file($img->ID);
                        $size = file_exists($file_path) ? size_format(filesize($file_path)) : '-';

                        // BADGES VISUALES
                        $badge_html = '<span class="status-badge unused">SIN USO</span>';
                        if ($is_protected) {
                            $badge_html = '<span class="status-badge protected">PROTEGIDO</span>';
                        } elseif ($usage['found']) {
                            if ($usage['source'] === 'woo') {
                                $badge_html = '<span class="status-badge woo" style="background:#7f54b3; color:white; border:none;">WOOCOMMERCE</span>';
                            } elseif ($usage['source'] === 'elementor') {
                                $badge_html = '<span class="status-badge" style="background:#ce264e; color:white; border:none;">ELEMENTOR</span>';
                            } else {
                                $badge_html = '<span class="status-badge in-use">EN USO</span>';
                            }
                        }
                    ?>
                    <tr>
                        <td><input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>"></td>
                        <td><img src="<?php echo $thumb ? esc_url($thumb[0]) : ''; ?>" class="maw-img-preview"></td>
                        <td>
                            <strong><?php echo esc_html($img->post_title); ?></strong><br>
                            <small style="color:#888"><?php echo esc_html(basename($file_path)); ?></small>
                        </td>
                        <td><?php echo $size; ?></td>
                        <td>
                            <?php echo $badge_html; ?>
                            <?php if($usage['found']): ?>
                                <div style="margin-top:4px; font-size:11px;">
                                    <a href="<?php echo $usage['locations'][0]['link']; ?>" target="_blank">
                                        <span class="dashicons dashicons-external" style="font-size:12px;"></span> 
                                        <?php echo mb_strimwidth($usage['locations'][0]['title'], 0, 25, '...'); ?>
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
                    <?php endforeach; endif; ?>
                    
                    <?php if ($display_count === 0) : ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">No hay resultados para el filtro seleccionado en esta página.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- VISTA GRID (Simplificada para no repetir código excesivo, usa la misma lógica) -->
        <div id="maw-grid-view" class="maw-grid" style="<?php echo $view_mode !== 'grid' ? 'display:none;' : ''; ?>">
            <?php if($images_raw): foreach($images_raw as $img): 
                 $usage = $scanner->scan_usage($img->ID);
                 if ($filter_source === 'woo' && $usage['source'] !== 'woo') continue;
                 if ($filter_source === 'unused' && $usage['found'] === true) continue;
                 
                 $is_protected = $whitelist->is_whitelisted($img->ID);
                 $thumb = wp_get_attachment_image_src($img->ID, 'medium');
                 
                 $cls = $usage['found'] ? 'in-use' : ($is_protected ? 'protected' : 'unused');
                 $lbl = $usage['found'] ? 'USO' : 'LIBRE';
                 if ($usage['source'] === 'woo') { $cls = 'woo'; $lbl = 'WOO'; }
            ?>
            <div class="maw-grid-item <?php echo $cls; ?>">
                <div class="maw-grid-thumb">
                    <input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>" class="maw-checkbox-overlay">
                    <img src="<?php echo $thumb ? esc_url($thumb[0]) : ''; ?>">
                    <?php if($usage['source'] === 'woo'): ?>
                        <span style="position:absolute; bottom:0; right:0; background:#7f54b3; color:white; padding:2px 5px; font-size:10px;">WOO</span>
                    <?php endif; ?>
                </div>
                <div class="maw-grid-meta">
                    <span class="maw-grid-title"><?php echo esc_html($img->post_title); ?></span>
                    <div style="display:flex; justify-content:space-between; margin-top:5px;">
                        <span class="status-badge <?php echo $cls; ?>" style="<?php echo $usage['source'] === 'woo' ? 'background:#7f54b3;color:white;' : ''; ?>"><?php echo $lbl; ?></span>
                        <button type="button" class="button button-small maw-whitelist-action" data-id="<?php echo $img->ID; ?>"><span class="dashicons dashicons-lock"></span></button>
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
                for($i=1; $i<=min(5, $total_pages); $i++) {
                    $c = ($paged==$i)?'button-primary':'';
                    echo '<a href="'.add_query_arg(['paged'=>$i]).'" class="button '.$c.'">'.$i.'</a> ';
                }
                ?>
            </div>
        </div>
    </form>
</div>
