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

include 'config.php';

// 2. Filter Logic (Month & Year)
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

if ($month < 1 || $month > 12) { $month = (int)date('m'); }
if ($year < 2020 || $year > 2030) { $year = (int)date('Y'); }

// 3. Summary Queries
$in = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log 
                     WHERE status='IN' AND MONTH(date_in)=$month AND YEAR(date_in)=$year")
                     ->fetch_assoc()['total'];

$out = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log 
                     WHERE status='OUT' AND MONTH(date_out)=$month AND YEAR(date_out)=$year")
                     ->fetch_assoc()['total'];

$stock = max(0, (int)$in - (int)$out);

$afterCutStock = $conn->query("SELECT COUNT(*) AS total FROM raw_material_log 
                               WHERE status='IN' AND action='cut_into_2'")
                               ->fetch_assoc()['total'];

// 4. Main Data Query
$result = $conn->query("SELECT * FROM raw_material_log 
                        WHERE (MONTH(date_in)=$month AND YEAR(date_in)=$year) 
                           OR (MONTH(date_out)=$month AND YEAR(date_out)=$year) 
                        ORDER BY id DESC");

$page_title = "Raw Material Inventory";
include 'header.php'; 
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam me-2"></i>Raw Material Inventory</h2>
    <div class="d-flex gap-2">
        <a href="raw_material_export.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-success shadow-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Download
        </a>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#manualEntryModal">
            <i class="bi bi-pencil-square me-1"></i> Manual Entry
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="small fw-bold text-muted">Month:</label>
                <select name="month" onchange="this.form.submit()" class="form-select form-select-sm w-auto d-inline-block ms-1">
                    <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?= $m ?>" <?= ($m==$month)?'selected':'' ?>>
                            <?= date("F", mktime(0,0,0,$m,1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="small fw-bold text-muted">Year:</label>
                <select name="year" onchange="this.form.submit()" class="form-select form-select-sm w-auto d-inline-block ms-1">
                    <?php for($y=2024; $y<=2030; $y++): ?>
                        <option value="<?= $y ?>" <?= ($y==$year)?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<form id="scanForm" method="post" action="scan_mother_action.php" autocomplete="off" style="position:absolute; left:-9999px;">
    <input id="qrInput" type="text" name="qr" autofocus>
</form>

<div class="row g-3 mb-4 text-center">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body">
                <h6 class="text-muted small mb-1">MONTHLY IN</h6>
                <h3 class="text-success fw-bold mb-0"><?= (int)$in ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body">
                <h6 class="text-muted small mb-1">MONTHLY OUT</h6>
                <h3 class="text-danger fw-bold mb-0"><?= (int)$out ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <h6 class="small mb-1">CURRENT STOCK</h6>
                <h3 class="fw-bold mb-0"><?= (int)$stock ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body">
                <h6 class="small mb-1">AFTER CUT STOCK</h6>
                <h3 class="fw-bold mb-0"><?= (int)$afterCutStock ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white fw-bold py-3">
        <i class="bi bi-clock-history me-2"></i>Raw Material Log
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle text-center mb-0">
            <thead class="table-light">
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
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['product'] ?? '-') ?></span></td>
                        <td><?= htmlspecialchars($row['lot_no'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['coil_no'] ?? '-') ?></td>
                        <td class="fw-bold"><?= isset($row['length']) ? number_format($row['length']) : '-' ?></td>
                        <td><?= isset($row['width']) ? number_format($row['width']) : '-' ?></td>
                        <td class="small"><?= $row['date_in'] ?? '-' ?></td>
                        <td class="small"><?= $row['date_out'] ?? '-' ?></td>
                        <td><span class="badge bg-info text-dark"><?= $row['action'] ?? '-' ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="py-4 text-muted">No records found for the selected period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-success text-white fw-bold py-3">
        <i class="bi bi-scissors me-2"></i>Available Stock After Cut
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle text-center mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Lot No.</th>
                    <th>Coil No.</th>
                    <th>Length</th>
                    <th>Width</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $resultCut = $conn->query("SELECT * FROM raw_material_log WHERE status='IN' AND action='cut_into_2' ORDER BY id ASC");
                if($resultCut && $resultCut->num_rows > 0):
                    while($rowCut = $resultCut->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($rowCut['product']) ?></strong></td>
                    <td><?= htmlspecialchars($rowCut['lot_no']) ?></td>
                    <td><?= htmlspecialchars($rowCut['coil_no']) ?></td>
                    <td class="text-success fw-bold"><?= number_format($rowCut['length']) ?> m</td>
                    <td><?= number_format($rowCut['width']) ?> mm</td>
                    <td>
                        <a href="add_slitting.php?stock_id=<?= $rowCut['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                            USE <i class="bi bi-chevron-right small ms-1"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-4 text-muted">No leftover stock from "Cut Into 2" process.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="manualEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Single-Box Manual Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manualEntryForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Enter Lot No & Coil No</label>
                        <input type="text" class="form-control form-control-lg" id="combined_input" 
                               placeholder="e.g., 826175 FK-1" required autofocus>
                        <div id="validationFeedback" class="invalid-feedback">
                            Please enter both Lot No and Coil No separated by a space (e.g., 826175 FK-1).
                        </div>
                        <div class="form-text mt-2">
                            Type the <strong>Lot Number</strong>, then a <strong>space</strong>, then the <strong>Coil Number</strong>.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="manualSubmitButton">Process Entry</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Scanner Logic (Existing)
    const qrInput = document.getElementById('qrInput');
    const scanForm = document.getElementById('scanForm');

    if(qrInput){
        qrInput.addEventListener('keydown', function(e){
            if(e.key === 'Enter'){
                e.preventDefault();
                if(this.value.trim() !== '') scanForm.submit();
            }
        });
    }

    // Refactored Manual Entry Parsing Logic
    const manualBtn = document.getElementById('manualSubmitButton');
    const combinedInput = document.getElementById('combined_input');
    const feedback = document.getElementById('validationFeedback');

    if(manualBtn){
        manualBtn.addEventListener('click', function() {
            const rawValue = combinedInput.value.trim();
            
            // 1. Use Regex /\s+/ to handle multiple spaces accidentally typed
            const parts = rawValue.split(/\s+/);

            // 2. Validation: Must have at least two distinct parts
            if (parts.length >= 2) {
                const lotNo = parts[0];
                const coilNo = parts.slice(1).join(' '); // Re-join if Coil No contains spaces (like 'FK 1')

                // Remove validation errors if any
                combinedInput.classList.remove('is-invalid');
                
                // 3. Format into the system-recognized QR syntax
                qrInput.value = `LOT=${lotNo};COIL=${coilNo}`;
                
                // 4. Submit via the existing scan handler
                scanForm.submit();
            } else {
                // Show validation error
                combinedInput.classList.add('is-invalid');
                feedback.style.display = 'block';
            }
        });

        // Clear error when user starts typing again
        combinedInput.addEventListener('input', () => {
            combinedInput.classList.remove('is-invalid');
            feedback.style.display = 'none';
        });
    }

    // Smart Focus (Existing)
    setInterval(() => {
        const el = document.activeElement;
        const modal = document.getElementById('manualEntryModal');
        const isModalOpen = modal ? modal.classList.contains('show') : false;
        
        if (!isModalOpen && el.tagName !== 'INPUT' && el.tagName !== 'SELECT' && el.tagName !== 'BUTTON') {
            if(qrInput) qrInput.focus();
        }
    }, 1000);
</script>

<?php include 'footer.php'; ?>