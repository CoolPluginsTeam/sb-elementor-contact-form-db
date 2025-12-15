(function ($) {
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
                        $.each(response.data.sheets, function (index, sheetName) {
                            $sheetSelect.append(new Option(sheetName, sheetName));
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

    // Scrape Form Fields from the Elementor Preview HTML (Real-time)
    // function fdbgpPopulateHeaders() {
    //     try {
    //         var $preview = window.elementor && window.elementor.$previewContents;
    //         if (!$preview) return;

    //         // Find the currently active form wrapper in preview
    //         // We try to find the one associated with the currently open panel
    //         // Best guess: The one with class .elementor-element-editable
    //         var $formWrapper = $preview.find(".elementor-element-editable .elementor-form-fields-wrapper");

    //         // Fallback: If not found (maybe not active yet), find ANY form wrapper if only one exists
    //         if ($formWrapper.length === 0) {
    //             $formWrapper = $preview.find(".elementor-form-fields-wrapper").first();
    //         }

    //         if ($formWrapper.length === 0) return;

    //         var fields = [];
    //         $formWrapper.find(".elementor-field-group").each(function () {
    //             var $group = jQuery(this);
    //             var $label = $group.find(".elementor-field-label");
    //             var $input = $group.find("input, textarea, select");

    //             if ($input.length === 0) return;

    //             var labelText = $label.text().trim();
    //             var inputName = $input.attr("name");
    //             var placeholder = $input.attr("placeholder");

    //             if (!labelText && placeholder) labelText = placeholder;
    //             if (!labelText) labelText = "Field " + (fields.length + 1);

    //             // Extract ID from name
    //             var id = inputName;
    //             if (id) {
    //                 if (id.indexOf("form_field_") === 0) {
    //                     id = id.replace("form_field_", "");
    //                 }
    //                 // Handle array inputs like form_fields[email]
    //                 if (id.indexOf("[") > -1) {
    //                     var matches = id.match(/\[(.*?)\]/);
    //                     if (matches) id = matches[1];
    //                 }
    //             }
    //             if (id) {
    //                 fields.push({ id: id, text: labelText });
    //             }
    //         });

    //         if (fields.length > 0) {
    //             var $headerControl = jQuery(".elementor-control-fdbgp_sheet_headers select");
    //             if ($headerControl.length > 0) {
    //                 // Keep existing selections
    //                 var currentVal = $headerControl.val() || [];

    //                 // Don't fully clear, just append new ones if missing
    //                 // Actually, Elementor rerenders the control on save, but for instant feedback we append options
    //                 var existingIds = [];
    //                 $headerControl.find("option").each(function () {
    //                     existingIds.push(jQuery(this).val());
    //                 });

    //                 var hasNew = false;
    //                 fields.forEach(function (f) {
    //                     if (existingIds.indexOf(f.id) === -1) {
    //                         var newOption = new Option(f.text, f.id, false, false);
    //                         $headerControl.append(newOption);
    //                         hasNew = true;
    //                     }
    //                 });

    //                 if (hasNew) {
    //                     $headerControl.trigger("change.select2");
    //                     // Add a small notification
    //                     var $status = $headerControl.closest(".elementor-control").find(".fdbgp-status");
    //                     if ($status.length === 0) {
    //                         $headerControl.closest(".elementor-control").append("<div class='fdbgp-status' style='font-size:10px; color:green; margin-top:5px;'></div>");
    //                         $status = $headerControl.closest(".elementor-control").find(".fdbgp-status");
    //                     }
    //                     $status.text("Fields synced from preview.").fadeOut(3000);
    //                 }

    //                 // Auto-Migrate Legacy Numeric Selections
    //                 var legacyUpdates = false;
    //                 var currentVal = $headerControl.val() || [];
    //                 if (!Array.isArray(currentVal)) currentVal = [currentVal];

    //                 var newVal = currentVal.slice();
    //                 var madeChanges = false;

    //                 for (var i = 0; i < currentVal.length; i++) {
    //                     var val = currentVal[i];
    //                     // Check if numeric (legacy) and simple integer
    //                     if (val == parseInt(val, 10)) {
    //                         var idx = parseInt(val, 10);
    //                         // Check if fields exist at this index
    //                         if (fields[idx]) {
    //                             var startId = fields[idx].id;

    //                             // If the NEW ID option exists
    //                             if ($headerControl.find("option[value='" + startId + "']").length > 0) {
    //                                 // Remove legacy from value list
    //                                 var removeIdx = newVal.indexOf(val);
    //                                 if (removeIdx > -1) {
    //                                     newVal.splice(removeIdx, 1);
    //                                     madeChanges = true;
    //                                 }

    //                                 // Add new ID to value list (if not present)
    //                                 if (newVal.indexOf(startId) === -1) {
    //                                     newVal.push(startId);
    //                                     madeChanges = true;
    //                                 }

    //                                 // Safely remove the legacy option Element
    //                                 // We do this carefully to avoid breaking Select2
    //                                 var $legacyOpt = $headerControl.find("option[value='" + val + "']");
    //                                 if ($legacyOpt.length > 0) {
    //                                     $legacyOpt.remove();
    //                                     legacyUpdates = true; // Mark as having done a DOM change
    //                                     console.log("[FDBGP] Migrated legacy header " + val + " to " + startId);
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }

    //                 if (legacyUpdates || madeChanges) {
    //                     // Update Select2 value
    //                     $headerControl.val(newVal).trigger("change");
    //                     $headerControl.trigger("change.select2");

    //                     var $status = $headerControl.closest(".elementor-control").find(".fdbgp-status");
    //                     if ($status.length > 0) {
    //                         $status.text("Legacy headers migrated. Saved.").show().delay(3000).fadeOut();
    //                     }
    //                 }
    //             }
    //         }
    //     } catch (e) {
    //         console.log("FDBGP Error scraping fields: " + e.message);
    //     }

    //     // Reset flag after small delay
    //     setTimeout(function () { window.fdbgpScanning = false; }, 1000);
    // }
    // Wait for Elementor to fully initialize
    $(window).on('elementor:init', function () {

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