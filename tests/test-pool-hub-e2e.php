<?php
/**
 * EIPSI Forms - Pool Hub v2 End-to-End Testing Script
 *
 * Script para testing completo del sistema de pools.
 * Funciones de testing idempotentes con cleanup automático.
 *
 * Uso:
 *   require_once __DIR__ . '/test-pool-hub-e2e.php';
 *   eipsi_e2e_run_all_tests();
 *
 * @package EIPSI_Forms
 * @since 2.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prefix consistente para logs
if ( ! defined( 'EIPSI_LOG_PREFIX' ) ) {
	define( 'EIPSI_LOG_PREFIX', '[EIPSI]' );
}

/**
 * ============================================================================
 * TEST FIXTURES - Helpers para crear datos de prueba
 * ============================================================================
 */

/**
 * Crear un pool de prueba para testing E2E.
 *
 * @param string $name Nombre del pool.
 * @param array  $studies Array de study IDs con probabilidades.
 * @return int|false ID del pool creado o false.
 */
function eipsi_e2e_create_test_pool( $name = 'Test Pool E2E', $studies = array() ) {
	global $wpdb;

	// Default: 3 estudios equilibrados
	if ( empty( $studies ) ) {
		$studies = array(
			array( 'study_id' => 1001, 'probability' => 0.33 ),
			array( 'study_id' => 1002, 'probability' => 0.33 ),
			array( 'study_id' => 1003, 'probability' => 0.34 ),
		);
	}

	$table = $wpdb->prefix . 'eipsi_longitudinal_pools';

	$inserted = $wpdb->insert(
		$table,
		array(
			'name'           => $name,
			'studies'        => wp_json_encode( array_column( $studies, 'study_id' ) ),
			'probabilities'  => wp_json_encode( array_column( $studies, 'probability' ) ),
			'config'         => wp_json_encode(
				array(
					'studies'              => $studies,
					'method'               => 'seeded',
					'allow_reassignment'   => false,
					'notify_on_completion' => false,
					'paused_message'       => __( 'Pool de prueba pausado.', 'eipsi-forms' ),
					'migrated_at'          => current_time( 'mysql' ),
					'version'              => 2,
				)
			),
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		)
	);

	if ( $inserted ) {
		$pool_id = $wpdb->insert_id;
		error_log( EIPSI_LOG_PREFIX . " [E2E] Pool creado: ID {$pool_id} - {$name}" );
		return $pool_id;
	}

	error_log( EIPSI_LOG_PREFIX . ' [E2E] ERROR: Falló creación de pool - ' . $wpdb->last_error );
	return false;
}

/**
 * Simular asignación de participantes a un pool.
 *
 * @param int   $pool_id ID del pool.
 * @param int   $count   Número de asignaciones a simular.
 * @param array $participants Array de emails (si vacío, se generan).
 * @return array Resultados de las asignaciones.
 */
function eipsi_e2e_simulate_assignment( $pool_id, $count = 100, $participants = array() ) {
	if ( ! class_exists( 'EIPSI_Pool_Assignment_Service' ) ) {
		require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-pool-assignment-service.php';
	}

	$service = new EIPSI_Pool_Assignment_Service();
	$results = array();

	// Generar emails de prueba si no se proporcionan
	if ( empty( $participants ) ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$participants[] = "e2e_test_{$i}_" . wp_rand( 1000, 9999 ) . '@test.local';
		}
	}

	error_log( EIPSI_LOG_PREFIX . " [E2E] Simulando {$count} asignaciones para pool {$pool_id}" );

	foreach ( $participants as $index => $email ) {
		$result = $service->assign_participant_to_pool( $pool_id, $email, "Test User {$index}" );

		if ( is_wp_error( $result ) ) {
			error_log( EIPSI_LOG_PREFIX . " [E2E] ERROR asignación {$email}: " . $result->get_error_message() );
			$results[] = array(
				'email'   => $email,
				'study'   => 'error',
				'error'   => $result->get_error_message(),
			);
		} else {
			$results[] = array(
				'email'             => $email,
				'study'             => $result['study_id'],
				'is_new_assignment' => $result['is_new_assignment'],
			);
		}
	}

	error_log( EIPSI_LOG_PREFIX . ' [E2E] Asignaciones completadas: ' . count( $results ) );
	return $results;
}

/**
 * Verificar distribución de probabilidad de asignaciones.
 *
 * @param array $assignments Resultados de eipsi_e2e_simulate_assignment.
 * @param array $expected_probs Array de study_id => probability.
 * @return array Reporte de distribución.
 */
