
<?php
require_once 'config.php';

$selected_month = isset($_POST['month']) ? $_POST['month'] : date('m');
$selected_year = isset($_POST['year']) ? $_POST['year'] : date('Y');

$coil_in_data = [];
$coil_out_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coil In Query
    $stmt_in = $conn->prepare("SELECT * FROM mother_coil WHERE MONTH(date_in) = ? AND YEAR(date_in) = ?");
    $stmt_in->bind_param("ss", $selected_month, $selected_year);
    $stmt_in->execute();
    $result_in = $stmt_in->get_result();
    while ($row = $result_in->fetch_assoc()) {
        $coil_in_data[] = $row;
    }
    $stmt_in->close();

    // Coil Out Query
    $stmt_out = $conn->prepare("SELECT * FROM slitting_product WHERE MONTH(date_out) = ? AND YEAR(date_out) = ? AND status = 'OUT'");
    $stmt_out->bind_param("ss", $selected_month, $selected_year);
    $stmt_out->execute();
    $result_out = $stmt_out->get_result();
    while ($row = $result_out->fetch_assoc()) {
        $coil_out_data[] = $row;
    }
    $stmt_out->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coil In/Out Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        h2 {
            font-size: 22px;
            font-weight: 600;
            color: #34495e;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        .form-container {
            text-align: center;
            margin-bottom: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }
        label {
            font-weight: 500;
        }
        select, input[type="submit"] {
            border: 1px solid #dcdfe6;
            border-radius: 4px;
            padding: 10px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        select:focus, input[type="submit"]:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        input[type="submit"] {
            background-color: #4a90e2;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        input[type="submit"]:hover {
            background-color: #357ABD;
        }
        .summary {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary h3 {
            margin-top: 0;
            font-weight: 600;
            color: #2c3e50;
        }
        .summary p {
            margin: 5px 0;
            font-size: 1.1em;
            color: #555;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .report-table th, .report-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .report-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }
        .report-table tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        .report-table tbody tr:hover {
            background-color: #f0f4f8;
        }
        .grid-container { 
            display: grid;
            grid-template-columns: 1fr; 
            gap: 40px;
        }
        /* Add a bit more specific styling for the two-column layout if you switch back */
        @media (min-width: 992px) {
            .grid-container.two-column {
                grid-template-columns: 1fr 1fr;
            }
        }

        .summary {
            display: flex;
            justify-content: space-around;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            border-radius: 12px;
        }

        .summary p {
            color: #e0e0e0;
        }

        .summary strong {
            font-size: 1.5em;
            color: white;
            display: block;
}

        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            background-color: transparent;
            border: 1px solid transparent;
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: .25rem;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
            margin-bottom: 1rem;
            text-decoration: none;
        }

        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            color: #fff;
            background-color: #5a6268;
            border-color: #545b62;
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="index.php" class="btn btn-secondary">Back</a>
        <h1>Coil In & Out Report</h1>

        <div class="form-container">
            <form action="report.php" method="post">
                <label for="month">Month:</label>
                <select name="month" id="month">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($selected_month == $i) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <label for="year">Year:</label>
                <select name="year" id="year">
                    <?php for ($i = date('Y'); $i >= date('Y') - 10; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($selected_year == $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <input type="submit" value="Generate Report">
            </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <div class="summary">
                <h3>Report for <?php echo date('F', mktime(0, 0, 0, $selected_month, 10)) . ' ' . $selected_year; ?></h3>
                <p>Total Coils In: <strong><?php echo count($coil_in_data); ?></strong></p>
                <p>Total Coils Out: <strong><?php echo count($coil_out_data); ?></strong></p>
            </div>

            <div class="grid-container">
                 <div>
                    <h2>Coil In Details</h2>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Date In</th>
                                <th>Coil No</th>
                                <th>Product</th>
                                <th>Lot No</th>
                                <th>Width</th>
                                <th>Length</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coil_in_data)): ?>
                                <tr><td colspan="6">No coils came in during this period.</td></tr>
                            <?php else: ?>
                                <?php foreach ($coil_in_data as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['date_in']); ?></td>
                                        <td><?php echo htmlspecialchars($row['coil_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['product']); ?></td>
                                        <td><?php echo htmlspecialchars($row['lot_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['width']); ?></td>
                                        <td><?php echo htmlspecialchars($row['length']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h2>Coil Out Details</h2>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Date Out</th>
                                <th>Coil No</th>
                                <th>Product</th>
                                <th>Lot No</th>
                                <th>Width</th>
                                <th>Length</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php if (empty($coil_out_data)): ?>
                                <tr><td colspan="6">No coils went out during this period.</td></tr>
                            <?php else: ?>
                                <?php foreach ($coil_out_data as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['date_out']); ?></td>
                                        <td><?php echo htmlspecialchars($row['coil_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['product']); ?></td>
                                        <td><?php echo htmlspecialchars($row['lot_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['width']); ?></td>
                                        <td><?php echo htmlspecialchars($row['length']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
