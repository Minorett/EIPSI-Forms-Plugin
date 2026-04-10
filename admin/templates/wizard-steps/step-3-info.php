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

// Default timing intervals between waves - ✅ v1.5.6: Todos 7 días por defecto (monitoreo semanal)
$default_intervals = array(
    array('from_wave' => 0, 'to_wave' => 1, 'days_after' => 7, 'time_unit' => 'days'),  // T1 to T2: 7 days
    array('from_wave' => 1, 'to_wave' => 2, 'days_after' => 7, 'time_unit' => 'days'),  // T2 to T3: 7 days
    array('from_wave' => 2, 'to_wave' => 3, 'days_after' => 7, 'time_unit' => 'days'),  // T3 to T4: 7 days
    array('from_wave' => 3, 'to_wave' => 4, 'days_after' => 7, 'time_unit' => 'days'),  // T4 to T5: 7 days
);

$timing_intervals = isset($step_data['timing_intervals']) ? $step_data['timing_intervals'] : $default_intervals;

// Ensure we have intervals for the number of waves
while (count($timing_intervals) < ($number_of_waves - 1)) {
    $last_interval = end($timing_intervals);
    $timing_intervals[] = array(
        'from_wave' => $last_interval['to_wave'],
        'to_wave' => $last_interval['to_wave'] + 1,
        'days_after' => 7, // ✅ v1.5.6: Default 7 días (no 30)
        'time_unit' => 'days' // Default time unit
    );
}

?>
<div class="eipsi-wizard-step" id="step-3">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="3">
        
        <div class="step-header">
            <h2>⏰ Programación Temporal</h2>
            <p>Configura cuándo deben realizarse las evaluaciones y cómo manejar los recordatorios.</p>
        </div>
        
        <div class="timing-config">
            <!-- Timing Intervals -->
            <div class="timing-section">
                <h3>📅 Intervalos entre Tomas</h3>
                <p class="section-description">Define cuánto tiempo debe pasar entre cada toma de evaluación.</p>
                
                <div class="intervals-list" id="intervals-list">
                    <?php for ($i = 0; $i < ($number_of_waves - 1); $i++): ?>
                        <div class="interval-item">
                            <div class="interval-header">
                                <span class="interval-label">
                                    Toma <?php echo $i + 1; ?> → Toma <?php echo $i + 2; ?>
                                </span>
                                <span class="interval-arrow">→</span>
                            </div>
                            <div class="interval-input">
                                <input type="number" 
                                       name="timing_intervals[<?php echo $i; ?>][days_after]"
                                       value="<?php echo isset($timing_intervals[$i]['days_after']) && $timing_intervals[$i]['days_after'] !== '' ? esc_attr($timing_intervals[$i]['days_after']) : '7'; ?>"
                                       min="1" 
                                       max="365"
                                       class="interval-days-input">
                                <select name="timing_intervals[<?php echo $i; ?>][time_unit]"
                                        class="interval-unit-select"
                                        onchange="handleTimeUnitChange(this)">
                                    <option value="days" <?php selected($timing_intervals[$i]['time_unit'] ?? 'days', 'days'); ?>>días</option>
                                    <option value="minutes" <?php selected($timing_intervals[$i]['time_unit'] ?? '', 'minutes'); ?>>minutos</option>
                                </select>
                                <span class="days-label">después</span>
                                <span class="day-equivalent" style="display: none;"></span>
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
                
                <div class="quick-templates">
                    <h4>Plantillas Rápidas:</h4>
                    <div class="template-buttons">
                        <button type="button" class="template-btn" onclick="eipsiApplyTimingTemplate('monitoreo_semanal', this)">
                            📅 Monitoreo Semanal (7d c/u)
                        </button>
                        <button type="button" class="template-btn" onclick="eipsiApplyTimingTemplate('pre_post_follow', this)">
                            📋 Pre-Post-Seguimiento (7d, 30d, 90d)
                        </button>
                        <button type="button" class="template-btn" onclick="eipsiApplyTimingTemplate('monthly', this)">
                            📊 Evaluaciones Mensuales
                        </button>
                        <button type="button" class="template-btn" onclick="eipsiApplyTimingTemplate('quarterly', this)">
                            📈 Evaluaciones Trimestrales
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Reminders & Retries -->
            <div class="reminders-section">
                <h3>📧 Recordatorios & Reintentos</h3>
                <p class="section-description">Configura cómo y cuándo enviar recordatorios a los participantes.</p>
                
                <div class="reminder-config">
                    <div class="config-item">
                        <label class="form-label">
                            📧 Recordatorio de Nueva Toma
                        </label>
                        <div class="info-box">
                            <p>Los participantes recibirán un email automático <strong>cuando la próxima toma esté disponible</strong> (según el intervalo configurado arriba).</p>
                        </div>
                        <input type="hidden" name="reminder_days_before" value="0">
                    </div>

                    <div class="config-item">
                        <label for="retry_after_days" class="form-label">
                            Si NO responde
                        </label>
                        <div class="retry-config">
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       name="enable_retries"
                                       <?php checked(!empty($step_data['enable_retries']), true); ?>
                                       value="1">
                                <span class="checkbox-text">Reintentar después de</span>
                            </label>
                            <input type="number" 
                                   name="retry_after_days" 
                                   class="config-input retry-input" 
                                   value="<?php echo $retry_after_days; ?>"
                                   min="1" 
                                   max="60"
                                   <?php echo empty($step_data['enable_retries']) ? 'disabled' : ''; ?>>
                            <span class="input-suffix">días</span>
                        </div>
                    </div>
                    
                    <div class="config-item">
                        <label for="max_retries" class="form-label">
                            Máximo de reintentos
                        </label>
                        <input type="number" 
                               id="max_retries"
                               name="max_retries" 
                               class="config-input" 
                               value="<?php echo $max_retries; ?>"
                               min="0" 
                               max="10">
                        <small class="form-help">
                            Número máximo de veces que se reenviará el recordatorio sin respuesta.
                        </small>
                    </div>
                    
                    <div class="config-item">
                        <label for="investigator_notification_days" class="form-label">
                            Notificar investigador después de
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   id="investigator_notification_days"
                                   name="investigator_notification_days" 
                                   class="config-input" 
                                   value="<?php echo $investigator_notification_days; ?>"
                                   min="1" 
                                   max="90">
                            <span class="input-suffix">días sin respuesta</span>
                        </div>
                        <small class="form-help">
                            El investigador recibirá una notificación si un participante no responde por X días.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Quick template functions
