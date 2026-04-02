<?php
session_start();

// 1. Authentication & Role Check
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Only 'slitting' role can access this specific dashboard
if ($_SESSION['role'] !== 'slitting') {
    header("Location: index.php");
    exit;
}

include 'config.php';

// 2. Data Fetching Logic
// === RAW MATERIAL SUMMARY ===
$in_raw  = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='IN'")->fetch_assoc()['total'];
$out_raw = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='OUT'")->fetch_assoc()['total'];
$stock_raw = max(0, (int)$in_raw - (int)$out_raw);

$afterCutStock_raw = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log 
                                    WHERE status='IN' AND action='cut_into_2'")
                                    ->fetch_assoc()['total'];

// === FINISH PRODUCT SUMMARY ===
$month = (int)date('m');
$year  = (int)date('Y');

$in_finish = $conn->query("SELECT COUNT(*) AS total FROM slitting_product 
                           WHERE status='IN' AND is_completed=0
                           AND (is_recoiled=0 OR is_recoiled IS NULL) 
                           AND (is_reslitted=0 OR is_reslitted IS NULL)")
                           ->fetch_assoc()['total'];

$stock_finish = $conn->query("SELECT COUNT(*) AS total FROM slitting_product 
                              WHERE status='IN' AND stock_counted=1
                              AND (is_recoiled=0 OR is_recoiled IS NULL) 
                              AND (is_reslitted=0 OR is_reslitted IS NULL)")
                              ->fetch_assoc()['total'];

$out_finish = $conn->query("SELECT COUNT(*) AS total FROM slitting_product 
                            WHERE status='OUT' 
                            AND MONTH(date_out)=$month AND YEAR(date_out)=$year")
                            ->fetch_assoc()['total'];

$deliver_finish = $conn->query("SELECT COUNT(*) AS total FROM slitting_product 
                                WHERE status='DELIVERED' 
                                AND MONTH(delivered_at)=$month AND YEAR(delivered_at)=$year")
                                ->fetch_assoc()['total'];

// 3. Set Page Title and Include Standard Header
$page_title = "Dashboard - MK Slitting";
include 'header.php'; 
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard - Metakote Slitting</h2>
    <span class="badge bg-dark p-2"><?php echo date('d M Y'); ?></span>
</div>

<?php if (isset($_GET['scan'])): ?>
    <div class="alert alert-info alert-dismissible fade show py-2 shadow-sm">
        <i class="bi bi-qr-code-scan me-2"></i> Scan status: <strong><?= htmlspecialchars($_GET['scan']) ?></strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form id="scanForm" method="post" action="scan_mother_action.php" autocomplete="off" style="position:absolute; left:-9999px;">
    <input id="qrInput" type="text" name="qr" autofocus>
</form>

<div class="row g-4">

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white fw-bold">
                <i class="bi bi-box-seam me-2"></i> Raw Material Summary
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">IN</small>
                        <h4 class="text-success fw-bold"><?= $in_raw ?></h4>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">OUT</small>
                        <h4 class="text-danger fw-bold"><?= $out_raw ?></h4>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">STOCK</small>
                        <h4 class="text-primary fw-bold"><?= $stock_raw ?></h4>
                    </div>
                    <div class="col-3">
                        <small class="text-muted d-block">CUT 2</small>
                        <h4 class="text-warning fw-bold"><?= $afterCutStock_raw ?></h4>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="raw_material.php" class="btn btn-outline-success btn-sm w-100">View Raw Material Details</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-scissors me-2"></i> Slitting Product (Monthly)
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">IN</small>
                        <h4 class="text-success fw-bold"><?= $in_finish ?></h4>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">STOCK</small>
                        <h4 class="text-primary fw-bold"><?= $stock_finish ?></h4>
                    </div>
                    <div class="col-3 border-end">
                        <small class="text-muted d-block">OUT</small>
                        <h4 class="text-danger fw-bold"><?= $out_finish ?></h4>
                    </div>
                    <div class="col-3">
                        <small class="text-muted d-block">DELIVER</small>
                        <h4 class="text-warning fw-bold"><?= $deliver_finish ?></h4>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="finish_product.php" class="btn btn-outline-primary btn-sm w-100">View Finished Goods List</a>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('click', function() {
        document.getElementById('qrInput').focus();
    });
</script>

<?php 
// 4. Include Standard Footer (Closes divs and body)
include 'footer.php'; 
?>