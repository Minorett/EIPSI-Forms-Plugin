<?php
/**
 * EIPSI Randomization Shortcode Handler
 * 
 * Procesa el shortcode [eipsi_randomization id="xyz"]
 * generado por el bloque de aleatorización.
 * 
 * Features:
 * - Carga configuración de aleatorización desde post_meta
 * - Determina asignación basada en email/IP/seed
 * - Respeta asignaciones manuales
 * - Renderiza el formulario asignado
 * - Trackea asignaciones en DB
 * 
 * @package EIPSI_Forms
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode: [eipsi_randomization id="rand_xyz"]
 * 
 * @param array $atts Atributos del shortcode
 * @return string HTML output
 */
function eipsi_randomization_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => '', // Randomization ID del bloque
		),
		$atts,
		'eipsi_randomization'
	);

	$randomization_id = sanitize_text_field( $atts['id'] );

	if ( empty( $randomization_id ) ) {
		return eipsi_randomization_error_notice(
			__( '⚠️ Error: No se especificó ID de aleatorización.', 'eipsi-forms' )
		);
	}

	// Buscar el post que contiene este randomization_id
	$config_post = eipsi_get_randomization_config_post( $randomization_id );

	if ( ! $config_post ) {
		return eipsi_randomization_error_notice(
			sprintf(
				__( '⚠️ Error: No se encontró configuración para ID "%s".', 'eipsi-forms' ),
				esc_html( $randomization_id )
			)
		);
	}

	// Cargar configuración del bloque
	$config = eipsi_extract_randomization_config( $config_post->ID, $randomization_id );

	if ( ! $config || empty( $config['formularios'] ) ) {
		return eipsi_randomization_error_notice(
			__( 'ℹ️ Esta configuración de aleatorización no tiene formularios asignados.', 'eipsi-forms' )
		);
	}

	if ( count( $config['formularios'] ) < 2 ) {
		return eipsi_randomization_error_notice(
			__( 'ℹ️ La aleatorización requiere al menos 2 formularios configurados.', 'eipsi-forms' )
		);
	}

	// Determinar el identificador del participante
	$participant_identifier = eipsi_get_participant_identifier();

	// Verificar asignaciones manuales primero
	$assigned_form_id = eipsi_check_manual_assignment( $config, $participant_identifier );

	// Si no hay asignación manual, calcular aleatoriamente
	if ( ! $assigned_form_id ) {
		$assigned_form_id = eipsi_calculate_random_assignment( $config, $participant_identifier );
	}

	// Trackear la asignación en DB
	eipsi_track_randomization_assignment( $randomization_id, $participant_identifier, $assigned_form_id );

	// Renderizar el formulario asignado
	ob_start();
	?>
	<div class="eipsi-randomization-container" 
	     data-randomization-id="<?php echo esc_attr( $randomization_id ); ?>"
	     data-assigned-form="<?php echo esc_attr( $assigned_form_id ); ?>">
		
		<?php if ( ! empty( $config['showInstructions'] ) ) : ?>
		<div class="randomization-notice" style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
			<p style="margin: 0; color: #0d47a1; font-weight: 500;">
				ℹ️ <?php esc_html_e( 'Este estudio utiliza aleatorización: cada participante recibe un formulario asignado aleatoriamente.', 'eipsi-forms' ); ?>
			</p>
			<p style="margin: 0.5rem 0 0 0; color: #1565c0; font-size: 0.9rem;">
				<?php esc_html_e( 'Su asignación es persistente. En futuras sesiones recibirá el mismo formulario.', 'eipsi-forms' ); ?>
			</p>
		</div>
		<?php endif; ?>

		<?php
		// Renderizar el formulario usando el template de EIPSI Forms
		if ( function_exists( 'eipsi_render_form_template' ) ) {
			echo eipsi_render_form_template( $assigned_form_id );
		} else {
			// Fallback: usar shortcode estándar
			echo do_shortcode( '[eipsi_form id="' . $assigned_form_id . '"]' );
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'eipsi_randomization', 'eipsi_randomization_shortcode' );

/**
 * Buscar el post que contiene la configuración de aleatorización
 * 
 * @param string $randomization_id ID de aleatorización
 * @return WP_Post|null
 */
