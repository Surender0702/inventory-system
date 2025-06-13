<?php
session_start();
include '../config/db.php';

// Redirect if not admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: add_inventory.php");
    exit;
}
$id = (int)$_GET['id'];

// Fetch existing item
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Item not found.";
    header("Location: add_inventory.php");
    exit;
}
$item = $result->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $quantity = (int)$_POST['quantity'];

    $image_path = $item['image_path']; // keep old image by default
    if (!empty($_FILES['item_image']['name'])) {
        $image_name = time() . '_' . basename($_FILES['item_image']['name']);
        $upload_path = '../uploads/' . $image_name;
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_path)) {
            $image_path = $image_name;
        }
    }

    $stmt = $conn->prepare("UPDATE inventory SET item_name = ?, description = ?, quantity = ?, image_path = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $item_name, $description, $quantity, $image_path, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "Item updated successfully!";
    header("Location: add_inventory.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../admin/admin-style.css">
  <style>
    body { background-color: #f8f9fa; }
    .content-wrapper { margin-left: 250px; padding: 20px; }
    .form-card { background: #fff; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.05); padding: 20px; }
    img.item-img { width: 60px; height: auto; }
  </style>
</head>
<body>

<?php include '../header.php'; ?>
<?php include '../sidebar.php'; ?>

<div class="content-wrapper">
  <h3>Edit Inventory Item</h3>

  <div class="form-card">
    <form method="POST" enctype="multipart/form-data" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Item Name</label>
        <input type="text" name="item_name" class="form-control" required value="<?= htmlspecialchars($item['item_name']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" required min="1" value="<?= $item['quantity'] ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Current Image</label><br>
        <?php if ($item['image_path']): ?>
          <img src="../uploads/<?= $item['image_path'] ?>" class="item-img">
        <?php else: ?> N/A <?php endif; ?>
      </div>
      <div class="col-md-3">
        <label class="form-label">Upload New Image</label>
        <input type="file" name="item_image" class="form-control">
      </div>
      <div class="col-md-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($item['description']) ?></textarea>
      </div>
      <div class="col-12 d-flex justify-content-between">
        <a href="add_inventory.php" class="btn btn-secondary">Back</a>
        <button type="submit" name="update_item" class="btn btn-primary">Update Item</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
