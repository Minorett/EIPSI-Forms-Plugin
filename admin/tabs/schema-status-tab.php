
<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EIPSI Forms Schema Status Tab
 * Database schema monitoring and repair interface
 * 
 * @package EIPSI_Forms
 * @since 1.6.0
 */

// Ensure database schema manager is loaded
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-manager.php';

$schema_status = EIPSI_Database_Schema_Manager::get_schema_health_summary();
$all_tables = EIPSI_Database_Schema_Manager::get_all_tables_status();

// Table display names
$table_display_names = array(
    'vas_form_results' => 'VAS Form Results',
    'vas_form_events' => 'VAS Form Events',
    'eipsi_randomization_configs' => 'Randomization Configs',
    'eipsi_randomization_assignments' => 'Randomization Assignments',
    'survey_studies' => 'Survey Studies',
    'survey_participants' => 'Survey Participants',
    'survey_sessions' => 'Survey Sessions',
    'survey_waves' => 'Survey Waves',
    'survey_assignments' => 'Survey Assignments',
    'survey_magic_links' => 'Magic Links',
    'survey_email_log' => 'Email Log',
    'survey_audit_log' => 'Audit Log',
    'eipsi_longitudinal_pools' => 'Longitudinal Pools',
    'eipsi_pool_assignments' => 'Pool Assignments',
    'survey_participant_access_log' => 'Access Log',
    'eipsi_device_data' => 'Device Data'
);
// Collation checks are handled automatically - method removed to prevent fatal error
// $collation_issues = EIPSI_Database_Schema_Manager::check_collation_issues();
// $needs_collation_fix = $collation_issues['needs_fix'];
$needs_collation_fix = false;
?>

