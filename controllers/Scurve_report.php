<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scurve_report extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scurve_report/scurve_report_model');
    }

    public function save_baseline($project_id)
    {
        if (!has_permission('projects', '', 'edit')) {
            access_denied('projects');
        }

        if ($this->input->post()) {
            $rows = $this->input->post('rows');
            $success = $this->scurve_report_model->save_baseline($project_id, $rows);

            if ($success) {
                set_alert('success', 'Baseline saved successfully.');
            } else {
                set_alert('danger', 'An error occurred while saving the baseline.');
            }
        }

        redirect(admin_url('projects/view/' . $project_id . '?group=scurve-baseline'));
    }

    public function get_chart_data($project_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

        $chart_data = $this->scurve_report_model->get_chart_data($project_id, $start_date, $end_date);

        echo json_encode([
            'chartData' => $chart_data,
            'csrfHash' => $this->security->get_csrf_hash(),
        ]);
    }

}