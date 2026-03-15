# Resort Management System - Developer Guide

## Code Architecture

The system is built with a simple MVC-inspired architecture using PHP for the backend.

```
├── config/          # Configuration files
├── views/           # Reusable templates (header, footer)
├── sql/             # Database schema
├── css/             # Stylesheets
├── js/              # Client-side JavaScript
├── *.php            # Main application pages (controllers)
└── README.md        # Documentation
```

## Database Schema Overview

### Core Tables

#### 1. rooms
Stores room information and availability status.

```sql
SELECT * FROM rooms;
```

**Key Fields:**
- `id` - Unique room identifier
- `room_number` - Room number (unique)
- `room_type` - Type: single, double, suite, deluxe, villa
- `capacity` - Number of guests
- `price_per_night` - Nightly rate
- `status` - available, occupied, maintenance
- `floor` - Floor number

#### 2. guests
Maintains guest information.

```sql
SELECT * FROM guests;
```

**Key Fields:**
- `id` - Guest ID
- `first_name`, `last_name` - Guest name
- `email`, `phone` - Contact info
- `address`, `city`, `country` - Address
- `id_type`, `id_number` - Identification

#### 3. bookings
Stores reservation records.

```sql
SELECT b.*, g.first_name, g.last_name, r.room_number
FROM bookings b
JOIN guests g ON b.guest_id = g.id
JOIN rooms r ON b.room_id = r.id;
```

**Key Fields:**
- `booking_number` - Unique booking reference
- `guest_id`, `room_id` - Foreign keys
- `check_in_date`, `check_out_date` - Stay dates
- `total_price` - Calculated total
- `status` - confirmed, checked-in, checked-out, cancelled
- `payment_status` - pending, paid, refunded

#### 4. services
Available resort services.

```sql
SELECT * FROM services WHERE status = 'available';
```

**Key Fields:**
- `id` - Service ID
- `name` - Service name
- `price` - Service cost
- `status` - available, unavailable

#### 5. booking_services
Links services to bookings (many-to-many).

#### 6. payments
Payment records for bookings.

**Key Fields:**
- `amount` - Payment amount
- `payment_method` - Cash, credit card, etc.
- `transaction_id` - Payment gateway reference

#### 7. maintenance_logs
Room maintenance tracking.

#### 8. users
Admin and staff accounts (for future authentication).

## Useful SQL Queries

### Get All Available Rooms
```sql
SELECT * FROM rooms WHERE status = 'available' ORDER BY room_number;
```

### Find Bookings by Date Range
```sql
SELECT b.*, g.first_name, g.last_name, r.room_number
FROM bookings b
JOIN guests g ON b.guest_id = g.id
JOIN rooms r ON b.room_id = r.id
WHERE b.check_in_date >= '2026-03-15' AND b.check_out_date <= '2026-03-20'
AND b.status IN ('confirmed', 'checked-in');
```

### Calculate Revenue for Date Range
```sql
SELECT SUM(total_price) as revenue
FROM bookings
WHERE check_in_date >= '2026-03-01'
AND check_out_date <= '2026-03-31'
AND status != 'cancelled';
```

### Get Guest Booking History
```sql
SELECT b.*, r.room_number, r.room_type
FROM bookings b
JOIN rooms r ON b.room_id = r.id
WHERE b.guest_id = 1
ORDER BY b.check_in_date DESC;
```

### Find Rooms with Maintenance Issues
```sql
SELECT DISTINCT r.*
FROM rooms r
LEFT JOIN maintenance_logs m ON r.id = m.room_id
WHERE r.status = 'maintenance'
OR (m.status = 'in-progress' AND m.end_date IS NULL);
```

### Get Room Occupancy for Specific Date
```sql
SELECT 
    (SELECT COUNT(*) FROM rooms) as total_rooms,
    (SELECT COUNT(DISTINCT room_id) FROM bookings 
     WHERE check_in_date <= '2026-03-15' 
     AND check_out_date > '2026-03-15'
     AND status IN ('confirmed', 'checked-in')) as occupied_rooms;
```

## PHP Code Examples

### Database Connection
```php
<?php
require_once 'config/db.php';

// Connection is available as $conn
// Example query:
$result = $conn->query("SELECT * FROM rooms WHERE status = 'available'");
$room = $result->fetch_assoc();
?>
```

### Add New Room (from rooms.php)
```php
<?php
$room_number = sanitize($_POST['room_number']);
$room_type = sanitize($_POST['room_type']);
$capacity = sanitize($_POST['capacity']);
$price = sanitize($_POST['price_per_night']);

$sql = "INSERT INTO rooms (room_number, room_type, capacity, price_per_night) 
        VALUES ('$room_number', '$room_type', $capacity, $price)";

if ($conn->query($sql)) {
    echo "Room added successfully!";
} else {
    echo "Error: " . $conn->error;
}
?>
```

