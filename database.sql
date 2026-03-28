-- OPTMS Tech Library ERP - Database Schema
-- Run this file once to set up the database

CREATE DATABASE IF NOT EXISTS library_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_erp;

-- Settings
CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY DEFAULT 1,
  name VARCHAR(255) DEFAULT 'OPTMS Tech Study Library',
  phone VARCHAR(30) DEFAULT '+91 72820 71620',
  email VARCHAR(255) DEFAULT 'admin@optms.co.in',
  addr TEXT DEFAULT 'Madhepura, Bihar - 852113',
  fine_per_day INT DEFAULT 5,
  loan_days INT DEFAULT 14,
  ac_fee INT DEFAULT 200,
  wa_number VARCHAR(30) DEFAULT '917282071620',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT IGNORE INTO settings (id) VALUES (1);

-- Batches
CREATE TABLE IF NOT EXISTS batches (
  id VARCHAR(30) PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  total_seats INT DEFAULT 80,
  occupied_seats INT DEFAULT 0,
  base_fee INT DEFAULT 1200,
  ac_extra INT DEFAULT 200,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students
CREATE TABLE IF NOT EXISTS students (
  id VARCHAR(30) PRIMARY KEY,
  fname VARCHAR(100) NOT NULL,
  lname VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  batch_id VARCHAR(30),
  seat_type ENUM('ac','non-ac') DEFAULT 'non-ac',
  seat VARCHAR(20),
  base_fee INT DEFAULT 0,
  discount_type ENUM('none','flat','percent') DEFAULT 'none',
  discount_value DECIMAL(10,2) DEFAULT 0,
  discount_reason VARCHAR(255) DEFAULT '',
  net_fee INT DEFAULT 0,
  paid_amt INT DEFAULT 0,
  fee_status ENUM('paid','partial','pending','overdue') DEFAULT 'pending',
  paid_on VARCHAR(50) DEFAULT '-',
  due_date VARCHAR(50),
  course VARCHAR(100),
  color VARCHAR(20) DEFAULT '#4a7c6f',
  join_date VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL
);

-- Books
CREATE TABLE IF NOT EXISTS books (
  id VARCHAR(30) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255),
  isbn VARCHAR(50),
  category ENUM('Academic','Self-Help','Fiction','Science','Other') DEFAULT 'Other',
  copies INT DEFAULT 1,
  available INT DEFAULT 1,
  shelf VARCHAR(50),
  emoji VARCHAR(10) DEFAULT '📘',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions (book issue/return)
CREATE TABLE IF NOT EXISTS transactions (
  id VARCHAR(30) PRIMARY KEY,
  student_id VARCHAR(30),
  book_id VARCHAR(30),
  issue_date VARCHAR(50),
  due_date VARCHAR(50),
  return_date VARCHAR(50),
  fine INT DEFAULT 0,
  status ENUM('issued','returned','overdue') DEFAULT 'issued',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
);

-- Expenses
CREATE TABLE IF NOT EXISTS expenses (
  id VARCHAR(30) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  amount INT NOT NULL,
  category ENUM('Utilities','Staff','Maintenance','Supplies','Books','Other') DEFAULT 'Other',
  expense_date VARCHAR(50),
  notes TEXT,
  emoji VARCHAR(10) DEFAULT '💸',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices / Fee Payments
CREATE TABLE IF NOT EXISTS invoices (
  id VARCHAR(30) PRIMARY KEY,
  student_id VARCHAR(30),
  type VARCHAR(100) DEFAULT 'Monthly Fee',
  amount INT DEFAULT 0,
  base_fee INT DEFAULT 0,
  discount INT DEFAULT 0,
  net_fee INT DEFAULT 0,
  paid_amt INT DEFAULT 0,
  balance INT DEFAULT 0,
  invoice_date VARCHAR(50),
  month VARCHAR(50),
  mode VARCHAR(100) DEFAULT 'Cash',
  status ENUM('paid','partial') DEFAULT 'paid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
);

-- Attendance
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(30),
  attendance_date DATE NOT NULL,
  status ENUM('present','absent') DEFAULT 'present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_attendance (student_id, attendance_date),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Staff
CREATE TABLE IF NOT EXISTS staff (
  id VARCHAR(30) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  role ENUM('admin','librarian','accountant','receptionist') DEFAULT 'librarian',
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
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('warning','info','success','error') DEFAULT 'info',
  title VARCHAR(255),
  msg TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity Log
CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  icon VARCHAR(10),
  bg VARCHAR(100),
  text TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- WhatsApp Send Log
CREATE TABLE IF NOT EXISTS wa_send_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sent_to VARCHAR(255),
  preview TEXT,
  type ENUM('single','bulk') DEFAULT 'single',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- SEED DATA
-- ========================

INSERT IGNORE INTO batches VALUES
('BT-1','Early Morning','05:00:00','08:00:00',50,45,900,200,NOW()),
('BT-2','Morning','08:00:00','12:00:00',80,45,1200,200,NOW()),
('BT-3','Afternoon','12:00:00','16:00:00',80,32,1200,200,NOW()),
('BT-4','Evening','16:00:00','20:00:00',80,59,1500,200,NOW()),
('BT-5','Night','20:00:00','00:00:00',60,18,1800,200,NOW()),
('BT-6','Late Night','00:00:00','05:00:00',40,40,2000,200,NOW());

INSERT IGNORE INTO students VALUES
('STU-001','Rahul','Kumar','9876543210','BT-2','ac','A-12',1400,'flat',200,'Early Bird',1200,1200,'paid','Mar 5','Apr 1, 2026','UPSC','#4a7c6f','Jan 10, 2026',NOW()),
('STU-002','Priya','Singh','9765432109','BT-4','non-ac','C-07',1500,'none',0,'',1500,0,'pending','-','Mar 15, 2026','BPSC','#c47d2b','Jan 15, 2026',NOW()),
('STU-003','Anjali','Mishra','9654321098','BT-5','non-ac','B-03',1800,'percent',10,'Sibling Discount',1620,1000,'partial','Mar 3','Apr 1, 2026','NEET','#3a7ab0','Feb 1, 2026',NOW()),
('STU-004','Vivek','Tiwari','9543210987','BT-2','ac','A-28',1400,'none',0,'',1400,0,'overdue','-','Mar 1, 2026','JEE','#7c5cbf','Feb 5, 2026',NOW()),
('STU-005','Neha','Kumari','9432109876','BT-4','non-ac','D-11',1500,'none',0,'',1500,1500,'paid','Mar 8','Apr 1, 2026','SSC','#c0444f','Feb 10, 2026',NOW()),
('STU-006','Sunita','Yadav','9321098765','BT-1','non-ac','E-05',900,'flat',100,'Staff Ward',800,500,'partial','Mar 10','Mar 20, 2026','Railways','#3a7d5e','Mar 1, 2026',NOW()),
('STU-007','Amit','Sharma','9210987654','BT-3','ac','F-02',1400,'none',0,'',1400,1400,'paid','Mar 4','Apr 1, 2026','CA','#4a7c6f','Mar 5, 2026',NOW()),
('STU-008','Kavya','Gupta','9109876543','BT-2','non-ac','A-35',1200,'none',0,'',1200,0,'overdue','-','Mar 1, 2026','UPSC','#c47d2b','Mar 10, 2026',NOW());

INSERT IGNORE INTO books VALUES
('BK-001','Wings of Fire','A.P.J. Abdul Kalam','978-81-7371-146-8','Science',5,3,'A-101','📘',NOW()),
('BK-002','Rich Dad Poor Dad','Robert Kiyosaki','978-1-4926-0295-0','Self-Help',4,1,'B-203','📙',NOW()),
('BK-003','Atomic Habits','James Clear','978-0-73521-129-2','Self-Help',3,2,'B-205','📗',NOW()),
('BK-004','The Alchemist','Paulo Coelho','978-0-06-231609-7','Fiction',6,4,'C-301','📕',NOW()),
('BK-005','NCERT Physics XII','NCERT','978-81-7450-487-1','Academic',8,0,'D-401','📔',NOW()),
('BK-006','Indian Polity','M. Laxmikanth','978-93-5260-419-3','Academic',10,6,'D-402','📘',NOW());

INSERT IGNORE INTO transactions VALUES
('TX-001','STU-001','BK-004','Mar 6, 2026','Mar 20, 2026',NULL,0,'issued',NOW()),
('TX-002','STU-002','BK-001','Mar 8, 2026','Mar 22, 2026',NULL,0,'issued',NOW()),
('TX-003','STU-003','BK-005','Mar 1, 2026','Mar 15, 2026',NULL,25,'overdue',NOW()),
('TX-004','STU-004','BK-002','Mar 2, 2026','Mar 16, 2026',NULL,20,'overdue',NOW());

INSERT IGNORE INTO expenses VALUES
('EX-001','Electricity Bill',8400,'Utilities','Mar 18, 2026','','⚡',NOW()),
('EX-002','Staff Salaries',22000,'Staff','Mar 1, 2026','','👨‍💼',NOW()),
('EX-003','AC Maintenance',3500,'Maintenance','Mar 10, 2026','','🔧',NOW()),
('EX-004','Stationery',2100,'Supplies','Mar 12, 2026','','📦',NOW()),
('EX-005','Internet',2800,'Utilities','Mar 5, 2026','','📶',NOW()),
('EX-006','New Books',3500,'Books','Mar 8, 2026','','📚',NOW());

INSERT IGNORE INTO staff VALUES
('SF-001','Admin User','admin','admin@optms.co.in','9999999999','admin',MD5('admin123'),1,1,1,1,1,1,1,'active',NOW());

INSERT IGNORE INTO notifications (type,title,msg,is_read) VALUES
('warning','Fee Overdue','2 students have overdue fees',0),
('info','New Enrollment','Kavya Gupta enrolled today',1),
('success','Fee Collected','Rahul Kumar paid ₹1200',1);
