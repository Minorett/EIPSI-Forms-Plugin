<?php
/**
 * EIPSI Forms Shortcodes
 * 
 * Official shortcode to insert form templates anywhere
 * 
 * @package EIPSI_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [eipsi_form id="123"]
 * 
 * Display a form template by ID
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function eipsi_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'eipsi_form');
    
    $template_id = absint($atts['id']);
    
    // Use shared render helper
    return eipsi_render_form_shortcode_markup($template_id);
}
add_shortcode('eipsi_form', 'eipsi_form_shortcode');

/**
 * Shortcode: [eipsi_survey_login survey_id="123"]
 * 
 * Display a login/registration form for participants
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function eipsi_survey_login_shortcode($atts) {
    $atts = shortcode_atts(array(
        'survey_id' => 0,
        'redirect_url' => '', // opcional, redirect post-login
    ), $atts, 'eipsi_survey_login');
    
    return eipsi_render_survey_login_form($atts);
}
add_shortcode('eipsi_survey_login', 'eipsi_survey_login_shortcode');

/**
 * Render helper for survey login form
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function eipsi_render_survey_login_form($atts) {
    ob_start();
    include EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/survey-login-form.php';
    return ob_get_clean();
}

/**
 * Shortcode: [eipsi_participant_dashboard survey_id="123"]
 * 
 * Display participant dashboard with progress and next wave
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function eipsi_participant_dashboard_shortcode($atts) {
    // Enqueue dashboard assets
    wp_enqueue_style(
        'eipsi-participant-dashboard-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/participant-dashboard.css',
        array('eipsi-theme-toggle-css'),
        EIPSI_FORMS_VERSION
    );
    
    wp_enqueue_script(
        'eipsi-participant-dashboard-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/participant-dashboard.js',
        array('jquery', 'eipsi-participant-auth'),
        EIPSI_FORMS_VERSION,
        true
    );
    
    wp_localize_script('eipsi-participant-dashboard-js', 'eipsiParticipantDashboardL10n', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_participant_dashboard'),
        'strings' => array(
            'confirm_logout' => __('¿Estás seguro de que quieres cerrar sesión?', 'eipsi-forms'),
            'logging_out' => __('Cerrando sesión...', 'eipsi-forms'),
            'logout_success' => __('Sesión cerrada correctamente', 'eipsi-forms'),
            'logout_error' => __('Error al cerrar sesión', 'eipsi-forms'),
            'status_completed_tooltip' => __('Esta toma fue completada exitosamente', 'eipsi-forms'),
            'status_pending_tooltip' => __('Esta toma está pendiente o en progreso', 'eipsi-forms'),
            'status_not_started_tooltip' => __('Esta toma aún no ha sido iniciada', 'eipsi-forms')
        )
    ));
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'survey_id' => 0,
    ), $atts, 'eipsi_participant_dashboard');
    
    // Check authentication
    if (!EIPSI_Auth_Service::is_authenticated()) {
        // Return login form if not authenticated
        return eipsi_render_survey_login_form(array(
            'survey_id' => $atts['survey_id'],
            'redirect_url' => get_permalink()
        ));
    }
    
    // Get current participant
    $participant_id = EIPSI_Auth_Service::get_current_participant();
    if (!$participant_id) {
        // Session expired or invalid
        EIPSI_Auth_Service::destroy_session();
        return eipsi_render_survey_login_form(array(
            'survey_id' => $atts['survey_id'],
            'redirect_url' => get_permalink()
        ));
    }
    
    // Get participant data
    $participant = EIPSI_Participant_Service::get_by_id($participant_id);
    if (!$participant) {
        EIPSI_Auth_Service::destroy_session();
        return eipsi_render_survey_login_form(array(
            'survey_id' => $atts['survey_id'],
            'redirect_url' => get_permalink()
        ));
    }
    
    // Get survey_id from participant or attribute
    $survey_id = !empty($participant->survey_id) ? (int) $participant->survey_id : absint($atts['survey_id']);
    if (empty($survey_id)) {
        return '<div class="eipsi-dashboard-error"><p>' . __('Error: No se encontró el estudio.', 'eipsi-forms') . '</p></div>';
    }
    
    // Get all waves for the study (with assignments)
    $all_waves = eipsi_get_participant_waves_with_assignments($participant_id, $survey_id);
    
    // Find next wave (first pending/not started wave ordered by wave_index)
    $next_wave = null;
    foreach ($all_waves as $wave) {
        if (empty($wave['assignment']) || $wave['assignment']['status'] !== 'submitted') {
            $next_wave = $wave;
            break;
        }
    }
    
    // Render dashboard template
    ob_start();
    include EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/participant-dashboard.php';
    return ob_get_clean();
}
add_shortcode('eipsi_participant_dashboard', 'eipsi_participant_dashboard_shortcode');

/**
 * Helper function to get participant waves with assignment data
 * 
 * @param int $participant_id Participant ID
 * @param int $survey_id Survey ID
 * @return array Array of waves with assignment data
 */
