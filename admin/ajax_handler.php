<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// CSRF protection
if (!isset($_SESSION['csrf_token']) || !isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$request_id = intval($data['request_id'] ?? 0);
$action = $data['action'] ?? '';

if ($request_id <= 0 || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE requests SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request approved.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $stmt->close();
    exit;
} elseif ($action === 'deny') {
    $stmt = $conn->prepare("UPDATE requests SET status = 'denied' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request denied.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $stmt->close();
    exit;
} elseif ($action === 'update_quantity') {
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : null;

    if ($quantity === null || $quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be zero or greater.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE requests SET approved_quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $request_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Approved quantity updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $stmt->close();
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}
