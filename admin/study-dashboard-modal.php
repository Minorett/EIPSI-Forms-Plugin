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

                    <!-- Shortcode Card -->
                    <div class="dashboard-card shortcode-card">
                        <h3>🔗 <?php esc_html_e('Shortcode del Estudio', 'eipsi-forms'); ?></h3>
                        <div class="card-body">
                            <p class="description">
                                <?php esc_html_e('Usá este shortcode en cualquier página para mostrar el estudio y sus tomas.', 'eipsi-forms'); ?>
                            </p>
                            <div class="eipsi-shortcode-field">
                                <code id="study-shortcode-display"></code>
                                <button type="button" class="button button-secondary button-small" id="copy-study-shortcode">
                                    📋 <?php esc_html_e('Copiar', 'eipsi-forms'); ?>
                                </button>
                            </div>
                            <p class="description eipsi-shortcode-note">
                                <?php esc_html_e('El shortcode siempre apunta al último estado del estudio.', 'eipsi-forms'); ?>
                            </p>
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

                    <!-- Magic Links Card -->
                    <div class="dashboard-card magic-links-card">
                        <h3>✨ <?php esc_html_e('Magic Links', 'eipsi-forms'); ?></h3>
                        <div class="card-body">
                            <p class="description">
                                <?php esc_html_e('Generá un enlace seguro para que cada participante entre sin contraseña. Cada Magic Link dura 48 horas y reemplaza el anterior.', 'eipsi-forms'); ?>
                            </p>
                            <form id="magic-link-form">
                                <label for="magic-link-email"><?php esc_html_e('Email del participante', 'eipsi-forms'); ?></label>
                                <input type="email" id="magic-link-email" class="widefat" required placeholder="participante@email.com">

                                <div class="magic-link-actions">
                                    <button type="button" class="button button-secondary" id="generate-magic-link">🔐 <?php esc_html_e('Generar enlace', 'eipsi-forms'); ?></button>
                                    <button type="submit" class="button button-primary" id="send-magic-link">📨 <?php esc_html_e('Enviar por email', 'eipsi-forms'); ?></button>
                                </div>

                                <div id="magic-link-output" class="magic-link-output" style="display:none;">
                                    <label for="magic-link-url"><?php esc_html_e('Enlace generado', 'eipsi-forms'); ?></label>
                                    <div class="magic-link-field">
                                        <input type="text" id="magic-link-url" class="widefat" readonly>
                                        <button type="button" class="button button-secondary button-small" id="copy-magic-link">📋 <?php esc_html_e('Copiar', 'eipsi-forms'); ?></button>
                                    </div>
                                    <p class="description magic-link-note">
                                        <?php esc_html_e('Este enlace es único para esta persona y expira en 48 horas.', 'eipsi-forms'); ?>
                                    </p>
                                </div>

                                <div id="magic-link-error" class="notice notice-error" style="display:none; margin-top: 10px;"></div>
                                <div id="magic-link-success" class="notice notice-success" style="display:none; margin-top: 10px;"></div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="dashboard-card actions-card">
                        <h3>⚙️ <?php esc_html_e('Acciones Rápidas', 'eipsi-forms'); ?></h3>
                        <div class="card-body quick-actions">
                            <button class="button button-primary" id="action-add-participant">👤 <?php esc_html_e('Agregar Participante', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-import-csv">📄 <?php esc_html_e('Importar CSV', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-edit-study"><?php esc_html_e('Editar Configuración', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-download-data"><?php esc_html_e('Descargar Datos', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-view-participants"><?php esc_html_e('Ver Lista de Participantes', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-cron-jobs">⏰ <?php esc_html_e('Tareas Programadas', 'eipsi-forms'); ?></button>
                            <button class="button button-link-delete" id="action-close-study"><?php esc_html_e('Cerrar Estudio', 'eipsi-forms'); ?></button>
                            <button class="button button-link-delete" id="action-delete-study" style="color: #d63638; margin-top: 10px;">🗑️ <?php esc_html_e('Eliminar Estudio', 'eipsi-forms'); ?></button>
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

<!-- Modal for Participants List -->
<div id="eipsi-participants-list-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content participants-modal">
        <div class="eipsi-modal-header">
            <h2>👥 <?php esc_html_e('Gestión de Participantes', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <div id="participants-loading" class="eipsi-loading-overlay">
                <div class="spinner is-active"></div>
                <p><?php esc_html_e('Cargando participantes...', 'eipsi-forms'); ?></p>
            </div>

            <div id="participants-content" style="display:none;">
                <div class="participants-filter-bar">
                    <div class="filter-group">
                        <label for="participant-status-filter"><?php esc_html_e('Estado:', 'eipsi-forms'); ?></label>
                        <select id="participant-status-filter" class="regular-text">
                            <option value="all"><?php esc_html_e('Todos', 'eipsi-forms'); ?></option>
                            <option value="active"><?php esc_html_e('Activos', 'eipsi-forms'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactivos', 'eipsi-forms'); ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="participant-search"><?php esc_html_e('Buscar:', 'eipsi-forms'); ?></label>
                        <input type="text" id="participant-search" class="regular-text" placeholder="<?php esc_attr_e('Email o nombre...', 'eipsi-forms'); ?>">
                    </div>
                    <div class="filter-actions">
                        <span id="participants-count" class="participants-count-badge"></span>
                    </div>
                </div>

                <div class="participants-table-wrapper">
                    <table class="wp-list-table widefat fixed striped participants-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Email', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Nombre', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Magic Link', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Fecha Registro', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Último Acceso', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Acciones', 'eipsi-forms'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="participants-tbody">
                            <!-- Filas generadas dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <div id="participants-pagination" class="participants-pagination">
                    <!-- Paginación generada dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal for Participant Detail -->
<div id="eipsi-participant-detail-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content participant-detail-modal">
        <div class="eipsi-modal-header">
            <h2>🧾 <?php esc_html_e('Detalle del Participante', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <div id="participant-detail-loading" class="eipsi-loading-overlay">
                <div class="spinner is-active"></div>
                <p><?php esc_html_e('Cargando historial...', 'eipsi-forms'); ?></p>
            </div>

            <div id="participant-detail-error" class="notice notice-error" style="display:none; margin-bottom: 15px;"></div>

            <div id="participant-detail-content" style="display:none;">
                <div class="participant-detail-header">
                    <div>
                        <h3 id="participant-detail-name"></h3>
                        <p id="participant-detail-email" class="participant-detail-email"></p>
                    </div>
                    <div id="participant-detail-status"></div>
                </div>

                <div class="participant-detail-grid">
                    <div class="participant-detail-card">
                        <h4><?php esc_html_e('Estado actual', 'eipsi-forms'); ?></h4>
                        <p><strong><?php esc_html_e('Creado:', 'eipsi-forms'); ?></strong> <span id="participant-detail-created"></span></p>
                        <p><strong><?php esc_html_e('Último ingreso:', 'eipsi-forms'); ?></strong> <span id="participant-detail-last-login"></span></p>
                        <p><strong><?php esc_html_e('Sesión activa:', 'eipsi-forms'); ?></strong> <span id="participant-detail-session"></span></p>
                    </div>

                    <div class="participant-detail-card">
                        <h4><?php esc_html_e('Timeline clínico', 'eipsi-forms'); ?></h4>
                        <div id="participant-detail-timeline"></div>
                    </div>
                </div>

                <div class="participant-detail-card">
                    <h4><?php esc_html_e('Waves completadas', 'eipsi-forms'); ?></h4>
                    <div class="participant-detail-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Wave', 'eipsi-forms'); ?></th>
                                    <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                                    <th><?php esc_html_e('Asignada', 'eipsi-forms'); ?></th>
                                    <th><?php esc_html_e('Completada', 'eipsi-forms'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="participant-waves-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="participant-detail-card">
                    <h4><?php esc_html_e('Historial de Magic Links', 'eipsi-forms'); ?></h4>
                    <div class="participant-detail-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Enviado', 'eipsi-forms'); ?></th>
                                    <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                                    <th><?php esc_html_e('Expira', 'eipsi-forms'); ?></th>
                                    <th><?php esc_html_e('Usado', 'eipsi-forms'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="participant-magic-links-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="eipsi-modal-footer">
            <button class="button button-secondary eipsi-modal-close"><?php esc_html_e('Cerrar', 'eipsi-forms'); ?></button>
        </div>
    </div>
</div>

<!-- Modal for Removing Participant -->
<div id="eipsi-remove-participant-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content small-modal">
        <div class="eipsi-modal-header">
            <h2>🗑️ <?php esc_html_e('Quitar participante', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <p class="description">
                <?php esc_html_e('Elegí cómo quitar a este participante del estudio. Siempre queda un registro en la auditoría.', 'eipsi-forms'); ?>
            </p>
            <p><strong><?php esc_html_e('Email:', 'eipsi-forms'); ?></strong> <span id="remove-participant-email"></span></p>

            <div class="remove-participant-options">
                <label class="remove-option">
                    <input type="radio" name="remove-participant-type" value="deactivate" checked>
                    <span>
                        <strong><?php esc_html_e('Desactivar (mantener historial)', 'eipsi-forms'); ?></strong><br>
                        <small><?php esc_html_e('El participante queda inactivo, no puede ingresar ni recibe recordatorios.', 'eipsi-forms'); ?></small>
                    </span>
                </label>
                <label class="remove-option">
                    <input type="radio" name="remove-participant-type" value="delete">
                    <span>
                        <strong><?php esc_html_e('Eliminar definitivamente', 'eipsi-forms'); ?></strong><br>
                        <small><?php esc_html_e('Se borra el participante, sus sesiones y asignaciones del estudio.', 'eipsi-forms'); ?></small>
                    </span>
                </label>
            </div>

            <p>
                <label for="remove-participant-reason"><strong><?php esc_html_e('Motivo (opcional):', 'eipsi-forms'); ?></strong></label>
                <textarea id="remove-participant-reason" class="widefat" rows="3" placeholder="Ej: abandono, solicitud del participante"></textarea>
            </p>

            <div id="remove-participant-error" class="notice notice-error" style="display:none; margin-top: 10px;"></div>
        </div>
        <div class="eipsi-modal-footer">
            <button class="button button-secondary eipsi-modal-close"><?php esc_html_e('Cancelar', 'eipsi-forms'); ?></button>
            <button class="button button-primary" id="confirm-remove-participant"><?php esc_html_e('Confirmar', 'eipsi-forms'); ?></button>
        </div>
    </div>
</div>

<!-- Modal for Magic Link Resend -->
<div id="eipsi-magic-link-resend-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content medium-modal">
        <div class="eipsi-modal-header">
            <h2>✨ <?php esc_html_e('Reenviar Magic Link', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <div class="magic-link-preview-meta">
                <p><strong><?php esc_html_e('Destinatario:', 'eipsi-forms'); ?></strong> <span id="resend-magic-link-email"></span></p>
                <p><strong><?php esc_html_e('Asunto:', 'eipsi-forms'); ?></strong> <span id="resend-magic-link-subject"></span></p>
            </div>
            <div id="resend-magic-link-preview" class="magic-link-preview"></div>
            <div id="resend-magic-link-link-wrap" class="magic-link-preview-link" style="display:none;">
                <label for="resend-magic-link-link"><?php esc_html_e('Enlace actual', 'eipsi-forms'); ?></label>
                <div class="magic-link-field">
                    <input type="text" id="resend-magic-link-link" class="widefat" readonly>
                    <button type="button" class="button button-secondary button-small" id="copy-resend-magic-link">📋 <?php esc_html_e('Copiar', 'eipsi-forms'); ?></button>
                </div>
            </div>
            <p class="description magic-link-preview-note">
                <?php esc_html_e('Al reenviar se generará un nuevo Magic Link y el anterior quedará vencido.', 'eipsi-forms'); ?>
            </p>
            <div id="resend-magic-link-error" class="notice notice-error" style="display:none; margin-top: 10px;"></div>
            <div id="resend-magic-link-success" class="notice notice-success" style="display:none; margin-top: 10px;"></div>
        </div>
        <div class="eipsi-modal-footer">
            <button class="button button-secondary eipsi-modal-close"><?php esc_html_e('Cerrar', 'eipsi-forms'); ?></button>
            <button class="button button-primary" id="confirm-resend-magic-link">📨 <?php esc_html_e('Enviar Magic Link', 'eipsi-forms'); ?></button>
        </div>
    </div>
</div>

<!-- Modal for Manual Magic Link -->
<div id="eipsi-magic-link-manual-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content small-modal">
        <div class="eipsi-modal-header">
            <h2>🔗 <?php esc_html_e('Generar Magic Link', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <form id="manual-magic-link-form">
                <p>
                    <label for="manual-magic-link-email"><?php esc_html_e('Email del participante', 'eipsi-forms'); ?></label>
                    <input type="email" id="manual-magic-link-email" class="widefat" required>
                </p>
                <button type="button" class="button button-secondary" id="manual-generate-magic-link">🔐 <?php esc_html_e('Generar enlace', 'eipsi-forms'); ?></button>

                <div id="manual-magic-link-output" class="magic-link-output" style="display:none;">
                    <label for="manual-magic-link-url"><?php esc_html_e('Enlace generado', 'eipsi-forms'); ?></label>
                    <div class="magic-link-field">
                        <input type="text" id="manual-magic-link-url" class="widefat" readonly>
                        <button type="button" class="button button-secondary button-small" id="manual-copy-magic-link">📋 <?php esc_html_e('Copiar', 'eipsi-forms'); ?></button>
                    </div>
                    <p class="description magic-link-note">
                        <?php esc_html_e('Compartilo por WhatsApp o el canal que prefieras. Expira en 48 horas.', 'eipsi-forms'); ?>
                    </p>
                </div>

                <div id="manual-magic-link-error" class="notice notice-error" style="display:none; margin-top: 10px;"></div>
                <div id="manual-magic-link-success" class="notice notice-success" style="display:none; margin-top: 10px;"></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for CSV Import -->
<div id="eipsi-import-csv-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content medium-modal">
        <div class="eipsi-modal-header">
            <h2>📄 <?php esc_html_e('Importar Participantes desde CSV', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <!-- Paso 1: Subir archivo -->
            <div id="csv-step-1" class="csv-import-step">
                <div class="csv-upload-area" id="csv-upload-area">
                    <div class="csv-upload-icon">📁</div>
                    <p class="csv-upload-text"><?php esc_html_e('Arrastra tu archivo CSV aquí o haz clic para seleccionar', 'eipsi-forms'); ?></p>
                    <p class="csv-upload-hint"><?php esc_html_e('Formato: email, first_name, last_name (máx. 500 participantes)', 'eipsi-forms'); ?></p>
                    <input type="file" id="csv-file-input" accept=".csv,.txt" style="display:none;">
                </div>
                <div class="csv-template-download">
                    <a href="#" id="download-csv-template" class="button button-link"><?php esc_html_e('📥 Descargar plantilla CSV', 'eipsi-forms'); ?></a>
                </div>
            </div>

            <!-- Paso 2: Vista previa -->
            <div id="csv-step-2" class="csv-import-step" style="display:none;">
                <div class="csv-preview-header">
                    <h4><?php esc_html_e('Vista previa de participantes', 'eipsi-forms'); ?></h4>
                    <span id="csv-preview-count" class="csv-count-badge"></span>
                </div>
                
                <div class="csv-validation-summary" id="csv-validation-summary">
                    <!-- Resumen de validaciones -->
                </div>

                <div class="csv-preview-table-wrapper">
                    <table class="wp-list-table widefat fixed striped csv-preview-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('#', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Email', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Nombre', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Apellido', 'eipsi-forms'); ?></th>
                                <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="csv-preview-tbody">
                            <!-- Filas generadas dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <div class="csv-preview-note">
                    <p class="description">
                        ⚠️ <?php esc_html_e('Los participantes existentes serán omitidos. Se generarán contraseñas automáticas.', 'eipsi-forms'); ?>
                    </p>
                </div>
            </div>

            <!-- Paso 3: Progreso -->
            <div id="csv-step-3" class="csv-import-step" style="display:none;">
                <div class="csv-import-progress">
                    <div class="progress-info">
                        <span id="csv-import-status"><?php esc_html_e('Importando participantes...', 'eipsi-forms'); ?></span>
                        <span id="csv-import-counter">0 / 0</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div id="csv-import-progress-bar" class="progress-bar-fill blue" style="width:0%"></div>
                    </div>
                    <div class="progress-details" id="csv-progress-details">
                        <!-- Detalles del progreso -->
                    </div>
                </div>
            </div>

            <!-- Paso 4: Resultados -->
            <div id="csv-step-4" class="csv-import-step" style="display:none;">
                <div class="csv-results" id="csv-results">
                    <!-- Resultados de la importación -->
                </div>
            </div>

            <!-- Mensajes de error/éxito -->
            <div id="csv-import-error" class="notice notice-error" style="display:none; margin: 10px 0;"></div>
            <div id="csv-import-success" class="notice notice-success" style="display:none; margin: 10px 0;"></div>
        </div>

        <div class="eipsi-modal-footer csv-modal-footer">
            <button type="button" class="button button-secondary" id="csv-cancel-btn"><?php esc_html_e('Cancelar', 'eipsi-forms'); ?></button>
            <button type="button" class="button button-primary" id="csv-validate-btn" style="display:none;">
                <?php esc_html_e('Validar Datos', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="button button-primary" id="csv-import-btn" style="display:none;" disabled>
                📧 <?php esc_html_e('Importar y Enviar Invitaciones', 'eipsi-forms'); ?>
            </button>
            <button type="button" class="button button-primary" id="csv-done-btn" style="display:none;">
                <?php esc_html_e('Finalizar', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Modal for Editing Study -->
<div id="eipsi-edit-study-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content medium-modal">
        <style>
        #eipsi-edit-study-modal .form-group {
            margin-bottom: 15px;
        }
        #eipsi-edit-study-modal label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        #eipsi-edit-study-modal .widefat {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        #eipsi-edit-study-modal textarea.widefat {
            min-height: 100px;
            resize: vertical;
        }
        #eipsi-edit-study-modal .date-range-group {
            display: flex;
            gap: 15px;
        }
        #eipsi-edit-study-modal .date-field {
            flex: 1;
        }
        #eipsi-edit-study-modal .notice {
            padding: 10px 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        #eipsi-edit-study-modal .notice-error {
            background-color: #ffebe8;
            border-left: 4px solid #c0392b;
            color: #c0392b;
        }
        #eipsi-edit-study-modal .notice-success {
            background-color: #e8f5e9;
            border-left: 4px solid #27ae60;
            color: #27ae60;
        }
        </style>
        <div class="eipsi-modal-header">
            <h2>⚙️ <?php esc_html_e('Editar Configuración del Estudio', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <form id="edit-study-form">
                <input type="hidden" id="edit-study-id" value="">
                
                <div class="form-group">
                    <label for="edit-study-name"><?php esc_html_e('Nombre del Estudio *', 'eipsi-forms'); ?></label>
                    <input type="text" id="edit-study-name" class="widefat" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-study-description"><?php esc_html_e('Descripción', 'eipsi-forms'); ?></label>
                    <textarea id="edit-study-description" class="widefat" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit-study-time-config"><?php esc_html_e('Configuración de Tiempo', 'eipsi-forms'); ?></label>
                    <select id="edit-study-time-config" class="widefat">
                        <option value="limited"><?php esc_html_e('Tiempo Limitado', 'eipsi-forms'); ?></option>
                        <option value="unlimited"><?php esc_html_e('Tiempo Ilimitado', 'eipsi-forms'); ?></option>
                    </select>
                </div>
                
                <div id="edit-study-dates-container" class="form-group">
                    <div class="date-range-group">
                        <div class="date-field">
                            <label for="edit-study-start-date"><?php esc_html_e('Fecha de Inicio', 'eipsi-forms'); ?></label>
                            <input type="date" id="edit-study-start-date" class="widefat">
                        </div>
                        <div class="date-field">
                            <label for="edit-study-end-date"><?php esc_html_e('Fecha de Finalización Estimada', 'eipsi-forms'); ?></label>
                            <input type="date" id="edit-study-end-date" class="widefat">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php esc_html_e('Configuración de Waves', 'eipsi-forms'); ?></label>
                    <div id="edit-study-waves-container">
                        <!-- Waves will be loaded dynamically -->
                    </div>
                </div>
                
                <div id="edit-study-error" class="notice notice-error" style="display:none; margin: 10px 0;"></div>
                <div id="edit-study-success" class="notice notice-success" style="display:none; margin: 10px 0;"></div>
                
                <button type="submit" class="button button-primary">
                    💾 <?php esc_html_e('Guardar Cambios', 'eipsi-forms'); ?></button>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Cron Jobs Configuration -->
<div id="eipsi-cron-jobs-modal" class="eipsi-modal" style="display:none; z-index: 100001;">
    <div class="eipsi-modal-content medium-modal">
        <div class="eipsi-modal-header">
            <h2>⏰ <?php esc_html_e('Configuración de Tareas Programadas', 'eipsi-forms'); ?></h2>
            <button class="eipsi-modal-close">&times;</button>
        </div>
        <div class="eipsi-modal-body">
            <div id="cron-jobs-loading" class="eipsi-loading-overlay">
                <div class="spinner is-active"></div>
                <p><?php esc_html_e('Cargando configuración...', 'eipsi-forms'); ?></p>
            </div>
            <div id="cron-jobs-content" style="display:none;"></div>
        </div>
    </div>
</div>