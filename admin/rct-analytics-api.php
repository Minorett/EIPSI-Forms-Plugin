<?php
/**
 * EIPSI Forms - RCT Analytics API
 * 
 * Maneja los endpoints AJAX para el RCT Analytics Dashboard
 * 
 * @package EIPSI_Forms
 * @since 1.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar los endpoints AJAX
 */
function eipsi_register_rct_analytics_endpoints() {
    // Para usuarios logueados
    add_action('wp_ajax_eipsi_get_randomizations', 'eipsi_get_randomizations');
    add_action('wp_ajax_eipsi_get_randomization_details', 'eipsi_get_randomization_details');
    add_action('wp_ajax_eipsi_get_randomization_users', 'eipsi_get_randomization_users');
    add_action('wp_ajax_eipsi_get_distribution_stats', 'eipsi_get_distribution_stats');
}
add_action('init', 'eipsi_register_rct_analytics_endpoints');

/**
 * Obtener lista de aleatorizaciones para el dashboard
 */
function eipsi_get_randomizations() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
        return;
    }

    global $wpdb;

    try {
        // Query principal: listar aleatorizaciones con estadísticas
        $query = "
            SELECT 
                rc.randomization_id,
                rc.formularios,
                rc.probabilidades,
                rc.method,
                rc.created_at,
                rc.updated_at,
                rc.show_instructions,
                COUNT(DISTINCT ra.user_fingerprint) as total_assigned,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                MAX(ra.assigned_at) as last_assignment,
                AVG(ra.access_count) as avg_access_count,
                AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days
            FROM {$wpdb->prefix}eipsi_randomization_configs rc
            LEFT JOIN {$wpdb->prefix}eipsi_randomization_assignments ra 
                ON rc.randomization_id = ra.randomization_id
            GROUP BY rc.randomization_id
            ORDER BY rc.created_at DESC
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($query);

        if ($results === false) {
            wp_send_json_error('Error en consulta SQL');
            return;
        }

        $randomizations = array();

        foreach ($results as $row) {
            // Decodificar JSON de formularios
            $formularios = json_decode($row->formularios, true);
            if (!$formularios) $formularios = array();

            // Decodificar JSON de probabilidades
            $probabilidades = json_decode($row->probabilidades, true);
            if (!$probabilidades) $probabilidades = array();

            // Obtener distribución por formulario
            $distribution_query = "
                SELECT 
                    ra.assigned_form_id,
                    COUNT(*) as count,
                    COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                    AVG(ra.access_count) as avg_access_count,
                    AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days
                FROM {$wpdb->prefix}eipsi_randomization_assignments ra
                WHERE ra.randomization_id = %s
                GROUP BY ra.assigned_form_id
            ";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $distribution = $wpdb->get_results($wpdb->prepare($distribution_query, $row->randomization_id));

            $formatted_distribution = array();
            foreach ($distribution as $dist) {
                // Obtener título del formulario
                $form_title = get_the_title($dist->assigned_form_id);
                if (!$form_title) {
                    $form_title = "Formulario ID: {$dist->assigned_form_id}";
                }

                $formatted_distribution[] = array(
                    'form_id' => $dist->assigned_form_id,
                    'form_title' => $form_title,
                    'count' => intval($dist->count),
                    'completed_count' => intval($dist->completed_count),
                    'avg_access_count' => round($dist->avg_access_count, 1),
                    'avg_days' => round($dist->avg_days, 1)
                );
            }

            // Determinar si está activa (tiene asignaciones recientes)
            $is_active = false;
            if ($row->last_assignment) {
                $days_since_last = (time() - strtotime($row->last_assignment)) / (60 * 60 * 24);
                $is_active = $days_since_last <= 30; // Activa si hay actividad en los últimos 30 días
            }

            // Formatear fechas
            $created_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($row->created_at));
            $last_assignment_formatted = $row->last_assignment ? 
                human_time_diff(strtotime($row->last_assignment)) . ' ago' : 
                'Nunca';

            $randomizations[] = array(
                'randomization_id' => $row->randomization_id,
                'method' => $row->method,
                'is_active' => $is_active,
                'created_at' => $row->created_at,
                'created_formatted' => $created_formatted,
                'last_assignment' => $row->last_assignment,
                'last_assignment_formatted' => $last_assignment_formatted,
                'total_assigned' => intval($row->total_assigned),
                'completed_count' => intval($row->completed_count),
                'avg_access_count' => round($row->avg_access_count, 1),
                'avg_days' => round($row->avg_days, 1),
                'distribution' => $formatted_distribution,
                'formularios' => $formularios,
                'probabilidades' => $probabilidades
            );
        }

        wp_send_json_success(array(
            'randomizations' => $randomizations,
            'total_count' => count($randomizations)
        ));

    } catch (Exception $e) {
        error_log('[EIPSI RCT Analytics] Error: ' . $e->getMessage());
        wp_send_json_error('Error interno del servidor');
    }
}

