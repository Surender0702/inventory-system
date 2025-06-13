<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit;
}

$employee_id = (int)$_SESSION['user_id'];

$total = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE employee_id = $employee_id")->fetch_assoc()['total'];
$pending = $conn->query("SELECT COUNT(*) AS pending FROM requests WHERE employee_id = $employee_id AND status = 'pending'")->fetch_assoc()['pending'];
$approved = $conn->query("SELECT COUNT(*) AS approved FROM requests WHERE employee_id = $employee_id AND status = 'approved'")->fetch_assoc()['approved'];

// Calendar Data
$calendarData = [];
$query = $conn->prepare("SELECT i.item_name, r.request_date, r.status 
                         FROM requests r 
                         JOIN inventory i ON r.item_id = i.id 
                         WHERE r.employee_id = ? 
                         ORDER BY r.request_date DESC 
                         LIMIT 50");
$query->bind_param("i", $employee_id);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $color = match (strtolower($row['status'])) {
        'approved' => '#28a745',
        'pending' => '#ffc107',
        'denied'  => '#dc3545',
        default   => '#6c757d'
    };
    $calendarData[] = [
        'title' => $row['item_name'] . ' (' . ucfirst($row['status']) . ')',
        'start' => date("Y-m-d", strtotime($row['request_date'])),
        'color' => $color
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" />
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #333; }

    .navbar {
      background: #2f4050; color: white; padding: 12px 24px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .navbar .title { font-size: 1.4rem; font-weight: bold; }
    .navbar a { color: #f2f2f2; text-decoration: none; }

    .sidebar {
      position: fixed; top: 50px; left: 0; bottom: 0;
      width: 220px; background: #2f4050; color: white; padding-top: 20px;
    }
    .sidebar a {
      display: block; padding: 12px 20px; color: white;
      text-decoration: none; transition: background 0.3s ease;
    }
    .sidebar a:hover { background: #1c2733; }

    .content {
      margin-left: 240px; padding: 30px;
    }

    .card-container {
      display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 40px;
    }
    .card {
      background: #fff; border-radius: 8px; padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;
      flex: 1; min-width: 240px;
      transition: transform 0.2s ease;
    }
    .card:hover { transform: translateY(-4px); }
    .card h3 { font-size: 2rem; margin: 15px 0 5px; }
    .card p { font-size: 1rem; color: #555; }
    .card i { font-size: 2.2rem; color: #2f4050; }

    #calendar {
      background: #fff; padding: 15px; border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto 40px;
    }

    .legend {
      text-align: center; margin: 20px 0;
    }
    .legend span {
      display: inline-block; margin: 0 10px; font-size: 0.95rem;
    }
    .legend span::before {
      content: "●"; margin-right: 6px; font-size: 1.2rem;
    }
    .legend .approved::before { color: #28a745; }
    .legend .pending::before { color: #ffc107; }
    .legend .denied::before  { color: #dc3545; }

  </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <div class="title"><i class="fas fa-chart-bar"></i> Employee Dashboard</div>
  <div class="user-info">👤 ID: <?= $_SESSION['user_id'] ?> | <a href="../auth/logout.php">Logout</a></div>
</div>

<!-- Sidebar -->
<div class="sidebar">
  <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
  <a href="request_stationery.php"><i class="fas fa-boxes"></i> Request Items</a>
  <a href="request_history.php"><i class="fas fa-history"></i> Request History</a>
</div>

<!-- Main Content -->
<div class="content">
  <h2>Welcome to Your Dashboard</h2>

  <!-- Request Summary Cards -->
  <div class="card-container">
    <div class="card">
      <i class="fas fa-file-alt"></i>
      <h3><?= $total ?></h3>
      <p>Total Requests</p>
    </div>
    <div class="card">
      <i class="fas fa-hourglass-half"></i>
      <h3><?= $pending ?></h3>
      <p>Pending Requests</p>
    </div>
    <div class="card">
      <i class="fas fa-check-circle"></i>
      <h3><?= $approved ?></h3>
      <p>Approved Requests</p>
    </div>
  </div>

  <!-- Request Calendar -->
  <h3>📅 My Request Calendar</h3>
  <div class="legend">
    <span class="approved">Approved</span>
    <span class="pending">Pending</span>
    <span class="denied">Denied</span>
  </div>

  <div id="calendar"></div>
</div>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: <?= json_encode($calendarData) ?>,
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listWeek'
      },
      height: 'auto'
    });
    calendar.render();
  });
</script>

</body>
</html>