function eipsi_get_randomization_config_post( $randomization_id ) {
	// Buscar en posts/páginas que contengan bloques de aleatorización
	$args = array(
		'post_type'      => array( 'post', 'page' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		's'              => $randomization_id, // Buscar en contenido
	);

	$query = new WP_Query( $args );

	if ( ! $query->have_posts() ) {
		return null;
	}

	// Buscar el post que contenga el bloque con este randomizationId
	foreach ( $query->posts as $post ) {
		$blocks = parse_blocks( $post->post_content );
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === 'eipsi/randomization' &&
			     isset( $block['attrs']['randomizationId'] ) &&
			     $block['attrs']['randomizationId'] === $randomization_id ) {
				return $post;
			}
		}
	}

	return null;
}

/**
 * Extraer configuración de aleatorización del post
 * 
 * @param int    $post_id Post ID
 * @param string $randomization_id Randomization ID
 * @return array|null
 */
function eipsi_extract_randomization_config( $post_id, $randomization_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return null;
	}

	$blocks = parse_blocks( $post->post_content );

	foreach ( $blocks as $block ) {
		if ( $block['blockName'] === 'eipsi/randomization' &&
		     isset( $block['attrs']['randomizationId'] ) &&
		     $block['attrs']['randomizationId'] === $randomization_id ) {
			return $block['attrs'];
		}
	}

	return null;
}

/**
 * Obtener identificador del participante
 * 
 * Prioridad:
 * 1. Email desde URL param (?email=)
 * 2. Email desde cookie/session
 * 3. IP address como fallback
 * 
 * @return string
 */
function eipsi_get_participant_identifier() {
	// 1. Desde URL param
	if ( isset( $_GET['email'] ) && is_email( $_GET['email'] ) ) {
		return sanitize_email( $_GET['email'] );
	}

	// 2. Desde cookie/session
	if ( isset( $_COOKIE['eipsi_participant_email'] ) && is_email( $_COOKIE['eipsi_participant_email'] ) ) {
		return sanitize_email( $_COOKIE['eipsi_participant_email'] );
	}

	// 3. Fallback a IP
	$ip = eipsi_get_client_ip();
	return 'ip_' . $ip;
}

/**
 * Obtener IP del cliente
 * 
 * @return string
 */
