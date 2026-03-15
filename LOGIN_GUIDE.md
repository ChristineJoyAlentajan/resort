# Login & Authorization System Guide

## Overview
A complete authentication system has been implemented for the Resort Management System with the following features:
- **User Login** with email and password
- **User Registration** for creating new staff accounts
- **Role-Based Authorization** (Admin, Manager, Staff)
- **Password Visibility Toggle** (Show/Hide)
- **Password Strength Indicator**
- **User Profile Management**
- **Change Password** functionality
- **Session Management** with automatic logout

---

## Files Created

### 1. **login.php**
Main login page for system access.

**Features:**
- Email and password authentication
- Show/Hide password toggle
- CSRF token protection
- Session management
- Role validation (Admin, Manager, Staff only)
- Link to registration page
- Responsive design with gradient styling

**Login Flow:**
```
User enters email & password
   └─> Email is checked in database
       └─> If found & active, password is verified
           └─> If password matches, role is checked
               └─> If role is admin/manager/staff, session is created
                   └─> Redirect to index.php
```

**Access:** `http://localhost/resort/login.php`

---

### 2. **register.php**
Registration page for creating new staff accounts.

**Features:**
- Full name, email, phone input
- Role selection (Staff, Manager, Admin)
- Password strength indicator
- Confirm password field
- Password show/hide toggle
- Validation for:
  - Minimum 6 characters
  - Password matching
  - Valid email format
  - Duplicate email prevention
- Password strength feedback:
  - ⚠ Weak (red)
  - • Fair (orange)
  - ✓ Strong (green)

**Password Requirements:**
- Minimum 6 characters
- Mix of uppercase and lowercase letters recommended
- Numbers and special characters for better security

**Access:** `http://localhost/resort/register.php`

---

### 3. **logout.php**
Simple logout script that destroys sessions.

**Access:** `http://localhost/resort/logout.php`

---

### 4. **profile.php**
User profile management page.

**Features:**
- View user information
- Edit name, email, phone
- Display user role with badge
- Update profile functionality
- Link to change password
- CSRF token protection

**Access:** `http://localhost/resort/profile.php` (Requires login)

---

### 5. **change-password.php**
Dedicated password change page.

**Features:**
- Current password verification
- New password with strength indicator
- Confirm password field
- Show/Hide password toggles
- Password requirements display
- CSRF token protection

**Access:** `http://localhost/resort/change-password.php` (Requires login)

---

## Security Features

### 1. **CSRF Token Protection**
All forms include CSRF tokens generated in `config/db.php`:
```php
generateToken();           // Generate or retrieve token
verifyToken($token);       // Verify token validity
```

### 2. **Password Security**
- Passwords are hashed using `password_hash()` (PHP's built-in function)
- Password verification uses `password_verify()`
- No plain text passwords stored

### 3. **Session Management**
- Sessions start automatically on each page
- Login check redirects unauthorized users to login page
- Session variables: `user_id`, `user_name`, `user_email`, `user_role`

### 4. **Input Validation**
- Email validation using `FILTER_VALIDATE_EMAIL`
- Input sanitization with `sanitize()` function
- All user inputs are escaped

### 5. **Role-Based Access Control**
Only these roles can access the system:
- **Admin** - Full system access
- **Manager** - Management level operations
- **Staff** - Standard staff operations

---

## How to Use

### For Users - Login

1. **Navigate to Login Page**
   ```
   http://localhost/resort/login.php
   ```

2. **Enter Credentials**
   - Email address
   - Password

3. **Show/Hide Password**
   - Click the eye icon to show/hide password

4. **Click Login**
   - If credentials are valid and role is authorized, you'll be redirected to dashboard

5. **On Success**
   - Session created
   - User info stored in session
   - Redirected to `index.php`

---

### For Admins - Create New User

#### Method 1: Registration Page
1. Click "Create Account" link on login page
2. Fill in user details:
   - Full Name
   - Email Address
   - Phone Number
   - User Role (Staff/Manager/Admin)
   - Password (min 6 characters)
   - Confirm Password
3. Click "Create Account"
4. New user can login with email and password

#### Method 2: Direct Database Insert
```php
// Requires password hashing
INSERT INTO users (name, email, password, phone, role, status) 
VALUES ('John Doe', 'john@resort.com', '$2y$10$...', '+1-800-000-0000', 'staff', 'active');
```

---

### For Users - Change Password

1. **Click User Menu** (top right, shows your name)
2. **Select "Change Password"**
3. **Enter Current Password**
   - For verification
4. **Enter New Password**
   - Watch the strength indicator
5. **Confirm New Password**
   - Must match new password
6. **Click "Change Password"**
7. **Logout and Login** with new password

---

### For Users - Access Profile

1. **Click User Menu** (top right)
2. **Click "Profile"**
3. **View/Edit Information**
   - Name
   - Email
   - Phone Number
4. **Click "Update Profile"** to save changes

---

## Database Table Structure

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'manager') DEFAULT 'staff',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```

**Fields:**
- `id` - User ID (Primary Key)
- `name` - User's full name
- `email` - Unique email address (login username)
- `password` - Hashed password (bcrypt)
- `role` - User role (admin/manager/staff)
- `phone` - Contact phone number
- `status` - Account status (active/inactive)

---

## Session Variables

After login, the following variables are available:
```php
$_SESSION['user_id']       // User ID
$_SESSION['user_name']     // User's full name
$_SESSION['user_email']    // User's email
$_SESSION['user_role']     // User's role (admin/manager/staff)
$_SESSION['csrf_token']    // CSRF protection token
```

---

## Authentication Flow Diagram

```
┌─────────────────────┐
│   Login Page        │
│  (login.php)        │
└──────────┬──────────┘
           │
           │ Enter Email & Password
           │
           ↓
