<base href="http://localhost/inventory-system/admin/">
<?php
include '../config/db.php';
include '../header.php';
session_start();

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    include '../config/db.php';

    $employee_id = $_GET['employee_id'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    $filters = [];
    $params = [];
    $param_types = '';

    if ($employee_id !== '') {
        $filters[] = "r.employee_id = ?";
        $params[] = $employee_id;
        $param_types .= 'i';
    }
    if ($start_date && $end_date) {
        $filters[] = "r.request_date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $param_types .= 'ss';
    }

    $where_clause = count($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="employee_request_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee Name', 'Item Name', 'Quantity', 'Approved Quantity', 'Status', 'Request Date']);

    $sql = "
        SELECT u.name as employee_name, i.item_name, r.quantity, r.approved_quantity, r.status, r.request_date
        FROM requests r
        JOIN inventory i ON r.item_id = i.id
        JOIN users u ON r.employee_id = u.id
        $where_clause
        ORDER BY r.request_date DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($param_types) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['employee_name'],
            $row['item_name'],
            $row['quantity'],
            $row['approved_quantity'],
            ucfirst($row['status']),
            $row['request_date']
        ]);
    }
    fclose($output);
    exit;
}

include '../config/db.php';

// Admin check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Unauthorized access.");
}

// Filters
$employee_id = $_GET['employee_id'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$filters = [];
$params = [];
$param_types = '';

if ($employee_id !== '') {
    $filters[] = "r.employee_id = ?";
    $params[] = $employee_id;
    $param_types .= 'i';
}
if ($start_date && $end_date) {
    $filters[] = "r.request_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $param_types .= 'ss';
}

$where_clause = count($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';

// Summary helpers
function getStatusCount($conn, $status, $where_clause, $param_types, $params) {
    $sql = "SELECT COUNT(*) FROM requests r $where_clause" . ($where_clause ? " AND" : " WHERE") . " r.status = ?";
    $stmt = $conn->prepare($sql);
    $params_with_status = [...$params, $status];
    $types = $param_types . 's';
    $stmt->bind_param($types, ...$params_with_status);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

function getTotalCount($conn, $where_clause, $param_types, $params) {
    $sql = "SELECT COUNT(*) FROM requests r $where_clause";
    $stmt = $conn->prepare($sql);
    if ($param_types) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

$total = getTotalCount($conn, $where_clause, $param_types, $params);
$approved = getStatusCount($conn, 'approved', $where_clause, $param_types, $params);
$denied = getStatusCount($conn, 'denied', $where_clause, $param_types, $params);
$pending = getStatusCount($conn, 'pending', $where_clause, $param_types, $params);

// Top requested items
$sql = "
    SELECT i.item_name, COUNT(*) as request_count
    FROM requests r
    JOIN inventory i ON r.item_id = i.id
    $where_clause
    GROUP BY r.item_id
    ORDER BY request_count DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
if ($param_types) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$top_items = $stmt->get_result();

$employees = $conn->query("SELECT id, name FROM users ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Report</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .main-wrapper {
            display: flex;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .main-content {
            flex-grow: 1;
            padding: 40px 30px;
            background: #f4f6f9;
            color: #2f3640;
            margin-left: 300px;
        }
        h2, h3 {
            font-weight: 600;
            margin-bottom: 20px;
        }
        form.filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
        }
        select, input[type="date"] {
            padding: 8px 10px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 16px;
            font-size: 14px;
            background: #273c75;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .summary-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .card {
            flex: 1;
            min-width: 200px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            text-align: center;
        }
        .card h4 {
            font-size: 15px;
            margin-bottom: 6px;
            color: #888;
        }
        .card p {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
        }
        th {
            background: #f7f9fc;
            font-weight: 600;
        }
        tr:hover {
            background: #f1f2f6;
        }
    </style>
     <link rel="stylesheet" href="../admin/admin-style.css">
</head>
<body>
<div class="main-wrapper">
    <?php include '../sidebar.php'; ?>
    <div class="main-content">
        <h2>Employee-wise Inventory Report</h2>

        <form method="GET" class="filters">
            <label>
                Employee:
                <select name="employee_id">
                    <option value="">-- All Employees --</option>
                    <?php while ($e = $employees->fetch_assoc()): ?>
                        <option value="<?= $e['id'] ?>" <?= ($e['id'] == $employee_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label>
                From: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </label>
            <label>
                To: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </label>
            <button type="submit">Generate Report</button>
        </form>

        <form method="GET" action="report.php">
            <input type="hidden" name="export" value="csv">
            <input type="hidden" name="employee_id" value="<?= htmlspecialchars($employee_id) ?>">
            <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            <button type="submit" style="background:#44bd32; margin-bottom:20px;">Export CSV</button>
        </form>

        <div class="summary-cards">
            <div class="card"><h4>Total Requests</h4><p><?= $total ?></p></div>
            <div class="card"><h4>Approved</h4><p><?= $approved ?></p></div>
            <div class="card"><h4>Denied</h4><p><?= $denied ?></p></div>
            <div class="card"><h4>Pending</h4><p><?= $pending ?></p></div>
        </div>

        <h3>Top 5 Requested Items</h3>
        <table>
            <thead>
                <tr><th>Item Name</th><th>Request Count</th></tr>
            </thead>
            <tbody>
                <?php while ($item = $top_items->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= $item['request_count'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 style="margin-top:40px;">📋 Detailed Request Logs</h3>
        <table id="logsTable" class="display nowrap">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Approved Quantity</th>
                    <th>Status</th>
                    <th>Request Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_log = "SELECT u.name AS employee_name, i.item_name, r.quantity, r.approved_quantity, r.status, r.request_date
                            FROM requests r
                            JOIN users u ON r.employee_id = u.id
                            JOIN inventory i ON r.item_id = i.id
                            ORDER BY r.request_date DESC
                            LIMIT 200";
                $log_result = $conn->query($sql_log);
                while ($row = $log_result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['employee_name']) ?></td>
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= $row['approved_quantity'] ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td><?= date('Y-m-d', strtotime($row['request_date'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(document).ready(function() {
    $('#logsTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copyHtml5', 'csvHtml5', 'excelHtml5', 'pdfHtml5', 'print'],
        responsive: true,
        pageLength: 10
    });
});
</script>
</body>
</html>