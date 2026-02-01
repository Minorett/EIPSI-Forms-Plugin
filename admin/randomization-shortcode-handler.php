<?php
/**
 * EIPSI Randomization Shortcode Handler - RCT System
 * 
 * Procesa el shortcode [eipsi_randomization id="xyz"]
 * con fingerprinting robusto y persistencia completa.
 * 
 * Features:
 * - Fingerprinting basado en canvas+device+browser
 * - Persistencia de asignaciones en DB
 * - Respeta asignaciones previas (F5 sin cambio)
 * - Asignaciones manuales (override ético)
 * - Método seeded (reproducible) o pure-random
 * - Tracking completo de accesos
 * 
 * @package EIPSI_Forms
 * @since 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [eipsi_randomization template="2400" config="abc123xyz"]
 * 
 * @param array $atts Atributos del shortcode
 * @return string HTML output
 */
function eipsi_randomization_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'template' => '', // Template ID del Form Library
            'config' => '',   // Config ID único
        ),
        $atts,
        'eipsi_randomization'
    );

    $template_id = intval( $atts['template'] );
    $config_id = sanitize_text_field( $atts['config'] );

    if ( empty( $template_id ) || empty( $config_id ) ) {
        return eipsi_randomization_error_notice(
            __( '⚠️ Error: Faltan parámetros requeridos (template y config).', 'eipsi-forms' )
        );
    }

    // PASO 1: Obtener configuración desde post meta (nuevo flujo)
    $config = eipsi_get_randomization_config_from_post_meta( $template_id, $config_id );
    
    if ( ! $config ) {
        // Fallback: buscar configuración legacy en blocks (backwards compatibility)
        $config_post = eipsi_get_randomization_config_post( $template_id );

        if ( ! $config_post ) {
            return eipsi_randomization_error_notice(
                sprintf(
                    __( '⚠️ Error: No se encontró configuración para template %d y config %s.', 'eipsi-forms' ),
                    $template_id,
                    esc_html( $config_id )
                )
            );
        }

        $config = eipsi_extract_randomization_config( $config_post->ID, $config_id );
    }

    // ✅ v1.3.19 - Obtener persistent_mode desde la configuración (default: true)
    // - true (default): Cada usuario asignado UNA VEZ, luego persistente
    // - false: Cada F5/reload = rotación cíclica (TESTING MODE)
    $persistent_mode = isset( $config['persistent_mode'] ) ? (bool) $config['persistent_mode'] : true;

    if ( ! $config || empty( $config['formularios'] ) ) {
        return eipsi_randomization_error_notice(
            __( 'ℹ️ Esta configuración de aleatorización no tiene formularios asignados.', 'eipsi-forms' )
        );
    }

    if ( count( $config['formularios'] ) < 1 ) {
        return eipsi_randomization_error_notice(
            __( 'ℹ️ La aleatorización requiere al menos 1 formulario configurado.', 'eipsi-forms' )
        );
    }

    // PASO 2: Obtener fingerprint del usuario (desde POST/AJAX o generar en servidor)
    $user_fingerprint = eipsi_get_user_fingerprint();

    // PASO 3: Buscar si ya existe una asignación previa para este usuario
    $existing_assignment = eipsi_get_existing_assignment( $config_id, $user_fingerprint );
    
    $is_new_assignment = false;

    if ( $existing_assignment && $persistent_mode ) {
        // YA FUE ASIGNADO Y MODO PERSISTENTE - usar la asignación existente (persistencia)
        $assigned_form_id = (int) $existing_assignment['assigned_form_id'];

        // Actualizar timestamp y contador de accesos
        eipsi_update_assignment_access( $existing_assignment['id'] );

        error_log( "[EIPSI RCT] Usuario existente: {$user_fingerprint} → Formulario: {$assigned_form_id} (PERSISTENTE)" );
    } elseif ( ! $persistent_mode ) {
        // MODO NO PERSISTENTE (F5 = ROTACIÓN CÍCLICA DEL "SOMBRERO")
        // Cada F5 avanza una posición en el array de formularios
        
        // Obtener formularios disponibles
        $formularios_ids = array();
        foreach ( $config['formularios'] as $form ) {
            if ( isset( $form['id'] ) && $form['id'] ) {
                $formularios_ids[] = intval( $form['id'] );
            }
        }
        
        if ( empty( $formularios_ids ) ) {
            return eipsi_randomization_error_notice(
                __( 'ℹ️ No hay formularios configurados para esta aleatorización.', 'eipsi-forms' )
            );
        }
        
        // Calcular la posición actual (rotación cíclica basada en sesión/browser)
        $rotation_key = 'eipsi_rotation_' . $config_id;
        $current_position = 0;
        
        // Intentar obtener posición desde cookie primero (para F5 correcto)
        if ( isset( $_COOKIE[ $rotation_key ] ) ) {
            $current_position = intval( $_COOKIE[ $rotation_key ] );
        }
        
        // Obtener el formulario para esta posición
        $total_forms = count( $formularios_ids );
        $form_index = $current_position % $total_forms;
        $assigned_form_id = $formularios_ids[ $form_index ];
        
        // Actualizar cookie para el próximo F5 (avanzar una posición)
        $next_position = ( $current_position + 1 ) % $total_forms;
        setcookie( $rotation_key, $next_position, time() + 86400, '/' ); // 24 horas
        
        error_log( "[EIPSI RCT] F5 Rotation: position={$current_position}/{$total_forms} → form={$assigned_form_id}" );
        
        // Si ya existe una asignación previa, actualizar para tracking
        if ( $existing_assignment ) {
            eipsi_update_assignment_full( $existing_assignment['id'], $assigned_form_id, false );
        } else {
            // Crear nueva asignación para tracking
            eipsi_create_assignment( $config_id, $user_fingerprint, $assigned_form_id, false );
        }
    } else {
        // NUEVA ASIGNACIÓN (primer acceso con persistent_mode=true)
        // Primero revisar asignaciones manuales desde DB (overrides)
        $assigned_form_id = eipsi_check_manual_override_db( $config_id, $user_fingerprint );

        if ( ! $assigned_form_id ) {
            // Calcular asignación aleatoria
            $assigned_form_id = eipsi_calculate_rct_assignment( $config, $user_fingerprint );
        }

        // Si ya existe una asignación pero estamos en modo test (persistent_mode=false), actualizamos en lugar de insertar
        if ( $existing_assignment ) {
            eipsi_update_assignment_full( $existing_assignment['id'], $assigned_form_id, $persistent_mode );
            error_log( "[EIPSI RCT] Usuario reasignado (test mode): {$user_fingerprint} → Formulario: {$assigned_form_id}" );
        } else {
            // Guardar nueva asignación en DB
            eipsi_create_assignment( $config_id, $user_fingerprint, $assigned_form_id, $persistent_mode );
            $is_new_assignment = true; // MARK NEW ASSIGNMENT
            
            if ( $persistent_mode ) {
                error_log( "[EIPSI RCT] Nuevo usuario: {$user_fingerprint} → Formulario: {$assigned_form_id} (PERSISTENTE)" );
            } else {
                error_log( "[EIPSI RCT] Nuevo usuario: {$user_fingerprint} → Formulario: {$assigned_form_id} (TEST MODE)" );
            }
        }
    }

    // Determine Group Name
    $group_name = get_the_title( $assigned_form_id );
    
    // Check if there is a custom label in config (optional optimization)
    if ( ! empty( $config['formularios'] ) ) {
        foreach ( $config['formularios'] as $form ) {
            if ( isset( $form['id'] ) && intval( $form['id'] ) === $assigned_form_id ) {
                 if ( ! empty( $form['label'] ) ) {
                     $group_name = $form['label'];
                 }
                 break;
            }
        }
    }

    // PASO 4: Renderizar el formulario asignado
    ob_start();
    ?>
    <div class="eipsi-randomization-container" 
         data-randomization-id="<?php echo esc_attr( $config_id ); ?>"
         data-assigned-form="<?php echo esc_attr( $assigned_form_id ); ?>"
         data-show-modal="<?php echo $is_new_assignment ? 'true' : 'false'; ?>">
        
        <?php 
        // 1. MODAL (Only if new assignment)
        if ( $is_new_assignment ) : ?>
            <div class="eipsi-rct-modal-overlay">
                <div class="eipsi-rct-modal">
                    <h3><?php esc_html_e( 'Estudio de Investigación', 'eipsi-forms' ); ?></h3>
                    <p>
                        <?php 
                        printf( 
                            esc_html__( 'Te hemos asignado al grupo: %s. Por favor completa el siguiente formulario.', 'eipsi-forms' ), 
                            '<strong>' . esc_html( $group_name ) . '</strong>'
                        ); 
                        ?>
                    </p>
                    <button class="eipsi-rct-modal-btn"><?php esc_html_e( 'Comenzar', 'eipsi-forms' ); ?></button>
                </div>
            </div>
        <?php endif; ?>

        <?php 
        // 2. BADGE (Always visible)
        ?>
        <div class="eipsi-rct-badge">
            <span class="icon">🔖</span>
            <span class="text">
                <?php printf( esc_html__( 'Grupo: %s', 'eipsi-forms' ), esc_html( $group_name ) ); ?>
            </span>
        </div>

        <?php if ( ! empty( $config['showInstructions'] ) ) : ?>
        <div class="randomization-notice" style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
            <p style="margin: 0; color: #0d47a1; font-weight: 500;">
                ℹ️ <?php esc_html_e( 'Este estudio utiliza aleatorización: cada participante recibe un formulario asignado aleatoriamente.', 'eipsi-forms' ); ?>
            </p>
            <p style="margin: 0.5rem 0 0 0; color: #1565c0; font-size: 0.9rem;">
                <?php
                if ( $persistent_mode ) {
                    esc_html_e( '✅ Su asignación es PERSISTENTE. En futuras sesiones recibirá el mismo formulario.', 'eipsi-forms' );
                } else {
                    esc_html_e( '⚠️ MODO TEST: Su asignación cambia en cada visita (para validar el funcionamiento).', 'eipsi-forms' );
                }
                ?>
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
 * Verificar si existe un override manual en DB para un usuario
 *
 * @param string $randomization_id ID de la configuración
 * @param string $user_fingerprint Fingerprint del usuario
 * @return int|null Form ID asignado manualmente o null
 */
