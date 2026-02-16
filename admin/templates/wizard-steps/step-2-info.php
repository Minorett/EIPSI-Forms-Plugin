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
        'estimated_duration' => '', // Empty = Infinite (no time limit)
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
        
        <div class="step-header">
            <h2>📊 Configuración de Waves</h2>
            <p>Define cuántas evaluaciones realizarás y qué formularios usarás para cada una.</p>
        </div>
        
        <div class="waves-config">
            <div class="number-of-waves">
                <label for="number_of_waves" class="form-label required">
                    ¿Cuántas tomas realizarás?
                </label>
                <div class="waves-counter">
                    <button type="button" class="counter-btn" onclick="eipsiDecreaseWaves()">−</button>
                    <input type="number" 
                           id="number_of_waves" 
                           name="number_of_waves" 
                           class="waves-input" 
                           value="<?php echo $number_of_waves; ?>"
                           min="1" 
                           max="10"
                           readonly>
                    <button type="button" class="counter-btn" onclick="eipsiIncreaseWaves()">+</button>
                </div>
                <small class="form-help">
                    Típicamente: baseline (pre), post-tratamiento, y seguimiento a 1-6 meses.
                </small>
            </div>
            
            <div class="waves-list" id="waves-list">
                <?php for ($i = 0; $i < $number_of_waves; $i++): ?>
                    <div class="wave-item" data-wave="<?php echo $i + 1; ?>">
                        <div class="wave-header">
                            <h3>Toma <?php echo $i + 1; ?></h3>
                            <span class="wave-status"><?php echo $waves_config[$i]['is_required'] ? 'Obligatoria' : 'Opcional'; ?></span>
                        </div>
                        
                        <div class="wave-fields">
                            <div class="field-group">
                                <label for="wave_name_<?php echo $i; ?>" class="form-label">
                                    Nombre de la Toma
                                </label>
                                <input type="text" 
                                       id="wave_name_<?php echo $i; ?>"
                                       name="waves_config[<?php echo $i; ?>][name]" 
                                       class="form-input" 
                                       value="<?php echo esc_attr($waves_config[$i]['name']); ?>"
                                       placeholder="Ej: Evaluación inicial">
                            </div>
                            
                            <div class="field-group">
                                <label for="wave_form_<?php echo $i; ?>" class="form-label">
                                    Formulario a usar
                                </label>
                                <select id="wave_form_<?php echo $i; ?>"
                                        name="waves_config[<?php echo $i; ?>][form_template_id]" 
                                        class="form-select">
                                    <option value="">Seleccionar formulario...</option>
                                    <?php foreach ($available_forms as $form): ?>
                                        <option value="<?php echo esc_attr($form['ID']); ?>"
                                                <?php selected($waves_config[$i]['form_template_id'], $form['ID']); ?>>
                                            <?php echo esc_html($form['post_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="field-group-inline">
                                <div class="field-group">
                                    <label for="wave_duration_<?php echo $i; ?>" class="form-label">
                                        ⏱️ Duración estimada
                                    </label>
                                    <input type="number" 
                                           id="wave_duration_<?php echo $i; ?>"
                                           name="waves_config[<?php echo $i; ?>][estimated_duration]" 
                                           class="form-input duration-input" 
                                           value="<?php echo esc_attr($waves_config[$i]['estimated_duration']); ?>"
                                           min="1" 
                                           max="180"
                                           placeholder="∞ Ilimitado"
                                           title="Deja este campo en blanco para tiempo ilimitado, o ingresa los minutos estimados (1-180)">
                                    <small class="field-help">En minutos. Déjalo en blanco para tiempo ilimitado.</small>
                                </div>
                                
                                <div class="field-group">
                                    <label class="form-label">
                                        &nbsp;
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" 
                                               name="waves_config[<?php echo $i; ?>][is_required]"
                                               <?php checked($waves_config[$i]['is_required'], true); ?>
                                               value="1">
                                        <span class="checkbox-text">Obligatoria</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </form>
</div>

<script>
// Wave management functions
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

function eipsiGenerateWaveItem(index) {
    const waveDiv = document.createElement('div');
    waveDiv.className = 'wave-item';
    waveDiv.setAttribute('data-wave', index + 1);
    
    const isRequired = index === 0 ? true : false; // First wave is required by default
    const defaultName = getDefaultWaveName(index);
    
    waveDiv.innerHTML = `
        <div class="wave-header">
            <h3>Toma ${index + 1}</h3>
            <span class="wave-status">${isRequired ? 'Obligatoria' : 'Opcional'}</span>
        </div>
        
        <div class="wave-fields">
            <div class="field-group">
                <label for="wave_name_${index}" class="form-label">
                    Nombre de la Toma
                </label>
                <input type="text" 
                       id="wave_name_${index}"
                       name="waves_config[${index}][name]" 
                       class="form-input" 
                       value="${defaultName}"
                       placeholder="Ej: Evaluación inicial">
            </div>
            
            <div class="field-group">
                <label for="wave_form_${index}" class="form-label">
                    Formulario a usar
                </label>
                <select id="wave_form_${index}"
                        name="waves_config[${index}][form_template_id]" 
                        class="form-select">
                    <option value="">Seleccionar formulario...</option>
                    ${getAvailableFormsHTML()}
                </select>
            </div>
            
            <div class="field-group-inline">
                <div class="field-group">
                    <label for="wave_duration_${index}" class="form-label">
                        ⏱️ Duración estimada
                    </label>
                    <input type="number" 
                           id="wave_duration_${index}"
                           name="waves_config[${index}][estimated_duration]" 
                           class="form-input duration-input" 
                           value=""
                           min="1" 
                           max="180"
                           placeholder="∞ Ilimitado"
                           title="Deja este campo en blanco para tiempo ilimitado, o ingresa los minutos estimados (1-180)">
                    <small class="field-help">En minutos. Déjalo en blanco para tiempo ilimitado.</small>
                </div>
                
                <div class="field-group">
                    <label class="form-label">
                        &nbsp;
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="waves_config[${index}][is_required]"
                               ${isRequired ? 'checked' : ''}
                               value="1">
                        <span class="checkbox-text">Obligatoria</span>
                    </label>
                </div>
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

<style>
.waves-config {
    max-width: 900px;
    margin: 0 auto;
}

.number-of-waves {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px solid #e9ecef;
}

.waves-counter {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1rem 0;
}

.counter-btn {
    width: 40px;
    height: 40px;
    border: 2px solid #667eea;
    background: white;
    color: #667eea;
    border-radius: 50%;
    font-size: 1.2rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s ease;
}

.counter-btn:hover {
    background: #667eea;
    color: white;
}

.waves-input {
    width: 80px;
    height: 40px;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    border: 2px solid #667eea;
    border-radius: 8px;
    background: white;
}

.waves-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.wave-item {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.wave-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.wave-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.wave-header h3 {
    margin: 0;
    color: #495057;
    font-size: 1.1rem;
    font-weight: 600;
}

.wave-status {
    padding: 0.25rem 0.75rem;
    background: #667eea;
    color: white;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.wave-fields {
    padding: 1.5rem;
    display: grid;
    gap: 1.5rem;
}

.field-group {
    display: flex;
    flex-direction: column;
}

.field-group-inline {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin-top: 1.75rem;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 0.5rem;
    transform: scale(1.2);
}

.checkbox-text {
    font-weight: 500;
    color: #495057;
}

.field-help {
    display: block;
    margin-top: 4px;
    font-size: 0.8rem;
    color: #6c757d;
    font-style: italic;
}

.duration-input {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cpath d='M12 6v6l4 2'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 35px;
}

.duration-input::placeholder {
    color: #28a745;
    font-weight: 500;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .number-of-waves {
        background: #2c3e50;
        border-color: #34495e;
    }
    
    .wave-item {
        background: #2c3e50;
        border-color: #34495e;
    }
    
    .wave-header {
        background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
        border-color: #4a5f7a;
    }
    
    .wave-header h3 {
        color: #ecf0f1;
    }
    
    .counter-btn {
        background: #2c3e50;
        border-color: #667eea;
        color: #667eea;
    }
    
    .waves-input {
        background: #2c3e50;
        border-color: #667eea;
        color: #ecf0f1;
    }
    
    .checkbox-text {
        color: #ecf0f1;
    }
}
</style>