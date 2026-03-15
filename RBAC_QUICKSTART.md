# Role-Based Access Control - Quick Reference

## Role Breakdown

### 👑 ADMIN - Full System Control

**Staff Management**
- ✅ Add new staff accounts
- ✅ Edit staff information  
- ✅ Delete staff accounts
- ✅ View all staff profiles

**Rooms & Cottages**
- ✅ Add new rooms/cottages
- ✅ Edit room details
- ✅ Delete rooms
- ✅ Set room prices
- ✅ View all rooms

**Pricing & Services**
- ✅ Manage resort services
- ✅ Set service prices
- ✅ Add/edit/delete services
- ✅ View service availability

**Bookings**
- ✅ View all bookings
- ✅ Create manual bookings
- ✅ Edit bookings
- ✅ Delete bookings
- ✅ Approve reservations

**Guests**
- ✅ View all guests
- ✅ Add new guests
- ✅ Edit guest info
- ✅ Delete guest records
- ✅ Monitor guest history

**Reports & Analytics**
- ✅ Daily income reports
- ✅ Weekly income reports
- ✅ Monthly income reports
- ✅ Booking statistics
- ✅ Custom date range reports

**System Settings**
- ✅ Manage system configuration
- ✅ Create database backups
- ✅ Maintain database
- ✅ View system logs
- ✅ Monitor admin actions

---

### 📊 MANAGER - Operations Management

**Booking Management**
- ✅ View all bookings
- ✅ Manage reservations
- ✅ Approve bookings
- ✅ Update booking status
- ❌ Cannot delete bookings

**Room Monitoring**
- ✅ View room availability
- ✅ Monitor room status
- ✅ Check room details
- ❌ Cannot add/edit/delete

**Guest Information**
- ✅ View all guests
- ✅ Check guest details
- ✅ Edit guest information
- ❌ Cannot delete guests

**Staff Oversight**
- ✅ View staff information
- ✅ Manage staff schedules
- ✅ Assign staff tasks
- ❌ Cannot add/edit/delete staff

**Reports & Sales**
- ✅ View daily sales
- ✅ View weekly reports
- ✅ View monthly reports
- ✅ Generate custom reports
- ❌ Cannot view admin logs

**Customer Service**
- ✅ Handle complaints
- ✅ Resolve issues
- ✅ Update special requests

---

### 👤 STAFF - Daily Service Tasks

**Reservation Check**
- ✅ View guest reservations
- ✅ Check booking details
- ✅ Verify room assignments
- ❌ Cannot create/edit bookings

**Guest Management**
- ✅ View guest info
- ✅ Register walk-in guests
- ✅ Add new guests
- ✅ Update guest details
- ❌ Cannot delete guests

**Room Operations**
- ✅ View room status
- ✅ Update room status
- ✅ Check availability
- ❌ Cannot add/edit/delete

**Check-in/Check-out**
- ✅ Process check-in
- ✅ Process check-out
- ✅ Update room status
- ✅ Record times

**Payment Recording**
- ✅ Record payments
- ✅ Update payment status
- ✅ Generate receipts

---

## Permission Matrix

| Feature | Admin | Manager | Staff |
|---------|:-----:|:-------:|:-----:|
| View Dashboard | ✅ | ✅ | ✅ |
| **ROOMS** |
| View Rooms | ✅ | ✅ | ✅ |
| Add Rooms | ✅ | ❌ | ❌ |
| Edit Rooms | ✅ | ❌ | ❌ |
| Delete Rooms | ✅ | ❌ | ❌ |
| Set Prices | ✅ | ❌ | ❌ |
| Update Room Status | ✅ | ✅ | ✅ |
| Monitor Availability | ✅ | ✅ | ✅ |
| **GUESTS** |
| View Guests | ✅ | ✅ | ✅ |
| Add Guests | ✅ | ✅ | ✅ |
| Edit Guests | ✅ | ✅ | ✅ |
| Delete Guests | ✅ | ❌ | ❌ |
| Register Walk-In | ✅ | ✅ | ✅ |
| **BOOKINGS** |
| View Bookings | ✅ | ✅ | ✅ |
| Add Bookings | ✅ | ✅ | ❌ |
| Edit Bookings | ✅ | ✅ | ❌ |
| Delete Bookings | ✅ | ❌ | ❌ |
| Approve Bookings | ✅ | ✅ | ❌ |
| **SERVICES** |
| View Services | ✅ | ❌ | ❌ |
| Manage Services | ✅ | ❌ | ❌ |
| Add Services | ✅ | ❌ | ❌ |
| Edit Services | ✅ | ❌ | ❌ |
| Delete Services | ✅ | ❌ | ❌ |
| **STAFF** |
| View Staff | ✅ | ✅ | ❌ |
| Add Staff | ✅ | ❌ | ❌ |
| Edit Staff | ✅ | ❌ | ❌ |
| Delete Staff | ✅ | ❌ | ❌ |
| Manage Schedules | ✅ | ✅ | ❌ |
| **REPORTS** |
| View Reports | ✅ | ✅ | ❌ |
| Daily Reports | ✅ | ✅ | ❌ |
| Weekly Reports | ✅ | ✅ | ❌ |
| Monthly Reports | ✅ | ✅ | ❌ |
| Custom Reports | ✅ | ✅ | ❌ |
| Income Reports | ✅ | ✅ | ❌ |
| **PAYMENTS** |
| View Payments | ✅ | ✅ | ✅ |
| Record Payments | ✅ | ✅ | ✅ |
| Update Payment Status | ✅ | ✅ | ✅ |
| **SETTINGS & LOGS** |
| System Settings | ✅ | ❌ | ❌ |
| Database Backup | ✅ | ❌ | ❌ |
| View Admin Logs | ✅ | ❌ | ❌ |

