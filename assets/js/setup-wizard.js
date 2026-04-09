/**
 * EIPSI Setup Wizard JavaScript
 * 
 * Funcionalidad JavaScript para el wizard de creación de estudios.
 * Maneja navegación, validación, auto-save y más.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

(function($) {
    'use strict';

    // Wizard state
    let wizardState = {
        currentStep: 1,
        totalSteps: 5,
        autoSaveInterval: null,
        lastSaveTime: null,
        isDirty: false
    };

    // Initialize wizard when document is ready
    $(document).ready(function() {
        initializeWizard();
    });

    /**
     * Initialize wizard functionality
     */
    function initializeWizard() {
        // Get current step from URL or form
        const urlParams = new URLSearchParams(window.location.search);
        const stepFromUrl = urlParams.get('step');
        const stepFromForm = $('#eipsi-wizard-form input[name="step_number"]').val();
        
        wizardState.currentStep = stepFromUrl ? parseInt(stepFromUrl) : (stepFromForm ? parseInt(stepFromForm) : 1);
        wizardState.currentStep = Math.max(1, Math.min(wizardState.totalSteps, wizardState.currentStep));

        // Initialize components
        initializeProgressBar();
        initializeFormValidation();
        initializeAutoSave();
        initializeNavigation();
        initializeStepSpecific();
        
        // Set initial state
        updateWizardUI();
        
        console.log('EIPSI Wizard initialized for step:', wizardState.currentStep);
    }

    /**
     * Initialize progress bar functionality
     */
    function initializeProgressBar() {
        $('.progress-step').on('click', function() {
            const stepNum = parseInt($(this).data('step'));
            
            // Only allow navigation to completed or current step
            if (isStepAccessible(stepNum)) {
                navigateToStep(stepNum);
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        const form = $('#eipsi-wizard-form');
        
        if (form.length) {
            // Real-time validation for important fields
            form.find('input[required], select[required]').on('blur', function() {
                validateField($(this));
            });
            
            // Mark form as dirty when user changes inputs
            form.find('input, select, textarea').on('change input', function() {
                wizardState.isDirty = true;
                markFormDirty(true);
            });
            
            // Handle form submission
            form.on('submit', function(e) {
                e.preventDefault();
                eipsiHandleFormSubmission();
            });
        }
    }

    /**
     * Initialize auto-save functionality
     */
    function initializeAutoSave() {
        // Clear existing interval
        if (wizardState.autoSaveInterval) {
            clearInterval(wizardState.autoSaveInterval);
        }
        
        // Start auto-save every 5 seconds
        wizardState.autoSaveInterval = setInterval(function() {
            if (wizardState.isDirty) {
                autoSaveCurrentStep();
            }
        }, 5000);
        
        // Save before page unload
        $(window).on('beforeunload', function() {
            if (wizardState.isDirty) {
                autoSaveCurrentStep(true);
            }
        });
    }

    /**
     * Initialize navigation buttons
     */
    function initializeNavigation() {
        // Previous button
        $(document).on('click', '.eipsi-wizard-navigation .button-secondary', function(e) {
            e.preventDefault();
            if (wizardState.currentStep > 1) {
                navigateToStep(wizardState.currentStep - 1);
            }
        });
        
        // Next button
        $(document).on('click', '.eipsi-wizard-navigation .button-primary', function(e) {
            e.preventDefault();
            eipsiSaveCurrentStep(wizardState.currentStep);
        });
    }

    /**
     * Initialize step-specific functionality
     */
    function initializeStepSpecific() {
        switch (wizardState.currentStep) {
            case 1:
                initializeStep1();
                break;
            case 2:
                initializeStep2();
                break;
            case 3:
                initializeStep3();
                break;
            case 4:
                initializeStep4();
                break;
            case 5:
                initializeStep5();
                break;
        }
    }

    /**
     * Step 1 specific initialization
     */
    function initializeStep1() {
        // Auto-generate study code from name
        const nameField = $('#study_name');
        const codeField = $('#study_code');
        
        if (nameField.length && codeField.length) {
            let timeout;
            
            nameField.on('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    if (nameField.val().trim() && !codeField.data('user-modified')) {
                        const code = generateStudyCode(nameField.val().trim());
                        codeField.val(code);
                    }
                }, 500);
            });
            
            codeField.on('input', function() {
                $(this).data('user-modified', true);
            });
        }
    }

    /**
     * Step 2 specific initialization
     */
    function initializeStep2() {
        // Wave management functions
        window.eipsiIncreaseWaves = function() {
            const input = $('#number_of_waves');
            const currentValue = parseInt(input.val());
            const maxWaves = 10;
            
            if (currentValue < maxWaves) {
                input.val(currentValue + 1);
                updateWavesList();
            }
        };
        
        window.eipsiDecreaseWaves = function() {
            const input = $('#number_of_waves');
            const currentValue = parseInt(input.val());
            const minWaves = 1;
            
            if (currentValue > minWaves) {
                input.val(currentValue - 1);
                updateWavesList();
            }
        };
        
        // Initialize waves list
        updateWavesList();
    }

    /**
     * Step 3 specific initialization
     */
    function initializeStep3() {
        // Timing template functions
        window.eipsiApplyTimingTemplate = function(template, btn) {
            applyTimingTemplate(template, btn);
        };
        
        // Handle retry checkbox
        $('input[name="enable_retries"]').on('change', function() {
            const retryInput = $('input[name="retry_after_days"]');
            retryInput.prop('disabled', !$(this).is(':checked'));
            if (!$(this).is(':checked')) {
                retryInput.val('7');
            }
        });
    }

    /**
     * Step 4 specific initialization
     */
    function initializeStep4() {
        // Consent template functions
        window.eipsiApplyConsentTemplate = function(template) {
            applyConsentTemplate(template);
        };
        
        // Handle method card selection
        $('.method-card').on('click', function(e) {
            if (!$(e.target).is('input[type="checkbox"]')) {
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
            }
        });
        
        // Update method card styles
        $('input[name="invitation_methods[]"]').on('change', function() {
            updateMethodCard($(this).closest('.method-card'), $(this).is(':checked'));
        });
    }

    /**
     * Step 5 specific initialization
     */
    function initializeStep5() {
        // Handle confirmation checkbox
        $('#activation_confirmed').on('change', function() {
            const activationButton = $('.eipsi-wizard-navigation .button-primary');
            
            if ($(this).is(':checked')) {
                activationButton.prop('disabled', false).css('opacity', '1').text('🚀 Activar Estudio');
            } else {
                activationButton.prop('disabled', true).css('opacity', '0.5').text('Confirmar Activación');
            }
        });
        
        // Print and download functions
        window.eipsiPrintSummary = function() {
            window.print();
        };
        
        window.eipsiDownloadSummary = function() {
            // TODO: Implement PDF/CSV export
            alert('Función de descarga próximamente disponible.');
        };
    }

    /**
     * Update waves list based on number input
     */
    function updateWavesList() {
        const numberOfWaves = parseInt($('#number_of_waves').val());
        const wavesList = $('#waves-list');
        
        if (!wavesList.length) return;
        
        // Clear existing wave items
        wavesList.empty();
        
        // Generate wave items
        for (let i = 0; i < numberOfWaves; i++) {
            const waveItem = generateWaveItem(i);
            wavesList.append(waveItem);
        }
        
        wizardState.isDirty = true;
        markFormDirty(true);
    }

    /**
     * Generate wave item HTML
     */
    function generateWaveItem(index) {
        const isRequired = index === 0; // First wave is required by default
        const defaultName = getDefaultWaveName(index);
        
        return $(`
            <div class="wave-item" data-wave="${index + 1}">
                <div class="wave-header">
                    <h3>Toma ${index + 1}</h3>
                    <span class="wave-status">${isRequired ? 'Obligatoria' : 'Opcional'}</span>
                </div>
                
                <div class="wave-fields">
                    <div class="field-group">
                        <label for="wave_name_${index}" class="form-label">
                            Nombre de la Toma
                        </label>
                        <input type="text" 
                               id="wave_name_${index}"
                               name="waves_config[${index}][name]" 
                               class="form-input" 
                               value="${defaultName}"
                               placeholder="Ej: Evaluación inicial">
                    </div>
                    
                    <div class="field-group">
                        <label for="wave_form_${index}" class="form-label">
                            Formulario a usar
                        </label>
                        <select id="wave_form_${index}"
                                name="waves_config[${index}][form_template_id]" 
                                class="form-select">
                            <option value="">Seleccionar formulario...</option>
                            ${getAvailableFormsHTML()}
                        </select>
                    </div>
                    
                    <input type="hidden" name="waves_config[${index}][estimated_duration]" value="">
                    <input type="hidden" name="waves_config[${index}][is_required]" value="${isRequired ? '1' : '0'}">
                </div>
            </div>
        `);
    }

    /**
     * Get default wave name
     */
    function getDefaultWaveName(index) {
        const defaultNames = [
            'Pre-intervención',
            'Post-intervención', 
            'Seguimiento 1 mes',
            'Seguimiento 3 meses',
            'Seguimiento 6 meses'
        ];
        
        return defaultNames[index] || `Toma ${index + 1}`;
    }

    /**
     * Get available forms HTML from localized data
     */
    function getAvailableFormsHTML() {
        // Use forms data localized from WordPress
        if (typeof eipsiWizard !== 'undefined' && eipsiWizard.availableForms) {
            let optionsHtml = '';
            
            eipsiWizard.availableForms.forEach(function(form) {
                optionsHtml += '<option value="' + form.ID + '">' + form.post_title + '</option>';
            });
            
            return optionsHtml;
        }
        
        return '';
    }

    /**
     * Apply timing template
     * v2.1.3: Added btn parameter and monitoreo_semanal template
     */
    function applyTimingTemplate(template, btn) {
        const templates = {
            'monitoreo_semanal': {
                2: [7],
                3: [7, 7],
                4: [7, 7, 7],
                5: [7, 7, 7, 7]
            },
            'pre_post_follow': {
                2: [7],
                3: [7, 30],
                4: [7, 30, 90],
                5: [7, 30, 60, 90]
            },
            'monthly': {
                2: [30],
                3: [30, 30],
                4: [30, 30, 30],
                5: [30, 30, 30, 30]
            },
            'quarterly': {
                2: [90],
                3: [90, 90],
                4: [90, 90, 90],
                5: [90, 90, 90, 90]
            }
        };

        const numberOfWaves = parseInt($('#number_of_waves').val()) || 3;

        if (templates[template] && templates[template][numberOfWaves]) {
            const intervals = templates[template][numberOfWaves];
            const inputs = $('input[name$="[days_after]"]');

            intervals.forEach((days, index) => {
                if (inputs[index]) {
                    $(inputs[index]).val(days);
                }
            });

            // v2.1.3: Use btn parameter instead of event.target
            const button = btn || (typeof event !== 'undefined' && event.target);
            if (button) {
                const originalText = button.textContent;
                button.textContent = '✅ Aplicado';
                button.style.background = '#28a745';
                button.style.color = 'white';

                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                    button.style.color = '';
                }, 2000);
            }

            wizardState.isDirty = true;
            markFormDirty(true);
        }
    }

    /**
     * Apply consent template
     */
    function applyConsentTemplate(template) {
        const templates = {
            'general': `Estimado/a participante,\n\nLe invitamos a participar en este estudio de investigación. Su participación es completamente voluntaria y puede retirarse en cualquier momento sin consecuencias.\n\nEl estudio tiene como objetivo [OBJETIVO DEL ESTUDIO]. La participación implicará completar cuestionarios que tomarán aproximadamente [DURACIÓN] minutos.\n\nSus respuestas serán confidenciales y anónimas. Los datos se utilizarán únicamente para fines de investigación académica.\n\nSi tiene preguntas sobre el estudio, puede contactar al investigador principal.\n\n¿Está de acuerdo en participar?`,
            
            'clinical': `Estimado/a participante,\n\nEste estudio evalúa la efectividad de intervenciones psicológicas en un contexto clínico.\n\nSu participación es completamente voluntaria. Puede retirarse del tratamiento en cualquier momento sin afectar su atención médica.\n\nLos datos clínicos se manejarán con estricta confidencialidad, conforme a las normativas de protección de datos de salud.\n\nSus respuestas ayudarán a mejorar las intervenciones psicológicas para futuros pacientes.\n\n¿Autoriza su participación en este estudio de investigación?`,
            
            'research': `Estimado/a participante,\n\nEste es un estudio de investigación académica sobre [TEMA DE INVESTIGACIÓN].\n\nSu participación implica:\n• Completar cuestionarios sobre [TEMAS]\n• Duración estimada: [DURACIÓN]\n• Participación completamente voluntaria\n• Derecho a retirarse sin consecuencias\n\nDatos y confidencialidad:\n• Respuestas anónimas y confidenciales\n• Solo el equipo de investigación tendrá acceso\n• Datos utilizados exclusivamente para fines académicos\n• Posibilidad de solicitar eliminación de datos\n\nSi acepta participar, haga clic en "Acepto participar".`
        };
        
        if (templates[template]) {
            $('#consent_message').val(templates[template]);
            
            // Visual feedback
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✅ Aplicada';
            btn.style.background = '#28a745';
            btn.style.color = 'white';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
            
            wizardState.isDirty = true;
            markFormDirty(true);
        }
    }

    /**
     * Update method card appearance
     */
    function updateMethodCard(card, isSelected) {
        if (isSelected) {
            card.addClass('selected');
            card.css('border-color', '#667eea');
            card.css('background-color', '#f8f9ff');
        } else {
            card.removeClass('selected');
            card.css('border-color', '#e9ecef');
            card.css('background-color', 'white');
        }
    }

    /**
     * Navigate to a specific step
     */
    function navigateToStep(step) {
        if (step === wizardState.currentStep) return;
        
        // Save current step first if dirty
        if (wizardState.isDirty) {
            eipsiSaveCurrentStep(wizardState.currentStep, function() {
                window.location.href = eipsiWizard.ajaxUrl.replace('admin-ajax.php', `admin.php?page=eipsi-longitudinal-study&tab=create-study&step=${step}`);
            });
        } else {
            window.location.href = eipsiWizard.ajaxUrl.replace('admin-ajax.php', `admin.php?page=eipsi-longitudinal-study&tab=create-study&step=${step}`);
        }
    }

    /**
     * Check if a step is accessible
     */
    function isStepAccessible(stepNum) {
        // Can navigate to current step
        if (stepNum === wizardState.currentStep) return true;
        
        // Can navigate to completed steps
        return stepNum < wizardState.currentStep;
    }

    /**
     * Save current step
                wizardState.lastSaveTime = new Date();
                
                if (callback) {
                    callback();
                } else if (step < wizardState.totalSteps) {
                    // Navigate to next step automatically
                    navigateToStep(step + 1);
                }
            } else {
                showError(data.data || 'Error al guardar el paso.');
            }
        })
        .catch(error => {
            showLoadingState(false);
            console.error('Error:', error);
            showError('Error de conexión. Por favor, intenta nuevamente.');
        });
    };

    /**
     * Auto-save current step
     */
    function autoSaveCurrentStep(isSync = false) {
        if (!wizardState.isDirty) return;
        
        const form = $('#eipsi-wizard-form');
        if (!form.length) return;
        
        const formData = new FormData(form[0]);
        formData.append('action', 'eipsi_auto_save_wizard_step');
        formData.append('current_step', wizardState.currentStep);
        formData.append('eipsi_wizard_nonce', eipsiWizard.nonce);
        
        const fetchOptions = {
            method: 'POST',
            body: formData
        };
        
        if (isSync) {
            fetchOptions.credentials = 'same-origin';
        }
        
        fetch(eipsiWizard.ajaxUrl, fetchOptions)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                wizardState.isDirty = false;
                markFormDirty(false);
                wizardState.lastSaveTime = new Date();
                console.log('Auto-save completed');
            }
        })
        .catch(error => {
            console.log('Auto-save failed:', error);
        });
    }

    /**
     * Activate study
     */
    window.eipsiActivateStudy = function() {
        if (!$('#activation_confirmed').is(':checked')) {
            showError('Debes confirmar la activación del estudio.');
            return;
        }
        
        const confirmation = confirm('¿Estás seguro de que deseas activar este estudio? Una vez activado, será difícil cambiar la estructura.');
        if (!confirmation) return;
        
        const form = $('#eipsi-wizard-form');
        const formData = new FormData(form[0]);
        
        formData.append('action', 'eipsi_activate_study');
        formData.append('current_step', 5);
        formData.append('eipsi_wizard_nonce', eipsiWizard.nonce);
        formData.append('activation_confirmed', '1');
        
        showLoadingState(true);
        
        fetch(eipsiWizard.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoadingState(false);
            
            if (data.success) {
                showSuccess('¡Estudio creado exitosamente!');
                setTimeout(() => {
                    window.location.href = data.data.redirect_url;
                }, 1500);
            } else {
                showError(data.data || 'Error al activar el estudio.');
            }
        })
        .catch(error => {
            showLoadingState(false);
            console.error('Error:', error);
            showError('Error de conexión. Por favor, intenta nuevamente.');
        });
    };

    /**
     * Update wizard UI
     */
    function updateWizardUI() {
        // Update progress bar
        $('.progress-step').removeClass('active completed disabled');
        
        for (let i = 1; i <= wizardState.totalSteps; i++) {
            const stepElement = $(`.progress-step[data-step="${i}"]`);
            
            if (i === wizardState.currentStep) {
                stepElement.addClass('active');
            } else if (i < wizardState.currentStep) {
                stepElement.addClass('completed');
            } else {
                stepElement.addClass('disabled');
            }
        }
        
        // Update progress bar width
        const progressWidth = ((wizardState.currentStep - 1) / (wizardState.totalSteps - 1)) * 100;
        $('.progress-bar').css('width', progressWidth + '%');
    }

    /**
     * Validate individual field
     */
    function validateField(field) {
        const value = field.val().trim();
        const isRequired = field.prop('required');
        
        // Remove existing error state
        field.removeClass('error');
        field.siblings('.error-message').remove();
        
        // Check if required field is empty
        if (isRequired && !value) {
            showFieldError(field, 'Este campo es obligatorio.');
            return false;
        }
        
        // Add specific validations based on field type
        const fieldType = field.attr('type');
        const fieldName = field.attr('name');
        
        if (fieldType === 'email' && value && !isValidEmail(value)) {
            showFieldError(field, 'Por favor, ingresa un email válido.');
            return false;
        }
        
        if (fieldName === 'study_code' && value && !isValidStudyCode(value)) {
            showFieldError(field, 'El código debe contener solo letras mayúsculas, números y guiones bajos.');
            return false;
        }
        
        return true;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        field.addClass('error');
        field.after(`<span class="error-message">${message}</span>`);
    }

    /**
     * Validate email
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate study code
     */
    function isValidStudyCode(code) {
        const codeRegex = /^[A-Z0-9_]+$/;
        return codeRegex.test(code);
    }

    /**
     * Generate study code from name
     */
    function generateStudyCode(name) {
        // Remove accents and special characters
        let cleanName = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        cleanName = cleanName.replace(/[^a-zA-Z0-9\s]/g, '');
        
        // Convert to uppercase and replace spaces with underscores
        cleanName = cleanName.toUpperCase().replace(/\s+/g, '_');
        
        // Take first 3 words or truncate to 15 characters
        const words = cleanName.split('_').slice(0, 3);
        let prefix = words.join('_');
        
        if (prefix.length > 15) {
            prefix = prefix.substring(0, 15);
        }
        
        // Add year
        const year = new Date().getFullYear();
        const finalCode = prefix + '_' + year;
        
        return finalCode;
    }

    /**
     * Handle form submission
     */
    function eipsiHandleFormSubmission() {
        const isValid = validateCurrentStep();
        
        if (isValid) {
            if (wizardState.currentStep < wizardState.totalSteps) {
                eipsiSaveCurrentStep(wizardState.currentStep);
            } else {
                eipsiActivateStudy();
            }
        }
    }

    /**
     * Validate current step
     */
    function validateCurrentStep() {
        let isValid = true;
        const requiredFields = $('#eipsi-wizard-form [required]');
        
        requiredFields.each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        // Step-specific validations
        switch (wizardState.currentStep) {
            case 1:
                isValid = validateStep1() && isValid;
                break;
            case 2:
                isValid = validateStep2() && isValid;
                break;
            case 3:
                isValid = validateStep3() && isValid;
                break;
            case 4:
                isValid = validateStep4() && isValid;
                break;
            case 5:
                isValid = validateStep5() && isValid;
                break;
        }
        
        return isValid;
    }

    /**
     * Validate step 1
     */
    function validateStep1() {
        let isValid = true;
        
        // Check study name length
        const name = $('#study_name').val().trim();
        if (name.length < 3) {
            showFieldError($('#study_name'), 'El nombre debe tener al menos 3 caracteres.');
            isValid = false;
        }
        
        // Check study code format
        const code = $('#study_code').val().trim();
        if (!isValidStudyCode(code)) {
            showFieldError($('#study_code'), 'El código debe contener solo letras mayúsculas, números y guiones bajos.');
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Validate step 2
     */
    function validateStep2() {
        let isValid = true;
        
        // Check number of waves
        const numberOfWaves = parseInt($('#number_of_waves').val());
        if (numberOfWaves < 1 || numberOfWaves > 10) {
            showError('El número de tomas debe estar entre 1 y 10.');
            isValid = false;
        }
        
        // Check that each wave has a name and form
        $('.wave-item').each(function(index) {
            const waveName = $(this).find('input[name*="[name]"]').val().trim();
            const waveForm = $(this).find('select[name*="[form_template_id]"]').val();
            
            if (!waveName) {
                showFieldError($(this).find('input[name*="[name]"]'), 'El nombre de la toma es obligatorio.');
                isValid = false;
            }
            
            if (!waveForm) {
                showFieldError($(this).find('select[name*="[form_template_id]"]'), 'Debes seleccionar un formulario.');
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Validate step 3
     */
    function validateStep3() {
        let isValid = true;
        
        // Check timing intervals
        $('input[name$="[days_after]"]').each(function() {
            const days = parseInt($(this).val());
            if (days < 1 || days > 365) {
                showFieldError($(this), 'Los días deben estar entre 1 y 365.');
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Validate step 4
     */
    function validateStep4() {
        let isValid = true;
        
        // Check that at least one invitation method is selected
        const selectedMethods = $('input[name="invitation_methods[]"]:checked').length;
        if (selectedMethods === 0) {
            showError('Debes seleccionar al menos un método de invitación.');
            isValid = false;
        }
        
        // Check consent message if required
        if ($('input[name="require_consent"]').is(':checked')) {
            const consentMessage = $('#consent_message').val().trim();
            if (!consentMessage) {
                showFieldError($('#consent_message'), 'El mensaje de consentimiento es obligatorio.');
                isValid = false;
            }
        }
        
        return isValid;
    }

    /**
     * Validate step 5
     */
    function validateStep5() {
        return $('#activation_confirmed').is(':checked');
    }

    /**
     * Mark form as dirty/clean
     */
    function markFormDirty(isDirty) {
        const form = $('#eipsi-wizard-form');
        
        if (isDirty) {
            form.addClass('form-dirty');
        } else {
            form.removeClass('form-dirty');
        }
    }

    /**
     * Show loading state
     */
    function showLoadingState(isLoading) {
        const wizard = $('.eipsi-setup-wizard');
        
        if (isLoading) {
            wizard.addClass('eipsi-wizard-loading');
        } else {
            wizard.removeClass('eipsi-wizard-loading');
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        // Remove existing error notices
        $('.eipsi-wizard-error').remove();
        
        // Create error notice
        const errorHtml = `
            <div class="eipsi-wizard-error">
                <h4>❌ Error</h4>
                <p>${message}</p>
            </div>
        `;
        
        $('.eipsi-setup-wizard').prepend(errorHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            $('.eipsi-wizard-error').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        // Remove existing notices
        $('.eipsi-wizard-error, .notice').remove();
        
        // Create success notice
        const successHtml = `
            <div class="eipsi-wizard-success">
                <h3>✅ ¡Éxito!</h3>
                <p>${message}</p>
            </div>
        `;
        
        $('.eipsi-setup-wizard').prepend(successHtml);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            $('.eipsi-wizard-success').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Global functions for template access
    window.eipsiNavigateToStep = navigateToStep;
    window.eipsiRegenerateCode = function() {
        const nameField = $('#study_name');
        const codeField = $('#study_code');
        
        if (nameField.val().trim()) {
            const code = generateStudyCode(nameField.val().trim());
            codeField.val(code);
        } else {
            showError('Por favor, ingresa primero el nombre del estudio.');
        }
    };

})(jQuery);