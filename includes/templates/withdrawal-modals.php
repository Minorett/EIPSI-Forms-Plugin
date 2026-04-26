<?php
/**
 * Fase 3 - v2.5: Modales de Abandono del Estudio
 * 
 * B1: Abandono estándar - salir del estudio pero conservar datos
 * B2: Eliminación de datos - alta fricción con verificación textual
 * 
 * @since 2.5.0
 * @package EIPSI_Forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$participant_id = $participant_id ?? $current_participant_id ?? '';
$study_id = $survey_id ?? $actual_study_id ?? (isset($study) ? ($study->id ?? '') : '');

// Simplified verification text
$required_verification_text = 'QUIERO QUE ELIMINEN MIS DATOS';
?>

<style>
/* ============================================
   FASE 3 - v2.5: Modales de Abandono
   ============================================ */

/* Overlay fondo */
.eipsi-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 10000;
    justify-content: center;
    align-items: center;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.eipsi-modal-overlay.active {
    display: flex;
    opacity: 1;
}

/* Contenedor modal */
.eipsi-modal {
    background: #ffffff;
    border-radius: 16px;
    max-width: 480px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: scale(0.95);
    transition: transform 0.3s ease;
}

.eipsi-modal-overlay.active .eipsi-modal {
    transform: scale(1);
}

/* Header modal */
.eipsi-modal-header {
    padding: 24px 24px 16px;
    border-bottom: 1px solid #e5e7eb;
}

.eipsi-modal-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 16px;
}

.eipsi-modal-icon.warning {
    background: #fef3c7;
}

.eipsi-modal-icon.danger {
    background: #fee2e2;
}

.eipsi-modal-icon.info {
    background: #e0f2fe;
}

.eipsi-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px 0;
}

.eipsi-modal-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

/* Body modal */
.eipsi-modal-body {
    padding: 24px;
}

.eipsi-modal-section {
    margin-bottom: 20px;
}

.eipsi-modal-label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.eipsi-modal-text {
    font-size: 14px;
    line-height: 1.6;
    color: #4b5563;
    margin: 0;
}

/* Info box */
.eipsi-modal-info-box {
    background: #f3f4f6;
    border-radius: 8px;
    padding: 16px;
    margin: 16px 0;
}

.eipsi-modal-info-box.warning {
    background: #fffbeb;
    border: 1px solid #fcd34d;
}

.eipsi-modal-info-box.danger {
    background: #fef2f2;
    border: 1px solid #fca5a5;
}

.eipsi-modal-info-box.info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
}

/* High friction text verification (B2) */
.eipsi-verification-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.eipsi-verification-label {
    font-size: 13px;
    font-weight: 600;
    color: #dc2626;
    margin-bottom: 8px;
}

.eipsi-verification-text {
    font-family: monospace;
    font-size: 13px;
    background: #fef2f2;
    padding: 8px 12px;
    border-radius: 6px;
    color: #991b1b;
    margin-bottom: 12px;
    border: 1px dashed #fca5a5;
    word-break: break-all;
}

.eipsi-verification-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.eipsi-verification-input:focus {
    outline: none;
    border-color: #dc2626;
}

.eipsi-verification-input.error {
    border-color: #dc2626;
    background: #fef2f2;
}

.eipsi-verification-error {
    color: #dc2626;
    font-size: 12px;
    margin-top: 6px;
    display: none;
}

.eipsi-verification-error.visible {
    display: block;
}

