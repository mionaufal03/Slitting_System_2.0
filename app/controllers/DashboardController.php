<?php
namespace App\Controllers;

use App\Models\RawMaterialModel;

class DashboardController
{
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index()
    {
        session_start();

        if (!isset($_SESSION['role'])) {
            header("Location: /login.php");
            exit;
        }

        // Only ADMIN can access dashboard
        if ($_SESSION['role'] !== 'slitting') {
            header("Location: /index.php");
            exit;
        }

        // === RAW MATERIAL SUMMARY ===
        $rawMaterialModel = new RawMaterialModel($this->pdo);
        $stats = $rawMaterialModel->getMonthlyStats(date('m'), date('Y'));
        $in_raw = $stats['in'];
        $out_raw = $stats['out'];
        $stock_raw = $stats['stock'];
        $afterCutStock_raw = $stats['after_cut'];

        // === FINISH PRODUCT ===
        // Similar, but need a FinishProductModel, for now keep simple
        $month = (int)date('m');
        $year  = (int)date('Y');

        $stmtIn = $this->pdo->prepare("SELECT COUNT(*) AS total FROM slitting_product 
                                       WHERE status='IN' AND is_completed=0
                                       AND (is_recoiled=0 OR is_recoiled IS NULL) 
                                       AND (is_reslitted=0 OR is_reslitted IS NULL)");
        $stmtIn->execute();
        $in_finish = $stmtIn->fetch()['total'];

        $stmtStock = $this->pdo->prepare("SELECT COUNT(*) AS total FROM slitting_product 
                                          WHERE status='IN' AND stock_counted=1
                                          AND (is_recoiled=0 OR is_recoiled IS NULL) 
                                          AND (is_reslitted=0 OR is_reslitted IS NULL)");
        $stmtStock->execute();
        $stock_finish = $stmtStock->fetch()['total'];

        $stmtOut = $this->pdo->prepare("SELECT COUNT(*) AS total FROM slitting_product 
                                        WHERE status='OUT' 
                                        AND MONTH(date_out)=? AND YEAR(date_out)=?");
        $stmtOut->execute([$month, $year]);
        $out_finish = $stmtOut->fetch()['total'];

        $stmtWaiting = $this->pdo->prepare("SELECT COUNT(*) AS total FROM slitting_product 
                                            WHERE status='IN' AND qc_approved=0");
        $stmtWaiting->execute();
        $deliver_finish = $stmtWaiting->fetch()['total'];

        // Pass to view
        require_once __DIR__ . '/../views/dashboard.php';
    }
}