function eipsi_check_manual_override_db( $randomization_id, $user_fingerprint ) {
    global $wpdb;

    $overrides_table = $wpdb->prefix . 'eipsi_manual_overrides';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $override = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT assigned_form_id, expires_at, status
            FROM {$overrides_table}
            WHERE randomization_id = %s
            AND user_fingerprint = %s
            AND status = 'active'
            LIMIT 1",
            $randomization_id,
            $user_fingerprint
        )
    );

    if ( $override ) {
        // Verificar si NO ha expirado
        if ( ! $override->expires_at || strtotime( $override->expires_at ) > time() ) {
            error_log( "[EIPSI Manual Override] Override encontrado para {$user_fingerprint} → Form {$override->assigned_form_id}" );
            return intval( $override->assigned_form_id );
        } else {
            error_log( "[EIPSI Manual Override] Override expirado para {$user_fingerprint}" );
            // Marcar como expired (background task)
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update(
                $overrides_table,
                array( 'status' => 'expired' ),
                array(
                    'randomization_id' => $randomization_id,
                    'user_fingerprint'   => $user_fingerprint
                ),
                array( '%s' ),
                array( '%s', '%s' )
            );
        }
    }

    return null;
}

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
 * Obtener fingerprint del usuario
 * 
 * Prioridad:
 * 1. Fingerprint desde POST (enviado por JS)
 * 2. Fingerprint desde cookie
 * 3. Email desde URL param (?email=) - para asignaciones manuales
 * 4. Generar fingerprint en servidor (fallback débil)
 * 
 * @return string
 */
