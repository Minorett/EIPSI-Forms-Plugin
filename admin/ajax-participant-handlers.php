<?php
/**
 * EIPSI Forms - Participant AJAX Handlers
 * 
 * Handles AJAX requests for participant authentication:
 * - Registration
 * - Login
 * - Logout
 * - Session info
 * 
 * @package EIPSI_Forms
 * @since 1.5.5
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// PARTICIPANT AUTHENTICATION AJAX HANDLERS
// =============================================================================

/**
 * AJAX Handler: Participant Registration
 * 
 * Registers a new participant for a longitudinal study.
 * 
 * @since 1.5.5
 */
function eipsi_participant_register_handler() {
    // Verify nonce
    check_ajax_referer('eipsi_participant_auth', 'nonce');
    
    // Sanitize and validate inputs
    $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
    $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
    
    // Validate required fields
    if (empty($survey_id)) {
        wp_send_json_error(array(
            'message' => __('ID del estudio es requerido.', 'eipsi-forms'),
            'code' => 'missing_survey_id'
        ));
    }
    
    if (empty($email) || !is_email($email)) {
        wp_send_json_error(array(
            'message' => __('Por favor ingresa un email válido.', 'eipsi-forms'),
            'code' => 'invalid_email'
        ));
    }
    
    if (empty($password)) {
        wp_send_json_error(array(
            'message' => __('La contraseña es requerida.', 'eipsi-forms'),
            'code' => 'missing_password'
        ));
    }
    
    if (strlen($password) < 8) {
        wp_send_json_error(array(
            'message' => __('La contraseña debe tener al menos 8 caracteres.', 'eipsi-forms'),
            'code' => 'short_password'
        ));
    }
    
    // Ensure services are loaded
    if (!class_exists('EIPSI_Participant_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-service.php';
    }
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    
    // Create participant
    $metadata = array(
        'first_name' => $first_name,
        'last_name' => $last_name
    );
    
    $result = EIPSI_Participant_Service::create_participant($survey_id, $email, $password, $metadata);
    
    if (!$result['success']) {
        $error_messages = array(
            'invalid_email' => __('El email ingresado no es válido.', 'eipsi-forms'),
            'short_password' => __('La contraseña debe tener al menos 8 caracteres.', 'eipsi-forms'),
            'email_exists' => __('Este email ya está registrado en este estudio.', 'eipsi-forms'),
            'db_error' => __('Error al crear el registro. Intenta nuevamente.', 'eipsi-forms')
        );
        
        wp_send_json_error(array(
            'message' => isset($error_messages[$result['error']]) ? $error_messages[$result['error']] : __('Error en el registro.', 'eipsi-forms'),
            'code' => $result['error']
        ));
    }
    
    // Create session (auto-login after registration)
    $session_result = EIPSI_Auth_Service::create_session($result['participant_id'], $survey_id);
    
    if (!$session_result['success']) {
        // Registration succeeded but session creation failed
        // Still return success but without auto-login
        wp_send_json_success(array(
            'message' => __('¡Registro exitoso! Por favor inicia sesión.', 'eipsi-forms'),
            'participant_id' => $result['participant_id'],
            'auto_login' => false
        ));
    }
    
    // Get redirect URL
    $redirect_url = eipsi_get_participant_redirect_url($survey_id, $result['participant_id']);
    
    wp_send_json_success(array(
        'message' => __('¡Registro exitoso! Redirigiendo...', 'eipsi-forms'),
        'participant_id' => $result['participant_id'],
        'auto_login' => true,
        'redirect' => $redirect_url
    ));
}
add_action('wp_ajax_nopriv_eipsi_participant_register', 'eipsi_participant_register_handler');
add_action('wp_ajax_eipsi_participant_register', 'eipsi_participant_register_handler');

/**
 * AJAX Handler: Participant Login
 * 
 * Authenticates a participant and creates a session.
 * 
 * @since 1.5.5
 */
function eipsi_participant_login_handler() {
    // Verify nonce
    check_ajax_referer('eipsi_participant_auth', 'nonce');
    
    // Sanitize and validate inputs
    $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
    
    // Validate required fields
    if (empty($survey_id)) {
        wp_send_json_error(array(
            'message' => __('ID del estudio es requerido.', 'eipsi-forms'),
            'code' => 'missing_survey_id'
        ));
    }
    
    if (empty($email) || !is_email($email)) {
        wp_send_json_error(array(
            'message' => __('Por favor ingresa un email válido.', 'eipsi-forms'),
            'code' => 'invalid_email'
        ));
    }
    
    if (empty($password)) {
        wp_send_json_error(array(
            'message' => __('La contraseña es requerida.', 'eipsi-forms'),
            'code' => 'missing_password'
        ));
    }
    
    // Ensure services are loaded
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    
    // Authenticate
    $auth_result = EIPSI_Auth_Service::authenticate($survey_id, $email, $password);
    
    if (!$auth_result['success']) {
        $error_messages = array(
            'user_not_found' => __('Usuario no encontrado. Verifica tu email o regístrate.', 'eipsi-forms'),
            'user_inactive' => __('Tu cuenta está desactivada. Contacta al investigador.', 'eipsi-forms'),
            'invalid_credentials' => __('Email o contraseña incorrectos.', 'eipsi-forms')
        );
        
        wp_send_json_error(array(
            'message' => isset($error_messages[$auth_result['error']]) ? $error_messages[$auth_result['error']] : __('Error de autenticación.', 'eipsi-forms'),
            'code' => $auth_result['error']
        ));
    }
    
    // Create session
    $session_result = EIPSI_Auth_Service::create_session($auth_result['participant_id'], $survey_id);
    
    if (!$session_result['success']) {
        wp_send_json_error(array(
            'message' => __('Error al crear la sesión. Intenta nuevamente.', 'eipsi-forms'),
            'code' => 'session_error'
        ));
    }
    
    // Get redirect URL
    $redirect_url = eipsi_get_participant_redirect_url($survey_id, $auth_result['participant_id']);
    
    wp_send_json_success(array(
        'message' => __('¡Bienvenido! Redirigiendo...', 'eipsi-forms'),
        'participant_id' => $auth_result['participant_id'],
        'redirect' => $redirect_url
    ));
}
add_action('wp_ajax_nopriv_eipsi_participant_login', 'eipsi_participant_login_handler');
add_action('wp_ajax_eipsi_participant_login', 'eipsi_participant_login_handler');

/**
 * AJAX Handler: Participant Logout
 * 
 * Destroys the current session.
 * 
 * @since 1.5.5
 */
function eipsi_participant_logout_handler() {
    // Verify nonce
    check_ajax_referer('eipsi_participant_auth', 'nonce');
    
    // Ensure service is loaded
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    
    // Destroy session
    EIPSI_Auth_Service::destroy_session();
    
    wp_send_json_success(array(
        'message' => __('Sesión cerrada correctamente.', 'eipsi-forms'),
        'redirect' => home_url('/')
    ));
}
add_action('wp_ajax_nopriv_eipsi_participant_logout', 'eipsi_participant_logout_handler');
add_action('wp_ajax_eipsi_participant_logout', 'eipsi_participant_logout_handler');

/**
 * AJAX Handler: Participant Info
 * 
 * Returns information about the currently authenticated participant.
 * 
 * @since 1.5.5
 */
function eipsi_participant_info_handler() {
    // Verify nonce
    check_ajax_referer('eipsi_participant_auth', 'nonce');
    
    // Ensure services are loaded
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    if (!class_exists('EIPSI_Participant_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-service.php';
    }
    
    // Check if authenticated
    if (!EIPSI_Auth_Service::is_authenticated()) {
        wp_send_json_error(array(
            'message' => __('No hay sesión activa.', 'eipsi-forms'),
            'code' => 'not_authenticated'
        ));
    }
    
    // Get participant ID
    $participant_id = EIPSI_Auth_Service::get_current_participant();
    if (!$participant_id) {
        wp_send_json_error(array(
            'message' => __('Sesión inválida.', 'eipsi-forms'),
            'code' => 'invalid_session'
        ));
    }
    
    // Get participant data
    $participant = EIPSI_Participant_Service::get_by_id($participant_id);
    if (!$participant) {
        EIPSI_Auth_Service::destroy_session();
        wp_send_json_error(array(
            'message' => __('Participante no encontrado.', 'eipsi-forms'),
            'code' => 'participant_not_found'
        ));
    }
    
    // Get session info
    $session_info = EIPSI_Auth_Service::get_current_session_info();
    
    wp_send_json_success(array(
        'participant_id' => (int) $participant->id,
        'survey_id' => (int) $participant->survey_id,
        'email' => $participant->email,
        'first_name' => $participant->first_name,
        'last_name' => $participant->last_name,
        'is_active' => (bool) $participant->is_active,
        'last_login_at' => $participant->last_login_at,
        'session' => $session_info ? array(
            'expires_at' => $session_info->expires_at,
            'time_remaining_hours' => $session_info->time_remaining_hours
        ) : null
    ));
}
add_action('wp_ajax_nopriv_eipsi_participant_info', 'eipsi_participant_info_handler');
add_action('wp_ajax_eipsi_participant_info', 'eipsi_participant_info_handler');

/**
 * AJAX Handler: Request Magic Link
 * 
 * Sends a magic link to the participant's email for passwordless login.
 * 
 * @since 1.5.5
 */
function eipsi_request_magic_link_handler() {
    // Verify nonce
    check_ajax_referer('eipsi_participant_auth', 'nonce');
    
    // Sanitize inputs
    $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    
    // Validate
    if (empty($survey_id) || empty($email) || !is_email($email)) {
        wp_send_json_error(array(
            'message' => __('Por favor ingresa un email válido.', 'eipsi-forms'),
            'code' => 'invalid_input'
        ));
    }
    
    // Ensure services are loaded
    if (!class_exists('EIPSI_Participant_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-service.php';
    }
    if (!class_exists('EIPSI_MagicLinksService')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-magic-links-service.php';
    }
    if (!class_exists('EIPSI_Email_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-service.php';
    }
    
    // Check if participant exists
    $participant = EIPSI_Participant_Service::get_by_email($survey_id, $email);
    
    if (!$participant) {
        // Don't reveal if email exists or not for security
        wp_send_json_success(array(
            'message' => __('Si el email está registrado, recibirás un enlace de acceso.', 'eipsi-forms')
        ));
    }
    
    // Generate magic link
    $token = EIPSI_MagicLinksService::generate_magic_link($survey_id, $participant->id);
    
    if (!$token) {
        wp_send_json_error(array(
            'message' => __('Error al generar el enlace. Intenta nuevamente.', 'eipsi-forms'),
            'code' => 'token_generation_error'
        ));
    }
    
    // Send email with magic link
    $email_result = EIPSI_Email_Service::send_welcome_email($survey_id, $participant->id);
    
    if (!$email_result) {
        wp_send_json_error(array(
            'message' => __('Error al enviar el email. Intenta nuevamente.', 'eipsi-forms'),
            'code' => 'email_error'
        ));
    }
    
    wp_send_json_success(array(
        'message' => __('Se ha enviado un enlace de acceso a tu email.', 'eipsi-forms')
    ));
}
add_action('wp_ajax_nopriv_eipsi_request_magic_link', 'eipsi_request_magic_link_handler');
add_action('wp_ajax_eipsi_request_magic_link', 'eipsi_request_magic_link_handler');

/**
 * AJAX Handler: Validate Magic Link Token
 * 
 * Validates a magic link token from the URL.
 * 
 * @since 1.5.5
 */
function eipsi_validate_magic_link_token_handler() {
    // Get token from POST
    $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
    
    if (empty($token)) {
        wp_send_json_error(array(
            'message' => __('Token no proporcionado.', 'eipsi-forms'),
            'code' => 'empty_token'
        ));
    }
    
    // Ensure service is loaded
    if (!class_exists('EIPSI_MagicLinksService')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-magic-links-service.php';
    }
    
    // Validate token
    $result = EIPSI_MagicLinksService::validate_magic_link($token);
    
    if (!$result['valid']) {
        $error_messages = array(
            'empty_token' => __('Token no proporcionado.', 'eipsi-forms'),
            'not_found' => __('Token inválido.', 'eipsi-forms'),
            'already_used' => __('Este enlace ya fue utilizado.', 'eipsi-forms'),
            'expired' => __('Este enlace ha expirado. Solicita uno nuevo.', 'eipsi-forms')
        );
        
        wp_send_json_error(array(
            'message' => isset($error_messages[$result['reason']]) ? $error_messages[$result['reason']] : __('Token inválido.', 'eipsi-forms'),
            'code' => $result['reason']
        ));
    }
    
    wp_send_json_success(array(
        'valid' => true,
        'survey_id' => (int) $result['survey_id'],
        'participant_id' => (int) $result['participant_id'],
        'expires_at' => $result['expires_at']
    ));
}
add_action('wp_ajax_nopriv_eipsi_validate_magic_link_token', 'eipsi_validate_magic_link_token_handler');
add_action('wp_ajax_eipsi_validate_magic_link_token', 'eipsi_validate_magic_link_token_handler');

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get the redirect URL for a participant after login/registration.
 * 
 * @param int $survey_id Survey/Study ID
 * @param int $participant_id Participant ID
 * @return string Redirect URL
 */
function eipsi_get_participant_redirect_url($survey_id, $participant_id) {
    // Check for a custom redirect URL in session or POST
    if (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
        $custom_redirect = esc_url_raw(wp_unslash($_POST['redirect_url']));
        if (!empty($custom_redirect)) {
            return $custom_redirect;
        }
    }
    
    // Try to find a page with the participant dashboard shortcode
    $dashboard_page = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        's' => 'eipsi_participant_dashboard'
    ));
    
    if (!empty($dashboard_page)) {
        return get_permalink($dashboard_page[0]->ID);
    }
    
    // Try to find a page with the survey login shortcode for this survey
    $login_pages = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_eipsi_survey_id',
                'value' => $survey_id
            )
        )
    ));
    
    if (!empty($login_pages)) {
        return get_permalink($login_pages[0]->ID);
    }
    
    // Default to home page
    return home_url('/');
}

/**
 * Helper function to check if a participant is logged in.
 * 
 * @return bool
 */
function eipsi_is_participant_logged_in() {
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    
    return EIPSI_Auth_Service::is_authenticated();
}

/**
 * Helper function to get the current participant ID.
 * 
 * @return int|null
 */
function eipsi_get_current_participant_id() {
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    
    return EIPSI_Auth_Service::get_current_participant();
}

/**
 * Helper function to get the current survey ID from session.
 * 
 * @return int|null
 */
function eipsi_get_current_survey_id() {
    if (!class_exists('EIPSI_Auth_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
    }
    
    return EIPSI_Auth_Service::get_current_survey();
}
