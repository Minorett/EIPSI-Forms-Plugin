<?php
/**
 * EIPSI Forms - Wave Skipping Cron Job (Phase 5 T1-Anchor)
 * 
 * Marks waves as 'skipped' if:
 * - A subsequent wave has become available
 * - The participant hasn't completed the prior wave
 * 
 * Example: If T4 is available but T3 is still pending/in_progress, T3 gets skipped.
 * 
 * Runs: Hourly via WP Cron
 * 
 * @package EIPSI_Forms
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main cron function - iterates all participants with pending assignments
 */
function eipsi_check_wave_skipping() {
    global $wpdb;
    
    error_log('[EIPSI Wave Skipping] Starting cron job');
    
    // Get all participants with at least one pending assignment
    $participants = $wpdb->get_results("
        SELECT DISTINCT participant_id, study_id 
        FROM {$wpdb->prefix}survey_assignments 
        WHERE status IN ('pending', 'in_progress')
    ");
    
    if (empty($participants)) {
        error_log('[EIPSI Wave Skipping] No participants with pending assignments');
        return;
    }
    
    error_log(sprintf('[EIPSI Wave Skipping] Processing %d participants', count($participants)));
    
    $total_skipped = 0;
    
    foreach ($participants as $p) {
        $skipped = eipsi_check_wave_skipping_for_participant($p->participant_id, $p->study_id);
        $total_skipped += $skipped;
    }
    
    error_log(sprintf('[EIPSI Wave Skipping] Cron completed: %d waves skipped across %d participants', 
        $total_skipped, count($participants)));
}

/**
 * Check and skip waves for a single participant
 * 
 * @param int $participant_id Participant ID
 * @param int $study_id Study ID
 * @return int Number of waves skipped
 */
function eipsi_check_wave_skipping_for_participant($participant_id, $study_id) {
    global $wpdb;
    
    // Get all assignments for this participant, ordered by wave_index
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.wave_id, a.status, a.available_at, w.wave_index
         FROM {$wpdb->prefix}survey_assignments a
         JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
         WHERE a.participant_id = %d AND a.study_id = %d 
         ORDER BY w.wave_index ASC",
        $participant_id, $study_id
    ));
    
    if (empty($assignments)) {
        return 0;
    }
    
    // Find the highest wave_index that is currently available
    $last_available_index = 0;
    $now = current_time('timestamp');
    
    foreach ($assignments as $a) {
        if ($a->available_at && strtotime($a->available_at) <= $now) {
            $last_available_index = max($last_available_index, $a->wave_index);
        }
    }
    
    if ($last_available_index == 0) {
        // No waves are available yet
        return 0;
    }
    
    // Skip all waves that are:
    // 1. Skippable status (pending or in_progress)
    // 2. Have wave_index < last_available_index
    $skippable_statuses = array('pending', 'in_progress');
    $skipped_count = 0;
    
    foreach ($assignments as $a) {
        if (in_array($a->status, $skippable_statuses) && 
            $a->wave_index < $last_available_index) {
            
            // Skip this wave with transaction and lock
            $wpdb->query('START TRANSACTION');
            
            try {
                // Lock the assignment row
                $locked = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, status FROM {$wpdb->prefix}survey_assignments 
                     WHERE id = %d FOR UPDATE",
                    $a->id
                ));
                
                if (!$locked) {
                    throw new Exception("Assignment {$a->id} not found");
                }
                
                // Double-check status (may have changed since initial query)
                if (!in_array($locked->status, $skippable_statuses)) {
                    error_log("[EIPSI Wave Skipping] Assignment {$a->id} status changed to '{$locked->status}', skipping");
                    $wpdb->query('ROLLBACK');
                    continue;
                }
                
                // Mark as skipped
                $updated = $wpdb->update(
                    $wpdb->prefix . 'survey_assignments',
                    array('status' => 'skipped'),
                    array('id' => $a->id),
                    array('%s'),
                    array('%d')
                );
                
                if ($updated === false) {
                    throw new Exception("Failed to update assignment {$a->id}: " . $wpdb->last_error);
                }
                
                // Cancel pending nudges for this assignment
                if (class_exists('EIPSI_Nudge_Event_Scheduler')) {
                    EIPSI_Nudge_Event_Scheduler::cancel_nudges_for_assignment($a->id);
                }
                
                $wpdb->query('COMMIT');
                
                error_log(sprintf(
                    '[EIPSI Wave Skipping] Skipped wave %d for participant %d (assignment %d)',
                    $a->wave_index, $participant_id, $a->id
                ));
                
                $skipped_count++;
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                error_log("[EIPSI Wave Skipping] Error skipping assignment {$a->id}: " . $e->getMessage());
            }
        }
    }
    
    return $skipped_count;
}

/**
 * Register the cron job
 * Called from eipsi-forms.php on plugin activation
 */
function eipsi_schedule_wave_skipping_cron() {
    if (!wp_next_scheduled('eipsi_wave_skipping_cron')) {
        wp_schedule_event(time(), 'hourly', 'eipsi_wave_skipping_cron');
        error_log('[EIPSI Wave Skipping] Cron job scheduled (hourly)');
    }
}

/**
 * Unregister the cron job
 * Called from eipsi-forms.php on plugin deactivation
 */
function eipsi_unschedule_wave_skipping_cron() {
    $timestamp = wp_next_scheduled('eipsi_wave_skipping_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'eipsi_wave_skipping_cron');
        error_log('[EIPSI Wave Skipping] Cron job unscheduled');
    }
}

// Hook the cron action
add_action('eipsi_wave_skipping_cron', 'eipsi_check_wave_skipping');
