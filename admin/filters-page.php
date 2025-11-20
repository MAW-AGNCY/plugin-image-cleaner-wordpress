<?php
if (!defined('ABSPATH')) exit;

// Instancias
$scanner = new MAW_Scanner();
$whitelist = Image_Cleaner_Pro::get_instance()->get_whitelist_manager();
$logger = Image_Cleaner_Pro::get_instance()->get_logger();

// Paginación y Filtros
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;
$filter_type = isset($_GET['ftype']) ? sanitize_text_field($_GET['ftype']) : 'all';

// Lógica de Borrado (Bugfix: Aseguramos que va a Trash)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_delete'])) {
    check_admin_referer('maw_clean_action', 'maw_nonce');
    $ids = isset($_POST['media_ids']) ? array_map('intval', $_POST['media_ids']) : [];
    $deleted = 0;

    foreach ($ids as $id) {
        if (!$whitelist->is_whitelisted($id)) {
            // force_delete = false envía a papelera
            if (wp_delete_attachment($id, false)) {
                $deleted++;
                $logger->log('deleted', basename(get_attached_file($id)), 'Desde Escáner Manual');
            }
        }
    }
    if ($deleted) echo '<div class="notice notice-success is-dismissible"><p>' . $deleted . ' imágenes movidas a la papelera.</p></div>';
}

// Consulta Principal
global $wpdb;
$sql = "SELECT ID, post_title, post_mime_type, post_date FROM {$wpdb->posts} WHERE post_type='attachment' AND post_status='inherit'";
if ($filter_type !== 'all') $sql .= $wpdb->prepare(" AND post_mime_type LIKE %s", $filter_type . '%');

// Total y Paginado
$total = $wpdb->get_var(str_replace('SELECT ID, post_title, post_mime_type, post_date', 'SELECT COUNT(*)', $sql));
$sql .= $wpdb->prepare(" ORDER BY ID DESC LIMIT %d OFFSET %d", $per_page, $offset);
$images = $wpdb->get_results($sql);
?>

<div class="maw-wrap">
    
    <!-- Header Premium -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-filter"></span> Escáner Inteligente <span class="maw-badge">PRO</span></h1>
        </div>
        <div>
            <a href="admin.php?page=image-cleaner-help" class="button">? Ayuda</a>
        </div>
    </div>

    <!-- Barra de Herramientas -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display:inline-flex; gap:10px;">
                <input type="hidden" name="page" value="image-cleaner-filters">
                <select name="ftype">
                    <option value="all" <?php selected($filter_type, 'all'); ?>>Todos los tipos</option>
                    <option value="image/jpeg" <?php selected($filter_type, 'image/jpeg'); ?>>JPEG / JPG</option>
                    <option value="image/png" <?php selected($filter_type, 'image/png'); ?>>PNG</option>
                </select>
                <button class="button">Filtrar</button>
            </form>
        </div>
        <div class="alignright">
            <span class="displaying-num"><?php echo $total; ?> elementos</span>
        </div>
    </div>

    <form method="post" id="maw-media-form">
        <?php wp_nonce_field('maw_clean_action', 'maw_nonce'); ?>
        
        <div class="maw-grid">
            <?php if ($images) : foreach ($images as $img) : 
                // ANÁLISIS EN TIEMPO REAL (Deep Scan)
                $usage = $scanner->scan_usage($img->ID);
                $is_whitelisted = $whitelist->is_whitelisted($img->ID);
                $thumb = wp_get_attachment_image_src($img->ID, 'medium');
                $src = $thumb ? $thumb[0] : '';
                
                // Determinar Estado Visual
                $status_class = $usage['found'] ? 'in-use' : ($is_whitelisted ? 'protected' : 'unused');
                $status_text = $usage['found'] ? 'EN USO' : ($is_whitelisted ? 'PROTEGIDO' : 'SIN USO');
                $status_icon = $usage['found'] ? 'dashicons-yes' : 'dashicons-warning';
            ?>
            <div class="maw-card-img <?php echo $is_whitelisted ? 'whitelisted' : ''; ?>" onclick="toggleSelect(this)">
                <div class="maw-thumb-wrapper">
                    <?php if ($src) : ?>
                        <img src="<?php echo esc_url($src); ?>" loading="lazy">
                    <?php else : ?>
                        <span class="dashicons dashicons-media-default" style="font-size:40px;color:#ccc;"></span>
                    <?php endif; ?>
                    <input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>" class="maw-checkbox-overlay" onclick="event.stopPropagation()">
                </div>
                
                <div class="maw-card-body">
                    <div>
                        <div class="maw-filename" title="<?php echo esc_attr($img->post_title); ?>">
                            <?php echo esc_html($img->post_title ?: 'Sin nombre'); ?>
                        </div>
                        <div class="maw-status <?php echo $status_class; ?>">
                            <span class="dashicons <?php echo $status_icon; ?>"></span> <?php echo $status_text; ?>
                        </div>
                        
                        <?php if ($usage['found']) : ?>
                            <ul class="maw-usage-list">
                                <?php foreach (array_slice($usage['locations'], 0, 2) as $loc) : ?>
                                    <li class="maw-usage-item">
                                        <span class="dashicons dashicons-admin-links"></span>
                                        <a href="<?php echo $loc['link']; ?>" target="_blank" title="<?php echo $loc['type']; ?>">
                                            <?php echo mb_strimwidth($loc['title'], 0, 15, '...'); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <?php if(count($usage['locations']) > 2): ?><li>...y más</li><?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="maw-flex-row" style="margin-top:10px; justify-content:space-between;">
                        <small><?php echo size_format(filesize(get_attached_file($img->ID))); ?></small>
                        
                        <button type="button" class="button button-small maw-whitelist-trigger" data-id="<?php echo $img->ID; ?>" onclick="event.stopPropagation(); toggleWhitelist(this)">
                            <?php echo $is_whitelisted ? 'Desbloquear' : 'Bloquear'; ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <p>No se encontraron imágenes.</p>
            <?php endif; ?>
        </div>

        <div class="maw-flex-row" style="margin-top:20px; padding:20px; background:#fff; border:1px solid #ccc;">
            <button type="submit" name="do_delete" class="button button-primary button-large">Mover Seleccionados a Papelera</button>
            <p class="description">Los elementos protegidos (Bloqueados) serán ignorados aunque los selecciones.</p>
        </div>

        <!-- Paginación -->
        <div class="maw-pagination">
            <?php 
            $total_pages = ceil($total / $per_page);
            for ($i = 1; $i <= min(10, $total_pages); $i++) : 
                $cls = ($paged == $i) ? 'current' : '';
            ?>
                <a href="<?php echo add_query_arg('paged', $i); ?>" class="maw-page-link <?php echo $cls; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </form>
</div>

<script>
function toggleSelect(el) {
    var cb = el.querySelector('input[type="checkbox"]');
    cb.checked = !cb.checked;
    el.classList.toggle('selected', cb.checked);
}

function toggleWhitelist(btn) {
    var id = btn.getAttribute('data-id');
    btn.textContent = '...';
    jQuery.post(ajaxurl, {
        action: 'maw_toggle_whitelist', 
        image_id: id,
        // Nota: Idealmente pasar nonce vía wp_localize_script
    }, function(res) {
        if(res.success) {
            location.reload();
        }
    });
}
</script>
