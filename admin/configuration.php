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
        wp_die(__('You do not have sufficient permissions to access this page.', 'vas-dinamico-forms'));
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
        <h1><?php echo esc_html__('Database Configuration', 'vas-dinamico-forms'); ?></h1>
        <p class="description">
            <?php echo esc_html__('Configure an external MySQL database to store form submission data. If no external database is configured, data will be stored in the default WordPress database.', 'vas-dinamico-forms'); ?>
        </p>
        
        <!-- Prominent Database Indicator -->
        <div class="eipsi-db-indicator-banner">
            <div class="eipsi-db-indicator-content">
                <div class="eipsi-db-indicator-icon">
                    <span class="dashicons dashicons-database"></span>
                </div>
                <div class="eipsi-db-indicator-info">
                    <div class="eipsi-db-indicator-label"><?php echo esc_html__('Current Storage Mode:', 'vas-dinamico-forms'); ?></div>
                    <div class="eipsi-db-indicator-value">
                        <?php if ($status['connected']): ?>
                            <span class="eipsi-db-badge eipsi-db-badge--dual" style="background-color: #10b981; color: white; padding: 6px 12px; border-radius: 4px; font-weight: 600;">
                                <span class="dashicons dashicons-update" style="font-size: 16px;"></span>
                                <?php echo esc_html__('Dual Storage Active', 'vas-dinamico-forms'); ?>
                            </span>
                            <div style="margin-top: 8px; font-size: 13px; color: #64748b;">
                                <?php echo esc_html__('Submissions saved to BOTH:', 'vas-dinamico-forms'); ?>
                                <strong><?php echo esc_html__('WordPress DB', 'vas-dinamico-forms'); ?></strong>
                                <?php echo esc_html__(' + ', 'vas-dinamico-forms'); ?>
                                <strong><?php echo esc_html($status['db_name']); ?></strong>
                            </div>
                        <?php else: ?>
                            <span class="eipsi-db-badge eipsi-db-badge--wordpress">
                                <span class="dashicons dashicons-wordpress"></span>
                                <?php echo esc_html__('WordPress Database Only', 'vas-dinamico-forms'); ?>
                            </span>
                            <div style="margin-top: 8px; font-size: 13px; color: #64748b;">
                                <?php echo esc_html__('Configure an external database below to enable dual storage', 'vas-dinamico-forms'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($status['connected']): ?>
                <div class="eipsi-db-indicator-status">
                    <span class="eipsi-status-dot eipsi-status-dot--connected"></span>
                    <span class="eipsi-status-text"><?php echo esc_html__('Connected', 'vas-dinamico-forms'); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($status['connected']): ?>
            <div style="margin-top: 12px; padding: 12px; background-color: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">
                <p style="margin: 0; font-size: 13px; color: #166534;">
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                    <strong><?php echo esc_html__('Zero Data Loss Protection:', 'vas-dinamico-forms'); ?></strong>
                    <?php echo esc_html__('All submissions are first saved to WordPress database (guaranteed), then automatically replicated to external database. If external database becomes unavailable, submissions continue saving to WordPress database without interruption.', 'vas-dinamico-forms'); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="eipsi-config-container">
            <!-- Configuration Form -->
            <div class="eipsi-config-form-section">
                <h2><?php echo esc_html__('Database Connection Settings', 'vas-dinamico-forms'); ?></h2>
                
                <form id="eipsi-db-config-form" class="eipsi-db-form">
                    <input type="hidden" id="eipsi_db_config_nonce" value="<?php echo esc_attr(wp_create_nonce('eipsi_admin_nonce')); ?>"><?php // Use eipsi_admin_nonce for consistency ?>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="db_host">
                                        <?php echo esc_html__('Host', 'vas-dinamico-forms'); ?>
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
                                        <?php echo esc_html__('Database server hostname or IP address (e.g., localhost, 192.168.1.100)', 'vas-dinamico-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="db_user">
                                        <?php echo esc_html__('Username', 'vas-dinamico-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="db_user"
                                        name="db_user"
                                        class="regular-text"
                                        value="<?php echo esc_attr($current_user); ?>"
                                        placeholder="<?php echo esc_attr__('MySQL username', 'vas-dinamico-forms'); ?>"
                                        aria-describedby="db_user_desc"
                                        required>
                                    <p class="description" id="db_user_desc">
                                        <?php echo esc_html__('MySQL user with access to the database', 'vas-dinamico-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="db_password">
                                        <?php echo esc_html__('Password', 'vas-dinamico-forms'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input type="password"
                                        id="db_password"
                                        name="db_password"
                                        class="regular-text"
                                        placeholder="<?php echo esc_attr__('MySQL password', 'vas-dinamico-forms'); ?>"
                                        aria-describedby="db_password_desc"
                                        <?php echo $credentials ? '' : 'required'; ?>>
                                    <p class="description" id="db_password_desc">
                                        <?php
                                        if ($credentials) {
                                            echo esc_html__('Leave blank to keep existing password', 'vas-dinamico-forms');
                                        } else {
                                            echo esc_html__('MySQL user password (will be encrypted)', 'vas-dinamico-forms');
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="db_name">
                                        <?php echo esc_html__('Database Name', 'vas-dinamico-forms'); ?>
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
                                        <?php echo esc_html__('Name of the database to store form submissions', 'vas-dinamico-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="eipsi-form-actions">
                        <button type="button" id="eipsi-test-connection" class="button button-secondary">
                            <span class="dashicons dashicons-database"></span>
                            <?php echo esc_html__('Test Connection', 'vas-dinamico-forms'); ?>
                        </button>
                        
                        <button type="submit" id="eipsi-save-config" class="button button-primary" disabled>
                            <span class="dashicons dashicons-yes"></span>
                            <?php echo esc_html__('Save Configuration', 'vas-dinamico-forms'); ?>
                        </button>
                        
                        <?php if ($credentials): ?>
                        <button type="button" id="eipsi-disable-external-db" class="button button-link-delete">
                            <?php echo esc_html__('Disable External Database', 'vas-dinamico-forms'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div id="eipsi-message-container" role="alert" aria-live="polite" style="display: none;"></div>
                </form>
            </div>
            
            <!-- Status Indicator -->
            <div class="eipsi-status-section">
                <h2><?php echo esc_html__('Database Connection Status', 'vas-dinamico-forms'); ?></h2>
                
                <div id="eipsi-status-box" class="eipsi-status-box">
                    <!-- WordPress Database Status (Always Active) -->
                    <div style="margin-bottom: 20px; padding: 12px; background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 4px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span class="dashicons dashicons-wordpress" style="color: #10b981; font-size: 20px;"></span>
                            <strong style="color: #166534;"><?php echo esc_html__('WordPress Database', 'vas-dinamico-forms'); ?></strong>
                            <span style="margin-left: auto; padding: 4px 8px; background-color: #10b981; color: white; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                <?php echo esc_html__('ACTIVE', 'vas-dinamico-forms'); ?>
                            </span>
                        </div>
                        <div class="eipsi-status-details">
                            <div class="status-detail-row">
                                <span class="detail-label"><?php echo esc_html__('Database Name:', 'vas-dinamico-forms'); ?></span>
                                <span class="detail-value"><?php echo esc_html(DB_NAME); ?></span>
                            </div>
                            <div class="status-detail-row">
                                <span class="detail-label"><?php echo esc_html__('Status:', 'vas-dinamico-forms'); ?></span>
                                <span class="detail-value" style="color: #10b981;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php echo esc_html__('Connected (Primary Storage)', 'vas-dinamico-forms'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- External Database Status -->
                    <div style="padding: 12px; background-color: <?php echo $status['connected'] ? '#f0fdf4' : '#f8f9fa'; ?>; border: 1px solid <?php echo $status['connected'] ? '#86efac' : '#e2e8f0'; ?>; border-radius: 4px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span class="dashicons dashicons-admin-site-alt3" style="color: <?php echo $status['connected'] ? '#10b981' : '#94a3b8'; ?>; font-size: 20px;"></span>
                            <strong style="color: <?php echo $status['connected'] ? '#166534' : '#64748b'; ?>;"><?php echo esc_html__('External Database', 'vas-dinamico-forms'); ?></strong>
                            <?php if ($status['connected']): ?>
                                <span style="margin-left: auto; padding: 4px 8px; background-color: #10b981; color: white; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html__('ACTIVE', 'vas-dinamico-forms'); ?>
                                </span>
                            <?php else: ?>
                                <span style="margin-left: auto; padding: 4px 8px; background-color: #94a3b8; color: white; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html__('NOT CONFIGURED', 'vas-dinamico-forms'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($status['connected']): ?>
                        <div class="eipsi-status-details">
                            <div class="status-detail-row">
                                <span class="detail-label"><?php echo esc_html__('Database Name:', 'vas-dinamico-forms'); ?></span>
                                <span class="detail-value"><?php echo esc_html($status['db_name']); ?></span>
                            </div>
                            <div class="status-detail-row">
                                <span class="detail-label"><?php echo esc_html__('Status:', 'vas-dinamico-forms'); ?></span>
                                <span class="detail-value" style="color: #10b981;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php echo esc_html__('Connected (Replicated Storage)', 'vas-dinamico-forms'); ?>
                                </span>
                            </div>
                            <div class="status-detail-row">
                                <span class="detail-label"><?php echo esc_html__('Records:', 'vas-dinamico-forms'); ?></span>
                                <span class="detail-value"><?php echo number_format_i18n($status['record_count']); ?></span>
                            </div>
                            <?php if (!empty($status['last_updated'])): ?>
                            <div class="status-detail-row">
                                <span class="detail-label"><?php echo esc_html__('Last Updated:', 'vas-dinamico-forms'); ?></span>
                                <span class="detail-value"><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $status['last_updated'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="eipsi-status-message">
                            <p style="color: #64748b; margin: 8px 0 0 0;"><?php echo esc_html__('Configure external database credentials below to enable dual storage mode.', 'vas-dinamico-forms'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($status['last_error'])): ?>
                    <div class="eipsi-error-box" style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ff9800; border-radius: 4px;">
                        <h4 style="margin: 0 0 8px 0; color: #856404;">
                            <span class="dashicons dashicons-warning" style="color: #ff9800;"></span>
                            <?php echo esc_html__('Fallback Mode Active', 'vas-dinamico-forms'); ?>
                        </h4>
                        <p style="margin: 0 0 8px 0; color: #856404;">
                            <?php echo esc_html__('Recent submissions were saved to the WordPress database because the external database was unavailable.', 'vas-dinamico-forms'); ?>
                        </p>
                        <div class="status-detail-row" style="font-size: 13px;">
                            <span class="detail-label"><?php echo esc_html__('Last Error:', 'vas-dinamico-forms'); ?></span>
                            <span class="detail-value" style="color: #d32f2f; font-family: monospace;"><?php echo esc_html($status['last_error']); ?></span>
                        </div>
                        <?php if (!empty($status['last_error_code'])): ?>
                        <div class="status-detail-row" style="font-size: 13px;">
                            <span class="detail-label"><?php echo esc_html__('Error Code:', 'vas-dinamico-forms'); ?></span>
                            <span class="detail-value" style="font-family: monospace;"><?php echo esc_html($status['last_error_code']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($status['last_error_time'])): ?>
                        <div class="status-detail-row" style="font-size: 13px;">
                            <span class="detail-label"><?php echo esc_html__('Occurred:', 'vas-dinamico-forms'); ?></span>
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
                        <?php echo esc_html__('Database Schema Status', 'vas-dinamico-forms'); ?>
                    </h3>
                    <?php
                    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database-schema-manager.php';
                    $schema_status = EIPSI_Database_Schema_Manager::get_verification_status();
                    ?>
                    <div class="eipsi-schema-details">
                        <?php if (!empty($schema_status['last_verified'])): ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Last Verified:', 'vas-dinamico-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $schema_status['last_verified'])); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($schema_status['last_sync_result'])): ?>
                        <?php $sync = $schema_status['last_sync_result']; ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Results Table:', 'vas-dinamico-forms'); ?></span>
                            <span class="detail-value">
                                <?php if ($sync['results_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Exists', 'vas-dinamico-forms'); ?>
                                    <?php if ($sync['results_table']['created']): ?>
                                        <em>(<?php echo esc_html__('created during last sync', 'vas-dinamico-forms'); ?>)</em>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                    <?php echo esc_html__('Missing', 'vas-dinamico-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Events Table:', 'vas-dinamico-forms'); ?></span>
                            <span class="detail-value">
                                <?php if ($sync['events_table']['exists']): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    <?php echo esc_html__('Exists', 'vas-dinamico-forms'); ?>
                                    <?php if ($sync['events_table']['created']): ?>
                                        <em>(<?php echo esc_html__('created during last sync', 'vas-dinamico-forms'); ?>)</em>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                                    <?php echo esc_html__('Missing', 'vas-dinamico-forms'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (!empty($sync['results_table']['columns_added']) || !empty($sync['events_table']['columns_added'])): ?>
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Columns Added:', 'vas-dinamico-forms'); ?></span>
                            <span class="detail-value">
                                <?php
                                $total_columns = count($sync['results_table']['columns_added']) + count($sync['events_table']['columns_added']);
                                echo esc_html(sprintf(__('%d columns synced', 'vas-dinamico-forms'), $total_columns));
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($status['connected']): ?>
                        <button type="button" id="eipsi-verify-schema" class="button button-secondary" style="margin-top: 10px;">
                            <span class="dashicons dashicons-update"></span>
                            <?php echo esc_html__('Verify & Repair Schema', 'vas-dinamico-forms'); ?>
                        </button>
                        <p class="description">
                            <?php echo esc_html__('Manually verify database schema and create any missing tables or columns.', 'vas-dinamico-forms'); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    </div>

                    <!-- Table Status Section -->
                    <div class="eipsi-table-status-box" style="margin-top: 20px;">
                        <h3>
                            <span class="dashicons dashicons-database-view"></span>
                            <?php echo esc_html__('Database Table Status', 'vas-dinamico-forms'); ?>
                        </h3>
                        
                        <div id="eipsi-table-status-content">
                            <?php if ($status['connected']): ?>
                                <p class="description">
                                    <?php echo esc_html__('Check if required database tables exist in the external database.', 'vas-dinamico-forms'); ?>
                                </p>
                                <button type="button" id="eipsi-check-table-status" class="button button-secondary" style="margin-top: 10px;">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php echo esc_html__('Check Table Status', 'vas-dinamico-forms'); ?>
                                </button>
                            <?php else: ?>
                                <p class="description" style="color: #64748b;">
                                    <?php echo esc_html__('Configure and connect to an external database to check table status.', 'vas-dinamico-forms'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Table Status Results (populated via AJAX) -->
                        <div id="eipsi-table-status-results" style="display: none; margin-top: 15px;"></div>
                    </div>

                    <!-- Help Section -->
                    <div class="eipsi-help-box">
                    <h3><?php echo esc_html__('Setup Instructions', 'vas-dinamico-forms'); ?></h3>
                    <ol>
                        <li><?php echo esc_html__('Enter your MySQL database credentials above', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('Click "Test Connection" to verify the credentials work', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('If the test is successful, click "Save Configuration"', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('All new form submissions will be saved to BOTH WordPress and external databases simultaneously', 'vas-dinamico-forms'); ?></li>
                    </ol>
                    
                    <h3><?php echo esc_html__('How Dual-Write Works', 'vas-dinamico-forms'); ?></h3>
                    <ul>
                        <li><strong><?php echo esc_html__('Step 1 - WordPress DB (Guaranteed):', 'vas-dinamico-forms'); ?></strong> <?php echo esc_html__('Every form submission is FIRST saved to the WordPress database. This is always successful and guarantees zero data loss.', 'vas-dinamico-forms'); ?></li>
                        <li><strong><?php echo esc_html__('Step 2 - External DB (Non-Blocking):', 'vas-dinamico-forms'); ?></strong> <?php echo esc_html__('The same submission is THEN replicated to the external database. If this fails, the submission still succeeded in Step 1.', 'vas-dinamico-forms'); ?></li>
                        <li><strong><?php echo esc_html__('Result:', 'vas-dinamico-forms'); ?></strong> <?php echo esc_html__('Under normal conditions, data is saved to BOTH databases. If external database fails, data is safely stored in WordPress database.', 'vas-dinamico-forms'); ?></li>
                    </ul>
                    
                    <h3><?php echo esc_html__('Important Notes', 'vas-dinamico-forms'); ?></h3>
                    <ul>
                        <li><?php echo esc_html__('The plugin will automatically create the required table and columns in the external database if they are missing', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('Passwords are encrypted before storage using WordPress security functions', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('Test the connection before saving to verify credentials and schema', 'vas-dinamico-forms'); ?></li>
                        <li><strong><?php echo esc_html__('Zero Data Loss Guarantee:', 'vas-dinamico-forms'); ?></strong> <?php echo esc_html__('Form submissions are never blocked or lost. WordPress database always receives the data first, then external database replication is attempted.', 'vas-dinamico-forms'); ?></li>
                        <li><strong><?php echo esc_html__('Automatic Schema Repair:', 'vas-dinamico-forms'); ?></strong> <?php echo esc_html__('If external database schema is outdated (missing columns), the plugin automatically repairs it and retries the insertion.', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('The status section above shows warnings when external database replication fails so you can investigate the issue', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('Enable WP_DEBUG to see detailed error logs for troubleshooting database issues', 'vas-dinamico-forms'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}
