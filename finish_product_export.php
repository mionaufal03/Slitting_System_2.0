<?php
// 1. Start output buffering to prevent corruption from notices/errors
ob_start();

include 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// 2. Get Month & Year
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

if ($month < 1 || $month > 12) { $month = (int)date('m'); }
if ($year < 2000 || $year > 2100) { $year = (int)date('Y'); }

// 3. Query - Using the slitting_product table logic
$sql = "SELECT id, status, source, product, lot_no, coil_no, roll_no, width, actual_length, date_out, delivered_at 
        FROM slitting_product 
        WHERE (is_recoiled=0 OR is_recoiled IS NULL) 
        AND (is_reslitted=0 OR is_reslitted IS NULL)
        AND (
            (status='IN' AND MONTH(date_in)=$month AND YEAR(date_in)=$year) OR 
            (status IN ('WAITING','OUT','APPROVED') AND MONTH(date_out)=$month AND YEAR(date_out)=$year) OR 
            (status='DELIVERED' AND MONTH(delivered_at)=$month AND YEAR(delivered_at)=$year)
        )
        ORDER BY id ASC";

$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Finish Product Report");

// 4. Headers
$headers = ["ID", "Status", "Source", "Product", "Lot & Coil No.", "Roll No.", "Width (mm)", "Actual Length (m)", "Date Out", "Delivered At", "QR Code"];
$sheet->fromArray($headers, NULL, 'A1');

$tempQrDir = sys_get_temp_dir() . '/qr_export_' . uniqid();
if(!is_dir($tempQrDir)) mkdir($tempQrDir, 0777, true);

$rowNum = 2;
$writer = new PngWriter();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        
        $lotCoil = trim($row['lot_no'] ?? '') . ' ' . trim($row['coil_no'] ?? '');
        // Format Roll No: R1 -> R-1
        $formattedRoll = str_replace('R', 'R-', $row['roll_no'] ?? '');

        $rowData = [
            $row['id'],
            $row['status'],
            $row['source'],
            $row['product'],
            $lotCoil,
            $formattedRoll,
            $row['width'],
            $row['actual_length'],
            $row['date_out'],
            $row['delivered_at']
        ];

        $sheet->fromArray($rowData, NULL, "A{$rowNum}");

        // Generate QR Code
        $qrTempPath = $tempQrDir . "/qr_" . $row['id'] . ".png";
        $qrContent = "ID:" . $row['id'] . " | Lot:" . $lotCoil;
        
        $qr = QrCode::create($qrContent)->setSize(200)->setMargin(10);
        $writer->write($qr)->saveToFile($qrTempPath);

        if(file_exists($qrTempPath)){
            $drawing = new Drawing();
            $drawing->setPath($qrTempPath);
            $drawing->setHeight(50); 
            $drawing->setCoordinates("K{$rowNum}"); 
            $drawing->setWorksheet($sheet);
            $sheet->getRowDimension($rowNum)->setRowHeight(45);
        }
        $rowNum++;
    }
}

// Styling
foreach (range('A','J') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
$sheet->getColumnDimension('K')->setWidth(15);

$lastRow = $rowNum - 1;
if ($lastRow >= 1) {
    $sheet->getStyle("A1:K$lastRow")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
}
$sheet->getStyle("A1:K1")->getFont()->setBold(true);
$sheet->getStyle("A1:K1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

// 5. CLEAR BUFFER before sending file
// This removes any hidden "white space" or "error text" that breaks the Excel file
ob_end_clean();

$filename = "finish_product_{$year}_{$month}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'. $filename .'"');
header('Cache-Control: max-age=0');

$xlsxWriter = new Xlsx($spreadsheet);
$xlsxWriter->save('php://output');

// Cleanup
$files = glob("$tempQrDir/*.png");
foreach($files as $file) { if(is_file($file)) unlink($file); }
if(is_dir($tempQrDir)) rmdir($tempQrDir);
exit;