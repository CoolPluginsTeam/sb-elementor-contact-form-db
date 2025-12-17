(function ($, window) {
    'use strict';

    class FDBGP_Editor {

        constructor() {
            this.widgetState = {};
            this.init();
        }

        init() {
            this.bindEvents();
            this.registerElementorHooks();
            this.startPollingFallback();
            this.restoreCachedSpreadsheetDelayed();
        }

        bindEvents() {

            $(document).on('click', '.fdbgp-create-spreadsheet', (e) => {
                e.preventDefault();
                this.createSpreadsheet(e.currentTarget);
            });

            $(document).on('click', '.fdbgp-update-sheet', (e) => {
                e.preventDefault();
                this.updateSheetHeaders(false, e.currentTarget);
            });

            $(document).on('change', '.elementor-control-fdbgp_sheet_list select', (e) => {
                this.onSheetChange($(e.currentTarget));
            });

            $(document).on('change', '.elementor-control-fdbgp_spreadsheetid select', (e) => {
                this.onSpreadsheetChange($(e.currentTarget));
            });

            $(document).on('change', "[data-setting='fdbgp_spreadsheetid']", (e) => {
                this.cacheSpreadsheetSelection($(e.currentTarget));
            });

            $(document).on('change', "[data-setting='fdbgp_sheet_list']", (e) => {
                this.cacheSheetSelection($(e.currentTarget));
            });
        }

        collectSettings($panel) {
            const settings = {};
            $panel.find('input, select, textarea').each(function () {
                const key = $(this).data('setting');
                if (key) settings[key] = $(this).val();
            });

            return {
                spreadsheetId: settings.fdbgp_spreadsheetid || '',
                sheetName: settings.fdbgp_sheet_list || '',
                newSheetName: settings.fdbgp_new_sheet_tab_name || '',
                spreadsheetName: settings.fdbgp_new_spreadsheet_name || '',
                sheetHeaders: settings.fdbgp_sheet_headers || []
            };
        }

        showError($el, msg) {
            $el
                .stop(true, true)
                .removeClass('elementor-panel-alert-success')
                .addClass('elementor-panel-alert-danger')
                .css({ opacity: 1 })
                .html(msg)
                .show();
        }

        showSuccess($el, msg) {
            $el
                .removeClass('elementor-panel-alert-danger')
                .addClass('elementor-panel-alert-success')
                .html(msg)
                .show()
                .delay(10000)
                .fadeOut();
        }

        updateSheetHeaders(confirmOverwrite = false, btn) {

            if (!btn) return;

            const $btn = $(btn);
            const $panel = $btn.closest('.elementor-panel');
            const $text = $btn.find('.elementor-button-text');
            const $message = $panel.find('#fdbgp-update-message');

            const originalText = $btn.data('original-text') || $text.text();
            $btn.data('original-text', originalText);

            const settings = this.collectSettings($panel);

            if (!settings.spreadsheetId || settings.spreadsheetId === 'new') {
                return this.showError($message, 'Please select a Spreadsheet first');
            }

            if (settings.sheetName === 'create_new_tab' && !settings.newSheetName) {
                return this.showError($message, 'Please enter a New Sheet Tab Name');
            }

            $btn.prop('disabled', true);
            $text.text('Updating...');

            $.post(ajaxurl, {
                action: 'fdbgp_update_sheet_headers',
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_id: settings.spreadsheetId,
                sheet_name: settings.sheetName,
                new_sheet_name: settings.newSheetName,
                headers: settings.sheetHeaders,
                confirm_overwrite: confirmOverwrite ? 'true' : 'false'
            }).done((response) => {

                if (response.success && response.data.confirm_needed) {
                    if (confirm(response.data.message)) {
                        this.updateSheetHeaders(true, btn);
                    }
                    return;
                }

                if (response.success) {
                    this.showSuccess($message, response.data.message);
                    if (settings.sheetName === 'create_new_tab') {
                        this.reloadSheetList($panel, settings.spreadsheetId, response.data.sheet_name);
                    }
                } else {
                    this.showError($message, response.data.message || 'Unknown error');
                }

            }).fail(() => {
                this.showError($message, 'Server error');
            }).always(() => {
                $btn.prop('disabled', false);
                $text.text(originalText);
            });
        }

        createSpreadsheet(btn) {

            const $btn = $(btn);
            const $panel = $btn.closest('.elementor-panel');
            const $text = $btn.find('.elementor-button-text');
            const $message = $panel.find('#fdbgp-message');

            const settings = this.collectSettings($panel);
            const originalText = $text.text();

            if (!settings.spreadsheetName) {
                return this.showError($message, 'Please enter a Spreadsheet Name');
            }

            if (!settings.sheetName) {
                return this.showError($message, 'Please enter a Sheet Tab Name');
            }

            if (!settings.sheetHeaders.length) {
                return this.showError($message, 'Please select at least one Sheet Header');
            }

            $btn.prop('disabled', true);
            $text.text('Creating...');

            $.post(ajaxurl, {
                action: 'fdbgp_create_spreadsheet',
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_name: settings.spreadsheetName,
                sheet_name: settings.sheetName,
                headers: settings.sheetHeaders
            }).done((response) => {

                if (response.success) {
                    this.cacheCreatedSpreadsheet(response.data);
                    this.showSuccess($message, response.data.message || 'Spreadsheet created');
                } else {
                    this.showError($message, response.data.message || 'Unknown error');
                }

            }).fail(() => {
                this.showError($message, 'Server error');
            }).always(() => {
                $btn.prop('disabled', false);
                $text.text(originalText);
            });
        }

        checkSheetContent($sheetSelect) {

            const sheetName = $sheetSelect.val();
            const $panel = $sheetSelect.closest('.elementor-panel');
            const spreadsheetId = $panel.find("[data-setting='fdbgp_spreadsheetid']").val();
            const $message = $panel.find('#fdbgp-update-message');

            if (!sheetName || sheetName === 'create_new_tab' || !spreadsheetId || spreadsheetId === 'new') {
                return $message.hide();
            }

            $.post(ajaxurl, {
                action: 'fdbgp_check_sheet_headers',
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_id: spreadsheetId,
                sheet_name: sheetName
            }).done((response) => {
                if (response.success && response.data.has_content) {
                    this.showError($message, response.data.message);
                } else {
                    $message.hide();
                }
            });
        }

        onSheetChange($select) {
            this.saveWidgetState($select.val());
            this.checkSheetContent($select);
        }

        onSpreadsheetChange($select) {
            this.clearWidgetState();
            this.loadSheets($select);
        }

        loadSheets($spreadsheetSelect) {

            const spreadsheetId = $spreadsheetSelect.val();
            if (!spreadsheetId || spreadsheetId === 'new') return;

            const $panel = $spreadsheetSelect.closest('.elementor-panel');
            const $sheetSelect = $panel.find("[data-setting='fdbgp_sheet_list']");

            $sheetSelect.html('<option>Loading...</option>');

            $.post(ajaxurl, {
                action: 'fdbgp_get_sheets',
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_id: spreadsheetId
            }).done((response) => {

                if (!response.success) return;

                $sheetSelect.empty();
                $.each(response.data.sheets, function (key, val) {
                    $sheetSelect.append(new Option(val, key));
                });

                const autoSelect = $sheetSelect.data('auto-select');
                if (autoSelect) {
                    $sheetSelect.val(autoSelect);
                    $sheetSelect.removeData('auto-select');
                }

                $sheetSelect.trigger('change');
            });
        }

        reloadSheetList($panel, spreadsheetId, newSheet) {
            const $spreadsheet = $panel.find("[data-setting='fdbgp_spreadsheetid']");
            const $sheet = $panel.find("[data-setting='fdbgp_sheet_list']");
            $sheet.data('auto-select', newSheet);
            $spreadsheet.val(spreadsheetId).trigger('change');
        }

        cacheCreatedSpreadsheet(data) {
            const postId = elementor.config.document.id || 'default';
            localStorage.setItem('fdbgp_cached_spreadsheet_' + postId, JSON.stringify(data));
        }

        cacheSpreadsheetSelection($select) {
            const val = $select.val();
            if (!val || val === 'new') return;

            const postId = elementor.config.document.id || 'default';
            localStorage.setItem('fdbgp_cached_spreadsheet_' + postId, JSON.stringify({
                id: val,
                name: $select.find('option:selected').text(),
                sheet_name: ''
            }));
        }

        cacheSheetSelection($select) {
            const sheet = $select.val();
            if (!sheet || sheet === 'create_new_tab') return;

            const postId = elementor.config.document.id || 'default';
            const key = 'fdbgp_cached_spreadsheet_' + postId;
            const cache = localStorage.getItem(key);

            if (!cache) return;
            const data = JSON.parse(cache);
            data.sheet_name = sheet;
            localStorage.setItem(key, JSON.stringify(data));
        }

        restoreCachedSpreadsheetDelayed() {
            setTimeout(() => this.restoreCachedSpreadsheet(), 2000);
        }

        restoreCachedSpreadsheet() {
            const postId = elementor.config.document.id || 'default';
            const cache = localStorage.getItem('fdbgp_cached_spreadsheet_' + postId);
            if (!cache) return;

            const data = JSON.parse(cache);
            const $spreadsheet = $("[data-setting='fdbgp_spreadsheetid']");
            if ($spreadsheet.val()) return;

            if (!$spreadsheet.find(`option[value="${data.id}"]`).length) {
                const $new = $spreadsheet.find('option[value="new"]').remove();
                $spreadsheet.append(new Option(data.name, data.id)).append($new);
            }

            $spreadsheet.val(data.id).trigger('change');
        }

        saveWidgetState(sheet) {
            try {
                const panel = elementor.getPanelView();
                const id = panel.getCurrentPageView().model.get('id');
                this.widgetState[id] = sheet;
            } catch (e) { }
        }

        clearWidgetState() {
            this.widgetState = {};
        }

        registerElementorHooks() {

            elementor.hooks.addAction('panel/open_editor/widget', (panel) => {
                setTimeout(() => {
                    const $spreadsheet = panel.$el.find("[data-setting='fdbgp_spreadsheetid']");
                    const $sheetSelect = panel.$el.find("[data-setting='fdbgp_sheet_list']");

                    if ($spreadsheet.val() && $spreadsheet.val() !== 'new') {
                        // Fix for initial load if sheet list is empty but spreadsheet is selected
                        if ($sheetSelect.find('option').length <= 2) {
                            $spreadsheet.trigger('change');
                        } else {
                            this.checkSheetContent($sheetSelect);
                        }
                    }
                }, 1000);
            });

            // Restore sheet selection on panel changes (Elementor Hook listener)
            // This ensures the custom logic persists even when Elementor redraws the panel
            if (elementor.channels && elementor.channels.editor) {
                elementor.channels.editor.on('change', () => {
                    setTimeout(() => {
                        const $sheetSelect = $('.elementor-control-fdbgp_sheet_list select:visible');
                        if ($sheetSelect.length === 0) return;

                        try {
                            const panel = elementor.getPanelView();
                            if (panel && panel.getCurrentPageView && panel.getCurrentPageView()) {
                                const widgetId = panel.getCurrentPageView().model.get('id');
                                if (widgetId && this.widgetState[widgetId]) {
                                    const savedSheet = this.widgetState[widgetId];
                                    const currentSheet = $sheetSelect.val();

                                    if (!currentSheet || currentSheet === '' || currentSheet !== savedSheet) {
                                        const optionExists = $sheetSelect.find(`option[value="${savedSheet}"]`).length > 0;
                                        const $spreadsheetSelect = $('.elementor-control-fdbgp_spreadsheetid select:visible');

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
            }

            elementor.channels.data.on('document:after:save', () => {
                const postId = elementor.config.document.id || 'default';
                localStorage.removeItem('fdbgp_cached_spreadsheet_' + postId);
            });
        }

        startPollingFallback() {
            const start = performance.now();
            const interval = setInterval(() => {

                const $spreadsheet = $(".elementor-control-fdbgp_spreadsheetid select:visible");
                const $sheet = $(".elementor-control-fdbgp_sheet_list select:visible");

                if ($spreadsheet.length && $sheet.length && $spreadsheet.val()) {
                    clearInterval(interval);
                    $spreadsheet.trigger('change');
                }

                if (performance.now() - start > 20000) {
                    clearInterval(interval);
                }

            }, 1000);
        }
    }

    $(window).on('elementor:init', function () {
        window.FDBGP_Editor = new FDBGP_Editor();
    });

    $(window).on('load', function () {
        if (typeof elementor !== 'undefined' && elementor.modules && elementor.modules.controls) {

            var FDBGP_DynamicSelect2 = elementor.modules.controls.BaseData.extend({

                onReady: function () {
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
                    var formFields = this.container.settings.get('form_fields');
                    var $select = this.ui.select;
                    var currentVal = this.getControlValue();
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
                        $.each(staticOptions, function (key, label) {
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

            // Register the View
            elementor.addControlView('fdbgp_dynamic_select2', FDBGP_DynamicSelect2);
        }
    });

})(jQuery, window);
