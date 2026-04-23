<?php
session_start();

// 1. Authentication & Role Check
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'slitting') {
    die("Access denied");
}

require_once 'config.php';

// 2. Data Logic: Historical summary from the new Unified Audit Log
$monthly_summary_query = "
    SELECT 
        DATE_FORMAT(performed_at, '%Y-%m') AS month_year, 
        COUNT(*) AS total_count 
    FROM 
        mother_coil_audit_log 
    WHERE 
        action_type = 'IN' 
    GROUP BY 
        month_year 
    ORDER BY 
        month_year DESC
";
$monthly_summary_result = $conn->query($monthly_summary_query);
$raw_material_monthly_summary = [];
if ($monthly_summary_result) {
    while ($row = $monthly_summary_result->fetch_assoc()) {
        $raw_material_monthly_summary[] = $row;
    }
}

$selected_month = isset($_POST['month']) ? $_POST['month'] : date('m');
$selected_year = isset($_POST['year']) ? $_POST['year'] : date('Y');

$raw_material_data = [];
$slitting_product_delivered_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 3. Raw Material Inbound (JOINED to get specs from Master Table)
    $stmt_in = $conn->prepare("
        SELECT audit.performed_at as date_in, mc.coil_no, mc.product, mc.lot_no, mc.width, mc.length 
        FROM mother_coil_audit_log audit
        JOIN mother_coil mc ON audit.mother_id = mc.id
        WHERE MONTH(audit.performed_at) = ? AND YEAR(audit.performed_at) = ? AND audit.action_type = 'IN'
    ");
    $stmt_in->bind_param("ss", $selected_month, $selected_year);
    $stmt_in->execute();
    $result_in = $stmt_in->get_result();
    while ($row = $result_in->fetch_assoc()) {
        $raw_material_data[] = $row;
    }
    $stmt_in->close();

    // 4. Slitting Product Delivered Query
    $stmt_out = $conn->prepare("SELECT * FROM slitting_product WHERE MONTH(delivered_at) = ? AND YEAR(delivered_at) = ? AND status='DELIVERED'");
    $stmt_out->bind_param("ss", $selected_month, $selected_year);
    $stmt_out->execute();
    $result_out = $stmt_out->get_result();
    while ($row = $result_out->fetch_assoc()) {
        $slitting_product_delivered_data[] = $row;
    }
    $stmt_out->close();
} 

$page_title = "Monthly Report - Nichias";
include 'header.php';
?>

<style>
    .summary-icon { font-size: 2rem; opacity: 0.8; }
    @media print {
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Operational Report</h2>
    <button onclick="window.print()" class="btn btn-dark shadow-sm">
        <i class="bi bi-printer me-1"></i> Print Report
    </button>
</div>

<div class="card shadow-sm mb-4 no-print border-0">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0 text-secondary">Historical Inbound Summary</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-center">
            <thead class="table-light">
                <tr>
                    <th>Month</th>
                    <th>Total Coils Received</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($raw_material_monthly_summary)): ?>
                    <tr><td colspan="2" class="py-4 text-muted">No audit logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($raw_material_monthly_summary as $summary): ?>
                        <tr>
                            <td class="fw-medium text-primary"><?php echo date("F Y", strtotime($summary['month_year'] . "-01")); ?></td>
                            <td><span class="badge bg-info text-dark px-3 py-2 fs-6"><?php echo $summary['total_count']; ?> Coils</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-5 no-print border-0">
    <div class="card-body bg-light rounded">
        <form action="report.php" method="post" class="row g-3 justify-content-center align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Month</label>
                <select name="month" class="form-select">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($selected_month == $i) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Year</label>
                <select name="year" class="form-select">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($selected_year == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 shadow-sm">Generate Data</button>
            </div>
        </form>
    </div>
</div>

<?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
    <div class="text-center mb-4 d-none d-print-block">
        <h2 class="fw-bold">MONTHLY COIL MANAGEMENT REPORT</h2>
        <h4 class="text-secondary"><?php echo date('F', mktime(0, 0, 0, $selected_month, 10)) . ' ' . $selected_year; ?></h4>
        <hr>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card bg-primary text-white border-0">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase opacity-75 small fw-bold">Raw Material Inbound</h6>
                        <h2 class="display-6 fw-bold mb-0"><?php echo count($raw_material_data); ?></h2>
                    </div>
                    <i class="bi bi-box-arrow-in-right summary-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white border-0">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase opacity-75 small fw-bold">Finished Goods Delivered</h6>
                        <h2 class="display-6 fw-bold mb-0"><?php echo count($slitting_product_delivered_data); ?></h2>
                    </div>
                    <i class="bi bi-truck summary-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h5 class="fw-bold mb-3"><i class="bi bi-journal-text text-primary me-2"></i>Inbound Detail (Master Records)</h5>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Date Recv</th>
                            <th>Coil No</th>
                            <th>Product</th>
                            <th>Lot No</th>
                            <th>Dimensions (W x L)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($raw_material_data)): ?>
                            <tr><td colspan="5" class="py-4 text-muted">No inbound records for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($raw_material_data as $row): ?>
                                <tr>
                                    <td class="small"><?php echo date("d/m/Y H:i", strtotime($row['date_in'])); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['coil_no']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['product']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['lot_no']); ?></td>
                                    <td><?php echo number_format($row['width'],1) . ' x ' . number_format($row['length'],0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h5 class="fw-bold mb-3"><i class="bi bi-check-circle text-success me-2"></i>Outbound Detail (Delivered Products)</h5>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0 text-center">
                    <thead class="table-success text-dark">
                        <tr>
                            <th>Delivered At</th>
                            <th>Coil No</th>
                            <th>Product</th>
                            <th>Lot / Roll</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slitting_product_delivered_data)): ?>
                            <tr><td colspan="5" class="py-4 text-muted">No deliveries found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($slitting_product_delivered_data as $row): ?>
                                <tr>
                                    <td class="small"><?php echo date("d/m/Y", strtotime($row['delivered_at'])); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['coil_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['product']); ?></td>
                                    <td class="text-muted"><?php echo $row['lot_no'] . ' / ' . $row['roll_no']; ?></td>
                                    <td class="fw-bold text-success"><?php echo number_format($row['width'],1) . ' x ' . number_format($row['length'],0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="text-center py-5 opacity-50 no-print">
        <i class="bi bi-calendar-range display-1 text-muted"></i>
        <p class="mt-3 fs-5">Select a month and year to view detailed operational statistics.</p>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>