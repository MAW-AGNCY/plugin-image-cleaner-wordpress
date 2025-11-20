# 🛡️ MAW Image Cleaner Pro

![Version](https://img.shields.io/badge/version-2.0.0-blue?style=flat-square)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-success?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple?style=flat-square)
![License](https://img.shields.io/badge/license-Proprietary-red?style=flat-square)

> **La Suite definitiva de optimización de medios para WordPress.**  
> Desarrollado por [Mondays at Work](https://www.mondaysatwork.com).

MAW Image Cleaner Pro no es un simple limpiador. Es una herramienta de auditoría forense para tu biblioteca de medios, diseñada para trabajar con ecosistemas complejos como **WooCommerce, Elementor y Divi**.

---

## ✨ Características Premium (v2.0.0)

### 🧠 Escáner "Deep Scan"
A diferencia de otros plugins que solo miran la "Imagen Destacada", nuestro motor `MAW_Scanner` analiza:
*   **Contenido HTML:** Búsqueda por Regex de nombres de archivo.
*   **WooCommerce:** Detección nativa de `_product_image_gallery`.
*   **Page Builders:** Decodificación de JSON de Elementor y Shortcodes de Divi.

### 🎨 Interfaz Visual (UI/UX)
*   **Vista Grid:** Visualiza tus imágenes como en la biblioteca nativa.
*   **Badges de Estado:** Identifica rápidamente qué está `EN USO`, `SIN USO` o `PROTEGIDO`.
*   **Modo Protección (Whitelist):** Bloquea imágenes corporativas (Logos, Favicons) para evitar errores humanos.

### 🛡️ Seguridad y Auditoría
*   **Papelera Segura:** Los archivos nunca se borran directamente (Soft Delete).
*   **Logs de Auditoría:** Registro inmutable de quién borró qué y cuándo.
*   **Alertas HTML:** Notificaciones por email con diseño profesional.

---

## 🚀 Instalación

1.  Descarga el archivo `.zip` de la última Release.
2.  Sube el plugin a `/wp-content/plugins/`.
3.  Activa el plugin desde el panel de WordPress.
4.  Ve a **MAW Cleaner** en el menú lateral.

---

## 📖 Guía Rápida

### 1. Escanear
Ve a la pestaña **Escanear**. El sistema analizará automáticamente el uso de las imágenes mostradas.
*   🟢 **Verde:** La imagen se usa en un Post/Producto. Se mostrará un enlace directo a donde se usa.
*   🔴 **Rojo:** No se encontraron referencias. Candidata a borrar.

### 2. Limpiar
Selecciona las imágenes marcadas en rojo. Haz clic en "Mover a Papelera".
*   *Tip:* Si hay una imagen que no se usa pero quieres conservar, haz clic en **Bloquear**.

### 3. Recuperar
Si te equivocas, ve a la pestaña **Recuperación**. Allí encontrarás los archivos borrados listos para ser restaurados.

---

## 🏗️ Estructura del Proyecto

```text
/
├── admin/                  # Vistas del Panel de Control (MVC)
│   ├── filters-page.php    # Interfaz del Escáner
│   ├── recovery-page.php   # Interfaz de Papelera
│   ├── help-page.php       # Documentación interna
│   └── ...
├── assets/
│   └── css/maw-admin-ui.css # Estilos Premium
├── includes/
│   ├── class-maw-scanner.php # Motor de análisis (Core Logic)
│   ├── class-maw-email.php   # Gestor de correos HTML
│   ├── class-logger.php      # Sistema de auditoría
│   └── ...
├── templates/              # Plantillas HTML para emails
└── image-cleaner.php       # Bootstrap del plugin