<div id="schema-status-tab" class="schema-status-tab-container">
    <h2>💾 DATABASE SCHEMA STATUS</h2>

    <!-- Refresh Controls - Simplified: Auto-fixes run silently -->
    <div class="monitoring-controls">
        <button id="refresh-schema" class="button button-primary">🔄 Refresh Now</button>
        <!-- Collation fixes are now handled automatically -->
        <label>
            <input type="checkbox" id="auto-refresh-schema">
            Auto-refresh every 30s
        </label>
        <button id="export-schema-report" class="button button-secondary">📥 Export Report</button>
        <span id="schema-last-update" class="monitoring-timestamp">
            Last update: <?php echo $schema_status['last_verified'] ? esc_html($schema_status['last_verified']) : 'Never'; ?>
        </span>
    </div>

    <!-- Auto-Fix Banner - Hidden: Issues are fixed automatically in background -->
    <div id="auto-fix-banner" class="auto-fix-banner" style="display: none !important; margin-bottom: 20px;">
        <!-- Banner suppressed - fixes run automatically -->
    </div>

    <!-- Schema Health Summary Card -->
    <div class="monitoring-card health-summary-card">
        <div class="card-header">
            <h3>📊 SCHEMA HEALTH SUMMARY</h3>
            <span class="status-indicator <?php 
                if ($schema_status['error_tables'] > 0) echo 'error';
                elseif ($schema_status['warning_tables'] > 0) echo 'warning';
                else echo 'ok';
            ?>">●</span>
        </div>
        <div class="card-body">
            <div class="stat-row">
                <span class="stat-label">Health Score:</span>
                <span class="stat-value <?php 
                    if ($schema_status['health_score'] >= 80) echo 'success';
                    elseif ($schema_status['health_score'] >= 50) echo 'warning';
                    else echo 'error';
                ?>"><?php echo esc_html($schema_status['health_score']); ?>%</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Tables:</span>
                <span class="stat-value"><?php echo esc_html($schema_status['healthy_tables']); ?> 
                    <span style="color: #4CAF50;">✓</span> / 
                    <?php echo esc_html($schema_status['warning_tables']); ?> 
                    <span style="color: #ff9800;">⚠</span> / 
                    <?php echo esc_html($schema_status['error_tables']); ?> 
                    <span style="color: #f44336;">✗</span>
                </span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Total Rows:</span>
                <span class="stat-value"><?php echo number_format($schema_status['total_rows']); ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Total Size:</span>
                <span class="stat-value"><?php echo number_format($schema_status['total_size_mb'], 2); ?> MB</span>
            </div>
            
            <!-- Issues list hidden by default - collations are handled automatically -->
            <?php if (!empty($schema_status['issues']) && defined('EIPSI_SHOW_SCHEMA_ISSUES')): ?>
            <div class="issues-list">
                <h4>⚠️ Issues Found (Developer Mode):</h4>
                <ul>
                    <?php foreach ($schema_status['issues'] as $table => $issues): ?>
                        <?php foreach ($issues as $issue): ?>
                        <li><strong><?php echo esc_html($table); ?>:</strong> <?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 🔒 Data Safety Status Card -->
    <?php
    // Load Data Safety System for monitoring
    global $wpdb;
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/data-safety-system.php';
    $safety_health = eipsi_safety_get_health_status();
    $emergency_table = $wpdb->prefix . 'eipsi_emergency_submissions';
    
    // Check if emergency table exists (suppress errors if not)
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$emergency_table}'") === $emergency_table;
    
    if ($table_exists) {
        $emergency_count = $wpdb->get_var("SELECT COUNT(*) FROM {$emergency_table}");
        $unresolved_count = $wpdb->get_var("SELECT COUNT(*) FROM {$emergency_table} WHERE resolved = 0");
    } else {
        $emergency_count = 0;
        $unresolved_count = 0;
    }
    
    $recent_empty = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vas_form_results 
         WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         AND (form_responses IS NULL OR form_responses = '[]' OR form_responses = '{}')"
    );
    $has_safety_issues = !$safety_health['healthy'] || $unresolved_count > 0 || $recent_empty > 0;
    ?>
    <div class="monitoring-card data-safety-card <?php echo $has_safety_issues ? 'has-issues' : ''; ?>">
        <div class="card-header">
            <h3>
                <?php if ($has_safety_issues): ?>
                    <span class="status-dot error">❌</span>
                <?php else: ?>
                    <span class="status-dot ok">✅</span>
                <?php endif; ?>
                🔒 DATA SAFETY STATUS
            </h3>
            <span class="status-indicator <?php echo $has_safety_issues ? 'error' : 'ok'; ?>">●</span>
        </div>
        <div class="card-body">
            <div class="stat-row">
                <span class="stat-label">Status:</span>
                <span class="stat-value <?php echo $has_safety_issues ? 'error' : 'success'; ?>">
                    <?php echo $has_safety_issues ? 'ATENCIÓN REQUERIDA' : 'TODOS LOS SISTEMAS OK'; ?>
                </span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Submissions (24h):</span>
                <span class="stat-value"><?php echo number_format($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vas_form_results WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")); ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Respuestas vacías (24h):</span>
                <span class="stat-value <?php echo $recent_empty > 0 ? 'error' : 'success'; ?>">
                    <?php echo intval($recent_empty); ?>
                    <?php if ($recent_empty > 0): ?>
                        <span title="Posible problema con field names en formularios">⚠️</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Emergencias pendientes:</span>
                <span class="stat-value <?php echo $unresolved_count > 0 ? 'error' : 'success'; ?>">
                    <?php echo intval($unresolved_count); ?>/<?php echo intval($emergency_count); ?>
                </span>
            </div>
            
            <?php if ($has_safety_issues): ?>
            <div class="issues-list" style="display: block !important;">
                <h4>⚠️ Problemas detectados:</h4>
                <ul>
                    <?php foreach ($safety_health['issues'] as $issue): ?>
                    <li><?php echo esc_html($issue); ?></li>
                    <?php endforeach; ?>
                    <?php if ($recent_empty > 0): ?>
                    <li>Hay submissions con respuestas vacías - revisar field names de formularios</li>
                    <?php endif; ?>
                    <?php if ($unresolved_count > 0): ?>
                    <li>Hay <?php echo $unresolved_count; ?> emergencia(s) sin resolver - revisar tabla wp_eipsi_emergency_submissions</li>
                    <?php endif; ?>
                </ul>
                <p style="margin-top: 10px; font-size: 12px;">
                    <strong>Solución:</strong> Si hay respuestas vacías, re-guardar los formularios (npm run build + Update en editor). 
                    Para emergencias, revisar logs con [EIPSI SAFETY] prefix.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table Cards Grid -->
    <div class="tables-grid">
        <?php foreach ($all_tables as $table_name => $table): ?>
        <div class="monitoring-card table-card" data-table="<?php echo esc_attr($table_name); ?>">
            <div class="card-header">
                <h3>
                    <?php if ($table['status'] === 'ok'): ?>
                        <span class="status-dot ok">✅</span>
                    <?php elseif ($table['status'] === 'warning'): ?>
                        <span class="status-dot warning">⚠️</span>
                    <?php else: ?>
                        <span class="status-dot error">❌</span>
                    <?php endif; ?>
                    <?php echo esc_html(isset($table_display_names[$table_name]) ? $table_display_names[$table_name] : $table_name); ?>
                </h3>
                <span class="table-name"><?php echo esc_html($table['full_table_name']); ?></span>
            </div>
            <div class="card-body">
                <div class="stat-row">
                    <span class="stat-label">Status:</span>
                    <span class="stat-value <?php echo $table['status']; ?>">
                        <?php if ($table['status'] === 'ok'): ?>OK
                        <?php elseif ($table['status'] === 'warning'): ?>Warning
                        <?php else: ?>Error<?php endif; ?>
                    </span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Rows:</span>
                    <span class="stat-value"><?php echo number_format($table['row_count']); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Columns:</span>
                    <span class="stat-value <?php echo empty($table['missing_columns']) ? 'success' : 'warning'; ?>">
                        <?php echo count($table['columns']); ?>/<?php echo count($table['required_columns']); ?>
                        <?php if (!empty($table['missing_columns'])): ?>
                            <span title="<?php echo esc_attr(implode(', ', $table['missing_columns'])); ?>">⚠️</span>
                        <?php else: ?>✅<?php endif; ?>
                    </span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Indexes:</span>
                    <span class="stat-value"><?php echo count($table['indexes']); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Size:</span>
                    <span class="stat-value"><?php echo number_format($table['size_mb'], 2); ?> MB</span>
                </div>
                
                <?php if (!empty($table['missing_columns'])): ?>
                <div class="missing-columns">
                    <strong>Missing:</strong> <?php echo esc_html(implode(', ', $table['missing_columns'])); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <?php if ($table['status'] !== 'ok'): ?>
                <button class="button button-small repair-table-btn" data-table="<?php echo esc_attr($table_name); ?>">
                    🔧 Repair Table
                </button>
                <?php endif; ?>
                <button class="button button-small toggle-details" data-table="<?php echo esc_attr($table_name); ?>">
                    📋 View Details
                </button>
            </div>
            
            <!-- Expandable Details -->
            <div class="table-details" id="details-<?php echo esc_attr($table_name); ?>" style="display: none;">
                <h4>Column Details:</h4>
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Type</th>
                            <th>Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        global $wpdb;
                        $full_table_name = $table['full_table_name'];
                        $column_types = $wpdb->get_results("SHOW COLUMNS FROM {$full_table_name}", ARRAY_A);
                        $column_type_map = array();
                        foreach ($column_types as $col) {
                            $column_type_map[$col['Field']] = $col['Type'];
                        }
                        
                        foreach ($table['required_columns'] as $col): 
                        ?>
                        <tr>
                            <td><?php echo esc_html($col); ?></td>
                            <td><?php echo isset($column_type_map[$col]) ? esc_html($column_type_map[$col]) : 'N/A'; ?></td>
                            <td>
                                <?php if (in_array($col, $table['columns'])): ?>
                                    <span style="color: #4CAF50;">✓</span>
                                <?php else: ?>
                                    <span style="color: #f44336;">✗</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h4>Indexes:</h4>
                <ul class="index-list">
                    <?php foreach ($table['indexes'] as $index): ?>
                    <li><?php echo esc_html($index); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo esc_js(wp_create_nonce('eipsi_admin_nonce')); ?>';
    let autoRefreshInterval = null;
    
    // Load schema status via AJAX
    function loadSchemaStatus() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_schema_status',
                nonce: nonce,
            },
            success: function(response) {
                if (response.success) {
                    updateSchemaUI(response.data);
                    $('#schema-last-update').text('Last update: ' + new Date().toLocaleTimeString());
                } else {
                    console.error('Schema status error:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }
    
    // Update UI with schema data
    function updateSchemaUI(data) {
        const summary = data.summary;
        
        // Update health summary
        $('.health-summary-card .stat-value').each(function() {
            const label = $(this).prev('.stat-label').text();
            if (label.includes('Score')) {
                $(this).text(summary.health_score + '%')
                    .attr('class', 'stat-value ' + (summary.health_score >= 80 ? 'success' : summary.health_score >= 50 ? 'warning' : 'error'));
            } else if (label.includes('Tables')) {
                $(this).html(summary.healthy_tables + ' <span style="color: #4CAF50;">✓</span> / ' + 
                           summary.warning_tables + ' <span style="color: #ff9800;">⚠</span> / ' + 
                           summary.error_tables + ' <span style="color: #f44336;">✗</span>');
            } else if (label.includes('Rows')) {
                $(this).text(summary.total_rows.toLocaleString());
            } else if (label.includes('Size')) {
                $(this).text(summary.total_size_mb.toFixed(2) + ' MB');
            }
        });
        
        // Update header status indicator
        const headerStatus = $('.health-summary-card .card-header .status-indicator');
        if (summary.error_tables > 0) {
            headerStatus.attr('class', 'status-indicator error');
        } else if (summary.warning_tables > 0) {
            headerStatus.attr('class', 'status-indicator warning');
        } else {
            headerStatus.attr('class', 'status-indicator ok');
        }
        
        // Update individual table cards
        $.each(data.tables, function(tableName, table) {
            const card = $('.table-card[data-table="' + tableName + '"]');
            if (!card.length) return;
            
            // Update status indicator in header
            const headerDot = card.find('.card-header .status-dot');
            if (table.status === 'ok') {
                headerDot.attr('class', 'status-dot ok').text('✅');
            } else if (table.status === 'warning') {
                headerDot.attr('class', 'status-dot warning').text('⚠️');
            } else {
                headerDot.attr('class', 'status-dot error').text('❌');
            }
            
            // Update status text
            const statusText = card.find('.stat-row .stat-value').first();
            statusText.text(table.status === 'ok' ? 'OK' : table.status === 'warning' ? 'Warning' : 'Error')
                .attr('class', 'stat-value ' + table.status);
            
            // Update columns count
            const columnsStat = card.find('.stat-row').eq(2).find('.stat-value');
            const missingIndicator = table.missing_columns.length > 0 ? '⚠️' : '✅';
            columnsStat.html(table.columns.length + '/' + table.required_columns.length + ' ' + missingIndicator)
                .attr('class', 'stat-value ' + (table.missing_columns.length === 0 ? 'success' : 'warning'));
            
            // Update rows and size
            card.find('.stat-row').eq(1).find('.stat-value').text(table.row_count.toLocaleString());
            card.find('.stat-row').eq(3).find('.stat-value').text(table.indexes.length);
            card.find('.stat-row').eq(4).find('.stat-value').text(table.size_mb.toFixed(2) + ' MB');
            
            // Update missing columns display
            let missingDiv = card.find('.missing-columns');
            if (table.missing_columns.length > 0) {
                if (!missingDiv.length) {
                    missingDiv = $('<div class="missing-columns"></div>');
                    card.find('.card-body').append(missingDiv);
                }
                missingDiv.html('<strong>Missing:</strong> ' + table.missing_columns.join(', '));
            } else if (missingDiv.length) {
                missingDiv.remove();
            }
            
            // Show/hide repair button
            const repairBtn = card.find('.repair-table-btn');
            if (table.status !== 'ok') {
                if (!repairBtn.length) {
                    repairBtn = $('<button class="button button-small repair-table-btn" data-table="' + tableName + '">🔧 Repair Table</button>');
                    card.find('.card-footer').prepend(repairBtn);
                }
                repairBtn.show();
            } else {
                repairBtn.hide();
            }
        });
    }
    
    // Refresh button
    $('#refresh-schema').click(function() {
        loadSchemaStatus();
    });
    
    // Auto-refresh toggle
    $('#auto-refresh-schema').change(function() {
        if (this.checked) {
            autoRefreshInterval = setInterval(loadSchemaStatus, 30000);
        } else {
            clearInterval(autoRefreshInterval);
        }
    });
    
    // Export report
    $('#export-schema-report').click(function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_export_schema_report',
                nonce: nonce,
            },
            success: function(response) {
                if (response.success) {
                    // Create downloadable JSON
                    const dataStr = JSON.stringify(response.data, null, 2);
                    const dataBlob = new Blob([dataStr], {type: 'application/json'});
                    const url = URL.createObjectURL(dataBlob);
                    
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'eipsi-schema-report-' + new Date().toISOString().slice(0,10) + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    alert('Error exporting report: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error exporting report. Please try again.');
            }
        });
    });
    
    // Fix collations
    $('#fix-collations').click(function() {
        if (!confirm('¿Estás seguro de que deseas corregir las collations de todas las tablas del plugin?\n\nEsto convertirá todas las tablas a utf8mb4_unicode_ci y puede tomar varios segundos en bases de datos grandes.')) {
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text(' Corrigiendo...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_fix_collations',
                nonce: nonce,
            },
            success: function(response) {
                $btn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    const result = response.data;
                    let message = ' Collations corregidas exitosamente!\n\n';
                    
                    if (result.total_fixed === 0) {
                        message = ' Todas las tablas ya tienen la collation correcta (utf8mb4_unicode_ci).';
                    } else {
                        message += 'Tablas corregidas: ' + result.total_fixed + '\n\n';
                        result.fixed_tables.forEach(function(table) {
                            if (table.success) {
                                message += '• ' + table.table + ': ' + table.old_collation + ' → ' + table.new_collation + '\n';
                            } else {
                                message += '• ' + table.table + ': ERROR - ' + table.error + '\n';
                            }
                        });
                    }
                    
                    alert(message);
                    
                    // Refresh schema status to show updated collations
                    loadSchemaStatus();
                } else {
                    alert('Error corrigiendo collations: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text(originalText);
                alert('Error de conexión. Por favor intenta nuevamente.');
            }
        });
    });
    
    // Repair table button
    $(document).on('click', '.repair-table-btn', function() {
        const tableName = $(this).data('table');
        const card = $(this).closest('.table-card');
        
        if (!confirm('Repair table ' + tableName + '?')) {
            return;
        }
        
        $(this).prop('disabled', true).text('Repairing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_repair_single_table',
                nonce: nonce,
                table_name: tableName
            },
            success: function(response) {
                if (response.success) {
                    alert('Table repaired: ' + response.data.message);
                    loadSchemaStatus();
                } else {
                    alert('Error repairing table: ' + response.data);
                }
            },
            error: function() {
                alert('AJAX error during repair');
            }
        });
    });
    
    // Toggle details
    $(document).on('click', '.toggle-details', function() {
        const tableName = $(this).data('table');
        const details = $('#details-' + tableName);
        
        if (details.is(':visible')) {
            details.hide();
            $(this).text('📋 View Details');
        } else {
            details.show();
            $(this).text('📋 Hide Details');
        }
    });
    
    // Auto-Fix Issues
    $('#auto-fix-issues').click(function() {
        if (!confirm('� Esto ejecutará correcciones automáticas para problemas comunes:\n\n' +
                     '• Waves con time_unit inválido (0, null, vacío)\n' +
                     '• Otras inconsistencias de datos\n\n' +
                     '¿Deseas continuar?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Corrigiendo...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_auto_fix_schema_issues',
                nonce: nonce,
            },
            success: function(response) {
                $btn.prop('disabled', false).text('� Auto-Fix Issues');
                
                if (response.success) {
                    const result = response.data;
                    let message = '✅ Correcciones aplicadas:\n\n';
                    
                    if (result.fixes.length === 0) {
                        message = '✅ No se encontraron problemas que requieran corrección.';
                    } else {
                        result.fixes.forEach(function(fix) {
                            message += `• ${fix.description}: ${fix.affected_rows} fila(s)\n`;
                        });
                    }
                    
                    alert(message);
                    
                    // Hide banner and refresh
                    $('#auto-fix-banner').hide();
                    loadSchemaStatus();
                } else {
                    alert('❌ Error: ' + (response.data?.message || 'Error desconocido'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('🔧 Auto-Fix Issues');
                alert('❌ Error de conexión');
            }
        });
    });
    
    // Check collations periodically
    function checkCollations() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_check_collation_issues',
                nonce: nonce,
            },
            success: function(response) {
                // Collation button suppressed - fixes run automatically in background
                $('#fix-collations').hide();
            }
        });
    }
    
    // Collation and auto-fix checks disabled - fixes run automatically in background
    // checkCollations(); // Disabled - no UI to update
    // checkAutoFixIssues(); // Disabled - fixes run silently
    
    // Initial load
    loadSchemaStatus();
    
    // Set up auto-refresh
    if ($('#auto-refresh-schema').is(':checked')) {
        autoRefreshInterval = setInterval(loadSchemaStatus, 30000);
    }
});
</script>

