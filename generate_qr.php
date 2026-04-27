<?php
// Prevent error messages from breaking the image stream
error_reporting(E_ALL & ~E_DEPRECATED); 
ini_set('display_errors', 0);

require __DIR__ . '/vendor/autoload.php';
include 'config.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

$type = $_GET['type'] ?? 'mother';
$size = 300; // Optimal size for display
$qrText = '';

// --- LOGIC BLOCK ---
if ($type === 'slitting') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) die('Invalid ID');

    $stmt = $conn->prepare("SELECT lot_no, coil_no, roll_no FROM slitting_product WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) die('Slitting product not found');
    $qrText = "LOT={$row['lot_no']};COIL={$row['coil_no']};ROLL={$row['roll_no']}";

/*} else {
    $lot  = trim($_GET['lot'] ?? '');
    $coil = trim($_GET['coil'] ?? '');

    if ($lot === '' || $coil === '') die('Invalid QR data');

    // ✅ CHANGE THIS: Use the format your scan_mother_action.php expects
    // This creates a string like "LOT=123;COIL=456" instead of a URL
    $qrText = "LOT=$lot;COIL=$coil";
} */

} else {
    $lot  = trim($_GET['lot'] ?? '');
    $coil = trim($_GET['coil'] ?? '');

    if ($lot === '' || $coil === '') die('Invalid QR data');

    // This creates a string like "LOT=826175;COIL=FK-1"
    $qrText = "LOT=$lot;COIL=$coil";
}

// Ensure there is always text
if (empty($qrText)) $qrText = "NO_DATA";

// --- GENERATION BLOCK (New Syntax) ---
$writer = new PngWriter();

// Create QR Code
$qrCode = QrCode::create($qrText)
    ->setEncoding(new Encoding('UTF-8'))
    ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low) // Fixed the Fatal Error here
    ->setSize($size)
    ->setMargin(10)
    ->setForegroundColor(new Color(0, 0, 0))
    ->setBackgroundColor(new Color(255, 255, 255));

try {
    $result = $writer->write($qrCode);

    // Output Header
    header('Content-Type: ' . $result->getMimeType());
    header('Cache-Control: no-cache, must-revalidate');

    // Stream image
    echo $result->getString();
} catch (Exception $e) {
    die("Error generating QR code");
}
exit;