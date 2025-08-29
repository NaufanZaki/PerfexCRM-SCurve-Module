<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scurve_report extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scurve_report/scurve_report_model');
    }

    // This method is not needed for the project tabs, Perfex handles it.
    // public function baseline($project_id) { ... }

    // Handle baseline form submission
    public function save_baseline($project_id)
    {
        // Ensure this is a POST request and user has permissions
        if (!has_permission('projects', '', 'edit')) {
            access_denied('projects');
        }

        if ($this->input->post()) {
            $rows = $this->input->post('rows');
            $success = $this->scurve_report_model->save_baseline($project_id, $rows);
            
            if ($success) {
                set_alert('success', 'Baseline saved successfully.');
            } else {
                set_alert('danger', 'Failed to save baseline.');
            }
        }
        // Redirect to the baseline tab after saving
        redirect(admin_url('projects/view/' . $project_id . '?group=scurve-baseline'));
    }

    // Provide chart data for frontend (AJAX)
    public function get_chart_data($project_id)
    {
        // Check for AJAX request is a good practice
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $rangeType = $this->input->post('range_type'); // We are not using this yet, but it's good to keep
        $startDate = $this->input->post('start_date');
        $endDate   = $this->input->post('end_date');

        // Call the new model function
        $data = $this->scurve_report_model->get_chart_data($project_id, $startDate, $endDate);

        // Send response back to the browser
        echo json_encode([
            'chartData' => $data,
            'csrfName'  => $this->security->get_csrf_token_name(),
            'csrfHash'  => $this->security->get_csrf_hash()
        ]);
    }
}