<?php
/**
 * EIPSI Participant Service
 * 
 * Gestiona participantes y su ciclo de vida en el sistema longitudinal.
 * Los participantes pueden registrarse con email+password y recibir waves.
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Participant_Service {
    
    /**
     * Crear nuevo participante
     * 
     * Valida email, password y crea registro en wp_survey_participants.
     * 
     * @param int $survey_id ID del survey
     * @param string $email Email del participante (será sanitizado)
     * @param string $password Password en texto plano (será hasheado)
     * @param array $metadata Datos adicionales (first_name, last_name)
     * @return array { success: bool, participant_id: int|null, error: string|null }
     */
    public static function create_participant($survey_id, $email, $password, $metadata = array()) {
        global $wpdb;
        
        try {
            // Validar email con is_email()
            if (!is_email($email)) {
                return array(
                    'success' => false,
                    'participant_id' => null,
                    'error' => 'invalid_email'
                );
            }
            
            // Sanitizar email
            $email = sanitize_email($email);
            
            // Validar password: mínimo 8 caracteres, no espacios-only
            if (strlen($password) < 8 || trim($password) === '') {
                return array(
                    'success' => false,
                    'participant_id' => null,
                    'error' => 'short_password'
                );
            }
            
            // Sanitizar metadata
            $first_name = isset($metadata['first_name']) ? sanitize_text_field($metadata['first_name']) : '';
            $last_name = isset($metadata['last_name']) ? sanitize_text_field($metadata['last_name']) : '';
            
            // Hash password con wp_hash_password()
            $password_hash = wp_hash_password($password);
            
            // Verificar UNIQUE(survey_id, email)
            $table_name = $wpdb->prefix . 'survey_participants';
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE survey_id = %d AND email = %s",
                $survey_id,
                $email
            ));
            
            if ($existing) {
                return array(
                    'success' => false,
                    'participant_id' => null,
                    'error' => 'email_exists'
                );
            }
            
            // INSERT en wp_survey_participants
            $result = $wpdb->insert(
                $table_name,
                array(
                    'survey_id' => $survey_id,
                    'email' => $email,
                    'password_hash' => $password_hash,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'created_at' => current_time('mysql'),
                    'is_active' => 1
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%d')
            );
            
            if ($result === false) {
                // Log error pero no mostrar al usuario
                error_log('EIPSI Participant creation failed: ' . $wpdb->last_error);
                return array(
                    'success' => false,
                    'participant_id' => null,
                    'error' => 'db_error'
                );
            }
            
            return array(
                'success' => true,
                'participant_id' => (int) $wpdb->insert_id,
                'error' => null
            );
            
        } catch (Exception $e) {
            error_log('EIPSI Participant creation exception: ' . $e->getMessage());
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'db_error'
            );
        }
    }
    
    /**
     * Obtener participante por email + survey
     * 
     * @param int $survey_id ID del survey
     * @param string $email Email del participante
     * @return object|null Fila de wp_survey_participants
     */
    public static function get_by_email($survey_id, $email) {
        global $wpdb;
        
        // Sanitizar email
        $email = sanitize_email($email);
        
        $table_name = $wpdb->prefix . 'survey_participants';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE survey_id = %d AND email = %s",
            $survey_id,
            $email
        ));
    }
    
    /**
     * Obtener participante por ID
     * 
     * @param int $participant_id ID del participante
     * @return object|null
     */
    public static function get_by_id($participant_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'survey_participants';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $participant_id
        ));
    }
    
    /**
     * Verificar password
     * 
     * @param int $participant_id ID del participante
     * @param string $plain_password Password en texto plano
     * @return bool
     */
    public static function verify_password($participant_id, $plain_password) {
        global $wpdb;
        
        // Obtener participante
        $participant = self::get_by_id($participant_id);
        if (!$participant) {
            return false;
        }
        
        // Verificar si está activo
        if (!$participant->is_active) {
            return false;
        }
        
        // Usar wp_check_password para verificar
        $is_valid = wp_check_password($plain_password, $participant->password_hash, $participant_id);
        
        // Log para rate limiting (implementado en Auth_Service)
        return $is_valid;
    }
    
    /**
     * Actualizar último login
     * 
     * @param int $participant_id ID del participante
     * @return bool
     */
    public static function update_last_login($participant_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'survey_participants';
        $result = $wpdb->update(
            $table_name,
            array('last_login_at' => current_time('mysql')),
            array('id' => $participant_id),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Marcar como activo/inactivo
     * 
     * @param int $participant_id ID del participante
     * @param bool $is_active Estado (true = activo, false = inactivo)
     * @return bool
     */
    public static function set_active($participant_id, $is_active) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'survey_participants';
        $result = $wpdb->update(
            $table_name,
            array('is_active' => $is_active ? 1 : 0),
            array('id' => $participant_id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Cambiar password del participante
     * 
     * @param int $participant_id ID del participante
     * @param string $password_old Password actual
     * @param string $password_new Password nuevo
     * @return array { success: bool, error: string|null }
     */
    public static function change_password($participant_id, $password_old, $password_new) {
        global $wpdb;
        
        // Verificar password actual
        if (!self::verify_password($participant_id, $password_old)) {
            return array(
                'success' => false,
                'error' => 'invalid_password'
            );
        }
        
        // Validar password nuevo: mínimo 8 chars
        if (strlen($password_new) < 8) {
            return array(
                'success' => false,
                'error' => 'short_password'
            );
        }
        
        // Hash password nuevo
        $password_hash = wp_hash_password($password_new);
        
        // UPDATE password_hash
        $table_name = $wpdb->prefix . 'survey_participants';
        $result = $wpdb->update(
            $table_name,
            array('password_hash' => $password_hash),
            array('id' => $participant_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            error_log('EIPSI Password change failed: ' . $wpdb->last_error);
            return array(
                'success' => false,
                'error' => 'db_error'
            );
        }
        
        return array(
            'success' => true,
            'error' => null
        );
    }
    
    /**
     * Listar participantes con paginación y filtros
     * 
     * @param int $survey_id ID del survey
     * @param int $page Página (default 1)
     * @param int $per_page Registros por página (default 50)
     * @param array $filters Filtros: status, search
     * @return array { total, participants, page, per_page, pages }
     */
    public static function list_participants($survey_id, $page = 1, $per_page = 50, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'survey_participants';
        $offset = ($page - 1) * $per_page;
        
        // Construir WHERE clause
        $where_conditions = array('survey_id = %d');
        $where_values = array($survey_id);
        
        // Filtro por status
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $where_conditions[] = 'is_active = 1';
            } elseif ($filters['status'] === 'inactive') {
                $where_conditions[] = 'is_active = 0';
            }
        }
        
        // Filtro por búsqueda
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where_conditions[] = 'email LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($filters['search']) . '%';
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Query total (sin LIMIT)
        $total_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        $total = (int) $wpdb->get_var($wpdb->prepare($total_query, $where_values));
        
        // Query paginada
        $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $participants = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        return array(
            'total' => $total,
            'participants' => $participants,
            'page' => (int) $page,
            'per_page' => (int) $per_page,
            'pages' => ceil($total / $per_page)
        );
    }
}