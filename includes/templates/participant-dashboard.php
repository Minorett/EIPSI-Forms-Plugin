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

/**
 * Rediseño del Dashboard del Participante
 * Versión 2.1.4 - Nueva estructura con timeline y estados visuales
 */

// Preparar datos de waves para el timeline
$waves_timeline = array();
$all_done = true;
$next_ready_index = -1;

foreach ($all_waves as $index => $wave) {
    $wave_id = $wave['id'];
    $assignment = isset($wave_assignments[$wave_id]) ? $wave_assignments[$wave_id] : null;
    
    // Determinar status
    if ($assignment && $assignment['status'] === 'submitted') {
        $status = 'done';
        $status_text = 'Completada';
    } elseif ($next_ready_index === -1 && (!$assignment || $assignment['status'] !== 'submitted')) {
        // Es la próxima wave disponible
        $next_ready_index = $index;
        
        // Verificar si está bloqueada por intervalo
        if (isset($wave['is_locked']) && $wave['is_locked']) {
            $status = 'locked';
            $status_text = 'Pendiente';
            $all_done = false;
        } else {
            $status = 'ready';
            $status_text = 'Lista ahora';
            $all_done = false;
        }
    } else {
        $status = 'future';
        $status_text = 'Pendiente';
        $all_done = false;
    }
    
    $waves_timeline[] = array(
        'index' => $index + 1,
        'label' => sprintf(__('Toma %d', 'eipsi-forms'), $index + 1),
        'name' => $wave['name'],
        'status' => $status,
        'status_text' => $status_text,
        'form_id' => isset($wave['form_id']) ? $wave['form_id'] : '',
        'wave_id' => $wave_id,
        'available_timestamp' => isset($wave['available_timestamp']) ? $wave['available_timestamp'] : '',
        'interval_unit' => isset($wave['interval_unit']) ? $wave['interval_unit'] : 'days'
    );
}