function eipsiApplyTimingTemplate(template, btn) {
    const intervalsList = document.getElementById('intervals-list');
    const numberOfWaves = parseInt('<?php echo $number_of_waves; ?>');
    
    const templates = {
        'monitoreo_semanal': {
            2: [7], // T1->T2: 7 days
            3: [7, 7], // T1->T2: 7d, T2->T3: 7d
            4: [7, 7, 7], // T1->T2: 7d, T2->T3: 7d, T3->T4: 7d
            5: [7, 7, 7, 7] // T1->T2: 7d, T2->T3: 7d, T3->T4: 7d, T4->T5: 7d
        },
        'pre_post_follow': {
            2: [7], // T1->T2: 7 days
            3: [7, 30], // T1->T2: 7d, T2->T3: 30d
            4: [7, 30, 90], // T1->T2: 7d, T2->T3: 30d, T3->T4: 90d
            5: [7, 30, 60, 90] // T1->T2: 7d, T2->T3: 30d, T3->T4: 60d, T4->T5: 90d
        },
        'monthly': {
            2: [30], // T1->T2: 30 days
            3: [30, 30], // T1->T2: 30d, T2->T3: 30d
            4: [30, 30, 30], // T1->T2: 30d, T2->T3: 30d, T3->T4: 30d
            5: [30, 30, 30, 30] // T1->T2: 30d, T2->T3: 30d, T3->T4: 30d, T4->T5: 30d
        },
        'quarterly': {
            2: [90], // T1->T2: 90 days
            3: [90, 90], // T1->T2: 90d, T2->T3: 90d
            4: [90, 90, 90], // T1->T2: 90d, T2->T3: 90d, T3->T4: 90d
            5: [90, 90, 90, 90] // T1->T2: 90d, T2->T3: 90d, T3->T4: 90d, T4->T5: 90d
        }
    };
    
    if (templates[template] && templates[template][numberOfWaves]) {
        const intervals = templates[template][numberOfWaves];
        const inputs = intervalsList.querySelectorAll('input[name$="[days_after]"]');
        
        intervals.forEach((days, index) => {
            if (inputs[index]) {
                inputs[index].value = days;
                // Update day equivalent display
                updateDayEquivalent(inputs[index]);
            }
        });
        
        // Reset all unit selectors to 'days'
        const unitSelects = intervalsList.querySelectorAll('select[name$="[time_unit]"]');
        unitSelects.forEach(select => {
            select.value = 'days';
        });
        
        // Visual feedback
        const button = btn || event.target;
        const originalText = button.textContent;
        button.textContent = '✅ Aplicado';
        button.style.background = '#28a745';
        button.style.color = 'white';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
            button.style.color = '';
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
    const container = input.closest('.interval-input');
    if (!container) return;
    
    const unitSelect = container.querySelector('select[name$="[time_unit]"]');
    const equivalentSpan = container.querySelector('.day-equivalent');
    
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

// Handle time unit change
function handleTimeUnitChange(select) {
    const container = select.closest('.interval-input');
    const input = container.querySelector('input[name$="[days_after]"]');
    const equivalentSpan = container.querySelector('.day-equivalent');
    const daysLabel = container.querySelector('.days-label');
    
    const unit = select.value;
    
    if (unit === 'minutes') {
        // Convert current days to minutes for display
        const currentDays = parseInt(input.value) || 7;
        input.value = daysToMinutes(currentDays);
        input.min = 1;
        input.max = 525600; // 1 year in minutes
        daysLabel.textContent = 'minutos después';
        equivalentSpan.style.display = 'inline';
        updateDayEquivalent(input);
    } else {
        // Convert current minutes to days
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
                retryInput.value = '7'; // Reset to default
            }
        });
    }
    
    // Initialize day equivalent displays
    document.querySelectorAll('.interval-days-input').forEach(input => {
        input.addEventListener('input', function() {
            updateDayEquivalent(this);
        });
    });
});
</script>

