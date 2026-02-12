<?php
/**
 * EIPSI Setup Wizard Controller
 * 
 * Gestiona el flujo paso-a-paso para crear estudios longitudinales.
 * Maneja GET/POST requests, validación, guardado en transient y activación.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Delay session start until WordPress admin is initialized to avoid headers already sent warning
add_action('admin_init', function() {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
});

/**
 * Display Setup Wizard Page
 */
function eipsi_display_setup_wizard_page() {
    // Require wizard validations
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/wizard-validators.php';
    
    // Start wizard session
    eipsi_start_wizard_session();
    
    // Handle POST requests (form submissions)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = eipsi_handle_wizard_submission();
        if ($result['success']) {
            wp_redirect($result['redirect_url']);
            exit;
        }
        $errors = $result['errors'];
    }
    
    // Get current step
    $current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
    $current_step = max(1, min(5, $current_step)); // Clamp between 1-5
    
    // Get wizard data from transient
    $wizard_data = eipsi_get_wizard_data();
    
    // Get available forms for dropdowns
    $available_forms = eipsi_get_available_forms();
    
    // Get admin users for investigator selection
    $admin_users = eipsi_get_admin_users();
    
    // Load the modern wizard template
    include EIPSI_FORMS_PLUGIN_DIR . 'admin/templates/longitudinal-study-wizard.php';
}

/**
 * Start wizard session and initialize transient
 */
function eipsi_start_wizard_session() {
    // Initialize wizard transient if not exists
    $transient_key = eipsi_get_wizard_transient_key();
    if (false === get_transient($transient_key)) {
        $initial_data = array(
            'step_1' => array(), // Basic info
            'step_2' => array(), // Waves configuration
            'step_3' => array(), // Timing configuration
            'step_4' => array(), // Participants configuration
            'step_5' => array(), // Summary/activation
            'current_step' => 1,
            'created_at' => current_time('mysql'),
            'last_updated' => current_time('mysql')
        );
        
        set_transient($transient_key, $initial_data, HOUR_IN_SECONDS * 2); // 2 hours
    }
}

/**
 * Get wizard transient key based on user session
 */
function eipsi_get_wizard_transient_key() {
    $user_id = get_current_user_id();
    $session_id = session_id();
    
    return 'eipsi_wizard_' . $user_id . '_' . substr($session_id, 0, 10);
}

/**
 * Get wizard data from transient
 */
function eipsi_get_wizard_data() {
    $transient_key = eipsi_get_wizard_transient_key();
    $data = get_transient($transient_key);
    
    return $data ? $data : array(
        'step_1' => array(),
        'step_2' => array(),
        'step_3' => array(),
        'step_4' => array(),
        'step_5' => array(),
        'current_step' => 1
    );
}

/**
 * Save wizard step data to transient
 */
function eipsi_save_wizard_step($step_number, $step_data) {
    $transient_key = eipsi_get_wizard_transient_key();
    $wizard_data = eipsi_get_wizard_data();
    
    // Update step data
    $wizard_data['step_' . $step_number] = $step_data;
    $wizard_data['current_step'] = max($wizard_data['current_step'], $step_number);
    $wizard_data['last_updated'] = current_time('mysql');
    
    // Save to transient
    set_transient($transient_key, $wizard_data, HOUR_IN_SECONDS * 2);
    
    return true;
}

/**
 * Handle wizard form submission
 */
function eipsi_handle_wizard_submission() {
    // Verify nonce
    if (!isset($_POST['eipsi_wizard_nonce']) || !wp_verify_nonce($_POST['eipsi_wizard_nonce'], 'eipsi_wizard_action')) {
        return array(
            'success' => false,
            'errors' => array('Security error. Please reload the page.')
        );
    }
    
    $action = sanitize_text_field($_POST['wizard_action']);
    $current_step = intval($_POST['current_step']);
    
    switch ($action) {
        case 'save_step':
            return eipsi_save_step_submission($current_step);
            
        case 'activate_study':
            return eipsi_activate_study_submission();
            
        default:
            return array(
                'success' => false,
                'errors' => array('Invalid action.')
            );
    }
}

/**
 * Save individual step submission
 */
function eipsi_save_step_submission($step_number) {
    $step_data = $_POST;
    
    // Validate step data
    $validation_result = eipsi_validate_step_data($step_number, $step_data);
    
    if (!$validation_result['valid']) {
        return array(
            'success' => false,
            'errors' => $validation_result['errors']
        );
    }
    
    // Sanitize step data
    $sanitized_data = eipsi_sanitize_step_data($step_number, $step_data);
    
    // Save to transient
    eipsi_save_wizard_step($step_number, $sanitized_data);
    
    // Determine next step
    $next_step = min($step_number + 1, 5);
    
    // Redirect to next step
    $redirect_url = admin_url('admin.php?page=eipsi-new-study&step=' . $next_step);
    
    return array(
        'success' => true,
        'redirect_url' => $redirect_url,
        'message' => 'Step saved successfully.'
    );
}

/**
 * Activate study submission
 */
