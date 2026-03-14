<?php
session_start();
require_once 'init.php';
require_once MODEL_PATH . 'RawMaterialModel.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'slitting') {
    die("Access denied");
}

$model = new RawMaterialModel($conn);

// 2. Handle Inputs
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// 3. Request Data from Model
$stats       = $model->getMonthlyStats($month, $year);
$logsResult  = $model->getLogs($month, $year);
$cutResult   = $model->getStockAfterCut($month, $year);

// 4. Render the UI
include VIEW_PATH . 'raw_material_view.php';