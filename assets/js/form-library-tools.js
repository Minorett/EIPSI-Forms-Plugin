/**
 * Form Library Tools - Export, Import, Duplicate
 * 
 * Maneja la UI y las interacciones para las herramientas de Form Library:
 * - Exportar formularios como JSON
 * - Importar formularios desde JSON
 * - Duplicar formularios con 1 click
 * 
 * @package VAS_Dinamico_Forms
 * @since 1.3.0
 */

(function($) {
    'use strict';

    const FormLibraryTools = {
        selectedFile: null,
        init() {
            this.selectedFile = null;
            this.bindExportActions();
            this.bindDuplicateActions();
            this.bindImportButton();
            this.bindClinicalTemplatesButtons();
        },

        /**
         * Export form as JSON (download)
         */
        bindExportActions() {
            $(document).on('click', '.eipsi-export-form', function(e) {
                e.preventDefault();
                
                const $link = $(this);
                const templateId = $link.data('template-id');
                const templateName = $link.data('template-name');
                
                // Show export mode selection modal
                FormLibraryTools.showExportModeModal(templateId, templateName, $link);
            });
        },

        /**
         * Show export mode selection modal
         */
        showExportModeModal(templateId, templateName, $triggerLink) {
            const modalHTML = `
                <div id="eipsi-export-mode-modal" style="display: none;">
                    <div class="eipsi-import-modal-backdrop"></div>
                    <div class="eipsi-import-modal-content" style="max-width: 540px;">
                        <div class="eipsi-import-modal-header">
                            <h2>Exportar formulario</h2>
                            <button type="button" class="eipsi-import-modal-close" aria-label="Cerrar">×</button>
                        </div>
                        <div class="eipsi-import-modal-body">
                            <p style="margin-bottom: 20px; color: #666;">
                                <strong>${templateName}</strong><br>
                                Seleccioná el formato de exportación:
                            </p>
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: flex; align-items: start; cursor: pointer; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; background: #f8f9fa; transition: all 0.2s;">
                                    <input type="radio" name="export_mode" value="lite" checked style="margin-top: 4px; margin-right: 12px; cursor: pointer;">
                                    <div>
                                        <strong style="color: #005a87; display: block; margin-bottom: 4px;">✨ Formato simplificado (recomendado)</strong>
                                        <span style="color: #666; font-size: 13px;">
                                            JSON limpio, editable a mano, ideal para demos y plantillas clínicas.
                                        </span>
                                    </div>
                                </label>
                            </div>
                            
                            <div>
                                <label style="display: flex; align-items: start; cursor: pointer; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; transition: all 0.2s;">
                                    <input type="radio" name="export_mode" value="full" style="margin-top: 4px; margin-right: 12px; cursor: pointer;">
                                    <div>
                                        <strong style="color: #333; display: block; margin-bottom: 4px;">Formato completo</strong>
                                        <span style="color: #666; font-size: 13px;">
                                            Incluye HTML generado y metadatos internos (más pesado).
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="eipsi-import-modal-footer">
                            <button type="button" class="button button-secondary eipsi-export-mode-cancel">
                                Cancelar
                            </button>
                            <button type="button" class="button button-primary eipsi-export-mode-confirm">
                                Exportar JSON
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            if ($('#eipsi-export-mode-modal').length === 0) {
                $('body').append(modalHTML);
                this.addImportModalStyles(); // Reuse existing modal styles
            }
            
            $('#eipsi-export-mode-modal').fadeIn(200);
            
            // Bind actions
            const $modal = $('#eipsi-export-mode-modal');
            
            // Highlight selected option
            $modal.find('input[name="export_mode"]').on('change', function() {
                $modal.find('label').css({
                    'border-color': '#e2e8f0',
                    'background': '#f8f9fa'
                });
                $(this).closest('label').css({
                    'border-color': '#005a87',
                    'background': 'rgba(0, 90, 135, 0.05)'
                });
            }).first().trigger('change');
            
            // Close modal
            $modal.find('.eipsi-import-modal-close, .eipsi-export-mode-cancel, .eipsi-import-modal-backdrop').off('click').on('click', function() {
                $modal.fadeOut(200, function() {
                    $(this).remove();
                });
            });
            
            // Confirm export
            $modal.find('.eipsi-export-mode-confirm').off('click').on('click', function() {
                const mode = $modal.find('input[name="export_mode"]:checked').val();
                $modal.fadeOut(200, function() {
                    $(this).remove();
                });
                FormLibraryTools.performExport(templateId, templateName, mode, $triggerLink);
            });
        },

        /**
         * Perform export with selected mode
         */
        performExport(templateId, templateName, mode, $triggerLink) {
            const originalText = $triggerLink.text();
            $triggerLink.text('⏳ Exportando...');
            
            $.ajax({
                url: eipsiFormTools.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_export_form',
                    nonce: eipsiFormTools.nonce,
                    template_id: templateId,
                    mode: mode
                },
                success(response) {
                    if (response.success) {
                        // Trigger download
                        FormLibraryTools.downloadJSON(
                            response.data.data,
                            response.data.filename
                        );
                        
                        const modeLabel = mode === 'lite' ? ' (simplificado)' : ' (completo)';
                        FormLibraryTools.showNotice(
                            eipsiFormTools.strings.exportSuccess + ': ' + templateName + modeLabel,
                            'success'
                        );
                    } else {
                        FormLibraryTools.showNotice(
                            response.data.message || eipsiFormTools.strings.exportError,
                            'error'
                        );
                    }
                },
                error() {
                    FormLibraryTools.showNotice(
                        eipsiFormTools.strings.exportError,
                        'error'
                    );
                },
                complete() {
                    $triggerLink.text(originalText);
                }
            });
        },

        /**
         * Duplicate form with 1 click
         */
        bindDuplicateActions() {
            $(document).on('click', '.eipsi-duplicate-form', function(e) {
                e.preventDefault();
                
                const $link = $(this);
                const templateId = $link.data('template-id');
                const templateName = $link.data('template-name');
                
                if (!confirm(eipsiFormTools.strings.duplicateConfirm + '\n\n' + templateName)) {
                    return;
                }
                
                // Visual feedback
                const originalText = $link.text();
                $link.text('⏳ Duplicando...');
                
                $.ajax({
                    url: eipsiFormTools.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_duplicate_form',
                        nonce: eipsiFormTools.nonce,
                        template_id: templateId
                    },
                    success(response) {
                        if (response.success) {
                            FormLibraryTools.showNotice(
                                response.data.message,
                                'success'
                            );
                            
                            // Reload page after short delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            FormLibraryTools.showNotice(
                                response.data.message || eipsiFormTools.strings.duplicateError,
                                'error'
                            );
                        }
                    },
                    error() {
                        FormLibraryTools.showNotice(
                            eipsiFormTools.strings.duplicateError,
                            'error'
                        );
                    },
                    complete() {
                        $link.text(originalText);
                    }
                });
            });
        },

        /**
         * Import form from JSON
         */
        bindImportButton() {
            $(document).on('click', '.eipsi-import-form-btn', function(e) {
                e.preventDefault();
                FormLibraryTools.showImportModal();
            });
        },

        /**
         * Create a new template from official clinical scales
         */
        bindClinicalTemplatesButtons() {
            $(document).on('click', '.eipsi-create-from-template', function(e) {
                e.preventDefault();

                const $button = $(this);

                if ($button.hasClass('is-busy')) {
                    return;
                }

                const templateId = $button.data('template-id');
                const templateName = $button.data('template-name') || templateId;

                if (!templateId) {
                    return;
                }

                const confirmTemplate = eipsiFormTools.strings.clinicalTemplateConfirm || '';
                const confirmMessage = confirmTemplate ? confirmTemplate.replace('%s', templateName) : '';

                if (confirmMessage && !confirm(confirmMessage)) {
                    return;
                }

                const originalText = $button.text();

                $button
                    .text(eipsiFormTools.strings.clinicalTemplateCreating)
                    .addClass('is-busy')
                    .prop('disabled', true);

                $.ajax({
                    url: eipsiFormTools.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_create_from_clinical_template',
                        nonce: eipsiFormTools.clinicalTemplatesNonce,
                        template_id: templateId
                    },
                    success(response) {
                        if (response.success) {
                            FormLibraryTools.showNotice(
                                response.data.message,
                                'success'
                            );

                            setTimeout(() => {
                                if (response.data.edit_url) {
                                    window.location.href = response.data.edit_url;
                                } else {
                                    window.location.reload();
                                }
                            }, 1200);
                        } else {
                            FormLibraryTools.showNotice(
                                (response.data && response.data.message) || eipsiFormTools.strings.clinicalTemplateError,
                                'error'
                            );

                            $button
                                .text(originalText)
                                .removeClass('is-busy')
                                .prop('disabled', false);
                        }
                    },
                    error() {
                        FormLibraryTools.showNotice(
                            eipsiFormTools.strings.clinicalTemplateError,
                            'error'
                        );

                        $button
                            .text(originalText)
                            .removeClass('is-busy')
                            .prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Show import modal
         */
        showImportModal() {
            // Create modal HTML
            const modalHTML = `
                <div id="eipsi-import-modal" style="display: none;">
                    <div class="eipsi-import-modal-backdrop"></div>
                    <div class="eipsi-import-modal-content">
                        <div class="eipsi-import-modal-header">
                            <h2>${eipsiFormTools.strings.importTitle}</h2>
                            <button type="button" class="eipsi-import-modal-close" aria-label="Cerrar">×</button>
                        </div>
                        <div class="eipsi-import-modal-body">
                            <p style="margin-bottom: 16px; color: #666;">
                                ${eipsiFormTools.strings.importInstructions}
                            </p>
                            <div class="eipsi-import-file-area">
                                <input type="file" id="eipsi-import-file-input" accept=".json" style="display: none;">
                                <div class="eipsi-import-dropzone" id="eipsi-import-dropzone">
                                    <div class="eipsi-import-dropzone-icon">📁</div>
                                    <p class="eipsi-import-dropzone-text">
                                        <strong>Hacé clic para seleccionar</strong> o arrastrá un archivo JSON aquí
                                    </p>
                                    <p class="eipsi-import-dropzone-filename" style="display: none;"></p>
                                </div>
                            </div>
                            <div class="eipsi-import-error" style="display: none;"></div>
                        </div>
                        <div class="eipsi-import-modal-footer">
                            <button type="button" class="button button-secondary eipsi-import-cancel">
                                ${eipsiFormTools.strings.importCancel}
                            </button>
                            <button type="button" class="button button-primary eipsi-import-submit" disabled>
                                ${eipsiFormTools.strings.importButton}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Inject modal into page
            if ($('#eipsi-import-modal').length === 0) {
                $('body').append(modalHTML);
                this.addImportModalStyles();
            }
            
            // Show modal
            $('#eipsi-import-modal').fadeIn(200);
            
            // Bind modal interactions
            this.bindImportModalActions();
        },

        /**
         * Bind import modal interactions
         */
        bindImportModalActions() {
            const $modal = $('#eipsi-import-modal');
            const $fileInput = $('#eipsi-import-file-input');
            const $dropzone = $('#eipsi-import-dropzone');
            const $submitBtn = $('.eipsi-import-submit');
            this.selectedFile = null;
            
            // Close modal
            $('.eipsi-import-modal-close, .eipsi-import-cancel, .eipsi-import-modal-backdrop').off('click').on('click', function() {
                FormLibraryTools.selectedFile = null;
                $modal.fadeOut(200, function() {
                    $(this).remove();
                });
            });
            
            // Click dropzone to trigger file input
            $dropzone.off('click').on('click', function() {
                $fileInput.trigger('click');
            });
            
            // File input change
            $fileInput.off('change').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    FormLibraryTools.handleFileSelect(file);
                }
            });
            
            // Drag and drop
            $dropzone.off('dragover drop');
            
            $dropzone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('eipsi-import-dropzone-active');
            });
            
            $dropzone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('eipsi-import-dropzone-active');
            });
            
            $dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('eipsi-import-dropzone-active');
                
                const file = e.originalEvent.dataTransfer.files[0];
                if (file) {
                    FormLibraryTools.handleFileSelect(file);
                }
            });
            
            // Submit import
            $submitBtn.off('click').on('click', function() {
                if (FormLibraryTools.selectedFile) {
                    FormLibraryTools.processImport(FormLibraryTools.selectedFile);
                }
            });
        },

        /**
         * Handle file selection
         */
        handleFileSelect(file) {
            const $dropzone = $('#eipsi-import-dropzone');
            const $filename = $('.eipsi-import-dropzone-filename');
            const $submitBtn = $('.eipsi-import-submit');
            const $error = $('.eipsi-import-error');
            
            // Validate file type
            if (!file.name.endsWith('.json')) {
                $error.html(eipsiFormTools.strings.invalidFile).show();
                $submitBtn.prop('disabled', true);
                return;
            }
            
            // Update UI
            $error.hide();
            $filename.html(`<strong>Archivo seleccionado:</strong> ${file.name}`).show();
            $('.eipsi-import-dropzone-icon').text('✓');
            $('.eipsi-import-dropzone-text').hide();
            $dropzone.addClass('eipsi-import-dropzone-selected');
            $submitBtn.prop('disabled', false);
            
            // Store file in the FormLibraryTools object
            FormLibraryTools.selectedFile = file;
        },

        /**
         * Process import
         */
        processImport(file) {
            const $submitBtn = $('.eipsi-import-submit');
            const $error = $('.eipsi-import-error');
            const originalText = $submitBtn.text();
            
            $submitBtn.text('⏳ Importando...').prop('disabled', true);
            $error.hide();
            
            // Read file
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const jsonString = e.target.result;
                
                // Send to server
                $.ajax({
                    url: eipsiFormTools.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_import_form',
                        nonce: eipsiFormTools.nonce,
                        json_data: jsonString
                    },
                    success(response) {
                        if (response.success) {
                            FormLibraryTools.showNotice(
                                response.data.message,
                                'success'
                            );
                            
                            // Close modal and reload page
                            $('#eipsi-import-modal').fadeOut(200, function() {
                                $(this).remove();
                            });
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            $error.html(response.data.message || eipsiFormTools.strings.importError).show();
                            $submitBtn.text(originalText).prop('disabled', false);
                        }
                    },
                    error() {
                        $error.html(eipsiFormTools.strings.importError).show();
                        $submitBtn.text(originalText).prop('disabled', false);
                    }
                });
            };
            
            reader.onerror = function() {
                $error.html(eipsiFormTools.strings.importError).show();
                $submitBtn.text(originalText).prop('disabled', false);
            };
            
            reader.readAsText(file);
        },

        /**
         * Download JSON file
         */
        downloadJSON(data, filename) {
            const jsonString = JSON.stringify(data, null, 2);
            const blob = new Blob([jsonString], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const $link = $('<a></a>')
                .attr('href', url)
                .attr('download', filename)
                .appendTo('body');
            
            $link[0].click();
            $link.remove();
            
            // Clean up
            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 100);
        },

        /**
         * Show admin notice
         */
        showNotice(message, type = 'info') {
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            
            const $notice = $(`
                <div class="notice ${noticeClass} is-dismissible eipsi-form-tools-notice" style="position: fixed; top: 32px; right: 20px; z-index: 999999; min-width: 300px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                    <p><strong>${message}</strong></p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Cerrar</span>
                    </button>
                </div>
            `);
            
            $('body').append($notice);
            
            // Bind dismiss button
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Add import modal styles
         */
        addImportModalStyles() {
            if ($('#eipsi-import-modal-styles').length > 0) {
                return;
            }
            
            const styles = `
                <style id="eipsi-import-modal-styles">
                    .eipsi-import-modal-backdrop {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0, 0, 0, 0.7);
                        z-index: 999998;
                    }
                    
                    .eipsi-import-modal-content {
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                        z-index: 999999;
                        width: 90%;
                        max-width: 600px;
                    }
                    
                    .eipsi-import-modal-header {
                        padding: 20px 24px;
                        border-bottom: 1px solid #ddd;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                    }
                    
                    .eipsi-import-modal-header h2 {
                        margin: 0;
                        font-size: 20px;
                        font-weight: 600;
                    }
                    
                    .eipsi-import-modal-close {
                        background: none;
                        border: none;
                        font-size: 32px;
                        line-height: 1;
                        cursor: pointer;
                        color: #999;
                        padding: 0;
                        width: 32px;
                        height: 32px;
                    }
                    
                    .eipsi-import-modal-close:hover {
                        color: #333;
                    }
                    
                    .eipsi-import-modal-body {
                        padding: 24px;
                    }
                    
                    .eipsi-import-dropzone {
                        border: 2px dashed #ccc;
                        border-radius: 8px;
                        padding: 40px 20px;
                        text-align: center;
                        cursor: pointer;
                        transition: all 0.3s;
                        background: #fafafa;
                    }
                    
                    .eipsi-import-dropzone:hover {
                        border-color: #2271b1;
                        background: #f0f6fc;
                    }
                    
                    .eipsi-import-dropzone-active {
                        border-color: #2271b1;
                        background: #e0f2fe;
                    }
                    
                    .eipsi-import-dropzone-selected {
                        border-color: #00a32a;
                        background: #e5f5e8;
                    }
                    
                    .eipsi-import-dropzone-icon {
                        font-size: 48px;
                        margin-bottom: 16px;
                    }
                    
                    .eipsi-import-dropzone-text {
                        margin: 0;
                        color: #666;
                    }
                    
                    .eipsi-import-dropzone-filename {
                        margin: 16px 0 0;
                        color: #00a32a;
                        font-size: 14px;
                    }
                    
                    .eipsi-import-error {
                        margin-top: 16px;
                        padding: 12px;
                        background: #fee2e2;
                        color: #b91c1c;
                        border-radius: 4px;
                        border-left: 4px solid #dc2626;
                    }
                    
                    .eipsi-import-modal-footer {
                        padding: 16px 24px;
                        border-top: 1px solid #ddd;
                        display: flex;
                        align-items: center;
                        justify-content: flex-end;
                        gap: 12px;
                    }
                </style>
            `;
            
            $('head').append(styles);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        FormLibraryTools.init();
    });

})(jQuery);
