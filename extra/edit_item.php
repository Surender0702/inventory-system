<?php
include '../init.php';
include '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: inventory.php");
    exit;
}

$id = (int)$_GET['id'];
$message = "";

// Fetch current item data
$sql = "SELECT * FROM inventory WHERE id=$id";
$res = $conn->query($sql);
if ($res->num_rows !== 1) {
    header("Location: inventory.php");
    exit;
}
$item = $res->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $quantity = (int)$_POST['quantity'];

    if ($item_name && $quantity >= 0) {
        $sql = "UPDATE inventory SET item_name='$item_name', description='$description', quantity=$quantity WHERE id=$id";
        if ($conn->query($sql)) {
            $message = "Item updated successfully!";
            // Refresh item data
            $res = $conn->query("SELECT * FROM inventory WHERE id=$id");
            $item = $res->fetch_assoc();
        } else {
            $message = "Error updating item: " . $conn->error;
        }
    } else {
        $message = "Please enter a valid item name and quantity.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit Inventory Item</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f7fa;
    margin: 0;
  }
  header {
    background: #4b79a1;
    color: #fff;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  }
  header h1 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
  }
  header nav a {
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    margin-left: 20px;
    transition: color 0.3s ease;
  }
  header nav a:hover {
    color: #cbd5e1;
  }
  main {
    max-width: 600px;
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
</style>
</head>
<body>

<header>
  <h1>Edit Item</h1>
  <nav>
    <a href="inventory.php">Back to Inventory</a>
    <a href="../auth/logout.php">Logout</a>
  </nav>
</header>

<main>
  <h2>Edit Inventory Item</h2>
  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <form method="POST" action="">
    <input type="text" name="item_name" placeholder="Item Name" value="<?= htmlspecialchars($item['item_name']) ?>" required />
    <textarea name="description" rows="3" placeholder="Description (optional)"><?= htmlspecialchars($item['description']) ?></textarea>
    <input type="number" name="quantity" placeholder="Quantity" min="0" value="<?= (int)$item['quantity'] ?>" required />
    <button type="submit">Update Item</button>
  </form>
</main>

</body>
</html>
