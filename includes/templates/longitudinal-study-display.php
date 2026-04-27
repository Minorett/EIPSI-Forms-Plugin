<?php
/**
 * Template: Longitudinal Study Display
 *
 * Displays a longitudinal study with participant dashboard, public login view,
 * and admin dashboard view.
 *
 * @package EIPSI_Forms
 * @since 1.5.0
 * @since 1.6.0 - Enhanced participant experience
 * @since 1.6.1 - Fixed authentication detection, added login form for public view
 * @since 2.1.0 - Fixed $completed_waves scope, duplicate description, wave time limit
 * @since 2.1.1 - Fixed time_unit respect (minutes/hours/days) in interval calculations
 *
 * Variables available from shortcode:
 * @var object $study               Study object from database
 * @var array  $waves               Array of wave rows (ARRAY_A)
 * @var int    $participant_count   Number of participants
 * @var array  $study_config        Decoded JSON configuration
 * @var string $pi_name             Principal investigator display name
 * @var string $shareable_url       URL with study parameters
 * @var string $shortcode_string    The shortcode syntax for copying
 * @var bool   $show_config         Whether to show configuration details
 * @var bool   $show_waves          Whether to show waves list
 * @var string $theme               Theme style (default, compact, card)
 * @var int    $time_limit_override Override time limit in minutes (0 = use wave default)
 * @var string $view_mode           View mode: dashboard | participant | public
 * @var bool   $is_participant_logged_in
 * @var int    $current_participant_id
 * @var int    $actual_study_id
 * @var bool   $magic_link_login_success
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure Wave_Service is loaded for time_unit normalization
// v2.1.2: Prevents empty(0) bug when time_unit = 0 (minutes)
if ( ! class_exists( 'Wave_Service' ) ) {
    require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/services/Wave_Service.php';
}

// =========================================================================
// Bootstrap variables
// =========================================================================

// Use auth state injected by the shortcode; fall back to live detection.
if ( ! isset( $is_participant_logged_in ) ) {
    $is_participant_logged_in = class_exists( 'EIPSI_Auth_Service' ) && EIPSI_Auth_Service::is_authenticated();
}
if ( ! isset( $current_participant_id ) ) {
    $current_participant_id = $is_participant_logged_in
        ? EIPSI_Auth_Service::get_current_participant()
        : 0;
}

$magic_link_login  = isset( $magic_link_login_success ) && $magic_link_login_success;
$total_waves       = count( $waves );
$study_id_for_query = isset( $actual_study_id ) ? $actual_study_id : (int) $study->id;

// FIX (v2.1.0): initialise progress variables BEFORE the if-block so they
// are available everywhere in the template (previously $completed_waves was
// scoped inside the if and the template computed a float workaround).
$next_wave            = null;
$completed_waves      = 0;
$participant_progress = 0;

if ( $is_participant_logged_in && $current_participant_id && $show_waves ) {
    global $wpdb;

    $assignments = $wpdb->get_results( $wpdb->prepare(
        "SELECT a.wave_id, a.status
           FROM {$wpdb->prefix}survey_assignments a
     INNER JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
          WHERE a.participant_id = %d AND w.study_id = %d
       ORDER BY w.wave_index ASC",
        $current_participant_id,
        $study_id_for_query
    ) );

    $wave_status = array();
    foreach ( $assignments as $assignment ) {
        $wave_status[ $assignment->wave_id ] = $assignment->status;
    }

    $last_wave_id = null;
    foreach ( $waves as $wave ) {
        if ( empty( $wave_status[ $wave['id'] ] ) || $wave_status[ $wave['id'] ] !== 'submitted' ) {
            if ( null === $next_wave ) {
                $next_wave = $wave;
            }
        } else {
            $completed_waves++;
            // Track the last completed wave (most recent one before next_wave)
            if ( null === $next_wave ) {
                $last_wave_id = $wave['id'];
            }
        }
    }

    $participant_progress = $total_waves > 0
        ? (int) round( ( $completed_waves / $total_waves ) * 100 )
        : 0;

    // =========================================================================
    // INTERVAL CHECK: Verify if next wave respects configured interval
    // =========================================================================
    if ( $next_wave && $last_wave_id && ! empty( $next_wave['interval_days'] ) ) {
        // Get submitted_at of the last completed submission
        $last_submission = $wpdb->get_row( $wpdb->prepare(
            "SELECT COALESCE(submitted_at, updated_at, created_at) as submitted_at
            FROM {$wpdb->prefix}survey_assignments
            WHERE participant_id = %d AND wave_id = %d AND status = 'submitted'",
            $current_participant_id,
            $last_wave_id
        ) );

        if ( $last_submission ) {
            // FIX (v2.1.2): Use Wave_Service helper to safely normalize time_unit
            // This prevents the empty(0) === true bug that treated minutes as days
            $interval_value  = (int) $next_wave['interval_days'];
            
            // CRITICAL: Use Wave_Service::normalize_time_unit() to safely handle
            // all possible values: 0, '0', 1, '1', 2, '2', 'minutes', 'hours', 'days'
            // The empty() function treats 0 as empty, causing the minutes bug
            $time_unit = Wave_Service::normalize_time_unit( $next_wave['time_unit'] ?? null );
            
            // DEBUG: Log values for troubleshooting
            error_log(sprintf('[EIPSI-DISPLAY] wave_id=%d, raw_time_unit=%s, normalized_time_unit=%s, interval_value=%d, submitted_at=%s',
                $next_wave['id'],
                $next_wave['time_unit'] ?? 'NULL',
                $time_unit,
                $interval_value,
                $last_submission->submitted_at
            ));
            
            // Map time_unit to strtotime-compatible string
            $unit_map = array(
                'minutes' => 'minutes',
                'hours'   => 'hours',
                'days'    => 'days'
            );
            $strtotime_unit = isset( $unit_map[ $time_unit ] ) ? $unit_map[ $time_unit ] : 'days';
            
            $available_date = strtotime( $last_submission->submitted_at . ' +' . $interval_value . ' ' . $strtotime_unit );
            
            // DEBUG: Log calculated date
            error_log(sprintf('[EIPSI-DISPLAY] Calculated available_date=%d (%s), strtotime_unit=%s, time_unit=%s',
                $available_date,
                date('Y-m-d H:i:s', $available_date),
                $strtotime_unit,
                $time_unit
            ));
            $now            = current_time( 'timestamp' );

            if ( $available_date > $now ) {
                // Next wave is locked - interval not yet passed
                // Format date as "8 de abril, 18:00" (no year if same year)
                $current_year = date('Y');
                $available_year = date('Y', $available_date);
                if ( $available_year === $current_year ) {
                    $next_wave['available_date'] = date_i18n( 'j \d\e F, H:i', $available_date );
                } else {
                    $next_wave['available_date'] = date_i18n( 'j \d\e F Y, H:i', $available_date );
                }
                $next_wave['available_timestamp'] = $available_date;
                $next_wave['is_locked']      = true;
            }
        }
    }
}

// CSS classes
$container_class = 'eipsi-longitudinal-study eipsi-theme-' . esc_attr( $theme );
$status_class    = 'status-' . esc_attr( $study->status );
$view_class      = 'view-' . esc_attr( $view_mode );
$public_return_url = get_permalink();
$consent_declined = isset( $_GET['consent'] ) && sanitize_text_field( wp_unslash( $_GET['consent'] ) ) === 'declined';
$withdrawal_success = isset( $_GET['withdrawal'] ) && sanitize_text_field( wp_unslash( $_GET['withdrawal'] ) ) === 'success';
$withdrawal_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
?>

<div class="<?php echo esc_attr( $container_class ); ?> <?php echo esc_attr( $view_class ); ?>"
     data-study-id="<?php echo esc_attr( $study->id ); ?>"
     data-study-code="<?php echo esc_attr( $study->study_code ); ?>">

    <?php // ================================================================
    // PARTICIPANT VIEW
    // ================================================================ ?>
    <?php if ( $view_mode === 'participant' ) : ?>

        <?php if ( $is_participant_logged_in ) : ?>
            <div class="eipsi-participant-welcome">

                <div class="welcome-header">
                    <div class="welcome-header-content">
                        <?php if ( $magic_link_login ) : ?>
                            <h3 class="welcome-title">✨ ¡Bienvenido/a!</h3>
                            <p class="welcome-subtitle">Accediste correctamente a tu estudio</p>
                        <?php else : ?>
                            <h3 class="welcome-title">👋 ¡Hola de nuevo!</h3>
                            <p class="welcome-subtitle">Tu progreso en este estudio</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ( $is_participant_logged_in && $current_participant_id ) : ?>
                        <!-- Dropdown de abandono (solo participantes logueados) -->
                        <div class="eipsi-header-dropdown-container">
                            <button type="button" 
                                    class="eipsi-header-dropdown-trigger" 
                                    id="eipsi-withdraw-dropdown-trigger"
                                    aria-label="Opciones de estudio"
                                    aria-expanded="false">
                                <span class="eipsi-dropdown-icon">⚙️</span>
                            </button>
                            <div class="eipsi-header-dropdown-menu" id="eipsi-withdraw-dropdown-menu" style="display: none;">
                                <button type="button" class="eipsi-dropdown-item" id="eipsi-withdraw-button">
                                    <span class="eipsi-item-icon">🚪</span>
                                    <span class="eipsi-item-text"><?php _e('Abandonar estudio', 'eipsi-forms'); ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="progress-overview">
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_attr( $participant_progress ); ?>%;"></div>
                        </div>
                        <span class="progress-text"><?php echo esc_html( $participant_progress ); ?>% completado</span>
                    </div>
                    <div class="progress-stats">
                        <span class="stat-item">
                            <?php
                            // FIX (v2.1.0): use $completed_waves (integer) instead of
                            // the float expression ($participant_progress / 100 * $total_waves).
                            printf(
                                '<strong>%d</strong> de %d tomas',
                                $completed_waves,
                                $total_waves
                            );
                            ?>
                        </span>
                    </div>
                </div>

                <?php if ( $next_wave ) : ?>
                    <div class="next-action">
                        <h4 class="next-action-title">📝 Tu próxima toma</h4>
                        <div class="next-action-card <?php echo ! empty( $next_wave['is_locked'] ) ? 'wave-locked' : ''; ?>">
                            <div class="wave-info">
                                <span class="wave-badge">T<?php echo esc_html( $next_wave['wave_index'] ); ?></span>
                                <strong class="wave-name"><?php echo esc_html( $next_wave['name'] ); ?></strong>
                            </div>
                            <?php if ( ! empty( $next_wave['is_locked'] ) ) : ?>
                                <div class="wave-locked-message" data-eipsi-countdown data-available-timestamp="<?php echo esc_attr( $next_wave['available_timestamp'] ); ?>">
                                    <div class="lock-content" style="flex: 1;">
                                        <?php
                                        // Calculate time remaining for countdown
                                        $time_remaining = $next_wave['available_timestamp'] - $now;
                                        $countdown_parts = array();
                                        
                                        if ( $time_remaining > 0 ) {
                                            $remaining_days = floor( $time_remaining / DAY_IN_SECONDS );
                                            $remaining_hours = floor( ( $time_remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
                                            $remaining_mins = floor( ( $time_remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
                                            
                                            if ( $remaining_days > 0 ) {
                                                $countdown_parts[] = $remaining_days . 'd';
                                            }
                                            if ( $remaining_hours > 0 || $remaining_days > 0 ) {
                                                $countdown_parts[] = $remaining_hours . 'h';
                                            }
                                            if ( $remaining_mins > 0 || ( $remaining_days === 0 && $remaining_hours === 0 ) ) {
                                                $countdown_parts[] = $remaining_mins . 'm';
                                            }
                                        }
                                        $countdown_text = implode( ' ', $countdown_parts );
                                        ?>
                                        <p class="lock-text" style="margin: 0 0 4px 0; font-weight: 500;">
                                            📅 <?php 
                                            printf(
                                                esc_html__( 'Disponible el %s', 'eipsi-forms' ),
                                                esc_html( $next_wave['available_date'] )
                                            ); 
                                            ?>
                                        </p>
                                        <?php if ( ! empty( $countdown_text ) ) : ?>
                                            <p class="countdown-text" style="margin: 0; font-size: 13px; color: #0284c7;">
                                                ⏳ <?php esc_html_e( 'Quedan', 'eipsi-forms' ); ?> <span class="countdown-value"><?php echo esc_html( $countdown_text ); ?></span>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ( ! empty( $next_wave['interval_days'] ) ) : ?>
                                        <small class="lock-hint">
                                            <?php
                                            // FIX (v2.1.1): Show correct time unit (minutes, hours, days)
                                            // IMPROVED (v2.2.0): Convert large minutes to human-readable format
                                            // FIX (v2.2.1): Use Wave_Service::normalize_time_unit() to handle 0 correctly
                                            // (empty(0) returns true in PHP, causing 'minutes' to become 'days')
                                            $interval_value = (int) $next_wave['interval_days'];
                                            $time_unit = Wave_Service::normalize_time_unit( $next_wave['time_unit'] ?? null );
                                            
                                            /**
                                             * Convert interval to human-readable format
                                             * - Minutes < 60: show as minutes
                                             * - Minutes 60-1439: convert to hours
                                             * - Minutes >= 1440: convert to days
                                             * - Hours >= 24: convert to days
                                             */
                                            function eipsi_format_interval_human_readable( $value, $unit ) {
                                                // Convert everything to minutes first
                                                $total_minutes = $value;
                                                if ( $unit === 'hours' ) {
                                                    $total_minutes = $value * 60;
                                                } elseif ( $unit === 'days' ) {
                                                    $total_minutes = $value * 1440;
                                                }
                                                
                                                // Less than 1 hour: show minutes
                                                if ( $total_minutes < 60 ) {
                                                    return sprintf(
                                                        _n( '%d minuto', '%d minutos', $total_minutes, 'eipsi-forms' ),
                                                        $total_minutes
                                                    );
                                                }
                                                
                                                // Less than 24 hours: show hours (with minutes if not exact)
                                                if ( $total_minutes < 1440 ) {
                                                    $hours = floor( $total_minutes / 60 );
                                                    $mins = $total_minutes % 60;
                                                    
                                                    if ( $mins === 0 ) {
                                                        return sprintf(
                                                            _n( '%d hora', '%d horas', $hours, 'eipsi-forms' ),
                                                            $hours
                                                        );
                                                    } else {
                                                        return sprintf(
                                                            __( '%d horas y %d minutos', 'eipsi-forms' ),
                                                            $hours,
                                                            $mins
                                                        );
                                                    }
                                                }
                                                
                                                // 24 hours or more: show days (with hours if not exact)
                                                $days = floor( $total_minutes / 1440 );
                                                $remaining_mins = $total_minutes % 1440;
                                                $hours = floor( $remaining_mins / 60 );
                                                
                                                if ( $remaining_mins === 0 ) {
                                                    return sprintf(
                                                        _n( '%d día', '%d días', $days, 'eipsi-forms' ),
                                                        $days
                                                    );
                                                } elseif ( $hours === 0 ) {
                                                    $mins = $remaining_mins % 60;
                                                    return sprintf(
                                                        __( '%d días y %d minutos', 'eipsi-forms' ),
                                                        $days,
                                                        $mins
                                                    );
                                                } else {
                                                    return sprintf(
                                                        __( '%d días y %d horas', 'eipsi-forms' ),
                                                        $days,
                                                        $hours
                                                    );
                                                }
                                            }
                                            
                                            $interval_text = eipsi_format_interval_human_readable( $interval_value, $time_unit );
                                            
                                            printf(
                                                esc_html__( '(disponible después de %s)', 'eipsi-forms' ),
                                                $interval_text
                                            );
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <form action="" method="get">
                                    <input type="hidden" name="form_id" value="<?php echo esc_attr( $next_wave['form_id'] ); ?>">
                                    <input type="hidden" name="wave_id" value="<?php echo esc_attr( $next_wave['id'] ); ?>">
                                    <button type="submit" class="button button-primary button-large">
                                        <?php esc_html_e( 'Comenzar toma →', 'eipsi-forms' ); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="completion-message">
                        <span class="completion-icon">🎉</span>
                        <h4 class="completion-title">¡Felicidades!</h4>
                        <p class="completion-text"><?php esc_html_e( 'Has completado todas las tomas de este estudio. ¡Gracias por tu participación!', 'eipsi-forms' ); ?></p>
                    </div>
                <?php endif; ?>

                <?php // Study description — shown once, here, for authenticated participants.
                if ( ! empty( $study->description ) ) : ?>
                    <div class="eipsi-study-description-section">
                        <h3 class="section-title">📋 <?php esc_html_e( 'Sobre este estudio', 'eipsi-forms' ); ?></h3>
                        <div class="description-content">
                            <?php echo wp_kses_post( wpautop( $study->description ) ); ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        <?php else : ?>
            <?php // Authenticated view requested but session is gone — show hero only. ?>
            <div class="eipsi-study-hero">
                <h2 class="hero-title">📊 <?php echo esc_html( $study->study_name ); ?></h2>
                <p class="hero-subtitle"><?php esc_html_e( 'Ayudá a la ciencia clínica completando este estudio', 'eipsi-forms' ); ?></p>
            </div>
        <?php endif; ?>

    <?php endif; // participant view ?>


    <?php // ================================================================
    // PUBLIC VIEW (not logged in)
    // ================================================================ ?>
    <?php if ( $view_mode === 'public' ) : ?>

        <div class="eipsi-public-view">

            <?php if ( $consent_declined || $withdrawal_success ) : ?>
                <div class="eipsi-public-status-card">
                    <div class="eipsi-public-status-icon" aria-hidden="true">
                        <?php echo $consent_declined ? 'X' : 'OK'; ?>
                    </div>
                    <h2 class="eipsi-public-status-title">
                        <?php
                        if ( $consent_declined ) {
                            esc_html_e( 'Decision registrada', 'eipsi-forms' );
                        } elseif ( $withdrawal_type === 'b2' ) {
                            esc_html_e( 'Solicitud de eliminacion registrada', 'eipsi-forms' );
                        } else {
                            esc_html_e( 'Participacion finalizada', 'eipsi-forms' );
                        }
                        ?>
                    </h2>
                    <p class="eipsi-public-status-text">
                        <?php
                        if ( $consent_declined ) {
                            esc_html_e( 'Decidiste no dar tu consentimiento para participar en este estudio. Tu decision quedo registrada y tu sesion se cerro correctamente.', 'eipsi-forms' );
                        } elseif ( $withdrawal_type === 'b2' ) {
                            esc_html_e( 'Tu solicitud de eliminacion de datos fue registrada. Ya no tenes una sesion activa en este estudio.', 'eipsi-forms' );
                        } else {
                            esc_html_e( 'Tu salida del estudio fue registrada. Ya no tenes una sesion activa y volviste al acceso publico.', 'eipsi-forms' );
                        }
                        ?>
                    </p>
                    <div class="eipsi-public-status-details">
                        <p>
                            <?php
                            if ( $consent_declined ) {
                                esc_html_e( 'Esta decision quedara disponible para el investigador en las exportaciones del estudio.', 'eipsi-forms' );
                            } elseif ( $withdrawal_type === 'b2' ) {
                                esc_html_e( 'No recibiras nuevos contactos y el equipo investigador procesara tu solicitud segun la configuracion del estudio.', 'eipsi-forms' );
                            } else {
                                esc_html_e( 'No recibiras nuevos contactos de este estudio y tus respuestas previas se conservaran segun la configuracion de investigacion.', 'eipsi-forms' );
                            }
                            ?>
                        </p>
                    </div>
                    <p class="eipsi-public-status-action">
                        <a href="<?php echo esc_url( $public_return_url ); ?>" class="eipsi-button-primary">
                            <?php esc_html_e( 'Volver al acceso del estudio', 'eipsi-forms' ); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>
                <div id="login-section" class="eipsi-login-section">
                    <?php if ( function_exists( 'eipsi_render_survey_login_form' ) ) : ?>
                        <?php echo eipsi_render_survey_login_form( array(
                            'survey_id'    => $study_id_for_query,
                            'redirect_url' => $public_return_url,
                        ) ); ?>
                    <?php else : ?>
                        <div class="eipsi-login-fallback">
                            <h3><?php esc_html_e( 'Acceso al Estudio', 'eipsi-forms' ); ?></h3>
                            <p><?php esc_html_e( 'Para participar en este estudio, necesitas iniciar sesion o registrarte.', 'eipsi-forms' ); ?></p>
                            <p>
                                <a href="<?php echo esc_url( wp_login_url( $public_return_url ) ); ?>" class="button button-primary">
                                    <?php esc_html_e( 'Iniciar sesion', 'eipsi-forms' ); ?>
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

    <?php endif; // public view ?>


    <?php // ================================================================
    // DASHBOARD VIEW (admin only)
    // ================================================================ ?>
    <?php if ( $view_mode === 'dashboard' || ( $view_mode !== 'participant' && $view_mode !== 'public' ) ) : ?>

        <div class="eipsi-study-header">
            <div class="eipsi-study-title-section">
                <span class="eipsi-study-badge <?php echo esc_attr( $status_class ); ?>">
                    <?php echo esc_html( ucfirst( $study->status ) ); ?>
                </span>
                <h2 class="eipsi-study-name"><?php echo esc_html( $study->study_name ); ?></h2>
                <?php if ( ! empty( $study->study_code ) ) : ?>
                    <span class="eipsi-study-code"><?php echo esc_html( $study->study_code ); ?></span>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $study->description ) ) : ?>
                <div class="eipsi-study-description">
                    <?php echo wp_kses_post( wpautop( $study->description ) ); ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>


    <?php // ================================================================
    // STUDY CONFIG SUMMARY (dashboard only)
    // ================================================================ ?>
    <?php if ( $show_config && $view_mode === 'dashboard' ) : ?>
        <div class="eipsi-study-config">
            <h3 class="eipsi-section-title">📊 <?php esc_html_e( 'Información del Estudio', 'eipsi-forms' ); ?></h3>
            <div class="eipsi-config-grid">

                <div class="eipsi-config-item">
                    <span class="config-label">👥 <?php esc_html_e( 'Participantes:', 'eipsi-forms' ); ?></span>
                    <span class="config-value"><?php echo number_format_i18n( $participant_count ); ?></span>
                </div>

                <div class="eipsi-config-item">
                    <span class="config-label">🌊 <?php esc_html_e( 'Ondas:', 'eipsi-forms' ); ?></span>
                    <span class="config-value"><?php echo number_format_i18n( count( $waves ) ); ?></span>
                </div>

                <?php if ( ! empty( $pi_name ) ) : ?>
                    <div class="eipsi-config-item">
                        <span class="config-label">🔬 <?php esc_html_e( 'Investigador Principal:', 'eipsi-forms' ); ?></span>
                        <span class="config-value"><?php echo esc_html( $pi_name ); ?></span>
                    </div>
                <?php endif; ?>

                <div class="eipsi-config-item">
                    <span class="config-label">📅 <?php esc_html_e( 'Creado:', 'eipsi-forms' ); ?></span>
                    <span class="config-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $study->created_at ) ) ); ?></span>
                </div>

                <?php if ( ! empty( $study_config['randomization_enabled'] ) ) : ?>
                    <div class="eipsi-config-item">
                        <span class="config-label">🎲 <?php esc_html_e( 'Aleatorización:', 'eipsi-forms' ); ?></span>
                        <span class="config-value">
                            <?php echo $study_config['randomization_enabled']
                                ? esc_html__( 'Activada', 'eipsi-forms' )
                                : esc_html__( 'No activada', 'eipsi-forms' ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( isset( $study_config['reminders_enabled'] ) ) : ?>
                    <div class="eipsi-config-item">
                        <span class="config-label">⏰ <?php esc_html_e( 'Recordatorios:', 'eipsi-forms' ); ?></span>
                        <span class="config-value">
                            <?php echo $study_config['reminders_enabled']
                                ? esc_html__( 'Activados', 'eipsi-forms' )
                                : esc_html__( 'No activados', 'eipsi-forms' ); ?>
                        </span>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>


    <?php // ================================================================
    // WAVES LIST (dashboard only)
    // ================================================================ ?>
    <?php if ( $show_waves && $view_mode === 'dashboard' ) : ?>
        <?php if ( ! empty( $waves ) ) : ?>
            <div class="eipsi-waves-section">
                <h3 class="eipsi-section-title">🌊 <?php esc_html_e( 'Tomas (Waves)', 'eipsi-forms' ); ?></h3>
                <div class="eipsi-waves-list">
                    <?php foreach ( $waves as $wave ) :
                        $wave_status_class = 'wave-status-' . esc_attr( $wave['status'] );
                        $form_title        = eipsi_get_form_title( $wave['form_id'] );

                        // FIX (v2.1.0): 'completion_time_limit' is not returned by the waves
                        // query — use null coalescing with 0 as fallback to avoid PHP notices.
                        $wave_time_limit      = isset( $wave['completion_time_limit'] ) ? (int) $wave['completion_time_limit'] : 0;
                        $effective_time_limit = $time_limit_override > 0 ? $time_limit_override : $wave_time_limit;
                        $time_display         = eipsi_format_time_limit( $effective_time_limit );
                    ?>
                        <div class="eipsi-wave-card <?php echo esc_attr( $wave_status_class ); ?>"
                             data-wave-id="<?php echo esc_attr( $wave['id'] ); ?>">

                            <div class="wave-header">
                                <span class="wave-index">T<?php echo esc_html( $wave['wave_index'] ); ?></span>
                                <span class="wave-status-badge <?php echo esc_attr( $wave_status_class ); ?>">
                                    <?php echo esc_html( eipsi_get_wave_status_label( $wave['status'] ) ); ?>
                                </span>
                            </div>

                            <div class="wave-content">
                                <h4 class="wave-name"><?php echo esc_html( $wave['name'] ); ?></h4>

                                <?php if ( ! empty( $wave['description'] ) ) : ?>
                                    <p class="wave-description"><?php echo esc_html( $wave['description'] ); ?></p>
                                <?php endif; ?>

                                <div class="wave-details">

                                    <div class="wave-detail">
                                        <span class="detail-icon">📋</span>
                                        <span class="detail-label"><?php esc_html_e( 'Formulario:', 'eipsi-forms' ); ?></span>
                                        <span class="detail-value"><?php echo esc_html( $form_title ); ?></span>
                                    </div>

                                    <div class="wave-detail">
                                        <span class="detail-icon">⏱️</span>
                                        <span class="detail-label"><?php esc_html_e( 'Tiempo Límite:', 'eipsi-forms' ); ?></span>
                                        <span class="detail-value"><?php echo esc_html( $time_display ); ?></span>
                                    </div>

                                    <?php if ( ! empty( $wave['due_date'] ) ) : ?>
                                        <div class="wave-detail">
                                            <span class="detail-icon">📅</span>
                                            <span class="detail-label"><?php esc_html_e( 'Vencimiento:', 'eipsi-forms' ); ?></span>
                                            <span class="detail-value">
                                                <?php echo esc_html( date_i18n(
                                                    get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                                                    strtotime( $wave['due_date'] )
                                                ) ); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $wave['is_mandatory'] ) ) : ?>
                                        <div class="wave-detail">
                                            <span class="detail-icon">⭐</span>
                                            <span class="detail-label"><?php esc_html_e( 'Obligatoria:', 'eipsi-forms' ); ?></span>
                                            <span class="detail-value"><?php esc_html_e( 'Sí', 'eipsi-forms' ); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $wave['reminder_days'] ) ) : ?>
                                        <div class="wave-detail">
                                            <span class="detail-icon">🔔</span>
                                            <span class="detail-label"><?php esc_html_e( 'Recordatorio:', 'eipsi-forms' ); ?></span>
                                            <span class="detail-value">
                                                <?php printf(
                                                    esc_html( _n( '%d día antes', '%d días antes', $wave['reminder_days'], 'eipsi-forms' ) ),
                                                    (int) $wave['reminder_days']
                                                ); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="eipsi-waves-empty">
                <p><?php esc_html_e( 'No hay tomas (waves) configuradas para este estudio.', 'eipsi-forms' ); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; // waves list ?>


    <?php // ================================================================
    // SHARE SECTION (dashboard only)
    // ================================================================ ?>
    <?php if ( $view_mode === 'dashboard' ) : ?>
        <div class="eipsi-share-section">
            <h3 class="eipsi-section-title">🔗 <?php esc_html_e( 'Compartir Estudio', 'eipsi-forms' ); ?></h3>

            <div class="eipsi-share-options">

                <div class="eipsi-share-option secure-shortcode">
                    <label>
                        <span class="label-icon">🔒</span>
                        <?php esc_html_e( 'Shortcode Seguro:', 'eipsi-forms' ); ?>
                        <span class="badge-recommended"><?php esc_html_e( 'Recomendado', 'eipsi-forms' ); ?></span>
                    </label>
                    <div class="eipsi-copy-field">
                        <code class="eipsi-shortcode-display"><?php echo esc_html( $shortcode_string ); ?></code>
                        <button type="button" class="eipsi-copy-btn"
                                data-copy="<?php echo esc_attr( $shortcode_string ); ?>"
                                title="<?php esc_attr_e( 'Copiar shortcode seguro', 'eipsi-forms' ); ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                            <span class="copy-text"><?php esc_html_e( 'Copiar', 'eipsi-forms' ); ?></span>
                        </button>
                    </div>
                    <small class="eipsi-help-text">
                        <?php esc_html_e( 'Usa study_code para mayor seguridad. Evitá usar IDs numéricos.', 'eipsi-forms' ); ?>
                    </small>
                </div>

                <div class="eipsi-share-option">
                    <label>
                        <span class="label-icon">🔗</span>
                        <?php esc_html_e( 'Enlace Directo:', 'eipsi-forms' ); ?>
                    </label>
                    <div class="eipsi-copy-field">
                        <input type="text" class="eipsi-url-display"
                               value="<?php echo esc_url( $shareable_url ); ?>" readonly>
                        <button type="button" class="eipsi-copy-btn"
                                data-copy="<?php echo esc_url( $shareable_url ); ?>"
                                title="<?php esc_attr_e( 'Copiar enlace', 'eipsi-forms' ); ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                            <span class="copy-text"><?php esc_html_e( 'Copiar', 'eipsi-forms' ); ?></span>
                        </button>
                    </div>
                    <small class="eipsi-help-text">
                        <?php esc_html_e( 'Compartí este enlace para acceder directamente al estudio.', 'eipsi-forms' ); ?>
                    </small>
                </div>

            </div>

            <div class="eipsi-magic-link-info">
                <h4 class="magic-link-title">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php esc_html_e( 'Invitar Participantes con Magic Links', 'eipsi-forms' ); ?>
                </h4>
                <p class="magic-link-description">
                    <?php esc_html_e( 'Los Magic Links permiten a los participantes acceder al estudio con un solo clic, sin necesidad de recordar contraseñas.', 'eipsi-forms' ); ?>
                </p>
                <div class="magic-link-features">
                    <ul>
                        <li>✅ <?php esc_html_e( 'Acceso seguro con tokens únicos', 'eipsi-forms' ); ?></li>
                        <li>✅ <?php esc_html_e( 'Válido por 7 días desde su generación', 'eipsi-forms' ); ?></li>
                        <li>✅ <?php esc_html_e( 'Revocable en cualquier momento', 'eipsi-forms' ); ?></li>
                        <li>✅ <?php esc_html_e( 'Ideal para estudios longitudinales', 'eipsi-forms' ); ?></li>
                    </ul>
                </div>
                <div class="magic-link-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=eipsi-longitudinal-study&tab=dashboard-study' ) ); ?>"
                       class="button button-primary">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e( 'Ir al Panel de Administración', 'eipsi-forms' ); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; // share section ?>

    <!-- Copy feedback (all views) -->
    <div class="eipsi-copy-feedback" style="display: none;" role="status" aria-live="polite">
        <span class="dashicons dashicons-yes"></span>
        <span class="feedback-text"></span>
    </div>

