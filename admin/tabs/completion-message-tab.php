<?php
/**
 * Completion Message Tab
 * Configure global thank-you message for all forms
 * Uses EIPSI_Completion_Message class from previous task
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
            <strong><?php _e('Global Configuration', 'vas-dinamico-forms'); ?></strong><br>
            <?php _e('This message is displayed to all participants after they successfully submit any form. Configure the message, logo display, action buttons, and optional redirect URL below.', 'vas-dinamico-forms'); ?>
        </p>
    </div>
    
    <form id="eipsi-completion-message-form" method="post">
        <?php wp_nonce_field('eipsi_admin_nonce', 'eipsi_nonce'); ?>
        
        <!-- Message Editor -->
        <div style="margin: 20px 0;">
            <label for="completion_message" style="display: block; margin-bottom: 8px; font-weight: 600;">
                <?php _e('Completion Message', 'vas-dinamico-forms'); ?>
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
        </div>
        
        <!-- Options -->
        <div style="margin: 20px 0; background: #f8f9fa; padding: 15px; border-radius: 6px;">
            <h3><?php _e('Message Options', 'vas-dinamico-forms'); ?></h3>
            
            <label style="display: block; margin-bottom: 12px;">
                <input type="checkbox" name="show_logo" <?php checked($config['show_logo']); ?>>
                <?php _e('Show Site Logo', 'vas-dinamico-forms'); ?>
                <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                    (<?php _e('from Appearance → Customize', 'vas-dinamico-forms'); ?>)
                </span>
            </label>
            
            <label style="display: block; margin-bottom: 12px;">
                <input type="checkbox" name="show_home_button" <?php checked($config['show_home_button']); ?>>
                <?php _e('Show "Return to Start" Button', 'vas-dinamico-forms'); ?>
            </label>
            
            <label style="display: block;">
                <?php _e('Redirect URL (Optional)', 'vas-dinamico-forms'); ?><br>
                <input type="url" 
                       name="redirect_url" 
                       value="<?php echo esc_attr($config['redirect_url']); ?>" 
                       placeholder="https://example.com"
                       style="width: 100%; max-width: 500px; padding: 8px; margin-top: 6px; border: 1px solid #ddd; border-radius: 4px;">
                <small style="display: block; margin-top: 6px; color: #666;">
                    <?php _e('If set, participants will see a "Continue" button linking to this URL.', 'vas-dinamico-forms'); ?>
                </small>
            </label>
        </div>
        
        <!-- Save Button -->
        <button type="submit" class="button button-primary" id="eipsi-save-completion">
            <?php _e('💾 Save Completion Message', 'vas-dinamico-forms'); ?>
        </button>
        <span id="eipsi-completion-spinner" class="spinner" style="display: none; margin-left: 10px;"></span>
        <span id="eipsi-completion-status" style="margin-left: 10px; font-weight: 600;"></span>
    </form>
    
    <!-- Live Preview -->
    <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 6px;">
        <h3><?php _e('Live Preview', 'vas-dinamico-forms'); ?></h3>
        <p style="color: #666; margin-bottom: 15px;">
            <?php _e('This is how participants will see the completion message:', 'vas-dinamico-forms'); ?>
        </p>
        
        <div style="max-width: 600px; margin: 0 auto;">
            <iframe id="eipsi-preview-frame" 
                    src="<?php echo esc_url(EIPSI_Completion_Message::get_page_url()); ?>" 
                    style="width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 6px; background: white;">
            </iframe>
        </div>
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
    formData.set('nonce', formData.get('eipsi_nonce'));
    formData.delete('eipsi_nonce');
    
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
            
            // Refresh preview
            const frame = document.getElementById('eipsi-preview-frame');
            frame.src = frame.src; // Reload iframe
        } else {
            status.textContent = '❌ <?php _e('Error:', 'vas-dinamico-forms'); ?> ' + data.data.message;
            status.style.color = '#dc3545';
        }
        
        setTimeout(() => { status.textContent = ''; }, 5000);
    })
    .catch(err => {
        spinner.style.display = 'none';
        status.textContent = '❌ <?php _e('Connection error. Please try again.', 'vas-dinamico-forms'); ?>';
        status.style.color = '#dc3545';
        console.error('AJAX Error:', err);
    });
});
</script>
