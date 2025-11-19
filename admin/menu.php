<?php
if (!defined('ABSPATH')) {
    exit;
}

function vas_dinamico_menu() {
    add_menu_page(
        __('EIPSI Forms', 'vas-dinamico-forms'),
        __('EIPSI Forms', 'vas-dinamico-forms'),
        'manage_options',
        'vas-dinamico-results',
        'vas_display_form_responses',
        plugin_dir_url(__FILE__) . '../assets/eipsi-icon-menu.svg',
        25
    );
    
    // Add submenu for Results & Experience (consolidated admin panel)
    add_submenu_page(
        'vas-dinamico-results',
        __('Results & Experience', 'vas-dinamico-forms'),
        __('Results & Experience', 'vas-dinamico-forms'),
        'manage_options',
        'vas-dinamico-results',
        'vas_display_form_responses'
    );
    
    // Add submenu for Configuration
    add_submenu_page(
        'vas-dinamico-results',
        __('Database Configuration', 'vas-dinamico-forms'),
        __('Configuration', 'vas-dinamico-forms'),
        'manage_options',
        'eipsi-db-config',
        'eipsi_display_configuration_page'
    );
}

add_action('admin_menu', 'vas_dinamico_menu');