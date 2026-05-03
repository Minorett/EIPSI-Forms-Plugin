<?php
/**
 * Wizard Step 5: Resumen y Activación
 * 
 * Template final que muestra resumen completo y confirma activación.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all wizard data for summary
$wizard_data = eipsi_get_wizard_data();
$step_1 = isset($wizard_data['step_1']) ? $wizard_data['step_1'] : array();
$step_2 = isset($wizard_data['step_2']) ? $wizard_data['step_2'] : array();
$step_3 = isset($wizard_data['step_3']) ? $wizard_data['step_3'] : array();
$step_4 = isset($wizard_data['step_4']) ? $wizard_data['step_4'] : array();

// Get investigator name
$investigator_name = '';
if (!empty($step_1['principal_investigator_id'])) {
    $investor_user = get_userdata($step_1['principal_investigator_id']);
    if ($investor_user) {
        $investigator_name = $investor_user->display_name;
    }
}

// Format invitation methods
$invitation_methods_labels = array(
    'magic_links' => 'Magic Links por Email',
    'csv_upload' => 'Subir Lista CSV',
    'public_registration' => 'Registro Público'
);

$selected_methods = array();
if (!empty($step_4['invitation_methods'])) {
    foreach ($step_4['invitation_methods'] as $method) {
        if (isset($invitation_methods_labels[$method])) {
            $selected_methods[] = $invitation_methods_labels[$method];
        }
    }
}

// Helper function to format minutes into human-readable duration
function eipsi_format_minutes_human_readable($minutes) {
    if ($minutes < 60) {
        return $minutes . ' minutos';
    }
    
    $days = floor($minutes / 1440);
    $remaining = $minutes % 1440;
    $hours = floor($remaining / 60);
    $mins = $remaining % 60;
    
    $parts = array();
    if ($days > 0) {
        $parts[] = $days . ' día' . ($days > 1 ? 's' : '');
    }
    if ($hours > 0) {
        $parts[] = $hours . ' hora' . ($hours > 1 ? 's' : '');
    }
    if ($mins > 0) {
        $parts[] = $mins . ' minuto' . ($mins > 1 ? 's' : '');
    }
    
    return implode(', ', $parts);
}

// Format timing summary with absolute offsets from T1
$timing_summary = array();
$wave_offsets = array(0); // T1 always at 0

if (!empty($step_3['timing_intervals'])) {
    foreach ($step_3['timing_intervals'] as $interval) {
        if (isset($interval['offset_minutes'])) {
            $wave_offsets[] = intval($interval['offset_minutes']);
        }
    }
}

// Build timing display
foreach ($wave_offsets as $index => $offset_minutes) {
    $wave_num = $index + 1;
    if ($wave_num === 1) {
        $timing_summary[] = array(
            'wave' => 'T1',
            'offset' => 'Inmediato (inicio del estudio)',
            'offset_raw' => 0
        );
    } else {
        $timing_summary[] = array(
            'wave' => "T{$wave_num}",
            'offset' => eipsi_format_minutes_human_readable($offset_minutes) . ' desde T1',
            'offset_raw' => $offset_minutes
        );
    }
}

// Get study end offset
$study_end_offset = isset($step_3['study_end_offset_minutes']) ? intval($step_3['study_end_offset_minutes']) : null;

?>
<div class="eipsi-wizard-step" id="step-5">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="5">
        
        <!-- Header del step -->
        <div class="eipsi-wiz-step-header">
            <p class="eipsi-wiz-step-title">Resumen y Confirmación</p>
            <p class="eipsi-wiz-step-sub">Revisa toda la configuración antes de activar tu estudio longitudinal.</p>
        </div>
        
        <div class="summary-container">
            <!-- Study Information -->
            <div class="eipsi-summary-section">
                <h3 style="margin:0 0 16px 0;color:#2c3e50;font-size:14px;font-weight:600;">Información del Estudio</h3>
                <div class="eipsi-summary-grid">
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Nombre:</span>
                        <span class="eipsi-summary-value"><?php echo esc_html($step_1['study_name'] ?? 'No especificado'); ?></span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Código:</span>
                        <span class="eipsi-summary-value code"><?php echo esc_html($step_1['study_code'] ?? 'No especificado'); ?></span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Investigador:</span>
                        <span class="eipsi-summary-value"><?php echo esc_html($investigator_name); ?></span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Estado:</span>
                        <span class="eipsi-summary-value" style="color:#ffc107;font-weight:600;">Pendiente de activación</span>
                    </div>
                </div>
                
                <?php if (!empty($step_1['description'])): ?>
                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0;">
                        <span class="eipsi-summary-label">Descripción:</span>
                        <p style="margin:8px 0 0 0;color:#64748b;font-size:13px;line-height:1.5;"><?php echo nl2br(esc_html($step_1['description'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Waves Configuration -->
            <div class="eipsi-summary-section">
                <h3 style="margin:0 0 16px 0;color:#2c3e50;font-size:14px;font-weight:600;">Configuración de Waves</h3>
                <div style="padding:12px;background:#f8f9fa;border-radius:8px;text-align:center;font-weight:600;color:#2c3e50;font-size:13px;margin-bottom:16px;">
                    <?php echo intval($step_2['number_of_waves'] ?? 0); ?> Tomas configuradas
                </div>
                
                <?php if (!empty($step_2['waves_config'])): ?>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <?php foreach ($step_2['waves_config'] as $index => $wave): ?>
                            <?php 
                            $wave_num = $index + 1;
                            $wave_name = esc_html($wave['name'] ?? "Toma {$wave_num}");
                            $is_required = isset($wave['is_required']) ? (bool)$wave['is_required'] : true;
                            ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f8f9fa;border-radius:8px;border:1px solid #e2e8f0;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span style="background:#3B6CAA;color:white;padding:2px 8px;border-radius:4px;font-weight:600;font-size:12px;">T<?php echo $wave_num; ?></span>
                                    <span style="font-weight:600;color:#2c3e50;font-size:13px;"><?php echo $wave_name; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Timing Configuration -->
            <div class="eipsi-summary-section">
                <h3 style="margin:0 0 16px 0;color:#2c3e50;font-size:14px;font-weight:600;">📅 Programación Temporal (Anclaje T1)</h3>
                
                <!-- Timeline visual -->
                <div style="background:#f8f9fa;border-radius:8px;padding:16px;margin-bottom:16px;">
                    <div style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:12px;">LÍNEA DE TIEMPO DEL ESTUDIO</div>
                    <?php if (!empty($timing_summary)): ?>
                        <?php foreach ($timing_summary as $index => $wave_info): ?>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:<?php echo $index < count($timing_summary) - 1 ? '12px' : '0'; ?>;">
                                <div style="background:#3B6CAA;color:white;padding:6px 12px;border-radius:6px;font-weight:600;font-size:12px;min-width:40px;text-align:center;">
                                    <?php echo esc_html($wave_info['wave']); ?>
                                </div>
                                <div style="flex:1;color:#2c3e50;font-size:13px;font-weight:500;">
                                    <?php echo esc_html($wave_info['offset']); ?>
                                </div>
                            </div>
                            <?php if ($index < count($timing_summary) - 1): ?>
                                <div style="margin-left:20px;height:16px;border-left:2px dashed #cbd5e1;"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if ($study_end_offset !== null && $study_end_offset > 0): ?>
                            <div style="margin-left:20px;height:16px;border-left:2px dashed #cbd5e1;"></div>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="background:#dc3545;color:white;padding:6px 12px;border-radius:6px;font-weight:600;font-size:12px;min-width:40px;text-align:center;">
                                    🔒
                                </div>
                                <div style="flex:1;color:#2c3e50;font-size:13px;font-weight:500;">
                                    Cierre del estudio: <?php echo eipsi_format_minutes_human_readable($study_end_offset); ?> desde T1
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Nudges summary -->
                <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:14px;margin-bottom:16px;">
                    <div style="font-size:12px;font-weight:600;color:#856404;margin-bottom:8px;">🔔 RECORDATORIOS AUTOMÁTICOS (NUDGES)</div>
                    <div style="font-size:12px;color:#856404;line-height:1.5;">
                        Cada toma tendrá <strong>4 recordatorios automáticos</strong> distribuidos proporcionalmente en el tiempo disponible hasta la próxima toma:
                        <ul style="margin:8px 0 0 0;padding-left:20px;">
                            <li>Nudge 1: <strong>15%</strong> del intervalo</li>
                            <li>Nudge 2: <strong>40%</strong> del intervalo</li>
                            <li>Nudge 3: <strong>70%</strong> del intervalo</li>
                            <li>Nudge 4: <strong>90%</strong> del intervalo</li>
                        </ul>
                        <div style="margin-top:8px;font-style:italic;">
                            💡 Los tiempos exactos se ajustarán automáticamente según el intervalo entre tomas. Podrás modificarlos manualmente desde el Dashboard del estudio.
                        </div>
                    </div>
                </div>
                
                <!-- Other timing settings -->
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">📬 Email de disponibilidad:</span>
                        <span class="eipsi-summary-value">Automático cuando cada toma esté lista</span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">🔁 Reintentos:</span>
                        <span class="eipsi-summary-value">
                            <?php if (!empty($step_3['enable_retries'])): ?>
                                Cada <?php echo intval($step_3['retry_after_days'] ?? 7); ?> días (máx <?php echo intval($step_3['max_retries'] ?? 3); ?> reintentos)
                            <?php else: ?>
                                Deshabilitados
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">⚠️ Alerta al investigador:</span>
                        <span class="eipsi-summary-value">Después de <?php echo intval($step_3['investigator_notification_days'] ?? 14); ?> días sin respuesta</span>
                    </div>
                </div>
            </div>
            
            <!-- Participants Configuration -->
            <div class="eipsi-summary-section">
                <h3 style="margin:0 0 16px 0;color:#2c3e50;font-size:14px;font-weight:600;">Participantes</h3>
                
                <?php if (!empty($selected_methods)): ?>
                    <div>
                        <span class="eipsi-summary-label">Métodos de invitación:</span>
                        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
                            <?php foreach ($selected_methods as $method): ?>
                                <span style="display:inline-block;padding:4px 12px;background:#3B6CAA;color:white;border-radius:20px;font-size:12px;font-weight:500;"><?php echo $method; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding:12px;background:#f8f9fa;border-radius:6px;text-align:center;color:#64748b;font-size:13px;">
                        No se seleccionaron métodos de invitación
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Important Notice -->
            <div class="eipsi-wiz-notice" style="background:linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);border:1px solid #ffc107;border-radius:10px;padding:18px;">
                <h4 style="margin:0 0 12px 0;color:#856404;font-size:13px;font-weight:600;">IMPORTANTE - ANTES DE ACTIVAR</h4>
                <ul style="margin:0;padding-left:18px;color:#856404;font-size:13px;line-height:1.6;">
                    <li style="margin-bottom:6px;"><strong>Estructura permanente:</strong> Una vez activado, cambiar la configuración de tomas será muy difícil.</li>
                    <li style="margin-bottom:6px;"><strong>Invitaciones inmediatas:</strong> Podrás empezar a invitar participantes inmediatamente después.</li>
                    <li style="margin-bottom:6px;"><strong>URLs generadas:</strong> Se crearán automáticamente los enlaces para participantes.</li>
                    <li><strong>Monitoreo:</strong> Podrás seguir el progreso desde el panel de control del estudio.</li>
                </ul>
            </div>
            
            <!-- Confirmation -->
            <div style="background:white;border:2px solid #008080;border-radius:10px;padding:24px;text-align:center;">
                <label style="display:flex;align-items:flex-start;gap:14px;cursor:pointer;text-align:left;max-width:600px;margin:0 auto;">
                    <input type="checkbox" id="activation_confirmed" name="activation_confirmed" value="1" style="margin-top:4px;transform:scale(1.3);">
                    <span style="color:#2c3e50;line-height:1.5;font-size:14px;">
                        <strong>Entiendo y confirmo:</strong> Deseo activar este estudio longitudinal con la configuración mostrada arriba.
                    </span>
                </label>
            </div>
        </div>
    </form>
    
    <!-- Autosave hint -->
    <div class="eipsi-wiz-autosave" id="eipsi-autosave-hint"></div>
</div>

<script>
// Disable/enable activation button based on confirmation - ACTUALIZADO
// El botón de activación está en la navegación, manejado por setup-wizard.php
document.addEventListener('DOMContentLoaded', function() {
    const confirmationCheckbox = document.getElementById('activation_confirmed');
    
    // El botón de activación tiene el ID definido en setup-wizard.php
    const activationButton = document.getElementById('eipsi-activate-btn');
    
    if (confirmationCheckbox && activationButton) {
        const updateActivationState = () => {
            const isChecked = confirmationCheckbox.checked;
            activationButton.disabled = !isChecked;
            activationButton.style.opacity = isChecked ? '1' : '0.5';
            
            // Actualizar color del borde del contenedor según estado
            const container = confirmationCheckbox.closest('div');
            if (container) {
                container.style.borderColor = isChecked ? '#008080' : '#e2e8f0';
            }
        };

        updateActivationState();
        confirmationCheckbox.addEventListener('change', updateActivationState);
    }
});

// Print summary function
function eipsiPrintSummary() {
    window.print();
}

// Download summary function
function eipsiDownloadSummary() {
    alert('Función de descarga próximamente disponible.');
}
</script>

<style>
/* Summary sections EIPSI */
.eipsi-summary-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}
.eipsi-summary-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.eipsi-summary-row {
    display: flex;
    gap: 12px;
    align-items: baseline;
}
.eipsi-summary-label {
    font-weight: 600;
    color: #64748b;
    font-size: 13px;
    min-width: 120px;
}
.eipsi-summary-value {
    color: #2c3e50;
    font-weight: 500;
    font-size: 13px;
}
.eipsi-summary-value.code {
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* Notice EIPSI */
.eipsi-wiz-notice {
    margin-bottom: 20px;
}

/* Contenedor de confirmación - transición de borde */
#step-5 .summary-container > div:last-of-type {
    transition: border-color 0.2s ease;
}
</style>