function eipsi_get_user_fingerprint() {
    // 1. Desde POST (enviado por el JS eipsi-fingerprint.js)
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( isset( $_POST['eipsi_user_fingerprint'] ) ) {
        $fingerprint = sanitize_text_field( wp_unslash( $_POST['eipsi_user_fingerprint'] ) );
        if ( strpos( $fingerprint, 'fp_' ) === 0 ) {
            return $fingerprint;
        }
    }

    // 2. Desde cookie (si el usuario ya visitó antes)
    if ( isset( $_COOKIE['eipsi_fingerprint'] ) ) {
        $fingerprint = sanitize_text_field( wp_unslash( $_COOKIE['eipsi_fingerprint'] ) );
        if ( strpos( $fingerprint, 'fp_' ) === 0 ) {
            return $fingerprint;
        }
    }

    // 3. Email desde URL param (para asignaciones manuales)
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['email'] ) && is_email( $_GET['email'] ) ) {
        return 'email_' . md5( sanitize_email( wp_unslash( $_GET['email'] ) ) );
    }

    // 4. Fallback: generar fingerprint en servidor (menos confiable)
    return eipsi_generate_server_fingerprint();
}

/**
 * Generar fingerprint en el servidor (fallback)
 * Combina User Agent + IP + Accept-Language
 * 
 * @return string
 */
