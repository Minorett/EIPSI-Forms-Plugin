<?php
/**
 * Plugin Name: EIPSI Forms
 * Plugin URI: https://enmediodelcontexto.com.ar
 * Description: Professional form builder with Gutenberg blocks, conditional logic, and Excel export capabilities.
 * Version: 1.3.0
 * Author: Mathias N. Rojas de la Fuente
 * Author URI: https://www.instagram.com/enmediodel.contexto/
 * Text Domain: eipsi-forms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: forms, contact-form, survey, quiz, poll, form-builder, gutenberg, blocks, admin-dashboard, excel-export, analytics
 * Stable tag: 1.3.0
 * 
 * @package EIPSI_Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EIPSI_FORMS_VERSION', '1.3.0');
define('EIPSI_FORMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EIPSI_FORMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EIPSI_FORMS_PLUGIN_FILE', __FILE__);
define('EIPSI_FORMS_SLUG', 'eipsi-forms');

require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/menu.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/results-page.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/export.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/handlers.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-manager.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/partial-responses.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/configuration.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-handlers.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/cron-handlers.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/study-close-handler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/completion-message-backend.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/form-library.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/form-library-tools.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/demo-templates.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/form-template-render.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/shortcodes.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'assets/js/eipsi-randomization-shortcode.php';

// Registrar el shortcode de aleatorización pública
add_action('init', function() {
    if (function_exists('eipsi_randomized_form_shortcode')) {
        add_shortcode('eipsi_randomized_form', 'eipsi_randomized_form_shortcode');
    }
    if (function_exists('eipsi_randomized_form_page_shortcode')) {
        add_shortcode('eipsi_randomized_form_page', 'eipsi_randomized_form_page_shortcode');
    }
});

/**
 * Crear página especial para acceso aleatorizado
 */
function eipsi_create_randomization_page() {
    // Buscar si ya existe
    $page = get_page_by_path('estudio-aleatorio');
    
    if (!$page) {
        // Crear página si no existe
        $page_id = wp_insert_post(array(
            'post_title' => __('Estudio Aleatorizado', 'eipsi-forms'),
            'post_name' => 'estudio-aleatorio',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '[eipsi_randomized_form_page]'
        ));
        
        if ($page_id && !is_wp_error($page_id)) {
            update_option('eipsi_randomization_page_id', $page_id);
            error_log('[EIPSI Forms] Página de aleatorización creada: /estudio-aleatorio/');
        }
    }
}

// Ejecutar en activación del plugin
add_action('eipsi_forms_activation', 'eipsi_create_randomization_page');

function eipsi_forms_activate() {
    global $wpdb;
    
    // Crear página de aleatorización
    eipsi_create_randomization_page();
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // === Cron Reminders Scheduling (Fase 2) ===
    // Schedule daily reminders
    if (!wp_next_scheduled('eipsi_send_take_reminders_daily')) {
        wp_schedule_event(time(), 'daily', 'eipsi_send_take_reminders_daily');
    }
    
    // Schedule weekly reminders
    if (!wp_next_scheduled('eipsi_send_take_reminders_weekly')) {
        wp_schedule_event(time(), 'weekly', 'eipsi_send_take_reminders_weekly');
    }
    
    // Create form results table
    $table_name = $wpdb->prefix . 'vas_form_results';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        form_id varchar(20) DEFAULT NULL,
        participant_id varchar(20) DEFAULT NULL,
        session_id varchar(255) DEFAULT NULL,
        participant varchar(255) DEFAULT NULL,
        interaction varchar(255) DEFAULT NULL,
        form_name varchar(255) NOT NULL,
        created_at datetime NOT NULL,
        submitted_at datetime DEFAULT NULL,
        device varchar(100) DEFAULT NULL,
        browser varchar(100) DEFAULT NULL,
        os varchar(100) DEFAULT NULL,
        screen_width int(11) DEFAULT NULL,
        duration int(11) DEFAULT NULL,
        duration_seconds decimal(8,3) DEFAULT NULL,
        start_timestamp_ms bigint(20) DEFAULT NULL,
        end_timestamp_ms bigint(20) DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        metadata LONGTEXT DEFAULT NULL,
        status enum('pending','submitted','error') DEFAULT 'submitted',
        form_responses longtext DEFAULT NULL,
        PRIMARY KEY (id),
        KEY form_name (form_name),
        KEY created_at (created_at),
        KEY form_id (form_id),
        KEY participant_id (participant_id),
        KEY session_id (session_id),
        KEY submitted_at (submitted_at),
        KEY form_participant (form_id, participant_id)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    
    // Create form events tracking table
    $events_table = $wpdb->prefix . 'vas_form_events';
    $sql_events = "CREATE TABLE IF NOT EXISTS $events_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        form_id varchar(255) NOT NULL DEFAULT '',
        session_id varchar(255) NOT NULL,
        event_type varchar(50) NOT NULL,
        page_number int(11) DEFAULT NULL,
        metadata text DEFAULT NULL,
        user_agent text DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY form_id (form_id),
        KEY session_id (session_id),
        KEY event_type (event_type),
        KEY created_at (created_at),
        KEY form_session (form_id, session_id)
    ) $charset_collate;";
    
    dbDelta($sql_events);
    
    // Create partial responses table for Save & Continue
    EIPSI_Partial_Responses::create_table();
    
    // Store schema version
    update_option('eipsi_db_schema_version', '1.2.2');
    
    // Log activation
    error_log('[EIPSI Forms] Plugin activated - Schema v1.2.2 installed');
}

