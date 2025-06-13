<?php
session_start();
include '../config/db.php';
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=inventory_report.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Item Name', 'Old Quantity', 'New Quantity', 'Changed By', 'Changed At']);

    $export_query = $query; // Reuse the earlier query
    $export_stmt = $conn->prepare($export_query);
    if ($types !== "") {
        $export_stmt->bind_param($types, ...$params);
    }
    $export_stmt->execute();
    $export_result = $export_stmt->get_result();

    while ($row = $export_result->fetch_assoc()) {
        fputcsv($out, [
            $row['item_name'],
            $row['old_quantity'],
            $row['new_quantity'],
            $row['changed_by'],
            $row['changed_at']
        ]);
    }
    fclose($out);
    exit;
}

// Date filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$query = "SELECT log.*, inv.item_name 
          FROM inventory_quantity_log log
          JOIN inventory inv ON log.inventory_id = inv.id";

// Apply date filters if present
$params = [];
$types = "";
if ($startDate && $endDate) {
    $query .= " WHERE DATE(log.changed_at) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types = "ss";
}

$query .= " ORDER BY log.changed_at DESC";
$stmt = $conn->prepare($query);

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Update Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4 bg-light">

<div class="container">
    <h2 class="mb-4">📊 Inventory Quantity Change Report</h2>
    <div class="col-md-3 d-flex align-items-end">
    <a href="quantity_report.php?export=csv&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-success w-100">⬇️ Export to Excel</a>
</div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
        </div>
        <div class="col-md-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">🔍 Filter</button>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <a href="quantity_report.php" class="btn btn-secondary w-100">🔄 Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Old Quantity</th>
                    <th>New Quantity</th>
                    <th>Changed By</th>
                    <th>Changed At</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): $i = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= $row['old_quantity'] ?></td>
                            <td><?= $row['new_quantity'] ?></td>
                            <td><?= htmlspecialchars($row['changed_by']) ?></td>
                            <td><?= date("d M Y, h:i A", strtotime($row['changed_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