### Update Booking Status
```php
<?php
$booking_id = 5;
$new_status = 'checked-in';

$sql = "UPDATE bookings SET status = '$new_status' WHERE id = $booking_id";

if ($conn->query($sql)) {
    // Also update room status
    $booking = $conn->query("SELECT room_id FROM bookings WHERE id = $booking_id")
               ->fetch_assoc();
    $conn->query("UPDATE rooms SET status = 'occupied' WHERE id = " . $booking['room_id']);
}
?>
```

### Calculate Booking Total
```php
<?php
function calculateBookingPrice($room_id, $check_in, $check_out) {
    global $conn;
    
    // Get room price
    $result = $conn->query("SELECT price_per_night FROM rooms WHERE id = $room_id");
    $room = $result->fetch_assoc();
    
    // Calculate nights
    $checkin_date = new DateTime($check_in);
    $checkout_date = new DateTime($check_out);
    $nights = $checkout_date->diff($checkin_date)->days;
    
    return $room['price_per_night'] * $nights;
}

$total = calculateBookingPrice(1, '2026-03-15', '2026-03-17');
echo "Total: $" . number_format($total, 2);
?>
```

### Generate Booking Report
```php
<?php
function getBookingReport($start_date, $end_date) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total_bookings,
                SUM(total_price) as total_revenue,
                AVG(total_price) as avg_booking_value,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings
            FROM bookings
            WHERE check_in_date >= '$start_date'
            AND check_out_date <= '$end_date'";
    
    return $conn->query($sql)->fetch_assoc();
}

$report = getBookingReport('2026-03-01', '2026-03-31');
print_r($report);
?>
```

## JavaScript Helper Functions

### Calculate Price (Frontend)
```javascript
// From js/main.js
function calculatePrice() {
    var checkIn = new Date(document.querySelector('input[name="check_in_date"]').value);
    var checkOut = new Date(document.querySelector('input[name="check_out_date"]').value);
    var price = 50; // Example price
    
    var nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
    var total = price * nights;
    
    return total;
}
```

### Show Notification
```javascript
// From js/main.js
showNotification('Booking created successfully!', 'success');
```

### Format Currency
```javascript
// From js/main.js
var formatted = formatCurrency(1000.50);
// Output: $1,000.50
```

## Common Customizations

### Add New Room Type
1. Edit `sql/resort_db.sql` - UPDATE the ENUM in rooms table
2. Re-import database OR
3. Modify in `rooms.php` form - add new option to room_type select

### Extend Booking Fields
1. Add column to bookings table
2. Update `bookings.php` to include new field in form
3. Update INSERT/UPDATE queries

### Add User Authentication
1. Create login.php
2. Add session checks at start of main pages
3. Add user management in config/db.php

### Add Email Notifications
```php
<?php
// Example email on booking creation
mail($guest_email, "Booking Confirmation", 
     "Your booking #" . $booking_number . " has been confirmed!");
?>
```

## URL Routing

All routing is file-based. URLs follow this pattern:

```
http://localhost/resort/[page].php?action=[list|add|edit|delete]&id=[id]
```

Examples:
- `index.php` - Dashboard
- `rooms.php?action=list` - List all rooms
- `rooms.php?action=add` - Add new room form
- `rooms.php?action=edit&id=1` - Edit room 1
- `bookings.php?action=add` - Create new booking

## Error Handling

The system uses basic error handling. For production, add:

```php
<?php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');

try {
    // Database operations
    if (!$conn->query($sql)) {
        throw new Exception("Database error: " . $conn->error);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    die("An error occurred. Please contact support.");
}
?>
```

## Performance Tips

1. **Add Indexes**: Already added for common queries
2. **Limit Results**: Use LIMIT clause for large datasets
3. **Pagination**: Implement using OFFSET
4. **Cache**: Add Redis for session management
5. **API Optimization**: Return JSON instead of HTML rendering

## Security Enhancements

1. Add prepared statements:
```php
<?php
$stmt = $conn->prepare("INSERT INTO guests (first_name, last_name) VALUES (?, ?)");
$stmt->bind_param("ss", $first_name, $last_name);
$stmt->execute();
?>
```

2. Add CSRF tokens to forms
3. Implement rate limiting
4. Add input validation on both client and server
5. Use HTTPS in production

## Testing Sample Queries

Run these in phpMyAdmin to test:

```sql
-- Test data integrity
SELECT COUNT(*) FROM bookings WHERE guest_id NOT IN (SELECT id FROM guests);

-- Test room availability
SELECT room_number, COUNT(*) 
FROM bookings 
WHERE status = 'checked-in' 
GROUP BY room_id;

-- Test payment tracking
SELECT payment_status, COUNT(*), SUM(amount) 
FROM payments 
GROUP BY booking_id;
```

## Future Development

Consider implementing:
- RESTful API endpoints
- WebSockets for real-time updates
- Mobile app backend
- Advanced analytics dashboard
- Multi-property support
- Rate management engine
- Channel manager integration

---

For more information, see `README.md` and `QUICKSTART.md`.
