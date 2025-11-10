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
                    <div class="eipsi-db-indicator-label"><?php echo esc_html__('Current Storage Location:', 'vas-dinamico-forms'); ?></div>
                    <div class="eipsi-db-indicator-value">
                        <?php if ($status['connected']): ?>
                            <span class="eipsi-db-badge eipsi-db-badge--external">
                                <span class="dashicons dashicons-admin-site-alt3"></span>
                                <?php echo esc_html__('External Database', 'vas-dinamico-forms'); ?>
                            </span>
                            <span class="eipsi-db-name"><?php echo esc_html($status['db_name']); ?></span>
                        <?php else: ?>
                            <span class="eipsi-db-badge eipsi-db-badge--wordpress">
                                <span class="dashicons dashicons-wordpress"></span>
                                <?php echo esc_html__('WordPress Database', 'vas-dinamico-forms'); ?>
                            </span>
                            <span class="eipsi-db-name"><?php echo esc_html(DB_NAME); ?></span>
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
                <h2><?php echo esc_html__('Connection Status', 'vas-dinamico-forms'); ?></h2>
                
                <div id="eipsi-status-box" class="eipsi-status-box">
                    <div class="eipsi-status-indicator">
                        <?php if ($status['connected']): ?>
                            <span class="status-icon status-connected"></span>
                            <span class="status-text"><?php echo esc_html__('Connected', 'vas-dinamico-forms'); ?></span>
                        <?php else: ?>
                            <span class="status-icon status-disconnected"></span>
                            <span class="status-text"><?php echo esc_html__('Disconnected', 'vas-dinamico-forms'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($status['connected']): ?>
                    <div class="eipsi-status-details">
                        <div class="status-detail-row">
                            <span class="detail-label"><?php echo esc_html__('Current Database:', 'vas-dinamico-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($status['db_name']); ?></span>
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
                        <p><?php echo esc_html__('No external database configured. Form submissions will be stored in the WordPress database.', 'vas-dinamico-forms'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Help Section -->
                <div class="eipsi-help-box">
                    <h3><?php echo esc_html__('Setup Instructions', 'vas-dinamico-forms'); ?></h3>
                    <ol>
                        <li><?php echo esc_html__('Enter your MySQL database credentials above', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('Click "Test Connection" to verify the credentials work', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('If the test is successful, click "Save Configuration"', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('All new form submissions will be stored in the external database', 'vas-dinamico-forms'); ?></li>
                    </ol>
                    
                    <h3><?php echo esc_html__('Important Notes', 'vas-dinamico-forms'); ?></h3>
                    <ul>
                        <li><?php echo esc_html__('The external database must have the same table structure as the WordPress database', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('Passwords are encrypted before storage using WordPress security functions', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('Test the connection before saving to prevent data loss', 'vas-dinamico-forms'); ?></li>
                        <li><?php echo esc_html__('If the external database becomes unavailable, submissions will fail', 'vas-dinamico-forms'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}
