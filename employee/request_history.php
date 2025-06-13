<?php
session_start();
require_once '../config/db.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit;
}

$employee_id = (int)$_SESSION['user_id'];

// Pagination configuration
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Get total request count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM requests WHERE employee_id = ?");
$countStmt->bind_param("i", $employee_id);
$countStmt->execute();
$countStmt->bind_result($totalRequests);
$countStmt->fetch();
$countStmt->close();

$totalPages = (int)ceil($totalRequests / $limit);

// Fetch paginated requests
$stmt = $conn->prepare("
    SELECT r.id, i.item_name, r.quantity AS requested_quantity, r.approved_quantity, r.status, r.request_date
    FROM requests r
    INNER JOIN inventory i ON r.item_id = i.id
    WHERE r.employee_id = ?
    ORDER BY r.request_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $employee_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Status badge formatter
function formatStatus(string $status): string {
    return match (strtolower($status)) {
        'approved' => '<span class="status-approved">✔ Approved</span>',
        'denied'   => '<span class="status-denied">✘ Denied</span>',
        default    => '<span class="status-pending">⏳ Pending</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Request History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f6f8;
    }

    .navbar {
      background: #2f4050;
      color: #fff;
      padding: 12px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .navbar .title {
      font-size: 1.3rem;
      font-weight: bold;
    }

    .navbar .user-info a {
      color: #ecf0f1;
      margin-left: 10px;
      text-decoration: underline;
    }

    .sidebar {
      position: fixed;
      top: 50px;
      left: 0;
      width: 220px;
      height: 100%;
      background: #2f4050;
      color: #fff;
      padding-top: 20px;
    }

    .sidebar a {
      display: block;
      color: white;
      padding: 12px 20px;
      text-decoration: none;
      transition: background 0.3s ease;
    }

    .sidebar a:hover {
      background: #1c2733;
    }

    .content {
      margin-left: 220px;
      padding: 20px;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: space-between;
      margin-bottom: 15px;
    }

    .filter-bar input, .filter-bar select {
      padding: 8px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 0 8px rgba(0,0,0,0.05);
    }

    th, td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: center;
    }

    th {
      background: #2c3e50;
      color: #fff;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .status-pending { color: orange; font-weight: bold; }
    .status-approved { color: green; font-weight: bold; }
    .status-denied { color: red; font-weight: bold; }

    .pagination {
  margin-top: 20px;
  text-align: center;
}
.page-link {
  margin: 0 5px;
  padding: 6px 12px;
  background: #ecf0f1;
  border-radius: 4px;
  text-decoration: none;
  color: #2c3e50;
  cursor: pointer;
}
.page-link.current-page {
  background: #2c3e50;
  color: white;
  font-weight: bold;
}


    @media (max-width: 768px) {
      .sidebar {
        position: static;
        width: 100%;
        display: flex;
        overflow-x: auto;
      }

      .sidebar a {
        flex: 1;
        text-align: center;
      }

      .content {
        margin-left: 0;
        padding: 15px;
      }
    }
  </style>
</head>
<body>
<?php
$employee_id = (int)$_SESSION['user_id'];

$name = '';
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();
?>
<!-- Navbar -->
<div class="navbar">
  <div class="title">Inventory Request System</div>
  <div class="user-info">
    👤 <?= htmlspecialchars($name) ?> (ID: <?= $employee_id ?>)
    | <a href="../auth/logout.php">Logout</a>
  </div>
</div>


<!-- Sidebar -->
<div class="sidebar">
  <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
  <a href="request_stationery.php"><i class="fas fa-boxes"></i> Request Items</a>
  <a href="request_history.php"><i class="fas fa-history"></i> Request History</a>
</div>

<!-- Main Content -->
<div class="content">
  <h2>My Stationery Request History</h2>

  <div style="margin-bottom: 15px;">
    <a href="export_excel.php" class="btn" style="background:#27ae60; color:#fff; padding: 10px 15px; text-decoration:none; border-radius:4px; margin-right:10px;">
      <i class="fas fa-file-excel"></i> Export to Excel
    </a>
    <a href="export_pdf.php" class="btn" style="background:#c0392b; color:#fff; padding: 10px 15px; text-decoration:none; border-radius:4px;">
      <i class="fas fa-file-pdf"></i> Export to PDF
    </a>
  </div>

  <!-- Filter -->
  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="Search by item name...">
    <select id="statusFilter">
      <option value="">All Status</option>
      <option value="approved">Approved</option>
      <option value="pending">Pending</option>
      <option value="denied">Denied</option>
    </select>
  </div>

  <!-- Request Table -->
  <table id="requestTable">
    <thead>
      <tr>
        <th>ID</th>
        <th>Item</th>
        <th>Requested Qty</th>
        <th>Approved Qty</th>
        <th>Status</th>
        <th>Request Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($requests)): ?>
        <?php foreach ($requests as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td><?= (int)$row['requested_quantity'] ?></td>
            <td><?= is_null($row['approved_quantity']) ? '-' : (int)$row['approved_quantity'] ?></td>
            <td data-status="<?= strtolower($row['status']) ?>"><?= formatStatus($row['status']) ?></td>
            <td><?= date("d-M-Y h:i A", strtotime($row['request_date'])) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">No requests found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <div id="pagination" class="pagination">
  <?php if ($page > 1): ?>
    <a href="#" class="page-link" data-page="<?= $page - 1 ?>">« Previous</a>
  <?php endif; ?>

  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="#" class="page-link<?= $i == $page ? ' current-page' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
  <?php endfor; ?>

  <?php if ($page < $totalPages): ?>
    <a href="#" class="page-link" data-page="<?= $page + 1 ?>">Next »</a>
  <?php endif; ?>
</div>



</div>

<script>
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const tableBody = document.querySelector('#requestTable tbody');
  const pagination = document.getElementById('pagination');

  function filterTable() {
    const keyword = searchInput.value.toLowerCase();
    const status = statusFilter.value;

    const rows = tableBody.querySelectorAll('tr');
    rows.forEach(row => {
      const itemName = row.cells[1].textContent.toLowerCase();
      const rowStatus = row.cells[4].dataset.status;
      const match = itemName.includes(keyword) && (status === '' || rowStatus === status);
      row.style.display = match ? '' : 'none';
    });
  }

  function loadPage(page) {
    fetch('fetch_requests.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'page=' + page
    })
    .then(response => response.json())
    .then(data => {
      tableBody.innerHTML = data.tableRows;
      pagination.innerHTML = data.paginationHtml;
      bindPagination(); // rebind events after loading
      filterTable();    // apply filter after new data loads
    });
  }

  function bindPagination() {
    document.querySelectorAll('.page-link').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const page = this.dataset.page;
        loadPage(page);
      });
    });
  }

  // Initial binding
  bindPagination();

  searchInput.addEventListener('input', filterTable);
  statusFilter.addEventListener('change', filterTable);
</script>


</body>
</html>
