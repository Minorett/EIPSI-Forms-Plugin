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

$participant_id = $participant_id ?? '';
$study_id = $survey_id ?? '';
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

.eipsi-modal-section:last-child {
    margin-bottom: 0;
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

/* Options list */
.eipsi-withdrawal-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 20px 0;
}

.eipsi-withdrawal-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #ffffff;
}

.eipsi-withdrawal-option:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}

.eipsi-withdrawal-option.selected {
    border-color: #dc2626;
    background: #fef2f2;
}

.eipsi-option-radio {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 2px;
    position: relative;
}

.eipsi-withdrawal-option.selected .eipsi-option-radio {
    border-color: #dc2626;
    background: #dc2626;
}

.eipsi-option-radio::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.eipsi-withdrawal-option.selected .eipsi-option-radio::after {
    opacity: 1;
}

.eipsi-option-content {
    flex: 1;
}

.eipsi-option-title {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 4px 0;
}

.eipsi-option-desc {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
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
    
    .eipsi-withdrawal-option {
        background: #1f2937;
        border-color: #4b5563;
    }
    
    .eipsi-withdrawal-option:hover {
        background: #374151;
        border-color: #6b7280;
    }
    
    .eipsi-option-title {
        color: #f9fafb;
    }
    
    .eipsi-option-desc {
        color: #9ca3af;
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
     MODAL 1: Consentimiento de Retención de Datos (GATE)
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-modal" data-modal-type="withdrawal">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon warning">⚠️</div>
            <h2 class="eipsi-modal-title"><?php _e('¿Estás seguro?', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle"><?php _e('Estás a punto de salir del estudio. Tu decisión tiene consecuencias importantes.', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-modal-info-box info">
                <p class="eipsi-modal-text">
                    <?php _e('Todos los datos que ya proporcionaste son valiosos para la investigación. Antes de continuar, necesitamos confirmar tu comprensión sobre qué sucederá con tus datos.', 'eipsi-forms'); ?>
                </p>
            </div>
            
            <!-- GATE DE CHECKBOX - Según roadmap v2.5 -->
            <div class="eipsi-checkbox-gate" style="margin: 24px 0; padding: 20px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px;">
                <label class="eipsi-checkbox-label" style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                    <input type="checkbox" 
                           id="eipsi-retention-consent" 
                           style="width: 20px; height: 20px; margin-top: 2px; flex-shrink: 0; accent-color: #3b6caa;">
                    <span style="font-size: 14px; line-height: 1.5; color: #374151;">
                        <strong><?php _e('Acepto que mis datos se conserven para la investigación', 'eipsi-forms'); ?></strong><br>
                        <span style="color: #6b7280; font-size: 13px;">
                            <?php _e('Doy mi consentimiento para que mis respuestas anteriores permanezcan en el estudio y formen parte del análisis. Entiendo que no podrán ser eliminadas posteriormente.', 'eipsi-forms'); ?>
                        </span>
                    </span>
                </label>
            </div>
            
            <div class="eipsi-modal-info-box warning" style="margin-top: 16px;">
                <p class="eipsi-modal-text" style="font-size: 13px;">
                    <strong><?php _e('Nota importante:', 'eipsi-forms'); ?></strong> 
                    <?php _e('Si NO tildás esta casilla, se interpretará que querés eliminar todos tus datos del estudio (incluyendo respuestas ya enviadas). Esta acción es irreversible.', 'eipsi-forms'); ?>
                </p>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" data-close-modal>
                <?php _e('Cancelar', 'eipsi-forms'); ?>
            </button>
            <!-- Botón SIEMPRE habilitado según roadmap - detecta estado del checkbox -->
            <button type="button" class="eipsi-modal-btn danger" id="eipsi-confirm-withdrawal-gate">
                <?php _e('Confirmar abandono', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL B1: Confirmación Abandono Estándar
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-b1-modal" data-modal-type="withdrawal-b1">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon warning">🚪</div>
            <h2 class="eipsi-modal-title"><?php _e('Confirmar abandono del estudio', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle"><?php _e('Estás a punto de salir del estudio. Verificá que entendés las consecuencias:', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-modal-section">
                <p class="eipsi-modal-label"><?php _e('Lo que va a pasar:', 'eipsi-forms'); ?></p>
                <ul style="margin: 0; padding-left: 20px; color: #4b5563; font-size: 14px; line-height: 1.8;">
                    <li><?php _e('No te contactarán para futuras olas del estudio', 'eipsi-forms'); ?></li>
                    <li><?php _e('Tus respuestas ya enviadas se conservan en el análisis', 'eipsi-forms'); ?></li>
                    <li><?php _e('Perderás acceso al dashboard del participante', 'eipsi-forms'); ?></li>
                    <li><?php _e('No podés volver a entrar al estudio', 'eipsi-forms'); ?></li>
                </ul>
            </div>
            
            <div class="eipsi-modal-info-box">
                <p class="eipsi-modal-text">
                    <?php _e('Tus respuestas hasta ahora son valiosas para la investigación. Gracias por tu contribución.', 'eipsi-forms'); ?>
                </p>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" data-close-modal>
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

<!-- ============================================
     MODAL B2: Eliminación de Datos (Alta Fricción)
     ============================================ -->
<div class="eipsi-modal-overlay" id="eipsi-withdrawal-b2-modal" data-modal-type="withdrawal-b2">
    <div class="eipsi-modal">
        <div class="eipsi-modal-header">
            <div class="eipsi-modal-icon danger">🗑️</div>
            <h2 class="eipsi-modal-title"><?php _e('Eliminar mis datos del estudio', 'eipsi-forms'); ?></h2>
            <p class="eipsi-modal-subtitle" style="color: #dc2626; font-weight: 600;"><?php _e('⚠️ Esta acción NO se puede deshacer', 'eipsi-forms'); ?></p>
        </div>
        
        <div class="eipsi-modal-body">
            <div class="eipsi-modal-info-box danger">
                <p class="eipsi-modal-text" style="color: #7f1d1d;">
                    <?php _e('Estás solicitando la eliminación completa de tus datos. Esto es diferente de simplemente abandonar:', 'eipsi-forms'); ?>
                </p>
            </div>
            
            <div class="eipsi-modal-section">
                <p class="eipsi-modal-label" style="color: #dc2626;"><?php _e('Consecuencias de la eliminación:', 'eipsi-forms'); ?></p>
                <ul style="margin: 0; padding-left: 20px; color: #7f1d1d; font-size: 14px; line-height: 1.8;">
                    <li><strong><?php _e('Todas tus respuestas serán eliminadas permanentemente', 'eipsi-forms'); ?></strong></li>
                    <li><?php _e('Tus datos NO formarán parte del análisis final', 'eipsi-forms'); ?></li>
                    <li><?php _e('No podés recuperar tu participación ni tus respuestas', 'eipsi-forms'); ?></li>
                    <li><?php _e('El investigador será notificado de tu solicitud', 'eipsi-forms'); ?></li>
                </ul>
            </div>
            
            <div class="eipsi-verification-section">
                <p class="eipsi-verification-label"><?php _e('Para confirmar, escribí exactamente el siguiente texto:', 'eipsi-forms'); ?></p>
                <div class="eipsi-verification-text">NO QUIERO QUE MIS RESPUESTAS FORMEN PARTE DEL ANÁLISIS</div>
                <input type="text" 
                       class="eipsi-verification-input" 
                       id="eipsi-b2-verification-input"
                       placeholder="<?php esc_attr_e('Escribí el texto exacto aquí...', 'eipsi-forms'); ?>"
                       autocomplete="off">
                <p class="eipsi-verification-error" id="eipsi-b2-verification-error">
                    <?php _e('El texto no coincide. Por favor escribilo exactamente como se muestra arriba, respetando mayúsculas.', 'eipsi-forms'); ?>
                </p>
            </div>
        </div>
        
        <div class="eipsi-modal-footer">
            <button type="button" class="eipsi-modal-btn secondary" data-close-modal>
                <?php _e('Cancelar', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="eipsi-modal-btn danger" id="eipsi-confirm-b2" disabled
                    data-participant-id="<?php echo esc_attr($participant_id); ?>"
                    data-study-id="<?php echo esc_attr($study_id); ?>">
                <?php _e('Eliminar mis datos permanentemente', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Fase 3 - v2.5: JavaScript para Modales de Abandono
 */
(function() {
    'use strict';
    
    // Referencias a elementos
    const dropdownTrigger = document.getElementById('eipsi-withdraw-dropdown-trigger');
    const dropdownMenu = document.getElementById('eipsi-withdraw-dropdown-menu');
    const withdrawButton = document.getElementById('eipsi-withdraw-button');
    const mainModal = document.getElementById('eipsi-withdrawal-modal');
    const b1Modal = document.getElementById('eipsi-withdrawal-b1-modal');
    const b2Modal = document.getElementById('eipsi-withdrawal-b2-modal');
    const gateConfirmBtn = document.getElementById('eipsi-confirm-withdrawal-gate');
    const retentionCheckbox = document.getElementById('eipsi-retention-consent');
    const b2Input = document.getElementById('eipsi-b2-verification-input');
    const b2ConfirmBtn = document.getElementById('eipsi-confirm-b2');
    const b2Error = document.getElementById('eipsi-b2-verification-error');
    
    let dropdownOpen = false;
    
    // Texto exacto requerido para B2
    const B2_VERIFICATION_TEXT = 'NO QUIERO QUE MIS RESPUESTAS FORMEN PARTE DEL ANÁLISIS';
    
    // Funciones del dropdown
    function openDropdown() {
        if (dropdownMenu) {
            dropdownOpen = true;
            dropdownMenu.setAttribute('aria-hidden', 'false');
        }
    }
    
    function closeDropdown() {
        if (dropdownMenu) {
            dropdownOpen = false;
            dropdownMenu.setAttribute('aria-hidden', 'true');
        }
    }
    
    function toggleDropdown() {
        if (dropdownOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }
    
    // Click en trigger del dropdown
    if (withdrawButton) {
        withdrawButton.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });
    }
    
    // Click fuera del dropdown lo cierra
    document.addEventListener('click', function(e) {
        if (dropdownOpen && dropdownMenu && !dropdownMenu.contains(e.target) && !withdrawButton.contains(e.target)) {
            closeDropdown();
        }
    });
    
    // Abrir modal principal desde el dropdown
    if (withdrawButton && mainModal) {
        withdrawButton.addEventListener('click', function() {
            closeDropdown();
            openModal(mainModal);
        });
    }
    
    // Cerrar modales al hacer click en overlay o botón cancelar
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.eipsi-modal-overlay');
            closeModal(modal);
        });
    });
    
    // Cerrar al hacer click en el overlay (fuera del modal)
    document.querySelectorAll('.eipsi-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    // ============================================
    // GATE DE CHECKBOX - Flujo según roadmap v2.5
    // ============================================
    // Modal 1: Checkbox "Entiendo que mis datos se conservan"
    // Si checked → B1 (abandono estándar)
    // Si unchecked → B2 (eliminación de datos)
    
    if (gateConfirmBtn && mainModal) {
        gateConfirmBtn.addEventListener('click', function() {
            const isChecked = retentionCheckbox ? retentionCheckbox.checked : false;
            
            closeModal(mainModal);
            
            if (isChecked) {
                // Checkbox TILDADO → B1 (Abandono estándar, datos se conservan)
                if (b1Modal) {
                    setTimeout(() => openModal(b1Modal), 300);
                }
            } else {
                // Checkbox NO TILDADO → B2 (Eliminación de datos)
                if (b2Modal) {
                    setTimeout(() => openModal(b2Modal), 300);
                }
            }
        });
    }
    
    // Validación de texto B2 (alta fricción)
    if (b2Input && b2ConfirmBtn) {
        b2Input.addEventListener('input', function() {
            const inputValue = this.value.trim().toUpperCase();
            const isValid = inputValue === B2_VERIFICATION_TEXT;
            
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
    
    // Confirmar B1 (abandono estándar)
    const b1ConfirmBtn = document.getElementById('eipsi-confirm-b1');
    if (b1ConfirmBtn) {
        b1ConfirmBtn.addEventListener('click', function() {
            const participantId = this.dataset.participantId;
            const studyId = this.dataset.studyId;
            
            executeWithdrawal('b1', participantId, studyId);
        });
    }
    
    // Confirmar B2 (eliminación de datos)
    if (b2ConfirmBtn) {
        b2ConfirmBtn.addEventListener('click', function() {
            const inputValue = b2Input ? b2Input.value.trim().toUpperCase() : '';
            
            if (inputValue !== B2_VERIFICATION_TEXT) {
                if (b2Error) b2Error.classList.add('visible');
                if (b2Input) b2Input.classList.add('error');
                return;
            }
            
            const participantId = this.dataset.participantId;
            const studyId = this.dataset.studyId;
            
            executeWithdrawal('b2', participantId, studyId);
        });
    }
    
    // Funciones auxiliares
    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus trap para accesibilidad
        const focusableElements = modal.querySelectorAll('button, input, [tabindex]:not([tabindex="-1"])');
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }
    
    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function executeWithdrawal(type, participantId, studyId) {
        // Mostrar estado de carga
        const confirmBtn = type === 'b1' ? b1ConfirmBtn : b2ConfirmBtn;
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = '<?php echo esc_js(__('Procesando...', 'eipsi-forms')); ?>';
        }
        
        // Llamada AJAX
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'eipsi_abandon_study',
                nonce: '<?php echo wp_create_nonce('eipsi_abandon_study'); ?>',
                participant_id: participantId,
                study_id: studyId,
                withdrawal_type: type,
                verification_text: type === 'b2' ? (b2Input ? b2Input.value.trim() : '') : ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirigir a pantalla de confirmación
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
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo esc_js(__('Error de conexión. Intentá de nuevo.', 'eipsi-forms')); ?>');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = type === 'b1' 
                    ? '<?php echo esc_js(__('Sí, quiero abandonar', 'eipsi-forms')); ?>'
                    : '<?php echo esc_js(__('Eliminar mis datos permanentemente', 'eipsi-forms')); ?>';
            }
        });
    }
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.eipsi-modal-overlay.active').forEach(modal => {
                closeModal(modal);
            });
        }
    });
})();
</script>