register_activation_hook(__FILE__, 'eipsi_forms_activate');
register_deactivation_hook(__FILE__, 'eipsi_forms_deactivate');

/**
 * Add weekly schedule interval for WP-Cron
 * 
 * @param array $schedules
 * @return array
 */
add_filter('cron_schedules', function($schedules) {
    if (!isset($schedules['weekly'])) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'eipsi-forms'),
        );
    }
    return $schedules;
});

/**
 * Cleanup scheduled cron events on deactivation
 */
function eipsi_forms_deactivate() {
    wp_clear_scheduled_hook('eipsi_send_take_reminders_daily');
    wp_clear_scheduled_hook('eipsi_send_take_reminders_weekly');
}

// Handle unsubscribe link from emails (Fase 2)
add_action('wp_loaded', 'eipsi_handle_unsubscribe_request');
function eipsi_handle_unsubscribe_request() {
    if (isset($_GET['eipsi_unsubscribe']) && $_GET['eipsi_unsubscribe'] === '1') {
        eipsi_unsubscribe_reminders_handler();
    }
}

function eipsi_forms_upgrade_database() {
    global $wpdb;
    
    $db_version_key = 'eipsi_forms_db_version';
    $current_db_version = get_option($db_version_key, '1.0');
    $required_db_version = '1.4';
    
    if (version_compare($current_db_version, $required_db_version, '>=')) {
        return;
    }
    
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // Check if table exists before attempting upgrades
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    
    if (!$table_exists) {
        // Table doesn't exist, activation hook will create it
        update_option($db_version_key, $required_db_version);
        return;
    }
    
    // Define all potentially missing columns with their ALTER TABLE statements
    $columns_to_add = array(
        'form_id' => "ALTER TABLE {$table_name} ADD COLUMN form_id varchar(20) DEFAULT NULL AFTER id",
        'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id varchar(20) DEFAULT NULL AFTER form_id",
        'session_id' => "ALTER TABLE {$table_name} ADD COLUMN session_id varchar(255) DEFAULT NULL AFTER participant_id",
        'browser' => "ALTER TABLE {$table_name} ADD COLUMN browser varchar(100) DEFAULT NULL AFTER device",
        'os' => "ALTER TABLE {$table_name} ADD COLUMN os varchar(100) DEFAULT NULL AFTER browser",
        'screen_width' => "ALTER TABLE {$table_name} ADD COLUMN screen_width int(11) DEFAULT NULL AFTER os",
        'duration_seconds' => "ALTER TABLE {$table_name} ADD COLUMN duration_seconds decimal(8,3) DEFAULT NULL AFTER duration",
        'start_timestamp_ms' => "ALTER TABLE {$table_name} ADD COLUMN start_timestamp_ms bigint(20) DEFAULT NULL AFTER duration_seconds",
        'end_timestamp_ms' => "ALTER TABLE {$table_name} ADD COLUMN end_timestamp_ms bigint(20) DEFAULT NULL AFTER start_timestamp_ms",
        'metadata' => "ALTER TABLE {$table_name} ADD COLUMN metadata LONGTEXT DEFAULT NULL AFTER ip_address",
        'status' => "ALTER TABLE {$table_name} ADD COLUMN status enum('pending','submitted','error') DEFAULT 'submitted' AFTER metadata"
    );
    
    foreach ($columns_to_add as $column => $alter_sql) {
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                $column
            )
        );
        
        if (empty($column_exists)) {
            $wpdb->query($alter_sql);
            
            if ($wpdb->last_error) {
                error_log('EIPSI Forms: Failed to add column ' . $column . ' - ' . $wpdb->last_error);
            } else {
                error_log('EIPSI Forms: Successfully added column ' . $column);
            }
        }
    }
    
    // Add indexes if they don't exist
    $indexes_to_add = array(
        'form_id' => "ALTER TABLE {$table_name} ADD INDEX form_id (form_id)",
        'form_participant' => "ALTER TABLE {$table_name} ADD INDEX form_participant (form_id, participant_id)"
    );
    
    foreach ($indexes_to_add as $index_name => $alter_sql) {
        $index_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
                DB_NAME,
                $table_name,
                $index_name
            )
        );
        
        if (empty($index_exists)) {
            $wpdb->query($alter_sql);
            
            if ($wpdb->last_error) {
                error_log('EIPSI Forms: Failed to add index ' . $index_name . ' - ' . $wpdb->last_error);
            }
        }
    }
    
    update_option($db_version_key, $required_db_version);
}

