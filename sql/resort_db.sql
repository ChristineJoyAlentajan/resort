-- Resort Management System Database Schema
-- Created: March 12, 2026

CREATE DATABASE IF NOT EXISTS resort_management;
USE resort_management;

-- Users Table (Admin/Staff)
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

-- Rooms Table
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    room_type ENUM('single', 'double', 'suite', 'deluxe', 'villa') NOT NULL,
    capacity INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    description TEXT,
    amenities JSON,
    floor INT,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Guests Table
CREATE TABLE guests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    country VARCHAR(100),
    id_type VARCHAR(50),
    id_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bookings Table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_number VARCHAR(50) UNIQUE NOT NULL,
    guest_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    number_of_guests INT NOT NULL,
    total_price DECIMAL(10, 2),
    status ENUM('confirmed', 'checked-in', 'checked-out', 'cancelled') DEFAULT 'confirmed',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Services Table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Booking Services (Many-to-Many)
CREATE TABLE booking_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Payments Table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'online_transfer') DEFAULT 'cash',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Room Maintenance Log
CREATE TABLE maintenance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    maintenance_type VARCHAR(100),
    description TEXT,
    start_date DATETIME,
    end_date DATETIME,
    assigned_to INT,
    status ENUM('pending', 'in-progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Create Indexes for better query performance
CREATE INDEX idx_booking_guest ON bookings(guest_id);
CREATE INDEX idx_booking_room ON bookings(room_id);
CREATE INDEX idx_booking_status ON bookings(status);
CREATE INDEX idx_booking_checkin ON bookings(check_in_date);
CREATE INDEX idx_room_status ON rooms(status);
CREATE INDEX idx_guest_email ON guests(email);
CREATE INDEX idx_payment_booking ON payments(booking_id);

-- Sample Data
INSERT INTO rooms (room_number, room_type, capacity, price_per_night, description, floor, status) VALUES
('101', 'single', 1, 50.00, 'Single occupancy room with city view', 1, 'available'),
('102', 'double', 2, 75.00, 'Double bed room with balcony', 1, 'available'),
('201', 'suite', 4, 150.00, 'Luxury suite with living area', 2, 'available'),
('202', 'deluxe', 2, 100.00, 'Deluxe room with premium amenities', 2, 'occupied'),
('301', 'villa', 6, 250.00, 'Private villa with pool and garden', 3, 'available');

INSERT INTO services (name, description, price, status) VALUES
('Room Service', 'In-room dining service', 15.00, 'available'),
('Spa Treatment', 'Full body massage and spa', 80.00, 'available'),
('Airport Transfer', 'Transportation to/from airport', 25.00, 'available'),
('Laundry Service', 'Professional laundry and pressing', 10.00, 'available'),
('Tour Arrangement', 'Local tour and sightseeing', 45.00, 'available');

INSERT INTO guests (first_name, last_name, email, phone, country) VALUES
('John', 'Doe', 'john@example.com', '5551234567', 'USA'),
('Jane', 'Smith', 'jane@example.com', '5559876543', 'Canada');
