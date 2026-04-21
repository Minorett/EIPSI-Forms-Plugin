<?php
/**
 * Plugin Name: EIPSI Forms
 * Plugin URI: https://enmediodelcontexto.com.ar
 * Description: Professional form builder with Gutenberg blocks, conditional logic, and Excel export capabilities.
 * Version: 2.0.0
 * Author: Mathias N. Rojas de la Fuente
 * Author URI: https://www.instagram.com/enmediodel.contexto/
 * Text Domain: eipsi-forms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: forms, contact-form, survey, quiz, poll, form-builder, gutenberg, blocks, admin-dashboard, excel-export, analytics, RCT, randomization, longitudinal, studies
 * Stable tag: 2.0.0
 *
 * @package EIPSI_Forms
 */

 if (!defined('ABSPATH')) {
    exit;
 }

 define('EIPSI_FORMS_VERSION', '2.1.4');
define('EIPSI_FORMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EIPSI_FORMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EIPSI_FORMS_PLUGIN_FILE', __FILE__);
define('EIPSI_FORMS_SLUG', 'eipsi-forms');

// Session Cookie Name for Participant Authentication
define('EIPSI_SESSION_COOKIE_NAME', 'eipsi_session_token');

/**
 * Get default menu capabilities for EIPSI Forms.
 *
 * @since 1.5.4
 *
 * @return array
 */
function eipsi_get_default_menu_capabilities() {
    return array(
        'main' => 'edit_posts',
        'results' => 'manage_options',
        'configuration' => 'manage_options',
        'longitudinal' => 'edit_posts',
        'form_library' => 'edit_posts'
    );
}

/**
 * Get filtered menu capabilities.
 *
 * @since 1.5.4
 *
 * @return array
 */
function eipsi_get_menu_capabilities() {
    return apply_filters('eipsi_forms_menu_capabilities', eipsi_get_default_menu_capabilities());
}

/**
 * Get capability required for longitudinal studies.
 *
 * @since 1.5.4
 *
 * @return string
 */
function eipsi_get_longitudinal_capability() {
    $capabilities = eipsi_get_menu_capabilities();
    $capability = isset($capabilities['longitudinal']) ? $capabilities['longitudinal'] : 'edit_posts';

    return apply_filters('eipsi_forms_longitudinal_capability', $capability);
}

/**
 * Check if current user can manage longitudinal studies.
 *
 * @since 1.5.4
 *
 * @return bool
 */
function eipsi_user_can_manage_longitudinal() {
    return current_user_can(eipsi_get_longitudinal_capability());
}

// Configuración longitudinal (v1.4.0+)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/config/longitudinal-config.php';

require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/menu.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/results-page.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/longitudinal-study-page.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-pools-page.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/longitudinal-pool-dashboard.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/export.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/handlers.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-manager.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/partial-responses.php';

// Database schema migration (v2.0.1) - Fix corrupt indexes
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-migration.php';

require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/configuration.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-handlers.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-handlers-wizard.php';
// Note: Study control handlers now included in ajax-handlers-wizard.php
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-email-log-handlers.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/cron-handlers.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/cron-reminders-handler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/delete-study-handler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/monitoring.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/study-close-handler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/completion-message-backend.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/form-library.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/form-library-tools.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/demo-templates.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/form-template-render.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/shortcodes.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'assets/js/eipsi-randomization-shortcode.php';

// Sistema RCT completo (v1.3.1)
// IMPORTANTE: manual-overrides-table.php debe cargarse ANTES de randomization-db-setup.php
// porque este último llama a eipsi_create_manual_overrides_table() en sus hooks
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/manual-overrides-table.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-db-setup.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-shortcode-handler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-config-handler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/randomization-frontend.php';

// Randomization Dashboard (v1.3.2)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-page.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-api.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/waves-manager-api.php';

// RCT Schema Migration (v1.3.6 - CRITICAL FIX)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/migrate-randomization-schema.php';

// Longitudinal Services (v1.4.0 - Fase 0: Arquitectura)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-auth-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-wave-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-smtp-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-anonymize-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-assignment-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-magic-links-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-access-log-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-auth-handler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/class-survey-access-handler.php';

// v2.5.0 - Nudge System Architecture (Job Queue, Event-Driven, Cache)
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/services/class-nudge-job-queue.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/services/class-nudge-event-scheduler.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/services/class-nudge-cache.php';

// Initialize Survey Access Handler
$eipsi_survey_access = new EIPSI_Survey_Access_Handler();
$eipsi_survey_access->init();

// Setup Wizard (v1.5.1)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/setup-wizard.php';

// Study Dashboard (v1.5.2)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/study-dashboard-api.php';

// Email System Handlers (v1.5.4)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-email-handlers.php';

// Participant Authentication Handlers (v1.5.5)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-participant-handlers.php';

// Export AJAX Handlers — participant roster + longitudinal (v1.8.0)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-export-handlers.php';

// ============================================================================
// PHASE 3 - RESEARCHER DATA CONFIDENCE (v2.1.0)
// ============================================================================
// Services for export hardening, monitoring upgrades, and GDPR compliance
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-access-log-export-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-completion-verification-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-timeline-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-failed-email-alerts-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-cron-health-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-data-request-service.php';

// Phase 3 AJAX Handlers
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/ajax-phase3-handlers.php';

// ============================================================================
// LONGITUDINAL POOLS - ASSIGNMENT LOGIC (v2.1.0 - Part 3)
// ============================================================================
// Service: weighted random assignment + magic link generation
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-pool-assignment-service.php';

// Pool Analytics Dashboard (v2.1.0 - Part 4)
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-pool-dashboard-service.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/longitudinal-pool-dashboard.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/pool-dashboard-api.php';

// Pool Studies REST API (v2.5.3) - Phase 1 of Pool Randomization System
// Endpoints: /eipsi/v1/pool-detect, /pool-config, /pool-assign, /pool-analytics
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/pool-rest-api.php';

// Pool Block Renderer (v2.5.3) - Phase 2: Block + Shortcode [eipsi_pool]
require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/class-pool-block-renderer.php';

// Pool Completion Hooks (v2.5.3) - Phase 4: Tracking de completitud
// Hook: eipsi_form_submitted → eipsi_check_pool_completion_on_submit
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/pool-completion-hooks.php';

// ============================================================================
// EMAIL SYSTEM CONFIGURATION (v1.5.4 - Default Email Fix)
// ============================================================================

/**
 * Configure default email sender for EIPSI Forms
 * Uses investigator email if set, otherwise falls back to WordPress admin email
 * 
 * @since 1.5.4
 */
function eipsi_mail_from($from_email) {
    // If SMTP is configured, it will handle the sender
    if (class_exists('EIPSI_SMTP_Service')) {
        $smtp_service = new EIPSI_SMTP_Service();
        if ($smtp_service->is_enabled()) {
            return $from_email;
        }
    }
    
    // Use investigator email or fall back to admin email
    $investigator_email = get_option('eipsi_investigator_email', '');
    if (!empty($investigator_email) && is_email($investigator_email)) {
        return $investigator_email;
    }
    
    return $from_email;
}
add_filter('wp_mail_from', 'eipsi_mail_from', 99);

/**
 * Configure default email sender name for EIPSI Forms
 * Uses investigator name if set, otherwise falls back to site name
 * 
 * @since 1.5.4
 */
function eipsi_mail_from_name($from_name) {
    // If SMTP is configured, it will handle the sender name
    if (class_exists('EIPSI_SMTP_Service')) {
        $smtp_service = new EIPSI_SMTP_Service();
        if ($smtp_service->is_enabled()) {
            return $from_name;
        }
    }
    
    // Use investigator name or fall back to site name
    $investigator_name = get_option('eipsi_investigator_name', '');
    if (!empty($investigator_name)) {
        return $investigator_name;
    }
    
    return $from_name;
}
add_filter('wp_mail_from_name', 'eipsi_mail_from_name', 99);

/**
 * Enable HTML emails by default
 * 
 * @since 1.5.4
 */
function eipsi_set_html_content_type() {
    return 'text/html';
}
add_filter('wp_mail_content_type', 'eipsi_set_html_content_type', 99);

/**
 * Log email errors for debugging
 * 
 * @since 1.5.4
 */
function eipsi_log_mail_error($wp_error) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[EIPSI Forms] Email Error: ' . $wp_error->get_error_message());
    }
    return $wp_error;
}
add_action('wp_mail_failed', 'eipsi_log_mail_error');

/**
 * Enqueue Randomization assets en admin
 */
