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
    
    // Determine active scope (global or form)
    $active_scope = isset($_GET['scope']) ? sanitize_key($_GET['scope']) : 'global';
    if (!in_array($active_scope, array('global', 'form'), true)) {
        $active_scope = 'global';
    }

    // Get available forms for dropdown
    global $wpdb;
    $form_ids = $wpdb->get_col("SELECT DISTINCT form_id FROM {$wpdb->prefix}vas_form_results WHERE form_id IS NOT NULL ORDER BY form_id");
    ?>

    <!-- === PRIVACY & SECURITY TAB - REDESIGNED v2.1.4 === -->
    <div class="eipsi-privacy-dashboard">
        <h2>🔒 Configuración de Privacidad y Seguridad</h2>

        <!-- Scope Tabs -->
        <div class="eipsi-scope-tabs">
            <button type="button" class="eipsi-scope-tab <?php echo $active_scope === 'global' ? 'active' : ''; ?>" data-scope="global">
                Configuración global
            </button>
            <button type="button" class="eipsi-scope-tab <?php echo $active_scope === 'form' ? 'active' : ''; ?>" data-scope="form">
                Por formulario
            </button>
        </div>

        <!-- GLOBAL PANEL -->
        <div id="eipsi-scope-global" class="eipsi-scope-panel" <?php echo $active_scope !== 'global' ? 'style="display:none"' : ''; ?>>

            <form id="eipsi-global-privacy-form" method="post">
                <?php wp_nonce_field('eipsi_global_privacy_nonce', 'eipsi_global_privacy_nonce'); ?>
                <input type="hidden" name="action" value="save_global_privacy_config">

                <!-- Sección 1: Captura de metadatos -->
                <div class="eipsi-ps-section">
                    <div class="eipsi-ps-header" data-target="eipsi-ps-capture-global">
                        <span class="eipsi-ps-title">Captura de metadatos</span>
                        <span class="eipsi-ps-meta">IP, fingerprint, tipo de dispositivo</span>
                        <span class="eipsi-ps-chevron">&#9656;</span>
                    </div>
                    <div class="eipsi-ps-body open" id="eipsi-ps-capture-global">

                        <div class="eipsi-ps-row">
                            <div class="eipsi-ps-label">
                                <strong>IP del dispositivo</strong>
                                <span>Capturar dirección IP para auditoría clínica (GDPR/HIPAA - retención 90 días)</span>
                            </div>
                            <label class="eipsi-ps-toggle">
                                <input type="checkbox" name="ip_address" <?php checked($global_config['ip_address']); ?>>
                                <span class="eipsi-ps-slider"></span>
                            </label>
                        </div>

                        <div class="eipsi-ps-row">
                            <div class="eipsi-ps-label">
                                <strong>Fingerprint completo</strong>
                                <span>Identificador único del dispositivo (Canvas, WebGL, resolución, zona horaria, idioma)</span>
                            </div>
                            <label class="eipsi-ps-toggle">
                                <input type="checkbox" name="fingerprint_enabled" <?php checked($global_config['fingerprint_enabled'] ?? true); ?>>
                                <span class="eipsi-ps-slider"></span>
                            </label>
                        </div>

                        <div class="eipsi-ps-row">
                            <div class="eipsi-ps-label">
                                <strong>Navegador y sistema operativo</strong>
                                <span>User agent y platform del dispositivo</span>
                            </div>
                            <label class="eipsi-ps-toggle">
                                <input type="checkbox" name="browser" <?php checked($global_config['browser']); ?>>
                                <span class="eipsi-ps-slider"></span>
                            </label>
                        </div>

                        <div class="eipsi-ps-row">
                            <div class="eipsi-ps-label">
                                <strong>Tamaño de pantalla</strong>
                                <span>Resolución y dimensiones de la ventana</span>
                            </div>
                            <label class="eipsi-ps-toggle">
                                <input type="checkbox" name="screen_width" <?php checked($global_config['screen_width']); ?>>
                                <span class="eipsi-ps-slider"></span>
                            </label>
                        </div>

                        <div class="eipsi-ps-row">
                            <div class="eipsi-ps-label">
                                <strong>Tipo de dispositivo</strong>
                                <span>Desktop, tablet o mobile</span>
                            </div>
                            <label class="eipsi-ps-toggle">
                                <input type="checkbox" name="device_type" <?php checked($global_config['device_type']); ?>>
                                <span class="eipsi-ps-slider"></span>
                            </label>
                        </div>

                    </div>
                </div>

                <!-- Sección 2: Exportación extendida -->
                <div class="eipsi-ps-section">
                    <div class="eipsi-ps-header" data-target="eipsi-ps-export-global">
                        <span class="eipsi-ps-title">Exportación extendida</span>
                        <span class="eipsi-ps-meta">12 campos de metadatos adicionales</span>
                        <span class="eipsi-ps-chevron">&#9656;</span>
                    </div>
                    <div class="eipsi-ps-body open" id="eipsi-ps-export-global">

                        <div class="eipsi-ps-export-grid">
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_canvas_fingerprint" <?php checked($global_config['export_canvas_fingerprint'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Canvas fingerprint</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_webgl_renderer" <?php checked($global_config['export_webgl_renderer'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>WebGL Renderer</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_screen_resolution" <?php checked($global_config['export_screen_resolution'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Screen Resolution</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_screen_depth" <?php checked($global_config['export_screen_depth'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Screen Depth</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_pixel_ratio" <?php checked($global_config['export_pixel_ratio'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Pixel Ratio</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_timezone" <?php checked($global_config['export_timezone'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Timezone</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_language" <?php checked($global_config['export_language'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Language</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_cpu_cores" <?php checked($global_config['export_cpu_cores'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>CPU Cores</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_ram" <?php checked($global_config['export_ram'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>RAM (GB)</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_plugins" <?php checked($global_config['export_plugins'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Browser Plugins</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_touch_support" <?php checked($global_config['export_touch_support'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Touch Support</span>
                            </div>
                            <div class="eipsi-ps-export-item">
                                <label class="eipsi-ps-toggle">
                                    <input type="checkbox" name="export_cookies_enabled" <?php checked($global_config['export_cookies_enabled'] ?? true); ?>>
                                    <span class="eipsi-ps-slider"></span>
                                </label>
                                <span>Cookies Enabled</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Sección 3: Retención de logs (solo Global) -->
                <div class="eipsi-ps-section">
                    <div class="eipsi-ps-header" data-target="eipsi-ps-retention">
                        <span class="eipsi-ps-title">Retención de logs de acceso</span>
                        <span class="eipsi-ps-meta">Configuración de retención de datos</span>
                        <span class="eipsi-ps-chevron">&#9656;</span>
                    </div>
                    <div class="eipsi-ps-body open" id="eipsi-ps-retention">

                        <form method="post" style="margin: 0; padding: 0;">
                            <?php wp_nonce_field('eipsi_access_log_retention_settings', 'eipsi_access_log_retention_nonce'); ?>

                            <div class="eipsi-ps-row" style="border-bottom: none;">
                                <div class="eipsi-ps-label">
                                    <strong>Días de retención</strong>
                                    <span>Tiempo que se conservan los logs de acceso (30-2555 días)</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="number" name="access_log_retention_days" value="<?php echo esc_attr($retention_days); ?>" min="30" max="2555" style="width: 80px; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 4px;">
                                    <span style="color: #64748b; font-size: 13px;">días</span>
                                </div>
                            </div>

                            <div class="eipsi-ps-retention-hint">
                                <strong>Recomendaciones:</strong> 90 días (estudios cortos) | 365 días (estudios anuales) | 2555 días/7 años (requerimiento regulatorio longitudinal)
                            </div>

                            <div class="eipsi-ps-save-bar" style="border-top: none; margin-top: 10px; padding-top: 0;">
                                <span></span>
                                <button type="submit" class="button button-secondary">Guardar retención</button>
                            </div>
                        </form>

                    </div>
                </div>

                <!-- Save Bar Global -->
                <div class="eipsi-ps-save-bar">
                    <span class="eipsi-ps-save-hint" id="eipsi-global-hint">Sin cambios pendientes</span>
                    <button type="submit" class="button button-primary" disabled>Guardar cambios</button>
                </div>

            </form>
        </div>

        <!-- FORM PANEL -->
        <div id="eipsi-scope-form" class="eipsi-scope-panel" <?php echo $active_scope !== 'form' ? 'style="display:none"' : ''; ?>>

            <?php if (empty($form_ids)): ?>
                <div class="notice notice-warning">
                    <p>No hay formularios disponibles. Cree un formulario primero.</p>
                </div>
            <?php else: ?>

                <form method="get" style="margin-bottom: 20px;">
                    <input type="hidden" name="page" value="eipsi-configuration">
                    <input type="hidden" name="tab" value="privacy-security">
                    <input type="hidden" name="scope" value="form">
                    <div class="eipsi-ps-form-selector">
                        <label for="form_id_select"><strong>Formulario:</strong></label>
                        <select name="form_id" id="form_id_select" onchange="this.form.submit()">
                            <option value="">Seleccionar formulario...</option>
                            <?php foreach ($form_ids as $fid): ?>
                                <option value="<?php echo esc_attr($fid); ?>" <?php selected($current_form_id, $fid); ?>>
                                    <?php echo esc_html($fid); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if ($current_form_id): ?>

                    <form id="eipsi-privacy-form" method="post">
                        <?php wp_nonce_field('eipsi_privacy_nonce', 'eipsi_privacy_nonce'); ?>
                        <input type="hidden" name="action" value="save_privacy_config">
                        <input type="hidden" name="form_id" value="<?php echo esc_attr($current_form_id); ?>">

                        <!-- Sección 1: Captura de metadatos -->
                        <div class="eipsi-ps-section">
                            <div class="eipsi-ps-header" data-target="eipsi-ps-capture-form">
                                <span class="eipsi-ps-title">Captura de metadatos</span>
                                <span class="eipsi-ps-meta">IP, fingerprint, tipo de dispositivo</span>
                                <span class="eipsi-ps-chevron">&#9656;</span>
                            </div>
                            <div class="eipsi-ps-body open" id="eipsi-ps-capture-form">

                                <div class="eipsi-ps-row">
                                    <div class="eipsi-ps-label">
                                        <strong>IP del dispositivo</strong>
                                        <span>Capturar dirección IP para auditoría clínica</span>
                                    </div>
                                    <label class="eipsi-ps-toggle">
                                        <input type="checkbox" name="ip_address" <?php checked($privacy_config['ip_address']); ?>>
                                        <span class="eipsi-ps-slider"></span>
                                    </label>
                                </div>

                                <div class="eipsi-ps-row">
                                    <div class="eipsi-ps-label">
                                        <strong>Fingerprint completo</strong>
                                        <span>Identificador único del dispositivo</span>
                                    </div>
                                    <label class="eipsi-ps-toggle">
                                        <input type="checkbox" name="fingerprint_enabled" <?php checked($privacy_config['fingerprint_enabled'] ?? true); ?>>
                                        <span class="eipsi-ps-slider"></span>
                                    </label>
                                </div>

                                <div class="eipsi-ps-row">
                                    <div class="eipsi-ps-label">
                                        <strong>Navegador y sistema operativo</strong>
                                        <span>User agent y platform del dispositivo</span>
                                    </div>
                                    <label class="eipsi-ps-toggle">
                                        <input type="checkbox" name="browser" <?php checked($privacy_config['browser']); ?>>
                                        <span class="eipsi-ps-slider"></span>
                                    </label>
                                </div>

                                <div class="eipsi-ps-row">
                                    <div class="eipsi-ps-label">
                                        <strong>Tamaño de pantalla</strong>
                                        <span>Resolución y dimensiones de la ventana</span>
                                    </div>
                                    <label class="eipsi-ps-toggle">
                                        <input type="checkbox" name="screen_width" <?php checked($privacy_config['screen_width']); ?>>
                                        <span class="eipsi-ps-slider"></span>
                                    </label>
                                </div>

                                <div class="eipsi-ps-row">
                                    <div class="eipsi-ps-label">
                                        <strong>Tipo de dispositivo</strong>
                                        <span>Desktop, tablet o mobile</span>
                                    </div>
                                    <label class="eipsi-ps-toggle">
                                        <input type="checkbox" name="device_type" <?php checked($privacy_config['device_type']); ?>>
                                        <span class="eipsi-ps-slider"></span>
                                    </label>
                                </div>

                            </div>
                        </div>

                        <!-- Sección 2: Exportación extendida -->
                        <div class="eipsi-ps-section">
                            <div class="eipsi-ps-header" data-target="eipsi-ps-export-form">
                                <span class="eipsi-ps-title">Exportación extendida</span>
                                <span class="eipsi-ps-meta">12 campos de metadatos adicionales</span>
                                <span class="eipsi-ps-chevron">&#9656;</span>
                            </div>
                            <div class="eipsi-ps-body open" id="eipsi-ps-export-form">

                                <div class="eipsi-ps-export-grid">
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_canvas_fingerprint" <?php checked($privacy_config['export_canvas_fingerprint'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Canvas fingerprint</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_webgl_renderer" <?php checked($privacy_config['export_webgl_renderer'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>WebGL Renderer</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_screen_resolution" <?php checked($privacy_config['export_screen_resolution'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Screen Resolution</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_screen_depth" <?php checked($privacy_config['export_screen_depth'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Screen Depth</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_pixel_ratio" <?php checked($privacy_config['export_pixel_ratio'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Pixel Ratio</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_timezone" <?php checked($privacy_config['export_timezone'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Timezone</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_language" <?php checked($privacy_config['export_language'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Language</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_cpu_cores" <?php checked($privacy_config['export_cpu_cores'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>CPU Cores</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_ram" <?php checked($privacy_config['export_ram'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>RAM (GB)</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_plugins" <?php checked($privacy_config['export_plugins'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Browser Plugins</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_touch_support" <?php checked($privacy_config['export_touch_support'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Touch Support</span>
                                    </div>
                                    <div class="eipsi-ps-export-item">
                                        <label class="eipsi-ps-toggle">
                                            <input type="checkbox" name="export_cookies_enabled" <?php checked($privacy_config['export_cookies_enabled'] ?? true); ?>>
                                            <span class="eipsi-ps-slider"></span>
                                        </label>
                                        <span>Cookies Enabled</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Save Bar Form -->
                        <div class="eipsi-ps-save-bar">
                            <span class="eipsi-ps-save-hint" id="eipsi-form-hint">Sin cambios pendientes</span>
                            <button type="submit" class="button button-primary" disabled>Guardar cambios</button>
                        </div>

                    </form>

                <?php else: ?>
                    <div class="notice notice-info">
                        <p>Seleccione un formulario arriba para configurar sus opciones de privacidad.</p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>

    <style>
        /* === PRIVACY & SECURITY TAB === */
        .eipsi-scope-tabs {
            display: flex;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 16px;
            width: fit-content;
        }

        .eipsi-scope-tab {
            padding: 8px 20px;
            font-size: 13px;
            background: #fff;
            border: none;
            cursor: pointer;
            color: #64748b;
            transition: background 0.15s;
        }

        .eipsi-scope-tab:not(:last-child) {
            border-right: 1px solid #e2e8f0;
        }

        .eipsi-scope-tab.active {
            background: #3B6CAA;
            color: #fff;
            font-weight: 500;
        }

        .eipsi-scope-tab:not(.active):hover {
            background: #f8f9fa;
        }

        .eipsi-ps-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .eipsi-ps-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.15s;
            user-select: none;
        }

        .eipsi-ps-header:hover {
            background: #f8f9fa;
        }

        .eipsi-ps-title {
            font-weight: 600;
            font-size: 13px;
            flex: 1;
            color: #2c3e50;
        }

        .eipsi-ps-meta {
            font-size: 12px;
            color: #64748b;
        }

        .eipsi-ps-chevron {
            color: #94a3b8;
            font-size: 11px;
            transition: transform 0.2s;
        }

        .eipsi-ps-body {
            display: none;
            padding: 4px 16px 14px;
            border-top: 1px solid #e2e8f0;
        }

        .eipsi-ps-body.open {
            display: block;
        }

        .eipsi-ps-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .eipsi-ps-row:last-child {
            border-bottom: none;
        }

        .eipsi-ps-label {
            flex: 1;
            padding-right: 20px;
        }

        .eipsi-ps-label strong {
            display: block;
            font-size: 13px;
            color: #2c3e50;
        }

        .eipsi-ps-label span {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.4;
        }

        .eipsi-ps-toggle {
            position: relative;
            display: inline-block;
            width: 34px;
            height: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .eipsi-ps-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .eipsi-ps-slider {
            position: absolute;
            inset: 0;
            background: #c3c4c7;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .eipsi-ps-slider:before {
            content: '';
            position: absolute;
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s;
        }

        .eipsi-ps-toggle input:checked + .eipsi-ps-slider {
            background: #3B6CAA;
        }

        .eipsi-ps-toggle input:checked + .eipsi-ps-slider:before {
            transform: translateX(14px);
        }

        .eipsi-ps-export-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .eipsi-ps-export-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }

        .eipsi-ps-export-item:nth-child(odd) {
            padding-right: 16px;
            border-right: 1px solid #e2e8f0;
        }

        .eipsi-ps-export-item:nth-child(even) {
            padding-left: 16px;
        }

        .eipsi-ps-export-item:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .eipsi-ps-save-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
        }

        .eipsi-ps-save-hint {
            font-size: 12px;
            color: #64748b;
        }

        .eipsi-ps-form-selector {
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .eipsi-ps-form-selector select {
            font-size: 13px;
            padding: 5px 8px;
        }

        .eipsi-ps-retention-hint {
            margin-top: 6px;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.5;
        }
    </style>

    <script>
        (function() {
            // Scope tabs switching
            document.querySelectorAll('.eipsi-scope-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var scope = this.dataset.scope;
                    document.querySelectorAll('.eipsi-scope-tab').forEach(function(t) {
                        t.classList.remove('active');
                    });
                    document.querySelectorAll('.eipsi-scope-panel').forEach(function(p) {
                        p.style.display = 'none';
                    });
                    this.classList.add('active');
                    document.getElementById('eipsi-scope-' + scope).style.display = 'block';

                    var url = new URL(window.location.href);
                    url.searchParams.set('scope', scope);
                    window.history.replaceState({}, '', url);
                });
            });

            // Collapsible sections
            document.querySelectorAll('.eipsi-ps-header').forEach(function(header) {
                header.addEventListener('click', function() {
                    var id = this.dataset.target;
                    var body = document.getElementById(id);
                    var isOpen = body.classList.toggle('open');
                    this.querySelector('.eipsi-ps-chevron').style.transform = isOpen ? 'rotate(90deg)' : '';
                    localStorage.setItem('eipsi_' + id, isOpen ? '1' : '0');
                });

                // Restore state
                var id = header.dataset.target;
                var saved = localStorage.getItem('eipsi_' + id);
                var body = document.getElementById(id);
                if (saved === '0') {
                    body.classList.remove('open');
                } else {
                    body.classList.add('open');
                    header.querySelector('.eipsi-ps-chevron').style.transform = 'rotate(90deg)';
                }
            });

            // Save bar - enable on change
            document.querySelectorAll('#eipsi-global-privacy-form, #eipsi-privacy-form').forEach(function(form) {
                var btn = form.querySelector('[type="submit"]');
                var hint = form.querySelector('.eipsi-ps-save-hint');
                if (!btn || !hint) return;

                form.addEventListener('change', function() {
                    btn.disabled = false;
                    hint.textContent = 'Hay cambios sin guardar';
                    hint.style.color = '#b35900';
                });
            });
        })();
    </script>

    <?php
}
?>
