<?php
session_start();

// must login
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// ONLY admin can access raw material
if ($_SESSION['role'] !== 'slitting') {
    die("Access denied");
}

include 'config.php';

// Bulan & Tahun dipilih
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

if ($month < 1 || $month > 12) { $month = (int)date('m'); }
if ($year < 2000 || $year > 2100) { $year = (int)date('Y'); }

// Summary untuk bulan dipilih
$in  = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log 
                     WHERE status='IN'  AND MONTH(date_in)=$month AND YEAR(date_in)=$year")
                     ->fetch_assoc()['total'];

$out = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log 
                     WHERE status='OUT' AND MONTH(date_out)=$month AND YEAR(date_out)=$year")
                     ->fetch_assoc()['total'];

// Current Stock (MONTH SELECTED) = IN (month) - OUT (month)
$totalInMonth = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log WHERE status='IN' AND MONTH(date_in)=$month AND YEAR(date_in)=$year")->fetch_assoc()['total'];

$totalOutMonth = $conn->query(" SELECT COUNT(*) AS total FROM raw_material_log WHERE status='OUT' AND MONTH(date_out)=$month AND YEAR(date_out)=$year")->fetch_assoc()['total'];

$stock = max(0, (int)$totalInMonth - (int)$totalOutMonth);

// After Cut Stock = Stock leftover from cut_into_2 (status IN with action cut_into_2)
$afterCutStock = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log 
                               WHERE status='IN' AND action='cut_into_2'")
                               ->fetch_assoc()['total'];

