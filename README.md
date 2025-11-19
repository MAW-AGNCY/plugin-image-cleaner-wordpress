# MAW Image Cleaner Pro - WordPress Plugin

![Version](https://img.shields.io/badge/version-1.0.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.5%2B-green)
![Author](https://img.shields.io/badge/Author-Mondays%20at%20Work-orange)

**MAW Image Cleaner Pro** es una solución avanzada desarrollada por [Mondays at Work](https://www.mondaysatwork.com) para auditar, limpiar y optimizar la biblioteca de medios de WordPress. Diseñado para tiendas WooCommerce y sitios de alto tráfico, garantiza la integridad de los datos mediante un sistema de "Papelera Segura".

## 🚀 Características Principales

*   **Escaneo Inteligente:** Detecta imágenes huérfanas que no están asignadas como imagen destacada ni en galerías de productos.
*   **Soporte WooCommerce:** Detección nativa de galerías de productos (`_product_image_gallery`) para evitar falsos positivos.
*   **Borrado Seguro (Soft Delete):** Las imágenes se envían a la papelera en lugar de eliminarse permanentemente, permitiendo su recuperación.
*   **Reportes y Alertas:** Sistema de notificaciones por email sobre el estado de la biblioteca.
*   **Filtros Avanzados:** Filtra por tipo de archivo (JPG, PNG, WebP), fecha y estado de uso.

## 🛠️ Instalación

1.  Descarga el archivo `.zip` del repositorio.
2.  Sube el plugin a tu instalación de WordPress en `Plugins > Añadir nuevo > Subir plugin`.
3.  Activa el plugin.
4.  Ve a `Image Cleaner` en el menú de administración.

## ⚙️ Uso Básico

1.  **Dashboard:** Revisa el resumen del espacio ocupado y el número de archivos.
2.  **Filtros:** Ve a la pestaña de filtros para buscar imágenes antiguas o pesadas.
3.  **Limpieza:** Selecciona las imágenes que el sistema marca como "No en uso".
4.  **Recuperación:** Si borraste algo por error, ve a la pestaña "Recuperación" para restaurarlo desde la papelera.

## 🔒 Seguridad

Este plugin ha sido auditado internamente para prevenir:
*   Vulnerabilidades CSRF (Cross-Site Request Forgery).
*   Accesos no autorizados (Control de capacidades `manage_options`).
*   Borrado accidental (Implementación de `wp_trash_post`).

## © Copyright y Autoría

Desarrollado y mantenido por **Mondays at Work**.

*   **Web:** [https://www.mondaysatwork.com](https://www.mondaysatwork.com)
*   **Email:** info@mondaysatwork.com
*   **Soporte:** Para soporte comercial o personalizaciones, contáctanos directamente.

---
*Nota: Este software es propiedad de Mondays at Work. Se distribuye para uso exclusivo de sus clientes o bajo los términos acordados.*
