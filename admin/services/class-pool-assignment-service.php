<?php
/**
 * EIPSI_Pool_Assignment_Service
 *
 * Lógica de asignación aleatoria ponderada para pools longitudinales.
 * Cuando un participante se une a un pool, se le asigna un estudio
 * según las probabilidades configuradas, se crea el participante,
 * se registra la asignación y se genera un magic link.
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 2.1.0
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EIPSI_Pool_Assignment_Service {

    /**
     * Tabla principal de pools.
     *
     * @var string
     */
    private $pools_table;

    /**
     * Tabla de asignaciones de pools.
     *
     * @var string
     */
    private $assignments_table;

    /**
     * Constructor: inicializa nombres de tablas.
     */
    public function __construct() {
        global $wpdb;
        $this->pools_table       = $wpdb->prefix . 'eipsi_longitudinal_pools';
        $this->assignments_table = $wpdb->prefix . 'eipsi_longitudinal_pool_assignments';
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Asignar participante a un estudio del pool.
     *
     * Flujo:
     * 1. Valida que el pool exista y esté activo.
     * 2. Para método seeded: devuelve asignación existente si ya fue asignado.
     * 3. Selecciona estudio según probabilidades ponderadas.
     * 4. Crea o recupera registro de participante en ese estudio.
     * 5. Registra asignación en wp_eipsi_longitudinal_pool_assignments.
     * 6. Genera magic link para ese estudio específico.
     * 7. Envía email de bienvenida (si Email Service disponible).
     *
     * @param int         $pool_id           ID del pool.
     * @param string      $participant_email Email del participante.
     * @param string|null $participant_name  Nombre opcional.
     * @return array|WP_Error {
     *     success: bool,
     *     study_id: int,
     *     participant_id: int,
     *     magic_link_url: string,
     *     is_new_assignment: bool,
     *     study_name: string
     * }
     */
    public function assign_participant_to_pool( $pool_id, $participant_email, $participant_name = null ) {
        $pool_id           = absint( $pool_id );
        $participant_email = sanitize_email( $participant_email );

        // ------------------------------------------------------------------
        // 1. Validar email
        // ------------------------------------------------------------------
        if ( ! is_email( $participant_email ) ) {
            return new WP_Error(
                'invalid_email',
                __( 'El email proporcionado no es válido.', 'eipsi-forms' )
            );
        }

        // ------------------------------------------------------------------
        // 2. Validar pool
        // ------------------------------------------------------------------
        $pool = $this->get_pool( $pool_id );
        if ( ! $pool ) {
            return new WP_Error(
                'pool_not_found',
                __( 'El pool no existe.', 'eipsi-forms' )
            );
        }

        if ( 'active' !== $pool->status ) {
            return new WP_Error(
                'pool_inactive',
                __( 'El pool no está activo actualmente.', 'eipsi-forms' )
            );
        }

        $studies       = json_decode( $pool->studies, true );
        $probabilities = json_decode( $pool->probabilities, true );

        if ( empty( $studies ) || ! is_array( $studies ) ) {
            return new WP_Error(
                'pool_no_studies',
                __( 'El pool no tiene estudios configurados.', 'eipsi-forms' )
            );
        }

        if ( empty( $probabilities ) || ! is_array( $probabilities ) ) {
            return new WP_Error(
                'pool_no_probabilities',
                __( 'El pool no tiene probabilidades configuradas.', 'eipsi-forms' )
            );
        }

        // ------------------------------------------------------------------
        // 3. Método seeded: retornar asignación existente si corresponde
        // ------------------------------------------------------------------
        if ( 'seeded' === $pool->method ) {
            $existing = $this->get_existing_assignment( $pool_id, $participant_email );
            if ( $existing ) {
                $magic_link_url = $this->generate_magic_link_for_study(
                    $existing->assigned_study_id,
                    $existing->participant_id
                );

                if ( is_wp_error( $magic_link_url ) ) {
                    return $magic_link_url;
                }

                $study_name = $this->get_study_name( $existing->assigned_study_id );

                return array(
                    'success'           => true,
                    'study_id'          => (int) $existing->assigned_study_id,
                    'participant_id'    => (int) $existing->participant_id,
                    'magic_link_url'    => $magic_link_url,
                    'is_new_assignment' => false,
                    'study_name'        => $study_name,
                    'pool_name'         => $pool->pool_name,
                );
            }
        }

        // ------------------------------------------------------------------
        // 4. Seleccionar estudio (seeded usa hash del email como semilla)
        // ------------------------------------------------------------------
        if ( 'seeded' === $pool->method ) {
            $seed = crc32( $participant_email );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_srand
            srand( $seed );
            $selected_study_id = $this->select_study_weighted( $studies, $probabilities );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_srand
            srand();
        } else {
            $selected_study_id = $this->select_study_weighted( $studies, $probabilities );
        }

        if ( ! $selected_study_id ) {
            return new WP_Error(
                'selection_failed',
                __( 'No se pudo seleccionar un estudio. Verifica que las probabilidades sean válidas.', 'eipsi-forms' )
            );
        }

        // ------------------------------------------------------------------
        // 5. Crear o recuperar participante en ese estudio
        // ------------------------------------------------------------------
        $participant_result = $this->create_or_get_participant(
            $selected_study_id,
            $participant_email,
            $participant_name
        );

        if ( is_wp_error( $participant_result ) ) {
            return $participant_result;
        }

        $participant_id = $participant_result['participant_id'];
        $is_new_participant = $participant_result['is_new'];

        // ------------------------------------------------------------------
        // 6. Registrar asignación en la tabla de asignaciones
        // ------------------------------------------------------------------
        $logged = $this->log_assignment( $pool_id, $participant_id, $selected_study_id );
        if ( ! $logged ) {
            // No bloqueamos el flujo por esto, solo lo registramos
            error_log( '[EIPSI PoolAssignment] Warning: no se pudo registrar la asignación en la tabla de log.' );
        }

        // ------------------------------------------------------------------
        // 7. Generar magic link para el estudio asignado
        // ------------------------------------------------------------------
        $magic_link_url = $this->generate_magic_link_for_study( $selected_study_id, $participant_id );
        if ( is_wp_error( $magic_link_url ) ) {
            return $magic_link_url;
        }

        // ------------------------------------------------------------------
        // 8. Enviar email de bienvenida (opcional, no bloquea)
        // ------------------------------------------------------------------
        if ( $is_new_participant && class_exists( 'EIPSI_Email_Service' ) ) {
            $this->send_welcome_notification(
                $selected_study_id,
                $participant_id,
                $magic_link_url,
                $pool->pool_name,
                $participant_name
            );
        }

        $study_name = $this->get_study_name( $selected_study_id );

        return array(
            'success'           => true,
            'study_id'          => (int) $selected_study_id,
            'participant_id'    => (int) $participant_id,
            'magic_link_url'    => $magic_link_url,
            'is_new_assignment' => true,
            'study_name'        => $study_name,
            'pool_name'         => $pool->pool_name,
        );
    }

    /**
     * Obtener asignación existente de un participante en un pool.
     *
     * Usado por el método seeded para garantizar idempotencia.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_email Email del participante.
     * @return object|null Fila de wp_eipsi_longitudinal_pool_assignments o null.
     */
    public function get_existing_assignment( $pool_id, $participant_email ) {
        global $wpdb;

        $participant_email = sanitize_email( $participant_email );

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT pa.*
                 FROM {$this->assignments_table} pa
                 INNER JOIN {$wpdb->prefix}survey_participants sp
                     ON sp.id = pa.participant_id
                 WHERE pa.pool_id = %d
                   AND sp.email = %s
                 ORDER BY pa.assigned_at DESC
                 LIMIT 1",
                $pool_id,
                $participant_email
            )
        );

        return $row ?: null;
    }

    /**
     * Obtener estadísticas del pool (conteo de asignaciones por estudio).
     *
     * @param int $pool_id ID del pool.
     * @return array Array con [study_id => count, ...] y total.
     */
    public function get_pool_stats( $pool_id ) {
        global $wpdb;

        $pool_id = absint( $pool_id );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT assigned_study_id, COUNT(*) as total
                 FROM {$this->assignments_table}
                 WHERE pool_id = %d
                 GROUP BY assigned_study_id",
                $pool_id
            ),
            ARRAY_A
        );

        $stats    = array();
        $grand_total = 0;

        foreach ( $rows as $row ) {
            $stats[ (int) $row['assigned_study_id'] ] = (int) $row['total'];
            $grand_total += (int) $row['total'];
        }

        return array(
            'by_study' => $stats,
            'total'    => $grand_total,
        );
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Selección ponderada de estudio.
     *
     * Algoritmo de "ruleta" con probabilidades acumuladas.
     * Ejemplo: A=40%, B=35%, C=25%
     *   rand=0..40  → A
     *   rand=40..75 → B
     *   rand=75..100→ C
     *
     * Nota: usa mt_rand() por ser más robusto que rand(), pero si se llamó
     * srand() antes (modo seeded), la secuencia ya está fijada.
     *
     * @param array $studies       Array de IDs de estudios.
     * @param array $probabilities Mapa [study_id => probability (float 0-100)].
     * @return int ID del estudio seleccionado, 0 si hay error.
     */
    private function select_study_weighted( $studies, $probabilities ) {
        // Normalizar probabilidades para cubrir edge cases (suma != 100)
        $total = 0;
        foreach ( $studies as $study_id ) {
            $total += isset( $probabilities[ $study_id ] ) ? (float) $probabilities[ $study_id ] : 0;
        }

        if ( $total <= 0 ) {
            error_log( '[EIPSI PoolAssignment] select_study_weighted: suma de probabilidades es 0.' );
            // Fallback: distribución uniforme
            $idx = array_rand( $studies );
            return (int) $studies[ $idx ];
        }

        // Número aleatorio entre 0.00 y total (soporta suma != 100)
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
        $rand       = mt_rand( 0, (int) ( $total * 100 ) ) / 100;
        $cumulative = 0;

        foreach ( $studies as $study_id ) {
            $probability = isset( $probabilities[ $study_id ] ) ? (float) $probabilities[ $study_id ] : 0;
            $cumulative += $probability;
            if ( $rand <= $cumulative ) {
                return (int) $study_id;
            }
        }

        // Fallback por redondeo: último estudio
        return (int) end( $studies );
    }

    /**
     * Registrar asignación en wp_eipsi_longitudinal_pool_assignments.
     *
     * @param int $pool_id        ID del pool.
     * @param int $participant_id ID del participante.
     * @param int $study_id       ID del estudio asignado.
     * @return bool True si se insertó correctamente.
     */
    private function log_assignment( $pool_id, $participant_id, $study_id ) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->assignments_table,
            array(
                'pool_id'           => absint( $pool_id ),
                'participant_id'    => absint( $participant_id ),
                'assigned_study_id' => absint( $study_id ),
                'assigned_at'       => current_time( 'mysql' ),
                'status'            => 'active',
            ),
            array( '%d', '%d', '%d', '%s', '%s' )
        );

        if ( false === $result ) {
            error_log( '[EIPSI PoolAssignment] log_assignment DB error: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Obtener datos del pool desde DB.
     *
     * @param int $pool_id ID del pool.
     * @return object|null Fila o null si no existe.
     */
    private function get_pool( $pool_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->pools_table} WHERE id = %d",
                absint( $pool_id )
            )
        );
    }

    /**
     * Crear o recuperar participante (sin password) para el estudio dado.
     *
     * Usa EIPSI_Participant_Service::create_or_get_for_magic_link si existe;
     * si no, implementa el flujo directamente.
     *
     * @param int         $study_id    ID del estudio (survey).
     * @param string      $email       Email del participante.
     * @param string|null $name        Nombre completo opcional.
     * @return array|WP_Error { participant_id, is_new } o WP_Error.
     */
    private function create_or_get_participant( $study_id, $email, $name = null ) {
        global $wpdb;

        $study_id = absint( $study_id );
        $email    = sanitize_email( $email );

        // Parsear nombre si se proporcionó
        $first_name = '';
        $last_name  = '';
        if ( ! empty( $name ) ) {
            $name_parts = explode( ' ', sanitize_text_field( $name ), 2 );
            $first_name = $name_parts[0];
            $last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';
        }

        // Usar método dedicado del Participant Service si está disponible
        if ( class_exists( 'EIPSI_Participant_Service' ) && method_exists( 'EIPSI_Participant_Service', 'create_or_get_for_magic_link' ) ) {
            $result = EIPSI_Participant_Service::create_or_get_for_magic_link( $study_id, $email );

            if ( ! $result['success'] ) {
                return new WP_Error(
                    'participant_creation_failed',
                    sprintf(
                        /* translators: %s: error code */
                        __( 'No se pudo crear el participante: %s', 'eipsi-forms' ),
                        $result['error']
                    )
                );
            }

            // Actualizar nombre si es nuevo participante y se proporcionó
            if ( $result['is_new'] && ( ! empty( $first_name ) || ! empty( $last_name ) ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'survey_participants',
                    array(
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                    ),
                    array( 'id' => $result['participant_id'] ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }

            return array(
                'participant_id' => $result['participant_id'],
                'is_new'         => $result['is_new'],
            );
        }

        // Fallback manual si el servicio no está disponible
        $table = $wpdb->prefix . 'survey_participants';

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE survey_id = %d AND email = %s",
                $study_id,
                $email
            )
        );

        if ( $existing ) {
            return array(
                'participant_id' => (int) $existing->id,
                'is_new'         => false,
            );
        }

        $temp_password = wp_generate_password( 32, true, true );
        $password_hash = wp_hash_password( $temp_password );

        $inserted = $wpdb->insert(
            $table,
            array(
                'survey_id'     => $study_id,
                'email'         => $email,
                'password_hash' => $password_hash,
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'created_at'    => current_time( 'mysql' ),
                'is_active'     => 1,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        if ( false === $inserted ) {
            error_log( '[EIPSI PoolAssignment] create_or_get_participant DB error: ' . $wpdb->last_error );
            return new WP_Error(
                'participant_db_error',
                __( 'Error de base de datos al crear el participante.', 'eipsi-forms' )
            );
        }

        return array(
            'participant_id' => (int) $wpdb->insert_id,
            'is_new'         => true,
        );
    }

    /**
     * Generar magic link URL para un estudio y participante.
     *
     * @param int $study_id       ID del estudio (survey).
     * @param int $participant_id ID del participante.
     * @return string|WP_Error URL con el token o WP_Error.
     */
    private function generate_magic_link_for_study( $study_id, $participant_id ) {
        if ( class_exists( 'EIPSI_Email_Service' ) && method_exists( 'EIPSI_Email_Service', 'generate_magic_link_url' ) ) {
            $url = EIPSI_Email_Service::generate_magic_link_url( $study_id, $participant_id );
            if ( $url ) {
                return $url;
            }
        }

        // Fallback directo a MagicLinksService
        if ( class_exists( 'EIPSI_MagicLinksService' ) ) {
            $token = EIPSI_MagicLinksService::generate_magic_link( $study_id, $participant_id );
            if ( $token ) {
                return add_query_arg( 'eipsi_magic', $token, site_url( '/' ) );
            }
        }

        return new WP_Error(
            'magic_link_failed',
            __( 'No se pudo generar el enlace de acceso. Por favor, contactá al investigador.', 'eipsi-forms' )
        );
    }

    /**
     * Enviar email de bienvenida al nuevo participante asignado.
     *
     * No bloquea el flujo principal si falla.
     *
     * @param int    $study_id       ID del estudio.
     * @param int    $participant_id ID del participante.
     * @param string $magic_link_url URL del magic link.
     * @param string $pool_name      Nombre del pool.
     * @param string $name           Nombre del participante.
     * @return void
     */
    private function send_welcome_notification( $study_id, $participant_id, $magic_link_url, $pool_name, $name = '' ) {
        try {
            if ( method_exists( 'EIPSI_Email_Service', 'send_welcome_email' ) ) {
                EIPSI_Email_Service::send_welcome_email( $study_id, $participant_id );
            } else {
                // Fallback: email básico con wp_mail
                global $wpdb;
                $participant = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT email, first_name FROM {$wpdb->prefix}survey_participants WHERE id = %d",
                        $participant_id
                    )
                );

                if ( ! $participant ) {
                    return;
                }

                $study_name       = $this->get_study_name( $study_id );
                $investigator     = get_option( 'eipsi_investigator_name', get_bloginfo( 'name' ) );
                $display_name     = ! empty( $participant->first_name ) ? $participant->first_name : $name;

                $subject = sprintf(
                    /* translators: %s: study name */
                    __( 'Te damos la bienvenida a %s', 'eipsi-forms' ),
                    $study_name
                );

                $body = sprintf(
                    /* translators: 1: participant name, 2: pool name, 3: study name, 4: magic link URL, 5: investigator name */
                    __(
                        "Hola %1\$s,\n\n" .
                        "Fuiste asignado/a al estudio \"%3\$s\" a través del pool \"%2\$s\".\n\n" .
                        "Podés comenzar tu participación haciendo clic en el siguiente enlace:\n\n" .
                        "%4\$s\n\n" .
                        "Este enlace es válido por 48 horas.\n\n" .
                        "Muchas gracias por tu participación.\n\n" .
                        "— %5\$s",
                        'eipsi-forms'
                    ),
                    esc_html( $display_name ),
                    esc_html( $pool_name ),
                    esc_html( $study_name ),
                    esc_url( $magic_link_url ),
                    esc_html( $investigator )
                );

                wp_mail( $participant->email, $subject, $body );
            }
        } catch ( Exception $e ) {
            error_log( '[EIPSI PoolAssignment] send_welcome_notification exception: ' . $e->getMessage() );
        }
    }

    /**
     * Obtener nombre del estudio por ID.
     *
     * @param int $study_id ID del estudio.
     * @return string Nombre del estudio o "Estudio #ID" como fallback.
     */
    private function get_study_name( $study_id ) {
        global $wpdb;

        $study_id = absint( $study_id );

        $name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT study_name FROM {$wpdb->prefix}survey_studies WHERE id = %d",
                $study_id
            )
        );

        return $name ? $name : sprintf( __( 'Estudio #%d', 'eipsi-forms' ), $study_id );
    }
}
