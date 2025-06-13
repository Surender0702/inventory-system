<?php
include '../init.php';
include '../config/db.php';

// Handle Add Item form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $quantity = (int)$_POST['quantity'];

    if ($item_name && $quantity >= 0) {
        $sql = "INSERT INTO inventory (item_name, description, quantity) VALUES ('$item_name', '$description', $quantity)";
        if ($conn->query($sql)) {
            $message = "Item added successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    } else {
        $message = "Please enter a valid item name and quantity.";
    }
}

// Fetch all items
$result = $conn->query("SELECT * FROM inventory ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Inventory Management - Admin</title>
<style>

  main {
    max-width: 960px;
    margin: 40px auto;
    padding: 0 20px 40px;
  }
  h2 {
    color: #283e51;
    margin-bottom: 20px;
  }
  .message {
    padding: 10px 15px;
    background: #daf5da;
    color: #2f6627;
    border-radius: 8px;
    margin-bottom: 20px;
  }
  form {
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    margin-bottom: 40px;
  }
  form input, form textarea {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 15px;
    border: 1.8px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }
  form input:focus, form textarea:focus {
    border-color: #4b79a1;
    outline: none;
  }
  form button {
    background: #4b79a1;
    color: white;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 1.1rem;
    transition: background 0.3s ease;
  }
  form button:hover {
    background: #3a6186;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
  }
  table th, table td {
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
    text-align: left;
  }
  table th {
    background: #4b79a1;
    color: white;
    font-weight: 600;
  }
  table tr:last-child td {
    border-bottom: none;
  }
  .action-buttons a {
    margin-right: 10px;
    text-decoration: none;
    color: #4b79a1;
    font-weight: 600;
  }
  .action-buttons a:hover {
    text-decoration: underline;
  }
</style>
<link rel="stylesheet" href="../style.css">
</head>
<body>

<header>
  <h1>Inventory Management</h1>
  <nav>
    <a href="admin.php">Dashboard</a>
    <a href="../auth/logout.php">Logout</a>
  </nav>
</header>

<!-- Main content layout with sidebar -->
<div style="display: flex; min-height: calc(100vh - 80px);"> <!-- Flex container -->

  <!-- Sidebar -->
  <?php include '../sidebar.php'; ?>
 
  <!-- Main content -->
  <main style="flex: 1; padding: 30px;">
    <h2>Add New Item</h2>
    <?php if (!empty($message)): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <input type="text" name="item_name" placeholder="Item Name" required />
      <textarea name="description" rows="3" placeholder="Description (optional)"></textarea>
      <input type="number" name="quantity" placeholder="Quantity" min="0" required />
      <button type="submit" name="add_item">Add Item</button>
    </form>

    <h2>Current Inventory</h2>

    <!-- Filter checkboxes -->
    <div style="margin-bottom: 20px;">
      <label><input type="checkbox" class="filter-quantity" value="red" checked> Less than 10 (Red)</label> &nbsp;&nbsp;
      <label><input type="checkbox" class="filter-quantity" value="yellow" checked> 10 to 20 (Yellow)</label> &nbsp;&nbsp;
      <label><input type="checkbox" class="filter-quantity" value="green" checked> More than 20 (Green)</label>
    </div>

    <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%;">
      <thead>
        <tr style="background-color: #f2f2f2;">
          <th>Item Name</th>
          <th>Description</th>
          <th>Quantity</th>
          <th>Added On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($item = $result->fetch_assoc()): ?>
            <?php
              $qty = (int)$item['quantity'];
              if ($qty < 10) {
                $rowClass = 'red';
              } elseif ($qty >= 10 && $qty <= 20) {
                $rowClass = 'yellow';
              } else {
                $rowClass = 'green';
              }
            ?>
            <tr class="item-row <?= $rowClass ?>">
              <td><?= htmlspecialchars($item['item_name']) ?></td>
              <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
              <td><?= $qty ?></td>
              <td><?= date('d M Y', strtotime($item['created_at'])) ?></td>
              <td class="action-buttons">
                <a href="edit_item.php?id=<?= $item['id'] ?>">Edit</a> |
                <a href="delete_item.php?id=<?= $item['id'] ?>" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center; color:#999;">No items found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Add CSS for row colors -->
    <style>
      .red { background-color: #f8d7da; }
      .yellow { background-color: #fff3cd; }
      .green { background-color: #d4edda; }
    </style>

    <!-- JS filter script -->
    <script>
      const checkboxes = document.querySelectorAll('.filter-quantity');
      const rows = document.querySelectorAll('.item-row');

      function filterRows() {
        const checkedColors = Array.from(checkboxes)
          .filter(chk => chk.checked)
          .map(chk => chk.value);

        rows.forEach(row => {
          const colorClass = Array.from(row.classList).find(cls => ['red','yellow','green'].includes(cls));
          if (checkedColors.includes(colorClass)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }

      checkboxes.forEach(chk => chk.addEventListener('change', filterRows));
      filterRows();
    </script>

  </main>
</div>

</body>
</html>