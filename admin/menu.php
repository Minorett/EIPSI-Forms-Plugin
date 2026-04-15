<?php
if (!defined('ABSPATH')) {
    exit;
}

function eipsi_forms_menu() {
    $capabilities = function_exists('eipsi_get_menu_capabilities')
        ? eipsi_get_menu_capabilities()
        : apply_filters('eipsi_forms_menu_capabilities', array(
            'main' => 'edit_posts',
            'results' => 'manage_options',
            'configuration' => 'manage_options',
            'longitudinal' => 'edit_posts',
            'form_library' => 'edit_posts'
        ));

    // Menu Principal: EIPSI Forms
    add_menu_page(
        __('EIPSI Forms', 'eipsi-forms'),
        __('EIPSI Forms', 'eipsi-forms'),
        $capabilities['main'],
        'eipsi-results-experience',
        'eipsi_display_results_experience_page',
        plugin_dir_url(__FILE__) . '../assets/eipsi-icon-menu.svg',
        25
    );

    // Submenú: Cross-sectional Study
    add_submenu_page(
        'eipsi-results-experience',
        __('Cross-sectional Study', 'eipsi-forms'),
        __('Cross-sectional Study', 'eipsi-forms'),
        $capabilities['results'],
        'eipsi-results-experience',
        'eipsi_display_results_experience_page'
    );

    // Submenú: Configuration
    add_submenu_page(
        'eipsi-results-experience',
        __('Configuration', 'eipsi-forms'),
        __('Configuration', 'eipsi-forms'),
        $capabilities['configuration'],
        'eipsi-configuration',
        'eipsi_display_configuration_page'
    );

    // Submenú: Longitudinal Study
    add_submenu_page(
        'eipsi-results-experience',
        __('Longitudinal Study', 'eipsi-forms'),
        __('Longitudinal Study', 'eipsi-forms'),
        $capabilities['longitudinal'],
        'eipsi-longitudinal-study',
        'eipsi_display_longitudinal_study_page'
    );

    // Submenú: Form Library
    add_submenu_page(
        'eipsi-results-experience',
        __('Form Library', 'eipsi-forms'),
        __('Form Library', 'eipsi-forms'),
        $capabilities['form_library'],
        'edit.php?post_type=eipsi_form_template'
    );
}

/**
 * Handle redirects for backwards compatibility from old menu URLs.
 * Redirects old standalone pages to new tabbed structure.
 */
function eipsi_admin_menu_redirects() {
    if (!is_admin()) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    
    // Skip if no page parameter or already on correct page
    if (empty($page)) {
        return;
    }

    // Get current tab to avoid redirect loops
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';

    switch ($page) {
        case 'eipsi-randomization':
            // Redirect to Cross-sectional Study > Randomization tab
            if ($current_tab !== 'randomization') {
                $redirect_url = add_query_arg(
                    array(
                        'page' => 'eipsi-results-experience',
                        'tab' => 'randomization'
                    ),
                    admin_url('admin.php')
                );
                wp_redirect($redirect_url);
                exit;
            }
            break;

        case 'eipsi-longitudinal-pools':
            // Redirect to Longitudinal Study > Longitudinal Pools tab
            if ($current_tab !== 'longitudinal-pools') {
                $redirect_url = add_query_arg(
                    array(
                        'page' => 'eipsi-longitudinal-study',
                        'tab' => 'longitudinal-pools'
                    ),
                    admin_url('admin.php')
                );
                wp_redirect($redirect_url);
                exit;
            }
            break;

        case 'eipsi-pool-dashboard':
            // Redirect to Longitudinal Study > Pool Analytics tab
            if ($current_tab !== 'pool-analytics') {
                $redirect_url = add_query_arg(
                    array(
                        'page' => 'eipsi-longitudinal-study',
                        'tab' => 'pool-analytics'
                    ),
                    admin_url('admin.php')
                );
                wp_redirect($redirect_url);
                exit;
            }
            break;

        case 'eipsi-configuration':
            // Redirect old notifications tab to Longitudinal Study > Recordatorios
            if ($current_tab === 'notifications') {
                $redirect_url = add_query_arg(
                    array(
                        'page' => 'eipsi-longitudinal-study',
                        'tab' => 'reminders'
                    ),
                    admin_url('admin.php')
                );
                wp_redirect($redirect_url);
                exit;
            }
            break;
    }
}
add_action('admin_menu', 'eipsi_forms_menu', 1);
add_action('admin_init', 'eipsi_admin_menu_redirects', 1);
