<?php
/**
 * Wizard Step 3: Timing entre Tomas (T1-Anchor System)
 * 
 * Template para configurar timing, recordatorios y reintentos.
 * Implementa el sistema de anclaje a T1 (offsets absolutos).
 *
 * @package EIPSI_Forms
 * @since 1.5.7
 */

if (!defined('ABSPATH')) {
    exit;
}

$step_data = isset($wizard_data['step_3']) ? $wizard_data['step_3'] : array();

// Get previous step data to build timing logic
$step_2_data = isset($wizard_data['step_2']) ? $wizard_data['step_2'] : array();
$number_of_waves = isset($step_2_data['number_of_waves']) ? intval($step_2_data['number_of_waves']) : 3;

// Default timing intervals (accumulated minutes from T1)
// T1 is always 0.
// T2 default: 7 days (10080 min)
// T3 default: 14 days (20160 min)
$timing_intervals = isset($step_data['timing_intervals']) ? $step_data['timing_intervals'] : array();

// If empty or old format, migrate/initialize
if (empty($timing_intervals)) {
    // Initial default: Weekly
    for ($i = 1; $i < $number_of_waves; $i++) {
        $timing_intervals[] = array(
            'wave_index' => $i,
            'offset_minutes' => $i * 10080,
            'time_unit' => 'days'
        );
    }
    // Add closure
    $timing_intervals[] = array(
        'wave_index' => 'closure',
        'offset_minutes' => $number_of_waves * 10080,
        'time_unit' => 'days'
    );
}

// Phase 5 T1-Anchor: Legacy retry fields (now hidden, replaced by nudges)
$retry_after_days = isset($step_data['retry_after_days']) ? intval($step_data['retry_after_days']) : 7;
$max_retries = isset($step_data['max_retries']) ? intval($step_data['max_retries']) : 3;
$investigator_notification_days = isset($step_data['investigator_notification_days']) ? intval($step_data['investigator_notification_days']) : 14;

