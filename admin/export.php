<?php
if (!defined('ABSPATH')) {
    exit;
}

// Verificar y definir la constante si no existe
if (!defined('VAS_DINAMICO_PLUGIN_DIR')) {
    define('VAS_DINAMICO_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Incluir la librería
require_once VAS_DINAMICO_PLUGIN_DIR . 'lib/SimpleXLSXGen.php';

// Usar el namespace
use Shuchkin\SimpleXLSXGen;

function vas_export_to_excel() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'vas-dinamico-forms'));
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // Obtener formulario específico si se filtra
    $form_filter = isset($_GET['form_name']) ? $wpdb->prepare('AND form_name = %s', $_GET['form_name']) : '';
    
    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1 $form_filter ORDER BY created_at DESC");
    
    if (empty($results)) {
        wp_die(__('No data to export.', 'vas-dinamico-forms'));
    }
    
    // Obtener todas las preguntas únicas para crear columnas
    $all_questions = [];
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        foreach ($form_data as $question => $answer) {
            if (!in_array($question, $all_questions)) {
                $all_questions[] = $question;
            }
        }
    }
    
    $data = array();
    // Encabezados: metadatos + preguntas dinámicas
    $headers = array('ID', 'Form', 'Date', 'Duration (s)', 'IP', 'Device', 'Browser', 'OS');
    $headers = array_merge($headers, $all_questions);
    $data[] = $headers;
    
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        
        $row_data = array(
            $row->id,
            $row->form_name,
            $row->created_at,
            $row->duration,
            $row->ip_address,
            $row->device,
            $row->browser,
            $row->os
        );
        
        // Agregar respuestas en el orden de las preguntas
        foreach ($all_questions as $question) {
            $row_data[] = isset($form_data[$question]) ? (is_array($form_data[$question]) ? json_encode($form_data[$question]) : $form_data[$question]) : '';
        }
        
        $data[] = $row_data;
    }
    
    $xlsx = SimpleXLSXGen::fromArray($data);
    $form_suffix = isset($_GET['form_name']) ? '-' . sanitize_title($_GET['form_name']) : '';
    $filename = 'form-responses' . $form_suffix . '-' . date('Y-m-d-H-i-s') . '.xlsx';
    $xlsx->downloadAs($filename);
    exit;
}

function vas_export_to_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'vas-dinamico-forms'));
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // Obtener formulario específico si se filtra
    $form_filter = isset($_GET['form_name']) ? $wpdb->prepare('AND form_name = %s', $_GET['form_name']) : '';
    
    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1 $form_filter ORDER BY created_at DESC");
    
    if (empty($results)) {
        wp_die(__('No data to export.', 'vas-dinamico-forms'));
    }
    
    // Obtener todas las preguntas únicas para crear columnas
    $all_questions = [];
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        foreach ($form_data as $question => $answer) {
            if (!in_array($question, $all_questions)) {
                $all_questions[] = $question;
            }
        }
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    $form_suffix = isset($_GET['form_name']) ? '-' . sanitize_title($_GET['form_name']) : '';
    header('Content-Disposition: attachment; filename=form-responses' . $form_suffix . '-' . date('Y-m-d-H-i-s') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados: metadatos + preguntas dinámicas
    $headers = array('ID', 'Form', 'Date', 'Duration (s)', 'IP', 'Device', 'Browser', 'OS');
    $headers = array_merge($headers, $all_questions);
    fputcsv($output, $headers);
    
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        
        $row_data = array(
            $row->id,
            $row->form_name,
            $row->created_at,
            $row->duration,
            $row->ip_address,
            $row->device,
            $row->browser,
            $row->os
        );
        
        // Agregar respuestas en el orden de las preguntas
        foreach ($all_questions as $question) {
            $row_data[] = isset($form_data[$question]) ? (is_array($form_data[$question]) ? json_encode($form_data[$question]) : $form_data[$question]) : '';
        }
        
        fputcsv($output, $row_data);
    }
    
    fclose($output);
    exit;
}

add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'vas-dinamico-results') {
        if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
            vas_export_to_excel();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            vas_export_to_csv();
        }
    }
});