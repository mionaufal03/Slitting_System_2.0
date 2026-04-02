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

// 2. Fetch Data with Effective Length Logic
$result = $conn->query("
    SELECT 
        r.*,
        COALESCE(NULLIF(s.actual_length, 0), r.length) AS effective_length
    FROM reslit_product r
    LEFT JOIN (
        SELECT sp1.*
        FROM slitting_product sp1
        INNER JOIN (
            SELECT lot_no, coil_no, roll_no, MAX(id) AS max_id
            FROM slitting_product
            GROUP BY lot_no, coil_no, roll_no
        ) sp2
          ON sp1.id = sp2.max_id
    ) s
      ON s.lot_no = r.lot_no
     AND s.coil_no = r.coil_no
     AND s.roll_no = r.roll_no
    ORDER BY r.id ASC
");

// 3. Set Page Title and Include Header
$page_title = "Reslit Product Management";
include 'header.php';
?>

<style>
    /* Keep your specialized UI styles for the Parent-Child relation */
    .status-cards { display: flex; gap: 15px; margin-bottom: 30px; }
    .status-card { flex: 1; border-radius: 8px; padding: 20px; text-align: center; color: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .status-card.pending { background: linear-gradient(135deg, #ffc107, #ff9800); }
    .status-card.completed { background: linear-gradient(135deg, #28a745, #20c997); }
    
    .info-box { background: #f0f9ff; border-left: 4px solid #0d6efd; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
    .slitting-box { border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 15px; background: #f9f9f9; }
    .highlight-field { background: #fff3cd !important; border: 2px solid #ffc107 !important; }
    .child-row-bg { background-color: #f0f9ff; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-intersect me-2"></i>Reslit Product Management</h2>
    <div class="d-flex gap-2">
        <a href="?download=excel" class="btn btn-success shadow-sm">
            <i class="bi bi-download me-1"></i> Download Excel
        </a>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php 
            if($_GET['success'] === 'added') echo "Product successfully added to reslit list!";
            elseif($_GET['success'] === 'started') echo "Reslit process started!";
            elseif($_GET['success'] === 'completed') echo "Reslit completed! Product added to stock.";
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
$pending = $conn->query("SELECT COUNT(*) as count FROM reslit_product WHERE status='pending'")->fetch_assoc()['count'];
$completed = $conn->query("SELECT COUNT(*) as count FROM reslit_product WHERE status='completed'")->fetch_assoc()['count'];
?>

<div class="status-cards">
    <div class="status-card pending">
        <h5>Pending Reslit</h5>
        <h2><?= $pending ?></h2>
    </div>
    <div class="status-card completed">
        <h5>Completed</h5>
        <h2><?= $completed ?></h2>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle text-center mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Product</th>
                    <th>Lot & Coil No.</th>
                    <th>Roll No.</th>
                    <th>Width</th>
                    <th>Length</th>
                    <th>Actual Length</th>
                    <th>Date In</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $row['id'] ?></strong></td>
                            <td>
                                <?php if($row['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">PENDING</span>
                                <?php else: ?>
                                    <span class="badge bg-success">COMPLETED</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['product'] ?? '-') ?></span></td>
                            <td><?= htmlspecialchars($row['lot_no'] ?? '-') . ' ' . htmlspecialchars($row['coil_no'] ?? '') ?></td>
                            <td><strong><?= htmlspecialchars($row['roll_no'] ?? '-') ?></strong></td>
                            <td><?= isset($row['width']) ? number_format($row['width']) : '-' ?></td>
                            <td><?= isset($row['effective_length']) ? number_format($row['effective_length']) : '-' ?></td>
                            <td class="text-success fw-bold"><?= isset($row['actual_length']) ? number_format($row['actual_length']) : '-' ?></td>
                            <td class="small"><?= htmlspecialchars($row['date_in'] ?? '-') ?></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <button class="btn btn-primary btn-sm mb-1" 
                                            onclick="showReslitModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars($row['product'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($row['lot_no'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($row['coil_no'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($row['roll_no'] ?? '', ENT_QUOTES) ?>', <?= (float)($row['width'] ?? 0) ?>, <?= (float)($row['effective_length'] ?? ($row['length'] ?? 0)) ?>)">
                                        <i class="bi bi-play-circle"></i> Reslit
                                    </button>
                                    <a href="edit_reslit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm mb-1"><i class="bi bi-pencil"></i></a>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <?php
                        $rolls = $conn->query("SELECT * FROM reslit_rolls WHERE parent_id = {$row['id']} ORDER BY id ASC");
                        if ($rolls && $rolls->num_rows > 0):
                            while ($roll = $rolls->fetch_assoc()):
                        ?>
                        <tr class="child-row-bg">
                            <td class="small text-muted">↳ R<?= $roll['id'] ?></td>
                            <td><span class="badge bg-success">COMPLETED</span></td>
                            <td><?= htmlspecialchars($row['product'] ?? '-') ?></td>
                            <td class="small">
                                <?= htmlspecialchars($row['lot_no'] ?? '-') ?><?= $roll['cut_letter'] ? htmlspecialchars($roll['cut_letter']) : '' ?> <?= htmlspecialchars($row['coil_no'] ?? '') ?>
                            </td>
                            <td><strong><?= htmlspecialchars($roll['roll_no'] ?? '-') ?></strong></td>
                            <td><?= isset($roll['new_width']) ? number_format($roll['new_width']) : '-' ?></td>
                            <td><?= isset($roll['length']) ? number_format($roll['length']) : '-' ?></td>
                            <td class="text-success fw-bold"><?= isset($roll['actual_length']) ? number_format($roll['actual_length']) : '-' ?></td>
                            <td>-</td>
                            <td><span class="badge bg-light text-dark border small">Done</span></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="py-5 text-muted">No reslit records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="reslitModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-scissors me-2"></i>Start Reslit Process</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="reslit_handler.php" id="reslitForm">
                <input type="hidden" name="action" value="complete_reslit_direct">
                <input type="hidden" name="id" id="reslit_id">
                <input type="hidden" name="cut_type" id="cut_type_value">
                
                <div class="modal-body p-4">
                    <div class="info-box shadow-sm mb-4">
                        <div class="row g-2">
                            <div class="col-6"><strong>Product:</strong> <span id="modal_product">-</span></div>
                            <div class="col-6"><strong>Lot No:</strong> <span id="modal_lot">-</span></div>
                            <div class="col-6"><strong>Roll No:</strong> <span id="modal_roll">-</span></div>
                            <div class="col-6"><strong>Width:</strong> <span id="modal_width">-</span> mm</div>
                            <div class="col-12"><strong>Length:</strong> <span id="modal_length">-</span> mtr</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Step 1: Select Cut Type</h6>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cut_type" id="cutNormal" value="normal" onchange="handleCutTypeChange()">
                                <label class="form-check-label fw-bold" for="cutNormal">Normal</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cut_type" id="cutInto2" value="cut_into_2" onchange="handleCutTypeChange()">
                                <label class="form-check-label fw-bold" for="cutInto2">Cut Into 2</label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="rollCountSection" style="display:none;" class="mb-4">
                        <hr>
                        <h6 class="fw-bold mb-3">Step 2: Number of Rolls</h6>
                        <select name="total" id="total" class="form-select w-auto" onchange="generateForm()">
                            <option value="">-- Select --</option>
                            <?php for($i=1;$i<=10;$i++): ?><option value="<?= $i ?>"><?= $i ?> Roll<?= $i>1?'s':'' ?></option><?php endfor; ?>
                        </select>
                    </div>
                    
                    <div id="slittingForm"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" id="submitBtn" style="display:none;">
                        <i class="bi bi-check-circle me-1"></i> Complete Reslit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let productData = {};

function showReslitModal(id, product, lot_no, coil_no, roll_no, width, length) {
    productData = { id, product, lot_no, coil_no, roll_no, width, length };
    document.getElementById('reslit_id').value = id;
    document.getElementById('modal_product').textContent = product;
    document.getElementById('modal_lot').textContent = lot_no + ' ' + coil_no;
    document.getElementById('modal_roll').textContent = roll_no;
    document.getElementById('modal_width').textContent = width;
    document.getElementById('modal_length').textContent = length;

    document.getElementById('reslitForm').reset();
    document.getElementById('rollCountSection').style.display = 'none';
    document.getElementById('slittingForm').innerHTML = '';
    document.getElementById('submitBtn').style.display = 'none';
    new bootstrap.Modal(document.getElementById('reslitModal')).show();
}

function handleCutTypeChange() {
    const cutType = document.querySelector('input[name="cut_type"]:checked')?.value;
    document.getElementById('cut_type_value').value = cutType || '';
    document.getElementById('slittingForm').innerHTML = '';
    document.getElementById('submitBtn').style.display = 'none';
    document.getElementById('total').value = '';
    
    if (cutType === 'cut_into_2') {
        document.getElementById('rollCountSection').style.display = 'none';
        document.getElementById('total').value = '2';
        generateForm();
    } else if (cutType === 'normal') {
        document.getElementById('rollCountSection').style.display = 'block';
    }
}

function generateForm() {
    let total = parseInt(document.getElementById('total').value);
    let container = document.getElementById('slittingForm');
    let submitBtn = document.getElementById('submitBtn');
    container.innerHTML = "";

    if (!total || total <= 0) { submitBtn.style.display = 'none'; return; }

    let formHTML = '<hr><h6 class="mb-3 fw-bold text-primary">Step 3: Roll Details</h6>';
    for (let i = 1; i <= total; i++) {
        formHTML += `
            <div class="slitting-box shadow-sm">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Roll ${i}</h6>
                <input type="hidden" name="roll_number[]" value="R${i}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Cut Letter</label>
                        <select name="cut_letter[]" class="form-select form-select-sm" onchange="updateLotDisplay(this, ${i - 1})">
                            <option value="">-- None --</option><option value="a">a</option><option value="b">b</option><option value="c">c</option><option value="d">d</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-danger">New Width (mm)</label>
                        <input type="number" step="0.01" name="new_width[]" class="form-control form-select-sm highlight-field" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nominal Length</label>
                        <input type="number" step="0.01" name="length[]" class="form-control form-select-sm" value="${productData.length}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-success">Actual Length (mtr)</label>
                        <input type="number" step="0.01" name="actual_length[]" class="form-control form-select-sm highlight-field" required>
                    </div>
                </div>
                <div class="mt-2 p-2 bg-light rounded small" id="lotDisplay${i - 1}">
                    <strong>Ref:</strong> ${productData.lot_no} ${productData.coil_no} R${i} | ${productData.width}mm
                </div>
            </div>`;
    }
    container.innerHTML = formHTML;
    submitBtn.style.display = 'inline-block';
}

function updateLotDisplay(select, rollNum) {
    const cutLetter = select.value;
    const displayDiv = document.getElementById('lotDisplay' + rollNum);
    displayDiv.innerHTML = `<strong>Ref:</strong> ${productData.lot_no}${cutLetter} ${productData.coil_no} R${rollNum + 1} | ${productData.width}mm`;
}

document.getElementById('reslitForm').addEventListener('submit', function(e) {
    if (!confirm('Complete reslit process now? Product will be added back to stock.')) e.preventDefault();
});
</script>

<?php include 'footer.php'; ?>