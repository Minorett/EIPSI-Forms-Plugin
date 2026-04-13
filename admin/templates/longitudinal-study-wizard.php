<?php
/**
 * Longitudinal Study Wizard Template - EIPSI Redesign
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

// Step names - SIN emojis
$steps = array(
    1 => 'Información',
    2 => 'Waves',
    3 => 'Programación',
    4 => 'Participantes',
    5 => 'Confirmar'
);

?>

<div class="wrap eipsi-longitudinal-study">
    <!-- Header EIPSI -->
    <div style="margin-bottom:24px;">
        <h1 style="font-size:20px;font-weight:600;color:#2c3e50;margin:0;">Estudio Longitudinal</h1>
        <p style="font-size:13px;color:#64748b;margin:6px 0 0 0;">Crear nuevo estudio</p>
    </div>

    <!-- Error and success messages -->
    <?php if (!empty($errors)): ?>
        <div style="background:#fee2e2;border:1px solid #dc2626;border-radius:8px;padding:12px 16px;margin-bottom:20px;">
            <strong style="color:#dc2626;font-size:13px;">Por favor, corrige los siguientes errores:</strong>
            <ul style="margin:8px 0 0 16px;padding:0;color:#7f1d1d;font-size:13px;">
                <?php foreach ($errors as $error): ?>
                    <li style="margin-bottom:4px;"><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div style="background:#d1fae5;border:1px solid #059669;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#065f46;font-size:13px;">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- EIPSI Progress Bar -->
    <div class="eipsi-wiz-bar">
        <?php foreach ($steps as $step_num => $step_name): ?>
            <?php
            $is_active = ($step_num == $current_step);
            $is_completed = ($step_num < $current_step);
            $is_last = ($step_num == 5);
            
            $dot_class = $is_active ? 'active' : ($is_completed ? 'done' : 'disabled');
            $lbl_class = $dot_class;
            $connector_class = $is_completed ? 'done' : '';
            ?>
            <div class="eipsi-wiz-item">
                <!-- Checkmark SVG para completados -->
                <span class="eipsi-wiz-dot <?php echo $dot_class; ?>">
                    <?php if ($is_completed): ?>
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M3 8l3.5 3.5L13 5" stroke="#006666" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php else: ?>
                        <?php echo $step_num; ?>
                    <?php endif; ?>
                </span>
                
                <!-- Label -->
                <span class="eipsi-wiz-lbl <?php echo $lbl_class; ?>"><?php echo esc_html($step_name); ?></span>
            </div>
            
            <!-- Connector (excepto último) -->
            <?php if (!$is_last): ?>
                <div class="eipsi-wiz-connector <?php echo $connector_class; ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Step Content -->
    <div class="study-form" style="margin-top:20px;">
        <?php
        // Load step-specific template (ahora usa los -new.php)
        $step_template = EIPSI_FORMS_PLUGIN_DIR . 'admin/templates/wizard-steps/step-' . $current_step . '-info-new.php';
        
        if (file_exists($step_template)) {
            include $step_template;
        } else {
            // Fallback a template original si no existe el nuevo
            $step_template_old = EIPSI_FORMS_PLUGIN_DIR . 'admin/templates/wizard-steps/step-' . $current_step . '-info.php';
            if (file_exists($step_template_old)) {
                include $step_template_old;
            } else {
                echo '<div style="background:#fee2e2;border:1px solid #dc2626;border-radius:8px;padding:12px 16px;color:#7f1d1d;font-size:13px;">No se encontró la plantilla para este paso.</div>';
            }
        }
        ?>
    </div>

    <!-- Navigation EIPSI -->
    <div class="eipsi-wiz-nav">
        <?php if ($current_step > 1): ?>
            <button type="button" 
                    class="eipsi-wiz-btn-secondary"
                    onclick="eipsiNavigateToStep(<?php echo $current_step - 1; ?>)">
                Anterior
            </button>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <?php if ($current_step < 5): ?>
            <button type="button" 
                    class="eipsi-wiz-btn-primary"
                    onclick="eipsiSaveCurrentStep(<?php echo $current_step; ?>)">
                Siguiente
            </button>
        <?php else: ?>
            <button type="button" 
                    id="eipsi-activate-btn"
                    class="eipsi-wiz-btn-primary"
                    onclick="eipsiActivateStudy()" 
                    disabled>
                Activar Estudio
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
// Navigation functions - MANTENER TAL CUAL
function eipsiNavigateToStep(step) {
    document.querySelector('.eipsi-wiz-nav').style.opacity = '0.6';
    document.querySelector('.eipsi-wiz-nav').style.pointerEvents = 'none';
    
    eipsiSaveCurrentStep(<?php echo $current_step; ?>, function() {
        window.location.href = '<?php echo admin_url('admin.php?page=eipsi-longitudinal-study&tab=create-study&step='); ?>' + step;
    });
}

function eipsiSaveCurrentStep(step, callback) {
    const form = document.getElementById('eipsi-wizard-form');
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'eipsi_save_wizard_step');
    formData.append('current_step', step);
    formData.append('eipsi_wizard_nonce', '<?php echo wp_create_nonce('eipsi_wizard_action'); ?>');

    const navButtons = document.querySelectorAll('.eipsi-wiz-nav button');
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
            window.location.href = '<?php echo admin_url('admin.php?page=eipsi-longitudinal-study&tab=create-study&step='); ?>' + (step + 1);
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
            
            const errorAlert = document.createElement('div');
            errorAlert.style.cssText = 'background:#fee2e2;border:1px solid #dc2626;border-radius:8px;padding:12px 16px;margin-top:16px;color:#7f1d1d;font-size:13px;';
            errorAlert.innerHTML = '<strong>Error al guardar:</strong><br>' + errorMessage.replace(/\n/g, '<br>');
            
            const formContainer = document.querySelector('.study-form');
            if (formContainer) {
                formContainer.prepend(errorAlert);
            }
            
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
        
        document.querySelector('.eipsi-wiz-nav').style.opacity = '1';
        document.querySelector('.eipsi-wiz-nav').style.pointerEvents = 'auto';
        
        // Autosave hint inline
        const hint = document.getElementById('eipsi-autosave-hint');
        if (hint) {
            hint.textContent = 'Borrador guardado';
            hint.style.color = '#008080';
            setTimeout(() => {
                hint.textContent = '';
                hint.style.color = '#94a3b8';
            }, 2500);
        }
    });
}

function eipsiActivateStudy() {
    const confirmationCheckbox = document.getElementById('activation_confirmed');
    if (!confirmationCheckbox || !confirmationCheckbox.checked) {
        const hint = document.getElementById('eipsi-autosave-hint');
        if (hint) {
            hint.textContent = 'Marcá la casilla de confirmación para continuar.';
            hint.style.color = '#dc2626';
        }
        return;
    }

    const form = document.getElementById('eipsi-wizard-form');
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'eipsi_activate_study');
    formData.append('current_step', 5);
    formData.append('eipsi_wizard_nonce', '<?php echo wp_create_nonce('eipsi_wizard_action'); ?>');
    formData.append('activation_confirmed', '1');

    const activateBtn = document.getElementById('eipsi-activate-btn');
    activateBtn.disabled = true;
    activateBtn.textContent = 'Activando estudio...';

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const successAlert = document.createElement('div');
            successAlert.style.cssText = 'background:#d1fae5;border:1px solid #059669;border-radius:8px;padding:16px;margin-top:16px;color:#065f46;font-size:14px;';
            successAlert.innerHTML = '<h3 style="margin:0 0 8px 0;">¡Estudio creado con éxito!</h3><p style="margin:0;">Serás redirigido al panel de control...</p>';
            
            const formContainer = document.querySelector('.study-form');
            if (formContainer) {
                formContainer.innerHTML = '';
                formContainer.prepend(successAlert);
            }
            
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
            
            const errorAlert = document.createElement('div');
            errorAlert.style.cssText = 'background:#fee2e2;border:1px solid #dc2626;border-radius:8px;padding:12px 16px;margin-top:16px;color:#7f1d1d;font-size:13px;';
            errorAlert.innerHTML = '<strong>Error al activar:</strong><br>' + errorMessage.replace(/\n/g, '<br>');
            
            const formContainer = document.querySelector('.study-form');
            if (formContainer) {
                formContainer.prepend(errorAlert);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, inténtalo de nuevo.');
    });
}

// Listener para checkbox de confirmación en step 5
document.addEventListener('DOMContentLoaded', function() {
    const confirmCb = document.getElementById('activation_confirmed');
    const activateBtn = document.getElementById('eipsi-activate-btn');
    if (confirmCb && activateBtn) {
        const toggle = () => {
            activateBtn.disabled = !confirmCb.checked;
            activateBtn.style.opacity = confirmCb.checked ? '1' : '0.4';
            activateBtn.style.cursor = confirmCb.checked ? 'pointer' : 'default';
        };
        toggle();
        confirmCb.addEventListener('change', toggle);
    }
});
</script>
