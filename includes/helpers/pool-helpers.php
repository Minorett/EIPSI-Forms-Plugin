<?php
/**
 * EIPSI Forms - Pool Helpers
 *
 * Funciones auxiliares para el sistema Pool de Estudios
 * - Detección de tipo de acceso (pool vs study individual)
 * - Validación de pools
 * - Helpers para redirección y manejo de asignaciones
 *
 * @package EIPSI_Forms
 * @since 2.5.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detectar tipo de recurso desde URL o parámetro
 *
 * @return array ['type' => 'pool'|'study'|'unknown', 'code' => string]
 */
function eipsi_detect_access_type() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $query_code = $_GET['code'] ?? '';
    
    // Patrón: /pool/POOL_CODIGO/
    if (preg_match('/\/pool\/([^\/\?]+)/', $uri, $matches)) {
        return array(
            'type' => 'pool',
            'code' => sanitize_text_field($matches[1])
        );
    }
    
    // Patrón: /estudio/ESTUDIO_CODIGO/
    if (preg_match('/\/estudio\/([^\/\?]+)/', $uri, $matches)) {
        return array(
            'type' => 'study',
            'code' => sanitize_text_field($matches[1])
        );
    }
    
    // Fallback a query param ?code=XXX&type=pool
    if ($query_code && isset($_GET['type']) && $_GET['type'] === 'pool') {
        return array(
            'type' => 'pool',
            'code' => sanitize_text_field($query_code)
        );
    }
    
    return array('type' => 'unknown', 'code' => '');
}

/**
 * Verificar si un código es un pool válido y activo
 *
 * @param string $pool_code Código del pool
 * @return array|false Datos del pool o false si no existe/no está activo
 */
function eipsi_get_valid_pool($pool_code) {
    global $wpdb;
    
    if (empty($pool_code)) {
        return false;
    }
    
    // Buscar en la tabla de pools
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $pool = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$pools_table} WHERE pool_name = %s OR id = %d",
        $pool_code,
        is_numeric($pool_code) ? intval($pool_code) : 0
    ));
    
    if (!$pool) {
        return false;
    }
    
    // Verificar estado
    if ($pool->status !== 'active') {
        return false;
    }
    
    // Decodificar config JSON
    $config = json_decode($pool->config, true) ?: array();
    
    // Preparar datos normalizados
    $pool_data = array(
        'id' => $pool->id,
        'code' => $pool->pool_name,
        'title' => $pool->pool_name,
        'description' => $pool->pool_description,
        'incentive_message' => $config['incentive_message'] ?? '',
        'redirect_mode' => $config['redirect_mode'] ?? 'transition',
        'status' => $pool->status,
        'method' => $pool->method,
        'notify_on_completion' => $config['notify_on_completion'] ?? false,
        'config' => $config,
        'created_at' => $pool->created_at,
        'updated_at' => $pool->updated_at
    );
    
    return $pool_data;
}

/**
 * Verificar si un participante ya tiene asignación en un pool
 *
 * @param string $pool_code Código del pool
 * @param string $participant_id ID del participante (email o ID)
 * @return array|false Datos de la asignación o false
 */
function eipsi_get_pool_assignment($pool_code, $participant_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'eipsi_pool_assignments';
    
    // Verificar si tabla existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    
    if (!$table_exists) {
        // Tabla no existe aún, retornar false
        return false;
    }
    
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE pool_code = %s AND participant_id = %s",
        $pool_code, $participant_id
    ));
    
    if ($row) {
        return array(
            'assignment_id' => $row->id,
            'study_id' => $row->study_id,
            'assigned_at' => $row->assigned_at,
            'pool_code' => $row->pool_code
        );
    }
    
    return false;
}

/**
 * Generar URL de acceso al pool
 *
 * @param string $pool_code Código del pool
 * @return string URL completa
 */
function eipsi_get_pool_url($pool_code) {
    return home_url('/pool/' . sanitize_title($pool_code) . '/');
}

/**
 * Generar URL de estudio individual
 *
 * @param string $study_code Código del estudio
 * @return string URL completa
 */
function eipsi_get_study_url($study_code) {
    return home_url('/estudio/' . sanitize_title($study_code) . '/');
}

