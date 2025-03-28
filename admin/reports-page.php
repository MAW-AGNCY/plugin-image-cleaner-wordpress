<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_enqueue_scripts', 'image_cleaner_enqueue_admin_scripts');
function image_cleaner_enqueue_admin_scripts($hook) {
    if ($hook != 'image-cleaner_page_image-cleaner-reports') {
        return;
    }
    wp_enqueue_style('image-cleaner-admin', plugins_url('css/admin.css', __FILE__));
    wp_enqueue_script('image-cleaner-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), null, true);

    // Debugging
    if ( !wp_style_is( 'image-cleaner-admin', 'enqueued' ) ) {
        error_log( 'Failed to enqueue image-cleaner-admin style.' );
    }
    if ( !wp_script_is( 'image-cleaner-admin', 'enqueued' ) ) {
        error_log( 'Failed to enqueue image-cleaner-admin script.' );
    }
}
?>
<div class="wrap image-cleaner-reports">
    <h1><?php esc_html_e('Reports', 'image-cleaner'); ?></h1>

    <p><?php esc_html_e('View detailed reports about unused images and cleanup actions.', 'image-cleaner'); ?></p>

    <div class="report-card">
        <h2><?php esc_html_e('Unused Images Report', 'image-cleaner'); ?></h2>
        <p><?php esc_html_e('A list of all images that are not currently used in posts or pages.', 'image-cleaner'); ?></p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=image-cleaner-reports&report_type=unused')); ?>" class="button"><?php esc_html_e('View Report', 'image-cleaner'); ?></a>
    </div>

    <div class="report-card">
        <h2><?php esc_html_e('Large Images Report', 'image-cleaner'); ?></h2>
        <p><?php esc_html_e('Images that exceed a specific size threshold.', 'image-cleaner'); ?></p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=image-cleaner-reports&report_type=large')); ?>" class="button"><?php esc_html_e('View Report', 'image-cleaner'); ?></a>
    </div>
</div>