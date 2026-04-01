<?php
session_start();

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!in_array($_SESSION['role'], ['slitting','mkl3'], true)) {
    die("Access denied for this role.");
}

include 'config.php';

// Handle the action from the modal form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sfc_id']) && isset($_POST['action'])) {
    $sfcId = (int)$_POST['sfc_id'];
    $action = $_POST['action']; // 'RECOIL', 'RESLIT', or 'SELL'

    // Fetch the SFC details
    $stmt = $conn->prepare("SELECT * FROM sfc WHERE sfc_id = ? AND date_out IS NULL");
    $stmt->bind_param("i", $sfcId);
    $stmt->execute();
    $sfc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($sfc) {
        $conn->begin_transaction();
        try {
            if ($action === 'RECOIL') {
                // Insert into recoiling_product
                $stmt = $conn->prepare(
                    "INSERT INTO recoiling_product (product, lot_no, coil_no, width, length, status, date_in) VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
                );
                $stmt->bind_param("ssssd", $sfc['product'], $sfc['lot_no'], $sfc['coil_no'], $sfc['width'], $sfc['length']);
                $stmt->execute();
                $stmt->close();

            } elseif ($action === 'RESLIT') {
                // Insert into reslit_product
                $stmt = $conn->prepare(
                    "INSERT INTO reslit_product (product, lot_no, coil_no, width, length, status, date_in) VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
                );
                $stmt->bind_param("ssssd", $sfc['product'], $sfc['lot_no'], $sfc['coil_no'], $sfc['width'], $sfc['length']);
                $stmt->execute();
                $stmt->close();
            } elseif ($action === 'SELL') {
                // Insert into slitting_product for QC
                $stmt = $conn->prepare(
                    "INSERT INTO slitting_product (product, lot_no, coil_no, width, length, status, date_in, date_out, cut_type, source) VALUES (?, ?, ?, ?, ?, 'WAITING', NOW(), NOW(), 'sfc_sell', ?)"
                );
                $source_value = 'sfc';
                $stmt->bind_param("ssssds", $sfc['product'], $sfc['lot_no'], $sfc['coil_no'], $sfc['width'], $sfc['length'], $source_value);
                $stmt->execute();
                $stmt->close();
            }

            // Mark the SFC as used
            $updateStmt = $conn->prepare("UPDATE sfc SET date_out = NOW(), action = ? WHERE sfc_id = ?");
            $updateStmt->bind_param("si", $action, $sfcId);
            $updateStmt->execute();
            $updateStmt->close();

            $conn->commit();
            
            if ($action === 'SELL') {
                header("Location: finish_product.php?success=1&msg=sfc_sold");
            } else {
                header("Location: sfc.php?success=1");
            }
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            die("An error occurred: " . $e->getMessage());
        }
    } else {
        die("SFC not found or already used.");
    }
}


// Fetch all available SFCs (which have not been used)
$result = $conn->query("SELECT * FROM sfc WHERE date_out IS NULL ORDER BY date_created DESC");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SFC (Shop Floor Control) Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 bg-dark text-white min-vh-100 p-3">
            <div class="d-flex align-items-center justify-content-center mb-3">
                <img src="assets/nichiaslogo.jpg" alt="Logo" style="max-width: 50px; height: auto;" class="me-2">
                <h4 class="m-0 text-white">MK SLITTING</h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2"><a class="nav-link text-white" href="index.php">Dashboard</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="mother_coil.php">Mother Coil</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="sfc.php">SFC Inventory</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="slitting_product.php">Slitting Product</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="recoiling.php">Recoiling Cut</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="reslit.php">Reslit Product</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="finish_product.php">Finish Product</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="report.php">Report</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div class="col-md-10 p-4">
            <h2 class="mb-4">SFC Inventory</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">SFC has been processed successfully.</div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>SFC ID</th>
                            <th>Product</th>
                            <th>Lot No.</th>
                            <th>Coil No.</th>
                            <th>Width</th>
                            <th>Length (mtr)</th>
                            <th>Date Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['sfc_id'], ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars($row['product'], ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars($row['lot_no'], ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars($row['coil_no'], ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars($row['width'], ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars(number_format($row['length'], 2), ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars($row['date_created'], ENT_QUOTES) ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm actionBtn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#actionModal"
                                            data-sfc-id="<?= $row['sfc_id'] ?>"
                                            data-sfc-details="SFC ID <?= $row['sfc_id'] ?>: <?= $row['product'] ?> (<?= $row['length'] ?>m)">
                                        Use SFC
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No SFC inventory available.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="actionModalLabel">Choose Action for SFC</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Please choose the next process for this SFC:</p>
        <p id="sfcDetails" class="fw-bold"></p>
        <form id="actionForm" method="post" action="sfc.php">
            <input type="hidden" name="sfc_id" id="sfc_id_input">
            <div class="d-grid gap-2">
                <button type="submit" name="action" value="RECOIL" class="btn btn-warning">RECOIL</button>
                <button type="submit" name="action" value="RESLIT" class="btn btn-info">RESLIT</button>
                <button type="submit" name="action" value="SELL" class="btn btn-success">SELL</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var actionModal = document.getElementById('actionModal');
    actionModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var sfcId = button.getAttribute('data-sfc-id');
        var sfcDetails = button.getAttribute('data-sfc-details');

        var modalTitle = actionModal.querySelector('.modal-title');
        var modalBodySfcIdInput = actionModal.querySelector('#sfc_id_input');
        var modalSfcDetails = actionModal.querySelector('#sfcDetails');

        modalTitle.textContent = 'Choose Action for SFC ' + sfcId;
        modalBodySfcIdInput.value = sfcId;
        modalSfcDetails.textContent = sfcDetails;
    });
});
</script>

</body>
</html>
