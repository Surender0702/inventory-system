<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$employee_id = $_SESSION['user_id'];
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['items'])) {
    foreach ($_POST['items'] as $item) {
        $item_id = (int)$item['item_id'];
        $quantity = (int)$item['quantity'];

        // Fetch stock
        $stmt = $conn->prepare("SELECT item_name, quantity FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stockResult = $stmt->get_result();
        $stock = $stockResult->fetch_assoc();
        $stmt->close();

        if ($stock && $stock['quantity'] >= $quantity && $quantity > 0) {
            // Check for duplicate pending request
            $check = $conn->prepare("SELECT id FROM requests WHERE employee_id = ? AND item_id = ? AND status = 'pending'");
            $check->bind_param("ii", $employee_id, $item_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                $insert = $conn->prepare("INSERT INTO requests (employee_id, item_id, quantity) VALUES (?, ?, ?)");
                $insert->bind_param("iii", $employee_id, $item_id, $quantity);
                $insert->execute();
                $insert->close();
            } else {
                $itemName = htmlspecialchars($stock['item_name']);
                $message .= "🟠 <strong>$itemName</strong> already requested and is pending approval.<br>";
            }

            $check->close();
        } else {
            $itemName = isset($stock['item_name']) ? htmlspecialchars($stock['item_name']) : "Item ID $item_id";
            $message .= "🔴 <strong>$itemName</strong> has insufficient stock or invalid quantity.<br>";
        }
    }

    $_SESSION['success_msg'] = $message === "" ? "✅ All items requested successfully!" : "⚠️ Some items could not be requested:<br>$message";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Email configuration
$employeeEmail = $employee_email; // Get this from employee session or form
$adminEmail = "admin@example.com"; // this should be Replaced with actual admin email

$subject = "New Stationery Request Submitted";
$message = "Dear User,\n\nA new stationery request has been submitted by $employee_name (Employee ID: $employee_id).\n\nPlease login to the system to view the details.\n\nThank you.";

$headers = "From: no-reply@yourdomain.com\r\n";
$headers .= "Reply-To: no-reply@yourdomain.com\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email to employee
mail($employeeEmail, $subject, $message, $headers);

// Send email to admin
mail($adminEmail, $subject, $message, $headers);


// another code 

// Fetch available items
$result = $conn->prepare("SELECT id, item_name, quantity FROM inventory WHERE quantity > 0");
$result->execute();
$items = $result->get_result()->fetch_all(MYSQLI_ASSOC);
$result->close();

$query = "
    SELECT 
        i.id, 
        i.item_name, 
        i.quantity AS total_quantity,
        COALESCE(SUM(r.approved_quantity), 0) AS total_approved,
        (i.quantity - COALESCE(SUM(r.approved_quantity), 0)) AS available_quantity
    FROM inventory i
    LEFT JOIN requests r ON i.id = r.item_id AND r.status = 'approved'
    GROUP BY i.id
";
$result = $conn->query($query);

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Request Stationery</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      color: #333;
    }

    .navbar {
      background: #2f4050;
      color: white;
      padding: 15px 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 1rem;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .navbar a {
      color: #fff;
      text-decoration: none;
      margin-left: 15px;
    }

    .sidebar {
      width: 220px;
      background: #2f4050;
      color: white;
      position: fixed;
      top: 60px;
      bottom: 0;
      padding-top: 20px;
      overflow-y: auto;
    }

    .sidebar a {
      display: block;
      color: white;
      padding: 12px 20px;
      text-decoration: none;
      font-size: 0.95rem;
    }

    .sidebar a:hover {
      background: #1c2733;
    }

    .content {
      margin-left: 240px;
      padding: 30px;
    }

    .main-container {
      display: flex;
      gap: 25px;
      flex-wrap: wrap;
    }

    .form-container, .stock-container {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      flex: 1;
      min-width: 350px;
    }

    h2 {
      margin-top: 0;
      color: #2f4050;
      font-size: 1.3rem;
    }

    .message {
      background: #e0f7da;
      border-left: 5px solid #2e7d32;
      color: #2e7d32;
      padding: 10px 15px;
      margin-bottom: 15px;
      border-radius: 5px;
    }

    #totalSelected {
      font-weight: bold;
      color: #333;
      margin: 15px 0;
    }

    .item-row {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-bottom: 10px;
    }

    .item-row input[type=number] {
      flex: 1;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .item-row span {
      flex: 2;
    }

    .item-row button {
      background: #ff4d4d;
      border: none;
      color: white;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }

    button[type="submit"] {
      margin-top: 20px;
      padding: 12px;
      background-color: #1c84c6;
      color: white;
      border: none;
      border-radius: 5px;
      width: 100%;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
    }

    .select-btn {
      background-color: #28a745;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .select-btn:disabled {
      background-color: #999;
      cursor: not-allowed;
    }

    #searchInput {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    .inventory-scroll {
      max-height: 500px;
      overflow-y: auto;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    .inventory-scroll table {
      width: 100%;
      border-collapse: collapse;
    }

    .inventory-scroll th,
    .inventory-scroll td {
      padding: 12px;
      border-bottom: 1px solid #eee;
      text-align: left;
      font-size: 0.95rem;
    }

    .inventory-scroll th {
      background-color: #f4f4f4;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .content {
        margin-left: 0;
      }

      .main-container {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

<div class="navbar">
  <div><i class="fas fa-box-open"></i> &nbsp;Request Stationery</div>
  <div>
    👤 ID: <?= $_SESSION['user_id'] ?>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="sidebar">
  <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
  <a href="request_stationery.php"><i class="fas fa-box"></i> Request Stationery</a>
  <a href="request_history.php"><i class="fas fa-clock-rotate-left"></i> Request History</a>
</div>

<div class="content">
  <div class="main-container">

    <!-- ================= Form: Selected Items ================== -->
    <div class="form-container">
      <h2><i class="fas fa-list-check"></i> Selected Items</h2>

      <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="message"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
      <?php endif; ?>

      <form method="POST" id="requestForm">
        <p id="totalSelected">🧾 Total Items Selected: 0</p>
        <div id="selectedItems"></div>
        <button type="submit" onclick="return confirm('Submit selected items?')">Submit Request</button>
      </form>
    </div>

    <!-- ================= Inventory Table ================== -->
    <div class="stock-container">
  <h2><i class="fas fa-boxes-stacked"></i> Available Inventory</h2>
  <input type="text" id="searchInput" placeholder="🔍 Search items..." />

  <div class="inventory-scroll">
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Available Qty</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['item_name']) ?></td>
            <td><?= max(0, (int)$item['available_quantity']) ?></td>
            <td>
              <button class="select-btn"
                      type="button"
                      data-id="<?= $item['id'] ?>"
                      data-name="<?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?>"
                      data-qty="<?= max(0, (int)$item['available_quantity']) ?>">
                Select
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>


  </div>
</div>

<script>
  let selectedItems = {};
  const selectedContainer = document.getElementById('selectedItems');
  const totalDisplay = document.getElementById('totalSelected');

  document.querySelectorAll('.select-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      const name = this.dataset.name;
      const available = parseInt(this.dataset.qty, 10);
      if (selectedItems[id]) return;
      addItem(id, name, available, this);
    });
  });

  function addItem(id, name, available, button) {
    const div = document.createElement('div');
    div.className = 'item-row';
    div.dataset.id = id;
    div.innerHTML = `
      <input type="hidden" name="items[${id}][item_id]" value="${id}">
      <span>${name} (Available: ${available})</span>
      <input type="number" name="items[${id}][quantity]" min="1" max="${available}" required value="1"
             oninput="validateQuantity(this, ${available})">
      <button type="button" class="remove-btn">Remove</button>
    `;
    selectedContainer.appendChild(div);
    selectedItems[id] = true;
    button.disabled = true;
    button.innerText = "Selected";
    updateTotalCount();

    div.querySelector('.remove-btn').addEventListener('click', () => {
      delete selectedItems[id];
      div.remove();
      button.disabled = false;
      button.innerText = "Select";
      updateTotalCount();
    });
  }

  function validateQuantity(input, maxQty) {
    let val = parseInt(input.value, 10);
    if (isNaN(val) || val < 1) val = 1;
    else if (val > maxQty) {
      alert("Quantity exceeds stock!");
      val = maxQty;
    }
    input.value = val;
  }

  function updateTotalCount() {
    totalDisplay.innerText = `🧾 Total Items Selected: ${Object.keys(selectedItems).length}`;
  }

  document.getElementById('searchInput').addEventListener('input', function () {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('table tbody tr').forEach(row => {
      const name = row.cells[0].innerText.toLowerCase();
      row.style.display = name.includes(filter) ? '' : 'none';
    });
  });
</script>
</body>
</html>