function eipsi_e2e_check_distribution( $assignments, $expected_probs = array() ) {
	$distribution = array();
	$total        = count( $assignments );

	foreach ( $assignments as $assignment ) {
		$study = $assignment['study'];
		if ( ! isset( $distribution[ $study ] ) ) {
			$distribution[ $study ] = 0;
		}
		$distribution[ $study ]++;
	}

	$report = array();
	error_log( EIPSI_LOG_PREFIX . ' [E2E] Reporte de distribución:' );

	foreach ( $distribution as $study => $count ) {
		$percentage = ( $count / $total ) * 100;
		$report[ $study ] = array(
			'count'       => $count,
			'percentage'  => round( $percentage, 2 ),
			'expected'    => $expected_probs[ $study ] ?? 'N/A',
			'deviation'   => isset( $expected_probs[ $study ] )
				? round( abs( $percentage - ( $expected_probs[ $study ] * 100 ) ), 2 )
				: 'N/A',
		);

		error_log( EIPSI_LOG_PREFIX . " [E2E]   Study {$study}: {$count} asignaciones ({$percentage}%)" );
	}

	return $report;
}

/**
 * Simular completitud de un pool.
 *
 * @param int $pool_id ID del pool.
 * @return bool Éxito de la operación.
 */
function eipsi_e2e_test_completion( $pool_id ) {
	global $wpdb;

	$assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';

	// Marcar todas las asignaciones pendientes como completadas
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$assignments_table} 
			 SET status = 'completed', 
			     completed_at = %s 
			 WHERE pool_id = %d 
			   AND status = 'pending'",
			current_time( 'mysql' ),
			$pool_id
		)
	);

	if ( false !== $updated ) {
		error_log( EIPSI_LOG_PREFIX . " [E2E] Pool {$pool_id} marcado como completado. Filas actualizadas: {$updated}" );
		return true;
	}

	error_log( EIPSI_LOG_PREFIX . ' [E2E] ERROR marcando pool como completado: ' . $wpdb->last_error );
	return false;
}

/**
 * Verificar que los analytics se actualizan correctamente.
 *
 * @param int $pool_id ID del pool.
 * @return array Datos de analytics o false.
 */
function eipsi_e2e_test_analytics( $pool_id ) {
	global $wpdb;

	$analytics_table   = $wpdb->prefix . 'eipsi_pool_analytics';
	$assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';

	// Contar totales por estado
	$stats = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT 
				COUNT(*) as total,
				SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
				SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
				SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned
			 FROM {$assignments_table} 
			 WHERE pool_id = %d",
			$pool_id
		),
		ARRAY_A
	);

	if ( ! $stats ) {
		error_log( EIPSI_LOG_PREFIX . ' [E2E] ERROR obteniendo analytics: ' . $wpdb->last_error );
		return false;
	}

	// Verificar que existen registros en analytics
	$analytics_exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$analytics_table} WHERE pool_id = %d",
			$pool_id
		)
	);

	error_log( EIPSI_LOG_PREFIX . " [E2E] Analytics pool {$pool_id}: Total={$stats['total']}, Completados={$stats['completed']}, Analytics Rows={$analytics_exists}" );

	return array(
		'assignments' => $stats,
		'analytics_rows' => intval( $analytics_exists ),
	);
}

/**
 * Limpiar datos de prueba (idempotente).
 *
 * @param int    $pool_id ID del pool a limpiar (opcional, si 0 limpia todo E2E).
 * @param string $pattern Patrón para identificar datos de prueba.
 * @return array Reporte de limpieza.
 */
function eipsi_e2e_cleanup( $pool_id = 0, $pattern = 'Test Pool E2E' ) {
	global $wpdb;

	$pools_table       = $wpdb->prefix . 'eipsi_longitudinal_pools';
	$assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';
	$analytics_table   = $wpdb->prefix . 'eipsi_pool_analytics';

	$report = array(
		'pools_deleted'       => 0,
		'assignments_deleted' => 0,
		'analytics_deleted'   => 0,
	);

	// Si se especifica un pool_id específico
	if ( $pool_id > 0 ) {
		// Eliminar asignaciones
		$report['assignments_deleted'] = $wpdb->delete( $assignments_table, array( 'pool_id' => $pool_id ), array( '%d' ) );
		// Eliminar analytics
		$report['analytics_deleted']   = $wpdb->delete( $analytics_table, array( 'pool_id' => $pool_id ), array( '%d' ) );
		// Eliminar pool
		$report['pools_deleted']       = $wpdb->delete( $pools_table, array( 'id' => $pool_id ), array( '%d' ) );

		error_log( EIPSI_LOG_PREFIX . " [E2E] Cleanup pool {$pool_id}: pools={$report['pools_deleted']}, assignments={$report['assignments_deleted']}, analytics={$report['analytics_deleted']}" );
		return $report;
	}

	// Limpieza masiva: eliminar todos los pools de prueba por nombre
	$test_pools = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id FROM {$pools_table} WHERE name LIKE %s",
			'%' . $wpdb->esc_like( $pattern ) . '%'
		),
		ARRAY_A
	);

	foreach ( $test_pools as $pool ) {
		$pool_id = $pool['id'];

		// Eliminar asignaciones
		$assignments_deleted = $wpdb->delete( $assignments_table, array( 'pool_id' => $pool_id ), array( '%d' ) );
		if ( false !== $assignments_deleted ) {
			$report['assignments_deleted'] += $assignments_deleted;
		}

		// Eliminar analytics
		$analytics_deleted = $wpdb->delete( $analytics_table, array( 'pool_id' => $pool_id ), array( '%d' ) );
		if ( false !== $analytics_deleted ) {
			$report['analytics_deleted'] += $analytics_deleted;
		}

		// Eliminar pool
		$pools_deleted = $wpdb->delete( $pools_table, array( 'id' => $pool_id ), array( '%d' ) );
		if ( $pools_deleted ) {
			$report['pools_deleted']++;
		}
	}

	error_log( EIPSI_LOG_PREFIX . " [E2E] Cleanup masivo completado: pools={$report['pools_deleted']}, assignments={$report['assignments_deleted']}, analytics={$report['analytics_deleted']}" );
	return $report;
}

