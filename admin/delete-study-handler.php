<?php
/**
 * AJAX Handler: Delete Study
 *
 * DELETES a study and ALL related data from wp_survey_studies table
 * This is irreversible!
 *
 * @since 1.5.3
 */

add_action('wp_ajax_eipsi_delete_study', 'eipsi_ajax_delete_study');

function eipsi_ajax_delete_study() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes', 'eipsi-forms'));
    }

    global $wpdb;

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;

    if (!$study_id) {
        wp_send_json_error(__('ID de estudio inválido', 'eipsi-forms'));
    }

    // Verify study exists
    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT id, study_name, study_code FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $study_id
    ));

    if (!$study) {
        wp_send_json_error(__('Estudio no encontrado', 'eipsi-forms'));
    }

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
        // Delete email logs
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}survey_email_log WHERE survey_id = %d",
            $study_id
        ));

        // Delete assignments (CASCADE will handle participants)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}survey_assignments WHERE study_id = %d",
            $study_id
        ));

        // Delete waves
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}survey_waves WHERE study_id = %d",
            $study_id
        ));

        // Delete magic links
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}survey_magic_links WHERE survey_id = %d",
            $study_id
        ));

        // Delete sessions
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}survey_sessions WHERE survey_id = %d",
            $study_id
        ));

        // Delete participants
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d",
            $study_id
        ));

        // Finally, delete the study
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}survey_studies WHERE id = %d",
            $study_id
        ));

        // Commit transaction
        $wpdb->query('COMMIT');

        error_log(sprintf(
            '[EIPSI Forms] Study deleted: %s (ID: %d) by user %d',
            $study->study_name,
            $study_id,
            get_current_user_id()
        ));

        wp_send_json_success(array(
            'message' => sprintf(__('Estudio "%s" eliminado correctamente', 'eipsi-forms'), $study->study_name)
        ));

    } catch (Exception $e) {
        // Rollback on error
        $wpdb->query('ROLLBACK');

        error_log(sprintf(
            '[EIPSI Forms] Error deleting study %d: %s',
            $study_id,
            $e->getMessage()
        ));

        wp_send_json_error(__('Error al eliminar el estudio: ' . $e->getMessage(), 'eipsi-forms'));
    }
}
