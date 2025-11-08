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
}

add_action('admin_menu', 'vas_dinamico_menu');