/**
 * Obtener detalles específicos de una aleatorización
 */
function eipsi_get_randomization_details() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
        return;
    }

    $randomization_id = sanitize_text_field($_POST['randomization_id'] ?? '');
    if (empty($randomization_id)) {
        wp_send_json_error('ID de aleatorización requerido');
        return;
    }

    global $wpdb;

    try {
        // Obtener configuración básica
        $config_query = "
            SELECT 
                rc.*,
                COUNT(DISTINCT ra.user_fingerprint) as total_assigned,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                MAX(ra.assigned_at) as last_assignment,
                MIN(ra.assigned_at) as first_assignment,
                AVG(ra.access_count) as avg_access_count,
                AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days
            FROM {$wpdb->prefix}eipsi_randomization_configs rc
            LEFT JOIN {$wpdb->prefix}eipsi_randomization_assignments ra 
                ON rc.randomization_id = ra.randomization_id
            WHERE rc.randomization_id = %s
            GROUP BY rc.id
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $config = $wpdb->get_row($wpdb->prepare($config_query, $randomization_id));

        if (!$config) {
            wp_send_json_error('Aleatorización no encontrada');
            return;
        }

        // Decodificar configuración
        $formularios = json_decode($config->formularios, true) ?: array();
        $probabilidades = json_decode($config->probabilidades, true) ?: array();

        // Obtener distribución detallada por formulario
        $distribution_query = "
            SELECT 
                ra.assigned_form_id,
                COUNT(*) as total_assigned,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                COUNT(CASE WHEN ra.last_access IS NULL THEN 1 END) as dropout_count,
                AVG(ra.access_count) as avg_access_count,
                AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days,
                MIN(ra.assigned_at) as first_assignment,
                MAX(ra.assigned_at) as last_assignment
            FROM {$wpdb->prefix}eipsi_randomization_assignments ra
            WHERE ra.randomization_id = %s
            GROUP BY ra.assigned_form_id
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $distribution_raw = $wpdb->get_results($wpdb->prepare($distribution_query, $randomization_id));

        $distribution = array();
        foreach ($distribution_raw as $dist) {
            $form_title = get_the_title($dist->assigned_form_id) ?: "Formulario ID: {$dist->assigned_form_id}";
            
            $completion_rate = $dist->total_assigned > 0 ? 
                round(($dist->completed_count / $dist->total_assigned) * 100, 1) : 0;

            $distribution[] = array(
                'form_id' => $dist->assigned_form_id,
                'form_title' => $form_title,
                'total_assigned' => intval($dist->total_assigned),
                'completed_count' => intval($dist->completed_count),
                'dropout_count' => intval($dist->dropout_count),
                'completion_rate' => $completion_rate,
                'avg_access_count' => round($dist->avg_access_count, 1),
                'avg_days' => round($dist->avg_days, 1),
                'first_assignment' => $dist->first_assignment,
                'last_assignment' => $dist->last_assignment
            );
        }

        // Calcular estadísticas generales
        $total_assigned = intval($config->total_assigned);
        $completed_count = intval($config->completed_count);
        $completion_rate = $total_assigned > 0 ? round(($completed_count / $total_assigned) * 100, 1) : 0;
        $dropout_count = $total_assigned - $completed_count;
        $dropout_rate = $total_assigned > 0 ? round(($dropout_count / $total_assigned) * 100, 1) : 0;

        // Formatear fechas
        $created_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($config->created_at));
        $first_assignment_formatted = $config->first_assignment ? 
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($config->first_assignment)) : 
            'N/A';
        $last_assignment_formatted = $config->last_assignment ? 
            human_time_diff(strtotime($config->last_assignment)) . ' ago' : 
            'Nunca';

        // Determinar estado
        $is_active = false;
        if ($config->last_assignment) {
            $days_since_last = (time() - strtotime($config->last_assignment)) / (60 * 60 * 24);
            $is_active = $days_since_last <= 30;
        }

        wp_send_json_success(array(
            'randomization_id' => $randomization_id,
            'created_at' => $config->created_at,
            'created_formatted' => $created_formatted,
            'method' => $config->method,
            'show_instructions' => (bool)$config->show_instructions,
            'is_active' => $is_active,
            'formularios' => $formularios,
            'probabilidades' => $probabilidades,
            
            // Estadísticas generales
            'total_assigned' => $total_assigned,
            'completed_count' => $completed_count,
            'dropout_count' => $dropout_count,
            'completion_rate' => $completion_rate,
            'dropout_rate' => $dropout_rate,
            'avg_access_count' => round($config->avg_access_count, 1),
            'avg_days' => round($config->avg_days, 1),
            
            // Distribución por formulario
            'distribution' => $distribution,
            
            // Timeline
            'first_assignment' => $config->first_assignment,
            'first_assignment_formatted' => $first_assignment_formatted,
            'last_assignment' => $config->last_assignment,
            'last_assignment_formatted' => $last_assignment_formatted
        ));

    } catch (Exception $e) {
        error_log('[EIPSI RCT Analytics] Error en detalles: ' . $e->getMessage());
        wp_send_json_error('Error interno del servidor');
    }
}

