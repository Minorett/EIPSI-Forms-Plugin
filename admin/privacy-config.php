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
        'quality_flag' => true,

        // RECOMENDADOS - ON por default
        'therapeutic_engagement' => true,
        'avoidance_patterns' => true,
        'device_type' => true,

        // AUDITORÍA CLÍNICA - ON por default (pero opcional)
        'ip_address' => true,
        'ip_storage' => 'plain_text',
        'ip_retention_days' => 90,

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
 * Obtiene configuración de privacidad para un formulario
 */
function get_privacy_config($form_id = null) {
    $defaults = get_privacy_defaults();
    
    if (!$form_id) {
        return $defaults;
    }
    
    $saved = get_option("eipsi_privacy_config_{$form_id}");
    
    if (!$saved) {
        return $defaults;
    }
    
    $config = array_merge($defaults, (array) $saved);
    
    return $config;
}

/**
 * Guarda configuración de privacidad
 */
function save_privacy_config($form_id, $config) {
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    // Sanitizar configuración - SOLO toggles permitidos
    $sanitized = array();
    $allowed_toggles = array(
        'therapeutic_engagement',
        'avoidance_patterns',
        'device_type',
        'browser',
        'os',
        'screen_width',
        'ip_address',
        'quality_flag'
    );
    
    foreach ($config as $key => $value) {
        if (in_array($key, $allowed_toggles)) {
            $sanitized[$key] = (bool) $value;
        }
    }
    
    return update_option("eipsi_privacy_config_{$form_id}", $sanitized);
}
