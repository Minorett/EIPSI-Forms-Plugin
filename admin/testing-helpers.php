<?php
/**
 * EIPSI Forms - Testing Helpers (Phase 5 T1-Anchor)
 * 
 * Utilities for testing longitudinal wave systems by simulating time travel.
 * 
 * SECURITY: Only available in WP_DEBUG mode
 * 
 * @package EIPSI_Forms
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simulate time travel by moving all relevant timestamps
 * 
 * WARNING: This modifies production data. Only use in testing environments.
 * 
 * Usage:
 *   eipsi_simulate_time_travel('+7 days');     // Move 7 days forward
 *   eipsi_simulate_time_travel('-3 days');     // Move 3 days backward
 *   eipsi_simulate_time_travel('+2 hours');    // Move 2 hours forward
 * 
 * @param string $interval PHP strtotime-compatible interval (e.g., '+7 days', '-3 hours')
 * @param int|null $study_id Optional: Only affect specific study
 * @param int|null $participant_id Optional: Only affect specific participant
 * @return array Statistics of affected rows
 */
function eipsi_simulate_time_travel($interval, $study_id = null, $participant_id = null) {
    global $wpdb;
    
    // Security check
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        error_log('[EIPSI Time Travel] BLOCKED: Only available in WP_DEBUG mode');
        return array('error' => 'Time travel only available in WP_DEBUG mode');
    }
    
    // Parse interval
    $seconds = strtotime($interval, 0);
    if ($seconds === false) {
        error_log("[EIPSI Time Travel] Invalid interval: {$interval}");
        return array('error' => 'Invalid interval format');
    }
    
    $direction = $seconds >= 0 ? '+' : '-';
    $abs_seconds = abs($seconds);
    
    error_log(sprintf(
        '[EIPSI Time Travel] Starting time travel: %s (%s%d seconds) for study_id=%s, participant_id=%s',
        $interval,
        $direction,
        $abs_seconds,
        $study_id ?? 'ALL',
        $participant_id ?? 'ALL'
    ));
    
    $stats = array(
        'interval' => $interval,
        'seconds' => $seconds,
        'affected' => array()
    );
    
    // Build WHERE clause for filtering
    $where_study = $study_id ? $wpdb->prepare('AND study_id = %d', $study_id) : '';
    $where_participant = $participant_id ? $wpdb->prepare('AND participant_id = %d', $participant_id) : '';
    
    // 1. Move survey_assignments timestamps
    $tables_to_update = array(
        'survey_assignments' => array(
            'columns' => array('available_at', 't1_completed_at', 'submitted_at', 'created_at', 'updated_at', 'assigned_at', 'first_viewed_at', 'last_nudge_sent_at', 'last_reminder_sent', 'last_retry_sent', 'due_at'),
            'where' => "1=1 {$where_study} {$where_participant}"
        ),
        'survey_participants' => array(
            'columns' => array('created_at', 'last_login_at', 'consent_decided_at', 'updated_at'),
            'where' => $participant_id ? $wpdb->prepare('id = %d', $participant_id) : '1=1'
        ),
        'survey_weekly_reminders' => array(
            'columns' => array('sent_at', 'created_at'),
            'where' => $participant_id ? 
                "assignment_id IN (SELECT id FROM {$wpdb->prefix}survey_assignments WHERE participant_id = {$participant_id})" : 
                '1=1'
        ),
        'survey_job_queue' => array(
            'columns' => array('scheduled_for', 'created_at', 'executed_at'),
            'where' => $study_id ? $wpdb->prepare('study_id = %d', $study_id) : '1=1'
        ),
        'survey_email_log' => array(
            'columns' => array('sent_at', 'created_at'),
            'where' => $study_id ? $wpdb->prepare('study_id = %d', $study_id) : '1=1'
        ),
        'survey_sessions' => array(
            'columns' => array('created_at', 'last_activity', 'expires_at'),
            'where' => $participant_id ? $wpdb->prepare('participant_id = %d', $participant_id) : '1=1'
        ),
        'survey_magic_links' => array(
            'columns' => array('created_at', 'expires_at', 'used_at'),
            'where' => $participant_id ? $wpdb->prepare('participant_id = %d', $participant_id) : '1=1'
        )
    );
    
    foreach ($tables_to_update as $table => $config) {
        $full_table = $wpdb->prefix . $table;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'");
        if (!$table_exists) {
            error_log("[EIPSI Time Travel] Table {$full_table} does not exist, skipping");
            continue;
        }
        
        $affected = 0;
        
        foreach ($config['columns'] as $column) {
            // Check if column exists
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$full_table} LIKE '{$column}'");
            if (!$column_exists) {
                continue;
            }
            
            // Build UPDATE query
            $sql = "UPDATE {$full_table} 
                    SET {$column} = DATE_ADD({$column}, INTERVAL {$abs_seconds} SECOND)
                    WHERE {$config['where']} 
                      AND {$column} IS NOT NULL";
            
            // Adjust direction
            if ($direction === '-') {
                $sql = str_replace('DATE_ADD', 'DATE_SUB', $sql);
            }
            
            $result = $wpdb->query($sql);
            
            if ($result !== false) {
                $affected += $result;
            }
        }
        
        $stats['affected'][$table] = $affected;
        error_log("[EIPSI Time Travel] Updated {$affected} rows in {$table}");
    }
    
    // 2. Move WP Cron scheduled events (for nudges and reminders)
    eipsi_time_travel_wp_cron($seconds);
    
    $total_affected = array_sum($stats['affected']);
    error_log(sprintf(
        '[EIPSI Time Travel] Completed: %d total rows affected across %d tables',
        $total_affected,
        count($stats['affected'])
    ));
    
    return $stats;
}

