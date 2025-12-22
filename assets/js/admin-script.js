jQuery(document).ready(function ($) {

    function handleFormsDBSubmenu() {
        var $entriesItem = jQuery('.wp-submenu a[href="admin.php?page=formsdb"]').closest('li');

        if (!$entriesItem.length) {
            return;
        }

        var $formKitItem = jQuery('.wp-submenu a[href="admin.php?page=cool-formkit"]').closest('li');

        if (!$formKitItem.length) {
            return;
        }

        var $entriesClone = $entriesItem.clone();
        $entriesItem.remove();

        $formKitItem.after($entriesClone);

        var $link = jQuery('.wp-submenu a[href="admin.php?page=formsdb"]');
        $link.prepend('↳ ').css({
            'padding-left': '10px',
            'font-style': 'italic',
            'opacity': '0.85'
        });
    }

    // setTimeout(() => {
    //     handleFormsDBSubmenu();
    // }, 500)

    function handleTermLink() {
        const termsLinks = document.querySelectorAll('.ccpw-see-terms');
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

    // Handle Create Spreadsheet Now button in Elementor editor
    if (typeof elementor !== 'undefined') {
        // Listen for the custom event triggered by the button
        elementor.channels.editor.on('fdbgp:create_spreadsheet', function (controlView) {
            var model = controlView.container.settings;

            // Get form settings
            var spreadsheetName = model.get('fdbgp_new_spreadsheet_name');
            var sheetName = model.get('fdbgp_sheet_name');
            var sheetHeaders = model.get('fdbgp_sheet_headers');

            // Validate
            if (!spreadsheetName || spreadsheetName.trim() === '') {
                alert('Please enter a Spreadsheet Name');
                return;
            }

            if (!sheetName || sheetName.trim() === '') {
                alert('Please enter a Sheet Name');
                return;
            }

            // Show loading notification
            elementorCommon.dialogsManager.createWidget('lightbox', {
                id: 'fdbgp-creating-spreadsheet',
                headerMessage: 'Creating Spreadsheet',
                message: 'Please wait while we create your spreadsheet...',
                hide: {
                    onBackgroundClick: false,
                    onEscKeyPress: false
                }
            }).show();

            // Make AJAX request
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fdbgp_create_spreadsheet',
                    _nonce: elementorCommon.config.ajax.nonce,
                    spreadsheet_name: spreadsheetName,
                    sheet_name: sheetName,
                    headers: sheetHeaders || []
                },
                success: function (response) {
                    // Close loading dialog
                    elementorCommon.dialogsManager.getWidgetById('fdbgp-creating-spreadsheet').destroy();

                    if (response.success) {
                        // Update the spreadsheet ID in the form settings
                        model.set('fdbgp_spreadsheetid', response.data.spreadsheet_id);

                        // Show success message
                        elementorCommon.dialogsManager.createWidget('confirm', {
                            headerMessage: 'Success!',
                            message: response.data.message + '\n\nThe form settings have been updated with the new spreadsheet.',
                            strings: {
                                confirm: 'OK'
                            },
                            onConfirm: function () {
                                // Refresh the panel to show updated dropdown
                                elementor.getPanelView().getCurrentPageView().render();
                            }
                        }).show();
                    } else {
                        elementorCommon.dialogsManager.createWidget('alert', {
                            headerMessage: 'Error',
                            message: response.data.message
                        }).show();
                    }
                },
                error: function () {
                    elementorCommon.dialogsManager.getWidgetById('fdbgp-creating-spreadsheet').destroy();
                    elementorCommon.dialogsManager.createWidget('alert', {
                        headerMessage: 'Error',
                        message: 'Failed to create spreadsheet. Please try again.'
                    }).show();
                }
            });
        });
    }

})