/**
 * Obtener lista de usuarios de una aleatorización
 */
function eipsi_get_randomization_users() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
        return;
    }

    $randomization_id = sanitize_text_field($_POST['randomization_id'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $limit = intval($_POST['limit'] ?? 50);
    $offset = ($page - 1) * $limit;

    if (empty($randomization_id)) {
        wp_send_json_error('ID de aleatorización requerido');
        return;
    }

    global $wpdb;

    try {
        // Query principal
        $query = "
            SELECT 
                ra.user_fingerprint,
                ra.assigned_form_id,
                ra.assigned_at,
                ra.last_access,
                ra.access_count,
                CASE WHEN ra.last_access IS NOT NULL THEN 'Sí' ELSE 'No' END as completado,
                DATEDIFF(CURDATE(), DATE(ra.assigned_at)) as dias_transcurridos
            FROM {$wpdb->prefix}eipsi_randomization_assignments ra
            WHERE ra.randomization_id = %s
            ORDER BY ra.assigned_at DESC
            LIMIT %d OFFSET %d
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($wpdb->prepare($query, $randomization_id, $limit, $offset));

        // Query para total
        $count_query = "
            SELECT COUNT(*) as total
            FROM {$wpdb->prefix}eipsi_randomization_assignments
            WHERE randomization_id = %s
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = $wpdb->get_var($wpdb->prepare($count_query, $randomization_id));

        $users = array();
        foreach ($results as $row) {
            $form_title = get_the_title($row->assigned_form_id) ?: "Formulario ID: {$row->assigned_form_id}";
            
            // Anonimizar fingerprint
            $fingerprint_short = substr($row->user_fingerprint, 0, 8) . '...' . substr($row->user_fingerprint, -6);

            // Formatear fecha de asignación
            $assigned_formatted = date_i18n('j M, H:i', strtotime($row->assigned_at));

            // Formatear último acceso
            $last_access_formatted = $row->last_access ? 
                human_time_diff(strtotime($row->last_access)) . ' ago' : 
                'Nunca';

            $users[] = array(
                'fingerprint' => $fingerprint_short,
                'fingerprint_full' => $row->user_fingerprint,
                'form_id' => $row->assigned_form_id,
                'form_title' => $form_title,
                'assigned_at' => $row->assigned_at,
                'assigned_formatted' => $assigned_formatted,
                'last_access' => $row->last_access,
                'last_access_formatted' => $last_access_formatted,
                'access_count' => intval($row->access_count),
                'completado' => $row->completado,
                'dias_transcurridos' => intval($row->dias_transcurridos)
            );
        }

        wp_send_json_success(array(
            'users' => $users,
            'total' => intval($total),
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ));

    } catch (Exception $e) {
        error_log('[EIPSI RCT Analytics] Error en usuarios: ' . $e->getMessage());
        wp_send_json_error('Error interno del servidor');
    }
}

/**
 * Generar y descargar CSV de asignaciones
 */
function eipsi_download_assignments_csv() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_die('No autorizado', '', array('response' => 403));
    }

    $randomization_id = sanitize_text_field($_POST['randomization_id'] ?? '');
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : null;

    if (empty($randomization_id)) {
        wp_die('ID de aleatorización requerido');
    }

    global $wpdb;

    try {
        // Query base
        $query = "
            SELECT 
                ra.randomization_id,
                ra.user_fingerprint,
                ra.assigned_form_id,
                ra.assigned_at,
                ra.last_access,
                ra.access_count
            FROM {$wpdb->prefix}eipsi_randomization_assignments ra
            WHERE ra.randomization_id = %s
        ";
        
        $params = array($randomization_id);
        
        // Filtro opcional por formulario
        if ($form_id) {
            $query .= " AND ra.assigned_form_id = %d";
            $params[] = $form_id;
        }
        
        $query .= " ORDER BY ra.assigned_at DESC";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($wpdb->prepare($query, $params));

        if (empty($results)) {
            wp_die('No hay asignaciones para esta aleatorización' . ($form_id ? ' y formulario' : ''));
        }

        // Preparar nombre del archivo
        $filename = $randomization_id . '_assignments' . ($form_id ? '_form_' . $form_id : '_complete') . '.csv';

        // Headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Abrir output stream
        $output = fopen('php://output', 'w');

        // UTF-8 BOM para Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        $headers = array(
            'randomization_id',
            'user_fingerprint',
            'assigned_form_id',
            'assigned_form_name',
            'assigned_at',
            'last_access',
            'access_count',
            'days_since_assignment',
            'completed_status'
        );
        fputcsv($output, $headers);

        // Procesar cada fila
        foreach ($results as $row) {
            // Obtener nombre del formulario
            $form = get_post($row->assigned_form_id);
            $form_name = $form ? $form->post_title : 'Desconocido';

            // Calcular días desde asignación
            $assigned_date = new DateTime($row->assigned_at, wp_timezone());
            $today = new DateTime('now', wp_timezone());
            $days_diff = $today->diff($assigned_date)->days;

            // Determinar status con reglas especificadas
            $completed_status = 'No Iniciado';
            if ($row->last_access) {
                $access_count = intval($row->access_count);
                if ($access_count >= 3) {
                    $completed_status = 'Completado';
                } elseif ($access_count >= 1) {
                    $completed_status = 'Parcial (' . $access_count . ' acceso' . ($access_count > 1 ? 's' : '') . ')';
                } else {
                    $completed_status = 'Abandonado (0 accesos)';
                }
            }

            // Anonimizar fingerprint (primeros 8 + ... + últimos 8)
            $full_fp = $row->user_fingerprint;
            $anon_fp = 'fp_' . substr($full_fp, 0, 8) . '...' . substr($full_fp, -8);

            // Formatear fechas en ISO 8601
            $assigned_at = wp_date('Y-m-d H:i:s', strtotime($row->assigned_at));
            $last_access = $row->last_access ? wp_date('Y-m-d H:i:s', strtotime($row->last_access)) : '';

            // Crear fila
            $csv_row = array(
                $row->randomization_id,
                $anon_fp,
                $row->assigned_form_id,
                $form_name,
                $assigned_at,
                $last_access,
                $row->access_count,
                $days_diff,
                $completed_status
            );

            fputcsv($output, $csv_row);
        }

        fclose($output);
        exit;

    } catch (Exception $e) {
        error_log('[EIPSI RCT Analytics] Error en descarga CSV: ' . $e->getMessage());
        wp_die('Error generando CSV: ' . $e->getMessage());
    }
}
add_action('wp_ajax_eipsi_download_assignments_csv', 'eipsi_download_assignments_csv');

