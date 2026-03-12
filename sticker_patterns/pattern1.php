<?php
// Pattern 1 - NAE,NRI,STAMPING with Standard Weight Lookup

$patternCSS = "
.sticker-container {
    width: 120mm;
    height: 47mm;
    background: white;
    border: 0px;
    padding: 1.9mm 1.5mm;
    position: relative;
    margin: 0 auto;
    page-break-after: always;
    overflow: hidden;
}

/* QR Code - Top Right Corner */
.qr-code {
    position: absolute;
    top: 5mm;
    right: -4mm;
    width: 27mm;
    height: 27mm;
}

.qr-code img {
    width: 80%;
    height: 80%;
    display: block;
    border: 0px;
}

.qr-placeholder {
    width: 100%;
    height: 100%;
    border: 2px dashed #ccc;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 10pt;
    color: #999;
    text-align: justify;
}

.internal-use {
    position: absolute;
    top: -8%;
    left: 39%;
    transform: translate(-50%, -50%);
    background: none;
    padding: 0mm 0mm;
    font-size: 8pt;
    font-weight: bold;
    color: #333;
    white-space: nowrap;
    pointer-events: none;
    z-index: 10;
}

.roll-number {
    position: absolute;
    bottom: 9mm;
    right: 4mm;
    font-size: 32pt;
    font-weight: bold;
    line-height: 1;
    background: none;
    background-color: transparent;
    
}

/* Main Content - Left Side */
.content-left {
    width: calc(100% - 15mm);
    padding-right: 5mm;
}

.row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-bottom: 1.8mm;
    font-size: 12pt;
}

.label {
    width: 28mm;
    flex-shrink: 0;
}

.colon {
    width: 2mm;
    text-align: center;
    flex-shrink: 0;
}

.value {
    text-align: center;
    font-weight: bold;
    border-bottom: 1.5px solid black;
    padding-bottom: 0.5mm;
    min-height: 18px;
}

.value.underline-text {
    border-bottom: none;
    text-decoration: underline;
    flex: 1;
}

.value.no-line {
    border-bottom: none !important;
    font-weight: normal !important;
}

/* Row 2 - Grade */
.row:nth-child(2) .value {
    width: 70mm !important;
}

/* Row 3 - Size */
.row:nth-child(3) .size-row {
    width: 70mm !important;
}
 
/* Row 4 - Lot No */
.row:nth-child(4) .value {
    width: 70mm !important;
}

/* Row 5 - Customer */
.row:nth-child(5) .value {
    width: 80mm !important;
}

/* Row 6 - Ref No */
.row:nth-child(6) .value {
    width: 70mm !important;
    border-bottom: none !important;
    font-weight: normal !important;
}

/* Size Row with mm x Mtr */
.size-row {
    display: flex;
    align-items: center;
    border-bottom: 1px solid black;
    padding-bottom: 0.5mm;
    padding-left: 4mm;
}

.size-number {
    text-align: center;
    font-weight: bold;
    font-size: 15pt;
    min-width: 15mm;
}

.size-unit {
    padding: 0 2mm;
    font-size: 15pt;
    font-weight: normal;
}

.size-x {
    padding: 0 2mm;
    font-weight: bold;
}
";

// Function to convert customer code to full name
function getCustomerFullName($code) {
    $customers = [
        'NAE' => 'NICHIAS AUTOPARTS EUROPE (NAE)',
        'NAX' => 'NAX MFG, SA.DE C.V',
        'NCI MFG' => 'NCI MFG., INC.',
        'TAIHO' => 'TAIHO MFG OF TN. INC',
        'NRI' => 'PT NICHIAS ROCKWOOL IND.',
        'ASHUKA' => 'ASHUKA TECHNOLOGIES SDN. BHD.',
        'NIPPON' => 'NTC(NIPPON GASKET)',
        'NTC' => 'NICHIAS THAILAND',
        'SGC' => 'SHANGHAI XINGSHENG',
        'STAMPING' => 'MK STAMPING',
        'YANTAI' => 'NICHIAS (SHANGHAI) AUTOPARTS TRADING',
        'NIP' => 'NICHIAS IND. PRODUCTS PVT. LTD.',
        'STOCK' => 'STOCK',
        'TRIAL' => 'TRIAL'
    ];
    
    return $customers[$code] ?? $code; // Return code if not found
}