function eipsi_enqueue_randomization_assets($hook) {
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';

    if ($page === 'eipsi-results-experience') {
        $active_tab = $active_tab ?: 'submissions';

        if ($active_tab === 'randomization') {
            wp_enqueue_style(
                'eipsi-randomization-css',
                EIPSI_FORMS_PLUGIN_URL . 'assets/css/randomization.css',
                array(),
                EIPSI_FORMS_VERSION
            );

            wp_enqueue_script(
                'eipsi-randomization-js',
                EIPSI_FORMS_PLUGIN_URL . 'assets/js/randomization.js',
                array('jquery'),
                EIPSI_FORMS_VERSION,
                true
            );

            wp_localize_script('eipsi-randomization-js', 'eipsiRandomization', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eipsi_randomization_nonce'),
                'adminUrl' => admin_url(),
                'strings' => array(
                    'loading' => __('Cargando datos...', 'eipsi-forms'),
                    'error' => __('Error al cargar datos', 'eipsi-forms'),
                    'success' => __('Actualizado correctamente', 'eipsi-forms'),
                    'confirmDelete' => __('¿Estás seguro de que quieres eliminar esta aleatorización?', 'eipsi-forms'),
                    'copied' => __('ID copiado al portapapeles', 'eipsi-forms')
                )
            ));
        }

        return;
    }

    if ($page === 'eipsi-longitudinal-study') {
        $active_tab = $active_tab ?: 'dashboard-study';

        if ($active_tab === 'waves-manager') {
            wp_enqueue_style(
                'eipsi-waves-manager-css',
                EIPSI_FORMS_PLUGIN_URL . 'assets/css/waves-manager.css',
                array(),
                EIPSI_FORMS_VERSION
            );

            wp_enqueue_script(
                'eipsi-waves-manager-js',
                EIPSI_FORMS_PLUGIN_URL . 'assets/js/waves-manager.js',
                array('jquery'),
                EIPSI_FORMS_VERSION,
                true
            );

            wp_localize_script('eipsi-waves-manager-js', 'eipsiWavesManager', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eipsi_waves_nonce'),
                'strings' => array(
                    'loading' => __('Cargando...', 'eipsi-forms'),
                    'error' => __('Ha ocurrido un error', 'eipsi-forms'),
                    'success' => __('Operación exitosa', 'eipsi-forms'),
                    'confirmDelete' => __('¿Estás seguro de que quieres eliminar esta onda? Esta acción es irreversible.', 'eipsi-forms'),
                    'confirmAssign' => __('¿Estás seguro de asignar los participantes seleccionados?', 'eipsi-forms'),
                    'noParticipants' => __('Por favor, selecciona al menos un participante.', 'eipsi-forms')
                )
            ));
        }

        if ($active_tab === 'dashboard-study') {
            wp_enqueue_style(
                'eipsi-study-dashboard-css',
                EIPSI_FORMS_PLUGIN_URL . 'assets/css/study-dashboard.css',
                array('eipsi-tokens'),
                EIPSI_FORMS_VERSION
            );

            wp_enqueue_script(
                'eipsi-study-dashboard',
                EIPSI_FORMS_PLUGIN_URL . 'assets/js/study-dashboard.js',
                array('jquery'),
                EIPSI_FORMS_VERSION,
                true
            );

            wp_localize_script('eipsi-study-dashboard', 'eipsiStudyDash', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eipsi_study_dashboard_nonce'),
                'strings' => array(
                    'confirmReminder' => __('¿Estás seguro de enviar recordatorios manuales?', 'eipsi-forms'),
                    'confirmClose' => __('¿Estás seguro de que quieres cerrar este estudio? Esta acción es irreversible.', 'eipsi-forms'),
                    'success' => __('Operación exitosa', 'eipsi-forms'),
                    'error' => __('Ha ocurrido un error', 'eipsi-forms')
                )
            ));
        }
    }
}
add_action('admin_enqueue_scripts', 'eipsi_enqueue_randomization_assets');

/**
 * Enqueue Admin Light Theme CSS for all EIPSI admin pages
 *
 * @since 1.5.0
 */
add_action('admin_enqueue_scripts', 'eipsi_enqueue_admin_light_theme');
function eipsi_enqueue_admin_light_theme($hook) {
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

    // List of EIPSI admin pages
    $eipsi_pages = array(
        'eipsi-results-experience',
        'eipsi-configuration',
        'eipsi-longitudinal-study'
    );

    if (in_array($page, $eipsi_pages, true)) {
        // Enqueue design tokens FIRST (single source of truth)
        wp_enqueue_style(
            'eipsi-tokens',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-tokens.css',
            array(),
            EIPSI_FORMS_VERSION
        );

        // Enqueue the unified admin light theme
        wp_enqueue_style(
            'eipsi-admin-light-theme',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/admin-light-theme.css',
            array('eipsi-tokens'),
            EIPSI_FORMS_VERSION
        );

        // Also enqueue the existing admin styles as dependencies
        wp_enqueue_style(
            'eipsi-admin-style',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/admin-style.css',
            array('eipsi-tokens', 'eipsi-admin-light-theme'),
            EIPSI_FORMS_VERSION
        );

        // Configuration panel styles for the configuration page
        if ($page === 'eipsi-configuration') {
            wp_enqueue_style(
                'eipsi-configuration-panel',
                EIPSI_FORMS_PLUGIN_URL . 'assets/css/configuration-panel.css',
                array('eipsi-tokens', 'eipsi-admin-light-theme'),
                EIPSI_FORMS_VERSION
            );

            // Enqueue configuration panel JavaScript
            wp_enqueue_script(
                'eipsi-configuration-panel',
                EIPSI_FORMS_PLUGIN_URL . 'assets/js/configuration-panel.js',
                array('jquery'),
                EIPSI_FORMS_VERSION,
                true
            );

            // Localize configuration panel script
            wp_localize_script('eipsi-configuration-panel', 'eipsiConfigL10n', array(
                'fillAllFields' => __('Por favor completa todos los campos requeridos.', 'eipsi-forms'),
                'connectionError' => __('Error de conexión al probar la conexión.', 'eipsi-forms'),
                'testFirst' => __('Por favor prueba la conexión primero.', 'eipsi-forms'),
                'saveError' => __('Error al guardar la configuración.', 'eipsi-forms'),
                'disableExternal' => __('Deshabilitar Base de Datos Externa', 'eipsi-forms'),
                'loading' => __('Cargando...', 'eipsi-forms')
            ));

            // Get active tab
            $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'database';

            // Enqueue email test JavaScript for SMTP tab
            if ($active_tab === 'smtp') {
                wp_enqueue_script(
                    'eipsi-email-test',
                    EIPSI_FORMS_PLUGIN_URL . 'assets/js/email-test.js',
                    array('jquery'),
                    EIPSI_FORMS_VERSION,
                    true
                );
            }
        }
    }
}

/**
 * Enqueue Setup Wizard assets en admin (v1.5.1)
 */
add_action('admin_enqueue_scripts', 'eipsi_enqueue_setup_wizard_assets');
function eipsi_enqueue_setup_wizard_assets($hook) {
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';

    if ($page === 'eipsi-longitudinal-study' && $active_tab === 'create-study') {
        wp_enqueue_style(
            'eipsi-longitudinal-studies-ui-css',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/longitudinal-studies-ui.css',
            array(),
            EIPSI_FORMS_VERSION
        );

        wp_enqueue_style(
            'eipsi-setup-wizard-css',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/setup-wizard.css',
            array(),
            EIPSI_FORMS_VERSION
        );

        wp_enqueue_script(
            'eipsi-setup-wizard-js',
            EIPSI_FORMS_PLUGIN_URL . 'assets/js/setup-wizard.js',
            array('jquery'),
            EIPSI_FORMS_VERSION,
            true
        );

        $available_forms = eipsi_get_available_forms_for_wizard();

        wp_localize_script('eipsi-setup-wizard-js', 'eipsiWizard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eipsi_wizard_action'),
            'adminUrl' => admin_url(),
            'availableForms' => $available_forms,
            'strings' => array(
                'loading' => __('Guardando...', 'eipsi-forms'),
                'error' => __('Error al guardar', 'eipsi-forms'),
                'success' => __('Guardado correctamente', 'eipsi-forms'),
                'confirmActivation' => __('¿Estás seguro de activar este estudio?', 'eipsi-forms'),
                'validationError' => __('Por favor, revisa los campos requeridos', 'eipsi-forms')
            )
        ));
    }
}

/**
 * Enqueue Participant Auth assets (frontend + admin)
 * 
 * @since 1.4.0
 */
add_action('wp_enqueue_scripts', 'eipsi_enqueue_participant_auth_assets');
add_action('admin_enqueue_scripts', 'eipsi_enqueue_participant_auth_assets');

function eipsi_enqueue_participant_auth_assets() {
    // Solo enqueue si hay autenticación de participantes en esta página
    global $post;
    if (!is_a($post, 'WP_Post')) return;
    
    // Check if page has participant-related shortcodes
    $has_participant_shortcode = has_shortcode($post->post_content, 'eipsi_survey_login') ||
                                  has_shortcode($post->post_content, 'eipsi_participant_dashboard') ||
                                  has_shortcode($post->post_content, 'eipsi_form');
    
    if (!$has_participant_shortcode) return;
    
    wp_enqueue_script(
        'eipsi-participant-auth',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/participant-auth.js',
        array('jquery'),
        EIPSI_FORMS_VERSION,
        true
    );

    // v2.5.3 - Definir eipsiAuth para evitar ReferenceError
    wp_localize_script('eipsi-participant-auth', 'eipsiAuth', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_participant_auth'),
        'loginUrl' => get_permalink(),
        'participantId' => null, // Se actualizará desde shortcodes si hay sesión
        'strings' => array(
            'checking' => __('Verificando sesión...', 'eipsi-forms'),
            'sessionExpired' => __('Tu sesión expiró. Por favor, iniciá sesión nuevamente.', 'eipsi-forms'),
            'unauthorized' => __('No tenés acceso a esta encuesta.', 'eipsi-forms'),
            'sessionContinued' => __('Sesión activa · Continuando...', 'eipsi-forms'),
            'technicalError' => __('Error técnico. Intentá de nuevo.', 'eipsi-forms'),
            'magicLinkSent' => __('¡Listo! Revisá tu email.', 'eipsi-forms'),
            'magicLinkError' => __('Error al enviar el link. Intentá de nuevo.', 'eipsi-forms'),
            'invalidEmail' => __('Por favor, ingresá un email válido.', 'eipsi-forms'),
            'enterEmail' => __('Ingresá tu email para comenzar', 'eipsi-forms'),
            'magicLinkButton' => __('Enviarme link mágico ✨', 'eipsi-forms'),
            'checkingEmail' => __('Enviando link...', 'eipsi-forms'),
            'welcomeBack' => __('¡Te extrañamos! Continuemos...', 'eipsi-forms'),
            'loginRequired' => __('Iniciá sesión para participar', 'eipsi-forms')
        )
    ));
}

/**
 * Enqueue Survey Login assets (frontend)
 * 
 * @since 1.4.0
 * @since 1.6.0 - Enhanced with progress indicators and magic link support
 */
