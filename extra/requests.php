<?php
session_start();
include '../config/db.php';

function sendEmailNotification($toEmail, $subject, $message) {
    $headers = "From: no-reply@yourdomain.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    return mail($toEmail, $subject, $message, $headers);
}

function safeQuery($conn, $query, $types = "", $params = []) {
    $stmt = $conn->prepare($query);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Redirect if not admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    $request_id = (int)$_POST['request_id'];
    $new_quantity = (int)$_POST['quantity'];
    if ($new_quantity > 0) {
        // Update quantity in requests table only if status is still pending
        $conn->query("UPDATE requests SET quantity = $new_quantity WHERE id = $request_id AND status = 'pending'");
    }
    // Redirect to avoid resubmission, preserving user_id
    $redirectUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : '';
    header("Location: requests.php?user_id=$redirectUserId");
    exit;
}

// Handle approval
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];

    // Get employee email and request info
    $stmt = safeQuery($conn, 
        "SELECT u.email, u.name, i.item_name, r.quantity 
         FROM requests r
         JOIN users u ON r.employee_id = u.id
         JOIN inventory i ON r.item_id = i.id
         WHERE r.id = ?", "i", [$id]);
    $data = $stmt->get_result()->fetch_assoc();

    // Update request status to approved
    safeQuery($conn, "UPDATE requests SET status = 'approved' WHERE id = ? AND status = 'pending'", "i", [$id]);

    // Deduct quantity from inventory
    safeQuery($conn,
        "UPDATE inventory i
         JOIN requests r ON i.id = r.item_id
         SET i.quantity = i.quantity - r.quantity
         WHERE r.id = ? AND r.status = 'approved'", "i", [$id]);

    // Send email
    $subject = "Your Stationery Request Has Been Approved";
    $message = "Dear {$data['name']},\n\nYour request for '{$data['item_name']}' (Quantity: {$data['quantity']}) has been approved.\n\nThanks,\nAdmin";
    if (!sendEmailNotification($data['email'], $subject, $message)) {
        error_log("Failed to send approval email to {$data['email']}");
    }

    header("Location: requests.php" . (isset($_GET['user_id']) ? "?user_id=" . (int)$_GET['user_id'] : ""));
    exit;
}


// Handle denial
if (isset($_GET['deny'])) {
    $id = (int)$_GET['deny'];

    $stmt = safeQuery($conn, 
        "SELECT u.email, u.name, i.item_name, r.quantity 
         FROM requests r
         JOIN users u ON r.employee_id = u.id
         JOIN inventory i ON r.item_id = i.id
         WHERE r.id = ?", "i", [$id]);
    $data = $stmt->get_result()->fetch_assoc();

    // Update request
    safeQuery($conn, "UPDATE requests SET status = 'denied' WHERE id = ? AND status = 'pending'", "i", [$id]);

    // Send email
    $subject = "Your Stationery Request Has Been Denied";
    $message = "Dear {$data['name']},\n\nYour request for '{$data['item_name']}' (Quantity: {$data['quantity']}) has been denied.\n\nThanks,\nAdmin";
    if (!sendEmailNotification($data['email'], $subject, $message)) {
        error_log("Failed to send denial email to {$data['email']}");
    }

    header("Location: requests.php" . (isset($_GET['user_id']) ? "?user_id=" . (int)$_GET['user_id'] : ""));
    exit;
}


// Check if showing user requests or user list
if (isset($_GET['user_id'])) {
    // Show requests by specific user
    $user_id = (int)$_GET['user_id'];
    $sql = "SELECT r.id, u.name AS employee_name, i.item_name, r.quantity, r.status, r.request_date
            FROM requests r
            JOIN users u ON r.employee_id = u.id
            JOIN inventory i ON r.item_id = i.id
            WHERE r.employee_id = $user_id
            ORDER BY r.request_date DESC";
    $result = $conn->query($sql);

    // Get user name for heading
    $userNameRes = $conn->query("SELECT name FROM users WHERE id = $user_id");
    $userName = $userNameRes->fetch_assoc()['name'] ?? 'Unknown User';

} else {
    // Show list of users with request counts
    $sql = "SELECT u.id, u.name, COUNT(r.id) AS request_count
            FROM users u
            JOIN requests r ON r.employee_id = u.id
            GROUP BY u.id, u.name
            ORDER BY u.name ASC";
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Requests</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
    background-color: #f8f9fa;
    color: #333;
  }

  h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 24px;
    color: #2c3e50;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  th, td {
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    text-align: center;
  }

  th {
    background-color: #343a40;
    color: #fff;
    font-size: 14px;
    text-transform: uppercase;
  }

  tr:nth-child(even) {
    background-color: #f9f9f9;
  }

  .btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    transition: background-color 0.2s ease;
  }

  .btn:hover {
    opacity: 0.9;
  }

  .approve {
    background-color: #28a745;
    color: white;
  }

  .deny {
    background-color: #dc3545;
    color: white;
  }

  .btn-search {
    background-color: #007bff;
    color: white;
  }

  .back-link {
    margin-bottom: 20px;
    display: inline-block;
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
  }

  .back-link:hover {
    text-decoration: underline;
  }

  input.quantity-input {
    width: 70px;
    padding: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
    text-align: center;
  }

  form.inline-form {
    display: inline-block;
    margin: 0;
  }

  .status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: bold;
    color: white;
    display: inline-block;
    font-size: 12px;
    text-transform: capitalize;
  }

  .status-pending {
    background-color: #fd7e14;
  }

  .status-approved {
    background-color: #28a745;
  }

  .status-denied {
    background-color: #dc3545;
  }

  /* Pagination Styling */
  .pagination {
    text-align: center;
    margin-top: 20px;
  }

  .pagination a {
    margin: 0 5px;
    padding: 6px 12px;
    background: #e0e0e0;
    color: #333;
    border-radius: 4px;
    text-decoration: none;
  }

  .pagination a.active {
    background: #333;
    color: white;
  }

  /* Responsive Table */
  @media (max-width: 768px) {
    table, thead, tbody, th, td, tr {
      display: block;
    }

    th {
      position: sticky;
      top: 0;
      background: #333;
      color: white;
    }

    td {
      border: none;
      border-bottom: 1px solid #eee;
      position: relative;
      padding-left: 50%;
    }

    td::before {
      position: absolute;
      left: 10px;
      width: 45%;
      white-space: nowrap;
      font-weight: bold;
      color: #555;
    }

    td:nth-of-type(1)::before { content: "ID"; }
    td:nth-of-type(2)::before { content: "Item"; }
    td:nth-of-type(3)::before { content: "Quantity"; }
    td:nth-of-type(4)::before { content: "Status"; }
    td:nth-of-type(5)::before { content: "Date"; }
    td:nth-of-type(6)::before { content: "Action"; }
  }

  .search-box {
    display: flex;
    max-width: 400px;
    margin-bottom: 1rem;
  }
  .search-box input[type="text"] {
    flex: 1;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px 0 0 4px;
    font-size: 14px;
  }
  .search-box button {
    padding: 8px 16px;
    border: none;
    background-color: #333;
    color: white;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
    font-size: 14px;
  }
