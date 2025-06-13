<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
  <div class="sidebar-header">
    <h2>IMS Admin</h2>
  </div>
  <ul class="sidebar-menu">
    <li class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
      <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    </li>
    <li class="<?= $currentPage == 'add_inventory.php' ? 'active' : '' ?>">
      <a href="add_inventory.php"><i class="fas fa-boxes"></i> Add Inventory</a>
    </li>
    <li class="<?= $currentPage == 'manage_requests.php' ? 'active' : '' ?>">
      <a href="requests.php"><i class="fas fa-tasks"></i> Manage Requests</a>
    </li>
    <li class="<?= $currentPage == 'quantity_update.php' ? 'active' : '' ?>">
      <a href="quantity_update.php"><i class="fas fa-tasks"></i> quantity update</a>
    </li>
    <li class="<?= $currentPage == 'report.php' ? 'active' : '' ?>">
      <a href="../report/report.php"><i class="fas fa-tasks"></i> Report</a>
    </li>
    <li class="<?= $currentPage == 'users.php' ? 'active' : '' ?>">
      <a href="../user/user_registration.php"><i class="fas fa-users"></i> Users</a>
    </li>
    <li>
      <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </li>
  </ul>
</aside>
