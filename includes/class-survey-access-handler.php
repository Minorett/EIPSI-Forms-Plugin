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

        // Redirect to Survey
        $redirect_url = home_url('/survey/');
        $redirect_url = add_query_arg(array(
            'form_id' => $wave_info->form_id,
            'wave_id' => $wave_info->wave_id
        ), $redirect_url);

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
