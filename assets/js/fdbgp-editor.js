(($, window) => {
    'use strict';

    class FDBGP_Editor {
        constructor() {
            this.widgetState = {};
            this.isRestoring = false;
            this.init();
        }

        init() {
            this.bindEvents();
            this.registerElementorHooks();
            this.startPollingFallback();
            this.restoreCachedSpreadsheetDelayed();
        }

        bindEvents() {
            const $doc = $(document);

            $doc.on('click', '.fdbgp-create-spreadsheet', (e) => {
                e.preventDefault();
                this.createSpreadsheet(e.currentTarget);
            });

            $doc.on('click', '.fdbgp-update-sheet', (e) => {
                e.preventDefault();
                this.updateSheetHeaders(false, e.currentTarget);
            });

            $doc.on('change', '.elementor-control-fdbgp_sheet_list select', (e) => {
                this.onSheetChange($(e.currentTarget));
            });

            $doc.on('change', '.elementor-control-fdbgp_spreadsheetid select', (e) => {
                this.onSpreadsheetChange($(e.currentTarget));
            });

            $doc.on('change', "[data-setting='fdbgp_spreadsheetid']", (e) => {
                this.cacheSpreadsheetSelection($(e.currentTarget));
            });

            $doc.on('change', "[data-setting='fdbgp_sheet_list']", (e) => {
                this.cacheSheetSelection($(e.currentTarget));
            });
        }

        collectSettings($panel) {
            const settings = {};
            // Using arrow function, so we use the second argument 'el' instead of 'this'
            $panel.find('input, select, textarea').each((_, el) => {
                const key = $(el).data('setting');
                if (key) settings[key] = $(el).val();
            });

            return {
                spreadsheetId: settings.fdbgp_spreadsheetid || '',
                sheetList: settings.fdbgp_sheet_list || '',
                sheetName: settings.fdbgp_sheet_name || '',
                newSheetName: settings.fdbgp_new_sheet_tab_name || '',
                spreadsheetName: settings.fdbgp_new_spreadsheet_name || '',
                sheetHeaders: settings.fdbgp_sheet_headers || []
            };
        }

        showError($el, msg) {
            $el.stop(true, true)
                .removeClass('elementor-panel-alert-success')
                .addClass('elementor-panel-alert-danger')
                .css({ opacity: 1 })
                .html(msg)
                .show();
        }

        showSuccess($el, msg) {
            $el.removeClass('elementor-panel-alert-danger')
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

            if (settings.sheetList === 'create_new_tab' && !settings.newSheetName) {
                return this.showError($message, 'Please enter a New Sheet Tab Name');
            }

            $btn.prop('disabled', true);
            $text.text('Updating...');

            // Resolve headers to labels (use Label if validation, otherwise ID)
            let headersToSend = settings.sheetHeaders;
            try {
                if (typeof elementor !== 'undefined' && elementor.getPanelView) {
                    const view = elementor.getPanelView().getCurrentPageView();
                    if (view && view.model) {
                        const formFields = view.model.get('settings').get('form_fields');

                        if (headersToSend && Array.isArray(headersToSend)) {
                            headersToSend = headersToSend.map(headerId => {
                                // System fields mapping
                                const systemLabels = {
                                    'user_ip': 'User IP',
                                    'user_agent': 'User Agent',
                                    'page_url': 'Page URL',
                                    'submission_date': 'Submission Date',
                                    // 'referer_url': 'Referer URL',
                                    // 'post_id': 'Post ID'
                                };

                                if (systemLabels[headerId]) {
                                    return systemLabels[headerId];
                                }

                                // Find in form fields
                                if (formFields && formFields.findWhere) {
                                    const field = formFields.findWhere({ custom_id: headerId });
                                    if (field) {
                                        const label = field.get('field_label');
                                        return label && label.trim() !== '' ? label : headerId;
                                    }
                                }

                                return headerId;
                            });
                        }
                    }
                }
            } catch (e) {
                console.error('FDBGP: Error resolving header labels:', e);
            }

            $.post(ajaxurl, {
                action: 'fdbgp_update_sheet_headers',
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_id: settings.spreadsheetId,
                sheet_name: settings.sheetList,
                new_sheet_name: settings.newSheetName,
                headers: headersToSend,
                confirm_overwrite: confirmOverwrite ? 'true' : 'false'
            }).done((response) => {
                const { success, data } = response;

                if (success && data.confirm_needed) {
                    if (confirm(data.message)) {
                        this.updateSheetHeaders(true, btn);
                    }
                    return;
                }

                if (success) {
                    this.showSuccess($message, data.message);

                    if (settings.sheetList === 'create_new_tab' && data.sheet_name) {
                        const $sheetSelect = $panel.find("[data-setting='fdbgp_sheet_list']");
                        $sheetSelect.html('<option>Loading...</option>');

                        $.post(ajaxurl, {
                            action: 'fdbgp_get_sheets',
                            _nonce: elementorCommon.config.ajax.nonce,
                            spreadsheet_id: settings.spreadsheetId
                        }).done((res) => {
                            if (res.success && res.data.sheets) {
                                $sheetSelect.empty();
                                Object.entries(res.data.sheets).forEach(([key, text]) => {
                                    $sheetSelect.append(new Option(text, key));
                                });

                                $sheetSelect.val(data.sheet_name).trigger('change');
                                this.saveWidgetState(data.sheet_name);

                                try {
                                    const currentView = elementor.getPanelView()?.getCurrentPageView?.();
                                    if (currentView?.model?.setSetting) {
                                        currentView.model.setSetting('fdbgp_sheet_list', data.sheet_name);
                                    }
                                } catch (e) {
                                    console.error('Error updating Elementor model:', e);
                                }
                            }
                        });
                    }
                } else {
                    this.showError($message, data.message || 'Unknown error');
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

            if (!settings.spreadsheetName) return this.showError($message, 'Please enter a Spreadsheet Name');
            if (!settings.sheetName) return this.showError($message, 'Please enter a Sheet Tab Name');
            if (!settings.sheetHeaders.length) return this.showError($message, 'Please select at least one Sheet Header');

            $btn.prop('disabled', true);
            $text.text('Creating...');

            // Resolve headers to labels (use Label if available, otherwise ID)
            let headersToSend = settings.sheetHeaders;
            try {
                if (typeof elementor !== 'undefined' && elementor.getPanelView) {
                    const view = elementor.getPanelView().getCurrentPageView();
                    if (view && view.model) {
                        const formFields = view.model.get('settings').get('form_fields');

                        if (headersToSend && Array.isArray(headersToSend)) {
                            headersToSend = headersToSend.map(headerId => {
                                // System fields mapping
                                const systemLabels = {
                                    'user_ip': 'User IP',
                                    'user_agent': 'User Agent',
                                    'page_url': 'Page URL',
                                    'submission_date': 'Submission Date',
                                };

                                if (systemLabels[headerId]) {
                                    return systemLabels[headerId];
                                }

                                // Find in form fields
                                if (formFields && formFields.findWhere) {
                                    const field = formFields.findWhere({ custom_id: headerId });
                                    if (field) {
                                        const label = field.get('field_label');
                                        return label && label.trim() !== '' ? label : headerId;
                                    }
                                }

                                return headerId;
                            });
                        }
                    }
                }
            } catch (e) {
                console.error('FDBGP: Error resolving header labels:', e);
            }

            $.post(ajaxurl, {
                action: 'fdbgp_create_spreadsheet',
                _nonce: elementorCommon.config.ajax.nonce,
                spreadsheet_name: settings.spreadsheetName,
                sheet_name: settings.sheetName,
                headers: headersToSend
            }).done((response) => {
                const { success, data } = response;

                if (success) {
                    this.cacheCreatedSpreadsheet(data);
                    this.showSuccess($message, data.message || 'Spreadsheet created');

                    setTimeout(() => {
                        const $spreadsheetSelect = $("[data-setting='fdbgp_spreadsheetid']");
                        if ($spreadsheetSelect.length) {
                            if (!$spreadsheetSelect.find(`option[value="${data.spreadsheet_id}"]`).length) {
                                const $newOption = $spreadsheetSelect.find("option[value='new']").remove();
                                $spreadsheetSelect.append(new Option(data.spreadsheet_name, data.spreadsheet_id));
                                $spreadsheetSelect.append($newOption);
                            }

                            if (data.sheet_name) {
                                $("[data-setting='fdbgp_sheet_list']").data('auto-select', data.sheet_name);
                            }
                            $spreadsheetSelect.val(data.spreadsheet_id).change();
                        }
                    }, 2000);
                } else {
                    this.showError($message, data.message || 'Unknown error');
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
            const sheetValue = $select.val();
            const $panel = $select.closest('.elementor-panel');
            const spreadsheetId = $panel.find("[data-setting='fdbgp_spreadsheetid']").val();

            try {
                const currentView = elementor.getPanelView()?.getCurrentPageView?.();
                const widgetId = currentView?.model?.get('id');

                if (widgetId) {
                    if (!window.fdbgpWidgetState) window.fdbgpWidgetState = {};

                    if (sheetValue && sheetValue !== 'create_new_tab') {
                        window.fdbgpWidgetState[widgetId] = { sheet: sheetValue, spreadsheet: spreadsheetId };

                        if (currentView.model.setSetting) {
                            currentView.model.setSetting('fdbgp_sheet_list', sheetValue);
                            if (spreadsheetId) currentView.model.setSetting('fdbgp_spreadsheetid', spreadsheetId);
                            currentView.model.trigger('change');
                        }
                    } else if (sheetValue === 'create_new_tab') {
                        delete window.fdbgpWidgetState[widgetId]?.sheet;
                    }
                }
            } catch (e) { /* silent fail */ }

            this.saveWidgetState(sheetValue);
            this.checkSheetContent($select);
        }

        onSpreadsheetChange($select) {

            const spreadsheetId = $select.val();
            if (spreadsheetId === 'new') {
                const $panel = $select.closest('.elementor-panel');
                $panel.find("[data-setting='fdbgp_new_spreadsheet_name']").val('');
                $panel.find("[data-setting='fdbgp_sheet_name']").val('');
            }
            try {
                const widgetId = elementor.getPanelView()?.getCurrentPageView?.().model.get('id');
                if (widgetId && window.fdbgpWidgetState?.[widgetId]) {
                    delete window.fdbgpWidgetState[widgetId].sheet;
                }
            } catch (e) { }

            this.clearWidgetState();
            this.loadSheets($select);
            this.checkSheetContent($select);
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
                Object.entries(response.data.sheets).forEach(([key, val]) => {
                    $sheetSelect.append(new Option(val, key));
                });

                const autoSelect = $sheetSelect.data('auto-select');
                if (autoSelect) {
                    $sheetSelect.val(autoSelect).removeData('auto-select');
                    this.saveWidgetState(autoSelect);
                } else {
                    // Check Elementor model for saved sheet
                    try {
                        const model = elementor.getPanelView()?.getCurrentPageView?.().model;
                        const savedSheet = model?.getSetting('fdbgp_sheet_list');
                        if (savedSheet && savedSheet !== 'create_new_tab' && response.data.sheets && Object.prototype.hasOwnProperty.call(response.data.sheets, savedSheet)) {
                            $sheetSelect.val(savedSheet);
                            this.saveWidgetState(savedSheet);
                        }
                    } catch (e) { }
                }

                $sheetSelect.trigger('change');
            });
        }

        getCacheKey() {
            try {
                const postId = elementor.config.document.id || 'default';
                const widgetId = elementor.getPanelView()?.getCurrentPageView?.().model.get('id');
                return widgetId ? `fdbgp_cached_spreadsheet_${postId}_${widgetId}` : `fdbgp_cached_spreadsheet_${postId}`;
            } catch (e) {
                return `fdbgp_cached_spreadsheet_default`;
            }
        }

        cacheCreatedSpreadsheet(data) {
            const cacheKey = this.getCacheKey();
            localStorage.setItem(cacheKey, JSON.stringify({
                id: data.spreadsheet_id,
                name: data.spreadsheet_name,
                sheet_name: data.sheet_name || ''
            }));
        }

        cacheSpreadsheetSelection($select) {
            const val = $select.val();
            if (!val || val === 'new') return;

            const cacheKey = this.getCacheKey();
            localStorage.setItem(cacheKey, JSON.stringify({
                id: val,
                name: $select.find('option:selected').text(),
                sheet_name: ''
            }));
        }

        cacheSheetSelection($select) {
            const sheet = $select.val();
            if (!sheet || sheet === 'create_new_tab') return;

            const cacheKey = this.getCacheKey();
            const cache = localStorage.getItem(cacheKey);

            if (cache) {
                const data = JSON.parse(cache);
                data.sheet_name = sheet;
                localStorage.setItem(cacheKey, JSON.stringify(data));
            }
        }

        restoreCachedSpreadsheetDelayed() {
            setTimeout(() => this.restoreCachedSpreadsheet(), 2000);
        }

        restoreCachedSpreadsheet() {
            const cacheKey = this.getCacheKey();
            const cache = localStorage.getItem(cacheKey);
            if (!cache) return;

            const data = JSON.parse(cache);
            const $spreadsheet = $("[data-setting='fdbgp_spreadsheetid']");

            if ($spreadsheet.val()) return;

            if (!$spreadsheet.find(`option[value="${data.id}"]`).length) {
                const $new = $spreadsheet.find('option[value="new"]').remove();
                $spreadsheet.append(new Option(data.name, data.id)).append($new);
            }

            $spreadsheet.val(data.id).trigger('change');

            if (data.sheet_name) {
                setTimeout(() => {
                    const $sheet = $("[data-setting='fdbgp_sheet_list']");
                    if ($sheet.find(`option[value="${data.sheet_name}"]`).length > 0) {
                        $sheet.val(data.sheet_name).trigger('change');
                    }
                }, 1500);
            }
        }

        saveWidgetState(sheet) {
            try {
                const id = elementor.getPanelView().getCurrentPageView().model.get('id');
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

                    // Check if both dropdowns are empty but localStorage has data
                    const currentSpreadsheet = $spreadsheet.val();
                    const currentSheet = $sheetSelect.val();


                    if ((!currentSpreadsheet || currentSpreadsheet === '') && (!currentSheet || currentSheet === '')) {
                        // Try to restore from localStorage
                        const cacheKey = this.getCacheKey();
                        const cache = localStorage.getItem(cacheKey);

                        if (cache) {
                            try {
                                const data = JSON.parse(cache);

                                // Add spreadsheet to dropdown if not exists
                                if (data.id && $spreadsheet.find(`option[value="${data.id}"]`).length === 0) {
                                    const $newOption = $spreadsheet.find('option[value="new"]');
                                    $newOption.remove();

                                    const newOpt = document.createElement('option');
                                    newOpt.value = data.id;
                                    newOpt.text = data.name;
                                    $spreadsheet.append(newOpt);
                                    $spreadsheet.append($newOption);
                                }

                                // Set spreadsheet value
                                if (data.id) {
                                    if (data.sheet_name) {
                                        $sheetSelect.data('auto-select', data.sheet_name);
                                    }
                                    $spreadsheet.val(data.id).trigger('change');
                                }
                            } catch (e) {
                                console.log('FDBGP: Error restoring from cache:', e);
                            }
                        } else {
                        }
                    } else if (currentSpreadsheet && currentSpreadsheet !== 'new') {
                        if ($sheetSelect.find('option').length <= 2) {
                            $spreadsheet.trigger('change');
                        } else {
                            this.checkSheetContent($sheetSelect);
                        }
                    }
                }, 1000);
            });

            if (elementor.channels?.editor) {
                elementor.channels.editor.on('change', () => {
                    setTimeout(() => {
                        if (this.isRestoring) return;

                        const $sheetSelect = $('.elementor-control-fdbgp_sheet_list select:visible');
                        const $spreadsheetSelect = $('.elementor-control-fdbgp_spreadsheetid select:visible');

                        if (!$sheetSelect.length || !$spreadsheetSelect.length) return;

                        try {
                            const widgetId = elementor.getPanelView()?.getCurrentPageView?.().model.get('id');
                            let savedState = window.fdbgpWidgetState?.[widgetId];

                            // If no widgetState, try localStorage as fallback
                            if (!savedState) {
                                const cacheKey = this.getCacheKey();
                                const cache = localStorage.getItem(cacheKey);

                                if (cache) {
                                    try {
                                        const data = JSON.parse(cache);
                                        savedState = {
                                            spreadsheet: data.id,
                                            sheet: data.sheet_name
                                        };
                                    } catch (e) { }
                                }
                            }

                            if (savedState) {
                                // Check if both dropdowns are empty
                                const currentSpreadsheet = $spreadsheetSelect.val();
                                const currentSheet = $sheetSelect.val();


                                // Restore spreadsheet first if empty
                                if ((!currentSpreadsheet || currentSpreadsheet === '') && savedState.spreadsheet) {
                                    // Add option if doesn't exist
                                    if ($spreadsheetSelect.find(`option[value="${savedState.spreadsheet}"]`).length === 0) {
                                        const cache = localStorage.getItem(this.getCacheKey());
                                        if (cache) {
                                            const data = JSON.parse(cache);
                                            const $newOption = $spreadsheetSelect.find('option[value="new"]');
                                            $newOption.remove();

                                            const newOpt = document.createElement('option');
                                            newOpt.value = data.id;
                                            newOpt.text = data.name;
                                            $spreadsheetSelect.append(newOpt);
                                            $spreadsheetSelect.append($newOption);
                                        }
                                    }

                                    this.isRestoring = true;
                                    if (savedState.sheet) {
                                        $sheetSelect.data('auto-select', savedState.sheet);
                                    }
                                    $spreadsheetSelect.val(savedState.spreadsheet).trigger('change');
                                    setTimeout(() => { this.isRestoring = false; }, 1000);
                                    return;
                                }

                                // Then restore sheet if needed
                                if (savedState.sheet && $sheetSelect.val() !== savedState.sheet) {
                                    const optionExists = $sheetSelect.find(`option[value="${savedState.sheet}"]`).length;
                                    if (!optionExists && $spreadsheetSelect.val()) {
                                        this.isRestoring = true;
                                        $sheetSelect.data('auto-select', savedState.sheet);
                                        $spreadsheetSelect.trigger('change');
                                        setTimeout(() => { this.isRestoring = false; }, 1000);
                                    } else if (optionExists) {
                                        $sheetSelect.val(savedState.sheet).trigger('change');
                                    }
                                }
                            }
                        } catch (e) {
                            console.error('FDBGP: Error in change handler:', e);
                            this.isRestoring = false;
                        }
                    }, 300);
                });
            }

            const clearCache = () => {
                const cacheKey = this.getCacheKey();
                localStorage.removeItem(cacheKey);
            };

            window.addEventListener('beforeunload', () => {
                clearCache();
            });

            // if (typeof $e !== 'undefined' && $e.commands) {
            //     $e.commands.on('run:after', (component, command) => {
            //         if (command?.startsWith('document/save/')) clearCache();
            //     });
            // } else if (elementor.channels?.editor) {
            //     elementor.channels.editor.on('saved', clearCache);
            // }
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

                if (performance.now() - start > 20000) clearInterval(interval);
            }, 1000);
        }
    }

    $(window).on('load', () => {
        window.FDBGP_Editor = new FDBGP_Editor();

    });

    $(window).on('load', () => {
        if (typeof elementor !== 'undefined' && elementor.modules && elementor.modules.controls) {

            const FDBGP_DynamicSelect2 = elementor.modules.controls.BaseData.extend({

                onReady: function () {
                    if (this.ui.select.length > 0) {
                        this.ui.select.select2({
                            placeholder: 'Select fields',
                            allowClear: true,
                            width: '100%'
                        });
                    }

                    this.updateOptions();

                    if (this.container.settings.has('form_fields')) {
                        this.listenTo(this.container.settings.get('form_fields'), 'add remove change', this.updateOptions);
                    }
                },

                updateOptions: function () {
                    const formFields = this.container.settings.get('form_fields');
                    const $select = this.ui.select;
                    const currentVal = this.getControlValue();
                    const staticOptions = this.model.get('options');

                    $select.empty();

                    if (formFields) {
                        formFields.each((model) => {
                            const id = model.get('custom_id');
                            let label = model.get('field_label');
                            if (!label) label = id;

                            const text = `${label} (${id})`;
                            $select.append(new Option(text, id, false, false));
                        });
                    }

                    if (staticOptions) {
                        $.each(staticOptions, (key, label) => {
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

            elementor.addControlView('fdbgp_dynamic_select2', FDBGP_DynamicSelect2);
        }
    });

})(jQuery, window);