<?php
session_start();

// 1. Authentication & Role Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'slitting') {
    die("Access denied");
}

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_reslit_direct') {
    
    $parent_id = intval($_POST['id']);
    $cut_type = $_POST['cut_type'];
    
    // Arrays from the form
    $roll_numbers = $_POST['roll_number'] ?? []; // e.g. R1, R2
    $cut_letters = $_POST['cut_letter'] ?? [];  // e.g. a, b, None
    $new_widths = $_POST['new_width'] ?? [];
    $lengths = $_POST['length'] ?? [];
    $actual_lengths = $_POST['actual_length'] ?? [];

    // 1. Fetch Parent Data to get Product, Lot, Coil, and Mother ID
    $stmt = $conn->prepare("SELECT * FROM reslit_product WHERE id = ?");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $parent = $stmt->get_result()->fetch_assoc();
    
    if (!$parent) {
        die("Parent record not found.");
    }

    // Start Transaction
    $conn->begin_transaction();

    try {
        $total_actual = 0;

        // 2. Loop through each roll generated in the modal
        foreach ($roll_numbers as $index => $roll_label) {
            $letter = $cut_letters[$index];
            $width = floatval($new_widths[$index]);
            $nom_len = floatval($lengths[$index]);
            $act_len = floatval($actual_lengths[$index]);
            
            // Construct the new Lot Number (e.g. 12345 + a)
            $new_lot_no = $parent['lot_no'] . $letter;
            $total_actual += $act_len;

            // A. Insert into slitting_product (Final Stock)
            $stmt_ins = $conn->prepare("INSERT INTO slitting_product 
                (mother_id, product, lot_no, coil_no, roll_no, width, length, actual_length, status, is_completed, stock_counted, date_in) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'IN', 1, 1, NOW())");
            
            $stmt_ins->bind_param("issssddd", 
                $parent['mother_id'], 
                $parent['product'], 
                $new_lot_no, 
                $parent['coil_no'], 
                $roll_label, 
                $width, 
                $nom_len, 
                $act_len
            );
            $stmt_ins->execute();

            // B. Insert into reslit_rolls (For History/Record keeping)
            $stmt_roll = $conn->prepare("INSERT INTO reslit_rolls 
                (parent_id, roll_no, cut_letter, new_width, length, actual_length) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_roll->bind_param("issddd", 
                $parent_id, 
                $roll_label, 
                $letter, 
                $width, 
                $nom_len, 
                $act_len
            );
            $stmt_roll->execute();
        }

        // 3. Update Parent Reslit Product Status to 'completed'
        $stmt_upd = $conn->prepare("UPDATE reslit_product SET status = 'completed', actual_length = ? WHERE id = ?");
        $stmt_upd->bind_param("di", $total_actual, $parent_id);
        $stmt_upd->execute();

        $conn->commit();
        header("Location: reslit.php?success=completed");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing reslit: " . $e->getMessage());
    }
} else {
    header("Location: reslit.php");
    exit;
}