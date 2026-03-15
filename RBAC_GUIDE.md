# Role-Based Access Control (RBAC) Documentation

## Overview

The Resort Management System implements a comprehensive Role-Based Access Control (RBAC) system that restricts functionality based on user roles. There are three primary roles:

1. **Admin** - Full system control
2. **Manager** - Operational management
3. **Staff** - Daily service tasks

---

## File Structure

### Core Authorization File
- **`config/auth.php`** - RBAC helper functions and permission definitions

### Role-Specific Pages
- **`reports.php`** - Admin & Manager only (view income reports, analytics)
- **`settings.php`** - Admin only (system settings, backups, logs)
- **`access-denied.php`** - Shown when users lack permissions

---

## Admin Role Capabilities

### Staff Management
- ✅ Add new staff accounts
- ✅ Edit staff information
- ✅ Delete staff accounts
- ✅ View all staff profiles
- ✅ Manage staff roles and permissions

**Module:** `users.php`

### Rooms & Cottages Management
- ✅ Add new rooms/cottages
- ✅ Edit room details and pricing
- ✅ Delete rooms from system
- ✅ View all rooms and availability
- ✅ Update room descriptions and amenities

**Module:** `rooms.php`

### Pricing & Services
- ✅ Set and modify room prices
- ✅ Add new resort services
- ✅ Edit service details and pricing
- ✅ Delete services
- ✅ Manage service availability

**Module:** `services.php`

### Booking Management
- ✅ View all bookings (past, present, future)
- ✅ Create manual bookings
- ✅ Edit booking information
- ✅ Delete bookings
- ✅ Approve reservations

**Module:** `bookings.php`

### Guest Management
- ✅ View all guest records
- ✅ Add new guests
- ✅ Edit guest information
- ✅ Delete guest profiles
- ✅ Monitor guest history

**Module:** `guests.php`

### Reports & Analytics
- ✅ View daily income reports
- ✅ View weekly income reports
- ✅ View monthly income reports
- ✅ Generate custom date range reports
- ✅ View booking statistics
- ✅ Download reports (coming soon)

**Module:** `reports.php`

### System Settings & Maintenance
- ✅ Manage system configuration
- ✅ Create database backups
- ✅ Maintain database integrity
- ✅ View system logs
- ✅ Monitor admin actions
- ✅ View system information

**Module:** `settings.php`

---

## Manager Role Capabilities

### Reservation Management
- ✅ View all bookings and schedules
- ✅ Approve/manage reservations
- ✅ Update booking status
- ✅ View booking details
- ❌ Cannot delete bookings
- ❌ Cannot modify prices

**Module:** `bookings.php`

### Room Monitoring
- ✅ View room availability
- ✅ Monitor room status (available, occupied, cleaning)
- ✅ Check room details and amenities
- ❌ Cannot add/edit/delete rooms
- ❌ Cannot change room prices

**Module:** `rooms.php`

### Guest Information
- ✅ View all guest records
- ✅ Check guest information
- ✅ Edit guest contact details
- ✅ Monitor guest history
- ❌ Cannot delete guests

**Module:** `guests.php`

### Staff Management (Limited)
- ✅ View staff information
- ✅ Manage staff schedules
- ✅ Assign staff tasks
- ❌ Cannot add/edit/delete staff

**Module:** `users.php`

### Reports & Sales
- ✅ View daily sales reports
- ✅ View weekly income reports
- ✅ View monthly income reports
- ✅ View booking statistics
- ✅ Generate custom reports
- ❌ Cannot access admin logs

**Module:** `reports.php`

### Customer Service
- ✅ Handle customer complaints
- ✅ Resolve booking issues
- ✅ Update guest information
- ✅ Manage special requests

---

## Staff Role Capabilities

### Reservation Check
- ✅ View guest reservations
- ✅ Check booking details
- ✅ Verify room assignments
- ❌ Cannot create/edit/delete bookings

**Module:** `bookings.php`

### Guest Management
- ✅ Register walk-in guests
- ✅ Add new guest profiles
- ✅ Update guest information
- ✅ View guest details
- ❌ Cannot delete guests
- ❌ Cannot view other staff accounts

**Module:** `guests.php`

### Room Operations
- ✅ View room status and availability
- ✅ Update room status (available, occupied, cleaning)
- ✅ Check-in procedures
- ✅ Check-out procedures
- ❌ Cannot add/edit/delete rooms
- ❌ Cannot view room prices

**Module:** `rooms.php`

### Payment Recording
- ✅ Record guest payments
- ✅ Update payment status
- ✅ Generate payment receipts
- ❌ Cannot modify prices
- ❌ Cannot generate financial reports

