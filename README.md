# Resort Management System

A comprehensive web-based resort management system with full CRUD operations for rooms, bookings, guests, and services management. Built with PHP, Bootstrap 5, and MySQL.

## Features

### Core Functionality
- **Room Management** - Add, edit, delete, and manage rooms with different types and pricing
- **Guest Management** - Maintain guest profiles with contact information and identification details
- **Booking Management** - Create and manage bookings with automatic price calculation
- **Services Management** - Manage resort services (spa, room service, tours, etc.)
- **Dashboard** - Real-time statistics and recent activity overview
- **Payment Tracking** - Track booking payment status

### Room Types
- Single
- Double
- Suite
- Deluxe
- Villa

### Key Features
- Responsive Bootstrap 5 design
- Clean and intuitive user interface
- Real-time data validation
- Search functionality in tables
- Status tracking for rooms and bookings
- Multiple payment status options
- Special requests for bookings
- Room maintenance tracking
- Service management with pricing

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (Apache, Nginx, etc.)
- Modern web browser

## Installation

### Step 1: Set Up Database

1. Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)
2. Execute the SQL script located at `sql/resort_db.sql`

```bash
mysql -u root -p < sql/resort_db.sql
```

Or in phpMyAdmin:
1. Create a new database named `resort_management`
2. Import the `sql/resort_db.sql` file

### Step 2: Configure Database Connection

Edit `config/db.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'resort_management');
```

### Step 3: Deploy Files

1. Copy all project files to your web server directory
2. Ensure proper file permissions (typically 755 for directories, 644 for files)

### Step 4: Access the Application

Open your web browser and navigate to:
```
http://localhost/resort/
```

## Project Structure

```
resort/
├── index.php              # Dashboard
├── rooms.php              # Room management
├── guests.php             # Guest management
├── bookings.php           # Booking management
├── services.php           # Service management
├── config/
│   └── db.php             # Database configuration
├── views/
│   ├── header.php         # Navigation header
│   └── footer.php         # Footer template
├── sql/
│   └── resort_db.sql      # Database schema
├── css/
│   └── style.css          # Custom styles
└── js/
    └── main.js            # JavaScript functionality
```

## Database Schema

### Tables

1. **users** - Admin and staff accounts
2. **rooms** - Room information and availability
3. **guests** - Guest profile information
4. **bookings** - Reservation records
5. **services** - Resort services and pricing
6. **booking_services** - Service orders for bookings
7. **payments** - Payment records
8. **maintenance_logs** - Room maintenance tracking

## Usage

### Adding a New Room

1. Navigate to **Rooms** menu
2. Click **Add New Room** button
3. Fill in room details:
   - Room Number (unique identifier)
   - Room Type (single, double, suite, deluxe, villa)
   - Capacity
   - Price per night
   - Floor number
   - Status (available, occupied, maintenance)
4. Click **Add Room**

### Creating a Booking

1. Navigate to **Bookings** menu
2. Click **New Booking** button
3. Select guest from dropdown (or add new guest first in Guests section)
4. Select available room
5. Set check-in and check-out dates
6. Enter number of guests
7. Set booking and payment status
8. Add special requests if needed
9. Click **Create Booking**

### Managing Guests

1. Navigate to **Guests** menu
2. To add: Click **Add New Guest**, fill in details
3. To edit: Click **Edit** button on guest row
4. To delete: Click **Delete** button, confirm deletion

### Managing Services

1. Navigate to **Services** menu
2. View services as cards
3. Click **Edit** to modify service details
4. Services are automatically available for booking add-ons

## Database Credentials (Default)

The default database configuration in `config/db.php`:

```
Host: localhost
User: root
Password: (empty)
Database: resort_management
```

**Important**: Change these credentials in production!

## API Endpoints

The system uses direct PHP file-based routing:

- `index.php` - Dashboard
- `rooms.php?action=list|add|edit&id=X` - Room management
- `guests.php?action=list|add|edit&id=X` - Guest management
- `bookings.php?action=list|add|edit&id=X` - Booking management
- `services.php?action=list|add|edit&id=X` - Service management

## Features in Detail

### Dashboard
- Total rooms count
- Available rooms count
- Total guests
- Total bookings
- Recent bookings list
- Upcoming check-ins

### Room Management
- Add/Edit/Delete rooms
- Track room status (available, occupied, maintenance)
- Room pricing management
- Room type classification

### Guest Management
- Add/Edit/Delete guests
- ID verification storage
- Contact information
- Address tracking

### Booking Management
- Create bookings with automatic price calculation
- Track booking status
- Payment status tracking
- Special requests management
- Multiple check-in/check-out management

### Service Management
- Add/Edit/Delete services
- Service pricing
- Availability status
- Service descriptions

## Security Best Practices

1. **SQL Injection Prevention**: Uses `mysqli::real_escape_string()`
2. **Input Validation**: All inputs are sanitized
3. **CSRF Protection**: Token generation ready (use in production)
4. **Error Handling**: Try-catch blocks for database operations
5. **Session Management**: Session-based state management

## Troubleshooting

### Database Connection Error
- Verify MySQL server is running
- Check database credentials in `config/db.php`
- Ensure database `resort_management` exists
- Check user permissions

### Tables Not Displaying
- Import SQL schema from `sql/resort_db.sql`
- Check database user has SELECT permissions
- Verify table names match in queries

### Styling Issues
- Ensure Bootstrap CDN is accessible
- Check CSS file path: `css/style.css`
- Clear browser cache

### JavaScript Not Working
- Verify jQuery and Bootstrap JS are loaded
- Check `js/main.js` file path
- Check browser console for errors

## Future Enhancements

- User authentication and authorization
- Email notifications for bookings
- Payment gateway integration
- Reporting and analytics
- Mobile app
- Advanced search filters
- Multi-language support
- SMS notifications
- Room rate management
- Inventory management

## License

This project is provided as-is for educational and commercial use.

## Support

For bugs and feature requests, create an issue or contact the development team.

## Sample Data

The database comes with sample data:

**Rooms:**
- Room 101: Single, $50/night
- Room 102: Double, $75/night
- Room 201: Suite, $150/night
- Room 202: Deluxe, $100/night
- Room 301: Villa, $250/night

**Services:**
- Room Service
- Spa Treatment
- Airport Transfer
- Laundry Service
- Tour Arrangement

**Guests:**
- John Doe
- Jane Smith

You can use this data to test the system before adding your own.

## Version

Resort Management System v1.0
Released: March 12, 2026

---

Happy Resort Management!
