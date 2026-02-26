<?php
/**
 * Pool Dashboard Service
 * Analytics and monitoring for longitudinal pools.
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EIPSI_Pool_Dashboard_Service {

    /**
     * Pools table name.
     *
     * @var string
     */
    private $pools_table;

    /**
     * Assignments table name.
     *
     * @var string
     */
    private $assignments_table;

    /**
     * Studies table name.
     *
     * @var string
     */
    private $studies_table;

    /**
     * Participants table name.
     *
     * @var string
     */
    private $participants_table;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->pools_table        = $wpdb->prefix . 'eipsi_longitudinal_pools';
        $this->assignments_table  = $wpdb->prefix . 'eipsi_longitudinal_pool_assignments';
        $this->studies_table      = $wpdb->prefix . 'survey_studies';
        $this->participants_table = $wpdb->prefix . 'survey_participants';
    }

    /**
     * Get comprehensive pool statistics.
     *
     * @param int $pool_id Pool ID.
     * @return array
     */
    public function get_pool_analytics( $pool_id ) {
        $pool_id = absint( $pool_id );

        $pool_info = $this->get_pool_info( $pool_id );
        $total     = $this->get_total_assignments( $pool_id );
        $breakdown = $this->get_studies_breakdown( $pool_id, $total );

        return array(
            'pool_info'        => $pool_info,
            'total_assignments' => $total,
            'studies_breakdown' => $breakdown,
            'completion_rates' => $this->get_completion_rates( $breakdown ),
            'dropout_rates'    => $this->get_dropout_rates( $breakdown ),
            'recent_activity'  => $this->get_recent_activity( $pool_id ),
            'wave_analytics'   => $this->get_wave_analytics( $pool_id ),
        );
    }

    /**
     * Get pool base info.
     *
     * @param int $pool_id Pool ID.
     * @return array|null
     */
    private function get_pool_info( $pool_id ) {
        global $wpdb;

        $pool = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->pools_table} WHERE id = %d", $pool_id ),
            ARRAY_A
        );

        if ( empty( $pool ) ) {
            return null;
        }

        $pool['studies'] = json_decode( $pool['studies'], true );
        $pool['probabilities'] = json_decode( $pool['probabilities'], true );

        if ( ! is_array( $pool['studies'] ) ) {
            $pool['studies'] = array();
        }

        if ( ! is_array( $pool['probabilities'] ) ) {
            $pool['probabilities'] = array();
        }

        return $pool;
    }

    /**
     * Get total assignments count.
     *
     * @param int $pool_id Pool ID.
     * @return int
     */
    private function get_total_assignments( $pool_id ) {
        global $wpdb;

        $total = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$this->assignments_table} WHERE pool_id = %d", $pool_id )
        );

        return (int) $total;
    }

    /**
     * Get breakdown per study.
     *
     * @param int $pool_id Pool ID.
     * @param int $total_assignments Total assignments.
     * @return array
     */
    private function get_studies_breakdown( $pool_id, $total_assignments ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.assigned_study_id,
                    s.study_name,
                    s.study_code,
                    COUNT(*) AS total_assignments,
                    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN a.status = 'assigned' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN a.status = 'dropped' THEN 1 ELSE 0 END) AS dropped
                FROM {$this->assignments_table} a
                LEFT JOIN {$this->studies_table} s ON a.assigned_study_id = s.id
                WHERE a.pool_id = %d
                GROUP BY a.assigned_study_id, s.study_name, s.study_code
                ORDER BY total_assignments DESC",
                $pool_id
            ),
            ARRAY_A
        );

        $breakdown = array();
        foreach ( $rows as $row ) {
            $total = (int) $row['total_assignments'];
            $completion_rate = $total > 0 ? round( ( (int) $row['completed'] / $total ) * 100, 1 ) : 0;
            $percent_of_pool = $total_assignments > 0 ? round( ( $total / $total_assignments ) * 100, 1 ) : 0;

            $breakdown[] = array(
                'study_id'         => (int) $row['assigned_study_id'],
                'study_name'       => $row['study_name'] ? $row['study_name'] : sprintf( __( 'Estudio #%d', 'eipsi-forms' ), (int) $row['assigned_study_id'] ),
                'study_code'       => $row['study_code'] ? $row['study_code'] : '',
                'assignments'      => $total,
                'percent_of_pool'  => $percent_of_pool,
                'completed'        => (int) $row['completed'],
                'in_progress'      => (int) $row['in_progress'],
                'dropped'          => (int) $row['dropped'],
                'completion_rate'  => $completion_rate,
            );
        }

        return $breakdown;
    }

    /**
     * Get completion rates per study.
     *
     * @param array $breakdown Breakdown array.
     * @return array
     */
    private function get_completion_rates( $breakdown ) {
        $rates = array();
        foreach ( $breakdown as $row ) {
            $rates[ $row['study_id'] ] = $row['completion_rate'];
        }

        return $rates;
    }

    /**
     * Get dropout rates per study.
     *
     * @param array $breakdown Breakdown array.
     * @return array
     */
    private function get_dropout_rates( $breakdown ) {
        $rates = array();
        foreach ( $breakdown as $row ) {
            $total = (int) $row['assignments'];
            $rates[ $row['study_id'] ] = $total > 0 ? round( ( $row['dropped'] / $total ) * 100, 1 ) : 0;
        }

        return $rates;
    }

    /**
     * Get recent activity.
     *
     * @param int $pool_id Pool ID.
     * @param int $limit Limit.
     * @return array
     */
    private function get_recent_activity( $pool_id, $limit = 20 ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.id AS assignment_id,
                    a.assigned_at,
                    a.status,
                    a.participant_id,
                    a.assigned_study_id,
                    p.email,
                    p.first_name,
                    p.last_name,
                    s.study_name,
                    s.study_code
                FROM {$this->assignments_table} a
                LEFT JOIN {$this->participants_table} p ON a.participant_id = p.id
                LEFT JOIN {$this->studies_table} s ON a.assigned_study_id = s.id
                WHERE a.pool_id = %d
                ORDER BY a.assigned_at DESC
                LIMIT %d",
                $pool_id,
                $limit
            ),
            ARRAY_A
        );

        $activity = array();
        foreach ( $rows as $row ) {
            $full_name = trim( sprintf( '%s %s', $row['first_name'], $row['last_name'] ) );
            $activity[] = array(
                'assignment_id' => (int) $row['assignment_id'],
                'participant_id' => (int) $row['participant_id'],
                'participant_name' => $full_name ? $full_name : $row['email'],
                'participant_email' => $row['email'],
                'study_name' => $row['study_name'] ? $row['study_name'] : sprintf( __( 'Estudio #%d', 'eipsi-forms' ), (int) $row['assigned_study_id'] ),
                'study_code' => $row['study_code'],
                'status' => $row['status'],
                'assigned_at' => $row['assigned_at'],
            );
        }

        return $activity;
    }

    /**
     * Get wave analytics (placeholder for future expansion).
     *
     * @param int $pool_id Pool ID.
     * @return array
     */
    private function get_wave_analytics( $pool_id ) {
        return array();
    }
}