function eipsi_get_participant_waves_with_assignments($participant_id, $survey_id) {
    global $wpdb;
    
    $waves = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            w.id,
            w.wave_index,
            w.name,
            w.form_id,
            w.due_date,
            w.reminder_days,
            w.retry_enabled,
            w.retry_days,
            w.max_retries,
            w.status as wave_status,
            w.is_mandatory
         FROM {$wpdb->prefix}survey_waves w
         WHERE w.study_id = %d
         ORDER BY w.wave_index ASC",
        $survey_id
    ), ARRAY_A);
    
    if (empty($waves)) {
        return array();
    }
    
    // Get assignments for this participant
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            a.wave_id,
            a.status,
            a.submitted_at,
            a.first_viewed_at,
            a.reminder_count,
            a.last_reminder_sent,
            fr.duration_seconds as submission_duration
         FROM {$wpdb->prefix}survey_assignments a
         LEFT JOIN {$wpdb->prefix}vas_form_results fr ON 
             fr.form_id = a.wave_id AND 
             fr.participant_id = %s
         WHERE a.participant_id = %d",
        $participant_id,
        $participant_id
    ), ARRAY_A);
    
    // Index assignments by wave_id
    $assignments_by_wave = array();
    foreach ($assignments as $assignment) {
        $assignments_by_wave[$assignment['wave_id']] = $assignment;
    }
    
    // Merge waves with assignments
    foreach ($waves as &$wave) {
        $wave['assignment'] = isset($assignments_by_wave[$wave['id']]) ? $assignments_by_wave[$wave['id']] : null;
    }
    
    return $waves;
}

/**
 * Add helpful information to shortcode in admin
 * Shows available forms when editing posts/pages
 */
function eipsi_shortcode_help_metabox() {
    $screens = array('post', 'page');
    
    foreach ($screens as $screen) {
        add_meta_box(
            'eipsi_shortcode_help',
            __('Shortcode de Formularios EIPSI', 'eipsi-forms'),
            'eipsi_shortcode_help_callback',
            $screen,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'eipsi_shortcode_help_metabox');

/**
 * Render shortcode help metabox
 */
function eipsi_shortcode_help_callback($post) {
    ?>
    <div class="eipsi-shortcode-help">
        <p style="font-size: 13px; margin: 0 0 12px;">
            <?php esc_html_e('Insertá formularios usando este formato:', 'eipsi-forms'); ?>
        </p>
        
        <code style="display: block; padding: 8px; background: #f0f0f1; border-radius: 3px; margin-bottom: 12px;">
            [eipsi_form id="<strong style="color: #2271b1;">123</strong>"]
        </code>
        
        <?php
        // Get available form templates
        $templates = get_posts(array(
            'post_type' => 'eipsi_form_template',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        if (!empty($templates)) {
            ?>
            <p style="font-size: 13px; margin: 12px 0 8px; font-weight: 600;">
                <?php esc_html_e('Formularios disponibles:', 'eipsi-forms'); ?>
            </p>
            
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px;">
                <?php foreach ($templates as $template) : ?>
                    <div style="padding: 8px; border-bottom: 1px solid #f0f0f1; font-size: 12px;">
                        <div style="font-weight: 600; margin-bottom: 4px;">
                            <?php echo esc_html($template->post_title ?: __('(Sin título)', 'eipsi-forms')); ?>
                        </div>
                        <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 2px; font-size: 11px; cursor: pointer;" 
                              onclick="navigator.clipboard.writeText('[eipsi_form id=&quot;<?php echo esc_attr($template->ID); ?>&quot;]'); this.style.background='#00a32a'; this.style.color='white'; setTimeout(() => { this.style.background='#f0f0f1'; this.style.color=''; }, 1000);" 
                              title="<?php esc_attr_e('Clic para copiar', 'eipsi-forms'); ?>">
                            [eipsi_form id="<?php echo esc_attr($template->ID); ?>"]
                        </code>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <p style="font-size: 11px; color: #666; margin: 8px 0 0;">
                <em><?php esc_html_e('💡 Clic en un shortcode para copiarlo', 'eipsi-forms'); ?></em>
            </p>
            <?php
        } else {
            ?>
            <p style="font-size: 13px; margin: 12px 0 0; padding: 8px; background: #f0f0f1; border-radius: 3px;">
                <?php esc_html_e('No hay formularios creados aún.', 'eipsi-forms'); ?>
                <br>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=eipsi_form_template')); ?>" style="font-size: 13px;">
                    <?php esc_html_e('+ Crear tu primer formulario', 'eipsi-forms'); ?>
                </a>
            </p>
            <?php
        }
        ?>
    </div>
    
    <style>
        .eipsi-shortcode-help code:hover {
            background: #e5e7eb !important;
        }
    </style>
    <?php
}

/**
 * Add shortcode column to pages/posts list
 * Shows if the page/post contains EIPSI form shortcodes
 */
function eipsi_add_shortcode_indicator_column($columns) {
    $columns['eipsi_forms'] = '<span class="dashicons dashicons-feedback" title="' . esc_attr__('Formularios EIPSI', 'eipsi-forms') . '"></span>';
    return $columns;
}
add_filter('manage_posts_columns', 'eipsi_add_shortcode_indicator_column');
add_filter('manage_pages_columns', 'eipsi_add_shortcode_indicator_column');

/**
 * Show shortcode indicator in column
 */
function eipsi_shortcode_indicator_column_content($column, $post_id) {
    if ($column === 'eipsi_forms') {
        $content = get_post_field('post_content', $post_id);
        $has_shortcode = has_shortcode($content, 'eipsi_form');
        $has_block = has_block('eipsi/form-container', $content);

        if ($has_shortcode || $has_block) {
            echo '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;" title="' . esc_attr__('Contiene formularios EIPSI', 'eipsi-forms') . '"></span>';
        } else {
            echo '<span style="color: #ddd;">—</span>';
        }
    }
}
add_action('manage_posts_custom_column', 'eipsi_shortcode_indicator_column_content', 10, 2);
add_action('manage_pages_custom_column', 'eipsi_shortcode_indicator_column_content', 10, 2);
