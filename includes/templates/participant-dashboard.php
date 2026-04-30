<?php
/**
 * Template: Participant Dashboard
 *
 * Fase 4 - Refactored to use EIPSI_Participant_Dashboard_Data
 * and partials: hero-card.php, timeline-history.php
 *
 * @package EIPSI_Forms
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del participante
$participant_id = $participant['id'] ?? 0;
$participant_name = $participant['first_name'] ?? ($participant['email'] ?? '');
$survey_id = $participant['survey_id'] ?? 0;

// Obtener URL del investigador
$investigator_email = get_option('eipsi_investigator_email', '');

// Fase 4: Obtener datos del timeline via clase de datos
$dashboard_data = EIPSI_Participant_Dashboard_Data::get_timeline_data($participant_id, $survey_id);
$timeline        = $dashboard_data['timeline'];
$active_wave     = $dashboard_data['active_wave'];
$study_completed = $dashboard_data['study_completed'];
?>

<div class="eipsi-participant-dashboard">

    <!-- Header de bienvenida con dropdown de abandono -->
    <div class="eipsi-dash-header">
        <p class="eipsi-dash-greeting">
            <?php 
            printf(
                /* translators: %s: Participant first name */
                esc_html__('Hola de nuevo, %s', 'eipsi-forms'),
                esc_html($participant_name)
            );
            ?>
        </p>
        
        <?php 
        // Fase 3 - v2.5: Dropdown de abandono SOLO si:
        // (1) Hay participante logueado ($participant_id > 0)
        // (2) Es estudio longitudinal (tiene waves en el timeline)
        if ($participant_id > 0 && !empty($timeline)) : 
        ?>
        <div class="eipsi-header-dropdown">
            <button type="button" class="eipsi-dropdown-trigger" id="eipsi-withdraw-dropdown-trigger"
                    data-participant-id="<?php echo esc_attr($participant_id); ?>"
                    data-study-id="<?php echo esc_attr($survey_id); ?>"
                    aria-haspopup="true"
                    aria-expanded="false"
                    title="<?php esc_attr_e('Opciones del estudio', 'eipsi-forms'); ?>">
                <span class="dropdown-icon">⚙️</span>
                <span class="dropdown-chevron">▼</span>
            </button>
            <div class="eipsi-dropdown-menu" id="eipsi-withdraw-dropdown-menu" role="menu" aria-hidden="true">
                <button type="button" class="eipsi-dropdown-item" id="eipsi-withdraw-button" role="menuitem">
                    <span class="item-icon">🚪</span>
                    <span class="item-text"><?php esc_html_e('Abandonar estudio', 'eipsi-forms'); ?></span>
                </button>
            </div>
        </div>
        <?php endif; ?>
    <!-- Fase 4: Hero Card con countdown y CTA (partial) -->
    <?php include __DIR__ . '/dashboard/hero-card.php'; ?>

    <!-- Fase 4: Timeline histórico visual (partial) -->
    <?php include __DIR__ . '/dashboard/timeline-history.php'; ?>

    <!-- Sección de contacto -->
    <div class="eipsi-dash-contact">
        <p><?php esc_html_e('Si tenés alguna pregunta o problema con el estudio, no dudes en contactar al investigador.', 'eipsi-forms'); ?></p>
        <?php if ($investigator_email): ?>
            <a href="mailto:<?php echo esc_attr($investigator_email); ?>?subject=<?php echo urlencode(__('Consulta sobre el estudio', 'eipsi-forms')); ?>" 
               class="eipsi-contact-link">
                <span class="contact-icon">📧</span>
                <?php echo esc_html($investigator_email); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Pie simplificado - Fase 3 v2.5: Sin logout (inservible), abandono movido a header -->
    <div class="eipsi-dash-footer">
        <div class="eipsi-footer-info">
            <span class="security-badge">🔒 <?php esc_html_e('Conexión segura', 'eipsi-forms'); ?></span>
        </div>
    </div>
</div>

<!-- Fase 3 - v2.5: Modales de Abandono -->
<?php include __DIR__ . '/withdrawal-modals.php'; ?>
