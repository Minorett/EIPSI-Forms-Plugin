<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register and sanitize access log retention setting
 * Phase 2 - Admin Settings for Retention Days
 *
 * @since 2.0.0
 */
function eipsi_register_access_log_retention_setting() {
    register_setting('eipsi_forms_options', 'eipsi_access_log_retention_days', array(
        'type' => 'integer',
        'default' => 365,
        'sanitize_callback' => function($value) {
            $value = intval($value);
            // Clamp between 30 and 2555 (7 years max)
            return max(30, min(2555, $value));
        }
    ));
}
add_action('admin_init', 'eipsi_register_access_log_retention_setting');

/**
 * Get access log retention days
 *
 * @return int Number of days to retain access logs (default: 365)
 */
function eipsi_get_access_log_retention_days() {
    return get_option('eipsi_access_log_retention_days', 365);
}

/**
 * Get last access log purge date
 *
 * @return string|null MySQL datetime or null if never purged
 */
function eipsi_get_last_access_log_purge_date() {
    return get_option('eipsi_last_access_log_purge', null);
}

function render_privacy_dashboard($form_id = null) {
    $current_form_id = $form_id ?: (isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '');

    // Handle access log retention settings form submission
    $retention_message = '';
    if (isset($_POST['eipsi_access_log_retention_nonce']) &&
        wp_verify_nonce($_POST['eipsi_access_log_retention_nonce'], 'eipsi_access_log_retention_settings')) {

        $retention_days = isset($_POST['access_log_retention_days']) ? intval($_POST['access_log_retention_days']) : 365;
        $retention_days = max(30, min(2555, $retention_days)); // Clamp between 30 and 2555

        update_option('eipsi_access_log_retention_days', $retention_days);
        $retention_message = __('Configuración de retención guardada correctamente.', 'eipsi-forms');
    }

    // Obtener configuración actual
    require_once dirname(__FILE__) . '/privacy-config.php';
    $global_config = get_global_privacy_defaults();
    $privacy_config = get_privacy_config($current_form_id);

    // Get current retention settings
    $retention_days = eipsi_get_access_log_retention_days();
    $last_purge = eipsi_get_last_access_log_purge_date();
    
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

                <!-- ✅ v1.5.4 - FINGERPRINT COMPLETO DEL DISPOSITIVO -->
                <div class="eipsi-toggle-group" style="background: #f0f9ff; border: 2px solid #3b82f6; padding: 16px; border-radius: 8px; margin-top: 20px;">
                    <h3>🖥️ Fingerprint Completo del Dispositivo</h3>
                    <p class="eipsi-section-description" style="margin-bottom: 12px;">
                        <strong>✅ ACTIVADO POR DEFECTO</strong> - Genera un identificador único del dispositivo para distinguir pacientes con IP compartida. Incluye Canvas, WebGL, resolución, zona horaria, idioma, etc.
                    </p>

                    <label style="display: block; margin-bottom: 12px;">
                        <input type="checkbox" name="fingerprint_enabled" <?php checked($global_config['fingerprint_enabled'] ?? true); ?>>
                        <strong>Generar Fingerprint_ID único del dispositivo</strong>
                        <span class="eipsi-tooltip">(Canvas + WebGL + Screen + Timezone + Language + Hardware)</span>
                    </label>

                    <details style="margin-top: 12px; padding: 12px; background: #fff; border-radius: 6px; border: 1px solid #cbd5e0;">
                        <summary style="cursor: pointer; font-weight: 600; color: #1e40af;">
                            📋 Ver detalles capturados del fingerprint
                        </summary>
                        <ul style="margin-top: 12px; margin-left: 20px; font-size: 13px; color: #475569; line-height: 1.6;">
                            <li><strong>Canvas Fingerprint:</strong> GPU rendering signature (único por navegador/GPU)</li>
                            <li><strong>WebGL Renderer:</strong> GPU vendor y renderer info</li>
                            <li><strong>Resolución de pantalla:</strong> width × height (ej: 1920×1080)</li>
                            <li><strong>Profundidad de color:</strong> 24/32 bits</li>
                            <li><strong>Pixel Ratio:</strong> Densidad de píxeles (1.0, 2.0, 3.0)</li>
                            <li><strong>Zona horaria:</strong> (ej: America/Argentina/Buenos_Aires)</li>
                            <li><strong>Idioma:</strong> navegador + idiomas preferidos</li>
                            <li><strong>Hardware:</strong> CPU cores + RAM (si disponible)</li>
                            <li><strong>Do Not Track:</strong> configuración de privacidad</li>
                            <li><strong>Cookies:</strong> habilitadas/deshabilitadas</li>
                            <li><strong>Plugins:</strong> lista de plugins del navegador</li>
                        </ul>
                    </details>
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

            <!-- ✅ v1.5.4 - FINGERPRINT COMPLETO DEL DISPOSITIVO -->
            <div class="eipsi-toggle-group" style="background: #f0f9ff; border: 2px solid #3b82f6; padding: 16px; border-radius: 8px; margin-top: 20px;">
                <h3>🖥️ Fingerprint Completo del Dispositivo</h3>
                <p class="eipsi-section-description" style="margin-bottom: 12px;">
                    <strong>✅ ACTIVADO POR DEFECTO</strong> - Genera un identificador único del dispositivo para distinguir pacientes con IP compartida.
                </p>

                <label style="display: block; margin-bottom: 12px;">
                    <input type="checkbox" name="fingerprint_enabled" <?php checked($privacy_config['fingerprint_enabled'] ?? true); ?>>
                    <strong>Generar Fingerprint_ID único del dispositivo</strong>
                    <span class="eipsi-tooltip">(Canvas + WebGL + Screen + Timezone + Language + Hardware)</span>
                </label>

                <details style="margin-top: 12px; padding: 12px; background: #fff; border-radius: 6px; border: 1px solid #cbd5e0;">
                    <summary style="cursor: pointer; font-weight: 600; color: #1e40af;">
                        📋 Ver detalles capturados del fingerprint
                    </summary>
                    <ul style="margin-top: 12px; margin-left: 20px; font-size: 13px; color: #475569; line-height: 1.6;">
                        <li><strong>Canvas Fingerprint:</strong> GPU rendering signature</li>
                        <li><strong>WebGL Renderer:</strong> GPU vendor y renderer info</li>
                        <li><strong>Resolución de pantalla:</strong> width × height</li>
                        <li><strong>Profundidad de color:</strong> 24/32 bits</li>
                        <li><strong>Pixel Ratio:</strong> Densidad de píxeles</li>
                        <li><strong>Zona horaria:</strong> Timezone del usuario</li>
                        <li><strong>Idioma:</strong> navegador + idiomas preferidos</li>
                        <li><strong>Hardware:</strong> CPU cores + RAM</li>
                        <li><strong>Do Not Track:</strong> configuración de privacidad</li>
                        <li><strong>Cookies:</strong> habilitadas/deshabilitadas</li>
                        <li><strong>Plugins:</strong> lista de plugins</li>
                    </ul>
                </details>
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
                <li>🖥️ <strong>Fingerprint Completo:</strong> Fingerprint_ID único (Canvas + WebGL + Screen + Timezone + Language + Hardware) - Por defecto ON</li>
                <li>🎯 <strong>Comportamiento Clínico:</strong> Tipo de Dispositivo - Por defecto ON</li>
                <li>🖥️ <strong>Fingerprint Liviano:</strong> Navegador, Sistema Operativo, Tamaño de Pantalla - Por defecto OFF (opcional para debugging)</li>
                <li>🔄 <strong>Override por Formulario:</strong> Cada formulario puede tener su propia configuración independientemente de la global</li>
                <li>📊 <strong>Exportación Excel:</strong> El Fingerprint_ID se incluye siempre que esté activado. Los detalles crudos del fingerprint se pueden exportar en una hoja separada (opción de debugging)</li>
            </ul>
        </div>

        <!-- SEPARADOR -->
        <hr style="margin: 30px 0; border: none; height: 1px; background: #e2e8f0;">

        <!-- ACCESS LOG RETENTION SECTION - Phase 2 -->
        <div class="eipsi-retention-config" style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-top: 20px;">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-clock" style="color: #2271b1;"></span>
                <?php echo esc_html__('Retención de Logs de Acceso', 'eipsi-forms'); ?>
            </h3>

            <p class="description" style="margin-bottom: 15px;">
                <?php echo esc_html__('Los logs de acceso registran cada acción de los participantes (login, magic link, completado de tomas, etc.) para auditoría y seguridad. Configura cuánto tiempo conservar estos registros.', 'eipsi-forms'); ?>
            </p>

            <?php if (!empty($retention_message)): ?>
                <div class="notice notice-success inline" style="margin-bottom: 15px;">
                    <p><?php echo esc_html($retention_message); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('eipsi_access_log_retention_settings', 'eipsi_access_log_retention_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 250px;">
                                <label for="access_log_retention_days">
                                    <?php echo esc_html__('Días de retención', 'eipsi-forms'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number"
                                    id="access_log_retention_days"
                                    name="access_log_retention_days"
                                    value="<?php echo esc_attr($retention_days); ?>"
                                    min="30"
                                    max="2555"
                                    class="small-text"
                                    required>
                                <span style="margin-left: 8px; color: #666;">
                                    <?php echo esc_html__('días', 'eipsi-forms'); ?>
                                </span>
                                <p class="description">
                                    <?php echo esc_html__('Mínimo: 30 días | Máximo: 2555 días (7 años). Registros más antiguos se eliminan automáticamente.', 'eipsi-forms'); ?>
                                </p>
                            </td>
                        </tr>

                        <?php if ($last_purge): ?>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html__('Última purga', 'eipsi-forms'); ?>
                            </th>
                            <td>
                                <span style="color: #666;">
                                    <?php
                                    echo esc_html(
                                        mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $last_purge)
                                    );
                                    ?>
                                </span>
                                <p class="description">
                                    <?php echo esc_html__('Fecha de la última ejecución automática de limpieza de logs.', 'eipsi-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php
                        // Get current log count
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'survey_participant_access_log';
                        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
                        if ($table_exists) {
                            $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
                            $old_logs = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                                $retention_days
                            ));
                        ?>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html__('Estado actual', 'eipsi-forms'); ?>
                            </th>
                            <td>
                                <span class="dashicons dashicons-database" style="color: #2271b1;"></span>
                                <strong><?php echo number_format_i18n($total_logs); ?></strong>
                                <?php echo esc_html__('registros totales', 'eipsi-forms'); ?>

                                <?php if ($old_logs > 0): ?>
                                <br>
                                <span style="color: #d63638; margin-top: 5px; display: inline-block;">
                                    <span class="dashicons dashicons-warning" style="font-size: 16px;"></span>
                                    <?php
                                    echo sprintf(
                                        esc_html__('%d registros tienen más de %d días y serán eliminados en la próxima purga.', 'eipsi-forms'),
                                        number_format_i18n($old_logs),
                                        $retention_days
                                    );
                                    ?>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-yes" style="margin-top: 3px;"></span>
                    <?php echo esc_html__('Guardar configuración de retención', 'eipsi-forms'); ?>
                </button>
            </form>

            <div style="margin-top: 20px; padding: 15px; background: #fff; border-radius: 6px; border: 1px solid #dcdcde;">
                <h4 style="margin-top: 0; color: #50575e;">
                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                    <?php echo esc_html__('Recomendaciones de retención', 'eipsi-forms'); ?>
                </h4>
                <ul style="margin: 0 0 0 20px; color: #666; font-size: 13px; line-height: 1.6;">
                    <li><strong>90 días:</strong> <?php echo esc_html__('Mínimo recomendado para auditoría básica.', 'eipsi-forms'); ?></li>
                    <li><strong>365 días:</strong> <?php echo esc_html__('Estándar para estudios clínicos anuales.', 'eipsi-forms'); ?></li>
                    <li><strong>7 años (2555 días):</strong> <?php echo esc_html__('Requisito regulatorio para estudios clínicos longitudinales.', 'eipsi-forms'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}
