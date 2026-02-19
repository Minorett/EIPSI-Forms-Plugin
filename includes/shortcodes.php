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
    
    // Allow overriding from URL if id is 0 or not set, ONLY if authenticated
    // This allows a single /survey/ page to serve multiple forms based on magic links
    if ($template_id === 0 && isset($_GET['form_id']) && function_exists('eipsi_is_participant_logged_in') && eipsi_is_participant_logged_in()) {
        $template_id = absint($_GET['form_id']);
    }
    
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
    // Enqueue required assets
    wp_enqueue_style(
        'eipsi-survey-login-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/survey-login.css',
        array(),
        EIPSI_FORMS_VERSION
    );
    
    wp_enqueue_script(
        'eipsi-survey-login-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/survey-login.js',
        array('jquery'),
        EIPSI_FORMS_VERSION,
        true
    );
    
    wp_enqueue_script(
        'eipsi-participant-auth',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/participant-auth.js',
        array('jquery'),
        EIPSI_FORMS_VERSION,
        true
    );
    
    // Localize script with auth data
    wp_localize_script('eipsi-participant-auth', 'eipsiAuth', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_participant_auth'),
        'strings' => array(
            'registering' => __('Registrando...', 'eipsi-forms'),
            'logging_in' => __('Ingresando...', 'eipsi-forms'),
            'loading' => __('Cargando...', 'eipsi-forms'),
            'confirm_logout' => __('¿Estás seguro de que quieres cerrar sesión?', 'eipsi-forms')
        )
    ));
    
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
        // Allow other shortcodes to add their help content
        do_action('eipsi_shortcode_help_metabox_content', $post);
        
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

/**
 * Shortcode: [eipsi_longitudinal_study study_code="STUDY_2025"] or [eipsi_longitudinal_study id="123"]
 * 
 * Display a longitudinal study configuration with waves, time limits, and settings.
 * The shortcode remains persistent regardless of study configuration changes.
 * 
 * SECURE: Use study_code instead of ID for better security.
 * Example: [eipsi_longitudinal_study study_code="ANSIEDAD_TCC_2025"]
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 * 
 * @since 1.5.0
 * @since 1.6.0 - Added study_code support for secure shortcodes
 */
