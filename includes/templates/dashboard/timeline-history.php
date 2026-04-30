<?php
/**
 * Partial: Timeline History for Participant Dashboard
 * 
 * Fase 4 - Dashboard del Participante (Timeline Visual)
 * Muestra el progreso histórico del participante en el estudio.
 *
 * Variables esperadas:
 * - $timeline: Array de waves con datos del timeline
 * 
 * NOTA: Los íconos se renderizan vía CSS background-image con SVG encoded.
 * CORRECCIÓN 2: No se usan caracteres hardcodeados (✅, ✗, etc.) ni en PHP ni en CSS content.
 * Los colores del SVG usan variables CSS para permitir cambios dinámicos.
 *
 * @package EIPSI_Forms
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($timeline)) {
    return;
}
?>

<div class="eipsi-timeline-history">
    <h3><?php esc_html_e('Tu progreso en el estudio', 'eipsi-forms'); ?></h3>
    
    <ol class="timeline-list">
        <?php foreach ($timeline as $wave) : ?>
        <li class="timeline-item status-<?php echo esc_attr($wave['status']); ?>">
            
            <!-- CORRECCIÓN 2: íconos via CSS background-image SVG, no caracteres hardcodeados -->
            <div class="timeline-marker">
                <span class="timeline-icon timeline-icon--<?php echo esc_attr($wave['status']); ?>"></span>
            </div>
            
            <div class="timeline-content">
                <span class="timeline-title"><?php echo esc_html($wave['title']); ?></span>
                
                <?php if ($wave['status'] === 'submitted') : ?>
                    <span class="timeline-meta">
                        <?php echo esc_html(
                            sprintf(
                                __('Completada el %s', 'eipsi-forms'),
                                date_i18n('j M, H:i', strtotime($wave['submitted_at']))
                            )
                        ); ?>
                    </span>
                    
                <?php elseif ($wave['status'] === 'expired') : ?>
                    <span class="timeline-meta timeline-meta--expired">
                        <?php echo esc_html(
                            $wave['due_at']
                                ? sprintf(__('Expiró el %s', 'eipsi-forms'), date_i18n('j M, H:i', strtotime($wave['due_at'])))
                                : __('No completada', 'eipsi-forms')
                        ); ?>
                    </span>
                    
                <?php elseif ($wave['status'] === 'available') : ?>
                    <span class="timeline-meta">
                        <?php echo esc_html(
                            $wave['due_at']
                                ? sprintf(__('Disponible hasta %s', 'eipsi-forms'), date_i18n('j M, H:i', strtotime($wave['due_at'])))
                                : __('Disponible', 'eipsi-forms')
                        ); ?>
                    </span>
                    
                <?php else : ?>
                    <span class="timeline-meta">
                        <?php echo esc_html(
                            sprintf(
                                __('Se abre el %s', 'eipsi-forms'),
                                date_i18n('j M, H:i', strtotime($wave['available_at']))
                            )
                        ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ol>
</div>
