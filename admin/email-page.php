<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_enqueue_scripts', 'image_cleaner_enqueue_admin_scripts');
function image_cleaner_enqueue_admin_scripts($hook) {
    if ($hook != 'toplevel_page_image-cleaner-email') {
        return;
    }
    wp_enqueue_style('image-cleaner-email-page', plugins_url('css/email-page.css', __FILE__));
    wp_enqueue_script('image-cleaner-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), null, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_test_email'])) {
        // Enviar un correo electrónico de prueba
        $email_recipient = sanitize_email($_POST['email_recipient']);
        $email_subject = sanitize_text_field($_POST['email_subject']);
        $email_message = sanitize_textarea_field($_POST['email_message']);
        $email_template = sanitize_text_field($_POST['email_template']);

        // Datos de ejemplo para las plantillas
        $image_types = 'JPEG, PNG';
        $total_size = '15 MB';
        $space_recovered = '10 MB';
        $deleted_images = [
            ['name' => 'image1.jpg', 'type' => 'JPEG', 'size' => '5 MB', 'deleted_on' => '2023-01-01'],
            ['name' => 'image2.png', 'type' => 'PNG', 'size' => '10 MB', 'deleted_on' => '2023-01-02'],
        ];

        ob_start();
        include IMAGE_CLEANER_PLUGIN_DIR . 'admin/email-templates/' . $email_template . '.php';
        $email_content = ob_get_clean();

        if (wp_mail($email_recipient, $email_subject, $email_content, ['Content-Type: text/html; charset=UTF-8'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Test email sent successfully.', 'image-cleaner') . '</p></div>';
            // Guardar en el historial
            $email_history = get_option('image_cleaner_email_history', []);
            $email_history[] = [
                'recipient' => $email_recipient,
                'subject' => $email_subject,
                'message' => $email_message,
                'template' => $email_template,
                'date' => current_time('mysql')
            ];
            update_option('image_cleaner_email_history', $email_history);
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to send test email.', 'image-cleaner') . '</p></div>';
        }
    } elseif (isset($_POST['reset_settings'])) {
        // Restablecer la configuración a los valores predeterminados
        delete_option('image_cleaner_email_recipient');
        delete_option('image_cleaner_email_frequency');
        delete_option('image_cleaner_email_subject');
        delete_option('image_cleaner_email_message');
        delete_option('image_cleaner_email_template');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings reset to default.', 'image-cleaner') . '</p></div>';
    } elseif (isset($_POST['clear_email_history'])) {
        // Eliminar el historial de correos electrónicos
        delete_option('image_cleaner_email_history');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Email history cleared.', 'image-cleaner') . '</p></div>';
    } else {
        // Validar y guardar la configuración del formulario
        if (isset($_POST['email_recipient']) && isset($_POST['email_frequency']) && isset($_POST['email_subject']) && isset($_POST['email_message']) && isset($_POST['email_template'])) {
            $email_recipient = sanitize_email($_POST['email_recipient']);
            $email_frequency = sanitize_text_field($_POST['email_frequency']);
            $email_subject = sanitize_text_field($_POST['email_subject']);
            $email_message = sanitize_textarea_field($_POST['email_message']);
            $email_template = sanitize_text_field($_POST['email_template']);

            update_option('image_cleaner_email_recipient', $email_recipient);
            update_option('image_cleaner_email_frequency', $email_frequency);
            update_option('image_cleaner_email_subject', $email_subject);
            update_option('image_cleaner_email_message', $email_message);
            update_option('image_cleaner_email_template', $email_template);

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'image-cleaner') . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Please fill in all fields.', 'image-cleaner') . '</p></div>';
        }
    }
}

