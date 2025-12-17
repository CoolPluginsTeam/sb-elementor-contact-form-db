(function ($) {
    /**
     * Function to Update Sheet Headers
     * Triggered by button click in Elementor Panel.
     * Exposes function to global scope so it can be called via onclick or JS.
     */
    window.fdbgpUpdateSheetHeaders = function (confirmOverwrite, btnContext) {
        // Determine which button triggered the event
        var btn = btnContext || (window.event && window.event.target ? window.event.target.closest("button") : null);
        if (!btn) return;

        var $btn = jQuery(btn);
        var $text = $btn.find(".elementor-button-text");

        // Store original text to restore later
        var originalText = $btn.data("original-text") || $text.text();
        if (!$btn.data("original-text")) $btn.data("original-text", originalText);

        var $message = jQuery("#fdbgp-update-message");
        // $message.hide();

        // Gather settings from the Elementor Panel inputs
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

        // Validation Checks
        if (!spreadsheetId || spreadsheetId === 'new') {
            $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html(" Please select a Spreadsheet first").show();
            return;
        }

        if (sheetName === 'create_new_tab' && !newSheetName) {
            $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html(" Please enter a New Sheet Tab Name").show();
            return;
        }

        // Lock UI
        $btn.prop("disabled", true);
        $text.text("Updating...");

        // Perform AJAX Update
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "fdbgp_update_sheet_headers",
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_id: spreadsheetId,
                sheet_name: sheetName,
                new_sheet_name: newSheetName,
                headers: sheetHeaders,
                confirm_overwrite: confirmOverwrite === true ? 'true' : 'false'
            },
            success: function (response) {
                if (response.success) {
                    // Handle recursive confirmation (if file exists/needs overwrite)
                    if (response.data.confirm_needed) {
                        if (confirm(response.data.message)) {
                            fdbgpUpdateSheetHeaders(true, btn);
                            return;
                        } else {
                            $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html("Cancelled.").show();
                        }
                    } else {
                        // Success Message
                        $message.removeClass("elementor-panel-alert-danger").addClass("elementor-panel-alert-success");
                        $message.html(response.data.message).show().delay(5000).fadeOut();

                        // If a new tab was created, reload the sheet list dropdown
                        if (sheetName === 'create_new_tab' && response.data.sheet_name) {
                            var $sheetSelect = $panel.find("[data-setting='fdbgp_sheet_list']");
                            $sheetSelect.html('<option>Loading...</option>');

                            jQuery.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'fdbgp_get_sheets',
                                    _nonce: elementorCommon.config.ajax.nonce,
                                    spreadsheet_id: spreadsheetId
                                },
                                success: function (res) {
                                    if (res.success && res.data.sheets) {
                                        $sheetSelect.empty();
                                        jQuery.each(res.data.sheets, function (key, text) {
                                            $sheetSelect.append(new Option(text, key));
                                        });
                                        // Select the newly created sheet
                                        $sheetSelect.val(response.data.sheet_name).trigger('change');
                                    }
                                }
                            });
                        }
                    }
                } else {
                    $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger");
                    $message.html(response.data.message || "Unknown error").show();
                }
            },
            error: function () {
                $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger");
                $message.html("❌ Server error").show();
            },
            complete: function () {
                $btn.prop("disabled", false);
                $text.text(originalText);
            }
        });
    };

    /**
     * Function to Create a New Spreadsheet
     * Exposes function to global scope.
     */
    window.fdbgpCreateSpreadsheet = function (btnContext) {
        var btn = btnContext || (window.event && window.event.target ? window.event.target.closest("button") : null);
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
            $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html("Error getting form settings: " + e.message).show();
            return;
        }

        // Validation
        if (!spreadsheetName || !spreadsheetName.trim()) {
            $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html("Please enter a Spreadsheet Name").show();
            return;
        }
        if (!sheetName || !sheetName.trim()) {
            $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html("Please enter a Sheet Tab Name").show();
            return;
        }
        if (!sheetHeaders || sheetHeaders.length === 0) {
            $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html("Please select at least one Sheet Header").show();
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
                    $message.removeClass("elementor-panel-alert-danger").addClass("elementor-panel-alert-success").html(response.data.message).show();

                    // Delay updating the UI so the message is visible
                    setTimeout(function () {
                        // Update the spreadsheet dropdown value dynamically
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

                            // Auto-select the newly created sheet in the sheet list
                            if (response.data.sheet_name) {
                                var $sheetSelect = jQuery("[data-setting='fdbgp_sheet_list']");
                                $sheetSelect.data('auto-select', response.data.sheet_name);
                            }

                            $spreadsheetSelect.val(response.data.spreadsheet_id).change();
                        }
                    }, 2000);
                } else {
                    $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html("❌ " + (response.data.message || "Unknown error")).show();
                }
            },
            error: function () {
                $message.removeClass("elementor-panel-alert-success").addClass("elementor-panel-alert-danger").html("❌ Server error").show();
            },
            complete: function () {
                $btn.prop("disabled", false);
                $text.text(originalText);
            }
        });
    };

    jQuery(document).ready(function ($) {
        // Expose function to check if a sheet has content (prevents accidental overwrites)
        window.fdbgpCheckSheetContent = function ($sheetSelect) {
            var sheetName = $sheetSelect.val();
            var $panel = $sheetSelect.closest('.elementor-panel');
            var spreadsheetId = $panel.find(".elementor-control-fdbgp_spreadsheetid select").val();

            var $message = $panel.find("#fdbgp-update-message");
            // $message.hide();

            if (!sheetName || sheetName === 'create_new_tab' || !spreadsheetId || spreadsheetId === 'new') {
                return;
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fdbgp_check_sheet_headers',
                    _nonce: elementorCommon.config.ajax.nonce,
                    spreadsheet_id: spreadsheetId,
                    sheet_name: sheetName
                },
                success: function (response) {
                    if (response.success && response.data.has_content) {
                        $message.css({
                            "background-color": "",
                            "color": "",
                            "border": ""
                        }).removeClass("elementor-panel-alert-danger elementor-panel-alert-success").addClass("elementor-panel-alert-info");
                        // Stop any ongoing animations (e.g., fadeOut from success message) and show permanently
                        $message.stop(true, true).css('opacity', '1').html(response.data.message || "Selected sheet is not empty. Backup recommended before updating.").show();
                    }
                }
            });
        };

        // --- Event Bindings ---

        // Attach Click Event for Create Spreadsheet Button
        $(document).on('click', '.fdbgp-create-spreadsheet', function (e) {
            e.preventDefault();
            fdbgpCreateSpreadsheet(this);
        });

        // Attach Click Event for Update Sheet Button
        $(document).on('click', '.fdbgp-update-sheet', function (e) {
            e.preventDefault();
            fdbgpUpdateSheetHeaders(false, this);
        });

        // Check Sheet Content on Selection Change & Handle Panel State
        $(document).on('change', '.elementor-control-fdbgp_sheet_list select', function () {
            var sheetValue = $(this).val();

            // Logic to persist state when Elementor redraws panel
            if (sheetValue && sheetValue !== '' && sheetValue !== 'create_new_tab') {
                try {
                    var panel = elementor.getPanelView();
                    if (panel && panel.getCurrentPageView && panel.getCurrentPageView()) {
                        var widgetId = panel.getCurrentPageView().model.get('id');
                        if (widgetId) {
                            if (!window.fdbgpWidgetState) window.fdbgpWidgetState = {};
                            if (!window.fdbgpWidgetState[widgetId]) window.fdbgpWidgetState[widgetId] = {};
                            window.fdbgpWidgetState[widgetId].sheet = sheetValue;
                        }
                    }
                } catch (e) { }
            } else if (sheetValue === 'create_new_tab') {
                // Clear saved state when user selects "Create New Tab"
                try {
                    var panel = elementor.getPanelView();
                    if (panel && panel.getCurrentPageView && panel.getCurrentPageView()) {
                        var widgetId = panel.getCurrentPageView().model.get('id');
                        if (widgetId && window.fdbgpWidgetState && window.fdbgpWidgetState[widgetId]) {
                            delete window.fdbgpWidgetState[widgetId].sheet;
                        }
                    }
                } catch (e) { }
            }
            window.fdbgpCheckSheetContent($(this));
        });

        // Restore sheet selection on panel changes (Elementor Hook listener)
        elementor.channels.editor.on('change', function () {
            setTimeout(function () {
                var $sheetSelect = $('.elementor-control-fdbgp_sheet_list select:visible');
                if ($sheetSelect.length === 0) return;

                try {
                    var panel = elementor.getPanelView();
                    if (panel && panel.getCurrentPageView && panel.getCurrentPageView()) {
                        var widgetId = panel.getCurrentPageView().model.get('id');
                        if (widgetId && window.fdbgpWidgetState && window.fdbgpWidgetState[widgetId] && window.fdbgpWidgetState[widgetId].sheet) {
                            var savedSheet = window.fdbgpWidgetState[widgetId].sheet;
                            var currentSheet = $sheetSelect.val();

                            if (!currentSheet || currentSheet === '' || currentSheet !== savedSheet) {
                                var optionExists = $sheetSelect.find('option[value="' + savedSheet + '"]').length > 0;
                                var $spreadsheetSelect = $('.elementor-control-fdbgp_spreadsheetid select:visible');

                                // If option doesn't exist yet, trigger spreadsheet change to load it
                                if (!optionExists && $spreadsheetSelect.length && $spreadsheetSelect.val()) {
                                    $sheetSelect.data('auto-select', savedSheet);
                                    $spreadsheetSelect.trigger('change');
                                } else if (optionExists) {
                                    $sheetSelect.val(savedSheet);
                                }
                            }
                        }
                    }
                } catch (e) { }
            }, 300);
        });

        // Clear sheet state when spreadsheet changes
        $(document).on('change', '.elementor-control-fdbgp_spreadsheetid select', function () {
            try {
                var panel = elementor.getPanelView();
                if (panel && panel.getCurrentPageView && panel.getCurrentPageView()) {
                    var widgetId = panel.getCurrentPageView().model.get('id');
                    if (widgetId && window.fdbgpWidgetState && window.fdbgpWidgetState[widgetId]) {
                        delete window.fdbgpWidgetState[widgetId].sheet;
                    }
                }
            } catch (e) { }
            window.fdbgpCheckSheetContent($(this));
        });

        // Fetch Sheets when Spreadsheet ID changes
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

                        // Handle auto-selection if triggered via state restoration
                        var autoSelect = $sheetSelect.data('auto-select');
                        if (autoSelect) {
                            $sheetSelect.val(autoSelect);
                            $sheetSelect.removeData('auto-select');
                        }

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
                var staticOptions = this.model.get('options');

                $select.empty();

                // Add fields from Repeater
                if (formFields) {
                    formFields.each(function (model) {
                        var id = model.get('custom_id');
                        var label = model.get('field_label');
                        if (!label) label = id;

                        var text = label + ' (' + id + ')';
                        $select.append(new Option(text, id, false, false));
                    });
                }

                // Add static options if any
                if (staticOptions) {
                    jQuery.each(staticOptions, function (key, label) {
                        $select.append(new Option(label, key, false, false));
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

    // Elementor Initialization Hooks
    $(window).on('elementor:init', function () {
        // Ensure sheet list loads if spreadsheet is already selected (Initial Load Fix)
        if (elementor && elementor.hooks) {
            elementor.hooks.addAction('panel/open_editor/widget', function (panel, model, view) {
                setTimeout(function () {
                    var $spreadsheetSelect = panel.$el.find("[data-setting='fdbgp_spreadsheetid']");
                    var $sheetSelect = panel.$el.find("[data-setting='fdbgp_sheet_list']");

                    if ($spreadsheetSelect.length && $spreadsheetSelect.val() && $spreadsheetSelect.val() !== 'new') {
                        // If sheet list seems unpopulated (only minimal options), trigger reload
                        if ($sheetSelect.find('option').length <= 2) {
                            $spreadsheetSelect.trigger('change');
                        } else {
                            // Otherwise check sheet content
                            if (window.fdbgpCheckSheetContent) {
                                window.fdbgpCheckSheetContent($sheetSelect);
                            }
                        }
                    }
                }, 1000); // 1 second delay to ensure complete rendering
            });
        }

        // Fallback: Polling to check if panel is already open (Page Reload)
        var fdbgpCheckInterval = setInterval(function () {
            // Find visible select controls
            var $spreadsheetSelect = jQuery(".elementor-control-fdbgp_spreadsheetid select").filter(':visible');

            if ($spreadsheetSelect.length && $spreadsheetSelect.val() && $spreadsheetSelect.val() !== 'new') {
                var $sheetSelect = jQuery(".elementor-control-fdbgp_sheet_list select").filter(':visible');

                // Need both to be present
                if ($sheetSelect.length) {
                    // If we found them, stop polling
                    clearInterval(fdbgpCheckInterval);

                    if ($sheetSelect.find('option').length <= 2) {
                        $spreadsheetSelect.trigger('change');
                    } else {
                        if (window.fdbgpCheckSheetContent) {
                            window.fdbgpCheckSheetContent($sheetSelect);
                        }
                    }
                }
            }

            // Stop polling after 20 seconds if nothing found to save resources
            if (performance.now() > 20000) clearInterval(fdbgpCheckInterval);
        }, 1000);
    });
})(jQuery);