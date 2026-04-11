<?php
/**
 * Template: Participant Dashboard (Enhanced v1.6.0)
 *
 * Mejoras:
 * - Progress visualization
 * - Better wave status display
 * - Time tracking
 * - Clear CTAs
 * - Mobile responsive
 * - Loading states
 *
 * @package EIPSI_Forms
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos del participante
$participant_id = $participant['id'] ?? 0;
$participant_name = $participant['first_name'] ?? ($participant['email'] ?? '');
$survey_id = $participant['survey_id'] ?? 0;

// Obtener próxima wave
$next_wave = $next_wave ?? null;

// Obtener historial de waves
$all_waves = $all_waves ?? array();

// Calcular estadísticas de progreso
$total_waves = count($all_waves);
$completed_waves = 0;
$pending_waves = 0;

foreach ($all_waves as $wave) {
    if (isset($wave['assignment']) && $wave['assignment']['status'] === 'submitted') {
        $completed_waves++;
    } else {
        $pending_waves++;
    }
}

$progress_percentage = $total_waves > 0 ? round(($completed_waves / $total_waves) * 100) : 0;

// Helper para formatear tiempo
function eipsi_format_duration($seconds) {
    if (empty($seconds) || $seconds < 1) {
        return '-';
    }

    $minutes = floor($seconds / 60);
    if ($minutes < 1) {
        return '< 1 min';
    } elseif ($minutes < 60) {
        return sprintf('%d min', $minutes);
    } else {
        $hours = floor($minutes / 60);
        $remaining_minutes = $minutes % 60;
        return sprintf('%dh %dmin', $hours, $remaining_minutes);
    }
}

// Helper para formato de fecha
function eipsi_format_date($date_str, $default = '-') {
    if (empty($date_str) || $date_str === '0000-00-00 00:00:00') {
        return $default;
    }

    $timestamp = strtotime($date_str);
    if (!$timestamp) {
        return $default;
    }

    $today = strtotime('today');
    $diff_days = floor(($today - $timestamp) / DAY_IN_SECONDS);

    if ($diff_days === 0) {
        return __('Hoy', 'eipsi-forms') . ' ' . date('H:i', $timestamp);
    } elseif ($diff_days === 1) {
        return __('Ayer', 'eipsi-forms');
    } elseif ($diff_days < 7) {
        return sprintf(__('%d días atrás', 'eipsi-forms'), $diff_days);
    } else {
        return date('d/m/Y', $timestamp);
    }
}

// Helper para calcular días restantes
function eipsi_calculate_due_status($due_date) {
    if (empty($due_date)) {
        return array('text' => '-', 'class' => '', 'urgency' => 'none');
    }

    $now = current_time('timestamp');
    $due_timestamp = strtotime($due_date);

    if (!$due_timestamp) {
        return array('text' => '-', 'class' => '', 'urgency' => 'none');
    }

    $days_diff = ceil(($due_timestamp - $now) / DAY_IN_SECONDS);

    if ($days_diff < 0) {
        return array(
            'text' => __('VENCIDA', 'eipsi-forms'),
            'class' => 'status-expired',
            'urgency' => 'high'
        );
    } elseif ($days_diff === 0) {
        return array(
            'text' => __('¡HOY!', 'eipsi-forms'),
            'class' => 'status-today',
            'urgency' => 'high'
        );
    } elseif ($days_diff === 1) {
        return array(
            'text' => __('Mañana', 'eipsi-forms'),
            'class' => 'status-soon',
            'urgency' => 'medium'
        );
    } elseif ($days_diff <= 3) {
        return array(
            'text' => sprintf(__('En %d días', 'eipsi-forms'), $days_diff),
            'class' => 'status-soon',
            'urgency' => 'medium'
        );
    } else {
        return array(
            'text' => sprintf(__('En %d días', 'eipsi-forms'), $days_diff),
            'class' => 'status-future',
            'urgency' => 'low'
        );
    }
}

// Obtener URL del investigador
$investigator_email = get_option('eipsi_investigator_email', '');
?>

<div class="eipsi-participant-dashboard">
    <!-- Sección 1: Bienvenida y Progreso -->
    <div class="eipsi-dashboard-header">
        <div class="eipsi-welcome-section">
            <h1 class="eipsi-dashboard-title">
                <span class="welcome-emoji">👋</span>
                <?php printf(__('¡Hola, %s!', 'eipsi-forms'), esc_html($participant_name)); ?>
            </h1>
            <p class="eipsi-dashboard-subtitle">
                <?php esc_html_e('Este es tu progreso en el estudio', 'eipsi-forms'); ?>
            </p>
        </div>
        
        <!-- Progress Bar -->
        <?php if ($total_waves > 0): ?>
        <div class="eipsi-progress-section">
            <div class="eipsi-progress-header">
                <span class="eipsi-progress-label"><?php esc_html_e('Progreso del estudio', 'eipsi-forms'); ?></span>
                <span class="eipsi-progress-percentage"><?php echo esc_html($progress_percentage); ?>%</span>
            </div>
            <div class="eipsi-progress-bar">
                <div class="eipsi-progress-fill" style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
            </div>
            <div class="eipsi-progress-stats">
                <span class="stat-completed">✅ <?php printf(__('%d completadas', 'eipsi-forms'), $completed_waves); ?></span>
                <span class="stat-pending">⏳ <?php printf(__('%d pendientes', 'eipsi-forms'), $pending_waves); ?></span>
                <span class="stat-total">📊 <?php printf(__('%d total', 'eipsi-forms'), $total_waves); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sección 2: Próxima Toma (CTA Principal) -->
    <div class="eipsi-dashboard-section eipsi-section-highlight">
        <h2 class="eipsi-section-title">
            <span class="section-icon">📋</span>
            <?php esc_html_e('Tu Próxima Toma', 'eipsi-forms'); ?>
        </h2>
        
        <?php if ($next_wave): ?>
            <?php
            $due_status = eipsi_calculate_due_status($next_wave['due_date']);
            $form_title = get_the_title($next_wave['form_id']) ?: sprintf(__('Toma %d', 'eipsi-forms'), $next_wave['wave_index']);
            $estimated_time = !empty($next_wave['estimated_time']) ? $next_wave['estimated_time'] : '10-15';
            ?>
            
            <div class="eipsi-next-take-card <?php echo esc_attr($due_status['urgency'] === 'high' ? 'urgent' : ''); ?>">
                <div class="eipsi-card-header">
                    <div class="wave-badge">
                        <span class="eipsi-wave-index">T<?php echo absint($next_wave['wave_index']); ?></span>
                    </div>
                    <div class="wave-info">
                        <h3 class="eipsi-form-title"><?php echo esc_html($form_title); ?></h3>
                        <span class="wave-estimated-time">⏱️ <?php printf(__('%s minutos', 'eipsi-forms'), esc_html($estimated_time)); ?></span>
                    </div>
                </div>
                
                <div class="eipsi-card-body">
                    <div class="eipsi-due-date <?php echo esc_attr($due_status['class']); ?>">
                        <span class="due-icon">
                            <?php 
                            if ($due_status['urgency'] === 'high') echo '⏰';
                            elseif ($due_status['urgency'] === 'medium') echo '⚡';
                            else echo '📅';
                            ?>
                        </span>
                        <div class="due-info">
                            <span class="eipsi-due-label"><?php esc_html_e('Fecha límite:', 'eipsi-forms'); ?></span>
                            <span class="eipsi-due-value"><?php echo esc_html($due_status['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="eipsi-take-actions">
                        <a href="<?php echo esc_url(add_query_arg(array('wave_id' => $next_wave['id'], 'survey_id' => $survey_id), home_url('/estudio/'))); ?>"
                           class="eipsi-button-primary eipsi-respond-now">
                            <span class="btn-icon">▶️</span>
                            <?php esc_html_e('Responder ahora', 'eipsi-forms'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="eipsi-next-take-card eipsi-no-next-take eipsi-completion-card">
                <div class="completion-icon">🎉</div>
                <div class="eipsi-card-body">
                    <h3 class="completion-title"><?php esc_html_e('¡Estudio completado!', 'eipsi-forms'); ?></h3>
                    <p class="eipsi-no-data">
                        <?php esc_html_e('No tenés tomas pendientes. ¡Gracias por completar todas las evaluaciones!', 'eipsi-forms'); ?>
                    </p>
                    <?php if ($total_waves > 0): ?>
                    <div class="completion-stats">
                        <span class="completion-badge">✅ <?php printf(__('%d de %d completadas', 'eipsi-forms'), $completed_waves, $total_waves); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sección 3: Historial de Tomas -->
    <div class="eipsi-dashboard-section">
        <h2 class="eipsi-section-title">
            <span class="section-icon">📊</span>
            <?php esc_html_e('Historial de Tomas', 'eipsi-forms'); ?>
        </h2>
        
        <?php if (!empty($all_waves)): ?>
            <div class="eipsi-waves-table-wrapper">
                <table class="eipsi-waves-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Toma', 'eipsi-forms'); ?></th>
                            <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                            <th><?php esc_html_e('Fecha límite', 'eipsi-forms'); ?></th>
                            <th><?php esc_html_e('Respondida', 'eipsi-forms'); ?></th>
                            <th><?php esc_html_e('Tiempo', 'eipsi-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_waves as $wave): ?>
                            <?php
                            $assignment = $wave['assignment'] ?? null;
                            $status = 'not_started';
                            $status_text = esc_html__('No iniciada', 'eipsi-forms');
                            $status_class = 'status-not-started';
                            $status_icon = '⚪';
                            
                            if ($assignment) {
                                switch ($assignment['status']) {
                                    case 'submitted':
                                        $status = 'completed';
                                        $status_text = esc_html__('Completada', 'eipsi-forms');
                                        $status_class = 'status-completed';
                                        $status_icon = '✅';
                                        break;
                                    case 'in_progress':
                                        $status = 'in_progress';
                                        $status_text = esc_html__('En progreso', 'eipsi-forms');
                                        $status_class = 'status-in-progress';
                                        $status_icon = '⏳';
                                        break;
                                    case 'pending':
                                        $status = 'pending';
                                        $status_text = esc_html__('Pendiente', 'eipsi-forms');
                                        $status_class = 'status-pending';
                                        $status_icon = '⏰';
                                        break;
                                }
                            }
                            
                            $form_title = get_the_title($wave['form_id']) ?: sprintf(__('Toma %d', 'eipsi-forms'), $wave['wave_index']);
                            $submitted_at = $assignment['submitted_at'] ?? null;
                            $duration_seconds = $assignment['submission_duration'] ?? null;
                            $due_status = eipsi_calculate_due_status($wave['due_date']);
                            ?>
                            
                            <tr class="<?php echo esc_attr($status_class); ?>">
                                <td class="eipsi-wave-cell">
                                    <div class="wave-info-compact">
                                        <span class="eipsi-wave-index">T<?php echo absint($wave['wave_index']); ?></span>
                                        <span class="eipsi-wave-name"><?php echo esc_html($form_title); ?></span>
                                    </div>
                                </td>
                                <td class="eipsi-status-cell">
                                    <span class="eipsi-status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($status_text); ?>">
                                        <span class="status-icon"><?php echo esc_html($status_icon); ?></span>
                                        <span class="status-text"><?php echo esc_html($status_text); ?></span>
                                    </span>
                                </td>
                                <td class="eipsi-due-cell <?php echo esc_attr($due_status['class']); ?>">
                                    <?php echo esc_html($due_status['text']); ?>
                                </td>
                                <td class="eipsi-submitted-cell">
                                    <?php echo esc_html(eipsi_format_date($submitted_at, '-')); ?>
                                </td>
                                <td class="eipsi-duration-cell">
                                    <?php echo esc_html(eipsi_format_duration($duration_seconds)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="eipsi-no-data eipsi-empty-state">
                <div class="empty-icon">📭</div>
                <p><?php esc_html_e('No hay tomas asignadas aún.', 'eipsi-forms'); ?></p>
                <p class="empty-hint"><?php esc_html_e('El investigador las configurará pronto.', 'eipsi-forms'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sección 4: Información de Contacto -->
    <div class="eipsi-dashboard-section eipsi-contact-section">
        <h2 class="eipsi-section-title">
            <span class="section-icon">💬</span>
            <?php esc_html_e('¿Necesitás ayuda?', 'eipsi-forms'); ?>
        </h2>
        <div class="eipsi-contact-info">
            <p><?php esc_html_e('Si tenés alguna pregunta o problema con el estudio, no dudes en contactar al investigador.', 'eipsi-forms'); ?></p>
            <?php if ($investigator_email): ?>
                <a href="mailto:<?php echo esc_attr($investigator_email); ?>?subject=<?php echo urlencode(__('Consulta sobre el estudio', 'eipsi-forms')); ?>" 
                   class="eipsi-contact-link">
                    <span class="contact-icon">📧</span>
                    <?php echo esc_html($investigator_email); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sección 5: Pie -->
    <div class="eipsi-dashboard-footer">
        <div class="eipsi-footer-actions">
            <button type="button" class="eipsi-button-logout" id="eipsi-logout-button" data-nonce="<?php echo wp_create_nonce('eipsi_participant_logout'); ?>">
                <span class="btn-icon">🚪</span>
                <?php esc_html_e('Cerrar sesión', 'eipsi-forms'); ?>
            </button>
        </div>
        <div class="eipsi-footer-info">
            <span class="security-badge">🔒 <?php esc_html_e('Conexión segura', 'eipsi-forms'); ?></span>
        </div>
    </div>
</div>
