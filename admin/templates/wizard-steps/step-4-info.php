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

// Set default values
$invitation_methods = isset($step_data['invitation_methods']) ? $step_data['invitation_methods'] : array('magic_links');
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
        
        <div class="step-header">
            <h2>👥 CONFIGURACIÓN DE PARTICIPANTES</h2>
            <p>Selecciona cómo invitarás a los participantes y qué información mostrarás antes de comenzar.</p>
        </div>
        
        <div class="participants-config">
            <!-- Invitation Methods -->
            <div class="invitation-section">
                <h3>📬 Métodos de Invitación</h3>
                <p class="section-description">Elige cómo deseas invitar a los participantes a tu estudio.</p>
                
                <div class="invitation-methods">
                    <div class="method-card <?php echo in_array('magic_links', $invitation_methods) ? 'selected' : ''; ?>">
                        <div class="method-header">
                            <input type="checkbox" 
                                   name="invitation_methods[]" 
                                   value="magic_links"
                                   <?php checked(in_array('magic_links', $invitation_methods)); ?>
                                   id="magic_links">
                            <label for="magic_links" class="method-title">
                                🔗 Magic Links por Email
                            </label>
                        </div>
                        <div class="method-description">
                            Enviar un enlace único personalizado por email a cada participante.
                            <div class="method-features">
                                ✓ Enlaces únicos y seguros<br>
                                ✓ Seguimiento de accesos<br>
                                ✓ No requiere registro previo
                            </div>
                        </div>
                    </div>
                    
                    <div class="method-card <?php echo in_array('csv_upload', $invitation_methods) ? 'selected' : ''; ?>">
                        <div class="method-header">
                            <input type="checkbox" 
                                   name="invitation_methods[]" 
                                   value="csv_upload"
                                   <?php checked(in_array('csv_upload', $invitation_methods)); ?>
                                   id="csv_upload">
                            <label for="csv_upload" class="method-title">
                                📄 Subir Lista CSV
                            </label>
                        </div>
                        <div class="method-description">
                            Subir un archivo CSV con emails de participantes para envío masivo.
                            <div class="method-features">
                                ✓ Envío masivo<br>
                                ✓ Plantilla CSV incluida<br>
                                ✓ Validación de emails
                            </div>
                        </div>
                    </div>
                    
                    <div class="method-card <?php echo in_array('public_registration', $invitation_methods) ? 'selected' : ''; ?>">
                        <div class="method-header">
                            <input type="checkbox" 
                                   name="invitation_methods[]" 
                                   value="public_registration"
                                   <?php checked(in_array('public_registration', $invitation_methods)); ?>
                                   id="public_registration">
                            <label for="public_registration" class="method-title">
                                🌐 Registro Público
                            </label>
                        </div>
                        <div class="method-description">
                            Crear una página pública donde los participantes se registran voluntariamente.
                            <div class="method-features">
                                ✓ Sin límites de invitación<br>
                                ✓ Página web personalizada<br>
                                ✓ Auto-registro
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Consent & Privacy -->
            <div class="consent-section">
                <h3>📋 Consentimiento Informado</h3>
                <p class="section-description">Configura la información que verán los participantes antes de comenzar.</p>
                
                <div class="consent-config">
                    <div class="config-item">
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   name="require_consent"
                                   <?php checked($require_consent, true); ?>
                                   value="1">
                            <span class="checkbox-text">
                                <strong>Requerir consentimiento informado</strong><br>
                                Los participantes deben aceptar antes de continuar
                            </span>
                        </label>
                    </div>
                    
                    <div class="config-item">
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   name="show_privacy_notice"
                                   <?php checked($show_privacy_notice, true); ?>
                                   value="1">
                            <span class="checkbox-text">
                                <strong>Mostrar aviso de privacidad</strong><br>
                                Información sobre protección de datos
                            </span>
                        </label>
                    </div>
                    
                    <div class="config-item">
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   name="auto_removal_inactive"
                                   <?php checked($auto_removal_inactive, false); ?>
                                   value="1">
                            <span class="checkbox-text">
                                <strong>Auto-remove participantes inactivos</strong><br>
                                Eliminar automáticamente participantes sin actividad después de 30 días
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="consent-message-editor">
                    <label for="consent_message" class="form-label">
                        Mensaje de Consentimiento
                    </label>
                    <textarea id="consent_message" 
                              name="consent_message" 
                              class="form-textarea" 
                              rows="8"
                              placeholder="Escribe el mensaje que verán los participantes..."><?php echo esc_textarea($consent_message); ?></textarea>
                    <small class="form-help">
                        Este texto aparecerá antes del primer formulario. Debe explicar el propósito del estudio y los derechos del participante.
                    </small>
                    
                    <div class="consent-templates">
                        <h4>Plantillas de Consentimiento:</h4>
                        <div class="template-buttons">
                            <button type="button" class="template-btn" onclick="eipsiApplyConsentTemplate('general')">
                                📋 Consentimiento General
                            </button>
                            <button type="button" class="template-btn" onclick="eipsiApplyConsentTemplate('clinical')">
                                🏥 Consentimiento Clínico
                            </button>
                            <button type="button" class="template-btn" onclick="eipsiApplyConsentTemplate('research')">
                                🔬 Consentimiento Investigación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Consent template functions
