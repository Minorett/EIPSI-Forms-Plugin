<?php
/**
 * EIPSI Forms - Pool Assignment AJAX API
 *
 * Handlers para que participantes se unan a pools de asignación aleatoria.
 * Funciona tanto para usuarios logueados como anónimos (nopriv).
 *
 * @package EIPSI_Forms
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler AJAX: unirse a un pool (usuarios logueados y anónimos).
 *
 * Inputs POST esperados:
 *   - eipsi_pool_join_nonce : nonce de seguridad
 *   - pool_id               : int
 *   - email                 : string (email del participante)
 *   - name                  : string (opcional)
 *
 * Respuesta JSON:
 *   { success: bool, data: { magic_link_url, study_name, pool_name, is_new_assignment } }
 *   { success: false, data: { message: string } }
 */
function eipsi_ajax_join_pool() {
    // -----------------------------------------------------------------
    // 1. Verificar nonce
    // -----------------------------------------------------------------
    $nonce = isset( $_POST['eipsi_pool_join_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['eipsi_pool_join_nonce'] ) )
        : '';

    if ( ! wp_verify_nonce( $nonce, 'eipsi_pool_join' ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Token de seguridad inválido. Recargá la página e intentá de nuevo.', 'eipsi-forms' ) ),
            403
        );
    }

    // -----------------------------------------------------------------
    // 2. Sanitizar inputs
    // -----------------------------------------------------------------
    $pool_id = isset( $_POST['pool_id'] ) ? absint( $_POST['pool_id'] ) : 0;
    $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : null;

    if ( ! $pool_id ) {
        wp_send_json_error(
            array( 'message' => __( 'ID de pool inválido.', 'eipsi-forms' ) ),
            400
        );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Por favor ingresá un email válido.', 'eipsi-forms' ) ),
            400
        );
    }

    // -----------------------------------------------------------------
    // 3. Llamar al servicio de asignación
    // -----------------------------------------------------------------
    if ( ! class_exists( 'EIPSI_Pool_Assignment_Service' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'services/class-pool-assignment-service.php';
    }

    $service = new EIPSI_Pool_Assignment_Service();
    $result  = $service->assign_participant_to_pool( $pool_id, $email, $name );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error(
            array( 'message' => $result->get_error_message() ),
            400
        );
    }

    // -----------------------------------------------------------------
    // 4. Respuesta de éxito
    // -----------------------------------------------------------------
    wp_send_json_success(
        array(
            'magic_link_url'    => esc_url( $result['magic_link_url'] ),
            'study_name'        => esc_html( $result['study_name'] ),
            'pool_name'         => esc_html( $result['pool_name'] ),
            'is_new_assignment' => (bool) $result['is_new_assignment'],
            'message'           => $result['is_new_assignment']
                ? __( '¡Listo! Te asignamos a tu estudio. Serás redirigido en un momento...', 'eipsi-forms' )
                : __( 'Ya tenés una asignación en este pool. Preparando tu acceso...', 'eipsi-forms' ),
        )
    );
}

add_action( 'wp_ajax_eipsi_join_pool', 'eipsi_ajax_join_pool' );
add_action( 'wp_ajax_nopriv_eipsi_join_pool', 'eipsi_ajax_join_pool' );

/**
 * Handler AJAX: obtener estadísticas de un pool (solo admins).
 *
 * Inputs POST esperados:
 *   - eipsi_pool_stats_nonce : nonce de seguridad
 *   - pool_id                : int
 *
 * Respuesta JSON:
 *   { success: bool, data: { by_study: {...}, total: int } }
 */
function eipsi_ajax_get_pool_stats() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Sin permisos.', 'eipsi-forms' ) ), 403 );
    }

    $nonce = isset( $_POST['eipsi_pool_stats_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['eipsi_pool_stats_nonce'] ) )
        : '';

    if ( ! wp_verify_nonce( $nonce, 'eipsi_pool_stats' ) ) {
        wp_send_json_error( array( 'message' => __( 'Token inválido.', 'eipsi-forms' ) ), 403 );
    }

    $pool_id = isset( $_POST['pool_id'] ) ? absint( $_POST['pool_id'] ) : 0;

    if ( ! $pool_id ) {
        wp_send_json_error( array( 'message' => __( 'ID de pool inválido.', 'eipsi-forms' ) ), 400 );
    }

    if ( ! class_exists( 'EIPSI_Pool_Assignment_Service' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'services/class-pool-assignment-service.php';
    }

    $service = new EIPSI_Pool_Assignment_Service();
    $stats   = $service->get_pool_stats( $pool_id );

    wp_send_json_success( $stats );
}

add_action( 'wp_ajax_eipsi_get_pool_stats', 'eipsi_ajax_get_pool_stats' );

/**
 * Handler AJAX: obtener resumen de todos los pools (para Overview).
 *
 * @since 2.5.3 - Fase 5: Pool Hub Dashboard
 */
function eipsi_ajax_get_all_pools_summary() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Sin permisos.', 'eipsi-forms')), 403);
    }

    global $wpdb;
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';

    $pools = $wpdb->get_results("SELECT * FROM {$pools_table} ORDER BY created_at DESC", ARRAY_A);

    $pools_summary = array();

    foreach ($pools as $pool) {
        $pool_id = intval($pool['id']);
        $studies = json_decode($pool['studies'] ?? '[]', true);
        $probabilities = json_decode($pool['probabilities'] ?? '[]', true);
        $config = json_decode($pool['config'] ?? '{}', true);

        $total_assignments = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d",
            $pool_id
        )));

        $completed_assignments = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d AND completed = 1",
            $pool_id
        )));

        $completion_rate = $total_assignments > 0 ? round(($completed_assignments / $total_assignments) * 100, 1) : 0;

        $distribution = array();
        $total_deviation = 0;

        foreach ($studies as $index => $study_id) {
            $expected_prob = isset($probabilities[$index]) ? floatval($probabilities[$index]) : 0;
            $actual_count = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d AND study_id = %d",
                $pool_id, $study_id
            )));

            $actual_pct = $total_assignments > 0 ? round(($actual_count / $total_assignments) * 100, 1) : 0;
            $deviation = abs($actual_pct - $expected_prob);
            $total_deviation += $deviation;

            $study_name = $wpdb->get_var($wpdb->prepare(
                "SELECT study_name FROM {$wpdb->prefix}survey_studies WHERE id = %d",
                $study_id
            ));

            $distribution[] = array(
                'study_id' => $study_id,
                'study_name' => $study_name,
                'expected' => $expected_prob,
                'actual' => $actual_pct,
                'count' => $actual_count,
                'deviation' => $deviation
            );
        }

        $balance_score = count($studies) > 0 ? round(100 - ($total_deviation / count($studies)), 1) : 100;

        $pools_summary[] = array(
            'id' => $pool_id,
            'name' => $pool['pool_name'],
            'description' => $pool['pool_description'],
            'status' => $pool['status'],
            'method' => $pool['method'],
            'studies_count' => count($studies),
            'total_assignments' => $total_assignments,
            'completed_assignments' => $completed_assignments,
            'completion_rate' => $completion_rate,
            'balance_score' => $balance_score,
            'distribution' => $distribution,
            'config' => $config,
            'created_at' => $pool['created_at']
        );
    }

    wp_send_json_success(array('pools' => $pools_summary));
}
add_action('wp_ajax_eipsi_get_all_pools_summary', 'eipsi_ajax_get_all_pools_summary');

