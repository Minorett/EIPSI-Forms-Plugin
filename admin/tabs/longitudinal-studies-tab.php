<?php
/**
 * Tab: Estudios Longitudinales
 * 
 * @since 1.5.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue styles and scripts
wp_enqueue_style('eipsi-longitudinal-studies-tab', EIPSI_FORMS_PLUGIN_URL . 'assets/css/longitudinal-studies-tab.css', array(), EIPSI_FORMS_VERSION);
wp_enqueue_style('eipsi-study-dashboard-css', EIPSI_FORMS_PLUGIN_URL . 'assets/css/study-dashboard.css', array(), EIPSI_FORMS_VERSION);
wp_enqueue_script('eipsi-study-dashboard', EIPSI_FORMS_PLUGIN_URL . 'admin/js/study-dashboard.js', array('jquery'), EIPSI_FORMS_VERSION, true);

// Localize data for JS
wp_localize_script('eipsi-study-dashboard', 'eipsiStudyDash', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('eipsi_study_dashboard_nonce'),
    'strings' => array(
        'loading' => __('Cargando...', 'eipsi-forms'),
        'error' => __('Error', 'eipsi-forms'),
        'success' => __('Éxito', 'eipsi-forms'),
    ),
));

global $wpdb;

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Search
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$where_clause = "";
if (!empty($search)) {
    // Buscar por nombre o código de estudio (study_code en lugar de study_id)
    $where_clause = $wpdb->prepare(
        "WHERE study_name LIKE %s OR study_code LIKE %s",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

// Fetch studies
$studies = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}survey_studies 
    $where_clause 
    ORDER BY created_at DESC 
    LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

$total_studies = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}survey_studies $where_clause");
$total_pages = ceil($total_studies / $per_page);

// Summary stats
$active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}survey_studies WHERE status = 'active'");
$completed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}survey_studies WHERE status = 'completed'");
$paused_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}survey_studies WHERE status = 'paused'");

?>

<div class="eipsi-longitudinal-studies-wrap">
    
    <!-- Summary Cards -->
    <div class="eipsi-summary-cards">
        <div class="eipsi-card-stat">
            <span class="stat-label">📊 <?php esc_html_e('Activos', 'eipsi-forms'); ?></span>
            <span class="stat-value"><?php echo (int)$active_count; ?></span>
        </div>
        <div class="eipsi-card-stat">
            <span class="stat-label">✅ <?php esc_html_e('Completados', 'eipsi-forms'); ?></span>
            <span class="stat-value"><?php echo (int)$completed_count; ?></span>
        </div>
        <div class="eipsi-card-stat">
            <span class="stat-label">⏸️ <?php esc_html_e('En Pausa', 'eipsi-forms'); ?></span>
            <span class="stat-value"><?php echo (int)$paused_count; ?></span>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="eipsi-list-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="eipsi-results">
            <input type="hidden" name="tab" value="longitudinal-studies">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Buscar por nombre o ID...', 'eipsi-forms'); ?>">
            <button type="submit" class="button button-secondary"><?php esc_html_e('Buscar', 'eipsi-forms'); ?></button>
            <?php if (!empty($search)): ?>
                <a href="?page=eipsi-results&tab=longitudinal-studies" class="button button-link"><?php esc_html_e('Limpiar', 'eipsi-forms'); ?></a>
            <?php endif; ?>
        </form>
        <a href="?page=eipsi-new-study" class="button button-primary">➕ <?php esc_html_e('Nuevo Estudio', 'eipsi-forms'); ?></a>
    </div>

    <!-- Studies Table -->
    <table class="wp-list-table widefat fixed">
        <thead>
            <tr>
                <th><?php esc_html_e('Nombre del Estudio', 'eipsi-forms'); ?></th>
                <th><?php esc_html_e('ID del Estudio', 'eipsi-forms'); ?></th>
                <th><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                <th><?php esc_html_e('Participantes', 'eipsi-forms'); ?></th>
                <th><?php esc_html_e('Acciones', 'eipsi-forms'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($studies)): ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No se encontraron estudios.', 'eipsi-forms'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($studies as $study): ?>
                    <?php 
                        // Usar study->id (PK) en lugar de study_id (no existe en schema)
                        // La relación con participantes es por survey_id (el id del estudio)
                        $participant_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d",
                            $study->id
                        ));
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($study->study_name); ?></strong></td>
                        <td><code><?php echo esc_html($study->study_code); ?></code></td>
                        <td>
                            <span class="eipsi-badge badge-<?php echo esc_attr($study->status); ?>">
                                <?php echo esc_html(ucfirst($study->status)); ?>
                            </span>
                        </td>
                        <td><?php echo (int)$participant_count; ?></td>
                        <td>
                            <button class="button button-secondary eipsi-view-study" data-study-id="<?php echo esc_attr($study->id); ?>">
                                👁️ <?php esc_html_e('Ver Detalles', 'eipsi-forms'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Anterior', 'eipsi-forms'),
                    'next_text' => __('Siguiente &raquo;', 'eipsi-forms'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php 
// Include the modal template
include dirname(__FILE__) . '/../study-dashboard-modal.php'; 
?>
