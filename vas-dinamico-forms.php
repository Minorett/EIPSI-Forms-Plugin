<?php
/**
 * Plugin Name: EIPSI Forms
 * Plugin URI: https://github.com/roofkat/VAS-dinamico-mvp
 * Description: Professional form builder with Gutenberg blocks, conditional logic, and Excel export capabilities.
 * Version: 1.2.0
 * Author: Mathias Rojas
 * Author URI: https://github.com/roofkat
 * Text Domain: vas-dinamico-forms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: forms, contact-form, survey, quiz, poll, form-builder, gutenberg, blocks, admin-dashboard, excel-export, analytics
 * Stable tag: 1.2.0
 * 
 * @package VAS_Dinamico_Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VAS_DINAMICO_VERSION', '1.2.0');
define('VAS_DINAMICO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VAS_DINAMICO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VAS_DINAMICO_PLUGIN_FILE', __FILE__);

require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/menu.php';
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/results-page.php';
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/export.php';
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/handlers.php';
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/configuration.php';
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/ajax-handlers.php';

function vas_dinamico_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create form results table
    $table_name = $wpdb->prefix . 'vas_form_results';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        participant varchar(255) DEFAULT NULL,
        interaction varchar(255) DEFAULT NULL,
        form_name varchar(255) NOT NULL,
        created_at datetime NOT NULL,
        device varchar(100) DEFAULT NULL,
        browser varchar(100) DEFAULT NULL,
        os varchar(100) DEFAULT NULL,
        screen_width int(11) DEFAULT NULL,
        duration int(11) DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        form_responses longtext DEFAULT NULL,
        PRIMARY KEY (id),
        KEY form_name (form_name),
        KEY created_at (created_at)
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
}

register_activation_hook(__FILE__, 'vas_dinamico_activate');

function vas_dinamico_enqueue_admin_assets($hook) {
    if (strpos($hook, 'vas-dinamico') === false && strpos($hook, 'form-results') === false && strpos($hook, 'eipsi-db-config') === false) {
        return;
    }
    
    wp_enqueue_style(
        'vas-dinamico-admin-style',
        VAS_DINAMICO_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        VAS_DINAMICO_VERSION
    );
    
    wp_enqueue_script(
        'vas-dinamico-admin-script',
        VAS_DINAMICO_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        VAS_DINAMICO_VERSION,
        true
    );
    
    wp_localize_script('vas-dinamico-admin-script', 'vasdinamico', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vas_dinamico_nonce'),
        'adminNonce' => wp_create_nonce('eipsi_admin_nonce')
    ));
    
    // Enqueue configuration panel assets
    if (strpos($hook, 'eipsi-db-config') !== false) {
        wp_enqueue_style(
            'eipsi-config-panel-style',
            VAS_DINAMICO_PLUGIN_URL . 'assets/css/configuration-panel.css',
            array(),
            VAS_DINAMICO_VERSION
        );
        
        wp_enqueue_script(
            'eipsi-config-panel-script',
            VAS_DINAMICO_PLUGIN_URL . 'assets/js/configuration-panel.js',
            array('jquery'),
            VAS_DINAMICO_VERSION,
            true
        );
        
        wp_localize_script('eipsi-config-panel-script', 'eipsiConfigL10n', array(
            'connected' => __('Connected', 'vas-dinamico-forms'),
            'disconnected' => __('Disconnected', 'vas-dinamico-forms'),
            'currentDatabase' => __('Current Database:', 'vas-dinamico-forms'),
            'records' => __('Records:', 'vas-dinamico-forms'),
            'noExternalDB' => __('No external database configured. Form submissions will be stored in the WordPress database.', 'vas-dinamico-forms'),
            'fillAllFields' => __('Please fill in all required fields.', 'vas-dinamico-forms'),
            'connectionError' => __('Connection test failed. Please check your credentials.', 'vas-dinamico-forms'),
            'testFirst' => __('Please test the connection before saving.', 'vas-dinamico-forms'),
            'saveError' => __('Failed to save configuration.', 'vas-dinamico-forms'),
            'disableError' => __('Failed to disable external database.', 'vas-dinamico-forms'),
            'confirmDisable' => __('Are you sure you want to disable the external database? Form submissions will be stored in the WordPress database.', 'vas-dinamico-forms'),
            'disableExternal' => __('Disable External Database', 'vas-dinamico-forms')
        ));
    }
}

add_action('admin_enqueue_scripts', 'vas_dinamico_enqueue_admin_assets');

function vas_dinamico_register_blocks() {
    if (!function_exists('register_block_type')) {
        return;
    }

    $asset_file = VAS_DINAMICO_PLUGIN_DIR . 'build/index.asset.php';
    
    if (!file_exists($asset_file)) {
        return;
    }

    $asset_data = include $asset_file;

    wp_register_script(
        'vas-dinamico-blocks-editor',
        VAS_DINAMICO_PLUGIN_URL . 'build/index.js',
        $asset_data['dependencies'],
        $asset_data['version'],
        true
    );

    wp_register_style(
        'vas-dinamico-blocks-editor',
        VAS_DINAMICO_PLUGIN_URL . 'build/index.css',
        array(),
        $asset_data['version']
    );

    wp_register_style(
        'vas-dinamico-blocks-style',
        VAS_DINAMICO_PLUGIN_URL . 'build/style-index.css',
        array(),
        $asset_data['version']
    );

    // REGISTRAR BLOQUES DESDE block.json (en /blocks/)
    $block_dirs = array(
        'form-block',
        'form-container',
        'pagina', 
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
        $block_path = VAS_DINAMICO_PLUGIN_DIR . 'blocks/' . $block_dir;
        
        if (file_exists($block_path . '/block.json')) {
            register_block_type($block_path);
        }
    }
}

add_action('init', 'vas_dinamico_register_blocks');

// === AGREGÁ ESTA FUNCIÓN JUSTO AQUÍ ===
function vas_dinamico_block_categories($block_categories, $editor_context) {
    if (!empty($editor_context->post)) {
        array_push(
            $block_categories,
            array(
                'slug' => 'eipsi-forms',
                'title' => __('EIPSI Forms', 'vas-dinamico-forms'),
                'icon' => null,
            )
        );
    }
    return $block_categories;
}
add_filter('block_categories_all', 'vas_dinamico_block_categories', 10, 2);
// === FIN DEL CÓDIGO NUEVO ===

function vas_dinamico_enqueue_block_assets($content) {
    $blocks = array(
        'vas-dinamico/form-container',
        'vas-dinamico/form-block',
        'vas-dinamico/campo-texto',
        'vas-dinamico/campo-textarea',
        'vas-dinamico/campo-descripcion',
        'vas-dinamico/campo-select',
        'vas-dinamico/campo-radio',
        'vas-dinamico/campo-multiple',
        'vas-dinamico/campo-likert',
        'vas-dinamico/vas-slider'
    );

    foreach ($blocks as $block) {
        if (has_block($block, $content)) {
            vas_dinamico_enqueue_frontend_assets();
            break;
        }
    }
    
    return $content;
}

// Enqueue frontend assets on every page
add_action('wp_enqueue_scripts', function() {
    vas_dinamico_enqueue_frontend_assets();
});

function vas_dinamico_render_form_block($attributes) {
    $form_id = isset($attributes['formId']) ? sanitize_text_field($attributes['formId']) : '';
    $show_title = isset($attributes['showTitle']) ? (bool) $attributes['showTitle'] : true;
    $class_name = isset($attributes['className']) ? sanitize_html_class($attributes['className']) : '';

    vas_dinamico_enqueue_frontend_assets();

    if (empty($form_id)) {
        return '<div class="vas-dinamico-form-notice"><p>' . esc_html__('Please configure the form ID in block settings.', 'vas-dinamico-forms') . '</p></div>';
    }

    $output = '<div class="vas-dinamico-form ' . esc_attr($class_name) . '">';
    
    if ($show_title) {
        $output .= '<h3 class="form-title">' . esc_html(ucwords(str_replace('-', ' ', $form_id))) . '</h3>';
    }
    
    $output .= '<form class="vas-form" data-form-id="' . esc_attr($form_id) . '" data-current-page="1" data-total-pages="1">';
    $output .= '<input type="hidden" name="form_id" value="' . esc_attr($form_id) . '">';
    $output .= '<input type="hidden" name="form_action" value="vas_dinamico_submit_form">';
    $output .= '<input type="hidden" name="ip_address" class="eipsi-ip-placeholder" value="">';
    $output .= '<input type="hidden" name="device" class="eipsi-device-placeholder" value="">';
    $output .= '<input type="hidden" name="browser" class="eipsi-browser-placeholder" value="">';
    $output .= '<input type="hidden" name="os" class="eipsi-os-placeholder" value="">';
    $output .= '<input type="hidden" name="screen_width" class="eipsi-screen-placeholder" value="">';
    $output .= '<input type="hidden" name="form_start_time" class="eipsi-start-time" value="">';
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

function vas_dinamico_enqueue_frontend_assets() {
    static $assets_enqueued = false;

    if ($assets_enqueued) {
        return;
    }

    wp_enqueue_style(
        'eipsi-forms-css',
        VAS_DINAMICO_PLUGIN_URL . 'assets/css/eipsi-forms.css',
        array('vas-dinamico-blocks-style'),
        VAS_DINAMICO_VERSION
    );

    wp_enqueue_script(
        'eipsi-tracking-js',
        VAS_DINAMICO_PLUGIN_URL . 'assets/js/eipsi-tracking.js',
        array(),
        VAS_DINAMICO_VERSION,
        true
    );

    wp_localize_script('eipsi-tracking-js', 'eipsiTrackingConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_tracking_nonce'),
    ));

    wp_enqueue_script(
        'eipsi-forms-js',
        VAS_DINAMICO_PLUGIN_URL . 'assets/js/eipsi-forms.js',
        array('eipsi-tracking-js'),
        VAS_DINAMICO_VERSION,
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
        ),
        'settings' => array(
            'debug' => apply_filters('vas_dinamico_debug_mode', defined('WP_DEBUG') && WP_DEBUG),
            'enableAutoScroll' => apply_filters('vas_dinamico_enable_auto_scroll', true),
            'scrollOffset' => apply_filters('vas_dinamico_scroll_offset', 20),
            'validateOnBlur' => apply_filters('vas_dinamico_validate_on_blur', true),
            'smoothScroll' => apply_filters('vas_dinamico_smooth_scroll', true),
        ),
    ));

    $assets_enqueued = true;
}

// Register admin post handlers
add_action('admin_post_vas_dinamico_delete_result', 'vas_dinamico_delete_result');
add_action('admin_post_vas_dinamico_edit_result', 'vas_dinamico_edit_result');
add_action('admin_post_vas_dinamico_export_excel', 'vas_export_responses');

// Puedes comentar o eliminar esto:
// function vas_dinamico_load_textdomain() {
//     load_plugin_textdomain(
//         'vas-dinamico-forms',
//         false,
//         dirname(plugin_basename(__FILE__)) . '/languages'
//     );
// }
// add_action('plugins_loaded', 'vas_dinamico_load_textdomain');