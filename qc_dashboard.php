<?php
require_once 'config.php';

// Fetch products pending QC approval
$query = "SELECT * FROM slitting_product WHERE status = 'PENDING' ORDER BY date_in DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC Dashboard - Nichias Slitting System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f4f7f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: #2c3e50;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.5em 1em;
        }
        .table thead th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark mb-4 no-print">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shield-check"></i> NICHIAS QC MANAGEMENT
            </a>
            <div class="d-flex">
                <span class="navbar-text text-light me-3">
                    Logged in as: Quality Controller
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark">Quality Control Dashboard</h2>
                <p class="text-secondary">Review and approve slitting products for dispatch.</p>
            </div>
            <button onclick="window.location.reload()" class="btn btn-white border shadow-sm">
                <i class="bi bi-arrow-clockwise"></i> Refresh Data
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3 border-start border-warning border-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded">
                            <i class="bi bi-hourglass-split text-warning fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-uppercase text-secondary small mb-1">Awaiting Inspection</h6>
                            <h3 class="fw-bold mb-0"><?php echo $result->num_rows; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold">Pending Approvals</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Production Date</th>
                            <th>Coil Number</th>
                            <th>Product Type</th>
                            <th>Dimensions (W x L)</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold small"><?php echo date('d M Y', strtotime($row['date_in'])); ?></div>
                                        <div class="text-muted extra-small" style="font-size: 0.75rem;">
                                            <?php echo date('H:i A', strtotime($row['date_in'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-bold"><?php echo htmlspecialchars($row['coil_no']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['product']); ?></td>
                                    <td>
                                        <span class="text-dark fw-medium"><?php echo $row['width']; ?></span> <span class="text-muted small">mm</span>
                                        <i class="bi bi-x small text-muted"></i>
                                        <span class="text-dark fw-medium"><?php echo $row['length']; ?></span> <span class="text-muted small">m</span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-warning text-dark status-badge">
                                            <i class="bi bi-search"></i> PENDING
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="qc_approve.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm px-3 shadow-sm">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </a>
                                        <a href="edit_slitting.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-secondary btn-sm ms-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-check-circle display-4 d-block mb-3 opacity-25"></i>
                                        All products have been inspected. No pending QC items.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>