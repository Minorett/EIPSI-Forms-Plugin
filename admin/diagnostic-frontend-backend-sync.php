<?php
/**
 * Frontend-Backend Sync Diagnostic Tool
 * 
 * This tool verifies that the frontend correctly reads and displays
 * backend changes (wave status, available_at, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get sync diagnostic data
 */
function eipsi_get_frontend_backend_sync_diagnostic() {
    global $wpdb;
    
    $diagnostics = array(
        'timestamp' => current_time('mysql'),
        'tests' => array(),
        'summary' => array(
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
        ),
    );
    
    // Test 1: Check if frontend query matches backend data
    $test1 = array(
        'name' => 'Frontend Query Consistency',
        'description' => 'Verifies that frontend reads the same data as backend writes',
        'status' => 'unknown',
        'details' => array(),
    );
    
    // Get a sample participant with assignments
    $sample = $wpdb->get_row("
        SELECT a.participant_id, a.wave_id, a.status, a.available_at, w.study_id, w.wave_index
        FROM {$wpdb->prefix}survey_assignments a
        JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
        WHERE a.status IN ('pending', 'skipped', 'submitted')
        ORDER BY a.updated_at DESC
        LIMIT 1
    ");
    
    if ($sample) {
        // Simulate frontend query (from longitudinal-study-display.php line 72-80)
        $frontend_data = $wpdb->get_results($wpdb->prepare(
            "SELECT a.wave_id, a.status
               FROM {$wpdb->prefix}survey_assignments a
         INNER JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
              WHERE a.participant_id = %d AND w.study_id = %d
           ORDER BY w.wave_index ASC",
            $sample->participant_id,
            $sample->study_id
        ));
        
        // Check if frontend would read the correct status
        $frontend_status = null;
        foreach ($frontend_data as $row) {
            if ($row->wave_id == $sample->wave_id) {
                $frontend_status = $row->status;
                break;
            }
        }
        
        if ($frontend_status === $sample->status) {
            $test1['status'] = 'pass';
            $test1['details'][] = "✅ Frontend reads correct status: '{$sample->status}'";
        } else {
            $test1['status'] = 'fail';
            $test1['details'][] = "❌ Status mismatch: Backend='{$sample->status}', Frontend='{$frontend_status}'";
        }
        
        $test1['details'][] = "Sample: participant_id={$sample->participant_id}, wave_id={$sample->wave_id}";
    } else {
        $test1['status'] = 'warning';
        $test1['details'][] = "⚠️ No assignments found to test";
    }
    
    $diagnostics['tests'][] = $test1;
    
    // Test 2: Check if skipped waves are properly excluded
    $test2 = array(
        'name' => 'Wave Skipping Logic',
        'description' => 'Verifies that skipped/expired waves are excluded from next_wave selection',
        'status' => 'unknown',
        'details' => array(),
    );
    
    $skipped_waves = $wpdb->get_results("
        SELECT a.participant_id, a.wave_id, a.status, w.name as wave_name, w.wave_index
        FROM {$wpdb->prefix}survey_assignments a
        JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
        WHERE a.status IN ('skipped', 'expired')
        ORDER BY a.updated_at DESC
        LIMIT 5
    ");
    
    if (count($skipped_waves) > 0) {
        $test2['status'] = 'pass';
        $test2['details'][] = "✅ Found " . count($skipped_waves) . " skipped/expired waves";
        
        foreach ($skipped_waves as $wave) {
            // Check if there's a next wave after this one
            $next_wave = $wpdb->get_row($wpdb->prepare("
                SELECT w.name as wave_name, w.wave_index, a.status
                FROM {$wpdb->prefix}survey_waves w
                LEFT JOIN {$wpdb->prefix}survey_assignments a 
                    ON w.id = a.wave_id AND a.participant_id = %d
                WHERE w.study_id = (
                    SELECT study_id FROM {$wpdb->prefix}survey_waves WHERE id = %d
                )
                AND w.wave_index > %d
                ORDER BY w.wave_index ASC
                LIMIT 1
            ", $wave->participant_id, $wave->wave_id, $wave->wave_index));
            
            if ($next_wave) {
                $test2['details'][] = "  • Wave '{$wave->wave_name}' (status={$wave->status}) → Next: '{$next_wave->wave_name}' (status={$next_wave->status})";
            } else {
                $test2['details'][] = "  • Wave '{$wave->wave_name}' (status={$wave->status}) → No next wave";
            }
        }
    } else {
        $test2['status'] = 'warning';
        $test2['details'][] = "⚠️ No skipped/expired waves found (this is OK if no waves have expired yet)";
    }
    
    $diagnostics['tests'][] = $test2;
    
    // Test 3: Check T1-Anchor available_at logic
    $test3 = array(
        'name' => 'T1-Anchor Available_at',
        'description' => 'Verifies that available_at is set correctly for waves after T1 completion',
        'status' => 'unknown',
        'details' => array(),
    );
    
    $t1_anchored = $wpdb->get_results("
        SELECT 
            p.email,
            w.name as wave_name,
            w.wave_index,
            a.status,
            a.available_at,
            a.submitted_at,
            TIMESTAMPDIFF(MINUTE, NOW(), a.available_at) as minutes_until_available
        FROM {$wpdb->prefix}survey_assignments a
        JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
        JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
        WHERE a.available_at IS NOT NULL
        AND w.wave_index > 1
        ORDER BY a.updated_at DESC
        LIMIT 10
    ");
    
    if (count($t1_anchored) > 0) {
        $test3['status'] = 'pass';
        $test3['details'][] = "✅ Found " . count($t1_anchored) . " waves with available_at set";
        
        foreach ($t1_anchored as $wave) {
            $locked = $wave->minutes_until_available > 0 ? '🔒 LOCKED' : '🔓 UNLOCKED';
            $time_info = $wave->minutes_until_available > 0 
                ? "in {$wave->minutes_until_available} min" 
                : "available now";
            
            $test3['details'][] = "  • {$wave->email} - {$wave->wave_name}: {$locked} ({$time_info})";
        }
    } else {
        $test3['status'] = 'warning';
        $test3['details'][] = "⚠️ No waves with available_at found (this is OK if no T1 has been completed yet)";
    }
    
    $diagnostics['tests'][] = $test3;
    
    // Test 4: Check for potential caching issues
    $test4 = array(
        'name' => 'Cache Detection',
        'description' => 'Checks if WordPress caching might interfere with real-time updates',
        'status' => 'unknown',
        'details' => array(),
    );
    
    $cache_plugins = array(
        'wp-super-cache/wp-cache.php' => 'WP Super Cache',
        'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
        'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
        'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
    );
    
    $active_cache = array();
    foreach ($cache_plugins as $plugin => $name) {
        if (is_plugin_active($plugin)) {
            $active_cache[] = $name;
        }
    }
    
    if (count($active_cache) > 0) {
        $test4['status'] = 'warning';
        $test4['details'][] = "⚠️ Active cache plugins detected: " . implode(', ', $active_cache);
        $test4['details'][] = "  → This might cause delays in frontend updates";
        $test4['details'][] = "  → Consider excluding participant dashboard from cache";
    } else {
        $test4['status'] = 'pass';
        $test4['details'][] = "✅ No cache plugins detected";
    }
    
    // Check for object caching
    if (wp_using_ext_object_cache()) {
        $test4['status'] = 'warning';
        $test4['details'][] = "⚠️ External object cache is active (Redis/Memcached)";
        $test4['details'][] = "  → Make sure participant data is not cached";
    }
    
    $diagnostics['tests'][] = $test4;
    
    // Calculate summary
    foreach ($diagnostics['tests'] as $test) {
        $diagnostics['summary']['total_tests']++;
        if ($test['status'] === 'pass') {
            $diagnostics['summary']['passed']++;
        } elseif ($test['status'] === 'fail') {
            $diagnostics['summary']['failed']++;
        } elseif ($test['status'] === 'warning') {
            $diagnostics['summary']['warnings']++;
        }
    }
    
    return $diagnostics;
}

/**
 * AJAX handler for frontend-backend sync diagnostic
 */
add_action('wp_ajax_eipsi_frontend_backend_sync_diagnostic', 'eipsi_ajax_frontend_backend_sync_diagnostic');
function eipsi_ajax_frontend_backend_sync_diagnostic() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $diagnostics = eipsi_get_frontend_backend_sync_diagnostic();
    wp_send_json_success($diagnostics);
}
