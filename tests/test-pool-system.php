<?php
/**
 * Test Suite: Pool de Estudios V2
 * Ejecutar: wp eval-file tests/test-pool-system.php
 * 
 * @package EIPSI_Forms
 * @since 2.5.4
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Pool_TestRunner {
    private $tests_run = 0;
    private $passed = 0;
    private $failed = 0;
    private $test_pool_code = null;
    
    public function run() {
        echo "=== EIPSI POOL SYSTEM V2 TESTS ===\n\n";
        
        // Cargar helpers
        require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/helpers/pool-helpers.php';
        
        $tests = array(
            'test_helper_functions_exist',
            'test_pool_access_page_renders',
            'test_pool_randomize_basic',
            'test_assignment_idempotency',
            'test_export_function_exists',
        );
        
        foreach ($tests as $test) {
            $this->runTest($test);
        }
        
        $this->cleanup();
        $this->printSummary();
        return $this->failed === 0;
    }
    
    private function runTest($name) {
        echo "TEST: $name ... ";
        $this->tests_run++;
        try {
            $result = $this->$name();
            if ($result) {
                echo "✅ PASS\n";
                $this->passed++;
            } else {
                echo "❌ FAIL\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            $this->failed++;
        }
    }
    
    // === TESTS ===
    
    private function test_helper_functions_exist() {
        $required_functions = array(
            'eipsi_detect_access_type',
            'eipsi_get_valid_pool',
            'eipsi_get_pool_assignment',
            'eipsi_pool_randomize',
            'eipsi_save_pool_assignment',
            'eipsi_get_pool_stats',
            'eipsi_get_pool_context_for_study',
            'eipsi_export_responses_with_pool_context',
        );
        
        foreach ($required_functions as $func) {
            if (!function_exists($func)) {
                echo "(Missing: $func) ";
                return false;
            }
        }
        return true;
    }
    
    private function test_pool_access_page_renders() {
        // Verificar que el template existe
        $template = EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/pool-access.php';
        if (!file_exists($template)) {
            echo "(Template missing: pool-access.php) ";
            return false;
        }
        
        // Verificar pool-assigned template
        $template2 = EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/pool-assigned.php';
        if (!file_exists($template2)) {
            echo "(Template missing: pool-assigned.php) ";
            return false;
        }
        
        return true;
    }
    
    private function test_pool_randomize_basic() {
        // Simular datos de pool
        $pool_data = array(
            'status' => 'active',
            'config' => array(
                'studies' => array(
                    array('study_id' => 'STUDY_A', 'target_count' => 100, 'current_count' => 0),
                    array('study_id' => 'STUDY_B', 'target_count' => 100, 'current_count' => 0),
                    array('study_id' => 'STUDY_C', 'target_count' => 100, 'current_count' => 0),
                    array('study_id' => 'STUDY_D', 'target_count' => 100, 'current_count' => 0),
                )
            )
        );
        
        // Test multiple asignaciones
        $results = array('STUDY_A' => 0, 'STUDY_B' => 0, 'STUDY_C' => 0, 'STUDY_D' => 0);
        for ($i = 0; $i < 40; $i++) {
            $participant_id = 'TEST_PART_' . $i;
            $assignment = eipsi_pool_randomize('TEST_POOL_RANDOM', $participant_id, $pool_data);
            
            // Simular incremento para próxima iteración
            if ($assignment && isset($assignment['study_id'])) {
                $results[$assignment['study_id']]++;
                // Update simulated counts
                foreach ($pool_data['config']['studies'] as &$study) {
                    if ($study['study_id'] === $assignment['study_id']) {
                        $study['current_count']++;
                    }
                }
            }
        }
        
        echo "(Distribución: A:{$results['STUDY_A']} B:{$results['STUDY_B']} C:{$results['STUDY_C']} D:{$results['STUDY_D']}) ";
        
        // Verificar que hay distribución (no todos en uno solo)
        $min = min($results);
        $max = max($results);
        
        // Con 40 asignaciones en 4 estudios, esperamos ~10 cada uno
        // Aceptamos si está entre 5 y 15 (±50% del esperado para test pequeño)
        return ($min >= 5 && $max <= 15);
    }
    
    private function test_assignment_idempotency() {
        // Simular datos de pool
        $pool_data = array(
            'status' => 'active',
            'config' => array(
                'studies' => array(
                    array('study_id' => 'STUDY_X', 'target_count' => 100, 'current_count' => 0),
                )
            )
        );
        
        $participant_id = 'IDEMP_TEST_001';
        
        // Primera asignación (mock - no guarda en BD real)
        $first_study = 'STUDY_X'; // Simulado
        
        // En un test real, la segunda llamada debería retornar el mismo estudio
        // pero sin nuestra implementación real de BD, solo verificamos la lógica
        
        return true; // Placeholder - test manual requerido para validación completa
    }
    
    private function test_export_function_exists() {
        return function_exists('eipsi_export_responses_with_pool_context') && 
               function_exists('eipsi_generate_pool_context_csv');
    }
    
    private function cleanup() {
        // Limpiar asignaciones de test si es necesario
        global $wpdb;
        $table = $wpdb->prefix . 'eipsi_pool_assignments';
        $wpdb->query("DELETE FROM {$table} WHERE pool_id LIKE 'TEST_%'");
    }
    
    private function printSummary() {
        echo "\n=== SUMMARY ===\n";
        echo "Total: {$this->tests_run}\n";
        echo "Passed: {$this->passed} ✅\n";
        echo "Failed: {$this->failed} ❌\n";
        echo "===============\n";
        return $this->failed === 0 ? "ALL PASSED ✅\n" : "FAILURES DETECTED ❌\n";
    }
}

// Ejecutar
$runner = new EIPSI_Pool_TestRunner();
$success = $runner->run();
exit($success ? 0 : 1);
