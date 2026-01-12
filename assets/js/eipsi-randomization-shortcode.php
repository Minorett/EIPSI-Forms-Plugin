<?php
/**
 * EIPSI Randomization Shortcode
 * 
 * Shortcode: [eipsi_randomized_form]
 * 
 * Uso:
 * [eipsi_randomized_form study_id="2394" show_meta="true"]
 * [eipsi_randomized_form study_id="2394" show_meta="false"] (sin instrucciones)
 */

if (!defined('ABSPATH')) {
    exit;
}

function eipsi_randomized_form_shortcode($atts) {
    $atts = shortcode_atts([
        'study_id'  => '',  // ID del formulario base
        'form_id'   => '',  // Sinónimo de study_id (más intuitivo)
        'show_meta' => 'true'  // Mostrar instrucciones/disclaimer
    ], $atts);

    // Soporte para study_id o form_id (prioridad a study_id por compatibilidad)
    $study_id = $atts['study_id'] ?: ($atts['form_id'] ?: false);
    
    // También soportar desde URL params (tanto ?study_id=X como ?form_id=X)
    if (!$study_id) {
        $study_id = $_GET['study_id'] ?? ($_GET['form_id'] ?? false);
    }
    
    if (!$study_id) {
        return '<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
            <p style="margin: 0; color: #c62828; font-weight: 500;">
                ⚠️ Error: No study_id/form_id proporcionado. Use: [eipsi_randomized_form study_id="123"] o [eipsi_randomized_form form_id="123"]
            </p>
        </div>';
    }

    // Verificar que el formulario existe
    if (!get_post($study_id) || get_post_type($study_id) !== 'eipsi_form') {
        return '<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
            <p style="margin: 0; color: #c62828; font-weight: 500;">
                ⚠️ Error: Formulario con ID ' . esc_html($study_id) . ' no encontrado.
            </p>
        </div>';
    }

    // Verificar que tiene configuración de randomización
    $random_config = get_post_meta($study_id, '_eipsi_random_config', true);
    if (!$random_config || empty($random_config['enabled']) || empty($random_config['forms'])) {
        return '<div style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
            <p style="margin: 0; color: #ef6c00; font-weight: 500;">
                ℹ️ Este formulario no tiene configuración de aleatorización activa.
                <br>Contactá al administrador para configurarlo.
            </p>
        </div>';
    }

    ob_start();
    ?>
    <div id="eipsi-randomization-container" 
         class="eipsi-randomization-wrapper" 
         data-study-id="<?php echo esc_attr($study_id); ?>"
         data-random="<?php echo isset($_GET['eipsi_random']) && $_GET['eipsi_random'] === 'true' ? 'true' : 'false'; ?>">
        
        <!-- Disclaimer/Instrucciones (opcional) -->
        <?php if ($atts['show_meta'] === 'true'): ?>
        <div class="randomization-notice" style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
            <p style="margin: 0; color: #0d47a1; font-weight: 500;">
                ℹ️ Este estudio utiliza aleatorización: cada participante recibe un formulario asignado aleatoriamente.
            </p>
            <p style="margin: 0.5rem 0 0 0; color: #1565c0; font-size: 0.9rem;">
                Su asignación es persistente para este estudio. En futuras tomas recibirá el mismo formulario.
            </p>
        </div>
        <?php endif; ?>

        <!-- Spinner mientras carga -->
        <div id="randomization-loading" style="text-align: center; padding: 2rem; display: none;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid #2196F3; border-radius: 50%; animation: eipsi-spin 1s linear infinite;"></div>
            <p style="margin-top: 1rem; color: #666;">Asignando formulario...</p>
            <style>
                @keyframes eipsi-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        </div>

        <!-- Contenedor donde se renderiza el formulario -->
        <div id="randomized-form-container"></div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('eipsi-randomization-container');
        if (container) {
            const studyId = container.dataset.studyId;
            const isRandomized = container.dataset.random;
            eipsiRandomizeForm(studyId, isRandomized);
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('eipsi_randomized_form', 'eipsi_randomized_form_shortcode');

/**
 * Shortcode para página de acceso aleatorio
 * Uso: [eipsi_randomized_form_page]
 * 
 * Este shortcode detecta ?form_id=X o ?study_id=X y renderiza el shortcode principal
 */
function eipsi_randomized_form_page_shortcode($atts) {
    // Detectar desde URL params (soporta study_id y form_id)
    $form_id = $_GET['form_id'] ?? ($_GET['study_id'] ?? false);
    
    if (!$form_id) {
        return '<div style="background: #ffebee; border-left: 4px solid #f44336; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
            <p style="margin: 0; color: #c62828; font-weight: 500;">
                ⚠️ Error: No se especificó un formulario. Usa: <code>?form_id=123</code> en la URL.
            </p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                Ejemplo: <code>' . home_url('/estudio-aleatorio/') . '?form_id=123</code>
            </p>
        </div>';
    }
    
    // Renderizar el shortcode principal con el form_id detectado
    return do_shortcode('[eipsi_randomized_form study_id="' . esc_attr($form_id) . '" show_meta="true"]');
}