<style>
.schema-status-tab-container {
    padding: 20px;
}

.monitoring-controls {
    margin-bottom: 30px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 4px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.monitoring-controls button {
    margin: 0;
}

.monitoring-timestamp {
    margin-left: auto;
    color: #999;
    font-size: 12px;
}

.monitoring-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-indicator {
    font-size: 20px;
    animation: pulse 2s infinite;
}

.status-indicator.ok { color: #4CAF50; }
.status-indicator.warning { color: #ff9800; }
.status-indicator.error { color: #f44336; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.status-dot {
    font-size: 18px;
}

.card-body {
    padding: 15px;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-label {
    font-weight: 600;
    color: #666;
}

.stat-value {
    font-weight: bold;
    color: #333;
}

.stat-value.success { color: #4CAF50; }
.stat-value.warning { color: #ff9800; }
.stat-value.error { color: #f44336; }

.card-footer {
    padding: 10px 15px;
    background: #f9f9f9;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.card-footer .button {
    margin: 0;
}

.table-name {
    font-size: 11px;
    color: #999;
    font-family: monospace;
}

.issues-list {
    margin-top: 15px;
    padding: 10px;
    background: #fff3cd;
    border-radius: 4px;
}

.issues-list h4 {
    margin: 0 0 10px 0;
    color: #856404;
}

.issues-list ul {
    margin: 0;
    padding-left: 20px;
}

.issues-list li {
    color: #856404;
    font-size: 12px;
    margin-bottom: 5px;
}

.missing-columns {
    margin-top: 10px;
    padding: 8px;
    background: #fce4e4;
    border-radius: 4px;
    font-size: 12px;
    color: #c62828;
}

.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.table-card .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
}

.table-card .card-header .table-name {
    font-size: 11px;
    color: #999;
    font-family: monospace;
    margin-left: 26px;
}

.table-details {
    padding: 15px;
    background: #f9f9f9;
    border-top: 1px solid #ddd;
}

.table-details h4 {
    margin: 15px 0 10px 0;
    font-size: 14px;
    color: #666;
}

.table-details h4:first-child {
    margin-top: 0;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.detail-table th,
.detail-table td {
    padding: 6px 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.detail-table th {
    background: #eee;
    font-weight: 600;
}

.index-list {
    margin: 0;
    padding-left: 20px;
    font-size: 12px;
    color: #666;
}

.index-list li {
    font-family: monospace;
}

.health-summary-card {
    max-width: 100%;
}

/* Collation Badge */
.collation-badge {
    display: inline-block;
    background: #ff9800;
    color: white;
    font-size: 11px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
    min-width: 18px;
    text-align: center;
}


@media (max-width: 768px) {
    .tables-grid {
        grid-template-columns: 1fr;
    }
    
    .monitoring-controls {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .monitoring-timestamp {
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>
