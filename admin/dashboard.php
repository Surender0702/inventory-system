<?php
session_start();
include '../config/db.php';
include '../header.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Dashboard Stats
$total_items        = $conn->query("SELECT COUNT(*) FROM inventory")->fetch_row()[0];
$total_requests     = $conn->query("SELECT COUNT(*) FROM requests")->fetch_row()[0];
$pending_requests   = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetch_row()[0];
$approved_requests  = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'approved'")->fetch_row()[0];
$denied_requests    = $conn->query("SELECT COUNT(*) FROM requests WHERE status = 'denied'")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Dashboard CSS -->
  <link rel="stylesheet" href="../admin/admin-style.css">
</head>
<body>

<div class="container">
  <!-- Sidebar -->
  <?php include '../sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <h2>Admin Dashboard</h2>

    <div class="dashboard-grid">
      <div class="card card-blue">
        <h3>Total Items</h3>
        <p><?= $total_items ?></p>
      </div>
      <div class="card card-orange">
        <h3>Total Requests</h3>
        <p><?= $total_requests ?></p>
      </div>
      <div class="card card-yellow">
        <h3>Pending Requests</h3>
        <p><?= $pending_requests ?></p>
      </div>
      <div class="card card-green">
        <h3>Approved</h3>
        <p><?= $approved_requests ?></p>
      </div>
      <div class="card card-red">
        <h3>Denied</h3>
        <p><?= $denied_requests ?></p>
      </div>
    </div>
  </main>
</div>

<!-- Dashboard Specific JS -->
<script src="../admin/script.js"></script>
</body>
</html>
