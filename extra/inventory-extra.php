<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Delete item if requested
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM inventory WHERE id = $id");
    header("Location: inventory.php");
    exit;
}

// Update item if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $id = (int)$_POST['item_id'];
    $item_name = trim($_POST['item_name']);
    $quantity = (int)$_POST['quantity'];

    $stmt = $conn->prepare("UPDATE inventory SET item_name = ?, quantity = ? WHERE id = ?");
    $stmt->bind_param("sii", $item_name, $quantity, $id);
    $stmt->execute();

    header("Location: inventory.php");
    exit;
}

// Fetch all inventory
$items = $conn->query("SELECT * FROM inventory ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Inventory</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    h2 { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 30px; background: white; }
    th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
    th { background-color: #f2f2f2; }

    .btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      color: white;
    }

    .edit { background: #3498db; }
    .delete { background: #e74c3c; }
    .save { background: #2ecc71; }

    form.inline-form input {
      padding: 5px;
      width: 100px;
    }
  </style>
</head>
<body>

<h2>Inventory Management</h2>

<table>
  <tr>
    <th>ID</th>
    <th>Item Name</th>
    <th>Quantity</th>
    <th>Action</th>
  </tr>

  <?php while($item = $items->fetch_assoc()): ?>
    <?php if (isset($_GET['edit']) && $_GET['edit'] == $item['id']): ?>
      <!-- Edit Row -->
      <tr>
        <form method="POST" class="inline-form">
          <td><?= $item['id'] ?></td>
          <td>
            <input type="text" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required>
          </td>
          <td>
            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" required>
          </td>
          <td>
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <button type="submit" name="update_item" class="btn save">💾 Save</button>
            <a href="inventory.php" class="btn delete">✖ Cancel</a>
          </td>
        </form>
      </tr>
    <?php else: ?>
      <!-- Normal Row -->
      <tr>
        <td><?= $item['id'] ?></td>
        <td><?= htmlspecialchars($item['item_name']) ?></td>
        <td><?= $item['quantity'] ?></td>
        <td>
          <a href="inventory.php?edit=<?= $item['id'] ?>" class="btn edit">✏️ Edit</a>
          <a href="inventory.php?delete=<?= $item['id'] ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this item?');">🗑 Delete</a>
        </td>
      </tr>
    <?php endif; ?>
  <?php endwhile; ?>
</table>

</body>
</html>
