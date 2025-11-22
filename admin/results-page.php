<?php
/**
 * Results & Experience Page
 * Main admin page with 3 tabs:
 * 1. Submissions (form responses)
 * 2. Completion Message (global thank-you config)
 * 3. Privacy & Metadata (per-form toggles)
 */

if (!defined('ABSPATH')) {
    exit;
}

function vas_display_form_responses() {
    // Verify capability
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'vas-dinamico-forms'));
    }

    // Determine active tab from URL param
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'submissions';
    $allowed_tabs = array('submissions', 'completion', 'privacy');

    if (!in_array($active_tab, $allowed_tabs)) {
        $active_tab = 'submissions';
    }

    // Nonce for AJAX operations
    $nonce = wp_create_nonce('eipsi_admin_nonce');
    ?>

    <div class="wrap eipsi-results-page">
        <h1><?php esc_html_e('Results & Experience', 'vas-dinamico-forms'); ?></h1>
        
        <!-- Tab Navigation (WordPress native style) -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=vas-dinamico-results&tab=submissions" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'submissions') ? 'nav-tab-active' : ''); ?>"
               data-tab="submissions">
                📊 <?php esc_html_e('Submissions', 'vas-dinamico-forms'); ?>
            </a>
            <a href="?page=vas-dinamico-results&tab=completion" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'completion') ? 'nav-tab-active' : ''); ?>"
               data-tab="completion">
                ✅ <?php esc_html_e('Finalización', 'vas-dinamico-forms'); ?>
            </a>
            <a href="?page=vas-dinamico-results&tab=privacy" 
               class="nav-tab <?php echo esc_attr(($active_tab === 'privacy') ? 'nav-tab-active' : ''); ?>"
               data-tab="privacy">
                🔒 <?php esc_html_e('Privacy & Metadata', 'vas-dinamico-forms'); ?>
            </a>
        </h2>
        
        <!-- Message container for AJAX feedback -->
        <div id="eipsi-message-container"></div>
        
        <!-- Tab 1: Submissions -->
        <?php if ($active_tab === 'submissions'): ?>
            <div class="tab-content" data-tab="submissions">
                <?php include dirname(__FILE__) . '/tabs/submissions-tab.php'; ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab 2: Completion Message -->
        <?php if ($active_tab === 'completion'): ?>
            <div class="tab-content" data-tab="completion">
                <?php include dirname(__FILE__) . '/tabs/completion-message-tab.php'; ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab 3: Privacy & Metadata -->
        <?php if ($active_tab === 'privacy'): ?>
            <div class="tab-content" data-tab="privacy">
                <?php include dirname(__FILE__) . '/tabs/privacy-metadata-tab.php'; ?>
            </div>
        <?php endif; ?>
        
    </div>

    <style>
        .eipsi-results-page {
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        
        .nav-tab-wrapper {
            border-bottom: 2px solid #ccc;
            margin: 20px 0;
            padding: 0;
        }
        
        .nav-tab {
            padding: 12px 20px;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .nav-tab:hover {
            color: #005a87;
        }
        
        .nav-tab-active {
            color: #005a87;
            border-bottom-color: #005a87;
            font-weight: 600;
        }
        
        #eipsi-message-container {
            margin: 20px 0;
        }
        
        .tab-content {
            margin-top: 20px;
        }
    </style>
    <?php
}
