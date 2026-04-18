<?php
/**
 * EIPSI_Pool_Assignment_Service
 *
 * Lógica de asignación aleatoria ponderada para pools de estudios.
 * Fase 3 del roadmap "Pool de Estudios → Nivel Randomization".
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 2.5.3
 * @since 2.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EIPSI_Pool_Assignment_Service
 *
 * Gestiona la asignación de participantes a estudios dentro de pools,
 * con soporte para métodos seeded (determinístico) y pure-random.
 */
class EIPSI_Pool_Assignment_Service {

    /**
     * Tabla principal de pools.
     *
     * @var string
     */
    private $pools_table;

    /**
     * Tabla de asignaciones de pools (Fase 1).
     *
     * @var string
     */
    private $assignments_table;

    /**
     * Tabla de analytics de pools (Fase 1).
     *
     * @var string
     */
    private $analytics_table;

    /**
     * Nombre de la cookie para asignaciones anónimas.
     *
     * @var string
     */
    private $cookie_name = 'eipsi_pool_assignment';

    /**
     * TTL de la cookie en segundos (30 días).
     *
     * @var int
     */
    private $cookie_ttl = 2592000;

    /**
     * Constructor: inicializa nombres de tablas.
     */
    public function __construct() {
        global $wpdb;
        $this->pools_table       = $wpdb->prefix . 'eipsi_longitudinal_pools';
        $this->assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';
        $this->analytics_table   = $wpdb->prefix . 'eipsi_pool_analytics';
    }

