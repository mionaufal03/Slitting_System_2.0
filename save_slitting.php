<?php
include 'config.php';

// Get POST data
$source_type = trim($_POST['source_type']); // 'mother' or 'stock'
$product     = trim($_POST['product']);
$lot_no      = trim($_POST['lot_no']);
$coil_no     = trim($_POST['coil_no']);
$total       = intval($_POST['total']);
$cut_type    = trim($_POST['cut_type']);

$roll_nos    = $_POST['roll_no'];
$widths      = $_POST['width'];
$lengths     = $_POST['length'];
$cut_letters = $_POST['cut_letter'] ?? [];

$slit_quantity = isset($_POST['slit_quantity']) ? floatval($_POST['slit_quantity']) : null;
$stock         = isset($_POST['stock']) ? floatval($_POST['stock']) : null;

// Determine source & FIX Mother ID issue
$mother_id = null; // Set to null initially
$stock_id = 0;
$source_data = null;

if($source_type === 'mother') {
    $mother_id = intval($_POST['mother_id']);
    $source_data = $conn->query("SELECT * FROM mother_coil WHERE id=$mother_id")->fetch_assoc();
} else if($source_type === 'stock') {
    $stock_id = intval($_POST['stock_id']);
    $source_data = $conn->query("SELECT * FROM raw_material_log WHERE id=$stock_id")->fetch_assoc();
    
    // AMBIL MOTHER_ID ASAL DARI LOG (PENTING UNTUK FK)
    if($source_data && !empty($source_data['mother_id'])) {
        $mother_id = intval($source_data['mother_id']);
    } else {
        $mother_id = null; // Jika tiada rujukan mother asal, biar null (pastikan DB allow null)
    }
}

// Insert each slitting product
for($i = 0; $i < $total; $i++){
    $roll_no = trim($roll_nos[$i]);
    $width   = trim($widths[$i]);
    $length  = trim($lengths[$i]);
    
    $final_lot_no = $lot_no;    
    if(isset($cut_letters[$i]) && $cut_letters[$i] !== ''){
        $final_lot_no = $lot_no . $cut_letters[$i];
    }

    // UPDATE: Gunakan mother_id yang telah divalidasi
    $stmt = $conn->prepare("INSERT INTO slitting_product 
        (mother_id, product, lot_no, coil_no, roll_no, width, length, cut_type, slit_quantity, stock, status, is_completed, stock_counted, source) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IN', 0, 0, ?)");

    $source_value = ($source_type === 'stock') ? 'sfg' : 'raw_material'; // Update source label
    
    $stmt->bind_param(
        "isssssssdds",
        $mother_id, // Sekarang tidak lagi 0 jika dari stock
        $product,
        $final_lot_no,
        $coil_no,
        $roll_no,
        $width,
        $length,
        $cut_type,
        $slit_quantity,
        $stock,
        $source_value
    );

    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();
}

// ===================== NEW: HANDLE SFC BALANCE FOR NORMAL CUT =====================
if ($cut_type === 'normal' && isset($_POST['sfc_balance_width']) && is_numeric($_POST['sfc_balance_width']) && floatval($_POST['sfc_balance_width']) > 0) {
    $sfc_width = (string)floatval($_POST['sfc_balance_width']);
    
    if ($source_data) {
        $original_length = floatval($source_data['length']);
        $sfc_stmt = $conn->prepare(
            "INSERT INTO sfc (product, lot_no, coil_no, width, length, date_created) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $sfc_stmt->bind_param(
            "ssssd",
            $source_data['product'],
            $source_data['lot_no'],
            $source_data['coil_no'],
            $sfc_width,
            $original_length
        );
        $sfc_stmt->execute();
        $sfc_stmt->close();
    }
}

// ===================== UPDATE RAW MATERIAL LOG =====================
if($source_data) {
    $action_value = $cut_type;
    
    if($source_type === 'mother') {
        // Update original raw material to OUT
        $stmt = $conn->prepare("UPDATE raw_material_log SET status='OUT', date_out=NOW(), action=? 
                               WHERE product=? AND lot_no=? AND coil_no=? AND status='IN' LIMIT 1");
        $stmt->bind_param("ssss", $action_value, $source_data['product'], $source_data['lot_no'], $source_data['coil_no']);
        $stmt->execute();
        $stmt->close();

        // Update mother_coil
        $stmt = $conn->prepare("UPDATE mother_coil SET status='OUT', date_out=NOW() WHERE id=?");
        $stmt->bind_param("i", $mother_id);
        $stmt->execute();
        $stmt->close();
        
    } else if($source_type === 'stock') {
        // Update stock after cut to OUT
        $stmt = $conn->prepare("UPDATE raw_material_log SET status='OUT', date_out=NOW(), action=? WHERE id=?");
        $stmt->bind_param("si", $action_value, $stock_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Record 2: If cut_into_2 insert NEW leftover (MESTI BAWA MOTHER_ID)
    if($cut_type === 'cut_into_2' && !empty($stock) && $stock > 0) {
        $remark = ($source_type === 'stock') ? 'Stock leftover from stock after cut' : 'Stock leftover from slitting';
        
        $stmt = $conn->prepare("INSERT INTO raw_material_log 
                               (mother_id, product, lot_no, coil_no, length, width, status, date_in, action, remark) 
                               VALUES (?, ?, ?, ?, ?, ?, 'IN', NOW(), 'cut_into_2', ?)");
        
        $width_value = isset($source_data['width']) ? $source_data['width'] : 0;
        
        $stmt->bind_param("isssdds",
            $mother_id, // Kekalkan pertalian dengan mother coil asal
            $source_data['product'],
            $source_data['lot_no'],
            $source_data['coil_no'],
            $stock,
            $width_value,
            $remark
        );
        
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: finish_product.php?success=1");
exit;
?>