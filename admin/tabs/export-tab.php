<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include the export service
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-export-service.php';
$export_service = new EIPSI_Export_Service();
$surveys = $export_service->get_available_surveys();
?>

<div id="export-tab" class="export-tab-container">
    <h2>📊 Export Longitudinal Data</h2>
    
    <!-- Filters -->
    <div class="export-filters">
        <div class="filter-group">
            <label for="filter-survey">Study:</label>
            <select id="filter-survey">
                <option value="">-- Select Study --</option>
                <?php foreach ($surveys as $survey): ?>
                    <option value="<?php echo esc_attr($survey->id); ?>">
                        <?php echo esc_html($survey->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-wave">Wave:</label>
            <select id="filter-wave">
                <option value="all">All Waves</option>
                <option value="T1">Wave 1 (T1)</option>
                <option value="T2">Wave 2 (T2)</option>
                <option value="T3">Wave 3 (T3)</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-date-from">From:</label>
            <input type="date" id="filter-date-from">
        </div>
        
        <div class="filter-group">
            <label for="filter-date-to">To:</label>
            <input type="date" id="filter-date-to">
        </div>
        
        <div class="filter-group">
            <label for="filter-status">Status:</label>
            <select id="filter-status">
                <option value="all">All</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="late">Late</option>
            </select>
        </div>
        
        <button id="clear-filters" class="button button-secondary">Clear</button>
    </div>
    
    <!-- Statistics -->
    <div id="export-stats" class="export-stats">
        <div class="stat-card">
            <h4>Total Participants</h4>
            <p class="stat-value">-</p>
        </div>
        <div class="stat-card">
            <h4>Completion Rate (T1)</h4>
            <p class="stat-value">-</p>
            <div class="progress-bar">
                <div class="progress-bar-fill" style="width: 0%"></div>
            </div>
        </div>
        <div class="stat-card">
            <h4>Avg Response Time (T1)</h4>
            <p class="stat-value">-</p>
        </div>
    </div>
    
    <!-- Detailed Stats -->
    <div id="detailed-stats" class="detailed-stats" style="display:none;">
        <h3>Completion Timeline</h3>
        <div id="completion-rates" class="completion-rates"></div>
        <h3>Response Time Analysis</h3>
        <div id="response-times" class="response-times"></div>
    </div>
    
    <!-- Download Actions -->
    <div class="export-actions">
        <button id="export-excel" class="button button-primary" disabled>
            📥 Download Excel (.xlsx)
        </button>
        <button id="export-csv" class="button button-primary" disabled>
            📥 Download CSV (.csv)
        </button>
    </div>
    
    <!-- Data Summary -->
    <div id="data-summary" class="data-summary" style="display:none;">
        <p><strong>Data:</strong> <span id="row-count">0</span> rows | <strong>Columns:</strong> <span id="column-count">0</span> | <strong>Encoding:</strong> UTF-8 | <strong>Last update:</strong> <span id="last-update">-</span></p>
    </div>
    
    <!-- Preview Table -->
    <div id="export-preview" class="export-preview" style="display:none;">
        <h3>Data Preview</h3>
        <table class="widefat">
            <thead>
                <tr id="preview-headers"></tr>
            </thead>
            <tbody id="preview-body"></tbody>
        </table>
        <p><em>Showing first 10 rows only</em></p>
    </div>
    
    <!-- Additional Actions -->
    <div class="additional-actions" style="display:none;">
        <button id="view-detailed-table" class="button button-secondary">View Detailed Table</button>
        <button id="email-report" class="button button-secondary">Send Report by Email</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce('eipsi_admin_nonce'); ?>';
    let currentStats = null;
    
    // Load statistics when survey changes
    function loadStats() {
        const surveyId = $('#filter-survey').val();
        if (!surveyId) {
            resetUI();
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_export_stats',
                nonce: nonce,
                survey_id: surveyId,
            },
            beforeSend: function() {
                $('#export-excel, #export-csv').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    currentStats = response.data;
                    updateStatsUI(currentStats);
                    $('#export-excel, #export-csv').prop('disabled', false);
                    $('#data-summary, .additional-actions').show();
                    loadPreview();
                } else {
                    showError('Failed to load statistics: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('Failed to load statistics');
            }
        });
    }
    
    // Update statistics UI
    function updateStatsUI(stats) {
        // Update main stats
        $('.stat-card:eq(0) .stat-value').text(stats.total_participants || 0);
        $('.stat-card:eq(2) .stat-value').text(stats.avg_response_times['T1'] ? stats.avg_response_times['T1'].minutes + ' min' : '-');
        
        // Update completion rate for T1
        if (stats.completion_rates['T1']) {
            const rate = stats.completion_rates['T1'].rate;
            $('.stat-card:eq(1) .stat-value').text(rate + '%');
            $('.progress-bar-fill').css('width', rate + '%');
        } else {
            $('.stat-card:eq(1) .stat-value').text('-');
            $('.progress-bar-fill').css('width', '0%');
        }
        
        // Update detailed stats
        if (Object.keys(stats.completion_rates).length > 0) {
            $('#detailed-stats').show();
            
            // Completion rates by wave
            let completionHtml = '';
            Object.keys(stats.completion_rates).forEach(function(wave) {
                const data = stats.completion_rates[wave];
                completionHtml += `
                    <div class="wave-stat">
                        <strong>${wave}:</strong> ${data.completed}/${data.total} (${data.rate}%)
                        <div class="mini-progress-bar">
                            <div class="mini-progress-fill" style="width: ${data.rate}%"></div>
                        </div>
                    </div>
                `;
            });
            $('#completion-rates').html(completionHtml);
            
            // Response times by wave
            let responseHtml = '';
            Object.keys(stats.avg_response_times).forEach(function(wave) {
                const data = stats.avg_response_times[wave];
                responseHtml += `
                    <div class="wave-stat">
                        <strong>${wave}:</strong> ${data.minutes} minutes (${data.seconds} seconds)
                    </div>
                `;
            });
            $('#response-times').html(responseHtml);
        }
        
        // Update data summary
        $('#last-update').text(new Date().toLocaleString());
    }
    
    // Load data preview
    function loadPreview() {
        const surveyId = $('#filter-survey').val();
        if (!surveyId) return;
        
        // Show preview with first few rows
        $('#export-preview').show();
        
        // This would typically be an AJAX call to get preview data
        // For now, we'll just show the headers
        const headers = ['Participant ID', 'Wave', 'Submitted At', 'Response Time (min)', 'Status', 'User Fingerprint'];
        let headerHtml = '';
        headers.forEach(function(header) {
            headerHtml += `<th>${header}</th>`;
        });
        $('#preview-headers').html(headerHtml);
        $('#column-count').text(headers.length);
        
        // Update row count (this would come from actual data)
        $('#row-count').text('0');
    }
    
    // Reset UI when no survey selected
    function resetUI() {
        $('.stat-value').text('-');
        $('.progress-bar-fill').css('width', '0%');
        $('#detailed-stats, #export-preview, #data-summary, .additional-actions').hide();
        $('#export-excel, #export-csv').prop('disabled', true);
        $('#preview-headers, #preview-body').empty();
    }
    
    // Show error message
    function showError(message) {
        alert('Error: ' + message);
    }
    
    // Export to Excel
    $('#export-excel').click(function() {
        const surveyId = $('#filter-survey').val();
        const filters = {
            wave_index: $('#filter-wave').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            status: $('#filter-status').val(),
        };
        
        // Build URL with parameters
        let url = `<?php echo admin_url('admin.php?page=eipsi-export-longitudinal&action=export_longitudinal_excel'); ?>`;
        url += `&survey_id=${surveyId}`;
        if (filters.wave_index !== 'all') url += `&wave_index=${filters.wave_index}`;
        if (filters.date_from) url += `&date_from=${filters.date_from}`;
        if (filters.date_to) url += `&date_to=${filters.date_to}`;
        if (filters.status !== 'all') url += `&status=${filters.status}`;
        
        // Add nonce for security
        url += `&_wpnonce=${nonce}`;
        
        window.location.href = url;
    });
    
    // Export to CSV
    $('#export-csv').click(function() {
        const surveyId = $('#filter-survey').val();
        const filters = {
            wave_index: $('#filter-wave').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            status: $('#filter-status').val(),
        };
        
        // Build URL with parameters
        let url = `<?php echo admin_url('admin.php?page=eipsi-export-longitudinal&action=export_longitudinal_csv'); ?>`;
        url += `&survey_id=${surveyId}`;
        if (filters.wave_index !== 'all') url += `&wave_index=${filters.wave_index}`;
        if (filters.date_from) url += `&date_from=${filters.date_from}`;
        if (filters.date_to) url += `&date_to=${filters.date_to}`;
        if (filters.status !== 'all') url += `&status=${filters.status}`;
        
        // Add nonce for security
        url += `&_wpnonce=${nonce}`;
        
        window.location.href = url;
    });
    
    // Clear filters
    $('#clear-filters').click(function() {
        $('#filter-wave').val('all');
        $('#filter-date-from, #filter-date-to').val('');
        $('#filter-status').val('all');
        loadStats();
    });
    
    // View detailed table
    $('#view-detailed-table').click(function() {
        const surveyId = $('#filter-survey').val();
        if (surveyId) {
            window.location.href = `<?php echo admin_url('admin.php?page=eipsi-results'); ?>&survey_id=${surveyId}`;
        }
    });
    
    // Email report
    $('#email-report').click(function() {
        // This would open an email modal or redirect to email functionality
        alert('Email report functionality would be implemented here');
    });
    
    // Load stats on change
    $('#filter-survey').change(loadStats);
    
    // Also load stats when other filters change (for demo purposes)
    $('#filter-wave, #filter-status').change(function() {
        if ($('#filter-survey').val()) {
            loadPreview();
        }
    });
});
</script>

