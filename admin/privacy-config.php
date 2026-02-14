<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtiene defaults de privacidad
 * Browser/OS/Screen Width OFF por default (opcional)
 * IP ON por default (pero configurable)
 */
function get_privacy_defaults() {
    return array(
        // OBLIGATORIOS
        'form_id' => true,
        'participant_id' => true,
        'session_id' => true,
        'timestamps_basic' => true,

        // RECOMENDADOS - ON por default
        'device_type' => true,

        // AUDITORÍA CLÍNICA - ON por default (pero opcional)
        'ip_address' => true,
        'ip_storage' => 'plain_text',
        'ip_retention_days' => 90,

        // ✅ v1.5.4 - FINGERPRINT COMPLETO - ON por default
        'fingerprint_enabled' => true,

        // DISPOSITIVO - OFF por default (opcional)
        'browser' => false,
        'os' => false,
        'screen_width' => false,

        // EXCLUIDOS (por privacidad y alcance)
        'screen_size' => false,
        'browser_os' => false,
        'user_agent_full' => false,
        'ip_geo' => false,
        'connection_type' => false,
        'movement_tracking' => false,
        'mood_tracking' => false,
        'research_consent' => false,
    );
}

/**
 * Obtiene la configuración global por defecto de privacidad
 */
function get_global_privacy_defaults() {
    $saved = get_option('eipsi_global_privacy_defaults');

    if (!$saved) {
        // Si no existe configuración global, usar defaults estándar
        return array(
            'ip_address' => true,
            'browser' => false,
            'os' => false,
            'screen_width' => false,
            'device_type' => true,
            'fingerprint_enabled' => true  // ✅ v1.5.4 - ON por default
        );
    }

    return $saved;
}

/**
 * Guarda la configuración global de privacidad
 */
function save_global_privacy_defaults($config) {
    if (!current_user_can('manage_options')) {
        return false;
    }

    // Sanitizar configuración - SOLO toggles permitidos
    $sanitized = array();
    $allowed_toggles = array(
        'device_type',
        'browser',
        'os',
        'screen_width',
        'ip_address',
        'fingerprint_enabled'  // ✅ v1.5.4 - Toggle de fingerprint completo
    );

    foreach ($config as $key => $value) {
        if (in_array($key, $allowed_toggles)) {
            $sanitized[$key] = (bool) $value;
        }
    }

    return update_option('eipsi_global_privacy_defaults', $sanitized);
}

/**
 * Obtiene configuración de privacidad para un formulario específico
 *
 * @param string $form_id Form ID
 * @return array Privacy configuration
 */
function get_privacy_config($form_id = null) {
    // Si no hay form_id, devolver defaults generales
    if (!$form_id) {
        return get_privacy_defaults();
    }

    // Obtener configuración específica del formulario
    $saved = get_option("eipsi_privacy_config_{$form_id}");

    // Si no hay configuración específica, usar la global
    if (!$saved || !is_array($saved)) {
        return get_global_privacy_defaults();
    }

    // Devolver configuración específica del formulario
    return $saved;
}

/**
 * Guarda configuración de privacidad para un formulario específico
 *
 * @param string $form_id Form ID
 * @param array $config Privacy configuration
 */
function save_privacy_config($form_id, $config) {
    if (!current_user_can('manage_options')) {
        return false;
    }

    // Sanitizar configuración - SOLO toggles permitidos
    $sanitized = array();
    $allowed_toggles = array(
        'device_type',
        'browser',
        'os',
        'screen_width',
        'ip_address',
        'fingerprint_enabled'  // ✅ v1.5.4 - Toggle de fingerprint completo
    );

    foreach ($config as $key => $value) {
        if (in_array($key, $allowed_toggles)) {
            $sanitized[$key] = (bool) $value;
        }
    }

    return update_option("eipsi_privacy_config_{$form_id}", $sanitized);
}
