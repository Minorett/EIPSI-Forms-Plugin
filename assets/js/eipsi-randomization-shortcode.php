<?php
/**
 * EIPSI Forms Randomization Shortcode
 * 
 * Public shortcode for randomized form assignment
 * Usage: [eipsi_randomized_form study_id="123" show_meta="true"]
 * 
 * @package EIPSI_Forms
 * @since 1.2.3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [eipsi_randomized_form]
 * 
 * Displays a form with random assignment based on study configuration
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function eipsi_randomized_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'study_id'  => '',
        'show_meta' => 'true'
    ), $atts, 'eipsi_randomized_form');

    // Get study_id from attribute or URL parameter
    $study_id = $atts['study_id'] ?: ($_GET['study_id'] ?? false);
    
    if (!$study_id) {
        return '<p style="color: #d63638; padding: 1rem; background: #fef7f1; border-left: 4px solid #d63638;">' . 
               __('Error: No study_id provided for randomization', 'eipsi-forms') . '</p>';
    }

    // Get participant_id from URL if provided (for longitudinal studies)
    $participant_id = $_GET['participant_id'] ?? null;
    
    // Check if randomization is enabled for this study
    $random_config = get_post_meta($study_id, '_eipsi_random_config', true);
    
    if (empty($random_config['enabled'])) {
        // If not enabled, load the base form normally
        return eipsi_render_form_shortcode_markup($study_id);
    }

    ob_start();
    ?>
    <div id="eipsi-randomization-container" 
         class="eipsi-randomization-wrapper" 
         data-study-id="<?php echo esc_attr($study_id); ?>"
         data-participant-id="<?php echo esc_attr($participant_id ?: ''); ?>"
         data-show-meta="<?php echo esc_attr($atts['show_meta']); ?>">
        
        <?php if ($atts['show_meta'] === 'true'): ?>
        <div class="randomization-notice">
            <p class="randomization-notice-text">
                ℹ️ <?php _e('Este estudio utiliza aleatorización: cada participante recibe un formulario asignado aleatoriamente.', 'eipsi-forms'); ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if (!$participant_id && isset($_GET['eipsi_random']) && $_GET['eipsi_random'] === 'true'): ?>
        <div class="randomization-participant-input" style="margin-bottom: 1.5rem; padding: 1rem; background: #f9f9f9; border-radius: 4px;">
            <label for="eipsi-participant-id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                <?php _e('Código de participante (opcional):', 'eipsi-forms'); ?>
            </label>
            <input type="text" 
                   id="eipsi-participant-id" 
                   class="eipsi-participant-input"
                   placeholder="<?php esc_attr_e('Ingresá tu código si ya participaste', 'eipsi-forms'); ?>"
                   style="width: 100%; max-width: 300px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 3px;">
            <p style="margin: 0.5rem 0 0; font-size: 0.875rem; color: #666;">
                <?php _e('Si ya participaste, ingresá tu código para recibir el mismo formulario', 'eipsi-forms'); ?>
            </p>
        </div>
        <?php endif; ?>

        <div id="randomization-loading" class="randomization-loading">
            <div class="randomization-spinner"></div>
            <p><?php _e('Asignando formulario...', 'eipsi-forms'); ?></p>
        </div>

        <div id="randomized-form-container" class="randomized-form-container"></div>

        <div id="randomization-error" class="randomization-error" style="display: none;"></div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('eipsi-randomization-container');
        if (!container) return;
        
        const studyId = container.dataset.studyId;
        const participantId = container.dataset.participantId || getParticipantIdFromInput();
        const isRandomized = window.location.search.includes('eipsi_random=true');
        
        // Load the randomized form
        if (typeof eipsiRandomizeForm === 'function') {
            eipsiRandomizeForm(studyId, isRandomized, participantId);
        } else {
            console.error('EIPSI Randomization: eipsiRandomizeForm function not found');
            document.getElementById('randomization-loading').style.display = 'none';
            document.getElementById('randomization-error').innerHTML = 
                '<p style="color: #d63638;"><?php _e('Error: No se pudo cargar el sistema de aleatorización', 'eipsi-forms'); ?></p>';
            document.getElementById('randomization-error').style.display = 'block';
        }
    });

    function getParticipantIdFromInput() {
        const input = document.getElementById('eipsi-participant-id');
        return input ? input.value.trim() : null;
    }

    // Update participant ID if user enters it
    document.addEventListener('input', function(e) {
        if (e.target.id === 'eipsi-participant-id') {
            const container = document.getElementById('eipsi-randomization-container');
            if (container) {
                container.dataset.participantId = e.target.value.trim();
            }
        }
    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('eipsi_randomized_form', 'eipsi_randomized_form_shortcode');

/**
 * Helper function to render form markup
 * Reuses the existing shortcode rendering logic
 */
function eipsi_render_form_shortcode_markup($form_id) {
    // Use the existing form render helper
    if (function_exists('eipsi_render_form_block')) {
        $attributes = array(
            'formId' => $form_id,
            'showTitle' => true
        );
        return eipsi_render_form_block($attributes);
    }
    
    // Fallback: try to load form templates
    $template = get_post($form_id);
    if (!$template || $template->post_type !== 'eipsi_form_template') {
        return '<p style="color: #d63638;">' . 
               sprintf(__('Formulario con ID %s no encontrado', 'eipsi-forms'), $form_id) . '</p>';
    }
    
    // This should use the same rendering logic as the form block
    // For now, return a placeholder
    return '<div class="eipsi-form-placeholder" data-form-id="' . esc_attr($form_id) . '">
            <p>' . sprintf(__('Cargando formulario %s...', 'eipsi-forms'), esc_html($template->post_title)) . '</p>
           </div>';
}