(function ($) {
    // Expose function to global scope so onclick works
    window.fdbgpUpdateSheetHeaders = function () {
        var btn = event.target.closest("button");
        var $btn = jQuery(btn);
        var $text = $btn.find(".elementor-button-text");
        var originalText = $text.text();
        var $message = jQuery("#fdbgp-update-message");

        $message.hide();

        var settings = {};
        try {
            var $panel = jQuery(btn).closest(".elementor-panel");
            $panel.find("input, select, textarea").each(function () {
                var $input = jQuery(this);
                var name = $input.attr("data-setting");
                if (name) {
                    settings[name] = $input.val();
                }
            });

            var spreadsheetId = settings["fdbgp_spreadsheetid"] || "";
            var sheetName = settings["fdbgp_sheet_list"] || "";
            var newSheetName = settings["fdbgp_new_sheet_tab_name"] || "";
            var sheetHeaders = settings["fdbgp_sheet_headers"] || [];

        } catch (e) {
            console.log(e);
            return;
        }

        if (!spreadsheetId || spreadsheetId === 'new') {
            $message.css({
                "background-color": "#fff3cd",
                "color": "#856404",
                "border": "1px solid #ffeaa7"
            }).html("⚠️ Please select a Spreadsheet first").show();
            return;
        }

        if (sheetName === 'create_new_tab' && !newSheetName) {
            $message.css({
                "background-color": "#fff3cd",
                "color": "#856404",
                "border": "1px solid #ffeaa7"
            }).html("⚠️ Please enter a New Sheet Name").show();
            return;
        }

        $btn.prop("disabled", true);
        $text.text("Updating...");

        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "fdbgp_update_sheet_headers",
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_id: spreadsheetId,
                sheet_name: sheetName,
                new_sheet_name: newSheetName,
                headers: sheetHeaders
            },
            success: function (response) {
                if (response.success) {
                    $message.css({
                        "background-color": "#d4edda",
                        "color": "#155724",
                        "border": "1px solid #c3e6cb"
                    }).html("✅ " + response.data.message).show();

                    // If created new tab, trigger refresh of list?
                    // Ideally we should switch the dropdown to the new sheet name
                    if (sheetName === 'create_new_tab' && response.data.sheet_name) {
                        // Trigger change on spreadsheet dropdown to refresh list
                        var $sId = $panel.find("[data-setting='fdbgp_spreadsheetid']");
                        // We can't easily auto-select the new one because it's async reload.
                        // But we can suggest user to refresh or just reload the list.
                        $sId.trigger('change');
                    }

                } else {
                    $message.css({
                        "background-color": "#f8d7da",
                        "color": "#721c24",
                        "border": "1px solid #f5c6cb"
                    }).html("❌ " + (response.data.message || "Unknown error")).show();
                }
            },
            error: function () {
                $message.css({
                    "background-color": "#f8d7da",
                    "color": "#721c24",
                    "border": "1px solid #f5c6cb"
                }).html("❌ Server error").show();
            },
            complete: function () {
                $btn.prop("disabled", false);
                $text.text(originalText);
            }
        });
    };

    // Expose function to global scope so onclick works
    window.fdbgpCreateSpreadsheet = function () {
        var btn = event.target.closest("button");
        var $btn = jQuery(btn);
        var $text = $btn.find(".elementor-button-text");
        var originalText = $text.text();
        var $message = jQuery("#fdbgp-message");

        // Hide any previous messages
        $message.hide();

        // Get settings directly from the panel
        var settings = {};
        try {
            var $panel = jQuery(btn).closest(".elementor-panel");
            $panel.find("input, select, textarea").each(function () {
                var $input = jQuery(this);
                var name = $input.attr("data-setting");
                if (name) {
                    settings[name] = $input.val();
                }
            });

            var spreadsheetName = settings["fdbgp_new_spreadsheet_name"] || "";
            var sheetName = settings["fdbgp_sheet_name"] || "";
            var sheetHeaders = settings["fdbgp_sheet_headers"] || [];
        } catch (e) {
            $message.css({
                "background-color": "#f8d7da",
                "color": "#721c24",
                "border": "1px solid #f5c6cb"
            }).html("Error getting form settings: " + e.message).show();
            return;
        }

        if (!spreadsheetName || !spreadsheetName.trim()) {
            $message.css({
                "background-color": "#fff3cd",
                "color": "#856404",
                "border": "1px solid #ffeaa7"
            }).html("⚠️ Please enter a Spreadsheet Name").show();
            return;
        }
        if (!sheetName || !sheetName.trim()) {
            $message.css({
                "background-color": "#fff3cd",
                "color": "#856404",
                "border": "1px solid #ffeaa7"
            }).html("⚠️ Please enter a Sheet Name").show();
            return;
        }

        if (!sheetHeaders || sheetHeaders.length === 0) {
            $message.css({
                "background-color": "#fff3cd",
                "color": "#856404",
                "border": "1px solid #ffeaa7"
            }).html("⚠️ Please select at least one Sheet Header").show();
            return;
        }

        $btn.prop("disabled", true);
        $text.text("Creating...");

        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "fdbgp_create_spreadsheet",
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_name: spreadsheetName,
                sheet_name: sheetName,
                headers: sheetHeaders
            },
            success: function (response) {
                if (response.success) {
                    // Update the spreadsheet dropdown value
                    var $spreadsheetSelect = jQuery("[data-setting=\"fdbgp_spreadsheetid\"]");
                    if ($spreadsheetSelect.length) {
                        if ($spreadsheetSelect.find("option[value=\"" + response.data.spreadsheet_id + "\"]").length === 0) {
                            // Remove "Create New Spreadsheet" option temporarily
                            var $newOption = $spreadsheetSelect.find("option[value=\'new\']");
                            $newOption.remove();

                            // Add the new spreadsheet
                            var newOpt = document.createElement("option");
                            newOpt.value = response.data.spreadsheet_id;
                            newOpt.text = response.data.spreadsheet_name;
                            $spreadsheetSelect.append(newOpt);

                            // Re-add "Create New Spreadsheet" at the end
                            $spreadsheetSelect.append($newOption);
                        }
                        $spreadsheetSelect.val(response.data.spreadsheet_id).change();
                    }

                    $message.css({
                        "background-color": "#d4edda",
                        "color": "#155724",
                        "border": "1px solid #c3e6cb"
                    }).html("✅ " + response.data.message).show();
                } else {
                    $message.css({
                        "background-color": "#f8d7da",
                        "color": "#721c24",
                        "border": "1px solid #f5c6cb"
                    }).html("❌ " + (response.data.message || "Unknown error")).show();
                }
            },
            error: function () {
                $message.css({
                    "background-color": "#f8d7da",
                    "color": "#721c24",
                    "border": "1px solid #f5c6cb"
                }).html("❌ Server error").show();
            },
            complete: function () {
                $btn.prop("disabled", false);
                $text.text(originalText);
            }
        });
    };

    jQuery(document).ready(function ($) {
        $(document).on('change', '.elementor-control-fdbgp_spreadsheetid select', function () {
            var spreadsheetId = $(this).val();
            var $panel = $(this).closest('.elementor-panel');
            var $sheetSelect = $panel.find('.elementor-control-fdbgp_sheet_list select');

            if (!spreadsheetId || spreadsheetId === 'new') return;

            $sheetSelect.html('<option>Loading...</option>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fdbgp_get_sheets',
                    _nonce: elementorCommon.config.ajax.nonce,
                    spreadsheet_id: spreadsheetId
                },
                success: function (response) {
                    if (response.success && response.data.sheets) {
                        $sheetSelect.empty();
                        $.each(response.data.sheets, function (key, text) {
                            $sheetSelect.append(new Option(text, key));
                        });
                        $sheetSelect.trigger('change');
                    } else {
                        $sheetSelect.html('<option>No sheets found</option>');
                    }
                },
                error: function () {
                    $sheetSelect.html('<option>Error loading sheets</option>');
                }
            });
        });
    });

    // Wait for Elementor to fully initialize
    $(window).on('load', function () {

        // Define the Custom Control Logic (Backbone.js)
        var FDBGP_DynamicSelect2 = elementor.modules.controls.BaseData.extend({

            onReady: function () {
                var self = this;

                // 1. Initialize Select2
                if (this.ui.select.length > 0) {
                    this.ui.select.select2({
                        placeholder: 'Select fields',
                        allowClear: true,
                        width: '100%'
                    });
                }

                // 2. Load initial options
                this.updateOptions();

                // 3. Listen for changes in the 'form_fields' repeater
                if (this.container.settings.has('form_fields')) {
                    this.listenTo(this.container.settings.get('form_fields'), 'add remove change', this.updateOptions);
                }
            },

            updateOptions: function () {
                var self = this;
                var formFields = this.container.settings.get('form_fields');
                var $select = this.ui.select;
                var currentVal = self.getControlValue();

                $select.empty();

                if (formFields) {
                    formFields.each(function (model) {
                        var id = model.get('custom_id');
                        var label = model.get('field_label');
                        if (!label) label = id;

                        var text = label + ' (' + id + ')';
                        $select.append(new Option(text, id, false, false));
                    });
                }

                if (currentVal) {
                    $select.val(currentVal);
                }

                $select.trigger('change');
            },

            onBeforeDestroy: function () {
                if (this.ui.select.data('select2')) {
                    this.ui.select.select2('destroy');
                }
            }
        });

        // Register the View so Elementor can render our custom control
        elementor.addControlView('fdbgp_dynamic_select2', FDBGP_DynamicSelect2);
    });
})(jQuery);