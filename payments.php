<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check login and permissions
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requirePermission('record_payments');

$action = $_GET['action'] ?? 'list';
$message = '';
$receiptPayment = null;

// Helper: format currency
function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}

// Fetch booking details by ID
function getBooking($id) {
    global $conn;
    $id = (int)$id;
    $sql = "SELECT b.*, g.first_name, g.last_name, r.room_number, r.room_type, r.price_per_night,
            (SELECT COALESCE(SUM(amount), 0) FROM payments p WHERE p.booking_id = b.id) AS paid_amount
            FROM bookings b
            JOIN guests g ON b.guest_id = g.id
            JOIN rooms r ON b.room_id = r.id
            WHERE b.id = $id";
    return $conn->query($sql)->fetch_assoc();
}

// Handle payment recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'record') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $method = sanitize($_POST['payment_method'] ?? 'cash');
    // Map new front-end option to stored DB value (enum still uses online_transfer)
    $methodForDb = $method === 'digital_bank' ? 'online_transfer' : $method;
    $transaction_id = sanitize($_POST['transaction_id'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    $booking = getBooking($booking_id);

    if (!$booking) {
        $message = '<div class="alert alert-danger">Booking not found.</div>';
    } elseif ($amount <= 0) {
        $message = '<div class="alert alert-danger">Please enter a valid payment amount.</div>';
    } else {
        $due = max(0, $booking['total_price'] - $booking['paid_amount']);
        if ($amount > $due) {
            $message = '<div class="alert alert-warning">Payment amount exceeds balance due. Please adjust.</div>';
        } else {
            // Insert payment record
            $sql = "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, notes) 
                    VALUES ($booking_id, $amount, '$methodForDb', '$transaction_id', '$notes')";
            if ($conn->query($sql)) {
                // Update booking payment status if fully paid
                $newPaidTotal = $booking['paid_amount'] + $amount;
                $newStatus = ($newPaidTotal >= $booking['total_price']) ? 'paid' : 'pending';
                $conn->query("UPDATE bookings SET payment_status = '$newStatus' WHERE id = $booking_id");

                // Fetch newly inserted payment for receipt
                $payment_id = $conn->insert_id;
                $receiptPayment = $conn->query("SELECT p.*, b.booking_number, g.first_name, g.last_name, r.room_number, r.room_type
                    FROM payments p
                    JOIN bookings b ON p.booking_id = b.id
                    JOIN guests g ON b.guest_id = g.id
                    JOIN rooms r ON b.room_id = r.id
                    WHERE p.id = $payment_id")->fetch_assoc();

                $message = '<div class="alert alert-success">Payment recorded successfully!</div>';
                // Refresh booking data to reflect the updated paid amount
                $selectedBooking = getBooking($booking_id);
                $action = 'pay';
            } else {
                $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
            }
        }
    }
}

// Load booking for payment form if requested
$selectedBooking = null;
if ($action === 'pay' && isset($_GET['id'])) {
    $selectedBooking = getBooking((int)$_GET['id']);
}

// Get bookings list with payment info
$bookingsQuery = "SELECT b.*, g.first_name, g.last_name, r.room_number, r.room_type,
    (SELECT COALESCE(SUM(amount), 0) FROM payments p WHERE p.booking_id = b.id) AS paid_amount
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC";
$bookings = $conn->query($bookingsQuery);

include 'views/header.php';
?>

<div class="mb-4">
    <h2><i class="fas fa-cash-register"></i> Payments</h2>
    <hr>
</div>

<?php echo $message; ?>

<?php if ($action === 'list'): ?>
    <div class="mb-3">
        <a href="payments.php?action=pay" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Record New Payment
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Booking Payments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Booking #</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($b = $bookings->fetch_assoc()):
                            $due = max(0, $b['total_price'] - $b['paid_amount']);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['booking_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['room_number'] . ' (' . ucfirst($b['room_type']) . ')'); ?></td>
                            <td><?php echo formatMoney($b['total_price']); ?></td>
                            <td><?php echo formatMoney($b['paid_amount']); ?></td>
                            <td><?php echo formatMoney($due); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($b['payment_status'] === 'paid' ? 'success' : ($b['payment_status'] === 'refunded' ? 'secondary' : 'warning')); ?>">
                                    <?php echo ucfirst($b['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($due > 0): ?>
                                    <a href="payments.php?action=pay&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No balance</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action === 'pay'): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo $selectedBooking ? 'Record Payment for Booking ' . htmlspecialchars($selectedBooking['booking_number']) : 'Record Payment'; ?></h5>
        </div>
        <div class="card-body">
            <?php if (!$selectedBooking): ?>
                <?php
                $pendingSql = "SELECT b.id, b.booking_number, g.first_name, g.last_name, r.room_number, r.room_type, b.total_price, 
                    (SELECT COALESCE(SUM(amount), 0) FROM payments p WHERE p.booking_id = b.id) AS paid_amount 
                    FROM bookings b 
                    JOIN guests g ON b.guest_id = g.id 
                    JOIN rooms r ON b.room_id = r.id 
                    ORDER BY b.created_at DESC";
                $pendingBookings = $conn->query($pendingSql);
                ?>

                <div class="alert alert-info">Select a booking from the list to record a payment.</div>
                <form method="GET" action="payments.php">
                    <input type="hidden" name="action" value="pay">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <select class="form-control" name="id" required>
                                <option value="">Select Booking</option>
                                <?php while ($pb = $pendingBookings->fetch_assoc()):
                                    $due = max(0, $pb['total_price'] - $pb['paid_amount']);
                                    if ($due <= 0) continue;
                                ?>
                                    <option value="<?php echo $pb['id']; ?>">
                                        <?php echo htmlspecialchars($pb['booking_number'] . ' — ' . $pb['first_name'] . ' ' . $pb['last_name'] . ' (Due: ' . formatMoney($due) . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-arrow-right"></i> Continue
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Guest</h6>
                        <p><?php echo htmlspecialchars($selectedBooking['first_name'] . ' ' . $selectedBooking['last_name']); ?></p>

                        <h6>Room</h6>
                        <p><?php echo htmlspecialchars($selectedBooking['room_number'] . ' (' . ucfirst($selectedBooking['room_type']) . ')'); ?></p>

                        <h6>Stay</h6>
                        <p>
                            <?php echo htmlspecialchars($selectedBooking['check_in_date']); ?>
                            to <?php echo htmlspecialchars($selectedBooking['check_out_date']); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Total Due</h6>
                        <?php $dueAmount = max(0, $selectedBooking['total_price'] - $selectedBooking['paid_amount']); ?>
                        <p class="h4"><?php echo formatMoney($dueAmount); ?></p>

                        <h6>Total Paid</h6>
                        <p><?php echo formatMoney($selectedBooking['paid_amount']); ?></p>

                        <h6>Booking Status</h6>
                        <p>
                            <span class="badge bg-<?php echo ($selectedBooking['payment_status'] === 'paid' ? 'success' : ($selectedBooking['payment_status'] === 'refunded' ? 'secondary' : 'warning')); ?>">
                                <?php echo ucfirst($selectedBooking['payment_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <form method="POST" action="payments.php?action=record">
                    <input type="hidden" name="booking_id" value="<?php echo $selectedBooking['id']; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Amount *</label>
                            <input type="number" step="0.01" min="0" max="<?php echo $dueAmount; ?>" class="form-control" name="amount" value="<?php echo $dueAmount > 0 ? $dueAmount : 0; ?>" required>
                            <small class="form-text text-muted">You can pay partial amount or the full due amount.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-control" name="payment_method" id="paymentMethod" required>
                                <option value="cash">Cash (Walk-in)</option>
                                <option value="credit_card">Card</option>
                                <option value="digital_bank">Digital Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <div id="digitalBank" style="display: none;">
                        <div class="alert alert-info">
                            <strong>Digital Bank Transfer</strong><br>
                            Scan the QR code below in your mobile banking app to pay.
                        </div>
                        <?php
                            $qrText = '';
                            if (isset($selectedBooking)) {
                                $qrText = 'PAYTO:CrizelsResort|BOOKING:' . $selectedBooking['booking_number'] .
                                    '|AMOUNT:' . number_format($dueAmount, 2) .
                                    '|BANK:CrizelsResortBank|ACC:123456789';
                            }
                        ?>
                        <div class="d-flex justify-content-center mb-3">
                            <img id="paymentQr" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode($qrText); ?>" alt="Digital Bank QR" style="max-width: 220px;" />
                        </div>
                    </div>
                    </div>

                    <div id="cardDetails" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cardholder Name</label>
                                <input type="text" class="form-control" placeholder="John Doe" name="card_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" placeholder="**** **** **** 1234" name="card_number">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Expiry</label>
                                <input type="text" class="form-control" placeholder="MM/YY" name="card_expiry">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" placeholder="123" name="card_cvv">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" class="form-control" name="transaction_id" value="TRX<?php echo time(); ?>" placeholder="Optional transaction reference">
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const paymentMethodEl = document.getElementById('paymentMethod');
                            const cardDetailsEl = document.getElementById('cardDetails');
                            const digitalBankEl = document.getElementById('digitalBank');

                            function updatePaymentFields() {
                                const method = paymentMethodEl?.value;
                                const showCard = ['credit_card'].includes(method);
                                const showDigital = method === 'digital_bank';

                                if (cardDetailsEl) cardDetailsEl.style.display = showCard ? 'block' : 'none';
                                if (digitalBankEl) digitalBankEl.style.display = showDigital ? 'block' : 'none';
                            }

                            paymentMethodEl?.addEventListener('change', updatePaymentFields);
                            updatePaymentFields();
                        });

                        function printReceipt() {
                            const receipt = document.getElementById('receipt');
                            if (!receipt) return;

                            const originalContents = document.body.innerHTML;
                            const receiptContents = receipt.innerHTML;

                            document.body.innerHTML = '<div class="container p-4">' + receiptContents + '</div>';
                            window.print();
                            document.body.innerHTML = originalContents;
                            window.location.reload();
                        }
                    </script>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes (e.g., payment details)"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Record Payment
                    </button>
                    <a href="payments.php" class="btn btn-secondary">Back to Payments</a>
                </form>

                <?php if ($receiptPayment): ?>
                    <hr>
                    <div id="receipt" class="p-4" style="border: 1px dashed #ccc; border-radius: 8px; background: #f9f9f9;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4>Payment Receipt</h4>
                                <p class="mb-0 text-muted"><?php echo date('Y-m-d H:i:s', strtotime($receiptPayment['payment_date'])); ?></p>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0">Crizel's Resort</h5>
                                <small class="text-muted">Payment Reference: <strong><?php echo htmlspecialchars($receiptPayment['id']); ?></strong></small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Guest</strong><br>
                                <?php echo htmlspecialchars($receiptPayment['first_name'] . ' ' . $receiptPayment['last_name']); ?><br>
                                <small class="text-muted">Booking #: <?php echo htmlspecialchars($receiptPayment['booking_number']); ?></small>
                            </div>
                            <div class="col-md-4">
                                <strong>Room</strong><br>
                                <?php echo htmlspecialchars($receiptPayment['room_number'] . ' (' . ucfirst($receiptPayment['room_type']) . ')'); ?><br>
                                <small class="text-muted">Amount Due: <?php echo formatMoney($selectedBooking['total_price']); ?></small>
                            </div>
                            <div class="col-md-4">
                                <strong>Payment</strong><br>
                                <?php echo formatMoney($receiptPayment['amount']); ?> <br>
                                <small class="text-muted">Method: <?php echo ($receiptPayment['payment_method'] === 'online_transfer') ? 'Digital Bank Transfer' : str_replace('_', ' ', ucfirst($receiptPayment['payment_method'])); ?></small><br>
                                <small class="text-muted">Txn ID: <?php echo htmlspecialchars($receiptPayment['transaction_id']); ?></small>
                            </div>
                        </div>

                        <?php if ($receiptPayment['payment_method'] === 'online_transfer'): ?>
                        <?php
                            $receiptQrText = "PAY TO: Crizel's Resort\n" .
                                "Booking: " . $receiptPayment['booking_number'] . "\n" .
                                "Amount: $" . number_format($receiptPayment['amount'], 2) . "\n" .
                                "Bank: Crizel's Resort Bank\n" .
                                "Account: 123-456-789";
                        ?>
                        <div class="text-center mb-3">
                            <img src="https://chart.googleapis.com/chart?cht=qr&chs=220x220&chl=<?php echo urlencode($receiptQrText); ?>" alt="Digital Bank QR" style="max-width: 220px;" />
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($receiptPayment['notes'])): ?>
                        <div class="mb-3">
                            <strong>Notes</strong><br>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($receiptPayment['notes'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="text-end">
                            <button class="btn btn-primary" onclick="printReceipt()">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const paymentMethodEl = document.getElementById('paymentMethod');
                        const cardDetailsEl = document.getElementById('cardDetails');
                        const digitalBankEl = document.getElementById('digitalBank');

                        function updatePaymentFields() {
                            const method = paymentMethodEl?.value;
                            const showCard = ['credit_card'].includes(method);
                            const showDigital = method === 'digital_bank';

                            if (cardDetailsEl) cardDetailsEl.style.display = showCard ? 'block' : 'none';
                            if (digitalBankEl) digitalBankEl.style.display = showDigital ? 'block' : 'none';
                        }

                        paymentMethodEl?.addEventListener('change', updatePaymentFields);
                        updatePaymentFields();
                    });

                    function printReceipt() {
                        const receipt = document.getElementById('receipt');
                        if (!receipt) return;

                        const originalContents = document.body.innerHTML;
                        const receiptContents = receipt.innerHTML;

                        document.body.innerHTML = '<div class="container p-4">' + receiptContents + '</div>';
                        window.print();
                        document.body.innerHTML = originalContents;
                        window.location.reload();
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'views/footer.php'; ?>
