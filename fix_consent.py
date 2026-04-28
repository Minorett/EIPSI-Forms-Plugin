import sys
import os

file_path = 'admin/ajax-handlers.php'
if not os.path.exists(file_path):
    print(f"Error: {file_path} not found")
    sys.exit(1)

with open(file_path, 'r') as f:
    content = f.read()

old_func = """function eipsi_save_consent_decision_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eipsi_forms_nonce')) {
        wp_send_json_error(array('message' => __('Invalid nonce', 'eipsi-forms')));
        return;
    }
    
    $form_id = sanitize_text_field($_POST['form_id'] ?? '');
    $decision = sanitize_text_field($_POST['decision'] ?? '');
    $participant_id = sanitize_text_field($_POST['participant_id'] ?? '');
    
    if (empty($form_id) || !in_array($decision, array('accepted', 'declined'))) {
        wp_send_json_error(array('message' => __('Invalid parameters', 'eipsi-forms')));
        return;
    }
    
    // Get participant_id from session if not provided
    if (empty($participant_id)) {
        $participant_id = eipsi_get_current_participant_id();
    }
    
    global $wpdb;

    // Resolve numeric template ID
    $template_id = is_numeric($form_id) ? intval($form_id) : 0;
    if (!$template_id) {
        $template_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ('eipsi_form_template', 'eipsi_form', 'page') LIMIT 1",
            $form_id
        ));
    }
    
    // v2.5.6: PRIORIDAD DE CONTEXTO - Invertida para evitar colisiones por form_id "default"
    // 1. Intentar por sesión activa (Lo más confiable si el usuario ya inició sesión)
    $study_id = eipsi_get_current_survey_id();

    // 2. Rescate por Participant ID (Si no hay sesión pero tenemos el ID del participante)
    if (!$study_id && !empty($participant_id) && is_numeric($participant_id)) {
        $study_id = $wpdb->get_var($wpdb->prepare(
            "SELECT survey_id FROM {$wpdb->prefix}survey_participants WHERE id = %d LIMIT 1",
            intval($participant_id)
        ));
    }

    // 3. Último recurso: Adivinar por el form_id (Origen del error actual)
    if (!$study_id) {
        $study_id = eipsi_get_study_id_for_form($form_id);
    }
    
    if ($study_id) {
        // Longitudinal study: save to wp_survey_participants
        $table = $wpdb->prefix . 'survey_participants';
        
        $data = array(
            'consent_decision' => $decision,
            'consent_decided_at' => current_time('mysql'),
            'consent_ip_address' => eipsi_get_client_ip(),
            'consent_user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'consent_context' => 'T1_consent_block',
        );
        
        // If declined, also set blocked_survey_id
        if ($decision === 'declined') {
            $data['consent_blocked_survey_id'] = $template_id ?: $form_id;
        }
        
        $existing_participant = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE id = %d AND survey_id = %d LIMIT 1",
            $participant_id,
            $study_id
        ));

        if (!$existing_participant) {
            wp_send_json_error(array('message' => __('Participant not found for this study', 'eipsi-forms')));
            return;
        }

        $data['status'] = ($decision === 'declined') ? 'consent_declined' : 'active';

        $result = $wpdb->update(
            $table,
            $data,
            array(
                'id' => $participant_id,
                'survey_id' => $study_id,
            )
        );

        if ($result === false) {
            wp_send_json_error(array('message' => __('Could not save consent decision', 'eipsi-forms')));
            return;
        }
    } else {
        // Standalone form: also save to wp_survey_participants (NOT assignments)
        $table = $wpdb->prefix . 'survey_participants';
        $context_id = $template_id ?: $form_id;
        
        $data = array(
            'consent_decision' => $decision,
            'consent_decided_at' => current_time('mysql'),
            'consent_ip_address' => eipsi_get_client_ip(),
            'consent_user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'consent_context' => 'T1_consent_block',
        );
        
        $where = array(
            'survey_id' => $context_id,
            'id' => $participant_id,
        );
        
        $result = $wpdb->update($table, $data, $where);
        
        // If no existing record, and we have a participant_id, try to insert if it's a numeric ID
        if ($result === false || $result === 0) {
            if ($participant_id) {
                $data['survey_id'] = $context_id;
                $data['id'] = $participant_id;
                $data['status'] = ($decision === 'declined') ? 'consent_declined' : 'active';
                $wpdb->insert($table, $data);
            }
        }
    }
    
    // Log the decision
    if (function_exists('eipsi_log_audit')) {
        eipsi_log_audit('consent_decision', array(
            'form_id' => $form_id,
            'participant_id' => $participant_id,
            'decision' => $decision,
            'study_id' => $study_id ?? null,
        ));
    }
    
    // Prepare redirect URL for declined consent
    $redirect_url = null;
    if ($decision === 'declined') {
        $study_url = '';

        if ($study_id) {
            // Utilizar el helper que busca por meta eipsi_study_id (inmune a colisiones de texto)
            $study_url = function_exists('eipsi_get_study_page_url') 
                ? eipsi_get_study_page_url($study_id) 
                : '';
        }

        if (empty($study_url)) {
            $study_url = home_url('/');
        }

        $redirect_url = add_query_arg(array('consent' => 'declined'), $study_url);
        
        error_log("[EIPSI-CONSENT] Decision declined - Redirecting to: {$redirect_url} (Study ID: {$study_id})");
        
        // Destruir sesión SOLO después de haber procesado redirección y logs
        if (class_exists('EIPSI_Auth_Service')) {
            EIPSI_Auth_Service::destroy_session();
        }
    }
    
    error_log("[EIPSI-CONSENT] Sending response with redirect: {$redirect_url}");
    
    wp_send_json_success(array(
        'message' => __('Decision saved', 'eipsi-forms'),
        'decision' => $decision,
        'redirect' => $redirect_url,
    ));
}"""

