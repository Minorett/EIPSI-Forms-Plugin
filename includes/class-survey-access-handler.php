<?php
/**
 * Survey Access Handler
 * 
 * Handles Magic Link validation and participant authentication.
 * Endpoint: /survey-access/?ml=TOKEN
 * 
 * @package EIPSI_Forms
 * @since 1.4.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Survey_Access_Handler {

    /**
     * Initialize the handler
     */
    public function init() {
        // Listen for requests to /survey-access/
        add_action('template_redirect', array($this, 'handle_request'));
        
        // Add rewrite rule just in case (optional, but good for cleanliness)
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Also handle email confirmation at root level
        add_action('template_redirect', array($this, 'handle_confirmation_request'));
    }

    /**
     * Handle email confirmation request
     * Endpoint: /?eipsi_confirm=TOKEN&email=EMAIL
     * 
     * @since 1.5.0
     */
    public function handle_confirmation_request() {
        // Check for confirmation parameter
        $confirm_token = isset($_GET['eipsi_confirm']) ? sanitize_text_field($_GET['eipsi_confirm']) : '';
        $confirm_email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
        
        if (empty($confirm_token) || empty($confirm_email)) {
            return; // Not a confirmation request
        }
        
        // Prevent caching
        nocache_headers();
        
        // Load required services
        if (!class_exists('EIPSI_Email_Confirmation_Service')) {
            $service_path = EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-confirmation-service.php';
            if (file_exists($service_path)) {
                require_once $service_path;
            } else {
                $this->render_confirmation_error('Error del Sistema', 'El servicio de confirmación no está disponible.');
                return;
            }
        }
        
        // Validate token
        $validation_result = EIPSI_Email_Confirmation_Service::validate_confirmation_token($confirm_token, $confirm_email);
        
        if (!$validation_result['success']) {
            $this->handle_confirmation_error($validation_result['error']);
            return;
        }
        
        // Mark confirmation as completed
        $mark_result = EIPSI_Email_Confirmation_Service::mark_confirmed($confirm_token, $confirm_email);
        if (!$mark_result['success']) {
            $this->render_confirmation_error('Error', 'No se pudo completar la confirmación. Por favor, contacta al administrador.');
            return;
        }
        
        // Activate the participant (set is_active = 1)
        global $wpdb;
        $participant_id = $validation_result['participant_id'];
        $survey_id = $validation_result['survey_id'];
        
        $wpdb->update(
            $wpdb->prefix . 'survey_participants',
            array('is_active' => 1, 'updated_at' => current_time('mysql')),
            array('id' => $participant_id),
            array('%d', '%s'),
            array('%d')
        );
        
        // Send welcome email with magic link
        if (!class_exists('EIPSI_Email_Service')) {
            $email_service_path = EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-service.php';
            if (file_exists($email_service_path)) {
                require_once $email_service_path;
            }
        }
        
        if (class_exists('EIPSI_Email_Service')) {
            EIPSI_Email_Service::send_welcome_after_confirmation($survey_id, $participant_id);
        }
        
        // Redirect to login page with confirmation message
        $this->render_confirmation_success($validation_result['email'], $survey_id);
    }
    
    /**
     * Handle confirmation errors
     */
    private function handle_confirmation_error($error) {
        $title = 'Confirmación fallida';
        $message = 'El enlace de confirmación no es válido.';
        
        switch ($error) {
            case 'invalid_token':
                $message = 'El enlace de confirmación no es válido o ya ha sido utilizado.';
                break;
            case 'token_expired':
                $title = 'Enlace expirado';
                $message = 'Este enlace de confirmación ha expirado. Por favor, solicita un nuevo enlace de confirmación desde el panel del estudio.';
                break;
            case 'invalid_parameters':
            default:
                $message = 'El enlace de confirmación está incompleto o es inválido.';
                break;
        }
        
        $this->render_confirmation_error($title, $message);
    }
    
    /**
     * Render confirmation success page - Redirect to login
     */
    private function render_confirmation_success($email, $survey_id = 0) {
        // Find study login page
        $login_url = $this->find_study_login_page($survey_id);
        
        // Add confirmation message as query parameter
        $redirect_url = add_query_arg(array(
            'eipsi_msg' => 'email_confirmed',
            'eipsi_email' => urlencode($email)
        ), $login_url);
        
        // Redirect to login page
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Find study login page URL
     * 
     * @param int $survey_id Survey/Study ID
     * @return string Login page URL
     */
    private function find_study_login_page($survey_id = 0) {
        // Method 1: Check for study login page with survey_id meta
        if ($survey_id > 0) {
            $login_pages = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'meta_query' => array(
                    array(
                        'key' => '_eipsi_survey_id',
                        'value' => $survey_id
                    )
                )
            ));
            
            if (!empty($login_pages)) {
                return get_permalink($login_pages[0]->ID);
            }
        }
        
        // Method 2: Find any page with eipsi_survey_login shortcode
        $login_pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            's' => 'eipsi_survey_login'
        ));
        
        if (!empty($login_pages)) {
            return get_permalink($login_pages[0]->ID);
        }
        
        // Method 3: Find participant dashboard page
        $dashboard_pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_eipsi_has_dashboard',
                    'value' => '1'
                )
            )
        ));
        
        if (!empty($dashboard_pages)) {
            return get_permalink($dashboard_pages[0]->ID);
        }
        
        // Default to home page
        return home_url('/');
    }
    
    /**
     * Render confirmation error page
     */
    private function render_confirmation_error($title, $message) {
        status_header(403);
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($title); ?> - EIPSI Forms</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; margin: 0; padding: 20px; }
                .container { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 40px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
                .error-icon { font-size: 60px; color: #dc3545; margin-bottom: 20px; }
                h1 { color: #dc3545; margin: 0 0 20px; font-size: 24px; }
                p { margin: 0 0 15px; color: #555; }
                .footer { margin-top: 30px; font-size: 13px; color: #888; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-icon">⚠</div>
                <h1><?php echo esc_html($title); ?></h1>
                <p><?php echo esc_html($message); ?></p>
                <div class="footer">
                    <p>© <?php echo date('Y'); ?> EIPSI Forms</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Add rewrite rules for the endpoint
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^survey-access/?$', 'index.php?eipsi_route=survey_access', 'top');
        add_rewrite_tag('%eipsi_route%', '([^&]+)');
    }

    /**
     * Handle the request
     */
    public function handle_request() {
        global $wp_query;
        
        // Check if we are hitting our endpoint
        // Method 1: Rewrite rule
        if (get_query_var('eipsi_route') === 'survey_access') {
            $is_endpoint = true;
        } 
        // Method 2: Direct path check (fallback if flush_rewrite_rules hasn't run)
        else {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $is_endpoint = (rtrim($path, '/') === '/survey-access');
        }

        if (!$is_endpoint) {
            return;
        }

        // Prevent caching
        nocache_headers();

        $token = isset($_GET['ml']) ? sanitize_text_field($_GET['ml']) : '';

        if (empty($token)) {
            $this->render_error('Token no válido', 'El enlace no contiene un token de acceso válido.');
            return;
        }

        // Ensure MagicLinksService is loaded
        if (!class_exists('EIPSI_MagicLinksService')) {
            $service_path = EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-magic-links-service.php';
            if (file_exists($service_path)) {
                require_once $service_path;
            } else {
                $this->render_error('Error del Sistema', 'El servicio de autenticación no está disponible.');
                return;
            }
        }

        // Validate Token
        $result = EIPSI_MagicLinksService::validate_magic_link($token);

        if (!$result['valid']) {
            $this->handle_validation_error($result);
            return;
        }

        // Find Target Wave (needed for redirection and session)
        // We use the survey_id (likely study_id) and participant_id to find the pending wave
        $wave_info = $this->get_target_wave($result['survey_id'], $result['participant_id']);

        if (!$wave_info) {
             // Case: No pending assignments. Maybe they are done?
             // We can still log them in to the dashboard if one exists, or show a message.
             $this->render_error('Sin encuestas pendientes', '¡Hola! No tienes encuestas pendientes en este momento. Gracias por tu participación.', false);
             return;
        }

        // Success - Setup Session
        $this->setup_session($result, $wave_info);

        // Mark token as used
        EIPSI_MagicLinksService::mark_magic_link_used($result['ml_id']);

        // Redirect to Participant Portal
        // Use /estudio/ by default (participant portal), with filter for customization
        $participant_portal_url = apply_filters('eipsi_participant_portal_url', home_url('/estudio/'), $result['study_id'], $result['participant_id']);
        
        $redirect_url = add_query_arg(array(
            'form_id' => $wave_info->form_id,
            'wave_id' => $wave_info->wave_id
        ), $participant_portal_url);

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Find the next pending wave for the participant
     */
    private function get_target_wave($study_id, $participant_id) {
        global $wpdb;
        
        // Query to find the first pending or in_progress assignment
        // Joins with waves table to get form_id
        $sql = "SELECT a.wave_id, w.form_id 
                FROM {$wpdb->prefix}survey_assignments a
                JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
                WHERE a.participant_id = %d 
                AND a.study_id = %d 
                AND a.status IN ('pending', 'in_progress')
                ORDER BY w.wave_index ASC
                LIMIT 1";
        
        return $wpdb->get_row($wpdb->prepare($sql, $participant_id, $study_id));
    }

    /**
     * Setup temporary authentication session
     */
    private function setup_session($auth_result, $wave_info) {
        if (!session_id()) {
            session_start();
        }

        $_SESSION['eipsi_participant_id'] = $auth_result['participant_id'];
        $_SESSION['eipsi_survey_id'] = $auth_result['survey_id']; // This is study_id
        $_SESSION['eipsi_wave_id'] = $wave_info->wave_id;
        $_SESSION['eipsi_authenticated'] = true;
        $_SESSION['eipsi_magic_link_session'] = true; // Flag for tracking source
        $_SESSION['eipsi_auth_ttl'] = time() + 3600; // 1 hour expiration
        
        // Optional: Log login event if audit service exists
    }

    /**
     * Handle validation failures with specific messages
     */
    private function handle_validation_error($result) {
        $title = 'Enlace no válido';
        $message = 'El enlace que has utilizado no es válido.';
        $show_button = true;

        switch ($result['reason']) {
            case 'expired':
                $title = 'Enlace expirado';
                $message = 'Este enlace de seguridad ha expirado (válido por 48 horas). Por favor, solicita uno nuevo.';
                break;
            case 'already_used':
                $title = 'Enlace ya utilizado';
                $message = 'Este enlace ya fue utilizado anteriormente. Por seguridad, los enlaces de acceso solo funcionan una vez.';
                break;
            case 'not_found':
            case 'empty_token':
            default:
                $message = 'El enlace no es válido o está incompleto. Asegúrate de copiar la dirección completa.';
                break;
        }

        $this->render_error($title, $message, $show_button);
    }

    /**
     * Render the error page
     */
    private function render_error($title, $message, $show_button = false) {
        // Use WordPress template structure if possible, or a clean standalone output
        // We'll use a clean output to avoid theme conflicts for this critical utility page
        
        status_header(403); // Forbidden/Error
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($title); ?> - EIPSI Forms</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    background-color: #f0f2f5;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                    color: #1d2327;
                }
                .eipsi-error-card {
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                    max-width: 450px;
                    width: 90%;
                    text-align: center;
                }
                h1 {
                    margin-top: 0;
                    color: #d63638;
                    font-size: 24px;
                }
                p {
                    line-height: 1.5;
                    color: #646970;
                    margin-bottom: 25px;
                }
                .eipsi-btn {
                    display: inline-block;
                    background: #2271b1;
                    color: white;
                    text-decoration: none;
                    padding: 12px 24px;
                    border-radius: 6px;
                    font-weight: 500;
                    transition: background 0.2s;
                }
                .eipsi-btn:hover {
                    background: #135e96;
                }
                .eipsi-logo {
                    margin-bottom: 20px;
                    font-weight: bold;
                    color: #2271b1;
                    font-size: 1.2em;
                }
            </style>
        </head>
        <body>
            <div class="eipsi-error-card">
                <div class="eipsi-logo">EIPSI Forms</div>
                <h1><?php echo esc_html($title); ?></h1>
                <p><?php echo esc_html($message); ?></p>
                
                <?php if ($show_button): ?>
                    <a href="mailto:?subject=Solicitud de nuevo enlace de acceso&body=Hola, intenté acceder a la encuesta pero mi enlace no funcionó. Por favor envíenme uno nuevo." class="eipsi-btn">
                        Pedir nuevo enlace
                    </a>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
