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

      // Fetch stock using prepared statement
      $stmt = $conn->prepare("SELECT item_name, quantity FROM inventory WHERE id = ?");
      $stmt->bind_param("i", $item_id);
      $stmt->execute();
      $stockResult = $stmt->get_result();
      $stock = $stockResult->fetch_assoc();
      $stmt->close();

      if ($stock && $stock['quantity'] >= $quantity && $quantity > 0) {
          // Check if there's already a pending request
          $check = $conn->prepare("SELECT id FROM requests WHERE employee_id = ? AND item_id = ? AND status = 'pending'");
          $check->bind_param("ii", $employee_id, $item_id);
          $check->execute();
          $check->store_result();

          if ($check->num_rows === 0) {
              // No pending request, insert new one
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

  // Set session message
  if ($message === "") {
      $_SESSION['success_msg'] = "✅ All items requested successfully!";
  } else {
      $_SESSION['success_msg'] = "⚠️ Some items could not be requested:<br>$message";
  }

  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}


// Fetch inventory
$result = $conn->prepare("SELECT id, item_name, quantity FROM inventory WHERE quantity > 0");
$result->execute();
$items = $result->get_result()->fetch_all(MYSQLI_ASSOC);
$result->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Inventory Request</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f6f9; }
    .navbar {
      background: #2f4050; color: white; padding: 10px 20px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .navbar .title { font-size: 1.3rem; font-weight: bold; }
    .navbar .user-info { font-size: 0.9rem; }
    .sidebar {
      width: 220px; background: #2f4050; color: white;
      position: fixed; top: 50px; bottom: 0; padding-top: 20px;
    }
    .sidebar a {
      display: block; color: white; padding: 10px 20px;
      text-decoration: none; transition: background 0.2s;
    }
    .sidebar a:hover { background: #1c2733; }
    .content {
      margin-left: 220px; padding: 20px;
    }
    .main-container {
      display: flex; gap: 20px;
    }
    .form-container, .stock-container {
      background: white; padding: 20px; border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1); flex: 1;
    }
    .form-container h2, .stock-container h3 {
      text-align: center; margin-bottom: 20px;
    }
    .message {
      padding: 10px; background: #e3fcef; color: #1b5e20;
      border-left: 5px solid #388e3c; margin-bottom: 10px;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td {
      padding: 8px; border: 1px solid #ddd; text-align: center;
    }
    .item-row {
      display: flex; gap: 10px; align-items: center; margin-bottom: 10px;
    }
    .item-row input[type=number] {
      flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;
    }
    .item-row span { flex: 2; }
    .item-row button {
      background: #ff4d4d; border: none; color: white;
      padding: 5px 10px; border-radius: 4px; cursor: pointer;
    }
    button[type="submit"] {
      padding: 10px; background-color: #1c84c6;
      color: white; border: none; border-radius: 4px; font-weight: bold;
      width: 100%; margin-top: 10px;
    }
    .select-btn {
      background-color: #28a745; color: white;
      border: none; padding: 5px 10px; border-radius: 4px;
      cursor: pointer;
    }
    .select-btn:disabled { background-color: #999; cursor: not-allowed; }
    #searchInput {
      width: 100%; padding: 8px; margin-bottom: 10px;
      border-radius: 4px; border: 1px solid #ccc;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <div class="title">Inventory Request System</div>
  <div class="user-info">👤 Employee ID: <?= $_SESSION['user_id'] ?> | <a href="../auth/logout.php" style="color: #f2f2f2;">Logout</a></div>
</div>

<!-- Sidebar -->
<div class="sidebar">
  <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
  <a href="request_stationery.php"><i class="fas fa-boxes"></i> Request Items</a>
  <a href="request_history.php"><i class="fas fa-history"></i> Request History</a>
</div>

<!-- Main Content -->
<div class="content">
  <div class="main-container">
    <!-- Left: Request Form -->
    <div class="form-container">
      <h2>Selected Items to Request</h2>

      <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="message"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
      <?php endif; ?>

      <form method="POST" id="requestForm">
        <div id="selectedItems"></div>
        <button type="submit" onclick="return confirm('Are you sure you want to submit this request?')">Submit Request</button>

      </form>
    </div>

    <!-- Right: Available Inventory -->
    <div class="stock-container">
      <h3>Available Inventory</h3>
      <input type="text" id="searchInput" placeholder="Search items..." />
      <table id="inventoryTable">
        <thead>
          <tr><th>Item</th><th>Qty</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['item_name']) ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td>
                <button type="button" class="select-btn"
                  data-id="<?= $item['id'] ?>"
                  data-name="<?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?>"
                  data-qty="<?= (int)$item['quantity'] ?>">
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

<script>
  let selectedItems = {};
  const selectedContainer = document.getElementById('selectedItems');
  const form = document.getElementById('requestForm');

  const totalDisplay = document.createElement('p');
  totalDisplay.id = 'totalSelected';
  totalDisplay.style.fontWeight = 'bold';
  form.prepend(totalDisplay);
  updateTotalCount();

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
      <input type="number" name="items[${id}][quantity]" min="1" max="${available}" required oninput="validateQuantity(this, ${available})" value="1">
      <button type="button" class="remove-btn">Remove</button>
    `;
    selectedContainer.appendChild(div);
    selectedItems[id] = true;
    button.disabled = true;
    button.innerText = "Selected";
    updateTotalCount();

    div.querySelector('.remove-btn').addEventListener('click', () => {
      removeItem(id, button, div);
    });
  }

  function removeItem(id, selectButton, itemRow) {
    delete selectedItems[id];
    itemRow.remove();
    selectButton.disabled = false;
    selectButton.innerText = "Select";
    updateTotalCount();
  }

  function validateQuantity(input, maxQty) {
    let val = parseInt(input.value, 10);
    if (isNaN(val) || val < 1) {
      val = 1;
    } else if (val > maxQty) {
      alert("Quantity exceeds available stock!");
      val = maxQty;
    }
    input.value = val;
  }

  function updateTotalCount() {
    const count = Object.keys(selectedItems).length;
    totalDisplay.innerText = `🧾 Total Items Selected: ${count}`;
  }

  document.getElementById('searchInput').addEventListener('input', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
    rows.forEach(row => {
      const itemName = row.cells[0].innerText.toLowerCase();
      row.style.display = itemName.includes(filter) ? '' : 'none';
    });
  });
</script>

</body>
</html>