// Determinar la wave actual para mostrar en la CTA
$current_wave = null;
$current_status = '';
if (!$all_done && $next_ready_index >= 0) {
    $current_wave = $waves_timeline[$next_ready_index];
    $current_status = $current_wave['status'];
}
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
        // (2) Es estudio longitudinal (tiene waves - $all_waves no vacío)
        if ($participant_id > 0 && !empty($all_waves)) : 
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
    <div class="eipsi-dashboard-section eipsi-section-highlight">
        <h2 class="eipsi-section-title">
            <span class="section-icon">📋</span>
            <?php esc_html_e('Tu Próxima Toma', 'eipsi-forms'); ?>
        </h2>
        
        <?php if ($next_wave): ?>
            <?php
            $form_title = get_the_title($next_wave['form_id']) ?: sprintf(__('Toma %d', 'eipsi-forms'), $next_wave['wave_index']);
            $is_locked = !empty($next_wave['is_locked']);
            $available_timestamp = isset($next_wave['available_timestamp']) ? $next_wave['available_timestamp'] : '';
            ?>
            
            <div class="eipsi-next-take-card <?php echo esc_attr($is_locked ? 'locked' : ''); ?>">
                <div class="eipsi-card-header">
                    <div class="wave-badge">
                        <span class="eipsi-wave-index">T<?php echo absint($next_wave['wave_index']); ?></span>
                    </div>
                    <div class="wave-info">
                        <h3 class="eipsi-form-title"><?php echo esc_html($form_title); ?></h3>
                    </div>
                </div>
                
                <div class="eipsi-card-body">
                    <?php if ($is_locked && $available_timestamp): ?>
                        <!-- Toma bloqueada - mostrar fecha de disponibilidad y countdown -->
                        <div class="eipsi-availability-info locked">
                            <span class="availability-icon">🔒</span>
                            <div class="availability-info">
                                <span class="eipsi-availability-label"><?php esc_html_e('Disponible el:', 'eipsi-forms'); ?></span>
                                <span class="eipsi-availability-date"><?php echo esc_html(date_i18n('j \d\e F Y, H:i', $available_timestamp)); ?></span>
                                <div class="eipsi-countdown" data-available-timestamp="<?php echo esc_attr($available_timestamp); ?>">
                                    <span class="countdown-label"><?php esc_html_e('Faltan:', 'eipsi-forms'); ?></span>
                                    <span class="countdown-value">--</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="eipsi-take-actions">
                            <button type="button" class="eipsi-button-secondary" disabled>
                                <span class="btn-icon">🔒</span>
                                <?php esc_html_e('Esperando...', 'eipsi-forms'); ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Toma lista para responder -->
                        <div class="eipsi-availability-info ready">
                            <span class="availability-icon">✅</span>
                            <div class="availability-info">
                                <span class="eipsi-availability-label"><?php esc_html_e('Estado:', 'eipsi-forms'); ?></span>
                                <span class="eipsi-availability-status"><?php esc_html_e('¡Lista para comenzar!', 'eipsi-forms'); ?></span>
                            </div>
                        </div>
                        
                        <div class="eipsi-take-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('wave_id' => $next_wave['id'], 'survey_id' => $survey_id), home_url('/estudio/'))); ?>"
                               class="eipsi-button-primary eipsi-respond-now">
                                <span class="btn-icon">▶️</span>
                                <?php esc_html_e('Responder ahora', 'eipsi-forms'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
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
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($all_done) : ?>
        <!-- Estado 3: Estudio completado -->
        <div class="eipsi-dash-cta eipsi-cta--complete">
            <p class="eipsi-cta-complete-title"><?php esc_html_e('Estudio completado', 'eipsi-forms'); ?></p>
            <p class="eipsi-cta-complete-sub">
                <?php esc_html_e('Gracias por tu participación. Tus respuestas han sido registradas.', 'eipsi-forms'); ?>
            </p>
        </div>
    <?php elseif ($current_status === 'locked') : ?>
        <!-- Estado 1: Toma bloqueada (hay intervalo de espera) -->
        <div class="eipsi-dash-cta eipsi-cta--locked">
            <div class="eipsi-cta-top">
                <span class="eipsi-cta-badge"><?php echo esc_html($current_wave['label']); ?></span>
                <span class="eipsi-cta-name"><?php echo esc_html($current_wave['name']); ?></span>
            </div>
            <p class="eipsi-cta-desc"><?php esc_html_e('Esta toma estará disponible en', 'eipsi-forms'); ?></p>
            <div class="eipsi-countdown"
                 data-available-timestamp="<?php echo esc_attr($current_wave['available_timestamp']); ?>"
                 data-unit="<?php echo esc_attr($current_wave['interval_unit']); ?>">
                <span class="eipsi-countdown-num">--</span>
                <span class="eipsi-countdown-unit"></span>
            </div>
            <p class="eipsi-cta-meta"><?php esc_html_e('Te notificaremos cuando esté disponible.', 'eipsi-forms'); ?></p>
        </div>
    <?php elseif ($current_status === 'ready') : ?>
        <!-- Estado 2: Toma disponible (puede comenzar) -->
        <div class="eipsi-dash-cta eipsi-cta--ready">
            <div class="eipsi-cta-top">
                <span class="eipsi-cta-badge"><?php echo esc_html($current_wave['label']); ?></span>
                <span class="eipsi-cta-name"><?php echo esc_html($current_wave['name']); ?></span>
            </div>
            <p class="eipsi-cta-desc">
                <?php esc_html_e('Esta toma ya está disponible. Podés completarla ahora o en cualquier momento antes de que se cierre la ventana de respuesta.', 'eipsi-forms'); ?>
            </p>
            <form action="" method="get">
                <input type="hidden" name="form_id" value="<?php echo esc_attr($current_wave['form_id']); ?>">
                <input type="hidden" name="wave_id" value="<?php echo esc_attr($current_wave['wave_id']); ?>">
                <button
                    type="submit"
                    class="eipsi-cta-btn"
                    style="background-color:#3B6CAA;color:#ffffff;border:none;border-radius:8px;padding:14px 20px;font-size:15px;font-weight:600;width:100%;cursor:pointer;display:block;text-align:center;"
                    onmouseover="this.style.backgroundColor='#1E3A5F'"
                    onmouseout="this.style.backgroundColor='#3B6CAA'"
                    onclick="this.style.backgroundColor='#1E3A5F';this.textContent='<?php echo esc_js(__('Cargando...', 'eipsi-forms')); ?>'"
                >
                    <?php esc_html_e('Comenzar toma →', 'eipsi-forms'); ?>
                </button>
            </form>
            <p class="eipsi-cta-meta"><?php esc_html_e('Duración estimada: 8–12 min · Podés pausar y retomar', 'eipsi-forms'); ?></p>
        </div>
    <?php endif; ?>

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
