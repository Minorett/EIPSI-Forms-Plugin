<?php
/**
 * Wizard Validators
 * 
 * Funciones de validación para cada paso del setup wizard.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validate step data before saving
 * 
 * @param int $step_number
 * @param array $step_data
 * @return array ['valid' => bool, 'errors' => array]
 */
function eipsi_validate_step_data($step_number, $step_data) {
    switch ($step_number) {
        case 1:
            return eipsi_validate_study_info($step_data);
        case 2:
            return eipsi_validate_waves_config($step_data);
        case 3:
            return eipsi_validate_timing_config($step_data);
        case 4:
            return eipsi_validate_participants_config($step_data);
        case 5:
            return eipsi_validate_summary_activation($step_data);
        default:
            return array('valid' => false, 'errors' => array('Paso no válido.'));
    }
}

/**
 * Sanitize step data for saving
 * 
 * @param int $step_number
 * @param array $step_data
 * @return array Sanitized data
 */
function eipsi_sanitize_step_data($step_number, $step_data) {
    switch ($step_number) {
        case 1:
            return eipsi_sanitize_study_info($step_data);
        case 2:
            return eipsi_sanitize_waves_config($step_data);
        case 3:
            return eipsi_sanitize_timing_config($step_data);
        case 4:
            return eipsi_sanitize_participants_config($step_data);
        case 5:
            return eipsi_sanitize_summary_activation($step_data);
        default:
            return $step_data;
    }
}

/**
 * Validate Step 1: Study Information
 */
function eipsi_validate_study_info($data) {
    $errors = array();
    
    // Study name validation
    if (empty($data['study_name'])) {
        $errors[] = '❌ El nombre del estudio es obligatorio. Usa un nombre descriptivo que identifique claramente tu investigación (ej: "Efectos de la Terapia Cognitiva en Ansiedad 2025").';
    } elseif (strlen($data['study_name']) < 3) {
        $errors[] = '❌ El nombre del estudio es muy corto. Usa al menos 3 caracteres para que sea identificable.';
    } elseif (strlen($data['study_name']) > 100) {
        $errors[] = '❌ El nombre del estudio es muy largo. Usa máximo 100 caracteres. Si necesitas más detalle, agrégalo en la descripción.';
    }
    
    // Study code validation
    if (empty($data['study_code'])) {
        $errors[] = '❌ El código del estudio es obligatorio. Este código sirve para identificar tu estudio en exportaciones y reportes. Usa mayúsculas, números y guiones bajos (ej: ANSIEDAD_TCC_2025).';
    } elseif (!preg_match('/^[A-Z0-9_]+$/', $data['study_code'])) {
        $errors[] = '❌ El código del estudio contiene caracteres no válidos. Solo puedes usar: letras MAYÚSCULAS (A-Z), números (0-9) y guiones bajos (_). No uses espacios ni caracteres especiales.';
    } elseif (strlen($data['study_code']) < 3) {
        $errors[] = '❌ El código del estudio es muy corto. Usa al menos 3 caracteres para asegurar que sea único.';
    } elseif (strlen($data['study_code']) > 50) {
        $errors[] = '❌ El código del estudio es muy largo. Usa máximo 50 caracteres para mantenerlo manejable en reportes.';
    }
    
    // Check for unique study code
    if (!empty($data['study_code'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_studies';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE study_code = %s",
            $data['study_code']
        ));
        
        if ($existing) {
            $errors[] = '❌ El código "' . esc_html($data['study_code']) . '" ya está en uso por otro estudio. Por favor elige un código único (puedes agregar el año o tus iniciales).';
        }
    }
    
    // Principal investigator validation
    if (empty($data['principal_investigator_id'])) {
        $errors[] = '❌ Debes seleccionar un investigador principal. Esta persona será el contacto principal del estudio y recibirá notificaciones importantes.';
    } elseif (!get_userdata($data['principal_investigator_id'])) {
        $errors[] = '❌ El investigador seleccionado no existe en el sistema. Por favor selecciona un usuario válido de la lista.';
    }
    
    // Description validation (optional)
    if (!empty($data['description']) && strlen($data['description']) > 1000) {
        $errors[] = '❌ La descripción es muy larga. Usa máximo 1000 caracteres. Sé conciso pero informativo sobre los objetivos del estudio.';
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors
    );
}

/**
 * Sanitize Step 1: Study Information
 */
function eipsi_sanitize_study_info($data) {
    return array(
        'study_name' => sanitize_text_field($data['study_name']),
        'study_code' => strtoupper(sanitize_text_field($data['study_code'])),
        'description' => sanitize_textarea_field($data['description']),
        'principal_investigator_id' => intval($data['principal_investigator_id'])
    );
}

