<?php
session_start();
include '../config/db.php';
include '../sidebar.php';

$inventoryItems = [];
$result = $conn->query("
    SELECT 
        i.id, 
        i.item_name, 
        i.quantity, 
        COALESCE(SUM(r.approved_quantity), 0) AS approved_qty
    FROM inventory i
    LEFT JOIN requests r ON r.item_id = i.id AND r.status = 'approved'
    GROUP BY i.id
");

while ($row = $result->fetch_assoc()) {
    $inventoryItems[] = $row;
}

$latestLogs = [];
$logResult = $conn->query("SELECT log.*, inv.item_name FROM inventory_quantity_log log JOIN inventory inv ON log.inventory_id = inv.id ORDER BY log.changed_at DESC LIMIT 5");
while ($row = $logResult->fetch_assoc()) {
    $latestLogs[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inventory_id'])) {
    $inventory_id = (int) $_POST['inventory_id'];
    $input_quantity = (int) $_POST['new_quantity'];
    $update_type = $_POST['update_type'];
    $changed_by = $_SESSION['username'] ?? 'Admin';

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $inventory_id);
        $stmt->execute();
        $stmt->bind_result($old_quantity);
        if (!$stmt->fetch()) throw new Exception("Item not found.");
        $stmt->close();

        $final_quantity = ($update_type === 'replace') ? $input_quantity : $old_quantity + $input_quantity;

        $update_stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $final_quantity, $inventory_id);
        $update_stmt->execute();
        $update_stmt->close();

        $log_stmt = $conn->prepare("INSERT INTO inventory_quantity_log (inventory_id, old_quantity, new_quantity, changed_by, changed_at) VALUES (?, ?, ?, ?, NOW())");
        $log_stmt->bind_param("iiis", $inventory_id, $old_quantity, $final_quantity, $changed_by);
        $log_stmt->execute();
        $log_stmt->close();

        $conn->commit();
        $_SESSION['successMessage'] = "Quantity updated successfully.";
        header("Location: quantity_update.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "Error: " . $e->getMessage();
    }
}

$latestLogs = [];
$logSql = "
    SELECT 
        log.*, 
        inv.item_name, 
        inv.quantity, 
        COALESCE(SUM(r.approved_quantity), 0) AS approved_qty
    FROM inventory_quantity_log log
    JOIN inventory inv ON log.inventory_id = inv.id
    LEFT JOIN requests r ON r.item_id = inv.id AND r.status = 'approved'
    GROUP BY log.id
    ORDER BY log.changed_at DESC
    LIMIT 5
";
$logResult = $conn->query($logSql);
while ($row = $logResult->fetch_assoc()) {
    $latestLogs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Quantity Update</title>
    <link rel="stylesheet" href="../admin/admin-style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .inventory-update-container {
            width: 700px;
            margin: 40px auto;
        }
        .card-custom {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }
        h3, h4 {
            font-weight: 600;
            color: #2c3e50;
        }
        label.form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }
        #preview {
            color: #6c757d;
            font-size: 0.95rem;
            font-style: italic;
        }
        .log-entry {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        .select2-container .select2-results__options {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>

</head>
<body>
<div class="inventory-update-container">
    <div class="card-custom mb-4">
        <h3 class="text-center mb-4">Inventory Quantity Update</h3>

        <?php if (isset($_SESSION['successMessage'])): ?>
            <div class="alert alert-success text-center fw-semibold"><?php echo $_SESSION['successMessage']; unset($_SESSION['successMessage']); ?></div>
        <?php elseif (isset($errorMessage)): ?>
            <div class="alert alert-danger text-center fw-semibold"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
        <div class="mb-3">
    <label class="form-label">Select Item</label>
    <select class="form-select" name="inventory_id" id="inventory_id" required onchange="updatePreview()">
        <option value="">-- Search by Name or ID --</option>
        <?php foreach ($inventoryItems as $item): ?>
            <?php
                $total = (int)$item['quantity'];
                $approved = (int)$item['approved_qty']; // Ensure this is fetched from DB
                $available = max(0, $total - $approved);
            ?>
            <option value="<?= $item['id'] ?>">
                <?= $item['id'] . ' - ' . htmlspecialchars($item['item_name']) . " (Available: {$available})" ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control" name="new_quantity" min="0" required oninput="updatePreview()">
            </div>

            <div class="mb-3">
                <label class="form-label">Update Type</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="update_type" value="add" checked onchange="updatePreview()">
                    <label class="form-check-label">Add to Existing</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="update_type" value="replace" onchange="updatePreview()">
                    <label class="form-check-label">Replace</label>
                </div>
            </div>

            <div id="preview" class="mb-3"></div>

            <button type="submit" class="btn btn-primary w-100">Update Quantity</button>
        </form>
    </div>

    <div class="card card-custom shadow-sm rounded-lg p-3 mb-4">
    <h4 class="mb-3 text-primary">📌 Recent Inventory Updates</h4>

    <?php if (!empty($latestLogs)): ?>
        <ul class="list-unstyled">
            <?php foreach ($latestLogs as $log): ?>
                <?php
                    $added_quantity = $log['new_quantity'] - $log['old_quantity'];
                    $added_quantity_display = $added_quantity > 0 ? "+$added_quantity" : $added_quantity;
                    $available_after_update = $log['quantity'] - $log['approved_qty'];
                ?>
                <li class="mb-3 border-bottom pb-2">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($log['item_name']) ?></div>
                    <small class="text-muted">
                        <?= date('d M Y, h:i A', strtotime($log['changed_at'])) ?> —
                        Updated by <strong><?= htmlspecialchars($log['changed_by']) ?></strong>
                    </small><br>
                    <span class="text-secondary d-block">
                        Quantity changed:
                        <span class="text-danger"><?= $log['old_quantity'] ?></span> →
                        <span class="text-success fw-semibold"><?= $log['new_quantity'] ?></span>
                        <span class="ms-2 text-muted">(Added: <?= $added_quantity_display ?>)</span>
                    </span>
                    <div class="text-muted small">
                        Available after update: <strong><?= $available_after_update ?></strong>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="text-muted">No recent updates available.</div>
    <?php endif; ?>
</div>


</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#inventory_id').select2({
        theme: 'bootstrap-5',
        minimumInputLength: 1,
        matcher: function(params, data) {
            if ($.trim(params.term) === '') return data;
            if (typeof data.text === 'undefined') return null;
            const term = params.term.toLowerCase();
            const text = data.text.toLowerCase();
            if (text.includes(term)) return data;
            return null;
        },
        width: '100%'
    }).on('select2:select', function (e) {
        updatePreview();
    });

    $('#inventory_id').on('select2:open', function() {
        const results = document.querySelectorAll('.select2-results__option[aria-selected]');
        if (results.length === 1) {
            results[0].click();
        }
    });
});
    function updatePreview() {
        const select = document.querySelector('[name=inventory_id]');
        const qty = parseInt(document.querySelector('[name=new_quantity]').value || 0);
        const type = document.querySelector('[name=update_type]:checked').value;
        const selected = select.options[select.selectedIndex];

        // Match "Available: 45" from option text
        const match = selected.textContent.match(/Available:\s*(\d+)/);
        if (match) {
            const currentAvailable = parseInt(match[1]);
            const final = type === 'replace' ? qty : currentAvailable + qty;
            document.getElementById('preview').textContent = `Preview: Available ${currentAvailable} → ${final}`;
        } else {
            document.getElementById('preview').textContent = '';
        }
    }

</script>
</body>
</html>
