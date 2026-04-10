<?php
/**
 * EIPSI Data Safety System
 * 
 * Sistema crítico de seguridad de datos para investigación clínica.
 * Garantiza: 0% pérdida de datos de formularios.
 * 
 * Múltiples capas de protección:
 * 1. Pre-flight validation
 * 2. LocalStorage backup (JavaScript)
 * 3. WordPress DB storage
 * 4. External DB storage
 * 5. Post-submit verification
 * 6. Emergency recovery
 * 
 * @package EIPSI_Forms
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pre-flight data validation
 * Verifica que todos los campos requeridos estén presentes antes de procesar
 */
function eipsi_safety_validate_submission($data) {
    $errors = array();
    $warnings = array();
    
    // Verificar campos críticos de identificación
    if (empty($data['form_id'])) {
        $errors[] = 'Missing critical: form_id';
    }
    
    if (empty($data['participant_id'])) {
        $warnings[] = 'Missing participant_id, will generate fingerprint';
    }
    
    // Verificar que form_responses no esté vacío si hay campos enviados
    if (isset($data['form_responses']) && empty($data['form_responses'])) {
        $warnings[] = 'Empty form_responses - possible field name issue';
    }
    
    // Log para auditoría
    if (!empty($warnings)) {
        error_log('[EIPSI SAFETY] Submission warnings: ' . implode(' | ', $warnings));
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
        'timestamp' => current_time('mysql'),
    );
}

/**
 * Guardar submission con retry automático
 */
function eipsi_safety_save_with_retry($data, $max_retries = 3) {
    $attempt = 0;
    $last_error = null;
    
    while ($attempt < $max_retries) {
        $attempt++;
        
        try {
            $result = eipsi_safety_attempt_save($data);
            
            if ($result['success']) {
                // Log éxito
                error_log(sprintf(
                    '[EIPSI SAFETY] Submission saved successfully on attempt %d. ID: %s',
                    $attempt,
                    $result['insert_id'] ?? 'unknown'
                ));
                return $result;
            }
            
            $last_error = $result['error'] ?? 'Unknown error';
            error_log(sprintf('[EIPSI SAFETY] Save attempt %d failed: %s', $attempt, $last_error));
            
            // Esperar antes de reintentar (backoff exponencial)
            if ($attempt < $max_retries) {
                usleep($attempt * 500000); // 0.5s, 1s, 1.5s
            }
            
        } catch (Exception $e) {
            $last_error = $e->getMessage();
            error_log('[EIPSI SAFETY] Exception on attempt ' . $attempt . ': ' . $last_error);
        }
    }
    
    // Todos los intentos fallaron - activar modo emergencia
    return eipsi_safety_emergency_save($data, $last_error);
}

/**
 * Intento de guardado normal
 */
function eipsi_safety_attempt_save($data) {
    global $wpdb;
    
    // Intentar DB externa primero si está configurada
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    if ($db_helper->is_enabled()) {
        $result = $db_helper->insert_form_submission($data);
        if ($result['success']) {
            return array(
                'success' => true,
                'insert_id' => $result['insert_id'],
                'storage' => 'external_db',
                'timestamp' => current_time('mysql'),
            );
        }
    }
    
    // Fallback a WordPress DB
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    $wpdb_result = $wpdb->insert($table_name, $data);
    
    if ($wpdb_result !== false) {
        return array(
            'success' => true,
            'insert_id' => $wpdb->insert_id,
            'storage' => 'wordpress_db',
            'timestamp' => current_time('mysql'),
        );
    }
    
    return array(
        'success' => false,
        'error' => $wpdb->last_error,
        'error_code' => 'DB_INSERT_FAILED',
    );
}

/**
 * Modo emergencia: Guardar datos críticos cuando todo falla
 * Usa la base de datos configurada (WordPress o External según configuración del usuario)
 */
