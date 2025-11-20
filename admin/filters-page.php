<?php if (!defined('ABSPATH')) exit;

$scanner = new MAW_Scanner();
$whitelist = Image_Cleaner_Pro::get_instance()->get_whitelist_manager();
$logger = Image_Cleaner_Pro::get_instance()->get_logger();

$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;
$view = isset($_POST['active_view']) ? $_POST['active_view'] : 'list';

// Lógica Borrado
if (isset($_POST['do_delete']) && check_admin_referer('maw_act', 'maw_nonce')) {
    $ids = isset($_POST['media_ids']) ? $_POST['media_ids'] : [];
    $count = 0;
    foreach($ids as $id) {
        if(!$whitelist->is_whitelisted($id)) {
            if(wp_delete_attachment($id, false)) { // false = Trash
                $count++;
                $logger->log('deleted', basename(get_attached_file($id)), 'Manual');
            }
        }
    }
    if($count) echo '<div class="notice notice-success is-dismissible"><p>'.$count.' enviados a papelera.</p></div>';
}

global $wpdb;
$sql = "SELECT ID, post_title, post_mime_type FROM $wpdb->posts WHERE post_type='attachment' AND post_status='inherit' ORDER BY ID DESC LIMIT %d OFFSET %d";
$images = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
$total = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='attachment' AND post_status='inherit'");
?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title"><h1><span class="dashicons dashicons-filter"></span> Escáner</h1></div>
    </div>

    <form method="post">
        <?php wp_nonce_field('maw_act', 'maw_nonce'); ?>
        
        <div class="maw-toolbar">
            <div class="maw-view-switcher">
                <button type="button" class="button <?php echo $view=='list'?'active':''; ?>" id="maw-view-list-btn"><span class="dashicons dashicons-list-view"></span></button>
                <button type="button" class="button <?php echo $view=='grid'?'active':''; ?>" id="maw-view-grid-btn"><span class="dashicons dashicons-grid-view"></span></button>
                <input type="hidden" name="active_view" id="active_view_input" value="<?php echo $view; ?>">
            </div>
            <div>
                <button type="submit" name="do_delete" class="button button-primary">Mover a Papelera</button>
            </div>
        </div>

        <!-- Vista Lista -->
        <div id="maw-list-view" style="<?php echo $view=='grid'?'display:none':''; ?>">
            <table class="maw-table-view">
                <thead><tr><th width="30"><input type="checkbox" id="cb-select-all-1"></th><th>Imagen</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach($images as $img): 
                        $u = $scanner->scan_usage($img->ID);
                        $p = $whitelist->is_whitelisted($img->ID);
                        $thumb = wp_get_attachment_image_src($img->ID, 'thumbnail');
                        $cls = $u['found']?'in-use':($p?'protected':'unused');
                        $lbl = $u['found']?'En Uso':($p?'Protegido':'Sin Uso');
                    ?>
                    <tr>
                        <td><input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>"></td>
                        <td><img src="<?php echo $thumb?$thumb[0]:''; ?>" class="maw-table-img"> <?php echo esc_html($img->post_title); ?></td>
                        <td><span class="status-badge <?php echo $cls; ?>"><?php echo $lbl; ?></span></td>
                        <td>
                            <button type="button" class="button button-small maw-whitelist-action" data-id="<?php echo $img->ID; ?>">
                                <?php echo $p?'Desbloquear':'Bloquear'; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Vista Grid -->
        <div id="maw-grid-view" class="maw-grid-view" style="<?php echo $view!='grid'?'display:none':''; ?>">
            <?php foreach($images as $img): 
                $u = $scanner->scan_usage($img->ID);
                $p = $whitelist->is_whitelisted($img->ID);
                $thumb = wp_get_attachment_image_src($img->ID, 'medium');
                $cls = $u['found']?'in-use':($p?'protected':'unused');
            ?>
            <div class="maw-grid-item">
                <div class="maw-thumb">
                    <input type="checkbox" name="media_ids[]" value="<?php echo $img->ID; ?>" class="maw-checkbox-overlay">
                    <img src="<?php echo $thumb?$thumb[0]:''; ?>">
                </div>
                <div class="maw-grid-details">
                    <span class="maw-filename"><?php echo esc_html($img->post_title); ?></span>
                    <span class="status-badge <?php echo $cls; ?>"><?php echo $u['found']?'En Uso':($p?'Safe':'Libre'); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="tablenav bottom"><div class="tablenav-pages">
            <?php for($i=1; $i<=min(10, ceil($total/$per_page)); $i++) echo '<a href="'.add_query_arg(['paged'=>$i]).'" class="button">'.$i.'</a> '; ?>
        </div></div>
    </form>
</div>
