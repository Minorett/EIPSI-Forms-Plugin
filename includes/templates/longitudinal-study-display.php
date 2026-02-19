<?php
/**
 * Template: Longitudinal Study Display
 *
 * Displays a longitudinal study configuration including:
 * - Study name and description
 * - Principal investigator
 * - Waves with forms and time limits
 * - Shareable link options
 * - Participant-friendly welcome section
 *
 * @package EIPSI_Forms
 * @since 1.5.0
 * @since 1.6.0 - Enhanced participant experience
 *
 * Variables available:
 * - $study: Study object from database
 * - $waves: Array of waves
 * - $participant_count: Number of participants
 * - $study_config: Decoded JSON configuration
 * - $pi_name: Principal investigator name
 * - $shareable_url: URL with study parameters
 * - $shortcode_string: The shortcode syntax
 * - $show_config: Whether to show configuration details
 * - $show_waves: Whether to show waves list
 * - $theme: Theme style (default, compact, card)
 * - $time_limit_override: Override time limit if set
 * - $view_mode: View mode (dashboard, participant, public)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if participant is logged in
$is_participant_logged_in = function_exists('EIPSI_Auth_Service') && EIPSI_Auth_Service::is_authenticated();
$current_participant_id = $is_participant_logged_in ? EIPSI_Auth_Service::get_current_participant() : 0;

// Get participant's next wave if logged in
$next_wave = null;
$participant_progress = 0;
$total_waves = count($waves);

if ($is_participant_logged_in && $current_participant_id && $show_waves) {
    global $wpdb;

    // Get this participant's assignments
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT a.wave_id, a.status
         FROM {$wpdb->prefix}survey_assignments a
         INNER JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
         WHERE a.participant_id = %d AND w.study_id = %d
         ORDER BY w.wave_index ASC",
        $current_participant_id,
        $study->id
    ));

    // Map wave_id to status
    $wave_status = array();
    foreach ($assignments as $assignment) {
        $wave_status[$assignment->wave_id] = $assignment->status;
    }

    // Find next pending wave
    foreach ($waves as $wave) {
        if (!isset($wave_status[$wave['id']]) || $wave_status[$wave['id']] !== 'submitted') {
            $next_wave = $wave;
            break;
        }
    }

    // Calculate progress
    $completed_waves = 0;
    foreach ($wave_status as $status) {
        if ($status === 'submitted') {
            $completed_waves++;
        }
    }
    $participant_progress = $total_waves > 0 ? round(($completed_waves / $total_waves) * 100) : 0;
}

// Determine CSS classes based on theme
$container_class = 'eipsi-longitudinal-study eipsi-theme-' . esc_attr($theme);
$status_class = 'status-' . esc_attr($study->status);
$view_class = 'view-' . esc_attr($view_mode);
?>

<div class="<?php echo esc_attr($container_class); ?> <?php echo esc_attr($view_class); ?>" data-study-id="<?php echo esc_attr($study->id); ?>" data-study-code="<?php echo esc_attr($study->study_code); ?>">

    <!-- Participant Welcome Section (only for participant view) -->
    <?php if ($view_mode === 'participant' || $view_mode === 'public'): ?>
        <?php if ($is_participant_logged_in): ?>
            <div class="eipsi-participant-welcome">
                <div class="welcome-header">
                    <h3 class="welcome-title">👋 ¡Hola de nuevo!</h3>
                    <p class="welcome-subtitle">Tu progreso en este estudio</p>
                </div>
                <div class="progress-overview">
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_attr($participant_progress); ?>%;"></div>
                        </div>
                        <span class="progress-text"><?php echo esc_html($participant_progress); ?>% completado</span>
                    </div>
                    <div class="progress-stats">
                        <span class="stat-item">
                            <strong><?php echo esc_html($participant_progress / 100 * $total_waves); ?></strong>
                            de <?php echo esc_html($total_waves); ?> tomas
                        </span>
                    </div>
                </div>

                <?php if ($next_wave): ?>
                    <div class="next-action">
                        <h4 class="next-action-title">📝 Tu próxima toma</h4>
                        <div class="next-action-card">
                            <div class="wave-info">
                                <span class="wave-badge">T<?php echo esc_html($next_wave['wave_index']); ?></span>
                                <strong class="wave-name"><?php echo esc_html($next_wave['name']); ?></strong>
                            </div>
                            <form action="" method="get">
                                <input type="hidden" name="form_id" value="<?php echo esc_attr($next_wave['form_id']); ?>">
                                <button type="submit" class="button button-primary button-large">
                                    Comenzar toma →
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="completion-message">
                        <span class="completion-icon">🎉</span>
                        <h4 class="completion-title">¡Felicidades!</h4>
                        <p class="completion-text">Has completado todas las tomas de este estudio. ¡Gracias por tu participación!</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="eipsi-study-hero">
                <h2 class="hero-title">📊 <?php echo esc_html($study->study_name); ?></h2>
                <p class="hero-subtitle">Ayudá a la ciencia clínica completando este estudio</p>
                <div class="hero-actions">
                    <a href="#login-section" class="button button-primary button-large">Iniciar Sesión</a>
                    <a href="#study-info" class="button button-secondary button-large">Más Información</a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Study Header (for dashboard view or if not participant welcome shown) -->
    <?php if ($view_mode === 'dashboard' || ($view_mode !== 'participant' && $view_mode !== 'public')): ?>
        <div class="eipsi-study-header">
            <div class="eipsi-study-title-section">
                <span class="eipsi-study-badge <?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html(ucfirst($study->status)); ?>
                </span>
                <h2 class="eipsi-study-name"><?php echo esc_html($study->study_name); ?></h2>
                <?php if (!empty($study->study_code)): ?>
                    <span class="eipsi-study-code"><?php echo esc_html($study->study_code); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($study->description)): ?>
                <div class="eipsi-study-description">
                    <?php echo wp_kses_post(wpautop($study->description)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Study Description (for participant view) -->
    <?php if (($view_mode === 'participant' || $view_mode === 'public') && !empty($study->description)): ?>
        <div class="eipsi-study-description-section" id="study-info">
            <h3 class="section-title">📋 Sobre este estudio</h3>
            <div class="description-content">
                <?php echo wp_kses_post(wpautop($study->description)); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Study Configuration Summary -->
    <?php if ($show_config): ?>
    <div class="eipsi-study-config">
        <h3 class="eipsi-section-title">📊 <?php esc_html_e('Información del Estudio', 'eipsi-forms'); ?></h3>
        
        <div class="eipsi-config-grid">
            <div class="eipsi-config-item">
                <span class="config-label">👥 <?php esc_html_e('Participantes:', 'eipsi-forms'); ?></span>
                <span class="config-value"><?php echo number_format_i18n($participant_count); ?></span>
            </div>
            
            <div class="eipsi-config-item">
                <span class="config-label">🌊 <?php esc_html_e('Ondas:', 'eipsi-forms'); ?></span>
                <span class="config-value"><?php echo number_format_i18n(count($waves)); ?></span>
            </div>
            
            <?php if (!empty($pi_name)): ?>
            <div class="eipsi-config-item">
                <span class="config-label">🔬 <?php esc_html_e('Investigador Principal:', 'eipsi-forms'); ?></span>
                <span class="config-value"><?php echo esc_html($pi_name); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="eipsi-config-item">
                <span class="config-label">📅 <?php esc_html_e('Creado:', 'eipsi-forms'); ?></span>
                <span class="config-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($study->created_at))); ?></span>
            </div>
            
            <?php if (!empty($study_config) && isset($study_config['randomization_enabled'])): ?>
            <div class="eipsi-config-item">
                <span class="config-label">🎲 <?php esc_html_e('Aleatorización:', 'eipsi-forms'); ?></span>
                <span class="config-value"><?php echo $study_config['randomization_enabled'] ? esc_html__('Activada', 'eipsi-forms') : esc_html__('No activada', 'eipsi-forms'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($study_config) && isset($study_config['reminders_enabled'])): ?>
            <div class="eipsi-config-item">
                <span class="config-label">⏰ <?php esc_html_e('Recordatorios:', 'eipsi-forms'); ?></span>
                <span class="config-value"><?php echo $study_config['reminders_enabled'] ? esc_html__('Activados', 'eipsi-forms') : esc_html__('No activados', 'eipsi-forms'); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Waves List -->
    <?php if ($show_waves && !empty($waves)): ?>
    <div class="eipsi-waves-section">
        <h3 class="eipsi-section-title">🌊 <?php esc_html_e('Tomas (Waves)', 'eipsi-forms'); ?></h3>
        
        <div class="eipsi-waves-list">
            <?php foreach ($waves as $wave): 
                $wave_status_class = 'wave-status-' . esc_attr($wave['status']);
                $form_title = eipsi_get_form_title($wave['form_id']);
                
                // Calculate time limit
                $effective_time_limit = $time_limit_override > 0 ? $time_limit_override : ($wave['completion_time_limit'] ?? 0);
                $time_display = eipsi_format_time_limit($effective_time_limit);
            ?>
            <div class="eipsi-wave-card <?php echo esc_attr($wave_status_class); ?>" data-wave-id="<?php echo esc_attr($wave['id']); ?>">
                <div class="wave-header">
                    <span class="wave-index">T<?php echo esc_html($wave['wave_index']); ?></span>
                    <span class="wave-status-badge <?php echo esc_attr($wave_status_class); ?>">
                        <?php echo esc_html(eipsi_get_wave_status_label($wave['status'])); ?>
                    </span>
                </div>
                
                <div class="wave-content">
                    <h4 class="wave-name"><?php echo esc_html($wave['name']); ?></h4>
                    
                    <?php if (!empty($wave['description'])): ?>
                        <p class="wave-description"><?php echo esc_html($wave['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="wave-details">
                        <div class="wave-detail">
                            <span class="detail-icon">📋</span>
                            <span class="detail-label"><?php esc_html_e('Formulario:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($form_title); ?></span>
                        </div>
                        
                        <div class="wave-detail">
                            <span class="detail-icon">⏱️</span>
                            <span class="detail-label"><?php esc_html_e('Tiempo Límite:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html($time_display); ?></span>
                        </div>
                        
                        <?php if (!empty($wave['due_date'])): ?>
                        <div class="wave-detail">
                            <span class="detail-icon">📅</span>
                            <span class="detail-label"><?php esc_html_e('Vencimiento:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($wave['due_date']))); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($wave['is_mandatory'])): ?>
                        <div class="wave-detail">
                            <span class="detail-icon">⭐</span>
                            <span class="detail-label"><?php esc_html_e('Obligatoria:', 'eipsi-forms'); ?></span>
                            <span class="detail-value"><?php esc_html_e('Sí', 'eipsi-forms'); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($wave['reminder_days'])): ?>
                        <div class="wave-detail">
                            <span class="detail-icon">🔔</span>
                            <span class="detail-label"><?php esc_html_e('Recordatorio:', 'eipsi-forms'); ?></span>
                            <span class="detail-value">
                                <?php 
                                printf(
                                    esc_html(_n('%d día antes', '%d días antes', $wave['reminder_days'], 'eipsi-forms')),
                                    $wave['reminder_days']
                                ); 
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($show_waves): ?>
    <div class="eipsi-waves-empty">
        <p><?php esc_html_e('No hay tomas (waves) configuradas para este estudio.', 'eipsi-forms'); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Share Section -->
    <div class="eipsi-share-section">
        <h3 class="eipsi-section-title">🔗 <?php esc_html_e('Compartir Estudio', 'eipsi-forms'); ?></h3>

        <div class="eipsi-share-options">
            <!-- SECURE SHORTCODE -->
            <div class="eipsi-share-option secure-shortcode">
                <label>
                    <span class="label-icon">🔒</span>
                    <?php esc_html_e('Shortcode Seguro:', 'eipsi-forms'); ?>
                    <span class="badge-recommended"><?php esc_html_e('Recomendado', 'eipsi-forms'); ?></span>
                </label>
                <div class="eipsi-copy-field">
                    <code class="eipsi-shortcode-display"><?php echo esc_html($shortcode_string); ?></code>
                    <button type="button" class="eipsi-copy-btn" data-copy="<?php echo esc_attr($shortcode_string); ?>" title="<?php esc_attr_e('Copiar shortcode seguro', 'eipsi-forms'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="copy-text"><?php esc_html_e('Copiar', 'eipsi-forms'); ?></span>
                    </button>
                </div>
                <small class="eipsi-help-text">
                    <?php esc_html_e('Usa study_code para mayor seguridad. Evita usar IDs numéricos.', 'eipsi-forms'); ?>
                </small>
            </div>

            <!-- Shareable URL -->
            <div class="eipsi-share-option">
                <label>
                    <span class="label-icon">🔗</span>
                    <?php esc_html_e('Enlace Directo:', 'eipsi-forms'); ?>
                </label>
                <div class="eipsi-copy-field">
                    <input type="text" class="eipsi-url-display" value="<?php echo esc_url($shareable_url); ?>" readonly>
                    <button type="button" class="eipsi-copy-btn" data-copy="<?php echo esc_url($shareable_url); ?>" title="<?php esc_attr_e('Copiar enlace', 'eipsi-forms'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="copy-text"><?php esc_html_e('Copiar', 'eipsi-forms'); ?></span>
                    </button>
                </div>
                <small class="eipsi-help-text">
                    <?php esc_html_e('Comparte este enlace para acceder directamente al estudio.', 'eipsi-forms'); ?>
                </small>
            </div>
        </div>

        <!-- Magic Link Integration -->
        <div class="eipsi-magic-link-info">
            <h4 class="magic-link-title">
                <span class="dashicons dashicons-email-alt"></span>
                <?php esc_html_e('Invitar Participantes con Magic Links', 'eipsi-forms'); ?>
            </h4>
            <p class="magic-link-description">
                <?php esc_html_e('Los Magic Links permiten a los participantes acceder al estudio con un solo clic, sin necesidad de recordar contraseñas.', 'eipsi-forms'); ?>
            </p>
            <div class="magic-link-features">
                <ul>
                    <li>✅ <?php esc_html_e('Acceso seguro con tokens únicos', 'eipsi-forms'); ?></li>
                    <li>✅ <?php esc_html_e('Válido por 7 días desde su generación', 'eipsi-forms'); ?></li>
                    <li>✅ <?php esc_html_e('Revocable en cualquier momento', 'eipsi-forms'); ?></li>
                    <li>✅ <?php esc_html_e('Ideal para estudios longitudinales', 'eipsi-forms'); ?></li>
                </ul>
            </div>
            <div class="magic-link-actions">
                <a href="<?php echo admin_url('admin.php?page=eipsi-longitudinal-study&tab=dashboard-study'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Ir al Panel de Administración', 'eipsi-forms'); ?>
                </a>
                <a href="https://docs.eipsi-forms.com/magic-links" target="_blank" class="button button-secondary">
                    <span class="dashicons dashicons-external"></span>
                    <?php esc_html_e('Ver Documentación', 'eipsi-forms'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Copy Feedback -->
    <div class="eipsi-copy-feedback" style="display: none;" role="status" aria-live="polite">
        <span class="dashicons dashicons-yes"></span>
        <span class="feedback-text"></span>
    </div>
    
</div>