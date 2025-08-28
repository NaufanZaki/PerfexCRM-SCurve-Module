<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scurve_report extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scurve_report/scurve_report_model');
    }

    // View baseline tab
    public function baseline($project_id)
    {
        $data['project_id'] = $project_id;
        $data['baseline']   = $this->scurve_report_model->get_project_baseline($project_id);
        $this->load->view('scurve_report/admin/baseline_tab', $data);
    }

    // Handle baseline form submission
    public function save_baseline($project_id)
    {
        if ($this->input->post()) {
            $rows = $this->input->post('rows');
            $this->scurve_report_model->save_baseline($project_id, $rows);
            set_alert('success', 'Baseline saved');
        }
        redirect(admin_url('projects/view/' . $project_id . '?group=scurve-report'));
    }

    // Provide chart data for frontend (AJAX)
    public function get_chart_data($project_id)
    {
        $baseline = $this->scurve_report_model->get_project_baseline($project_id);

        $labels = [];
        $plan   = [];
        $actual = [];

        foreach ($baseline as $row) {
            $labels[] = $row['period_label'];
            $plan[]   = (float)$row['plan_cumulative'];
            $actual[] = (float)$row['actual_cumulative'];
        }

        echo json_encode([
            'labels' => $labels,
            'plan'   => $plan,
            'actual' => $actual
        ]);
    }
}
