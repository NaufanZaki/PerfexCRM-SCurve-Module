<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI =& get_instance();

$table = db_prefix() . 'scurve_baselines';

// Create table if not exists
if (!$CI->db->table_exists($table)) {
    $CI->db->query("
        CREATE TABLE `$table` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `period_label` VARCHAR(50) NOT NULL,
            `plan_cumulative` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `actual_cumulative` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `date_point` DATE NOT NULL,
            PRIMARY KEY (`id`),
            KEY `project_id` (`project_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

// Always refresh table (clear all old data)
$CI->db->truncate($table);