/**
 * Handler AJAX: toggle estado activo/pausado de un pool.
 *
 * @since 2.5.3 - Fase 5: Pool Hub Dashboard
 */
function eipsi_ajax_toggle_pool_status() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Sin permisos.', 'eipsi-forms')), 403);
    }

    $pool_id = isset($_POST['pool_id']) ? absint($_POST['pool_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';

    if (!$pool_id || !in_array($status, array('active', 'paused'))) {
        wp_send_json_error(array('message' => __('Parámetros inválidos.', 'eipsi-forms')), 400);
    }

    global $wpdb;
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';

    $result = $wpdb->update(
        $pools_table,
        array('status' => $status),
        array('id' => $pool_id),
        array('%s'),
        array('%d')
    );

    if ($result === false) {
        wp_send_json_error(array('message' => __('Error al actualizar estado.', 'eipsi-forms')), 500);
    }

    wp_send_json_success(array(
        'pool_id' => $pool_id,
        'status' => $status,
        'message' => $status === 'active' ? __('Pool activado.', 'eipsi-forms') : __('Pool pausado.', 'eipsi-forms')
    ));
}
add_action('wp_ajax_eipsi_toggle_pool_status', 'eipsi_ajax_toggle_pool_status');

/**
 * Handler AJAX: obtener analytics detallados de un pool.
 *
 * @since 2.5.3 - Fase 5: Pool Hub Dashboard
 */
function eipsi_ajax_get_pool_analytics() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Sin permisos.', 'eipsi-forms')), 403);
    }

    $pool_id = isset($_GET['pool_id']) ? absint($_GET['pool_id']) : 0;

    if (!$pool_id) {
        wp_send_json_error(array('message' => __('ID de pool inválido.', 'eipsi-forms')), 400);
    }

    global $wpdb;
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';
    $participants_table = $wpdb->prefix . 'survey_participants';
    $studies_table = $wpdb->prefix . 'survey_studies';

    $pool = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$pools_table} WHERE id = %d",
        $pool_id
    ), ARRAY_A);

    if (!$pool) {
        wp_send_json_error(array('message' => __('Pool no encontrado.', 'eipsi-forms')), 404);
    }

    $studies = json_decode($pool['studies'] ?? '[]', true);
    $probabilities = json_decode($pool['probabilities'] ?? '[]', true);

    $total_assignments = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d",
        $pool_id
    )));

    $completed = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d AND completed = 1",
        $pool_id
    )));

    $dropouts = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$assignments_table}
         WHERE pool_id = %d AND completed = 0
         AND last_access < DATE_SUB(NOW(), INTERVAL 7 DAY)",
        $pool_id
    )));

    $completion_rate = $total_assignments > 0 ? round(($completed / $total_assignments) * 100, 1) : 0;
    $dropout_rate = $total_assignments > 0 ? round(($dropouts / $total_assignments) * 100, 1) : 0;

    $study_breakdown = array();
    $total_deviation = 0;

    foreach ($studies as $index => $study_id) {
        $expected = isset($probabilities[$index]) ? floatval($probabilities[$index]) : 0;
        $study_name = $wpdb->get_var($wpdb->prepare(
            "SELECT study_name FROM {$studies_table} WHERE id = %d",
            $study_id
        ));

        $assigned = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d AND study_id = %d",
            $pool_id, $study_id
        )));

        $study_completed = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d AND study_id = %d AND completed = 1",
            $pool_id, $study_id
        )));

        $in_progress = $assigned - $study_completed;
        $actual_pct = $total_assignments > 0 ? round(($assigned / $total_assignments) * 100, 1) : 0;
        $delta = round($actual_pct - $expected, 1);
        $total_deviation += abs($delta);
        $study_completion_rate = $assigned > 0 ? round(($study_completed / $assigned) * 100, 1) : 0;

        $study_breakdown[] = array(
            'study_id' => $study_id,
            'study_name' => $study_name,
            'assigned' => $assigned,
            'expected_pct' => $expected,
            'actual_pct' => $actual_pct,
            'delta' => $delta,
            'completed' => $study_completed,
            'in_progress' => $in_progress,
            'completion_rate' => $study_completion_rate
        );
    }

    $balance_score = count($studies) > 0 ? round(100 - ($total_deviation / count($studies)), 1) : 100;

    $recent_activity = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, p.email, s.study_name
         FROM {$assignments_table} a
         LEFT JOIN {$participants_table} p ON a.participant_id = p.participant_id
         LEFT JOIN {$studies_table} s ON a.study_id = s.id
         WHERE a.pool_id = %d
         ORDER BY a.assigned_at DESC
         LIMIT 10",
        $pool_id
    ), ARRAY_A);

    $activity_formatted = array();
    foreach ($recent_activity as $item) {
        $activity_formatted[] = array(
            'participant_email' => $item['email'] ? substr($item['email'], 0, strpos($item['email'], '@') + 1) . '***' : '***',
            'study_name' => $item['study_name'],
            'assigned_at' => mysql2date('d/m/Y H:i', $item['assigned_at']),
            'status' => $item['completed'] ? 'completado' : 'asignado'
        );
    }

    wp_send_json_success(array(
        'pool' => array(
            'id' => $pool_id,
            'name' => $pool['pool_name'],
            'status' => $pool['status']
        ),
        'metrics' => array(
            'total_assignments' => $total_assignments,
            'completion_rate' => $completion_rate,
            'balance_score' => $balance_score,
            'dropout_rate' => $dropout_rate
        ),
        'study_breakdown' => $study_breakdown,
        'recent_activity' => $activity_formatted
    ));
}
add_action('wp_ajax_eipsi_get_pool_analytics', 'eipsi_ajax_get_pool_analytics');