add_action('wp_enqueue_scripts', 'eipsi_enqueue_survey_login_assets');
function eipsi_enqueue_survey_login_assets() {
    // Detectar si hay shortcode [eipsi_survey_login] en la página actual
    global $post;
    if (!is_a($post, 'WP_Post')) return;
    
    if (has_shortcode($post->post_content, 'eipsi_survey_login') || 
        has_shortcode($post->post_content, 'eipsi_participant_dashboard')) {
        
        // Enhanced login styles
        wp_enqueue_style(
            'eipsi-survey-login-css',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/survey-login-enhanced.css',
            array('eipsi-theme-toggle-css'),
            EIPSI_FORMS_VERSION
        );
        
        // Enhanced login scripts
        wp_enqueue_script(
            'eipsi-survey-login-js',
            EIPSI_FORMS_PLUGIN_URL . 'assets/js/survey-login-enhanced.js',
            array('jquery', 'eipsi-participant-auth'),
            EIPSI_FORMS_VERSION,
            true
        );
        
        // Participant dashboard styles
        wp_enqueue_style(
            'eipsi-participant-dashboard-css',
            EIPSI_FORMS_PLUGIN_URL . 'assets/css/participant-dashboard.css',
            array('eipsi-theme-toggle-css'),
            EIPSI_FORMS_VERSION
        );
        
        // Participant dashboard scripts
        wp_enqueue_script(
            'eipsi-participant-dashboard-js',
            EIPSI_FORMS_PLUGIN_URL . 'assets/js/participant-dashboard.js',
            array('jquery', 'eipsi-participant-auth'),
            EIPSI_FORMS_VERSION,
            true
        );
        
        // Localize dashboard script
        wp_localize_script('eipsi-participant-dashboard-js', 'eipsiParticipantDashboardL10n', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eipsi_participant_dashboard'),
            'strings' => array(
                'confirm_logout' => __('¿Estás seguro de que quieres cerrar sesión?', 'eipsi-forms'),
                'logging_out' => __('Cerrando sesión...', 'eipsi-forms'),
                'logout_success' => __('Sesión cerrada correctamente', 'eipsi-forms'),
                'logout_error' => __('Error al cerrar sesión', 'eipsi-forms')
            )
        ));
    }
}

/**
 * Enqueue Participant UX Enhanced Assets (v1.7.0)
 *
 * Carga los assets mejorados para UX de participantes con
 * enfoque en empatía y experiencia humana.
 *
 * @since 1.7.0
 */
add_action('wp_enqueue_scripts', 'eipsi_enqueue_participant_ux_assets');
function eipsi_enqueue_participant_ux_assets() {
    // Detectar si hay formularios EIPSI en la página
    global $post;
    if (!is_a($post, 'WP_Post')) return;

    $has_eipsi_form = has_shortcode($post->post_content, 'eipsi_form') ||
                      has_shortcode($post->post_content, 'eipsi_survey_form') ||
                      has_shortcode($post->post_content, 'eipsi_longitudinal_study') ||
                      has_shortcode($post->post_content, 'eipsi_survey_login') ||
                      has_shortcode($post->post_content, 'eipsi_participant_dashboard');

    if (!$has_eipsi_form) {
        return;
    }

    // Participant UX Enhanced Styles
    wp_enqueue_style(
        'eipsi-participant-ux-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/participant-ux-enhanced.css',
        array('eipsi-theme-toggle-css'),
        EIPSI_FORMS_VERSION
    );

    // Participant UX Enhanced Scripts
    wp_enqueue_script(
        'eipsi-participant-ux-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/participant-ux-enhanced.js',
        array('jquery'),
        EIPSI_FORMS_VERSION,
        true
    );

    // Localize script con strings traducibles
    wp_localize_script('eipsi-participant-ux-js', 'eipsiParticipantUX', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_participant_ux'),
        'strings' => array(
            'progress' => __('Progreso', 'eipsi-forms'),
            'questionsAnswered' => __('preguntas respondidas', 'eipsi-forms'),
            'of' => __('de', 'eipsi-forms'),
            'celebrate' => __('¡Genial!', 'eipsi-forms'),
            'complete' => __('¡Felicidades! Completaste todas las preguntas', 'eipsi-forms'),
            'sectionComplete' => __('¡Excelente! Completaste esta sección', 'eipsi-forms'),
            'helpTitle' => __('¿Por qué preguntamos esto?', 'eipsi-forms'),
            'expandHelp' => __('Ver más contexto', 'eipsi-forms'),
            'collapseHelp' => __('Mostrar menos', 'eipsi-forms')
        )
    ));
}

// Registrar el shortcode de aleatorización pública (legacy)
add_action('init', function() {
    if (function_exists('eipsi_randomized_form_shortcode')) {
        add_shortcode('eipsi_randomized_form', 'eipsi_randomized_form_shortcode');
    }
    if (function_exists('eipsi_randomized_form_page_shortcode')) {
        add_shortcode('eipsi_randomized_form_page', 'eipsi_randomized_form_page_shortcode');
    }
});

/**
 * v2.5.3 - Wake-up autónomo: Procesar jobs pendientes en cada visita al sitio
 * Los participantes mantienen el sistema funcionando automáticamente
 */
add_action('wp_loaded', 'eipsi_wake_up_job_processor', 20);

function eipsi_wake_up_job_processor() {
    // Solo ejecutar en requests normales (no AJAX, no CRON, no REST API)
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;
    
    // Verificar si la clase existe
    if (!class_exists('EIPSI_Nudge_Job_Queue')) {
        return;
    }
    
    // Contar jobs urgentes pendientes
    $pending_count = EIPSI_Nudge_Job_Queue::count_pending_urgent();
    
    // Solo procesar si hay jobs pendientes
    if ($pending_count > 0) {
        // Procesar máximo 2 jobs para no relentizar la página
        $stats = EIPSI_Nudge_Job_Queue::process_batch(2);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[EIPSI WakeUp] Auto-processed jobs via page visit: %d completed, %d retried, %d failed',
                $stats['completed'],
                $stats['retried'],
                $stats['failed']
            ));
        }
    }
}

/**
 * Crear página especial para acceso aleatorizado
 * DESHABILITADO por defecto (v1.3.17) - Ya no se crea automáticamente
 * Si se necesita, el usuario puede crear la página manualmente y agregar el shortcode
 */
// function eipsi_create_randomization_page() {
//     // Buscar si ya existe
//     $page = get_page_by_path('estudio-aleatorio');
//     
//     if (!$page) {
//         // Crear página si no existe
//         $page_id = wp_insert_post(array(
//             'post_title' => __('Estudio Aleatorizado', 'eipsi-forms'),
//             'post_name' => 'estudio-aleatorio',
//             'post_type' => 'page',
//             'post_status' => 'publish',
//             'post_content' => '[eipsi_randomized_form_page]'
//         ));
//         
//         if ($page_id && !is_wp_error($page_id)) {
//             update_option('eipsi_randomization_page_id', $page_id);
//             error_log('[EIPSI Forms] Página de aleatorización creada: /estudio-aleatorio/');
//         }
//     }
// }

// // Ejecutar en activación del plugin
// add_action('eipsi_forms_activation', 'eipsi_create_randomization_page');

function eipsi_forms_activate() {
    global $wpdb;

    // Crear página de aleatorización (DESHABILITADO v1.3.17)
    // eipsi_create_randomization_page();

    $charset_collate = $wpdb->get_charset_collate();

    // === Cron Reminders Scheduling (Fase 2 - Legacy) ===
    // Schedule daily reminders
    if (!wp_next_scheduled('eipsi_send_take_reminders_daily')) {
        wp_schedule_event(time(), 'daily', 'eipsi_send_take_reminders_daily');
    }

    // Schedule weekly reminders
    if (!wp_next_scheduled('eipsi_send_take_reminders_weekly')) {
        wp_schedule_event(time(), 'weekly', 'eipsi_send_take_reminders_weekly');
    }

    // === Cron Reminders Scheduling (Task 4.2 - Longitudinal) ===
    // Schedule wave reminders every 5 minutes (for faster email delivery when waves become available)
    if (!wp_next_scheduled('eipsi_send_wave_reminders_hourly')) {
        wp_schedule_event(time(), 'every_5_minutes', 'eipsi_send_wave_reminders_hourly');
    }

    // Schedule dropout recovery every 5 minutes
    if (!wp_next_scheduled('eipsi_send_dropout_recovery_hourly')) {
        wp_schedule_event(time(), 'every_5_minutes', 'eipsi_send_dropout_recovery_hourly');
    }
    
    // Phase 2: Schedule daily purge of access logs (GDPR compliance)
    if (!wp_next_scheduled('eipsi_purge_access_logs_daily')) {
        wp_schedule_event(time(), 'daily', 'eipsi_purge_access_logs_daily');
    }
    
    // Double Opt-In: Schedule daily cleanup of unconfirmed participants
    if (!wp_next_scheduled('eipsi_cleanup_unconfirmed_participants_daily')) {
        wp_schedule_event(time(), 'daily', 'eipsi_cleanup_unconfirmed_participants_daily');
    }
    
    // Create form results table
    $table_name = $wpdb->prefix . 'vas_form_results';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        form_id varchar(20) DEFAULT NULL,
        participant_id varchar(20) DEFAULT NULL,
        survey_id INT(11) DEFAULT NULL,
        wave_index INT(11) DEFAULT NULL,
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
        KEY participant_survey_wave (participant_id, survey_id, wave_index),
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

    // ============================================================================
    // PHASE 3 TABLES - Researcher Data Confidence
    // ============================================================================

    // Create cron log table
    $cron_log_table = $wpdb->prefix . 'survey_cron_log';
    $sql_cron_log = "CREATE TABLE IF NOT EXISTS $cron_log_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        cron_hook VARCHAR(100) NOT NULL,
        executed_at DATETIME NOT NULL,
        metadata TEXT,
        PRIMARY KEY (id),
        KEY cron_hook (cron_hook),
        KEY executed_at (executed_at)
    ) $charset_collate;";
    dbDelta($sql_cron_log);

    // Create data requests table (GDPR)
    $data_requests_table = $wpdb->prefix . 'survey_data_requests';
    $sql_data_requests = "CREATE TABLE IF NOT EXISTS $data_requests_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        participant_id BIGINT(20) UNSIGNED NOT NULL,
        survey_id BIGINT(20) UNSIGNED NOT NULL,
        request_type VARCHAR(20) NOT NULL,
        reason TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        admin_id BIGINT(20) UNSIGNED,
        admin_notes TEXT,
        result_data TEXT,
        created_at DATETIME NOT NULL,
        started_processing_at DATETIME,
        processed_at DATETIME,
        PRIMARY KEY (id),
        KEY participant_id (participant_id),
        KEY survey_id (survey_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";
    dbDelta($sql_data_requests);

    // ============================================================================
    // END PHASE 3 TABLES
    // ============================================================================

    // ============================================================================
    // POOL STUDIES TABLES - Phase 1 of Pool Randomization System (v2.5.3)
    // ============================================================================

    // Create pool assignments table - tracks participant assignments to pool studies
    $pool_assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';
    $sql_pool_assignments = "CREATE TABLE IF NOT EXISTS $pool_assignments_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        pool_id BIGINT(20) UNSIGNED NOT NULL,
        participant_id VARCHAR(255) NOT NULL,
        study_id BIGINT(20) UNSIGNED NOT NULL,
        assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        first_access DATETIME DEFAULT NULL,
        last_access DATETIME DEFAULT NULL,
        access_count INT(11) DEFAULT 0,
        completed TINYINT(1) DEFAULT 0,
        completed_at DATETIME DEFAULT NULL,
        completion_form_id VARCHAR(20) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_pool_id (pool_id),
        KEY idx_participant_id (participant_id),
        KEY idx_study_id (study_id),
        UNIQUE KEY unique_pool_participant (pool_id, participant_id)
    ) $charset_collate;";
    dbDelta($sql_pool_assignments);

    // Create pool analytics table - daily metrics per study per pool
    $pool_analytics_table = $wpdb->prefix . 'eipsi_pool_analytics';
    $sql_pool_analytics = "CREATE TABLE IF NOT EXISTS $pool_analytics_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        pool_id BIGINT(20) UNSIGNED NOT NULL,
        date DATE NOT NULL,
        study_id BIGINT(20) UNSIGNED NOT NULL,
        assignments INT(11) DEFAULT 0,
        completions INT(11) DEFAULT 0,
        cumulative_assignments INT(11) DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_pool_date (pool_id, date),
        KEY idx_study_id (study_id)
    ) $charset_collate;";
    dbDelta($sql_pool_analytics);

    // ============================================================================
    // END POOL STUDIES TABLES
    // ============================================================================

    // Store schema version
    update_option('eipsi_db_schema_version', '1.2.3');

    // Log activation
    error_log('[EIPSI Forms] Plugin activated - Schema v1.2.2 installed');
}

