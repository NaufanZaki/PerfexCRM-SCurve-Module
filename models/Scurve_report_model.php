<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scurve_report_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function save_baseline($project_id, $data)
    {
        $this->db->trans_begin();

        $this->db->where('project_id', $project_id);
        $this->db->delete(db_prefix() . 'scurve_baselines');

        $batch_data = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $row) {
                if (!empty($row['date_point']) && !empty($row['period_label'])) {
                    $batch_data[] = [
                        'project_id' => $project_id,
                        'period_label' => $row['period_label'],
                        'plan_cumulative' => !empty($row['plan_cumulative']) ? $row['plan_cumulative'] : 0,
                        'actual_cumulative' => !empty($row['actual_cumulative']) ? $row['actual_cumulative'] : 0,
                        'date_point' => $row['date_point']
                    ];
                }
            }
        }

        if (count($batch_data) > 0) {
            $this->db->insert_batch(db_prefix() . 'scurve_baselines', $batch_data);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }

    public function get_baseline_data($project_id)
    {
        return $this->db->where('project_id', $project_id)
            ->order_by('date_point', 'ASC')
            ->get('tblscurve_baselines')
            ->result_array();
    }


    public function get_chart_data($project_id, $start_date = null, $end_date = null)
    {
        $this->db->where('project_id', $project_id);

        if (!empty($start_date)) {
            $this->db->where('date_point >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('date_point <=', $end_date);
        }

        $this->db->order_by('date_point', 'ASC');
        $query = $this->db->get(db_prefix() . 'scurve_baselines');

        $labels = [];
        $plan = [];
        $actual = [];

        $todayIndex = null;
        $today = date('Y-m-d');

        $results = $query->result();

        foreach ($results as $index => $row) {
            $labels[] = $row->period_label;
            $plan[] = (float) $row->plan_cumulative;
            $actual[] = (float) $row->actual_cumulative;

            // If today is equal or after this point, mark it
            if ($row->date_point == $today && $todayIndex === null) {
                $todayIndex = $index;
            }
        }

        return [
            'labels' => $labels,
            'plan' => $plan,
            'actual' => $actual,
            'todayIndex' => $todayIndex,
        ];
    }
}
