<?php
/**
 * EIPSI_Auth_Service
 *
 * Maneja autenticación sin login vía magic links y sesiones propias:
 * - Token generation + validation
 * - Session creation
 * - Rate limiting
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 1.4.2
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Auth_Service {
    
    /**
     * Authenticate participant (login).
     *
     * Valida credenciales y crea sesión si son correctas.
     *
     * @param int    $survey_id ID del survey.
     * @param string $email Email del participante.
     * @param string $password Password en texto plano.
     * @return array { success: bool, participant_id: int|null, error: string|null }
     * @since 1.4.0
     * @access public
     */
    public static function authenticate($survey_id, $email, $password) {
        // Sanitizar email
        $email = sanitize_email($email);

        // Obtener participante
        $participant = EIPSI_Participant_Service::get_by_email($survey_id, $email);

        if (!$participant) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'user_not_found'
            );
        }

        // Verificar si está activo
        if (!$participant->is_active) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'user_inactive'
            );
        }

        // Verificar password
        $is_valid = EIPSI_Participant_Service::verify_password($participant->id, $password);

        if (!$is_valid) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'invalid_credentials'
            );
        }

        // Actualizar último login
        EIPSI_Participant_Service::update_last_login($participant->id);

        return array(
            'success' => true,
            'participant_id' => $participant->id,
            'error' => null
        );
    }

    /**
     * Authenticate participant (passwordless - email only).
     *
     * Valida email y estado activo sin verificar contraseña.
     * Usado para flujo de autenticación sin contraseña.
     *
     * @param int    $survey_id ID del survey.
     * @param string $email Email del participante.
     * @return array { success: bool, participant_id: int|null, error: string|null }
     * @since 2.0.0
     * @access public
     */
    public static function authenticate_passwordless($survey_id, $email) {
        // Sanitizar email
        $email = sanitize_email($email);

        // Obtener participante
        $participant = EIPSI_Participant_Service::get_by_email($survey_id, $email);

        if (!$participant) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'user_not_found'
            );
        }

        // Verificar si está activo
        if (!$participant->is_active) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'user_inactive'
            );
        }

        // Actualizar último login
        EIPSI_Participant_Service::update_last_login($participant->id);

        return array(
            'success' => true,
            'participant_id' => $participant->id,
            'error' => null
        );
    }
    
    /**
     * Create session token and cookie.
     *
     * La sesión se almacena en:
     * 1. Cookie HTTP-only: EIPSI_SESSION_COOKIE_NAME
     * 2. Tabla wp_survey_sessions (para invalidación)
     *
     * @param int $participant_id ID del participante.
     * @param int $survey_id ID del survey.
     * @param int $ttl_hours Tiempo de vida en horas (default 168 = 7 días).
     * @return array { success: bool, token: string|null, error: string|null }
     * @since 1.4.0
     * @access public
     */
    public static function create_session($participant_id, $survey_id, $ttl_hours = 168) {
        global $wpdb;
        
        try {
            // Generar token único
            $token = wp_generate_password(64, true, true);
            
            // Hash token para almacenar en DB
            $token_hash = hash('sha256', $token);
            
            // Calcular expires_at
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$ttl_hours} hours"));
            
            // Obtener IP
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
            
            // Obtener User-Agent
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'unknown';
            
            // Insertar en wp_survey_sessions
            $table_name = $wpdb->prefix . 'survey_sessions';
            $result = $wpdb->insert(
                $table_name,
                array(
                    'token' => $token_hash, // Hash almacenado en DB
                    'participant_id' => $participant_id,
                    'survey_id' => $survey_id,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent,
                    'expires_at' => $expires_at,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%d', '%d', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                error_log('EIPSI Session creation failed: ' . $wpdb->last_error);
                return array(
                    'success' => false,
                    'token' => null,
                    'error' => 'db_error'
                );
            }
            
            // Setear cookie segura (HTTP-only, Secure, SameSite)
            $cookie_expires = strtotime("+{$ttl_hours} hours");
            
            // Cookie name desde constante (definir en plugin principal)
            $cookie_name = defined('EIPSI_SESSION_COOKIE_NAME') ? EIPSI_SESSION_COOKIE_NAME : 'eipsi_session_token';
            
            // Setear cookie - usar setcookie() simple para compatibilidad PHP < 7.3
            $secure = is_ssl(); // Solo HTTPS
            
            if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                // PHP 7.3+: usar opciones como array
                $cookie_options = array(
                    'expires' => $cookie_expires,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                );
                setcookie($cookie_name, $token, $cookie_options);
            } else {
                // PHP < 7.3: usar setcookie() tradicional
                setcookie(
                    $cookie_name,
                    $token,
                    $cookie_expires,
                    '/',  // path
                    '',   // domain
                    $secure,
                    true  // httponly
                );
            }
            
            return array(
                'success' => true,
                'token' => $token, // Solo para testing/debugging - NUNCA loguear
                'error' => null
            );
            
        } catch (Exception $e) {
            error_log('EIPSI Session creation exception: ' . $e->getMessage());
            return array(
                'success' => false,
                'token' => null,
                'error' => 'db_error'
            );
        }
    }
    
    /**
     * Get current participant from session.
     *
     * Lee la cookie del plugin y valida contra la tabla wp_survey_sessions.
     *
     * @return int|null participant_id o null si no hay sesión.
     * @since 1.4.0
     * @access public
     */
    public static function get_current_participant() {
        global $wpdb;
        
        // Cookie name
        $cookie_name = defined('EIPSI_SESSION_COOKIE_NAME') ? EIPSI_SESSION_COOKIE_NAME : 'eipsi_session_token';
        
        // Leer cookie
        $token = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
        if (!$token) {
            return null;
        }
        
        // Hash token
        $token_hash = hash('sha256', $token);
        
        // Query: SELECT participant_id FROM sessions WHERE token = %s AND expires_at > NOW()
        $table_name = $wpdb->prefix . 'survey_sessions';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT participant_id FROM $table_name WHERE token = %s AND expires_at > %s",
            $token_hash,
            current_time('mysql')
        ));
        
        return $result ? (int) $result : null;
    }
    
    /**
     * Get current survey from session.
     *
     * @return int|null survey_id o null si no hay sesión.
     * @since 1.4.0
     * @access public
     */
    public static function get_current_survey() {
        global $wpdb;
        
        // Cookie name
        $cookie_name = defined('EIPSI_SESSION_COOKIE_NAME') ? EIPSI_SESSION_COOKIE_NAME : 'eipsi_session_token';
        
        // Leer cookie
        $token = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
        if (!$token) {
            return null;
        }
        
        // Hash token
        $token_hash = hash('sha256', $token);
        
        // Query: SELECT survey_id FROM sessions WHERE token = %s AND expires_at > NOW()
        $table_name = $wpdb->prefix . 'survey_sessions';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT survey_id FROM $table_name WHERE token = %s AND expires_at > %s",
            $token_hash,
            current_time('mysql')
        ));
        
        return $result ? (int) $result : null;
    }
    
    /**
     * Destroy session (logout).
     *
     * Elimina la cookie y marca la sesión como inválida en la DB.
     *
     * @return bool True si se ejecutó el logout.
     * @since 1.4.0
     * @access public
     */
    public static function destroy_session() {
        global $wpdb;
        
        // Cookie name
        $cookie_name = defined('EIPSI_SESSION_COOKIE_NAME') ? EIPSI_SESSION_COOKIE_NAME : 'eipsi_session_token';
        
        // Leer token de cookie
        $token = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
        
        if ($token) {
            // Hash token para buscar en DB
            $token_hash = hash('sha256', $token);
            
            // DELETE FROM sessions WHERE token_hash = hash(token)
            $table_name = $wpdb->prefix . 'survey_sessions';
            $wpdb->delete(
                $table_name,
                array('token' => $token_hash),
                array('%s')
            );
        }
        
        // Borrar cookie: setear con fecha de expiración en el pasado
        $past_time = time() - 3600;
        
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $cookie_options = array(
                'expires' => $past_time,
                'path' => '/',
                'domain' => '',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax'
            );
            setcookie($cookie_name, '', $cookie_options);
        } else {
            setcookie($cookie_name, '', $past_time, '/', '', is_ssl(), true);
        }
        
        return true; // Siempre true si llegamos aquí
    }
    
    /**
     * Validate active session.
     *
     * Verifica si hay una sesión válida activa.
     *
     * @return bool True si hay sesión válida.
     * @since 1.4.0
     * @access public
     */
    public static function is_authenticated() {
        return self::get_current_participant() !== null;
    }
    
    /**
     * Cleanup expired sessions.
     *
     * DELETE FROM wp_survey_sessions WHERE expires_at < NOW()
     *
     * @return int Número de sesiones eliminadas.
     * @since 1.4.0
     * @access public
     */
    public static function cleanup_expired_sessions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'survey_sessions';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE expires_at < %s",
            current_time('mysql')
        ));
        
        return (int) $deleted;
    }
    
    /**
     * Get current session info.
     *
     * @return object|null Objeto con: participant_id, survey_id, ip_address, user_agent, created_at, expires_at, time_remaining_hours.
     * @since 1.4.0
     * @access public
     */
    public static function get_current_session_info() {
        global $wpdb;
        
        // Cookie name
        $cookie_name = defined('EIPSI_SESSION_COOKIE_NAME') ? EIPSI_SESSION_COOKIE_NAME : 'eipsi_session_token';
        
        // Leer token
        $token = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
        if (!$token) {
            return null;
        }
        
        // Hash token
        $token_hash = hash('sha256', $token);
        
        // Query: SELECT * FROM sessions WHERE token = hash AND expires_at > NOW()
        $table_name = $wpdb->prefix . 'survey_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s AND expires_at > %s",
            $token_hash,
            current_time('mysql')
        ));
        
        if (!$session) {
            return null;
        }
        
        // Calcular time_remaining_hours
        $expires_timestamp = strtotime($session->expires_at);
        $now_timestamp = time();
        $time_remaining_seconds = max(0, $expires_timestamp - $now_timestamp);
        $time_remaining_hours = round($time_remaining_seconds / 3600, 2);
        
        // Agregar time_remaining_hours al objeto
        $session->time_remaining_hours = $time_remaining_hours;
        $session->time_remaining_seconds = $time_remaining_seconds;
        
        return $session;
    }
    
    /**
     * Get session remaining time in seconds.
     *
     * Returns the remaining time for the current session.
     *
     * @return array { remaining_seconds: int, expires_at: string, is_expired: bool }
     * @since 2.0.0
     * @access public
     */
    public static function get_session_remaining_time() {
        $session = self::get_current_session_info();
        
        if (!$session) {
            return array(
                'remaining_seconds' => 0,
                'expires_at' => null,
                'is_expired' => true
            );
        }
        
        $expires_timestamp = strtotime($session->expires_at);
        $now_timestamp = time();
        $remaining_seconds = max(0, $expires_timestamp - $now_timestamp);
        
        return array(
            'remaining_seconds' => (int) $remaining_seconds,
            'expires_at' => $session->expires_at,
            'is_expired' => $remaining_seconds <= 0
        );
    }
    
    /**
     * Extend current session.
     *
     * Extends the session expiration time by the specified TTL.
     *
     * @param int $ttl_hours Time to extend in hours (default 168 = 7 days).
     * @return array { success: bool, new_expires_at: string|null, error: string|null }
     * @since 2.0.0
     * @access public
     */
    public static function extend_session($ttl_hours = 168) {
        global $wpdb;
        
        // Cookie name
        $cookie_name = defined('EIPSI_SESSION_COOKIE_NAME') ? EIPSI_SESSION_COOKIE_NAME : 'eipsi_session_token';
        
        // Leer token
        $token = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
        if (!$token) {
            return array(
                'success' => false,
                'new_expires_at' => null,
                'error' => 'no_session'
            );
        }
        
        // Hash token
        $token_hash = hash('sha256', $token);
        
        // Check if session exists and is valid
        $table_name = $wpdb->prefix . 'survey_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s",
            $token_hash
        ));
        
        if (!$session) {
            return array(
                'success' => false,
                'new_expires_at' => null,
                'error' => 'session_not_found'
            );
        }
        
        // Check if already expired
        $expires_timestamp = strtotime($session->expires_at);
        if ($expires_timestamp < time()) {
            return array(
                'success' => false,
                'new_expires_at' => null,
                'error' => 'session_expired'
            );
        }
        
        // Calculate new expiration time
        $new_expires_at = date('Y-m-d H:i:s', strtotime("+{$ttl_hours} hours"));
        
        // Update session in database
        $result = $wpdb->update(
            $table_name,
            array('expires_at' => $new_expires_at),
            array('token' => $token_hash),
            array('%s'),
            array('%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'new_expires_at' => null,
                'error' => 'db_error'
            );
        }
        
        // Update cookie with new expiration
        $cookie_expires = strtotime("+{$ttl_hours} hours");
        $secure = is_ssl();
        
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $cookie_options = array(
                'expires' => $cookie_expires,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            );
            setcookie($cookie_name, $token, $cookie_options);
        } else {
            setcookie(
                $cookie_name,
                $token,
                $cookie_expires,
                '/',
                '',
                $secure,
                true
            );
        }
        
        return array(
            'success' => true,
            'new_expires_at' => $new_expires_at,
            'error' => null
        );
    }
}