/**
 * Obtener o crear ID de participante desde cookie/session
 *
 * @return string ID del participante
 */
function eipsi_get_participant_id() {
    // Primero verificar si hay usuario logueado de WordPress
    if (is_user_logged_in()) {
        return 'wp_' . get_current_user_id();
    }
    
    // Verificar cookie existente
    if (isset($_COOKIE['eipsi_participant_id'])) {
        return sanitize_text_field($_COOKIE['eipsi_participant_id']);
    }
    
    // Verificar sessionStorage via AJAX (no disponible en PHP directamente)
    // Retornar null para indicar que se necesita identificación
    return null;
}

/**
 * Crear nueva cookie de participante
 *
 * @param string $participant_id ID a guardar
 * @return bool
 */
function eipsi_set_participant_cookie($participant_id) {
    if (empty($participant_id)) {
        return false;
    }
    
    // Cookie por 1 año
    setcookie('eipsi_participant_id', $participant_id, time() + 365 * 24 * 60 * 60, '/');
    
    return true;
}

/**
 * Renderizar página de acceso al pool (interfaz minimalista)
 *
 * @param string $pool_code Código del pool
 * @param array $pool_data Datos del pool
 * @return void
 */
function eipsi_render_pool_access_page($pool_code, $pool_data) {
    // Verificar si el participante ya tiene asignación
    $participant_id = eipsi_get_participant_id();
    
    if ($participant_id) {
        $assignment = eipsi_get_pool_assignment($pool_code, $participant_id);
        
        if ($assignment) {
            // Ya asignado, redirigir al estudio
            $study_url = eipsi_get_study_url($assignment['study_id']);
            wp_redirect($study_url);
            exit;
        }
    }
    
    // Mostrar interfaz de acceso
    $template_path = EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/pool-access.php';
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Fallback si no existe el template
        wp_die(__('Error: Template de pool no encontrado.', 'eipsi-forms'));
    }
    
    exit;
}

/**
 * ============================================================================
 * FASE 2 - ALEATORIZACIÓN SIMPLE EQUIPROBABLE
 * ============================================================================
 *
 * Algoritmo de asignación aleatoria simple equiprobable para pools.
 * Cada estudio disponible tiene la misma probabilidad de ser asignado.
 *
 * @since 2.5.4
 */

/**
 * Realizar aleatorización simple equiprobable
 *
 * @param string $pool_code Código del pool
 * @param string $participant_id ID del participante
 * @param array $pool_data Datos del pool (con estudios y probabilidades)
 * @return array|false Estudio asignado o false si error/pool saturado
 */
function eipsi_pool_randomize($pool_code, $participant_id, $pool_data) {
    global $wpdb;
    
    // Verificar si pool está activo
    if (!$pool_data || $pool_data['status'] !== 'active') {
        error_log('[EIPSI-POOL] Pool no activo: ' . $pool_code);
        return false;
    }
    
    // Verificar si ya tiene asignación (idempotencia)
    $existing = eipsi_get_pool_assignment($pool_code, $participant_id);
    if ($existing) {
        error_log('[EIPSI-POOL] Participante ya asignado: ' . $participant_id);
        return $existing;
    }
    
    // Obtener estudios del config
    $studies = $pool_data['config']['studies'] ?? array();
    if (empty($studies)) {
        error_log('[EIPSI-POOL] No hay estudios configurados: ' . $pool_code);
        return false;
    }
    
    // Filtrar estudios disponibles (que no alcanzaron su target)
    $available_studies = array_filter($studies, function($study) use ($pool_data) {
        $current_count = $study['current_count'] ?? 0;
        $target_count = $study['target_count'] ?? PHP_INT_MAX;
        return $current_count < $target_count;
    });
    
    // Re-indexar array
    $available_studies = array_values($available_studies);
    
    if (empty($available_studies)) {
        // Pool saturado
        error_log('[EIPSI-POOL] Pool saturado: ' . $pool_code);
        do_action('eipsi_pool_saturated', $pool_code, $participant_id);
        return false;
    }
    
    // ALEATORIZACIÓN SIMPLE EQUIPROBABLE
    // Cada estudio tiene probabilidad 1/n donde n = cantidad de estudios disponibles
    $random_index = array_rand($available_studies);
    $assigned_study = $available_studies[$random_index];
    
    $study_id = $assigned_study['study_id'];
    $study_code = is_array($study_id) ? ($study_id['code'] ?? $study_id['id']) : $study_id;
    
    // Guardar asignación en BD
    $assignment = array(
        'pool_code' => $pool_code,
        'participant_id' => $participant_id,
        'study_id' => $study_code,
        'assigned_at' => current_time('mysql'),
        'assignment_method' => 'simple_equiprobable',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    );
    
    $result = eipsi_save_pool_assignment($assignment);
    
    if (!$result) {
        error_log('[EIPSI-POOL] Error guardando asignación');
        return false;
    }
    
    // Incrementar contador del estudio (en config)
    eipsi_increment_study_count($pool_code, $study_code);
    
    // Log para auditoría
    error_log(sprintf(
        '[EIPSI-POOL] Asignación exitosa: pool=%s, participant=%s, study=%s, method=%s',
        $pool_code,
        $participant_id,
        $study_code,
        'simple_equiprobable'
    ));
    
    return array(
        'assignment_id' => $result,
        'study_id' => $study_code,
        'assigned_at' => $assignment['assigned_at'],
        'pool_code' => $pool_code,
        'method' => 'simple_equiprobable'
    );
}

