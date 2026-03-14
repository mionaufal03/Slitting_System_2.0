<?php
require_once 'config.php';
require_once __DIR__ . '/../models/MotherCoilModel.php';

session_start();

// 1. Security Check (Only allow logged-in mkl3 users to delete)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mkl3') {
    die("Access denied. You do not have permission to delete records.");
}

// 2. Validate input
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // 3. Use the Model to perform the deletion
    $model = new MotherCoilModel($conn);
    
    // We call the delete method we added to the Model in the previous step
    if ($model->delete($id)) {
        header("Location: mother_coil.php?success=3"); // Success redirect
    } else {
        echo "Error: Could not delete the record.";
    }
} else {
    echo "Invalid ID provided.";
}
exit;