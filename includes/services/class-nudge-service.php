<?php
/**
 * EIPSI Nudge Service
 * 
 * Maneja el sistema de 5 nudges (0-4) para recordatorios de waves.
 * Nudge 0: Siempre se envía inmediatamente cuando la wave está disponible.
 * Nudges 1-4: Solo si el investigador activó follow_up_reminders_enabled.
 * 
 * @package EIPSI_Forms
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EIPSI_Nudge_Service
 */
class EIPSI_Nudge_Service {
    
    /**
     * Nudge configurations
     */
    const NUDGE_AVAILABLE = 0;      // "Tu Toma X está lista"
    const NUDGE_FOLLOW_UP = 1;      // "¿Ya completaste?" / "Quedan 2 días"
    const NUDGE_REMINDER = 2;       // "Te esperamos" / "Mañana vence"
    const NUDGE_URGENCY = 3;        // "¿Necesitás ayuda?" / "Plazo extendido"
    const NUDGE_LAST_CALL = 4;      // "Última oportunidad"
    
    /**
     * Get the nudge configuration for a specific stage
     * 
     * @param int $stage Nudge stage (0-4)
     * @param bool $has_due_date Whether the wave has a due date
     * @return array|null Nudge configuration or null if invalid
     */
    public static function get_nudge_config($stage, $has_due_date = false) {
        $configs = self::get_all_nudge_configs($has_due_date);
        return isset($configs[$stage]) ? $configs[$stage] : null;
    }
    
    /**
     * Get all nudge configurations
     * 
     * @param bool $has_due_date Whether the wave has a due date
     * @return array All nudge configurations
     */
    public static function get_all_nudge_configs($has_due_date = false) {
        if ($has_due_date) {
            // Strategy: Days before/after due date
            return array(
                self::NUDGE_AVAILABLE => array(
                    'label' => __('Disponible', 'eipsi-forms'),
                    'subject_key' => 'nudge_0_available',
                    'template' => 'wave-nudge-0',
                    'timing' => 'immediate',
                    'timing_days' => 0,
                    'tone' => 'neutral',
                    'description' => __('Email inmediato cuando la wave está disponible', 'eipsi-forms')
                ),
                self::NUDGE_FOLLOW_UP => array(
                    'label' => __('Seguimiento', 'eipsi-forms'),
                    'subject_key' => 'nudge_1_follow_up',
                    'template' => 'wave-nudge-1-due',
                    'timing' => 'days_before',
                    'timing_days' => 2,
                    'tone' => 'gentle',
                    'description' => __('2 días antes del vencimiento', 'eipsi-forms')
                ),
                self::NUDGE_REMINDER => array(
                    'label' => __('Recordatorio', 'eipsi-forms'),
                    'subject_key' => 'nudge_2_reminder',
                    'template' => 'wave-nudge-2-due',
                    'timing' => 'days_before',
                    'timing_days' => 1,
                    'tone' => 'urgent',
                    'description' => __('1 día antes del vencimiento', 'eipsi-forms')
                ),
                self::NUDGE_URGENCY => array(
                    'label' => __('Extensión', 'eipsi-forms'),
                    'subject_key' => 'nudge_3_extension',
                    'template' => 'wave-nudge-3-due',
                    'timing' => 'days_after',
                    'timing_days' => 0,
                    'tone' => 'helpful',
                    'description' => __('Día del vencimiento (extensión ofrecida)', 'eipsi-forms')
                ),
                self::NUDGE_LAST_CALL => array(
                    'label' => __('Último llamado', 'eipsi-forms'),
                    'subject_key' => 'nudge_4_last_call',
                    'template' => 'wave-nudge-4-due',
                    'timing' => 'days_after',
                    'timing_days' => 7,
                    'tone' => 'final',
                    'description' => __('7 días después del vencimiento', 'eipsi-forms')
                )
            );
        } else {
            // Strategy: Days since available (no due date)
            return array(
                self::NUDGE_AVAILABLE => array(
                    'label' => __('Disponible', 'eipsi-forms'),
                    'subject_key' => 'nudge_0_available',
                    'template' => 'wave-nudge-0',
                    'timing' => 'immediate',
                    'timing_days' => 0,
                    'tone' => 'neutral',
                    'description' => __('Email inmediato cuando la wave está disponible', 'eipsi-forms')
                ),
                self::NUDGE_FOLLOW_UP => array(
                    'label' => __('Seguimiento', 'eipsi-forms'),
                    'subject_key' => 'nudge_1_follow_up',
                    'template' => 'wave-nudge-1',
                    'timing' => 'days_after',
                    'timing_days' => 3,
                    'tone' => 'gentle',
                    'description' => __('3 días después de disponible', 'eipsi-forms')
                ),
                self::NUDGE_REMINDER => array(
                    'label' => __('Recordatorio', 'eipsi-forms'),
                    'subject_key' => 'nudge_2_reminder',
                    'template' => 'wave-nudge-2',
                    'timing' => 'days_after',
                    'timing_days' => 7,
                    'tone' => 'warm',
                    'description' => __('7 días después de disponible', 'eipsi-forms')
                ),
                self::NUDGE_URGENCY => array(
                    'label' => __('Ayuda', 'eipsi-forms'),
                    'subject_key' => 'nudge_3_help',
                    'template' => 'wave-nudge-3',
                    'timing' => 'days_after',
                    'timing_days' => 14,
                    'tone' => 'helpful',
                    'description' => __('14 días después de disponible', 'eipsi-forms')
                ),
                self::NUDGE_LAST_CALL => array(
                    'label' => __('Último llamado', 'eipsi-forms'),
                    'subject_key' => 'nudge_4_last_call',
                    'template' => 'wave-nudge-4',
                    'timing' => 'days_after',
                    'timing_days' => 30,
                    'tone' => 'final',
                    'description' => __('30 días después de disponible', 'eipsi-forms')
                )
            );
        }
    }
    
