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
        
        <div class="step-header">
            <h2>📋 INFORMACIÓN BÁSICA</h2>
            <p>Proporciona la información esencial de tu estudio de investigación.</p>
        </div>
        
        <div class="form-grid">
            <div class="form-row">
                <label for="study_name" class="form-label required">
                    Nombre del Estudio
                </label>
                <input type="text" 
                       id="study_name" 
                       name="study_name" 
                       class="form-input" 
                       value="<?php echo esc_attr($study_name); ?>"
                       placeholder="Ej: Efectividad de Intervención Cognitivo-Conductual"
                       required>
                <small class="form-help">
                    Un nombre descriptivo que identifique claramente tu investigación.
                </small>
            </div>
            
            <div class="form-row">
                <label for="study_code" class="form-label required">
                    Código del Estudio (ID)
                </label>
                <input type="text" 
                       id="study_code" 
                       name="study_code" 
                       class="form-input" 
                       value="<?php echo esc_attr($study_code); ?>"
                       placeholder="Se generará automáticamente"
                       required
                       maxlength="50">
                <small class="form-help">
                    Código único para identificar el estudio. Se usará en URLs y bases de datos.
                    <button type="button" class="button-link" onclick="eipsiRegenerateCode()">
                        🔄 Regenerar
                    </button>
                </small>
            </div>
            
            <div class="form-row">
                <label for="description" class="form-label">
                    Descripción (para participantes)
                </label>
                <textarea id="description" 
                          name="description" 
                          class="form-textarea" 
                          rows="4"
                          placeholder="Describe brevemente el propósito del estudio y qué se espera de los participantes..."><?php echo esc_textarea($description); ?></textarea>
                <small class="form-help">
                    Esta descripción será visible para los participantes en los formularios de consentimiento.
                </small>
            </div>
            
            <div class="form-row">
                <label for="principal_investigator_id" class="form-label required">
                    Investigador Principal
                </label>
                <select id="principal_investigator_id" 
                        name="principal_investigator_id" 
                        class="form-select" 
                        required>
                    <option value="">Seleccionar investigador...</option>
                    <?php foreach ($admin_users as $user): ?>
                        <option value="<?php echo $user->ID; ?>" 
                                <?php selected($principal_investigator_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-help">
                    Usuario administrador responsable del estudio.
                </small>
            </div>
        </div>
    </form>
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
.eipsi-wizard-step {
    max-width: 800px;
    margin: 0 auto;
}

.step-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
}

.step-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.step-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.form-grid {
    display: grid;
    gap: 1.5rem;
}

.form-row {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    font-size: 0.95rem;
}

.form-label.required::after {
    content: ' *';
    color: #e74c3c;
}

.form-input,
.form-select,
.form-textarea {
    padding: 0.75rem;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
    background: white;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-help {
    margin-top: 0.25rem;
    font-size: 0.85rem;
    color: #6c757d;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.button-link {
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    text-decoration: underline;
    font-size: 0.85rem;
    padding: 0;
}

.button-link:hover {
    color: #5a6fd8;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .form-input,
    .form-select,
    .form-textarea {
        background: #2c3e50;
        border-color: #34495e;
        color: #ecf0f1;
    }
    
    .form-label {
        color: #ecf0f1;
    }
    
    .form-help {
        color: #95a5a6;
    }
}
</style>