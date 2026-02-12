<?php
/**
 * Study Cron Jobs Configuration Tab
 * 
 * Allows researchers to configure automatic cron jobs for their studies
 * 
 * @package EIPSI_Forms
 * @since 1.5.3
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get study ID from URL
$study_id = isset($_GET['study_id']) ? intval($_GET['study_id']) : 0;

// Get study configuration
$config = array();
if ($study_id) {
    $config = array(
        'cron_enabled' => get_post_meta($study_id, '_eipsi_study_cron_enabled', true),
        'cron_frequency' => get_post_meta($study_id, '_eipsi_study_cron_frequency', true),
        'cron_actions' => get_post_meta($study_id, '_eipsi_study_cron_actions', true),
        'cron_last_run' => get_post_meta($study_id, '_eipsi_study_cron_last_run', true),
        'cron_next_run' => get_post_meta($study_id, '_eipsi_study_cron_next_run', true),
    );
}

// Available frequencies
$frequencies = array(
    'daily' => __('Diario', 'eipsi-forms'),
    'weekly' => __('Semanal', 'eipsi-forms'),
    'monthly' => __('Mensual', 'eipsi-forms'),
);

// Available actions
$actions = array(
    'send_reminders' => __('Enviar recordatorios de waves pendientes', 'eipsi-forms'),
    'sync_data' => __('Sincronizar datos con servidores externos', 'eipsi-forms'),
    'generate_reports' => __('Generar reportes automáticos', 'eipsi-forms'),
);

// Get study name
$study_name = $study_id ? get_the_title($study_id) : __('Selecciona un estudio', 'eipsi-forms');
?>

<div class="eipsi-study-cron-jobs-tab">

    <!-- Info Box -->
    <div class="notice notice-info inline" style="margin: 0 0 20px 0;">
        <p>
            <strong><?php _e('Configuración de Tareas Programadas', 'eipsi-forms'); ?></strong><br>
            <?php _e('Configura tareas automáticas para tu estudio. Los cron jobs se ejecutarán según la frecuencia seleccionada.', 'eipsi-forms'); ?>
        </p>
    </div>

    <?php if (!$study_id): ?>
        <div class="notice notice-warning inline">
            <p><?php _e('Por favor selecciona un estudio desde el Study Dashboard para configurar sus cron jobs.', 'eipsi-forms'); ?></p>
        </div>
    <?php else: ?>

        <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 6px;">
            <h3 style="margin-top: 0; color: #3B6CAA;">
                📋 <?php echo esc_html($study_name); ?>
            </h3>
            <p><?php _e('Configura las tareas automáticas para este estudio.', 'eipsi-forms'); ?></p>
        </div>

        <form id="eipsi_study_cron_config_form" method="post">
            <?php wp_nonce_field('eipsi_study_cron_nonce', 'eipsi_study_cron_nonce'); ?>
            <input type="hidden" name="study_id" value="<?php echo esc_attr($study_id); ?>">

            <!-- Cron Jobs Configuration -->
            <div style="margin: 30px 0; padding: 20px; background: white; border: 1px solid #ddd; border-radius: 6px;">
                <h3 style="margin-top: 0; color: #3B6CAA;">
                    ⏰ <?php _e('Configuración de Cron Jobs', 'eipsi-forms'); ?>
                </h3>

                <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px;">
                    <label style="display: block; margin-bottom: 12px;">
                        <input type="checkbox" id="cron_enabled" name="cron_enabled" <?php checked(!empty($config['cron_enabled'])); ?>>
                        <strong><?php _e('Activar tareas programadas para este estudio', 'eipsi-forms'); ?></strong>
                    </label>
                </div>

                <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px;">
                    <label for="cron_frequency" style="display: block; margin-bottom: 8px; font-weight: 600;">
                        <?php _e('Frecuencia de ejecución', 'eipsi-forms'); ?>
                    </label>
                    <select id="cron_frequency" name="cron_frequency" style="width: 200px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <?php foreach ($frequencies as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($config['cron_frequency'], $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                        <?php _e('Acciones a ejecutar', 'eipsi-forms'); ?>
                    </label>
                    <?php foreach ($actions as $value => $label): ?>
                        <div style="margin: 8px 0;">
                            <label>
                                <input type="checkbox" name="cron_actions[]" value="<?php echo esc_attr($value); ?>"
                                    <?php echo is_array($config['cron_actions']) && in_array($value, $config['cron_actions']) ? 'checked' : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cron Status -->
                <div style="margin: 20px 0; padding: 15px; background: #e2e3e5; border: 1px solid #ddd; border-radius: 4px;">
                    <h4 style="margin-top: 0;">📊 <?php _e('Estado del Cron Job', 'eipsi-forms'); ?></h4>
                    <p>
                        <strong><?php _e('Última ejecución:', 'eipsi-forms'); ?></strong>
                        <span id="cron_last_run">
                            <?php echo !empty($config['cron_last_run']) ? esc_html(date('Y-m-d H:i:s', strtotime($config['cron_last_run']))) : __('Nunca', 'eipsi-forms'); ?>
                        </span>
                    </p>
                    <p>
                        <strong><?php _e('Próxima ejecución:', 'eipsi-forms'); ?></strong>
                        <span id="cron_next_run">
                            <?php echo !empty($config['cron_next_run']) ? esc_html(date('Y-m-d H:i:s', strtotime($config['cron_next_run']))) : __('No programada', 'eipsi-forms'); ?>
                        </span>
                    </p>
                </div>

                <!-- Save Button -->
                <button type="submit" class="button button-primary" id="eipsi_save_cron_config">
                    <?php _e('💾 Guardar Configuración', 'eipsi-forms'); ?>
                </button>
                <span id="eipsi_cron_spinner" class="spinner" style="display: none; margin-left: 10px;"></span>
                <span id="eipsi_cron_status" style="margin-left: 10px; font-weight: 600;"></span>
            </div>

            <!-- Info Box -->
            <div style="margin: 30px 0; padding: 20px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 6px;">
                <h3 style="margin-top: 0;">💡 <?php _e('Cómo funciona', 'eipsi-forms'); ?></h3>
                <ul style="margin: 10px 0;">
                    <li><?php _e('<strong>Frecuencia:</strong> Selecciona con qué frecuencia deseas que se ejecuten las tareas.', 'eipsi-forms'); ?></li>
                    <li><?php _e('<strong>Acciones:</strong> Elige qué tareas automáticas deseas ejecutar.', 'eipsi-forms'); ?></li>
                    <li><?php _e('<strong>Ejecución:</strong> Los cron jobs se ejecutarán automáticamente según la configuración.', 'eipsi-forms'); ?></li>
                    <li><?php _e('<strong>Logs:</strong> Todas las ejecuciones se registran para tu revisión.', 'eipsi-forms'); ?></li>
                </ul>
                <p style="margin-top: 15px;">
                    <small><?php _e('Nota: Los cron jobs de WordPress requieren visitas frecuentes a tu sitio para ejecutarse. Considera configurar un cron real del servidor para mayor confiabilidad.', 'eipsi-forms'); ?></small>
                </p>
            </div>

        </form>

    <?php endif; ?>

</div>

<script>
// Save configuration form handler
document.getElementById('eipsi_study_cron_config_form').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const spinner = document.getElementById('eipsi_cron_spinner');
    const status = document.getElementById('eipsi_cron_status');

    spinner.style.display = 'inline-block';
    status.textContent = '';
    status.style.color = '';

    const formData = new FormData(form);
    formData.append('action', 'eipsi_save_study_cron_config');

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        spinner.style.display = 'none';

        if (data.success) {
            status.textContent = '✅ ' + data.data.message;
            status.style.color = '#28a745';
            
            // Update status display
            if (data.data.last_run) {
                document.getElementById('cron_last_run').textContent = data.data.last_run;
            }
            if (data.data.next_run) {
                document.getElementById('cron_next_run').textContent = data.data.next_run;
            }
        } else {
            status.textContent = '❌ <?php _e('Error:', 'eipsi-forms'); ?> ' + data.data.message;
            status.style.color = '#dc3545';
        }

        setTimeout(() => { status.textContent = ''; }, 5000);
    })
    .catch(err => {
        spinner.style.display = 'none';
        status.textContent = '❌ <?php _e('Error de conexión. Inténtalo de nuevo.', 'eipsi-forms'); ?>';
        status.style.color = '#dc3545';
        console.error('AJAX Error:', err);
    });
});
</script>