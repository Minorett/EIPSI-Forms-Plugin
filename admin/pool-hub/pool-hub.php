<?php
/**
 * EIPSI Pool Hub - Unified interface for pool management
 *
 * Fase 1: Entry point with sidebar + tabbed interface
 * Combines Longitudinal Pools (config) + Pool Analytics (monitoring)
 *
 * @package EIPSI_Forms
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render Pool Hub page
 */
function eipsi_render_pool_hub() {
    // Security check
    if ( ! function_exists( 'eipsi_user_can_manage_longitudinal' ) || ! eipsi_user_can_manage_longitudinal() ) {
        wp_die( esc_html__( 'Unauthorized', 'eipsi-forms' ) );
    }

    global $wpdb;

    // Get active pool
    $pool_id = isset( $_GET['pool_id'] ) ? absint( $_GET['pool_id'] ) : 0;
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'configuration';
    
    // Validate tab
    $allowed_tabs = array( 'configuration', 'participants', 'analytics', 'integration' );
    if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
        $active_tab = 'configuration';
    }

    // Get all pools for sidebar
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $pools = $wpdb->get_results( "SELECT id, pool_name, status FROM {$pools_table} ORDER BY created_at DESC", ARRAY_A );

    // If no pool selected but pools exist, select first one
    if ( ! $pool_id && ! empty( $pools ) ) {
        $pool_id = (int) $pools[0]['id'];
        $active_tab = 'configuration';
    }

    // Get active pool data
    $active_pool = null;
    if ( $pool_id ) {
        $active_pool = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$pools_table} WHERE id = %d", $pool_id ),
            ARRAY_A
        );
    }

    // Enqueue assets
    wp_enqueue_style( 'eipsi-pool-hub', EIPSI_FORMS_PLUGIN_URL . 'admin/pool-hub/assets/pool-hub.css', array(), EIPSI_FORMS_VERSION );
    wp_enqueue_script( 'eipsi-pool-hub', EIPSI_FORMS_PLUGIN_URL . 'admin/pool-hub/assets/pool-hub.js', array( 'jquery' ), EIPSI_FORMS_VERSION, true );
    
    wp_localize_script( 'eipsi-pool-hub', 'eipsiPoolHub', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'eipsi_pool_hub_nonce' ),
        'poolId'  => $pool_id,
        'activeTab' => $active_tab,
        'strings' => array(
            'confirmDelete' => __( '¿Eliminar este pool? Esta acción no se puede deshacer.', 'eipsi-forms' ),
            'confirmReassign' => __( '¿Re-asignar este participante a otro estudio?', 'eipsi-forms' ),
            'saving' => __( 'Guardando...', 'eipsi-forms' ),
            'saved' => __( 'Cambios guardados', 'eipsi-forms' ),
            'error' => __( 'Error al guardar', 'eipsi-forms' ),
        ),
    ) );

    // Tab definitions
    $tabs = array(
        'configuration' => array(
            'label' => __( 'Configuración', 'eipsi-forms' ),
            'icon' => 'dashicons-admin-generic',
            'file' => 'tab-configuration.php',
        ),
        'participants' => array(
            'label' => __( 'Participantes', 'eipsi-forms' ),
            'icon' => 'dashicons-groups',
            'file' => 'tab-participants.php',
        ),
        'analytics' => array(
            'label' => __( 'Analytics', 'eipsi-forms' ),
            'icon' => 'dashicons-chart-area',
            'file' => 'tab-analytics.php',
        ),
        'integration' => array(
            'label' => __( 'Integración', 'eipsi-forms' ),
            'icon' => 'dashicons-rest-api',
            'file' => 'tab-integration.php',
        ),
    );
    ?>

    <div class="wrap eipsi-pool-hub">
        <!-- Header -->
        <div class="eipsi-pool-hub-header">
            <h1><?php esc_html_e( '🏊 Pool Hub', 'eipsi-forms' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Gestión y monitoreo unificado de pools longitudinales', 'eipsi-forms' ); ?>
            </p>
        </div>

        <div class="eipsi-pool-hub-layout">
            <!-- Sidebar: Pool Selector -->
            <?php require_once __DIR__ . '/components/pool-sidebar.php'; ?>

            <!-- Main Content -->
            <div class="eipsi-pool-hub-main">
                <?php if ( ! $pool_id ) : ?>
                    <!-- Empty state -->
                    <div class="eipsi-pool-empty-state">
                        <div class="eipsi-empty-icon">🏊</div>
                        <h2><?php esc_html_e( 'No hay pools creados', 'eipsi-forms' ); ?></h2>
                        <p><?php esc_html_e( 'Creá tu primer pool longitudinal para empezar a asignar participantes.', 'eipsi-forms' ); ?></p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=eipsi-pool-hub&pool_id=new' ) ); ?>" class="button button-primary">
                            <?php esc_html_e( 'Crear primer pool', 'eipsi-forms' ); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <!-- Pool Header -->
                    <div class="eipsi-pool-main-header">
                        <div class="eipsi-pool-title-section">
                            <h2><?php echo esc_html( $active_pool['pool_name'] ?? '' ); ?></h2>
                            <span class="eipsi-pool-status eipsi-status--<?php echo esc_attr( $active_pool['status'] ?? 'inactive' ); ?>">
                                <?php echo esc_html( ucfirst( $active_pool['status'] ?? __( 'inactivo', 'eipsi-forms' ) ) ); ?>
                            </span>
                        </div>
                        <div class="eipsi-pool-actions">
                            <button type="button" class="button button-secondary" id="eipsi-pool-duplicate">
                                <?php esc_html_e( 'Duplicar', 'eipsi-forms' ); ?>
                            </button>
                            <?php if ( ! empty( $active_pool['status'] ) && 'active' === $active_pool['status'] ) : ?>
                                <button type="button" class="button button-secondary" id="eipsi-pool-pause">
                                    <?php esc_html_e( 'Pausar', 'eipsi-forms' ); ?>
                                </button>
                            <?php else : ?>
                                <button type="button" class="button button-primary" id="eipsi-pool-activate">
                                    <?php esc_html_e( 'Activar', 'eipsi-forms' ); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <nav class="eipsi-pool-tabs-nav">
                        <ul class="eipsi-pool-tabs-list">
                            <?php foreach ( $tabs as $tab_id => $tab ) : ?>
                                <li class="eipsi-pool-tab-item <?php echo $active_tab === $tab_id ? 'is-active' : ''; ?>"
                                    data-tab="<?php echo esc_attr( $tab_id ); ?>">
                                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'eipsi-pool-hub', 'pool_id' => $pool_id, 'tab' => $tab_id ), admin_url( 'admin.php' ) ) ); ?>">
                                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                                        <?php echo esc_html( $tab['label'] ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>

                    <!-- Tab Content -->
                    <div class="eipsi-pool-tab-content" data-active-tab="<?php echo esc_attr( $active_tab ); ?>">
                        <?php
                        $tab_file = __DIR__ . '/tabs/' . $tabs[ $active_tab ]['file'];
                        if ( file_exists( $tab_file ) ) {
                            require_once $tab_file;
                        } else {
                            echo '<div class="notice notice-error"><p>' . esc_html__( 'Tab no encontrado.', 'eipsi-forms' ) . '</p></div>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        /* Fase 1: Layout Base - EIPSI Design System */
        .eipsi-pool-hub {
            --eipsi-sidebar-width: 280px;
            --eipsi-header-height: 60px;
            --eipsi-blue: #3B6CAA;
            --eipsi-blue-dark: #1E3A5F;
            --eipsi-teal: #008080;
            --eipsi-gray-100: #f8fafc;
            --eipsi-gray-200: #e2e8f0;
            --eipsi-gray-600: #64748b;
            --eipsi-gray-800: #1e293b;
        }

        .eipsi-pool-hub-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--eipsi-gray-200);
        }

        .eipsi-pool-hub-header h1 {
            font-size: 22px;
            font-weight: 600;
            color: var(--eipsi-gray-800);
            margin: 0 0 5px;
        }

        .eipsi-pool-hub-header .description {
            color: var(--eipsi-gray-600);
            margin: 0;
        }

        .eipsi-pool-hub-layout {
            display: flex;
            gap: 0;
            min-height: calc(100vh - 200px);
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Sidebar styles en pool-sidebar.php */

        /* Main Content */
        .eipsi-pool-hub-main {
            flex: 1;
            padding: 24px;
            background: #fff;
            overflow-y: auto;
        }

        /* Pool Header */
        .eipsi-pool-main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--eipsi-gray-200);
        }

        .eipsi-pool-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .eipsi-pool-title-section h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--eipsi-gray-800);
            margin: 0;
        }

        .eipsi-pool-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .eipsi-pool-status.eipsi-status--active {
            background: #dcfce7;
            color: #166534;
        }

        .eipsi-pool-status.eipsi-status--inactive {
            background: #fef3c7;
            color: #92400e;
        }

        .eipsi-pool-status.eipsi-status--paused {
            background: #fee2e2;
            color: #991b1b;
        }

        .eipsi-pool-actions {
            display: flex;
            gap: 8px;
        }

        /* Tabs Navigation */
        .eipsi-pool-tabs-nav {
            margin-bottom: 24px;
            border-bottom: 1px solid var(--eipsi-gray-200);
        }

        .eipsi-pool-tabs-list {
            display: flex;
            gap: 0;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .eipsi-pool-tab-item {
            position: relative;
        }

        .eipsi-pool-tab-item a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            color: var(--eipsi-gray-600);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
        }

        .eipsi-pool-tab-item a:hover {
            color: var(--eipsi-blue);
        }

        .eipsi-pool-tab-item.is-active a {
            color: var(--eipsi-blue);
            border-bottom-color: var(--eipsi-blue);
        }

        .eipsi-pool-tab-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        /* Tab Content */
        .eipsi-pool-tab-content {
            min-height: 400px;
        }

        /* Empty State */
        .eipsi-pool-empty-state {
            text-align: center;
            padding: 80px 40px;
        }

        .eipsi-empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .eipsi-pool-empty-state h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--eipsi-gray-800);
            margin: 0 0 10px;
        }

        .eipsi-pool-empty-state p {
            color: var(--eipsi-gray-600);
            margin: 0 0 24px;
        }

        /* Responsive */
        @media (max-width: 960px) {
            .eipsi-pool-hub-layout {
                flex-direction: column;
            }

            .eipsi-pool-tabs-list {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>

    <?php
}
