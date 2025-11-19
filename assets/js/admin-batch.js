jQuery(document).ready(function($) {
    $('#start-scan-btn').on('click', function(e) {
        e.preventDefault();
        processBatch(0); // Iniciar desde el offset 0
    });

    function processBatch(offset) {
        $('.scan-status').text('Escaneando imágenes desde ' + offset + '...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'maw_scan_images_batch', // Acción registrada en WP
                offset: offset,
                nonce: $('#maw_scan_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.remaining > 0) {
                        // Si quedan imágenes, llamar a la función recursivamente
                        var progress = response.data.progress_percentage;
                        $('.progress-bar').css('width', progress + '%');
                        processBatch(response.data.next_offset);
                    } else {
                        $('.scan-status').text('¡Escaneo completado!');
                        location.reload(); // Recargar para ver resultados
                    }
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});