register_activation_hook(__FILE__, 'eipsi_forms_activate');
register_deactivation_hook(__FILE__, 'eipsi_forms_deactivate');

/**
 * Sincronizar schema longitudinal/RCT al activar plugin
 *
 * @since 1.4.0
 */
register_activation_hook(EIPSI_FORMS_PLUGIN_FILE, function() {
    // Run database migration to fix corrupt indexes (v2.0.1)
    if (function_exists('eipsi_migrate_fix_corrupt_indexes')) {
        eipsi_migrate_fix_corrupt_indexes();
    }

    // Sync tables after migration
    do_action('eipsi_sync_longitudinal_tables');
    do_action('eipsi_sync_rct_tables');
});

/**
 * Sincronizar schema longitudinal cada vez que se carga el plugin
 *
 * @since 1.4.0
 */
add_action('plugins_loaded', function() {
    do_action('eipsi_sync_longitudinal_tables');
}, 5);

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
    
    // Add custom intervals for study cron jobs
    if (!isset($schedules['eipsi_daily'])) {
        $schedules['eipsi_daily'] = array(
            'interval' => DAY_IN_SECONDS,
            'display' => __('Once Daily (EIPSI)', 'eipsi-forms'),
        );
    }
    
    if (!isset($schedules['eipsi_weekly'])) {
        $schedules['eipsi_weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly (EIPSI)', 'eipsi-forms'),
        );
    }
    
    if (!isset($schedules['eipsi_monthly'])) {
        $schedules['eipsi_monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display' => __('Once Monthly (EIPSI)', 'eipsi-forms'),
        );
    }
    
    // v2.2.2 - Intervalo cada 5 minutos para emails de waves disponibles
    if (!isset($schedules['every_5_minutes'])) {
        $schedules['every_5_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __('Every 5 Minutes', 'eipsi-forms'),
        );
    }
    
    return $schedules;
});

/**
 * Cleanup scheduled cron events on deactivation
 */
function eipsi_forms_deactivate() {
    // Legacy cron jobs
    wp_clear_scheduled_hook('eipsi_send_take_reminders_daily');
    wp_clear_scheduled_hook('eipsi_send_take_reminders_weekly');

    // Task 4.2 cron jobs (longitudinal)
    wp_clear_scheduled_hook('eipsi_send_wave_reminders_hourly');
    wp_clear_scheduled_hook('eipsi_send_dropout_recovery_hourly');

    // Study cron jobs (v1.5.3)
    wp_clear_scheduled_hook('eipsi_study_cron_job');
    
    // Phase 2: Access log purge
    wp_clear_scheduled_hook('eipsi_purge_access_logs_daily');
    
    // Double Opt-In cleanup cron
    wp_clear_scheduled_hook('eipsi_cleanup_unconfirmed_participants_daily');
}

/**
 * Phase 2: Daily purge of old access logs (GDPR compliance)
 */
