<?php
session_start();
include '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

define('RESULTS_PER_PAGE', 10);

// CSRF token generation & validation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'], $_POST['selected_requests'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: requests.php");
        exit;
    }

    $action = $_POST['bulk_action'];
    $selected = $_POST['selected_requests']; // array of request IDs

    foreach ($selected as $req_id) {
        $req_id = intval($req_id);
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE requests SET status = 'approved' WHERE id = ?");
        } elseif ($action === 'deny') {
            $stmt = $conn->prepare("UPDATE requests SET status = 'denied' WHERE id = ?");
        }
        if ($stmt) {
            $stmt->bind_param("i", $req_id);
            $stmt->execute();
        }
    }

    $_SESSION['success_message'] = "Selected requests " . htmlspecialchars($action) . "d successfully.";
    header("Location: requests.php");
    exit;
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header('Location: requests.php');
        exit;
    }

    $request_id = (int)$_POST['request_id'];
    $new_quantity = (int)$_POST['quantity'];

    // Accept quantity >= 0 now
    if ($new_quantity >= 0) {
        // Optional: Check if request is still pending
        $check = $conn->prepare("SELECT id FROM requests WHERE id = ? AND status = 'pending'");
        $check->bind_param("i", $request_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE requests SET approved_quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $request_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = "Approved quantity updated successfully.";
        } else {
            $_SESSION['error_message'] = "Invalid or already processed request.";
        }
        $check->close();
    } else {
        $_SESSION['error_message'] = "Quantity must be zero or greater.";
    }

    $redirect_emp = $_POST['employee_id'] ?? '';
    header("Location: requests.php" . ($redirect_emp ? "?employee_id=" . (int)$redirect_emp : ''));
    exit();
}


