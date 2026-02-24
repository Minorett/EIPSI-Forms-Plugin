<?php
/**
 * EIPSI_Participant_Auth_Handler
 *
 * Maneja la autenticación de participantes via AJAX:
 * - Login con email/password
 * - Registro con study_code
 * - Magic link para password reset
 * - Rate limiting
 * - CSRF protection
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 2.0.0
 * @since Phase 2 - Task 2A
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Participant_Auth_Handler {
    
    /**
     * Initialize AJAX handlers.
     *
     * @since 2.0.0
     */
    public static function init() {
        // Login
        add_action('wp_ajax_eipsi_participant_login', array(__CLASS__, 'handle_login'));
        add_action('wp_ajax_nopriv_eipsi_participant_login', array(__CLASS__, 'handle_login'));
        
        // Registration
        add_action('wp_ajax_eipsi_participant_register', array(__CLASS__, 'handle_register'));
        add_action('wp_ajax_nopriv_eipsi_participant_register', array(__CLASS__, 'handle_register'));
        
        // Magic Link Request (for login and password reset)
        add_action('wp_ajax_eipsi_participant_magic_link', array(__CLASS__, 'handle_magic_link_request'));
        add_action('wp_ajax_nopriv_eipsi_participant_magic_link', array(__CLASS__, 'handle_magic_link_request'));
        
        // Logout
        add_action('wp_ajax_eipsi_participant_logout', array(__CLASS__, 'handle_logout'));
        add_action('wp_ajax_nopriv_eipsi_participant_logout', array(__CLASS__, 'handle_logout'));
        
        // Check session
        add_action('wp_ajax_eipsi_participant_check_session', array(__CLASS__, 'handle_check_session'));
        add_action('wp_ajax_nopriv_eipsi_participant_check_session', array(__CLASS__, 'handle_check_session'));
    }
    
    /**
     * Handle participant login.
     *
     * @since 2.0.0
     */
    public static function handle_login() {
        // Verify nonce (CSRF protection)
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eipsi_participant_auth')) {
            wp_send_json_error(array(
                'code' => 'invalid_nonce',
                'message' => __('Error de seguridad. Por favor, recargá la página.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Get and validate inputs
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $survey_id = absint($_POST['survey_id'] ?? 0);
        $remember = !empty($_POST['remember']);
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(array(
                'code' => 'invalid_email',
                'message' => __('Por favor, ingresá un email válido.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Validate password
        if (strlen($password) < 8) {
            wp_send_json_error(array(
                'code' => 'invalid_password',
                'message' => __('La contraseña debe tener al menos 8 caracteres.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Check rate limit
        $rate_limit = EIPSI_Participant_Access_Log_Service::check_rate_limit($email, $survey_id);
        if (!$rate_limit['allowed']) {
            wp_send_json_error(array(
                'code' => 'rate_limited',
                'message' => sprintf(
                    __('Demasiados intentos fallidos. Por favor, esperá %d minutos antes de intentar nuevamente.', 'eipsi-forms'),
                    ceil($rate_limit['retry_after'] / 60)
                ),
                'retry_after' => $rate_limit['retry_after']
            ));
            wp_die();
        }
        
        // Get survey_id from study_code if provided
        $study_code = sanitize_text_field($_POST['study_code'] ?? '');
        if (!empty($study_code) && empty($survey_id)) {
            global $wpdb;
            $study = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}survey_studies WHERE study_code = %s",
                $study_code
            ));
            if ($study) {
                $survey_id = $study->id;
            }
        }
        
        // Authenticate
        $result = EIPSI_Auth_Service::authenticate($survey_id, $email, $password);
        
        if (!$result['success']) {
            // Log failed login attempt
            EIPSI_Participant_Access_Log_Service::log(0, $survey_id, 'login_failed', array(
                'email' => $email,
                'reason' => $result['error']
            ));
            
            // Map error codes to user-friendly messages
            $error_messages = array(
                'user_not_found' => __('No encontramos una cuenta con ese email en este estudio.', 'eipsi-forms'),
                'user_inactive' => __('Tu cuenta está desactivada. Contactá al investigador.', 'eipsi-forms'),
                'invalid_credentials' => __('Email o contraseña incorrectos.', 'eipsi-forms')
            );
            
            wp_send_json_error(array(
                'code' => $result['error'],
                'message' => $error_messages[$result['error']] ?? __('Error de autenticación.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Create session
        $ttl_hours = $remember ? 720 : 168; // 30 days if remember, 7 days otherwise
        $session_result = EIPSI_Auth_Service::create_session($result['participant_id'], $survey_id, $ttl_hours);
        
        if (!$session_result['success']) {
            wp_send_json_error(array(
                'code' => 'session_error',
                'message' => __('Error al crear la sesión. Por favor, intentá nuevamente.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Log successful login
        EIPSI_Participant_Access_Log_Service::log($result['participant_id'], $survey_id, 'login', array(
            'email' => $email,
            'remember' => $remember
        ));
        
        // Get redirect URL
        $redirect_url = esc_url_raw($_POST['redirect_url'] ?? '');
        if (empty($redirect_url)) {
            $redirect_url = home_url('/estudio/');
        }
        
        wp_send_json_success(array(
            'message' => __('¡Bienvenido/a! Redirigiendo...', 'eipsi-forms'),
            'redirect_url' => $redirect_url
        ));
        
        wp_die();
    }
    
    /**
     * Handle participant registration.
     *
     * @since 2.0.0
     */
    public static function handle_register() {
        // Verify nonce (CSRF protection)
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eipsi_participant_auth')) {
            wp_send_json_error(array(
                'code' => 'invalid_nonce',
                'message' => __('Error de seguridad. Por favor, recargá la página.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Get and validate inputs
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $study_code = sanitize_text_field($_POST['study_code'] ?? '');
        $survey_id = absint($_POST['survey_id'] ?? 0);
        $accept_terms = !empty($_POST['accept_terms']);
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(array(
                'code' => 'invalid_email',
                'message' => __('Por favor, ingresá un email válido.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Validate password
        if (strlen($password) < 8) {
            wp_send_json_error(array(
                'code' => 'short_password',
                'message' => __('La contraseña debe tener al menos 8 caracteres.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Validate names
        if (empty($first_name) || empty($last_name)) {
            wp_send_json_error(array(
                'code' => 'missing_name',
                'message' => __('Por favor, completá tu nombre y apellido.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Validate terms acceptance
        if (!$accept_terms) {
            wp_send_json_error(array(
                'code' => 'terms_required',
                'message' => __('Debés aceptar los términos y condiciones para continuar.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Get survey_id from study_code if provided
        if (!empty($study_code) && empty($survey_id)) {
            global $wpdb;
            $study = $wpdb->get_row($wpdb->prepare(
                "SELECT id, study_name, status FROM {$wpdb->prefix}survey_studies WHERE study_code = %s",
                $study_code
            ));
            
            if (!$study) {
                wp_send_json_error(array(
                    'code' => 'invalid_study_code',
                    'message' => __('El código de estudio no es válido.', 'eipsi-forms')
                ));
                wp_die();
            }
            
            if (!in_array($study->status, array('active', 'paused'))) {
                wp_send_json_error(array(
                    'code' => 'study_not_active',
                    'message' => __('El estudio no está disponible actualmente.', 'eipsi-forms')
                ));
                wp_die();
            }
            
            $survey_id = $study->id;
        }
        
        // Validate survey_id
        if (empty($survey_id)) {
            wp_send_json_error(array(
                'code' => 'missing_study',
                'message' => __('Por favor, ingresá un código de estudio válido.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Check if email already exists for this study
        $existing_participant = EIPSI_Participant_Service::get_by_email($survey_id, $email);
        if ($existing_participant) {
            // Log registration attempt with existing email
            EIPSI_Participant_Access_Log_Service::log(0, $survey_id, 'registration', array(
                'email' => $email,
                'status' => 'email_exists'
            ));
            
            wp_send_json_error(array(
                'code' => 'email_exists',
                'message' => __('Ya existe una cuenta con ese email en este estudio.', 'eipsi-forms'),
                'show_login_link' => true
            ));
            wp_die();
        }
        
        // Create participant
        $result = EIPSI_Participant_Service::create_participant(
            $survey_id,
            $email,
            $password,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name
            )
        );
        
        if (!$result['success']) {
            $error_messages = array(
                'invalid_email' => __('El email no es válido.', 'eipsi-forms'),
                'short_password' => __('La contraseña debe tener al menos 8 caracteres.', 'eipsi-forms'),
                'email_exists' => __('Ya existe una cuenta con ese email.', 'eipsi-forms'),
                'db_error' => __('Error al crear la cuenta. Por favor, intentá nuevamente.', 'eipsi-forms')
            );
            
            wp_send_json_error(array(
                'code' => $result['error'],
                'message' => $error_messages[$result['error']] ?? __('Error en el registro.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Log successful registration
        EIPSI_Participant_Access_Log_Service::log($result['participant_id'], $survey_id, 'registration', array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name
        ));
        
        // Create session (auto-login after registration)
        $session_result = EIPSI_Auth_Service::create_session($result['participant_id'], $survey_id, 168);
        
        if (!$session_result['success']) {
            // Registration succeeded but session creation failed - still return success
            wp_send_json_success(array(
                'message' => __('¡Cuenta creada! Por favor, iniciá sesión.', 'eipsi-forms'),
                'requires_login' => true,
                'redirect_url' => ''
            ));
            wp_die();
        }
        
        // Get redirect URL
        $redirect_url = esc_url_raw($_POST['redirect_url'] ?? '');
        if (empty($redirect_url)) {
            $redirect_url = home_url('/estudio/');
        }
        
        wp_send_json_success(array(
            'message' => __('¡Cuenta creada exitosamente! Redirigiendo...', 'eipsi-forms'),
            'redirect_url' => $redirect_url
        ));
        
        wp_die();
    }
    
    /**
     * Handle magic link request (for login and password reset).
     *
     * @since 2.0.0
     */
    public static function handle_magic_link_request() {
        // Verify nonce (CSRF protection)
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eipsi_participant_auth')) {
            wp_send_json_error(array(
                'code' => 'invalid_nonce',
                'message' => __('Error de seguridad. Por favor, recargá la página.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Get and validate inputs
        $email = sanitize_email($_POST['email'] ?? '');
        $study_code = sanitize_text_field($_POST['study_code'] ?? '');
        $survey_id = absint($_POST['survey_id'] ?? 0);
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(array(
                'code' => 'invalid_email',
                'message' => __('Por favor, ingresá un email válido.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Get survey_id from study_code if provided
        if (!empty($study_code) && empty($survey_id)) {
            global $wpdb;
            $study = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}survey_studies WHERE study_code = %s",
                $study_code
            ));
            if ($study) {
                $survey_id = $study->id;
            }
        }
        
        // Find participant
        $participant = EIPSI_Participant_Service::get_by_email($survey_id, $email);
        
        // Always return success to prevent email enumeration
        // But only send email if participant exists
        if ($participant) {
            // Generate magic link
            $token = EIPSI_MagicLinksService::generate_magic_link($survey_id, $participant->id);
            
            if ($token) {
                // Send magic link email
                $email_sent = EIPSI_Email_Service::send_magic_link_email($survey_id, $participant->id, $token);
                
                // Log magic link request
                EIPSI_Participant_Access_Log_Service::log($participant->id, $survey_id, 'magic_link_sent', array(
                    'email' => $email,
                    'email_sent' => $email_sent
                ));
            }
        } else {
            // Log failed magic link request (email not found)
            EIPSI_Participant_Access_Log_Service::log(0, $survey_id, 'magic_link_sent', array(
                'email' => $email,
                'status' => 'not_found'
            ));
        }
        
        // Always return same message to prevent email enumeration
        wp_send_json_success(array(
            'message' => __('Si el email está registrado, recibirás un link mágico en breve.', 'eipsi-forms')
        ));
        
        wp_die();
    }
    
    /**
     * Handle participant logout.
     *
     * @since 2.0.0
     */
    public static function handle_logout() {
        // Verify nonce (CSRF protection)
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eipsi_participant_logout')) {
            wp_send_json_error(array(
                'code' => 'invalid_nonce',
                'message' => __('Error de seguridad.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        // Get participant info before destroying session
        $participant_id = EIPSI_Auth_Service::get_current_participant();
        $survey_id = EIPSI_Auth_Service::get_current_survey();
        
        // Log logout
        if ($participant_id) {
            EIPSI_Participant_Access_Log_Service::log($participant_id, $survey_id, 'logout');
        }
        
        // Destroy session
        EIPSI_Auth_Service::destroy_session();
        
        wp_send_json_success(array(
            'message' => __('Sesión cerrada exitosamente.', 'eipsi-forms'),
            'redirect_url' => home_url('/')
        ));
        
        wp_die();
    }
    
    /**
     * Check session status.
     *
     * @since 2.0.0
     */
    public static function handle_check_session() {
        $participant_id = EIPSI_Auth_Service::get_current_participant();
        
        if (!$participant_id) {
            wp_send_json_error(array(
                'code' => 'no_session',
                'message' => __('No hay sesión activa.', 'eipsi-forms')
            ));
            wp_die();
        }
        
        $session_info = EIPSI_Auth_Service::get_current_session_info();
        $participant = EIPSI_Participant_Service::get_by_id($participant_id);
        
        wp_send_json_success(array(
            'participant_id' => $participant_id,
            'email' => $participant->email ?? '',
            'first_name' => $participant->first_name ?? '',
            'time_remaining_hours' => $session_info->time_remaining_hours ?? 0
        ));
        
        wp_die();
    }
}

// Initialize
EIPSI_Participant_Auth_Handler::init();
