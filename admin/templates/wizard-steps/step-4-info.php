<?php
/**
 * Wizard Step 4: Configuración de Participantes
 * 
 * Template para configurar métodos de invitación y consentimiento.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

$step_data = isset($wizard_data['step_4']) ? $wizard_data['step_4'] : array();

// Set default values - ALL methods enabled by default
$invitation_methods = isset($step_data['invitation_methods']) ? $step_data['invitation_methods'] : array('magic_links', 'csv_upload', 'public_registration');
$require_consent = isset($step_data['require_consent']) ? $step_data['require_consent'] : true;
$consent_message = isset($step_data['consent_message']) ? $step_data['consent_message'] : '';
$show_privacy_notice = isset($step_data['show_privacy_notice']) ? $step_data['show_privacy_notice'] : true;
$auto_removal_inactive = isset($step_data['auto_removal_inactive']) ? $step_data['auto_removal_inactive'] : false;

// Default consent message
if (empty($consent_message)) {
    $consent_message = "Estimado/a participante,\n\nEste estudio evalúa la efectividad de una intervención psicológica.\n\nSu participación es completamente voluntaria. Puede retirarse en cualquier momento sin consecuencias.\n\nLos datos se recopilarán de forma anónima y confidencial, conforme a las normativas de protección de datos.\n\nSi tiene preguntas, puede contactar al investigador principal.\n\n¿Está de acuerdo en participar?";
}

?>
<div class="eipsi-wizard-step" id="step-4">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="4">
        
        <!-- Header del step -->
        <div class="eipsi-wiz-step-header">
            <p class="eipsi-wiz-step-title">Participantes</p>
            <p class="eipsi-wiz-step-sub">Selecciona cómo invitarás a los participantes y qué información mostrarás antes de comenzar.</p>
        </div>
        
        <div class="participants-config">
            <!-- Invitation Methods -->
            <div class="invitation-section" style="background:#f8f9fa;padding:20px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:20px;">
                <h3 style="margin:0 0 8px 0;color:#2c3e50;font-size:15px;font-weight:600;">Métodos de Invitación</h3>
                <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">Elige cómo deseas invitar a los participantes a tu estudio.</p>
                
                <div class="invitation-methods">
                    <!-- Method Card 1 -->
                    <div class="eipsi-method-card <?php echo in_array('magic_links', $invitation_methods) ? 'selected' : ''; ?>">
                        <div class="eipsi-method-header">
                            <input type="checkbox" 
                                   name="invitation_methods[]" 
                                   value="magic_links"
                                   <?php checked(in_array('magic_links', $invitation_methods)); ?>
                                   id="magic_links">
                            <label for="magic_links" class="eipsi-method-title">Magic Links por Email</label>
                        </div>
                        <div class="eipsi-method-desc">
                            Enviar un enlace único personalizado por email a cada participante.
                            <ul style="margin:8px 0 0 0;padding-left:16px;list-style:none;">
                                <li style="font-size:12px;color:#008080;margin-bottom:3px;">✓ Enlaces únicos y seguros</li>
                                <li style="font-size:12px;color:#008080;margin-bottom:3px;">✓ Seguimiento de accesos</li>
                                <li style="font-size:12px;color:#008080;">✓ No requiere registro previo</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Method Card 2 -->
                    <div class="eipsi-method-card <?php echo in_array('csv_upload', $invitation_methods) ? 'selected' : ''; ?>">
                        <div class="eipsi-method-header">
                            <input type="checkbox" 
                                   name="invitation_methods[]" 
                                   value="csv_upload"
                                   <?php checked(in_array('csv_upload', $invitation_methods)); ?>
                                   id="csv_upload">
                            <label for="csv_upload" class="eipsi-method-title">Subir Lista CSV</label>
                        </div>
                        <div class="eipsi-method-desc">
                            Subir un archivo CSV con emails de participantes para envío masivo.
                            <ul style="margin:8px 0 0 0;padding-left:16px;list-style:none;">
                                <li style="font-size:12px;color:#008080;margin-bottom:3px;">✓ Envío masivo</li>
                                <li style="font-size:12px;color:#008080;margin-bottom:3px;">✓ Plantilla CSV incluida</li>
                                <li style="font-size:12px;color:#008080;">✓ Validación de emails</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Method Card 3 -->
                    <div class="eipsi-method-card <?php echo in_array('public_registration', $invitation_methods) ? 'selected' : ''; ?>">
                        <div class="eipsi-method-header">
                            <input type="checkbox" 
                                   name="invitation_methods[]" 
                                   value="public_registration"
                                   <?php checked(in_array('public_registration', $invitation_methods)); ?>
                                   id="public_registration">
                            <label for="public_registration" class="eipsi-method-title">Registro Público</label>
                        </div>
                        <div class="eipsi-method-desc">
                            Crear una página pública donde los participantes se registran voluntariamente.
                            <ul style="margin:8px 0 0 0;padding-left:16px;list-style:none;">
                                <li style="font-size:12px;color:#008080;margin-bottom:3px;">✓ Sin límites de invitación</li>
                                <li style="font-size:12px;color:#008080;margin-bottom:3px;">✓ Página web personalizada</li>
                                <li style="font-size:12px;color:#008080;">✓ Auto-registro</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Consent & Privacy -->
            <div class="consent-section" style="background:#f8f9fa;padding:20px;border-radius:10px;border:1px solid #e2e8f0;">
                <h3 style="margin:0 0 8px 0;color:#2c3e50;font-size:15px;font-weight:600;">Consentimiento Informado</h3>
                <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">Configura la información que verán los participantes antes de comenzar.</p>
                
                <div class="consent-config">
                    <div class="eipsi-wiz-check-row">
                        <label class="eipsi-wiz-check">
                            <input type="checkbox" 
                                   name="require_consent"
                                   <?php checked($require_consent, true); ?>
                                   value="1">
                            <span class="eipsi-wiz-check-text">
                                <strong>Requerir consentimiento informado</strong>
                                <span class="eipsi-wiz-check-sub">Los participantes deben aceptar antes de continuar</span>
                            </span>
                        </label>
                    </div>
                    
                    <div class="eipsi-wiz-check-row">
                        <label class="eipsi-wiz-check">
                            <input type="checkbox" 
                                   name="show_privacy_notice"
                                   <?php checked($show_privacy_notice, true); ?>
                                   value="1">
                            <span class="eipsi-wiz-check-text">
                                <strong>Mostrar aviso de privacidad</strong>
                                <span class="eipsi-wiz-check-sub">Información sobre protección de datos</span>
                            </span>
                        </label>
                    </div>
                    
                    <div class="eipsi-wiz-check-row">
                        <label class="eipsi-wiz-check">
                            <input type="checkbox" 
                                   name="auto_removal_inactive"
                                   <?php checked($auto_removal_inactive, false); ?>
                                   value="1">
                            <span class="eipsi-wiz-check-text">
                                <strong>Auto-remove participantes inactivos</strong>
                                <span class="eipsi-wiz-check-sub">Eliminar automáticamente participantes sin actividad después de 30 días</span>
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="consent-message-editor" style="margin-top:20px;padding-top:20px;border-top:1px solid #e2e8f0;">
                    <label for="consent_message" class="eipsi-wiz-label">Mensaje de Consentimiento</label>
                    <textarea id="consent_message" 
                              name="consent_message" 
                              class="eipsi-wiz-textarea" 
                              rows="8"
                              placeholder="Escribe el mensaje que verán los participantes..."><?php echo esc_textarea($consent_message); ?></textarea>
                    <span class="eipsi-wiz-help">Este texto aparecerá antes del primer formulario. Debe explicar el propósito del estudio y los derechos del participante.</span>
                    
                    <!-- Plantillas -->
                    <div style="margin-top:16px;">
                        <h4 style="margin:0 0 12px 0;color:#2c3e50;font-size:13px;">Plantillas de Consentimiento:</h4>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="button"
                                style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                                onmouseover="this.style.backgroundColor='#d6edff';this.style.borderColor='#3B6CAA';this.style.color='#1E3A5F'"
                                onmouseout="this.style.backgroundColor='#f1f5f9';this.style.borderColor='#e2e8f0';this.style.color='#2c3e50'"
                                onclick="eipsiApplyConsentTemplate('general')">
                                Consentimiento General
                            </button>
                            <button type="button"
                                style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                                onmouseover="this.style.backgroundColor='#d6edff';this.style.borderColor='#3B6CAA';this.style.color='#1E3A5F'"
                                onmouseout="this.style.backgroundColor='#f1f5f9';this.style.borderColor='#e2e8f0';this.style.color='#2c3e50'"
                                onclick="eipsiApplyConsentTemplate('clinical')">
                                Consentimiento Clínico
                            </button>
                            <button type="button"
                                style="padding:6px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#2c3e50;cursor:pointer;"
                                onmouseover="this.style.backgroundColor='#d6edff';this.style.borderColor='#3B6CAA';this.style.color='#1E3A5F'"
                                onmouseout="this.style.backgroundColor='#f1f5f9';this.style.borderColor='#e2e8f0';this.style.color='#2c3e50'"
                                onclick="eipsiApplyConsentTemplate('research')">
                                Consentimiento Investigación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Autosave hint -->
    <div class="eipsi-wiz-autosave" id="eipsi-autosave-hint"></div>
    
    <!-- Tip box -->
    <div style="background:#f0f6fc;border:1px solid #AED6F1;border-radius:8px;padding:14px 16px;margin-top:16px;">
        <p style="font-size:12px;font-weight:500;color:#1E3A5F;margin-bottom:8px;">Sobre el consentimiento informado</p>
        <ul style="padding-left:16px;margin:0;">
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">El participante debe ver y aceptar el consentimiento antes de responder</li>
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">Podés personalizar el texto según las necesidades de tu estudio</li>
            <li style="font-size:12px;color:#64748b;line-height:1.4;">El Magic Link es el método más seguro para invitar participantes identificados</li>
        </ul>
    </div>
</div>

<script>
// Consent template functions - MANTENER TAL CUAL
function eipsiApplyConsentTemplate(template) {
    const consentTextarea = document.getElementById('consent_message');
    
    const templates = {
        'general': `Estimado/a participante,\n\nLe invitamos a participar en este estudio de investigación. Su participación es completamente voluntaria y puede retirarse en cualquier momento sin consecuencias.\n\nEl estudio tiene como objetivo [OBJETIVO DEL ESTUDIO]. La participación implicará completar cuestionarios que tomarán aproximadamente [DURACIÓN] minutos.\n\nSus respuestas serán confidenciales y anónimas. Los datos se utilizarán únicamente para fines de investigación académica.\n\nSi tiene preguntas sobre el estudio, puede contactar al investigador principal.\n\n¿Está de acuerdo en participar?`,
        
        'clinical': `Estimado/a participante,\n\nEste estudio evalúa la efectividad de intervenciones psicológicas en un contexto clínico.\n\nSu participación es completamente voluntaria. Puede retirarse del tratamiento en cualquier momento sin afectar su atención médica.\n\nLos datos clínicos se manejarán con estricta confidencialidad, conforme a las normativas de protección de datos de salud.\n\nSus respuestas ayudarán a mejorar las intervenciones psicológicas para futuros pacientes.\n\n¿Autoriza su participación en este estudio de investigación?`,
        
        'research': `Estimado/a participante,\n\nEste es un estudio de investigación académica sobre [TEMA DE INVESTIGACIÓN].\n\nSu participación implica:\n• Completar cuestionarios sobre [TEMAS]\n• Duración estimada: [DURACIÓN]\n• Participación completamente voluntaria\n• Derecho a retirarse sin consecuencias\n\nDatos y confidencialidad:\n• Respuestas anónimas y confidenciales\n• Solo el equipo de investigación tendrá acceso\n• Datos utilizados exclusivamente para fines académicos\n• Posibilidad de solicitar eliminación de datos\n\nSi acepta participar, haga clic en "Acepto participar".`,
    };
    
    if (templates[template] && consentTextarea) {
        consentTextarea.value = templates[template];
        
        // Visual feedback - ACTUALIZADO a color EIPSI
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Aplicada';
        btn.style.background = '#008080';
        btn.style.color = 'white';
        btn.style.borderColor = '#008080';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '#f1f5f9';
            btn.style.color = '#2c3e50';
            btn.style.borderColor = '#e2e8f0';
        }, 2000);
    }
}

// Handle method card selection - ACTUALIZADO para usar nuevas clases
document.addEventListener('DOMContentLoaded', function() {
    const methodCards = document.querySelectorAll('.eipsi-method-card');
    const checkboxes = document.querySelectorAll('input[name="invitation_methods[]"]');
    
    methodCards.forEach((card, index) => {
        const checkbox = checkboxes[index];
        
        card.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT') {
                checkbox.checked = !checkbox.checked;
                eipsiUpdateMethodCard(card, checkbox.checked);
            }
        });
        
        // Initialize card state
        eipsiUpdateMethodCard(card, checkbox.checked);
    });
});

function eipsiUpdateMethodCard(card, isSelected) {
    if (isSelected) {
        card.classList.add('selected');
        card.style.borderColor = '#3B6CAA';
        card.style.backgroundColor = '#f0f6fc';
        card.style.boxShadow = '0 0 0 3px rgba(59,108,170,0.1)';
    } else {
        card.classList.remove('selected');
        card.style.borderColor = '#e2e8f0';
        card.style.backgroundColor = '#ffffff';
        card.style.boxShadow = 'none';
    }
}
</script>

<style>
/* Tarjetas de método con estilos inline por defecto */
.eipsi-method-card {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 12px;
}
.eipsi-method-card:hover {
    border-color: #3B6CAA;
}
.eipsi-method-card.selected {
    border-color: #3B6CAA;
    background: #f0f6fc;
    box-shadow: 0 0 0 3px rgba(59,108,170,0.1);
}
.eipsi-method-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}
.eipsi-method-title {
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
    cursor: pointer;
}
.eipsi-method-desc {
    color: #64748b;
    font-size: 13px;
    line-height: 1.4;
    padding-left: 32px;
}

/* Checkboxes con estructura EIPSI */
.eipsi-wiz-check-row {
    margin-bottom: 14px;
    padding: 14px;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.eipsi-wiz-check {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    gap: 12px;
}
.eipsi-wiz-check input[type="checkbox"] {
    margin-top: 2px;
    transform: scale(1.2);
    flex-shrink: 0;
}
.eipsi-wiz-check-text {
    display: flex;
    flex-direction: column;
    line-height: 1.4;
    color: #2c3e50;
}
.eipsi-wiz-check-text strong {
    font-weight: 600;
    font-size: 13px;
}
.eipsi-wiz-check-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}
</style>
