<?php
include 'config.php';

$qr = $_POST['qr'] ?? '';

// =====================
// 0) Return URL (balik page asal)
// =====================
$returnUrl = $_SERVER['HTTP_REFERER'] ?? ($BASE_URL . "/index.php");
$returnUrl = trim($returnUrl);

// ✅ Security: pastikan redirect hanya dalam sistem sendiri
if (strpos($returnUrl, $BASE_URL) !== 0) {
    $returnUrl = $BASE_URL . "/index.php";
}

function back_to($returnUrl, $status)
{
    // kalau dah ada query, guna & , kalau tak ada guna ?
    $join = (strpos($returnUrl, '?') !== false) ? '&' : '?';
    header("Location: " . $returnUrl . $join . "scan=" . urlencode($status));
    exit;
}

// =====================
// 1) Normalize input
// =====================
$qr = trim($qr);
$qr = str_replace(["\r\n", "\r"], "\n", $qr);

if ($qr === '') {
    back_to($returnUrl, "empty");
}

// =====================
// 2) Ambil baris pertama sahaja
// =====================
$firstLine = strtok($qr, "\n");
$firstLine = trim($firstLine);

// =====================
// 3) Buang symbology/AIM prefix contoh: ]C1, ]Q3, ]d2 dll
// =====================
$firstLine = preg_replace('/^\][A-Za-z0-9]{2,3}/', '', $firstLine);

// =====================
// 4) Buang control characters (TAB, etc)
// =====================
$firstLine = preg_replace('/[[:cntrl:]]+/', '', $firstLine);
$firstLine = trim($firstLine);

// =====================
// 5) Parse QR (support format baru & lama)
// =====================
$lot_no = '';
$coil_no = '';
$roll_no = '';

// A) Format key=value (baru): LOT=...;COIL=...;ROLL=...
$data = [];
parse_str(str_replace(';', '&', $firstLine), $data);

if (!empty($data)) {
    $upper = [];
    foreach ($data as $k => $v) {
        $upper[strtoupper($k)] = $v;
    }
    $lot_no  = trim($upper['LOT']  ?? '');
    $coil_no = trim($upper['COIL'] ?? '');
    $roll_no = trim($upper['ROLL'] ?? '');
}

// B) Fallback format lama/compact: LOT;COIL  atau  LOT;COIL;ROLL
if ($lot_no === '' || $coil_no === '') {
    $parts = array_values(array_filter(array_map('trim', explode(';', $firstLine)), 'strlen'));
    $lot_no  = $parts[0] ?? '';
    $coil_no = $parts[1] ?? '';
    $roll_no = $parts[2] ?? $roll_no;
}

$lot_no  = trim($lot_no);
$coil_no = trim($coil_no);
$roll_no = trim($roll_no);

if ($lot_no === '' || $coil_no === '') {
    back_to($returnUrl, "invalid");
}

// =====================
// 6) Cari mother coil (ikut flow sedia ada)
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
// 7) Handle Scan Logic based on mother_coil status
// =====================
$mother_id = (int)$mother['id'];

// Begin transaction for data consistency
$conn->begin_transaction();

try {
    // Lock the row for update to prevent race conditions
    $stmt = $conn->prepare("SELECT status FROM mother_coil WHERE id=? FOR UPDATE");
    $stmt->bind_param("i", $mother_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentThreadMother = $result->fetch_assoc();
    $currentStatus = $currentThreadMother['status'];
    $stmt->close();

    if ($currentStatus === 'NEW' || $currentStatus === null) {
        // First scan: NEW -> IN
        $stmt = $conn->prepare("UPDATE mother_coil SET status='IN', date_in=NOW() WHERE id=?");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();

        // Add to raw_material_log as IN
        $stmt = $conn->prepare("INSERT INTO raw_material_log (product, lot_no, coil_no, length, width, status, date_in, action, remark) VALUES (?, ?, ?, ?, ?, 'IN', NOW(), 'IN', 'Scanned from mother coil')");
        $stmt->bind_param("ssddd",
            $mother['product'],
            $mother['lot_no'],
            $mother['coil_no'],
            $mother['length'],
            $mother['width']
        );
        $stmt->execute();
        $stmt->close();

        // Add to log
        $stmt = $conn->prepare("INSERT INTO mother_coil_log (mother_id, status) VALUES (?, 'IN')");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        back_to($returnUrl, "in");

    } elseif ($currentStatus === 'IN') {
        // Second scan: IN -> OUT
        $stmt = $conn->prepare("UPDATE mother_coil SET status='OUT', date_out=NOW() WHERE id=?");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();

        // Add to raw_material_log as OUT
        $stmt = $conn->prepare("INSERT INTO raw_material_log (product, lot_no, coil_no, length, width, status, date_out, action, remark) VALUES (?, ?, ?, ?, ?, 'OUT', NOW(), 'OUT', 'Scanned out from mother coil')");
        $stmt->bind_param(
            "ssddd",
            $mother['product'],
            $mother['lot_no'],
            $mother['coil_no'],
            $mother['length'],
            $mother['width']
        );
        $stmt->execute();
        $stmt->close();

        // Add to log
        $stmt = $conn->prepare("INSERT INTO mother_coil_log (mother_id, status) VALUES (?, 'OUT')");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Redirect to add_slitting page
        $go = "add_slitting.php?mother_id=" . $mother_id;
        header("Location: $go");
        exit;

    } elseif ($currentStatus === 'OUT') {
        // Already scanned out
        $conn->rollback();
        back_to($returnUrl, "already_out");
    }

} catch (Exception $e) {
    $conn->rollback();
    // Log error or handle it as needed
    error_log("Scan transaction failed: " . $e->getMessage());
    back_to($returnUrl, "error");
}