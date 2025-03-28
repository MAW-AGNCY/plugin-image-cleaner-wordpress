jQuery(document).ready(function ($) {
    $('.image-cleaner-delete-button').on('click', function (e) {
        e.preventDefault();
        const confirmDelete = confirm('Are you sure you want to delete the selected images?');
        if (confirmDelete) {
            const imageIds = $('.image-cleaner-checkbox:checked')
                .map(function () {
                    return $(this).data('image-id');
                })
                .get();
            
            $.post(ajaxurl, {
                action: 'image_cleaner_delete_images',
                image_ids: imageIds,
            }, function (response) {
                if (response.success) {
                    alert('Images deleted successfully.');
                    location.reload();
                } else {
                    alert('Error deleting images: ' + response.data);
                }
            });
        }
    });
});