add_action('eipsi_purge_access_logs_daily', 'eipsi_purge_access_logs_handler');
function eipsi_purge_access_logs_handler() {
    if (!class_exists('EIPSI_Participant_Access_Log_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-participant-access-log-service.php';
    }
    
    $retention_days = (int) get_option('eipsi_access_log_retention_days', 365);
    $deleted = EIPSI_Participant_Access_Log_Service::purge_old_logs($retention_days);
    
    if ($deleted > 0) {
        error_log("[EIPSI Forms] Purged {$deleted} old access log records (retention: {$retention_days} days)");
    }
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

        // Enqueue email test script
        wp_enqueue_script(
            'eipsi-email-test-script',
            EIPSI_FORMS_PLUGIN_URL . 'assets/js/email-test.js',
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
            'deleteError' => __('Failed to delete data. Please check the error logs.', 'eipsi-forms'),
            'smtpFillAllFields' => __('Completa servidor, puerto y usuario SMTP.', 'eipsi-forms'),
            'smtpTestError' => __('Error al probar SMTP. Verificá las credenciales.', 'eipsi-forms'),
            'smtpTestFirst' => __('Probá el SMTP antes de guardar.', 'eipsi-forms'),
            'smtpSaveError' => __('No se pudo guardar la configuración SMTP.', 'eipsi-forms'),
            'smtpDisableError' => __('No se pudo desactivar el SMTP.', 'eipsi-forms'),
            'smtpConfirmDisable' => __('¿Seguro que querés desactivar el SMTP? Se usará wp_mail().', 'eipsi-forms'),
            'smtpDisableLabel' => __('Desactivar SMTP', 'eipsi-forms'),
            'smtpActive' => __('SMTP activo', 'eipsi-forms'),
            'smtpInactive' => __('SMTP inactivo', 'eipsi-forms'),
            'smtpNoConfig' => __('No hay configuración SMTP activa. Los correos se enviarán con wp_mail().', 'eipsi-forms'),
            'smtpHostLabel' => __('Servidor:', 'eipsi-forms'),
            'smtpPortLabel' => __('Puerto:', 'eipsi-forms'),
            'smtpUserLabel' => __('Usuario:', 'eipsi-forms'),
            'smtpEncryptionLabel' => __('Seguridad:', 'eipsi-forms')
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

/**
 * Enqueue CSS & JS for Block Editor (Gutenberg WYSIWYG)
 *
 * Asegura que los estilos principales se carguen en el preview del editor
 * para que las CSS variables aplicadas por edit.js se rendericen correctamente.
 *
 * @since 1.3.8
 */
function eipsi_forms_enqueue_block_editor_assets() {
    // === CARGAR TOKENS PRIMERO (single source of truth) ===
    wp_enqueue_style(
        'eipsi-tokens',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-tokens.css',
        array(),
        EIPSI_FORMS_VERSION
    );

    // === CARGAR CSS PRINCIPALES ===
    // 1. CSS del formulario principal - CONSUME las CSS variables
    wp_enqueue_style(
        'eipsi-forms-styles',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-forms.css',
        array('eipsi-tokens'),
        EIPSI_FORMS_VERSION
    );

    // 2. Estilos de admin (para coherencia visual en el editor)
    wp_enqueue_style(
        'eipsi-admin-style',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/admin-style.css',
        array('eipsi-tokens'),
        EIPSI_FORMS_VERSION
    );

    // 3. CSS de tema (para dark mode en editor)
    wp_enqueue_style(
        'eipsi-theme-toggle',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/theme-toggle.css',
        array('eipsi-tokens'),
        EIPSI_FORMS_VERSION
    );

    // 4. CSS de aleatorización (para randomization controls)
    wp_enqueue_style(
        'eipsi-randomization',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-randomization.css',
        array('eipsi-tokens'),
        EIPSI_FORMS_VERSION
    );
}

// Hook CRÍTICO: Ejecutar ANTES de que se registren los bloques
add_action('enqueue_block_editor_assets', 'eipsi_forms_enqueue_block_editor_assets');

/**
 * Enqueue CSS & JS for FRONTEND (página publicada)
 *
 * Versión completa con soporte para:
 * - Block styles
 * - Fingerprinting para aleatorización RCT
 * - Tracking de progreso
 * - Randomization system
 * - Dark mode
 *
 * @since 1.3.12
 */
function eipsi_forms_enqueue_frontend_assets() {
    // Solo en frontend, NO en admin
    if (is_admin()) {
        return;
    }

    // Enqueue design tokens FIRST (single source of truth)
    wp_enqueue_style(
        'eipsi-tokens',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-tokens.css',
        array(),
        EIPSI_FORMS_VERSION
    );

    // Enqueue main form CSS (no longer uses build/style-index.css - removed in v1.3.10 CSS refactor)
    wp_enqueue_style(
        'eipsi-forms-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-forms.css',
        array('eipsi-tokens'),  // Depends on tokens
        EIPSI_FORMS_VERSION
    );

    // Dark mode theme toggle styles - CRITICAL for all form fields
    wp_enqueue_style(
        'eipsi-theme-toggle-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/theme-toggle.css',
        array('eipsi-tokens', 'eipsi-forms-css'),
        EIPSI_FORMS_VERSION
    );

    // Fingerprinting script para aleatorización RCT (v1.3.1)
    wp_enqueue_script(
        'eipsi-fingerprint-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-fingerprint.js',
        array(),
        EIPSI_FORMS_VERSION,
        true
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
        'savePartialNonce' => wp_create_nonce('eipsi_save_partial'),
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

    // === SAVE & CONTINUE SYSTEM (v1.3.15 - CRITICAL FIX) ===
    // CSS del modal de recuperación de sesión
    wp_enqueue_style(
        'eipsi-save-continue-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-save-continue.css',
        array('eipsi-forms-css'),
        EIPSI_FORMS_VERSION
    );

    // JS de Save & Continue: autosave + IndexedDB + modal de recuperación
    wp_enqueue_script(
        'eipsi-save-continue-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-save-continue.js',
        array('eipsi-forms-js'),
        EIPSI_FORMS_VERSION,
        true
    );
    // === FIN SAVE & CONTINUE ===

    // Enqueue Randomization Public System styles (Fase 3)
    wp_enqueue_style(
        'eipsi-randomization-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-randomization.css',
        array('eipsi-theme-toggle-css'),
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

    // === LOGIN GATE SYSTEM (Task 1.3) ===
    // CSS del login gate
    wp_enqueue_style(
        'eipsi-login-gate-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/login-gate.css',
        array('eipsi-theme-toggle-css'),
        EIPSI_FORMS_VERSION
    );

    // JS del login gate
    wp_enqueue_script(
        'eipsi-login-gate-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/login-gate.js',
        array('jquery'),
        EIPSI_FORMS_VERSION,
        true
    );
}

// Hook para FRONTEND (página publicada) - Solo una vez
add_action('wp_enqueue_scripts', 'eipsi_forms_enqueue_frontend_assets');

function eipsi_forms_register_blocks() {
    if (!function_exists('register_block_type')) {
        return;
    }

    // Pass admin nonce to block editor for AJAX calls (e.g., eipsi_get_forms_list)
    // Also pass permalink and postId for randomization link generation
    $current_post_id = isset($post) ? $post->ID : (isset($_GET['post']) ? intval($_GET['post']) : 0);
    $permalink = $current_post_id ? get_permalink($current_post_id) : '';

    // Register editor data script (for AJAX calls)
    wp_register_script(
        'eipsi-blocks-editor-data',
        '',
        array(),
        EIPSI_FORMS_VERSION,
        true
    );

    wp_localize_script('eipsi-blocks-editor-data', 'eipsiEditorData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_admin_nonce'),
        'permalink' => $permalink,
        'postId' => $current_post_id,
    ));

    // Backward compatibility: also expose as window.eipsiAdminNonce
    wp_add_inline_script('eipsi-blocks-editor-data',
        'window.eipsiAdminNonce = eipsiEditorData?.nonce || "";',
        'after'
    );

    // Registrar automáticamente todos los bloques desde build/blocks/
    $blocks_dir = EIPSI_FORMS_PLUGIN_DIR . 'build/blocks';
    
    if (!is_dir($blocks_dir)) {
        return;
    }
    
    $block_folders = scandir($blocks_dir);
    
    foreach ($block_folders as $block_folder) {
        // Skip . and ..
        if ($block_folder === '.' || $block_folder === '..' || $block_folder === 'index') {
            continue;
        }
        
        $block_json_path = $blocks_dir . '/' . $block_folder . '/block.json';
        
        // Registrar bloque si existe su block.json
        if (file_exists($block_json_path)) {
            // Special handling for randomization block with render_callback
            if ($block_folder === 'randomization-block') {
                register_block_type($block_json_path, array(
                    'render_callback' => 'eipsi_render_randomization_block'
                ));
            }
            // Special handling for pool block with render_callback (v2.5.3)
            elseif ($block_folder === 'pool-block') {
                register_block_type($block_json_path, array(
                    'render_callback' => 'eipsi_render_pool_join_block'
                ));
            } else {
                register_block_type($block_json_path);
            }
        }
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

/**
 * Render callback para bloque de aleatorización (KISS flow)
 * 
 * Procesa el shortcode guardado en el bloque y lo renderiza
 * 
 * @param array $attributes Atributos del bloque
 * @return string HTML output
 */
function eipsi_render_randomization_block($attributes) {
    $shortcode = isset($attributes['generatedShortcode']) ? $attributes['generatedShortcode'] : '';

    if (empty($shortcode)) {
        return '<div class="eipsi-randomization-notice">' .
               '<p>' . esc_html__('Configurá el bloque de aleatorización para mostrar un formulario.', 'eipsi-forms') . '</p>' .
               '</div>';
    }

    // Enqueue assets necesarios para aleatorización
    eipsi_forms_enqueue_frontend_assets();

    // Procesar el shortcode
    return do_shortcode($shortcode);
}

/**
 * Render callback para bloque Pool de Estudios (v2.5.3)
 * 
 * Renderiza el bloque de asignación a pools longitudinales
 * 
 * @param array $attributes Atributos del bloque
 * @return string HTML output
 */
function eipsi_render_pool_join_block($attributes) {
    // Usar la clase renderer para mantener consistencia
    if (!class_exists('EIPSI_Pool_Block_Renderer')) {
        return '<div class="eipsi-pool-error">' .
               '<p>' . esc_html__('Error: Pool Block Renderer no disponible.', 'eipsi-forms') . '</p>' .
               '</div>';
    }

    return EIPSI_Pool_Block_Renderer::render_block($attributes);
}

function eipsi_forms_enqueue_block_assets($content) {
    $blocks = array(
        'eipsi/form-container',
        'eipsi/consent-block',
        'eipsi/campo-texto',
        'eipsi/campo-textarea',
        'eipsi/campo-descripcion',
        'eipsi/campo-select',
        'eipsi/campo-radio',
        'eipsi/campo-multiple',
        'eipsi/campo-likert',
        'eipsi/vas-slider',
        'eipsi/pool-join', // v2.5.3 - Pool de Estudios
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

// Register admin post handlers
add_action('admin_post_eipsi_forms_export_excel', 'eipsi_export_to_excel');
add_action('admin_post_eipsi_save_pool', 'eipsi_handle_save_pool');
// Deletion and editing of results are handled via admin_init in admin/handlers.php and admin/results-page.php

/**
 * Load plugin text domain for translations
 */
function eipsi_forms_load_textdomain() {
    load_plugin_textdomain(
        'eipsi-forms',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'eipsi_forms_load_textdomain');

/**
 * Show admin notice if SMTP is not configured but Double Opt-In is enabled
 * 
 * @since 1.5.0
 */
add_action('admin_notices', 'eipsi_smtp_configuration_notice');
function eipsi_smtp_configuration_notice() {
    // Only show to users who can manage options
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Only show on EIPSI admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'eipsi') === false) {
        return;
    }
    
    // Check if Double Opt-In is enabled
    $double_optin_enabled = defined('EIPSI_DOUBLE_OPTIN_ENABLED') ? EIPSI_DOUBLE_OPTIN_ENABLED : true;
    if (!$double_optin_enabled) {
        return;
    }
    
    // Check SMTP configuration
    if (!class_exists('EIPSI_SMTP_Service')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-smtp-service.php';
    }
    
    $smtp_service = new EIPSI_SMTP_Service();
    if ($smtp_service->is_configured()) {
        return; // SMTP is configured, no notice needed
    }
    
    // Show warning notice
    $config_url = admin_url('admin.php?page=eipsi-configuration&tab=smtp');
    ?>
    <div class="notice notice-warning is-dismissible">
        <p>
            <strong><?php echo esc_html__('EIPSI Forms - Double Opt-In Requiere SMTP', 'eipsi-forms'); ?></strong>
        </p>
        <p>
            <?php echo esc_html__('El sistema Double Opt-In está habilitado pero SMTP no está configurado. Los participantes no recibirán emails de confirmación.', 'eipsi-forms'); ?>
        </p>
        <p>
            <a href="<?php echo esc_url($config_url); ?>" class="button button-primary">
                <?php echo esc_html__('Configurar SMTP Ahora', 'eipsi-forms'); ?>
            </a>
        </p>
    </div>
    <?php
}

/**
 * Handle saving pool data from admin form
 *
 * @since 2.5.3 - Moved from pool-hub-v2.php to ensure handler is always registered
 */
function eipsi_handle_save_pool() {
    error_log('[EIPSI-POOL] Handler eipsi_handle_save_pool executing');
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['pool_nonce'] ?? '', 'eipsi_save_pool_nonce')) {
        error_log('[EIPSI-POOL] Nonce verification failed');
        wp_die(__('Error de seguridad. Por favor, recargá la página.', 'eipsi-forms'));
    }
    
    // Check permissions
    if (!function_exists('eipsi_user_can_manage_longitudinal') || !eipsi_user_can_manage_longitudinal()) {
        error_log('[EIPSI-POOL] Permission check failed');
        wp_die(__('No tenés permisos para realizar esta acción.', 'eipsi-forms'));
    }
    
    global $wpdb;
    
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    
    // Get form data
    $pool_id = intval($_POST['pool_id'] ?? 0);
    $pool_name = sanitize_text_field($_POST['pool_name'] ?? '');
    $pool_description = sanitize_textarea_field($_POST['pool_description'] ?? '');
    $pool_incentive = sanitize_textarea_field($_POST['pool_incentive'] ?? '');
    $method = sanitize_key($_POST['method'] ?? 'seeded');
    $notify_on_completion = !empty($_POST['notify_on_completion']);
    $studies_data = json_decode(stripslashes($_POST['pool_studies_data'] ?? '[]'), true);
    
    error_log('[EIPSI-POOL] Received data: pool_id=' . $pool_id . ', name=' . $pool_name . ', studies=' . count($studies_data));
    
    if (empty($pool_name)) {
        wp_die(__('El nombre del pool es obligatorio.', 'eipsi-forms'));
    }
    
    if (empty($pool_description)) {
        wp_die(__('La descripción del pool es obligatoria.', 'eipsi-forms'));
    }
    
    // Extract study IDs and probabilities
    $study_ids = array();
    $probabilities = array();
    $total_prob = 0;
    
    foreach ($studies_data as $item) {
        if (!empty($item['study_id']) && isset($item['probability'])) {
            $study_ids[] = intval($item['study_id']);
            $prob = floatval($item['probability']);
            $probabilities[] = $prob;
            $total_prob += $prob;
        }
    }
    
    error_log('[EIPSI-POOL] Processed ' . count($study_ids) . ' studies, total_prob=' . $total_prob);
    
    // Get redirect mode (default: transition)
    $redirect_mode = sanitize_key($_POST['redirect_mode'] ?? 'transition');
    if (!in_array($redirect_mode, ['transition', 'minimal'])) {
        $redirect_mode = 'transition';
    }
    
    // Build config JSON (Fase 4: incluye notify_on_completion, incentive, redirect_mode)
    $config = array(
        'studies' => $studies_data,
        'method' => $method,
        'notify_on_completion' => $notify_on_completion,
        'incentive_message' => $pool_incentive,
        'redirect_mode' => $redirect_mode,
        'updated_at' => current_time('mysql'),
    );

    // Validate total is 100% (with 0.1% tolerance for floating point precision)
    if (abs($total_prob - 100) > 0.1) {
        wp_die(sprintf(__('La suma de probabilidades debe ser 100%% (±0.1%% tolerancia). Actual: %.2f%%', 'eipsi-forms'), $total_prob));
    }

    $now = current_time('mysql');

    if ($pool_id > 0) {
        // Update existing pool
        $wpdb->update(
            $pools_table,
            array(
                'pool_name' => $pool_name,
                'pool_description' => $pool_description,
                'studies' => json_encode($study_ids),
                'probabilities' => json_encode($probabilities),
                'method' => $method,
                'config' => json_encode($config),
                'updated_at' => $now
            ),
            array('id' => $pool_id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        error_log('[EIPSI-POOL] Updated existing pool ID: ' . $pool_id);
    } else {
        // Create new pool
        $wpdb->insert(
            $pools_table,
            array(
                'pool_name' => $pool_name,
                'pool_description' => $pool_description,
                'studies' => json_encode($study_ids),
                'probabilities' => json_encode($probabilities),
                'method' => $method,
                'config' => json_encode($config),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        $pool_id = $wpdb->insert_id;
        error_log('[EIPSI-POOL] Created new pool ID: ' . $pool_id);
    }
    
    // Auto-create WordPress page for this pool
    $page_id = eipsi_create_pool_page($pool_id, $pool_name);
    $page_url = $page_id ? get_permalink($page_id) : null;
    
    error_log('[EIPSI-POOL] Page created: ' . ($page_id ? $page_id : 'failed'));
    
    // Redirect back with success message
    $message_type = $pool_id > 0 ? 'updated' : 'created';
    $redirect_url = admin_url('admin.php?page=eipsi-longitudinal-study&tab=pool-hub&message=pool_' . $message_type);
    if ($page_url) {
        $redirect_url .= '&page_url=' . urlencode($page_url);
    }
    
    error_log('[EIPSI-POOL] Redirecting to: ' . $redirect_url);
    
    wp_redirect($redirect_url);
    exit;
}

/**
 * Create WordPress page for pool with shortcode
 *
 * @param int    $pool_id   Pool ID
 * @param string $pool_name Pool name
 * @return int|false Page ID or false on failure
 * @since 2.5.0
 */
function eipsi_create_pool_page($pool_id, $pool_name) {
    $pool_slug = 'pool-' . sanitize_title($pool_name);
    
    // Check if page already exists
    $existing_page = get_page_by_path($pool_slug);
    
    if (!$existing_page) {
        $existing_pages = get_posts(array(
            'post_type' => 'page',
            'meta_key' => 'eipsi_pool_id',
            'meta_value' => $pool_id,
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_pages)) {
            $existing_page = $existing_pages[0];
        }
    }
    
    if ($existing_page) {
        update_post_meta($existing_page->ID, 'eipsi_pool_id', $pool_id);
        return $existing_page->ID;
    }
    
    // Create new page
    $page_title = sprintf(__('Pool: %s', 'eipsi-forms'), $pool_name);
    $page_content = '<!-- wp:shortcode -->[eipsi_pool pool_id="' . esc_attr($pool_id) . '"]<!-- /wp:shortcode -->';
    
    $page_id = wp_insert_post(array(
        'post_title' => $page_title,
        'post_name' => $pool_slug,
        'post_content' => $page_content,
        'post_status' => 'publish',
        'post_type' => 'page',
        'meta_input' => array(
            'eipsi_pool_id' => $pool_id
        )
    ));
    
    if (is_wp_error($page_id)) {
        error_log('[EIPSI] Failed to create pool page: ' . $page_id->get_error_message());
        return false;
    }
    
    return $page_id;
}

/**
 * ============================================================================
 * FASE 6 - Migración de pools a formato v2
 * ============================================================================
 *
 * Convierte la configuración vieja de pools al nuevo formato estructurado.
 * Idempotente: solo corre una vez por sitio.
 *
 * @since 2.5.3
 */
function eipsi_migrate_pools_to_v2() {
    // Solo correr una vez
    if ( get_option( 'eipsi_pools_migrated_v2' ) ) {
        return;
    }

    global $wpdb;

    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';

    // Verificar que la tabla existe
    $table_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $pools_table
        )
    );

    if ( ! $table_exists ) {
        error_log( '[EIPSI Pool] Migración v2: tabla de pools no existe, nada que migrar.' );
        update_option( 'eipsi_pools_migrated_v2', true );
        return;
    }

    $pools = $wpdb->get_results( "SELECT * FROM {$pools_table}" );

    if ( empty( $pools ) ) {
        error_log( '[EIPSI Pool] Migración v2: no hay pools para migrar.' );
        update_option( 'eipsi_pools_migrated_v2', true );
        return;
    }

    $migrated_count = 0;

    foreach ( $pools as $pool ) {
        // Obtener config vieja (puede estar en columna config o ser null)
        $old_config = json_decode( $pool->config ?? '{}', true );

        // Si ya tiene el formato nuevo (versión 2), saltear
        if ( isset( $old_config['version'] ) && $old_config['version'] >= 2 ) {
            continue;
        }

        // Construir array de estudios con probabilidades
        $studies_array = array();
        $old_studies   = json_decode( $pool->studies ?? '[]', true );
        $old_probs     = json_decode( $pool->probabilities ?? '[]', true );

        if ( is_array( $old_studies ) ) {
            foreach ( $old_studies as $index => $study_id ) {
                $studies_array[] = array(
                    'study_id'    => intval( $study_id ),
                    'probability' => isset( $old_probs[ $index ] ) ? floatval( $old_probs[ $index ] ) : 0,
                );
            }
        }

        // Construir nuevo formato de config
        $new_config = array(
            'studies'              => $studies_array,
            'method'               => $old_config['method'] ?? 'seeded',
            'allow_reassignment'   => $old_config['allow_reassignment'] ?? false,
            'notify_on_completion' => $old_config['notify_on_completion'] ?? false,
            'paused_message'       => $old_config['paused_message'] ?? __( 'Este estudio no está disponible en este momento.', 'eipsi-forms' ),
            'migrated_at'          => current_time( 'mysql' ),
            'version'              => 2,
        );

        // Actualizar el pool con el nuevo config
        $updated = $wpdb->update(
            $pools_table,
            array( 'config' => wp_json_encode( $new_config ) ),
            array( 'id'     => $pool->id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( $updated !== false ) {
            $migrated_count++;
            error_log( "[EIPSI Pool] Migrado pool ID {$pool->id} a formato v2" );
        } else {
            error_log( "[EIPSI Pool] ERROR: Falló migración del pool ID {$pool->id}: " . $wpdb->last_error );
        }
    }

    update_option( 'eipsi_pools_migrated_v2', true );
    error_log( "[EIPSI Pool] Migración v2 completada. {$migrated_count} pools migrados." );
}

/**
 * Hook para ejecutar la migración de pools en plugins_loaded
 *
 * @since 2.5.3
 */
add_action( 'plugins_loaded', function() {
    // Ejecutar migración de pools v2 después de que todas las tablas estén listas
    if ( is_admin() ) {
        eipsi_migrate_pools_to_v2();
    }
}, 20 );

/**
 * ============================================================================
 * FASE 1 - POOL ACCESS ENDPOINT (Nuevo sistema Pool de Estudios V2)
 * ============================================================================
 *
 * Maneja el acceso a pools mediante URL /pool/POOL_CODIGO/
 * Interfaz minimalista sin lista de estudios, sin duraciones.
 *
 * @since 2.5.4
 */

// 1. Registrar rewrite rules para pools
add_action('init', 'eipsi_register_pool_rewrite_rules', 10);

function eipsi_register_pool_rewrite_rules() {
    // Pattern: /pool/POOL_CODIGO/
    add_rewrite_rule(
        '^pool/([^/]+)/?$',
        'index.php?eipsi_pool_code=$matches[1]',
        'top'
    );
    
    // También: /estudio/ESTUDIO_CODIGO/ (para estudios individuales)
    add_rewrite_rule(
        '^estudio/([^/]+)/?$',
        'index.php?eipsi_study_code=$matches[1]',
        'top'
    );
    
    // Registrar query vars
    add_filter('query_vars', 'eipsi_add_pool_query_vars');
}

function eipsi_add_pool_query_vars($vars) {
    $vars[] = 'eipsi_pool_code';
    $vars[] = 'eipsi_study_code';
    return $vars;
}

// 2. Interceptar requests y servir el template apropiado
add_action('template_redirect', 'eipsi_handle_pool_access', 1);

function eipsi_handle_pool_access() {
    $pool_code = get_query_var('eipsi_pool_code');
    $study_code = get_query_var('eipsi_study_code');
    
    // Cargar helpers si no están cargados
    if (!function_exists('eipsi_get_valid_pool')) {
        $helpers_file = EIPSI_FORMS_PLUGIN_DIR . 'includes/helpers/pool-helpers.php';
        if (file_exists($helpers_file)) {
            require_once $helpers_file;
        }
    }
    
    // CASO A: Acceso a Pool
    if (!empty($pool_code)) {
        // Verificar que el pool existe y está activo
        $pool_data = eipsi_get_valid_pool($pool_code);
        
        if (!$pool_data) {
            // Pool no existe o no está activo - mostrar error 404
            wp_die(
                __('El pool solicitado no existe o no está disponible.', 'eipsi-forms'),
                __('Pool no encontrado', 'eipsi-forms'),
                array('response' => 404)
            );
        }
        
        // Verificar si el participante ya tiene asignación
        $participant_id = eipsi_get_participant_id();
        
        if ($participant_id) {
            $assignment = eipsi_get_pool_assignment($pool_code, $participant_id);
            
            if ($assignment) {
                // Ya asignado - redirigir al estudio
                $study_url = eipsi_get_study_url($assignment['study_id']);
                wp_redirect($study_url);
                exit;
            }
        }
        
        // Mostrar interfaz de acceso al pool
        eipsi_render_pool_access_page($pool_code, $pool_data);
        exit;
    }
    
    // CASO B: Acceso a Estudio Individual (placeholder para Fase 2)
    if (!empty($study_code)) {
        // Por ahora, dejar que el sistema existente maneje esto
        // En Fase 2 implementaremos el redireccionamiento post-asignación
        return;
    }
}

// 3. Handler para POST del formulario de pool
add_action('admin_post_nopriv_eipsi_pool_join', 'eipsi_handle_pool_join');
add_action('admin_post_eipsi_pool_join', 'eipsi_handle_pool_join');

function eipsi_handle_pool_join() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['pool_nonce'] ?? '', 'eipsi_pool_access')) {
        wp_die(__('Error de seguridad. Por favor, recargá la página.', 'eipsi-forms'));
    }
    
    // Obtener datos
    $pool_code = sanitize_text_field($_POST['pool_code'] ?? '');
    $email = sanitize_email($_POST['participant_email'] ?? '');
    $consent = !empty($_POST['pool_consent']);
    
    if (empty($pool_code) || empty($email) || !$consent) {
        wp_die(__('Por favor completá todos los campos y aceptá el consentimiento.', 'eipsi-forms'));
    }
    
    // Cargar helpers
    if (!function_exists('eipsi_get_valid_pool')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/helpers/pool-helpers.php';
    }
    
    // Verificar pool
    $pool_data = eipsi_get_valid_pool($pool_code);
    if (!$pool_data) {
        wp_die(__('El pool no está disponible.', 'eipsi-forms'));
    }
    
    // Obtener modo de redirección (default: transition)
    $redirect_mode = isset($pool_data['redirect_mode']) ? $pool_data['redirect_mode'] : 'transition';
    
    // Crear participante (usar email como ID para MVP)
    $participant_id = 'email_' . md5($email);
    eipsi_set_participant_cookie($participant_id);
    
    // Verificar si ya tiene asignación (doble check)
    $assignment = eipsi_get_pool_assignment($pool_code, $participant_id);
    
    if ($assignment) {
        // Ya asignado - redirigir directo al estudio (sin importar modo)
        wp_redirect(eipsi_get_study_url($assignment['study_id']));
        exit;
    }
    
    // Guardar email para referencia (en sesión o cookie adicional)
    setcookie('eipsi_participant_email', $email, time() + 365 * 24 * 60 * 60, '/');
    
    // EJECUTAR ALEATORIZACIÓN (Fase 2)
    $assignment = eipsi_pool_randomize($pool_code, $participant_id, $pool_data);
    
    if (!$assignment) {
        // Error en aleatorización (pool saturado o error BD)
        wp_die(__('No se pudo realizar la asignación. El pool puede estar saturado o ocurrió un error. Por favor, intentá más tarde o contactá al investigador.', 'eipsi-forms'));
    }
    
    // SEGÚN MODO DE REDIRECCIÓN:
    if ($redirect_mode === 'minimal') {
        // MODO MÍNIMO (1 click): Redirigir INMEDIATAMENTE al estudio asignado
        error_log('[EIPSI-POOL] Modo minimal: redirigiendo inmediatamente al estudio ' . $assignment['study_id']);
        wp_redirect(eipsi_get_study_url($assignment['study_id']));
        exit;
        
    } else {
        // MODO TRANSICIÓN (default): Mostrar página de "Asignación exitosa"
        error_log('[EIPSI-POOL] Modo transición: mostrando página de confirmación para estudio ' . $assignment['study_id']);
        eipsi_render_pool_assigned_page($pool_code, $participant_id, $assignment);
    }
}

/**
 * ============================================================================
 * FASE 3 - AJAX ENDPOINTS PARA ADMIN DASHBOARD DEL POOL
 * ============================================================================
 */

// 3.1 Obtener estadísticas del pool
add_action('wp_ajax_eipsi_get_pool_stats', 'eipsi_ajax_get_pool_stats');

function eipsi_ajax_get_pool_stats() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tenés permisos.', 'eipsi-forms')));
    }
    
    $pool_code = sanitize_text_field($_POST['pool_code'] ?? '');
    
    if (empty($pool_code)) {
        wp_send_json_error(array('message' => __('Código de pool requerido.', 'eipsi-forms')));
    }
    
    // Cargar helpers si es necesario
    if (!function_exists('eipsi_get_pool_stats')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/helpers/pool-helpers.php';
    }
    
    $stats = eipsi_get_pool_stats($pool_code);
    
    if (!$stats) {
        wp_send_json_error(array('message' => __('Pool no encontrado.', 'eipsi-forms')));
    }
    
    wp_send_json_success($stats);
}

// 3.2 Exportar asignaciones a CSV
add_action('wp_ajax_eipsi_export_pool_csv', 'eipsi_ajax_export_pool_csv');

function eipsi_ajax_export_pool_csv() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tenés permisos.', 'eipsi-forms')));
    }
    
    $pool_code = sanitize_text_field($_POST['pool_code'] ?? '');
    
    if (empty($pool_code)) {
        wp_send_json_error(array('message' => __('Código de pool requerido.', 'eipsi-forms')));
    }
    
    // Cargar helpers
    if (!function_exists('eipsi_export_pool_assignments_csv')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/helpers/pool-helpers.php';
    }
    
    $csv = eipsi_export_pool_assignments_csv($pool_code);
    
    if (!$csv) {
        wp_send_json_error(array('message' => __('No hay asignaciones para exportar.', 'eipsi-forms')));
    }
    
    wp_send_json_success(array(
        'csv' => $csv,
        'filename' => 'pool_' . $pool_code . '_assignments_' . date('Y-m-d') . '.csv'
    ));
}

// 3.3 Cambiar estado del pool
add_action('wp_ajax_eipsi_change_pool_status', 'eipsi_ajax_change_pool_status');

function eipsi_ajax_change_pool_status() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tenés permisos.', 'eipsi-forms')));
    }
    
    $pool_code = sanitize_text_field($_POST['pool_code'] ?? '');
    $new_status = sanitize_key($_POST['status'] ?? '');
    
    if (empty($pool_code) || empty($new_status)) {
        wp_send_json_error(array('message' => __('Datos incompletos.', 'eipsi-forms')));
    }
    
    // Cargar helpers
    if (!function_exists('eipsi_change_pool_status')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/helpers/pool-helpers.php';
    }
    
    $result = eipsi_change_pool_status($pool_code, $new_status);
    
    if (!$result) {
        wp_send_json_error(array('message' => __('No se pudo cambiar el estado.', 'eipsi-forms')));
    }
    
    $status_labels = array(
        'active' => __('Activo', 'eipsi-forms'),
        'paused' => __('Pausado', 'eipsi-forms'),
        'closed' => __('Cerrado', 'eipsi-forms')
    );
    
    wp_send_json_success(array(
        'message' => sprintf(__('Pool %s correctamente.', 'eipsi-forms'), $status_labels[$new_status] ?? $new_status),
        'status' => $new_status
    ));
}

