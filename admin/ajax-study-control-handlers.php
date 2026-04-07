<?php
/**
 * AJAX Handler: Pause study
 * Sets study status to 'paused' - stops reminders and new submissions
 * 
 * @since 1.6.1
 */
add_action('wp_ajax_eipsi_pause_study', 'eipsi_pause_study_handler');

function eipsi_pause_study_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');
    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error(array('message' => 'No tienes permisos'));
    }
    
    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (!$study_id) {
        wp_send_json_error(array('message' => 'Study ID requerido'));
    }
    
    global $wpdb;
    $result = $wpdb->update(
        $wpdb->prefix . 'survey_studies',
        array('status' => 'paused'),
        array('id' => $study_id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        error_log("[EIPSI] Study {$study_id} paused by user " . get_current_user_id());
        wp_send_json_success(array(
            'message' => 'Estudio pausado correctamente',
            'status' => 'paused'
        ));
    } else {
        wp_send_json_error(array('message' => 'Error al pausar el estudio'));
    }
}

/**
 * AJAX Handler: Resume study
 * Sets study status back to 'active'
 * 
 * @since 1.6.1
 */
add_action('wp_ajax_eipsi_resume_study', 'eipsi_resume_study_handler');

function eipsi_resume_study_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');
    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error(array('message' => 'No tienes permisos'));
    }
    
    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (!$study_id) {
        wp_send_json_error(array('message' => 'Study ID requerido'));
    }
    
    global $wpdb;
    $result = $wpdb->update(
        $wpdb->prefix . 'survey_studies',
        array('status' => 'active'),
        array('id' => $study_id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        error_log("[EIPSI] Study {$study_id} resumed by user " . get_current_user_id());
        wp_send_json_success(array(
            'message' => 'Estudio reanudado correctamente',
            'status' => 'active'
        ));
    } else {
        wp_send_json_error(array('message' => 'Error al reanudar el estudio'));
    }
}

/**
 * AJAX Handler: Get study status counts for dashboard
 * Returns counts of participants by status for a study
 * 
 * @since 1.6.1
 */
add_action('wp_ajax_eipsi_get_study_status_counts', 'eipsi_get_study_status_counts_handler');

function eipsi_get_study_status_counts_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');
    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error(array('message' => 'No tienes permisos'));
    }
    
    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (!$study_id) {
        wp_send_json_error(array('message' => 'Study ID requerido'));
    }
    
    global $wpdb;
    
    // Get study status
    $study_status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $study_id
    ));
    
    // Count participants by status
    $active_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants 
         WHERE survey_id = %d AND is_active = 1",
        $study_id
    ));
    
    $completed_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT participant_id) FROM {$wpdb->prefix}survey_assignments 
         WHERE study_id = %d AND status = 'submitted'",
        $study_id
    ));
    
    $paused_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT participant_id) FROM {$wpdb->prefix}survey_assignments 
         WHERE study_id = %d AND status = 'paused'",
        $study_id
    ));
    
    $total_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d",
        $study_id
    ));
    
    wp_send_json_success(array(
        'study_status' => $study_status,
        'active' => intval($active_count),
        'completed' => intval($completed_count),
        'paused' => intval($paused_count),
        'total' => intval($total_count)
    ));
}
