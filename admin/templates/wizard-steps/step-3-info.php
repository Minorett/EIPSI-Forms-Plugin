<?php
/**
 * Wizard Step 3: Timing entre Tomas
 * 
 * Template para configurar timing, recordatorios y reintentos.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

$step_data = isset($wizard_data['step_3']) ? $wizard_data['step_3'] : array();

// Get previous step data to build timing logic
$step_2_data = isset($wizard_data['step_2']) ? $wizard_data['step_2'] : array();
$number_of_waves = isset($step_2_data['number_of_waves']) ? intval($step_2_data['number_of_waves']) : 3;

// Default timing intervals between waves - v1.5.6: Todos 7 días por defecto (monitoreo semanal)
$default_intervals = array(
    array('from_wave' => 0, 'to_wave' => 1, 'days_after' => 7, 'time_unit' => 'days'),
    array('from_wave' => 1, 'to_wave' => 2, 'days_after' => 7, 'time_unit' => 'days'),
    array('from_wave' => 2, 'to_wave' => 3, 'days_after' => 7, 'time_unit' => 'days'),
    array('from_wave' => 3, 'to_wave' => 4, 'days_after' => 7, 'time_unit' => 'days'),
);

$timing_intervals = isset($step_data['timing_intervals']) ? $step_data['timing_intervals'] : $default_intervals;

// Ensure we have intervals for the number of waves
while (count($timing_intervals) < ($number_of_waves - 1)) {
    $last_interval = end($timing_intervals);
    $timing_intervals[] = array(
        'from_wave' => $last_interval['to_wave'],
        'to_wave' => $last_interval['to_wave'] + 1,
        'days_after' => 7,
        'time_unit' => 'days'
    );
}

// BUG FIX: Initialize variables to avoid PHP warnings
$retry_after_days = isset($step_data['retry_after_days']) ? intval($step_data['retry_after_days']) : 7;
$max_retries = isset($step_data['max_retries']) ? intval($step_data['max_retries']) : 3;
$investigator_notification_days = isset($step_data['investigator_notification_days']) ? intval($step_data['investigator_notification_days']) : 14;

?>
<div class="eipsi-wizard-step" id="step-3">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="3">
        
        <!-- Header del step -->
        <div class="eipsi-wiz-step-header">
            <p class="eipsi-wiz-step-title">Programación</p>
            <p class="eipsi-wiz-step-sub">Configura cuándo deben realizarse las evaluaciones y cómo manejar los recordatorios.</p>
        </div>
        
        <div class="timing-config">
            <!-- Timing Intervals -->
            <div class="timing-section" style="background:#f8f9fa;padding:20px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:20px;">
                <h3 style="margin:0 0 8px 0;color:#2c3e50;font-size:15px;font-weight:600;">Intervalos entre Tomas</h3>
                <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">Define cuánto tiempo debe pasar entre cada toma de evaluación.</p>
                
                <div class="intervals-list" id="intervals-list">
                    <?php for ($i = 0; $i < ($number_of_waves - 1); $i++): ?>
                        <div class="eipsi-interval-item">
                            <span class="eipsi-interval-label">T<?php echo $i + 1; ?> → T<?php echo $i + 2; ?></span>
                            <div class="eipsi-interval-controls">
                                <input type="number" 
                                       name="timing_intervals[<?php echo $i; ?>][days_after]"
                                       class="eipsi-interval-input"
                                       value="<?php echo isset($timing_intervals[$i]['days_after']) && $timing_intervals[$i]['days_after'] !== '' ? esc_attr($timing_intervals[$i]['days_after']) : '7'; ?>"
                                       min="1" 
                                       max="365">
                                <select name="timing_intervals[<?php echo $i; ?>][time_unit]"
                                        class="eipsi-wiz-select eipsi-interval-unit"
                                        onchange="handleTimeUnitChange(this)">
                                    <option value="days" <?php selected($timing_intervals[$i]['time_unit'] ?? 'days', 'days'); ?>>días</option>
                                    <option value="minutes" <?php selected($timing_intervals[$i]['time_unit'] ?? '', 'minutes'); ?>>minutos</option>
                                </select>
                                <span class="eipsi-interval-suffix">después</span>
                                <span class="eipsi-interval-equiv" style="display:none"></span>
                            </div>
                            <input type="hidden" 
                                   name="timing_intervals[<?php echo $i; ?>][from_wave]"
                                   value="<?php echo $timing_intervals[$i]['from_wave']; ?>">
                            <input type="hidden" 
                                   name="timing_intervals[<?php echo $i; ?>][to_wave]"
                                   value="<?php echo $timing_intervals[$i]['to_wave']; ?>">
                        </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Plantillas rápidas -->
                <div style="border-top:1px solid #e2e8f0;padding-top:16px;margin-top:16px;">
                    <h4 style="margin:0 0 12px 0;color:#2c3e50;font-size:13px;">Plantillas Rápidas:</h4>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="button"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            onmouseover="this.style.backgroundColor='#d6edff';this.style.borderColor='#3B6CAA';this.style.color='#1E3A5F'"
                            onmouseout="this.style.backgroundColor='#f1f5f9';this.style.borderColor='#e2e8f0';this.style.color='#2c3e50'"
                            onclick="eipsiApplyTimingTemplate('monitoreo_semanal', this)">
                            Semanal (7d c/u)
                        </button>
                        <button type="button"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            onmouseover="this.style.backgroundColor='#d6edff';this.style.borderColor='#3B6CAA';this.style.color='#1E3A5F'"
                            onmouseout="this.style.backgroundColor='#f1f5f9';this.style.borderColor='#e2e8f0';this.style.color='#2c3e50'"
                            onclick="eipsiApplyTimingTemplate('pre_post_follow', this)">
                            Pre-Post-Seguimiento
                        </button>
                        <button type="button"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            onmouseover="this.style.backgroundColor='#d6edff';this.style.borderColor='#3B6CAA';this.style.color='#1E3A5F'"
                            onmouseout="this.style.backgroundColor='#f1f5f9';this.style.borderColor='#e2e8f0';this.style.color='#2c3e50'"
                            onclick="eipsiApplyTimingTemplate('monthly', this)">
                            Mensual
                        </button>
                        <button type="button"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            onmouseover="this.style.backgroundColor='#d6edff';this.style.borderColor='#3B6CAA';this.style.color='#1E3A5F'"
                            onmouseout="this.style.backgroundColor='#f1f5f9';this.style.borderColor='#e2e8f0';this.style.color='#2c3e50'"
                            onclick="eipsiApplyTimingTemplate('quarterly', this)">
                            Trimestral
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Reminders & Retries -->
            <div class="reminders-section" style="background:#f8f9fa;padding:20px;border-radius:10px;border:1px solid #e2e8f0;">
                <h3 style="margin:0 0 8px 0;color:#2c3e50;font-size:15px;font-weight:600;">Recordatorios & Reintentos</h3>
                <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">Configura cómo y cuándo enviar recordatorios a los participantes.</p>
                
                <div class="reminder-config">
                    <div class="eipsi-wiz-field">
                        <label class="eipsi-wiz-label">Recordatorio de Nueva Toma</label>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;">
                            <p style="margin:0;font-size:13px;color:#64748b;line-height:1.5;">Los participantes recibirán un email automático <strong style="color:#2c3e50;">cuando la próxima toma esté disponible</strong> (según el intervalo configurado arriba).</p>
                        </div>
                        <input type="hidden" name="reminder_days_before" value="0">
                    </div>

                    <div class="eipsi-wiz-field">
                        <label class="eipsi-wiz-label">Si NO responde</label>
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" 
                                       name="enable_retries"
                                       <?php checked(!empty($step_data['enable_retries']), true); ?>
                                       value="1">
                                <span style="font-size:13px;color:#2c3e50;">Reintentar después de</span>
                            </label>
                            <input type="number" 
                                   name="retry_after_days" 
                                   class="eipsi-wiz-input" 
                                   style="width:80px;text-align:center;"
                                   value="<?php echo $retry_after_days; ?>"
                                   min="1" 
                                   max="60"
                                   <?php echo empty($step_data['enable_retries']) ? 'disabled' : ''; ?>>
                            <span style="font-size:13px;color:#64748b;">días</span>
                        </div>
                    </div>
                    
                    <div class="eipsi-wiz-field">
                        <label for="max_retries" class="eipsi-wiz-label">Máximo de reintentos</label>
                        <input type="number" 
                               id="max_retries"
                               name="max_retries" 
                               class="eipsi-wiz-input" 
                               style="width:100px;text-align:center;"
                               value="<?php echo $max_retries; ?>"
                               min="0" 
                               max="10">
                        <span class="eipsi-wiz-help">Número máximo de veces que se reenviará el recordatorio sin respuesta.</span>
                    </div>
                    
                    <div class="eipsi-wiz-field">
                        <label for="investigator_notification_days" class="eipsi-wiz-label">Notificar investigador después de</label>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <input type="number" 
                                   id="investigator_notification_days"
                                   name="investigator_notification_days" 
                                   class="eipsi-wiz-input" 
                                   style="width:100px;text-align:center;"
                                   value="<?php echo $investigator_notification_days; ?>"
                                   min="1" 
                                   max="90">
                            <span style="font-size:13px;color:#64748b;">días sin respuesta</span>
                        </div>
                        <span class="eipsi-wiz-help">El investigador recibirá una notificación si un participante no responde por X días.</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Autosave hint -->
    <div class="eipsi-wiz-autosave" id="eipsi-autosave-hint"></div>
    
    <!-- Tip box -->
    <div style="background:#f0f6fc;border:1px solid #AED6F1;border-radius:8px;padding:14px 16px;margin-top:16px;">
        <p style="font-size:12px;font-weight:500;color:#1E3A5F;margin-bottom:8px;">Consejos para tu estudio</p>
        <ul style="padding-left:16px;margin:0;">
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">Usá nombres claros para tus tomas: "Línea base", "Seguimiento 1", "Final"</li>
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">Programá recordatorios para mejorar la tasa de respuesta</li>
            <li style="font-size:12px;color:#64748b;line-height:1.4;">Podés agregar participantes después de activar el estudio</li>
        </ul>
    </div>
</div>

<script>
// Quick template functions - MANTENER TAL CUAL
function eipsiApplyTimingTemplate(template, btn) {
    const intervalsList = document.getElementById('intervals-list');
    const numberOfWaves = parseInt('<?php echo $number_of_waves; ?>');
    
    const templates = {
        'monitoreo_semanal': {
            2: [7],
            3: [7, 7],
            4: [7, 7, 7],
            5: [7, 7, 7, 7]
        },
        'pre_post_follow': {
            2: [7],
            3: [7, 30],
            4: [7, 30, 90],
            5: [7, 30, 60, 90]
        },
        'monthly': {
            2: [30],
            3: [30, 30],
            4: [30, 30, 30],
            5: [30, 30, 30, 30]
        },
        'quarterly': {
            2: [90],
            3: [90, 90],
            4: [90, 90, 90],
            5: [90, 90, 90, 90]
        }
    };
    
    if (templates[template] && templates[template][numberOfWaves]) {
        const intervals = templates[template][numberOfWaves];
        const inputs = intervalsList.querySelectorAll('input[name$="[days_after]"]');
        
        intervals.forEach((days, index) => {
            if (inputs[index]) {
                inputs[index].value = days;
                updateDayEquivalent(inputs[index]);
            }
        });
        
        // Reset all unit selectors to 'days'
        const unitSelects = intervalsList.querySelectorAll('select[name$="[time_unit]"]');
        unitSelects.forEach(select => {
            select.value = 'days';
        });
        
        // Visual feedback - ACTUALIZADO a color EIPSI #008080
        const button = btn || event.target;
        const originalText = button.textContent;
        button.textContent = 'Aplicado';
        button.style.background = '#008080';
        button.style.color = 'white';
        button.style.borderColor = '#008080';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '#f1f5f9';
            button.style.color = '#2c3e50';
            button.style.borderColor = '#e2e8f0';
        }, 2000);
    }
}

// Minutes to days conversion helpers
const MINUTES_PER_DAY = 1440;

function minutesToDays(minutes) {
    return Math.round(minutes / MINUTES_PER_DAY);
}

function daysToMinutes(days) {
    return days * MINUTES_PER_DAY;
}

function formatDayEquivalent(minutes) {
    const days = minutesToDays(minutes);
    if (days === 0) {
        return `${minutes} minutos`;
    }
    return `${minutes} minutos (${days} ${days === 1 ? 'día' : 'días'})`;
}

function updateDayEquivalent(input) {
    const container = input.closest('.eipsi-interval-controls');
    if (!container) return;
    
    const unitSelect = container.querySelector('select[name$="[time_unit]"]');
    const equivalentSpan = container.querySelector('.eipsi-interval-equiv');
    
    if (!equivalentSpan) return;
    
    const value = parseInt(input.value) || 0;
    const unit = unitSelect ? unitSelect.value : 'days';
    
    if (unit === 'minutes') {
        equivalentSpan.textContent = formatDayEquivalent(value);
        equivalentSpan.style.display = 'inline';
    } else {
        equivalentSpan.style.display = 'none';
    }
}

// Handle time unit change - ACTUALIZADO: buscar .eipsi-interval-equiv y .eipsi-interval-suffix
function handleTimeUnitChange(select) {
    const container = select.closest('.eipsi-interval-controls');
    const input = container.querySelector('input[name$="[days_after]"]');
    const equivalentSpan = container.querySelector('.eipsi-interval-equiv');
    const daysLabel = container.querySelector('.eipsi-interval-suffix');
    
    const unit = select.value;
    
    if (unit === 'minutes') {
        const currentDays = parseInt(input.value) || 7;
        input.value = daysToMinutes(currentDays);
        input.min = 1;
        input.max = 525600;
        daysLabel.textContent = 'minutos después';
        equivalentSpan.style.display = 'inline';
        updateDayEquivalent(input);
    } else {
        const currentMinutes = parseInt(input.value) || 10080;
        input.value = minutesToDays(currentMinutes);
        input.min = 1;
        input.max = 365;
        daysLabel.textContent = 'días después';
        equivalentSpan.style.display = 'none';
    }
}

// Handle retry checkbox
document.addEventListener('DOMContentLoaded', function() {
    const retryCheckbox = document.querySelector('input[name="enable_retries"]');
    const retryInput = document.querySelector('input[name="retry_after_days"]');
    
    if (retryCheckbox && retryInput) {
        retryCheckbox.addEventListener('change', function() {
            retryInput.disabled = !this.checked;
            if (!this.checked) {
                retryInput.value = '7';
            }
        });
    }
    
    // Initialize day equivalent displays
    document.querySelectorAll('.eipsi-interval-input').forEach(input => {
        input.addEventListener('input', function() {
            updateDayEquivalent(this);
        });
    });
});
</script>
