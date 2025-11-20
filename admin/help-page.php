<?php if (!defined('ABSPATH')) exit; ?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-sos"></span> Centro de Ayuda</h1>
        </div>
    </div>

    <div class="maw-grid" style="grid-template-columns: 1fr 1fr;">
        
        <div class="maw-card-img" style="cursor:default; display:block; padding:20px;">
            <h3>🟢 Interpretando los Estados</h3>
            <p>Nuestro escáner analiza tu sitio y asigna estados:</p>
            <ul style="list-style:disc; margin-left:20px;">
                <li><strong>EN USO:</strong> Encontrada en Post, Página, Producto WooCommerce o Elementor/Divi. <span style="color:green">NO BORRAR.</span></li>
                <li><strong>SIN USO:</strong> No encontramos referencias en la base de datos. Candidata a borrar.</li>
                <li><strong>PROTEGIDO:</strong> Tú has bloqueado esta imagen manualmente para evitar borrados accidentales.</li>
            </ul>
        </div>

        <div class="maw-card-img" style="cursor:default; display:block; padding:20px;">
            <h3>🛠 Integraciones Soportadas</h3>
            <p>Versión 2.0.0 soporta nativamente:</p>
            <ul style="list-style:disc; margin-left:20px;">
                <li>WordPress Core (Imágenes destacadas, Contenido).</li>
                <li><strong>WooCommerce:</strong> Galerías de producto.</li>
                <li><strong>Elementor:</strong> Datos JSON y widgets de imagen.</li>
                <li><strong>Divi Theme:</strong> Shortcodes y módulos.</li>
            </ul>
        </div>

        <div class="maw-card-img" style="cursor:default; display:block; padding:20px;">
            <h3>⚠️ ¿Borraste algo por error?</h3>
            <p>No entres en pánico.</p>
            <ol>
                <li>Ve a la pestaña <strong>Recuperación</strong>.</li>
                <li>Busca el archivo en la lista.</li>
                <li>Haz clic en <strong>Restaurar</strong>.</li>
            </ol>
            <p><em>Nota: Si vacías la papelera de WordPress, los archivos se perderán para siempre.</em></p>
        </div>
        
        <div class="maw-card-img" style="cursor:default; display:block; padding:20px;">
            <h3>📞 Soporte Mondays at Work</h3>
            <p>Si encuentras un bug o necesitas una integración a medida:</p>
            <p><a href="mailto:info@mondaysatwork.com" class="button button-primary">Contactar Soporte</a></p>
            <p><small>Versión instalada: 2.0.0</small></p>
        </div>
    </div>
</div>
