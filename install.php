<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'scurve_baselines')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "scurve_baselines` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `project_id` INT(11) NOT NULL,
      `period_label` VARCHAR(50) NOT NULL, -- e.g. W1, Feb, 2025
      `plan_cumulative` DECIMAL(5,2) DEFAULT 0,
      `actual_cumulative` DECIMAL(5,2) DEFAULT 0,
      `date_point` DATE NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}
