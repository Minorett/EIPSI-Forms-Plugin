<?php
if (!defined('ABSPATH')) {
    exit;
}

// AJAX: Get export statistics
add_action('wp_ajax_eipsi_get_export_stats', 'eipsi_get_export_stats_handler');

function eipsi_get_export_stats_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
    if (!$survey_id) {
        wp_send_json_error(array('message' => 'Invalid survey ID'));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-export-service.php';
    $export_service = new EIPSI_Export_Service();
    $stats = $export_service->get_export_statistics($survey_id);
    
    wp_send_json_success($stats);
}

// AJAX: Export to Excel
add_action('wp_ajax_eipsi_export_to_excel', 'eipsi_export_to_excel_handler');

function eipsi_export_to_excel_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
    $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();
    
    if (!$survey_id) {
        wp_send_json_error(array('message' => 'Invalid survey ID'));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-export-service.php';
    $export_service = new EIPSI_Export_Service();
    $data = $export_service->export_longitudinal_data($survey_id, $filters);
    $filename = $export_service->export_to_excel($data, $survey_id);
    
    wp_send_json_success(array('filename' => $filename));
}

// AJAX: Export to CSV
add_action('wp_ajax_eipsi_export_to_csv', 'eipsi_export_to_csv_handler');

function eipsi_export_to_csv_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
    $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();
    
    if (!$survey_id) {
        wp_send_json_error(array('message' => 'Invalid survey ID'));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-export-service.php';
    $export_service = new EIPSI_Export_Service();
    $data = $export_service->export_longitudinal_data($survey_id, $filters);
    $filename = $export_service->export_to_csv($data, $survey_id);
    
    wp_send_json_success(array('filename' => $filename));
}