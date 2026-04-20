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

// Format timing summary
$timing_summary = array();
if (!empty($step_3['timing_intervals'])) {
    foreach ($step_3['timing_intervals'] as $interval) {
        $from_wave = intval($interval['from_wave']) + 1;
        $to_wave = intval($interval['to_wave']) + 1;
        $value = intval($interval['days_after']);
        $time_unit = isset($interval['time_unit']) ? $interval['time_unit'] : 'days';
        
        if ($time_unit === 'minutes') {
            $formatted = eipsi_format_minutes_human_readable($value);
            $timing_summary[] = "T{$from_wave} → T{$to_wave}: {$formatted}";
        } elseif ($time_unit === 'hours') {
            $unit_label = $value == 1 ? 'hora' : 'horas';
            $timing_summary[] = "T{$from_wave} → T{$to_wave}: {$value} {$unit_label}";
        } elseif ($time_unit === 'days') {
            $unit_label = $value == 1 ? 'día' : 'días';
            $timing_summary[] = "T{$from_wave} → T{$to_wave}: {$value} {$unit_label}";
        } else {
            $timing_summary[] = "T{$from_wave} → T{$to_wave}: {$value} días";
        }
    }
}

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
                <h3 style="margin:0 0 16px 0;color:#2c3e50;font-size:14px;font-weight:600;">Programación Temporal</h3>
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
                    <?php if (!empty($timing_summary)): ?>
                        <?php foreach ($timing_summary as $interval): ?>
                            <div style="padding:10px;background:#f8f9fa;border-radius:6px;text-align:center;font-weight:500;color:#2c3e50;font-size:13px;">
                                <?php echo $interval; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Notificación de nueva toma:</span>
                        <span class="eipsi-summary-value">Email automático el mismo día que esté disponible</span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Reintentos:</span>
                        <span class="eipsi-summary-value">
                            <?php if (!empty($step_3['enable_retries'])): ?>
                                Cada <?php echo intval($step_3['retry_after_days'] ?? 7); ?> días (máx <?php echo intval($step_3['max_retries'] ?? 3); ?> reintentos)
                            <?php else: ?>
                                Deshabilitados
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Alerta al investigador:</span>
                        <span class="eipsi-summary-value">Después de <?php echo intval($step_3['investigator_notification_days'] ?? 14); ?> días sin respuesta</span>
                    </div>
                </div>
            </div>
            
            <!-- Participants Configuration -->
            <div class="eipsi-summary-section">
                <h3 style="margin:0 0 16px 0;color:#2c3e50;font-size:14px;font-weight:600;">Participantes</h3>
                
                <?php if (!empty($selected_methods)): ?>
                    <div style="margin-bottom:16px;">
                        <span class="eipsi-summary-label">Métodos de invitación:</span>
                        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
                            <?php foreach ($selected_methods as $method): ?>
                                <span style="display:inline-block;padding:4px 12px;background:#3B6CAA;color:white;border-radius:20px;font-size:12px;font-weight:500;"><?php echo $method; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Consentimiento:</span>
                        <span class="eipsi-summary-value" style="<?php echo !empty($step_4['require_consent']) ? 'color:#008080;font-weight:600;' : 'color:#6c757d;'; ?>">
                            <?php echo !empty($step_4['require_consent']) ? 'Requerido' : 'Opcional'; ?>
                        </span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Aviso privacidad:</span>
                        <span class="eipsi-summary-value" style="<?php echo !empty($step_4['show_privacy_notice']) ? 'color:#008080;font-weight:600;' : 'color:#6c757d;'; ?>">
                            <?php echo !empty($step_4['show_privacy_notice']) ? 'Mostrado' : 'Oculto'; ?>
                        </span>
                    </div>
                    <div class="eipsi-summary-row">
                        <span class="eipsi-summary-label">Auto-remove inactivos:</span>
                        <span class="eipsi-summary-value" style="<?php echo !empty($step_4['auto_removal_inactive']) ? 'color:#008080;font-weight:600;' : 'color:#6c757d;'; ?>">
                            <?php echo !empty($step_4['auto_removal_inactive']) ? 'Activado' : 'Desactivado'; ?>
                        </span>
                    </div>
                </div>
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