/**
 * Guardar asignación de pool en la base de datos
 *
 * @param array $assignment Datos de la asignación
 * @return int|false ID de la asignación o false si error
 */
function eipsi_save_pool_assignment($assignment) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'eipsi_pool_assignments';
    
    $result = $wpdb->insert(
        $table,
        array(
            'pool_id' => $assignment['pool_code'],  // Guardamos el código como ID
            'participant_id' => $assignment['participant_id'],
            'study_id' => $assignment['study_id'],
            'assigned_at' => $assignment['assigned_at'],
            'ip_address' => $assignment['ip_address'],
            'user_agent' => substr($assignment['user_agent'] ?? '', 0, 255)
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        error_log('[EIPSI-POOL] Error insertando asignación: ' . $wpdb->last_error);
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * Incrementar contador de participantes para un estudio en el pool
 *
 * @param string $pool_code Código del pool
 * @param string $study_id ID del estudio
 * @return bool
 */
function eipsi_increment_study_count($pool_code, $study_id) {
    global $wpdb;
    
    // Obtener pool actual
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $pool = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$pools_table} WHERE pool_name = %s",
        $pool_code
    ));
    
    if (!$pool) {
        return false;
    }
    
    // Decodificar config
    $config = json_decode($pool->config, true) ?: array();
    $studies = $config['studies'] ?? array();
    
    // Encontrar y actualizar el estudio
    foreach ($studies as &$study) {
        $study_code = is_array($study['study_id']) ? ($study['study_id']['code'] ?? $study['study_id']['id']) : $study['study_id'];
        if ($study_code == $study_id) {
            $study['current_count'] = ($study['current_count'] ?? 0) + 1;
            break;
        }
    }
    unset($study); // Romper referencia
    
    // Guardar config actualizado
    $config['studies'] = $studies;
    
    $wpdb->update(
        $pools_table,
        array('config' => json_encode($config)),
        array('id' => $pool->id),
        array('%s'),
        array('%d')
    );
    
    return true;
}

/**
 * Renderizar página de asignación exitosa (página de transición)
 *
 * @param string $pool_code Código del pool
 * @param string $participant_id ID del participante
 * @param array $assignment Datos de la asignación
 * @return void
 */
function eipsi_render_pool_assigned_page($pool_code, $participant_id, $assignment) {
    $template_path = EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/pool-assigned.php';
    
    // Variables para el template
    $study_id = $assignment['study_id'];
    $study_url = eipsi_get_study_url($study_id);
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Fallback: redirigir directo
        wp_redirect($study_url);
        exit;
    }
    
    exit;
}

/**
 * ============================================================================
 * FASE 3 - DASHBOARD DE ADMINISTRACIÓN DEL POOL
 * ============================================================================
 *
 * Funciones para el panel de control del investigador:
 * - Estadísticas de asignaciones
 * - Distribución por estudio
 * - Exportación CSV
 * - Acciones (pausar, cerrar)
 *
 * @since 2.5.4
 */

