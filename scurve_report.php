<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: S-Curve Progress Report
Description: Adds a project S-Curve chart to visualize planned vs. actual progress.
Version: 1.1
Author: Naufan Zaki Luqmanulhakim
*/

define('SCURVE_REPORT_MODULE_NAME', 'scurve_report');

/**
 * Debug logging for module
 */
function scurve_report_log($message)
{
    $path = APPPATH . 'logs/scurve_debug.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($path, $line, FILE_APPEND);
}
scurve_report_log('scurve_report.php loaded');

/**
 * Activation hook (creates DB tables, etc.)
 */
register_activation_hook(SCURVE_REPORT_MODULE_NAME, 'scurve_report_activation_hook');
function scurve_report_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files
 */
register_language_files(SCURVE_REPORT_MODULE_NAME, ['scurve_report']);

/**
 * Init hooks
 */
hooks()->add_action('admin_init', 'scurve_report_init');
function scurve_report_init()
{
    $CI = &get_instance();
    $CI->load->helper('url');

    // Load language file
    $CI->lang->load('scurve_report', 'english', false, true, APP_MODULES_PATH . 'scurve_report/');

    // Add project tab
    hooks()->add_filter('project_tabs', 'scurve_add_project_tab');
}

/**
 * Add the "S-Curve Report" tab into project view
 */
function scurve_add_project_tab($tabs)
{
    // S-Curve Report tab
    $tabs[] = [
        'slug'     => 'scurve-report',
        'name'     => _l('scurve_report'),
        'icon'     => 'fa fa-area-chart',
        'view'     => 'scurve_report/admin/scurve_chart_tab',
        'position' => 50,
    ];

    // Baseline Management tab
    $tabs[] = [
        'slug'     => 'scurve-baseline',
        'name'     => _l('scurve_tab_baseline'), // Make sure this key exists in your language file
        'icon'     => 'fa fa-sliders',
        'view'     => 'scurve_report/admin/baseline_tab',
        'position' => 51,
    ];

    return $tabs;
}

/**
 * Add CSS to admin head
 */
hooks()->add_action('app_admin_head', 'scurve_report_add_head_components');
function scurve_report_add_head_components()
{
    echo '<link href="' . module_dir_url('scurve_report', 'assets/scurve.css') . '" rel="stylesheet" type="text/css">';
}

/**
 * Add JS to admin footer
 */
hooks()->add_action('app_admin_footer', 'scurve_report_load_scripts');
function scurve_report_load_scripts()
{
    echo '<script src="' . module_dir_url('scurve_report', 'assets/scurve.js') . '"></script>';
}

/**
 * Cron: Take weekly progress snapshots
 */
hooks()->add_action('after_cron_run', 'scurve_report_cron_snapshot');
function scurve_report_cron_snapshot($manually)
{
    $CI = &get_instance();
    $CI->load->model(SCURVE_REPORT_MODULE_NAME . '/scurve_report_model');

    $last_run_option = 'scurve_last_cron_run';
    $last_run = get_option($last_run_option);

    // Run this cron job only once a week (604800 seconds)
    if ($last_run == '' || (time() > ($last_run + 604800))) {
        $CI->scurve_report_model->take_all_project_snapshots();
        update_option($last_run_option, time());
        scurve_report_log('Cron snapshot executed.');
    }
}
