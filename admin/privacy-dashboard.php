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
                        <input type="checkbox" name="device_type" <?php checked($global_config['device_type']); ?>>
                        <strong>Tipo de Dispositivo</strong>
                        <span class="eipsi-tooltip">(mobile/desktop/tablet)</span>
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


            <!-- CAPTURA BÁSICA -->
            <div class="eipsi-toggle-group">
                <h3>📋 Captura Básica</h3>
                <label>
                    <input type="checkbox" name="ip_address" <?php checked($privacy_config['ip_address'] ?? true); ?>>
                    <strong>Capturar IP del dispositivo</strong>
                    <span class="eipsi-tooltip">(Auditoría clínica - GDPR/HIPAA - retención 90 días)</span>
                </label>
            </div>

            <!-- FINGERPRINT LIVIANO DEL DISPOSITIVO -->
            <div class="eipsi-toggle-group">
                <h3>🖥️ Fingerprint Liviano del Dispositivo</h3>
                <p class="eipsi-section-description">⚠️ Estos datos son <strong>opcionales</strong> y están <strong>desactivados por defecto</strong>. Útiles para distinguir pacientes con IP compartida.</p>

                <label>
                    <input type="checkbox" name="browser" <?php checked($privacy_config['browser'] ?? false); ?>>
                    <strong>Capturar navegador y sistema operativo</strong>
                    <span class="eipsi-tooltip">(ej: Chrome 131, Firefox 132, Windows 10)</span>
                </label>

                <label>
                    <input type="checkbox" name="screen_width" <?php checked($privacy_config['screen_width'] ?? false); ?>>
                    <strong>Capturar tamaño de pantalla</strong>
                    <span class="eipsi-tooltip">(ej: 1920x1080, 1080x2400)</span>
                </label>
            </div>

            <!-- COMPORTAMIENTO CLÍNICO -->
            <div class="eipsi-toggle-group">
                <h3>🎯 Comportamiento Clínico</h3>

                <label>
                    <input type="checkbox" name="device_type" <?php checked($privacy_config['device_type'] ?? true); ?>>
                    <strong>Tipo de Dispositivo</strong>
                    <span class="eipsi-tooltip">(mobile/desktop/tablet)</span>
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
                <li>✅ <strong>Captura Básica:</strong> IP Address - Por defecto ON para auditoría clínica (GDPR/HIPAA compliant)</li>
                <li>🎯 <strong>Comportamiento Clínico:</strong> Tipo de Dispositivo - Por defecto ON</li>
                <li>🖥️ <strong>Fingerprint del Dispositivo:</strong> Navegador, Sistema Operativo, Tamaño de Pantalla - Por defecto OFF (opcional para debugging)</li>
                <li>🔄 <strong>Override por Formulario:</strong> Cada formulario puede tener su propia configuración independientemente de la global</li>
                <li>📊 <strong>Todos los datos:</strong> Incluidos en exportación Excel/CSV según configuración de privacidad</li>
            </ul>
        </div>
    </div>
    <?php
}
