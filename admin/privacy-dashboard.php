<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_privacy_dashboard($form_id = null) {
    $current_form_id = $form_id ?: (isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '');
    
    // Obtener configuración actual
    require_once dirname(__FILE__) . '/privacy-config.php';
    $privacy_config = get_privacy_config($current_form_id);
    
    ?>
    <div class="eipsi-privacy-dashboard">
        <h2>🔒 Configuración de Metadatos y Privacidad</h2>
        
        <?php if ($current_form_id): ?>
            <p><strong>Formulario:</strong> <code><?php echo esc_html($current_form_id); ?></code></p>
        <?php endif; ?>
        
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
                
                <label>
                    <input type="checkbox" checked disabled> 
                    <strong>Quality Flag</strong>
                    <span class="eipsi-tooltip">(Control automático: HIGH/NORMAL/LOW)</span>
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
                    <input type="checkbox" name="clinical_consistency" <?php checked($privacy_config['clinical_consistency'] ?? true); ?>>
                    <strong>Clinical Consistency</strong>
                    <span class="eipsi-tooltip">(Incoherencias lógicas detectadas)</span>
                </label>
                
                <label>
                    <input type="checkbox" name="avoidance_patterns" <?php checked($privacy_config['avoidance_patterns'] ?? true); ?>>
                    <strong>Avoidance Patterns</strong>
                    <span class="eipsi-tooltip">(Saltos, retrocesos, omisiones)</span>
                </label>
            </div>
            
            <!-- TRAZABILIDAD (REQUERIDA) -->
            <div class="eipsi-toggle-group">
                <h3>📋 Trazabilidad</h3>
                
                <label>
                    <input type="checkbox" name="device_type" <?php checked($privacy_config['device_type'] ?? true); ?>>
                    <strong>Device Type</strong>
                    <span class="eipsi-tooltip">(mobile/desktop/tablet)</span>
                </label>
                
                <label>
                    <input type="checkbox" checked disabled readonly>
                    <strong>IP Address</strong>
                    <span class="eipsi-required">⚠️ REQUERIDO - NO CONFIGURABLE</span>
                    <span class="eipsi-tooltip">(Trazabilidad clínica obligatoria - 190.194.12.34)</span>
                </label>
            </div>
            
            <button type="submit" class="button button-primary">💾 Guardar Configuración</button>
        </form>
        
        <!-- INFO BOX -->
        <div class="eipsi-info-box">
            <p><strong>ℹ️ Sobre IP:</strong> Se captura y almacena en texto plano por 90 días para auditoría clínica según requisito del equipo. No configurable.</p>
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
        
        .eipsi-required {
            color: #d32f2f;
            font-weight: 600;
            font-size: 11px;
            margin-left: 8px;
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
    </style>
    <?php
}
