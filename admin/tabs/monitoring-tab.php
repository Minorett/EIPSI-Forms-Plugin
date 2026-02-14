<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="monitoring-tab" class="monitoring-tab-container">
    <h2>🔧 System Health & Monitoring</h2>

    <!-- Refresh Controls -->
    <div class="monitoring-controls">
        <button id="refresh-monitoring" class="button button-primary">🔄 Refresh Now</button>
        <label>
            <input type="checkbox" id="auto-refresh" checked>
            Auto-refresh every 30s
        </label>
        <button id="export-monitoring" class="button button-secondary">📥 Export Report</button>
        <span id="last-update" class="monitoring-timestamp">Last update: -</span>
    </div>

    <!-- Email Stats Card -->
    <div class="monitoring-card email-card">
        <div class="card-header">
            <h3>📧 EMAIL SERVICE</h3>
            <span class="status-indicator" id="email-status">●</span>
        </div>
        <div class="card-body">
            <div class="stat-row">
                <span class="stat-label">Enviados hoy:</span>
                <span class="stat-value" id="email-sent">-</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Fallidos:</span>
                <span class="stat-value error" id="email-failed">-</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Bounce Rate:</span>
                <span class="stat-value" id="email-bounce">-</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Último envío:</span>
                <span class="stat-value" id="email-last">-</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">En cola:</span>
                <span class="stat-value warning" id="email-pending">-</span>
            </div>
        </div>
        <div class="card-footer">
            <a href="<?php echo esc_url(admin_url('admin.php?page=eipsi-longitudinal-study&tab=email-log')); ?>">View Email Log →</a>
        </div>
    </div>

    <!-- Cron Jobs Card -->
    <div class="monitoring-card cron-card">
        <div class="card-header">
            <h3>⏰ CRON JOBS</h3>
            <span class="status-indicator" id="cron-status">●</span>
        </div>
        <div class="card-body">
            <div class="cron-job">
                <div class="cron-name">Wave Reminders</div>
                <div class="cron-status" id="cron-wave_reminders">-</div>
                <div class="cron-time" id="cron-wave_reminders-time">-</div>
            </div>
            <div class="cron-job">
                <div class="cron-name">Session Cleanup</div>
                <div class="cron-status" id="cron-session_cleanup">-</div>
                <div class="cron-time" id="cron-session_cleanup-time">-</div>
            </div>
            <div class="cron-job">
                <div class="cron-name">Email Retry</div>
                <div class="cron-status" id="cron-email_retry">-</div>
                <div class="cron-time" id="cron-email_retry-time">-</div>
            </div>
            <div class="cron-job">
                <div class="cron-name">Dropout Recovery</div>
                <div class="cron-status" id="cron-dropout_recovery">-</div>
                <div class="cron-time" id="cron-dropout_recovery-time">-</div>
            </div>
        </div>
        <div class="card-footer">
            <small>Status: ✅ OK | ⚠️ WARNING | ❌ ERROR</small>
        </div>
    </div>

    <!-- Sessions Card -->
    <div class="monitoring-card sessions-card">
        <div class="card-header">
            <h3>🔐 SESSIONS</h3>
            <span class="status-indicator" id="sessions-status">●</span>
        </div>
        <div class="card-body">
            <div class="stat-row">
                <span class="stat-label">Sesiones activas:</span>
                <span class="stat-value success" id="sessions-active">-</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Expiradas hoy:</span>
                <span class="stat-value" id="sessions-expired">-</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Sin usar (24h):</span>
                <span class="stat-value warning" id="sessions-unused">-</span>
            </div>
            <button id="cleanup-sessions" class="button button-small">Clean unused sessions</button>
        </div>
        <div class="card-footer">
            TTL: 7 days | Cleanup: hourly
        </div>
    </div>

    <!-- Database Card -->
    <div class="monitoring-card database-card">
        <div class="card-header">
            <h3>💾 DATABASE</h3>
            <span class="status-indicator" id="database-status">●</span>
        </div>
        <div class="card-body">
            <div class="stat-row">
                <span class="stat-label">Tamaño total:</span>
                <span class="stat-value" id="db-size">-</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Conexión:</span>
                <span class="stat-value success" id="db-connection">-</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="db-progress" style="width: 0%"></div>
            </div>
        </div>
        <div class="card-footer">
            <a href="javascript:void(0);" id="optimize-db">Optimize tables →</a>
        </div>
    </div>

    <!-- Audit Log Card -->
    <div class="monitoring-card audit-card">
        <div class="card-header">
            <h3>📋 AUDIT LOG (últimas 10)</h3>
        </div>
        <div class="card-body">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="audit-log-body">
                    <tr><td colspan="4" style="text-align: center;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo esc_js(wp_create_nonce('eipsi_admin_nonce')); ?>';
    let autoRefreshInterval = null;

    // Load monitoring data
    function loadMonitoringData() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_monitoring_data',
                nonce: nonce,
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                    $('#last-update').text('Last update: ' + new Date().toLocaleTimeString());
                }
            },
        });
    }

    // Update dashboard with new data
    function updateDashboard(data) {
        const email = data.email;
        const cron = data.cron;
        const sessions = data.sessions;
        const database = data.database;

        // Email stats
        $('#email-sent').text(email.sent_today);
        $('#email-failed').text(email.failed_today);
        $('#email-bounce').text(email.bounce_rate + '%');
        $('#email-last').text(email.last_sent ? formatTime(email.last_sent) : 'Never');
        $('#email-pending').text(email.pending_count);

        const emailStatus = email.failed_today > 0 ? 'warning' : 'ok';
        $('#email-status').attr('class', 'status-indicator ' + emailStatus);

        // Cron jobs
        let overallCronStatus = 'ok';
        Object.keys(cron).forEach(function(job) {
            const status = cron[job].status;
            if (status === 'error') {
                overallCronStatus = 'error';
            } else if (status === 'warning' && overallCronStatus !== 'error') {
                overallCronStatus = 'warning';
            }

            $('#cron-' + job)
                .text(status.toUpperCase())
                .attr('class', 'cron-status cron-' + status);
            $('#cron-' + job + '-time').text(cron[job].last_run ? formatTime(cron[job].last_run) : 'Never');
        });
        $('#cron-status').attr('class', 'status-indicator ' + overallCronStatus);

        // Sessions
        $('#sessions-active').text(sessions.active_sessions);
        $('#sessions-expired').text(sessions.expired_today);
        $('#sessions-unused').text(sessions.unused_sessions);
        const sessionStatus = sessions.unused_sessions > 0 ? 'warning' : 'ok';
        $('#sessions-status').attr('class', 'status-indicator ' + sessionStatus);

        // Database
        $('#db-size').text(database.table_size_mb + ' MB');
        $('#db-connection')
            .text(database.connection_status.toUpperCase())
            .attr('class', 'stat-value ' + (database.connection_status === 'ok' ? 'success' : 'error'));
        $('#database-status').attr('class', 'status-indicator ' + (database.connection_status === 'ok' ? 'ok' : 'error'));
        const dbProgress = Math.min((parseFloat(database.table_size_mb) / 1024) * 100, 100);
        $('#db-progress').css('width', dbProgress + '%');
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    // Load audit log
    function loadAuditLog() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_audit_log',
                nonce: nonce,
                limit: 10,
            },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(entry) {
                        html += '<tr>';
                        html += '<td>' + formatTime(entry.created_at) + '</td>';
                        html += '<td>' + escapeHtml(entry.action) + '</td>';
                        html += '<td>' + escapeHtml(entry.user_id) + '</td>';
                        html += '<td><small>' + escapeHtml(entry.details) + '</small></td>';
                        html += '</tr>';
                    });
                    $('#audit-log-body').html(html || '<tr><td colspan="4">No entries</td></tr>');
                }
            },
        });
    }

    // Format time helper
    function formatTime(timeString) {
        const date = new Date(timeString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return diff + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';

        return date.toLocaleString();
    }

    // Refresh button
    $('#refresh-monitoring').click(function() {
        loadMonitoringData();
        loadAuditLog();
    });

    // Auto-refresh toggle
    $('#auto-refresh').change(function() {
        if (this.checked) {
            autoRefreshInterval = setInterval(function() {
                loadMonitoringData();
                loadAuditLog();
            }, 30000); // 30 segundos
        } else {
            clearInterval(autoRefreshInterval);
        }
    });

    // Export monitoring report
    $('#export-monitoring').click(function() {
        window.location.href = ajaxurl + '?action=eipsi_export_monitoring_report&nonce=' + nonce;
    });

    // Cleanup / optimize placeholders
    $('#cleanup-sessions').click(function(event) {
        event.preventDefault();
        alert('Cleanup en cola. Si necesitás forzarlo ahora, hacelo desde tus cron jobs.');
    });

    $('#optimize-db').click(function(event) {
        event.preventDefault();
        alert('Optimizá tablas desde tu hosting para evitar locks innecesarios.');
    });

    // Initial load
    loadMonitoringData();
    loadAuditLog();

    // Set up auto-refresh
    if ($('#auto-refresh').is(':checked')) {
        autoRefreshInterval = setInterval(function() {
            loadMonitoringData();
            loadAuditLog();
        }, 30000);
    }
});
</script>

