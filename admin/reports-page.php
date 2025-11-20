<?php if (!defined('ABSPATH')) exit; 
$logger = Image_Cleaner_Pro::get_instance()->get_logger();
?>
<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title"><h1>Reportes de Auditoría</h1></div>
        <button onclick="window.print()" class="button">Imprimir</button>
    </div>
    <div class="maw-card">
        <?php $logger->render_logs_table(); ?>
    </div>
</div>