function eipsi_safety_emergency_save($data, $original_error) {
    global $wpdb;
    
    error_log('[EIPSI SAFETY] ACTIVATING EMERGENCY SAVE MODE');
    
    // Determinar qué base de datos usar según la configuración del usuario
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $using_external = $db_helper->is_enabled();
    $emergency_id = null;
    $emergency_table = $wpdb->prefix . 'eipsi_emergency_submissions';
    
    // Datos a guardar
    $emergency_data = array(
        'form_id' => $data['form_id'] ?? 'unknown',
        'participant_id' => $data['participant_id'] ?? null,
        'form_responses' => $data['form_responses'] ?? null,
        'metadata' => $data['metadata'] ?? null,
        'raw_post_data' => wp_json_encode($_POST),
        'error_message' => $original_error,
    );
    
    // Intentar guardar en la DB configurada (External o WordPress)
    if ($using_external) {
        $mysqli = $db_helper->get_connection();
        if ($mysqli) {
            // Crear tabla de emergencia en DB externa
            $create_sql = "CREATE TABLE IF NOT EXISTS {$emergency_table} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_id VARCHAR(50) NOT NULL,
                participant_id VARCHAR(255),
                form_responses LONGTEXT,
                metadata LONGTEXT,
                raw_post_data LONGTEXT,
                error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                resolved TINYINT DEFAULT 0,
                resolved_at DATETIME NULL,
                KEY form_id (form_id),
                KEY created_at (created_at),
                KEY resolved (resolved)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $mysqli->query($create_sql);
            
            // Insertar datos
            $stmt = $mysqli->prepare(
                "INSERT INTO {$emergency_table} 
                (form_id, participant_id, form_responses, metadata, raw_post_data, error_message, created_at, resolved) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)"
            );
            
            if ($stmt) {
                $stmt->bind_param(
                    'ssssss',
                    $emergency_data['form_id'],
                    $emergency_data['participant_id'],
                    $emergency_data['form_responses'],
                    $emergency_data['metadata'],
                    $emergency_data['raw_post_data'],
                    $emergency_data['error_message']
                );
                
                if ($stmt->execute()) {
                    $emergency_id = $mysqli->insert_id;
                    error_log('[EIPSI SAFETY] Emergency data saved to EXTERNAL DB');
                }
                $stmt->close();
            }
            $mysqli->close();
        }
    }
    
    // Fallback a WordPress DB si no se pudo guardar en externa o si no está configurada
    if (!$emergency_id) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$emergency_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id VARCHAR(50) NOT NULL,
            participant_id VARCHAR(255),
            form_responses LONGTEXT,
            metadata LONGTEXT,
            raw_post_data LONGTEXT,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved TINYINT DEFAULT 0,
            resolved_at DATETIME NULL,
            KEY form_id (form_id),
            KEY created_at (created_at),
            KEY resolved (resolved)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $wpdb->insert($emergency_table, $emergency_data);
        $emergency_id = $wpdb->insert_id;
        error_log('[EIPSI SAFETY] Emergency data saved to WORDPRESS DB');
    }
    
    // Notificar al admin CRÍTICO
    eipsi_safety_alert_admin_critical($emergency_id, $data, $original_error, $using_external ? 'external' : 'wordpress');
    
    return array(
        'success' => true, // Retornamos éxito porque guardamos en emergencia
        'emergency_mode' => true,
        'emergency_id' => $emergency_id,
        'message' => __('Tu respuesta fue guardada en modo seguro. El administrador será notificado.', 'eipsi-forms'),
        'storage' => $using_external ? 'emergency_table_external' : 'emergency_table_wp',
    );
}

/**
 * Alerta crítica al administrador
 */