/* Footer modal */
.eipsi-modal-footer {
    padding: 16px 24px 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Botones */
.eipsi-modal-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.eipsi-modal-btn.secondary {
    background: #f3f4f6;
    color: #374151;
}

.eipsi-modal-btn.secondary:hover {
    background: #e5e7eb;
}

.eipsi-modal-btn.primary {
    background: #3b82f6;
    color: white;
}

.eipsi-modal-btn.primary:hover {
    background: #2563eb;
}

.eipsi-modal-btn.danger {
    background: #dc2626;
    color: white;
}

.eipsi-modal-btn.danger:hover {
    background: #b91c1c;
}

.eipsi-modal-btn.danger:disabled {
    background: #fca5a5;
    cursor: not-allowed;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .eipsi-modal {
        background: #1f2937;
    }
    
    .eipsi-modal-header {
        border-color: #374151;
    }
    
    .eipsi-modal-title {
        color: #f9fafb;
    }
    
    .eipsi-modal-subtitle {
        color: #9ca3af;
    }
    
    .eipsi-modal-text {
        color: #d1d5db;
    }
    
    .eipsi-modal-label {
        color: #e5e7eb;
    }
    
    .eipsi-modal-info-box {
        background: #374151;
    }

    .eipsi-modal-info-box.info {
        background: #1e3a8a;
        border-color: #1e40af;
    }
    
    .eipsi-modal-btn.secondary {
        background: #374151;
        color: #e5e7eb;
    }
    
    .eipsi-modal-btn.secondary:hover {
        background: #4b5563;
    }
    
    .eipsi-verification-input {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .eipsi-verification-input:focus {
        border-color: #f87171;
    }
}

/* Responsive */
@media (max-width: 640px) {
    .eipsi-modal-overlay {
        padding: 16px;
        align-items: flex-end;
    }
    
    .eipsi-modal {
        max-height: 85vh;
        border-radius: 16px 16px 0 0;
    }
    
    .eipsi-modal-overlay.active .eipsi-modal {
        animation: slideUp 0.3s ease;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }
    
    .eipsi-modal-footer {
        flex-direction: column-reverse;
    }
    
    .eipsi-modal-btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<!-- ============================================
     MODAL 1: Advertencia General de Confirmación
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-step-1">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon warning">⚠️</div>
            <h2 class="eipsi-modal-title"><?php _e('¿Estás seguro?', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle"><?php _e('Estás a punto de iniciar el proceso para salir del estudio.', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-modal-text">
                <?php _e('Tu participación es fundamental para esta investigación. Antes de continuar, queremos asegurarnos de que esta es tu decisión final.', 'eipsi-forms'); ?>
            </div>
            <div class="eipsi-modal-info-box info">
                <?php _e('Si decides continuar, te pediremos que elijas qué sucederá con los datos que ya has proporcionado.', 'eipsi-forms'); ?>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" data-close-modal>
                <?php _e('Cancelar', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="eipsi-modal-btn primary" id="eipsi-btn-step-1-next">
                <?php _e('Siguiente', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL 2: Consentimiento de Retención (GATE)
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-step-2">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon info">📋</div>
            <h2 class="eipsi-modal-title"><?php _e('Gestión de tus datos', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle"><?php _e('Decidí qué sucederá con la información que ya compartiste.', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-checkbox-gate" style="margin: 0; padding: 20px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px;">
                <label class="eipsi-checkbox-label" style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                    <input type="checkbox" 
                           id="eipsi-retention-consent" 
                           checked
                           style="width: 20px; height: 20px; margin-top: 2px; flex-shrink: 0; accent-color: #3b6caa;">
                    <span style="font-size: 14px; line-height: 1.5; color: #374151;">
                        <strong><?php _e('Acepto que mis datos se conserven para la investigación', 'eipsi-forms'); ?></strong><br>
                        <span style="color: #6b7280; font-size: 13px;">
                            <?php _e('Doy mi consentimiento para que mis respuestas anteriores permanezcan en el estudio y formen parte del análisis.', 'eipsi-forms'); ?>
                        </span>
                    </span>
                </label>
            </div>
            
            <div class="eipsi-modal-info-box warning" style="margin-top: 16px;">
                <p class="eipsi-modal-text" style="font-size: 13px;">
                    <strong><?php _e('Nota importante:', 'eipsi-forms'); ?></strong> 
                    <?php _e('Si desmarcas esta casilla, iniciaremos el proceso de eliminación total y permanente de tus datos del estudio.', 'eipsi-forms'); ?>
                </p>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" id="eipsi-btn-step-2-back">
                <?php _e('Volver', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="eipsi-modal-btn primary" id="eipsi-btn-step-2-next">
                <?php _e('Confirmar', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL 3: Advertencia de Acción Irreversible (B2 PATH)
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-step-3">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon danger">🛑</div>
            <h2 class="eipsi-modal-title"><?php _e('Acción Irreversible', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle" style="color: #dc2626; font-weight: 600;"><?php _e('Estás a punto de borrar todo tu historial', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-modal-text">
                <?php _e('Al proceder, todas tus respuestas serán eliminadas de nuestras bases de datos y no podrán ser recuperadas ni utilizadas en la investigación.', 'eipsi-forms'); ?>
            </div>
            <div class="eipsi-modal-info-box danger">
                <p class="eipsi-modal-text" style="color: #7f1d1d; font-weight: 600;">
                    <?php _e('Esta acción es permanente y final.', 'eipsi-forms'); ?>
                </p>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" id="eipsi-btn-step-3-back">
                <?php _e('Volver', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="eipsi-modal-btn danger" id="eipsi-btn-step-3-next">
                <?php _e('Siguiente', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL 4: Verificación de Alta Fricción (B2 PATH)
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-step-4">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon danger">🗑️</div>
            <h2 class="eipsi-modal-title"><?php _e('Confirmación de Identidad', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle"><?php _e('Para confirmar la eliminación, escribí el texto de seguridad.', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-verification-section" style="margin-top: 0; padding-top: 0; border-top: none;">
                <p class="eipsi-verification-label"><?php _e('Escribí exactamente el siguiente texto:', 'eipsi-forms'); ?></p>
                <div class="eipsi-verification-text" id="eipsi-verification-text-display"><?php echo esc_html($required_verification_text); ?></div>
                <input type="text" 
                       class="eipsi-verification-input" 
                       id="eipsi-b2-verification-input"
                       placeholder="<?php esc_attr_e('Escribí el texto de seguridad aquí...', 'eipsi-forms'); ?>"
                       autocomplete="off">
                <p class="eipsi-verification-error" id="eipsi-b2-verification-error">
                    <?php _e('El texto no coincide. Verificá mayúsculas.', 'eipsi-forms'); ?>
                </p>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" id="eipsi-btn-step-4-back">
                <?php _e('Volver', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="eipsi-modal-btn danger" id="eipsi-confirm-b2" disabled
                    data-participant-id="<?php echo esc_attr($participant_id); ?>"
                    data-study-id="<?php echo esc_attr($study_id); ?>">
                <?php _e('Eliminar mis datos permanentemente', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL B1 FINAL: Confirmación Abandono Estándar
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-b1-final">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon warning">🚪</div>
            <h2 class="eipsi-modal-title"><?php _e('Finalizar participación', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle"><?php _e('Tus datos se conservarán para la investigación.', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-modal-section">
                <p class="eipsi-modal-label"><?php _e('Resumen de tu salida:', 'eipsi-forms'); ?></p>
                <ul style="margin: 0; padding-left: 20px; color: #4b5563; font-size: 14px; line-height: 1.8;">
                    <li><?php _e('No recibirás más contactos de este estudio.', 'eipsi-forms'); ?></li>
                    <li><?php _e('Tus respuestas actuales se mantienen en el análisis.', 'eipsi-forms'); ?></li>
                    <li><?php _e('Esta acción es definitiva.', 'eipsi-forms'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" id="eipsi-btn-b1-back">
                <?php _e('Volver', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="eipsi-modal-btn danger" id="eipsi-confirm-b1" 
                    data-participant-id="<?php echo esc_attr($participant_id); ?>"
                    data-study-id="<?php echo esc_attr($study_id); ?>">
                <?php _e('Sí, quiero abandonar', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Fase 3 - v2.5: JavaScript para Circuito de Abandono
 */
(function() {
    'use strict';
    
    // Referencias a modales
    const modal1 = document.getElementById('eipsi-withdrawal-step-1');
    const modal2 = document.getElementById('eipsi-withdrawal-step-2');
    const modal3 = document.getElementById('eipsi-withdrawal-step-3');
    const modal4 = document.getElementById('eipsi-withdrawal-step-4');
    const modalB1 = document.getElementById('eipsi-withdrawal-b1-final');
    
    // Botones de navegación
    const btnStep1Next = document.getElementById('eipsi-btn-step-1-next');
    const btnStep2Back = document.getElementById('eipsi-btn-step-2-back');
    const btnStep2Next = document.getElementById('eipsi-btn-step-2-next');
    const btnStep3Back = document.getElementById('eipsi-btn-step-3-back');
    const btnStep3Next = document.getElementById('eipsi-btn-step-3-next');
    const btnStep4Back = document.getElementById('eipsi-btn-step-4-back');
    const btnB1Back = document.getElementById('eipsi-btn-b1-back');
    
    // Inputs y Confirmación
    const retentionCheckbox = document.getElementById('eipsi-retention-consent');
    const b2Input = document.getElementById('eipsi-b2-verification-input');
    const b2ConfirmBtn = document.getElementById('eipsi-confirm-b2');
    const b1ConfirmBtn = document.getElementById('eipsi-confirm-b1');
    const b2Error = document.getElementById('eipsi-b2-verification-error');
    
    // Texto exacto requerido
    const REQUIRED_TEXT = "<?php echo esc_js($required_verification_text); ?>";
    
    // Gear Icon Trigger
    const withdrawButton = document.getElementById('eipsi-withdraw-button');
    if (withdrawButton) {
        withdrawButton.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(modal1);
        });
    }
    
    // Navegación Paso 1 -> 2
    if (btnStep1Next) {
        btnStep1Next.addEventListener('click', () => {
            closeModal(modal1);
            setTimeout(() => openModal(modal2), 200);
        });
    }
    
    // Navegación Paso 2 -> 1 (Back)
    if (btnStep2Back) {
        btnStep2Back.addEventListener('click', () => {
            closeModal(modal2);
            setTimeout(() => openModal(modal1), 200);
        });
    }
    
    // Navegación Paso 2 -> (B1 o Paso 3)
    if (btnStep2Next) {
        btnStep2Next.addEventListener('click', () => {
            const isChecked = retentionCheckbox ? retentionCheckbox.checked : false;
            closeModal(modal2);
            if (isChecked) {
                setTimeout(() => openModal(modalB1), 200);
            } else {
                setTimeout(() => openModal(modal3), 200);
            }
        });
    }
    
    // Navegación Paso 3 -> 2 (Back)
    if (btnStep3Back) {
        btnStep3Back.addEventListener('click', () => {
            closeModal(modal3);
            setTimeout(() => openModal(modal2), 200);
        });
    }
    
    // Navegación Paso 3 -> 4
    if (btnStep3Next) {
        btnStep3Next.addEventListener('click', () => {
            closeModal(modal3);
            setTimeout(() => openModal(modal4), 200);
        });
    }
    
    // Navegación Paso 4 -> 3 (Back)
    if (btnStep4Back) {
        btnStep4Back.addEventListener('click', () => {
            closeModal(modal4);
            setTimeout(() => openModal(modal3), 200);
        });
    }
    
    // Navegación B1 -> 2 (Back)
    if (btnB1Back) {
        btnB1Back.addEventListener('click', () => {
            closeModal(modalB1);
            setTimeout(() => openModal(modal2), 200);
        });
    }
    
    // Validación de texto B2
    if (b2Input && b2ConfirmBtn) {
        b2Input.addEventListener('input', function() {
            const inputValue = this.value.trim().toUpperCase();
            const isValid = inputValue === REQUIRED_TEXT;
            
            b2ConfirmBtn.disabled = !isValid;
            
            if (inputValue.length > 0 && !isValid) {
                this.classList.add('error');
                if (b2Error) b2Error.classList.add('visible');
            } else {
                this.classList.remove('error');
                if (b2Error) b2Error.classList.remove('visible');
            }
        });
    }
    
    // Ejecución de abandono B1
    if (b1ConfirmBtn) {
        b1ConfirmBtn.addEventListener('click', function() {
            executeWithdrawal('b1', this.dataset.participantId, this.dataset.studyId);
        });
    }
    
    // Ejecución de abandono B2
    if (b2ConfirmBtn) {
        b2ConfirmBtn.addEventListener('click', function() {
            const inputValue = b2Input ? b2Input.value.trim().toUpperCase() : '';
            if (inputValue !== REQUIRED_TEXT) return;
            executeWithdrawal('b2', this.dataset.participantId, this.dataset.studyId);
        });
    }
    
    // Funciones auxiliares
    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        const focusable = modal.querySelectorAll('button, input');
        if (focusable.length > 0) focusable[0].focus();
    }
    
    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function executeWithdrawal(type, participantId, studyId) {
        const confirmBtn = type === 'b1' ? b1ConfirmBtn : b2ConfirmBtn;
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = '<?php echo esc_js(__('Procesando...', 'eipsi-forms')); ?>';
        }
        
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'eipsi_abandon_study',
                nonce: '<?php echo wp_create_nonce('eipsi_abandon_study'); ?>',
                participant_id: participantId,
                study_id: studyId,
                withdrawal_type: type,
                verification_text: type === 'b2' ? (b2Input ? b2Input.value.trim() : '') : ''
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.data.redirect_url || '/';
            } else {
                alert(data.data?.message || '<?php echo esc_js(__('Error al procesar la solicitud', 'eipsi-forms')); ?>');
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = type === 'b1' 
                        ? '<?php echo esc_js(__('Sí, quiero abandonar', 'eipsi-forms')); ?>'
                        : '<?php echo esc_js(__('Eliminar mis datos permanentemente', 'eipsi-forms')); ?>';
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('<?php echo esc_js(__('Error de conexión.', 'eipsi-forms')); ?>');
            if (confirmBtn) confirmBtn.disabled = false;
        });
    }
    
    // Cerrar con ESC o click fuera
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.eipsi-modal-overlay.active').forEach(m => closeModal(m));
        }
    });
    
    document.querySelectorAll('.eipsi-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this);
        });
    });
    
    // Soporte para atributo data-close-modal
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal(this.closest('.eipsi-modal-overlay'));
        });
    });
})();
</script>
