<?php
if (!defined('ABSPATH')) {
    exit;
}

function eipsi_forms_menu() {
    $capabilities = apply_filters('eipsi_forms_menu_capabilities', array(
        'main' => 'edit_posts',
        'results' => 'manage_options',
        'configuration' => 'manage_options',
        'longitudinal' => 'manage_options',
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

    // Submenú: Results & Experience
    add_submenu_page(
        'eipsi-results-experience',
        __('Results & Experience', 'eipsi-forms'),
        __('Results & Experience', 'eipsi-forms'),
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

add_action('admin_menu', 'eipsi_forms_menu');
