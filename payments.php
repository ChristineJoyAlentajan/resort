<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requirePermission('record_payments');

$action = $_GET['action'] ?? 'list';
$message = '';
$receiptPayment = null;

function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'record') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

    header('Content-Type: application/json');

    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $method = sanitize($_POST['payment_method'] ?? 'cash');
    $methodForDb = $method === 'digital_bank' ? 'online_transfer' : $method;
    $transaction_id = sanitize($_POST['transaction_id'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    $booking = getBooking($booking_id);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }

    if ($amount < 0) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid payment amount.']);
        exit;
    }

    $due = max(0, $booking['total_price'] - $booking['paid_amount']);
    if ($amount === 0) {
        $amount = $due;
    }

    $sql = "INSERT INTO payments (booking_id, amount, payment_method, transaction_id, notes) 
            VALUES ($booking_id, $amount, '$methodForDb', '$transaction_id', '$notes')";

    if (!$conn->query($sql)) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
        exit;
    }

    $newPaidTotal = $booking['paid_amount'] + $amount;
    $newStatus = ($newPaidTotal >= $booking['total_price']) ? 'paid' : 'pending';
    $conn->query("UPDATE bookings SET payment_status = '$newStatus' WHERE id = $booking_id");

    $receiptPayment = $conn->query("SELECT p.*, b.booking_number, g.first_name, g.last_name, r.room_number, r.room_type
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN guests g ON b.guest_id = g.id
        JOIN rooms r ON b.room_id = r.id
        WHERE p.booking_id = $booking_id
        ORDER BY p.id DESC
        LIMIT 1")->fetch_assoc();

    if (!$receiptPayment) {
        echo json_encode(['success' => false, 'message' => 'Unable to load payment receipt.']);
        exit;
    }

    $receiptData = [
        'booking_number' => $receiptPayment['booking_number'] ?? '',
        'guest' => trim(($receiptPayment['first_name'] ?? '') . ' ' . ($receiptPayment['last_name'] ?? '')),
        'room' => trim(($receiptPayment['room_number'] ?? '') . ' (' . ucfirst($receiptPayment['room_type'] ?? '') . ')'),
        'amount' => number_format($receiptPayment['amount'] ?? 0, 2),
        'method' => ($receiptPayment['payment_method'] ?? '') === 'online_transfer' ? 'Digital Bank Transfer' : str_replace('_', ' ', ucfirst($receiptPayment['payment_method'] ?? '')),
        'transaction_id' => $receiptPayment['transaction_id'] ?? '',
        'notes' => $receiptPayment['notes'] ?? '',
        'payment_date' => date('Y-m-d H:i:s', strtotime($receiptPayment['payment_date'] ?? '')), 
    ];

    echo json_encode(['success' => true, 'receipt' => $receiptData]);
    exit;
}

