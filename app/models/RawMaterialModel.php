<?php
namespace App\Models;

class RawMaterialModel {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function getByIdAndStatus($id, $status, $action) {
        $stmt = $this->db->prepare("SELECT * FROM raw_material_log WHERE id = ? AND status = ? AND action = ?");
        $stmt->execute([$id, $status, $action]);
        return $stmt->fetch();
    }

    public function getMonthlyStats($month, $year) {
        $stats = [];

        // Total IN for the month
        $stmtIn = $this->db->prepare("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='IN' AND MONTH(date_in)=? AND YEAR(date_in)=?");
        $stmtIn->execute([$month, $year]);
        $stats['in'] = $stmtIn->fetch()['total'];

        // Total OUT for the month
        $stmtOut = $this->db->prepare("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='OUT' AND MONTH(date_out)=? AND YEAR(date_out)=?");
        $stmtOut->execute([$month, $year]);
        $stats['out'] = $stmtOut->fetch()['total'];

        // Stock Calculation
        $stats['stock'] = max(0, (int)$stats['in'] - (int)$stats['out']);

        // After Cut Stock (Independent of month in your original logic)
        $stmtCut = $this->db->prepare("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='IN' AND action='cut_into_2'");
        $stmtCut->execute();
        $stats['after_cut'] = $stmtCut->fetch()['total'];

        return $stats;
    }

    public function getLogs($month, $year) {
        $stmt = $this->db->prepare("SELECT * FROM raw_material_log 
                                    WHERE (MONTH(date_in)=? AND YEAR(date_in)=?) 
                                       OR (MONTH(date_out)=? AND YEAR(date_out)=?) 
                                    ORDER BY id ASC");
        $stmt->execute([$month, $year, $month, $year]);
        return $stmt->fetchAll();
    }

    public function getStockAfterCut($month, $year) {
        $stmt = $this->db->prepare("SELECT * FROM raw_material_log 
                                    WHERE status='IN' AND action='cut_into_2'
                                    AND (MONTH(date_in)=? AND YEAR(date_in)=?)
                                    ORDER BY id ASC");
        $stmt->execute([$month, $year]);
        return $stmt->fetchAll();
    }
}