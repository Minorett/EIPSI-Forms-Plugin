<?php
/**
 * Results & Experience Page
 * Main admin page with 3 tabs:
 * 1. Submissions (form responses)
 * 2. Completion Message (global thank-you config)
 * 3. Privacy & Metadata (per-form toggles)
 */

if (!defined('ABSPATH')) {
    exit;
}

function eipsi_display_form_responses() {
    // Verify capability
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'eipsi-forms'));
    }

    // Determine active tab from URL param
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'submissions';
    $allowed_tabs = array('submissions', 'completion', 'privacy', 'randomization', 'longitudinal-studies', 'waves-manager', 'cron-reminders', 'email-log', 'monitoring');

    if (!in_array($active_tab, $allowed_tabs)) {
        $active_tab = 'submissions';
    }

    // Nonce for AJAX operations
    $nonce = wp_create_nonce('eipsi_admin_nonce');
    ?>

    <div class="wrap eipsi-results-page">
        <h1><?php esc_html_e('Results & Experience', 'eipsi-forms'); ?></h1>
        
        <!-- Tab Navigation (WordPress native style) -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=eipsi-results&tab=submissions" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'submissions') ? 'nav-tab-active' : ''); ?>"
               data-tab="submissions">
                📊 <?php esc_html_e('Submissions', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=longitudinal-studies" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'longitudinal-studies') ? 'nav-tab-active' : ''); ?>"
               data-tab="longitudinal-studies">
                📚 <?php esc_html_e('Estudios', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=completion" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'completion') ? 'nav-tab-active' : ''); ?>"
               data-tab="completion">
                ✅ <?php esc_html_e('Finalización', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=privacy" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'privacy') ? 'nav-tab-active' : ''); ?>"
               data-tab="privacy">
                🔒 <?php esc_html_e('Privacy & Metadata', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=randomization" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'randomization') ? 'nav-tab-active' : ''); ?>"
               data-tab="randomization">
                🎲 <?php esc_html_e('Randomization', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=waves-manager"
               class="nav-tab <?php echo esc_attr(($active_tab === 'waves-manager') ? 'nav-tab-active' : ''); ?>"
               data-tab="waves-manager">
                🌊 <?php esc_html_e('Waves Manager', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=cron-reminders"
               class="nav-tab <?php echo esc_attr(($active_tab === 'cron-reminders') ? 'nav-tab-active' : ''); ?>"
               data-tab="cron-reminders">
                ⏰ <?php esc_html_e('Recordatorios', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=email-log"
               class="nav-tab <?php echo esc_attr(($active_tab === 'email-log') ? 'nav-tab-active' : ''); ?>"
               data-tab="email-log">
                📧 <?php esc_html_e('Email Log & Dropout', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results&tab=monitoring"
               class="nav-tab <?php echo esc_attr(($active_tab === 'monitoring') ? 'nav-tab-active' : ''); ?>"
               data-tab="monitoring">
                🔧 <?php esc_html_e('Monitoring', 'eipsi-forms'); ?>
            </a>
        </h2>
        
        <!-- Message container for AJAX feedback -->
        <div id="eipsi-message-container"></div>
        
        <!-- Tab 1: Submissions -->
        <?php if ($active_tab === 'submissions'): ?>
            <div class="tab-content" data-tab="submissions">
                <?php include dirname(__FILE__) . '/tabs/submissions-tab.php'; ?>
            </div>
        <?php endif; ?>

        <!-- Tab: Longitudinal Studies (v1.5.2) -->
        <?php if ($active_tab === 'longitudinal-studies'): ?>
            <div class="tab-content" data-tab="longitudinal-studies">
                <?php include dirname(__FILE__) . '/tabs/longitudinal-studies-tab.php'; ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab 2: Completion Message -->
        <?php if ($active_tab === 'completion'): ?>
            <div class="tab-content" data-tab="completion">
                <?php include dirname(__FILE__) . '/tabs/completion-message-tab.php'; ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab 3: Privacy & Metadata -->
        <?php if ($active_tab === 'privacy'): ?>
            <div class="tab-content" data-tab="privacy">
                <?php include dirname(__FILE__) . '/tabs/privacy-metadata-tab.php'; ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab 4: Randomization -->
        <?php if ($active_tab === 'randomization'): ?>
            <div class="tab-content" data-tab="randomization">
                <?php 
                // Incluir la página del Randomization
                if (file_exists(dirname(__FILE__) . '/randomization-page.php')) {
                    require_once dirname(__FILE__) . '/randomization-page.php';
                    eipsi_display_randomization();
                } else {
                    echo '<p>Error: Randomization no disponible</p>';
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Tab 5: Waves Manager -->
        <?php if ($active_tab === 'waves-manager'): ?>
            <div class="tab-content" data-tab="waves-manager">
                <?php include dirname(__FILE__) . '/tabs/waves-manager-tab.php'; ?>
            </div>
        <?php endif; ?>

        <!-- Tab 6: Cron Reminders (Task 4.2) -->
        <?php if ($active_tab === 'cron-reminders'): ?>
            <div class="tab-content" data-tab="cron-reminders">
                <?php include dirname(__FILE__) . '/tabs/cron-reminders-tab.php'; ?>
            </div>
        <?php endif; ?>

        <!-- Tab 7: Email Log & Dropout Management (Task 4.3) -->
        <?php if ($active_tab === 'email-log'): ?>
            <div class="tab-content" data-tab="email-log">
                <?php
                // Enqueue CSS and JS
                wp_enqueue_style('eipsi-email-log-css', plugins_url('admin/css/email-log.css', EIPSI_FORMS_PLUGIN_FILE), array(), '1.5.0');
                wp_enqueue_script('eipsi-email-log-js', plugins_url('admin/js/email-log.js', EIPSI_FORMS_PLUGIN_FILE), array('jquery'), '1.5.0', true);

                // Localize script
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

        <!-- Tab 8: Monitoring -->
        <?php if ($active_tab === 'monitoring'): ?>
            <div class="tab-content" data-tab="monitoring">
                <?php include dirname(__FILE__) . '/tabs/monitoring-tab.php'; ?>
            </div>
        <?php endif; ?>

    </div>

    <style>
        .eipsi-results-page {
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        
        .nav-tab-wrapper {
            border-bottom: 2px solid #ccc;
            margin: 20px 0;
            padding: 0;
        }
        
        .nav-tab {
            padding: 12px 20px;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .nav-tab:hover {
            color: #005a87;
        }
        
        .nav-tab-active {
            color: #005a87;
            border-bottom-color: #005a87;
            font-weight: 600;
        }
        
        #eipsi-message-container {
            margin: 20px 0;
        }
        
        .tab-content {
            margin-top: 20px;
        }
    </style>
    <?php
}