$selectedBooking = null;
if ($action === 'pay' && isset($_GET['id'])) {
    $selectedBooking = getBooking((int)$_GET['id']);
}

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
                            $shouldPay = ($b['payment_status'] === 'pending');
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
                                <?php if ($shouldPay): ?>
                                    <button type="button" class="btn btn-sm btn-success payBtn" 
                                        data-bs-toggle="modal" data-bs-target="#paymentModal"
                                        data-booking-id="<?php echo $b['id']; ?>"
                                        data-booking-number="<?php echo htmlspecialchars($b['booking_number']); ?>"
                                        data-guest="<?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?>"
                                        data-room="<?php echo htmlspecialchars($b['room_number'] . ' (' . ucfirst($b['room_type']) . ')'); ?>"
                                        data-checkin="<?php echo htmlspecialchars($b['check_in_date']); ?>"
                                        data-checkout="<?php echo htmlspecialchars($b['check_out_date']); ?>"
                                        data-total="<?php echo number_format($b['total_price'], 2, '.', ''); ?>"
                                        data-paid="<?php echo number_format($b['paid_amount'], 2, '.', ''); ?>"
                                        data-due="<?php echo number_format($due, 2, '.', ''); ?>"
                                    >
                                        <i class="fas fa-credit-card"></i> Pay
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">No payment required</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="paymentAlert"></div>

                    <div id="paymentDetails" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Booking:</strong> <span id="modalBookingNumber"></span></p>
                                <p><strong>Guest:</strong> <span id="modalGuest"></span></p>
                                <p><strong>Room:</strong> <span id="modalRoom"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Stay:</strong> <span id="modalStay"></span></p>
                                <p><strong>Total:</strong> <span id="modalTotal"></span></p>
                            </div>
                        </div>
                    </div>

                    <form id="paymentForm" method="POST" action="payments.php?action=record">
                        <input type="hidden" name="booking_id" id="modalBookingId" value="">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Amount *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="amount" id="modalAmount" readonly required>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" value="" id="modalCustomAmount">
                                <label class="form-check-label" for="modalCustomAmount">Edit amount (partial payment)</label>
                            </div>
                            <small class="form-text text-muted">Amount defaults to balance due and will be recorded when you submit.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Method *</label>
                                <select class="form-control" name="payment_method" id="modalMethod" required>
                                    <option value="cash">Cash (Walk-in)</option>
                                    <option value="credit_card">Card</option>
                                    <option value="digital_bank">Digital Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        <div id="modalDigitalBank" style="display: none;" class="mb-3">
                            <div class="alert alert-info">
                                <strong>Digital Bank Transfer</strong><br>
                                Scan the QR code below in your banking app.
                            </div>
                            <div class="d-flex justify-content-center mb-2">
                                <img id="modalQr" src="" alt="Digital Bank QR" style="max-width: 220px;" />
                            </div>
                        </div>

                        <div id="modalCard" style="display: none;" class="mb-3">
                            <div class="alert alert-info">
                                <strong>Card Payment</strong><br>
                                Please process the card payment using your card terminal (POS machine).
                                Then enter the transaction reference ID below.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" class="form-control" name="transaction_id" value="TRX<?php echo time(); ?>" placeholder="Terminal transaction reference">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes (e.g., payment details)"></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success" id="modalSubmitBtn">
                                <i class="fas fa-check-circle"></i> Record Payment
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>

                    <div id="modalReceipt" style="display: none;" class="mt-4">
                        <hr>
                        <h5>Receipt</h5>
                        <div id="modalReceiptBody"></div>
                        <div class="text-end mt-3">
                            <button class="btn btn-primary" id="modalPrintBtn">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const paymentModalEl = document.getElementById('paymentModal');
        const modalBookingId = document.getElementById('modalBookingId');
        const modalBookingNumber = document.getElementById('modalBookingNumber');
        const modalGuest = document.getElementById('modalGuest');
        const modalRoom = document.getElementById('modalRoom');
        const modalStay = document.getElementById('modalStay');
        const modalTotal = document.getElementById('modalTotal');
        const modalAmount = document.getElementById('modalAmount');
        const modalCustomAmount = document.getElementById('modalCustomAmount');
        const modalMethod = document.getElementById('modalMethod');
        const modalCard = document.getElementById('modalCard');
        const modalDigitalBank = document.getElementById('modalDigitalBank');
        const modalQr = document.getElementById('modalQr');
        const paymentAlert = document.getElementById('paymentAlert');
        const paymentForm = document.getElementById('paymentForm');
        const modalReceipt = document.getElementById('modalReceipt');
        const modalReceiptBody = document.getElementById('modalReceiptBody');
        const modalPrintBtn = document.getElementById('modalPrintBtn');

        function buildQrPayload(bookingNumber, amount) {
            const cleanedAmount = parseFloat(amount) || 0;
            return `PAYTO:CrizelsResort|BOOKING:${bookingNumber}|AMOUNT:${cleanedAmount.toFixed(2)}|BANK:CrizelsResortBank|ACC:123456789`;
        }

        function updateModalQr() {
            if (!modalQr) return;
            const bookingNumber = modalBookingNumber.textContent || '';
            const amount = modalAmount.value || '0';
            const payload = buildQrPayload(bookingNumber, amount);
            modalQr.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(payload);
        }

        function updatePaymentFields() {
            const method = modalMethod.value;
            const showCard = method === 'credit_card';
            const showDigital = method === 'digital_bank';

            if (modalCard) modalCard.style.display = showCard ? 'block' : 'none';
            if (modalDigitalBank) modalDigitalBank.style.display = showDigital ? 'block' : 'none';
            if (showDigital) updateModalQr();
        }

        function showAlert(type, message) {
            if (!paymentAlert) return;
            paymentAlert.innerHTML = `<div class="alert alert-${type} alert-dismissible" role="alert">${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        }

        function setReceipt(data) {
            if (!modalReceiptBody) return;
            modalReceiptBody.innerHTML = `
                <p><strong>Booking:</strong> ${data.booking_number}</p>
                <p><strong>Guest:</strong> ${data.guest}</p>
                <p><strong>Room:</strong> ${data.room}</p>
                <p><strong>Payment:</strong> $${data.amount} (${data.method})</p>
                <p><strong>Transaction ID:</strong> ${data.transaction_id}</p>
                <p><strong>Date:</strong> ${data.payment_date}</p>
                ${data.notes ? `<p><strong>Notes:</strong> ${data.notes}</p>` : ''}
            `;
            if (modalReceipt) modalReceipt.style.display = 'block';
        }

        function resetModal() {
            if (paymentForm) paymentForm.reset();
            if (modalReceipt) modalReceipt.style.display = 'none';
            if (modalReceiptBody) modalReceiptBody.innerHTML = '';
            if (paymentAlert) paymentAlert.innerHTML = '';
        }

        if (paymentModalEl) {
            paymentModalEl.addEventListener('show.bs.modal', function (event) {
                resetModal();
                const button = event.relatedTarget;
                if (!button) return;
                const dataset = button.dataset;

                modalBookingId.value = dataset.bookingId || '';
                modalBookingNumber.textContent = dataset.bookingNumber || '';
                modalGuest.textContent = dataset.guest || '';
                modalRoom.textContent = dataset.room || '';
                modalStay.textContent = `${dataset.checkin || ''} to ${dataset.checkout || ''}`;
                modalTotal.textContent = `$${dataset.total || '0.00'}`;
                modalAmount.value = dataset.total || '';
                modalMethod.value = 'cash';
                updatePaymentFields();
            });
        }

        if (modalMethod) {
            modalMethod.addEventListener('change', updatePaymentFields);
        }

        if (modalAmount) {
            modalAmount.addEventListener('input', updateModalQr);
        }

        if (modalCustomAmount) {
            modalCustomAmount.addEventListener('change', function () {
                if (!modalAmount) return;
                modalAmount.readOnly = !this.checked;
                if (!this.checked) {
                    modalAmount.value = modalTotal.textContent.replace(/[^0-9\.]/g, '');
                    updateModalQr();
                }
            });
        }

        if (paymentForm) {
            paymentForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(paymentForm);
                fetch('payments.php?action=record', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        showAlert('danger', 'Unexpected server response. Please refresh and try again.');
                        console.error('Payment API parsing error:', e, text);
                        return;
                    }

                    if (data.success) {
                        showAlert('success', 'Payment recorded successfully.');
                        setReceipt(data.receipt);

                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        showAlert('danger', data.message || 'Unable to record payment.');
                    }
                })
                .catch((err) => {
                    console.error(err);
                    showAlert('danger', 'Unexpected error.');
                });
            });
        }

        if (modalPrintBtn) {
            modalPrintBtn.addEventListener('click', function () {
                const receiptHtml = modalReceiptBody.innerHTML;
                const printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Receipt</title></head><body>' + receiptHtml + '</body></html>');
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            });
        }
    </script>

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
                        <div class="alert alert-info">
                            <strong>Card Payment</strong><br>
                            Process the card payment on the terminal, then provide the transaction reference below.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" name="transaction_id" value="TRX<?php echo time(); ?>" placeholder="Terminal transaction reference">
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
