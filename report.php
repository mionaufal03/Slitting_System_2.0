<?php
require_once 'config.php';

// This query provides a historical, cumulative count of all scan-in events for each month.
// It correctly counts all 'IN' statuses from the log, ensuring the total doesn't decrease
// if materials are later marked as 'OUT', which aligns with the critical business logic for this report.
// Monthly summary query for raw material
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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Report - Nichias Slitting System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .report-header {
            background: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .card {
            border: none;
            border-radius: 12px;
        }
        .table thead {
            background-color: #f1f3f5;
        }
        .summary-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        /* Style for Printing */
        @media print {
            .no-print { display: none !important; }
            .container { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body>

    <div class="container pt-4 no-print">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-house-door"></i> Back to Home
        </a>
    </div>

    <div class="report-header shadow-sm no-print">
        <div class="container text-center">
            <h1 class="fw-bold text-primary"><i class="bi bi-file-earmark-bar-graph"></i> Monthly Report</h1>
            <p class="text-muted">Nichias Monthly Coil Management Report</p>
        </div>
    </div>

    <div class="container pb-5">
        
        <div class="card shadow-sm mb-4 no-print">
            <div class="card-header">
                <h4 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-month text-primary"></i> Monthly Raw Material Scanned (Checked-in)</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Month</th>
                            <th class="text-center">Total Raw Materials Scanned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($raw_material_monthly_summary)): ?>
                            <tr><td colspan="2" class="text-center py-4 text-muted">No data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($raw_material_monthly_summary as $summary): ?>
                                <tr>
                                    <td class="ps-3 fw-medium"><?php echo date("F Y", strtotime($summary['month_year'] . "-01")); ?></td>
                                    <td class="text-center"><?php echo $summary['total_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-4 no-print">
            <div class="card-body">
                <form action="report.php" method="post" class="row g-3 justify-content-center align-items-end">
                    <div class="col-md-3">
                        <label for="month" class="form-label fw-semibold text-secondary small text-uppercase">Month</label>
                        <select name="month" id="month" class="form-select">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($selected_month == $i) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year" class="form-label fw-semibold text-secondary small text-uppercase">Year</label>
                        <select name="year" id="year" class="form-select">
                            <?php for ($i = date('Y'); $i >= date('Y') - 10; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($selected_year == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                            <i class="bi bi-search"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            
            <div class="text-center mb-4 d-none d-print-block">
                <h2>Monthly Report: <?php echo date('F', mktime(0, 0, 0, $selected_month, 10)) . ' ' . $selected_year; ?></h2>
                <hr>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card bg-primary text-white shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between p-4">
                            <div>
                                <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Total Raw Material In</h6>
                                <h2 class="display-5 fw-bold mb-0"><?php echo count($raw_material_data); ?></h2>
                            </div>
                            <i class="bi bi-box-arrow-in-right summary-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-success text-white shadow-sm h-100">
                        <div class="card-body d-flex align-items-center justify-content-between p-4">
                            <div>
                                <h6 class="text-uppercase mb-1 opacity-75 small fw-bold">Total Slitting Product Delivered</h6>
                                <h2 class="display-5 fw-bold mb-0"><?php echo count($slitting_product_delivered_data); ?></h2>
                            </div>
                            <i class="bi bi-truck summary-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-dark mb-0"><i class="bi bi-journal-text text-primary"></i> Raw Material In Details</h4>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                    </div>
                    <div class="card shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Date In</th>
                                        <th>Coil No</th>
                                        <th>Product</th>
                                        <th>Lot No</th>
                                        <th>Width (mm)</th>
                                        <th>Length (m)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($raw_material_data)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No raw material entry data found for this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($raw_material_data as $row): ?>
                                            <tr>
                                                <td class="ps-3 fw-medium small"><?php echo htmlspecialchars($row['date_in']); ?></td>
                                                <td><span class="badge bg-light text-dark border fw-bold"><?php echo htmlspecialchars($row['coil_no']); ?></span></td>
                                                <td><?php echo htmlspecialchars($row['product']); ?></td>
                                                <td class="text-secondary small"><?php echo htmlspecialchars($row['lot_no']); ?></td>
                                                <td><?php echo htmlspecialchars($row['width']); ?></td>
                                                <td><?php echo htmlspecialchars($row['length']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <h4 class="fw-bold text-dark mb-3"><i class="bi bi-check-circle text-success"></i> Slitting Product Delivered Details</h4>
                    <div class="card shadow-sm border-top border-success border-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Delivered At</th>
                                        <th>Coil No</th>
                                        <th>Product</th>
                                        <th>Lot No</th>
                                        <th>Width (mm)</th>
                                        <th>Length (m)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($slitting_product_delivered_data)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No slitting product delivered data found for this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($slitting_product_delivered_data as $row): ?>
                                            <tr>
                                                <td class="ps-3 fw-medium small"><?php echo htmlspecialchars($row['delivered_at']); ?></td>
                                                <td><span class="badge bg-light text-dark border fw-bold"><?php echo htmlspecialchars($row['coil_no']); ?></span></td>
                                                <td><?php echo htmlspecialchars($row['product']); ?></td>
                                                <td class="text-secondary small"><?php echo htmlspecialchars($row['lot_no']); ?></td>
                                                <td><?php echo htmlspecialchars($row['width']); ?></td>
                                                <td><?php echo htmlspecialchars($row['length']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="text-center py-5 opacity-50">
                <i class="bi bi-calendar-check display-1"></i>
                <p class="mt-3">Please select a month and year to generate the report.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>