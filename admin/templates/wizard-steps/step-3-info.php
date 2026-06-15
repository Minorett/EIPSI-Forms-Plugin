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

// Debug: Log timing_intervals format
if (!empty($timing_intervals)) {
    error_log('[EIPSI STEP3] Loading timing_intervals: ' . json_encode($timing_intervals));
}

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

/**
 * Format duration in minutes to human-readable Spanish
 */
if (!function_exists('eipsi_format_duration_human')) {
    function eipsi_format_duration_human($minutes) {
        if ($minutes <= 0) return '0 minutos';
        
        $weeks = floor($minutes / 10080);
        $days = floor(($minutes % 10080) / 1440);
        $hours = floor(($minutes % 1440) / 60);
        $mins = $minutes % 60;
        
        $parts = [];
        if ($weeks > 0) $parts[] = $weeks === 1 ? '1 semana' : "{$weeks} semanas";
        if ($days > 0) $parts[] = $days === 1 ? '1 día' : "{$days} días";
        if ($hours > 0) $parts[] = $hours === 1 ? '1 hora' : "{$hours} horas";
        if ($mins > 0) $parts[] = $mins === 1 ? '1 minuto' : "{$mins} minutos";
        
        return implode(', ', $parts);
    }
}
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
        display: none; /* Replaced by dynamic vertical bars */
    }
    .timeline-event {
        position: relative;
        padding-bottom: 70px; /* Space to fit the 60px visual bars and avoid overlaps */
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
    .timeline-resource-info {
        display: block;
        font-size: 11px;
        color: #0d9488; /* Elegant teal/cyan clinical color */
        font-weight: 500;
        margin-top: 4px;
    }
    .timeline-bars {
        position: absolute;
        left: -16px;
        top: 20px;
        width: 4px;
        display: flex;
        flex-direction: column;
        z-index: 0;
    }
    .bar-solid {
        width: 100%;
        background-color: #3B6CAA; /* EIPSI brand blue */
        border-radius: 2px;
    }
    .bar-dotted {
        width: 100%;
        background-image: linear-gradient(to bottom, #cbd5e1 50%, transparent 50%);
        background-size: 4px 8px;
        background-repeat: repeat-y;
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
                        // Handle 'closure' special case
                        if ($index === 'closure') {
                            // Look for study_end_offset_minutes in step_data
                            global $wizard_data;
                            $step_data = isset($wizard_data['step_3']) ? $wizard_data['step_3'] : array();
                            $closure_offset = isset($step_data['study_end_offset_minutes']) ? intval($step_data['study_end_offset_minutes']) : 0;
                            error_log(sprintf('[EIPSI STEP3] Loading closure offset: %d min', $closure_offset));
                            return $closure_offset;
                        }
                        
                        // Handle other string indices
                        if (!is_numeric($index)) {
                            error_log(sprintf('[EIPSI STEP3] Unknown string index: %s, returning 0', $index));
                            return 0;
                        }
                        
                        $index = intval($index);
                        
                        foreach ($intervals as $interval) {
                            // New format: from_wave/to_wave
                            if (isset($interval['to_wave']) && intval($interval['to_wave']) == $index) {
                                $offset = isset($interval['offset_minutes']) ? intval($interval['offset_minutes']) : 0;
                                error_log(sprintf('[EIPSI STEP3] Found offset for wave %d (new format): %d min', $index, $offset));
                                return $offset;
                            }
                            // Legacy format: wave_index
                            if (isset($interval['wave_index']) && intval($interval['wave_index']) == $index) {
                                $offset = isset($interval['offset_minutes']) ? intval($interval['offset_minutes']) : 0;
                                error_log(sprintf('[EIPSI STEP3] Found offset for wave %d (legacy format): %d min', $index, $offset));
                                return $offset;
                            }
                        }
                        
                        // Default: weekly intervals
                        $default_offset = $index * 10080;
                        error_log(sprintf('[EIPSI STEP3] No offset found for wave %d, using default: %d min', $index, $default_offset));
                        return $default_offset;
                    }

                    <?php 
                    // Pre-calculate all offsets including closure for window defaults
                    $all_offsets = array();
                    for ($i = 1; $i < $number_of_waves; $i++) {
                        $all_offsets[$i] = get_offset_for_wave($i, $timing_intervals);
                    }
                    $closure_offset_final = get_offset_for_wave('closure', $timing_intervals);
                    
                    for ($i = 1; $i < $number_of_waves; $i++): 
                        $current_offset = $all_offsets[$i];
                        
                        // Determine display unit
                        $unit = 'days';
                        $display_val = round($current_offset / 1440);
                        if ($current_offset % 1440 !== 0) {
                            $unit = 'minutes';
                            $display_val = $current_offset;
                        }
                        
                        // Calculate maxAllowed: next_offset - current_offset
                        // For last wave, use closure offset as next_offset
                        $is_last_wave = ($i === $number_of_waves - 1);
                        $next_offset = $is_last_wave ? $closure_offset_final : $all_offsets[$i + 1];
                        $max_allowed_window = $next_offset - $current_offset;
                        if ($max_allowed_window <= 0) $max_allowed_window = $current_offset; // Fallback safety
                        
                        // Default window = maxAllowed
                        $default_window = $max_allowed_window;
                        
                        $window_unit = 'days';
                        $window_display_val = round($default_window / 1440);
                        if ($default_window % 1440 !== 0) {
                            $window_unit = 'minutes';
                            $window_display_val = $default_window;
                        }
                    ?>
                        <div class="eipsi-interval-item" data-wave-index="<?php echo $i; ?>" 
                             data-offset-minutes="<?php echo $current_offset; ?>"
                             data-next-offset-minutes="<?php echo $next_offset; ?>"
                             data-max-allowed-window="<?php echo $max_allowed_window; ?>"
                             style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:12px;background:#fff;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                <span class="eipsi-interval-label" style="font-weight:600;color:#1e293b;">T<?php echo $i + 1; ?> desde T1</span>
                                <?php if ($is_last_wave): ?>
                                    <span style="font-size:10px;color:#0891b2;background:#e0f2fe;padding:2px 8px;border-radius:4px;">Última toma → cierre</span>
                                <?php else: ?>
                                    <span style="font-size:10px;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:4px;">Siguiente: T<?php echo $i + 2; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="eipsi-interval-controls" style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
                                <input type="number" 
                                       class="eipsi-interval-input"
                                       value="<?php echo $display_val; ?>"
                                       min="1"
                                       style="width:80px;">
                                <select class="eipsi-wiz-select eipsi-interval-unit"
                                        data-previous-unit="<?php echo esc_attr($unit); ?>"
                                        style="width:100px;">
                                    <option value="days" <?php selected($unit, 'days'); ?>>días</option>
                                    <option value="minutes" <?php selected($unit, 'minutes'); ?>>minutos</option>
                                </select>
                                <span class="eipsi-interval-equiv" style="font-size:11px;color:#64748b;"></span>
                                
                                <input type="hidden" name="wave_index[]" value="<?php echo $i; ?>">
                                <input type="hidden" name="offset_minutes[]" value="<?php echo $current_offset; ?>" class="eipsi-hidden-offset">
                            </div>
                            
                            <!-- Plazo de respuesta (antes "Ventana de respuesta") -->
                            <div style="border-top:1px dashed #e2e8f0;padding-top:10px;">
                                <label style="display:block;font-size:12px;color:#64748b;margin-bottom:6px;">
                                    ⏱️ Plazo de respuesta
                                </label>
                                <div class="eipsi-window-controls" style="display:flex;gap:8px;align-items:center;">
                                    <input type="number" 
                                           class="eipsi-window-input"
                                           value="<?php echo $window_display_val; ?>"
                                           min="1"
                                           max="<?php echo $window_display_val; ?>"
                                           style="width:80px;">
                                    <select class="eipsi-wiz-select eipsi-window-unit"
                                            data-previous-unit="<?php echo esc_attr($window_unit); ?>"
                                            style="width:100px;">
                                        <option value="days" <?php selected($window_unit, 'days'); ?>>días</option>
                                        <option value="minutes" <?php selected($window_unit, 'minutes'); ?>>minutos</option>
                                    </select>
                                    <span class="eipsi-window-equiv" style="font-size:11px;color:#64748b;"></span>
                                    
                                    <input type="hidden" name="window_minutes[]" value="<?php echo $default_window; ?>" class="eipsi-hidden-window">
                                </div>
                                <small class="eipsi-window-hint" style="display:block;margin-top:4px;color:#94a3b8;font-size:11px;">
                                    ℹ️ Máximo: <?php echo eipsi_format_duration_human($max_allowed_window); ?> (hasta la <?php echo $is_last_wave ? 'el cierre del estudio' : 'siguiente toma'; ?>). Los nudges se distribuirán dentro de este plazo.
                                </small>
                                <small class="eipsi-window-error" style="display:none;margin-top:2px;color:#ef4444;font-size:11px;font-weight:500;">⚠️ No puede superar el tiempo hasta la <?php echo $is_last_wave ? 'el cierre del estudio' : 'siguiente toma'; ?>.</small>
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
                                   min="1">
                            <select class="eipsi-wiz-select eipsi-interval-unit"
                                    data-previous-unit="<?php echo esc_attr($unit_c); ?>">
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
                        <button type="button" class="eipsi-template-btn"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            data-template="semanal_7x">
                            📅 Semanal
                        </button>
                        <button type="button" class="eipsi-template-btn"
                            style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                            data-template="quincenal_14x">
                            📆 Quincenal
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
    
    // Check if unit changed (triggered by select)
    const isUnitChange = element === unitSelect;
    const newUnit = unitSelect.value;
    const currentValue = parseInt(input.value) || 0;
    
    // If unit changed, convert the value
    if (isUnitChange) {
        const previousUnit = unitSelect.dataset.previousUnit || 'days';
        
        if (previousUnit === 'days' && newUnit === 'minutes') {
            // Convert days to minutes
            input.value = currentValue * MINUTES_PER_DAY;
        } else if (previousUnit === 'minutes' && newUnit === 'days') {
            // Convert minutes to days (rounded)
            input.value = Math.round(currentValue / MINUTES_PER_DAY);
        }
        
        // Store current unit for next change
        unitSelect.dataset.previousUnit = newUnit;
    }
    
    const value = parseInt(input.value) || 0;
    const unit = unitSelect.value;
    
    const totalMinutes = (unit === 'days') ? value * MINUTES_PER_DAY : value;
    hiddenOffset.value = totalMinutes;
    
    // Update equivalent label
    equivSpan.textContent = eipsiFormatDuration(totalMinutes);
    
    // Update data attributes for this item
    item.dataset.offsetMinutes = totalMinutes;
    
    // Find and update the NEXT wave's maxAllowed for its window
    const allWaveItems = Array.from(document.querySelectorAll('.eipsi-interval-item:not(.closure):not([data-wave-index="0"])'));
    const currentIndex = allWaveItems.indexOf(item);
    
    if (currentIndex >= 0) {
        // Update this wave's maxAllowed based on next wave offset
        const nextItem = allWaveItems[currentIndex + 1]; // Next wave after this
        const closureItem = document.querySelector('.eipsi-interval-item.closure');
        
        let nextOffset;
        if (nextItem) {
            const nextHiddenOffset = nextItem.querySelector('.eipsi-hidden-offset');
            nextOffset = parseInt(nextHiddenOffset.value) || 0;
        } else if (closureItem) {
            const closureHidden = closureItem.querySelector('.eipsi-hidden-offset');
            nextOffset = parseInt(closureHidden.value) || 0;
        } else {
            nextOffset = totalMinutes + 10080;
        }
        
        item.dataset.nextOffsetMinutes = nextOffset;
        item.dataset.maxAllowedWindow = nextOffset - totalMinutes;
        
        // Update hint text if it exists
        const hint = item.querySelector('.eipsi-window-hint');
        if (hint && typeof eipsiFormatDuration === 'function') {
            const isLast = !nextItem;
            hint.textContent = 'ℹ️ Máximo: ' + eipsiFormatDuration(nextOffset - totalMinutes) + 
                ' (hasta ' + (isLast ? 'el cierre del estudio' : 'la siguiente toma') + 
                '). Los nudges se distribuirán dentro de este plazo.';
        }
        
        // Re-validate this wave's window
        const windowInput = item.querySelector('.eipsi-window-input');
        if (windowInput) {
            eipsiSyncWindow(windowInput);
        }
        
        // Also update the PREVIOUS wave (its maxAllowed depends on this wave's offset)
        if (currentIndex > 0) {
            const prevItem = allWaveItems[currentIndex - 1];
            const prevHiddenOffset = prevItem.querySelector('.eipsi-hidden-offset');
            const prevOffset = parseInt(prevHiddenOffset.value) || 0;
            
            prevItem.dataset.nextOffsetMinutes = totalMinutes;
            prevItem.dataset.maxAllowedWindow = totalMinutes - prevOffset;
            
            // Update hint
            const prevHint = prevItem.querySelector('.eipsi-window-hint');
            if (prevHint && typeof eipsiFormatDuration === 'function') {
                prevHint.textContent = 'ℹ️ Máximo: ' + eipsiFormatDuration(totalMinutes - prevOffset) + 
                    ' (hasta la siguiente toma). Los nudges se distribuirán dentro de este plazo.';
            }
            
            // Re-validate previous wave's window
            const prevWindowInput = prevItem.querySelector('.eipsi-window-input');
            if (prevWindowInput) {
                eipsiSyncWindow(prevWindowInput);
            }
        }
    }
    
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
    
    // Trigger step validation
    eipsiValidateStep3();
    
    // Trigger dirty state for wizard
    if (window.jQuery) {
        window.jQuery('#eipsi-wizard-form').trigger('change');
    }
}

/**
 * Synchronize window_minutes input with maxAllowed validation
 * maxAllowed = next_offset - current_offset
 */
function eipsiSyncWindow(element) {
    const item = element.closest('.eipsi-interval-item');
    if (!item) return;
    
    const windowInput = item.querySelector('.eipsi-window-input');
    const windowUnitSelect = item.querySelector('.eipsi-window-unit');
    const hiddenWindow = item.querySelector('.eipsi-hidden-window');
    const windowEquivSpan = item.querySelector('.eipsi-window-equiv');
    const windowHint = item.querySelector('.eipsi-window-hint');
    const windowError = item.querySelector('.eipsi-window-error');
    
    // Get maxAllowed from data attribute (set by PHP)
    const maxAllowedMinutes = parseInt(item.dataset.maxAllowedWindow) || 0;
    
    // Check if unit changed
    const isUnitChange = element === windowUnitSelect;
    const newUnit = windowUnitSelect.value;
    const currentValue = parseInt(windowInput.value) || 0;
    
    if (isUnitChange) {
        const previousUnit = windowUnitSelect.dataset.previousUnit || 'days';
        
        if (previousUnit === 'days' && newUnit === 'minutes') {
            windowInput.value = currentValue * MINUTES_PER_DAY;
        } else if (previousUnit === 'minutes' && newUnit === 'days') {
            windowInput.value = Math.round(currentValue / MINUTES_PER_DAY);
        }
        
        windowUnitSelect.dataset.previousUnit = newUnit;
    }
    
    const value = parseInt(windowInput.value) || 0;
    const unit = windowUnitSelect.value;
    const windowMinutes = (unit === 'days') ? value * MINUTES_PER_DAY : value;
    
    // Clear previous state
    windowInput.style.borderColor = '';
    windowInput.style.backgroundColor = '';
    windowEquivSpan.style.color = '#64748b';
    if (windowError) windowError.style.display = 'none';
    if (windowHint) windowHint.style.display = 'block';
    
    // Validate: windowMinutes cannot exceed maxAllowedMinutes (gap to next wave/closure)
    if (windowMinutes > maxAllowedMinutes) {
        windowInput.style.borderColor = '#ef4444';
        windowInput.style.backgroundColor = '#fef2f2';
        windowEquivSpan.textContent = '⚠️ Excede el plazo máximo';
        windowEquivSpan.style.color = '#ef4444';
        if (windowError) windowError.style.display = 'block';
        if (windowHint) windowHint.style.display = 'none';
        
        // Don't update hidden value when invalid
        // Trigger step validation (will disable Next button)
        eipsiValidateStep3();
        return;
    }
    
    // Valid - update hidden and display
    hiddenWindow.value = windowMinutes;
    windowEquivSpan.textContent = eipsiFormatDuration(windowMinutes);
    
    // Trigger step validation (will enable Next if all valid)
    eipsiValidateStep3();
    
    // Ensure the visual timeline preview is updated in real-time on window change
    eipsiUpdateTimelinePreview();
    
    // Trigger dirty state
    if (window.jQuery) {
        window.jQuery('#eipsi-wizard-form').trigger('change');
    }
}

/**
 * Validate all window inputs in Step 3 and disable/enable Next button
 * Called after every window or offset change
 */
function eipsiValidateStep3() {
    let allValid = true;
    
    document.querySelectorAll('.eipsi-window-input').forEach(windowInput => {
        const item = windowInput.closest('.eipsi-interval-item');
        if (!item) return;
        
        const windowUnitSelect = item.querySelector('.eipsi-window-unit');
        const value = parseInt(windowInput.value) || 0;
        const unit = windowUnitSelect.value;
        const windowMinutes = (unit === 'days') ? value * MINUTES_PER_DAY : value;
        const maxAllowedMinutes = parseInt(item.dataset.maxAllowedWindow) || 0;
        
        // Check if window exceeds maxAllowed
        if (windowMinutes > maxAllowedMinutes) {
            allValid = false;
        }
    });
    
    // Find the Next button in the parent wizard template
    const nextBtn = document.querySelector('.eipsi-wiz-btn-primary');
    if (nextBtn) {
        if (allValid) {
            nextBtn.disabled = false;
            nextBtn.style.opacity = '1';
            nextBtn.style.cursor = 'pointer';
        } else {
            nextBtn.disabled = true;
            nextBtn.style.opacity = '0.5';
            nextBtn.style.cursor = 'default';
        }
    }
    
    return allValid;
}

/**
 * Auto-calculate study closure based on last wave gap
 * Phase 3 T1-Anchor: Auto-calculates study_end_offset_minutes and updates 
 * data-next-offset-minutes / data-max-allowed-window on the last wave
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
    
    // Update the last wave's data attributes and re-validate its window
    const waveItems = document.querySelectorAll('.eipsi-interval-item:not(.closure)');
    if (waveItems.length > 0) {
        const lastWaveItem = waveItems[waveItems.length - 1];
        const lastWaveHiddenOffset = lastWaveItem.querySelector('.eipsi-hidden-offset');
        const lastWaveOffsetVal = parseInt(lastWaveHiddenOffset.value) || 0;
        
        // Update data attributes for maxAllowed validation
        lastWaveItem.dataset.nextOffsetMinutes = closureOffset;
        lastWaveItem.dataset.maxAllowedWindow = closureOffset - lastWaveOffsetVal;
        
        // Also update the hint text for the last wave
        const lastWaveHint = lastWaveItem.querySelector('.eipsi-window-hint');
        if (lastWaveHint && typeof eipsiFormatDuration === 'function') {
            lastWaveHint.textContent = 'ℹ️ Máximo: ' + eipsiFormatDuration(closureOffset - lastWaveOffsetVal) + ' (hasta el cierre del estudio). Los nudges se distribuirán dentro de este plazo.';
        }
        
        // Re-validate the last wave's window
        const lastWaveWindowInput = lastWaveItem.querySelector('.eipsi-window-input');
        if (lastWaveWindowInput) {
            eipsiSyncWindow(lastWaveWindowInput);
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
    const hiddenWindows = Array.from(document.querySelectorAll('.eipsi-hidden-window'));
    
    // Helper to format days nicely (integer or decimal up to 2 decimal places)
    function formatDayValue(minutes) {
        const days = minutes / MINUTES_PER_DAY;
        if (Number.isInteger(days)) {
            return days;
        }
        return parseFloat(days.toFixed(2));
    }
    
    let html = '';
    const totalHeight = 60; // Fixed total height for the visual connection bar
    
    indices.forEach((input, i) => {
        const index = input.value;
        const isClosure = index === 'closure';
        const offset = parseInt(hiddenOffsets[i].value) || 0;
        
        let label = '';
        let timeText = '';
        let openDays = null;
        let closeDays = null;
        let solidHeight = 0;
        let dottedHeight = 0;
        let showBars = false;
        
        if (index === '0') {
            // T1 (Inicio)
            label = 'T1 (Inicio del estudio)';
            timeText = 'Día 0';
            
            // Next offset is T2's offset
            const nextOffset = (hiddenOffsets[1] && parseInt(hiddenOffsets[1].value)) || 0;
            const windowMinutes = nextOffset; // T1 is open until T2
            const availability = nextOffset;
            
            openDays = 0;
            closeDays = formatDayValue(nextOffset);
            
            if (availability > 0) {
                showBars = true;
                const solidMinutes = Math.min(windowMinutes, availability);
                solidHeight = Math.round(totalHeight * (solidMinutes / availability));
                dottedHeight = totalHeight - solidHeight;
            }
        } else if (isClosure) {
            // Closure
            label = 'Cierre del estudio';
            timeText = `Día ${formatDayValue(offset)}`;
            showBars = false;
        } else {
            // Subsequent waves (T2, T3...)
            const idx = parseInt(index);
            label = `Toma ${idx + 1}`;
            timeText = `Día ${formatDayValue(offset)}`;
            
            // Next offset is either the next wave's offset or closure's offset
            const nextOffset = (hiddenOffsets[i + 1] && parseInt(hiddenOffsets[i + 1].value)) || 0;
            const availability = nextOffset - offset;
            
            // Find corresponding window from hiddenWindows (maps to idx - 1)
            const windowInput = hiddenWindows[idx - 1];
            const windowMinutes = windowInput ? (parseInt(windowInput.value) || 0) : 0;
            
            openDays = formatDayValue(offset);
            closeDays = formatDayValue(offset + windowMinutes);
            
            if (availability > 0) {
                showBars = true;
                const solidMinutes = Math.min(windowMinutes, availability);
                solidHeight = Math.round(totalHeight * (solidMinutes / availability));
                dottedHeight = totalHeight - solidHeight;
            }
        }
        
        let barsHtml = '';
        if (showBars) {
            barsHtml = `
                <div class="timeline-bars" style="height: ${totalHeight}px;">
                    <div class="bar-solid" style="height: ${solidHeight}px;"></div>
                    ${dottedHeight > 0 ? `<div class="bar-dotted" style="height: ${dottedHeight}px;"></div>` : ''}
                </div>
            `;
        }
        
        let resourceInfoHtml = '';
        if (openDays !== null && closeDays !== null) {
            resourceInfoHtml = `
                <div class="timeline-resource-info">
                    Abre: Día ${openDays} | Cierra: Día ${closeDays}
                </div>
            `;
        }
        
        html += `
            <div class="timeline-event ${isClosure ? 'closure' : ''}">
                <div class="timeline-dot"></div>
                ${barsHtml}
                <div class="timeline-content">
                    <span class="timeline-label">${label}</span>
                    <span class="timeline-time">${timeText}</span>
                </div>
                ${resourceInfoHtml}
            </div>
        `;
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
            const currentUnit = units[i] ? units[i].value : 'days';
            
            // Set value according to current unit
            if (currentUnit === 'minutes') {
                input.value = accumulatedDays * MINUTES_PER_DAY;
            } else {
                input.value = accumulatedDays;
            }
            
            // Update data-previous-unit to match current unit
            if (units[i]) {
                units[i].dataset.previousUnit = currentUnit;
            }
            
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
    // Initial sync for all offset inputs
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
    
    // Initial sync for all window inputs
    document.querySelectorAll('.eipsi-window-input').forEach(input => {
        const item = input.closest('.eipsi-interval-item');
        if (item) {
            const hidden = item.querySelector('.eipsi-hidden-window');
            const equiv = item.querySelector('.eipsi-window-equiv');
            if (hidden && equiv) {
                equiv.textContent = eipsiFormatDuration(parseInt(hidden.value));
            }
        }
    });
    
    // Event listeners para botones de plantillas
    document.querySelectorAll('.eipsi-template-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const template = this.dataset.template;
            eipsiApplyTimingTemplate(template, this);
        });
    });
    
    // Event delegation para offset inputs y selects
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('eipsi-interval-input')) {
            eipsiSyncOffset(e.target);
        }
        if (e.target.classList.contains('eipsi-window-input')) {
            eipsiSyncWindow(e.target);
        }
    });
    
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('eipsi-interval-unit')) {
            eipsiSyncOffset(e.target);
        }
        if (e.target.classList.contains('eipsi-window-unit')) {
            eipsiSyncWindow(e.target);
        }
    });
    
    eipsiUpdateTimelinePreview();
    
    // Initial validation to set correct Next button state
    eipsiValidateStep3();
});

</script>