<style>
.monitoring-tab-container {
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

.cron-job {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f9f9f9;
    margin-bottom: 10px;
    border-radius: 3px;
}

.cron-job:last-child { margin-bottom: 0; }

.cron-name {
    font-weight: 600;
}

.cron-status {
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 3px;
}

.cron-status.cron-ok { background: #c8e6c9; color: #2e7d32; }
.cron-status.cron-warning { background: #ffe0b2; color: #e65100; }
.cron-status.cron-error { background: #ffcdd2; color: #c62828; }
.cron-status.cron-unknown { background: #e0e0e0; color: #666; }

.cron-time {
    font-size: 12px;
    color: #999;
}

.progress-bar {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    margin: 10px 0;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #45a049);
    transition: width 0.3s ease;
}

.card-footer {
    padding: 10px 15px;
    background: #f9f9f9;
    border-top: 1px solid #ddd;
    font-size: 12px;
}

.card-footer a {
    color: #3B6CAA;
    text-decoration: none;
}

.card-footer a:hover {
    color: #3B6CAA;
}

.audit-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.audit-table thead {
    background: #f5f5f5;
}

.audit-table th,
.audit-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.audit-table th {
    font-weight: 600;
}

.audit-table tbody tr:hover {
    background: #f9f9f9;
}

@media (min-width: 768px) {
    .monitoring-card {
        max-width: calc(50% - 10px);
        display: inline-block;
        margin-right: 20px;
        vertical-align: top;
    }

    .monitoring-card:nth-child(odd) {
        margin-right: 20px;
    }

    .monitoring-card:nth-child(even) {
        margin-right: 0;
    }

    .audit-card {
        max-width: 100%;
    }
}
</style>