/**
 * Validate Step 2: Waves Configuration
 */
function eipsi_validate_waves_config($data) {
    $errors = array();
    
    // Number of waves validation
    if (empty($data['number_of_waves'])) {
        $errors[] = '❌ Debes especificar cuántas tomas tendrá tu estudio. Este número determina cuántas veces evaluarás a los participantes (por ejemplo: pre, post, seguimiento).';
    } elseif (!is_numeric($data['number_of_waves']) || intval($data['number_of_waves']) < 1) {
        $errors[] = '❌ El número de tomas debe ser al menos 1. La mayoría de estudios longitudinales tienen entre 2 y 4 tomas.';
    } elseif (intval($data['number_of_waves']) > 10) {
        $errors[] = '❌ No se pueden configurar más de 10 tomas. Si necesitas más, considera dividir tu estudio en fases.';
    }
    
    // Waves configuration validation
    if (!empty($data['waves_config']) && is_array($data['waves_config'])) {
        $wave_names = array();
        
        foreach ($data['waves_config'] as $index => $wave) {
            $wave_number = $index + 1;
            
            // Wave name validation
            if (empty($wave['name'])) {
                $errors[] = "❌ La Toma {$wave_number} necesita un nombre descriptivo. Por ejemplo: 'Evaluación inicial', 'Post-tratamiento' o 'Seguimiento 3 meses'.";
            } elseif (strlen($wave['name']) > 100) {
                $errors[] = "❌ El nombre de la Toma {$wave_number} es demasiado largo. Usa máximo 100 caracteres para que sea fácil de identificar.";
            }
            
            // Check for duplicate wave names
            $wave_name = trim($wave['name']);
            if (!empty($wave_name) && in_array(strtolower($wave_name), $wave_names)) {
                $errors[] = "❌ El nombre '{$wave_name}' está repetido. Cada toma debe tener un nombre único para poder identificarlas correctamente.";
            }
            if (!empty($wave_name)) {
                $wave_names[] = strtolower($wave_name);
            }
            
            // Form template validation
            if (empty($wave['form_template_id'])) {
                $errors[] = "❌ La Toma {$wave_number} necesita un formulario asociado. Selecciona qué cuestionario usarás para esta evaluación.";
            } elseif (!get_post($wave['form_template_id'])) {
                $errors[] = "❌ El formulario seleccionado para la Toma {$wave_number} no existe o fue eliminado. Por favor selecciona otro formulario.";
            }
            
            // Duration validation - now optional with "Infinite" as default
            // If empty, null, or 0, it means "Infinite" (no time limit)
            if (!empty($wave['estimated_duration'])) {
                // Only validate if a value was provided (not infinite)
                if (!is_numeric($wave['estimated_duration']) || intval($wave['estimated_duration']) < 1) {
                    $errors[] = "❌ La duración estimada para la Toma {$wave_number} debe ser un número positivo mayor a 0, o déjala en blanco para tiempo ilimitado.";
                } elseif (intval($wave['estimated_duration']) > 180) {
                    $errors[] = "❌ La duración estimada para la Toma {$wave_number} no puede exceder 180 minutos (3 horas). Considera dividir el formulario en sesiones más cortas.";
                }
            }
            // Note: Empty/null duration means "Infinite" - no validation error
        }
        
        // At least one wave should be required
        $required_waves = 0;
        foreach ($data['waves_config'] as $wave) {
            if (!empty($wave['is_required'])) {
                $required_waves++;
            }
        }
        
        if ($required_waves == 0) {
            $errors[] = '❌ Debes marcar al menos una toma como "Obligatoria". Recomendamos que la primera toma (baseline) sea obligatoria para asegurar datos iniciales.';
        }
    } else {
        $errors[] = '❌ No se encontró la configuración de las tomas. Por favor recarga la página e intenta nuevamente.';
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors
    );
}

/**
 * Sanitize Step 2: Waves Configuration
 */
function eipsi_sanitize_waves_config($data) {
    $sanitized = array(
        'number_of_waves' => intval($data['number_of_waves']),
        'waves_config' => array()
    );
    
    if (!empty($data['waves_config']) && is_array($data['waves_config'])) {
        foreach ($data['waves_config'] as $index => $wave) {
            // Handle duration: empty/null/0 means "Infinite" (no time limit)
            $duration = isset($wave['estimated_duration']) && $wave['estimated_duration'] !== '' 
                ? intval($wave['estimated_duration']) 
                : null; // null represents "Infinite"
            
            $sanitized['waves_config'][] = array(
                'name' => sanitize_text_field($wave['name']),
                'form_template_id' => intval($wave['form_template_id']),
                'estimated_duration' => $duration,
                'is_required' => !empty($wave['is_required'])
            );
        }
    }
    
    return $sanitized;
}