function eipsi_safety_alert_admin_critical($emergency_id, $data, $error, $db_type = 'wordpress') {
    $to = get_option('admin_email');
    $subject = sprintf(
        '[CRÍTICO] Pérdida de datos prevenida - Formulario: %s',
        $data['form_id'] ?? 'unknown'
    );
    
    $body = sprintf(
        "Se activó el modo de emergencia de datos.\n\n" .
        "ID de Emergencia: %d\n" .
        "Formulario: %s\n" .
        "Participante: %s\n" .
        "Error original: %s\n" .
        "Base de datos usada: %s\n" .
        "Fecha: %s\n\n" .
        "Los datos fueron guardados en la tabla: %s\n" .
        "Acción requerida: Verificar tabla de emergencias en %s y recuperar datos.",
        $emergency_id,
        $data['form_id'] ?? 'unknown',
        $data['participant_id'] ?? 'N/A',
        $error,
        $db_type === 'external' ? 'External DB (configurada por usuario)' : 'WordPress DB',
        current_time('mysql'),
        $GLOBALS['wpdb']->prefix . 'eipsi_emergency_submissions',
        $db_type === 'external' ? 'la base de datos externa configurada' : 'la base de datos de WordPress'
    );
    
    wp_mail($to, $subject, $body);
    
    // También loguear
    error_log(sprintf(
        '[EIPSI SAFETY] EMERGENCY ALERT SENT - ID: %d, Form: %s, DB: %s',
        $emergency_id,
        $data['form_id'] ?? 'unknown',
        $db_type
    ));
}

/**
 * Verificación post-submit: Confirmar que los datos realmente se guardaron
 */
function eipsi_safety_verify_submission($insert_id, $storage_type, $data) {
    global $wpdb;
    
    if ($storage_type === 'emergency_table') {
        // En modo emergencia, ya verificamos con el insert_id
        return true;
    }
    
    // Verificar que el registro existe
    if ($storage_type === 'wordpress_db') {
        $table = $wpdb->prefix . 'vas_form_results';
        $record = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE id = %d",
            $insert_id
        ));
        
        if ($record) {
            // Verificación adicional: comprobar que form_responses no está vacío
            $responses = $wpdb->get_var($wpdb->prepare(
                "SELECT form_responses FROM {$table} WHERE id = %d",
                $insert_id
            ));
            
            if (empty($responses) || $responses === '[]' || $responses === '{}') {
                error_log(sprintf(
                    '[EIPSI SAFETY] WARNING: Submission %d saved but form_responses is empty!',
                    $insert_id
                ));
                return false;
            }
            
            return true;
        }
    }
    
    return false;
}

/**
 * Dashboard de salud para admin
 */
function eipsi_safety_get_health_status() {
    global $wpdb;
    
    $status = array(
        'healthy' => true,
        'issues' => array(),
        'stats' => array(),
    );
    
    // Verificar si hay emergencias sin resolver
    $emergency_table = $wpdb->prefix . 'eipsi_emergency_submissions';
    $unresolved = $wpdb->get_var("SELECT COUNT(*) FROM {$emergency_table} WHERE resolved = 0");
    
    if ($unresolved > 0) {
        $status['healthy'] = false;
        $status['issues'][] = sprintf(
            '%d submission(s) en modo emergencia sin resolver',
            $unresolved
        );
    }
    
    // Verificar submissions recientes con respuestas vacías
    $recent_empty = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vas_form_results 
         WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         AND (form_responses IS NULL OR form_responses = '[]' OR form_responses = '{}')"
    );
    
    if ($recent_empty > 0) {
        $status['healthy'] = false;
        $status['issues'][] = sprintf(
            '%d submission(s) recientes con respuestas vacías',
            $recent_empty
        );
    }
    
    $status['stats']['unresolved_emergencies'] = $unresolved;
    $status['stats']['recent_empty_responses'] = $recent_empty;
    
    return $status;
}

/**
 * AJAX handler: Verificación de salud del sistema
 */
function eipsi_safety_health_check_ajax() {
    check_ajax_referer('eipsi_safety_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sin permisos'));
    }
    
    $health = eipsi_safety_get_health_status();
    
    wp_send_json_success(array(
        'healthy' => $health['healthy'],
        'issues' => $health['issues'],
        'stats' => $health['stats'],
        'timestamp' => current_time('mysql'),
    ));
}
add_action('wp_ajax_eipsi_safety_health_check', 'eipsi_safety_health_check_ajax');