// Obtener los valores actuales de la configuración
$email_recipient = get_option('image_cleaner_email_recipient', '');
$email_frequency = get_option('image_cleaner_email_frequency', 'weekly');
$email_subject = get_option('image_cleaner_email_subject', __('Test Email from Image Cleaner', 'image-cleaner'));
$email_message = get_option('image_cleaner_email_message', __('This is a test email from the Image Cleaner plugin.', 'image-cleaner'));
$email_template = get_option('image_cleaner_email_template', 'default');
$email_history = get_option('image_cleaner_email_history', []);
?>
<div class="wrap image-cleaner-admin">
    <h1><?php esc_html_e('Image Cleaner - Email Notifications', 'image-cleaner'); ?></h1>

    <form method="post" action="" id="email-settings-form">
        <h2><?php esc_html_e('Configure Email Settings', 'image-cleaner'); ?></h2>

        <label for="email-recipient">
            <?php esc_html_e('Recipient Email:', 'image-cleaner'); ?>
            <input type="email" id="email-recipient" name="email_recipient" value="<?php echo esc_attr($email_recipient); ?>" placeholder="e.g., admin@example.com" required>
        </label>

        <label for="email-frequency">
            <?php esc_html_e('Email Frequency:', 'image-cleaner'); ?>
            <select id="email-frequency" name="email_frequency">
                <option value="daily" <?php selected($email_frequency, 'daily'); ?>><?php esc_html_e('Daily', 'image-cleaner'); ?></option>
                <option value="weekly" <?php selected($email_frequency, 'weekly'); ?>><?php esc_html_e('Weekly', 'image-cleaner'); ?></option>
                <option value="monthly" <?php selected($email_frequency, 'monthly'); ?>><?php esc_html_e('Monthly', 'image-cleaner'); ?></option>
            </select>
        </label>

        <label for="email-subject">
            <?php esc_html_e('Email Subject:', 'image-cleaner'); ?>
            <input type="text" id="email-subject" name="email_subject" value="<?php echo esc_attr($email_subject); ?>" placeholder="<?php esc_attr_e('Enter email subject', 'image-cleaner'); ?>" required>
        </label>

        <label for="email-message">
            <?php esc_html_e('Email Message:', 'image-cleaner'); ?>
            <textarea id="email-message" name="email_message" rows="5" placeholder="<?php esc_attr_e('Enter email message', 'image-cleaner'); ?>" required><?php echo esc_textarea($email_message); ?></textarea>
        </label>

        <label for="email-template">
            <?php esc_html_e('Email Template:', 'image-cleaner'); ?>
            <select id="email-template" name="email_template">
                <option value="default" <?php selected($email_template, 'default'); ?>><?php esc_html_e('Default', 'image-cleaner'); ?></option>
                <option value="basic-email-template" <?php selected($email_template, 'basic-email-template'); ?>><?php esc_html_e('Basic Email Template', 'image-cleaner'); ?></option>
                <option value="advanced-email-template" <?php selected($email_template, 'advanced-email-template'); ?>><?php esc_html_e('Advanced Email Template', 'image-cleaner'); ?></option>
            </select>
        </label>

        <div class="button-group">
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Save Settings', 'image-cleaner'); ?>
            </button>
            <button type="submit" name="send_test_email" class="button button-secondary">
                <?php esc_html_e('Send Test Email', 'image-cleaner'); ?>
            </button>
            <button type="button" id="preview-email" class="button button-secondary">
                <?php esc_html_e('Preview Email', 'image-cleaner'); ?>
            </button>
            <button type="submit" name="reset_settings" class="button button-secondary">
                <?php esc_html_e('Reset Settings', 'image-cleaner'); ?>
            </button>
            <button type="submit" name="clear_email_history" class="button button-secondary">
                <?php esc_html_e('Clear Email History', 'image-cleaner'); ?>
            </button>
        </div>
    </form>

    <div id="email-preview" style="display:none;">
        <h2><?php esc_html_e('Email Preview', 'image-cleaner'); ?></h2>
        <div id="email-preview-content"></div>
    </div>

    <h2><?php esc_html_e('Email History', 'image-cleaner'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Recipient', 'image-cleaner'); ?></th>
                <th><?php esc_html_e('Subject', 'image-cleaner'); ?></th>
                <th><?php esc_html_e('Date', 'image-cleaner'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($email_history)) : ?>
                <?php foreach ($email_history as $email) : ?>
                    <tr>
                        <td><?php echo esc_html($email['recipient']); ?></td>
                        <td><?php echo esc_html($email['subject']); ?></td>
                        <td><?php echo esc_html($email['date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3"><?php esc_html_e('No emails sent yet.', 'image-cleaner'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('email-settings-form').addEventListener('submit', function(event) {
    var email = document.getElementById('email-recipient').value;
    if (!email) {
        event.preventDefault();
        alert('<?php esc_html_e('Please enter a valid email address.', 'image-cleaner'); ?>');
    }
});

document.getElementById('preview-email').addEventListener('click', function() {
    var subject = document.getElementById('email-subject').value;
    var message = document.getElementById('email-message').value;
    var template = document.getElementById('email-template').value;
    var previewContent = '<strong>' + subject + '</strong><br><br>' + message;

    // Simulate the template rendering
    if (template === 'basic-email-template') {
        previewContent = '<div style="background-color: #0073aa; color: #ffffff; padding: 10px; text-align: center;"><h1>' + subject + '</h1></div><div style="padding: 20px;">' + message + '</div>';
    } else if (template === 'advanced-email-template') {
        previewContent = '<div style="background-color: #333333; color: #ffffff; padding: 10px; text-align: center;"><h1>' + subject + '</h1></div><div style="padding: 20px; border: 1px solid #cccccc;">' + message + '</div>';
    }

    document.getElementById('email-preview-content').innerHTML = previewContent;
    document.getElementById('email-preview').style.display = 'block';
});
</script>