/**
 * Validate Step 3: Timing Configuration
 */
function eipsi_validate_timing_config($data) {
    $errors = array();
    
    // Timing intervals validation
    if (!empty($data['timing_intervals']) && is_array($data['timing_intervals'])) {
        foreach ($data['timing_intervals'] as $index => $interval) {
            $interval_num = $index + 1;
            if (empty($interval['days_after'])) {
                $errors[] = "❌ El intervalo {$interval_num} necesita especificar cuántos días después de la toma anterior debe ocurrir.";
            } elseif (!is_numeric($interval['days_after']) || intval($interval['days_after']) < 1) {
                $errors[] = "❌ El intervalo {$interval_num} debe tener un número positivo de días (mínimo 1 día).";
            } elseif (intval($interval['days_after']) > 365) {
                $errors[] = "❌ El intervalo {$interval_num} no puede exceder 365 días (1 año). Para estudios con intervalos más largos, considera crear ondas manualmente.";
            }
        }
    }
    
    // Reminder days validation
    if (!empty($data['reminder_days_before'])) {
        if (!is_numeric($data['reminder_days_before']) || intval($data['reminder_days_before']) < 0) {
            $errors[] = '❌ Los días de recordatorio deben ser 0 (mismo día) o un número positivo.';
        } elseif (intval($data['reminder_days_before']) > 30) {
            $errors[] = '❌ Los días de recordatorio no pueden exceder 30. Los recordatorios con más de un mes de anticipación suelen ser poco efectivos.';
        }
    }
    
    // Retry configuration validation
    if (!empty($data['enable_retries'])) {
        if (empty($data['retry_after_days'])) {
            $errors[] = '❌ Has activado los reintentos pero no especificaste después de cuántos días. Configura este valor para indicar cuándo se debe enviar una nueva invitación si el participante no completó la toma.';
        } elseif (!is_numeric($data['retry_after_days']) || intval($data['retry_after_days']) < 1) {
            $errors[] = '❌ Los días para reintentos deben ser al menos 1. Dale al participante tiempo suficiente para responder.';
        } elseif (intval($data['retry_after_days']) > 60) {
            $errors[] = '❌ Los días para reintentos no pueden exceder 60 (2 meses). Si necesitas más tiempo, considera crear una nueva onda en lugar de un reintento.';
        }
        
        if (empty($data['max_retries']) && $data['max_retries'] !== '0' && $data['max_retries'] !== 0) {
            $errors[] = '❌ Debes especificar el número máximo de reintentos. Puedes usar 0 para reintentos ilimitados, o un número específico (1-10) para limitarlos.';
        } elseif (!is_numeric($data['max_retries']) || intval($data['max_retries']) < 0) {
            $errors[] = '❌ El número máximo de reintentos debe ser 0 (ilimitado) o un número positivo.';
        } elseif (intval($data['max_retries']) > 10) {
            $errors[] = '❌ El número máximo de reintentos no puede exceder 10. Demasiados reintentos pueden molestar a los participantes.';
        }
    }
    
    // Investigator notification validation
    if (!empty($data['investigator_notification_days'])) {
        if (!is_numeric($data['investigator_notification_days']) || intval($data['investigator_notification_days']) < 1) {
            $errors[] = '❌ Los días para notificar al investigador deben ser un número positivo.';
        } elseif (intval($data['investigator_notification_days']) > 90) {
            $errors[] = '❌ Los días para notificar al investigador no pueden exceder 90 (3 meses). Para seguimientos más largos, configura recordatorios manuales.';
        }
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors
    );
}

/**
 * Sanitize Step 3: Timing Configuration
 */
function eipsi_sanitize_timing_config($data) {
    $sanitized = array();
    
    // Sanitize timing intervals
    if (!empty($data['timing_intervals']) && is_array($data['timing_intervals'])) {
        $sanitized['timing_intervals'] = array();
        foreach ($data['timing_intervals'] as $interval) {
            $sanitized['timing_intervals'][] = array(
                'from_wave' => intval($interval['from_wave']),
                'to_wave' => intval($interval['to_wave']),
                'days_after' => intval($interval['days_after'])
            );
        }
    }
    
    // Sanitize other timing settings
    $sanitized['reminder_days_before'] = !empty($data['reminder_days_before']) ? intval($data['reminder_days_before']) : 3;
    $sanitized['enable_retries'] = !empty($data['enable_retries']);
    $sanitized['retry_after_days'] = !empty($data['retry_after_days']) ? intval($data['retry_after_days']) : 7;
    $sanitized['max_retries'] = !empty($data['max_retries']) ? intval($data['max_retries']) : 3;
    $sanitized['investigator_notification_days'] = !empty($data['investigator_notification_days']) ? intval($data['investigator_notification_days']) : 14;
    
    return $sanitized;
}