</style>
    <!-- <link rel="stylesheet" href="../style.css"> -->
</head>
<body>
<div class="container">
  
<a href="../dashboard/admin.php" class="back-link" style="display: inline-block; margin: 10px 0;">&larr; Back to Dashboard</a>

 <!-- Sidebar -->
<?php if (isset($_GET['user_id'])): 
    $user_id = (int)$_GET['user_id'];
    $search = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';
    $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $searchSql = '';
    if ($search !== '') {
        $searchSql = " AND (i.item_name LIKE '%$search%' OR r.status LIKE '%$search%')";
    }

    // Get paginated result
    $sql = "SELECT r.id, u.name AS employee_name, i.item_name, r.quantity, r.status, r.request_date
            FROM requests r
            JOIN users u ON r.employee_id = u.id
            JOIN inventory i ON r.item_id = i.id
            WHERE r.employee_id = $user_id $searchSql
            ORDER BY r.request_date DESC
            LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);

    // Total results for pagination
    $totalRes = $conn->query("SELECT COUNT(*) AS total 
        FROM requests r
        JOIN inventory i ON r.item_id = i.id
        WHERE r.employee_id = $user_id $searchSql")->fetch_assoc();

    $totalPages = ceil($totalRes['total'] / $limit);

    // Get employee name
    $userNameRes = $conn->query("SELECT name FROM users WHERE id = $user_id");
    $userName = $userNameRes->fetch_assoc()['name'] ?? 'Unknown User';
?>
  <a href="requests.php" class="back-link">&larr; Back to Employee List</a>
  <h2>Requests by <?= htmlspecialchars($userName) ?></h2>
  <form method="GET" action="requests.php" style="margin-bottom: 15px;">
  <input type="hidden" name="user_id" value="<?= $user_id ?>">
  <div class="search-box">
  <input 
    type="text" 
    name="search" 
    placeholder="Search by item or status" 
    value="<?= htmlspecialchars($search) ?>"
  >
  <button type="submit">Search</button>
</div>
</form>
  <table>
    <tr>
      <th>ID</th>
      <th>Item</th>
      <th>Quantity</th>
      <th>Status</th>
      <th>Date</th>
      <th>Action</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['item_name']) ?></td>
          <td>
            <?php if ($row['status'] == 'pending'): ?>
              <form method="POST" action="requests.php" class="inline-form">
                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                <input type="number" name="quantity" value="<?= $row['quantity'] ?>" min="1" class="quantity-input" required>
                <button type="submit" name="update_quantity" class="btn" style="background:#2196F3; color:white;">Update</button>
              
              </form>
            <?php else: ?>
              <?= $row['quantity'] ?>
            <?php endif; ?>
          </td>
          <td>
  <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
    <?= ucfirst($row['status']) ?>
  </span>