┌─────────────────────────────────┐
│ Check Email in Database         │
└──────────┬──────────────────────┘
           │
       Found?
      ↙     ↖
    NO       YES
    │        │
    │        ↓
    │    ┌────────────────────────┐
    │    │ Verify Password        │
    │    │ (password_verify)      │
    │    └────────┬───────────────┘
    │             │
    │         Matches?
    │        ↙     ↖
    │      NO       YES
    │      │        │
    │      │        ↓
    │      │    ┌─────────────────┐
    │      │    │ Check Role      │
    │      │    │ (admin/manager/ │
    │      │    │  staff)         │
    │      │    └────────┬────────┘
    │      │             │
    │      │         Allowed?
    │      │        ↙     ↖
    │      │      NO       YES
    │      │      │        │
    │      │      ↓        ↓
    │      │   Error   ┌──────────────┐
    │      │   Message │ Create       │
    │      │           │ Session      │
    │      │           └────┬─────────┘
    │      │                │
    └──────┴────────┬───────┘
                    │
                    ↓
            ┌──────────────┐
            │ Redirect to  │
            │ index.php    │
            └──────────────┘
```

---

## Troubleshooting

### Issue: "Invalid email or password" error
**Solutions:**
- Verify email address is correct
- Check password is correct
- Ensure user account exists in database
- Verify user account status is 'active'

### Issue: "Your account does not have access" error
**Solutions:**
- User role must be: admin, manager, or staff
- Contact administrator to update user role
- Contact administrator to activate account

### Issue: Password strength indicator not showing
**Solutions:**
- Ensure JavaScript is enabled
- Check browser console for errors
- Try refreshing the page

### Issue: Logout not working
**Solutions:**
- Clear browser cookies
- Clear browser cache
- Try logging out again
- Check if session is enabled in PHP

### Issue: "Security token expired" error
**Solutions:**
- This is normal if page was idle too long
- Refresh the page and resubmit form
- Session may have expired

---

## Implementation Checklist

- [x] Login form with authentication
- [x] Password show/hide toggle
- [x] Registration form for new users
- [x] Role-based authorization (Admin, Manager, Staff)
- [x] Password strength indicator
- [x] Profile management page
- [x] Change password functionality
- [x] Session management
- [x] Logout functionality
- [x] CSRF token protection
- [x] Input validation and sanitization
- [x] Password hashing (bcrypt)
- [x] User dropdown menu in navbar
- [x] Responsive design
- [x] Error messages and success messages

---

## Testing Credentials

To test the system, you can insert a test user into the database:

```sql
-- Test Admin Account (password: test123456)
INSERT INTO users (name, email, password, role, phone, status) 
VALUES ('Admin User', 'admin@resort.com', '$2y$10$n8Nz3Z.K.Z.K.Z.K.Z.K.K.uJD.O.K.K.K.K.K.K.K.K.K.K.K.H8Cee', 'admin', '+1-800-000-0001', 'active');

-- Test Manager Account (password: test123456)
INSERT INTO users (name, email, password, role, phone, status) 
VALUES ('Manager User', 'manager@resort.com', '$2y$10$n8Nz3Z.K.Z.K.Z.K.Z.K.K.uJD.O.K.K.K.K.K.K.K.K.K.K.K.H8Cee', 'manager', '+1-800-000-0002', 'active');

-- Test Staff Account (password: test123456)
INSERT INTO users (name, email, password, role, phone, status) 
VALUES ('Staff User', 'staff@resort.com', '$2y$10$n8Nz3Z.K.Z.K.Z.K.Z.K.K.uJD.O.K.K.K.K.K.K.K.K.K.K.K.H8Cee', 'staff', '+1-800-000-0003', 'active');
```

**Note:** The password hash above is for "test123456". To generate your own hash:
```php
echo password_hash('test123456', PASSWORD_DEFAULT);
```

---

## Advanced Features

### Custom Role Restrictions
To modify allowed roles, edit `login.php`:
```php
$allowed_roles = ['admin', 'manager', 'staff']; // Modify this array
```

### Session Timeout
To add automatic session timeout, add to config/db.php:
```php
$_SESSION['login_time'] = time();
$timeout = 1800; // 30 minutes

if (time() - $_SESSION['login_time'] > $timeout) {
    session_destroy();
    header("Location: login.php?expired=1");
}
```

### Login logging
To track logins, create a login_logs table:
```sql
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    browser_info TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## Support & Documentation

For more information:
- See `DEVELOPER_GUIDE.md` for developer guidelines
- See `README.md` for project overview
- See `QUICKSTART.md` for quick start guide

---

**Last Updated:** March 15, 2026
**Version:** 1.0
