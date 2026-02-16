<?php
/**
 * Test file for Longitudinal Study Shortcode
 * 
 * This file contains test cases and examples for the [eipsi_longitudinal_study] shortcode.
 * 
 * @package EIPSI_Forms
 * @since 1.5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test Case 1: Basic Shortcode Rendering
 * 
 * Expected: Study header with name, code, and status
 * Shortcode: [eipsi_longitudinal_study id="1"]
 */
function test_basic_shortcode_rendering() {
    $shortcode = '[eipsi_longitudinal_study id="1"]';
    $output = do_shortcode($shortcode);
    
    // Check for required elements
    $has_study_header = strpos($output, 'eipsi-study-header') !== false;
    $has_waves_section = strpos($output, 'eipsi-waves-section') !== false;
    $has_share_section = strpos($output, 'eipsi-share-section') !== false;
    
    return $has_study_header && $has_waves_section && $has_share_section;
}

/**
 * Test Case 2: Error Handling - Invalid Study ID
 * 
 * Expected: Error message with helpful text
 * Shortcode: [eipsi_longitudinal_study id="99999"]
 */
function test_invalid_study_id() {
    $shortcode = '[eipsi_longitudinal_study id="99999"]';
    $output = do_shortcode($shortcode);
    
    return strpos($output, 'eipsi-longitudinal-study-error') !== false;
}

/**
 * Test Case 3: Error Handling - Missing Study ID
 * 
 * Expected: Error message with example usage
 * Shortcode: [eipsi_longitudinal_study]
 */
function test_missing_study_id() {
    $shortcode = '[eipsi_longitudinal_study]';
    $output = do_shortcode($shortcode);
    
    return strpos($output, 'eipsi-longitudinal-study-error') !== false;
}

/**
 * Test Case 4: Specific Wave Display
 * 
 * Expected: Only wave with index 1 displayed
 * Shortcode: [eipsi_longitudinal_study id="1" wave="1"]
 */
function test_specific_wave_display() {
    $shortcode = '[eipsi_longitudinal_study id="1" wave="1"]';
    $output = do_shortcode($shortcode);
    
    // Check that only one wave card is rendered
    preg_match_all('/class="eipsi-wave-card/', $output, $matches);
    return count($matches[0]) === 1;
}

/**
 * Test Case 5: Hide Configuration
 * 
 * Expected: No configuration section
 * Shortcode: [eipsi_longitudinal_study id="1" show_config="no"]
 */
function test_hide_config() {
    $shortcode = '[eipsi_longitudinal_study id="1" show_config="no"]';
    $output = do_shortcode($shortcode);
    
    return strpos($output, 'eipsi-study-config') === false;
}

/**
 * Test Case 6: Hide Waves
 * 
 * Expected: No waves section
 * Shortcode: [eipsi_longitudinal_study id="1" show_waves="no"]
 */
function test_hide_waves() {
    $shortcode = '[eipsi_longitudinal_study id="1" show_waves="no"]';
    $output = do_shortcode($shortcode);
    
    return strpos($output, 'eipsi-waves-section') === false;
}

/**
 * Test Case 7: Compact Theme
 * 
 * Expected: Container has compact theme class
 * Shortcode: [eipsi_longitudinal_study id="1" theme="compact"]
 */
function test_compact_theme() {
    $shortcode = '[eipsi_longitudinal_study id="1" theme="compact"]';
    $output = do_shortcode($shortcode);
    
    return strpos($output, 'eipsi-theme-compact') !== false;
}

/**
 * Test Case 8: Card Theme
 * 
 * Expected: Container has card theme class
 * Shortcode: [eipsi_longitudinal_study id="1" theme="card"]
 */
function test_card_theme() {
    $shortcode = '[eipsi_longitudinal_study id="1" theme="card"]';
    $output = do_shortcode($shortcode);
    
    return strpos($output, 'eipsi-theme-card') !== false;
}