---

## Navigation Menu by Role

### Admin Sees
```
🏠 Dashboard
🚪 Rooms
👥 Guests
📅 Bookings
🎁 Services
👤 Staff
📊 Reports
⚙️ Settings
👤 Profile (dropdown)
```

### Manager Sees
```
🏠 Dashboard
🚪 Rooms
👥 Guests
📅 Bookings
👤 Staff (view only)
📊 Reports
👤 Profile (dropdown)
```

### Staff Sees
```
🏠 Dashboard
🚪 Rooms
👥 Guests
📅 Bookings
👤 Profile (dropdown)
```

---

## Access Control in Code

### Checking Permissions
```php
<?php
require_once 'config/auth.php';

// Check if user has a specific permission
if (hasPermission('manage_staff')) {
    // Show staff management section
}

// Check if user has any of the permissions
if (hasAnyPermission(['add_rooms', 'edit_rooms'])) {
    // Show room management buttons
}

// Require permission (redirect if no access)
requirePermission('manage_services');
```

### Checking Roles
```php
<?php
require_once 'config/auth.php';

// Check specific role
if (isAdmin()) {
    // Admin-only code
}

if (isManager()) {
    // Manager-only code
}

if (isStaff()) {
    // Staff-only code
}

// Require role
requireRole('admin');  // Only admins can access
requireAnyRole(['admin', 'manager']);  // Admins or managers
```

---

## Testing Access Control

### Create Test Accounts
```sql
-- Admin
INSERT INTO users (name, email, password, role, status) 
VALUES ('Admin User', 'admin@resort.com', '$2y$10$...', 'admin', 'active');

-- Manager
INSERT INTO users (name, email, password, role, status) 
VALUES ('Manager User', 'manager@resort.com', '$2y$10$...', 'manager', 'active');

-- Staff
INSERT INTO users (name, email, password, role, status) 
VALUES ('Staff User', 'staff@resort.com', '$2y$10$...', 'staff', 'active');
```

### Test Each Role
1. **Log in as Admin** → Verify all menu items appear
2. **Log in as Manager** → Verify limited menu (no Settings/Staff)
3. **Log in as Staff** → Verify minimal menu
4. **Try direct URL access** to restricted pages → Should redirect

---

## Common Permission Names

```
// Staff Management
manage_staff, add_staff, edit_staff, delete_staff, view_staff

// Rooms
manage_rooms, add_rooms, edit_rooms, delete_rooms, view_rooms

// Pricing & Services
manage_services, add_services, edit_services, delete_services
manage_pricing, set_prices

// Bookings
manage_bookings, approve_bookings, delete_bookings, view_bookings

// Guests
manage_guests, add_guests, edit_guests, delete_guests, view_guests
register_walkin_guests, check_guest_info

// Room Operations
update_room_status, monitor_room_availability

// Payments & Check-in
record_payments, checkin_checkout

// Reports
view_reports, generate_reports, view_income_reports, view_sales_reports

// System
manage_settings, backup_database, maintain_database, view_system_logs

// Dashboard
view_dashboard
```

---

## Important Notes

1. **Permissions are Checked Automatically**
   - Each page requires specific permissions
   - Unauthorized access redirects to `access-denied.php`

2. **All Actions are Logged**
   - Admin actions are recorded in `admin_logs` table
   - View in Settings page

3. **Role Cannot Be Changed by Users**
   - Only admins can assign/change roles
   - Users cannot elevate their own permissions

4. **Session-Based**
   - Permissions loaded from session
   - Logout clears session

5. **CSRF Protected**
   - All forms have token verification
   - Tokens prevent unauthorized actions

---

## Quick Setup Checklist

- [x] Authorization system (RBAC) implemented
- [x] Three roles defined (Admin, Manager, Staff)
- [x] Permissions assigned to each role
- [x] Pages include permission checks
- [x] Admin action logging enabled
- [x] Access denied page created
- [x] Role-based navigation menu
- [x] Documentation complete

---

**For detailed information, see:** [RBAC_GUIDE.md](RBAC_GUIDE.md)
**For login info, see:** [LOGIN_GUIDE.md](LOGIN_GUIDE.md)

Last Updated: March 15, 2026