/**
 * Move WP Cron scheduled events in time
 * 
 * @param int $seconds Seconds to move (positive = future, negative = past)
 */
function eipsi_time_travel_wp_cron($seconds) {
    $crons = _get_cron_array();
    
    if (empty($crons)) {
        return;
    }
    
    $new_crons = array();
    $moved_count = 0;
    
    foreach ($crons as $timestamp => $cron) {
        $new_timestamp = $timestamp + $seconds;
        
        // Only move EIPSI-related cron jobs
        $is_eipsi = false;
        foreach ($cron as $hook => $events) {
            if (strpos($hook, 'eipsi_') === 0) {
                $is_eipsi = true;
                break;
            }
        }
        
        if ($is_eipsi) {
            $new_crons[$new_timestamp] = $cron;
            $moved_count++;
        } else {
            $new_crons[$timestamp] = $cron;
        }
    }
    
    _set_cron_array($new_crons);
    
    error_log("[EIPSI Time Travel] Moved {$moved_count} WP Cron events");
}

/**
 * Reset time travel for a study or participant
 * 
 * WARNING: This cannot truly "undo" time travel. It resets to current time.
 * 
 * @param int|null $study_id Optional: Reset specific study
 * @param int|null $participant_id Optional: Reset specific participant
 */
function eipsi_reset_time_travel($study_id = null, $participant_id = null) {
    global $wpdb;
    
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        error_log('[EIPSI Time Travel] BLOCKED: Only available in WP_DEBUG mode');
        return array('error' => 'Time travel only available in WP_DEBUG mode');
    }
    
    error_log('[EIPSI Time Travel] WARNING: Reset cannot truly undo time travel. Use with caution.');
    
    // This is a placeholder - true reset would require storing original timestamps
    // For now, just log a warning
    return array(
        'warning' => 'Reset not implemented. Time travel changes are permanent unless you restore from backup.'
    );
}

/**
 * Get time travel status for debugging
 * 
 * @param int|null $study_id Optional: Check specific study
 * @param int|null $participant_id Optional: Check specific participant
 * @return array Status information
 */
