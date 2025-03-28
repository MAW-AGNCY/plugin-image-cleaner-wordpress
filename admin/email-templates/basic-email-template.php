<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($subject); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header { background-color: #0073aa; color: #ffffff; padding: 10px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { background-color: #f4f4f4; color: #333333; padding: 10px; text-align: center; border-radius: 0 0 8px 8px; }
        .info { margin-bottom: 20px; }
        .info h2 { margin: 0 0 10px; }
        .info p { margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo esc_html($subject); ?></h1>
        </div>
        <div class="content">
            <div class="info">
                <h2><?php esc_html_e('Summary of Deleted Images', 'image-cleaner'); ?></h2>
                <p><?php echo nl2br(esc_html($message)); ?></p>
            </div>
            <div class="info">
                <h2><?php esc_html_e('Details', 'image-cleaner'); ?></h2>
                <p><?php esc_html_e('Types of Images Deleted:', 'image-cleaner'); ?> <?php echo esc_html($image_types); ?></p>
                <p><?php esc_html_e('Total Size of Deleted Images:', 'image-cleaner'); ?> <?php echo esc_html($total_size); ?></p>
                <p><?php esc_html_e('Total Space Recovered:', 'image-cleaner'); ?> <?php echo esc_html($space_recovered); ?></p>
            </div>
        </div>
        <div class="footer">
            <p><?php esc_html_e('Thank you for using Image Cleaner!', 'image-cleaner'); ?></p>
        </div>
    </div>
</body>
</html>