// ============================================================================
// POOL HUB V3 - AJAX ENDPOINTS (v2.5.4)
// Moved from pool-assignment-api.php
// ============================================================================

/**
 * 4.1 Get all pools summary for Pool Hub V3
 */
add_action('wp_ajax_eipsi_get_all_pools_summary', 'eipsi_ajax_get_all_pools_summary');

function eipsi_ajax_get_all_pools_summary() {
    error_log('[EIPSI-POOL-DEBUG] === GET_ALL_POOLS_SUMMARY CALLED ===');
    error_log('[EIPSI-POOL-DEBUG] REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
    error_log('[EIPSI-POOL-DEBUG] POST data: ' . print_r($_POST, true));
    
    // Try both nonces for compatibility
    $nonce_ok = false;
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'eipsi_pool_hub')) {
        $nonce_ok = true;
        error_log('[EIPSI-POOL-DEBUG] Nonce eipsi_pool_hub OK');
    } elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'eipsi_admin_nonce')) {
        $nonce_ok = true;
        error_log('[EIPSI-POOL-DEBUG] Nonce eipsi_admin_nonce OK');
    }
    
    if (!$nonce_ok) {
        error_log('[EIPSI-POOL-DEBUG] ERROR: Nonce verification failed');
        wp_send_json_error(array('message' => __('Token inválido.', 'eipsi-forms'), 'debug' => 'nonce failed'), 403);
    }
    
    if (!current_user_can('manage_options')) {
        error_log('[EIPSI-POOL-DEBUG] ERROR: User lacks manage_options capability');
        wp_send_json_error(array('message' => __('Sin permisos.', 'eipsi-forms')), 403);
    }
    error_log('[EIPSI-POOL-DEBUG] User permissions OK');
    
    global $wpdb;
    $table_pools = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $table_assignments = $wpdb->prefix . 'eipsi_pool_assignments';
    
    $table_pools = $wpdb->prefix . 'eipsi_longitudinal_pools';
    error_log('[EIPSI-POOL-DEBUG] Table: ' . $table_pools);
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_pools}'");
    error_log('[EIPSI-POOL-DEBUG] Table exists: ' . ($table_exists ? 'YES' : 'NO'));
    
    $pools = $wpdb->get_results("SELECT * FROM {$table_pools} ORDER BY id DESC");
    error_log('[EIPSI-POOL-DEBUG] Found ' . count($pools) . ' pools');
    
    $result = array();
    
    foreach ($pools as $pool) {
        $config = json_decode($pool->config, true) ?: array();
        $studies = isset($config['studies']) ? $config['studies'] : array();
        
        // Count assignments (pool_id en assignments es el ID numérico del pool)
        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT study_id, COUNT(*) as count FROM {$table_assignments} WHERE pool_id = %d GROUP BY study_id",
            $pool->id
        ));
        
        $distribution = array();
        $total = 0;
        foreach ($assignments as $row) {
            $distribution[] = array(
                'study_id' => $row->study_id,
                'count' => (int) $row->count
            );
            $total += (int) $row->count;
        }
        
        $result[] = array(
            'id' => (int) $pool->id,
            'name' => $pool->pool_name,
            'status' => $pool->status,
            'description' => $config['description'] ?? '',
            'incentive' => $config['incentive'] ?? '',
            'studies_count' => count($studies),
            'total_assignments' => $total,
            'distribution' => $distribution,
            'completion_rate' => 0,
            'balance_score' => 100,
            'page_url' => get_permalink($pool->page_id ?? 0),
            'config' => $config
        );
    }
    
    error_log('[EIPSI-POOL-DEBUG] Returning ' . count($result) . ' pools');
    error_log('[EIPSI-POOL-DEBUG] === END GET_ALL_POOLS_SUMMARY ===');
    
    wp_send_json_success(array('pools' => $result));
}

