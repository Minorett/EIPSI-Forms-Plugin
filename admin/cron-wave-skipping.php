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
    
    error_log(sprintf('[EIPSI Wave Skipping] ========== Checking participant %d (study %d) ==========', $participant_id, $study_id));
    
    // Get all assignments for this participant, ordered by wave_index
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.wave_id, a.status, a.available_at, w.wave_index, w.wave_name
         FROM {$wpdb->prefix}survey_assignments a
         JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
         WHERE a.participant_id = %d AND a.study_id = %d 
         ORDER BY w.wave_index ASC",
        $participant_id, $study_id
    ));
    
    if (empty($assignments)) {
        error_log('[EIPSI Wave Skipping] No assignments found for this participant');
        return 0;
    }
    
    // Log current state of all waves
    error_log(sprintf('[EIPSI Wave Skipping] Found %d waves for participant %d:', count($assignments), $participant_id));
    foreach ($assignments as $a) {
        $available_str = $a->available_at ? date('Y-m-d H:i:s', strtotime($a->available_at)) : 'NULL';
        error_log(sprintf('  - Wave %d (%s): status=%s, available_at=%s, assignment_id=%d', 
            $a->wave_index, $a->wave_name, $a->status, $available_str, $a->id));
    }
    
    // Find the highest wave_index that is currently available
    $last_available_index = 0;
    $now = current_time('timestamp');
    $now_str = date('Y-m-d H:i:s', $now);
    
    error_log(sprintf('[EIPSI Wave Skipping] Current time: %s', $now_str));
    
    foreach ($assignments as $a) {
        if ($a->available_at && strtotime($a->available_at) <= $now) {
            $last_available_index = max($last_available_index, $a->wave_index);
            error_log(sprintf('[EIPSI Wave Skipping] Wave %d is AVAILABLE (available_at=%s <= now=%s)', 
                $a->wave_index, date('Y-m-d H:i:s', strtotime($a->available_at)), $now_str));
        } else {
            $reason = !$a->available_at ? 'no available_at set' : 'not yet available';
            error_log(sprintf('[EIPSI Wave Skipping] Wave %d is NOT AVAILABLE (%s)', $a->wave_index, $reason));
        }
    }
    
    error_log(sprintf('[EIPSI Wave Skipping] Last available wave index: %d', $last_available_index));
    
    if ($last_available_index == 0) {
        error_log('[EIPSI Wave Skipping] No waves are available yet - nothing to skip');
        return 0;
    }
    
    // Skip all waves that are:
    // 1. NOT T1 (wave_index > 1) - T1 NEVER gets skipped
    // 2. Skippable status (pending or in_progress)
    // 3. Have wave_index < last_available_index
    $skippable_statuses = array('pending', 'in_progress');
    $skipped_count = 0;
    
    error_log('[EIPSI Wave Skipping] Evaluating which waves to skip...');
    
    foreach ($assignments as $a) {
        // CRITICAL: T1 (wave_index = 1) NEVER gets skipped
        if ($a->wave_index == 1) {
            error_log(sprintf('[EIPSI Wave Skipping] Wave %d (T1): PROTECTED - T1 never gets skipped (status=%s)', 
                $a->wave_index, $a->status));
            continue;
        }
        
        $should_skip = in_array($a->status, $skippable_statuses) && $a->wave_index < $last_available_index;
        
        if ($should_skip) {
            error_log(sprintf('[EIPSI Wave Skipping] Wave %d (%s): WILL SKIP - status=%s (skippable), wave_index=%d < last_available=%d', 
                $a->wave_index, $a->wave_name, $a->status, $a->wave_index, $last_available_index));
        } else {
            $reason = !in_array($a->status, $skippable_statuses) 
                ? "status={$a->status} (not skippable)" 
                : "wave_index={$a->wave_index} >= last_available={$last_available_index}";
            error_log(sprintf('[EIPSI Wave Skipping] Wave %d (%s): NO SKIP - %s', 
                $a->wave_index, $a->wave_name, $reason));
        }
        
        if ($should_skip) {
            
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
                    '[EIPSI Wave Skipping] ✓ SKIPPED: Wave %d (%s) for participant %d (assignment %d) - Nudges cancelled',
                    $a->wave_index, $a->wave_name, $participant_id, $a->id
                ));
                
                $skipped_count++;
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                error_log("[EIPSI Wave Skipping] Error skipping assignment {$a->id}: " . $e->getMessage());
            }
        }
    }
    
    error_log(sprintf('[EIPSI Wave Skipping] ========== Summary: %d waves skipped for participant %d ==========', 
        $skipped_count, $participant_id));
    
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
