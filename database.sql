-- ========================
-- DATABASE
-- ========================

-- ========================
-- SETTINGS
-- ========================
CREATE TABLE settings (
    id INT PRIMARY KEY,
    name VARCHAR(255) DEFAULT 'OPTMS Tech Study Library',
    phone VARCHAR(30) DEFAULT '+91 72820 71620',
    email VARCHAR(255) DEFAULT 'admin@optms.co.in',
    addr VARCHAR(255) DEFAULT 'Madhepura, Bihar - 852113',
    fine_per_day INT DEFAULT 5,
    loan_days INT DEFAULT 14,
    ac_fee INT DEFAULT 200,
    wa_number VARCHAR(30) DEFAULT '917282071620',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = 1;

-- ========================
-- BATCHES
-- ========================
CREATE TABLE batches (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_seats INT DEFAULT 80,
    occupied_seats INT DEFAULT 0,
    base_fee INT DEFAULT 1200,
    ac_extra INT DEFAULT 200,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- STUDENTS
-- ========================
CREATE TABLE students (
    id VARCHAR(30) PRIMARY KEY,
    fname VARCHAR(100) NOT NULL,
    lname VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    batch_id VARCHAR(30),
    seat_type ENUM('ac', 'non-ac') DEFAULT 'non-ac',
    seat VARCHAR(20),
    base_fee INT DEFAULT 0,
    discount_type ENUM('none', 'flat', 'percent') DEFAULT 'none',
    discount_value DECIMAL(10, 2) DEFAULT 0,
    discount_reason VARCHAR(255),
    net_fee INT DEFAULT 0,
    paid_amt INT DEFAULT 0,
    fee_status ENUM(
        'paid',
        'partial',
        'pending',
        'overdue'
    ) DEFAULT 'pending',
    paid_on DATE,
    due_date DATE,
    course VARCHAR(100),
    color VARCHAR(20) DEFAULT '#4a7c6f',
    join_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batch (batch_id),
    FOREIGN KEY (batch_id) REFERENCES batches (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- BOOKS
-- ========================
CREATE TABLE books (
    id VARCHAR(30) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    isbn VARCHAR(50),
    category ENUM(
        'Academic',
        'Self-Help',
        'Fiction',
        'Science',
        'Other'
    ) DEFAULT 'Other',
    copies INT DEFAULT 1,
    available INT DEFAULT 1,
    shelf VARCHAR(50),
    emoji VARCHAR(10) DEFAULT '📘',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- TRANSACTIONS
-- ========================
CREATE TABLE transactions (
    id VARCHAR(30) PRIMARY KEY,
    student_id VARCHAR(30),
    book_id VARCHAR(30),
    issue_date DATE,
    due_date DATE,
    return_date DATE,
    fine INT DEFAULT 0,
    status ENUM(
        'issued',
        'returned',
        'overdue'
    ) DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_book (book_id),
    FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE SET NULL,
    FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- EXPENSES
-- ========================
CREATE TABLE expenses (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    amount INT NOT NULL,
    category ENUM(
        'Utilities',
        'Staff',
        'Maintenance',
        'Supplies',
        'Books',
        'Other'
    ) DEFAULT 'Other',
    expense_date DATE,
    notes TEXT,
    emoji VARCHAR(10) DEFAULT '💸',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- INVOICES
-- ========================
CREATE TABLE invoices (
    id VARCHAR(30) PRIMARY KEY,
    student_id VARCHAR(30),
    type VARCHAR(100) DEFAULT 'Monthly Fee',
    amount INT DEFAULT 0,
    base_fee INT DEFAULT 0,
    discount INT DEFAULT 0,
    net_fee INT DEFAULT 0,
    paid_amt INT DEFAULT 0,
    balance INT DEFAULT 0,
    invoice_date DATE,
    month VARCHAR(20),
    mode VARCHAR(100) DEFAULT 'Cash',
    status ENUM('paid', 'partial') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_invoice_student (student_id),
    FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- ATTENDANCE
-- ========================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30),
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, attendance_date),
    FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- STAFF
-- ========================
CREATE TABLE staff (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role ENUM(
        'admin',
        'librarian',
        'accountant',
        'receptionist'
    ) DEFAULT 'librarian',
    email VARCHAR(255),
    phone VARCHAR(20),
    username VARCHAR(100),
    password_hash VARCHAR(255),
    perm_students TINYINT(1) DEFAULT 1,
    perm_fees TINYINT(1) DEFAULT 0,
    perm_books TINYINT(1) DEFAULT 1,
    perm_expenses TINYINT(1) DEFAULT 0,
    perm_reports TINYINT(1) DEFAULT 1,
    perm_staff TINYINT(1) DEFAULT 0,
    perm_settings TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- NOTIFICATIONS
-- ========================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM(
        'warning',
        'info',
        'success',
        'error'
    ) DEFAULT 'info',
    title VARCHAR(255),
    msg TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- ACTIVITY LOG
-- ========================
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(10),
    bg VARCHAR(100),
    text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- WHATSAPP LOG
-- ========================
CREATE TABLE wa_send_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sent_to VARCHAR(255),
    preview TEXT,
    type ENUM('single', 'bulk') DEFAULT 'single',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ========================
-- DEFAULT ADMIN ACCOUNT
-- ========================
-- Default credentials: username = admin | password = Admin@1234
-- Change the password after first login via the Change Password button (⚙ in sidebar).
-- To generate a new bcrypt hash in PHP:
--   echo password_hash('YourNewPassword', PASSWORD_BCRYPT);

INSERT INTO staff (
    id, name, role, email, phone,
    username, password_hash,
    perm_students, perm_fees, perm_books,
    perm_expenses, perm_reports, perm_staff, perm_settings,
    status
) VALUES (
    'SF-001',
    'Admin',
    'admin',
    'admin@optms.co.in',
    '+91 72820 71620',
    'admin',
    '$2b$12$8vXLjNiJkI4JR4qYMeujG.bkgF9O7zo2iZtgzgH1R6hjgUse5Ka6G',
    1, 1, 1, 1, 1, 1, 1,
    'active'
) ON DUPLICATE KEY UPDATE
    username       = 'admin',
    password_hash  = '$2b$12$8vXLjNiJkI4JR4qYMeujG.bkgF9O7zo2iZtgzgH1R6hjgUse5Ka6G',
    status         = 'active';

-- ========================
-- MIGRATION (if upgrading from older schema without password_hash / status)
-- Run these manually if the columns don't exist yet:
-- ========================
-- ALTER TABLE staff ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) AFTER username;
-- ALTER TABLE staff ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active' AFTER perm_settings;
-- ========================
-- MIGRATION: Fix existing 0000-00-00 date records
-- Run once if you have existing students with bad dates
-- ========================
UPDATE students SET due_date  = DATE_ADD(COALESCE(join_date, CURDATE()), INTERVAL 30 DAY) WHERE due_date  IS NULL OR due_date  = '0000-00-00';
UPDATE students SET join_date = CURDATE() WHERE join_date IS NULL OR join_date = '0000-00-00';
UPDATE students SET paid_on   = NULL WHERE paid_on = '0000-00-00' OR paid_on = '-';