/**
 * Obtener estadísticas de distribución: Teórico vs Real
 * 
 * Compara la distribución configurada (teórica) vs la distribución actual (real)
 * para detectar desbalances, sesgos o errores en la aleatorización
 * 
 * @param string $randomization_id ID de la configuración de aleatorización
 * @param string $format Formato de respuesta: 'json' | 'summary'
 * @return array Estadísticas de distribución con drift analysis
 */
function eipsi_get_distribution_stats() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
        return;
    }

    $randomization_id = sanitize_text_field($_POST['randomization_id'] ?? '');
    $format = sanitize_text_field($_POST['format'] ?? 'json');

    if (empty($randomization_id)) {
        wp_send_json_error('ID de aleatorización requerido');
        return;
    }

    global $wpdb;

    try {
        // Obtener configuración de aleatorización
        $config_query = "
            SELECT 
                rc.randomization_id,
                rc.formularios,
                rc.probabilidades,
                rc.created_at,
                COUNT(DISTINCT ra.user_fingerprint) as total_assigned,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count
            FROM {$wpdb->prefix}eipsi_randomization_configs rc
            LEFT JOIN {$wpdb->prefix}eipsi_randomization_assignments ra 
                ON rc.randomization_id = ra.randomization_id
            WHERE rc.randomization_id = %s
            GROUP BY rc.id
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $config = $wpdb->get_row($wpdb->prepare($config_query, $randomization_id));

        if (!$config) {
            wp_send_json_error('Aleatorización no encontrada');
            return;
        }

        // Decodificar configuración
        $formularios = json_decode($config->formularios, true) ?: array();
        $probabilidades = json_decode($config->probabilidades, true) ?: array();

        $total_assigned = intval($config->total_assigned);
        $completed_count = intval($config->completed_count);

        // Obtener distribución real por formulario
        $distribution_query = "
            SELECT 
                ra.assigned_form_id,
                COUNT(*) as assigned_count,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                AVG(ra.access_count) as avg_access_count,
                AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days_to_complete
            FROM {$wpdb->prefix}eipsi_randomization_assignments ra
            WHERE ra.randomization_id = %s
            GROUP BY ra.assigned_form_id
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $distribution_raw = $wpdb->get_results($wpdb->prepare($distribution_query, $randomization_id));

        // Crear mapa de distribución real
        $real_distribution = array();
        foreach ($distribution_raw as $dist) {
            $real_distribution[$dist->assigned_form_id] = array(
                'assigned_count' => intval($dist->assigned_count),
                'completed_count' => intval($dist->completed_count),
                'avg_access_count' => round($dist->avg_access_count, 1),
                'avg_days_to_complete' => round($dist->avg_days_to_complete, 1)
            );
        }

        // Procesar cada formulario configurado
        $formularios_stats = array();
        $total_drift_sum = 0;
        $max_drift = 0;
        $max_drift_form_id = null;

        foreach ($formularios as $form_config) {
            $form_id = intval($form_config['id']);
            $theoretical_probability = floatval($probabilidades[$form_id] ?? 0);

            // Obtener datos reales para este formulario
            $real_data = $real_distribution[$form_id] ?? array(
                'assigned_count' => 0,
                'completed_count' => 0,
                'avg_access_count' => 0,
                'avg_days_to_complete' => 0
            );

            // Calcular porcentajes reales
            $assigned_count = $real_data['assigned_count'];
            $assigned_percentage = $total_assigned > 0 ? 
                round(($assigned_count / $total_assigned) * 100, 1) : 0;

            // Calcular drift (diferencia entre real y teórico)
            $drift_percentage = 0;
            $drift_status = 'ok';

            if ($theoretical_probability > 0) {
                $drift = $assigned_percentage - $theoretical_probability;
                $drift_percentage = round(($drift / $theoretical_probability) * 100, 1);

                // Determinar status basado en thresholds
                if (abs($drift_percentage) <= 3) {
                    $drift_status = 'ok';
                } elseif (abs($drift_percentage) <= 5) {
                    $drift_status = 'warning';
                } else {
                    $drift_status = 'alert';
                }
            }

            // Calcular tasas de completado
            $completion_rate = $assigned_count > 0 ? 
                round(($real_data['completed_count'] / $assigned_count) * 100, 1) : 0;
            $dropout_rate = 100 - $completion_rate;

            // Obtener título del formulario
            $form_title = get_the_title($form_id) ?: "Formulario ID: {$form_id}";

            // Determinar indicador de estado
            $status_indicator = '✅';
            if ($drift_status === 'warning') {
                $status_indicator = '⚠️';
            } elseif ($drift_status === 'alert') {
                $status_indicator = '🔴';
            }

            $form_stats = array(
                'form_id' => $form_id,
                'form_title' => $form_title,
                'probability_theoretical' => $theoretical_probability,
                'assigned_count' => $assigned_count,
                'assigned_percentage' => $assigned_percentage,
                'completed_count' => $real_data['completed_count'],
                'completion_rate' => $completion_rate,
                'dropout_rate' => $dropout_rate,
                'drift_percentage' => $drift_percentage,
                'drift_status' => $drift_status,
                'avg_access_count' => $real_data['avg_access_count'],
                'avg_days_to_complete' => $real_data['avg_days_to_complete'],
                'status_indicator' => $status_indicator
            );

            $formularios_stats[] = $form_stats;

            // Actualizar estadísticas globales
            $total_drift_sum += abs($drift_percentage);
            if (abs($drift_percentage) > $max_drift) {
                $max_drift = abs($drift_percentage);
                $max_drift_form_id = $form_id;
            }
        }

        // Calcular resumen global
        $avg_drift = count($formularios_stats) > 0 ? $total_drift_sum / count($formularios_stats) : 0;
        $overall_completion_rate = $total_assigned > 0 ? 
            round(($completed_count / $total_assigned) * 100, 1) : 0;

        // Calcular health score (0-100)
        $health_score = calculate_health_score($avg_drift, $overall_completion_rate, $total_assigned);

        // Determinar estado general
        $overall_status = 'ok';
        if ($max_drift > 5) {
            $overall_status = 'alert';
        } elseif ($max_drift > 3) {
            $overall_status = 'warning';
        }

        // Generar recomendación
        $recommendation = generate_distribution_recommendation($overall_status, $max_drift, $max_drift_form_id, $formularios);

        // Calcular margen de error (95% CI)
        $margin_error = calculate_margin_error($total_assigned);

        // Respuesta
        $response = array(
            'success' => true,
            'randomization_id' => $randomization_id,
            'created_at' => $config->created_at,
            'total_assigned' => $total_assigned,
            'completed_count' => $completed_count,
            'formularios' => $formularios_stats,
            'summary' => array(
                'total_drift' => round($total_drift_sum, 1),
                'max_drift' => $max_drift,
                'max_drift_form_id' => $max_drift_form_id,
                'overall_status' => $overall_status,
                'health_score' => $health_score,
                'recommendation' => $recommendation
            ),
            'metadata' => array(
                'calculation_timestamp' => time(),
                'sample_size_note' => $total_assigned . ' asignaciones (±' . $margin_error . '% error margin)'
            )
        );

        wp_send_json_success($response);

    } catch (Exception $e) {
        error_log('[EIPSI Distribution Stats] Error: ' . $e->getMessage());
        wp_send_json_error('Error interno del servidor');
    }
}

