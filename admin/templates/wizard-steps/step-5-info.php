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

// Format timing summary
$timing_summary = array();
if (!empty($step_3['timing_intervals'])) {
    foreach ($step_3['timing_intervals'] as $interval) {
        $from_wave = intval($interval['from_wave']) + 1;
        $to_wave = intval($interval['to_wave']) + 1;
        $days = intval($interval['days_after']);
        $timing_summary[] = "T{$from_wave} → T{$to_wave}: {$days} días";
    }
}

?>
<div class="eipsi-wizard-step" id="step-5">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="5">
        
        <div class="step-header">
            <h2>✅ RESUMEN Y CONFIRMACIÓN</h2>
            <p>Revisa toda la configuración antes de activar tu estudio longitudinal.</p>
        </div>
        
        <div class="summary-container">
            <!-- Study Information -->
            <div class="summary-section">
                <h3>📋 INFORMACIÓN DEL ESTUDIO</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="label">Nombre:</span>
                        <span class="value"><?php echo esc_html($step_1['study_name'] ?? 'No especificado'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Código:</span>
                        <span class="value code"><?php echo esc_html($step_1['study_code'] ?? 'No especificado'); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Investigador:</span>
                        <span class="value"><?php echo esc_html($investigator_name); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Estado:</span>
                        <span class="value status pending">Pendiente de activación</span>
                    </div>
                </div>
                
                <?php if (!empty($step_1['description'])): ?>
                    <div class="summary-description">
                        <span class="label">Descripción:</span>
                        <p><?php echo nl2br(esc_html($step_1['description'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Waves Configuration -->
            <div class="summary-section">
                <h3>📊 Configuración de Waves</h3>
                <div class="waves-summary">
                    <div class="waves-count">
                        <strong><?php echo intval($step_2['number_of_waves'] ?? 0); ?> Tomas configuradas</strong>
                    </div>
                    
                    <?php if (!empty($step_2['waves_config'])): ?>
                        <div class="waves-list">
                            <?php foreach ($step_2['waves_config'] as $index => $wave): ?>
                                <?php 
                                $wave_num = $index + 1;
                                $wave_name = esc_html($wave['name'] ?? "Toma {$wave_num}");
                                $duration = intval($wave['estimated_duration'] ?? 15);
                                $is_required = isset($wave['is_required']) ? (bool)$wave['is_required'] : true;
                                ?>
                                <div class="wave-summary-item">
                                    <div class="wave-info">
                                        <span class="wave-number">T<?php echo $wave_num; ?></span>
                                        <span class="wave-name"><?php echo $wave_name; ?></span>
                                    </div>
                                    <div class="wave-details">
                                        <span class="wave-duration"><?php echo $duration; ?> min</span>
                                        <span class="wave-status <?php echo $is_required ? 'required' : 'optional'; ?>">
                                            <?php echo $is_required ? 'Obligatoria' : 'Opcional'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Timing Configuration -->
            <div class="summary-section">
                <h3>⏰ Programación Temporal</h3>
                <div class="timing-summary">
                    <?php if (!empty($timing_summary)): ?>
                        <div class="intervals-list">
                            <?php foreach ($timing_summary as $interval): ?>
                                <div class="interval-item">
                                    <span class="interval-text"><?php echo $interval; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="timing-details">
                        <div class="detail-item">
                            <span class="label">Recordatorio:</span>
                            <span class="value"><?php echo intval($step_3['reminder_days_before'] ?? 3); ?> días antes</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Reintentos:</span>
                            <span class="value">
                                <?php if (!empty($step_3['enable_retries'])): ?>
                                    Cada <?php echo intval($step_3['retry_after_days'] ?? 7); ?> días (máx <?php echo intval($step_3['max_retries'] ?? 3); ?>)
                                <?php else: ?>
                                    Deshabilitados
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Notificación investigador:</span>
                            <span class="value"><?php echo intval($step_3['investigator_notification_days'] ?? 14); ?> días sin respuesta</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Participants Configuration -->
            <div class="summary-section">
                <h3>👥 PARTICIPANTES</h3>
                <div class="participants-summary">
                    <?php if (!empty($selected_methods)): ?>
                        <div class="methods-list">
                            <span class="label">Métodos de invitación:</span>
                            <?php foreach ($selected_methods as $method): ?>
                                <span class="method-tag"><?php echo $method; ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="consent-summary">
                        <div class="detail-item">
                            <span class="label">Consentimiento:</span>
                            <span class="value <?php echo !empty($step_4['require_consent']) ? 'enabled' : 'disabled'; ?>">
                                <?php echo !empty($step_4['require_consent']) ? 'Requerido' : 'Opcional'; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Aviso privacidad:</span>
                            <span class="value <?php echo !empty($step_4['show_privacy_notice']) ? 'enabled' : 'disabled'; ?>">
                                <?php echo !empty($step_4['show_privacy_notice']) ? 'Mostrado' : 'Oculto'; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Auto-remove inactivos:</span>
                            <span class="value <?php echo !empty($step_4['auto_removal_inactive']) ? 'enabled' : 'disabled'; ?>">
                                <?php echo !empty($step_4['auto_removal_inactive']) ? 'Activado' : 'Desactivado'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Important Notice -->
            <div class="important-notice">
                <h4>⚠️ IMPORTANTE - ANTES DE ACTIVAR</h4>
                <ul>
                    <li><strong>Estructura permanente:</strong> Una vez activado, cambiar la configuración de tomas será muy difícil.</li>
                    <li><strong>Invitaciones inmediatas:</strong> Podrás empezar a invitar participantes inmediatamente después.</li>
                    <li><strong>URLs generadas:</strong> Se crearán automáticamente los enlaces para participantes.</li>
                    <li><strong>Monitoreo:</strong> Podrás seguir el progreso desde el panel de control del estudio.</li>
                </ul>
            </div>
            
            <!-- Confirmation -->
            <div class="activation-confirmation">
                <label class="confirmation-checkbox">
                    <input type="checkbox" 
                           id="activation_confirmed"
                           name="activation_confirmed" 
                           value="1">
                    <span class="checkbox-mark">✓</span>
                    <span class="checkbox-text">
                        <strong>Entiendo y confirmo:</strong> Deseo activar este estudio longitudinal con la configuración mostrada arriba.
                    </span>
                </label>
            </div>
        </div>
    </form>
</div>

<script>
// Disable/enable activation button based on confirmation
document.addEventListener('DOMContentLoaded', function() {
    const confirmationCheckbox = document.getElementById('activation_confirmed');
    const activationButton = document.querySelector('.study-navigation .button-primary');
    
    if (confirmationCheckbox && activationButton) {
        const updateActivationState = () => {
            const isChecked = confirmationCheckbox.checked;
            activationButton.disabled = !isChecked;
            activationButton.style.opacity = isChecked ? '1' : '0.5';
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
    // TODO: Implement PDF/CSV export of study configuration
    alert('Función de descarga próximamente disponible.');
}
</script>

<style>
.summary-container {
    max-width: 900px;
    margin: 0 auto;
    display: grid;
    gap: 2rem;
}

.summary-section {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
}

.summary-section h3 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-grid {
    display: grid;
    gap: 0.75rem;
}

.summary-item {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 1rem;
    align-items: center;
}

.summary-item .label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
}

.summary-item .value {
    color: #495057;
    font-weight: 500;
}

.summary-item .value.code {
    font-family: 'Courier New', monospace;
    background: #ffffff;
    color: #000000;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
}

.summary-item .value.status.pending {
    color: #ffc107;
    font-weight: 600;
}

.summary-description {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.summary-description .label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
    display: block;
    margin-bottom: 0.5rem;
}

.summary-description p {
    margin: 0;
    color: #495057;
    line-height: 1.5;
}

.waves-summary {
    display: grid;
    gap: 1rem;
}

.waves-count {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    color: #495057;
}

.waves-list {
    display: grid;
    gap: 0.75rem;
}

.wave-summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.wave-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.wave-number {
    background: #667eea;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.8rem;
}

.wave-name {
    font-weight: 600;
    color: #495057;
}

.wave-details {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.wave-duration {
    font-size: 0.85rem;
    color: #6c757d;
}

.wave-status {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.wave-status.required {
    background: #dc3545;
    color: white;
}

.wave-status.optional {
    background: #28a745;
    color: white;
}

.timing-summary .intervals-list {
    display: grid;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.interval-item {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 6px;
    text-align: center;
    font-weight: 500;
    color: #495057;
}

.timing-details {
    display: grid;
    gap: 0.75rem;
}

.detail-item {
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 1rem;
    align-items: center;
}

.detail-item .label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
}

.detail-item .value {
    color: #495057;
    font-weight: 500;
}

.detail-item .value.enabled {
    color: #28a745;
    font-weight: 600;
}

.detail-item .value.disabled {
    color: #6c757d;
}

.participants-summary .methods-list {
    margin-bottom: 1rem;
}

.participants-summary .label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
    margin-right: 1rem;
}

.method-tag {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #667eea;
    color: white;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.important-notice {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 2px solid #ffc107;
    border-radius: 12px;
    padding: 1.5rem;
}

.important-notice h4 {
    margin: 0 0 1rem 0;
    color: #856404;
    font-size: 1rem;
    font-weight: 600;
}

.important-notice ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #856404;
}

.important-notice li {
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.activation-confirmation {
    background: white;
    border: 2px solid #28a745;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
}

.confirmation-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    cursor: pointer;
    text-align: left;
    max-width: 600px;
    margin: 0 auto;
}

.confirmation-checkbox input[type="checkbox"] {
    display: none;
}

.checkbox-mark {
    width: 24px;
    height: 24px;
    border: 2px solid #28a745;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: transparent;
    transition: all 0.2s ease;
    flex-shrink: 0;
    margin-top: 0.2rem;
}

.confirmation-checkbox input[type="checkbox"]:checked + .checkbox-mark {
    background: #28a745;
    color: white;
}

.checkbox-text {
    color: #495057;
    line-height: 1.5;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .summary-section {
        background: #2c3e50;
        border-color: #34495e;
    }
    
    .summary-section h3 {
        color: #ecf0f1;
    }
    
    .summary-item .label {
        color: #95a5a6;
    }
    
    .summary-item .value {
        color: #ecf0f1;
    }
    
    .summary-description p {
        color: #ecf0f1;
    }
    
    .waves-count,
    .wave-summary-item,
    .interval-item {
        background: #34495e;
        border-color: #4a5f7a;
    }
    
    .wave-name {
        color: #ecf0f1;
    }
    
    .detail-item .label {
        color: #95a5a6;
    }
    
    .detail-item .value {
        color: #ecf0f1;
    }
    
    .method-tag {
        background: #667eea;
        color: white;
    }
    
    .important-notice {
        background: linear-gradient(135deg, #856404 0%, #6c5800 100%);
        border-color: #ffc107;
    }
    
    .important-notice h4,
    .important-notice li {
        color: #fff3cd;
    }
    
    .checkbox-text {
        color: #ecf0f1;
    }
    
    .activation-confirmation {
        background: #2c3e50;
        border-color: #28a745;
    }
}
</style>