<?php if (!defined('ABSPATH')) exit; ?>

<div class="maw-wrap">
    <!-- HEADER CON ACCIÓN DIRECTA -->
    <div class="maw-header">
        <div class="maw-title">
            <h1><span class="dashicons dashicons-sos"></span> Centro de Soporte Premium</h1>
        </div>
        <a href="https://www.mondaysatwork.com/contacto" target="_blank" class="button button-primary">
            <span class="dashicons dashicons-external" style="margin-top:4px;"></span> Web Oficial
        </a>
    </div>

    <div class="maw-dashboard-grid" style="grid-template-columns: 2fr 1fr;">
        
        <!-- COLUMNA IZQUIERDA: DOCUMENTACIÓN -->
        <div class="maw-card">
            <h3><span class="dashicons dashicons-book"></span> Documentación Rápida</h3>
            
            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Cómo funciona el Escáner Profundo?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Nuestro escáner <strong>MAW Deep Scan</strong> revisa 4 niveles:</p>
                    <ol style="margin-left: 20px;">
                        <li><strong>WordPress Core:</strong> Entradas, páginas y custom post types.</li>
                        <li><strong>WooCommerce:</strong> Imágenes destacadas de producto y galerías.</li>
                        <li><strong>Metadatos:</strong> Campos personalizados (ACF) y configuraciones de tema.</li>
                        <li><strong>Page Builders:</strong> Elementor (JSON) y Divi (Shortcodes).</li>
                    </ol>
                </div>
            </div>

            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Qué es la "Papelera Segura"?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Para evitar desastres, este plugin nunca borra archivos del servidor inmediatamente. Los mueve a la papelera (estado <code>trash</code>). Tienes 30 días (dependiendo de tu configuración) para restaurarlos desde la pestaña <strong>Recuperación</strong>.</p>
                </div>
            </div>

            <div class="maw-accordion">
                <div class="maw-accordion-header">
                    ¿Cómo configuro los emails?
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="maw-accordion-content">
                    <p>Ve a la pestaña <strong>Notificaciones</strong>. Puedes elegir la frecuencia de los reportes y el diseño de la plantilla. Asegúrate de que tu servidor permite el envío de correos SMTP.</p>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: CONTACTO Y SOCIAL -->
        <div class="maw-card" style="text-align: center;">
            <img src="https://mondaysatwork.com/wp-content/uploads/2014/03/logo.mondaysatwork.png" alt="MAW Logo" style="max-width: 150px; margin-bottom: 15px;">
            
            <p style="font-size: 13px; color: var(--maw-text-sec);">
                Desarrollamos soluciones digitales de primer nivel.
            </p>

            <div class="maw-social-links" style="margin: 20px 0; display: flex; justify-content: center; gap: 15px;">
                <!-- Facebook -->
                <a href="https://www.facebook.com/mondaysatwork" target="_blank" title="Facebook" style="text-decoration:none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#444444"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                </a>
                <!-- X (Twitter) -->
                <a href="https://x.com/mondaysatwork/" target="_blank" title="X" style="text-decoration:none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#444444"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <!-- LinkedIn -->
                <a href="https://www.linkedin.com/company/mondays-at-work" target="_blank" title="LinkedIn" style="text-decoration:none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#444444"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
                <!-- GitHub -->
                <a href="https://github.com/orgs/Mondays-at-work" target="_blank" title="GitHub" style="text-decoration:none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#444444"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                </a>
            </div>

            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; border: 1px solid #eee;">
                <h4 style="margin:0 0 5px 0; color:#a81010;">¿Necesitas soporte técnico?</h4>
                <a href="mailto:info@mondaysatwork.com" style="text-decoration:none; font-weight:bold; color:#444;">info@mondaysatwork.com</a>
            </div>
        </div>
    </div>
</div>

<!-- CSS para el efecto hover de los iconos sociales -->
<style>
.maw-social-links a:hover svg { fill: #a81010; transition: fill 0.3s ease; }
</style>
