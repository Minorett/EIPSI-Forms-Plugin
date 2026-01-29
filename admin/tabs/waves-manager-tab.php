<?php
/**
 * Waves Manager Tab
 * 
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// 1. Fetch all studies
$studies = $wpdb->get_results("SELECT id, study_name, study_id FROM {$wpdb->prefix}survey_studies ORDER BY created_at DESC");

// 2. Determine active study
$current_study_id = isset($_GET['study_id']) ? absint($_GET['study_id']) : (isset($studies[0]) ? $studies[0]->id : 0);

// 3. Get waves for active study
$waves = array();
$next_wave_index = 1;
if ($current_study_id) {
    $waves = EIPSI_Wave_Service::get_study_waves($current_study_id);
    if (!empty($waves)) {
        $last_wave = end($waves);
        $next_wave_index = (int)$last_wave['wave_index'] + 1;
    }
}

// 4. Get available forms (following setup-wizard logic)
$available_forms = get_posts(array(
    'post_type' => 'page',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_eipsi_form_active',
            'value' => '1',
            'compare' => '='
        )
    ),
    'orderby' => 'title',
    'order' => 'ASC'
));

?>

<div class="eipsi-waves-manager-wrap" data-study-id="<?php echo esc_attr($current_study_id); ?>">
    
    <!-- Header with Study Selector -->
    <div class="eipsi-waves-header">
        <div class="study-selector-wrap">
            <label for="eipsi-study-selector"><strong><?php esc_html_e('Seleccionar Estudio:', 'eipsi-forms'); ?></strong></label>
            <select id="eipsi-study-selector" onchange="window.location.href='?page=eipsi-results&tab=waves-manager&study_id=' + this.value">
                <?php if (empty($studies)): ?>
                    <option value=""><?php esc_html_e('No hay estudios creados', 'eipsi-forms'); ?></option>
                <?php else: ?>
                    <?php foreach ($studies as $study): ?>
                        <option value="<?php echo esc_attr($study->id); ?>" <?php selected($current_study_id, $study->id); ?>>
                            <?php echo esc_html($study->study_name . ' (' . $study->study_id . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <div class="header-actions">
            <?php if ($current_study_id): ?>
                <button type="button" class="button button-primary" id="eipsi-create-wave-btn" data-next-index="<?php echo esc_attr($next_wave_index); ?>">
                    ➕ <?php esc_html_e('Nueva Onda (Wave)', 'eipsi-forms'); ?>
                </button>
            <?php endif; ?>
        </div>


    <?php if (!$current_study_id): ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e('Por favor, selecciona o crea un estudio primero para gestionar sus ondas.', 'eipsi-forms'); ?></p>
        </div>
    <?php else: ?>
        
        <!-- Waves List -->
        <div class="eipsi-waves-list">
            <?php if (empty($waves)): ?>
                <div class="eipsi-empty-state">
                    <p><?php esc_html_e('No hay ondas configuradas para este estudio.', 'eipsi-forms'); ?></p>
                </div>
            <?php else: ?>
                <div class="eipsi-waves-grid">
                    <?php foreach ($waves as $wave): ?>
                        <?php 
                        $stats = EIPSI_Wave_Service::get_wave_stats($wave['id']);
                        $progress = ($stats['total'] > 0) ? round(($stats['submitted'] / $stats['total']) * 100) : 0;
                        $form_post = get_post($wave['form_id']);
                        $form_name = $form_post ? $form_post->post_title : __('Formulario no encontrado', 'eipsi-forms');
                        ?>
                        <div class="eipsi-wave-card" data-wave-id="<?php echo esc_attr($wave['id']); ?>">
                            <div class="wave-card-header">
                                <span class="wave-index">T<?php echo esc_html($wave['wave_index']); ?></span>
                                <h3 class="wave-name"><?php echo esc_html($wave['name']); ?></h3>
                                <span class="wave-status status-<?php echo esc_attr($wave['status']); ?>">
                                    <?php echo esc_html(ucfirst($wave['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="wave-card-body">
                                <p class="wave-description"><?php echo esc_html($wave['description'] ?? ''); ?></p>
                                <div class="wave-meta">
                                    <span><strong><?php esc_html_e('Formulario:', 'eipsi-forms'); ?></strong> <?php echo esc_html($form_name); ?></span>
                                    <span><strong><?php esc_html_e('Vence:', 'eipsi-forms'); ?></strong> <?php echo esc_html($wave['due_date'] ? date_i18n(get_option('date_format') . ' H:i', strtotime($wave['due_date'])) : __('Sin fecha', 'eipsi-forms')); ?></span>
                                </div>
                                
                                <div class="wave-stats">
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo (int)$stats['total']; ?></span>
                                        <span class="stat-label"><?php esc_html_e('Asignados', 'eipsi-forms'); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo (int)$stats['submitted']; ?></span>
                                        <span class="stat-label"><?php esc_html_e('Respondidos', 'eipsi-forms'); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo (int)$stats['pending']; ?></span>
                                        <span class="stat-label"><?php esc_html_e('Pendientes', 'eipsi-forms'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="wave-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo (int)$progress; ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo (int)$progress; ?>%</span>
                                </div>
                            </div>
                            
                            <div class="wave-card-actions">
                                <button type="button" class="button eipsi-edit-wave-btn" data-wave="<?php echo esc_attr(json_encode($wave)); ?>">
                                    <?php esc_html_e('Editar', 'eipsi-forms'); ?>
                                </button>
                                <button type="button" class="button eipsi-assign-participants-btn">
                                    <?php esc_html_e('Asignar', 'eipsi-forms'); ?>
                                </button>
                                <button type="button" class="button eipsi-extend-deadline-btn">
                                    <?php esc_html_e('Extender', 'eipsi-forms'); ?>
                                </button>
                                <button type="button" class="button eipsi-send-reminder-btn">
                                    <?php esc_html_e('Recordatorio', 'eipsi-forms'); ?>
                                </button>
                                <button type="button" class="button button-link-delete eipsi-delete-wave-btn">
                                    <?php esc_html_e('Eliminar', 'eipsi-forms'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Crear/Editar Wave -->
<div id="eipsi-wave-modal" class="eipsi-modal" style="display:none;">
    <div class="eipsi-modal-content">
        <span class="eipsi-close-modal">&times;</span>
        <h3 id="wave-modal-title"><?php esc_html_e('Crear Nueva Onda', 'eipsi-forms'); ?></h3>
        <form id="eipsi-wave-form">
            <input type="hidden" name="wave_id" id="wave_id" value="">
            <input type="hidden" name="study_id" value="<?php echo esc_attr($current_study_id); ?>">
            
            <div class="form-group">
                <label for="wave_name"><?php esc_html_e('Nombre de la Onda:', 'eipsi-forms'); ?></label>
                <input type="text" id="wave_name" name="name" required placeholder="Ej: Evaluación Inicial">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="wave_index"><?php esc_html_e('Índice (T1, T2...):', 'eipsi-forms'); ?></label>
                    <input type="number" id="wave_index" name="wave_index" min="1" step="1" required>
                </div>
                <div class="form-group">
                    <label for="form_id"><?php esc_html_e('Formulario Asociado:', 'eipsi-forms'); ?></label>
                    <select id="form_id" name="form_id" required>
                        <option value=""><?php esc_html_e('Seleccionar formulario...', 'eipsi-forms'); ?></option>
                        <?php foreach ($available_forms as $form): ?>
                            <option value="<?php echo esc_attr($form->ID); ?>"><?php echo esc_html($form->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="due_date"><?php esc_html_e('Fecha de Vencimiento:', 'eipsi-forms'); ?></label>
                <input type="datetime-local" id="due_date" name="due_date">
            </div>
            
            <div class="form-group">
                <label for="wave_description"><?php esc_html_e('Descripción:', 'eipsi-forms'); ?></label>
                <textarea id="wave_description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_mandatory" value="1" checked>
                    <?php esc_html_e('Esta onda es obligatoria', 'eipsi-forms'); ?>
                </label>
            </div>

            <div class="modal-footer">
                <button type="submit" class="button button-primary" id="save-wave-btn"><?php esc_html_e('Guardar Onda', 'eipsi-forms'); ?></button>
                <button type="button" class="button eipsi-close-modal-btn"><?php esc_html_e('Cancelar', 'eipsi-forms'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Asignar Participantes -->
<div id="eipsi-assign-modal" class="eipsi-modal" style="display:none;">
    <div class="eipsi-modal-content modal-large">
        <span class="eipsi-close-modal">&times;</span>
        <h3><?php esc_html_e('Asignar Participantes a la Onda', 'eipsi-forms'); ?> <span id="assign-wave-name"></span></h3>
        
        <div class="assign-filters">
            <p><?php esc_html_e('Selecciona los participantes que aún no están asignados a esta onda.', 'eipsi-forms'); ?></p>
            <div class="selection-actions">
                <button type="button" class="button button-small" id="select-all-participants"><?php esc_html_e('Seleccionar Todos', 'eipsi-forms'); ?></button>
                <button type="button" class="button button-small" id="deselect-all-participants"><?php esc_html_e('Deseleccionar Todos', 'eipsi-forms'); ?></button>
            </div>
        </div>
        
        <div class="participants-list-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="check-column"><input type="checkbox" id="master-participant-check"></th>
                        <th><?php esc_html_e('Nombre Completo', 'eipsi-forms'); ?></th>
                        <th><?php esc_html_e('Email', 'eipsi-forms'); ?></th>
                        <th><?php esc_html_e('ID Participante', 'eipsi-forms'); ?></th>
                    </tr>
                </thead>
                <tbody id="available-participants-tbody">
                    <!-- Loaded via AJAX -->
                    <tr>
                        <td colspan="4" style="text-align:center;"><?php esc_html_e('Cargando participantes...', 'eipsi-forms'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="modal-footer">
            <button type="button" class="button button-primary" id="confirm-assign-btn"><?php esc_html_e('Asignar Seleccionados', 'eipsi-forms'); ?></button>
            <button type="button" class="button eipsi-close-modal-btn"><?php esc_html_e('Cerrar', 'eipsi-forms'); ?></button>
        </div>
    </div>
</div>
