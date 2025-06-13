<?php
session_start();
include '../config/db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=inventory_export.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "ID\tItem Name\tQuantity\tDescription\tImage\n";

$result = $conn->query("SELECT * FROM inventory ORDER BY id DESC");

while ($row = $result->fetch_assoc()) {
    echo $row['id'] . "\t" .
         $row['item_name'] . "\t" .
         $row['quantity'] . "\t" .
         str_replace(["\r", "\n"], ' ', $row['description']) . "\t" .
         $row['image_path'] . "\n";
}
exit;
