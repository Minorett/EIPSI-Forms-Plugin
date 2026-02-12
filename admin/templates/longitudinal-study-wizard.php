<?php
/**
 * Longitudinal Study Wizard Template
 * 
 * Modern, user-friendly interface for creating longitudinal studies.
 * Designed specifically for clinical psychologists and psychiatrists.
 * 
 * @package EIPSI_Forms
 * @since 1.5.3
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current step and data
$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$current_step = max(1, min(5, $current_step));

$wizard_data = eipsi_get_wizard_data();
$errors = isset($errors) ? $errors : array();
$success_message = isset($message) ? $message : '';

// Step names with clinical-friendly language
$steps = array(
    1 => array(
        'name' => 'Información Básica',
        'description' => 'Configura los detalles fundamentales de tu estudio',
        'icon' => '📋'
    ),
    2 => array(
        'name' => 'Configuración de Tomas',
        'description' => 'Define las ondas/tomas y sus formularios',
        'icon' => '📊'
    ),
    3 => array(
        'name' => 'Programación Temporal',
        'description' => 'Establece fechas y recordatorios',
        'icon' => '⏰'
    ),
    4 => array(
        'name' => 'Participantes',
        'description' => 'Agrega o importa participantes',
        'icon' => '👥'
    ),
    5 => array(
        'name' => 'Revisión y Activación',
        'description' => 'Revisa y activa tu estudio',
        'icon' => '✅'
    )
);

?>

<div class="wrap eipsi-longitudinal-study fade-in">
    <!-- Header with clear title -->
    <div class="study-header">
        <h1>
            <?php echo $current_step === 5 ? '✅ Revisión Final' : '📊 Estudio Longitudinal'; ?>
        </h1>
        <p class="eipsi-step-indicator">
            Paso <?php echo $current_step; ?> de 5: <?php echo esc_html($steps[$current_step]['description']); ?>
        </p>
    </div>

    <!-- Error and success messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Por favor, corrige los siguientes errores:</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- Modern Progress Indicator -->
    <div class="study-progress">
        <div class="progress-steps">
            <?php foreach ($steps as $step_num => $step_info): ?>
                <?php
                $is_active = ($step_num == $current_step);
                $is_completed = ($step_num < $current_step);
                $is_accessible = ($step_num <= $current_step || eipsi_is_step_completed($step_num, $wizard_data));
                
                $step_class = 'progress-step';
                if ($is_active) $step_class .= ' active';
                if ($is_completed) $step_class .= ' completed';
                if (!$is_accessible) $step_class .= ' disabled';
                ?>
                <div class="<?php echo $step_class; ?>" data-step="<?php echo $step_num; ?>">
                    <div class="step-number"><?php echo $step_info['icon']; ?></div>
                    <div class="step-name">
                        <strong><?php echo esc_html($step_info['name']); ?></strong>
                        <small style="display: block; font-size: 0.7rem; opacity: 0.8;">
                            <?php echo esc_html($step_info['description']); ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?php echo ($current_step - 1) * 25; ?>%"></div>
        </div>
    </div>

    <!-- Step Content -->
    <div class="study-form">
        <?php
        // Load step-specific template
        $step_template = EIPSI_FORMS_PLUGIN_DIR . 'admin/templates/wizard-steps/step-' . $current_step . '-info.php';
        
        if (file_exists($step_template)) {
            include $step_template;
        } else {
            echo '<div class="alert alert-error">No se encontró la plantilla para este paso.</div>';
        }
        ?>
    </div>

    <!-- Navigation with clear actions -->
    <div class="study-navigation">
        <?php if ($current_step > 1): ?>
            <button type="button" class="button button-secondary" 
                    onclick="eipsiNavigateToStep(<?php echo $current_step - 1; ?>)">
                ← Anterior
            </button>
        <?php endif; ?>

        <?php if ($current_step < 5): ?>
            <button type="button" class="button button-primary" 
                    onclick="eipsiSaveCurrentStep(<?php echo $current_step; ?>)">
                Siguiente →
            </button>
        <?php else: ?>
            <button type="button" class="button button-primary" 
                    onclick="eipsiActivateStudy()">
                🎉 Activar Estudio
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Tooltip explanations -->
<div style="margin-top: 2rem; padding: 1rem; background: var(--clinical-highlight); border-radius: 8px;">
    <h4 style="margin: 0 0 0.5rem 0; color: var(--eipsi-primary);">💡 Consejos para tu estudio:</h4>
    <ul style="margin: 0; padding-left: 1.5rem; color: var(--eipsi-text);">
        <li>Usa nombres claros para tus tomas (ej: "Línea Base", "Seguimiento 1", "Final")</li>
        <li>Programa recordatorios para mejorar la tasa de respuesta</li>
        <li>Puedes agregar más participantes después de activar el estudio</li>
    </ul>
</div>

<script>
// Navigation functions with improved user feedback
function eipsiNavigateToStep(step) {
    // Show loading state
    document.querySelector('.study-navigation').style.opacity = '0.6';
    document.querySelector('.study-navigation').style.pointerEvents = 'none';
    
    // Save current step before navigating
    eipsiSaveCurrentStep(<?php echo $current_step; ?>, function() {
        window.location.href = '<?php echo admin_url('admin.php?page=eipsi-new-study&step='); ?>' + step;
    });
}

function eipsiSaveCurrentStep(step, callback) {
    const form = document.getElementById('eipsi-wizard-form');
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    // Add WordPress action parameter
    formData.append('action', 'eipsi_save_wizard_step');
    formData.append('current_step', step);
    formData.append('eipsi_wizard_nonce', '<?php echo wp_create_nonce('eipsi_wizard_action'); ?>');

    // Show loading feedback
    const navButtons = document.querySelectorAll('.study-navigation button');
    navButtons.forEach(btn => {
        btn.disabled = true;
        btn.textContent = 'Guardando...';
    });

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && callback) {
            callback();
        } else if (data.success) {
            // Navigate to next step automatically
            window.location.href = '<?php echo admin_url('admin.php?page=eipsi-new-study&step='); ?>' + (step + 1);
        } else {
            // Handle validation errors
            let errorMessage = 'Error desconocido';
            if (data.data) {
                if (Array.isArray(data.data)) {
                    errorMessage = data.data.join('\n');
                } else if (typeof data.data === 'string') {
                    errorMessage = data.data;
                } else if (data.data.message) {
                    errorMessage = data.data.message;
                }
            }
            
            // Show user-friendly error
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-error';
            errorAlert.style.marginTop = '1rem';
            errorAlert.innerHTML = '<strong>Error al guardar:</strong><br>' + errorMessage.replace(/\n/g, '<br>');
            
            const formContainer = document.querySelector('.study-form');
            if (formContainer) {
                formContainer.prepend(errorAlert);
            }
            
            // Scroll to error
            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, inténtalo de nuevo.');
    })
    .finally(() => {
        navButtons.forEach(btn => {
            btn.disabled = false;
            if (btn.textContent === 'Guardando...') {
                btn.textContent = btn.dataset.originalText || btn.textContent;
            }
        });
        
        document.querySelector('.study-navigation').style.opacity = '1';
        document.querySelector('.study-navigation').style.pointerEvents = 'auto';
    });
}

function eipsiActivateStudy() {
    if (!confirm('¿Estás seguro/a de que quieres activar este estudio?\n\nUna vez activado, la estructura será más difícil de modificar, pero podrás agregar participantes en cualquier momento.')) {
        return;
    }

    const form = document.getElementById('eipsi-wizard-form');
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    // Add WordPress action parameter
    formData.append('action', 'eipsi_activate_study');
    formData.append('current_step', 5);
    formData.append('eipsi_wizard_nonce', '<?php echo wp_create_nonce('eipsi_wizard_action'); ?>');

    // Add activation confirmation
    const confirmationCheckbox = document.getElementById('activation_confirmed');
    if (confirmationCheckbox && confirmationCheckbox.checked) {
        formData.append('activation_confirmed', '1');
    } else {
        alert('Debes confirmar la activación marcando la casilla.');
        return;
    }

    // Show loading state
    const activateBtn = document.querySelector('.button-primary');
    activateBtn.disabled = true;
    activateBtn.textContent = 'Activando estudio...';

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success feedback
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success';
            successAlert.style.marginTop = '1rem';
            successAlert.innerHTML = '<h3 style="margin: 0 0 0.5rem 0;">¡Estudio creado con éxito!</h3><p>Serás redirigido al panel de control...</p>';
            
            const formContainer = document.querySelector('.study-form');
            if (formContainer) {
                formContainer.innerHTML = '';
                formContainer.prepend(successAlert);
            }
            
            // Redirect after brief delay
            setTimeout(() => {
                window.location.href = data.data.redirect_url;
            }, 2000);
        } else {
            let errorMessage = 'Error desconocido';
            if (data.data) {
                if (Array.isArray(data.data)) {
                    errorMessage = data.data.join('\n');
                } else if (typeof data.data === 'string') {
                    errorMessage = data.data;
                } else if (data.data.message) {
                    errorMessage = data.data.message;
                }
            }
            alert('Error al activar el estudio:\n' + errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, inténtalo de nuevo.');
    })
    .finally(() => {
        activateBtn.disabled = false;
        activateBtn.textContent = '🎉 Activar Estudio';
    });
}

// Auto-save with visual feedback
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('eipsi-wizard-form');
    if (form) {
        let autoSaveTimeout;
        
        form.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                const formData = new FormData(form);
                formData.append('action', 'eipsi_auto_save_wizard_step');
                formData.append('current_step', <?php echo $current_step; ?>);
                formData.append('eipsi_wizard_nonce', '<?php echo wp_create_nonce('eipsi_wizard_action'); ?>');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show brief feedback
                        const feedback = document.createElement('div');
                        feedback.className = 'alert alert-info';
                        feedback.textContent = 'Cambios guardados automáticamente';
                        feedback.style.position = 'fixed';
                        feedback.style.bottom = '20px';
                        feedback.style.right = '20px';
                        feedback.style.zIndex = '9999';
                        feedback.style.padding = '0.5rem 1rem';
                        feedback.style.fontSize = '0.9rem';
                        
                        document.body.appendChild(feedback);
                        
                        setTimeout(() => {
                            feedback.remove();
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.log('Auto-guardado fallido:', error);
                });
            }, 3000); // 3 seconds
        });
    }
});

// Store original button text for restoration
window.addEventListener('load', function() {
    const buttons = document.querySelectorAll('.study-navigation button');
    buttons.forEach(btn => {
        btn.dataset.originalText = btn.textContent;
    });
});
</script>

<style>
/* Ensure the new CSS is loaded */
@import url('<?php echo plugins_url('assets/css/longitudinal-studies-ui.css', EIPSI_FORMS_PLUGIN_FILE); ?>');
</style>