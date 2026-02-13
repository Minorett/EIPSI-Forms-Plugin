<?php
if (!defined('ABSPATH')) {
    exit;
}

function eipsi_forms_menu() {
    // Menu Principal: EIPSI Forms
    add_menu_page(
        __('EIPSI Forms', 'eipsi-forms'),
        __('EIPSI Forms', 'eipsi-forms'),
        'manage_options',
        'eipsi-results',
        'eipsi_display_form_responses',
        plugin_dir_url(__FILE__) . '../assets/eipsi-icon-menu.svg',
        25
    );

    // Submenú: Longitudinal Study (REORGANIZADO - v1.5.0)
    // Este es ahora el punto central para todas las funcionalidades longitudinales
    add_submenu_page(
        'eipsi-results',
        __('Longitudinal Study Dashboard', 'eipsi-forms'),
        __('Longitudinal Study', 'eipsi-forms'),
        'manage_options',
        'eipsi-results',
        'eipsi_display_form_responses'
    );

    // Submenú: Configuration (mantenido separado)
    add_submenu_page(
        'eipsi-results',
        __('Database Configuration', 'eipsi-forms'),
        __('Configuration', 'eipsi-forms'),
        'manage_options',
        'eipsi-db-config',
        'eipsi_display_configuration_page'
    );

    // Submenú: Create New Study (redirige al Setup Wizard)
    add_submenu_page(
        'eipsi-results',
        __('Create New Longitudinal Study', 'eipsi-forms'),
        __('Create Study', 'eipsi-forms'),
        'manage_options',
        'eipsi-new-study',
        'eipsi_display_setup_wizard_page'
    );
}

add_action('admin_menu', 'eipsi_forms_menu');