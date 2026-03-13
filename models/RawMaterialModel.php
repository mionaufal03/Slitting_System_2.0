<?php
class RawMaterialModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function getMonthlyStats($month, $year) {
        $stats = [];

        // Total IN for the month
        $resIn = $this->db->query("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='IN' AND MONTH(date_in)=$month AND YEAR(date_in)=$year");
        $stats['in'] = $resIn->fetch_assoc()['total'];

        // Total OUT for the month
        $resOut = $this->db->query("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='OUT' AND MONTH(date_out)=$month AND YEAR(date_out)=$year");
        $stats['out'] = $resOut->fetch_assoc()['total'];

        // Stock Calculation
        $stats['stock'] = max(0, (int)$stats['in'] - (int)$stats['out']);

        // After Cut Stock (Independent of month in your original logic)
        $resCut = $this->db->query("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='IN' AND action='cut_into_2'");
        $stats['after_cut'] = $resCut->fetch_assoc()['total'];

        return $stats;
    }

    public function getLogs($month, $year) {
        return $this->db->query("SELECT * FROM raw_material_log 
                                 WHERE (MONTH(date_in)=$month AND YEAR(date_in)=$year) 
                                    OR (MONTH(date_out)=$month AND YEAR(date_out)=$year) 
                                 ORDER BY id ASC");
    }

    public function getStockAfterCut($month, $year) {
        return $this->db->query("SELECT * FROM raw_material_log 
                                 WHERE status='IN' AND action='cut_into_2'
                                 AND (MONTH(date_in)=$month AND YEAR(date_in)=$year)
                                 ORDER BY id ASC");
    }
}