    /**
     * Get timeline preview for UI display
     * 
     * @param bool $has_due_date Whether the wave has a due date
     * @return string Timeline description
     */
    public static function get_timeline_preview($has_due_date = false) {
        $configs = self::get_all_nudge_configs($has_due_date);
        
        $timeline = array();
        foreach ($configs as $stage => $config) {
            if ($stage === self::NUDGE_AVAILABLE) {
                $timeline[] = '0d'; // Inmediato
            } elseif ($config['timing'] === 'days_before') {
                $timeline[] = '-' . $config['timing_days'] . 'd';
            } else {
                $timeline[] = '+' . $config['timing_days'] . 'd';
            }
        }
        
        return implode(' → ', $timeline);
    }
    
    /**
     * Convert any time unit to seconds for calculations
     * 
     * @param int $value Time value
     * @param string $unit Unit: minutes, hours, days
     * @return int Seconds
     */
    public static function convert_to_seconds($value, $unit = 'days') {
        switch ($unit) {
            case 'minutes':
                return $value * 60;
            case 'hours':
                return $value * 3600;
            case 'days':
            default:
                return $value * 86400;
        }
    }
    
    /**
     * Check if a nudge should be sent now
     * 
     * @param object $assignment Assignment data
     * @param object $wave Wave data
     * @param int $current_stage Current reminder stage
     * @param array $custom_config Optional custom config with unit support
     * @return bool Whether nudge should be sent
     */
    public static function should_send_nudge($assignment, $wave, $current_stage, $custom_config = null) {
        $assignment_id = isset($assignment->id) ? $assignment->id : 'unknown';
        $participant_id = isset($assignment->participant_id) ? $assignment->participant_id : 'unknown';
        $wave_id = isset($wave->id) ? $wave->id : 'unknown';
        $wave_name = isset($wave->name) ? $wave->name : 'unknown';
        
        // Stage 0 (NUDGE_AVAILABLE) is always sent immediately when wave becomes available
        // This is the INITIAL availability email, NOT a follow-up reminder
        if ((int)$current_stage === self::NUDGE_AVAILABLE) {
            $available_at = isset($assignment->available_at) ? $assignment->available_at : 'not_set';
            error_log("[EIPSI Nudge] CHECK NUDGE 0: assignment_id={$assignment_id}, participant_id={$participant_id}, wave_id={$wave_id}, wave_name={$wave_name}, available_at={$available_at}, result=ALLOWED");
            return true;
        }
        
        // For stages 1-4 (follow-up reminders), check if follow_up_reminders_enabled
        error_log("[EIPSI Nudge] Stage {$current_stage} - checking follow_up_reminders_enabled: " . (isset($wave->follow_up_reminders_enabled) ? $wave->follow_up_reminders_enabled : 'NOT SET'));
        if (empty($wave->follow_up_reminders_enabled)) {
            error_log("[EIPSI Nudge] Stage {$current_stage} - BLOCKED: follow_up_reminders_enabled is empty");
            return false;
        }
        
        // Use custom config if provided (from modal), otherwise use defaults
        if ($custom_config && isset($custom_config[$current_stage])) {
            $config = $custom_config[$current_stage];
            $timing_value = isset($config['hours']) ? intval($config['hours']) : 24;
            $timing_unit = isset($config['unit']) ? $config['unit'] : 'hours';
        } else {
            // Get config from wave's nudge_config JSON
            $nudge_config = isset($wave->nudge_config) ? json_decode($wave->nudge_config, true) : array();
            $nudge_key = "nudge_{$current_stage}";
            if (isset($nudge_config[$nudge_key])) {
                $config = $nudge_config[$nudge_key];
                $timing_value = isset($config['value']) ? intval($config['value']) : 24;
                $timing_unit = isset($config['unit']) ? $config['unit'] : 'hours';
            } else {
                // Fallback to defaults (simplified - no reference_point)
                $config = self::get_nudge_config($current_stage, false);
                if (!$config) {
                    return false;
                }
                $timing_value = $config['timing_days'];
                $timing_unit = 'days';
            }
        }
        
        // Convert to seconds for calculation
        $timing_seconds = self::convert_to_seconds($timing_value, $timing_unit);
        
        $now = current_time('timestamp');
        
        // Calculate based on available date (after wave becomes available)
        // Simplified v2.4.0 - Always use wave_availability, removed reference_point
        $available_ts = strtotime($assignment->available_at);
        $trigger_ts = $available_ts + $timing_seconds;
        
        error_log("[EIPSI Nudge] Stage {$current_stage}: trigger at " . date('Y-m-d H:i:s', $trigger_ts) . " ({$timing_value} {$timing_unit} after available)");
        
        return ($now >= $trigger_ts);
    }
    
    /**
     * Get the next stage to send
     * 
     * @param int $current_stage Current stage (0-4)
     * @return int|null Next stage or null if completed
     */
    public static function get_next_stage($current_stage) {
        $next = $current_stage + 1;
        return ($next <= self::NUDGE_LAST_CALL) ? $next : null;
    }
    
    /**
     * Get human-readable description of a nudge stage
     * 
     * @param int $stage Stage number
     * @param bool $has_due_date Whether wave has due date
     * @return string Description
     */
    public static function get_stage_description($stage, $has_due_date = false) {
        $config = self::get_nudge_config($stage, $has_due_date);
        return $config ? $config['description'] : '';
    }
}
