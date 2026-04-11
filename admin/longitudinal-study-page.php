<?php
/**
 * Longitudinal Study Admin Page
 *
 * Admin page with tabs:
 * - Create Study
 * - Dashboard Study
 * - Recordatorios y Notificaciones
 * - Email Log & Dropout
 * - Longitudinal Pools
 * - Pool Analytics
 * - Export
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 * @since 2.1.2 - Removed Waves Manager tab (merged into Dashboard)
 */

if (!defined('ABSPATH')) {
    exit;
}

function eipsi_display_longitudinal_study_page() {
    if (!function_exists('eipsi_user_can_manage_longitudinal') || !eipsi_user_can_manage_longitudinal()) {
        wp_die(__('Unauthorized', 'eipsi-forms'));
    }

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard-study';
    $allowed_tabs = array(
        'create-study',
        'dashboard-study',
        // REMOVED: 'waves-manager' - functionality merged into Dashboard
        'reminders',
        'email-log',
        'longitudinal-pools',
        'pool-analytics',
        'export'
    );

    if (!in_array($active_tab, $allowed_tabs, true)) {
        $active_tab = 'dashboard-study';
    }
    ?>

    <div class="wrap eipsi-longitudinal-page">
        <h1><?php esc_html_e('EIPSI Forms - Longitudinal Study', 'eipsi-forms'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=eipsi-longitudinal-study&tab=create-study"
               class="nav-tab <?php echo esc_attr(($active_tab === 'create-study') ? 'nav-tab-active' : ''); ?>"
               data-tab="create-study">
                📋 <?php esc_html_e('Create Study', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-longitudinal-study&tab=dashboard-study"
               class="nav-tab <?php echo esc_attr(($active_tab === 'dashboard-study') ? 'nav-tab-active' : ''); ?>"
               data-tab="dashboard-study">
                📚 <?php esc_html_e('Dashboard Study', 'eipsi-forms'); ?>
            </a>
            <!-- REMOVED: Waves Manager tab - functionality merged into Dashboard -->
            <a href="?page=eipsi-longitudinal-study&tab=reminders"
               class="nav-tab <?php echo esc_attr(($active_tab === 'reminders') ? 'nav-tab-active' : ''); ?>"
               data-tab="reminders">
                ⏰ <?php esc_html_e('Recordatorios', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-longitudinal-study&tab=email-log"
               class="nav-tab <?php echo esc_attr(($active_tab === 'email-log') ? 'nav-tab-active' : ''); ?>"
               data-tab="email-log">
                📧 <?php esc_html_e('Email Log & Dropout', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-pool-hub"
               class="nav-tab <?php echo esc_attr(($active_tab === 'pool-hub' || $active_tab === 'longitudinal-pools' || $active_tab === 'pool-analytics') ? 'nav-tab-active' : ''); ?>"
               data-tab="pool-hub">
                🏊 <?php esc_html_e('Pool Hub', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-longitudinal-study&tab=export"
               class="nav-tab <?php echo esc_attr(($active_tab === 'export') ? 'nav-tab-active' : ''); ?>"
               data-tab="export">
                📥 <?php esc_html_e('Export', 'eipsi-forms'); ?>
            </a>
        </h2>

        <?php if ($active_tab === 'create-study'): ?>
            <div class="tab-content" data-tab="create-study">
                <?php
                if (function_exists('eipsi_display_setup_wizard_page')) {
                    eipsi_display_setup_wizard_page();
                } else {
                    echo '<p>' . esc_html__('El wizard de creación no está disponible.', 'eipsi-forms') . '</p>';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'dashboard-study'): ?>
            <div class="tab-content" data-tab="dashboard-study">
                <?php include dirname(__FILE__) . '/tabs/longitudinal-studies-tab.php'; ?>
            </div>
        <?php endif; ?>

        <!-- REMOVED: waves-manager tab content - functionality merged into Dashboard -->

        <?php if ($active_tab === 'reminders'): ?>
            <div class="tab-content" data-tab="reminders">
                <?php include dirname(__FILE__) . '/tabs/cron-reminders-tab.php'; ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'email-log'): ?>
            <div class="tab-content" data-tab="email-log">
                <?php
                wp_enqueue_style('eipsi-email-log-css', plugins_url('admin/css/email-log.css', EIPSI_FORMS_PLUGIN_FILE), array(), '1.5.0');
                wp_enqueue_script('eipsi-email-log-js', plugins_url('admin/js/email-log.js', EIPSI_FORMS_PLUGIN_FILE), array('jquery'), '1.5.0', true);

                wp_localize_script('eipsi-email-log-js', 'eipsi', array(
                    'nonce' => wp_create_nonce('eipsi_admin_nonce'),
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'i18n' => array(
                        'loading' => __('Cargando...', 'eipsi-forms'),
                        'errorLoading' => __('Error al cargar', 'eipsi-forms'),
                        'connectionError' => __('Error de conexión', 'eipsi-forms'),
                        'view' => __('Ver', 'eipsi-forms'),
                        'resend' => __('Reenviar', 'eipsi-forms'),
                        'page' => __('Página', 'eipsi-forms'),
                        'of' => __('de', 'eipsi-forms'),
                        'noEmails' => __('No se encontraron emails', 'eipsi-forms'),
                        'noAtRisk' => __('No hay participantes en riesgo', 'eipsi-forms'),
                        'selectParticipants' => __('Por favor selecciona al menos un participante', 'eipsi-forms'),
                        'confirmResend' => __('¿Deseas reenviar este email?', 'eipsi-forms'),
                        'emailSent' => __('Email enviado exitosamente', 'eipsi-forms'),
                        'errorSending' => __('Error al enviar email', 'eipsi-forms'),
                        'reminder' => __('Recordatorio', 'eipsi-forms'),
                        'extend' => __('Extender', 'eipsi-forms'),
                        'complete' => __('Completada', 'eipsi-forms'),
                        'deactivate' => __('Desactivar', 'eipsi-forms'),
                        'confirmReminder' => __('¿Deseas enviar un recordatorio a este participante?', 'eipsi-forms'),
                        'reminderSent' => __('Recordatorio enviado exitosamente', 'eipsi-forms'),
                        'extended' => __('Vencimiento extendido', 'eipsi-forms'),
                        'days' => __('días', 'eipsi-forms'),
                        'confirmComplete' => __('¿Deseas marcar esta toma como completada?', 'eipsi-forms'),
                        'markedComplete' => __('Toma marcada como completada', 'eipsi-forms'),
                        'confirmDeactivate' => __('¿Deseas desactivar este participante?', 'eipsi-forms'),
                        'deactivated' => __('Participante desactivado', 'eipsi-forms'),
                        'actionComplete' => __('Acción completada exitosamente', 'eipsi-forms'),
                        'error' => __('Error', 'eipsi-forms'),
                        'type' => __('Tipo', 'eipsi-forms'),
                        'to' => __('Para', 'eipsi-forms'),
                        'status' => __('Estado', 'eipsi-forms'),
                        'sentAt' => __('Enviado', 'eipsi-forms'),
                        'subject' => __('Asunto', 'eipsi-forms'),
                        'content' => __('Contenido', 'eipsi-forms')
                    )
                ));
                ?>
                <?php include dirname(__FILE__) . '/tabs/email-log-tab.php'; ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'longitudinal-pools'): ?>
            <div class="tab-content" data-tab="longitudinal-pools">
                <?php
                require_once dirname(__FILE__) . '/tabs/longitudinal-pools-tab.php';
                if (function_exists('eipsi_render_longitudinal_pools_tab')) {
                    eipsi_render_longitudinal_pools_tab();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Error: La funcionalidad de Longitudinal Pools no está disponible.', 'eipsi-forms') . '</p></div>';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'pool-analytics'): ?>
            <div class="tab-content" data-tab="pool-analytics">
                <?php
                require_once dirname(__FILE__) . '/tabs/pool-analytics-tab.php';
                if (function_exists('eipsi_render_pool_analytics_tab')) {
                    eipsi_render_pool_analytics_tab();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Error: La funcionalidad de Pool Analytics no está disponible.', 'eipsi-forms') . '</p></div>';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'export'): ?>
            <div class="tab-content" data-tab="export">
                <?php include dirname(__FILE__) . '/tabs/export-tab.php'; ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .eipsi-longitudinal-page {
            background: white;
            padding: 20px;
            border-radius: 8px;
        }

        .nav-tab-wrapper {
            border-bottom: 2px solid #ccc;
            margin: 20px 0;
            padding: 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0;
        }

        .nav-tab {
            padding: 12px 20px;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            display: inline-block;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-tab:hover {
            color: #3B6CAA;
            background-color: #f5f5f5;
        }

        .nav-tab-active {
            color: #3B6CAA;
            border-bottom-color: #3B6CAA;
            font-weight: 600;
            background-color: #fff;
        }

        @media (max-width: 1200px) {
            .nav-tab-wrapper {
                gap: 5px;
            }

            .nav-tab {
                padding: 10px 15px;
                font-size: 13px;
            }
        }

        .tab-content {
            margin-top: 20px;
        }
    </style>
    <?php
}
