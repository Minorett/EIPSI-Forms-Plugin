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
    
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
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
                    <div class="eipsi-status-message">
                        <p><?php echo esc_html__('No external database configured. Form submissions will be stored in the WordPress database.', 'eipsi-forms'); ?></p>
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
                    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database-schema-manager.php';
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
                        <?php if (!empty($sync['results_table']['columns_added']) || !empty($sync['events_table']['columns_added'])): ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Columns Added:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php
                                $total_columns = count($sync['results_table']['columns_added']) + count($sync['events_table']['columns_added']);
                                echo esc_html(sprintf(__('%d columns synced', 'eipsi-forms'), $total_columns));
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($status['connected']): ?>
                        <button type="button" id="eipsi-verify-schema" class="button button-secondary" style="margin-top: 10px;">
                            <span class="dashicons dashicons-update"></span>
                            <?php echo esc_html__('Verify & Repair Schema', 'eipsi-forms'); ?>
                        </button>
                        <p class="description">
                            <?php echo esc_html__('Manually verify database schema and create any missing tables or columns.', 'eipsi-forms'); ?>
                        </p>
                        <?php endif; ?>
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
                                <p class="description">
                                    <?php echo esc_html__('Check if required database tables exist in the external database.', 'eipsi-forms'); ?>
                                </p>
                                <button type="button" id="eipsi-check-table-status" class="button button-secondary" style="margin-top: 10px;">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php echo esc_html__('Check Table Status', 'eipsi-forms'); ?>
                                </button>
                            <?php else: ?>
                                <p class="description" style="color: #64748b;">
                                    <?php echo esc_html__('Configure and connect to an external database to check table status.', 'eipsi-forms'); ?>
                                </p>
                            <?php endif; ?>
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
