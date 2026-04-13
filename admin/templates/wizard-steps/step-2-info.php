<?php
/**
 * Wizard Step 2: Configuración de Tomas/Waves
 * 
 * Template para configurar las tomas longitudinales del estudio.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

$step_data = isset($wizard_data['step_2']) ? $wizard_data['step_2'] : array();

// Set default values
$number_of_waves = isset($step_data['number_of_waves']) ? intval($step_data['number_of_waves']) : 3;
$waves_config = isset($step_data['waves_config']) ? $step_data['waves_config'] : array();

// Ensure we have enough wave configs
while (count($waves_config) < $number_of_waves) {
    $waves_config[] = array(
        'name' => '',
        'form_template_id' => '',
        'estimated_duration' => '',
        'is_required' => true
    );
}

// Default wave names
$default_names = array('Pre-intervención', 'Post-intervención', 'Seguimiento 1 mes', 'Seguimiento 3 meses', 'Seguimiento 6 meses');

for ($i = 0; $i < count($waves_config); $i++) {
    if (empty($waves_config[$i]['name']) && isset($default_names[$i])) {
        $waves_config[$i]['name'] = $default_names[$i];
    }
}

?>
<div class="eipsi-wizard-step" id="step-2">
    <form id="eipsi-wizard-form" method="post">
        <input type="hidden" name="step_number" value="2">
        
        <!-- Header del step -->
        <div class="eipsi-wiz-step-header">
            <p class="eipsi-wiz-step-title">Waves</p>
            <p class="eipsi-wiz-step-sub">Define cuántas evaluaciones realizarás y qué formularios usarás para cada una.</p>
        </div>
        
        <!-- Contador de waves -->
        <div class="eipsi-wiz-counter-wrap">
            <label class="eipsi-wiz-label">Cantidad de tomas<span class="eipsi-req">*</span></label>
            <div class="eipsi-wiz-counter">
                <button type="button" class="eipsi-wiz-counter-btn" onclick="eipsiDecreaseWaves()">−</button>
                <input type="number" id="number_of_waves" name="number_of_waves" class="eipsi-wiz-counter-input" value="<?php echo $number_of_waves; ?>" min="1" max="10" readonly>
                <button type="button" class="eipsi-wiz-counter-btn" onclick="eipsiIncreaseWaves()">+</button>
            </div>
            <span class="eipsi-wiz-help">Típicamente: baseline, post-tratamiento, seguimiento.</span>
        </div>
        
        <!-- Lista de waves -->
        <div class="waves-list" id="waves-list">
            <?php for ($i = 0; $i < $number_of_waves; $i++): ?>
                <div class="eipsi-wave-card" data-wave="<?php echo $i + 1; ?>">
                    <div class="eipsi-wave-card-header">
                        <span class="eipsi-wave-num">T<?php echo $i + 1; ?></span>
                        <span class="eipsi-wave-card-title"><?php echo esc_html($waves_config[$i]['name'] ?: 'Toma ' . ($i + 1)); ?></span>
                        <span class="eipsi-wave-required <?php echo $i === 0 ? 'required' : 'optional'; ?>">
                            <?php echo $i === 0 ? 'Obligatoria' : 'Opcional'; ?>
                        </span>
                    </div>
                    <div class="eipsi-wave-card-body">
                        <div class="eipsi-wiz-field">
                            <label for="wave_name_<?php echo $i; ?>" class="eipsi-wiz-label">Nombre de la toma</label>
                            <input type="text" 
                                   id="wave_name_<?php echo $i; ?>"
                                   name="waves_config[<?php echo $i; ?>][name]" 
                                   class="eipsi-wiz-input" 
                                   value="<?php echo esc_attr($waves_config[$i]['name']); ?>"
                                   placeholder="Ej: Evaluación inicial">
                        </div>
                        <div class="eipsi-wiz-field">
                            <label for="wave_form_<?php echo $i; ?>" class="eipsi-wiz-label">Formulario a usar</label>
                            <select id="wave_form_<?php echo $i; ?>"
                                    name="waves_config[<?php echo $i; ?>][form_template_id]" 
                                    class="eipsi-wiz-select">
                                <option value="">Seleccionar formulario...</option>
                                <?php foreach ($available_forms as $form): ?>
                                    <option value="<?php echo esc_attr($form['ID']); ?>"
                                            <?php selected($waves_config[$i]['form_template_id'], $form['ID']); ?>>
                                        <?php echo esc_html($form['post_title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </form>
    
    <!-- Autosave hint -->
    <div class="eipsi-wiz-autosave" id="eipsi-autosave-hint"></div>
    
    <!-- Tip box -->
    <div style="background:#f0f6fc;border:1px solid #AED6F1;border-radius:8px;padding:14px 16px;margin-top:16px;">
        <p style="font-size:12px;font-weight:500;color:#1E3A5F;margin-bottom:8px;">Consejos para tus tomas</p>
        <ul style="padding-left:16px;margin:0;">
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">Usá nombres claros para tus tomas: "Línea base", "Seguimiento 1", "Final"</li>
            <li style="font-size:12px;color:#64748b;margin-bottom:4px;line-height:1.4;">Programá recordatorios para mejorar la tasa de respuesta</li>
            <li style="font-size:12px;color:#64748b;line-height:1.4;">Podés agregar participantes después de activar el estudio</li>
        </ul>
    </div>
</div>

<script>
// Wave management functions - MANTENER TAL CUAL
function eipsiIncreaseWaves() {
    const input = document.getElementById('number_of_waves');
    const currentValue = parseInt(input.value);
    const maxWaves = 10;
    
    if (currentValue < maxWaves) {
        input.value = currentValue + 1;
        eipsiUpdateWavesList();
    }
}

function eipsiDecreaseWaves() {
    const input = document.getElementById('number_of_waves');
    const currentValue = parseInt(input.value);
    const minWaves = 1;
    
    if (currentValue > minWaves) {
        input.value = currentValue - 1;
        eipsiUpdateWavesList();
    }
}

function eipsiUpdateWavesList() {
    const numberOfWaves = parseInt(document.getElementById('number_of_waves').value);
    const wavesList = document.getElementById('waves-list');
    
    // Clear existing wave items
    wavesList.innerHTML = '';
    
    // Generate wave items
    for (let i = 0; i < numberOfWaves; i++) {
        const waveItem = eipsiGenerateWaveItem(i);
        wavesList.appendChild(waveItem);
    }
}

// ACTUALIZADO: Template con nuevas clases EIPSI
function eipsiGenerateWaveItem(index) {
    const waveDiv = document.createElement('div');
    waveDiv.className = 'eipsi-wave-card';
    waveDiv.setAttribute('data-wave', index + 1);
    
    const isRequired = index === 0;
    const requiredClass = isRequired ? 'required' : 'optional';
    const requiredText = isRequired ? 'Obligatoria' : 'Opcional';
    const defaultName = getDefaultWaveName(index);
    
    waveDiv.innerHTML = `
        <div class="eipsi-wave-card-header">
            <span class="eipsi-wave-num">T${index + 1}</span>
            <span class="eipsi-wave-card-title">${defaultName}</span>
            <span class="eipsi-wave-required ${requiredClass}">${requiredText}</span>
        </div>
        <div class="eipsi-wave-card-body">
            <div class="eipsi-wiz-field">
                <label for="wave_name_${index}" class="eipsi-wiz-label">Nombre de la toma</label>
                <input type="text" 
                       id="wave_name_${index}"
                       name="waves_config[${index}][name]" 
                       class="eipsi-wiz-input" 
                       value="${defaultName}"
                       placeholder="Ej: Evaluación inicial">
            </div>
            <div class="eipsi-wiz-field">
                <label for="wave_form_${index}" class="eipsi-wiz-label">Formulario a usar</label>
                <select id="wave_form_${index}"
                        name="waves_config[${index}][form_template_id]" 
                        class="eipsi-wiz-select">
                    <option value="">Seleccionar formulario...</option>
                    ${getAvailableFormsHTML()}
                </select>
            </div>
        </div>
    `;
    
    return waveDiv;
}

function getDefaultWaveName(index) {
    const defaultNames = [
        'Pre-intervención',
        'Post-intervención', 
        'Seguimiento 1 mes',
        'Seguimiento 3 meses',
        'Seguimiento 6 meses'
    ];
    
    return defaultNames[index] || `Toma ${index + 1}`;
}

function getAvailableFormsHTML() {
    if (typeof eipsiWizard !== 'undefined' && eipsiWizard.availableForms) {
        return eipsiWizard.availableForms
            .map((form) => `<option value="${form.ID}">${form.post_title}</option>`)
            .join('');
    }

    return '';
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners for the number input
    const numberInput = document.getElementById('number_of_waves');
    numberInput.addEventListener('change', eipsiUpdateWavesList);
});
</script>
