<?php
/**
 * Setup Wizard Template
 * 
 * Template principal que renderiza el wizard paso-a-paso
 * con progress bar y navegación.
 *
 * @package EIPSI_Forms
 * @since 1.5.1
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

?>
<div class="wrap eipsi-setup-wizard">
    <h1 class="wp-heading-inline">
        <?php echo $current_step === 5 ? '✅ Confirmar y Activar Estudio' : 'Crear Estudio Longitudinal'; ?>
    </h1>
    
    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <p><strong>Errores encontrados:</strong></p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Progress Bar -->
    <div class="eipsi-wizard-progress">
        <div class="progress-steps">
            <?php
            $steps = array(
                1 => 'Información Básica',
                2 => 'Config Tomas',
                3 => 'Timing',
                4 => 'Participantes',
                5 => 'Resumen'
            );
            
            foreach ($steps as $step_num => $step_name):
                $is_active = ($step_num == $current_step);
                $is_completed = ($step_num < $current_step);
                $is_accessible = ($step_num <= $current_step || eipsi_is_step_completed($step_num, $wizard_data));
                
                $step_class = 'progress-step';
                if ($is_active) $step_class .= ' active';
                if ($is_completed) $step_class .= ' completed';
                if (!$is_accessible) $step_class .= ' disabled';
            ?>
                <div class="<?php echo $step_class; ?>" data-step="<?php echo $step_num; ?>">
                    <span class="step-number"><?php echo $step_num; ?></span>
                    <span class="step-name"><?php echo esc_html($step_name); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?php echo ($current_step - 1) * 25; ?>%"></div>
        </div>
    </div>
    
    <!-- Step Content -->
    <div class="eipsi-wizard-content">
        <?php
        // Load step-specific template
        $step_template = EIPSI_FORMS_PLUGIN_DIR . 'admin/templates/wizard-steps/step-' . $current_step . '-info.php';
        
        if (file_exists($step_template)) {
            include $step_template;
        } else {
            echo '<div class="notice notice-error"><p>Template de paso no encontrado.</p></div>';
        }
        ?>
    </div>
    
    <!-- Navigation -->
    <div class="eipsi-wizard-navigation">
        <?php if ($current_step > 1): ?>
            <button type="button" class="button button-secondary" onclick="eipsiNavigateToStep(<?php echo $current_step - 1; ?>)">
                ← Anterior
            </button>
        <?php endif; ?>
        
        <?php if ($current_step < 5): ?>
            <button type="button" class="button button-primary" onclick="eipsiSaveCurrentStep(<?php echo $current_step; ?>)">
                Siguiente →
            </button>
        <?php else: ?>
            <button type="button" class="button button-primary" onclick="eipsiActivateStudy()">
                Activar Estudio
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
// Navigation functions
function eipsiNavigateToStep(step) {
    // Save current step before navigating
    eipsiSaveCurrentStep(<?php echo $current_step; ?>, function() {
        window.location.href = '<?php echo admin_url('admin.php?page=eipsi-new-study&step='); ?>' + step;
    });
}

function eipsiSaveCurrentStep(step, callback) {
    const form = document.getElementById('eipsi-wizard-form');
    const formData = new FormData(form);
    
    // Add WordPress action parameter (REQUIRED for admin-ajax.php)
    formData.append('action', 'eipsi_save_wizard_step');
    formData.append('current_step', step);
    formData.append('eipsi_wizard_nonce', '<?php echo wp_create_nonce('eipsi_wizard_action'); ?>');
    
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
            // Handle validation errors or generic errors
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
            alert('Error al guardar el paso:\n' + errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intenta nuevamente.');
    });
}

function eipsiActivateStudy() {
    if (!confirm('¿Estás seguro de que deseas activar este estudio? Una vez activado, será difícil cambiar la estructura.')) {
        return;
    }
    
    const form = document.getElementById('eipsi-wizard-form');
    const formData = new FormData(form);
    
    // Add WordPress action parameter (REQUIRED for admin-ajax.php)
    formData.append('action', 'eipsi_activate_study');
    formData.append('current_step', 5);
    formData.append('eipsi_wizard_nonce', '<?php echo wp_create_nonce('eipsi_wizard_action'); ?>');
    
    // Add activation confirmation
    const confirmationCheckbox = document.getElementById('activation_confirmed');
    if (confirmationCheckbox && confirmationCheckbox.checked) {
        formData.append('activation_confirmed', '1');
    } else {
        alert('Debes confirmar la activación marcando la casilla correspondiente.');
        return;
    }
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('¡Estudio creado exitosamente!');
            window.location.href = data.data.redirect_url;
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
        alert('Error de conexión. Por favor, intenta nuevamente.');
    });
}

// Auto-save every 5 seconds
let autoSaveInterval;

function startAutoSave() {
    autoSaveInterval = setInterval(function() {
        const form = document.getElementById('eipsi-wizard-form');
        if (form) {
            const formData = new FormData(form);
            // Add WordPress action parameter (REQUIRED for admin-ajax.php)
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
                    console.log('Auto-save completed');
                }
            })
            .catch(error => {
                console.log('Auto-save failed:', error);
            });
        }
    }, 5000); // 5 seconds
}

function stopAutoSave() {
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
    }
}

// Initialize auto-save when page loads
document.addEventListener('DOMContentLoaded', function() {
    startAutoSave();
});

// Stop auto-save before page unload
window.addEventListener('beforeunload', function() {
    stopAutoSave();
});
</script>