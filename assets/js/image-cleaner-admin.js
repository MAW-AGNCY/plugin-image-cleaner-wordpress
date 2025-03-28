jQuery(document).ready(function ($) {
    // Pestañas de navegación
    $('.image-cleaner-tab-navigation a').on('click', function (e) {
        e.preventDefault();
        const target = $(this).attr('href');
        window.location.href = target;
    });

    // Mostrar notificación al guardar filtros (ejemplo)
    $('#image-cleaner-save-filters').on('click', function () {
        alert('Filters saved successfully!');
    });

    // Animación para alertas
    $('.image-cleaner-alert').fadeIn(300).delay(3000).fadeOut(500);
});