/**
 * Calcular health score basado en drift promedio, tasa de completado y tamaño de muestra
 * 
 * @param float $avg_drift Drift promedio absoluto
 * @param float $completion_rate Tasa de completado general
 * @param int $sample_size Tamaño de muestra
 * @return int Health score (0-100)
 */
function calculate_health_score($avg_drift, $completion_rate, $sample_size) {
    // Base score
    $score = 100;
    
    // Penalizar drift alto
    $drift_penalty = min($avg_drift * 2, 30); // Max 30 puntos por drift
    $score -= $drift_penalty * 0.5;
    
    // Penalizar completado bajo
    $completion_penalty = max(0, 100 - $completion_rate);
    $score -= $completion_penalty * 0.4;
    
    // Penalizar muestra pequeña
    $min_sample_size = 100; // Tamaño mínimo para análisis confiable
    $sample_penalty = max(0, (1 - ($sample_size / $min_sample_size)) * 20);
    $score -= $sample_penalty;
    
    return max(0, min(100, round($score)));
}

/**
 * Generar recomendación basada en el análisis de distribución
 * 
 * @param string $overall_status Estado general
 * @param float $max_drift Máximo drift encontrado
 * @param int $max_drift_form_id ID del formulario con máximo drift
 * @param array $formularios Configuración de formularios
 * @return string Recomendación en español
 */
