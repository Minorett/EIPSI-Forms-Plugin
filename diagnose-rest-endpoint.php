<?php
/**
 * Diagnóstico: Verificar endpoint REST API para eipsi_form_template
 *
 * Este script verifica:
 * 1. Si el CPT está registrado con show_in_rest
 * 2. Si el endpoint REST es accesible
 * 3. Qué posts existen
 * 4. Qué permisos tiene el usuario actual
 *
 * Para ejecutar: wp eval-file diagnose-rest-endpoint.php
 */

if (!defined('ABSPATH')) {
    // Si no estamos en WordPress, intentar cargar WordPress
    $wp_load = __DIR__ . '/../../../wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        echo "ERROR: No se pudo cargar WordPress\n";
        exit(1);
    }
}

echo "\n=== DIAGNÓSTICO: Endpoint REST API para eipsi_form_template ===\n\n";

// 1. Verificar si el CPT está registrado
echo "1. Verificando registro del CPT eipsi_form_template...\n";
$cpt = get_post_type_object('eipsi_form_template');
if ($cpt) {
    echo "   ✓ CPT registrado\n";
    echo "   - show_in_rest: " . ($cpt->show_in_rest ? 'SÍ' : 'NO') . "\n";
    echo "   - public: " . ($cpt->public ? 'SÍ' : 'NO') . "\n";
    echo "   - capability_type: " . $cpt->capability_type . "\n";

    // Verificar capacidades del usuario actual
    $current_user = wp_get_current_user();
    echo "\n   Usuario actual: " . $current_user->display_name . " (ID: " . $current_user->ID . ")\n";
    echo "   - manage_options: " . (current_user_can('manage_options') ? 'SÍ' : 'NO') . "\n";
    echo "   - edit_posts: " . (current_user_can('edit_posts') ? 'SÍ' : 'NO') . "\n";
    echo "   - read: " . (current_user_can('read') ? 'SÍ' : 'NO') . "\n";

    // Verificar capacidades específicas del CPT
    echo "\n   Capacidades del CPT para el usuario actual:\n";
    echo "   - read_post: " . (current_user_can($cpt->cap->read_post, 1) ? 'SÍ' : 'NO') . "\n";
    echo "   - read_private_posts: " . (current_user_can($cpt->cap->read_private_posts) ? 'SÍ' : 'NO') . "\n";
} else {
    echo "   ✗ CPT NO registrado\n";
}
echo "\n";

// 2. Verificar posts existentes
echo "2. Buscando posts eipsi_form_template...\n";
$args = array(
    'post_type' => 'eipsi_form_template',
    'posts_per_page' => 100,
    'post_status' => 'publish',
);

$query = new WP_Query($args);
if ($query->have_posts()) {
    echo "   ✓ Encontrados {$query->found_posts} formularios:\n";
    while ($query->have_posts()) {
        $query->the_post();
        $id = get_the_ID();
        $title = get_the_title();
        echo "     - ID {$id}: {$title}\n";
    }
    wp_reset_postdata();
} else {
    echo "   ✗ No se encontraron formularios\n";
}
echo "\n";

// 3. Verificar endpoint REST
echo "3. Probando acceso al endpoint REST API...\n";
$rest_url = rest_url('wp/v2/eipsi_form_template?per_page=100&status=publish');
echo "   URL: {$rest_url}\n";

$request = new WP_REST_Request('GET', '/wp/v2/eipsi_form_template');
$request->set_param('per_page', 100);
$request->set_param('status', 'publish');

$response = rest_do_request($request);

if ($response->is_error()) {
    echo "   ✗ ERROR en REST API:\n";
    $error = $response->as_error();
    echo "     Código: {$error->get_error_code()}\n";
    echo "     Mensaje: {$error->get_error_message()}\n";
} else {
    echo "   ✓ REST API funcionando\n";
    $data = $response->get_data();
    echo "   Posts devueltos: " . count($data) . "\n";
    if (!empty($data)) {
        echo "   Primeros posts:\n";
        foreach (array_slice($data, 0, 5) as $post) {
            echo "     - ID {$post['id']}: {$post['title']['rendered']}\n";
        }
    }
}
echo "\n";

// 4. Simular lo que hace el bloque de aleatorización
echo "4. Simulando la llamada del bloque de aleatorización...\n";
echo "   El bloque usa apiFetch con esta ruta:\n";
echo "   /wp/v2/eipsi_form_template?per_page=100&status=publish\n";

// Verificar si hay filtros que estén modificando la query
global $wp_rest_server;
if ($wp_rest_server) {
    echo "   ✓ Servidor REST inicializado\n";
} else {
    echo "   ✗ Servidor REST NO inicializado\n";
}
echo "\n";

echo "=== FIN DEL DIAGNÓSTICO ===\n";