function eipsi_get_time_travel_status($study_id = null, $participant_id = null) {
    global $wpdb;
    
    $where_study = $study_id ? $wpdb->prepare('AND study_id = %d', $study_id) : '';
    $where_participant = $participant_id ? $wpdb->prepare('AND participant_id = %d', $participant_id) : '';
    
    // Get earliest and latest timestamps
    $status = array(
        'current_time' => current_time('mysql'),
        'assignments' => array()
    );
    
    $assignments = $wpdb->get_results("
        SELECT 
            id,
            participant_id,
            wave_index,
            status,
            available_at,
            t1_completed_at,
            submitted_at,
            created_at
        FROM {$wpdb->prefix}survey_assignments
        WHERE 1=1 {$where_study} {$where_participant}
        ORDER BY participant_id, wave_index
    ");
    
    foreach ($assignments as $a) {
        $status['assignments'][] = array(
            'id' => $a->id,
            'participant_id' => $a->participant_id,
            'wave_index' => $a->wave_index,
            'status' => $a->status,
            'available_at' => $a->available_at,
            't1_completed_at' => $a->t1_completed_at,
            'submitted_at' => $a->submitted_at,
            'created_at' => $a->created_at,
            'is_available' => $a->available_at && strtotime($a->available_at) <= time()
        );
    }
    
    return $status;
}

/**
 * Manual cron trigger for testing (bypasses WP Cron schedule)
 * 
 * @param string $hook Cron hook name (e.g., 'eipsi_wave_skipping_cron')
 */
function eipsi_trigger_cron_manually($hook) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        error_log('[EIPSI Manual Cron] BLOCKED: Only available in WP_DEBUG mode');
        return array('error' => 'Manual cron only available in WP_DEBUG mode');
    }
    
    error_log("[EIPSI Manual Cron] Triggering: {$hook}");
    
    do_action($hook);
    
    error_log("[EIPSI Manual Cron] Completed: {$hook}");
    
    return array('success' => true, 'hook' => $hook);
}

// Register admin page for time travel UI (only in WP_DEBUG)
if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
    add_action('admin_menu', 'eipsi_register_time_travel_page');
}

function eipsi_register_time_travel_page() {
    add_submenu_page(
        'eipsi-forms',
        'Time Travel (Testing)',
        '⏰ Time Travel',
        'manage_options',
        'eipsi-time-travel',
        'eipsi_render_time_travel_page'
    );
}

function eipsi_render_time_travel_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    // Handle form submission
    if (isset($_POST['eipsi_time_travel_submit'])) {
        check_admin_referer('eipsi_time_travel');
        
        $interval = sanitize_text_field($_POST['interval']);
        $study_id = !empty($_POST['study_id']) ? intval($_POST['study_id']) : null;
        $participant_id = !empty($_POST['participant_id']) ? intval($_POST['participant_id']) : null;
        
        $result = eipsi_simulate_time_travel($interval, $study_id, $participant_id);
        
        echo '<div class="notice notice-success"><p>Time travel executed: ' . esc_html($interval) . '</p></div>';
        echo '<pre>' . esc_html(print_r($result, true)) . '</pre>';
    }
    
    ?>
    <div class="wrap">
        <h1>⏰ EIPSI Time Travel (Testing Only)</h1>
        
        <div class="notice notice-warning">
            <p><strong>WARNING:</strong> This tool modifies production data. Only use in testing environments.</p>
            <p>Time travel moves all timestamps (assignments, emails, cron jobs) by the specified interval.</p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('eipsi_time_travel'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="interval">Time Interval</label></th>
                    <td>
                        <input type="text" name="interval" id="interval" value="+7 days" class="regular-text" />
                        <p class="description">Examples: "+7 days", "-3 hours", "+2 weeks"</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="study_id">Study ID (optional)</label></th>
                    <td>
                        <input type="number" name="study_id" id="study_id" class="small-text" />
                        <p class="description">Leave empty to affect all studies</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="participant_id">Participant ID (optional)</label></th>
                    <td>
                        <input type="number" name="participant_id" id="participant_id" class="small-text" />
                        <p class="description">Leave empty to affect all participants</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="eipsi_time_travel_submit" class="button button-primary" value="Execute Time Travel" />
            </p>
        </form>
        
        <hr>
        
        <h2>Manual Cron Triggers</h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=eipsi-time-travel&trigger=wave_skipping'); ?>" class="button">Trigger Wave Skipping</a>
            <a href="<?php echo admin_url('admin.php?page=eipsi-time-travel&trigger=weekly_reminders'); ?>" class="button">Trigger Weekly Reminders</a>
        </p>
        
        <?php
        if (isset($_GET['trigger'])) {
            $trigger = sanitize_text_field($_GET['trigger']);
            $hook_map = array(
                'wave_skipping' => 'eipsi_wave_skipping_cron',
                'weekly_reminders' => 'eipsi_weekly_t1_reminders_cron'
            );
            
            if (isset($hook_map[$trigger])) {
                $result = eipsi_trigger_cron_manually($hook_map[$trigger]);
                echo '<div class="notice notice-success"><p>Cron triggered: ' . esc_html($hook_map[$trigger]) . '</p></div>';
            }
        }
        ?>
    </div>
    <?php
}