function eipsi_generate_server_fingerprint() {
    $components = array();

    // User Agent
    if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $components[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
    }

    // IP Address
    $components[] = eipsi_get_client_ip();

    // Accept Language
    if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
        $components[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );
    }

    // Accept Encoding
    if ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
        $components[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) );
    }

    $combined     = implode( '|', $components );
    $hash         = hash( 'sha256', $combined );
    $fingerprint  = 'fp_server_' . substr( $hash, 0, 24 );

    error_log( '[EIPSI RCT] Fingerprint generado en servidor (fallback): ' . $fingerprint );

    return $fingerprint;
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
 * Calcular asignación aleatoria basada en probabilidades
 * 
 * @param array  $config Configuración de aleatorización
 * @param string $user_fingerprint Fingerprint del usuario
 * @return int Post ID del formulario asignado
 */
function eipsi_calculate_rct_assignment( $config, $user_fingerprint ) {
    $formularios = $config['formularios'];
    $probabilidades = isset( $config['probabilidades'] ) ? $config['probabilidades'] : array();
    $method      = isset( $config['method'] ) ? $config['method'] : 'seeded';

    // Si es método seeded, usar hash del fingerprint como seed
    if ( $method === 'seeded' ) {
        $seed = crc32( $user_fingerprint . $config['config_id'] );
        mt_srand( $seed );
        error_log( "[EIPSI RCT] Método seeded - seed: {$seed}" );
    }

    // Crear array de probabilidades acumuladas
    $cumulative_probabilities = array();
    $cumulative               = 0;

    foreach ( $formularios as $form ) {
        $form_id = isset( $form['id'] ) ? $form['id'] : 0;
        $porcentaje = isset( $probabilidades[ $form_id ] ) ? intval( $probabilidades[ $form_id ] ) : 0;
        
        $cumulative += $porcentaje;
        $cumulative_probabilities[] = array(
            'postId'     => $form_id,
            'cumulative' => $cumulative,
        );
    }

    // Generar número aleatorio entre 0-100
    $random = mt_rand( 0, 100 );

    error_log( "[EIPSI RCT] Random generado: {$random} de 100" );

    // Encontrar el formulario correspondiente
    foreach ( $cumulative_probabilities as $prob ) {
        if ( $random <= $prob['cumulative'] ) {
            // Resetear seed si era seeded
            if ( $method === 'seeded' ) {
                mt_srand();
            }
            error_log( "[EIPSI RCT] Formulario asignado: {$prob['postId']}" );
            return intval( $prob['postId'] );
        }
    }

    // Fallback (no debería llegar aquí)
    if ( $method === 'seeded' ) {
        mt_srand();
    }
    error_log( '[EIPSI RCT] Fallback: usando primer formulario' );
    $first_form = reset( $formularios );
    return intval( isset( $first_form['id'] ) ? $first_form['id'] : 0 );
}

/**
 * Función legacy removida - usar versión actualizada en línea 523
 * Con el nuevo esquema: template_id + config_id
 */

/**
 * Función para actualizar una asignación existente (usada en test mode)
 * 
 * @param int $assignment_id ID de la asignación en la DB
 * @param int $assigned_form_id Nuevo formulario asignado
 * @param bool $persistent_mode Nuevo estado del modo persistente
 * @return bool True si se actualizó correctamente
 */
function eipsi_update_assignment_full( $assignment_id, $assigned_form_id, $persistent_mode = true ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    $data = array(
        'assigned_form_id' => $assigned_form_id,
        'last_access' => current_time( 'mysql' ),
        'access_count' => 1, // Reiniciamos contador en reasignación
    );
    
    $format = array( '%d', '%s', '%d' );

    // Intentar agregar persistent_mode si la columna existe
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $column_check = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'persistent_mode'" );
    
    if ( ! empty( $column_check ) ) {
        $data['persistent_mode'] = $persistent_mode ? 1 : 0;
        $format[] = '%d';
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = $wpdb->update( 
        $table_name, 
        $data, 
        array( 'id' => $assignment_id ), 
        $format, 
        array( '%d' ) 
    );

    return $result !== false;
}

/**
 * Actualizar timestamp y contador de accesos
 * 
 * @param int $assignment_id ID de la asignación
 * @return bool True si se actualizó correctamente
 */
function eipsi_update_assignment_access( $assignment_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table_name} 
            SET last_access = %s,
            access_count = access_count + 1
            WHERE id = %d",
            current_time( 'mysql' ),
            $assignment_id
        )
    );

    return $result !== false;
}

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
 * 
 * @since 1.3.4 - Actualizado para nuevo flujo
 */
