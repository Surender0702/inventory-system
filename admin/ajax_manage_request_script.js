// General AJAX handler for approve, deny, and quantity update
function handleRequest(action, requestId, quantity = null) {
    const data = {
        csrf_token: document.querySelector('input[name="csrf_token"]').value,
        request_id: requestId
    };

    if (action) data.action = action;
    if (quantity !== null) data.quantity = quantity;

    fetch('manage_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    })
    .then(res => res.json())
    .then(response => {
        alert(response.message);
        if (response.success) location.reload(); // Optionally update DOM without reload
    });
}

// Hook up approve and deny buttons
document.querySelectorAll('.btn-approve[data-id], .btn-deny[data-id]').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const action = this.classList.contains('btn-approve') ? 'approve' : 'deny';
        handleRequest(action, id);
    });
});

// Hook up quantity update forms
document.querySelectorAll('.quantity-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = this.querySelector('input[name="request_id"]').value;
        const qty = this.querySelector('input[name="quantity"]').value;
        handleRequest(null, id, qty);
    });
});