function generate_distribution_recommendation($overall_status, $max_drift, $max_drift_form_id, $formularios) {
    if ($overall_status === 'ok') {
        return 'Distribución saludable. Los desbalances están dentro del rango estadísticamente esperado.';
    }
    
    if ($overall_status === 'warning') {
        $form_title = 'Formulario desconocido';
        foreach ($formularios as $form) {
            if (intval($form['id']) === $max_drift_form_id) {
                $form_title = get_the_title($form['id']) ?: 'Formulario ID: ' . $form['id'];
                break;
            }
        }
        
        return "Monitorear {$form_title} (drift: {$max_drift}%). Verificar que no haya sesgos sistemáticos.";
    }
    
    if ($overall_status === 'alert') {
        $form_title = 'Formulario desconocido';
        foreach ($formularios as $form) {
            if (intval($form['id']) === $max_drift_form_id) {
                $form_title = get_the_title($form['id']) ?: 'Formulario ID: ' . $form['id'];
                break;
            }
        }
        
        $actions = array();
        
        if ($max_drift > 10) {
            $actions[] = 'Verificar configuración de probabilidades';
            $actions[] = 'Revisar algoritmo de seed';
            $actions[] = 'Validar persistencia de asignaciones';
        }
        
        $actions[] = 'Aumentar tamaño de muestra';
        $actions[] = 'Investigar posibles sesgos en fingerprinting';
        
        return "ALERTA: {$form_title} muestra drift crítico ({$max_drift}%). " . implode('. ', $actions) . '.';
    }
    
    return 'Revisar configuración de distribución.';
}

/**
 * Calcular margen de error para el tamaño de muestra dado (95% CI)
 * 
 * @param int $n Tamaño de muestra
 * @return float Margen de error en porcentaje
 */
function calculate_margin_error($n) {
    if ($n <= 0) return 100;
    
    // Fórmula: 1.96 * sqrt(p(1-p)/n) * 100
    // Asumiendo p=0.5 (peor caso)
    $margin = 1.96 * sqrt(0.5 * 0.5 / $n) * 100;
    
    return round($margin, 1);
}
?>