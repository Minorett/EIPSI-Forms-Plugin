<?php
/**
 * EIPSI Forms - Pool Block Renderer
 *
 * Renderiza el bloque Gutenberg "Pool de Estudios" y el shortcode [eipsi_pool].
 * Maneja la asignación de participantes a estudios longitudinales.
 *
 * @package EIPSI_Forms
 * @since 2.5.3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que renderiza el bloque Pool y el shortcode [eipsi_pool].
 */
class EIPSI_Pool_Block_Renderer {

    /**
     * Inicializar el shortcode y bloque.
     */
    public static function init() {
        add_shortcode('eipsi_pool', array(__CLASS__, 'render_shortcode'));
        add_shortcode('eipsi_pool_join', array(__CLASS__, 'render_shortcode_compat'));
    }

    /**
     * Render callback para el bloque Gutenberg.
     *
     * @param array $attributes Atributos del bloque.
     * @return string HTML renderizado.
     */
    public static function render_block($attributes) {
        $pool_id = sanitize_text_field($attributes['poolId'] ?? '');
        $method = sanitize_text_field($attributes['method'] ?? 'seeded');
        $button_text = sanitize_text_field($attributes['buttonText'] ?? __('Unirse al estudio', 'eipsi-forms'));
        $redirect_mode = sanitize_text_field($attributes['redirectMode'] ?? 'auto');

        if (!$pool_id) {
            return '<p class="eipsi-pool-error">' . esc_html__('Configurá el bloque seleccionando estudios.', 'eipsi-forms') . '</p>';
        }

        return self::render_pool_content($pool_id, $method, $button_text, $redirect_mode);
    }