</td>

          <td><?= date("Y-m-d H:i", strtotime($row['request_date'])) ?></td>
          <td>
            <?php if ($row['status'] == 'pending'): ?>
                <a href="?user_id=<?= $user_id ?>&approve=<?= $row['id'] ?>" 
                class="btn approve" 
                onclick="return confirm('Are you sure you want to approve this request?');">
                Approve
                </a>

                <a href="?user_id=<?= $user_id ?>&deny=<?= $row['id'] ?>" 
                class="btn deny" 
                onclick="return confirm('Are you sure you want to deny this request?');">
                Deny
                </a>

            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6">No requests found for this employee.</td></tr>
    <?php endif; ?>
  </table>
  <?php if ($totalPages > 1): ?>
  <div style="text-align: center; margin-top: 20px;">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="?user_id=<?= $user_id ?>&search=<?= urlencode($search) ?>&page=<?= $p ?>" class="btn" style="<?= $p == $page ? 'background:#333;color:white;' : '' ?>">
        <?= $p ?>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php else: ?>
    <h2>Employees Who Made Requests</h2>

<table>
  <tr>
    <th>Employee ID</th>
    <th>Employee Name</th>
    <th>Number of Requests</th>
    <th>Status</th> <!-- New Column -->
  </tr>

  <?php
  // Query: Count requests and check for pending ones
  $sql = "SELECT u.id, u.name, 
                 COUNT(r.id) AS request_count,
                 SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) AS pending_count
          FROM users u
          JOIN requests r ON u.id = r.employee_id
          WHERE u.role = 'employee'
          GROUP BY u.id, u.name
          ORDER BY u.name ASC";

  $result = $conn->query($sql);
  ?>
  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td>
          <a href="?user_id=<?= $row['id'] ?>">
            <?= htmlspecialchars($row['name']) ?>
          </a>
        </td>
        <td><?= $row['request_count'] ?></td>
        <td>
          <?php if ($row['pending_count'] > 0): ?>
            <span style="color: orange; font-weight: bold;">🆕 New Request</span>
          <?php else: ?>
            <span style="color: green;">All Reviewed</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="4">No requests found.</td></tr>
  <?php endif; ?>
</table>

  <script>
  $(document).ready(function() {
    $('table').DataTable({
      pageLength: 10
    });
  });
</script>
<?php endif; ?>
</div>
</body>
</html>
