-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 31, 2026 at 10:05 PM
-- Server version: 5.7.44-48
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edrppymy_udaanlibrary`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `bg` varchar(100) DEFAULT NULL,
  `text` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent') DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_seats` int(11) DEFAULT '80',
  `occupied_seats` int(11) DEFAULT '0',
  `base_fee` int(11) DEFAULT '1200',
  `ac_extra` int(11) DEFAULT '200',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` varchar(30) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `category` enum('Academic','Self-Help','Fiction','Science','Other') DEFAULT 'Other',
  `copies` int(11) DEFAULT '1',
  `available` int(11) DEFAULT '1',
  `shelf` varchar(50) DEFAULT NULL,
  `emoji` varchar(10) DEFAULT '?',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `category` enum('Utilities','Staff','Maintenance','Supplies','Books','Other') DEFAULT 'Other',
  `expense_date` date DEFAULT NULL,
  `notes` text,
  `emoji` varchar(10) DEFAULT '?',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` varchar(30) NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `type` varchar(100) DEFAULT 'Monthly Fee',
  `amount` int(11) DEFAULT '0',
  `base_fee` int(11) DEFAULT '0',
  `discount` int(11) DEFAULT '0',
  `net_fee` int(11) DEFAULT '0',
  `paid_amt` int(11) DEFAULT '0',
  `balance` int(11) DEFAULT '0',
  `invoice_date` date DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `mode` varchar(100) DEFAULT 'Cash',
  `status` enum('paid','partial') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pdf_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` enum('warning','info','success','error') DEFAULT 'info',
  `title` varchar(255) DEFAULT NULL,
  `msg` text,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `renewals`
--

CREATE TABLE `renewals` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `student_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `months` tinyint(4) NOT NULL DEFAULT '1',
  `new_due_date` date NOT NULL,
  `fee_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_mode` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'cash',
  `notes` text COLLATE utf8_unicode_ci,
  `renewed_by` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT 'OPTMS Tech Study Library',
  `phone` varchar(30) DEFAULT '+91 72820 71620',
  `email` varchar(255) DEFAULT 'admin@optms.co.in',
  `addr` varchar(255) DEFAULT 'Madhepura, Bihar - 852113',
  `fine_per_day` int(11) DEFAULT '5',
  `loan_days` int(11) DEFAULT '14',
  `ac_fee` int(11) DEFAULT '200',
  `wa_number` varchar(30) DEFAULT '917282071620',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('admin','librarian','accountant','receptionist') DEFAULT 'librarian',
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `perm_students` tinyint(1) DEFAULT '1',
  `perm_fees` tinyint(1) DEFAULT '0',
  `perm_books` tinyint(1) DEFAULT '1',
  `perm_expenses` tinyint(1) DEFAULT '0',
  `perm_reports` tinyint(1) DEFAULT '1',
  `perm_staff` tinyint(1) DEFAULT '0',
  `perm_settings` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

CREATE TABLE `staff_attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `att_date` date NOT NULL,
  `status` enum('present','absent','half') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'present',
  `marked_by` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_salary`
--

CREATE TABLE `staff_salary` (
  `staff_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `base_monthly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` varchar(30) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT '',
  `addr` varchar(255) DEFAULT '',
  `batch_id` varchar(30) DEFAULT NULL,
  `seat_type` enum('ac','non-ac') DEFAULT 'non-ac',
  `seat` varchar(20) DEFAULT NULL,
  `base_fee` int(11) DEFAULT '0',
  `discount_type` enum('none','flat','percent') DEFAULT 'none',
  `discount_value` decimal(10,2) DEFAULT '0.00',
  `discount_reason` varchar(255) DEFAULT NULL,
  `net_fee` int(11) DEFAULT '0',
  `paid_amt` int(11) DEFAULT '0',
  `fee_status` enum('paid','partial','pending','overdue') DEFAULT 'pending',
  `paid_on` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#4a7c6f',
  `join_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` varchar(30) NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `book_id` varchar(30) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `fine` int(11) DEFAULT '0',
  `status` enum('issued','returned','overdue') DEFAULT 'issued',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `wa_send_log`
--

CREATE TABLE `wa_send_log` (
  `id` int(11) NOT NULL,
  `sent_to` varchar(255) DEFAULT NULL,
  `preview` text,
  `type` enum('single','bulk') DEFAULT 'single',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_student` (`student_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `renewals`
--
ALTER TABLE `renewals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_renewals_student` (`student_id`),
  ADD KEY `idx_renewals_date` (`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_att` (`staff_id`,`att_date`),
  ADD KEY `idx_sa_date` (`att_date`);

--
-- Indexes for table `staff_salary`
--
ALTER TABLE `staff_salary`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batch` (`batch_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_book` (`book_id`);

--
-- Indexes for table `wa_send_log`
--
ALTER TABLE `wa_send_log`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wa_send_log`
--
ALTER TABLE `wa_send_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
