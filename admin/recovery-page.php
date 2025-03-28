<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_menu', 'image_cleaner_register_recovery_page');
function image_cleaner_register_recovery_page() {
    add_menu_page(
        __('Image Cleaner Recovery', 'image-cleaner'),
        __('Image Recovery', 'image-cleaner'),
        'manage_options',
        'image-cleaner-recovery',
        'image_cleaner_render_recovery_page',
        'dashicons-backup',
        6
    );
}

add_action('admin_enqueue_scripts', 'image_cleaner_enqueue_admin_scripts');
function image_cleaner_enqueue_admin_scripts($hook) {
    if ($hook != 'toplevel_page_image-cleaner-recovery') {
        return;
    }
    wp_enqueue_style('image-cleaner-admin', plugins_url('css/admin.css', __FILE__));
    wp_enqueue_script('image-cleaner-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), null, true);
}

function image_cleaner_render_recovery_page() {
    add_action('admin_post_image_cleaner_recover', 'image_cleaner_handle_recovery');
    
    function image_cleaner_handle_recovery() {
        if (!isset($_POST['image_cleaner_nonce']) || !wp_verify_nonce($_POST['image_cleaner_nonce'], 'image_cleaner_recover')) {
            wp_die(__('Nonce verification failed', 'image-cleaner'));
        }
    
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to recover images', 'image-cleaner'));
        }
    
        $image_id = intval($_POST['image_id']);
        if ($image_id) {
            wp_untrash_post($image_id);
            wp_redirect(admin_url('admin.php?page=image-cleaner-recovery&recovered=true'));
            exit;
        } else {
            wp_die(__('Invalid image ID', 'image-cleaner'));
        }
    }
    ?>
    <div class="wrap image-cleaner-admin">
        <h1><?php esc_html_e('Image Cleaner - Recovery', 'image-cleaner'); ?></h1>

        <p><?php esc_html_e('Restore deleted images from the backup repository.', 'image-cleaner'); ?></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Image', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('File Name', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Date Deleted', 'image-cleaner'); ?></th>
                    <th><?php esc_html_e('Actions', 'image-cleaner'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- AJAX or PHP logic for recovery -->
                <?php
                // Example of how to display images for recovery
                $images = get_posts([
                    'post_type' => 'attachment',
                    'post_status' => 'trash',
                    'posts_per_page' => -1,
                ]);

                if ($images) {
                    foreach ($images as $image) {
                        $image_id = $image->ID;
                        $file_name = basename(get_attached_file($image_id));
                        $date_deleted = get_the_date('', $image_id);
                        $image_url = wp_get_attachment_url($image_id);
                        ?>
                        <tr>
                            <td><img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($file_name); ?>" width="50"></td>
                            <td><?php echo esc_html($file_name); ?></td>
                            <td><?php echo esc_html($date_deleted); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="image_cleaner_recover">
                                    <input type="hidden" name="image_id" value="<?php echo esc_attr($image_id); ?>">
                                    <?php wp_nonce_field('image_cleaner_recover', 'image_cleaner_nonce'); ?>
                                    <input type="submit" class="button" value="<?php esc_attr_e('Recover', 'image-cleaner'); ?>">
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="4">' . esc_html__('No images found for recovery.', 'image-cleaner') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
