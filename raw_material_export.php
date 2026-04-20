<?php
session_start();

// must login
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// ONLY slitting/admin can access
if ($_SESSION['role'] !== 'slitting') {
    die("Access denied");
}

include 'config.php';
require 'vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Get month & year from URL
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

$monthName = strtoupper(date("F", mktime(0,0,0,$month,1)));

// Query data based on your new table: stock_raw_material
// Using date_in for the monthly filter
$query = "
    SELECT grade, lot_no, coil_no, width, length, status, source_type, date_in
    FROM stock_raw_material
    WHERE MONTH(date_in) = $month AND YEAR(date_in) = $year
    ORDER BY date_in ASC
";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Raw Material Stock");

// ========== HEADER TITLE ==========
$title = "METAKOTE DEPARTMENT - RAW MATERIAL STOCK LIST $monthName $year";
$sheet->mergeCells("A1:I1"); 
$sheet->setCellValue("A1", $title);

$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(18);
$sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// ========== TABLE HEADERS ==========
$headers = ["Grade", "Lot No.", "Coil No.", "Width", "Length", "Status", "Source", "Date In", "QR Link"];
$sheet->fromArray($headers, NULL, 'A3');
$sheet->getStyle("A3:I3")->getFont()->setBold(true);
$sheet->getStyle("A3:I3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// ========== DATA POPULATION ==========
$rowNum = 4;
while($row = $result->fetch_assoc()){
    // Using coil_no for the QR Code content
    $qrLink = "https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=" . urlencode($row['coil_no']); 
    
    $sheet->fromArray([
        $row['grade'],
        $row['lot_no'],
        $row['coil_no'],
        $row['width'],
        $row['length'],
        $row['status'],
        $row['source_type'],
        $row['date_in'],
        $qrLink
    ], NULL, "A{$rowNum}");
    $rowNum++;
}

// ========== STYLING ==========
$lastRow = $rowNum - 1;
if ($lastRow >= 3) {
    $sheet->getStyle("A3:I{$lastRow}")->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER
        ]
    ]);
}

// Auto size columns
foreach (range('A','I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ========== DOWNLOAD FILE ==========
$filename = "stock_raw_material_{$year}_{$month}.xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;