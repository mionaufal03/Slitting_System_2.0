<?php
include 'config.php';

$qr = $_POST['qr'] ?? '';

// =====================
// 0) Return URL Logic
// =====================
$returnUrl = $_SERVER['HTTP_REFERER'] ?? ($BASE_URL . "/index.php");
$returnUrl = trim($returnUrl);

if (strpos($returnUrl, $BASE_URL) !== 0) {
    $returnUrl = $BASE_URL . "/index.php";
}

function back_to($returnUrl, $status)
{
    $join = (strpos($returnUrl, '?') !== false) ? '&' : '?';
    header("Location: " . $returnUrl . $join . "scan=" . urlencode($status));
    exit;
}

// =====================
// 1) Normalize Input
// =====================
$qr = trim($qr);
$qr = str_replace(["\r\n", "\r"], "\n", $qr);

if ($qr === '') {
    back_to($returnUrl, "empty");
}

$firstLine = strtok($qr, "\n");
$firstLine = trim($firstLine);
$firstLine = preg_replace('/^\][A-Za-z0-9]{2,3}/', '', $firstLine);
$firstLine = preg_replace('/[[:cntrl:]]+/', '', $firstLine);
$firstLine = trim($firstLine);

// =====================
// 2) Parse QR (LOT=...;COIL=...)
// =====================
$lot_no = '';
$coil_no = '';

$data = [];
parse_str(str_replace(';', '&', $firstLine), $data);

if (!empty($data)) {
    $upper = [];
    foreach ($data as $k => $v) { $upper[strtoupper($k)] = $v; }
    $lot_no  = trim($upper['LOT']  ?? '');
    $coil_no = trim($upper['COIL'] ?? '');
}

if ($lot_no === '' || $coil_no === '') {
    $parts = array_values(array_filter(array_map('trim', explode(';', $firstLine)), 'strlen'));
    $lot_no  = $parts[0] ?? '';
    $coil_no = $parts[1] ?? '';
}

if (trim($lot_no) === '' || trim($coil_no) === '') {
    back_to($returnUrl, "invalid");
}

// =====================
// 3) Find Mother Coil
// =====================
$stmt = $conn->prepare("SELECT * FROM mother_coil WHERE lot_no=? AND coil_no=?");
$stmt->bind_param("ss", $lot_no, $coil_no);
$stmt->execute();
$mother = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mother) {
    back_to($returnUrl, "notfound");
}

// =====================
// 4) Handle Scan Logic
// =====================
$mother_id = (int)$mother['id'];
$conn->begin_transaction();

try {
    // Select for update to prevent race conditions
    $stmt = $conn->prepare("SELECT status FROM mother_coil WHERE id=? FOR UPDATE");
    $stmt->bind_param("i", $mother_id);
    $stmt->execute();
    $currentStatus = $stmt->get_result()->fetch_assoc()['status'];
    $stmt->close();

    if ($currentStatus === 'NEW' || $currentStatus === null || $currentStatus === '') {
        // First scan: Mark as IN and update Stock Boolean
        $stmt = $conn->prepare("UPDATE mother_coil SET status='IN', stock=1, date_in=NOW() WHERE id=?");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();

        // Normalized raw_material_log (Only FK and status)
        $stmt = $conn->prepare("INSERT INTO raw_material_log (mother_id, status, date_in, action, remark) VALUES (?, 'IN', NOW(), 'SCAN_IN', 'Material scanned into production')");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();

        // New Unified Audit Log
        $stmt = $conn->prepare("INSERT INTO mother_coil_audit_log (mother_id, action_type, performed_at, remark) VALUES (?, 'SCAN_IN', NOW(), 'QR Code scanned at intake')");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        back_to($returnUrl, "in");

    } elseif ($currentStatus === 'IN') {
        // If already IN, proceed to process (Slitting)
        $conn->commit();
        header("Location: add_slitting.php?mother_id=" . $mother_id);
        exit;

    } elseif ($currentStatus === 'OUT') {
        $conn->rollback();
        back_to($returnUrl, "already_out");
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Scan transaction failed: " . $e->getMessage());
    back_to($returnUrl, "error");
}