### Check-in/Check-out
- ✅ Process guest check-ins
- ✅ Process guest check-outs
- ✅ Update room status during checkin/checkout
- ✅ Record check-in times
- ✅ Manage special requests

---

## Authorization Functions

### Core Functions in `config/auth.php`

#### Permission Checking
```php
// Check single permission
hasPermission('view_rooms');

// Check multiple permissions (ANY)
hasAnyPermission(['view_reports', 'manage_bookings']);

// Check multiple permissions (ALL)
hasAllPermissions(['view_rooms', 'update_room_status']);

// Check if logged in
isLoggedIn();

// Get current user role
getUserRole();
```

#### Role Checking
```php
// Check specific role
isAdmin();
isManager();
isStaff();

// Require specific role
requireRole('admin');

// Require any role
requireAnyRole(['admin', 'manager']);
```

#### Enforcement Functions
```php
// Require permission (redirects if denied)
requirePermission('manage_staff');

// Require any permission (redirects if denied)
requireAnyPermission(['view_reports', 'manage_bookings']);

// Require role (redirects if denied)
requireRole('admin');
```

#### Information Functions
```php
// Get all permissions for current user
$permissions = getCurrentUserPermissions();

// Get permissions for specific role
$admin_perms = getRolePermissions('admin');

// Get all available roles
$roles = getAllRoles();

// Get role display name
$name = getRoleDisplayName('admin'); // Returns: "Administrator"

// Get role badge HTML
echo getRoleBadge('manager');
```

#### Logging Functions
```php
// Log admin actions
logAdminAction('staff_deleted', 'Removed user #5', ['user_id' => 5]);
```

---

## Implementation Examples

### Restrict Page to Admin Only
```php
<?php
require_once 'config/auth.php';

// Method 1: Check role
if (!isAdmin()) {
    header("Location: access-denied.php");
    exit();
}

// Method 2: Require function
requireRole('admin');

// Method 3: Require permission
requirePermission('manage_staff');
```

### Show Content Based on Permissions
```php
<?php if (hasPermission('add_staff')): ?>
    <a href="add-staff.php" class="btn btn-primary">Add Staff</a>
<?php endif; ?>

<?php if (hasPermission('manage_services')): ?>
    <button onclick="openServiceModal()" class="btn btn-primary">Add Service</button>
<?php endif; ?>
```

### Conditional Form Actions
```php
<?php if (hasPermission('edit_rooms')): ?>
    <form method="POST" action="update-room.php">
        <!-- Form fields -->
        <button type="submit">Update Room</button>
    </form>
<?php else: ?>
    <p class="text-muted">You don't have permission to edit rooms</p>
<?php endif; ?>
```

### Log Admin Actions
```php
<?php
// When deleting staff
if ($delete_successful) {
    logAdminAction(
        'staff_deleted',
        'Deleted staff member: ' . $staff_name,
        ['staff_id' => $staff_id, 'email' => $staff_email]
    );
}
```

---

## Permission Matrix

| Feature | Admin | Manager | Staff |
|---------|-------|---------|-------|
| **View Dashboard** | ✅ | ✅ | ✅ |
| **View Rooms** | ✅ | ✅ | ✅ |
| **Add Rooms** | ✅ | ❌ | ❌ |
| **Edit Rooms** | ✅ | ❌ | ❌ |
| **Delete Rooms** | ✅ | ❌ | ❌ |
| **Set Prices** | ✅ | ❌ | ❌ |
| **View Guests** | ✅ | ✅ | ✅ |
| **Add Guests** | ✅ | ✅ | ✅ |
| **Edit Guests** | ✅ | ✅ | ✅ |
| **Delete Guests** | ✅ | ❌ | ❌ |
| **View Bookings** | ✅ | ✅ | ✅ |
| **Add Bookings** | ✅ | ✅ | ❌ |
| **Approve Bookings** | ✅ | ✅ | ❌ |
| **Delete Bookings** | ✅ | ❌ | ❌ |
| **Update Room Status** | ✅ | ✅ | ✅ |
| **Record Payments** | ✅ | ✅ | ✅ |
| **Check-in/Check-out** | ✅ | ✅ | ✅ |
| **Manage Services** | ✅ | ❌ | ❌ |
| **Manage Staff** | ✅ | ❌ | ❌ |
| **View Reports** | ✅ | ✅ | ❌ |
| **Generate Reports** | ✅ | ✅ | ❌ |
| **Manage Settings** | ✅ | ❌ | ❌ |
| **Backup Database** | ✅ | ❌ | ❌ |
| **View Admin Logs** | ✅ | ❌ | ❌ |

---

## Login Page Navigation

Based on role, users will see different menu items:

### Admin Navbar
- 🏠 Dashboard
- 🚪 Rooms
- 👥 Guests
- 📅 Bookings
- 🎁 Services
- 👤 Staff
- 📊 Reports
- ⚙️ Settings

### Manager Navbar
- 🏠 Dashboard
- 🚪 Rooms
- 👥 Guests
- 📅 Bookings
- 👤 Staff (View only)
- 📊 Reports

### Staff Navbar
- 🏠 Dashboard
- 🚪 Rooms
- 👥 Guests
- 📅 Bookings

---

## Admin Logs Table

The system automatically creates and maintains an `admin_logs` table that tracks:
- **User ID** - Who performed the action
- **User Name** - Display name of the user
- **User Role** - Role of the user (admin/manager/staff)
- **Action** - Type of action (delete_staff, add_room, etc.)
- **Description** - Detailed description
- **IP Address** - User's IP address
- **Data** - JSON data associated with action
- **Timestamp** - When action occurred

**Accessible at:** `settings.php` (Admin only)

---

## Security Best Practices

### 1. Always Check Permissions
- Never assume user has access
- Always verify permissions before showing/processing data
- Use `requirePermission()` for critical actions

### 2. Prevent Direct URL Access
- Check permissions at the start of each page
- Redirect to access-denied.php if no permission
- Log unauthorized access attempts

### 3. Validate Form Data
- Verify user has permission to perform action
- Check CSRF tokens
- Sanitize all inputs

### 4. Log Important Actions
- Log all administrative actions
- Log all data modifications
- Log failed access attempts

### 5. Secure Database Backups
- Store backups outside web root
- Use strong encryption
- Rotate backup files

---

## Common Tasks

### Add Permission Check to Existing Page
```php
<?php
// At the top of the page, after session/db init
require_once 'config/auth.php';

// Redirect if user lacks permission
requirePermission('permission_name');

// OR show based on permission
if (!hasPermission('permission_name')) {
    header("Location: access-denied.php");
    exit();
}
```

### Create Admin-Only Page
```php
<?php
require_once 'config/auth.php';
requireRole('admin');
// Page content here
```

### Create Manager+ Page
```php
<?php
require_once 'config/auth.php';
requireAnyRole(['admin', 'manager']);
// Page content here
```

### Show Button Based on Role
```php
<?php if (isAdmin()): ?>
    <button>Admin Action</button>
<?php elseif (isManager()): ?>
    <button>Manager Action</button>
<?php else: ?>
    <p>Staff view only</p>
<?php endif; ?>
```

---

## Testing the System

To test different roles:

1. Create test accounts for each role:
   - Admin: `admin@resort.com`
   - Manager: `manager@resort.com`
   - Staff: `staff@resort.com`

2. Log in with each account and verify:
   - Correct menu items appear
   - Can access allowed pages
   - Cannot access restricted pages
   - Forms only show for allowed actions

3. Try direct URL access to restricted pages
   - Should be denied
   - Should redirect to `access-denied.php`

4. Monitor admin logs in `settings.php`:
   - Actions are logged
   - IP addresses are recorded
   - Timestamps are accurate

---

## Advanced Customization

### Add New Permission
1. Open `config/auth.php`
2. Add to role in `ROLE_PERMISSIONS` array:
   ```php
   'admin' => [
       'my_new_permission' => true,  // Add here
   ]
   ```
3. Use in code:
   ```php
   if (hasPermission('my_new_permission')) { }
   ```

### Add New Role
1. Define in `ROLE_PERMISSIONS`:
   ```php
   'guest_manager' => [
       'view_guests' => true,
       'edit_guests' => true,
   ]
   ```
2. Update database `users` table to include new role
3. Use in code:
   ```php
   if (getUserRole() === 'guest_manager') { }
   ```

### Log Custom Actions
```php
logAdminAction(
    'action_name',
    'Detailed description of action',
    ['key' => 'value', 'data' => 'optional']
);
```

---

## Troubleshooting

### Users Seeing "Access Denied"
1. Check user's role in database
2. Verify role permissions in `config/auth.php`
3. Check `requirePermission()` calls are correct

### Permission Not Working
1. Verify permission name in `ROLE_PERMISSIONS`
2. Check role is assigned to user
3. Clear browser cache
4. Verify function call: `hasPermission('exact_name')`

### Menu Items Not Showing
1. Check header.php permissions
2. Verify `require_once 'config/auth.php'`
3. Verify role in `$_SESSION['user_role']`

---

## Support

For more information, see:
- [LOGIN_GUIDE.md](LOGIN_GUIDE.md) - User authentication
- [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) - Development guidelines
- [README.md](README.md) - Project overview

---

**Last Updated:** March 15, 2026
**Version:** 1.0
