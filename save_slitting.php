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

// Determine source & Validate Mother ID
$mother_id = null;
$stock_id = 0;
$source_data = null;

if($source_type === 'mother') {
    $mother_id = intval($_POST['mother_id']);
    // Pull current data from mother_coil
    $source_data = $conn->query("SELECT * FROM mother_coil WHERE id=$mother_id")->fetch_assoc();
} else if($source_type === 'stock') {
    $stock_id = intval($_POST['stock_id']);
    // Pull current data from raw_material_log (Stock After Cut)
    $source_data = $conn->query("SELECT * FROM raw_material_log WHERE id=$stock_id")->fetch_assoc();
    
    if($source_data && !empty($source_data['mother_id'])) {
        $mother_id = intval($source_data['mother_id']);
    }
}

if (!$source_data) {
    die("Error: Source material not found.");
}

// Start Transaction to ensure data integrity
$conn->begin_transaction();

try {
    // 1. Insert each slitting product (Finished Goods)
    for($i = 0; $i < $total; $i++){
        $roll_no = trim($roll_nos[$i]);
        $width   = trim($widths[$i]);
        $length  = trim($lengths[$i]);
        
        $final_lot_no = $lot_no;    
        if(isset($cut_letters[$i]) && $cut_letters[$i] !== ''){
            $final_lot_no = $lot_no . $cut_letters[$i];
        }

        $stmt = $conn->prepare("INSERT INTO slitting_product 
            (mother_id, product, lot_no, coil_no, roll_no, width, length, cut_type, slit_quantity, stock, status, is_completed, stock_counted, source) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IN', 0, 0, ?)");

        $source_value = ($source_type === 'stock') ? 'sfg' : 'raw_material';
        
        $stmt->bind_param("isssssssdds", $mother_id, $product, $final_lot_no, $coil_no, $roll_no, $width, $length, $cut_type, $slit_quantity, $stock, $source_value);
        $stmt->execute();
        $stmt->close();
    }

    // 2. Handle SFC Balance (Scrap/Leftover Width)
    if ($cut_type === 'normal' && isset($_POST['sfc_balance_width']) && floatval($_POST['sfc_balance_width']) > 0) {
        $sfc_width = floatval($_POST['sfc_balance_width']);
        $original_length = floatval($source_data['length']);
        
        $sfc_stmt = $conn->prepare("INSERT INTO sfc (product, lot_no, coil_no, width, length, date_created) VALUES (?, ?, ?, ?, ?, NOW())");
        $sfc_stmt->bind_param("ssssd", $product, $lot_no, $coil_no, $sfc_width, $original_length);
        $sfc_stmt->execute();
        $sfc_stmt->close();
    }

    // 3. Update Material Lifecycle (Status & Audit Log)
    if($source_type === 'mother') {
        // Update master table
        $upd_mother = $conn->prepare("UPDATE mother_coil SET status='OUT', stock=0, date_out=NOW() WHERE id=?");
        $upd_mother->bind_param("i", $mother_id);
        $upd_mother->execute();

        // Update existing raw log entry
        $upd_raw = $conn->prepare("UPDATE raw_material_log SET status='OUT', date_out=NOW(), action=? WHERE mother_id=? AND status='IN' LIMIT 1");
        $upd_raw->bind_param("si", $cut_type, $mother_id);
        $upd_raw->execute();

    } else if($source_type === 'stock') {
        // Update Stock After Cut log
        $upd_stock = $conn->prepare("UPDATE raw_material_log SET status='OUT', date_out=NOW(), action=? WHERE id=?");
        $upd_stock->bind_param("si", $cut_type, $stock_id);
        $upd_stock->execute();
    }

    // 4. Record the "OUT" action in the Unified Audit Log
    $audit_remark = "Processed via slitting ($cut_type)";
    $audit = $conn->prepare("INSERT INTO mother_coil_audit_log (mother_id, action_type, performed_at, remark) VALUES (?, 'OUT', NOW(), ?)");
    $audit->bind_param("is", $mother_id, $audit_remark);
    $audit->execute();

    // 5. If "Cut Into 2", create the new leftover stock record
    if($cut_type === 'cut_into_2' && $stock > 0) {
        $remark = ($source_type === 'stock') ? 'Stock leftover from stock after cut' : 'Stock leftover from slitting';
        
        // Note: product, lot_no, coil_no are now omitted if you fully normalized, 
        // but kept here as per your current table structure in raw_material_log
        $ins_leftover = $conn->prepare("INSERT INTO raw_material_log (mother_id, status, date_in, action, length, remark) VALUES (?, 'IN', NOW(), 'cut_into_2', ?, ?)");
        $ins_leftover->bind_param("ids", $mother_id, $stock, $remark);
        $ins_leftover->execute();
        
        // Log the return to stock in audit log
        $audit_in = $conn->prepare("INSERT INTO mother_coil_audit_log (mother_id, action_type, performed_at, remark) VALUES (?, 'IN', NOW(), 'Leftover from Cut Into 2 returned to stock')");
        $audit_in->bind_param("i", $mother_id);
        $audit_in->execute();
    }

    $conn->commit();
    header("Location: finish_product.php?success=1");

} catch (Exception $e) {
    $conn->rollback();
    die("Transaction failed: " . $e->getMessage());
}
exit;
?>