/**
 * Obtener estadísticas de un pool
 *
 * @param string $pool_code Código del pool
 * @return array Estadísticas del pool
 */
function eipsi_get_pool_stats($pool_code) {
    global $wpdb;
    
    $pool_data = eipsi_get_valid_pool($pool_code);
    if (!$pool_data) {
        return false;
    }
    
    $assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';
    
    // Total asignaciones
    $total_assignments = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %s",
        $pool_code
    ));
    
    // Asignaciones por estudio
    $by_study = $wpdb->get_results($wpdb->prepare(
        "SELECT study_id, COUNT(*) as count FROM {$assignments_table} WHERE pool_id = %s GROUP BY study_id",
        $pool_code
    ), OBJECT_K);
    
    // Distribución con porcentajes
    $studies = $pool_data['config']['studies'] ?? array();
    $distribution = array();
    
    foreach ($studies as $study) {
        $study_id = is_array($study['study_id']) ? ($study['study_id']['code'] ?? $study['study_id']['id']) : $study['study_id'];
        $count = isset($by_study[$study_id]) ? intval($by_study[$study_id]->count) : 0;
        $target = $study['target_count'] ?? 0;
        
        $distribution[] = array(
            'study_id' => $study_id,
            'name' => $study_id, // TODO: obtener nombre real del estudio
            'count' => $count,
            'target' => $target,
            'percentage' => $total_assignments > 0 ? round(($count / $total_assignments) * 100, 1) : 0,
            'fill_percentage' => $target > 0 ? round(($count / $target) * 100, 1) : 0
        );
    }
    
    return array(
        'pool_code' => $pool_code,
        'status' => $pool_data['status'],
        'total_assignments' => intval($total_assignments),
        'studies_count' => count($studies),
        'distribution' => $distribution,
        'created_at' => $pool_data['created_at'],
        'updated_at' => $pool_data['updated_at']
    );
}

/**
 * Exportar asignaciones de un pool a CSV
 *
 * @param string $pool_code Código del pool
 * @return string Contenido CSV
 */
function eipsi_export_pool_assignments_csv($pool_code) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'eipsi_pool_assignments';
    
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE pool_id = %s ORDER BY assigned_at DESC",
        $pool_code
    ));
    
    if (empty($assignments)) {
        return false;
    }
    
    // Headers CSV
    $csv = "participant_id,study_id,assigned_at,ip_address,user_agent\n";
    
    foreach ($assignments as $row) {
        $csv .= sprintf(
            "%s,%s,%s,%s,%s\n",
            $row->participant_id,
            $row->study_id,
            $row->assigned_at,
            $row->ip_address,
            str_replace(array("\n", "\r", ","), array(" ", " ", " "), $row->user_agent)
        );
    }
    
    return $csv;
}

/**
 * Cambiar estado de un pool
 *
 * @param string $pool_code Código del pool
 * @param string $new_status Nuevo estado (active, paused, closed)
 * @return bool
 */
function eipsi_change_pool_status($pool_code, $new_status) {
    global $wpdb;
    
    $valid_statuses = array('active', 'paused', 'closed');
    if (!in_array($new_status, $valid_statuses)) {
        return false;
    }
    
    $table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    
    $result = $wpdb->update(
        $table,
        array('status' => $new_status),
        array('pool_name' => $pool_code),
        array('%s'),
        array('%s')
    );
    
    return $result !== false;
}

/**
 * Pausar un pool (no acepta nuevas asignaciones)
 *
 * @param string $pool_code Código del pool
 * @return bool
 */
function eipsi_pause_pool($pool_code) {
    return eipsi_change_pool_status($pool_code, 'paused');
}

/**
 * Cerrar un pool (definitivo)
 *
 * @param string $pool_code Código del pool
 * @return bool
 */
function eipsi_close_pool($pool_code) {
    return eipsi_change_pool_status($pool_code, 'closed');
}

/**
 * Reactivar un pool pausado
 *
 * @param string $pool_code Código del pool
 * @return bool
 */
function eipsi_activate_pool($pool_code) {
    return eipsi_change_pool_status($pool_code, 'active');
}
