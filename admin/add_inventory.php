<?php
session_start();
include '../config/db.php';

// Redirect non-admin users
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ===================== Handle Add Inventory =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $quantity = (int)$_POST['quantity'];

    // this for checking duplicate item name
    $check_stmt = $conn->prepare("SELECT id FROM inventory WHERE item_name = ?");
    $check_stmt->bind_param("s", $item_name);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $_SESSION['error'] = "Item with this name already exists!";
        header("Location: add_inventory.php");
        exit;
    }
    $check_stmt->close();

    // Upload image if exists
    $image_path = '';
    if (!empty($_FILES['item_image']['name'])) {
        $image_name = time() . '_' . basename($_FILES['item_image']['name']);
        $upload_path = '../uploads/' . $image_name;
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_path)) {
            $image_path = $image_name;
        }
    }

    $stmt = $conn->prepare("INSERT INTO inventory (item_name, description, quantity, image_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $item_name, $description, $quantity, $image_path);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "Item added successfully!";
    header("Location: add_inventory.php");
    exit;
}

// ===================== Handle Delete =====================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM inventory WHERE id = $id");
    $_SESSION['success'] = "Item deleted!";
    header("Location: add_inventory.php");
    exit;
}

// ===================== Filters & Pagination =====================
$search = trim($_GET['search'] ?? '');
$qty_filter = $_GET['qty_filter'] ?? [];
if (!is_array($qty_filter)) $qty_filter = [];

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE conditions
$whereClauses = [];
$params = [];
$types = '';

