jQuery(document).ready(function ($) {
    $('#cynder-paymaya-void-items').on('click', function() {
        const process = confirm('Are you sure you want to void this order? This action cannot be undone.');

        if (!process) return;

        console.log('Process void');
    });
});