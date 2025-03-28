<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($subject); ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <div class="content">
        <?php echo nl2br(esc_html($message)); ?>
    </div>
</body>
</html>