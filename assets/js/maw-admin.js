jQuery(document).ready(function($) {
    
    // 1. CAMBIO DE VISTA (GRID vs LISTA)
    $('#maw-view-grid-btn').on('click', function() {
        $('.maw-view-switcher button').removeClass('active');
        $(this).addClass('active');
        $('#maw-list-view').hide();
        $('#maw-grid-view').css('display', 'grid'); // Restaurar grid layout
        $('#active_view_input').val('grid');
    });

    $('#maw-view-list-btn').on('click', function() {
        $('.maw-view-switcher button').removeClass('active');
        $(this).addClass('active');
        $('#maw-grid-view').hide();
        $('#maw-list-view').show();
        $('#active_view_input').val('list');
    });

    // 2. SELECCIÓN MASIVA
    $('#cb-select-all-1').on('click', function() {
        var checked = this.checked;
        // Seleccionar checkboxes en ambas vistas
        $('input[name="media_ids[]"]').prop('checked', checked);
        // Estilo visual para grid items
        if(checked) {
            $('.maw-grid-item').addClass('selected');
        } else {
            $('.maw-grid-item').removeClass('selected');
        }
    });

    // Selección individual visual en Grid
    $('.maw-grid-item').on('click', function(e) {
        // Si el clic no fue directamente en el checkbox o botón, activamos el checkbox
        if (!$(e.target).is('input[type="checkbox"]') && !$(e.target).is('button') && !$(e.target).is('a')) {
            var cb = $(this).find('input[name="media_ids[]"]');
            cb.prop('checked', !cb.prop('checked'));
        }
        // Actualizar clase visual
        var isChecked = $(this).find('input[name="media_ids[]"]').prop('checked');
        $(this).toggleClass('selected', isChecked);
    });

    // 3. ACORDEÓN DE AYUDA
    $('.maw-accordion-header').on('click', function() {
        $(this).parent().toggleClass('active');
    });

    // 4. WHITELIST AJAX (Para botones pequeños)
    $('.maw-whitelist-action').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var id = btn.data('id');
        
        btn.addClass('updating').text('...');

        $.post(ajaxurl, {
            action: 'maw_toggle_whitelist',
            image_id: id,
            // nonce: maw_vars.nonce // Si pasaras vars localizadas
        }, function(response) {
            if(response.success) {
                location.reload(); // Recargar para actualizar estados visuales
            }
        });
    });
});