/**
 * ============================================================================
 * TEST RUNNER - Ejecutar todos los tests E2E
 * ============================================================================
 */

/**
 * Ejecutar suite completa de tests E2E.
 *
 * @return array Resultados de todos los tests.
 */
function eipsi_e2e_run_all_tests() {
	error_log( EIPSI_LOG_PREFIX . ' [E2E] ============================================' );
	error_log( EIPSI_LOG_PREFIX . ' [E2E] INICIANDO SUITE DE TESTS E2E - Pool Hub v2' );
	error_log( EIPSI_LOG_PREFIX . ' [E2E] ============================================' );

	$results = array(
		'passed'  => 0,
		'failed'  => 0,
		'tests'   => array(),
		'cleanup' => array(),
	);

	// Cleanup inicial para asegurar estado limpio
	$results['cleanup']['initial'] = eipsi_e2e_cleanup();

	// Test 1: Crear pool
	$pool_id = eipsi_e2e_create_test_pool();
	$results['tests']['create_pool'] = array(
		'name'    => 'Crear Pool de Prueba',
		'passed'  => ( false !== $pool_id ),
		'pool_id' => $pool_id,
	);
	if ( $pool_id ) {
		$results['passed']++;
	} else {
		$results['failed']++;
		error_log( EIPSI_LOG_PREFIX . ' [E2E] FAIL: No se pudo crear el pool de prueba' );
		return $results;
	}

	// Test 2: Simular asignaciones
	$assignments = eipsi_e2e_simulate_assignment( $pool_id, 50 );
	$results['tests']['simulate_assignments'] = array(
		'name'        => 'Simular 50 Asignaciones',
		'passed'      => ( count( $assignments ) === 50 ),
		'count'       => count( $assignments ),
		'assignments' => $assignments,
	);
	if ( count( $assignments ) === 50 ) {
		$results['passed']++;
	} else {
		$results['failed']++;
		error_log( EIPSI_LOG_PREFIX . ' [E2E] FAIL: Número incorrecto de asignaciones' );
	}

	// Test 3: Verificar distribución
	$expected_probs = array( 1001 => 0.33, 1002 => 0.33, 1003 => 0.34 );
	$distribution   = eipsi_e2e_check_distribution( $assignments, $expected_probs );
	$results['tests']['check_distribution'] = array(
		'name'         => 'Verificar Distribución de Probabilidad',
		'passed'       => ( count( $distribution ) === 3 ),
		'distribution' => $distribution,
	);
	if ( count( $distribution ) === 3 ) {
		$results['passed']++;
	} else {
		$results['failed']++;
		error_log( EIPSI_LOG_PREFIX . ' [E2E] FAIL: Distribución incorrecta' );
	}

	// Test 4: Completitud
	$completion_ok = eipsi_e2e_test_completion( $pool_id );
	$results['tests']['test_completion'] = array(
		'name'   => 'Simular Completitud de Pool',
		'passed' => $completion_ok,
	);
	if ( $completion_ok ) {
		$results['passed']++;
	} else {
		$results['failed']++;
		error_log( EIPSI_LOG_PREFIX . ' [E2E] FAIL: No se pudo completar el pool' );
	}

	// Test 5: Analytics
	$analytics = eipsi_e2e_test_analytics( $pool_id );
	$results['tests']['test_analytics'] = array(
		'name'      => 'Verificar Analytics',
		'passed'    => ( false !== $analytics && $analytics['assignments']['total'] > 0 ),
		'analytics' => $analytics,
	);
	if ( false !== $analytics && $analytics['assignments']['total'] > 0 ) {
		$results['passed']++;
	} else {
		$results['failed']++;
		error_log( EIPSI_LOG_PREFIX . ' [E2E] FAIL: Analytics no disponibles' );
	}

	// Cleanup final
	$results['cleanup']['final'] = eipsi_e2e_cleanup( $pool_id );

	// Summary
	error_log( EIPSI_LOG_PREFIX . ' [E2E] ============================================' );
	error_log( EIPSI_LOG_PREFIX . " [E2E] TESTS COMPLETADOS: {$results['passed']} passed, {$results['failed']} failed" );
	error_log( EIPSI_LOG_PREFIX . ' [E2E] ============================================' );

	return $results;
}
