<?php
/**
 * Results & Experience Page
 * Main admin page with tabs:
 * 1. Submissions (form responses)
 * 2. Completion Message (global thank-you config)
 * 3. Privacy & Metadata (per-form toggles)
 * 4. Randomization (RCT settings)
 */

if (!defined('ABSPATH')) {
    exit;
}

function eipsi_display_results_experience_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'eipsi-forms'));
    }

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'submissions';
    $allowed_tabs = array(
        'submissions',
        'completion',
        'privacy',
        'randomization'
    );

    if (!in_array($active_tab, $allowed_tabs, true)) {
        $active_tab = 'submissions';
    }

    $nonce = wp_create_nonce('eipsi_admin_nonce');
    ?>

    <div class="wrap eipsi-results-page">
        <h1><?php esc_html_e('EIPSI Forms - Results & Experience', 'eipsi-forms'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=eipsi-results-experience&tab=submissions"
               class="nav-tab <?php echo esc_attr(($active_tab === 'submissions') ? 'nav-tab-active' : ''); ?>"
               data-tab="submissions">
                📊 <?php esc_html_e('Submissions', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results-experience&tab=completion"
               class="nav-tab <?php echo esc_attr(($active_tab === 'completion') ? 'nav-tab-active' : ''); ?>"
               data-tab="completion">
                ✅ <?php esc_html_e('Finalización', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results-experience&tab=privacy"
               class="nav-tab <?php echo esc_attr(($active_tab === 'privacy') ? 'nav-tab-active' : ''); ?>"
               data-tab="privacy">
                🔒 <?php esc_html_e('Privacy & Metadata', 'eipsi-forms'); ?>
            </a>
            <a href="?page=eipsi-results-experience&tab=randomization"
               class="nav-tab <?php echo esc_attr(($active_tab === 'randomization') ? 'nav-tab-active' : ''); ?>"
               data-tab="randomization">
                🎲 <?php esc_html_e('Randomization', 'eipsi-forms'); ?>
            </a>
        </h2>

        <div id="eipsi-message-container"></div>

        <?php if ($active_tab === 'submissions'): ?>
            <div class="tab-content" data-tab="submissions">
                <?php include dirname(__FILE__) . '/tabs/submissions-tab.php'; ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'completion'): ?>
            <div class="tab-content" data-tab="completion">
                <?php include dirname(__FILE__) . '/tabs/completion-message-tab.php'; ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'privacy'): ?>
            <div class="tab-content" data-tab="privacy">
                <?php include dirname(__FILE__) . '/tabs/privacy-metadata-tab.php'; ?>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'randomization'): ?>
            <div class="tab-content" data-tab="randomization">
                <?php
                if (file_exists(dirname(__FILE__) . '/randomization-page.php')) {
                    require_once dirname(__FILE__) . '/randomization-page.php';
                    eipsi_display_randomization();
                } else {
                    echo '<p>' . esc_html__('Error: Randomization no disponible', 'eipsi-forms') . '</p>';
                }
                ?>
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
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0;
        }

        .nav-tab {
            padding: 12px 20px;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            display: inline-block;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-tab:hover {
            color: #3B6CAA;
            background-color: #f5f5f5;
        }

        .nav-tab-active {
            color: #3B6CAA;
            border-bottom-color: #3B6CAA;
            font-weight: 600;
            background-color: #fff;
        }

        @media (max-width: 1200px) {
            .nav-tab-wrapper {
                gap: 5px;
            }

            .nav-tab {
                padding: 10px 15px;
                font-size: 13px;
            }
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

function eipsi_display_form_responses() {
    eipsi_display_results_experience_page();
}
