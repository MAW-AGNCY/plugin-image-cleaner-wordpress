<?php if (!defined('ABSPATH')) exit; ?>

<div class="maw-wrap">
    <!-- Header -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-sos"></span> Centro de Ayuda y Soporte</h1>
        </div>
        <!-- Botón Principal de Contacto -->
        <a href="mailto:info@mondaysatwork.com?subject=Soporte%20Plugin%20MAW%20Cleaner" class="button button-primary">
            <span class="dashicons dashicons-email-alt" style="margin-top:3px;"></span> Contactar Soporte
        </a>
    </div>

    <div class="maw-dashboard-grid" style="grid-template-columns: 2fr 1fr;">
        
        <!-- Columna Izquierda: FAQs -->
        <div class="maw-card">
            <h3>Preguntas Frecuentes (FAQ)</h3>
            
            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Es seguro borrar las imágenes marcadas en rojo?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Sí. Nuestro motor <strong>Deep Scan</strong> analiza:</p>
                    <ul style="list-style:disc; margin-left:20px; margin-bottom:10px;">
                        <li>Entradas y Páginas de WordPress.</li>
                        <li>Productos y Galerías de WooCommerce.</li>
                        <li>Datos de Elementor y Shortcodes de Divi.</li>
                    </ul>
                    <p>Si aparece como <strong>SIN USO</strong>, es que no hemos encontrado ninguna referencia en la base de datos.</p>
                </div>
            </div>

            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    No veo las imágenes en la papelera...
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Esto ocurre si tu servidor tiene configurado el borrado permanente inmediato. Verifica tu archivo <code>wp-config.php</code> y busca <code>define('EMPTY_TRASH_DAYS', 0);</code>. Si está en 0, cámbialo a 30 para tener un margen de seguridad.</p>
                </div>
            </div>

            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Cómo protejo imágenes importantes (Whitelist)?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>En la pestaña <strong>Escanear</strong>, cada imagen tiene un botón llamado "Bloquear". Al pulsarlo, la imagen entra en modo protegido y el sistema impedirá su borrado accidental en futuras limpiezas.</p>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Info Técnica -->
        <div class="maw-card">
            <h3>Información del Sistema</h3>
            <p class="description">Proporciona esta información si contactas con soporte.</p>
            
            <table class="maw-table" style="border:none; margin-top:10px;">
                <tr>
                    <td><strong>Plugin:</strong></td>
                    <td>v2.2.0 Premium</td>
                </tr>
                <tr>
                    <td><strong>WordPress:</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP:</strong></td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td><strong>Memoria Límite:</strong></td>
                    <td><?php echo ini_get('memory_limit'); ?></td>
                </tr>
            </table>

            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">

            <div style="text-align:center;">
                <p>Desarrollado con ❤️ por</p>
                <a href="https://www.mondaysatwork.com" target="_blank" style="text-decoration:none; font-weight:bold; color:#2271b1;">
                    Mondays at Work
                </a>
                <br>
                <small>Agencia Digital Especializada</small>
                <br><br>
                <a href="mailto:info@mondaysatwork.com" style="font-size:12px; color:#666;">info@mondaysatwork.com</a>
            </div>
        </div>

    </div>
</div>