/**
 * 4.2 Get pool analytics
 */
add_action('wp_ajax_eipsi_get_pool_analytics', 'eipsi_ajax_get_pool_analytics');

function eipsi_ajax_get_pool_analytics() {
    check_ajax_referer('eipsi_pool_hub', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Sin permisos.', 'eipsi-forms')), 403);
    }
    
    $pool_id = isset($_GET['pool_id']) ? intval($_GET['pool_id']) : 0;
    if (!$pool_id) {
        wp_send_json_error(array('message' => __('ID inválido.', 'eipsi-forms')), 400);
    }
    
    global $wpdb;
    $table_pools = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $table_assignments = $wpdb->prefix . 'eipsi_pool_assignments';
    $table_participants = $wpdb->prefix . 'survey_participants';
    $table_studies = $wpdb->prefix . 'surveys';
    
    $pool = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_pools} WHERE id = %d",
        $pool_id
    ));
    
    if (!$pool) {
        wp_send_json_error(array('message' => __('Pool no encontrado.', 'eipsi-forms')), 404);
    }
    
    $config = json_decode($pool->config_json, true) ?: array();
    $studies = isset($config['studies']) ? $config['studies'] : array();
    
    // Get assignments with participant info
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, p.email as participant_email 
        FROM {$table_assignments} a 
        LEFT JOIN {$table_participants} p ON a.participant_id = p.id 
        WHERE a.pool_id = %s 
        ORDER BY a.assigned_at DESC",
        $pool->pool_code
    ));
    
    $study_breakdown = array();
    $total = count($assignments);
    $completed = 0;
    
    foreach ($studies as $study) {
        $study_assignments = array_filter($assignments, function($a) use ($study) {
            return $a->study_id == $study['study_id'];
        });
        
        $study_completed = count(array_filter($study_assignments, function($a) {
            return $a->status === 'completed';
        }));
        
        $study_total = count($study_assignments);
        $completed += $study_completed;
        
        $study_breakdown[] = array(
            'study_id' => $study['study_id'],
            'study_name' => $wpdb->get_var($wpdb->prepare(
                "SELECT study_name FROM {$table_studies} WHERE id = %d",
                $study['study_id']
            )) ?: 'Estudio #' . $study['study_id'],
            'assigned' => $study_total,
            'completed' => $study_completed,
            'in_progress' => $study_total - $study_completed,
            'actual_pct' => $total > 0 ? round(($study_total / $total) * 100, 1) : 0,
            'expected_pct' => (float) $study['probability'],
            'delta' => $total > 0 ? round((($study_total / $total) * 100) - $study['probability'], 1) : 0,
            'completion_rate' => $study_total > 0 ? round(($study_completed / $study_total) * 100, 1) : 0
        );
    }
    
    // Recent activity
    $recent = array_slice($assignments, 0, 10);
    $activity = array();
    foreach ($recent as $a) {
        $activity[] = array(
            'participant_email' => $a->participant_email ?: 'ID: ' . $a->participant_id,
            'study_name' => $wpdb->get_var($wpdb->prepare(
                "SELECT study_name FROM {$table_studies} WHERE id = %d",
                $a->study_id
            )) ?: 'Estudio #' . $a->study_id,
            'assigned_at' => human_time_diff(strtotime($a->assigned_at), current_time('timestamp')) . ' atrás',
            'status' => $a->status
        );
    }
    
    wp_send_json_success(array(
        'metrics' => array(
            'total_assignments' => $total,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'balance_score' => 100,
            'dropout_rate' => $total > 0 ? round((($total - $completed) / $total) * 100, 1) : 0
        ),
        'study_breakdown' => $study_breakdown,
        'recent_activity' => $activity
    ));
}

