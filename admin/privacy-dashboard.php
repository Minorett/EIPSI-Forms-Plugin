<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_privacy_dashboard($form_id = null) {
    $current_form_id = $form_id ?: (isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '');
    
    // Obtener configuración actual
    require_once dirname(__FILE__) . '/privacy-config.php';
    $global_config = get_global_privacy_defaults();
    $privacy_config = get_privacy_config($current_form_id);
    
    ?>
    <div class="eipsi-privacy-dashboard">
        <h2>🔒 Configuración de Metadatos y Privacidad</h2>
        
        <!-- SECCIÓN A: CONFIGURACIÓN GLOBAL (SIEMPRE VISIBLE) -->
        <div class="eipsi-global-config">
            <h3>🌍 Configuración Global (por defecto para todos los formularios)</h3>
            <p style="color: #666; margin-bottom: 15px; font-size: 13px;">
                Estos valores se aplican a todos los formularios, salvo a aquellos que tengan una configuración específica en la sección 'Por formulario'.
            </p>
            
            <form id="eipsi-global-privacy-form" method="post">
                <?php wp_nonce_field('eipsi_global_privacy_nonce', 'eipsi_global_privacy_nonce'); ?>
                <input type="hidden" name="action" value="save_global_privacy_config">
                
                <!-- CAPTURA BÁSICA -->
                <div class="eipsi-toggle-group">
                    <h3>📋 Captura Básica</h3>
                    
                    <label>
                        <input type="checkbox" name="ip_address" <?php checked($global_config['ip_address']); ?>>
                        <strong>Capturar IP del dispositivo</strong>
                        <span class="eipsi-tooltip">(Auditoría clínica - GDPR/HIPAA - retención 90 días)</span>
                    </label>
                </div>
                
                <!-- FINGERPRINT LIVIANO DEL DISPOSITIVO -->
                <div class="eipsi-toggle-group">
                    <h3>🖥️ Fingerprint Liviano del Dispositivo</h3>
                    <p class="eipsi-section-description">⚠️ Estos datos son <strong>opcionales</strong> y están <strong>desactivados por defecto</strong>. Útiles para distinguir pacientes con IP compartida.</p>
                    
                    <label>
                        <input type="checkbox" name="browser" <?php checked($global_config['browser']); ?>>
                        <strong>Capturar navegador y sistema operativo</strong>
                        <span class="eipsi-tooltip">(ej: Chrome 131, Firefox 132, Windows 10)</span>
                    </label>
                    
                    <label>
                        <input type="checkbox" name="screen_width" <?php checked($global_config['screen_width']); ?>>
                        <strong>Capturar tamaño de pantalla</strong>
                        <span class="eipsi-tooltip">(ej: 1920x1080, 1080x2400)</span>
                    </label>
                </div>
                
                <!-- COMPORTAMIENTO CLÍNICO -->
                <div class="eipsi-toggle-group">
                    <h3>🎯 Comportamiento Clínico</h3>
                    
                    <label>
                        <input type="checkbox" name="therapeutic_engagement" <?php checked($global_config['therapeutic_engagement']); ?>>
                        <strong>Engagement Terapéutico</strong>
                        <span class="eipsi-tooltip">(Tiempo por campo, cambios, navegación)</span>
                    </label>
                    
                    <label>
                        <input type="checkbox" name="avoidance_patterns" <?php checked($global_config['avoidance_patterns']); ?>>
                        <strong>Patrones de Evitación</strong>
                        <span class="eipsi-tooltip">(Saltos, retrocesos, omisiones)</span>
                    </label>
                    
                    <label>
                        <input type="checkbox" name="device_type" <?php checked($global_config['device_type']); ?>>
                        <strong>Tipo de Dispositivo</strong>
                        <span class="eipsi-tooltip">(mobile/desktop/tablet)</span>
                    </label>
                    
                    <label>
                        <input type="checkbox" name="quality_flag" <?php checked($global_config['quality_flag']); ?>>
                        <strong>Quality Flag</strong>
                        <span class="eipsi-tooltip">(Control automático: HIGH/NORMAL/LOW)</span>
                    </label>
                </div>
                
                <button type="submit" class="button button-primary">💾 Guardar Configuración Global</button>
            </form>
        </div>
        
        <!-- SEPARADOR -->
        <hr style="margin: 30px 0; border: none; height: 1px; background: #e2e8f0;">
        
        <!-- SECCIÓN B: CONFIGURACIÓN POR FORMULARIO (OVERRIDE) -->
        <div class="eipsi-per-form-config">
            <h3>🎯 Configuración por Formulario (override)</h3>
            
            <?php if ($current_form_id): ?>
                <p><strong>Formulario:</strong> <code><?php echo esc_html($current_form_id); ?></code></p>
                
                <form id="eipsi-privacy-form" method="post">
                    <?php wp_nonce_field('eipsi_privacy_nonce', 'eipsi_privacy_nonce'); ?>
                    <input type="hidden" name="action" value="save_privacy_config">
                    <input type="hidden" name="form_id" value="<?php echo esc_attr($current_form_id); ?>">
            
            <!-- SEGURIDAD BÁSICA (OBLIGATORIO) -->
            <div class="eipsi-toggle-group">
                <h3>🔐 Seguridad Básica</h3>
                <label>
                    <input type="checkbox" checked disabled> 
                    <strong>Form ID</strong>
                    <span class="eipsi-tooltip">(Identificador único: ACA-a3f1b2)</span>
                </label>
                
                <label>
                    <input type="checkbox" checked disabled> 
                    <strong>Participant ID</strong>
                    <span class="eipsi-tooltip">(ID anónimo: p-a1b2c3d4e5f6)</span>
                </label>
            </div>
            
            <!-- COMPORTAMIENTO CLÍNICO (RECOMENDADO) -->
            <div class="eipsi-toggle-group">
                <h3>🎯 Comportamiento Clínico <span class="eipsi-recommended">(Recomendado)</span></h3>
                
                <label>
                    <input type="checkbox" name="therapeutic_engagement" <?php checked($privacy_config['therapeutic_engagement'] ?? true); ?>>
                    <strong>Therapeutic Engagement</strong>
                    <span class="eipsi-tooltip">(Tiempo por campo, cambios, navegación)</span>
                </label>
                
                <label>
                    <input type="checkbox" name="avoidance_patterns" <?php checked($privacy_config['avoidance_patterns'] ?? true); ?>>
                    <strong>Avoidance Patterns</strong>
                    <span class="eipsi-tooltip">(Saltos, retrocesos, omisiones)</span>
                </label>
            </div>
            
            <!-- TRAZABILIDAD -->
            <div class="eipsi-toggle-group">
                <h3>📋 Trazabilidad</h3>
                
                <label>
                    <input type="checkbox" name="device_type" <?php checked($privacy_config['device_type'] ?? true); ?>>
                    <strong>Device Type</strong>
                    <span class="eipsi-tooltip">(mobile/desktop/tablet)</span>
                </label>
                
                <label>
                    <input type="checkbox" name="ip_address" <?php checked($privacy_config['ip_address'] ?? true); ?>>
                    <strong>IP Address</strong>
                    <span class="eipsi-tooltip">(Auditoría clínica - GDPR/HIPAA - retención 90 días)</span>
                </label>
                
                <label>
                    <input type="checkbox" name="quality_flag" <?php checked($privacy_config['quality_flag'] ?? true); ?>>
                    <strong>Quality Flag</strong>
                    <span class="eipsi-tooltip">(Control automático: HIGH/NORMAL/LOW)</span>
                </label>
            </div>
            
            <!-- DISPOSITIVO (OPCIONAL - OFF por defecto) -->
            <div class="eipsi-toggle-group">
                <h3>🖥️ Fingerprint Liviano del Dispositivo <span class="eipsi-optional">(Opcional)</span></h3>
                <p class="eipsi-section-description">⚠️ Estos datos son <strong>opcionales</strong> y están <strong>desactivados por defecto</strong>. Actívalos si necesitas distinguir pacientes con IP compartida (ej. wifi de clínica).</p>
                
                <label>
                    <input type="checkbox" name="browser" <?php checked($privacy_config['browser'] ?? false); ?>>
                    <strong>Navegador</strong>
                    <span class="eipsi-tooltip">(ej: Chrome 131, Firefox 132, Safari 17)</span>
                </label>
                
                <label>
                    <input type="checkbox" name="os" <?php checked($privacy_config['os'] ?? false); ?>>
                    <strong>Sistema Operativo</strong>
                    <span class="eipsi-tooltip">(ej: Windows 10, Android 15, iOS 18)</span>
                </label>
                
                <label>
                    <input type="checkbox" name="screen_width" <?php checked($privacy_config['screen_width'] ?? false); ?>>
                    <strong>Tamaño de Pantalla</strong>
                    <span class="eipsi-tooltip">(ej: 1920x1080, 1080x2400)</span>
                </label>
            </div>
            
            <button type="submit" class="button button-primary">💾 Guardar Configuración para este Formulario</button>
        </form>
    <?php else: ?>
        <div class="notice notice-info">
            <p>👆 <strong>Selecciona un formulario arriba para sobrescribir la configuración global solo para ese formulario.</strong></p>
            <p>Mientras tanto, puedes configurar los valores globales que se aplicarán por defecto a todos los formularios.</p>
        </div>
    <?php endif; ?>
        </div>
        
        <!-- INFO BOX -->
        <div class="eipsi-info-box">
            <p><strong>ℹ️ Información de Privacidad:</strong></p>
            <ul>
                <li>✅ <strong>Datos clínicos:</strong> Siempre capturados (therapeutic engagement y avoidance patterns)</li>
                <li>✅ <strong>IP Address:</strong> Por defecto ON - Auditoría clínica (GDPR/HIPAA compliant)</li>
                <li>⚠️ <strong>Dispositivo (navegador/OS/pantalla):</strong> Por defecto OFF - Solo para debugging</li>
                <li>🔄 <strong>Retención de IP:</strong> 90 días (configurable)</li>
                <li>📊 <strong>Todos los datos:</strong> Incluidos en exportación Excel/CSV</li>
            </ul>
        </div>
    </div>
    
    <style>
        .eipsi-privacy-dashboard {
            max-width: 700px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .eipsi-toggle-group {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #005a87;
            border-radius: 4px;
        }
        
        .eipsi-toggle-group h3 {
            margin-top: 0;
            color: #005a87;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .eipsi-section-description {
            margin: 10px 0;
            padding: 8px;
            background: #fff3cd;
            border-left: 3px solid #ffc107;
            color: #856404;
            font-size: 12px;
            border-radius: 3px;
        }
        
        .eipsi-toggle-group label {
            display: block;
            margin: 10px 0;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .eipsi-toggle-group label:hover {
            background: rgba(0, 90, 135, 0.05);
        }
        
        .eipsi-toggle-group input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .eipsi-toggle-group input[type="checkbox"]:disabled {
            cursor: not-allowed;
            opacity: 0.8;
        }
        
        .eipsi-tooltip {
            font-size: 11px;
            color: #64748b;
            margin-left: 8px;
            font-style: italic;
        }
        
        .eipsi-optional {
            color: #f39c12;
            font-size: 0.8em;
            margin-left: 6px;
            font-weight: 600;
        }
        
        .eipsi-recommended {
            color: #005a87;
            font-size: 0.8em;
            margin-left: 6px;
            font-weight: 600;
        }
        
        .eipsi-info-box {
            margin-top: 20px;
            padding: 12px;
            background: #e3f2fd;
            border-left: 4px solid #005a87;
            border-radius: 4px;
            color: #005a87;
            font-size: 12px;
        }
        
        .eipsi-info-box ul {
            margin: 8px 0;
            padding-left: 20px;
        }
        
        .eipsi-info-box li {
            margin: 6px 0;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Manejar envío del formulario global
        $('#eipsi-global-privacy-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var $submitButton = $(this).find('button[type="submit"]');
            var originalText = $submitButton.text();
            
            $submitButton.prop('disabled', true).text('💾 Guardando...');
            
            $('.eipsi-message').remove();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=eipsi_save_global_privacy_config',
                success: function(response) {
                    if (response.success) {
                        showGlobalMessage('success', response.data.message);
                    } else {
                        showGlobalMessage('error', response.data.message || 'Error al guardar la configuración global.');
                    }
                },
                error: function() {
                    showGlobalMessage('error', 'Error al guardar la configuración global. Por favor, inténtelo de nuevo.');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Manejar envío del formulario por formulario
        $('#eipsi-privacy-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var $submitButton = $(this).find('button[type="submit"]');
            var originalText = $submitButton.text();
            
            $submitButton.prop('disabled', true).text('💾 Guardando...');
            
            $('.eipsi-message').remove();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=eipsi_save_privacy_config',
                success: function(response) {
                    if (response.success) {
                        showFormMessage('success', response.data.message);
                    } else {
                        showFormMessage('error', response.data.message || 'Error al guardar la configuración.');
                    }
                },
                error: function() {
                    showFormMessage('error', 'Error al guardar la configuración. Por favor, inténtelo de nuevo.');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(originalText);
                }
            });
        });
        
        function showGlobalMessage(type, message) {
            var $message = $('<div>')
                .addClass('eipsi-message notice is-dismissible')
                .addClass(type === 'success' ? 'notice-success' : 'notice-error')
                .html('<p>' + message + '</p>');
            
            $('#eipsi-global-privacy-form').before($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        function showFormMessage(type, message) {
            var $message = $('<div>')
                .addClass('eipsi-message notice is-dismissible')
                .addClass(type === 'success' ? 'notice-success' : 'notice-error')
                .html('<p>' + message + '</p>');
            
            $('#eipsi-privacy-form').before($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    });
    </script>
    <?php
}