</div>

<?php
// Incluir modales de abandono si el participante está logueado
if ( $is_participant_logged_in && $current_participant_id ) {
    include EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/withdrawal-modals.php';
}
?>

<?php if ( $is_participant_logged_in && $current_participant_id ) : ?>
<!-- Estilos para dropdown de abandono -->
<style>
.welcome-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.eipsi-header-dropdown-container { position: relative; flex-shrink: 0; }
.eipsi-header-dropdown-trigger { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; cursor: pointer; font-size: 18px; transition: all 0.2s; }
.eipsi-header-dropdown-trigger:hover { background: #f1f5f9; border-color: #cbd5e1; }
.eipsi-header-dropdown-menu { position: absolute; top: calc(100% + 8px); right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); min-width: 180px; z-index: 1000; }
.eipsi-dropdown-item { width: 100%; padding: 12px 16px; border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151; text-align: left; }
.eipsi-dropdown-item:hover { background: #fef2f2; color: #dc2626; }
.eipsi-item-icon { font-size: 16px; }
</style>

<!-- Script para dropdown de abandono -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var trigger = document.getElementById('eipsi-withdraw-dropdown-trigger');
    var menu = document.getElementById('eipsi-withdraw-dropdown-menu');
    if (trigger && menu) {
        trigger.addEventListener('click', function() {
            var isVisible = menu.style.display === 'block';
            menu.style.display = isVisible ? 'none' : 'block';
            trigger.setAttribute('aria-expanded', !isVisible);
        });
        document.addEventListener('click', function(e) {
            if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = 'none';
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>
<?php endif; ?>

<?php
// Enqueue countdown script if there's a locked wave with future availability
if ( ! empty( $next_wave['is_locked'] ) && ! empty( $available_date ) ) : ?>
    <script src="<?php echo esc_url( plugins_url( 'assets/js/participant-countdown.js', dirname( dirname( __FILE__ ) ) ) ); ?>?ver=2.2.0"></script>
<?php endif; ?>