/**
 * Handler AJAX: exportar asignaciones de pool a CSV.
 *
 * @since 2.5.3 - Fase 5: Pool Hub Dashboard
 */
function eipsi_ajax_export_pool_assignments() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_die(__('Sin permisos.', 'eipsi-forms'));
    }

    $pool_id = isset($_GET['pool_id']) ? absint($_GET['pool_id']) : 0;

    if (!$pool_id) {
        wp_die(__('ID de pool inválido.', 'eipsi-forms'));
    }

    global $wpdb;
    $assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';
    $participants_table = $wpdb->prefix . 'survey_participants';
    $studies_table = $wpdb->prefix . 'survey_studies';

    $pool_name = $wpdb->get_var($wpdb->prepare(
        "SELECT pool_name FROM {$wpdb->prefix}eipsi_longitudinal_pools WHERE id = %d",
        $pool_id
    ));

    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, p.email, p.name as participant_name, s.study_name
         FROM {$assignments_table} a
         LEFT JOIN {$participants_table} p ON a.participant_id = p.participant_id
         LEFT JOIN {$studies_table} s ON a.study_id = s.id
         WHERE a.pool_id = %d
         ORDER BY a.assigned_at DESC",
        $pool_id
    ), ARRAY_A);

    $filename = sanitize_file_name('pool-' . $pool_name . '-assignments-' . date('Y-m-d') . '.csv');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, array(
        'participant_id', 'email', 'participant_name', 'study_id', 'study_name',
        'assigned_at', 'last_access', 'access_count', 'completed', 'completed_at'
    ));

    foreach ($assignments as $row) {
        fputcsv($output, array(
            $row['participant_id'],
            $row['email'],
            $row['participant_name'],
            $row['study_id'],
            $row['study_name'],
            $row['assigned_at'],
            $row['last_access'],
            $row['access_count'],
            $row['completed'] ? '1' : '0',
            $row['completed_at']
        ));
    }

    fclose($output);
    exit;
}
add_action('wp_ajax_eipsi_export_pool_assignments', 'eipsi_ajax_export_pool_assignments');
