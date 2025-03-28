<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $wpdb;

// Calcular el tamaño total de la biblioteca
$total_size = 0;
$attachments = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment'");
foreach ($attachments as $attachment) {
    $file_path = get_attached_file($attachment->ID);
    if (file_exists($file_path)) {
        $total_size += filesize($file_path);
    }
}
$total_size_formatted = size_format($total_size);

// Calcular el posible espacio recuperable
$unused_size = 0;
$unused_attachments = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_parent = 0");
foreach ($unused_attachments as $attachment) {
    $file_path = get_attached_file($attachment->ID);
    if (file_exists($file_path)) {
        $unused_size += filesize($file_path);
    }
}
$unused_size_formatted = size_format($unused_size);

// Calcular el número de imágenes por tipo de archivo
$image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
$image_counts = [];
foreach ($image_types as $type) {
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_mime_type = %s", $type));
    $image_counts[$type] = $count;
}

// Fecha de la última limpieza
$last_cleanup_date = get_option('image_cleaner_last_cleanup_date', 'N/A');

?>
<div class="wrap image-cleaner-admin">
    <h1><?php esc_html_e('Image Cleaner - Overview', 'image-cleaner'); ?></h1>

    <p><?php esc_html_e('Welcome to the Image Cleaner Plugin. Use the options in the menu to manage your images effectively.', 'image-cleaner'); ?></p>

    <ul>
        <li><i class="dashicons dashicons-filter"></i><a href="<?php echo admin_url('admin.php?page=image-cleaner-filters'); ?>"><?php esc_html_e('Filters: Apply custom filters to find specific images.', 'image-cleaner'); ?></a></li>
        <li><i class="dashicons dashicons-chart-bar"></i><a href="<?php echo admin_url('admin.php?page=image-cleaner-reports'); ?>"><?php esc_html_e('Reports: Review image cleaning reports.', 'image-cleaner'); ?></a></li>
        <li><i class="dashicons dashicons-backup"></i><a href="<?php echo admin_url('admin.php?page=image-cleaner-recovery'); ?>"><?php esc_html_e('Recovery: Restore deleted images.', 'image-cleaner'); ?></a></li>
        <li><i class="dashicons dashicons-email-alt"></i><a href="<?php echo admin_url('admin.php?page=image-cleaner-email'); ?>"><?php esc_html_e('Email Notifications: Configure email summaries.', 'image-cleaner'); ?></a></li>
    </ul>

    <div class="image-cleaner-stats">
        <div class="stat">
            <i class="dashicons dashicons-images-alt2"></i>
            <h2><?php echo esc_html(array_sum($image_counts)); ?></h2>
            <p><?php esc_html_e('Total Images', 'image-cleaner'); ?></p>
        </div>
        <div class="stat">
            <i class="dashicons dashicons-yes"></i>
            <h2><?php echo esc_html(count($attachments) - count($unused_attachments)); ?></h2>
            <p><?php esc_html_e('Images in Use', 'image-cleaner'); ?></p>
        </div>
        <div class="stat">
            <i class="dashicons dashicons-no"></i>
            <h2><?php echo esc_html(count($unused_attachments)); ?></h2>
            <p><?php esc_html_e('Unused Images', 'image-cleaner'); ?></p>
        </div>
        <div class="stat">
            <i class="dashicons dashicons-chart-pie"></i>
            <h2><?php echo esc_html($total_size_formatted); ?></h2>
            <p><?php esc_html_e('Total Library Size', 'image-cleaner'); ?></p>
        </div>
        <div class="stat">
            <i class="dashicons dashicons-trash"></i>
            <h2><?php echo esc_html($unused_size_formatted); ?></h2>
            <p><?php esc_html_e('Potential Recoverable Space', 'image-cleaner'); ?></p>
        </div>
        <?php foreach ($image_counts as $type => $count) : ?>
            <div class="stat">
                <i class="dashicons dashicons-format-<?php echo esc_attr(str_replace('image/', '', $type)); ?>"></i>
                <h2><?php echo esc_html($count); ?></h2>
                <p><?php echo esc_html(ucfirst(str_replace('image/', '', $type))); ?></p>
            </div>
        <?php endforeach; ?>
        <div class="stat">
            <i class="dashicons dashicons-calendar-alt"></i>
            <h2><?php echo esc_html($last_cleanup_date); ?></h2>
            <p><?php esc_html_e('Last Cleanup Date', 'image-cleaner'); ?></p>
        </div>
    </div>

    <style>
        .image-cleaner-admin {
            font-family: 'Roboto', sans-serif;
            color: #333;
        }
        .image-cleaner-admin h1 {
            color: #0073aa;
            font-size: 32px;
            margin-bottom: 20px;
        }
        .image-cleaner-admin p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .image-cleaner-admin ul {
            list-style: none;
            padding: 0;
        }
        .image-cleaner-admin ul li {
            background: #f7f7f7;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            transition: background 0.3s, transform 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .image-cleaner-admin ul li:hover {
            background: #e2e2e2;
            transform: translateY(-2px);
        }
        .image-cleaner-admin ul li a {
            text-decoration: none;
            color: #0073aa;
            font-weight: bold;
            margin-left: 10px;
            font-size: 18px;
            flex: 1;
        }
        .image-cleaner-admin ul li a:hover {
            text-decoration: underline;
        }
        .image-cleaner-admin ul li i {
            font-size: 24px;
            color: #0073aa;
        }
        .image-cleaner-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .image-cleaner-stats .stat {
            background: #f7f7f7;
            padding: 15px;
            border-radius: 5px;
            flex: 1 1 calc(33.333% - 20px);
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background 0.3s, transform 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .image-cleaner-stats .stat:hover {
            background: #e2e2e2;
            transform: translateY(-2px);
        }
        .image-cleaner-stats .stat h2 {
            margin: 10px 0 0;
            font-size: 24px;
            color: #0073aa;
        }
        .image-cleaner-stats .stat p {
            margin: 5px 0 0;
            font-size: 14px;
        }
        .image-cleaner-stats .stat i {
            font-size: 36px;
            color: #0073aa;
            margin-bottom: 10px;
        }
    </style>
</div>