// Search condition
if ($search !== '') {
    $whereClauses[] = "item_name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
// Quantity filter using HAVING clause
$qtyConditions = [];
foreach ($qty_filter as $filter) {
    if ($filter === 'red') {
        $qtyConditions[] = "(i.quantity - IFNULL(SUM(r.approved_quantity), 0)) < 10";
    } elseif ($filter === 'yellow') {
        $qtyConditions[] = "(i.quantity - IFNULL(SUM(r.approved_quantity), 0)) BETWEEN 10 AND 20";
    } elseif ($filter === 'green') {
        $qtyConditions[] = "(i.quantity - IFNULL(SUM(r.approved_quantity), 0)) > 20";
    }
}
$having_sql = '';
if (!empty($qtyConditions)) {
    $having_sql = 'HAVING ' . implode(' OR ', $qtyConditions);
}
// Final WHERE string
$whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// ===================== Main Data Query with Approved Quantity =====================
$query = "
    SELECT 
        i.*, 
        IFNULL(SUM(r.approved_quantity), 0) AS approved_qty
    FROM inventory i
    LEFT JOIN requests r 
        ON i.id = r.item_id AND r.status = 'approved'
    $whereSql
    GROUP BY i.id
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


// ===================== Count for Pagination =====================
$count_query = "SELECT COUNT(*) FROM inventory $whereSql";
$count_stmt = $conn->prepare($count_query);

if (count($whereClauses) > 0) {
    // Only bind search parameter (not limit/offset)
    $count_params = [];
    $count_types = '';

    if ($search !== '') {
        $count_params[] = "%$search%";
        $count_types .= 's';
    }

    $count_stmt->bind_param($count_types, ...$count_params);
}

$count_stmt->execute();
$count_stmt->bind_result($total_items);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_items / $limit);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f8f9fa; }
    .content-wrapper { margin-left: 250px; padding: 20px; }
    .form-card, .table-card { background: #fff; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.05); padding: 20px; }
    img.item-img { width: 50px; height: auto; }
    .pagination { justify-content: center; }
    /* Color legend boxes for filter */
    .color-box {
      display: inline-block;
      width: 20px;
      height: 20px;
      margin-right: 6px;
      vertical-align: middle;
      border-radius: 3px;
    }
    .color-red { background-color: #dc3545; }       /* Bootstrap danger red */
    .color-yellow { background-color: #d39e00; }    /* Custom yellow */
    .color-green { background-color: #198754; }     /* Bootstrap success green */
  </style>
    <link rel="stylesheet" href="../admin/admin-style.css">
</head>
<body>

<?php include '../header.php'; ?>
<?php include '../sidebar.php'; ?>

<div class="content-wrapper">
<div class="card card-custom shadow-sm p-4 mb-4 rounded-3">
    <h3 class="text-primary mb-4">➕ Add New Inventory Item</h3>

    <!-- ==== Flash Messages ==== -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ==== Inventory Form ==== -->
    <form method="POST" enctype="multipart/form-data" class="row g-4">
        <div class="col-md-4">
            <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
            <input type="text" id="item_name" name="item_name" class="form-control" required placeholder="Enter item name" />
        </div>

        <div class="col-md-2">
            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" id="quantity" name="quantity" class="form-control" required min="1" placeholder="0" />
        </div>

        <div class="col-md-3">
            <label for="item_image" class="form-label">Upload Image</label>
            <input type="file" id="item_image" name="item_image" class="form-control" accept="image/*" />
        </div>

        <div class="col-md-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Optional description..."></textarea>
        </div>

        <div class="col-12 d-flex justify-content-end">
            <button type="submit" name="add_item" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Item
            </button>
        </div>
    </form>
</div>

  
  <div class="table-card shadow-sm p-4 bg-white rounded">
  <h4 class="mb-4 text-primary">📦 Inventory List</h4>

  <!-- Filter by Quantity Form -->
<!-- <form method="GET" class="row g-3 align-items-end mb-3">
  <div class="col-md-auto">
    <label class="form-label fw-semibold">Filter by Quantity:</label>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="filterRed" name="qty_filter[]" value="red"
        <?= in_array('red', $qty_filter) ? 'checked' : '' ?>>
      <label class="form-check-label" for="filterRed">
        <span class="color-box color-red me-1"></span><small>Less than 10</small>
      </label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="filterYellow" name="qty_filter[]" value="yellow"
        <?= in_array('yellow', $qty_filter) ? 'checked' : '' ?>>
      <label class="form-check-label" for="filterYellow">
        <span class="color-box color-yellow me-1"></span><small>10 to 20</small>
      </label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="filterGreen" name="qty_filter[]" value="green"
        <?= in_array('green', $qty_filter) ? 'checked' : '' ?>>
      <label class="form-check-label" for="filterGreen">
        <span class="color-box color-green me-1"></span><small>More than 20</small>
      </label>
    </div>
  </div>

  <div class="col-auto">
    <button type="submit" class="btn btn-success mt-2">Apply Filter</button>
  </div>

  <div class="col-auto">
    <a href="add_inventory.php" class="btn btn-outline-secondary mt-2">Reset</a>
  </div>
</form> -->

<!-- Search Item Form -->
<form method="GET" class="row g-3 align-items-end mb-4">
  <div class="col-md">
    <label for="search" class="form-label fw-semibold">Search Item:</label>
    <input type="text" id="search" name="search" class="form-control" placeholder="Enter item name..." value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary mt-2">Search</button>
  </div>
  <div class="col-auto">
    <a href="add_inventory.php" class="btn btn-outline-secondary mt-2">Reset</a>
  </div>
</form>


  <!-- Export Buttons -->
  <div class="d-flex justify-content-end gap-2 mb-3">
    <a href="export_excel.php" class="btn btn-outline-success btn-sm">📊 Export to Excel</a>
    <a href="export_pdf.php" class="btn btn-outline-danger btn-sm">📄 Export to PDF</a>
  </div>
  <div class="mb-3">
  <label class="form-check-label me-3">
    <input type="checkbox" class="form-check-input filter-checkbox" value="red"> 
    <span class="badge bg-danger">Red (&lt;10)</span>
  </label>
  <label class="form-check-label me-3">
    <input type="checkbox" class="form-check-input filter-checkbox" value="yellow"> 
    <span class="badge bg-warning text-dark">Yellow (11–20)</span>
  </label>
  <label class="form-check-label">
    <input type="checkbox" class="form-check-input filter-checkbox" value="green"> 
    <span class="badge bg-success">Green (&gt;20)</span>
  </label>
</div>
<!-- <div class="mb-3">
  <label for="itemLimit" class="form-label">Show Items:</label>
  <select id="itemLimit" class="form-select" style="width: auto; display: inline-block;">
    <option value="all">All</option>
    <option value="20">20</option>
    <option value="30">30</option>
    <option value="40">40</option>
  </select>
</div> -->
<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Item</th>
          <th>Total Qty</th>
          <th>Approved Qty</th>
          <th>Available Qty</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php
              $total = (int)$row['quantity'];
              $approved = (int)$row['approved_qty'];
              $available = max(0, $total - $approved); // Prevent negative values
            ?>
            <tr data-available="<?= $available ?>">

              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['item_name']) ?></td>
              <td><span class="badge bg-primary"><?= $total ?></span></td>
              <td><span class="badge bg-secondary"><?= $approved ?></span></td>
              <td>
                <?php if ($available < 10): ?>
                  <span class="badge bg-danger"><?= $available ?></span>
                <?php elseif ($available <= 20): ?>
                  <span class="badge bg-warning text-dark"><?= $available ?></span>
                <?php else: ?>
                  <span class="badge bg-success"><?= $available ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($row['image_path']): ?>
                  <img src="../uploads/<?= htmlspecialchars($row['image_path']) ?>" alt="Item Image" class="img-thumbnail">
                <?php else: ?>
                  N/A
                <?php endif; ?>
              </td>
              <td>
                <a href="edit_inventory.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏ Edit</a>
                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Are you sure you want to delete this item?')">🗑 Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr data-available="<?= $available ?>">

            <td colspan="7" class="text-center text-muted">No items found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <nav class="mt-3">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« Prev</a>
          </li>
        <?php endif; ?>
        
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
          <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
          <li class="page-item">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next »</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
<script>
  document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const red = document.querySelector('input[value="red"]').checked;
      const yellow = document.querySelector('input[value="yellow"]').checked;
      const green = document.querySelector('input[value="green"]').checked;

      document.querySelectorAll('tbody tr').forEach(row => {
        const available = parseInt(row.getAttribute('data-available'));
        let show = false;

        if (red && available < 10) show = true;
        if (yellow && available >= 11 && available <= 20) show = true;
        if (green && available > 20) show = true;

        row.style.display = show || (!red && !yellow && !green) ? '' : 'none';
      });
    });
  });

  // document.getElementById('itemLimit').addEventListener('change', function () {
  //   const limit = this.value === 'all' ? Infinity : parseInt(this.value);
  //   const rows = document.querySelectorAll('tbody tr');
    
  //   rows.forEach((row, index) => {
  //     row.style.display = index < limit ? '' : 'none';
  //   });
  // });
</script>

</body>
</html>