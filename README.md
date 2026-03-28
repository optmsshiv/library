# OPTMS Tech Library ERP v6 — PHP/MySQL Backend

## File Structure
```
library_erp/
├── index.php          ← Main dashboard (open this in browser)
├── setup.php          ← Run once to create DB tables + seed data
├── database.sql       ← Full schema + seed data (used by setup.php)
├── .htaccess          ← Apache rewrite rules
├── api/
│   └── index.php      ← REST API (handles all AJAX requests)
└── includes/
    └── db.php         ← Database config & PDO connection
```

## Setup Instructions

### 1. Configure Database
Edit `includes/db.php` and `setup.php` — update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_erp');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
```

### 2. Upload to Server
Upload the entire `library_erp/` folder to your web server (Apache/Nginx with PHP 7.4+).

### 3. Run Setup
Visit: `http://yourserver.com/library_erp/setup.php`
This creates all tables and seeds sample data.

### 4. Delete setup.php
After setup succeeds, delete `setup.php` from your server.

### 5. Open Dashboard
Visit: `http://yourserver.com/library_erp/`

## Requirements
- PHP 7.4+ with PDO and PDO_MySQL extensions
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (or Nginx with rewrite rules)

## All Features (Fully Working)
✅ Dashboard with live stats, batch occupancy, expense tracker  
✅ Student enrollment with discount system  
✅ Batch management (add/edit/delete)  
✅ Seat allocation with fee-status color coding  
✅ Attendance (mark/save by date per student)  
✅ Books catalog (add/delete, ISBN, shelf)  
✅ Book issue & return with auto fine calculation  
✅ Fee collection (Cash/UPI/NEFT/Split modes)  
✅ Invoice generation & print  
✅ Expense tracking  
✅ WhatsApp messaging (opens wa.me links with pre-filled messages)  
✅ Staff management with role-based permissions  
✅ Notifications system  
✅ Reports (Monthly/Fee/Books/Attendance/Expense/Student)  
✅ Analytics dashboard  
✅ Settings (library info, fine rate, loan days)  
