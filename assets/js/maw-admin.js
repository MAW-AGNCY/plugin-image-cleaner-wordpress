jQuery(document).ready(function($) {
    
    // 1. Switcher de Vistas (Lista / Grid)
    $('#maw-view-grid-btn').on('click', function() {
        $('.maw-view-switcher button').removeClass('active');
        $(this).addClass('active');
        $('#maw-list-view').hide();
        $('#maw-grid-view').css('display', 'grid');
        $('#active_view_input').val('grid');
    });

    $('#maw-view-list-btn').on('click', function() {
        $('.maw-view-switcher button').removeClass('active');
        $(this).addClass('active');
        $('#maw-grid-view').hide();
        $('#maw-list-view').show();
        $('#active_view_input').val('list');
    });

    // 2. Select All (Funciona para ambas vistas)
    $('#cb-select-all-1').on('click', function() {
        var checked = this.checked;
        $('input[name="media_ids[]"]').prop('checked', checked);
        
        if(checked) {
            $('.maw-grid-item').addClass('selected');
        } else {
            $('.maw-grid-item').removeClass('selected');
        }
    });

    // 3. Selección visual individual en Grid
    $('.maw-grid-item').on('click', function(e) {
        // Evitar disparar si clicamos en un botón o enlace
        if (!$(e.target).is('input') && !$(e.target).is('button') && !$(e.target).is('a')) {
            var cb = $(this).find('input[name="media_ids[]"]');
            cb.prop('checked', !cb.prop('checked'));
        }
        var isChecked = $(this).find('input[name="media_ids[]"]').prop('checked');
        $(this).toggleClass('selected', isChecked);
    });

    // 4. Acordeón de Ayuda
    $('.maw-accordion-header').on('click', function() {
        $(this).parent().toggleClass('active');
    });

    // 5. AJAX Whitelist (Botones pequeños)
    $('.maw-whitelist-action').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var id = btn.data('id');
        
        btn.text('...'); // Feedback visual
        
        $.post(ajaxurl, {
            action: 'maw_toggle_whitelist',
            image_id: id
        }, function(response) {
            if(response.success) {
                location.reload();
            }
        });
    });
});