// Data detail
$result = $conn->query("SELECT * FROM raw_material_log 
                        WHERE (MONTH(date_in)=$month AND YEAR(date_in)=$year) 
                           OR (MONTH(date_out)=$month AND YEAR(date_out)=$year) 
                        ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Raw Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-3">Raw Material</h2>

    <?php if (isset($_GET['scan'])): ?>
        <div class="alert alert-info py-2 my-2">
            Scan status: <?= htmlspecialchars($_GET['scan']) ?>
        </div>
    <?php endif; ?>

    <!-- ✅ Hidden form for wireless scanner (invisible to user) -->
    <form id="scanForm" method="post" action="scan_mother_action.php" autocomplete="off" style="position:absolute; left:-9999px;">
        <input id="qrInput" type="text" name="qr" autofocus>
    </form>

    <!-- Dropdown pilih bulan & tahun -->
    <form method="get" class="mb-3 d-flex gap-2 align-items-center">
        <label>Select Month:</label>
        <select name="month" onchange="this.form.submit()" class="form-select w-auto d-inline-block">
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= ($m==$month)?'selected':'' ?>>
                    <?= date("F", mktime(0,0,0,$m,1)) ?>
                </option>
            <?php endfor; ?>
        </select>

        <label>Select Year:</label>
        <select name="year" onchange="this.form.submit()" class="form-select w-auto d-inline-block">
            <?php for($y=2026; $y<=2030; $y++): ?>
                <option value="<?= $y ?>" <?= ($y==$year)?'selected':'' ?>>
                    <?= $y ?>
                </option>
            <?php endfor; ?>
        </select>
    </form>

    <!-- Button Download -->
    <div class="mb-3 d-flex gap-2">
        <a href="raw_material_export.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-success">
            Download 
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            Manual Entry
        </button>
    </div>

    <!-- Summary cards -->
    <div class="row text-center mb-4">
        <div class="col-md-3">
            <div class="card text-bg-white">
                <div class="card-body">
                    <h5 class="mb-0">IN</h5>
                    <h2 class="mb-0"><?= (int)$in ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3 mt-md-0">
            <div class="card text-bg-white">
                <div class="card-body">
                    <h5 class="mb-0">OUT</h5>
                    <h2 class="mb-0"><?= (int)$out ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3 mt-md-0">
            <div class="card text-bg-primary">
                <div class="card-body">
                    <h5 class="mb-0">STOCK</h5>
                    <h2 class="mb-0"><?= (int)$stock ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3 mt-md-0">
            <div class="card text-bg-success">
                <div class="card-body">
                    <h5 class="mb-0">AFTER CUT STOCK</h5>
                    <h2 class="mb-0"><?= (int)$afterCutStock ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Raw Material -->
    <h4 class="mt-4 mb-3">Raw Material Log</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Product</th>
                    <th>Lot No.</th>
                    <th>Coil No.</th>
                    <th>Length (mtr)</th>
                    <th>Width</th>
                    <th>Date In</th>
                    <th>Date Out</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['lot_no'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['coil_no'] ?? '-') ?></td>
                        <td><?= isset($row['length']) && $row['length'] !== null ? number_format($row['length']) : '-' ?></td>
                        <td><?= isset($row['width']) && $row['width'] !== null ? number_format($row['width']) : '-' ?></td>
                        <td><?= $row['date_in'] ?? '-' ?></td>
                        <td><?= $row['date_out'] ?? '-' ?></td>
                        <td><?= $row['action'] ?? '-' ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-muted">No record for selected month/year</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Stock After Cut -->
    <h4 class="mt-5 mb-3">Stock After Cut</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Product</th>
                    <th>Lot No.</th>
                    <th>Coil No.</th>
                    <th>Length (mtr)</th>
                    <th>Width</th>
                    <th>Date In</th>
                    <th>Remark</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $resultCut = $conn->query("SELECT * FROM raw_material_log 
                                           WHERE status='IN' 
                                           AND action='cut_into_2'
                                           AND (MONTH(date_in)=$month AND YEAR(date_in)=$year)
                                           ORDER BY id ASC");
                
                if($resultCut && $resultCut->num_rows > 0):
                    while($rowCut = $resultCut->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= htmlspecialchars($rowCut['product'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($rowCut['lot_no'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($rowCut['coil_no'] ?? '-') ?></td>
                    <td><?= isset($rowCut['length']) && $rowCut['length'] !== null ? number_format($rowCut['length']) : '-' ?></td>
                    <td><?= isset($rowCut['width']) && $rowCut['width'] !== null ? number_format($rowCut['width']) : '-' ?></td>
                    <td><?= $rowCut['date_in'] ?? '-' ?></td>
                    <td>cut_into_2</td>
                    <td>
                        <a href="add_slitting.php?stock_id=<?= $rowCut['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-right"></i> USE
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="8" class="text-muted">No stock after cut for this month</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="index.php" class="btn btn-secondary mt-3">← Back</a>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manualEntryModalLabel">Manual Mother Coil Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="manualEntryForm">
                    <div class="mb-3">
                        <label for="manual_lot_no" class="form-label">Lot No.</label>
                        <input type="text" class="form-control" id="manual_lot_no" required>
                    </div>
                    <div class="mb-3">
                        <label for="manual_coil_no" class="form-label">Coil No.</label>
                        <input type="text" class="form-control" id="manual_coil_no" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="manualSubmitButton">Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ✅ Hidden scanner handler (scanner = keyboard)
  const qrInput = document.getElementById('qrInput');
  const scanForm = document.getElementById('scanForm');

  // Auto submit bila scanner hantar ENTER
  qrInput.addEventListener('keydown', function(e){
    if(e.key === 'Enter'){
      e.preventDefault();
      const v = qrInput.value.trim();
      if(v !== '') scanForm.submit();
    }
  });

  // ✅ Fokus balik ke qrInput, tapi JANGAN kacau bila user tengah pilih dropdown / input lain
  setInterval(() => {
    const el = document.activeElement;

    // kalau user tengah guna select / input / textarea / button, jangan rampas fokus
    if (el && (el.tagName === 'SELECT' || el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'BUTTON')) {
      // tapi kalau yang active tu qrInput sendiri, ok
      if (el === qrInput) return;
      return;
    }
    
    // Jangan focus kalau modal manual entry tengah buka
    const modalEl = document.getElementById('manualEntryModal');
    if (modalEl && modalEl.classList.contains('show')) {
        return;
    }

    if (document.activeElement !== qrInput) qrInput.focus();
  }, 800);

  // Handle Manual Entry submission
  document.getElementById('manualSubmitButton').addEventListener('click', function() {
      const lotNo = document.getElementById('manual_lot_no').value.trim();
      const coilNo = document.getElementById('manual_coil_no').value.trim();

      if (lotNo && coilNo) {
          const qrString = `LOT=${lotNo};COIL=${coilNo}`;
          document.getElementById('qrInput').value = qrString;
          document.getElementById('scanForm').submit();
      } else {
          alert('Please fill in both Lot No. and Coil No.');
      }
  });

  const manualEntryModal = document.getElementById('manualEntryModal');
  manualEntryModal.addEventListener('shown.bs.modal', () => {
      document.getElementById('manual_lot_no').focus();
      document.getElementById('manualEntryForm').reset();
  });

</script>

</body>
</html>