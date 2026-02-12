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
                            <button class="button button-primary" id="action-add-participant">👤 <?php esc_html_e('Agregar Participante', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-import-csv">📄 <?php esc_html_e('Importar CSV', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-edit-study"><?php esc_html_e('Editar Configuración', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-download-data"><?php esc_html_e('Descargar Datos', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-view-participants"><?php esc_html_e('Ver Lista de Participantes', 'eipsi-forms'); ?></button>
                            <button class="button button-secondary" id="action-cron-jobs">⏰ <?php esc_html_e('Tareas Programadas', 'eipsi-forms'); ?></button>
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