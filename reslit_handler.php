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
    $roll_numbers   = $_POST['roll_number'] ?? []; // e.g. R1, R2
    $cut_letters    = $_POST['cut_letter'] ?? [];  // e.g. a, b, None
    $new_widths     = $_POST['new_width'] ?? [];
    $lengths        = $_POST['length'] ?? [];
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
        // === 2. VALIDATION LOOP: Check for duplicates before any database changes ===
        foreach ($roll_numbers as $index => $roll_label) {
            $letter = $cut_letters[$index];
            $temp_lot_no = $parent['lot_no'] . $letter;
            $coil_no = $parent['coil_no'];

            $check = $conn->prepare("SELECT id FROM slitting_product WHERE lot_no = ? AND coil_no = ? AND roll_no = ?");
            $check->bind_param("sss", $temp_lot_no, $coil_no, $roll_label);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Duplicate Error: The roll combination [Lot: $temp_lot_no | Coil: $coil_no | Roll: $roll_label] already exists in Slitting Product Inventory.");
            }
            $check->close();
        }

        $total_actual = 0;

        // === 3. PROCESSING LOOP: Insert data (Proceeds only if no duplicates found) ===
        foreach ($roll_numbers as $index => $roll_label) {
            $letter = $cut_letters[$index];
            $width = floatval($new_widths[$index]);
            $nom_len = floatval($lengths[$index]);
            $act_len = floatval($actual_lengths[$index]);
            
            $new_lot_no = $parent['lot_no'] . $letter;
            $total_actual += $act_len;

            // A. Insert into slitting_product (Final Stock)
            $stmt_ins = $conn->prepare("INSERT INTO slitting_product 
                (mother_id, product, lot_no, coil_no, roll_no, width, length, actual_length, status, is_completed, stock_counted, date_in, source) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'IN', 1, 1, NOW(), 'reslit')");
            
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

        // 4. Update Parent Reslit Product Status to 'completed'
        $stmt_upd = $conn->prepare("UPDATE reslit_product SET status = 'completed', actual_length = ? WHERE id = ?");
        $stmt_upd->bind_param("di", $total_actual, $parent_id);
        $stmt_upd->execute();

        $conn->commit();
        header("Location: reslit.php?success=completed");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // ERROR PAGE WITH REFILL OPTION
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
            <title>Reslit Error</title>
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card border-danger shadow">
                            <div class="card-header bg-danger text-white">
                                <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reslit Process Failed</h4>
                            </div>
                            <div class="card-body p-4 text-center">
                                <p class="lead text-danger fw-bold"><?php echo $e->getMessage(); ?></p>
                                <hr>
                                <p class="text-secondary">A duplicate entry was found in the inventory. To fix this, click the button below to return to the form and change the <strong>Cut Letter</strong> (e.g., to 'a' or 'b') to make the combination unique.</p>
                                
                                <div class="d-flex justify-content-center gap-3 mt-4">
                                    <button onclick="history.back()" class="btn btn-warning px-4 fw-bold">
                                        <i class="bi bi-arrow-left"></i> Back to Refill Form
                                    </button>
                                    <a href="reslit.php" class="btn btn-outline-secondary px-4">Cancel and Exit</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    header("Location: reslit.php");
    exit;
}