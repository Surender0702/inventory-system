<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

// Validate employee session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    echo json_encode(['tableRows' => '', 'paginationHtml' => '']);
    exit;
}

$employee_id = (int)$_SESSION['user_id'];
$page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch total number of requests
$stmtCount = $conn->prepare("SELECT COUNT(*) FROM requests WHERE employee_id = ?");
$stmtCount->bind_param("i", $employee_id);
$stmtCount->execute();
$stmtCount->bind_result($totalRecords);
$stmtCount->fetch();
$stmtCount->close();

$totalPages = ceil($totalRecords / $limit);

// Fetch paginated request data
$stmt = $conn->prepare("SELECT r.id, i.item_name, r.quantity AS requested_quantity, r.approved_quantity, r.status, r.request_date
                        FROM requests r
                        JOIN inventory i ON r.item_id = i.id
                        WHERE r.employee_id = ?
                        ORDER BY r.request_date DESC
                        LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $employee_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$tableRows = '';
while ($row = $result->fetch_assoc()) {
    $status = strtolower($row['status']);
    $formattedStatus = match ($status) {
        'approved' => '<span class="status-approved">✔ Approved</span>',
        'denied'   => '<span class="status-denied">✘ Denied</span>',
        default    => '<span class="status-pending">⏳ Pending</span>',
    };

    $tableRows .= "<tr>
        <td>" . (int)$row['id'] . "</td>
        <td>" . htmlspecialchars($row['item_name']) . "</td>
        <td>" . (int)$row['requested_quantity'] . "</td>
        <td>" . (is_null($row['approved_quantity']) ? '-' : (int)$row['approved_quantity']) . "</td>
        <td data-status='{$status}'>{$formattedStatus}</td>
        <td>" . date("d-M-Y h:i A", strtotime($row['request_date'])) . "</td>
    </tr>";
}

// Generate pagination HTML
$paginationHtml = '';
if ($totalPages > 1) {
    if ($page > 1) {
        $paginationHtml .= '<a href="#" class="page-link" data-page="' . ($page - 1) . '">« Previous</a>';
    }

    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $page) ? ' current-page' : '';
        $paginationHtml .= '<a href="#" class="page-link' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
    }

    if ($page < $totalPages) {
        $paginationHtml .= '<a href="#" class="page-link" data-page="' . ($page + 1) . '">Next »</a>';
    }
}

// Return the response
echo json_encode([
    'tableRows' => $tableRows,
    'paginationHtml' => $paginationHtml
]);
