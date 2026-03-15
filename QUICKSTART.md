# Quick Start Guide - Resort Management System

## Installation in 5 Minutes

### Prerequisites
- XAMPP/WAMP/LAMP stack installed with PHP 7.4+
- MySQL running
- Web browser

### Step 1: Place Files
Copy the entire `resort` folder to your web server directory:
- **XAMPP**: `C:\xampp\htdocs\resort\`
- **WAMP**: `C:\wamp64\www\resort\`
- **Linux**: `/var/www/html/resort/`

### Step 2: Create Database
Open phpMyAdmin: `http://localhost/phpmyadmin`

1. Click **Databases**
2. Create database named: `resort_management`
3. Go to **Import** tab
4. Upload file: `sql/resort_db.sql`
5. Click **Go**

### Step 3: Update Database Config
Edit `config/db.php` if using different credentials:

```php
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');               // Your MySQL password
define('DB_NAME', 'resort_management');
```

### Step 4: Access Application
Open browser and go to:
```
http://localhost/resort/
```

You should see the Dashboard!

## Quick Tutorial

### 1. Add Your First Guest
1. Click **Guests** in navigation
2. Click **Add New Guest**
3. Fill in: First Name, Last Name, Country
4. Click **Add Guest**

### 2. Add a Room
1. Click **Rooms** in navigation
2. Click **Add New Room**
3. Fill in:
   - Room Number: 101
   - Type: Single
   - Capacity: 1
   - Price: 50
   - Floor: 1
4. Click **Add Room**

### 3. Create a Booking
1. Click **Bookings** in navigation
2. Click **New Booking**
3. Select Guest: Choose the guest you just added
4. Room: Choose Room 101
5. Check-in: Tomorrow's date
6. Check-out: Day after tomorrow
7. Guests: 1
8. Click **Create Booking**

### 4. View Dashboard
Go back to home, you should see:
- 1 Total Room
- 0 Available Rooms (occupied by your booking)
- 1 Guest
- 1 Booking

## Default Database Content

The system comes with sample data:
- 5 rooms ready to use
- 5 services (spa, room service, airport transfer, etc.)
- 2 sample guests

## Main Features Overview

| Section | What You Can Do |
|---------|-----------------|
| Dashboard | View stats, recent bookings, upcoming check-ins |
| Rooms | Add/Edit/Delete rooms, track availability |
| Guests | Manage guest profiles and contact info |
| Bookings | Create/manage reservations, track payments |
| Services | Add services and manage pricing |

## Common Tasks

### Change Room Status
1. Go to **Rooms**
2. Click **Edit** on a room
3. Change Status dropdown
4. Click **Update Room**

### Update Payment Status
1. Go to **Bookings**
2. Click **Edit** on booking
3. Change Payment Status
4. Click **Update Booking**

### Search Data
- Tables have search box at top
- Type to filter results instantly

## Troubleshooting

**Error: "Connection failed"**
- Check MySQL is running
- Verify credentials in `config/db.php`
- Database name should be `resort_management`

**Blank page or 404 errors**
- Check folder name is `resort`
- Ensure PHP is enabled
- Check web server is running

**No sample data visible**
- Re-import `sql/resort_db.sql`
- Make sure database was created and selected

## File Permissions (Linux/Mac)

If you get permission errors:
```bash
chmod 755 /var/www/html/resort/
chmod 755 /var/www/html/resort/config/
chmod 755 /var/www/html/resort/views/
```

## Important Notes

1. **Default Credentials**: System doesn't require login (basic setup)
   - For production, add authentication in future versions

2. **Data Validation**: 
   - Room number must be unique
   - Guest email must be valid format
   - Check-out date must be after check-in

3. **Price Calculation**: 
   - Automatically calculated based on room price × number of nights
   - Updates when you change dates

4. **Sample Data**:
   - 5 rooms (101-301) ranging from $50-$250/night
   - Sample guests: John Doe, Jane Smith
   - 5 services available

## Next Steps

After setup, try:
1. Add more guests
2. Create multiple bookings
3. Add custom services
4. Experiment with different room types
5. Change booking and payment statuses

## Need Help?

Refer to:
- `README.md` - Full documentation
- Database schema: `sql/resort_db.sql`
- Contact: Check project documentation

---

**Your Resort Management System is ready to use!**

Start by adding guests and rooms, then create bookings!