/**
 * Test Case 9: Time Limit Override
 * 
 * Expected: Display shows overridden time limit
 * Shortcode: [eipsi_longitudinal_study id="1" time_limit="60"]
 */
function test_time_limit_override() {
    $shortcode = '[eipsi_longitudinal_study id="1" time_limit="60"]';
    $output = do_shortcode($shortcode);
    
    // Check for "60 minutos" or "1 hora" in output
    return strpos($output, '60 min') !== false || strpos($output, '1 hora') !== false;
}

/**
 * Test Case 10: Assets Enqueued
 * 
 * Expected: CSS and JS files are enqueued
 */
function test_assets_enqueued() {
    global $wp_styles, $wp_scripts;
    
    // Reset queues
    $wp_styles->queue = array();
    $wp_scripts->queue = array();
    
    // Run shortcode
    do_shortcode('[eipsi_longitudinal_study id="1"]');
    
    // Check if styles are enqueued
    $css_enqueued = wp_style_is('eipsi-longitudinal-study-css', 'enqueued');
    $js_enqueued = wp_script_is('eipsi-longitudinal-study-js', 'enqueued');
    
    return $css_enqueued && $js_enqueued;
}

/**
 * Run all tests
 * 
 * @return array Test results
 */
function run_all_shortcode_tests() {
    $tests = array(
        'Basic Rendering' => test_basic_shortcode_rendering(),
        'Invalid Study ID' => test_invalid_study_id(),
        'Missing Study ID' => test_missing_study_id(),
        'Specific Wave' => test_specific_wave_display(),
        'Hide Config' => test_hide_config(),
        'Hide Waves' => test_hide_waves(),
        'Compact Theme' => test_compact_theme(),
        'Card Theme' => test_card_theme(),
        'Time Limit Override' => test_time_limit_override(),
        'Assets Enqueued' => test_assets_enqueued(),
    );
    
    return $tests;
}

/**
 * Display test results in admin
 */
function display_shortcode_test_results() {
    $results = run_all_shortcode_tests();
    
    echo '<div class="wrap">';
    echo '<h1>Longitudinal Study Shortcode Tests</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Test</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($results as $test_name => $passed) {
        $status = $passed ? 
            '<span style="color: green;">✓ PASS</span>' : 
            '<span style="color: red;">✗ FAIL</span>';
        echo '<tr>';
        echo '<td>' . esc_html($test_name) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    $passed_count = count(array_filter($results));
    $total_count = count($results);
    
    echo '<p><strong>Results:</strong> ' . esc_html($passed_count) . '/' . esc_html($total_count) . ' tests passed</p>';
    echo '</div>';
}

// Example usage in admin:
// add_action('admin_menu', function() {
//     add_submenu_page(
//         'eipsi-longitudinal-study',
//         'Shortcode Tests',
//         'Shortcode Tests',
//         'manage_options',
//         'eipsi-shortcode-tests',
//         'display_shortcode_test_results'
//     );
// });

/**
 * Manual Test Examples
 * 
 * Copy these into a WordPress page to test:
 * 
 * <!-- Test 1: Basic -->
 * [eipsi_longitudinal_study id="1"]
 * 
 * <!-- Test 2: Specific Wave -->
 * [eipsi_longitudinal_study id="1" wave="1"]
 * 
 * <!-- Test 3: Compact Theme -->
 * [eipsi_longitudinal_study id="1" theme="compact"]
 * 
 * <!-- Test 4: Card Theme -->
 * [eipsi_longitudinal_study id="1" theme="card"]
 * 
 * <!-- Test 5: Hide Config -->
 * [eipsi_longitudinal_study id="1" show_config="no"]
 * 
 * <!-- Test 6: Time Limit Override -->
 * [eipsi_longitudinal_study id="1" time_limit="45"]
 * 
 * <!-- Test 7: Combined Attributes -->
 * [eipsi_longitudinal_study id="1" wave="1" theme="compact" show_config="no"]
 */
