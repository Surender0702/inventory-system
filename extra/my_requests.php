<?php
session_start();
include '../config/db.php';

// Check if logged in and is employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit;
}

$employee_id = (int)$_SESSION['user_id'];

// Updated query with approved_quantity
$stmt = $conn->prepare("SELECT r.id, i.item_name, r.quantity AS requested_quantity, r.approved_quantity, r.status, r.request_date
                        FROM requests r
                        JOIN inventory i ON r.item_id = i.id
                        WHERE r.employee_id = ?
                        ORDER BY r.request_date DESC");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

function formatStatus($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'approved':
            return '<span class="status-approved">✔ Approved</span>';
        case 'denied':
            return '<span class="status-denied">✘ Denied</span>';
        case 'pending':
        default:
            return '<span class="status-pending">⏳ Pending</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Stationery Requests</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
    h2 { text-align: center; }
    table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    th { background: #2c3e50; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .status-pending { color: orange; font-weight: bold; }
    .status-approved { color: green; font-weight: bold; }
    .status-denied { color: red; font-weight: bold; }
  </style>
</head>
<body>

<h2>My Stationery Requests</h2>

<table>
  <tr>
    <th>ID</th>
    <th>Item</th>
    <th>Requested Qty</th>
    <th>Approved Qty</th>
    <th>Status</th>
    <th>Request Date</th>
  </tr>

  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= (int)$row['id'] ?></td>
        <td><?= htmlspecialchars($row['item_name']) ?></td>
        <td><?= (int)$row['requested_quantity'] ?></td>
        <td><?= is_null($row['approved_quantity']) ? '-' : (int)$row['approved_quantity'] ?></td>
        <td><?= formatStatus($row['status']) ?></td>
        <td><?= date("Y-m-d H:i", strtotime($row['request_date'])) ?></td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="6">No requests found.</td></tr>
  <?php endif; ?>
</table>

</body>
</html>