function eipsiApplyConsentTemplate(template) {
    const consentTextarea = document.getElementById('consent_message');
    
    const templates = {
        'general': `Estimado/a participante,\n\nLe invitamos a participar en este estudio de investigación. Su participación es completamente voluntaria y puede retirarse en cualquier momento sin consecuencias.\n\nEl estudio tiene como objetivo [OBJETIVO DEL ESTUDIO]. La participación implicará completar cuestionarios que tomarán aproximadamente [DURACIÓN] minutos.\n\nSus respuestas serán confidenciales y anónimas. Los datos se utilizarán únicamente para fines de investigación académica.\n\nSi tiene preguntas sobre el estudio, puede contactar al investigador principal.\n\n¿Está de acuerdo en participar?`,
        
        'clinical': `Estimado/a participante,\n\nEste estudio evalúa la efectividad de intervenciones psicológicas en un contexto clínico.\n\nSu participación es completamente voluntaria. Puede retirarse del tratamiento en cualquier momento sin afectar su atención médica.\n\nLos datos clínicos se manejarán con estricta confidencialidad, conforme a las normativas de protección de datos de salud.\n\nSus respuestas ayudarán a mejorar las intervenciones psicológicas para futuros pacientes.\n\n¿Autoriza su participación en este estudio de investigación?`,
        
        'research': `Estimado/a participante,\n\nEste es un estudio de investigación académica sobre [TEMA DE INVESTIGACIÓN].\n\nSu participación implica:\n• Completar cuestionarios sobre [TEMAS]\n• Duración estimada: [DURACIÓN]\n• Participación completamente voluntaria\n• Derecho a retirarse sin consecuencias\n\nDatos y confidencialidad:\n• Respuestas anónimas y confidenciales\n• Solo el equipo de investigación tendrá acceso\n• Datos utilizados exclusivamente para fines académicos\n• Posibilidad de solicitar eliminación de datos\n\nSi acepta participar, haga clic en "Acepto participar".`,
    };
    
    if (templates[template] && consentTextarea) {
        consentTextarea.value = templates[template];
        
        // Visual feedback
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✅ Aplicada';
        btn.style.background = '#28a745';
        btn.style.color = 'white';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    }
}

// Handle method card selection
document.addEventListener('DOMContentLoaded', function() {
    const methodCards = document.querySelectorAll('.method-card');
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
        card.style.borderColor = '#4a6fa5';
        card.style.backgroundColor = '#243a57';
    } else {
        card.classList.remove('selected');
        card.style.borderColor = '#2c4a71';
        card.style.backgroundColor = '#1f314a';
    }
}
</script>

<style>
.participants-config {
    max-width: 1000px;
    margin: 0 auto;
    display: grid;
    gap: 2rem;
}

.invitation-section,
.consent-section {
    background: var(--eipsi-primary-dark);
    padding: 2rem;
    border-radius: 12px;
    border: 2px solid #1f314a;
    color: #ffffff;
}

.invitation-section h3,
.consent-section h3 {
    margin: 0 0 0.5rem 0;
    color: #ffffff;
    font-size: 1.2rem;
    font-weight: 600;
}

.section-description {
    margin: 0 0 1.5rem 0;
    color: #ffffff;
    font-size: 0.95rem;
    opacity: 0.85;
}

.invitation-methods {
    display: grid;
    gap: 1rem;
}

.method-card {
    border: 2px solid #2c4a71;
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #1f314a;
    color: #ffffff;
}

.method-card:hover {
    border-color: #4a6fa5;
    box-shadow: 0 4px 12px rgba(31, 49, 74, 0.4);
}

.method-card.selected {
    border-color: #4a6fa5;
    background: #243a57;
}

.method-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.method-header input[type="checkbox"] {
    transform: scale(1.2);
}

.method-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}

.method-description {
    color: #ffffff;
    line-height: 1.5;
    opacity: 0.9;
}

.method-features {
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: #ffffff;
    font-weight: 500;
}

.consent-config {
    margin-bottom: 2rem;
}

.config-item {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #1f314a;
    border-radius: 8px;
    border: 1px solid #2c4a71;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    gap: 0.75rem;
}

.checkbox-label input[type="checkbox"] {
    margin-top: 0.2rem;
    transform: scale(1.1);
}

.checkbox-text {
    line-height: 1.4;
    color: #ffffff;
}

.consent-message-editor {
    border-top: 1px solid #2c4a71;
    padding-top: 1.5rem;
}

.consent-message-editor .form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #ffffff;
    display: block;
}

.consent-message-editor .form-textarea {
    background: #1f314a;
    border-color: #2c4a71;
    color: #ffffff;
}

.consent-message-editor .form-textarea::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.consent-section .form-help {
    color: #ffffff;
    opacity: 0.8;
}

.consent-templates {
    margin-top: 1rem;
}

.consent-templates h4 {
    margin: 0 0 1rem 0;
    color: #ffffff;
    font-size: 1rem;
}

.template-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.template-btn {
    padding: 0.5rem 1rem;
    background: #1f314a;
    border: 2px solid #2c4a71;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    white-space: nowrap;
    color: #ffffff;
}

.template-btn:hover {
    background: #4a6fa5;
    color: white;
    border-color: #4a6fa5;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .invitation-section,
    .consent-section {
        background: var(--eipsi-primary-dark);
        border-color: #1f314a;
    }
    
    .method-card {
        background: #1f314a;
        border-color: #2c4a71;
        color: #ffffff;
    }
    
    .method-card.selected {
        background: #243a57;
        border-color: #4a6fa5;
    }
    
    .method-card:hover {
        border-color: #4a6fa5;
        box-shadow: 0 4px 12px rgba(31, 49, 74, 0.4);
    }
    
    .method-title,
    .method-description,
    .method-features,
    .checkbox-text {
        color: #ffffff;
    }
    
    .config-item {
        background: #1f314a;
        border-color: #2c4a71;
    }
    
    .template-btn {
        background: #1f314a;
        border-color: #2c4a71;
        color: #ffffff;
    }
    
    .template-btn:hover {
        background: #4a6fa5;
        border-color: #4a6fa5;
        color: white;
    }
}
</style>