function eipsi_activate_study_submission() {
    $wizard_data = eipsi_get_wizard_data();
    
    // Validate all steps are complete
    for ($i = 1; $i <= 4; $i++) {
        if (empty($wizard_data['step_' . $i])) {
            return array(
                'success' => false,
                'errors' => array('You must complete all steps before activating the study.')
            );
        }
    }
    
    // Validate activation confirmation
    if (!isset($_POST['activation_confirmed']) || $_POST['activation_confirmed'] !== '1') {
        return array(
            'success' => false,
            'errors' => array('You must confirm study activation.')
        );
    }
    
    // Create the study
    $study_id = eipsi_create_study_from_wizard($wizard_data);
    
    if (!$study_id) {
        return array(
            'success' => false,
            'errors' => array('Error creating the study. Please try again.')
        );
    }
    
    // Clear wizard transient
    $transient_key = eipsi_get_wizard_transient_key();
    delete_transient($transient_key);
    
    // Redirect to study dashboard (to be implemented in Task 1.5.2)
    $redirect_url = admin_url('admin.php?page=eipsi-results&study_id=' . $study_id);
    
    return array(
        'success' => true,
        'redirect_url' => $redirect_url,
        'message' => 'Study created successfully.'
    );
}

/**
 * Create study from wizard data
 */
function eipsi_create_study_from_wizard($wizard_data) {
    global $wpdb;
    
    // Get study data from wizard
    $step_1 = $wizard_data['step_1'];
    $step_2 = $wizard_data['step_2'];
    $step_3 = $wizard_data['step_3'];
    $step_4 = $wizard_data['step_4'];
    
    // Generate unique study code
    $study_code = eipsi_generate_unique_study_code($step_1['study_code']);
    
    // Insert study record
    $table_name = $wpdb->prefix . 'survey_studies';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'study_code' => $study_code,
            'study_name' => $step_1['study_name'],
            'description' => $step_1['description'],
            'principal_investigator_id' => $step_1['principal_investigator_id'],
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
    );
    
    if (!$result) {
        return false;
    }
    
    $study_id = $wpdb->insert_id;
    
    // Create waves for this study
    eipsi_create_study_waves($study_id, $step_2, $step_3);
    
    // Store participant configuration
    eipsi_store_participant_config($study_id, $step_4);
    
    return $study_id;
}

/**
 * Generate unique study code
 */
function eipsi_generate_unique_study_code($requested_code) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'survey_studies';
    $code = $requested_code;
    $counter = 1;
    
    while (true) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE study_code = %s",
            $code
        ));
        
        if (!$existing) {
            return $code;
        }
        
        $counter++;
        $code = $requested_code . '-' . $counter;
    }
}

/**
 * Create waves for study
 * 
 * @param int   $study_id       Study ID
 * @param array $wave_config    Wave configuration from wizard step 2
 * @param array $timing_config  Timing configuration from wizard step 3
 * @return bool Success status
 */
function eipsi_create_study_waves($study_id, $wave_config, $timing_config) {
    if (empty($wave_config['waves_config']) || !is_array($wave_config['waves_config'])) {
        error_log('[EIPSI] No waves configured for study ' . $study_id);
        return false;
    }
    
    // Extract timing configuration with defaults
    $reminder_days = isset($timing_config['reminder_days']) ? absint($timing_config['reminder_days']) : 3;
    $retry_enabled = isset($timing_config['retry_enabled']) ? (int)(bool)$timing_config['retry_enabled'] : 1;
    $retry_days = isset($timing_config['retry_days']) ? absint($timing_config['retry_days']) : 7;
    $max_retries = isset($timing_config['max_retries']) ? absint($timing_config['max_retries']) : 3;
    
    $created_count = 0;
    
    foreach ($wave_config['waves_config'] as $index => $wave) {
        // Map wizard fields to wave service format
        $wave_data = array(
            'name' => sanitize_text_field($wave['name'] ?? ('Toma ' . ($index + 1))),
            'wave_index' => absint($wave['wave_index'] ?? ($index + 1)),
            'form_id' => absint($wave['form_template_id'] ?? 0),
            'is_mandatory' => isset($wave['is_required']) ? (int)(bool)$wave['is_required'] : 1,
            'status' => 'draft',
            'reminder_days' => $reminder_days,
            'retry_enabled' => $retry_enabled,
            'retry_days' => $retry_days,
            'max_retries' => $max_retries,
        );
        
        // Skip if no form_id
        if (empty($wave_data['form_id'])) {
            error_log('[EIPSI] Skipping wave ' . $wave_data['name'] . ' - no form template');
            continue;
        }
        
        // Create wave using service
        $result = EIPSI_Wave_Service::create_wave($study_id, $wave_data);
        
        if (is_wp_error($result)) {
            error_log('[EIPSI] Error creating wave: ' . $result->get_error_message());
        } else {
            $created_count++;
            error_log('[EIPSI] Created wave ID ' . $result . ' for study ' . $study_id);
        }
    }
    
    error_log('[EIPSI] Created ' . $created_count . ' waves for study ' . $study_id);
    return $created_count > 0;
}

/**
 * Store participant configuration
 */
function eipsi_store_participant_config($study_id, $participant_config) {
    // TODO: Store participant configuration in study settings
    // This could be stored in wp_options or a separate study_config table
    
    error_log('[EIPSI] Storing participant config for study ' . $study_id . ': ' . json_encode($participant_config));
    
    return true;
}

/**
 * Get available forms for dropdowns
 * 
 * Busca tanto en form templates como en páginas con formularios activos
 */
function eipsi_get_available_forms() {
    return eipsi_get_available_forms_for_wizard();
}

/**
 * Get admin users for investigator selection
 */
function eipsi_get_admin_users() {
    $users = get_users(array(
        'role__in' => array('administrator', 'editor'),
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    return $users;
}