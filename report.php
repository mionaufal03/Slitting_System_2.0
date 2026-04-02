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

// 2. Data Logic: Historical cumulative count
$monthly_summary_query = "
    SELECT
        DATE_FORMAT(date_in, '%Y-%m') AS month_year,
        COUNT(*) AS total_count
    FROM
        raw_material_log
    WHERE
        status = 'IN' AND date_in IS NOT NULL
    GROUP BY
        month_year
    ORDER BY
        month_year DESC;
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
    // Raw Material In Query
    $stmt_in = $conn->prepare("SELECT * FROM raw_material_log WHERE MONTH(date_in) = ? AND YEAR(date_in) = ? AND status = 'IN'");
    $stmt_in->bind_param("ss", $selected_month, $selected_year);
    $stmt_in->execute();
    $result_in = $stmt_in->get_result();
    while ($row = $result_in->fetch_assoc()) {
        $raw_material_data[] = $row;
    }
    $stmt_in->close();

    // Slitting Product Delivered Query
    $stmt_out = $conn->prepare("SELECT * FROM slitting_product WHERE MONTH(delivered_at) = ? AND YEAR(delivered_at) = ?");
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
    /* Style for Printing */
    @media print {
        .no-print { display: none !important; }
        .col-md-10 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Monthly Performance Report</h2>
    <button onclick="window.print()" class="btn btn-dark shadow-sm">
        <i class="bi bi-printer me-1"></i> Print Report
    </button>
</div>

<div class="card shadow-sm mb-4 no-print border-0">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="fw-bold mb-0 text-secondary">Historical Checked-in Summary</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-center">
            <thead class="table-light">
                <tr>
                    <th>Month</th>
                    <th>Total Raw Materials Scanned</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($raw_material_monthly_summary)): ?>
                    <tr><td colspan="2" class="py-4 text-muted">No data available.</td></tr>
                <?php else: ?>
                    <?php foreach ($raw_material_monthly_summary as $summary): ?>
                        <tr>
                            <td class="fw-medium text-primary"><?php echo date("F Y", strtotime($summary['month_year'] . "-01")); ?></td>
                            <td><span class="badge bg-info text-dark px-3 py-2 fs-6"><?php echo $summary['total_count']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-5 no-print border-0">
    <div class="card-body bg-light rounded shadow-inner">
        <form action="report.php" method="post" class="row g-3 justify-content-center align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase text-muted">Filter Month</label>
                <select name="month" class="form-select">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($selected_month == $i) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase text-muted">Filter Year</label>
                <select name="year" class="form-select">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($selected_year == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                    <i class="bi bi-search me-2"></i>Generate Detailed Report
                </button>
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
            <div class="card bg-primary text-white shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Raw Material Inbound</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo count($raw_material_data); ?></h2>
                    </div>
                    <i class="bi bi-box-arrow-in-right summary-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Finished Goods Delivered</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo count($slitting_product_delivered_data); ?></h2>
                    </div>
                    <i class="bi bi-truck summary-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h4 class="fw-bold mb-3"><i class="bi bi-journal-text text-primary me-2"></i>Raw Material In Details</h4>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0 text-center" style="font-size: 0.9rem;">
                    <thead class="table-dark">
                        <tr>
                            <th>Date In</th>
                            <th>Coil No</th>
                            <th>Product</th>
                            <th>Lot No</th>
                            <th>W x L</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($raw_material_data)): ?>
                            <tr><td colspan="5" class="py-4 text-muted">No raw material data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($raw_material_data as $row): ?>
                                <tr>
                                    <td class="small"><?php echo htmlspecialchars($row['date_in'] ?? ''); ?></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['coil_no'] ?? ''); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['product'] ?? ''); ?></span></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['lot_no'] ?? ''); ?></td>
                                    <td><?php echo ($row['width'] ?? '0') . ' x ' . ($row['length'] ?? '0'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h4 class="fw-bold mb-3"><i class="bi bi-check-circle text-success me-2"></i>Delivered Product Details</h4>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0 text-center" style="font-size: 0.9rem;">
                    <thead class="table-success text-dark">
                        <tr>
                            <th>Delivered At</th>
                            <th>Coil No</th>
                            <th>Product</th>
                            <th>Lot / Roll</th>
                            <th>Dimensions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slitting_product_delivered_data)): ?>
                            <tr><td colspan="5" class="py-4 text-muted">No delivered products found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($slitting_product_delivered_data as $row): ?>
                                <tr>
                                    <td class="small"><?php echo htmlspecialchars($row['delivered_at'] ?? ''); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['coil_no'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['product'] ?? ''); ?></td>
                                    <td class="text-muted"><?php echo ($row['lot_no'] ?? '') . ' / ' . ($row['roll_no'] ?? ''); ?></td>
                                    <td class="fw-bold text-success"><?php echo ($row['width'] ?? '0') . ' x ' . ($row['length'] ?? '0'); ?></td>
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
        <i class="bi bi-calendar-check display-1 text-muted"></i>
        <p class="mt-3 fs-5">Select a month and year above to generate detailed data.</p>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>