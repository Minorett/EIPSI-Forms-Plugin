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
    
    // NUEVO: Calcular colspan dinámico
    $colspan = $show_form_column ? 7 : 6;
    
    ?>
    <div class="wrap">
        <h1>Form Responses</h1>
        
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
                    <th>ID</th>
                    <?php if ($show_form_column): ?>
                    <th>Form</th>
                    <?php endif; ?>
                    <th>Date</th>
                    <th>Duration (s)</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Actions</th>
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
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <?php if ($show_form_column): ?>
                            <td><strong><?php echo esc_html($row->form_name); ?></strong></td>
                            <?php endif; ?>
                            <td><?php echo esc_html($row->created_at); ?></td>
                            <td><?php echo esc_html($row->duration); ?></td>
                            <td><?php echo esc_html($row->ip_address); ?></td>
                            <td><?php echo esc_html($row->device); ?></td>
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

    <!-- Modal para View/Edit -->
    <div id="vas-response-modal" style="display:none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: hidden;">
        <div class="vas-modal-content" style="background-color: #fefefe; margin: 2% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 800px; max-height: 85vh; border-radius: 5px; position: relative; display: flex; flex-direction: column;">
            <div style="flex-shrink: 0;">
                <span class="vas-close-modal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; position: absolute; right: 15px; top: 10px; z-index: 10;">&times;</span>
                <h3 style="margin-right: 40px;">Response Details</h3>
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