add_action('plugins_loaded', 'eipsi_forms_upgrade_database');

// Verify schema on load (failsafe check)
add_action('plugins_loaded', 'eipsi_forms_verify_schema_on_load');

function eipsi_forms_verify_schema_on_load() {
    $schema_version = get_option('eipsi_db_schema_version');
    
    // If schema version not set or outdated, trigger repair
    if (!$schema_version || version_compare($schema_version, '1.2.2', '<')) {
        EIPSI_Database_Schema_Manager::repair_local_schema();
    }
    
    // Ensure partial responses table exists (idempotent)
    EIPSI_Partial_Responses::create_table();
}

// Add periodic schema verification (every 24 hours)
add_action('admin_init', array('EIPSI_Database_Schema_Manager', 'periodic_verification'));

function eipsi_forms_enqueue_admin_assets($hook) {
    if (strpos($hook, 'eipsi') === false && strpos($hook, 'form-results') === false && strpos($hook, 'eipsi-db-config') === false) {
        return;
    }

    wp_enqueue_style(
        'eipsi-admin-style',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        EIPSI_FORMS_VERSION
    );

    wp_enqueue_script(
        'eipsi-admin-script',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        EIPSI_FORMS_VERSION,
        true
    );

    wp_localize_script('eipsi-admin-script', 'eipsiAdminConfig', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_forms_nonce'),
        'adminNonce' => wp_create_nonce('eipsi_admin_nonce')
    ));

    // Enqueue configuration panel assets
    if (strpos($hook, 'eipsi-db-config') !== false) {
        wp_enqueue_style(
            'eipsi-config-panel-style',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/configuration-panel.css',
            array(),
            EIPSI_FORMS_VERSION
        );

        wp_enqueue_script(
            'eipsi-config-panel-script',
            EIPSI_FORMS_PLUGIN_URL . 'assets/js/configuration-panel.js',
            array('jquery'),
            EIPSI_FORMS_VERSION,
            true
        );

        wp_localize_script('eipsi-config-panel-script', 'eipsiConfigL10n', array(
            'connected' => __('Connected', 'eipsi-forms'),
            'disconnected' => __('Disconnected', 'eipsi-forms'),
            'currentDatabase' => __('Current Database:', 'eipsi-forms'),
            'records' => __('Records:', 'eipsi-forms'),
            'noExternalDB' => __('No external database configured. Form submissions will be stored in the WordPress database.', 'eipsi-forms'),
            'fillAllFields' => __('Please fill in all required fields.', 'eipsi-forms'),
            'connectionError' => __('Connection test failed. Please check your credentials.', 'eipsi-forms'),
            'testFirst' => __('Please test the connection before saving.', 'eipsi-forms'),
            'saveError' => __('Failed to save configuration.', 'eipsi-forms'),
            'disableError' => __('Failed to disable external database.', 'eipsi-forms'),
            'confirmDisable' => __('Are you sure you want to disable the external database? Form submissions will be stored in the WordPress database.', 'eipsi-forms'),
            'disableExternal' => __('Disable External Database', 'eipsi-forms'),
            'confirmDeleteTitle' => __('⚠️ Delete All Clinical Data?', 'eipsi-forms'),
            'confirmDeleteMessage' => __('This action will PERMANENTLY delete all form responses, session data, and event logs from EIPSI Forms.\n\nThis CANNOT be undone.\n\nAre you absolutely sure?', 'eipsi-forms'),
            'confirmDeleteYes' => __('Yes, delete all data', 'eipsi-forms'),
            'confirmDeleteNo' => __('Cancel', 'eipsi-forms'),
            'deleteSuccess' => __('All clinical data has been successfully deleted.', 'eipsi-forms'),
            'deleteError' => __('Failed to delete data. Please check the error logs.', 'eipsi-forms')
        ));
    }

    // Enqueue Privacy Dashboard assets (Smart Save Button)
    if (strpos($hook, 'eipsi') !== false && isset($_GET['tab']) && $_GET['tab'] === 'privacy') {
        wp_enqueue_script(
            'eipsi-privacy-dashboard',
            EIPSI_FORMS_PLUGIN_URL . 'admin/js/privacy-dashboard.js',
            array('jquery'),
            filemtime(EIPSI_FORMS_PLUGIN_DIR . 'admin/js/privacy-dashboard.js'),
            true
        );

        // Make ajaxurl available (jQuery already has it via eipsiAdminConfig object)
        // No additional localization needed
    }
}