// Handle approve/deny request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header('Location: requests.php');
        exit;
    }

    $action = $_POST['action'];
    $request_id = (int)$_POST['request_id'];

    $stmt = $conn->prepare("SELECT status, item_id, quantity, approved_quantity FROM requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if ($request && $request['status'] === 'pending') {
        $final_quantity = $request['approved_quantity'] > 0 ? $request['approved_quantity'] : $request['quantity'];

        if ($action === 'approve') {
            $stmt2 = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
            $stmt2->bind_param("i", $request['item_id']);
            $stmt2->execute();
            $invRes = $stmt2->get_result();
            $item = $invRes->fetch_assoc();

            if ($item && $item['quantity'] >= $final_quantity) {
                $stmt3 = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                $stmt3->bind_param("ii", $final_quantity, $request['item_id']);
                $stmt3->execute();

                $stmt4 = $conn->prepare("UPDATE requests SET status = 'approved' WHERE id = ?");
                $stmt4->bind_param("i", $request_id);
                $stmt4->execute();
                $_SESSION['success_message'] = "Request approved successfully.";
            } else {
                $_SESSION['error_message'] = "Not enough inventory to approve request ID $request_id.";
            }
        } elseif ($action === 'deny') {
            $stmt4 = $conn->prepare("UPDATE requests SET status = 'denied' WHERE id = ?");
            $stmt4->bind_param("i", $request_id);
            $stmt4->execute();
            $_SESSION['success_message'] = "Request denied successfully.";
        }
    }
    $redirect_emp = $_POST['employee_id'] ?? '';
    header("Location: requests.php" . ($redirect_emp ? "?employee_id=" . (int)$redirect_emp : ''));
    exit();
}

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Function to get total rows for pagination
function getTotalRows($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

// Fetch employees who made requests with pagination
function fetchEmployees($conn, $searchTerm, $limit, $offset) {
    if ($searchTerm) {
        $searchTermLike = "%$searchTerm%";
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id, u.name
            FROM users u
            JOIN requests r ON u.id = r.employee_id
            WHERE u.name LIKE ?
            ORDER BY u.name ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("sii", $searchTermLike, $limit, $offset);
    } else {
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id, u.name
            FROM users u
            JOIN requests r ON u.id = r.employee_id
            ORDER BY u.name ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch requests for a given employee with pagination
function fetchRequestsByEmployee($conn, $employee_id, $searchTerm, $limit, $offset) {
    if ($searchTerm) {
        $searchTermLike = "%$searchTerm%";
        $stmt = $conn->prepare("
            SELECT r.id, r.item_id, r.quantity, r.approved_quantity, r.status, r.request_date, i.item_name
            FROM requests r
            JOIN inventory i ON r.item_id = i.id
            WHERE r.employee_id = ? AND (i.item_name LIKE ? OR r.status LIKE ?)
            ORDER BY r.request_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("issii", $employee_id, $searchTermLike, $searchTermLike, $limit, $offset);
    } else {
        $stmt = $conn->prepare("
            SELECT r.id, r.item_id, r.quantity, r.approved_quantity, r.status, r.request_date, i.item_name
            FROM requests r
            JOIN inventory i ON r.item_id = i.id
            WHERE r.employee_id = ?
            ORDER BY r.request_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $employee_id, $limit, $offset);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch employee name for header
$employee_name = '';
if ($employee_id) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $emp = $res->fetch_assoc();
    $employee_name = $emp ? htmlspecialchars($emp['name']) : "Unknown";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Inventory Requests</title>
    <link rel="stylesheet" href="../admin/admin-style.css">
    <style>
        /* Container for sidebar + main content */
        .container {
            display: flex;
            min-height: 100vh;
        }
        main {
            flex-grow: 1;
            padding: 20px;
            /* background-color: #f9f9f9; */
            padding-left: 200px;
        }
        .inline-form {
            display: inline-block;
            margin-right: 10px;
        }
        /* Button styles */
        .btn { padding: 6px 12px; border: none; cursor: pointer; border-radius: 4px; }
        .btn-approve { background-color: #28a745; color: #fff; }
        .btn-deny { background-color: #dc3545; color: #fff; }
        .btn-update { background-color: #007bff; color: #fff; }
        .btn-disabled { background-color: #6c757d; color: #fff; cursor: not-allowed; }
        .btn-back { margin-bottom: 15px; display: inline-block; }
        .search-form input[type="search"] { padding: 6px; width: 200px; }
        .search-form button { padding: 6px 10px; margin-left: 5px; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f1f1f1; }
        .message.success { background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.error { background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .pagination { margin-top: 15px; }
        .pagination a, .pagination span.current {
            padding: 6px 12px;
            margin-right: 4px;
            border-radius: 4px;
            text-decoration: none;
            border: 1px solid #ccc;
            color: #333;
        }
        .pagination span.current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
            font-weight: bold;
        }
        /* Status Badge Styling */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            color: white;
            font-size: 0.85em;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
            margin-right: 6px;
        }
        .status-new { background-color: #28a745; }
        .status-pending { background-color: #ffc107; color: #212529; }
        .status-review { background-color: #17a2b8; }

        .btn {
    padding: 8px 16px;
    font-size: 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}
    </style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="container">
    <?php include '../sidebar.php'; ?>

    <main>
        <?php
        // Messages (Success / Error)
        if (!empty($_SESSION['success_message'])) {
            echo '<div class="message success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        if (!empty($_SESSION['error_message'])) {
            echo '<div class="message error">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <?php if ($employee_id === 0): ?>
            <!-- ===================== Employee List ===================== -->
            <h2>Inventory Requests Management</h2>

            <form method="get" class="search-form" action="requests.php">
                <input type="search" name="search" placeholder="Search employees" value="<?= htmlspecialchars($search) ?>" />
                <button type="submit" class="btn btn-approve">Search</button>
                <?php if ($search): ?>
                    <a href="requests.php" class="btn btn-deny" style="margin-left:10px;">Clear</a>
                <?php endif; ?>
            </form>

            <?php
            // Pagination and employee fetching
            $total_rows = $search
                ? getTotalRows($conn, "SELECT COUNT(DISTINCT u.id) FROM users u JOIN requests r ON u.id = r.employee_id WHERE u.name LIKE ?", ["%$search%"], "s")
                : getTotalRows($conn, "SELECT COUNT(DISTINCT u.id) FROM users u JOIN requests r ON u.id = r.employee_id");

            $total_pages = ceil($total_rows / RESULTS_PER_PAGE);
            $offset = ($page - 1) * RESULTS_PER_PAGE;
            $employees = fetchEmployees($conn, $search, RESULTS_PER_PAGE, $offset);

            // Prepare employee ID list
            $employee_ids = [];
            while ($row = $employees->fetch_assoc()) {
                $employee_ids[] = $row['id'];
            }
            $employees->data_seek(0); // Reset result pointer

            // Fetch request counts
            $requestCounts = [];
            if (!empty($employee_ids)) {
                $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
                $stmt = $conn->prepare("SELECT employee_id, status, COUNT(*) as cnt FROM requests WHERE employee_id IN ($placeholders) GROUP BY employee_id, status");
                $types = str_repeat('i', count($employee_ids));
                $bind = [$types];
                foreach ($employee_ids as $i => $id) $bind[] = &$employee_ids[$i];
                call_user_func_array([$stmt, 'bind_param'], $bind);
                $stmt->execute();
                $res = $stmt->get_result();

                foreach ($employee_ids as $eid) {
                    $requestCounts[$eid] = ['new' => 0, 'pending' => 0, 'review' => 0];
                }
                while ($r = $res->fetch_assoc()) {
                    $eid = $r['employee_id'];
                    $status = strtolower($r['status']);
                    $requestCounts[$eid][$status] = $r['cnt'];
                }
                $stmt->close();
            }
            ?>
            <!-- ===== Employee Table ===== -->
            <table>
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Requests Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employees->num_rows > 0): ?>
                        <?php while ($emp = $employees->fetch_assoc()): ?>
                            <?php $c = $requestCounts[$emp['id']]; ?>
                            <tr>
                                <td><?= htmlspecialchars($emp['name']) ?></td>
                                <td>
                                    <!-- <span class="status-badge status-new">New: <?= $c['new'] ?></span> -->
                                    <span class="status-badge status-pending">Pending: <?= $c['pending'] ?></span>
                                    <!-- <span class="status-badge status-review">Review: <?= $c['review'] ?></span> -->
                                </td>
                                <td><a href="requests.php?employee_id=<?= $emp['id'] ?>" class="btn btn-approve">View Requests</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;">No employees found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ===== Pagination ===== -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <?= $p == $page
                            ? "<span class='current'>$p</span>"
                            : "<a href='requests.php?page=$p" . ($search ? '&search=' . urlencode($search) : '') . "'>$p</a>" ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ===================== Individual Employee Request View ===================== -->
            <a href="requests.php" class="btn-back">← Back to Employee List</a>
            <h2>Requests by Employee: <?= $employee_name ?> (ID: <?= $employee_id ?>)</h2>

            <!-- Search Form -->
            <form method="get" class="search-form" action="requests.php">
                <input type="hidden" name="employee_id" value="<?= $employee_id ?>" />
                <input type="search" name="search" placeholder="Search by item or status" value="<?= htmlspecialchars($search) ?>" />
                <button type="submit" class="btn btn-approve">Search</button>
                <?php if ($search): ?>
                    <a href="requests.php?employee_id=<?= $employee_id ?>" class="btn btn-deny" style="margin-left:10px;">Clear</a>
                <?php endif; ?>
            </form>

            <?php
            $total_rows = $search
                ? getTotalRows($conn, "SELECT COUNT(*) FROM requests r JOIN inventory i ON r.item_id = i.id WHERE r.employee_id = ? AND (i.item_name LIKE ? OR r.status LIKE ?)", [$employee_id, "%$search%", "%$search%"], "iss")
                : getTotalRows($conn, "SELECT COUNT(*) FROM requests WHERE employee_id = ?", [$employee_id], "i");

            $total_pages = ceil($total_rows / RESULTS_PER_PAGE);
            $offset = ($page - 1) * RESULTS_PER_PAGE;
            $requests = fetchRequestsByEmployee($conn, $employee_id, $search, RESULTS_PER_PAGE, $offset);
            ?>
            <!-- ===== Requests Table ===== -->
            <?php $current_page = $_GET['page'] ?? 1; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
                <input type="hidden" name="page" value="<?= $current_page ?>" />

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin: 15px 0;">
                    <button type="submit" name="bulk_action" value="approve" class="btn btn-success">
                        Approve Selected
                    </button>
                    <button type="submit" name="bulk_action" value="deny" class="btn btn-danger">
                        Deny Selected
                    </button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all" /></th>
                            <th>Request ID</th>
                            <th>Item</th>
                            <th>Requested Qty</th>
                            <th>Approved Qty</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests->num_rows > 0): ?>
                            <?php while ($r = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <input type="checkbox" name="selected_requests[]" value="<?= $r['id'] ?>" class="select_item" />
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['item_name']) ?></td>
                                    <td><?= $r['quantity'] ?></td>
                                    <td><?= $r['approved_quantity'] ?: '-' ?></td>
                                    <td style="text-transform: capitalize;"><?= htmlspecialchars($r['status']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($r['request_date'])) ?></td>
                                    <td>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <form class="inline-form update-form" data-id="<?= $r['id'] ?>">
                                                <input type="number" name="quantity" min="0" value="<?= $r['approved_quantity'] ?: $r['quantity'] ?>" required />
                                                <button type="button" class="btn btn-update">Update</button>
                                            </form>

                                            <form class="inline-form approve-form" data-id="<?= $r['id'] ?>">
                                                <button type="button" class="btn btn-approve">Approve</button>
                                            </form>

                                            <form class="inline-form deny-form" data-id="<?= $r['id'] ?>">
                                                <button type="button" class="btn btn-deny">Deny</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #666; font-style: italic;">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;">No requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <script>
            // Toggle select all checkboxes
            document.getElementById('select_all').addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.select_item');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
            </script>

            <!-- ===== Pagination ===== -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $query_params = "employee_id=$employee_id" . ($search ? '&search=' . urlencode($search) : '');

                // First & Previous
                if ($page > 1) {
                    echo "<a href='requests.php?$query_params&page=1' class='first'>&laquo; First</a>";
                    echo "<a href='requests.php?$query_params&page=" . ($page - 1) . "' class='prev'>&lsaquo; Prev</a>";
                }

                // Page numbers around current
                $range = 2; // number of links left and right of current
                $start = max(1, $page - $range);
                $end = min($total_pages, $page + $range);

                if ($start > 1) {
                    echo "<a href='requests.php?$query_params&page=1'>1</a>";
                    if ($start > 2) echo "<span class='dots'>...</span>";
                }

                for ($p = $start; $p <= $end; $p++) {
                    echo $p == $page
                        ? "<span class='current'>$p</span>"
                        : "<a href='requests.php?$query_params&page=$p'>$p</a>";
                }

                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) echo "<span class='dots'>...</span>";
                    echo "<a href='requests.php?$query_params&page=$total_pages'>$total_pages</a>";
                }

                // Next & Last
                if ($page < $total_pages) {
                    echo "<a href='requests.php?$query_params&page=" . ($page + 1) . "' class='next'>Next &rsaquo;</a>";
                    echo "<a href='requests.php?$query_params&page=$total_pages' class='last'>Last &raquo;</a>";
                }
                ?>
            </div>
        <?php endif; ?>


        <?php endif; ?>
    </main>
</div>

<?php include '../footer.php'; ?>

<!-- ajax code for without loading code update, approved and deny -->
<script>
document.body.addEventListener('click', function (e) {
    // Handle Update Quantity button click
    const updateBtn = e.target.closest('.btn-update');
    if (updateBtn) {
        const form = updateBtn.closest('.update-form');
        if (!form) {
            alert('Form not found.');
            return;
        }
        const requestId = form.getAttribute('data-id');
        const quantityInput = form.querySelector('input[name="quantity"]');
        const quantityStr = quantityInput.value.trim();
        const quantity = Number(quantityStr);

        if (quantityStr === '' || isNaN(quantity) || quantity < 0) {
            alert('Please enter a valid quantity (zero or greater).');
            return;
        }

        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_quantity',
                request_id: requestId,
                quantity: quantity,
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'  // Or pass from JS variable if available
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Handle Approve button click
    const approveBtn = e.target.closest('.btn-approve');
    if (approveBtn && approveBtn.closest('.approve-form')) {
        const form = approveBtn.closest('.approve-form');
        const requestId = form.getAttribute('data-id');

        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'approve',
                request_id: requestId,
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Handle Deny button click
    const denyBtn = e.target.closest('.btn-deny');
    if (denyBtn && denyBtn.closest('.deny-form')) {
        const form = denyBtn.closest('.deny-form');
        const requestId = form.getAttribute('data-id');

        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'deny',
                request_id: requestId,
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
});

</script>
<!-- ajax code for without loading code update, approved and deny -->
<script src="ajax_manage_request_script.js"></script>
</body>
</html>