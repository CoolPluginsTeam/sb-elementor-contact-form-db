jQuery(document).ready(function ($) {
    
    function handleTermLink() {
        const termsLinks = document.querySelectorAll('.fdbgp-ccpw-see-terms');
        const termsBox = document.getElementById('termsBox');

        termsLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (termsBox) {
                    // Toggle display using plain JavaScript
                    const isVisible = termsBox.style.display === 'block';
                    termsBox.style.display = isVisible ? 'none' : 'block';
                    link.innerHTML = !isVisible ? 'Hide Terms' : 'See terms';
                }
            });
        });
    }

    handleTermLink();

    jQuery('.copy-btn').click(function (e) {
        e.preventDefault();

        var target = $(this).data('clipboard-target');
        var text = $(target).text();
        var $button = $(this);
        var originalText = $button.text();

        // Function to show feedback
        function showCopiedFeedback() {
            $button.text('Copied!');
            $button.addClass('button-primary');

            setTimeout(function () {
                $button.text(originalText);
                $button.removeClass('button-primary');
            }, 2000);
        }

        // Check if secure context and navigator.clipboard available
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(showCopiedFeedback)
                .catch(function (err) {
                    console.log('Clipboard API failed, falling back to execCommand:', err);
                    fallbackCopy();
                });
        } else {
            // Fallback for insecure contexts
            fallbackCopy();
        }

        function fallbackCopy() {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();

            try {
                var successful = document.execCommand('copy');
                if (successful) showCopiedFeedback();
            } catch (err) {
                console.log('execCommand copy failed: ', err);
            }

            $temp.remove();
        }
    });


    // Show/hide Generate Token button based on Client ID and Secret
    jQuery('#client_id, #client_secret').on('input', function () {
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