    /**
     * Asignar participante a un estudio del pool.
     *
     * Si ya tiene asignación previa (modo seeded), devolverla.
     * Si no, hacer weighted random y persistir.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_id    ID del participante (email_id o fingerprint).
     * @param string $method            Método de asignación (seeded|pure-random).
     * @return object|false             Objeto asignación o false en error.
     */
    public function assign_participant( $pool_id, $participant_id, $method = 'seeded' ) {
        global $wpdb;

        $pool_id        = absint( $pool_id );
        $participant_id = sanitize_text_field( $participant_id );
        $method         = in_array( $method, array( 'seeded', 'pure-random' ), true ) ? $method : 'seeded';

        // Verificar que el pool existe y está activo
        $pool = $this->get_pool( $pool_id );
        if ( ! $pool || $pool->status !== 'active' ) {
            error_log( "[EIPSI-POOL] Pool {$pool_id} no existe o no está activo" );
            return false;
        }

        // Obtener config del pool
        $config = json_decode( $pool->config, true );
        if ( empty( $config['studies'] ) ) {
            error_log( "[EIPSI-POOL] Pool {$pool_id} no tiene estudios configurados" );
            return false;
        }

        $studies = $config['studies'];

        // Verificar allow_reassignment
        $allow_reassignment = ! empty( $config['allow_reassignment'] );

        // Buscar asignación existente
        $existing = $this->get_existing_assignment( $pool_id, $participant_id );

        if ( $existing ) {
            // Si no permite re-asignación o no completó, devolver existente
            if ( ! $allow_reassignment || ! $existing->completed ) {
                $this->record_access( $existing->id );
                return $this->format_assignment( $existing, true );
            }
            // Si permite re-asignación y completó, continuar para crear nueva
        }

        // Weighted random assignment
        $selected_study = $this->weighted_random_assign( $pool_id, $participant_id, $method, $studies );

        if ( ! $selected_study ) {
            error_log( "[EIPSI-POOL] Error en weighted random para pool {$pool_id}" );
            return false;
        }

        $study_id = $selected_study['id'];

        // Crear nueva asignación
        $assignment_id = $this->create_assignment( $pool_id, $participant_id, $study_id );

        if ( ! $assignment_id ) {
            return false;
        }

        // Obtener la asignación recién creada
        $assignment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->assignments_table} WHERE id = %d",
                $assignment_id
            )
        );

        // Setear cookie para participantes anónimos (no autenticados)
        if ( ! $this->is_authenticated_participant( $participant_id ) ) {
            $this->set_assignment_cookie( $pool_id, $assignment_id );
        }

        return $this->format_assignment( $assignment, false );
    }

    /**
     * Obtener asignación existente para pool+participante.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_id    ID del participante.
     * @return object|null              Fila de asignación o null.
     */
    public function get_existing_assignment( $pool_id, $participant_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->assignments_table}
                 WHERE pool_id = %d AND participant_id = %s
                 ORDER BY assigned_at DESC
                 LIMIT 1",
                absint( $pool_id ),
                sanitize_text_field( $participant_id )
            )
        );
    }

    /**
     * Weighted random: elegir study_id según probabilidades configuradas.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_id    ID del participante.
     * @param string $method            Método de asignación.
     * @param array  $studies           Array de estudios con probabilidades.
     * @return array|false              Estudio seleccionado o false.
     */
    public function weighted_random_assign( $pool_id, $participant_id, $method, $studies ) {
        if ( empty( $studies ) ) {
            return false;
        }

        if ( count( $studies ) === 1 ) {
            return $studies[0];
        }

        // Generar número aleatorio según método
        if ( $method === 'seeded' ) {
            // Seeded: usar hash determinístico
            $seed = crc32( $pool_id . '_' . $participant_id );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_srand
            srand( $seed );
            $rand = mt_rand( 0, 10000 ) / 100; // 0.00 a 100.00
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_srand
            srand();
        } else {
            // Pure-random: usar random_int criptográfico
            $rand = random_int( 0, 10000 ) / 100;
        }

        // Recorrer estudios acumulando probabilidades
        $cumulative = 0;
        foreach ( $studies as $study ) {
            $probability = isset( $study['probability'] ) ? floatval( $study['probability'] ) : 0;
            $cumulative += $probability;
            if ( $rand <= $cumulative ) {
                return $study;
            }
        }

        // Fallback al último estudio
        return end( $studies );
    }

    /**
     * Registrar nueva asignación en eipsi_pool_assignments.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_id    ID del participante.
     * @param int    $study_id          ID del estudio asignado.
     * @return int|false                ID de la asignación creada o false.
     */
    public function create_assignment( $pool_id, $participant_id, $study_id ) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->assignments_table,
            array(
                'pool_id'        => absint( $pool_id ),
                'participant_id' => sanitize_text_field( $participant_id ),
                'study_id'       => absint( $study_id ),
                'assigned_at'    => current_time( 'mysql' ),
                'first_access'   => current_time( 'mysql' ),
                'last_access'    => current_time( 'mysql' ),
                'access_count'   => 1,
                'completed'      => 0,
            ),
            array( '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%d' )
        );

        if ( $result === false ) {
            error_log( '[EIPSI-POOL] Error creando asignación: ' . $wpdb->last_error );
            return false;
        }

        $assignment_id = (int) $wpdb->insert_id;

        // Actualizar analytics diarios de assignments (Fase 4)
        $this->update_daily_analytics_assignments( $pool_id, $study_id );

        return $assignment_id;
    }

    /**
     * Actualizar access_count y last_access en cada visita.
     *
     * @param int $assignment_id ID de la asignación.
     * @return bool True si se actualizó correctamente.
     */
    public function record_access( $assignment_id ) {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->assignments_table}
                 SET last_access = NOW(),
                     access_count = access_count + 1,
                     first_access = IFNULL(first_access, NOW())
                 WHERE id = %d",
                absint( $assignment_id )
            )
        );

        return $result !== false;
    }

    /**
     * Marcar asignación como completada.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_id    ID del participante.
     * @param string $completion_form_id ID del formulario que completó.
     * @return bool True si se actualizó correctamente.
     */
    public function mark_completed( $pool_id, $participant_id, $completion_form_id = '' ) {
        global $wpdb;

        // Obtener study_id antes de marcar completada (para analytics)
        $assignment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT study_id FROM {$this->assignments_table}
                 WHERE pool_id = %d AND participant_id = %s AND completed = 0",
                absint( $pool_id ),
                sanitize_text_field( $participant_id )
            )
        );

        $study_id = $assignment ? $assignment->study_id : 0;

        $result = $wpdb->update(
            $this->assignments_table,
            array(
                'completed'          => 1,
                'completed_at'       => current_time( 'mysql' ),
                'completion_form_id' => sanitize_text_field( $completion_form_id ),
            ),
            array(
                'pool_id'        => absint( $pool_id ),
                'participant_id' => sanitize_text_field( $participant_id ),
                'completed'      => 0,
            ),
            array( '%d', '%s', '%s' ),
            array( '%d', '%s', '%d' )
        );

        if ( $result !== false && $result > 0 && $study_id ) {
            // Actualizar analytics diarios de completions
            $this->update_daily_analytics_completions( $pool_id, $study_id );
            return true;
        }

        return $result !== false && $result > 0;
    }

    /**
     * Actualizar analytics diarios al completar un estudio.
     *
     * Incrementa el contador de completions para el día actual.
     *
     * @param int $pool_id  ID del pool.
     * @param int $study_id ID del estudio.
     * @return bool True si se actualizó correctamente.
     */
    private function update_daily_analytics_completions( $pool_id, $study_id ) {
        global $wpdb;

        $today = current_time( 'Y-m-d' );

        // Total acumulado de asignaciones a este estudio en este pool
        $cumulative = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->assignments_table}
                 WHERE pool_id = %d AND study_id = %d",
                absint( $pool_id ),
                absint( $study_id )
            )
        );

        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->analytics_table}
                 (pool_id, date, study_id, completions, cumulative_assignments)
                 VALUES (%d, %s, %d, 1, %d)
                 ON DUPLICATE KEY UPDATE
                 completions = completions + 1,
                 cumulative_assignments = %d",
                absint( $pool_id ),
                $today,
                absint( $study_id ),
                intval( $cumulative ),
                intval( $cumulative )
            )
        );

        return $result !== false;
    }

    /**
     * Actualizar analytics diarios al crear una asignación.
     *
     * Incrementa el contador de assignments para el día actual.
     *
     * @param int $pool_id  ID del pool.
     * @param int $study_id ID del estudio.
     * @return bool True si se actualizó correctamente.
     */
    private function update_daily_analytics_assignments( $pool_id, $study_id ) {
        global $wpdb;

        $today = current_time( 'Y-m-d' );

        // Total acumulado de asignaciones a este estudio en este pool
        $cumulative = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->assignments_table}
                 WHERE pool_id = %d AND study_id = %d",
                absint( $pool_id ),
                absint( $study_id )
            )
        );

        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->analytics_table}
                 (pool_id, date, study_id, assignments, cumulative_assignments)
                 VALUES (%d, %s, %d, 1, %d)
                 ON DUPLICATE KEY UPDATE
                 assignments = assignments + 1,
                 cumulative_assignments = %d",
                absint( $pool_id ),
                $today,
                absint( $study_id ),
                intval( $cumulative ),
                intval( $cumulative )
            )
        );

        return $result !== false;
    }

    /**
     * Obtener URL del estudio asignado.
     *
     * Busca en wp_survey_studies la página asociada al estudio.
     *
     * @param int $study_id ID del estudio.
     * @return string URL del estudio o vacío si no se encuentra.
     */
    public function get_study_url( $study_id ) {
        global $wpdb;

        $studies_table = $wpdb->prefix . 'survey_studies';

        $config = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT config FROM {$studies_table} WHERE id = %d",
                absint( $study_id )
            )
        );

        if ( ! $config ) {
            return '';
        }

        $config_array = json_decode( $config, true );
        return $config_array['shortcode_page_url'] ?? '';
    }

    /**
     * Verificar si todas las waves ACTIVAS del estudio están completadas.
     *
     * Un estudio se considera completado cuando todas sus waves activas
     * tienen status 'submitted' en wp_survey_assignments para ese participante.
     *
     * @param int $study_id       ID del estudio.
     * @param int $participant_id ID del participante.
     * @return bool True si todas las waves activas están completadas.
     */
    public function is_study_completed( $study_id, $participant_id ) {
        global $wpdb;

        $waves_table      = $wpdb->prefix . 'survey_waves';
        $assignments_table = $wpdb->prefix . 'survey_assignments';

        // Contar total de waves ACTIVAS del estudio
        $total_waves = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$waves_table}
                 WHERE study_id = %d AND status = 'active'",
                absint( $study_id )
            )
        );

        if ( ! $total_waves ) {
            return false;
        }

        // Contar assignments completados (waves con status 'submitted')
        $completed_waves = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$assignments_table} sa
                 JOIN {$waves_table} sw ON sw.id = sa.wave_id
                 WHERE sw.study_id = %d
                 AND sw.status = 'active'
                 AND sa.participant_id = %d
                 AND sa.status = 'submitted'",
                absint( $study_id ),
                absint( $participant_id )
            )
        );

        return intval( $completed_waves ) >= intval( $total_waves );
    }

    /**
     * Setear cookie de asignación para participantes anónimos.
     *
     * @param int $pool_id       ID del pool.
     * @param int $assignment_id ID de la asignación.
     */
    public function set_assignment_cookie( $pool_id, $assignment_id ) {
        $cookie_value = wp_json_encode( array(
            'pool_id'       => $pool_id,
            'assignment_id' => $assignment_id,
        ) );

        setcookie(
            $this->cookie_name . '_' . $pool_id,
            $cookie_value,
            array(
                'expires'  => time() + $this->cookie_ttl,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );
    }

    /**
     * Obtener asignación desde cookie.
     *
     * @param int $pool_id ID del pool.
     * @return array|null Datos de asignación o null.
     */
    public function get_assignment_from_cookie( $pool_id ) {
        $cookie_name = $this->cookie_name . '_' . $pool_id;

        if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
            return null;
        }

        $cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
        $data = json_decode( $cookie_value, true );

        if ( ! is_array( $data ) || empty( $data['assignment_id'] ) ) {
            return null;
        }

        return $data;
    }

    /**
     * Verificar si el participante está autenticado.
     *
     * @param string $participant_id ID del participante.
     * @return bool True si está autenticado.
     */
    public function is_authenticated_participant( $participant_id ) {
        // Si es un email_id (contiene @) o fingerprint (hash), no está autenticado vía Auth Service
        // Los participantes autenticados vía EIPSI_Auth_Service usan el ID numérico directo
        return is_numeric( $participant_id ) && intval( $participant_id ) > 0;
    }

    /**
     * Legacy: Asignar participante a un pool (mantener compatibilidad).
     *
     * @param int         $pool_id           ID del pool.
     * @param string      $participant_email Email del participante.
     * @param string|null $participant_name  Nombre opcional.
     * @return array|WP_Error Resultado de la asignación.
     */
    public function assign_participant_to_pool( $pool_id, $participant_email, $participant_name = null ) {
        // Buscar o crear participante para obtener su ID
        $participant = $this->create_or_get_participant_legacy( $pool_id, $participant_email, $participant_name );

        if ( is_wp_error( $participant ) ) {
            return $participant;
        }

        $participant_id = $participant['participant_id'];

        // Obtener pool para el método
        $pool = $this->get_pool( $pool_id );
        $method = $pool && isset( $pool->method ) ? $pool->method : 'seeded';

        // Usar el nuevo método de asignación
        $assignment = $this->assign_participant( $pool_id, $participant_id, $method );

        if ( ! $assignment ) {
            return new WP_Error( 'assignment_failed', __( 'No se pudo asignar el participante.', 'eipsi-forms' ) );
        }

        // Generar magic link
        $magic_link_url = $this->generate_magic_link_for_study( $assignment->study_id, $participant_id );

        if ( is_wp_error( $magic_link_url ) ) {
            return $magic_link_url;
        }

        return array(
            'success'           => true,
            'study_id'          => $assignment->study_id,
            'participant_id'    => $participant_id,
            'magic_link_url'    => $magic_link_url,
            'is_new_assignment' => ! $assignment->is_existing,
            'study_name'        => $this->get_study_name( $assignment->study_id ),
            'pool_name'         => $pool ? $pool->pool_name : '',
        );
    }

    /**
     * Legacy: Obtener estadísticas del pool.
     *
     * @param int $pool_id ID del pool.
     * @return array Estadísticas del pool.
     */
    public function get_pool_stats( $pool_id ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT study_id, COUNT(*) as total
                 FROM {$this->assignments_table}
                 WHERE pool_id = %d
                 GROUP BY study_id",
                absint( $pool_id )
            ),
            ARRAY_A
        );

        $stats = array();
        $total = 0;

        foreach ( $rows as $row ) {
            $stats[ intval( $row['study_id'] ) ] = intval( $row['total'] );
            $total += intval( $row['total'] );
        }

        return array(
            'by_study' => $stats,
            'total'    => $total,
        );
    }

    /**
     * Obtener datos del pool.
     *
     * @param int $pool_id ID del pool.
     * @return object|null Datos del pool o null.
     */
    public function get_pool( $pool_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->pools_table} WHERE id = %d",
                absint( $pool_id )
            )
        );
    }

    /**
     * Formatear asignación para retorno.
     *
     * @param object $assignment Fila de asignación.
     * @param bool   $is_existing Si es una asignación existente.
     * @return object Objeto formateado.
     */
    private function format_assignment( $assignment, $is_existing = false ) {
        return (object) array(
            'id'            => intval( $assignment->id ),
            'pool_id'       => intval( $assignment->pool_id ),
            'participant_id'=> $assignment->participant_id,
            'study_id'      => intval( $assignment->study_id ),
            'assigned_at'   => $assignment->assigned_at,
            'first_access'  => $assignment->first_access,
            'last_access'   => $assignment->last_access,
            'access_count'  => intval( $assignment->access_count ),
            'completed'     => (bool) $assignment->completed,
            'completed_at'  => $assignment->completed_at,
            'is_existing'   => $is_existing,
        );
    }

    /**
     * Obtener nombre del estudio.
     *
     * @param int $study_id ID del estudio.
     * @return string Nombre del estudio.
     */
    private function get_study_name( $study_id ) {
        global $wpdb;

        $name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT study_name FROM {$wpdb->prefix}survey_studies WHERE id = %d",
                absint( $study_id )
            )
        );

        return $name ?: sprintf( __( 'Estudio #%d', 'eipsi-forms' ), $study_id );
    }

    /**
     * Generar magic link para un estudio.
     *
     * @param int $study_id       ID del estudio.
     * @param int $participant_id ID del participante.
     * @return string|WP_Error URL del magic link o error.
     */
    private function generate_magic_link_for_study( $study_id, $participant_id ) {
        if ( ! class_exists( 'EIPSI_Magic_Links_Service' ) ) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-magic-links-service.php';
        }

        if ( ! class_exists( 'EIPSI_Magic_Links_Service' ) ) {
            return new WP_Error( 'magic_link_service_unavailable', __( 'Servicio de magic links no disponible.', 'eipsi-forms' ) );
        }

        $magic_link_service = new EIPSI_Magic_Links_Service();
        return $magic_link_service->generate_link( $participant_id, $study_id );
    }

    /**
     * Legacy: Crear o obtener participante.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_email Email del participante.
     * @param string $participant_name  Nombre del participante.
     * @return array|WP_Error Datos del participante o error.
     */
    private function create_or_get_participant_legacy( $pool_id, $participant_email, $participant_name ) {
        global $wpdb;

        // Buscar participante existente por email
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}survey_participants WHERE email = %s ORDER BY id DESC LIMIT 1",
                sanitize_email( $participant_email )
            )
        );

        if ( $existing ) {
            return array(
                'participant_id' => intval( $existing->id ),
                'is_new'         => false,
            );
        }

        // Crear nuevo participante
        $result = $wpdb->insert(
            $wpdb->prefix . 'survey_participants',
            array(
                'email'      => sanitize_email( $participant_email ),
                'first_name' => sanitize_text_field( $participant_name ),
                'is_active'  => 1,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s' )
        );

        if ( $result === false ) {
            return new WP_Error( 'participant_creation_failed', __( 'No se pudo crear el participante.', 'eipsi-forms' ) );
        }

        return array(
            'participant_id' => intval( $wpdb->insert_id ),
            'is_new'         => true,
        );
    }

    /**
     * Legacy: Obtener asignación existente por email.
     *
     * @param int    $pool_id           ID del pool.
     * @param string $participant_email Email del participante.
     * @return object|null Asignación existente o null.
     */
    public function get_existing_assignment_by_email( $pool_id, $participant_email ) {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT pa.*
                 FROM {$this->assignments_table} pa
                 INNER JOIN {$wpdb->prefix}survey_participants sp ON sp.id = pa.participant_id
                 WHERE pa.pool_id = %d
                   AND sp.email = %s
                 ORDER BY pa.assigned_at DESC
                 LIMIT 1",
                absint( $pool_id ),
                sanitize_email( $participant_email )
            )
        );

        return $row ?: null;
    }
}
