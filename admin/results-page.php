<?php
if (!defined('ABSPATH')) {
    exit;
}

function vas_display_form_responses() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // Obtener lista de formularios únicos
    $forms = $wpdb->get_col("SELECT DISTINCT form_name FROM $table_name ORDER BY form_name");
    
    // Filtro actual
    $current_form = isset($_GET['form_filter']) ? sanitize_text_field($_GET['form_filter']) : '';
    
    // NUEVO: Determinar si mostrar columna Form
    $show_form_column = empty($current_form);
    
    // Construir query con filtro
    $where = $current_form ? $wpdb->prepare("WHERE form_name = %s", $current_form) : '';
    $results = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC");
    
    // NUEVO: Calcular colspan dinámico (Form ID, Participant ID, Date, Time, Duration, Device, Browser, Actions)
    $colspan = $show_form_column ? 8 : 7;
    
    ?>
    <div class="wrap">
        <h1>Form Responses</h1>
        
        <?php
        // Display admin notices for delete actions
        if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Response deleted successfully.', 'vas-dinamico-forms'); ?></p>
            </div>
            <?php
        }
        
        if (isset($_GET['error'])) {
            $error_message = '';
            switch ($_GET['error']) {
                case 'permission':
                    $error_message = __('You do not have sufficient permissions to perform this action.', 'vas-dinamico-forms');
                    break;
                case 'invalid':
                    $error_message = __('Invalid request. Please try again.', 'vas-dinamico-forms');
                    break;
                case 'nonce':
                    $error_message = __('Security check failed. Please refresh the page and try again.', 'vas-dinamico-forms');
                    break;
                case 'delete':
                    $error_message = __('Failed to delete response. The record may not exist.', 'vas-dinamico-forms');
                    break;
                default:
                    $error_message = __('An error occurred. Please try again.', 'vas-dinamico-forms');
            }
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
            <?php
        }
        ?>
        
        <!-- Notice about metadata-only view -->
        <?php if (!empty($current_form)): ?>
        <div class="notice notice-info" style="margin: 20px 0;">
            <p><strong><?php _e('Active Filter:', 'vas-dinamico-forms'); ?></strong> <?php echo esc_html($current_form); ?> | 
            <a href="<?php echo esc_url(admin_url('admin.php?page=vas-dinamico-results')); ?>"><?php _e('View All Forms', 'vas-dinamico-forms'); ?></a></p>
        </div>
        <?php endif; ?>
        
        <div class="notice notice-info" style="margin: 20px 0;">
            <p><strong><?php _e('Privacy Notice:', 'vas-dinamico-forms'); ?></strong> <?php _e('This table displays session metadata only. Complete responses with questionnaire answers are available via CSV/Excel export below.', 'vas-dinamico-forms'); ?></p>
        </div>
        
        <!-- Filtro por formulario -->
        <div class="vas-form-filter" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px;">
            <form method="get">
                <input type="hidden" name="page" value="vas-dinamico-results">
                <label for="form_filter" style="font-weight: bold; margin-right: 10px;">Filter by Form:</label>
                <select name="form_filter" id="form_filter" onchange="this.form.submit()" style="padding: 8px; min-width: 200px;">
                    <option value="">All Forms</option>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?php echo esc_attr($form); ?>" <?php selected($current_form, $form); ?>>
                            <?php echo esc_html($form); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Botones de exportación -->
        <div class="vas-export-buttons" style="margin: 20px 0;">
            <?php
            $export_params = $current_form ? ['form_name' => $current_form] : [];
            $csv_url = add_query_arg(array_merge(['action' => 'export_csv'], $export_params));
            $excel_url = add_query_arg(array_merge(['action' => 'export_excel'], $export_params));
            ?>
            <a href="<?php echo esc_url($csv_url); ?>" class="button" style="margin-right: 10px;">
                📥 Download CSV
            </a>
            <a href="<?php echo esc_url($excel_url); ?>" class="button button-primary">
                📊 Download Excel
            </a>
        </div>

        <!-- Tabla de respuestas -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <?php if ($show_form_column): ?>
                    <th style="width: 10%;">Form ID</th>
                    <?php endif; ?>
                    <th style="width: 12%;">Participant ID</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 10%;">Time</th>
                    <th style="width: 10%;">Duration (s)</th>
                    <th style="width: 10%;">Device</th>
                    <th style="width: 12%;">Browser</th>
                    <th style="width: 14%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr>
                        <td colspan="<?php echo $colspan; ?>" style="text-align: center; padding: 20px;">
                            No responses found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $timezone_string = get_option('timezone_string');
                    $gmt_offset = get_option('gmt_offset');
                    
                    foreach ($results as $row): 
                        // Format date and time using WordPress timezone
                        $date_obj = new DateTime($row->created_at, new DateTimeZone('UTC'));
                        if ($timezone_string) {
                            $date_obj->setTimezone(new DateTimeZone($timezone_string));
                        } elseif ($gmt_offset) {
                            $offset_string = sprintf('%+03d:%02d', floor($gmt_offset), abs($gmt_offset * 60) % 60);
                            $date_obj->setTimezone(new DateTimeZone($offset_string));
                        }
                        
                        $date_formatted = $date_obj->format('Y-m-d');
                        $time_formatted = $date_obj->format('H:i:s');
                        
                        // Use duration_seconds for precision, fall back to duration if not available
                        $duration_display = !empty($row->duration_seconds) 
                            ? number_format($row->duration_seconds, 3) 
                            : number_format($row->duration, 0);
                        
                        // Ensure form_id and participant_id have fallbacks
                        $form_id_display = !empty($row->form_id) ? $row->form_id : 'N/A';
                        $participant_id_display = !empty($row->participant_id) ? $row->participant_id : 'N/A';
                    ?>
                        <tr>
                            <?php if ($show_form_column): ?>
                            <td><strong><?php echo esc_html($form_id_display); ?></strong></td>
                            <?php endif; ?>
                            <td><?php echo esc_html($participant_id_display); ?></td>
                            <td><?php echo esc_html($date_formatted); ?></td>
                            <td><?php echo esc_html($time_formatted); ?></td>
                            <td><?php echo esc_html($duration_display); ?></td>
                            <td><?php echo esc_html($row->device); ?></td>
                            <td><?php echo esc_html($row->browser); ?></td>
                            <td>
                                <div class="vas-action-buttons">
                                    <a href="#" class="button button-small vas-view-response" 
                                       data-id="<?php echo esc_attr($row->id); ?>"
                                       style="text-decoration: none; margin: 2px; padding: 6px 8px; font-size: 12px; background: #2271b1; color: white; border: none; border-radius: 3px;"
                                       title="View response details">
                                        👁️
                                    </a>
                                    
                                    <a href="<?php echo esc_url(wp_nonce_url(
                                        add_query_arg(['action' => 'delete', 'id' => $row->id]), 
                                        'delete_response_' . $row->id
                                    )); ?>" 
                                       class="button button-small vas-delete-response" 
                                       style="text-decoration: none; margin: 2px; padding: 6px 8px; font-size: 12px; background: #d63638; color: white; border: none; border-radius: 3px;"
                                       onclick="return confirm('Are you sure you want to delete this response?')"
                                       title="Delete response">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para View Metadata -->
    <div id="vas-response-modal" style="display:none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: hidden;">
        <div class="vas-modal-content" style="background-color: #fefefe; margin: 2% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 800px; max-height: 85vh; border-radius: 5px; position: relative; display: flex; flex-direction: column;">
            <div style="flex-shrink: 0;">
                <span class="vas-close-modal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; position: absolute; right: 15px; top: 10px; z-index: 10;">&times;</span>
                <h3 style="margin-right: 40px;">Session Metadata</h3>
                <p style="color: #666; font-size: 13px; margin: 5px 0 15px 0;">Complete responses with questionnaire answers are available via CSV/Excel export.</p>
            </div>
            <div id="vas-modal-body" style="flex-grow: 1; overflow-y: auto; padding: 10px 0;"></div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Abrir modal para View - CONEXIÓN REAL CON AJAX
        $('.vas-view-response').on('click', function(e) {
            e.preventDefault();
            var responseId = $(this).data('id');
            $('#vas-modal-body').html('<div style="text-align: center; padding: 40px;"><p>Loading response details...</p></div>');
            $('#vas-response-modal').show();
            
            // Llamada AJAX REAL a tu función existente en ajax-handlers.php
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'eipsi_get_response_details',
                    id: responseId,
                    nonce: '<?php echo wp_create_nonce('eipsi_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#vas-modal-body').html(response.data);
                        
                        // TOGGLE PARA CONTEXTO DE INVESTIGACIÓN
                        $('#toggle-research-context').on('click', function() {
                            var section = $('#research-context-section');
                            if (section.is(':visible')) {
                                section.hide();
                                $(this).html('🧠 Mostrar Contexto de Investigación');
                                $(this).css('background', '#2271b1');
                            } else {
                                section.show();
                                $(this).html('🧠 Ocultar Contexto de Investigación');
                                $(this).css('background', '#135e96');
                            }
                        });
                        
                    } else {
                        $('#vas-modal-body').html(
                            '<div style="text-align: center; padding: 20px; color: #d63638;">' +
                            '<h3>Error</h3>' +
                            '<p>' + (response.data || 'Could not load response details') + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $('#vas-modal-body').html(
                        '<div style="text-align: center; padding: 20px; color: #d63638;">' +
                        '<h3>Connection Error</h3>' +
                        '<p>Please try again or check the console for details.</p>' +
                        '<p><small>Technical: ' + error + '</small></p>' +
                        '</div>'
                    );
                    console.error('AJAX Error:', error);
                }
            });
        });
    
        // Cerrar modal
        $('.vas-close-modal').on('click', function() {
            $('#vas-response-modal').hide();
        });
    
        // Cerrar modal al hacer click fuera
        $(window).on('click', function(e) {
            if ($(e.target).is('#vas-response-modal')) {
                $('#vas-response-modal').hide();
            }
        });
    });
    </script>
    <?php
}