function eipsi_longitudinal_study_shortcode($atts) {
    // Parse attributes with defaults
    $atts = shortcode_atts(array(
        'study_code' => '',      // Study code (preferred, secure)
        'id' => 0,                // Study ID (deprecated, less secure)
        'wave' => 0,              // Specific wave to display (optional, 0 = all waves)
        'time_limit' => 0,        // Override time limit in minutes (optional, 0 = use study default)
        'show_config' => 'yes',   // Show study configuration details (yes/no)
        'show_waves' => 'yes',    // Show waves list (yes/no)
        'theme' => 'default',     // Theme: default, compact, card
        'view' => 'dashboard',    // View mode: dashboard, participant, public
    ), $atts, 'eipsi_longitudinal_study');
    
    $study_code = sanitize_text_field($atts['study_code']);
    $study_id = absint($atts['id']);
    $wave_index = absint($atts['wave']);
    $time_limit_override = absint($atts['time_limit']);
    $show_config = strtolower($atts['show_config']) === 'yes';
    $show_waves = strtolower($atts['show_waves']) === 'yes';
    $theme = sanitize_key($atts['theme']);
    $view_mode = sanitize_key($atts['view']);
    
    // Get study data - prefer study_code for security
    global $wpdb;
    
    if (!empty($study_code)) {
        // SECURE: Use study_code (prevents ID guessing)
        $study = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_studies WHERE study_code = %s",
            $study_code
        ));
        
        if (!$study) {
            return eipsi_longitudinal_study_error(
                __('⚠️ Error: No se encontró el estudio con ese código.', 'eipsi-forms'),
                __('Verificá el código del estudio en el panel de administración.', 'eipsi-forms')
            );
        }
    } elseif ($study_id > 0) {
        // BACKWARD COMPATIBILITY: Use numeric ID (less secure)
        $study = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_studies WHERE id = %d",
            $study_id
        ));
        
        if (!$study) {
            return eipsi_longitudinal_study_error(
                __('⚠️ Error: No se encontró el estudio con ese ID.', 'eipsi-forms'),
                __('Ejemplo: [eipsi_longitudinal_study study_code="ESTUDIO_2025"]', 'eipsi-forms')
            );
        }
    } else {
        return eipsi_longitudinal_study_error(
            __('⚠️ Error: Se requiere el código o ID del estudio.', 'eipsi-forms'),
            __('Ejemplo: [eipsi_longitudinal_study study_code="ESTUDIO_2025"]', 'eipsi-forms')
        );
    }
    
    // Check if study is active (or paused - allow viewing paused studies)
    if (!in_array($study->status, array('active', 'paused'))) {
        return eipsi_longitudinal_study_error(
            sprintf(__('ℹ️ El estudio "%s" no está disponible actualmente.', 'eipsi-forms'), esc_html($study->study_name)),
            __('Estado: ' . ucfirst($study->status), 'eipsi-forms')
        );
    }
    
    // Enqueue required assets
    wp_enqueue_style(
        'eipsi-longitudinal-study-css',
        EIPSI_FORMS_PLUGIN_URL . 'assets/css/longitudinal-study-shortcode.css',
        array('eipsi-theme-toggle-css'),
        EIPSI_FORMS_VERSION
    );
    
    wp_enqueue_script(
        'eipsi-longitudinal-study-js',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/longitudinal-study-shortcode.js',
        array('jquery'),
        EIPSI_FORMS_VERSION,
        true
    );
    
    wp_localize_script('eipsi-longitudinal-study-js', 'eipsiLongitudinalStudyL10n', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_longitudinal_study_nonce'),
        'strings' => array(
            'copied' => __('¡Shortcode copiado!', 'eipsi-forms'),
            'linkCopied' => __('¡Enlace copiado!', 'eipsi-forms'),
            'copyError' => __('Error al copiar', 'eipsi-forms'),
            'shareTitle' => __('Compartir Estudio', 'eipsi-forms'),
            'shareDescription' => __('Copia el shortcode o el enlace para compartir este estudio.', 'eipsi-forms')
        )
    ));
    
    // Get waves for the study
    $waves = array();
    if ($show_waves) {
        if ($wave_index > 0) {
            // Get specific wave
            $waves = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_waves 
                 WHERE study_id = %d AND wave_index = %d 
                 ORDER BY wave_index ASC",
                $study_id,
                $wave_index
            ), ARRAY_A);
        } else {
            // Get all waves
            $waves = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_waves 
                 WHERE study_id = %d 
                 ORDER BY wave_index ASC",
                $study_id
            ), ARRAY_A);
        }
    }
    
    // Get participant count
    $participant_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d",
        $study_id
    ));
    
    // Get study configuration from JSON
    $study_config = !empty($study->config) ? json_decode($study->config, true) : array();
    
    // Get principal investigator
    $pi_name = '';
    if (!empty($study->principal_investigator_id)) {
        $pi_user = get_userdata($study->principal_investigator_id);
        if ($pi_user) {
            $pi_name = $pi_user->display_name;
        }
    }
    
    // Build shareable URL
    $current_url = get_permalink();
    $shareable_url = add_query_arg(array(
        'eipsi_study' => $study->study_code, // Use study_code for security
        'wave' => $wave_index > 0 ? $wave_index : '',
    ), $current_url);
    
    // Generate the shortcode string for copying (PREFER study_code for security)
    $shortcode_string = '[eipsi_longitudinal_study study_code="' . $study->study_code . '"';
    if ($wave_index > 0) {
        $shortcode_string .= ' wave="' . $wave_index . '"';
    }
    if ($time_limit_override > 0) {
        $shortcode_string .= ' time_limit="' . $time_limit_override . '"';
    }
    $shortcode_string .= ']';
    
    // Render the template
    ob_start();
    include EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/longitudinal-study-display.php';
    return ob_get_clean();
}
add_shortcode('eipsi_longitudinal_study', 'eipsi_longitudinal_study_shortcode');

/**
 * Generate error notice for longitudinal study shortcode
 * 
 * @param string $message Error message
 * @param string $help Optional help text
 * @return string HTML error notice
 */
function eipsi_longitudinal_study_error($message, $help = '') {
    $output = '<div class="eipsi-longitudinal-study-error" style="background: #ffebee; border-left: 4px solid #f44336; padding: 1rem; margin: 1rem 0; border-radius: 4px;">';
    $output .= '<p style="margin: 0; color: #c62828; font-weight: 500;">' . esc_html($message) . '</p>';
    if (!empty($help)) {
        $output .= '<p style="margin: 0.5rem 0 0 0; color: #d32f2f; font-size: 0.9rem;">' . esc_html($help) . '</p>';
    }
    $output .= '</div>';
    return $output;
}

