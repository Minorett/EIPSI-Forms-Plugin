<?php
/**
 * Template: Progress Bar Component (v1.7.0)
 *
 * Componente reutilizable para mostrar el progreso del formulario
 * en tiempo real, con porcentaje, preguntas respondidas y animación.
 *
 * Uso:
 * <?php echo eipsi_render_progress_bar($form_id, $total_questions, $sticky = false); ?>
 *
 * @package EIPSI_Forms
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render progress bar component
 *
 * @param int $form_id ID del formulario
 * @param int $total_questions Total de preguntas en el formulario
 * @param bool $sticky Si la barra debe ser sticky al hacer scroll
 * @param bool $show_questions_count Si mostrar el conteo de preguntas
 * @return string HTML del componente
 */
function eipsi_render_progress_bar($form_id, $total_questions, $sticky = false, $show_questions_count = true) {
    $sticky_class = $sticky ? 'eipsi-form-progress-container--sticky' : '';
    $unique_id = 'eipsi-progress-' . $form_id;

    ob_start();
    ?>
    <div class="eipsi-form-progress-container <?php echo esc_attr($sticky_class); ?>"
         id="<?php echo esc_attr($unique_id); ?>"
         data-form-id="<?php echo esc_attr($form_id); ?>"
         data-total-questions="<?php echo esc_attr($total_questions); ?>"
         data-form-selector="#vas-form-<?php echo esc_attr($form_id); ?>">

        <!-- Progress Bar -->
        <div class="eipsi-form-progress">
            <div class="eipsi-form-progress__fill" style="width: 0%;"></div>
        </div>

        <!-- Progress Stats -->
        <div class="eipsi-form-progress__stats">
            <div class="eipsi-form-progress__percentage">0%</div>
            <?php if ($show_questions_count): ?>
            <div class="eipsi-form-progress__questions">
                0 <?php esc_html_e('preguntas respondidas de', 'eipsi-forms'); ?> <?php echo absint($total_questions); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render progress bar with milestones
 *
 * @param int $form_id ID del formulario
 * @param int $total_questions Total de preguntas
 * @param array $milestones Array de milestones (opcional)
 * @param bool $sticky Si es sticky
 * @return string HTML del componente
 */
function eipsi_render_progress_bar_milestones($form_id, $total_questions, $milestones = array(), $sticky = false) {
    $default_milestones = array(
        array(
            'percentage' => 25,
            'label' => __('Buen comienzo', 'eipsi-forms'),
            'emoji' => '🌱'
        ),
        array(
            'percentage' => 50,
            'label' => mitad,
            'emoji' => '💪'
        ),
        array(
            'percentage' => 75,
            'label' => casi ahí,
            'emoji' => '🎯'
        ),
        array(
            'percentage' => 100,
            'label' => completado,
            'emoji' => '🌟'
        )
    );

    $milestones = !empty($milestones) ? $milestones : $default_milestones;
    $sticky_class = $sticky ? 'eipsi-form-progress-container--sticky' : '';
    $unique_id = 'eipsi-progress-' . $form_id;

    ob_start();
    ?>
    <div class="eipsi-form-progress-container <?php echo esc_attr($sticky_class); ?>"
         id="<?php echo esc_attr($unique_id); ?>"
         data-form-id="<?php echo esc_attr($form_id); ?>"
         data-total-questions="<?php echo esc_attr($total_questions); ?>"
         data-form-selector="#vas-form-<?php echo esc_attr($form_id); ?>"
         data-milestones='<?php echo json_encode($milestones); ?>'>

        <!-- Progress Bar -->
        <div class="eipsi-form-progress">
            <div class="eipsi-form-progress__fill" style="width: 0%;"></div>
        </div>

        <!-- Progress Stats -->
        <div class="eipsi-form-progress__stats">
            <div class="eipsi-form-progress__percentage">0%</div>
            <div class="eipsi-form-progress__questions">
                0 <?php esc_html_e('de', 'eipsi-forms'); ?> <?php echo absint($total_questions); ?>
            </div>
        </div>

        <!-- Milestones Indicator (opcional) -->
        <?php if (!empty($milestones)): ?>
        <div class="eipsi-form-progress__milestones">
            <?php foreach ($milestones as $milestone): ?>
            <div class="eipsi-form-progress__milestone"
                 data-percentage="<?php echo esc_attr($milestone['percentage']); ?>"
                 data-label="<?php echo esc_attr($milestone['label']); ?>"
                 data-emoji="<?php echo esc_attr($milestone['emoji']); ?>">
                <span class="milestone-emoji"><?php echo esc_html($milestone['emoji']); ?></span>
                <span class="milestone-label"><?php echo esc_html($milestone['label']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .eipsi-form-progress__milestones {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            font-size: 11px;
            color: #999;
        }

        .eipsi-form-progress__milestone {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }

        .eipsi-form-progress__milestone.reached {
            opacity: 1;
        }

        .eipsi-form-progress__milestone .milestone-emoji {
            font-size: 14px;
        }

        .eipsi-form-progress__milestone .milestone-label {
            font-size: 10px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .eipsi-form-progress__milestones {
                font-size: 9px;
            }

            .eipsi-form-progress__milestone .milestone-emoji {
                font-size: 12px;
            }

            .eipsi-form-progress__milestone .milestone-label {
                display: none; /* Ocultar labels en móvil para espacio */
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
?>

<!-- Ejemplo de uso en un formulario -->
<!--
<?php
// Antes del formulario, mostrar barra de progreso
echo eipsi_render_progress_bar(123, 25, true);
?>

<form id="vas-form-123" class="vas-form">
    <!-- Preguntas del formulario -->
</form>

<script>
jQuery(document).ready(function($) {
    // La barra de progreso se inicializa automáticamente
    // cuando se carga el JS participant-ux-enhanced.js
});
</script>
-->

<!-- Ejemplo con milestones -->
<!--
<?php
echo eipsi_render_progress_bar_milestones(123, 25, array(
    array('percentage' => 25, 'label' => 'Buen comienzo', 'emoji' => '🌱'),
    array('percentage' => 50, 'label' => 'Mitad', 'emoji' => '💪'),
    array('percentage' => 75, 'label' => 'Casi ahí', 'emoji' => '🎯'),
    array('percentage' => 100, 'label' => '¡Listo!', 'emoji' => '🌟')
), true);
?>
-->
