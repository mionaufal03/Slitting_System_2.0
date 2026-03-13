<?php
require_once 'init.php';
require_once MODEL_PATH . 'MotherCoilModel.php';

session_start();
if (!isset($_SESSION['role'])) { header("Location: login.php"); exit; }

$model = new MotherCoilModel($conn);

// 1. Handle AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_product') {
    header('Content-Type: application/json');
    $product = $model->productFromCoil($_GET['coil'] ?? '');
    echo json_encode(['ok' => ($product !== ''), 'product' => $product]);
    exit;
}

// 2. Handle POST Actions (Add/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] === 'mkl3') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            // Auto-lookup product name before saving
            $_POST['product'] = $model->productFromCoil($_POST['coil_no']);
            $model->add($_POST);
        } elseif ($action === 'update') {
            $model->update($_POST);
        }
        
        header("Location: mother_coil.php?success=1");
        exit;
    }
}

// 3. Fetch Data for Display
$coilsResult = $model->getAll();

// 4. Load the View
include VIEW_PATH . 'mother_coil_list.php';