<style>
.timing-config {
    max-width: 900px;
    margin: 0 auto;
    display: grid;
    gap: 2rem;
}

.timing-section,
.reminders-section {
    background: #f8fafc;
    padding: 2rem;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    color: #1e293b;
}

.timing-section h3,
.reminders-section h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1.2rem;
    font-weight: 600;
}

.section-description {
    margin: 0 0 1.5rem 0;
    color: #64748b;
    font-size: 0.95rem;
}

.timing-section .form-label,
.reminders-section .form-label {
    color: #1e293b;
}

.timing-section .form-help,
.reminders-section .form-help {
    color: #64748b;
}

.intervals-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.interval-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    color: #1e293b;
}

.interval-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 120px;
}

.interval-label {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
}

.interval-arrow {
    color: #64748b;
    font-weight: bold;
    font-size: 1.1rem;
}

.interval-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.interval-days-input {
    width: 80px;
    padding: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    text-align: center;
    font-weight: 600;
    background: #ffffff;
    color: #1e293b;
}

.interval-days-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.interval-unit-select {
    padding: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    background: #ffffff;
    color: #1e293b;
    font-size: 0.9rem;
    cursor: pointer;
}

.interval-unit-select:focus {
    outline: none;
    border-color: #3b82f6;
}

.days-label {
    color: #64748b;
    font-size: 0.9rem;
}

.day-equivalent {
    color: #28a745;
    font-size: 0.85rem;
    font-weight: 500;
    margin-left: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(40, 167, 69, 0.15);
    border-radius: 4px;
    white-space: nowrap;
}

.quick-templates {
    border-top: 1px solid #e2e8f0;
    padding-top: 1.5rem;
}

.quick-templates h4 {
    margin: 0 0 1rem 0;
    color: #1e293b;
    font-size: 1rem;
}

.template-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.template-btn {
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    white-space: nowrap;
    color: #1e293b;
}

.template-btn:hover {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.reminder-config {
    display: grid;
    gap: 1.5rem;
}

.config-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.input-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.config-input {
    width: 100px;
    padding: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    text-align: center;
    font-weight: 600;
    background: #ffffff;
    color: #1e293b;
}

.config-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.input-suffix {
    color: #64748b;
    font-size: 0.9rem;
}

.retry-config {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.retry-input {
    width: 80px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 0.5rem;
    transform: scale(1.1);
}

.checkbox-text {
    font-weight: 500;
    color: #1e293b;
}

/* Dark mode support - DISABLED for admin */
@media (prefers-color-scheme: dark) {
    /* Keep light theme in admin for readability */
    
    .template-btn:hover {
        background: #4a6fa5;
        border-color: #4a6fa5;
        color: white;
    }
}
</style>