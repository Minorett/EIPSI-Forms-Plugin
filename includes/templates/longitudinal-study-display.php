<?php
/**
 * Template: Longitudinal Study Display
 * 
 * Displays a longitudinal study configuration including:
 * - Study name and description
 * - Principal investigator
 * - Waves with forms and time limits
 * - Shareable link options
 * 
 * @package EIPSI_Forms
 * @since 1.5.0
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
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determine CSS classes based on theme
$container_class = 'eipsi-longitudinal-study eipsi-theme-' . esc_attr($theme);
$status_class = 'status-' . esc_attr($study->status);
?>

<div class="<?php echo esc_attr($container_class); ?>" data-study-id="<?php echo esc_attr($study->id); ?>">
    
    <!-- Study Header -->
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
            <!-- Shortcode Copy -->
            <div class="eipsi-share-option">
                <label><?php esc_html_e('Shortcode:', 'eipsi-forms'); ?></label>
                <div class="eipsi-copy-field">
                    <code class="eipsi-shortcode-display"><?php echo esc_html($shortcode_string); ?></code>
                    <button type="button" class="eipsi-copy-btn" data-copy="<?php echo esc_attr($shortcode_string); ?>" title="<?php esc_attr_e('Copiar shortcode', 'eipsi-forms'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="copy-text"><?php esc_html_e('Copiar', 'eipsi-forms'); ?></span>
                    </button>
                </div>
                <small class="eipsi-help-text"><?php esc_html_e('Copia este shortcode y pégalo en cualquier página o post.', 'eipsi-forms'); ?></small>
            </div>
            
            <!-- Shareable URL -->
            <div class="eipsi-share-option">
                <label><?php esc_html_e('Enlace Directo:', 'eipsi-forms'); ?></label>
                <div class="eipsi-copy-field">
                    <input type="text" class="eipsi-url-display" value="<?php echo esc_url($shareable_url); ?>" readonly>
                    <button type="button" class="eipsi-copy-btn" data-copy="<?php echo esc_url($shareable_url); ?>" title="<?php esc_attr_e('Copiar enlace', 'eipsi-forms'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="copy-text"><?php esc_html_e('Copiar', 'eipsi-forms'); ?></span>
                    </button>
                </div>
                <small class="eipsi-help-text"><?php esc_html_e('Comparte este enlace para acceder directamente al estudio.', 'eipsi-forms'); ?></small>
            </div>
        </div>
        
        <!-- Magic Link Info -->
        <div class="eipsi-magic-link-info">
            <p>
                <span class="dashicons dashicons-email-alt"></span>
                <?php esc_html_e('¿Necesitas invitar participantes? Usa los Magic Links desde el panel de administración del estudio.', 'eipsi-forms'); ?>
            </p>
        </div>
    </div>
    
    <!-- Copy Feedback -->
    <div class="eipsi-copy-feedback" style="display: none;" role="status" aria-live="polite">
        <span class="dashicons dashicons-yes"></span>
        <span class="feedback-text"></span>
    </div>
    
</div>