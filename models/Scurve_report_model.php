<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scurve_report_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // Save baseline inputs
    public function save_baseline($project_id, $data)
    {
        foreach ($data as $row) {
            $insert = [
                'project_id'       => $project_id,
                'period_label'     => $row['period_label'],
                'plan_cumulative'  => $row['plan_cumulative'],
                'actual_cumulative'=> $row['actual_cumulative'],
                'date_point'       => $row['date_point']
            ];
            $this->db->insert(db_prefix() . 'scurve_baselines', $insert);
        }
    }

    // Fetch baseline by project
    public function get_project_baseline($project_id)
    {
        return $this->db->where('project_id', $project_id)
                        ->order_by('date_point', 'asc')
                        ->get(db_prefix() . 'scurve_baselines')
                        ->result_array();
    }

    
}
    