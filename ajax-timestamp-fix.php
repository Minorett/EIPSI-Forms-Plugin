<?php
// Script auxiliar para generar el bloque de código corregido

$corrected_block = '
    $form_responses = array();
    $exclude_fields = array(\'form_id\', \'form_action\', \'ip_address\', \'device\', \'browser\', \'os\', \'screen_width\', \'form_start_time\', \'form_end_time\', \'current_page\', \'nonce\', \'action\', \'participant_id\', \'session_id\', \'metadata\', \'end_timestamp_ms\');

    $user_data = array(
        \'email\' => \'\',
        \'name\' => \'\'
    );

    foreach ($_POST as $key => $value) {
        if (!in_array($key, $exclude_fields) && is_string($value)) {
            $form_responses[$key] = sanitize_text_field($value);

            if (strtolower($key) === \'email\' || strpos(strtolower($key), \'correo\') !== false) {
                $user_data[\'email\'] = sanitize_email($value);
            }
            if (strtolower($key) === \'name\' || strtolower($key) === \'nombre\') {
                $user_data[\'name\'] = sanitize_text_field($value);
            }
        }
    }

    $start_timestamp_ms = null;
    $end_timestamp_ms = null;
    $duration = 0;
    $duration_seconds = 0.0;

    if (!empty($start_time)) {
        $start_timestamp_ms = intval($start_time);

        // === FIJO: Usar end_timestamp_ms del frontend si existe ===
        // Esto evita el error de ~0.6s por delay de red
        $frontend_end_timestamp_ms = isset($_POST[\'end_timestamp_ms\']) ? intval($_POST[\'end_timestamp_ms\']) : null;

        if (!empty($frontend_end_timestamp_ms)) {
            // Usar timestamp del frontend (preciso, sin delay de red)
            $end_timestamp_ms = $frontend_end_timestamp_ms;
            $duration_ms = max(0, $end_timestamp_ms - $start_timestamp_ms);
            $duration = intval($duration_ms / 1000);
            $duration_seconds = round($duration_ms / 1000, 3);
        } elseif (!empty($end_time)) {
            // Fallback: usar form_end_time si no hay end_timestamp_ms separado
            $end_timestamp_ms = intval($end_time);
            $duration_ms = max(0, $end_timestamp_ms - $start_timestamp_ms);
            $duration = intval($duration_ms / 1000);
            $duration_seconds = round($duration_ms / 1000, 3);
        } else {
            // Último fallback: recapturar en backend (legacy)
            $current_timestamp_ms = round(microtime(true) * 1000);
            $end_timestamp_ms = $current_timestamp_ms;
            $duration_ms = max(0, $end_timestamp_ms - $start_timestamp_ms);
            $duration = intval($duration_ms / 1000);
            $duration_seconds = round($duration_ms / 1000, 3);
        }
    }
';

echo $corrected_block;
