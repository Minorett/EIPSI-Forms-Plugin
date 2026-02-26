<?php
/**
 * EIPSI Forms - Longitudinal Randomization Pools
 * Admin UI for managing longitudinal pools and probabilities.
 *
 * @package EIPSI_Forms
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render admin page for longitudinal pools.
 */
function eipsi_display_longitudinal_pools_page() {
    if ( ! function_exists( 'eipsi_user_can_manage_longitudinal' ) || ! eipsi_user_can_manage_longitudinal() ) {
        wp_die( esc_html__( 'Unauthorized', 'eipsi-forms' ) );
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $studies_table = $wpdb->prefix . 'survey_studies';

    $messages = array();
    $errors = array();

    $active_pool_id = isset( $_GET['pool_id'] ) ? absint( $_GET['pool_id'] ) : 0;

    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && $active_pool_id ) {
        $delete_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( wp_verify_nonce( $delete_nonce, 'eipsi_delete_longitudinal_pool_' . $active_pool_id ) ) {
            $deleted = $wpdb->delete(
                $table_name,
                array( 'id' => $active_pool_id ),
                array( '%d' )
            );

            if ( false !== $deleted ) {
                $messages[] = __( 'Pool eliminado correctamente.', 'eipsi-forms' );
                $active_pool_id = 0;
            } else {
                $errors[] = __( 'No se pudo eliminar el pool. Intenta nuevamente.', 'eipsi-forms' );
            }
        } else {
            $errors[] = __( 'Token inválido para eliminar el pool.', 'eipsi-forms' );
        }
    }

    if ( isset( $_POST['eipsi_longitudinal_pools_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eipsi_longitudinal_pools_nonce'] ) ), 'eipsi_save_longitudinal_pool' ) ) {
        $pool_id = isset( $_POST['pool_id'] ) ? absint( $_POST['pool_id'] ) : 0;
        $pool_name = sanitize_text_field( wp_unslash( $_POST['pool_name'] ?? '' ) );
        $pool_description = sanitize_textarea_field( wp_unslash( $_POST['pool_description'] ?? '' ) );
        $method = sanitize_text_field( wp_unslash( $_POST['method'] ?? 'seeded' ) );
        $selected_studies = isset( $_POST['studies'] ) ? array_map( 'absint', (array) $_POST['studies'] ) : array();
        $input_probabilities = isset( $_POST['probabilities'] ) ? (array) $_POST['probabilities'] : array();

        $allowed_methods = array( 'seeded', 'pure-random' );
        if ( ! in_array( $method, $allowed_methods, true ) ) {
            $method = 'seeded';
        }

        if ( empty( $pool_name ) ) {
            $errors[] = __( 'El nombre del pool es obligatorio.', 'eipsi-forms' );
        }

        if ( empty( $selected_studies ) ) {
            $errors[] = __( 'Selecciona al menos un estudio longitudinal.', 'eipsi-forms' );
        }

        $probabilities = array();
        $total_probability = 0;
        foreach ( $selected_studies as $study_id ) {
            $probability_value = isset( $input_probabilities[ $study_id ] ) ? (float) $input_probabilities[ $study_id ] : 0;
            $probabilities[ $study_id ] = $probability_value;
            $total_probability += $probability_value;
        }

        if ( ! empty( $selected_studies ) && abs( 100 - $total_probability ) > 0.01 ) {
            $errors[] = __( 'Las probabilidades deben sumar 100%.', 'eipsi-forms' );
        }

        if ( empty( $errors ) ) {
            $data = array(
                'pool_name' => $pool_name,
                'pool_description' => $pool_description,
                'studies' => wp_json_encode( array_values( $selected_studies ) ),
                'probabilities' => wp_json_encode( $probabilities ),
                'method' => $method,
                'updated_at' => current_time( 'mysql' ),
            );
            $formats = array( '%s', '%s', '%s', '%s', '%s', '%s' );

            if ( $pool_id ) {
                $existing_status = $wpdb->get_var(
                    $wpdb->prepare( "SELECT status FROM {$table_name} WHERE id = %d", $pool_id )
                );
                $data['status'] = $existing_status ? $existing_status : 'active';
                $formats[] = '%s';

                $updated = $wpdb->update(
                    $table_name,
                    $data,
                    array( 'id' => $pool_id ),
                    $formats,
                    array( '%d' )
                );

                if ( false !== $updated ) {
                    $messages[] = __( 'Pool actualizado correctamente.', 'eipsi-forms' );
                    $active_pool_id = $pool_id;
                } else {
                    $errors[] = __( 'No se pudo actualizar el pool. Intenta nuevamente.', 'eipsi-forms' );
                }
            } else {
                $data['status'] = 'active';
                $data['created_at'] = current_time( 'mysql' );
                $formats[] = '%s';
                $formats[] = '%s';

                $inserted = $wpdb->insert( $table_name, $data, $formats );
                if ( $inserted ) {
                    $messages[] = __( 'Pool creado correctamente.', 'eipsi-forms' );
                    $active_pool_id = (int) $wpdb->insert_id;
                } else {
                    $errors[] = __( 'No se pudo crear el pool. Intenta nuevamente.', 'eipsi-forms' );
                }
            }
        }
    }

    $active_pool = null;
    if ( $active_pool_id ) {
        $active_pool = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $active_pool_id ),
            ARRAY_A
        );
    }

    $pools = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at DESC", ARRAY_A );
    $studies = $wpdb->get_results( "SELECT id, study_name, study_code, status FROM {$studies_table} ORDER BY created_at DESC" );

    $selected_studies = $active_pool ? json_decode( $active_pool['studies'], true ) : array();
    $selected_probabilities = $active_pool ? json_decode( $active_pool['probabilities'], true ) : array();

    if ( ! is_array( $selected_studies ) ) {
        $selected_studies = array();
    }

    if ( ! is_array( $selected_probabilities ) ) {
        $selected_probabilities = array();
    }

    ?>
    <div class="wrap eipsi-longitudinal-pools">
        <h1><?php esc_html_e( 'Longitudinal Pools', 'eipsi-forms' ); ?></h1>

        <?php foreach ( $messages as $message ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endforeach; ?>

        <?php foreach ( $errors as $error ) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html( $error ); ?></p>
            </div>
        <?php endforeach; ?>

        <div class="eipsi-pools-grid">
            <section class="eipsi-pools-list">
                <div class="eipsi-section-header">
                    <h2><?php esc_html_e( 'Pools existentes', 'eipsi-forms' ); ?></h2>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=eipsi-longitudinal-pools' ) ); ?>" class="button button-secondary">
                        <?php esc_html_e( 'Nuevo pool', 'eipsi-forms' ); ?>
                    </a>
                </div>

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Nombre del pool', 'eipsi-forms' ); ?></th>
                            <th><?php esc_html_e( 'Estudios', 'eipsi-forms' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'eipsi-forms' ); ?></th>
                            <th><?php esc_html_e( 'Acciones', 'eipsi-forms' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $pools ) ) : ?>
                            <tr>
                                <td colspan="4"><?php esc_html_e( 'Todavía no hay pools creados.', 'eipsi-forms' ); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ( $pools as $pool ) : ?>
                                <?php
                                $pool_studies = json_decode( $pool['studies'], true );
                                $study_count = is_array( $pool_studies ) ? count( $pool_studies ) : 0;
                                $edit_url = add_query_arg(
                                    array(
                                        'page' => 'eipsi-longitudinal-pools',
                                        'pool_id' => (int) $pool['id'],
                                    ),
                                    admin_url( 'admin.php' )
                                );
                                $delete_url = wp_nonce_url(
                                    add_query_arg(
                                        array(
                                            'page' => 'eipsi-longitudinal-pools',
                                            'action' => 'delete',
                                            'pool_id' => (int) $pool['id'],
                                        ),
                                        admin_url( 'admin.php' )
                                    ),
                                    'eipsi_delete_longitudinal_pool_' . (int) $pool['id']
                                );
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $pool['pool_name'] ); ?></strong></td>
                                    <td><?php echo esc_html( $study_count ); ?></td>
                                    <td>
                                        <span class="eipsi-status-badge status-<?php echo esc_attr( $pool['status'] ); ?>">
                                            <?php echo esc_html( ucfirst( $pool['status'] ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>">
                                            <?php esc_html_e( 'Editar', 'eipsi-forms' ); ?>
                                        </a>
                                        <a class="button button-small button-link-delete" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_attr__( '¿Eliminar este pool? Esta acción no se puede deshacer.', 'eipsi-forms' ); ?>');">
                                            <?php esc_html_e( 'Eliminar', 'eipsi-forms' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="eipsi-pools-form">
                <h2>
                    <?php echo $active_pool ? esc_html__( 'Editar pool', 'eipsi-forms' ) : esc_html__( 'Crear nuevo pool', 'eipsi-forms' ); ?>
                </h2>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=eipsi-longitudinal-pools' ) ); ?>">
                    <?php wp_nonce_field( 'eipsi_save_longitudinal_pool', 'eipsi_longitudinal_pools_nonce' ); ?>
                    <input type="hidden" name="pool_id" value="<?php echo esc_attr( $active_pool_id ); ?>">

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="pool_name"><?php esc_html_e( 'Nombre del pool', 'eipsi-forms' ); ?></label></th>
                                <td>
                                    <input type="text" class="regular-text" id="pool_name" name="pool_name" value="<?php echo esc_attr( $active_pool['pool_name'] ?? '' ); ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pool_description"><?php esc_html_e( 'Descripción', 'eipsi-forms' ); ?></label></th>
                                <td>
                                    <textarea class="large-text" rows="3" id="pool_description" name="pool_description"><?php echo esc_textarea( $active_pool['pool_description'] ?? '' ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="method"><?php esc_html_e( 'Método', 'eipsi-forms' ); ?></label></th>
                                <td>
                                    <?php $selected_method = $active_pool['method'] ?? 'seeded'; ?>
                                    <select id="method" name="method">
                                        <option value="seeded" <?php selected( $selected_method, 'seeded' ); ?>><?php esc_html_e( 'Seeded (mismo participante = misma asignación)', 'eipsi-forms' ); ?></option>
                                        <option value="pure-random" <?php selected( $selected_method, 'pure-random' ); ?>><?php esc_html_e( 'Pure-random (cada acceso es nuevo)', 'eipsi-forms' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h3><?php esc_html_e( 'Estudios y probabilidades', 'eipsi-forms' ); ?></h3>

                    <?php if ( empty( $studies ) ) : ?>
                        <p><?php esc_html_e( 'No hay estudios longitudinales disponibles. Crea un estudio antes de armar un pool.', 'eipsi-forms' ); ?></p>
                    <?php else : ?>
                        <table class="widefat fixed">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Seleccionar', 'eipsi-forms' ); ?></th>
                                    <th><?php esc_html_e( 'Estudio', 'eipsi-forms' ); ?></th>
                                    <th><?php esc_html_e( 'Código', 'eipsi-forms' ); ?></th>
                                    <th><?php esc_html_e( 'Probabilidad (%)', 'eipsi-forms' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $studies as $study ) : ?>
                                    <?php
                                    $is_selected = in_array( (int) $study->id, $selected_studies, true );
                                    $probability_value = isset( $selected_probabilities[ $study->id ] ) ? (float) $selected_probabilities[ $study->id ] : '';
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="studies[]" value="<?php echo esc_attr( $study->id ); ?>" <?php checked( $is_selected ); ?>>
                                        </td>
                                        <td><?php echo esc_html( $study->study_name ); ?></td>
                                        <td><code><?php echo esc_html( $study->study_code ); ?></code></td>
                                        <td>
                                            <input type="number" name="probabilities[<?php echo esc_attr( $study->id ); ?>]" value="<?php echo esc_attr( $probability_value ); ?>" min="0" max="100" step="0.01">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="description">
                            <?php esc_html_e( 'Recordá que la suma de probabilidades debe ser 100%.', 'eipsi-forms' ); ?>
                        </p>
                    <?php endif; ?>

                    <p>
                        <button type="submit" class="button button-primary">
                            <?php echo $active_pool ? esc_html__( 'Guardar cambios', 'eipsi-forms' ) : esc_html__( 'Crear pool', 'eipsi-forms' ); ?>
                        </button>
                    </p>
                </form>

                <?php if ( $active_pool_id && class_exists( 'EIPSI_Pool_Assignment_Service' ) ) :
                    $assignment_svc = new EIPSI_Pool_Assignment_Service();
                    $pool_stats     = $assignment_svc->get_pool_stats( $active_pool_id );
                    $studies_map    = array();
                    foreach ( $studies as $s ) {
                        $studies_map[ (int) $s->id ] = $s->study_name;
                    }
                ?>
                    <div class="eipsi-pool-stats-box">
                        <h3><?php esc_html_e( 'Asignaciones actuales', 'eipsi-forms' ); ?></h3>

                        <?php if ( 0 === $pool_stats['total'] ) : ?>
                            <p class="description"><?php esc_html_e( 'Todavía no hay participantes asignados a este pool.', 'eipsi-forms' ); ?></p>
                        <?php else : ?>
                            <p class="eipsi-pool-stats-total">
                                <?php
                                printf(
                                    /* translators: %d: number of participants */
                                    esc_html( _n( '%d participante asignado', '%d participantes asignados', $pool_stats['total'], 'eipsi-forms' ) ),
                                    (int) $pool_stats['total']
                                );
                                ?>
                            </p>
                            <table class="widefat fixed striped eipsi-pool-stats-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Estudio', 'eipsi-forms' ); ?></th>
                                        <th style="width:80px;"><?php esc_html_e( 'N', 'eipsi-forms' ); ?></th>
                                        <th style="width:100px;"><?php esc_html_e( '%', 'eipsi-forms' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $pool_stats['by_study'] as $s_id => $count ) :
                                        $s_name  = isset( $studies_map[ $s_id ] ) ? $studies_map[ $s_id ] : sprintf( __( 'Estudio #%d', 'eipsi-forms' ), $s_id );
                                        $percent = $pool_stats['total'] > 0 ? round( ( $count / $pool_stats['total'] ) * 100, 1 ) : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html( $s_name ); ?></td>
                                            <td><?php echo esc_html( $count ); ?></td>
                                            <td><?php echo esc_html( $percent ); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <div class="eipsi-pool-shortcode-preview">
                            <h4><?php esc_html_e( 'Shortcode de acceso público', 'eipsi-forms' ); ?></h4>
                            <p class="description"><?php esc_html_e( 'Pegá este shortcode en cualquier página para que los participantes se unan al pool:', 'eipsi-forms' ); ?></p>
                            <code class="eipsi-pool-shortcode-code">[eipsi_pool_join pool_id="<?php echo esc_attr( $active_pool_id ); ?>"]</code>
                            <p class="description">
                                <?php esc_html_e( 'Con nombre:', 'eipsi-forms' ); ?>
                                <code>[eipsi_pool_join pool_id="<?php echo esc_attr( $active_pool_id ); ?>" show_name="1"]</code>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </section>
        </div>
    </div>

    <style>
        .eipsi-longitudinal-pools {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }

        .eipsi-pools-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
            gap: 24px;
            margin-top: 20px;
        }

        .eipsi-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .eipsi-pools-form {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .eipsi-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .eipsi-status-badge.status-active {
            background: #dcfce7;
            color: #166534;
        }

        .eipsi-status-badge.status-inactive {
            background: #fef3c7;
            color: #92400e;
        }

        @media (max-width: 1200px) {
            .eipsi-pools-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Stats box */
        .eipsi-pool-stats-box {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .eipsi-pool-stats-box h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .eipsi-pool-stats-total {
            font-size: 13px;
            color: #475569;
            margin-bottom: 10px;
        }

        .eipsi-pool-stats-table {
            font-size: 13px;
        }

        /* Shortcode preview */
        .eipsi-pool-shortcode-preview {
            margin-top: 20px;
            padding: 14px 16px;
            background: #f1f5f9;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .eipsi-pool-shortcode-preview h4 {
            margin: 0 0 6px;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        .eipsi-pool-shortcode-code {
            display: block;
            background: #1e293b;
            color: #a5f3fc;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin: 8px 0 4px;
            word-break: break-all;
            user-select: all;
            cursor: text;
        }
    </style>
    <?php
}
