# MAW Image Cleaner Pro - WordPress Media Optimization Suite

![Version](https://img.shields.io/badge/version-2.2.0-a81010?style=flat-square)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-444444?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-444444?style=flat-square)
![License](https://img.shields.io/badge/license-Proprietary-a81010?style=flat-square)

> **La solución definitiva para auditar, limpiar y optimizar bibliotecas de medios en ecosistemas complejos (WooCommerce, Elementor, Divi).**

Desarrollado por **[Mondays at Work](https://www.mondaysatwork.com)**.

---

## 🚀 Características Premium

### 🧠 Escáner Inteligente (Deep Scan)
A diferencia de los plugins convencionales, nuestro motor detecta el uso de imágenes en:
*   **WooCommerce:** Galerías de producto y variaciones.
*   **Page Builders:** Datos JSON de Elementor y Shortcodes de Divi.
*   **Metadatos:** Campos ACF y configuraciones de tema.

### 🛡️ Sistema de Seguridad
*   **Papelera Forzada (SQL Direct):** Evita el borrado permanente accidental, incluso si WordPress tiene la papelera desactivada.
*   **Whitelist (Protección):** Permite bloquear imágenes corporativas (Logos, Favicons) para que nunca sean sugeridas para borrado.
*   **Auditoría Forense (Logs):** Registro inmutable de todas las acciones realizadas (borrado, recuperación) con fecha y usuario responsable.

### 🎨 Interfaz Corporativa (MAW UI)
*   Diseño limpio y coherente con la identidad de **Mondays at Work**.
*   Vistas conmutables: Lista detallada (con peso y tipo) o Cuadrícula visual.
*   Gráficos de uso de almacenamiento en tiempo real.

### 📧 Notificaciones HTML
*   Plantillas de correo electrónico profesionales y responsive.
*   Alertas automáticas sobre el estado de la biblioteca.

---

## 🛠️ Instalación

1.  Descarga el archivo `.zip` de la última Release.
2.  Sube el plugin a través de `Plugins > Añadir nuevo > Subir plugin` en WordPress.
3.  Activa el plugin.
4.  Navega a **MAW Cleaner** en el menú lateral.

---

## 📖 Guía de Uso

### 1. Escanear
Ve a la pestaña **Escanear**. El sistema marcará automáticamente el estado de cada imagen:
*   🟢 **EN USO:** Encontrada en el contenido.
*   🔴 **SIN USO:** Segura para borrar.
*   🟣 **WOO:** En uso por WooCommerce.

### 2. Proteger (Whitelist)
Si ves una imagen marcada como "Sin Uso" que deseas conservar (ej. un logo que solo usas en emails), haz clic en el botón **Bloquear**.

### 3. Limpiar
Selecciona las imágenes no deseadas y haz clic en **Mover a Papelera**.

### 4. Recuperar
¿Te equivocaste? Ve a la pestaña **Recuperación**, busca el archivo y haz clic en **Restaurar**.

---

## 📂 Estructura del Repositorio

```text
plugin-image-cleaner/
├── admin/                  # Vistas (MVC)
│   ├── overview-page.php   # Dashboard
│   ├── filters-page.php    # Escáner
│   └── ...
├── assets/                 # Recursos Estáticos
│   ├── css/maw-admin-ui.css # Estilos Corporativos
│   └── js/maw-admin.js     # Lógica JS
├── includes/               # Lógica de Negocio
│   ├── class-maw-scanner.php       # Motor de Análisis
│   ├── class-maw-email-manager.php # Sistema de Emails
│   └── ...
├── templates/              # Plantillas HTML
│   └── emails/             # Emails Corporativos
└── image-cleaner.php       # Bootstrap
