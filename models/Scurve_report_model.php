<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scurve_report_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Save baseline inputs for a project.
     * This will delete the old baseline and insert the new one.
     */
    public function save_baseline($project_id, $data)
    {
        // Start a transaction to ensure data integrity
        $this->db->trans_begin();

        // Delete the existing baseline for this project first
        $this->db->where('project_id', $project_id);
        $this->db->delete(db_prefix() . 'scurve_baselines');

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $row) {
                // Only insert if there's a date and a label
                if (!empty($row['date_point']) && !empty($row['period_label'])) {
                    $insert = [
                        'project_id'       => $project_id,
                        'period_label'     => $row['period_label'],
                        'plan_cumulative'  => !empty($row['plan_cumulative']) ? $row['plan_cumulative'] : 0,
                        'actual_cumulative'=> !empty($row['actual_cumulative']) ? $row['actual_cumulative'] : 0,
                        'date_point'       => $row['date_point']
                    ];
                    $this->db->insert(db_prefix() . 'scurve_baselines', $insert);
                }
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }

    /**
     * Fetch baseline data for editing in the baseline tab.
     */
    public function get_project_baseline($project_id)
    {
        return $this->db->where('project_id', $project_id)
                        ->order_by('date_point', 'asc')
                        ->get(db_prefix() . 'scurve_baselines')
                        ->result_array();
    }

    /**
     * Fetch and format data for the S-Curve chart.
     * This function was missing.
     */
    public function get_chart_data($project_id, $startDate = null, $endDate = null)
    {
        $this->db->where('project_id', $project_id);
        $this->db->order_by('date_point', 'ASC');

        // Optional: Filter by date range if provided
        if ($startDate && $endDate) {
            $this->db->where('date_point >=', $startDate);
            $this->db->where('date_point <=', $endDate);
        }

        $baseline_data = $this->db->get(db_prefix() . 'scurve_baselines')->result_array();

        $labels = [];
        $plan_data = [];
        $actual_data = [];
        $today_index = null;
        $current_date = date('Y-m-d');

        foreach ($baseline_data as $i => $row) {
            $labels[] = $row['period_label'];
            $plan_data[] = $row['plan_cumulative'];
            $actual_data[] = $row['actual_cumulative'];
            
            // Find the index for "Today's" line
            if ($today_index === null && $row['date_point'] >= $current_date) {
                $today_index = $i > 0 ? $i - 0.5 : 0; // Place line between points
            }
        }

        return [
            'labels' => $labels,
            'plan'   => $plan_data,
            'actual' => $actual_data,
            'todayIndex' => $today_index,
        ];
    }
}