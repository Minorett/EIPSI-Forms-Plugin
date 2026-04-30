<?php
/**
 * Partial: Hero Card for Participant Dashboard
 * 
 * Fase 4 - Dashboard del Participante
 * Muestra la wave activa con countdown y CTA
 *
 * Variables esperadas:
 * - $active_wave: Array con datos de la wave activa (o null)
 * - $study_completed: Boolean indicando si el estudio está completado
 * - $survey_id: ID del estudio
 * 
 * @package EIPSI_Forms
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si el estudio está completado, mostrar estado final
if ($study_completed) : ?>
    
    <!-- CORRECCIÓN 3: estado cuando no hay wave activa -->
    <div class="eipsi-hero-card" data-status="completed">
        <div class="hero-status-badge status-completed">
            <?php esc_html_e('Estudio completado', 'eipsi-forms'); ?>
        </div>
        <p class="hero-completed-message">
            <?php esc_html_e('Has finalizado todas las tomas de este estudio. Gracias por tu participación.', 'eipsi-forms'); ?>
        </p>
    </div>

<?php elseif ($active_wave) : 
    
    // Calcular timestamp objetivo y tipo de countdown
    $countdown_type = $active_wave['status'] === 'available' ? 'until-expires' : 'until-available';
    $target_timestamp = $active_wave['status'] === 'available' 
        ? ($active_wave['due_at'] ? strtotime($active_wave['due_at']) : 0)
        : strtotime($active_wave['available_at']);
    
    // Generar URL del formulario
    $form_url = eipsi_get_wave_form_url($active_wave['wave_id'], $survey_id);
    ?>
    
    <div class="eipsi-hero-card"
         data-status="<?php echo esc_attr($active_wave['status']); ?>"
         data-target-timestamp="<?php echo esc_attr($target_timestamp); ?>"
         data-countdown-type="<?php echo esc_attr($countdown_type); ?>">
        
        <div class="hero-status-badge status-<?php echo esc_attr($active_wave['status']); ?>">
            <?php echo esc_html(EIPSI_Participant_Dashboard_Data::get_status_label($active_wave['status'])); ?>
        </div>
        
        <h2 class="hero-wave-title">
            <?php echo esc_html($active_wave['title']); ?>
        </h2>
        
        <div class="hero-countdown">
            <?php if ($active_wave['status'] === 'available' && $active_wave['due_at']) : ?>
                <!-- Countdown hasta expiración -->
                <span class="countdown-label"><?php esc_html_e('Expira en:', 'eipsi-forms'); ?></span>
                <div class="countdown-timer">
                    <div class="countdown-unit">
                        <span class="countdown-value" data-unit="days">--</span>
                        <span class="countdown-label"><?php esc_html_e('días', 'eipsi-forms'); ?></span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value" data-unit="hours">--</span>
                        <span class="countdown-label"><?php esc_html_e('horas', 'eipsi-forms'); ?></span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value" data-unit="minutes">--</span>
                        <span class="countdown-label"><?php esc_html_e('min', 'eipsi-forms'); ?></span>
                    </div>
                </div>
                
            <?php elseif ($active_wave['status'] === 'available') : ?>
                <!-- Última wave sin cierre: no mostrar countdown, solo CTA -->
                
            <?php elseif ($active_wave['status'] === 'pending') : ?>
                <!-- Countdown hasta apertura -->
                <span class="countdown-label"><?php esc_html_e('Se abre en:', 'eipsi-forms'); ?></span>
                <div class="countdown-timer">
                    <div class="countdown-unit">
                        <span class="countdown-value" data-unit="days">--</span>
                        <span class="countdown-label"><?php esc_html_e('días', 'eipsi-forms'); ?></span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value" data-unit="hours">--</span>
                        <span class="countdown-label"><?php esc_html_e('horas', 'eipsi-forms'); ?></span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value" data-unit="minutes">--</span>
                        <span class="countdown-label"><?php esc_html_e('min', 'eipsi-forms'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($active_wave['status'] === 'available') : ?>
            <a href="<?php echo esc_url($form_url); ?>" class="hero-cta-button">
                <?php esc_html_e('Completar ahora', 'eipsi-forms'); ?>
            </a>
        <?php endif; ?>
    </div>

<?php endif; ?>