add_action('admin_enqueue_scripts', 'eipsi_forms_enqueue_admin_assets');

function eipsi_forms_register_blocks() {
    if (!function_exists('register_block_type')) {
        return;
    }

    $asset_file = EIPSI_FORMS_PLUGIN_DIR . 'build/index.asset.php';
    
    if (!file_exists($asset_file)) {
        return;
    }

    $asset_data = include $asset_file;

    wp_register_script(
        'eipsi-blocks-editor',
        EIPSI_FORMS_PLUGIN_URL . 'build/index.js',
        $asset_data['dependencies'],
        $asset_data['version'],
        true
    );

    // Pass admin nonce to block editor for AJAX calls (e.g., eipsi_get_forms_list)
    wp_localize_script('eipsi-blocks-editor', 'eipsiEditorData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_admin_nonce'),
        'siteUrl' => home_url(),
    ));
    
    // Backward compatibility: also expose as window.eipsiAdminNonce
    wp_add_inline_script('eipsi-blocks-editor', 
        'window.eipsiAdminNonce = eipsiEditorData?.nonce || "";', 
        'after'
    );

    wp_register_style(
        'eipsi-blocks-editor',
        EIPSI_FORMS_PLUGIN_URL . 'build/index.css',
        array(),
        $asset_data['version']
    );

    wp_register_style(
        'eipsi-blocks-style',
        EIPSI_FORMS_PLUGIN_URL . 'build/style-index.css',
        array(),
        $asset_data['version']
    );

    // REGISTRAR BLOQUES DESDE block.json (en /blocks/)
    $block_dirs = array(
        'form-block',
        'form-container',
        'pagina', 
        'consent-block',
        'campo-texto',
        'campo-textarea',
        'campo-descripcion',
        'campo-select',
        'campo-radio',
        'campo-multiple',
        'campo-likert',
        'vas-slider'
    );

    foreach ($block_dirs as $block_dir) {
        $block_path = EIPSI_FORMS_PLUGIN_DIR . 'blocks/' . $block_dir;
        
        if (!file_exists($block_path . '/block.json')) {
            continue;
        }

        $args = array();

        if ('form-block' === $block_dir) {
            $args['render_callback'] = 'eipsi_render_form_block';
        }

        register_block_type($block_path, $args);
    }
}

add_action('init', 'eipsi_forms_register_blocks');

// === AGREGÁ ESTA FUNCIÓN JUSTO AQUÍ ===
function eipsi_forms_block_categories($block_categories, $editor_context) {
    if (!empty($editor_context->post)) {
        array_push(
            $block_categories,
            array(
                'slug' => 'eipsi-forms',
                'title' => __('EIPSI Forms', 'eipsi-forms'),
                'icon' => null,
            )
        );
    }
    return $block_categories;
}
add_filter('block_categories_all', 'eipsi_forms_block_categories', 10, 2);
// === FIN DEL CÓDIGO NUEVO ===