function eipsi_get_client_ip() {
	$ip = '';

	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Verificar si hay asignación manual para este participante
 * 
 * @param array  $config Configuración de aleatorización
 * @param string $participant_identifier Identificador del participante
 * @return int|null Post ID del formulario asignado manualmente, o null
 */
function eipsi_check_manual_assignment( $config, $participant_identifier ) {
	if ( empty( $config['manualAssignments'] ) || ! is_array( $config['manualAssignments'] ) ) {
		return null;
	}

	// Limpiar identificador (remover "ip_" prefix si existe)
	$clean_identifier = str_replace( 'ip_', '', strtolower( $participant_identifier ) );

	foreach ( $config['manualAssignments'] as $assignment ) {
		$assigned_email = strtolower( trim( $assignment['email'] ) );

		if ( $assigned_email === $clean_identifier ) {
			return intval( $assignment['formId'] );
		}
	}

	return null;
}

/**
 * Calcular asignación aleatoria basada en probabilidades
 * 
 * @param array  $config Configuración de aleatorización
 * @param string $participant_identifier Identificador del participante
 * @return int Post ID del formulario asignado
 */
function eipsi_calculate_random_assignment( $config, $participant_identifier ) {
	$formularios = $config['formularios'];
	$method      = isset( $config['method'] ) ? $config['method'] : 'seeded';

	// Si es método seeded, usar hash del identificador como seed
	if ( $method === 'seeded' ) {
		$seed = crc32( $participant_identifier . $config['randomizationId'] );
		mt_srand( $seed );
	}

	// Crear array de probabilidades acumuladas
	$cumulative_probabilities = array();
	$cumulative               = 0;

	foreach ( $formularios as $form ) {
		$cumulative                 += $form['porcentaje'];
		$cumulative_probabilities[] = array(
			'postId'     => $form['postId'],
			'cumulative' => $cumulative,
		);
	}

	// Generar número aleatorio entre 0-100
	$random = mt_rand( 0, 100 );

	// Encontrar el formulario correspondiente
	foreach ( $cumulative_probabilities as $prob ) {
		if ( $random <= $prob['cumulative'] ) {
			// Resetear seed si era seeded
			if ( $method === 'seeded' ) {
				mt_srand();
			}
			return intval( $prob['postId'] );
		}
	}

	// Fallback (no debería llegar aquí)
	if ( $method === 'seeded' ) {
		mt_srand();
	}
	return intval( $formularios[0]['postId'] );
}

/**
 * Trackear asignación en la base de datos
 * 
 * @param string $randomization_id ID de aleatorización
 * @param string $participant_identifier Identificador del participante
 * @param int    $assigned_form_id Post ID del formulario asignado
 */
function eipsi_track_randomization_assignment( $randomization_id, $participant_identifier, $assigned_form_id ) {
	global $wpdb;

	// Tabla para trackear asignaciones
	$table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

	// Verificar si ya existe una asignación
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$existing = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} 
			WHERE randomization_id = %s 
			AND participant_identifier = %s",
			$randomization_id,
			$participant_identifier
		)
	);

	if ( $existing ) {
		// Actualizar timestamp de último acceso
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_name,
			array(
				'last_access'  => current_time( 'mysql' ),
				'access_count' => $existing->access_count + 1,
			),
			array(
				'id' => $existing->id,
			),
			array( '%s', '%d' ),
			array( '%d' )
		);
	} else {
		// Crear nueva asignación
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'randomization_id'        => $randomization_id,
				'participant_identifier'  => $participant_identifier,
				'assigned_form_id'        => $assigned_form_id,
				'assigned_at'             => current_time( 'mysql' ),
				'last_access'             => current_time( 'mysql' ),
				'access_count'            => 1,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d' )
		);
	}
}

/**
 * Crear tabla de tracking de asignaciones
 */
function eipsi_create_randomization_assignments_table() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'eipsi_randomization_assignments';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		randomization_id VARCHAR(255) NOT NULL,
		participant_identifier VARCHAR(255) NOT NULL,
		assigned_form_id BIGINT(20) UNSIGNED NOT NULL,
		assigned_at DATETIME NOT NULL,
		last_access DATETIME NOT NULL,
		access_count INT(11) DEFAULT 1,
		PRIMARY KEY  (id),
		KEY randomization_id (randomization_id),
		KEY participant_identifier (participant_identifier),
		KEY assigned_form_id (assigned_form_id)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

// Crear tabla en activación del plugin
add_action( 'eipsi_forms_activation', 'eipsi_create_randomization_assignments_table' );

/**
 * Generar notice de error
 * 
 * @param string $message Mensaje de error
 * @return string HTML
 */
function eipsi_randomization_error_notice( $message ) {
	return sprintf(
		'<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
			<p style="margin: 0; color: #c62828; font-weight: 500;">%s</p>
		</div>',
		wp_kses_post( $message )
	);
}

/**
 * Hook para manejar query param ?eipsi_rand=xyz
 * Permite acceso directo sin necesidad de shortcode
 */
function eipsi_handle_randomization_query_param() {
	if ( ! isset( $_GET['eipsi_rand'] ) ) {
		return;
	}

	$randomization_id = sanitize_text_field( $_GET['eipsi_rand'] );

	// Buscar página que contenga este shortcode o bloque
	$config_post = eipsi_get_randomization_config_post( $randomization_id );

	if ( $config_post ) {
		// Redirigir a la página con el bloque
		wp_safe_redirect( get_permalink( $config_post->ID ) );
		exit;
	}

	// Si no se encuentra, mostrar error
	wp_die(
		eipsi_randomization_error_notice(
			sprintf(
				__( '⚠️ No se encontró configuración de aleatorización para ID: %s', 'eipsi-forms' ),
				esc_html( $randomization_id )
			)
		),
		__( 'Error de Aleatorización', 'eipsi-forms' ),
		array( 'response' => 404 )
	);
}

add_action( 'template_redirect', 'eipsi_handle_randomization_query_param' );
