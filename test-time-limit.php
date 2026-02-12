<?php
/**
 * Test script for unlimited time functionality
 * 
 * This script tests that the time limit feature works correctly,
 * including the ability to set unlimited time for form completion.
 */

// Bootstrap WordPress
require_once '/var/www/html/wp-load.php';

if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-wave-service.php';

echo "=== Testing Unlimited Time Functionality ===\n\n";

// Test 1: Create a wave with time limit
echo "Test 1: Creating wave with time limit...\n";
$wave_data_with_limit = array(
    'name' => 'Test Wave with Time Limit',
    'wave_index' => 1,
    'form_id' => 1, // Assuming form ID 1 exists
    'has_time_limit' => 1,
    'completion_time_limit' => 30,
    'is_mandatory' => 1
);

$wave_id_with_limit = EIPSI_Wave_Service::create_wave(1, $wave_data_with_limit);

if (is_wp_error($wave_id_with_limit)) {
    echo "❌ Failed: " . $wave_id_with_limit->get_error_message() . "\n";
} else {
    echo "✅ Success: Wave created with ID: " . $wave_id_with_limit . "\n";
    
    // Verify the data
    $wave = EIPSI_Wave_Service::get_wave($wave_id_with_limit);
    echo "   - Has time limit: " . ($wave->has_time_limit ? 'Yes' : 'No') . "\n";
    echo "   - Time limit (minutes): " . ($wave->completion_time_limit ?: 'NULL') . "\n";
}

echo "\n";

// Test 2: Create a wave without time limit (unlimited)
echo "Test 2: Creating wave without time limit (unlimited)...\n";
$wave_data_unlimited = array(
    'name' => 'Test Wave with Unlimited Time',
    'wave_index' => 2,
    'form_id' => 1, // Assuming form ID 1 exists
    'has_time_limit' => 0,
    'completion_time_limit' => null,
    'is_mandatory' => 1
);

$wave_id_unlimited = EIPSI_Wave_Service::create_wave(1, $wave_data_unlimited);

if (is_wp_error($wave_id_unlimited)) {
    echo "❌ Failed: " . $wave_id_unlimited->get_error_message() . "\n";
} else {
    echo "✅ Success: Wave created with ID: " . $wave_id_unlimited . "\n";
    
    // Verify the data
    $wave = EIPSI_Wave_Service::get_wave($wave_id_unlimited);
    echo "   - Has time limit: " . ($wave->has_time_limit ? 'Yes' : 'No') . "\n";
    echo "   - Time limit (minutes): " . ($wave->completion_time_limit ?: 'NULL (unlimited)') . "\n";
}

echo "\n";

// Test 3: Update wave from limited to unlimited
echo "Test 3: Updating wave from limited to unlimited time...\n";
$update_data = array(
    'has_time_limit' => 0,
    'completion_time_limit' => null
);

$update_result = EIPSI_Wave_Service::update_wave($wave_id_with_limit, $update_data);

if (is_wp_error($update_result)) {
    echo "❌ Failed: " . $update_result->get_error_message() . "\n";
} else {
    echo "✅ Success: Wave updated\n";
    
    // Verify the update
    $updated_wave = EIPSI_Wave_Service::get_wave($wave_id_with_limit);
    echo "   - Has time limit: " . ($updated_wave->has_time_limit ? 'Yes' : 'No') . "\n";
    echo "   - Time limit (minutes): " . ($updated_wave->completion_time_limit ?: 'NULL (unlimited)') . "\n";
}

echo "\n";

// Test 4: Update wave from unlimited to limited
echo "Test 4: Updating wave from unlimited to limited time...\n";
$update_data2 = array(
    'has_time_limit' => 1,
    'completion_time_limit' => 45
);

$update_result2 = EIPSI_Wave_Service::update_wave($wave_id_unlimited, $update_data2);

if (is_wp_error($update_result2)) {
    echo "❌ Failed: " . $update_result2->get_error_message() . "\n";
} else {
    echo "✅ Success: Wave updated\n";
    
    // Verify the update
    $updated_wave2 = EIPSI_Wave_Service::get_wave($wave_id_unlimited);
    echo "   - Has time limit: " . ($updated_wave2->has_time_limit ? 'Yes' : 'No') . "\n";
    echo "   - Time limit (minutes): " . ($updated_wave2->completion_time_limit ?: 'NULL') . "\n";
}

echo "\n=== Test Summary ===\n";
echo "All tests completed. Check the output above for any failures.\n";
echo "The unlimited time functionality allows researchers to create waves without time constraints.\n";

// Cleanup
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->prefix}survey_waves WHERE name LIKE 'Test Wave%'");

echo "\nCleanup completed.\n";