// Function to get standard weight from database
function getStandardWeight($conn, $product_code) {
    $stmt = $conn->prepare("SELECT std_weight FROM std_wgt WHERE product_code = ?");
    $stmt->bind_param("s", $product_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (float)$row['std_weight'];
    }
    
    $stmt->close();
    return 0; // Default if not found
}

function render_sticker($product, $customer, $ref_no, $tomboNo, $lotNo, $qrImageUrl) {
    // Get standard weight directly from product
    $std_weight = $product['std_weight'] ?? 0;
    
    // Calculate estimated weight
    // Formula: est_weight = std_weight x [(width x actual_length) / 1000]
    $width = $product['width'];
    $actual_length = $product['actual_length'] ?? $product['length'];
    $est_weight = $std_weight * (($width * $actual_length) / 1000);
    
    // Round to nearest whole number (141.75 = 142, 141.11 = 141)
    $est_weight = round($est_weight);
    
    ob_start();
    ?>
    <div class="sticker-container">
        <!-- QR Code -->
        <div class="qr-code">
            <img src="<?= $qrImageUrl ?>" alt="QR Code" 
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="qr-placeholder">
                QR Code<br>Failed to Load<br>
                <small style="font-size:7pt;">Check generate qr</small>
            </div>
            <div class="internal-use">INTERNAL USE</div>
        </div>
        
        <!-- Roll Number -->
        <div class="roll-number">
            <?= htmlspecialchars($product['roll_no']) ?>
        </div>
        
        <!-- Content -->
        <div class="content-left">
            <!-- TOMBO No -->
            <div class="row">
                <div class="label">TOMBO No.</div>
                <div class="colon">:</div>
                <div class="value underline-text"><?= $tomboNo ?></div>
            </div>
            
            <!-- Grade -->
            <div class="row">
                <div class="label">Grade</div>
                <div class="colon">:</div>
                <div class="value"><?= htmlspecialchars($product['product']) ?></div>
            </div>
            
            <!-- Size -->
            <div class="row">
                <div class="label">Size</div>
                <div class="colon">:</div>
                <div class="size-row">
                    <span class="size-number"><?= number_format($product['width'], 0) ?></span>
                    <span class="size-unit">mm</span>
                    <span class="size-x">x</span>
                    <span class="size-number"><?= number_format($product['actual_length'] ?? $product['length'], 0) ?></span>
                    <span class="size-unit">Mtr</span>
                </div>
            </div>
            
            <!-- Lot No -->
            <div class="row">
                <div class="label">Lot No.</div>
                <div class="colon">:</div>
                <div class="value"><?= htmlspecialchars($lotNo) ?></div>
            </div>
            
            <!-- Customer - Show full name, single line, smaller font -->
            <div class="row">
                <div class="label">Customer</div>
                <div class="colon">:</div>
                <div class="value" style="font-size: 10pt; font-weight: normal; text-overflow: ellipsis;"><?= htmlspecialchars(getCustomerFullName($customer)) ?></div>
            </div>
            
            <!-- Ref No with Est. Wgt on same line -->
            <div class="row" style="margin-bottom: 0mm;">
                <div class="label">Ref. No.</div>
                <div class="colon">:</div>
                <div class="value no-line" style="width: 50mm; text-align: center; white-space: nowrap; overflow: visible;"><?= htmlspecialchars($ref_no) ?></div>
                <div style="margin-right: -13mm; margin-left: 1mm; display: flex; align-items: center; white-space: nowrap;">
                    <span style="font-weight: normal; font-size: 11pt;">Est. Wgt (kg): </span>
                    <span style="font-weight: bold; font-size: 18pt; display: inline-block; min-width: -10px; text-align: center; margin-left: 5mm;"><?= $est_weight > 0 ? number_format($est_weight, 0) : '0' ?></span>                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>