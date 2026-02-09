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

// Set default values
$reminder_days_before = isset($step_data['reminder_days_before']) ? intval($step_data['reminder_days_before']) : 3;
$retry_after_days = isset($step_data['retry_after_days']) ? intval($step_data['retry_after_days']) : 7;
$max_retries = isset($step_data['max_retries']) ? intval($step_data['max_retries']) : 3;
$investigator_notification_days = isset($step_data['investigator_notification_days']) ? intval($step_data['investigator_notification_days']) : 14;

// Default timing intervals between waves
$default_intervals = array(
    array('from_wave' => 0, 'to_wave' => 1, 'days_after' => 7),   // T1 to T2: 7 days
    array('from_wave' => 1, 'to_wave' => 2, 'days_after' => 30),  // T2 to T3: 30 days
    array('from_wave' => 2, 'to_wave' => 3, 'days_after' => 60),  // T3 to T4: 60 days
    array('from_wave' => 3, 'to_wave' => 4, 'days_after' => 90),  // T4 to T5: 90 days
);

$timing_intervals = isset($step_data['timing_intervals']) ? $step_data['timing_intervals'] : $default_intervals;

// Ensure we have intervals for the number of waves
while (count($timing_intervals) < ($number_of_waves - 1)) {
    $last_interval = end($timing_intervals);
    $timing_intervals[] = array(
        'from_wave' => $last_interval['to_wave'],
        'to_wave' => $last_interval['to_wave'] + 1,
        'days_after' => 30 // Default 30 days
    );
}

?>
<div class="eipsi-wizard-step" id="step-3">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="3">
        
        <div class="step-header">
            <h2>⏱️ TIMING ENTRE TOMAS</h2>
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
                                       value="<?php echo esc_attr($timing_intervals[$i]['days_after']); ?>"
                                       min="1" 
                                       max="365"
                                       class="interval-days-input">
                                <span class="days-label">días después</span>
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
                        <button type="button" class="template-btn" onclick="eipsiApplyTimingTemplate('pre_post_follow')">
                            📋 Pre-Post-Seguimiento (7d, 30d, 90d)
                        </button>
                        <button type="button" class="template-btn" onclick="eipsiApplyTimingTemplate('monthly')">
                            📊 Evaluaciones Mensuales
                        </button>
                        <button type="button" class="template-btn" onclick="eipsiApplyTimingTemplate('quarterly')">
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
                        <label for="reminder_days_before" class="form-label">
                            Enviar recordatorio
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   id="reminder_days_before"
                                   name="reminder_days_before" 
                                   class="config-input" 
                                   value="<?php echo $reminder_days_before; ?>"
                                   min="0" 
                                   max="30">
                            <span class="input-suffix">días ANTES del vencimiento</span>
                        </div>
                        <small class="form-help">
                            Los participantes recibirán un email de recordatorio antes de la fecha límite.
                        </small>
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
function eipsiApplyTimingTemplate(template) {
    const intervalsList = document.getElementById('intervals-list');
    const numberOfWaves = parseInt('<?php echo $number_of_waves; ?>');
    
    const templates = {
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
            }
        });
        
        // Visual feedback
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✅ Aplicado';
        btn.style.background = '#28a745';
        btn.style.color = 'white';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
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
    background: white;
    padding: 2rem;
    border-radius: 12px;
    border: 2px solid #e9ecef;
}

.timing-section h3,
.reminders-section h3 {
    margin: 0 0 0.5rem 0;
    color: #495057;
    font-size: 1.2rem;
    font-weight: 600;
}

.section-description {
    margin: 0 0 1.5rem 0;
    color: #6c757d;
    font-size: 0.95rem;
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
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.interval-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 120px;
}

.interval-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

.interval-arrow {
    color: #667eea;
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
    border: 2px solid #e9ecef;
    border-radius: 6px;
    text-align: center;
    font-weight: 600;
}

.days-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.quick-templates {
    border-top: 1px solid #dee2e6;
    padding-top: 1.5rem;
}

.quick-templates h4 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1rem;
}

.template-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.template-btn {
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.template-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
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
    border: 2px solid #e9ecef;
    border-radius: 6px;
    text-align: center;
    font-weight: 600;
}

.input-suffix {
    color: #6c757d;
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
    color: #495057;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .timing-section,
    .reminders-section {
        background: #2c3e50;
        border-color: #34495e;
    }
    
    .interval-item {
        background: #34495e;
        border-color: #4a5f7a;
    }
    
    .interval-label {
        color: #ecf0f1;
    }
    
    .interval-days-input,
    .config-input {
        background: #34495e;
        border-color: #4a5f7a;
        color: #ecf0f1;
    }
    
    .template-btn {
        background: #34495e;
        border-color: #4a5f7a;
        color: #ecf0f1;
    }
    
    .template-btn:hover {
        background: #667eea;
        border-color: #667eea;
        color: white;
    }
    
    .section-description,
    .days-label,
    .input-suffix {
        color: #95a5a6;
    }
    
    .checkbox-text {
        color: #ecf0f1;
    }
}
</style>