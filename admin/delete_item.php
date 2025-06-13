<?php
include '../init.php';
include '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: inventory.php");
    exit;
}

$id = (int)$_GET['id'];

$sql = "DELETE FROM inventory WHERE id=$id";

if ($conn->query($sql)) {
    header("Location: inventory.php?msg=deleted");
} else {
    header("Location: inventory.php?msg=error");
}
exit;