new_func = """function eipsi_save_consent_decision_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eipsi_forms_nonce')) {
        wp_send_json_error(array('message' => __('Invalid nonce', 'eipsi-forms')));
        return;
    }
    
    $form_id = sanitize_text_field($_POST['form_id'] ?? '');
    $decision = sanitize_text_field($_POST['decision'] ?? '');
    $participant_id = sanitize_text_field($_POST['participant_id'] ?? '');
    $participant_source = !empty($participant_id) ? 'POST' : 'UNKNOWN';
    
    if (empty($form_id) || !in_array($decision, array('accepted', 'declined'))) {
        wp_send_json_error(array('message' => __('Invalid parameters', 'eipsi-forms')));
        return;
    }
    
    // Get participant_id from session if not provided
    if (empty($participant_id)) {
        $participant_id = eipsi_get_current_participant_id();
        $participant_source = 'SESSION/HELPER';
    }
    
    global $wpdb;

    // Resolve numeric template ID
    $template_id = is_numeric($form_id) ? intval($form_id) : 0;
    if (!$template_id) {
        $template_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ('eipsi_form_template', 'eipsi_form', 'page') LIMIT 1",
            $form_id
        ));
    }
    
    // v2.5.6: PRIORIDAD DE CONTEXTO - Invertida para evitar colisiones por form_id "default"
    // 1. Intentar por sesión activa (Lo más confiable si el usuario ya inició sesión)
    $study_id = eipsi_get_current_survey_id();
    $study_source = $study_id ? 'SESSION' : 'NONE';

    // 2. Rescate por Participant ID (Si no hay sesión pero tenemos el ID del participante)
    if (!$study_id && !empty($participant_id) && is_numeric($participant_id)) {
        $study_id = $wpdb->get_var($wpdb->prepare(
            "SELECT survey_id FROM {$wpdb->prefix}survey_participants WHERE id = %d LIMIT 1",
            intval($participant_id)
        ));
        if ($study_id) { $study_source = 'PARTICIPANT_LOOKUP'; }
    }

    // 3. Último recurso: Adivinar por el form_id (Origen del error actual)
    if (!$study_id) {
        $study_id = eipsi_get_study_id_for_form($form_id);
        if ($study_id) { $study_source = 'FORM_ID_FALLBACK'; }
    }

    // Instrumentación detallada antes de la validación
    error_log(sprintf(
        '[EIPSI-CONSENT-DEBUG] Decision Handler: FormID=%s (TemplateID=%d), ParticipantID=%s (Source=%s), StudyID=%s (Source=%s)',
        $form_id,
        $template_id,
        $participant_id,
        $participant_source,
        $study_id ?: 'NULL',
        $study_source
    ));
    
    if ($study_id) {
        // Longitudinal study: save to wp_survey_participants
        $table = $wpdb->prefix . 'survey_participants';
        
        $data = array(
            'consent_decision' => $decision,
            'consent_decided_at' => current_time('mysql'),
            'consent_ip_address' => eipsi_get_client_ip(),
            'consent_user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'consent_context' => 'T1_consent_block',
        );
        
        // If declined, also set blocked_survey_id
        if ($decision === 'declined') {
            $data['consent_blocked_survey_id'] = $template_id ?: $form_id;
        }
        
        $validate_query = $wpdb->prepare(
            "SELECT id FROM {$table} WHERE id = %d AND survey_id = %d LIMIT 1",
            $participant_id,
            $study_id
        );
        
        error_log("[EIPSI-CONSENT-DEBUG] Validation SQL: " . $validate_query);
        
        $existing_participant = $wpdb->get_var($validate_query);

        if (!$existing_participant) {
            error_log(sprintf('[EIPSI-CONSENT-ERROR] Participant %s not found for Study %s', $participant_id, $study_id));
            wp_send_json_error(array('message' => __('Participant not found for this study', 'eipsi-forms')));
            return;
        }

        $data['status'] = ($decision === 'declined') ? 'consent_declined' : 'active';

        $result = $wpdb->update(
            $table,
            $data,
            array(
                'id' => $participant_id,
                'survey_id' => $study_id,
            )
        );

        if ($result === false) {
            wp_send_json_error(array('message' => __('Could not save consent decision', 'eipsi-forms')));
            return;
        }
    } else {
        // Standalone form: also save to wp_survey_participants (NOT assignments)
        $table = $wpdb->prefix . 'survey_participants';
        $context_id = $template_id ?: $form_id;
        
        $data = array(
            'consent_decision' => $decision,
            'consent_decided_at' => current_time('mysql'),
            'consent_ip_address' => eipsi_get_client_ip(),
            'consent_user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'consent_context' => 'T1_consent_block',
        );
        
        $where = array(
            'survey_id' => $context_id,
            'id' => $participant_id,
        );
        
        error_log(sprintf('[EIPSI-CONSENT-DEBUG] Standalone Fallback: ContextID=%s, ParticipantID=%s', $context_id, $participant_id));
        
        $result = $wpdb->update($table, $data, $where);
        
        // If no existing record, and we have a participant_id, try to insert if it's a numeric ID
        if ($result === false || $result === 0) {
            if ($participant_id && is_numeric($participant_id)) {
                $data['survey_id'] = $context_id;
                $data['id'] = $participant_id;
                $data['status'] = ($decision === 'declined') ? 'consent_declined' : 'active';
                $wpdb->insert($table, $data);
            }
        }
    }
    
    // Log the decision
    if (function_exists('eipsi_log_audit')) {
        eipsi_log_audit('consent_decision', array(
            'form_id' => $form_id,
            'participant_id' => $participant_id,
            'decision' => $decision,
            'study_id' => $study_id ?? null,
        ));
    }
    
    // Prepare redirect URL for declined consent
    $redirect_url = null;
    if ($decision === 'declined') {
        $study_url = '';

        if ($study_id) {
            // Utilizar el helper que busca por meta eipsi_study_id (inmune a colisiones de texto)
            if (!function_exists('eipsi_get_study_page_url')) {
                require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/setup-wizard.php';
            }
            
            $study_url = function_exists('eipsi_get_study_page_url') 
                ? eipsi_get_study_page_url($study_id) 
                : '';
        }

        if (empty($study_url)) {
            $study_url = home_url('/');
        }

        $redirect_url = add_query_arg(array('consent' => 'declined'), $study_url);
        
        error_log("[EIPSI-CONSENT] Decision declined - Redirecting to: {$redirect_url} (Study ID: {$study_id})");
        
        // Destruir sesión SOLO después de haber procesado redirección y logs
        if (class_exists('EIPSI_Auth_Service')) {
            EIPSI_Auth_Service::destroy_session();
        }
    }
    
    error_log("[EIPSI-CONSENT] Sending response with redirect: {$redirect_url}");
    
    wp_send_json_success(array(
        'message' => __('Decision saved', 'eipsi-forms'),
        'decision' => $decision,
        'redirect' => $redirect_url,
    ));
}"""

if old_func in content:
    new_content = content.replace(old_func, new_func)
    with open(file_path, 'w') as f:
        f.write(new_content)
    print("Success")
else:
    print("Failed to find function")
