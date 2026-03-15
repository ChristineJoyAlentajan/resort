// Resort Management System - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and popovers
    initializeBootstrapComponents();
    
    // Add form validation
    addFormValidation();
    
    // Add date picker functionality
    addDatePickerFunctionality();
    
    // Add table search functionality
    addTableSearchFunctionality();
});

// Initialize Bootstrap Components
function initializeBootstrapComponents() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Form Validation
function addFormValidation() {
    var forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Check required fields
            var requiredFields = form.querySelectorAll('[required]');
            var isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    showNotification('Please fill all required fields', 'warning');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Remove invalid class when user starts typing
        var inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
}

// Add Date Picker Functionality
function addDatePickerFunctionality() {
    var dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(function(input) {
        // Set minimum date to today for booking dates
        if (input.name.includes('check_in_date') || input.name.includes('check_out_date')) {
            var today = new Date().toISOString().split('T')[0];
            if (!input.getAttribute('min')) {
                input.setAttribute('min', today);
            }
        }
    });
}

// Add Table Search Functionality
function addTableSearchFunctionality() {
    // Create search input above tables if needed
    var tables = document.querySelectorAll('.table');
    
    tables.forEach(function(table) {
        var searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control mb-3';
        searchInput.placeholder = 'Search table...';
        
        table.parentElement.insertBefore(searchInput, table);
        
        searchInput.addEventListener('keyup', function() {
            var filter = this.value.toUpperCase();
            var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var text = rows[i].textContent.toUpperCase();
                rows[i].style.display = text.indexOf(filter) > -1 ? '' : 'none';
            }
        });
    });
}

// Notification/Alert System
function showNotification(message, type = 'info') {
    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    
    var container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            alertDiv.remove();
        }, 5000);
    }
}

// Format Currency
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value);
}

// Format Date
function formatDate(dateString) {
    var options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Delete confirmation handler
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Calculate number of nights
function calculateNights() {
    var checkInElem = document.querySelector('input[name="check_in_date"]');
    var checkOutElem = document.querySelector('input[name="check_out_date"]');
    
    if (checkInElem && checkOutElem) {
        checkInElem.addEventListener('change', calculatePrice);
        checkOutElem.addEventListener('change', calculatePrice);
    }
}

// Calculate booking price
function calculatePrice() {
    var checkInElem = document.querySelector('input[name="check_in_date"]');
    var checkOutElem = document.querySelector('input[name="check_out_date"]');
    var roomElem = document.querySelector('select[name="room_id"]');
    
    if (checkInElem && checkOutElem && checkInElem.value && checkOutElem.value) {
        var checkIn = new Date(checkInElem.value);
        var checkOut = new Date(checkOutElem.value);
        
        if (checkOut > checkIn) {
            var nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            
            // Get room price from selected option
            if (roomElem && roomElem.value) {
                var selectedOption = roomElem.querySelector('option[value="' + roomElem.value + '"]');
                if (selectedOption) {
                    var priceText = selectedOption.text;
                    var priceMatch = priceText.match(/\$(\d+\.?\d*)/);
                    
                    if (priceMatch) {
                        var nightlyPrice = parseFloat(priceMatch[1]);
                        var totalPrice = nightlyPrice * nights;
                        
                        showNotification('Total Price: ' + formatCurrency(totalPrice) + ' (' + nights + ' nights)', 'info');
                    }
                }
            }
        } else {
            showNotification('Check-out date must be after check-in date', 'warning');
        }
    }
}

// Export table to CSV
function exportTableToCSV(tableName, filename = 'export.csv') {
    var csv = [];
    var rows = document.querySelectorAll('table tbody tr');
    
    // Get headers
    var headers = document.querySelectorAll('table thead th');
    var headerArray = [];
    headers.forEach(function(header) {
        headerArray.push(header.textContent.trim());
    });
    csv.push(headerArray.join(','));
    
    // Get rows
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        var rowArray = [];
        cells.forEach(function(cell, index) {
            // Skip action buttons column
            if (index < cells.length - 1) {
                rowArray.push('"' + cell.textContent.trim() + '"');
            }
        });
        csv.push(rowArray.join(','));
    });
    
    // Create and download file
    var csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    var link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', filename);
    link.click();
}

// Print table
function printTable() {
    window.print();
}

// Logout confirmation
function confirmLogout() {
    return confirm('Are you sure you want to logout?');
}

// Initialize on page load
window.addEventListener('load', function() {
    calculateNights();
});
