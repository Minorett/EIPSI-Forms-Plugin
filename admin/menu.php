<?php
if (!defined('ABSPATH')) {
    exit;
}

function eipsi_forms_menu() {
    add_menu_page(
        __('EIPSI Forms', 'eipsi-forms'),
        __('EIPSI Forms', 'eipsi-forms'),
        'manage_options',
        'eipsi-results',
        'eipsi_display_form_responses',
        plugin_dir_url(__FILE__) . '../assets/eipsi-icon-menu.svg',
        25
    );
    
    // Add submenu for Results & Experience (consolidated admin panel)
    add_submenu_page(
        'eipsi-results',
        __('Results & Experience', 'eipsi-forms'),
        __('Results & Experience', 'eipsi-forms'),
        'manage_options',
        'eipsi-results',
        'eipsi_display_form_responses'
    );
    
    // Add submenu for Configuration
    add_submenu_page(
        'eipsi-results',
        __('Database Configuration', 'eipsi-forms'),
        __('Configuration', 'eipsi-forms'),
        'manage_options',
        'eipsi-db-config',
        'eipsi_display_configuration_page'
    );
    
    // Add submenu for Setup Wizard
    add_submenu_page(
        'eipsi-results',
        __('Create New Longitudinal Study', 'eipsi-forms'),
        __('Longitudinal Study', 'eipsi-forms'),
        'manage_options',
        'eipsi-new-study',
        'eipsi_display_setup_wizard_page'
    );
}

add_action('admin_menu', 'eipsi_forms_menu');