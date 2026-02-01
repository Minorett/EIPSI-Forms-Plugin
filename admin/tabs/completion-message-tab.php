<?php
/**
 * Finalización Tab
 * Configure integrated thank-you page (no external redirect)
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/completion-message-backend.php';

$config = EIPSI_Completion_Message::get_config();
?>

<div class="eipsi-completion-tab">
    
    <!-- Info Box -->
    <div class="notice notice-info inline" style="margin: 0 0 20px 0;">
        <p>
            <strong><?php _e('Integrated Thank-You Page', 'eipsi-forms'); ?></strong><br>
            <?php _e('After submitting a form, participants will see an integrated thank-you page on the same URL. Configure the title, message, logo display, and action button below.', 'eipsi-forms'); ?>
        </p>
    </div>
    
    <form id="eipsi-completion-message-form" method="post">
        <?php wp_nonce_field('eipsi_admin_nonce', 'eipsi_admin_nonce'); ?>
        
        <!-- Title Field -->
        <div style="margin: 20px 0;">
            <label for="completion_title" style="display: block; margin-bottom: 8px; font-weight: 600;">
                <?php _e('Título', 'eipsi-forms'); ?>
            </label>
            <input type="text" 
                   id="completion_title" 
                   name="title" 
                   value="<?php echo esc_attr($config['title']); ?>" 
                   placeholder="<?php echo esc_attr__('¡Gracias por completar el formulario!', 'eipsi-forms'); ?>"
                   style="width: 100%; max-width: 600px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="display: block; margin-top: 6px; color: #666;">
                <?php _e('Main heading shown on the thank-you page', 'eipsi-forms'); ?>
            </small>
        </div>
        
        <!-- Message Editor -->
        <div style="margin: 20px 0;">
            <label for="completion_message" style="display: block; margin-bottom: 8px; font-weight: 600;">
                <?php _e('Mensaje', 'eipsi-forms'); ?>
            </label>
            <?php 
            wp_editor(
                $config['message'],
                'completion_message',
                array(
                    'textarea_name' => 'message',
                    'media_buttons' => true,
                    'tinymce' => true,
                    'height' => 300,
                )
            );
            ?>
            <small style="display: block; margin-top: 6px; color: #666;">
                <?php _e('Rich text content displayed to participants', 'eipsi-forms'); ?>
            </small>
        </div>
        
        <!-- Options -->
        <div style="margin: 20px 0; background: #f8f9fa; padding: 15px; border-radius: 6px;">
            <h3><?php _e('Display Options', 'eipsi-forms'); ?></h3>
            
            <label style="display: block; margin-bottom: 12px;">
                <input type="checkbox" name="show_logo" <?php checked($config['show_logo']); ?>>
                <?php _e('Mostrar logo del sitio', 'eipsi-forms'); ?>
                <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                    (<?php _e('from Appearance → Customize', 'eipsi-forms'); ?>)
                </span>
            </label>
            
            <label style="display: block; margin-bottom: 12px;">
                <input type="checkbox" name="show_home_button" <?php checked($config['show_home_button']); ?>>
                <?php _e('Mostrar botón "Volver al inicio"', 'eipsi-forms'); ?>
            </label>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                <label for="button_text" style="display: block; margin-bottom: 8px; font-weight: 600;">
                    <?php _e('Texto del botón', 'eipsi-forms'); ?>
                </label>
                <input type="text" 
                       id="button_text" 
                       name="button_text" 
                       value="<?php echo esc_attr($config['button_text']); ?>" 
                       placeholder="<?php echo esc_attr__('Comenzar de nuevo', 'eipsi-forms'); ?>"
                       style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <small style="display: block; margin-top: 6px; color: #666;">
                    <?php _e('Button label (only shown if button is enabled)', 'eipsi-forms'); ?>
                </small>
            </div>
            
            <div style="margin-top: 15px;">
                <label for="button_action" style="display: block; margin-bottom: 8px; font-weight: 600;">
                    <?php _e('Acción del botón', 'eipsi-forms'); ?>
                </label>
                <select id="button_action" 
                        name="button_action" 
                        style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="reload" <?php selected($config['button_action'], 'reload'); ?>>
                        <?php _e('Recargar formulario (ideal para kiosks)', 'eipsi-forms'); ?>
                    </option>
                    <option value="close" <?php selected($config['button_action'], 'close'); ?>>
                        <?php _e('Cerrar pestaña', 'eipsi-forms'); ?>
                    </option>
                    <option value="none" <?php selected($config['button_action'], 'none'); ?>>
                        <?php _e('Ninguna acción', 'eipsi-forms'); ?>
                    </option>
                </select>
                <small style="display: block; margin-top: 6px; color: #666;">
                    <?php _e('What happens when the button is clicked', 'eipsi-forms'); ?>
                </small>
            </div>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                <label style="display: block;">
                    <input type="checkbox" name="show_animation" <?php checked($config['show_animation']); ?>>
                    <?php _e('Animación sutil', 'eipsi-forms'); ?>
                    <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                        (<?php _e('subtle confetti or fade effect', 'eipsi-forms'); ?>)
                    </span>
                </label>
            </div>
        </div>
        
        <!-- Save Button -->
        <button type="submit" class="button button-primary" id="eipsi-save-completion">
            <?php _e('💾 Guardar Configuración', 'eipsi-forms'); ?>
        </button>
        <span id="eipsi-completion-spinner" class="spinner" style="display: none; margin-left: 10px;"></span>
        <span id="eipsi-completion-status" style="margin-left: 10px; font-weight: 600;"></span>
    </form>
    
    <!-- Live Preview Info -->
    <div style="margin: 30px 0; padding: 20px; background: #f0f6fc; border-left: 4px solid #3B6CAA; border-radius: 6px;">
        <h3 style="margin-top: 0;"><?php _e('💡 How it works', 'eipsi-forms'); ?></h3>
        <ul style="margin: 10px 0;">
            <li><?php _e('After submitting any form, participants will see this thank-you page on the <strong>same URL</strong> (no external redirect)', 'eipsi-forms'); ?></li>
            <li><?php _e('The page is dynamically generated and integrated into the form experience', 'eipsi-forms'); ?></li>
            <li><?php _e('Perfect for kiosks, tablets, and clinical environments where URL consistency is important', 'eipsi-forms'); ?></li>
        </ul>
    </div>
    
</div>

<script>
document.getElementById('eipsi-completion-message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const spinner = document.getElementById('eipsi-completion-spinner');
    const status = document.getElementById('eipsi-completion-status');
    
    spinner.style.display = 'inline-block';
    status.textContent = '';
    
    const formData = new FormData(form);
    formData.append('action', 'eipsi_save_completion_message');
    // Rename nonce field to match what AJAX handler expects
    formData.set('nonce', formData.get('eipsi_admin_nonce'));
    formData.delete('eipsi_admin_nonce');
    
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
        } else {
            status.textContent = '❌ <?php _e('Error:', 'eipsi-forms'); ?> ' + data.data.message;
            status.style.color = '#dc3545';
        }
        
        setTimeout(() => { status.textContent = ''; }, 5000);
    })
    .catch(err => {
        spinner.style.display = 'none';
        status.textContent = '❌ <?php _e('Connection error. Please try again.', 'eipsi-forms'); ?>';
        status.style.color = '#dc3545';
        console.error('AJAX Error:', err);
    });
});
</script>
