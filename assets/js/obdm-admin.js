jQuery(document).ready(function($) {
    // Confirm before following a delete link.
    $(document).on('click', '.obdm-delete-btn', function(e) {
        var message = $(this).data('obdm-confirm');

        if (message && !window.confirm(message)) {
            e.preventDefault();
        }
    });

    // Copy shortcodes to the clipboard when clicked.
    $(document).on('click', '[data-obdm-copy]', function(e) {
        e.preventDefault();

        var $code = $(this);
        var text = $.trim($code.text());
        var original = $code.text();
        var copied = $code.attr('data-obdm-copied') || 'Copied!';

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        }

        $code.text(copied);
        window.setTimeout(function() {
            $code.text(original);
        }, 1200);
    });
});
