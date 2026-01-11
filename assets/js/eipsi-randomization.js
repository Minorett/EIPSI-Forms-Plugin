/**
 * EIPSI Forms Randomization System
 * 
 * Public randomization for clinical research studies
 * Features: seeded randomization, manual override, persistent assignments
 */

(function() {
    'use strict';

    /**
     * Main randomization function
     * 
     * @param {string} studyId - ID of the base/study form
     * @param {boolean} isRandomized - Whether randomization is active
     * @param {string|null} participantId - Optional participant ID for longitudinal
     */
    async function eipsiRandomizeForm(studyId, isRandomized, participantId = null) {
        const container = document.getElementById('randomized-form-container');
        const loading = document.getElementById('randomization-loading');
        const error = document.getElementById('randomization-error');

        if (!container) {
            console.error('EIPSI Randomization: Container not found');
            return;
        }

        // Hide loading/error states initially
        if (loading) loading.style.display = 'none';
        if (error) error.style.display = 'none';

        // If not randomized, load base form normally
        if (!isRandomized) {
            await loadFormInContainer(studyId, container);
            return;
        }

        // Show loading state
        if (loading) loading.style.display = 'block';

        try {
            // 1. Get randomization config
            const config = await fetchRandomizationConfig(studyId);
            
            if (!config.enabled || !config.forms || config.forms.length < 2) {
                throw new Error('Configuración de aleatorización inválida o insuficiente');
            }

            // Get or generate participant ID if not provided
            if (!participantId) {
                participantId = getParticipantId(studyId);
            }

            // 2. Check for manual assignment (override)
            const manualAssignment = await checkManualAssignment(studyId, participantId);
            let assignedFormId = manualAssignment;
            let isManualOverride = !!manualAssignment;
            let seed = null;

            // 3. If no manual override, randomize
            if (!assignedFormId) {
                // Generate reproducible seed
                seed = generateReproducibleSeed(studyId, participantId);
                
                // Randomly assign form
                assignedFormId = randomAssignForm(config.forms, seed);
                
                // Persist assignment
                await persistAssignment(studyId, participantId, assignedFormId, seed, false);
            }

            if (!assignedFormId) {
                throw new Error('No se pudo asignar un formulario');
            }

            // 4. Load the assigned form
            await loadFormInContainer(assignedFormId, container);

            // 5. Store assignment info for response metadata
            window.eipsiFormMetadata = window.eipsiFormMetadata || {};
            window.eipsiFormMetadata.randomAssignment = {
                study_id: studyId,
                participant_id: participantId,
                assigned_form_id: assignedFormId,
                is_manual_override: isManualOverride,
                seed: seed,
                timestamp: new Date().toISOString()
            };

            // Hide loading state
            if (loading) loading.style.display = 'none';

        } catch (error) {
            console.error('EIPSI Randomization Error:', error);
            if (loading) loading.style.display = 'none';
            if (error) {
                error.innerHTML = `<p style="color: #d63638; padding: 1rem; background: #fef7f1; border-left: 4px solid #d63638;">
                    <strong><?php echo esc_js(__('Error en asignación', 'eipsi-forms')); ?>:</strong><br>
                    ${error.message}<br><br>
                    <button onclick="location.reload()" style="padding: 0.5rem 1rem; background: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer;">
                        <?php echo esc_js(__('Recargar página', 'eipsi-forms')); ?>
                    </button>
                </p>`;
                error.style.display = 'block';
            }
        }
    }

    /**
     * Fetch randomization configuration from server
     */
    async function fetchRandomizationConfig(studyId) {
        const formData = new FormData();
        formData.append('action', 'eipsi_get_randomization_config');
        formData.append('form_id', studyId);
        formData.append('nonce', window.eipsiEditorData?.nonce || '');

        const response = await fetch(window.eipsiEditorData?.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.data || 'Error loading randomization config');
        }

        return data.data;
    }

    /**
     * Check for manual assignment override
     */
    async function checkManualAssignment(studyId, participantId) {
        if (!participantId) return null;

        const formData = new FormData();
        formData.append('action', 'eipsi_check_manual_assignment');
        formData.append('form_id', studyId);
        formData.append('participant_id', participantId);
        formData.append('nonce', window.eipsiEditorData?.nonce || '');

        const response = await fetch(window.eipsiEditorData?.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (!data.success) {
            console.warn('Manual assignment check failed:', data.data);
            return null;
        }

        return data.data; // null or form ID
    }

    /**
     * Generate reproducible seed for deterministic randomization
     */
    function generateReproducibleSeed(studyId, participantId) {
        // If participant ID exists, use it for reproducibility
        if (participantId) {
            return hashCode(`${studyId}_${participantId}_eipsi_v1`);
        }
        
        // Otherwise, use localStorage to persist seed for this study
        const storageKey = `eipsi_seed_${studyId}`;
        let seed = localStorage.getItem(storageKey);
        
        if (!seed) {
            // Generate cryptographically secure random seed
            seed = cryptoRandomString();
            localStorage.setItem(storageKey, seed);
        }
        
        return seed;
    }

    /**
     * Generate cryptographically secure random string
     */
    function cryptoRandomString() {
        if (window.crypto && window.crypto.getRandomValues) {
            const array = new Uint32Array(4);
            window.crypto.getRandomValues(array);
            return array.join('');
        }
        // Fallback for older browsers
        return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    }

    /**
     * Hash function for generating numeric seeds
     */
    function hashCode(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        return Math.abs(hash).toString();
    }

    /**
     * Randomly assign form using seeded RNG
     */
    function randomAssignForm(formsList, seed) {
        if (!formsList || formsList.length === 0) {
            throw new Error('No forms available for randomization');
        }

        // Use seeded RNG for reproducibility
        const rng = seededRandom(seed);
        const randomIndex = Math.floor(rng() * formsList.length);
        
        return formsList[randomIndex].id;
    }

    /**
     * Seeded random number generator for reproducibility
     * @param {string} seed - String seed
     * @returns {function} RNG function that returns numbers between 0 and 1
     */
    function seededRandom(seed) {
        // Mulberry32 PRNG algorithm
        let h = 0;
        for (let i = 0; i < seed.length; i++) {
            h = Math.imul(31, h) + seed.charCodeAt(i) | 0;
        }
        
        let state = h >>> 0;
        
        return function() {
            let t = state += 0x6D2B79F5;
            t = Math.imul(t ^ t >>> 15, t | 1);
            t ^= t + Math.imul(t ^ t >>> 7, t | 61);
            return ((t ^ t >>> 14) >>> 0) / 4294967296;
        };
    }

    /**
     * Persist assignment to server
     */
    async function persistAssignment(studyId, participantId, assignedFormId, seed, isManual = false) {
        const formData = new FormData();
        formData.append('action', 'eipsi_persist_assignment');
        formData.append('form_id', studyId);
        formData.append('assigned_form_id', assignedFormId);
        formData.append('participant_id', participantId);
        formData.append('seed', seed || '');
        formData.append('is_manual', isManual ? '1' : '0');
        formData.append('nonce', window.eipsiEditorData?.nonce || '');

        const response = await fetch(window.eipsiEditorData?.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (!data.success) {
            console.warn('Failed to persist assignment:', data.data);
            // Non-critical error, continue
        }

        return data.success;
    }

    /**
     * Get or generate participant ID
     */
    function getParticipantId(studyId) {
        // Try URL parameter first
        const urlParams = new URLSearchParams(window.location.search);
        const urlParticipantId = urlParams.get('participant_id');
        if (urlParticipantId) return urlParticipantId;

        // Try localStorage for this study
        const storageKey = `eipsi_participant_${studyId}`;
        let participantId = localStorage.getItem(storageKey);
        
        if (!participantId) {
            // Generate a simple participant ID (not cryptographically secure, but unique enough)
            participantId = 'p' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem(storageKey, participantId);
        }
        
        return participantId;
    }

    /**
     * Load form into container
     */
    async function loadFormInContainer(formId, container) {
        // Use the existing form load mechanism
        if (typeof eipsiLoadForm === 'function') {
            // If there's a dedicated form loader function
            await eipsiLoadForm(formId, container);
        } else {
            // Fallback: use AJAX
            const formData = new FormData();
            formData.append('action', 'eipsi_load_form');
            formData.append('form_id', formId);
            formData.append('nonce', window.eipsiEditorData?.nonce || '');

            const response = await fetch(window.eipsiEditorData?.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Error loading form');
            }

            container.innerHTML = data.data;
        }

        // Trigger form ready event
        const event = new CustomEvent('eipsiFormReady', {
            detail: { formId: formId }
        });
        document.dispatchEvent(event);
    }

    // Expose function to global scope
    window.eipsiRandomizeForm = eipsiRandomizeForm;

    // Auto-initialize on DOM ready (fallback for direct script loading)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('eipsi-randomization-container');
            if (container && typeof window.eipsiRandomizeForm === 'function') {
                const studyId = container.dataset.studyId;
                const participantId = container.dataset.participantId;
                const isRandomized = window.location.search.includes('eipsi_random=true');
                
                if (studyId) {
                    eipsiRandomizeForm(studyId, isRandomized, participantId);
                }
            }
        });
    }

})();