function eipsi_handle_randomization_query_param() {
    if ( ! isset( $_GET['eipsi_rand'] ) ) {
        return;
    }

    $randomization_id = sanitize_text_field( $_GET['eipsi_rand'] );

    // Si el parámetro incluye template y config (nuevo formato)
    if ( strpos( $randomization_id, '_' ) !== false ) {
        // Formato: template_configID (ej: 2400_config_123456)
        $parts = explode( '_', $randomization_id, 2 );
        if ( count( $parts ) === 2 ) {
            $template_id = intval( $parts[0] );
            $config_id = $parts[1];
            
            $config = eipsi_get_randomization_config_from_post_meta( $template_id, $config_id );
            if ( $config ) {
                // Redirigir a la página con el shortcode correspondiente
                $shortcode = sprintf( '[eipsi_randomization template="%d" config="%s"]', $template_id, $config_id );
                wp_safe_redirect( add_query_arg( 'eipsi_rand_shortcode', base64_encode( $shortcode ), home_url() ) );
                exit;
            }
        }
    }

    // Fallback: buscar página que contenga este shortcode o bloque (legacy)
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

/**
 * Función para obtener asignación existente (actualizada para nuevo flujo)
 * 
 * @param string $config_id Config ID (randomization_id)
 * @param string $user_fingerprint Fingerprint del usuario
 * @return array|null Array con datos de asignación o null
 */
function eipsi_get_existing_assignment( $config_id, $user_fingerprint ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $assignment = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE randomization_id = %s 
            AND config_id = %s 
            AND user_fingerprint = %s
            LIMIT 1",
            $config_id,
            $config_id,
            $user_fingerprint
        ),
        ARRAY_A
    );

    return $assignment;
}

/**
 * Función para crear nueva asignación (actualizada para nuevo flujo)
 * 
 * @param string $config_id Config ID (randomization_id)
 * @param string $user_fingerprint Fingerprint del usuario
 * @param int $assigned_form_id Post ID del formulario asignado
 * @param bool $persistent_mode Modo persistente (true/false)
 * @return bool True si se creó correctamente
 */
function eipsi_create_assignment( $config_id, $user_fingerprint, $assigned_form_id, $persistent_mode = true ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // Intentar insertar con la columna persistent_mode (si existe)
    // Si falla, la función autofix la creará automáticamente en la próxima ejecución
    $data = array(
        'randomization_id' => $config_id,
        'config_id' => $config_id,
        'user_fingerprint' => $user_fingerprint,
        'assigned_form_id' => $assigned_form_id,
        'assigned_at' => current_time( 'mysql' ),
        'last_access' => current_time( 'mysql' ),
        'access_count' => 1,
    );

    $format = array( '%s', '%s', '%s', '%d', '%s', '%s', '%d' );

    // Intentar agregar persistent_mode si la columna existe
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $column_check = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'persistent_mode'" );
    
    if ( ! empty( $column_check ) ) {
        $data['persistent_mode'] = $persistent_mode ? 1 : 0;
        $format[] = '%d';
        error_log( "[EIPSI RCT] Asignación creada con persistent_mode={$persistent_mode}" );
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = $wpdb->insert( $table_name, $data, $format );

    if ( $result === false ) {
        error_log( "[EIPSI RCT] ERROR al crear asignación: {$wpdb->last_error}" );
        return false;
    }

    return true;
}

/**
 * Función para cerrar sesión de aleatorización (persistent_mode=OFF)
 * 
 * Elimina la asignación del usuario de la tabla y borra la cookie de rotación.
 * Esto permite que el próximo F5/reload asigne un nuevo formulario en la rotación cíclica.
 * 
 * @param string $config_id Config ID (randomization_id)
 * @param string $user_fingerprint Fingerprint del usuario
 * @return bool True si se cerró correctamente
 */
function eipsi_close_randomization_session( $config_id, $user_fingerprint ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // Eliminar asignación de la tabla
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = $wpdb->delete(
        $table_name,
        array(
            'randomization_id' => $config_id,
            'config_id' => $config_id,
            'user_fingerprint' => $user_fingerprint,
        ),
        array( '%s', '%s', '%s' )
    );

    // Eliminar cookie de rotación
    $rotation_key = 'eipsi_rotation_' . $config_id;
    if ( isset( $_COOKIE[ $rotation_key ] ) ) {
        setcookie( $rotation_key, '', time() - 3600, '/' );
        unset( $_COOKIE[ $rotation_key ] );
    }

    if ( $result !== false ) {
        error_log( "[EIPSI RCT] Sesión cerrada para fingerprint={$user_fingerprint} en config={$config_id}" );
        return true;
    } else {
        error_log( "[EIPSI RCT] ERROR al cerrar sesión: {$wpdb->last_error}" );
        return false;
    }
}

/* 
 * EIPSI Randomization Shortcode Handler - END OF FILE
 * Todos los comentarios están correctamente cerrados.
 * Última línea: 523
 */
