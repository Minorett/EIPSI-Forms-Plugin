<?php
/**
 * Testing Script: Randomization Fix v1.3.6
 *
 * Este script verifica que el fix del bloque de aleatorización funciona correctamente.
 *
 * Uso:
 * 1. Guardar este archivo en la raíz del proyecto
 * 2. Acceder a: http://tu-sitio.com/test-randomization-fix.php
 * 3. Revisar los resultados
 *
 * @since 1.3.6
 */

if (!defined('ABSPATH')) {
    // Cargar WordPress si no está cargado
    $wp_load_path = __DIR__ . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('Error: No se encontró wp-load.php. Este script debe estar en la raíz de WordPress.');
    }
}

// Verificar que el usuario es admin
if (!current_user_can('manage_options')) {
    wp_die('Acceso denegado. Necesitas permisos de administrador para ejecutar este test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test: Randomization Fix v1.3.6</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #2271b1; }
        h2 { color: #135e96; margin-top: 30px; }
        .test-case { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .error { background: #ffebee; border-left: 4px solid #f44336; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .status { font-weight: bold; }
        .status.pass { color: #2e7d32; }
        .status.fail { color: #c62828; }
        .code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .summary { margin-top: 30px; padding: 20px; border-radius: 5px; }
        .summary.all-pass { background: #e8f5e9; }
        .summary.some-fail { background: #ffebee; }
    </style>
</head>
<body>
    <h1>🧪 Test: Randomization Fix v1.3.6</h1>
    <p>Verificación de que el bloque de aleatorización puede acceder a formularios en diferentes estados.</p>

<?php
$results = [];

// Función helper para registrar tests
function run_test($name, $callback) {
    global $results;
    $start_time = microtime(true);
    try {
        $result = $callback();
        $duration = round((microtime(true) - $start_time) * 1000, 2);
        $results[] = [
            'name' => $name,
            'pass' => $result['pass'],
            'message' => $result['message'],
            'duration' => $duration
        ];
        return $result;
    } catch (Exception $e) {
        $duration = round((microtime(true) - $start_time) * 1000, 2);
        $results[] = [
            'name' => $name,
            'pass' => false,
            'message' => 'Exception: ' . $e->getMessage(),
            'duration' => $duration
        ];
        return ['pass' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

// TEST 1: Verificar que el endpoint existe
run_test('Endpoint /randomization-detect existe', function() {
    $routes = rest_get_server()->get_routes();
    $exists = isset($routes['/eipsi/v1/randomization-detect']);
    return [
        'pass' => $exists,
        'message' => $exists ? 'Endpoint registrado correctamente' : '❌ Endpoint no encontrado'
    ];
});

// TEST 2: Verificar que el endpoint /randomization-config existe
run_test('Endpoint /randomization-config existe', function() {
    $routes = rest_get_server()->get_routes();
    $exists = isset($routes['/eipsi/v1/randomization-config']);
    return [
        'pass' => $exists,
        'message' => $exists ? 'Endpoint registrado correctamente' : '❌ Endpoint no encontrado'
    ];
});

// TEST 3: Obtener formularios de prueba
$formularios = get_posts([
    'post_type' => 'eipsi_form_template',
    'posts_per_page' => 5,
    'post_status' => 'any'
]);

run_test('Existen formularios de prueba', function() use (&$formularios) {
    $count = count($formularios);
    return [
        'pass' => $count > 0,
        'message' => $count > 0 ? "Se encontraron {$count} formularios" : '❌ No se encontraron formularios. Crea al menos uno en Form Library.'
    ];
});

// TEST 4: Test con formulario publicado (si existe)
if (!empty($formularios)) {
    $test_form = $formularios[0];
    $form_id = $test_form->ID;
    $form_status = $test_form->post_status;

    run_test("Validar formulario ID {$form_id} (status: {$form_status})", function() use ($form_id, $form_status) {
        $request = new WP_REST_Request('POST', '/eipsi/v1/randomization-detect');
        $request->set_param('post_id', 1); // Template ID (puede ser cualquiera)
        $request->set_param('shortcodes_input', "[eipsi_form id=\"{$form_id}\"]");

        $response = rest_do_request($request);
        $data = $response->get_data();

        $pass = isset($data['success']) && $data['success'] === true;

        return [
            'pass' => $pass,
            'message' => $pass
                ? "✅ Formulario detectado exitosamente: {$data['message']}"
                : "❌ Error: " . ($data['message'] ?? 'Unknown error')
        ];
    });
}

// TEST 5: Test con formulario que no existe
run_test('Validar formulario inexistente (debe fallar)', function() {
    $fake_id = 999999;

    $request = new WP_REST_Request('POST', '/eipsi/v1/randomization-detect');
    $request->set_param('post_id', 1);
    $request->set_param('shortcodes_input', "[eipsi_form id=\"{$fake_id}\"]");

    $response = rest_do_request($request);
    $data = $response->get_data();

    $pass = isset($data['success']) && $data['success'] === false
           && strpos($data['message'], 'no existe o fue eliminado') !== false;

    return [
        'pass' => $pass,
        'message' => $pass
            ? "✅ Rechazó correctamente el ID inexistente: {$data['message']}"
            : "❌ No rechazó correctamente: {$data['message'] ?? 'Unknown error'}"
    ];
});

// TEST 6: Test con shortcode inválido
run_test('Validar shortcode inválido (debe fallar)', function() {
    $request = new WP_REST_Request('POST', '/eipsi/v1/randomization-detect');
    $request->set_param('post_id', 1);
    $request->set_param('shortcodes_input', "[invalid_shortcode id=\"123\"]");

    $response = rest_do_request($request);
    $data = $response->get_data();

    $pass = isset($data['success']) && $data['success'] === false;

    return [
        'pass' => $pass,
        'message' => $pass
            ? "✅ Rechazó correctamente el shortcode inválido: {$data['message']}"
            : "❌ No rechazó correctamente: {$data['message'] ?? 'Unknown error'}"
    ];
});

// TEST 7: Verificar que el CPT existe
run_test('CPT eipsi_form_template está registrado', function() {
    $cpt = get_post_type_object('eipsi_form_template');
    return [
        'pass' => $cpt !== null,
        'message' => $cpt !== null
            ? "✅ CPT registrado correctamente: {$cpt->labels->name}"
            : '❌ CPT no encontrado'
    ];
});

// TEST 8: Test parser de shortcodes
run_test('Parser de shortcodes funciona correctamente', function() use ($formularios) {
    if (empty($formularios)) {
        return ['pass' => false, 'message' => 'No hay formularios para testear'];
    }

    $form_id = $formularios[0]->ID;

    $test_cases = [
        "[eipsi_form id=\"{$form_id}\"]",
        "[eipsi_form id='{$form_id}']",
        "  [eipsi_form id=\"{$form_id}\"]  ", // con espacios
        "[eipsi_form id=\"{$form_id}\"]\n", // con newline
    ];

    $all_pass = true;
    foreach ($test_cases as $test_case) {
        $request = new WP_REST_Request('POST', '/eipsi/v1/randomization-detect');
        $request->set_param('post_id', 1);
        $request->set_param('shortcodes_input', $test_case);

        $response = rest_do_request($request);
        $data = $response->get_data();

        if (!isset($data['success']) || $data['success'] !== true) {
            $all_pass = false;
            break;
        }
    }

    return [
        'pass' => $all_pass,
        'message' => $all_pass
            ? "✅ Todos los formatos de shortcode funcionan correctamente"
            : "❌ Algunos formatos fallaron"
    ];
});

// TEST 9: Test multi-formulario (si hay suficientes formularios)
if (count($formularios) >= 2) {
    $form1 = $formularios[0]->ID;
    $form2 = $formularios[1]->ID;

    run_test("Validar múltiples formularios (IDs: {$form1}, {$form2})", function() use ($form1, $form2) {
        $input = "[eipsi_form id=\"{$form1}\"]\n[eipsi_form id=\"{$form2}\"]";

        $request = new WP_REST_Request('POST', '/eipsi/v1/randomization-detect');
        $request->set_param('post_id', 1);
        $request->set_param('shortcodes_input', $input);

        $response = rest_do_request($request);
        $data = $response->get_data();

        $pass = isset($data['success'])
            && $data['success'] === true
            && isset($data['formularios'])
            && count($data['formularios']) === 2;

        return [
            'pass' => $pass,
            'message' => $pass
                ? "✅ Detectados {$data['message']}"
                : "❌ Error: " . ($data['message'] ?? 'Unknown error')
        ];
    });
}

// Mostrar resultados
?>

    <h2>📊 Resultados de Tests</h2>

<?php foreach ($results as $i => $result): ?>
    <div class="test-case <?php echo $result['pass'] ? 'success' : 'error'; ?>">
        <h3>Test #<?php echo $i + 1; ?>: <?php echo esc_html($result['name']); ?></h3>
        <p><strong>Estado:</strong>
            <span class="status <?php echo $result['pass'] ? 'pass' : 'fail'; ?>">
                <?php echo $result['pass'] ? '✅ PASS' : '❌ FAIL'; ?>
            </span>
        </p>
        <p><strong>Mensaje:</strong> <?php echo esc_html($result['message']); ?></p>
        <p><strong>Duración:</strong> <?php echo $result['duration']; ?>ms</p>
    </div>
<?php endforeach; ?>

    <div class="summary <?php echo count(array_filter($results, fn($r) => !$r['pass'])) === 0 ? 'all-pass' : 'some-fail'; ?>">
        <h2>📈 Resumen</h2>
        <p>
            <strong>Total de tests:</strong> <?php echo count($results); ?><br>
            <strong>Pass:</strong> <?php echo count(array_filter($results, fn($r) => $r['pass'])); ?><br>
            <strong>Fail:</strong> <?php echo count(array_filter($results, fn($r) => !$r['pass'])); ?>
        </p>
        <p><strong>Tiempo total:</strong> <?php echo round(array_sum(array_column($results, 'duration')), 2); ?>ms</p>
        <hr>
        <p><strong>Versión del plugin:</strong> <?php echo defined('EIPSI_FORMS_VERSION') ? EIPSI_FORMS_VERSION : 'N/A'; ?></p>
    </div>

    <h2>🔍 Información de Debug</h2>
    <div class="test-case info">
        <h3>Formularios Encontrados</h3>
<?php if (!empty($formularios)): ?>
        <ul>
<?php foreach (array_slice($formularios, 0, 5) as $form): ?>
            <li><strong>ID:</strong> <?php echo $form->ID; ?> | <strong>Título:</strong> <?php echo esc_html($form->post_title); ?> | <strong>Estado:</strong> <?php echo esc_html($form->post_status); ?></li>
<?php endforeach; ?>
        </ul>
<?php else: ?>
        <p>No se encontraron formularios. Crea al menos uno en <strong>EIPSI Forms → Form Library</strong>.</p>
<?php endif; ?>
    </div>

    <h2>📝 Instrucciones de Uso</h2>
    <ol>
        <li>Abre el editor de Gutenberg en cualquier página o post</li>
        <li>Inserta el bloque <strong>Aleatorización de Formularios</strong></li>
        <li>En el textarea, pega los shortcodes de tus formularios:</li>
    </ol>
    <pre>[eipsi_form id="2424"]
[eipsi_form id="2417"]</pre>
    <ol start="4">
        <li>Clic en <strong>🔍 Detectar Formularios</strong></li>
        <li>Deberías ver los formularios detectados con sus nombres</li>
        <li>Ajusta las probabilidades si es necesario</li>
        <li>Clic en <strong>💾 Guardar Configuración</strong></li>
        <li>Copia el shortcode generado y úsalo en cualquier página</li>
    </ol>

    <p style="margin-top: 40px; font-size: 12px; color: #666;">
        <strong>Nota:</strong> Este script es solo para testing. Elimínalo después de verificar que todo funciona correctamente.
    </p>

</body>
</html>
