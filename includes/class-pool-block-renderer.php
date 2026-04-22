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
                'button_text'   => '',  // Atributo viejo
                'button_label'  => __('Comenzar mi participación', 'eipsi-forms'), // Atributo viejo alternativo
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

        // Determinar el texto del botón (soporta ambos atributos viejos)
        $button_text = !empty($atts['button_text'])
            ? $atts['button_text']
            : $atts['button_label'];

        // Convertir a atributos del nuevo formato
        $new_atts = array(
            'pool_id'       => $pool_id,
            'method'        => 'seeded',
            'button_text'   => sanitize_text_field($button_text),
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
        global $wpdb;
        $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';

        // Get pool data
        $pool = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$pools_table} WHERE id = %d",
            $pool_id
        ), ARRAY_A);

        if (!$pool) {
            return '<div class="eipsi-pool-wrapper"><p>' . esc_html__('Pool no encontrado.', 'eipsi-forms') . '</p></div>';
        }

        $pool_name = $pool['name'] ?? 'Pool';
        $description = $pool['description'] ?? '';
        $incentive = $pool['incentive'] ?? '';

        // Pool-specific login template (different from survey login)
        ob_start();
        ?>
        <div class="eipsi-pool-wrapper">
            <div class="eipsi-pool-access-container">
                <div class="eipsi-pool-header">
                    <div class="eipsi-pool-icon">🏊</div>
                    <h2 class="eipsi-pool-title"><?php echo esc_html($pool_name); ?></h2>
                    <?php if ($description): ?>
                        <p class="eipsi-pool-description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                    <?php if ($incentive): ?>
                        <div class="eipsi-pool-incentive">
                            <span class="incentive-icon">🎁</span>
                            <span class="incentive-text"><?php echo esc_html($incentive); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="eipsi-pool-login-box">
                    <h3><?php esc_html_e('Iniciá sesión para participar', 'eipsi-forms'); ?></h3>
                    <p class="eipsi-pool-login-subtitle"><?php esc_html_e('Necesitás estar autenticado para unirte a este pool de estudios.', 'eipsi-forms'); ?></p>
                    <?php echo do_shortcode('[eipsi_survey_login]'); ?>
                </div>
            </div>
        </div>

        <style>
            .eipsi-pool-access-container {
                max-width: 600px;
                margin: 0 auto;
                padding: 2rem 1rem;
            }
            .eipsi-pool-header {
                text-align: center;
                margin-bottom: 2rem;
            }
            .eipsi-pool-icon {
                font-size: 48px;
                margin-bottom: 0.5rem;
            }
            .eipsi-pool-title {
                font-size: 24px;
                font-weight: 600;
                color: #1e293b;
                margin: 0 0 1rem 0;
            }
            .eipsi-pool-description {
                font-size: 16px;
                color: #64748b;
                margin: 0 0 1rem 0;
                line-height: 1.5;
            }
            .eipsi-pool-incentive {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #fef3c7;
                padding: 12px 16px;
                border-radius: 8px;
                margin-top: 1rem;
            }
            .eipsi-pool-incentive .incentive-icon {
                font-size: 20px;
            }
            .eipsi-pool-incentive .incentive-text {
                font-size: 14px;
                color: #92400e;
                font-weight: 500;
            }
            .eipsi-pool-login-box {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 2rem;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
            .eipsi-pool-login-box h3 {
                font-size: 18px;
                font-weight: 600;
                color: #1e293b;
                margin: 0 0 0.5rem 0;
                text-align: center;
            }
            .eipsi-pool-login-subtitle {
                font-size: 14px;
                color: #64748b;
                margin: 0 0 1.5rem 0;
                text-align: center;
            }
        </style>
        <?php
        return ob_get_clean();
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