?>
<style>
    .eipsi-timeline-preview {
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        margin-top: 24px;
    }
    .timeline-title {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .timeline-container {
        display: flex;
        flex-direction: column;
        gap: 0;
        position: relative;
        padding-left: 20px;
    }
    .timeline-container::before {
        content: '';
        position: absolute;
        left: 5px;
        top: 10px;
        bottom: 10px;
        width: 2px;
        background: #e2e8f0;
    }
    .timeline-event {
        position: relative;
        padding-bottom: 20px;
        padding-left: 20px;
    }
    .timeline-event:last-child {
        padding-bottom: 0;
    }
    .timeline-dot {
        position: absolute;
        left: -20px;
        top: 6px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #3B6CAA;
        z-index: 1;
    }
    .timeline-event.closure .timeline-dot {
        border-color: #64748b;
        background: #64748b;
    }
    .timeline-content {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }
    .timeline-label {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
    }
    .timeline-time {
        font-size: 12px;
        color: #64748b;
        font-family: monospace;
    }
    .timeline-gap {
        font-size: 11px;
        color: #94a3b8;
        font-style: italic;
        margin-top: -15px;
        margin-bottom: 10px;
        padding-left: 20px;
    }
    .eipsi-interval-equiv {
        display: block;
        font-size: 11px;
        color: #008080;
        margin-top: 4px;
        font-weight: 500;
    }
</style>

<div class="eipsi-wizard-step" id="step-3">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="3">
        
        <!-- Header del step -->
        <div class="eipsi-wiz-step-header">
            <p class="eipsi-wiz-step-title">Programación (Anclaje T1)</p>
            <p class="eipsi-wiz-step-sub">Define el momento exacto de cada toma contando desde el inicio (T1).</p>
        </div>
        
        <div class="timing-config">
            <!-- Timing Intervals -->
            <div class="timing-section" style="background:#f8f9fa;padding:20px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:20px;">
                <h3 style="margin:0 0 8px 0;color:#2c3e50;font-size:15px;font-weight:600;">Línea de Tiempo del Estudio</h3>
                <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">T1 ocurre inmediatamente al iniciar. Configura las siguientes tomas:</p>
                
                <div class="intervals-list" id="intervals-list">
                    <!-- T1 is always 0 -->
                    <input type="hidden" name="wave_index[]" value="0">
                    <input type="hidden" name="offset_minutes[]" value="0" class="eipsi-hidden-offset">

                    <?php 
                    // Helper to find existing offset for a wave
                    function get_offset_for_wave($index, $intervals) {
                        foreach ($intervals as $interval) {
                            if (isset($interval['wave_index']) && $interval['wave_index'] == $index) {
                                return intval($interval['offset_minutes']);
                            }
                        }
                        return $index * 10080; // Default weekly
                    }

                    for ($i = 1; $i < $number_of_waves; $i++): 
                        $current_offset = get_offset_for_wave($i, $timing_intervals);
                        // Determine display unit
                        $unit = 'days';
                        $display_val = round($current_offset / 1440);
                        if ($current_offset % 1440 !== 0) {
                            $unit = 'minutes';
                            $display_val = $current_offset;
                        }
                    ?>
                        <div class="eipsi-interval-item" data-wave-index="<?php echo $i; ?>">
                            <span class="eipsi-interval-label">T<?php echo $i + 1; ?> desde T1</span>
                            <div class="eipsi-interval-controls">
                                <input type="number" 
                                       class="eipsi-interval-input"
                                       value="<?php echo $display_val; ?>"
                                       min="1" 
                                       oninput="eipsiSyncOffset(this)">
                                <select class="eipsi-wiz-select eipsi-interval-unit"
                                        onchange="eipsiSyncOffset(this)">
                                    <option value="days" <?php selected($unit, 'days'); ?>>días</option>
                                    <option value="minutes" <?php selected($unit, 'minutes'); ?>>minutos</option>
                                </select>
                                <span class="eipsi-interval-equiv"></span>
                                
                                <input type="hidden" name="wave_index[]" value="<?php echo $i; ?>">
                                <input type="hidden" name="offset_minutes[]" value="<?php echo $current_offset; ?>" class="eipsi-hidden-offset">
                            </div>
                        </div>
                    <?php endfor; ?>

                    <!-- Cierre del Estudio -->
                    <?php 
                        $closure_offset = get_offset_for_wave('closure', $timing_intervals);
                        $unit_c = 'days';
                        $display_val_c = round($closure_offset / 1440);
                        if ($closure_offset % 1440 !== 0) {
                            $unit_c = 'minutes';
                            $display_val_c = $closure_offset;
                        }
                    ?>
                    <div class="eipsi-interval-item closure" style="border-top: 2px solid #e2e8f0; padding-top: 15px; margin-top: 10px;">
                        <span class="eipsi-interval-label" style="font-weight:700;">🔒 Cierre del estudio</span>
                        <div class="eipsi-interval-controls">
                            <input type="number" 
                                   class="eipsi-interval-input"
                                   id="eipsi-closure-input"
                                   value="<?php echo $display_val_c; ?>"
                                   min="1"
                                   oninput="eipsiSyncOffset(this)">
                            <select class="eipsi-wiz-select eipsi-interval-unit"
                                    onchange="eipsiSyncOffset(this)">
                                <option value="days" <?php selected($unit_c, 'days'); ?>>días</option>
                                <option value="minutes" <?php selected($unit_c, 'minutes'); ?>>minutos</option>
                            </select>
                            <span class="eipsi-interval-equiv"></span>
                            
                            <input type="hidden" name="wave_index[]" value="closure">
                            <input type="hidden" name="offset_minutes[]" value="<?php echo $closure_offset; ?>" class="eipsi-hidden-offset">
                            <input type="hidden" name="study_end_offset_minutes" id="study-end-offset-minutes" value="<?php echo $closure_offset; ?>">
                        </div>
                        <small style="display:block;margin-top:6px;color:#64748b;font-size:12px;">
                            ℹ️ Se calcula automáticamente sumando el intervalo de la última toma. Podés ajustarlo manualmente.
                        </small>
                    </div>
                </div>
                
                <!-- Timeline Preview -->
                <div id="eipsi-timeline-preview" class="eipsi-timeline-preview">
                    <div class="timeline-title">
                        <span>📊 Vista previa de la agenda</span>
                    </div>
                    <div class="timeline-container" id="timeline-container">
                        <!-- Dynamic content -->
                    </div>
                </div>

                <!-- Plantillas rápidas -->
                <div style="border-top:1px solid #e2e8f0;padding-top:16px;margin-top:16px;">
                    <h4 style="margin:0 0 12px 0;color:#2c3e50;font-size:13px;">Plantillas Rápidas:</h4>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="button"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            onclick="eipsiApplyTimingTemplate('semanal_7x', this)">
                            📅 Semanal (7×X)
                        </button>
                        <button type="button"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            onclick="eipsiApplyTimingTemplate('quincenal_14x', this)">
                            📆 Quincenal (14×X)
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Recordatorios Automáticos -->
            <div class="reminders-section" style="background:#f0f9ff;padding:20px;border-radius:10px;border:1px solid #bae6fd;">
                <h3 style="margin:0 0 8px 0;color:#0c4a6e;font-size:15px;font-weight:600;">📧 Recordatorios Automáticos</h3>
                <p style="margin:0 0 16px 0;color:#0369a1;font-size:13px;">Los participantes recibirán recordatorios automáticos para completar cada toma.</p>
                
                <div class="reminder-config">
                    <div class="eipsi-wiz-field">
                        <label class="eipsi-wiz-label" style="color:#0c4a6e;">✅ Sistema de Recordatorios Inteligente</label>
                        <div style="background:#fff;border:1px solid #bae6fd;border-radius:8px;padding:14px 16px;">
                            <p style="margin:0 0 10px 0;font-size:13px;color:#0369a1;line-height:1.6;">
                                <strong style="color:#0c4a6e;">📬 Notificación de disponibilidad:</strong> Los participantes recibirán un email automático cuando cada toma esté disponible (según el intervalo configurado arriba).
                            </p>
                            <p style="margin:0;font-size:13px;color:#0369a1;line-height:1.6;">
                                <strong style="color:#0c4a6e;">🔔 Recordatorios de seguimiento:</strong> Si el participante no responde, se enviarán automáticamente 4 recordatorios distribuidos proporcionalmente en el tiempo disponible hasta la próxima toma. Por ejemplo, si el intervalo es de 7 días, los recordatorios se enviarán aproximadamente al 15%, 40%, 70% y 90% del intervalo. Podrás ajustar estos tiempos en el Dashboard del estudio.
                            </p>
                        </div>
                        <input type="hidden" name="reminder_days_before" value="0">
                        <input type="hidden" name="enable_retries" value="0">
                        <input type="hidden" name="retry_after_days" value="7">
                        <input type="hidden" name="max_retries" value="3">
                        <input type="hidden" name="investigator_notification_days" value="14">
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Autosave hint -->
    <div class="eipsi-wiz-autosave" id="eipsi-autosave-hint"></div>
</div>

<script>
/**
 * EIPSI Timing Logic - T1-Anchor System
 */

const MINUTES_PER_DAY = 1440;

/**
 * Human readable duration formatter
 */
function eipsiFormatDuration(minutes) {
    if (minutes === 0) return 'T1 (Inicio)';
    
    const weeks = Math.floor(minutes / 10080);
    const days = Math.floor((minutes % 10080) / 1440);
    const hours = Math.floor((minutes % 1440) / 60);
    const mins = minutes % 60;
    
    let parts = [];
    if (weeks > 0) parts.push(weeks === 1 ? '1 semana' : `${weeks} semanas`);
    if (days > 0) parts.push(days === 1 ? '1 día' : `${days} días`);
    if (hours > 0) parts.push(hours === 1 ? '1 hora' : `${hours} horas`);
    if (mins > 0) parts.push(mins === 1 ? '1 minuto' : `${mins} minutos`);
    
    return parts.length > 0 ? parts.join(', ') : '0 min';
}

/**
 * Synchronize input with hidden offset minutes
 */
function eipsiSyncOffset(element) {
    const item = element.closest('.eipsi-interval-item');
    if (!item) return;
    
    const input = item.querySelector('.eipsi-interval-input');
    const unitSelect = item.querySelector('.eipsi-interval-unit');
    const hiddenOffset = item.querySelector('.eipsi-hidden-offset');
    const equivSpan = item.querySelector('.eipsi-interval-equiv');
    
    const value = parseInt(input.value) || 0;
    const unit = unitSelect.value;
    
    const totalMinutes = (unit === 'days') ? value * MINUTES_PER_DAY : value;
    hiddenOffset.value = totalMinutes;
    
    // Update equivalent label
    equivSpan.textContent = eipsiFormatDuration(totalMinutes);
    
    // If it's not the closure, we might want to auto-update closure
    if (!item.classList.contains('closure')) {
        eipsiAutoUpdateClosure();
    } else {
        // Phase 3 T1-Anchor: If closure changed manually, sync study_end_offset_minutes
        const studyEndField = document.getElementById('study-end-offset-minutes');
        if (studyEndField) {
            studyEndField.value = totalMinutes;
        }
    }
    
    eipsiUpdateTimelinePreview();
    
    // Trigger dirty state for wizard
    if (window.jQuery) {
        window.jQuery('#eipsi-wizard-form').trigger('change');
    }
}

/**
 * Auto-calculate study closure based on last wave gap
 * Phase 3 T1-Anchor: Auto-calculates study_end_offset_minutes
 */
function eipsiAutoUpdateClosure() {
    const hiddenOffsets = Array.from(document.querySelectorAll('.eipsi-hidden-offset'));
    if (hiddenOffsets.length < 2) return;
    
    // Penultimate is the last wave, last is the closure
    const lastWaveOffset = parseInt(hiddenOffsets[hiddenOffsets.length - 2].value) || 0;
    const penultimateWaveOffset = (hiddenOffsets.length > 2) ? parseInt(hiddenOffsets[hiddenOffsets.length - 3].value) || 0 : 0;
    
    let gap = lastWaveOffset - penultimateWaveOffset;
    if (gap <= 0) gap = 10080; // Default 7 days if something is wrong
    
    const closureOffset = lastWaveOffset + gap;
    
    const closureItem = document.querySelector('.eipsi-interval-item.closure');
    if (closureItem) {
        const closureHidden = closureItem.querySelector('.eipsi-hidden-offset');
        const closureInput = closureItem.querySelector('.eipsi-interval-input');
        const closureUnit = closureItem.querySelector('.eipsi-interval-unit');
        
        closureHidden.value = closureOffset;
        
        if (closureUnit.value === 'days') {
            closureInput.value = Math.round(closureOffset / MINUTES_PER_DAY);
        } else {
            closureInput.value = closureOffset;
        }
        
        closureItem.querySelector('.eipsi-interval-equiv').textContent = eipsiFormatDuration(closureOffset);
        
        // Phase 3 T1-Anchor: Sync study_end_offset_minutes field
        const studyEndField = document.getElementById('study-end-offset-minutes');
        if (studyEndField) {
            studyEndField.value = closureOffset;
        }
    }
}

/**
 * Update Timeline Preview Component
 */
function eipsiUpdateTimelinePreview() {
    const container = document.getElementById('timeline-container');
    if (!container) return;
    
    const hiddenOffsets = Array.from(document.querySelectorAll('.eipsi-hidden-offset'));
    const indices = Array.from(document.querySelectorAll('input[name="wave_index[]"]'));
    
    let html = '';
    let previousOffset = 0;
    
    // T1 is always first
    html += `
        <div class="timeline-event">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <span class="timeline-label">T1 (Inicio del estudio)</span>
                <span class="timeline-time">Día 0</span>
            </div>
        </div>
    `;
    
    indices.forEach((input, i) => {
        const index = input.value;
        if (index === '0') return; // Skip T1 as it's already added
        
        const offset = parseInt(hiddenOffsets[i].value) || 0;
        const isClosure = index === 'closure';
        const label = isClosure ? 'Cierre del estudio' : `Toma ${parseInt(index) + 1}`;
        const dayLabel = `Día ${Math.floor(offset / MINUTES_PER_DAY)}`;
        
        // Add gap info
        const gap = offset - previousOffset;
        if (gap > 0) {
            html += `<div class="timeline-gap">... espera de ${eipsiFormatDuration(gap)} ...</div>`;
        }
        
        html += `
            <div class="timeline-event ${isClosure ? 'closure' : ''}">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <span class="timeline-label">${label}</span>
                    <span class="timeline-time">${dayLabel}</span>
                </div>
            </div>
        `;
        
        previousOffset = offset;
    });
    
    container.innerHTML = html;
}

/**
 * Apply Timing Templates (Accumulated)
 */
function eipsiApplyTimingTemplate(template, btn) {
    const numberOfWaves = parseInt('<?php echo $number_of_waves; ?>');
    
    // Base intervals in days (converted to accumulated below)
    const baseGaps = {
        'semanal_7x': [7, 7, 7, 7, 7, 7, 7, 7, 7, 7],
        'quincenal_14x': [14, 14, 14, 14, 14, 14, 14, 14, 14, 14]
    };
    
    if (baseGaps[template]) {
        const gaps = baseGaps[template];
        const inputs = document.querySelectorAll('.eipsi-interval-item:not(.closure) .eipsi-interval-input');
        const units = document.querySelectorAll('.eipsi-interval-item:not(.closure) .eipsi-interval-unit');
        
        let accumulatedDays = 0;
        
        inputs.forEach((input, i) => {
            accumulatedDays += gaps[i] || 7;
            input.value = accumulatedDays;
            if (units[i]) units[i].value = 'days';
            
            // Sync this input
            eipsiSyncOffset(input);
        });
        
        // Feedback
        const originalText = btn.textContent;
        btn.textContent = 'Aplicado';
        btn.style.background = '#008080';
        btn.style.color = 'white';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    }
}

// Initial update
document.addEventListener('DOMContentLoaded', () => {
    // Initial sync for all inputs
    document.querySelectorAll('.eipsi-interval-input').forEach(input => {
        const item = input.closest('.eipsi-interval-item');
        if (item) {
            const hidden = item.querySelector('.eipsi-hidden-offset');
            const equiv = item.querySelector('.eipsi-interval-equiv');
            if (hidden && equiv) {
                equiv.textContent = eipsiFormatDuration(parseInt(hidden.value));
            }
        }
    });
    
    eipsiUpdateTimelinePreview();
});

</script>