<style>
.export-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.export-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-card {
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h4 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #000;
    margin: 0;
}

.progress-bar {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    margin-top: 10px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #45a049);
    transition: width 0.3s ease;
}

.detailed-stats {
    margin: 30px 0;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 8px;
}

.detailed-stats h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 18px;
}

.wave-stat {
    margin: 10px 0;
    padding: 10px;
    background: #fff;
    border-radius: 4px;
    border-left: 4px solid #4CAF50;
}

.mini-progress-bar {
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    margin-top: 5px;
    overflow: hidden;
}

.mini-progress-fill {
    height: 100%;
    background: #4CAF50;
    transition: width 0.3s ease;
}

.export-actions {
    margin: 30px 0;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.export-actions button {
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.export-actions button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.export-actions button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.data-summary {
    padding: 15px;
    background: #e8f5e8;
    border: 1px solid #4CAF50;
    border-radius: 4px;
    margin: 20px 0;
}

.data-summary p {
    margin: 0;
    font-size: 14px;
}

.export-preview {
    margin: 30px 0;
    overflow-x: auto;
}

.export-preview table {
    border-collapse: collapse;
    width: 100%;
}

.export-preview th,
.export-preview td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

.export-preview th {
    background: #f5f5f5;
    font-weight: 600;
}

.export-preview tbody tr:nth-child(even) {
    background: #f9f9f9;
}

.additional-actions {
    margin: 20px 0;
    text-align: center;
}

.additional-actions button {
    margin: 0 10px;
    padding: 8px 16px;
}

/* Responsive design */
@media (max-width: 768px) {
    .export-filters {
        grid-template-columns: 1fr;
    }
    
    .export-stats {
        grid-template-columns: 1fr;
    }
    
    .export-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>