/**
 * Validate Step 4: Participants Configuration
 */
function eipsi_validate_participants_config($data) {
    $errors = array();
    
    // Invitation methods validation
    if (empty($data['invitation_methods']) || !is_array($data['invitation_methods'])) {
        $errors[] = '❌ Debes seleccionar al menos un método de invitación. Elige entre: Magic Links (email individual), CSV (carga masiva), o Registro Público (enlace abierto).';
    } else {
        $valid_methods = array('magic_links', 'csv_upload', 'public_registration');
        foreach ($data['invitation_methods'] as $method) {
            if (!in_array($method, $valid_methods)) {
                $errors[] = "❌ Método de invitación no reconocido: {$method}. Usa solo las opciones disponibles en el formulario.";
            }
        }
    }
    
    // Consent message validation
    if (!empty($data['require_consent']) && empty($data['consent_message'])) {
        $errors[] = '❌ Has marcado que requieres consentimiento informado, pero no proporcionaste el texto del consentimiento. Escribe el mensaje que los participantes deben aceptar, o desmarca la opción de consentimiento.';
    } elseif (!empty($data['consent_message']) && strlen($data['consent_message']) > 2000) {
        $errors[] = '❌ El mensaje de consentimiento es muy largo (máximo 2000 caracteres). Sé claro y conciso. Recuerda que los participantes deben poder leer y entender fácilmente el consentimiento.';
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors
    );
}

/**
 * Sanitize Step 4: Participants Configuration
 */
function eipsi_sanitize_participants_config($data) {
    $sanitized = array();
    
    // Sanitize invitation methods
    $valid_methods = array('magic_links', 'csv_upload', 'public_registration');
    $sanitized['invitation_methods'] = array();
    
    if (!empty($data['invitation_methods']) && is_array($data['invitation_methods'])) {
        foreach ($data['invitation_methods'] as $method) {
            if (in_array($method, $valid_methods)) {
                $sanitized['invitation_methods'][] = $method;
            }
        }
    }
    
    // Sanitize other settings
    $sanitized['require_consent'] = !empty($data['require_consent']);
    $sanitized['consent_message'] = !empty($data['consent_message']) ? sanitize_textarea_field($data['consent_message']) : '';
    $sanitized['show_privacy_notice'] = !empty($data['show_privacy_notice']);
    $sanitized['auto_removal_inactive'] = !empty($data['auto_removal_inactive']);
    
    return $sanitized;
}

/**
 * Validate Step 5: Summary and Activation
 */
function eipsi_validate_summary_activation($data) {
    $errors = array();
    
    // Activation confirmation validation
    if (empty($data['activation_confirmed'])) {
        $errors[] = '❌ Debes confirmar la activación del estudio. Marca la casilla de confirmación para indicar que has revisado toda la configuración y estás listo para activar el estudio.';
    }
    
    // Additional validation can be added here for step dependencies
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors
    );
}

/**
 * Sanitize Step 5: Summary and Activation
 */
function eipsi_sanitize_summary_activation($data) {
    return array(
        'activation_confirmed' => !empty($data['activation_confirmed'])
    );
}

/**
 * Check if a step is completed based on wizard data
 */
function eipsi_is_step_completed($step_number, $wizard_data) {
    $step_data = isset($wizard_data['step_' . $step_number]) ? $wizard_data['step_' . $step_number] : array();
    
    switch ($step_number) {
        case 1:
            return !empty($step_data['study_name']) && !empty($step_data['study_code']) && !empty($step_data['principal_investigator_id']);
        case 2:
            return !empty($step_data['number_of_waves']) && !empty($step_data['waves_config']);
        case 3:
            return !empty($step_data['timing_intervals']) || !empty($step_data['reminder_days_before']);
        case 4:
            return !empty($step_data['invitation_methods']);
        case 5:
            return false; // Step 5 is the activation step
        default:
            return false;
    }
}

/**
 * Generate study code from name
 */
function eipsi_generate_study_code_from_name($name) {
    // Remove accents and special characters
    $clean_name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    $clean_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $clean_name);
    
    // Convert to uppercase and replace spaces with underscores
    $clean_name = strtoupper(str_replace(' ', '_', $clean_name));
    
    // Take first 3 words or truncate to 15 characters
    $words = explode('_', $clean_name);
    $words = array_filter($words); // Remove empty words
    $words = array_slice($words, 0, 3);
    $prefix = implode('_', $words);
    
    if (strlen($prefix) > 15) {
        $prefix = substr($prefix, 0, 15);
    }
    
    // Add year
    $year = date('Y');
    $final_code = $prefix . '_' . $year;
    
    return $final_code;
}