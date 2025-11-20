<?php if (!defined('ABSPATH')) exit; ?>

<div class="maw-wrap">
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-sos"></span> Centro de Soporte Platinum</h1>
        </div>
        <a href="mailto:support@mondaysatwork.com" class="button button-primary">Abrir Ticket</a>
    </div>

    <div class="maw-dashboard-grid">
        
        <!-- FAQs Acordeón -->
        <div class="maw-card">
            <h3>Preguntas Frecuentes (FAQ)</h3>
            
            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Es seguro borrar las imágenes marcadas en rojo?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Sí, el sistema ha escaneado Base de Datos, Elementor, Divi y WooCommerce. Si aparece en rojo (SIN USO), es que no hemos encontrado ninguna referencia. Aun así, siempre recomendamos hacer una copia de seguridad antes de limpiezas masivas.</p>
                </div>
            </div>

            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Por qué no veo archivos en la papelera?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Verifica tu archivo <code>wp-config.php</code>. Si tienes definida la constante <code>EMPTY_TRASH_DAYS</code> en 0, la papelera está desactivada y los archivos se borran inmediatamente. Cámbialo a 30 para tener seguridad.</p>
                </div>
            </div>

            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Cómo protejo mi Logo corporativo?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Ve a la pestaña <strong>Escanear</strong>, busca tu logo y pulsa el botón <strong>Bloquear</strong>. Esto lo añadirá a la Whitelist interna y nunca será sugerido para borrado.</p>
                </div>
            </div>
        </div>

        <!-- Información Técnica -->
        <div class="maw-card">
            <h3>Información del Sistema</h3>
            <table class="maw-table" style="border:none; box-shadow:none;">
                <tr><td><strong>Versión Plugin:</strong></td><td>2.2.0 Platinum</td></tr>
                <tr><td><strong>WordPress:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                <tr><td><strong>Límite PHP:</strong></td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                <tr><td><strong>Papelera WP:</strong></td><td><?php echo (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS==0) ? '<span style="color:red">Desactivada</span>' : 'Activada'; ?></td></tr>
            </table>
        </div>

    </div>
</div>
