<?php
/**
 * EIPSI Randomization Frontend - Procesamiento de Aleatorización
 * 
 * Maneja la lógica de aleatorización para el nuevo flujo manual de shortcodes.
 * 
 * @package EIPSI_Forms
 * @since 1.3.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Función para calcular asignación aleatoria basada en probabilidades
 * 
 * @param array $config Configuración de aleatorización
 * @param string $user_fingerprint Fingerprint del usuario
 * @return int Post ID del formulario asignado
 */
function eipsi_calculate_frontend_assignment( $config, $user_fingerprint ) {
    $formularios = $config['formularios'];
    $metodo = $config['metodo'] ?? 'pure-random';
    $seed = $config['seed'] ?? '';

    // Si es método seeded, usar hash del fingerprint como seed
    if ( $metodo === 'seeded' && ! empty( $seed ) ) {
        $final_seed = crc32( $user_fingerprint . $seed );
        mt_srand( $final_seed );
        error_log( "[EIPSI RCT] Método seeded - seed: {$final_seed}" );
    }

    // Crear array de probabilidades acumuladas
    $cumulative_probabilities = array();
    $cumulative = 0;

    foreach ( $formularios as $form ) {
        $form_id = $form['id'];
        $porcentaje = $config['probabilidades'][ $form_id ] ?? 0;
        
        $cumulative += $porcentaje;
        $cumulative_probabilities[] = array(
            'form_id' => $form_id,
            'cumulative' => $cumulative,
        );
    }

    // Generar número aleatorio entre 0-100
    $random = mt_rand( 0, 100 );

    error_log( "[EIPSI RCT] Random generado: {$random} de 100" );

    // Encontrar el formulario correspondiente
    foreach ( $cumulative_probabilities as $prob ) {
        if ( $random <= $prob['cumulative'] ) {
            // Resetear seed si era seeded
            if ( $metodo === 'seeded' && ! empty( $seed ) ) {
                mt_srand();
            }
            error_log( "[EIPSI RCT] Formulario asignado: {$prob['form_id']}" );
            return intval( $prob['form_id'] );
        }
    }

    // Fallback (no debería llegar aquí)
    if ( $metodo === 'seeded' && ! empty( $seed ) ) {
        mt_srand();
    }
    error_log( '[EIPSI RCT] Fallback: usando primer formulario' );
    return intval( $formularios[0]['id'] );
}

/**
 * Función para verificar asignaciones manuales (override ético)
 * 
 * @param array $config Configuración de aleatorización
 * @param string $user_fingerprint Fingerprint del usuario
 * @return int|null Post ID del formulario asignado o null
 */

/**
 * Registrar shortcode público para el nuevo flujo
 */
add_action( 'init', function() {
    add_shortcode( 'eipsi_randomization', 'eipsi_randomization_shortcode' );
} );

/**
 * Enqueue scripts necesarios para el frontend
 */
add_action( 'wp_enqueue_scripts', 'eipsi_randomization_frontend_scripts' );
function eipsi_randomization_frontend_scripts() {
    // Solo cargar si hay un shortcode en la página
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'eipsi_randomization' ) ) {
        return;
    }

    // Enqueue script para fingerprinting (si no está ya cargado)
    if ( ! wp_script_is( 'eipsi-fingerprint', 'enqueued' ) ) {
        wp_enqueue_script(
            'eipsi-fingerprint',
            EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-fingerprint.js',
            array(),
            EIPSI_FORMS_VERSION,
            true
        );
    }
}

/**
 * Agregar datos necesarios para JavaScript
 */
add_action( 'wp_head', 'eipsi_randomization_inline_data' );
function eipsi_randomization_inline_data() {
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'eipsi_randomization' ) ) {
        return;
    }
    ?>
    <script type="text/javascript">
    window.eipsiRandomization = {
        ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
        nonce: '<?php echo esc_js( wp_create_nonce( 'eipsi_randomization_nonce' ) ); ?>',
        strings: {
            loading: '<?php echo esc_js( __( 'Cargando...', 'eipsi-forms' ) ); ?>',
            error: '<?php echo esc_js( __( 'Error al cargar formulario', 'eipsi-forms' ) ); ?>'
        }
    };
    </script>
    <?php
}

/**
 * Handler AJAX para enviar fingerprint del usuario
 */
add_action( 'wp_ajax_eipsi_send_user_fingerprint', 'eipsi_handle_send_user_fingerprint' );
add_action( 'wp_ajax_nopriv_eipsi_send_user_fingerprint', 'eipsi_handle_send_user_fingerprint' );
function eipsi_handle_send_user_fingerprint() {
    check_ajax_referer( 'eipsi_randomization_nonce', 'nonce' );
    
    $fingerprint = sanitize_text_field( $_POST['fingerprint'] ?? '' );
    $template_id = intval( $_POST['template_id'] ?? 0 );
    $config_id = sanitize_text_field( $_POST['config_id'] ?? '' );
    
    if ( empty( $fingerprint ) || empty( $template_id ) || empty( $config_id ) ) {
        wp_send_json_error( array( 'message' => 'Datos incompletos' ) );
    }
    
    // Guardar fingerprint en sesión/temporal para uso posterior
    set_transient( 'eipsi_user_fingerprint_' . $template_id . '_' . $config_id, $fingerprint, HOUR_IN_SECONDS );
    
    wp_send_json_success( array( 'message' => 'Fingerprint recibido' ) );
}

/**
 * Función helper para logging (desarrollo)
 */
function eipsi_randomization_log( $message ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[EIPSI RCT] ' . $message );
    }
}

/**
 * CSS adicional para el frontend
 */
add_action( 'wp_head', 'eipsi_randomization_frontend_styles' );
function eipsi_randomization_frontend_styles() {
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'eipsi_randomization' ) ) {
        return;
    }
    ?>
    <style>
    .eipsi-randomization-container {
        margin: 1rem 0;
        padding: 1rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
    }
    
    .eipsi-randomization-notice {
        background: #e3f2fd;
        border-left: 4px solid #2196F3;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 4px;
    }
    
    .eipsi-randomization-notice p {
        margin: 0;
        color: #0d47a1;
        font-weight: 500;
    }
    
    .eipsi-randomization-loading {
        text-align: center;
        padding: 2rem;
        color: #666;
    }
    
    .eipsi-randomization-error {
        background: #ffebee;
        border-left: 4px solid #f44336;
        padding: 1rem;
        margin: 1rem 0;
        border-radius: 4px;
        color: #c62828;
    }
    </style>
    <?php
}