function eipsi_forms_enqueue_block_assets($content) {
    $blocks = array(
        'eipsi/form-container',
        'eipsi/form-block',
        'eipsi/consent-block',
        'eipsi/campo-texto',
        'eipsi/campo-textarea',
        'eipsi/campo-descripcion',
        'eipsi/campo-select',
        'eipsi/campo-radio',
        'eipsi/campo-multiple',
        'eipsi/campo-likert',
        'eipsi/vas-slider'
    );

    foreach ($blocks as $block) {
        if (has_block($block, $content)) {
            eipsi_forms_enqueue_frontend_assets();
            break;
        }
    }
    
    return $content;
}

// Enqueue frontend assets on every page
add_action('wp_enqueue_scripts', function() {
    eipsi_forms_enqueue_frontend_assets();
});

function eipsi_forms_render_form_block($attributes) {
    $form_id = isset($attributes['formId']) ? sanitize_text_field($attributes['formId']) : '';
    $show_title = isset($attributes['showTitle']) ? (bool) $attributes['showTitle'] : true;
    $class_name = isset($attributes['className']) ? sanitize_html_class($attributes['className']) : '';

    eipsi_forms_enqueue_frontend_assets();

    if (empty($form_id)) {
        return '<div class="eipsi-form-notice"><p>' . esc_html__('Please configure the form ID in block settings.', 'eipsi-forms') . '</p></div>';
    }

    $output = '<div class="eipsi-form ' . esc_attr($class_name) . '">';
    
    if ($show_title) {
        $output .= '<h3 class="form-title">' . esc_html(ucwords(str_replace('-', ' ', $form_id))) . '</h3>';
    }
    
    $output .= '<form class="vas-form" data-form-id="' . esc_attr($form_id) . '" data-current-page="1" data-total-pages="1">';
    $output .= '<input type="hidden" name="form_id" value="' . esc_attr($form_id) . '">';
    $output .= '<input type="hidden" name="form_action" value="eipsi_forms_submit_form">';
    $output .= '<input type="hidden" name="ip_address" class="eipsi-ip-placeholder" value="">';
    $output .= '<input type="hidden" name="device" class="eipsi-device-placeholder" value="">';
    $output .= '<input type="hidden" name="browser" class="eipsi-browser-placeholder" value="">';
    $output .= '<input type="hidden" name="os" class="eipsi-os-placeholder" value="">';
    $output .= '<input type="hidden" name="screen_width" class="eipsi-screen-placeholder" value="">';
    $output .= '<input type="hidden" name="form_start_time" class="eipsi-start-time" value="">';
    $output .= '<input type="hidden" name="form_end_time" class="eipsi-end-time" value="">';
    $output .= '<input type="hidden" name="current_page" class="eipsi-current-page" value="1">';
    $output .= '<div class="form-group">';
    $output .= '<label for="form-name" class="required">Nombre</label>';
    $output .= '<input type="text" id="form-name" name="name" required>';
    $output .= '<div class="form-error"></div>';
    $output .= '</div>';
    $output .= '<div class="form-group">';
    $output .= '<label for="form-email" class="required">Correo electrónico</label>';
    $output .= '<input type="email" id="form-email" name="email" required>';
    $output .= '<div class="form-error"></div>';
    $output .= '</div>';
    $output .= '<div class="form-group">';
    $output .= '<label for="form-message">Mensaje</label>';
    $output .= '<textarea id="form-message" name="message"></textarea>';
    $output .= '<div class="form-error"></div>';
    $output .= '</div>';
    $output .= '<div class="form-submit">';
    $output .= '<button type="submit">Enviar</button>';
    $output .= '</div>';
    $output .= '</form>';
    $output .= '</div>';

    return $output;
}

