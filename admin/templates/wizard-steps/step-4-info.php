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

?>
<div class="eipsi-wizard-step" id="step-4">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="4">
        
        <!-- Header del step -->
        <div class="eipsi-wiz-step-header">
            <p class="eipsi-wiz-step-title">Participantes</p>
            <p class="eipsi-wiz-step-sub">Selecciona cómo invitarás a los participantes a tu estudio.</p>
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
        </div>
    </form>
    
    <!-- Autosave hint -->
    <div class="eipsi-wiz-autosave" id="eipsi-autosave-hint"></div>
    
    <!-- Tip box -->
    <div style="background:#f0f6fc;border:1px solid #AED6F1;border-radius:8px;padding:14px 16px;margin-top:16px;">
        <p style="font-size:12px;font-weight:500;color:#1E3A5F;margin-bottom:8px;">💡 Sobre los métodos de invitación</p>
        <ul style="padding-left:16px;margin:0;">
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;"><strong>Magic Links:</strong> El método más seguro para estudios con participantes identificados. Cada enlace es único y no transferible.</li>
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;"><strong>CSV Upload:</strong> Ideal para invitar a múltiples participantes de forma masiva desde una lista existente.</li>
            <li style="font-size:12px;color:#64748b;line-height:1.4;"><strong>Registro Público:</strong> Perfecto para estudios abiertos donde los participantes se auto-registran voluntariamente.</li>
        </ul>
    </div>
</div>

<script>
// Handle method card selection
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
/* Tarjetas de método */
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
</style>
