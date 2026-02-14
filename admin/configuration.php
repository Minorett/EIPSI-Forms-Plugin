<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EIPSI Forms Configuration Page
 * UI for managing external database credentials
 */

function eipsi_display_configuration_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'eipsi-forms'));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();

    $smtp_service = class_exists('EIPSI_SMTP_Service') ? new EIPSI_SMTP_Service() : null;
    $smtp_config = $smtp_service ? $smtp_service->get_config() : null;
    $smtp_host = $smtp_config ? $smtp_config['host'] : '';
    $smtp_port = $smtp_config ? $smtp_config['port'] : 587;
    $smtp_user = $smtp_config ? $smtp_config['user'] : '';
    $smtp_encryption = $smtp_config ? $smtp_config['encryption'] : 'tls';
    $smtp_encryption_labels = array(
        'tls' => __('TLS (recomendado)', 'eipsi-forms'),
        'ssl' => __('SSL', 'eipsi-forms'),
        'none' => __('Sin cifrado', 'eipsi-forms')
    );

    // Get current credentials for display (without password)
    $credentials = $db_helper->get_credentials();
    $current_host = $credentials ? $credentials['host'] : '';
    $current_user = $credentials ? $credentials['user'] : '';
    $current_db_name = $credentials ? $credentials['name'] : '';
    
    // Get current status
    $status = $db_helper->get_status();
    
    ?>
    <div class="wrap eipsi-config-wrap">
        <h1><?php echo esc_html__('Database Configuration', 'eipsi-forms'); ?></h1>
        <p class="description">
            <?php echo esc_html__('Configure an external MySQL database to store form submission data. If no external database is configured, data will be stored in the default WordPress database.', 'eipsi-forms'); ?>
        </p>
        
        <!-- Prominent Database Indicator -->
        <div class="eipsi-db-indicator-banner">
            <div class="eipsi-db-indicator-content">
                <div class="eipsi-db-indicator-icon">
                    <span class="dashicons dashicons-database"></span>
                </div>
                <div class="eipsi-db-indicator-info">
                    <div class="eipsi-db-indicator-label"><?php echo esc_html__('Current Storage Location:', 'eipsi-forms'); ?></div>
                    <div class="eipsi-db-indicator-value">
                        <?php if ($status['connected']): ?>
                            <span class="eipsi-db-badge eipsi-db-badge--external">
                                <span class="dashicons dashicons-admin-site-alt3"></span>
                                <?php echo esc_html__('External Database', 'eipsi-forms'); ?>
                            </span>
                            <span class="eipsi-db-name"><?php echo esc_html($status['db_name']); ?></span>
                        <?php else: ?>
                            <span class="eipsi-db-badge eipsi-db-badge--wordpress">
                                <span class="dashicons dashicons-wordpress"></span>
                                <?php echo esc_html__('WordPress Database', 'eipsi-forms'); ?>
                            </span>
                            <span class="eipsi-db-name"><?php echo esc_html(DB_NAME); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($status['connected']): ?>
                <div class="eipsi-db-indicator-status">
                    <span class="eipsi-status-dot eipsi-status-dot--connected"></span>
                    <span class="eipsi-status-text"><?php echo esc_html__('Connected', 'eipsi-forms'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="eipsi-config-container">
            <!-- Configuration Form -->
            <div class="eipsi-config-form-section">
                <h2><?php echo esc_html__('Database Connection Settings', 'eipsi-forms'); ?></h2>
                
                <form id="eipsi-db-config-form" class="eipsi-db-form">
                    <input type="hidden" id="eipsi_db_config_nonce" value="<?php echo esc_attr(wp_create_nonce('eipsi_admin_nonce')); ?>"><?php // Use eipsi_admin_nonce for consistency ?>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="db_host">
                                        <?php echo esc_html__('Host', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="db_host"
                                        name="db_host"
                                        class="regular-text"
                                        value="<?php echo esc_attr($current_host); ?>"
                                        placeholder="localhost"
                                        aria-describedby="db_host_desc"
                                        required>
                                    <p class="description" id="db_host_desc">
                                        <?php echo esc_html__('Database server hostname or IP address (e.g., localhost, 192.168.1.100)', 'eipsi-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="db_user">
                                        <?php echo esc_html__('Username', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="db_user"
                                        name="db_user"
                                        class="regular-text"
                                        value="<?php echo esc_attr($current_user); ?>"
                                        placeholder="<?php echo esc_attr__('MySQL username', 'eipsi-forms'); ?>"
                                        aria-describedby="db_user_desc"
                                        required>
                                    <p class="description" id="db_user_desc">
                                        <?php echo esc_html__('MySQL user with access to the database', 'eipsi-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="db_password">
                                        <?php echo esc_html__('Password', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="password"
                                        id="db_password"
                                        name="db_password"
                                        class="regular-text"
                                        placeholder="<?php echo esc_attr__('MySQL password', 'eipsi-forms'); ?>"
                                        aria-describedby="db_password_desc"
                                        <?php echo $credentials ? '' : 'required'; ?>>
                                    <p class="description" id="db_password_desc">
                                        <?php
                                        if ($credentials) {
                                            echo esc_html__('Leave blank to keep existing password', 'eipsi-forms');
                                        } else {
                                            echo esc_html__('MySQL user password (will be encrypted)', 'eipsi-forms');
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="db_name">
                                        <?php echo esc_html__('Database Name', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="db_name"
                                        name="db_name"
                                        class="regular-text"
                                        value="<?php echo esc_attr($current_db_name); ?>"
                                        placeholder="research_db_custom"
                                        aria-describedby="db_name_desc"
                                        required>
                                    <p class="description" id="db_name_desc">
                                        <?php echo esc_html__('Name of the database to store form submissions', 'eipsi-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="eipsi-form-actions">
                        <button type="button" id="eipsi-test-connection" class="button button-secondary">
                            <span class="dashicons dashicons-database"></span>
                            <?php echo esc_html__('Test Connection', 'eipsi-forms'); ?>
                        </button>
                        
                        <button type="submit" id="eipsi-save-config" class="button button-primary" disabled>
                            <span class="dashicons dashicons-yes"></span>
                            <?php echo esc_html__('Save Configuration', 'eipsi-forms'); ?>
                        </button>
                        
                        <?php if ($credentials): ?>
                        <button type="button" id="eipsi-disable-external-db" class="button button-link-delete">
                            <?php echo esc_html__('Disable External Database', 'eipsi-forms'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div id="eipsi-message-container" role="alert" aria-live="polite" style="display: none;"></div>
                </form>
            </div>
            
            <!-- Status Indicator -->
            <div class="eipsi-status-section">
                <h2><?php echo esc_html__('Connection Status', 'eipsi-forms'); ?></h2>
                
                <div id="eipsi-status-box" class="eipsi-status-box">
                    <div class="eipsi-status-indicator">
                        <?php if ($status['connected']): ?>
                            <span class="status-icon status-connected"></span>
                            <span class="status-text"><?php echo esc_html__('Connected', 'eipsi-forms'); ?></span>
                        <?php else: ?>
                            <span class="status-icon status-disconnected"></span>
                            <span class="status-text"><?php echo esc_html__('Disconnected', 'eipsi-forms'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($status['connected']): ?>
                    <div class="eipsi-status-details">
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Current Database:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($status['db_name']); ?></span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Records:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo number_format_i18n($status['record_count']); ?></span>
                        </div>
                        <?php if (!empty($status['last_updated'])): ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Last Updated:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $status['last_updated'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <?php 
                    // Get local table status for WordPress database
                    $local_table_status = $db_helper->check_table_status();
                    ?>
                    <div class="eipsi-status-details">
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Base de datos:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($local_table_status['db_name']); ?> @ <?php echo esc_html($local_table_status['db_host']); ?></span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Registros totales:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo number_format_i18n($local_table_status['total_records']); ?></span>
                        </div>
                        <?php if (!empty($local_table_status['last_verified'])): ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Última verificación:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $local_table_status['last_verified'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Local Table Status Details -->
                    <div class="eipsi-local-table-status" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <h4 style="margin-top: 0; margin-bottom: 12px;">
                            <span class="dashicons dashicons-database-view"></span>
                            <?php echo esc_html__('Estado de tablas locales', 'eipsi-forms'); ?>
                        </h4>
                                            
                        <!-- RCT Configs Table -->
                        <div class="status-detail-row" style="margin-bottom: 8px;">
                            <span class="detail-label" style="min-width: 200px;">
                                <?php echo esc_html__('RCT Configs:', 'eipsi-forms'); ?>
                            </span>
                            <span class="detail-value">
                                <?php if ($local_table_status['randomization_configs_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Existe', 'eipsi-forms'); ?>
                                    <span style="color: #666; font-size: 12px;">
                                        (<?php echo number_format_i18n($local_table_status['randomization_configs_table']['row_count']); ?> <?php echo esc_html__('configs', 'eipsi-forms'); ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                                    <?php echo esc_html__('No existe', 'eipsi-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <!-- RCT Assignments Table -->
                        <div class="status-detail-row" style="margin-bottom: 8px;">
                            <span class="detail-label" style="min-width: 200px;">
                                <?php echo esc_html__('RCT Assignments:', 'eipsi-forms'); ?>
                            </span>
                            <span class="detail-value">
                                <?php if ($local_table_status['randomization_assignments_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Existe', 'eipsi-forms'); ?>
                                    <span style="color: #666; font-size: 12px;">
                                        (<?php echo number_format_i18n($local_table_status['randomization_assignments_table']['row_count']); ?> <?php echo esc_html__('asignaciones', 'eipsi-forms'); ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                                    <?php echo esc_html__('No existe', 'eipsi-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <!-- Longitudinal Tables -->
                        <?php if (!empty($local_table_status['longitudinal_tables'])): ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <h5 style="margin-top: 0; margin-bottom: 10px; color: #666;">
                                <?php echo esc_html__('Tablas longitudinales (v1.4.0+)', 'eipsi-forms'); ?>
                            </h5>
                            <?php foreach ($local_table_status['longitudinal_tables'] as $key => $table_info): ?>
                            <div class="status-detail-row" style="margin-bottom: 6px;">
                                <span class="detail-label" style="min-width: 200px; font-size: 12px;">
                                    <?php 
                                    $table_labels = array(
                                        'survey_studies' => __('Estudios:', 'eipsi-forms'),
                                        'survey_participants' => __('Participantes:', 'eipsi-forms'),
                                        'survey_sessions' => __('Sesiones:', 'eipsi-forms'),
                                        'survey_waves' => __('Tomas:', 'eipsi-forms'),
                                        'survey_assignments' => __('Asignaciones:', 'eipsi-forms'),
                                        'survey_magic_links' => __('Magic Links:', 'eipsi-forms'),
                                        'survey_email_log' => __('Email Log:', 'eipsi-forms'),
                                        'survey_audit_log' => __('Audit Log:', 'eipsi-forms'),
                                    );
                                    echo esc_html($table_labels[$key] ?? $key . ':');
                                    ?>
                                </span>
                                <span class="detail-value" style="font-size: 12px;">
                                    <?php if ($table_info['exists']): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 14px;"></span>
                                        <span style="color: #666;">
                                            (<?php echo number_format_i18n($table_info['row_count']); ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-dismiss" style="color: #dc3232; font-size: 14px;"></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($status['last_error'])): ?>
                    <div class="eipsi-error-box" style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ff9800; border-radius: 4px;">
                        <h4 style="margin: 0 0 8px 0; color: #856404;">
                            <span class="dashicons dashicons-warning" style="color: #ff9800;"></span>
                            <?php echo esc_html__('Fallback Mode Active', 'eipsi-forms'); ?>
                        </h4>
                        <p style="margin: 0 0 8px 0; color: #856404;">
                            <?php echo esc_html__('Recent submissions were saved to the WordPress database because the external database was unavailable.', 'eipsi-forms'); ?>
                        </p>
                        <div class="status-detail-row" style="font-size: 13px;">
                            <span class="detail-label"><?php echo esc_html__('Last Error:', 'eipsi-forms'); ?></span>
                            <span class="detail-value" style="color: #d32f2f; font-family: monospace;"><?php echo esc_html($status['last_error']); ?></span>
                        </div>
                        <?php if (!empty($status['last_error_code'])): ?>
                        <div class="status-detail-row" style="font-size: 13px;">
                            <span class="detail-label"><?php echo esc_html__('Error Code:', 'eipsi-forms'); ?></span>
                            <span class="detail-value" style="font-family: monospace;"><?php echo esc_html($status['last_error_code']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($status['last_error_time'])): ?>
                        <div class="status-detail-row" style="font-size: 13px;">
                            <span class="detail-label"><?php echo esc_html__('Occurred:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $status['last_error_time'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    </div>

                    <!-- Schema Verification Status -->
                    <div class="eipsi-schema-status-box" style="margin-top: 20px;">
                    <h3>
                        <span class="dashicons dashicons-database-view"></span>
                        <?php echo esc_html__('Database Schema Status', 'eipsi-forms'); ?>
                    </h3>
                    <?php
                    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-manager.php';
                    $schema_status = EIPSI_Database_Schema_Manager::get_verification_status();
                    ?>
                    <div class="eipsi-schema-details">
                        <?php if (!empty($schema_status['last_verified'])): ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Last Verified:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $schema_status['last_verified'])); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($schema_status['last_sync_result'])): ?>
                        <?php $sync = $schema_status['last_sync_result']; ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Results Table:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php if ($sync['results_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Exists', 'eipsi-forms'); ?>
                                    <?php if ($sync['results_table']['created']): ?>
                                        <em>(<?php echo esc_html__('created during last sync', 'eipsi-forms'); ?>)</em>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                    <?php echo esc_html__('Missing', 'eipsi-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Events Table:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php if ($sync['events_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Exists', 'eipsi-forms'); ?>
                                    <?php if ($sync['events_table']['created']): ?>
                                        <em>(<?php echo esc_html__('created during last sync', 'eipsi-forms'); ?>)</em>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                    <?php echo esc_html__('Missing', 'eipsi-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('RCT Configs Table:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php if ($sync['randomization_configs_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Exists', 'eipsi-forms'); ?>
                                    <?php if ($sync['randomization_configs_table']['created']): ?>
                                        <em>(<?php echo esc_html__('created during last sync', 'eipsi-forms'); ?>)</em>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                    <?php echo esc_html__('Missing', 'eipsi-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('RCT Assignments Table:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php if ($sync['randomization_assignments_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Exists', 'eipsi-forms'); ?>
                                    <?php if ($sync['randomization_assignments_table']['created']): ?>
                                        <em>(<?php echo esc_html__('created during last sync', 'eipsi-forms'); ?>)</em>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                    <?php echo esc_html__('Missing', 'eipsi-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (!empty($sync['results_table']['columns_added']) || !empty($sync['events_table']['columns_added']) || !empty($sync['randomization_configs_table']['columns_added']) || !empty($sync['randomization_assignments_table']['columns_added'])): ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Columns Added:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php
                                $total_columns = count($sync['results_table']['columns_added']) + count($sync['events_table']['columns_added']) + count($sync['randomization_configs_table']['columns_added']) + count($sync['randomization_assignments_table']['columns_added']);
                                echo esc_html(sprintf(__('%d columns synced', 'eipsi-forms'), $total_columns));
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($sync['randomization_assignments_table']['columns_added']) && in_array('config_id', $sync['randomization_assignments_table']['columns_added'])): ?>
                        <div class="status-detail-row" style="background-color: #e8f5e8; padding: 8px; border-radius: 4px; margin-top: 8px;">
                            <span class="detail-label" style="font-weight: bold;"><?php echo esc_html__('🔧 CRITICAL FIX APPLIED:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php echo esc_html__('config_id column added to RCT Assignments table - randomization queries now functional', 'eipsi-forms'); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($status['connected']): ?>
                        <!-- External Database Schema Verification -->
                        <div style="margin-bottom: 15px;">
                            <h4 style="margin-top: 0; margin-bottom: 10px; color: #666;">
                                <?php echo esc_html__('Base de datos externa:', 'eipsi-forms'); ?>
                            </h4>
                            <button type="button" id="eipsi-verify-schema" class="button button-secondary" style="margin-top: 10px;">
                                <span class="dashicons dashicons-update"></span>
                                <?php echo esc_html__('Verify & Repair Schema', 'eipsi-forms'); ?>
                            </button>
                            <p class="description">
                                <?php echo esc_html__('Manually verify database schema and create any missing tables or columns.', 'eipsi-forms'); ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <!-- Local WordPress Database Schema Verification -->
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <h4 style="margin-top: 0; margin-bottom: 10px; color: #666;">
                                <?php echo esc_html__('Base de datos local de WordPress:', 'eipsi-forms'); ?>
                            </h4>
                            <button type="button" id="eipsi-verify-local-schema" class="button button-secondary" style="margin-top: 10px;">
                                <span class="dashicons dashicons-update"></span>
                                <?php echo esc_html__('Verificar y reparar esquema local', 'eipsi-forms'); ?>
                            </button>
                            <p class="description">
                                <?php echo esc_html__('Verifica el esquema de la base de datos de WordPress y crea las tablas o columnas que falten.', 'eipsi-forms'); ?>
                            </p>

                            <!-- Local Schema Results Container -->
                            <div id="eipsi-local-schema-results" style="display: none; margin-top: 15px; padding: 12px; background: #f0f6fc; border-radius: 4px; border-left: 4px solid #2271b1;">
                                <h4 style="margin-top: 0; margin-bottom: 10px;"><?php echo esc_html__('Resultados de verificación', 'eipsi-forms'); ?></h4>
                                <div id="eipsi-local-schema-content"></div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <!-- Table Status Section -->
                    <div class="eipsi-table-status-box" style="margin-top: 20px;">
                        <h3>
                            <span class="dashicons dashicons-database-view"></span>
                            <?php echo esc_html__('Database Table Status', 'eipsi-forms'); ?>
                        </h3>

                        <div id="eipsi-table-status-content">
                            <?php if ($status['connected']): ?>
                            <!-- External Database Table Status -->
                            <div style="margin-bottom: 20px;">
                                <h4 style="margin-top: 0; margin-bottom: 10px; color: #666;">
                                    <?php echo esc_html__('Base de datos externa:', 'eipsi-forms'); ?>
                                </h4>
                                <p class="description">
                                    <?php echo esc_html__('Check if required database tables exist in the external database.', 'eipsi-forms'); ?>
                                </p>
                                <button type="button" id="eipsi-check-table-status" class="button button-secondary" style="margin-top: 10px;">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php echo esc_html__('Check Table Status', 'eipsi-forms'); ?>
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Local WordPress Database Table Status -->
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <h4 style="margin-top: 0; margin-bottom: 10px; color: #666;">
                                    <?php echo esc_html__('Base de datos local de WordPress:', 'eipsi-forms'); ?>
                                </h4>
                                <p class="description">
                                    <?php echo esc_html__('Verifica el estado detallado de las tablas en la base de datos de WordPress.', 'eipsi-forms'); ?>
                                </p>
                                <button type="button" id="eipsi-check-local-table-status" class="button button-secondary" style="margin-top: 10px;">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php echo esc_html__('Verificar estado de tablas locales', 'eipsi-forms'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Table Status Results (populated via AJAX) -->
                        <div id="eipsi-table-status-results" style="display: none; margin-top: 15px;"></div>
                    </div>

                    <!-- Help Section -->
                    <div class="eipsi-help-box">
                    <h3><?php echo esc_html__('Setup Instructions', 'eipsi-forms'); ?></h3>
                    <ol>
                        <li><?php echo esc_html__('Enter your MySQL database credentials above', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('Click "Test Connection" to verify the credentials work', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('If the test is successful, click "Save Configuration"', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('All new form submissions will be stored in the external database', 'eipsi-forms'); ?></li>
                    </ol>
                    
                    <h3><?php echo esc_html__('Important Notes', 'eipsi-forms'); ?></h3>
                    <ul>
                        <li><?php echo esc_html__('The plugin will automatically create the required table and columns in the external database if they are missing', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('Passwords are encrypted before storage using WordPress security functions', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('Test the connection before saving to verify credentials and schema', 'eipsi-forms'); ?></li>
                        <li><strong><?php echo esc_html__('Automatic Fallback:', 'eipsi-forms'); ?></strong> <?php echo esc_html__('If the external database becomes unavailable, submissions will automatically be saved to the WordPress database without blocking the user', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('Admin notifications will alert you when fallback mode is active so you can investigate the issue', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('Enable WP_DEBUG to see detailed error logs for troubleshooting database issues', 'eipsi-forms'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SMTP Configuration Section -->
        <div class="eipsi-config-container" style="margin-top: 30px;">
            <div class="eipsi-config-form-section">
                <h2><?php echo esc_html__('Configuración SMTP', 'eipsi-forms'); ?></h2>
                <p class="description">
                    <?php echo esc_html__('Configura tu servidor de correo para que los recordatorios clínicos salgan desde tu cuenta sin depender de plugins externos.', 'eipsi-forms'); ?>
                </p>

                <form id="eipsi-smtp-config-form" class="eipsi-db-form">
                    <input type="hidden" id="eipsi_smtp_config_nonce" value="<?php echo esc_attr(wp_create_nonce('eipsi_admin_nonce')); ?>">

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="smtp_host">
                                        <?php echo esc_html__('Servidor SMTP', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="smtp_host"
                                        name="smtp_host"
                                        class="regular-text"
                                        value="<?php echo esc_attr($smtp_host); ?>"
                                        placeholder="smtp.gmail.com"
                                        required>
                                    <p class="description">
                                        <?php echo esc_html__('Dominio o IP del servidor SMTP.', 'eipsi-forms'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="smtp_port">
                                        <?php echo esc_html__('Puerto SMTP', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="number"
                                        id="smtp_port"
                                        name="smtp_port"
                                        class="regular-text"
                                        value="<?php echo esc_attr($smtp_port); ?>"
                                        placeholder="587"
                                        min="1"
                                        max="65535"
                                        required>
                                    <p class="description">
                                        <?php echo esc_html__('Puerto habitual: 587 (TLS) o 465 (SSL).', 'eipsi-forms'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="smtp_user">
                                        <?php echo esc_html__('Usuario SMTP', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="email"
                                        id="smtp_user"
                                        name="smtp_user"
                                        class="regular-text"
                                        value="<?php echo esc_attr($smtp_user); ?>"
                                        placeholder="tu-correo@dominio.com"
                                        required>
                                    <p class="description">
                                        <?php echo esc_html__('Correo completo usado para autenticar en el servidor.', 'eipsi-forms'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="smtp_password">
                                        <?php echo esc_html__('Contraseña SMTP', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="password"
                                        id="smtp_password"
                                        name="smtp_password"
                                        class="regular-text"
                                        placeholder="<?php echo esc_attr__('Contraseña de aplicación o SMTP', 'eipsi-forms'); ?>"
                                        <?php echo $smtp_config ? '' : 'required'; ?>>
                                    <p class="description">
                                        <?php
                                        if ($smtp_config) {
                                            echo esc_html__('Deja en blanco para mantener la contraseña actual.', 'eipsi-forms');
                                        } else {
                                            echo esc_html__('Usa una contraseña de aplicación si tu proveedor lo requiere.', 'eipsi-forms');
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="smtp_encryption">
                                        <?php echo esc_html__('Seguridad', 'eipsi-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <select id="smtp_encryption" name="smtp_encryption" class="regular-text">
                                        <?php foreach ($smtp_encryption_labels as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($smtp_encryption, $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php echo esc_html__('Selecciona el tipo de cifrado que requiere tu servidor.', 'eipsi-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="eipsi-form-actions">
                        <button type="button" id="eipsi-test-smtp" class="button button-secondary">
                            <span class="dashicons dashicons-email"></span>
                            <?php echo esc_html__('Probar SMTP', 'eipsi-forms'); ?>
                        </button>

                        <button type="submit" id="eipsi-save-smtp-config" class="button button-primary" disabled>
                            <span class="dashicons dashicons-yes"></span>
                            <?php echo esc_html__('Guardar configuración', 'eipsi-forms'); ?>
                        </button>

                        <?php if ($smtp_config): ?>
                        <button type="button" id="eipsi-disable-smtp" class="button button-link-delete">
                            <?php echo esc_html__('Desactivar SMTP', 'eipsi-forms'); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <p class="description" style="margin-top: 12px;">
                        <?php echo esc_html__('El correo de prueba se envía al email del investigador o al email del administrador del sitio.', 'eipsi-forms'); ?>
                    </p>

                    <div id="eipsi-smtp-message-container" role="alert" aria-live="polite" style="display: none;"></div>
                </form>
            </div>

            <div class="eipsi-status-section">
                <h2><?php echo esc_html__('Estado de correo', 'eipsi-forms'); ?></h2>

                <div id="eipsi-smtp-status-box" class="eipsi-status-box">
                    <div class="eipsi-status-indicator">
                        <?php if ($smtp_config): ?>
                            <span class="status-icon status-connected"></span>
                            <span class="status-text"><?php echo esc_html__('SMTP activo', 'eipsi-forms'); ?></span>
                        <?php else: ?>
                            <span class="status-icon status-disconnected"></span>
                            <span class="status-text"><?php echo esc_html__('SMTP inactivo', 'eipsi-forms'); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($smtp_config): ?>
                    <div class="eipsi-status-details">
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Servidor:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($smtp_config['host']); ?></span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Puerto:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($smtp_config['port']); ?></span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Usuario:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($smtp_config['user']); ?></span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Seguridad:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($smtp_encryption_labels[$smtp_config['encryption']] ?? $smtp_config['encryption']); ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="eipsi-status-message">
                        <p><?php echo esc_html__('No hay configuración SMTP activa. Los correos se enviarán con wp_mail().', 'eipsi-forms'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Data Reset Section (Emergency Tool) -->
        <div class="eipsi-config-container" style="margin-top: 30px;">
            <div class="eipsi-danger-zone-section" style="border: 2px solid #d32f2f; border-radius: 8px; padding: 24px; background: #fff;">
                <h2 style="color: #d32f2f; margin-top: 0;">
                    <span class="dashicons dashicons-warning" style="color: #d32f2f;"></span>
                    <?php echo esc_html__('Advanced Tools — Data Reset', 'eipsi-forms'); ?>
                </h2>
                
                <p style="margin-bottom: 16px; font-size: 15px; line-height: 1.6;">
                    <?php echo esc_html__('Use this emergency tool to permanently delete all clinical data stored by EIPSI Forms. This is useful for clearing test data before starting real clinical use.', 'eipsi-forms'); ?>
                </p>
                
                <div style="background: #fff3cd; border-left: 4px solid #ff9800; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
                    <p style="margin: 0; color: #856404; font-weight: 600;">
                        <span class="dashicons dashicons-info" style="color: #ff9800;"></span>
                        <?php echo esc_html__('What will be deleted:', 'eipsi-forms'); ?>
                    </p>
                    <ul style="margin: 8px 0 0 24px; color: #856404;">
                        <li><?php echo esc_html__('All form responses from all participants', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('All session data and event logs', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('All metadata (durations, quality flags, timestamps)', 'eipsi-forms'); ?></li>
                    </ul>
                    <p style="margin: 8px 0 0 0; color: #856404; font-weight: 600;">
                        <?php echo esc_html__('What will NOT be deleted:', 'eipsi-forms'); ?>
                    </p>
                    <ul style="margin: 8px 0 0 24px; color: #856404;">
                        <li><?php echo esc_html__('Form definitions (structure, questions, blocks)', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('Plugin configuration and database settings', 'eipsi-forms'); ?></li>
                        <li><?php echo esc_html__('Privacy and design presets', 'eipsi-forms'); ?></li>
                    </ul>
                </div>
                
                <div style="background: #ffebee; border-left: 4px solid #d32f2f; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
                    <p style="margin: 0; color: #c62828; font-weight: 700; font-size: 15px;">
                        <span class="dashicons dashicons-dismiss" style="color: #d32f2f;"></span>
                        <?php echo esc_html__('⚠️ THIS ACTION CANNOT BE UNDONE', 'eipsi-forms'); ?>
                    </p>
                    <p style="margin: 8px 0 0 0; color: #c62828;">
                        <?php echo esc_html__('All clinical data will be permanently deleted. Make sure to export or backup your data before proceeding if you need to keep it.', 'eipsi-forms'); ?>
                    </p>
                </div>
                
                <button type="button" id="eipsi-delete-all-data" class="button" style="background: #d32f2f; color: white; border-color: #b71c1c; padding: 8px 24px; height: auto; font-size: 15px; font-weight: 600;">
                    <span class="dashicons dashicons-trash" style="margin-top: 4px;"></span>
                    <?php echo esc_html__('Delete All Clinical Data', 'eipsi-forms'); ?>
                </button>
                <input type="hidden" id="eipsi-delete-data-nonce" value="<?php echo esc_attr(wp_create_nonce('eipsi_delete_all_data')); ?>">
                
                <p class="description" style="margin-top: 12px; color: #666; font-size: 13px;">
                    <?php echo esc_html__('You will be asked to confirm this action before any data is deleted.', 'eipsi-forms'); ?>
                </p>
            </div>
        </div>
    </div>
    <?php
}
