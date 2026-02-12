<?php
/**
 * Study Dashboard Modal
 * 
 * @since 1.5.2
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="eipsi-study-dashboard-modal" class="eipsi-modal" style="display:none;">
    <div class="eipsi-modal-content dashboard-wide">
        <div class="eipsi-modal-header">
            <h2 id="study-modal-title"><?php esc_html_e('Detalles del Estudio', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        
        <div class="eipsi-modal-body">
            <div id="eipsi-dashboard-loading" class="eipsi-loading-overlay">
                <div class="spinner is-active"></div>
                <p><?php esc_html_e('Cargando datos del estudio...', 'eipsi-forms'); ?></p>
            </div>

            <div id="eipsi-dashboard-content" style="display:none;">
                <div class="eipsi-dashboard-grid">
                    
                    <!-- General Status Card -->
                    <div class="dashboard-card status-card">
                        <h3>📊 <?php esc_html_e('Estado General', 'eipsi-forms'); ?></h3>
                        <div class="card-body">
                            <p><strong><?php esc_html_e('Estado:', 'eipsi-forms'); ?></strong> <span id="study-status-badge" class="eipsi-badge"></span></p>
                            <p><strong><?php esc_html_e('Creado:', 'eipsi-forms'); ?></strong> <span id="study-created-at"></span></p>
                            <p><strong><?php esc_html_e('Estimado Finalización:', 'eipsi-forms'); ?></strong> <span id="study-estimated-end"></span></p>
                            <p><strong><?php esc_html_e('ID Interno:', 'eipsi-forms'); ?></strong> <code id="study-id-display"></code></p>
                        </div>
                    </div>

                    <!-- Participant Stats Card -->
                    <div class="dashboard-card participants-card">
                        <h3>👥 <?php esc_html_e('Participantes', 'eipsi-forms'); ?></h3>
                        <div class="card-body">
                            <div class="stat-row">
                                <span class="label"><?php esc_html_e('Total:', 'eipsi-forms'); ?></span>
                                <span class="value" id="total-participants">0</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar-group">
                                    <span class="mini-label"><?php esc_html_e('Completados', 'eipsi-forms'); ?></span>
                                    <div class="progress-bar-bg"><div id="bar-completed" class="progress-bar-fill green"></div></div>
                                    <span id="percent-completed">0%</span>
                                </div>
                                <div class="progress-bar-group">
                                    <span class="mini-label"><?php esc_html_e('En Curso', 'eipsi-forms'); ?></span>
                                    <div class="progress-bar-bg"><div id="bar-in-progress" class="progress-bar-fill blue"></div></div>
                                    <span id="percent-in-progress">0%</span>
                                </div>
                                <div class="progress-bar-group">
                                    <span class="mini-label"><?php esc_html_e('Inactivos', 'eipsi-forms'); ?></span>
                                    <div class="progress-bar-bg"><div id="bar-inactive" class="progress-bar-fill red"></div></div>
                                    <span id="percent-inactive">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Waves Card -->
                    <div class="dashboard-card waves-card full-width">
                        <h3>📋 <?php esc_html_e('Tomas / Waves', 'eipsi-forms'); ?></h3>
                        <div id="waves-container" class="waves-grid">
                            <!-- Waves will be injected here via JS -->
                        </div>
                    </div>

                    <!-- Emails Card -->
                    <div class="dashboard-card emails-card">
                        <h3>📧 <?php esc_html_e('Emails', 'eipsi-forms'); ?></h3>
                        <div class="card-body">
                            <p><strong><?php esc_html_e('Enviados hoy:', 'eipsi-forms'); ?></strong> <span id="emails-sent-today">0</span></p>
                            <p><strong><?php esc_html_e('Fallidos:', 'eipsi-forms'); ?></strong> <span id="emails-failed" class="text-error">0</span></p>
                            <p><strong><?php esc_html_e('Último envío:', 'eipsi-forms'); ?></strong> <span id="emails-last-sent"></span></p>
                            <button class="button button-secondary button-small" id="view-email-logs"><?php esc_html_e('Ver Log de Emails', 'eipsi-forms'); ?></button>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="dashboard-card actions-card">
                        <h3>⚙️ <?php esc_html_e('Acciones Rápidas', 'eipsi-forms'); ?></h3>
                        <div class="card-body quick-actions">
                            <button class="button button-primary" id="action-add-participant">👥 <?php esc_html_e('Agregar Participante', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-edit-study"><?php esc_html_e('Editar Configuración', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-download-data"><?php esc_html_e('Descargar Datos', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-view-participants"><?php esc_html_e('Ver Lista de Participantes', 'eipsi-forms'); ?></button>
                            <button class="button button-link-delete" id="action-close-study"><?php esc_html_e('Cerrar Estudio', 'eipsi-forms'); ?></button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="eipsi-modal-footer">
            <button class="button button-secondary eipsi-modal-close"><?php esc_html_e('Cerrar', 'eipsi-forms'); ?></button>
            <button class="button button-primary" id="refresh-dashboard">🔄 <?php esc_html_e('Actualizar', 'eipsi-forms'); ?></button>
        </div>
    </div>
</div>

<!-- Extra Modal for Email Logs -->
<div id="eipsi-email-logs-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content">
        <div class="eipsi-modal-header">
            <h2><?php esc_html_e('Log de Emails', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha', 'eipsi-forms'); ?></th>
                        <th><?php esc_html_e('Destinatario', 'eipsi-forms'); ?></th>
                        <th><?php esc_html_e('Asunto', 'eipsi-forms'); ?></th>
                        <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                    </tr>
                </thead>
                <tbody id="email-logs-tbody">
                    <!-- Injected via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Extra Modal for Extending Deadline -->
<div id="eipsi-extend-deadline-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content small-modal">
        <div class="eipsi-modal-header">
            <h2><?php esc_html_e('Extender Plazo', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <form id="extend-deadline-form">
                <input type="hidden" id="extend-wave-id" value="">
                <p>
                    <label for="new-deadline-date"><?php esc_html_e('Nueva fecha de vencimiento:', 'eipsi-forms'); ?></label>
                    <input type="date" id="new-deadline-date" class="widefat" required>
                </p>
                <button type="submit" class="button button-primary"><?php esc_html_e('Guardar Cambios', 'eipsi-forms'); ?></button>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Adding Participant -->
<div id="eipsi-add-participant-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content small-modal">
        <div class="eipsi-modal-header">
            <h2>👥 <?php esc_html_e('Agregar Participante', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <form id="add-participant-form">
                <input type="hidden" id="add-participant-study-id" value="">
                
                <p>
                    <label for="participant-email"><?php esc_html_e('Email *', 'eipsi-forms'); ?></label>
                    <input type="email" id="participant-email" class="widefat" required placeholder="participante@email.com">
                </p>
                
                <p>
                    <label for="participant-first-name"><?php esc_html_e('Nombre', 'eipsi-forms'); ?></label>
                    <input type="text" id="participant-first-name" class="widefat" placeholder="<?php esc_attr_e('Opcional', 'eipsi-forms'); ?>">
                </p>
                
                <p>
                    <label for="participant-last-name"><?php esc_html_e('Apellido', 'eipsi-forms'); ?></label>
                    <input type="text" id="participant-last-name" class="widefat" placeholder="<?php esc_attr_e('Opcional', 'eipsi-forms'); ?>">
                </p>
                
                <p>
                    <label for="participant-password"><?php esc_html_e('Contraseña Temporal', 'eipsi-forms'); ?></label>
                    <input type="text" id="participant-password" class="widefat" placeholder="<?php esc_attr_e('Dejar vacío para generar automáticamente', 'eipsi-forms'); ?>">
                    <small style="color: #666; display: block; margin-top: 4px;"><?php esc_html_e('Mínimo 8 caracteres', 'eipsi-forms'); ?></small>
                </p>
                
                <div id="add-participant-error" class="notice notice-error" style="display:none; margin: 10px 0;"></div>
                <div id="add-participant-success" class="notice notice-success" style="display:none; margin: 10px 0;"></div>
                
                <button type="submit" class="button button-primary" id="submit-add-participant">
                    ✉️ <?php esc_html_e('Crear y Enviar Invitación', 'eipsi-forms'); ?>
                </button>
            </form>
        </div>
    </div>
</div>
