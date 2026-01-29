<?php
/**
 * Template: Participant Dashboard
 *
 * Muestra el progreso del participante en el estudio longitudinal.
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
        return array('text' => '-', 'class' => '');
    }
    
    $now = current_time('timestamp');
    $due_timestamp = strtotime($due_date);
    
    if (!$due_timestamp) {
        return array('text' => '-', 'class' => '');
    }
    
    $days_diff = ceil(($due_timestamp - $now) / DAY_IN_SECONDS);
    
    if ($days_diff < 0) {
        return array(
            'text' => __('VENCIDA', 'eipsi-forms'),
            'class' => 'status-expired'
        );
    } elseif ($days_diff === 0) {
        return array(
            'text' => __('HOY', 'eipsi-forms'),
            'class' => 'status-today'
        );
    } elseif ($days_diff === 1) {
        return array(
            'text' => __('Mañana', 'eipsi-forms'),
            'class' => 'status-soon'
        );
    } else {
        return array(
            'text' => sprintf(__('Vence en %d días', 'eipsi-forms'), $days_diff),
            'class' => ''
        );
    }
}
?>

<div class="eipsi-participant-dashboard">
    <!-- Sección 1: Bienvenida -->
    <div class="eipsi-dashboard-header">
        <h1 class="eipsi-dashboard-title">
            👋 <?php printf(__('¡Hola, %s!', 'eipsi-forms'), esc_html($participant_name)); ?>
        </h1>
        <p class="eipsi-dashboard-subtitle">
            <?php esc_html_e('Este es tu progreso en el estudio', 'eipsi-forms'); ?>
        </p>
    </div>

    <!-- Sección 2: Próxima Toma -->
    <div class="eipsi-dashboard-section">
        <h2 class="eipsi-section-title">
            <?php esc_html_e('📋 Tu Próxima Toma', 'eipsi-forms'); ?>
        </h2>
        
        <?php if ($next_wave): ?>
            <?php
            $due_status = eipsi_calculate_due_status($next_wave['due_date']);
            $form_title = get_the_title($next_wave['form_id']) ?: sprintf(__('Toma %d', 'eipsi-forms'), $next_wave['wave_index']);
            ?>
            
            <div class="eipsi-next-take-card">
                <div class="eipsi-card-header">
                    <span class="eipsi-wave-index">T<?php echo absint($next_wave['wave_index']); ?></span>
                    <h3 class="eipsi-form-title"><?php echo esc_html($form_title); ?></h3>
                </div>
                
                <div class="eipsi-card-body">
                    <div class="eipsi-due-date <?php echo esc_attr($due_status['class']); ?>">
                        <span class="eipsi-due-label"><?php esc_html_e('Fecha límite:', 'eipsi-forms'); ?></span>
                        <span class="eipsi-due-value"><?php echo esc_html($due_status['text']); ?></span>
                    </div>
                    
                    <div class="eipsi-take-actions">
                        <a href="<?php echo esc_url(add_query_arg(array('wave_id' => $next_wave['id'], 'survey_id' => $survey_id), home_url('/'))); ?>"
                           class="eipsi-button-primary eipsi-respond-now">
                            <?php esc_html_e('Responder ahora →', 'eipsi-forms'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="eipsi-next-take-card eipsi-no-next-take">
                <div class="eipsi-card-body">
                    <p class="eipsi-no-data">
                        <?php esc_html_e('🎉 No tienes tomas pendientes. ¡Gracias por completar el estudio!', 'eipsi-forms'); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sección 3: Historial de Tomas -->
    <div class="eipsi-dashboard-section">
        <h2 class="eipsi-section-title">
            <?php esc_html_e('📊 Historial de Tomas', 'eipsi-forms'); ?>
        </h2>
        
        <?php if (!empty($all_waves)): ?>
            <div class="eipsi-waves-table-wrapper">
                <table class="eipsi-waves-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Toma', 'eipsi-forms'); ?></th>
                            <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                            <th><?php esc_html_e('Fecha esperada', 'eipsi-forms'); ?></th>
                            <th><?php esc_html_e('Fecha respondida', 'eipsi-forms'); ?></th>
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
                            
                            if ($assignment) {
                                switch ($assignment['status']) {
                                    case 'submitted':
                                        $status = 'completed';
                                        $status_text = esc_html__('✅ Completada', 'eipsi-forms');
                                        $status_class = 'status-completed';
                                        break;
                                    case 'in_progress':
                                        $status = 'pending';
                                        $status_text = esc_html__('⏳ Pendiente', 'eipsi-forms');
                                        $status_class = 'status-pending';
                                        break;
                                    case 'pending':
                                        $status = 'pending';
                                        $status_text = esc_html__('⏰ No iniciada', 'eipsi-forms');
                                        $status_class = 'status-pending';
                                        break;
                                }
                            }
                            
                            $form_title = get_the_title($wave['form_id']) ?: sprintf(__('Toma %d', 'eipsi-forms'), $wave['wave_index']);
                            $submitted_at = $assignment['submitted_at'] ?? null;
                            $duration_seconds = $assignment['submission_duration'] ?? null;
                            ?>
                            
                            <tr class="<?php echo esc_attr($status_class); ?>">
                                <td class="eipsi-wave-cell">
                                    <span class="eipsi-wave-index">T<?php echo absint($wave['wave_index']); ?></span>
                                    <span class="eipsi-wave-name"><?php echo esc_html($form_title); ?></span>
                                </td>
                                <td class="eipsi-status-cell">
                                    <span class="eipsi-status-badge <?php echo esc_attr($status_class); ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="eipsi-due-cell">
                                    <?php echo esc_html(eipsi_format_date($wave['due_date'], '-')); ?>
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
            <div class="eipsi-no-data">
                <p><?php esc_html_e('No hay tomas asignadas aún.', 'eipsi-forms'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sección 4: Pie -->
    <div class="eipsi-dashboard-footer">
        <div class="eipsi-footer-actions">
            <a href="#" class="eipsi-link-contact" onclick="alert('<?php esc_attr_e('Por favor, contacta al investigador del estudio.\n\nEmail: [configurar en admin]', 'eipsi-forms'); ?>'); return false;">
                <?php esc_html_e('¿Problemas? Contactar investigador', 'eipsi-forms'); ?>
            </a>
            
            <button type="button" class="eipsi-button-logout" id="eipsi-logout-button" data-nonce="<?php echo wp_create_nonce('eipsi_participant_logout'); ?>">
                <?php esc_html_e('Cerrar sesión', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>