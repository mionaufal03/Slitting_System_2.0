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

// 2. Data Fetching Logic
// New combined query
$result = $conn->query("
    (SELECT
        id,
        status,
        product,
        lot_no,
        coil_no,
        roll_no,
        width,
        length,
        actual_length,
        new_length,
        date_in,
        completed_at,
        remark,
        'recoiling_product' as source_table
    FROM recoiling_product)

    UNION ALL

    (SELECT
        id,
        'sfc' as status,
        product,
        lot_no,
        coil_no,
        '-' as roll_no,
        width,
        length,
        length as actual_length,
        NULL as new_length,
        date_in,
        NULL as completed_at,
        remark,
        'raw_material_log' as source_table
    FROM raw_material_log
    WHERE status = 'IN' AND action = 'sfc')

    ORDER BY
      CASE status
        WHEN 'sfc' THEN 1
        WHEN 'pending' THEN 2
        WHEN 'completed' THEN 3
        ELSE 4
      END,
      date_in ASC
");

$childRes = $conn->query("
    SELECT recoiling_id, lot_no, coil_no, roll_no, width, length, actual_length
    FROM slitting_product
    WHERE recoiling_id IS NOT NULL
    ORDER BY recoiling_id ASC, id ASC
");

$children = [];
if ($childRes) {
    while ($c = $childRes->fetch_assoc()) {
        $children[(int)$c['recoiling_id']][] = $c;
    }
}

// Correctly count summary
$resPending = $conn->query("SELECT COUNT(*) AS c FROM recoiling_product WHERE status='pending'");
$pending = $resPending ? (int)($resPending->fetch_assoc()['c'] ?? 0) : 0;

$resCompleted = $conn->query("SELECT COUNT(*) AS c FROM recoiling_product WHERE status='completed'");
$completed = $resCompleted ? (int)($resCompleted->fetch_assoc()['c'] ?? 0) : 0;

$page_title = "Recoiling Cut";
include 'header.php';
?>

<style>
    /* Keep your custom UI styles */
    .status-cards { display:flex; gap:15px; margin-bottom:30px; }
    .status-card { flex:1; border-radius:8px; padding:20px; text-align:center; color:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.1); transition:transform .2s; }
    .status-card:hover { transform: translateY(-5px); }
    .status-card h5 { font-size:1rem; font-weight:600; margin-bottom:10px; text-transform:uppercase; }
    .status-card h2 { font-size:2.5rem; font-weight:700; margin:0; color:#fff; }
    .status-card.pending { background: linear-gradient(135deg, #ffc107, #ff9800); }
    .status-card.completed { background: linear-gradient(135deg, #28a745, #20c997); }
    
    .roll-box { border:1px solid #ddd; padding:15px; border-radius:8px; margin-bottom:15px; background:#f9f9f9; }
    .defect-input { border:2px solid #dc3545; }
    .defect-input:focus { border-color:#dc3545; box-shadow:0 0 0 .2rem rgba(220,53,69,.25); }

    .child-row td { background: #f8f9fa; }
    .child-indent { display:flex; align-items:center; gap:8px; justify-content:center; }
    .child-indent .icon { font-size: 1.1rem; color:#6c757d; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-arrow-repeat"></i> Recoiling Cut</h2>
    <a href="?download=excel" class="btn btn-success shadow-sm">
        <i class="bi bi-download"></i> Download Excel
    </a>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= ($_GET['success'] === 'completed') ? 'Recoiling completed! Product added to stock.' : 'Product successfully added to recoiling list!' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="status-cards">
    <div class="status-card pending">
        <h5>Pending Items</h5>
        <h2><?= $pending ?></h2>
    </div>
    <div class="status-card completed">
        <h5>Completed Items</h5>
        <h2><?= $completed ?></h2>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Product</th>
                    <th>Lot & Coil No.</th>
                    <th>Roll No.</th>
                    <th>Width</th>
                    <th>Length</th>
                    <th>New Length</th>
                    <th>Date In</th>
                    <th>Remark</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                      $isSfcFromRaw = ($row['source_table'] === 'raw_material_log');
                      $rid = (int)$row['id'];
                      $status = $row['status'] ?? '';
                      $canRecoil = ($status === 'pending' || $status === 'sfc');
                      $kids = !$isSfcFromRaw ? ($children[$rid] ?? []) : [];
                    ?>
                    <tr>
                      <td><strong><?= $rid ?></strong></td>
                      <td>
                        <?php if ($status === 'sfc'): ?>
                          <span class="badge bg-info text-dark">SFC</span>
                        <?php elseif ($status === 'pending'): ?>
                          <span class="badge bg-warning text-dark">PENDING</span>
                        <?php else: ?>
                          <span class="badge bg-success">COMPLETED</span>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($row['product'] ?? '-') ?></td>
                      <td><?= htmlspecialchars((($kids[0]['lot_no'] ?? $row['lot_no'] ?? '-') . ' ' . ($kids[0]['coil_no'] ?? $row['coil_no'] ?? ''))) ?></td>
                      <td><strong><?= htmlspecialchars($kids[0]['roll_no'] ?? ($row['roll_no'] ?? '-')) ?></strong></td>
                      <td><?= isset($row['width']) ? number_format((float)$row['width']) : '-' ?></td>
                      <td><?= isset($row['actual_length']) ? number_format((float)$row['actual_length']) : '-' ?></td>
                      <td>
                        <?php
                          $nl = isset($kids[0]['actual_length']) ? (float)$kids[0]['actual_length'] : (float)($row['new_length'] ?? 0);
                          echo ($nl > 0 && !$isSfcFromRaw) ? '<strong class="text-success">' . number_format($nl) . '</strong>' : '-';
                        ?>
                      </td>
                      <td class="small"><?= htmlspecialchars($row['date_in'] ?? '-') ?></td>
                      <td class="small italic text-muted"><?= htmlspecialchars($row['remark'] ?? '-') ?></td>
                      <td>
                        <?php if ($canRecoil): ?>
                          <button class="btn btn-primary btn-sm" onclick='showRecoilingModal(<?= $rid ?>, <?= json_encode($row["product"] ?? "") ?>, <?= json_encode($row["lot_no"] ?? "") ?>, <?= json_encode($row["coil_no"] ?? "") ?>, <?= json_encode($row["roll_no"] ?? "") ?>, <?= json_encode((float)($row["width"] ?? 0)) ?>, <?= json_encode((float)($row["actual_length"] ?? 0)) ?>, <?= json_encode($row["source_table"]) ?>)'>
                            <i class="bi bi-play-circle"></i> Recoil
                          </button>
                        <?php else: ?>
                          <span class="badge bg-light text-dark border">Done</span>
                        <?php endif; ?>
                      </td>
                    </tr>

                    <?php if (count($kids) > 1): ?>
                      <?php for ($i = 1; $i < count($kids); $i++): ?>
                        <tr class="child-row">
                          <td><div class="child-indent"><i class="bi bi-arrow-return-right text-muted"></i></div></td>
                          <td><span class="badge bg-success">COMPLETED</span></td>
                          <td><?= htmlspecialchars($row['product'] ?? '-') ?></td>
                          <td><?= htmlspecialchars(($kids[$i]['lot_no'] ?? '-') . ' ' . ($kids[$i]['coil_no'] ?? ($row['coil_no'] ?? ''))) ?></td>
                          <td><strong><?= htmlspecialchars($kids[$i]['roll_no'] ?? '-') ?></strong></td>
                          <td><?= number_format((float)$kids[$i]['width']) ?></td>
                          <td><?= number_format((float)$kids[$i]['length']) ?></td>
                          <td><strong class="text-success"><?= number_format((float)$kids[$i]['actual_length']) ?></strong></td>
                          <td colspan="2"></td>
                          <td><span class="badge bg-light text-dark border">Done</span></td>
                        </tr>
                      <?php endfor; ?>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="11" class="text-center py-5 text-muted">No records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="recoilingModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Start Recoiling Process</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="recoiling_handler.php" id="recoilingForm">
        <input type="hidden" name="action" value="start_and_complete_recoiling">
        <input type="hidden" name="id" id="recoil_id">
        <input type="hidden" name="source_table" id="recoil_source_table">
        <input type="hidden" name="source_log_id" id="recoil_source_log_id">

        <div class="modal-body">
          <div class="info-box shadow-sm mb-4">
            <div class="row">
              <div class="col-6"><strong>Product:</strong> <span id="modal_product">-</span></div>
              <div class="col-6"><strong>Lot No:</strong> <span id="modal_lot">-</span></div>
              <div class="col-6 mt-2"><strong>Roll No:</strong> <span id="modal_roll">-</span></div>
              <div class="col-6 mt-2"><strong>Width:</strong> <span id="modal_width">-</span> mm</div>
              <div class="col-6 mt-2"><strong>Original Length:</strong> <span id="modal_length">-</span> m</div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-bold">Step 1: Select Cut Type</label>
            <div class="d-flex gap-4">
                <div class="form-check">
                 <input class="form-check-input" type="radio" name="cut_type" id="cutNormal" value="normal" onchange="handleCutTypeChange()">
                  <label class="form-check-label" for="cutNormal">Cut defect at start/end</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="cut_type" id="cutInto2" value="cut_into_2" onchange="handleCutTypeChange()">
                  <label class="form-check-label" for="cutInto2">Cut Length Into 2</label>
                </div>
            </div>
          </div>

          <div id="rollDetailsForm" style="display:none;">
            <hr>
            <h6 class="mb-3 fw-bold">Step 2: Enter Production Details</h6>
            <div id="rollsContainer"></div>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4" id="submitBtn" style="display:none;">
            <i class="bi bi-check-circle me-1"></i> Complete Recoiling
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let productData = {};

function showRecoilingModal(id, product, lot_no, coil_no, roll_no, width, length, source_table) {
    productData = { id, product, lot_no, coil_no, roll_no, width: parseFloat(width), length: parseFloat(length), source_table };
    document.getElementById('recoil_source_table').value = source_table;
    if (source_table === 'raw_material_log') {
        document.getElementById('recoil_source_log_id').value = id;
        document.getElementById('recoil_id').value = '';
    } else {
        document.getElementById('recoil_id').value = id;
        document.getElementById('recoil_source_log_id').value = '';
    }
    document.getElementById('modal_product').textContent = product;
    document.getElementById('modal_lot').textContent = lot_no + ' ' + coil_no;
    document.getElementById('modal_roll').textContent = roll_no;
    document.getElementById('modal_width').textContent = width;
    document.getElementById('modal_length').textContent = length;

    document.getElementById('cutNormal').checked = false;
    document.getElementById('cutInto2').checked = false;
    document.getElementById('rollsContainer').innerHTML = '';
    document.getElementById('rollDetailsForm').style.display = 'none';
    document.getElementById('submitBtn').style.display = 'none';
    new bootstrap.Modal(document.getElementById('recoilingModal')).show();
}

function  letterOptionsHTML(){
    return `<option value="">-- None --</option><option value="a">a</option><option value="b">b</option><option value="c">c</option><option value="d">d</option>`;
}

function handleCutTypeChange(){
    const selected = document.querySelector('input[name="cut_type"]:checked');
    if(!selected) return;
    const type = selected.value;
    const container = document.getElementById('rollsContainer');
    container.innerHTML = '';
    document.getElementById('rollDetailsForm').style.display = 'block';
    document.getElementById('submitBtn').style.display = 'inline-block';
    if(type === 'normal'){
        container.appendChild(buildNormalForm());
        updateNormalActual();
    } else {
        container.appendChild(buildCutInto2FormA());
        container.appendChild(buildCutInto2FormB());
        updateCutInto2ActualB();
    }
}

function buildNormalForm(){
    const div = document.createElement('div');
    div.className = 'roll-box shadow-sm';
    div.innerHTML = `
        <input type="hidden" name="new_width[]" value="${productData.width}"><input type="hidden" name="roll_number[]" value="1"><input type="hidden" name="length[]" value="${productData.length}">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Cut Letter</label>
                <select class="form-select" name="letter[]">${letterOptionsHTML()}</select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Defect (m)</label>
                <input type="number" step="0.01" name="defect[]" id="normal_defect" class="form-control defect-input" value="0">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Actual Length (m)</label>
                <input type="number" step="0.01" name="actual_length[]" id="normal_actual" class="form-control bg-light fw-bold text-success">
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Remark</label>
                <input type="text" name="remark[]" class="form-control" placeholder="Defect type / note...">
            </div>
        </div>`;
    setTimeout(() => {
        const def = document.getElementById('normal_defect');
        const act = document.getElementById('normal_actual');
        def.addEventListener('input', () => { 
            const autoVal = (productData.length || 0) - parseFloat(def.value || 0);
            act.value = (autoVal >= 0 ? autoVal : 0).toFixed(2);
        });
        act.value = productData.length.toFixed(2);
    }, 50);
    return div;
}

function buildCutInto2FormA(){
    const div = document.createElement('div');
    div.className = 'roll-box shadow-sm mb-3';
    div.innerHTML = `
        <h6 class="text-primary fw-bold">First Part</h6>
        <input type="hidden" name="new_width[]" value="${productData.width}"><input type="hidden" name="roll_number[]" value="1"><input type="hidden" name="length[]" value="${productData.length}">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label small fw-bold">Letter</label><select class="form-select form-select-sm" name="letter[]">${letterOptionsHTML()}</select></div>
            <div class="col-md-4"><label class="form-label small fw-bold">Defect</label><input type="number" step="0.01" name="defect[]" id="defectA" class="form-control form-select-sm defect-input" value="0"></div>
            <div class="col-md-4"><label class="form-label small fw-bold text-success">Actual Length</label><input type="number" step="0.01" name="actual_length[]" id="actualA" class="form-control form-select-sm fw-bold" required></div>
            <div class="col-12"><input type="text" name="remark[]" class="form-control form-select-sm" placeholder="Remark for Part 1"></div>
        </div>`;
    setTimeout(() => {
        document.getElementById('defectA').addEventListener('input', updateCutInto2ActualB);
        document.getElementById('actualA').addEventListener('input', updateCutInto2ActualB);
    }, 50);
    return div;
}

function buildCutInto2FormB(){
    const div = document.createElement('div');
    div.className = 'roll-box shadow-sm';
    div.innerHTML = `
        <h6 class="text-primary fw-bold">Second Part</h6>
        <input type="hidden" name="new_width[]" value="${productData.width}"><input type="hidden" name="roll_number[]" value="1"><input type="hidden" name="length[]" value="${productData.length}"><input type="hidden" name="defect[]" value="0">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label small fw-bold">Letter</label><select class="form-select form-select-sm" name="letter[]">${letterOptionsHTML()}</select></div>
            <div class="col-md-8"><label class="form-label small fw-bold text-success">Computed Actual Length (m)</label><input type="number" step="0.01" name="actual_length[]" id="actualB" class="form-control form-select-sm bg-light fw-bold" readonly></div>
            <div class="col-12"><input type="text" name="remark[]" class="form-control form-select-sm" placeholder="Remark for Part 2"></div>
        </div>`;
    return div;
}

function updateCutInto2ActualB(){
    const original = productData.length || 0;
    const defectA = parseFloat(document.getElementById('defectA')?.value) || 0;
    const actualA = parseFloat(document.getElementById('actualA')?.value) || 0;
    const actualBInput = document.getElementById('actualB');
    if(actualBInput) {
        const computedB = original - defectA - actualA;
        actualBInput.value = (computedB >= 0 ? computedB : 0).toFixed(2);
    }
}

document.getElementById('recoilingForm').addEventListener('submit', function(e){
    e.preventDefault();
    const selected = document.querySelector('input[name="cut_type"]:checked');
    if(!selected) { alert('Sila pilih Cut Type!'); return; }
    if(confirm('Complete recoiling, product will be added to finish product stock.')) this.submit();
});

// Check URL for success/error messages
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('success') && urlParams.get('success') === 'completed') {
    // Standard JS alert or you can use a custom UI element
    alert("✅ Recoiling Completed! The product has been moved to Finished Products.");
} else if (urlParams.has('error')) {
    alert("❌ Error: " + urlParams.get('msg'));
}
</script>

<?php include 'footer.php'; ?> 