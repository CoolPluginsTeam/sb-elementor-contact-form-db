document.addEventListener('DOMContentLoaded', function () {

    jQuery('.copy-btn').click(function(e) {
        e.preventDefault();
        var target = $(this).data('clipboard-target');
        var text = $(target).text();
        var $button = $(this);
        var originalText = $button.text();
        
        // Create temporary input element
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                $button.text('<?php esc_attr_e("Copied!", "elementor-contact-form-db"); ?>');
                $button.addClass('button-primary');
                
                setTimeout(function() {
                    $button.text(originalText);
                    $button.removeClass('button-primary');
                }, 2000);
            }
        } catch (err) {
            console.log('Failed to copy text: ', err);
        }
        
        $temp.remove();
    });
    
    // Show/hide Generate Token button based on Client ID and Secret
    jQuery('#client_id, #client_secret').on('input', function() {
        var clientId = $('#client_id').val().trim();
        var clientSecret = $('#client_secret').val().trim();
        var $authBtn = $('#authbtn');
        
        if (clientId && clientSecret && !$('#client_token').val()) {
            $authBtn.show();
        } else {
            $authBtn.hide();
        }
    });

})