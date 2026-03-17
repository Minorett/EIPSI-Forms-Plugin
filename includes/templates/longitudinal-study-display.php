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

    foreach ( $waves as $wave ) {
        if ( empty( $wave_status[ $wave['id'] ] ) || $wave_status[ $wave['id'] ] !== 'submitted' ) {
            if ( null === $next_wave ) {
                $next_wave = $wave;
            }
        } else {
            $completed_waves++;
        }
    }

    $participant_progress = $total_waves > 0
        ? (int) round( ( $completed_waves / $total_waves ) * 100 )
        : 0;
}

// CSS classes
$container_class = 'eipsi-longitudinal-study eipsi-theme-' . esc_attr( $theme );
$status_class    = 'status-' . esc_attr( $study->status );
$view_class      = 'view-' . esc_attr( $view_mode );
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
                    <?php if ( $magic_link_login ) : ?>
                        <h3 class="welcome-title">✨ ¡Bienvenido/a!</h3>
                        <p class="welcome-subtitle">Accediste correctamente a tu estudio</p>
                    <?php else : ?>
                        <h3 class="welcome-title">👋 ¡Hola de nuevo!</h3>
                        <p class="welcome-subtitle">Tu progreso en este estudio</p>
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
                        <div class="next-action-card">
                            <div class="wave-info">
                                <span class="wave-badge">T<?php echo esc_html( $next_wave['wave_index'] ); ?></span>
                                <strong class="wave-name"><?php echo esc_html( $next_wave['name'] ); ?></strong>
                            </div>
                            <form action="" method="get">
                                <input type="hidden" name="form_id" value="<?php echo esc_attr( $next_wave['form_id'] ); ?>">
                                <input type="hidden" name="wave_id" value="<?php echo esc_attr( $next_wave['id'] ); ?>">
                                <button type="submit" class="button button-primary button-large">
                                    <?php esc_html_e( 'Comenzar toma →', 'eipsi-forms' ); ?>
                                </button>
                            </form>
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

            <div class="eipsi-study-hero">
                <h2 class="hero-title">📊 <?php echo esc_html( $study->study_name ); ?></h2>
                <?php if ( ! empty( $study->description ) ) : ?>
                    <p class="hero-description"><?php echo esc_html( wp_trim_words( $study->description, 30 ) ); ?></p>
                <?php endif; ?>
                <p class="hero-subtitle"><?php esc_html_e( 'Ayudá a la ciencia clínica completando este estudio', 'eipsi-forms' ); ?></p>
            </div>

            <div id="login-section" class="eipsi-login-section">
                <?php if ( function_exists( 'eipsi_render_survey_login_form' ) ) : ?>
                    <?php echo eipsi_render_survey_login_form( array(
                        'survey_id'    => $study_id_for_query,
                        'redirect_url' => get_permalink(),
                    ) ); ?>
                <?php else : ?>
                    <div class="eipsi-login-fallback">
                        <h3><?php esc_html_e( 'Acceso al Estudio', 'eipsi-forms' ); ?></h3>
                        <p><?php esc_html_e( 'Para participar en este estudio, necesitás iniciar sesión o registrarte.', 'eipsi-forms' ); ?></p>
                        <p>
                            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="button button-primary">
                                <?php esc_html_e( 'Iniciar Sesión', 'eipsi-forms' ); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <?php // FIX (v2.1.0): description shown only once (in hero above as excerpt,
            // and in full here). Removed the duplicate block that appeared lower in the
            // original template for view_mode === 'public'. ?>
            <div id="study-info" class="eipsi-study-info-section">
                <?php if ( ! empty( $study->description ) ) : ?>
                    <div class="eipsi-study-description-section">
                        <h3 class="section-title">📋 <?php esc_html_e( 'Sobre este estudio', 'eipsi-forms' ); ?></h3>
                        <div class="description-content">
                            <?php echo wp_kses_post( wpautop( $study->description ) ); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $pi_name ) ) : ?>
                    <div class="eipsi-pi-info">
                        <span class="pi-label">🔬 <?php esc_html_e( 'Investigador Principal:', 'eipsi-forms' ); ?></span>
                        <span class="pi-name"><?php echo esc_html( $pi_name ); ?></span>
                    </div>
                <?php endif; ?>
            </div>

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
