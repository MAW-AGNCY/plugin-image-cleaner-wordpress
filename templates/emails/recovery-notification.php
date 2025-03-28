<!DOCTYPE html>
<html>
<head>
    <title>Image Recovery Notification</title>
</head>
<body>
    <h1>Image Recovery Notification</h1>
    <p>Hello,</p>
    <p>The following images were successfully restored:</p>
    <ul>
        <?php foreach ($restored_images as $image) : ?>
            <li><?php echo $image; ?></li>
        <?php endforeach; ?>
    </ul>
    <p>If you didn't request this action, please contact support immediately.</p>
</body>
</html>