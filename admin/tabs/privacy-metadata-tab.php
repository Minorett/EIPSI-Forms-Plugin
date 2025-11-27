<?php
/**
 * Privacy & Metadata Tab
 * Configure per-form metadata capture settings
 * Includes privacy-dashboard.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include privacy dashboard
include dirname(dirname(__FILE__)) . '/privacy-dashboard.php';

// Get form_id from URL if present
$selected_form_id = isset($_GET['privacy_form_id']) ? sanitize_text_field($_GET['privacy_form_id']) : '';

// Get all unique form IDs from database
global $wpdb;
$table_name = $wpdb->prefix . 'vas_form_results';
$form_ids = $wpdb->get_col("SELECT DISTINCT form_id FROM $table_name WHERE form_id IS NOT NULL AND form_id != '' ORDER BY form_id");

?>
<div class="eipsi-privacy-tab-header" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
    <h3 style="margin-top: 0;">Selecciona un formulario</h3>
    <p style="color: #666; margin-bottom: 12px;">
        La configuración de privacidad se aplica por formulario. Selecciona el formulario que deseas configurar:
    </p>
    
    <?php if (empty($form_ids)): ?>
        <div class="notice notice-warning inline">
            <p>
                <strong>No hay formularios con respuestas aún.</strong><br>
                Los formularios aparecerán aquí una vez que se haya registrado al menos una respuesta.
                <br>La configuración por defecto se aplicará automáticamente a todos los formularios nuevos.
            </p>
        </div>
    <?php else: ?>
        <form method="get" style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="page" value="vas-dinamico-results">
            <input type="hidden" name="tab" value="privacy">
            <label for="privacy_form_id" style="font-weight: 600;">
                Formulario:
            </label>
            <select name="privacy_form_id" id="privacy_form_id" onchange="this.form.submit()" style="padding: 8px; min-width: 250px;">
                <option value="">-- Selecciona un formulario --</option>
                <?php foreach ($form_ids as $form_id): ?>
                    <option value="<?php echo esc_attr($form_id); ?>" <?php selected($selected_form_id, $form_id); ?>>
                        <?php echo esc_html($form_id); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php
// Only render the privacy dashboard if a form is selected
if (!empty($selected_form_id)) {
    render_privacy_dashboard($selected_form_id);
} elseif (!empty($form_ids)) {
    echo '<div class="notice notice-info inline" style="padding: 15px;">';
    echo '<p>👆 <strong>Selecciona un formulario arriba para configurar sus opciones de privacidad.</strong></p>';
    echo '</div>';
}
