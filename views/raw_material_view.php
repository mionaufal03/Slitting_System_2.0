<!DOCTYPE html>
<html>
<head>
    <title>Raw Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-3">Raw Material</h2>

    <div class="row text-center mb-4">
        <div class="col-md-3">
            <div class="card text-bg-white"><div class="card-body">
                <h5 class="mb-0">IN</h5><h2 class="mb-0"><?= (int)$stats['in'] ?></h2>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-white"><div class="card-body">
                <h5 class="mb-0">OUT</h5><h2 class="mb-0"><?= (int)$stats['out'] ?></h2>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-primary"><div class="card-body">
                <h5 class="mb-0">STOCK</h5><h2 class="mb-0"><?= (int)$stats['stock'] ?></h2>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success"><div class="card-body">
                <h5 class="mb-0">AFTER CUT STOCK</h5><h2 class="mb-0"><?= (int)$stats['after_cut'] ?></h2>
            </div></div>
        </div>
    </div>

    <h4 class="mt-4 mb-3">Raw Material Log</h4>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr><th>Product</th><th>Lot No.</th><th>Coil No.</th><th>Date In</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php while($row = $logsResult->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['product'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['lot_no'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['coil_no'] ?? '-') ?></td>
                    <td><?= $row['date_in'] ?></td>
                    <td><?= $row['action'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    </div>
</body>
</html>