function eipsi_forms_enqueue_frontend_assets() {
    static $assets_enqueued = false;

    if ($assets_enqueued) {
        return;
    }

    // Ensure block styles are registered before enqueueing main CSS
    if (!wp_style_is('eipsi-blocks-style', 'registered')) {
        wp_register_style(
            'eipsi-blocks-style',
            EIPSI_FORMS_PLUGIN_URL . 'build/style-index.css',
            array(),
            EIPSI_FORMS_VERSION
        );
    }

    wp_enqueue_style(
        'eipsi-forms-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-forms.css',
        array('eipsi-blocks-style'),
        EIPSI_FORMS_VERSION
    );

    // Dark mode theme toggle styles - CRITICAL for all form fields
    wp_enqueue_style(
        'eipsi-theme-toggle-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/theme-toggle.css',
        array('eipsi-forms-css'),
        EIPSI_FORMS_VERSION
    );

    // Save & Continue UI styles
    wp_enqueue_style(
        'eipsi-save-continue-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-save-continue.css',
        array('eipsi-theme-toggle-css'),
        EIPSI_FORMS_VERSION
    );

    wp_enqueue_script(
        'eipsi-tracking-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-tracking.js',
        array(),
        EIPSI_FORMS_VERSION,
        true
    );

    wp_localize_script('eipsi-tracking-js', 'eipsiTrackingConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_tracking_nonce'),
    ));

    wp_enqueue_script(
        'eipsi-forms-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-forms.js',
        array('eipsi-tracking-js'),
        EIPSI_FORMS_VERSION,
        true
    );

    wp_localize_script('eipsi-forms-js', 'eipsiFormsConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_forms_nonce'),
        'strings' => array(
            'requiredField' => 'Este campo es obligatorio.',
            'sliderRequired' => 'Por favor, interactúe con la escala para continuar.',
            'invalidEmail' => 'Por favor, introduzca una dirección de correo electrónico válida.',
            'submitting' => 'Enviando...',
            'submit' => 'Enviar',
            'error' => 'Ocurrió un error. Por favor, inténtelo de nuevo.',
            'success' => '¡Formulario enviado correctamente!',
            'studyClosedMessage' => __('Este estudio está cerrado y no acepta más respuestas. Contacta al investigador si tienes dudas.', 'eipsi-forms'),
        ),
        'settings' => array(
            'debug' => apply_filters('eipsi_forms_debug_mode', defined('WP_DEBUG') && WP_DEBUG),
            'enableAutoScroll' => apply_filters('eipsi_forms_enable_auto_scroll', true),
            'scrollOffset' => apply_filters('eipsi_forms_scroll_offset', 20),
            'validateOnBlur' => apply_filters('eipsi_forms_validate_on_blur', true),
            'smoothScroll' => apply_filters('eipsi_forms_smooth_scroll', true),
        ),
    ));
    
    // Enqueue Save & Continue script
    wp_enqueue_script(
        'eipsi-save-continue-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-save-continue.js',
        array('eipsi-forms-js'),
        EIPSI_FORMS_VERSION,
        true
    );

    // Enqueue Randomization script (Fase 1 & 2)
    wp_enqueue_script(
        'eipsi-random-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-random.js',
        array('eipsi-forms-js'),
        EIPSI_FORMS_VERSION,
        true
    );

    wp_localize_script('eipsi-random-js', 'eipsiRandomData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_random_nonce'),
    ));

    // Enqueue Randomization Public System styles (Fase 3)
    wp_enqueue_style(
        'eipsi-randomization-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-randomization.css',
        array('eipsi-save-continue-css'),
        EIPSI_FORMS_VERSION
    );

    // Enqueue Randomization Public System script (Fase 3)
    wp_enqueue_script(
        'eipsi-randomization-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-randomization.js',
        array('eipsi-forms-js'),
        EIPSI_FORMS_VERSION,
        true
    );

    // Dark mode is now CSS-only via @media (prefers-color-scheme: dark)
    // No JavaScript needed - the theme-toggle.js file is deprecated as of v4.0.0
    // wp_enqueue_script( 'eipsi-theme-toggle-js', ... ) is removed

    $assets_enqueued = true;
}

// Register admin post handlers
add_action('admin_post_eipsi_forms_export_excel', 'eipsi_export_to_excel');
// Deletion and editing of results are handled via admin_init in admin/handlers.php and admin/results-page.php

// Puedes comentar o eliminar esto:
// function eipsi_forms_load_textdomain() {
//     load_plugin_textdomain(
//         'eipsi-forms',
//         false,
//         dirname(plugin_basename(__FILE__)) . '/languages'
//     );
// }
// add_action('plugins_loaded', 'eipsi_forms_load_textdomain');
