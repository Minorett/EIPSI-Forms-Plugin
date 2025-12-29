<?php
/**
 * TEST: Privacy Configuration Per Form
 *
 * Este test verifica que la configuración de privacidad por formulario funciona correctamente.
 *
 * Ejecutar con: php test-privacy-per-form.php
 */

require_once __DIR__ . '/admin/privacy-config.php';

echo "========================================\n";
echo "TEST: Privacy Configuration Per Form\n";
echo "========================================\n\n";

// Test 1: get_privacy_config sin form_id
echo "Test 1: get_privacy_config() sin form_id\n";
$config = get_privacy_config();
if (is_array($config)) {
    echo "✅ Returns array\n";
} else {
    echo "❌ FAIL: Does not return array\n";
}
echo "\n";

// Test 2: get_privacy_config con form_id que no existe
echo "Test 2: get_privacy_config('NONEXISTENT') sin config específica\n";
$config = get_privacy_config('NONEXISTENT');
if (is_array($config) && isset($config['ip_address'])) {
    echo "✅ Returns array with defaults\n";
    echo "   IP Address: " . ($config['ip_address'] ? 'ON' : 'OFF') . "\n";
} else {
    echo "❌ FAIL: Does not return array or missing ip_address\n";
}
echo "\n";

// Test 3: save_privacy_config
echo "Test 3: save_privacy_config('TEST_FORM_ID')\n";
$test_config = array(
    'ip_address' => true,
    'browser' => true,
    'screen_width' => false,
    'therapeutic_engagement' => true,
    'avoidance_patterns' => false,
    'device_type' => true,
    'quality_flag' => true
);
$result = save_privacy_config('TEST_FORM_ID', $test_config);
if ($result) {
    echo "✅ Config saved\n";
} else {
    echo "❌ FAIL: Config not saved\n";
}
echo "\n";

// Test 4: get_privacy_config con config específica guardada
echo "Test 4: get_privacy_config('TEST_FORM_ID') con config específica\n";
$config = get_privacy_config('TEST_FORM_ID');
if (is_array($config)) {
    echo "✅ Returns array\n";
    echo "   IP Address: " . ($config['ip_address'] ? 'ON' : 'OFF') . "\n";
    echo "   Browser: " . ($config['browser'] ? 'ON' : 'OFF') . "\n";
    echo "   Screen Width: " . ($config['screen_width'] ? 'ON' : 'OFF') . "\n";
    echo "   Therapeutic Engagement: " . ($config['therapeutic_engagement'] ? 'ON' : 'OFF') . "\n";
    echo "   Avoidance Patterns: " . ($config['avoidance_patterns'] ? 'ON' : 'OFF') . "\n";
    echo "   Device Type: " . ($config['device_type'] ? 'ON' : 'OFF') . "\n";
    echo "   Quality Flag: " . ($config['quality_flag'] ? 'ON' : 'OFF') . "\n";

    // Verify values match
    if (
        $config['ip_address'] === true &&
        $config['browser'] === true &&
        $config['screen_width'] === false &&
        $config['therapeutic_engagement'] === true &&
        $config['avoidance_patterns'] === false &&
        $config['device_type'] === true &&
        $config['quality_flag'] === true
    ) {
        echo "✅ All values match expected\n";
    } else {
        echo "❌ FAIL: Values do not match expected\n";
    }
} else {
    echo "❌ FAIL: Does not return array\n";
}
echo "\n";

// Test 5: get_global_privacy_defaults
echo "Test 5: get_global_privacy_defaults()\n";
$global_config = get_global_privacy_defaults();
if (is_array($global_config)) {
    echo "✅ Returns array\n";
    if (isset($global_config['ip_address']) && isset($global_config['therapeutic_engagement'])) {
        echo "✅ Has required keys\n";
    } else {
        echo "❌ FAIL: Missing required keys\n";
    }
} else {
    echo "❌ FAIL: Does not return array\n";
}
echo "\n";

// Test 6: Override por formulario
echo "Test 6: Override por formulario\n";
$global_config = get_global_privacy_defaults();
$form_config = get_privacy_config('TEST_FORM_ID');

if ($global_config['therapeutic_engagement'] !== $form_config['avoidance_patterns']) {
    echo "✅ Form config differs from global (test uses avoid=false)\n";
} else {
    echo "ℹ️  Form config matches global (may be same values)\n";
}
echo "\n";

// Test 7: Test de campos específicos
echo "Test 7: Verificar campos específicos en config por formulario\n";
$test_form_config = array(
    'ip_address' => false,
    'browser' => true,
    'screen_width' => true,
    'therapeutic_engagement' => false,
    'avoidance_patterns' => false,
    'device_type' => false,
    'quality_flag' => false
);
save_privacy_config('TEST_FORM_ID_2', $test_form_config);
$config = get_privacy_config('TEST_FORM_ID_2');

$all_correct = true;
foreach ($test_form_config as $key => $expected) {
    if ($config[$key] !== $expected) {
        echo "❌ FAIL: {$key} expected " . ($expected ? 'ON' : 'OFF') . " but got " . ($config[$key] ? 'ON' : 'OFF') . "\n";
        $all_correct = false;
    }
}

if ($all_correct) {
    echo "✅ All fields saved and retrieved correctly\n";
}
echo "\n";

echo "========================================\n";
echo "TEST COMPLETE\n";
echo "========================================\n";

// Cleanup
delete_option("eipsi_privacy_config_TEST_FORM_ID");
delete_option("eipsi_privacy_config_TEST_FORM_ID_2");

echo "\nCleanup completed (test options deleted)\n";