/**
 * 4.3 Toggle pool status (Pool Hub V3)
 */
add_action('wp_ajax_eipsi_toggle_pool_status', 'eipsi_ajax_toggle_pool_status');

function eipsi_ajax_toggle_pool_status() {
    check_ajax_referer('eipsi_pool_hub', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Sin permisos.', 'eipsi-forms')), 403);
    }
    
    $pool_id = isset($_POST['pool_id']) ? intval($_POST['pool_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';
    
    if (!$pool_id || !in_array($status, array('active', 'paused', 'closed'))) {
        wp_send_json_error(array('message' => __('Datos inválidos.', 'eipsi-forms')), 400);
    }
    
    global $wpdb;
    $table_pools = $wpdb->prefix . 'eipsi_longitudinal_pools';
    
    $result = $wpdb->update(
        $table_pools,
        array('status' => $status),
        array('id' => $pool_id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success(array('status' => $status));
    } else {
        wp_send_json_error(array('message' => __('Error al actualizar.', 'eipsi-forms')));
    }
}

/**
 * 4.4 Export pool assignments to CSV
 */
add_action('wp_ajax_eipsi_export_pool_assignments', 'eipsi_ajax_export_pool_assignments');

function eipsi_ajax_export_pool_assignments() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Sin permisos.', 'eipsi-forms'));
    }
    
    check_ajax_referer('eipsi_pool_hub', 'nonce');
    
    $pool_id = isset($_GET['pool_id']) ? intval($_GET['pool_id']) : 0;
    if (!$pool_id) {
        wp_die(__('ID inválido.', 'eipsi-forms'));
    }
    
    global $wpdb;
    $table_pools = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $table_assignments = $wpdb->prefix . 'eipsi_pool_assignments';
    $table_participants = $wpdb->prefix . 'survey_participants';
    
    $pool = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_pools} WHERE id = %d",
        $pool_id
    ));
    
    if (!$pool) {
        wp_die(__('Pool no encontrado.', 'eipsi-forms'));
    }
    
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, p.email, p.first_name, p.last_name 
        FROM {$table_assignments} a 
        LEFT JOIN {$table_participants} p ON a.participant_id = p.id 
        WHERE a.pool_id = %s 
        ORDER BY a.assigned_at DESC",
        $pool->pool_code
    ));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pool-assignments-' . $pool->pool_code . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
    
    fputcsv($output, array('ID', 'Email', 'Nombre', 'Apellido', 'Study ID', 'Estado', 'Fecha Asignación', 'IP'));
    
    foreach ($assignments as $row) {
        fputcsv($output, array(
            $row->id,
            $row->email,
            $row->first_name,
            $row->last_name,
            $row->study_id,
            $row->status,
            $row->assigned_at,
            $row->ip_address
        ));
    }
    
    fclose($output);
    exit;
}

/**
 * 4.5 Join pool (frontend AJAX)
 */
add_action('wp_ajax_nopriv_eipsi_join_pool', 'eipsi_ajax_join_pool');
add_action('wp_ajax_eipsi_join_pool', 'eipsi_ajax_join_pool');

function eipsi_ajax_join_pool() {
    check_ajax_referer('eipsi_pool_access', 'nonce');
    
    $pool_code = isset($_POST['pool_code']) ? sanitize_text_field($_POST['pool_code']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['pool_email']) : '';
    $consent = isset($_POST['consent']) ? true : false;
    
    if (empty($pool_code) || empty($email)) {
        wp_send_json_error(array('message' => __('Datos incompletos.', 'eipsi-forms')));
    }
    
    if (!$consent) {
        wp_send_json_error(array('message' => __('Debes aceptar los términos.', 'eipsi-forms')));
    }
    
    // Load helpers
    if (!function_exists('eipsi_pool_randomize')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/helpers/pool-helpers.php';
    }
    
    // Get pool
    global $wpdb;
    $table_pools = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $pool = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_pools} WHERE pool_code = %s",
        $pool_code
    ));
    
    if (!$pool || $pool->status !== 'active') {
        wp_send_json_error(array('message' => __('Pool no disponible.', 'eipsi-forms')));
    }
    
    $config = json_decode($pool->config_json, true) ?: array();
    
    // Check if already assigned
    $existing = eipsi_get_pool_assignment($pool_code, $email);
    if ($existing) {
        $study_url = get_permalink(get_post_meta($existing['study_id'], 'survey_page_id', true));
        wp_send_json_success(array(
            'already_assigned' => true,
            'study_id' => $existing['study_id'],
            'redirect_url' => $study_url
        ));
    }
    
    // Randomize
    $pool_data = array('status' => $pool->status, 'config' => $config);
    $assignment = eipsi_pool_randomize($pool_code, $email, $pool_data);
    
    if (!$assignment) {
        wp_send_json_error(array('message' => __('No se pudo asignar. Intentá de nuevo.', 'eipsi-forms')));
    }
    
    // Save assignment
    $result = eipsi_save_pool_assignment($pool_code, $email, $assignment['study_id']);
    
    if ($result) {
        $study_url = get_permalink(get_post_meta($assignment['study_id'], 'survey_page_id', true));
        wp_send_json_success(array(
            'study_id' => $assignment['study_id'],
            'study_name' => $assignment['study_name'],
            'redirect_url' => $study_url,
            'mode' => $config['redirect_mode'] ?? 'transition'
        ));
    } else {
        wp_send_json_error(array('message' => __('Error al guardar asignación.', 'eipsi-forms')));
    }
}