    /**
     * Shortcode [eipsi_pool pool_id="X"]
     *
     * @param array $atts Atributos del shortcode.
     * @return string HTML renderizado.
     */
    public static function render_shortcode($atts) {
        // No renderizar en el panel admin
        if (is_admin()) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'pool_id'       => 0,
                'method'        => 'seeded',
                'button_text'   => __('Unirse al estudio', 'eipsi-forms'),
                'redirect_mode' => 'auto',
            ),
            $atts,
            'eipsi_pool'
        );

        $pool_id = absint($atts['pool_id']);
        $method = sanitize_text_field($atts['method']);
        $button_text = sanitize_text_field($atts['button_text']);
        $redirect_mode = sanitize_text_field($atts['redirect_mode']);

        if (!$pool_id) {
            return sprintf(
                '<p class="eipsi-pool-error">%s</p>',
                esc_html__('Error: pool_id no especificado.', 'eipsi-forms')
            );
        }

        return self::render_pool_content($pool_id, $method, $button_text, $redirect_mode);
    }

    /**
     * Shortcode de compatibilidad [eipsi_pool_join pool_id="X"]
     * Alias del shortcode nuevo para mantener backward compatibility.
     *
     * @param array $atts Atributos del shortcode.
     * @return string HTML renderizado.
     */
    public static function render_shortcode_compat($atts) {
        if (is_admin()) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'pool_id'       => 0,
                'show_name'     => '0',
                'redirect'      => '1',
                'button_label'  => __('Comenzar mi participación', 'eipsi-forms'),
            ),
            $atts,
            'eipsi_pool_join'
        );

        $pool_id = absint($atts['pool_id']);
        if (!$pool_id) {
            return sprintf(
                '<p class="eipsi-pool-error">%s</p>',
                esc_html__('Error: pool_id no especificado.', 'eipsi-forms')
            );
        }

        // Convertir a atributos del nuevo formato
        $new_atts = array(
            'pool_id'       => $pool_id,
            'method'        => 'seeded',
            'button_text'   => sanitize_text_field($atts['button_label']),
            'redirect_mode' => filter_var($atts['redirect'], FILTER_VALIDATE_BOOLEAN) ? 'auto' : 'embed',
        );

        return self::render_shortcode($new_atts);
    }

    /**
     * Renderizar el contenido del pool.
     *
     * @param int    $pool_id       ID del pool.
     * @param string $method        Método de asignación.
     * @param string $button_text   Texto del botón.
     * @param string $redirect_mode Modo de redirección.
     * @return string HTML renderizado.
     */
    private static function render_pool_content($pool_id, $method, $button_text, $redirect_mode) {
        global $wpdb;

        // Verificar que el pool existe y está activo
        $pool = self::get_pool_info($pool_id);
        if (!$pool) {
            return sprintf(
                '<div class="eipsi-pool-wrapper"><div class="eipsi-pool-error">' .
                '<div class="eipsi-pool-error-icon">⚠️</div>' .
                '<div class="eipsi-pool-error-message">%s</div></div></div>',
                esc_html__('El pool solicitado no existe o no está disponible.', 'eipsi-forms')
            );
        }

        // Verificar si el pool está pausado
        if ($pool->status === 'paused') {
            return sprintf(
                '<div class="eipsi-pool-wrapper"><div class="eipsi-pool-paused">' .
                '<div class="eipsi-pool-paused-icon">⏸️</div>' .
                '<div class="eipsi-pool-paused-message">%s</div></div></div>',
                esc_html__('Este pool está temporalmente pausado.', 'eipsi-forms')
            );
        }

        // Verificar autenticación del participante
        $participant_id = 0;
        $current_study_id = 0;
        
        if (class_exists('EIPSI_Auth_Service')) {
            $participant_id = EIPSI_Auth_Service::get_current_participant();
            $current_study_id = EIPSI_Auth_Service::get_current_survey();
        }

        // Si no está autenticado, mostrar formulario de login
        if (!$participant_id) {
            return self::render_login_required($pool_id);
        }

        // Obtener o crear asignación del participante
        $assignment = self::get_or_create_assignment($pool_id, $participant_id, $method);

        if (!$assignment) {
            return sprintf(
                '<div class="eipsi-pool-wrapper"><div class="eipsi-pool-error">' .
                '<div class="eipsi-pool-error-icon">❌</div>' .
                '<div class="eipsi-pool-error-message">%s</div></div></div>',
                esc_html__('Error al procesar tu asignación. Por favor, intentá de nuevo.', 'eipsi-forms')
            );
        }

        // Si ya completó el estudio asignado
        if ($assignment['completed']) {
            return sprintf(
                '<div class="eipsi-pool-wrapper"><div class="eipsi-pool-completed">' .
                '<div class="eipsi-pool-completed-icon">✅</div>' .
                '<div class="eipsi-pool-completed-message">%s</div></div></div>',
                esc_html__('¡Ya completaste tu participación en este pool!', 'eipsi-forms')
            );
        }

        // Si ya tiene asignación, mostrar estado
        if ($assignment['is_existing']) {
            return self::render_assigned_state($assignment, $button_text, $redirect_mode);
        }

        // Nueva asignación - mostrar botón para unirse
        return self::render_join_button($assignment, $button_text, $redirect_mode);
    }

    /**
     * Obtener información del pool.
     *
     * @param int $pool_id ID del pool.
     * @return object|null Datos del pool o null si no existe.
     */
    private static function get_pool_info($pool_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eipsi_longitudinal_pools';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $pool_id
            )
        );
    }

    /**
     * Obtener o crear asignación de participante usando el servicio.
     *
     * @param int    $pool_id       ID del pool.
     * @param int    $participant_id ID del participante.
     * @param string $method        Método de asignación.
     * @return array|null Datos de la asignación o null.
     */
    private static function get_or_create_assignment($pool_id, $participant_id, $method) {
        // Usar el servicio de asignación de pools (Fase 3)
        $service = new EIPSI_Pool_Assignment_Service();
        
        $assignment = $service->assign_participant($pool_id, $participant_id, $method);
        
        if (!$assignment) {
            return null;
        }

        return array(
            'study_id'      => $assignment->study_id,
            'is_existing'   => $assignment->is_existing,
            'completed'     => $assignment->completed,
            'study_url'     => $service->get_study_url($assignment->study_id),
        );
    }

    /**
     * Renderizar estado de asignación existente.
     *
     * @param array  $assignment   Datos de la asignación.
     * @param string $button_text  Texto del botón.
     * @param string $redirect_mode Modo de redirección.
     * @return string HTML.
     */
    private static function render_assigned_state($assignment, $button_text, $redirect_mode) {
        $study_name = self::get_study_name($assignment['study_id']);

        // Si es modo auto, redirigir inmediatamente
        if ($redirect_mode === 'auto' && !empty($assignment['study_url'])) {
            wp_redirect($assignment['study_url']);
            exit;
        }

        return sprintf(
            '<div class="eipsi-pool-wrapper">
                <div class="eipsi-pool-assigned">
                    <div class="eipsi-pool-assigned-badge">
                        <span>✓</span> %s
                    </div>
                    <div class="eipsi-pool-study-name">%s</div>
                    <div class="eipsi-pool-status">%s</div>
                    <a href="%s" class="eipsi-pool-join-button" style="margin-top: 1rem; display: inline-block;">
                        %s
                    </a>
                </div>
            </div>',
            esc_html__('Ya tenés un estudio asignado', 'eipsi-forms'),
            esc_html($study_name),
            esc_html__('Hacé clic para continuar con tu estudio', 'eipsi-forms'),
            esc_url($assignment['study_url']),
            esc_html($button_text)
        );
    }

    /**
     * Renderizar botón para unirse.
     *
     * @param array  $assignment   Datos de la asignación.
     * @param string $button_text  Texto del botón.
     * @param string $redirect_mode Modo de redirección.
     * @return string HTML.
     */
    private static function render_join_button($assignment, $button_text, $redirect_mode) {
        // Si es modo auto, redirigir inmediatamente
        if ($redirect_mode === 'auto' && !empty($assignment['study_url'])) {
            wp_redirect($assignment['study_url']);
            exit;
        }

        $study_name = self::get_study_name($assignment['study_id']);

        return sprintf(
            '<div class="eipsi-pool-wrapper">
                <div class="eipsi-pool-join">
                    <p style="margin-bottom: 1rem;">%s <strong>%s</strong></p>
                    <a href="%s" class="eipsi-pool-join-button">
                        %s
                    </a>
                </div>
            </div>',
            esc_html__('Te hemos asignado al estudio:', 'eipsi-forms'),
            esc_html($study_name),
            esc_url($assignment['study_url']),
            esc_html($button_text)
        );
    }

    /**
     * Renderizar login requerido.
     *
     * @param int $pool_id ID del pool.
     * @return string HTML.
     */
    private static function render_login_required($pool_id) {
        // Usar el shortcode de login existente
        $login_form = do_shortcode('[eipsi_survey_login]');

        return sprintf(
            '<div class="eipsi-pool-wrapper">
                <div class="eipsi-pool-login-required">
                    <div class="eipsi-pool-login-title">%s</div>
                    <p style="margin-bottom: 1rem; color: #64748b;">%s</p>
                    %s
                </div>
            </div>',
            esc_html__('Iniciá sesión para participar', 'eipsi-forms'),
            esc_html__('Necesitás estar autenticado para unirte a este pool de estudios.', 'eipsi-forms'),
            $login_form
        );
    }

    /**
     * Obtener nombre del estudio.
     *
     * @param int $study_id ID del estudio.
     * @return string Nombre del estudio.
     */
    private static function get_study_name($study_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'survey_studies';

        $name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT study_name FROM {$table} WHERE id = %d",
                $study_id
            )
        );

        return $name ?: sprintf(__('Estudio #%d', 'eipsi-forms'), $study_id);
    }
}

// Inicializar
add_action('init', array('EIPSI_Pool_Block_Renderer', 'init'));
