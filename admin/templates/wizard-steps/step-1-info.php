<?php
/**
 * Wizard Step 1: Información Básica
 * 
 * Template para capturar información básica del estudio.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

$step_data = isset($wizard_data['step_1']) ? $wizard_data['step_1'] : array();

// Set default values
$study_name = isset($step_data['study_name']) ? $step_data['study_name'] : '';
$study_code = isset($step_data['study_code']) ? $step_data['study_code'] : '';
$description = isset($step_data['description']) ? $step_data['description'] : '';
$principal_investigator_id = isset($step_data['principal_investigator_id']) ? $step_data['principal_investigator_id'] : '';

// Auto-generate study code if empty
if (empty($study_code) && !empty($study_name)) {
    $study_code = eipsi_generate_study_code_from_name($study_name);
}

?>
<div class="eipsi-wizard-step" id="step-1">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="1">
        
        <!-- Header del step -->
        <div class="eipsi-wiz-step-header">
            <p class="eipsi-wiz-step-title">Información básica</p>
            <p class="eipsi-wiz-step-sub">Proporciona la información esencial de tu estudio de investigación.</p>
        </div>
        
        <!-- Campos de formulario -->
        <div class="eipsi-wiz-field">
            <label for="study_name" class="eipsi-wiz-label">Nombre del Estudio<span class="eipsi-req">*</span></label>
            <input type="text" 
                   id="study_name" 
                   name="study_name" 
                   class="eipsi-wiz-input" 
                   value="<?php echo esc_attr($study_name); ?>"
                   placeholder="Ej: Efectividad de Intervención Cognitivo-Conductual"
                   required>
            <span class="eipsi-wiz-help">Un nombre descriptivo que identifique claramente tu investigación.</span>
        </div>
        
        <div class="eipsi-wiz-field">
            <label for="study_code" class="eipsi-wiz-label">Código del Estudio (ID)<span class="eipsi-req">*</span></label>
            <input type="text" 
                   id="study_code" 
                   name="study_code" 
                   class="eipsi-wiz-input" 
                   value="<?php echo esc_attr($study_code); ?>"
                   placeholder="Se generará automáticamente"
                   required
                   maxlength="50">
            <span class="eipsi-wiz-help">
                Código único para identificar el estudio. Se usará en URLs y bases de datos.
                <button type="button" 
                    style="background:none;border:none;color:#3B6CAA;cursor:pointer;text-decoration:underline;font-size:11px;padding:0;margin-left:8px;"
                    onmouseover="this.style.color='#1E3A5F'"
                    onmouseout="this.style.color='#3B6CAA'"
                    onclick="eipsiRegenerateCode()">
                    Regenerar
                </button>
            </span>
        </div>
        
        <div class="eipsi-wiz-field">
            <label for="description" class="eipsi-wiz-label">Descripción (para participantes)</label>
            <textarea id="description" 
                      name="description" 
                      class="eipsi-wiz-textarea" 
                      rows="4"
                      placeholder="Describe brevemente el propósito del estudio y qué se espera de los participantes..."><?php echo esc_textarea($description); ?></textarea>
            <span class="eipsi-wiz-help">Esta descripción será visible para los participantes en los formularios de consentimiento.</span>
        </div>
        
        <div class="eipsi-wiz-field">
            <label for="principal_investigator_id" class="eipsi-wiz-label">Investigador Principal<span class="eipsi-req">*</span></label>
            <select id="principal_investigator_id" 
                    name="principal_investigator_id" 
                    class="eipsi-wiz-select" 
                    required>
                <option value="">Seleccionar investigador...</option>
                <?php foreach ($admin_users as $user): ?>
                    <option value="<?php echo $user->ID; ?>" 
                            <?php selected($principal_investigator_id, $user->ID); ?>>
                        <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="eipsi-wiz-help">Usuario administrador responsable del estudio.</span>
        </div>
    </form>
    
    <!-- Autosave hint -->
    <div class="eipsi-wiz-autosave" id="eipsi-autosave-hint"></div>
    
    <!-- Tip box -->
    <div style="background:#f0f6fc;border:1px solid #AED6F1;border-radius:8px;padding:14px 16px;margin-top:16px;">
        <p style="font-size:12px;font-weight:500;color:#1E3A5F;margin-bottom:8px;">Consejos para tu estudio</p>
        <ul style="padding-left:16px;margin:0;">
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">Usá nombres claros para identificar tu investigación</li>
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">El código se usará en URLs y base de datos</li>
            <li style="font-size:12px;color:#64748b;line-height:1.4;">Podés modificar esta información más adelante</li>
        </ul>
    </div>
</div>

<script>
// Auto-generate study code from name
function eipsiRegenerateCode() {
    const nameField = document.getElementById('study_name');
    const codeField = document.getElementById('study_code');
    
    if (nameField.value.trim()) {
        const code = eipsiGenerateCodeFromName(nameField.value.trim());
        codeField.value = code;
    } else {
        alert('Por favor, ingresa primero el nombre del estudio.');
    }
}

function eipsiGenerateCodeFromName(name) {
    // Remove accents and special characters
    let cleanName = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    cleanName = cleanName.replace(/[^a-zA-Z0-9\s]/g, '');
    
    // Convert to uppercase and replace spaces with underscores
    cleanName = cleanName.toUpperCase().replace(/\s+/g, '_');
    
    // Take first 3 words or truncate to 15 characters
    const words = cleanName.split('_').slice(0, 3);
    let prefix = words.join('_');
    
    if (prefix.length > 15) {
        prefix = prefix.substring(0, 15);
    }
    
    // Add year
    const year = new Date().getFullYear();
    const finalCode = prefix + '_' + year;
    
    return finalCode;
}

// Auto-update code when name changes
document.addEventListener('DOMContentLoaded', function() {
    const nameField = document.getElementById('study_name');
    const codeField = document.getElementById('study_code');
    
    if (nameField && codeField) {
        let timeout;
        
        nameField.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                if (nameField.value.trim() && !codeField.dataset.userModified) {
                    const code = eipsiGenerateCodeFromName(nameField.value.trim());
                    codeField.value = code;
                }
            }, 500);
        });
        
        // Mark as user modified when code field is changed
        codeField.addEventListener('input', function() {
            codeField.dataset.userModified = 'true';
        });
    }
});
</script>

<style>
/* Espaciado aumentado entre campos del wizard */
.eipsi-wiz-field {
    margin-bottom: 24px !important;
}
.eipsi-wiz-field:last-child {
    margin-bottom: 0 !important;
}
</style>
