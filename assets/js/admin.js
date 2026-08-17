jQuery(document).ready(function($) {
    // Handle delete confirmations
    $('.bdm-delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Toggle is_available checkbox styling
    $('input[name="is_available"], input[name="is_active"]').on('change', function() {
        var $label = $(this).closest('td').prev('th').text();
    });
});