/**
 * Helper function to get form title by ID
 * 
 * @param int $form_id Form post ID
 * @return string Form title or fallback
 */
function eipsi_get_form_title($form_id) {
    $form_post = get_post($form_id);
    if ($form_post) {
        return $form_post->post_title;
    }
    return __('Formulario no disponible', 'eipsi-forms');
}

/**
 * Helper function to format time limit
 * 
 * @param int $minutes Time limit in minutes
 * @return string Formatted time string
 */
function eipsi_format_time_limit($minutes) {
    if ($minutes <= 0) {
        return __('Ilimitado', 'eipsi-forms');
    }
    if ($minutes < 60) {
        return sprintf(__('%d minutos', 'eipsi-forms'), $minutes);
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($mins === 0) {
        return sprintf(__('%d horas', 'eipsi-forms'), $hours);
    }
    return sprintf(__('%d horas %d minutos', 'eipsi-forms'), $hours, $mins);
}

/**
 * Helper function to get wave status label
 * 
 * @param string $status Wave status
 * @return string Status label
 */
function eipsi_get_wave_status_label($status) {
    $labels = array(
        'draft' => __('Borrador', 'eipsi-forms'),
        'active' => __('Activa', 'eipsi-forms'),
        'completed' => __('Completada', 'eipsi-forms'),
        'paused' => __('Pausada', 'eipsi-forms'),
    );
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}

/**
 * Add longitudinal study shortcode help to metabox
 * 
 * @since 1.5.0
 * @since 1.6.0 - Updated to use study_code for security
 */
function eipsi_add_longitudinal_study_to_metabox($post) {
    global $wpdb;

    // Get available longitudinal studies
    $studies = $wpdb->get_results(
        "SELECT id, study_name, study_code FROM {$wpdb->prefix}survey_studies
         WHERE status IN ('active', 'paused')
         ORDER BY created_at DESC"
    );

    if (!empty($studies)) {
        ?>
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

        <p style="font-size: 13px; margin: 0 0 12px; font-weight: 600;">
            <?php esc_html_e('Estudios Longitudinales disponibles:', 'eipsi-forms'); ?>
        </p>

        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px;">
            <?php foreach ($studies as $study) :
                $secure_shortcode = '[eipsi_longitudinal_study study_code="' . esc_attr($study->study_code) . '"]';
            ?>
                <div style="padding: 8px; border-bottom: 1px solid #f0f0f1; font-size: 12px;">
                    <div style="font-weight: 600; margin-bottom: 4px;">
                        <?php echo esc_html($study->study_name); ?>
                        <span style="color: #666; font-weight: normal;">(<?php echo esc_html($study->study_code); ?>)</span>
                    </div>
                    <!-- SECURE SHORTCODE -->
                    <code style="background: #e3f2fd; padding: 2px 6px; border-radius: 2px; font-size: 11px; cursor: pointer; border: 1px solid #2196f3; color: #1565c0;"
                          onclick="navigator.clipboard.writeText(<?php echo wp_json_encode($secure_shortcode); ?>); this.style.background='#00a32a'; this.style.color='white'; this.style.borderColor='#00a32a'; setTimeout(() => { this.style.background='#e3f2fd'; this.style.color='#1565c0'; this.style.borderColor='#2196f3'; }, 1000);"
                          title="<?php esc_attr_e('Clic para copiar shortcode seguro', 'eipsi-forms'); ?>">
                        🔒 [eipsi_longitudinal_study study_code="<?php echo esc_html($study->study_code); ?>"]
                    </code>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="font-size: 11px; color: #666; margin: 8px 0 0;">
            <em>
                <strong><?php esc_html_e('🔒 Nuevo formato seguro:', 'eipsi-forms'); ?></strong>
                <?php esc_html_e('Usá study_code en lugar de ID para mayor seguridad.', 'eipsi-forms'); ?><br>
                <?php esc_html_e('Atributos opcionales: wave="1", time_limit="30", show_config="no", view="participant"', 'eipsi-forms'); ?>
            </em>
        </p>
        <?php
    }
}
add_action('eipsi_shortcode_help_metabox_content', 'eipsi_add_longitudinal_study_to_metabox', 10, 1);
