<?php
/**
 * EIPSI Participant Dashboard Data Service
 * 
 * Fase 4 - Dashboard del Participante (Timeline Visual)
 * Proporciona datos estructurados para el frontend del dashboard
 *
 * @package EIPSI_Forms
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EIPSI_Participant_Dashboard_Data
 * 
 * Maneja la obtención y transformación de datos del timeline
 * para el dashboard del participante.
 */
class EIPSI_Participant_Dashboard_Data {

    /**
     * Obtener datos completos del timeline para un participante
     *
     * @param int $participant_id ID del participante
     * @param int $study_id ID del estudio/survey
     * @return array Datos del timeline, wave activa y estado de completitud
     */
    public static function get_timeline_data($participant_id, $study_id) {
        global $wpdb;
        
        // Obtener assignments con datos de waves
        $assignments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.id,
                a.wave_id,
                a.status,
                a.available_at,
                a.due_at,
                a.submitted_at,
                w.wave_index,
                w.name as wave_title
            FROM {$wpdb->prefix}survey_assignments a
            JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
            WHERE a.participant_id = %d AND a.study_id = %d
            ORDER BY w.wave_index ASC
        ", $participant_id, $study_id));
        
        $now = current_time('timestamp');
        $timeline = [];
        $active_wave = null;
        
        foreach ($assignments as $a) {
            $available_ts = strtotime($a->available_at);
            // CORRECCIÓN 1: due_ts puede ser null (última wave sin cierre configurado)
            $due_ts = $a->due_at ? strtotime($a->due_at) : null;
            
            $visual_status = self::calculate_visual_status($a, $now, $available_ts, $due_ts);
            
            $wave_data = [
                'index'          => $a->wave_index,
                'title'          => $a->wave_title,
                'status'         => $visual_status,
                'db_status'      => $a->status,
                'available_at'   => $a->available_at,
                'due_at'         => $a->due_at,
                'submitted_at'   => $a->submitted_at,
                'time_remaining' => null,
                'time_until_open'=> null,
            ];
            
            if ($visual_status === 'available') {
                $wave_data['time_remaining'] = $due_ts ? $due_ts - $now : null;
                $active_wave = $wave_data;
            } elseif ($visual_status === 'pending') {
                $wave_data['time_until_open'] = $available_ts - $now;
                if (!$active_wave) {
                    $active_wave = $wave_data;
                }
            }
            
            $timeline[] = $wave_data;
        }
        
        return [
            'timeline'          => $timeline,
            'active_wave'       => $active_wave,
            'study_completed'   => self::is_study_completed($timeline),
        ];
    }
    
    /**
     * Calcular el estado visual de una wave para el frontend
     *
     * Estados posibles:
     * - 'submitted': Ya completó esta wave
     * - 'expired': Se pasó la fecha límite (y no fue completada)
     * - 'available': Está disponible para responder ahora
     * - 'pending': Aún no está disponible (bloqueada por tiempo)
     *
     * @param object $assignment Datos del assignment de la BD
     * @param int $now Timestamp actual
     * @param int $available_ts Timestamp de disponibilidad
     * @param int|null $due_ts Timestamp de vencimiento (puede ser null)
     * @return string Estado visual
     */
    private static function calculate_visual_status($assignment, $now, $available_ts, $due_ts) {
        if ($assignment->status === 'submitted') {
            return 'submitted';
        }
        
        // CORRECCIÓN 1: Guarda para due_ts null (última wave abierta indefinidamente)
        if ($due_ts && $now >= $due_ts) {
            return 'expired';
        }
        
        if ($now >= $available_ts) {
            return 'available';
        }
        
        return 'pending';
    }

    /**
     * Determinar si el estudio está completado
     *
     * @param array $timeline Array de waves con sus estados
     * @return bool True si todas las waves están submitted o expired
     */
    private static function is_study_completed($timeline) {
        if (empty($timeline)) {
            return false;
        }
        
        foreach ($timeline as $wave) {
            if ($wave['status'] !== 'submitted' && $wave['status'] !== 'expired') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtener label traducido para un estado visual
     *
     * @param string $status Estado visual ('available', 'pending', 'expired', 'submitted')
     * @return string Label traducido
     */
    public static function get_status_label($status) {
        $labels = [
            'available' => __('Disponible ahora', 'eipsi-forms'),
            'pending'   => __('Próximamente', 'eipsi-forms'),
            'expired'   => __('Tiempo agotado', 'eipsi-forms'),
            'submitted' => __('Completada', 'eipsi-forms'),
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Formatear segundos en formato legible (días, horas, minutos)
     *
     * @param int|null $seconds Segundos a formatear
     * @return string Tiempo formateado
     */
    public static function format_duration($seconds) {
        if (empty($seconds) || $seconds < 1) {
            return '-';
        }

        $minutes = floor($seconds / 60);
        if ($minutes < 1) {
            return '< 1 min';
        } elseif ($minutes < 60) {
            return sprintf('%d min', $minutes);
        } else {
            $hours = floor($minutes / 60);
            $remaining_minutes = $minutes % 60;
            return sprintf('%dh %dmin', $hours, $remaining_minutes);
        }
    }
    
    /**
     * Formatear fecha en formato relativo amigable
     *
     * @param string $date_str Fecha en formato string
     * @param string $default Valor por defecto si la fecha es inválida
     * @return string Fecha formateada
     */
    public static function format_date_relative($date_str, $default = '-') {
        if (empty($date_str) || $date_str === '0000-00-00 00:00:00') {
            return $default;
        }

        $timestamp = strtotime($date_str);
        if (!$timestamp) {
            return $default;
        }

        $today = strtotime('today');
        $diff_days = floor(($today - $timestamp) / DAY_IN_SECONDS);

        if ($diff_days === 0) {
            return __('Hoy', 'eipsi-forms') . ' ' . date('H:i', $timestamp);
        } elseif ($diff_days === 1) {
            return __('Ayer', 'eipsi-forms');
        } elseif ($diff_days < 7) {
            return sprintf(__('%d días atrás', 'eipsi-forms'), $diff_days);
        } else {
            return date_i18n('j M, H:i', $timestamp);
        }
    }
}
