-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 11, 2026 at 12:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `workforcedb`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT 0.00,
  `regular_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_status` enum('None','Pending','Approved','Rejected') DEFAULT 'None',
  `status` enum('Present','Late','Half-Day','Absent') DEFAULT 'Present',
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `user_id`, `date`, `time_in`, `time_out`, `total_hours`, `regular_hours`, `overtime_hours`, `overtime_status`, `status`, `notes`) VALUES
(6, 3, '2026-04-06', '2026-04-06 08:15:00', '2026-04-06 17:00:00', 8.75, 8.00, 0.00, 'Pending', 'Late', 'Heavy traffic on EDSA'),
(7, 3, '2026-04-07', '2026-04-07 08:30:00', '2026-04-07 17:00:00', 8.50, 8.00, 0.00, 'Pending', 'Late', 'Bus broke down'),
(8, 3, '2026-04-08', '2026-04-08 07:55:00', '2026-04-08 19:00:00', 11.08, 8.00, 3.08, 'Pending', 'Present', 'Stayed late to finish pending system updates'),
(9, 3, '2026-04-09', '2026-04-09 07:50:00', '2026-04-09 19:30:00', 11.66, 8.00, 3.66, 'Approved', 'Present', 'Overtime approved by Admin for deployment'),
(10, 3, '2026-04-10', '2026-04-10 08:00:00', '2026-04-10 17:00:00', 9.00, 8.00, 0.00, 'Pending', 'Present', NULL),
(11, 16, '2026-04-06', '2026-04-06 07:50:00', '2026-04-06 17:00:00', 9.16, 8.00, 0.00, 'None', 'Present', NULL),
(12, 16, '2026-04-07', NULL, NULL, 0.00, 0.00, 0.00, 'None', 'Absent', 'Called in sick - Medical Certificate sent to HR'),
(13, 16, '2026-04-08', '2026-04-08 08:00:00', '2026-04-08 12:00:00', 4.00, 4.00, 0.00, 'None', 'Half-Day', 'Still feeling unwell, went home early'),
(14, 16, '2026-04-09', '2026-04-09 07:55:00', '2026-04-09 17:00:00', 9.08, 8.00, 0.00, 'None', 'Present', 'Fully recovered and back to work'),
(15, 16, '2026-04-10', '2026-04-10 08:05:00', '2026-04-10 17:00:00', 8.91, 8.00, 0.00, 'None', 'Late', 'Slightly late, rain delay'),
(16, 3, '2026-04-11', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0.00, 0.00, 0.00, 'None', 'Present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_details`
--

CREATE TABLE `employee_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `shift_start` time DEFAULT '08:00:00',
  `shift_end` time DEFAULT '17:00:00',
  `role` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `monthly_salary` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_details`
--

INSERT INTO `employee_details` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `gender`, `birth_date`, `mobile_number`, `address`, `join_date`, `position`, `shift_start`, `shift_end`, `role`, `profile_image`, `monthly_salary`) VALUES
(2, 2, 'Gerardo', 'Flores', 'Loquinario', NULL, 'staff123@gmail.com', 'Male', '0000-00-00', '', '', NULL, 'HR Staff', '08:00:00', '17:00:00', 'HR Staff', NULL, 0.00),
(3, 3, 'Jairus', NULL, 'Fernandez', NULL, 'employee1@gmail.com', 'Male', '0000-00-00', '', '', NULL, 'Employee', '08:00:00', '17:00:00', 'Employee', NULL, 0.00),
(5, 16, 'Christian Meynard', 'Balbada', 'Samonte', 'II', 'xtnetomak@gmail.com', 'Male', '2005-02-24', '+639273262233', 'Blk 6, Lot 21, Ipil st. Hillcrest Village, Caloocan City', '2026-04-09', 'Employee', '08:00:00', '17:00:00', 'Employee', NULL, 0.00),
(6, 1, 'Christian Meynard', NULL, 'Samonte', NULL, 'admin123@gmail.com', '', '0000-00-00', '', '', NULL, '', '08:00:00', '17:00:00', 'Admin', 'uploads/profile_images/69d902cedc17f_Screenshot 2024-11-28 181926.png', 0.00),
(7, 17, 'LOQUINARIO', 'Flores', 'GERARDO', 'Jr.', 'gyeqiu159@gmail.com', 'Male', '2005-04-07', '+639342342342', 'Catmon St.', '2026-04-10', 'Employee', '08:00:00', '17:00:00', 'Employee', 'uploads/profile_images/69d9e87405749_Screenshot 2025-04-24 100749.png', 20000.00),
(8, 18, 'Lucas', 'Kas', 'Traumen', NULL, 'lucastraumen50@gmail.com', 'Male', '1999-04-07', '+639232311414', 'Amparo', '2026-04-10', 'Employee', '08:00:00', '17:00:00', 'Employee', 'uploads/profile_images/69d917deba829_Screenshot 2026-01-27 183545.png', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `employee_requests`
--

CREATE TABLE `employee_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_requests`
--

INSERT INTO `employee_requests` (`request_id`, `user_id`, `request_type`, `subject`, `message`, `status`, `date_submitted`) VALUES
(1, 3, 'Attendance Issue', 'ako to', 'boang', 'Reviewed', '2026-03-14 23:07:14'),
(2, 3, 'Attendance Issue', '5', 'papalit', 'Reviewed', '2026-03-14 23:16:39'),
(3, 3, 'Attendance Issue', '5', 'papalit', 'Reviewed', '2026-03-14 23:16:49'),
(4, 3, 'Attendance Issue', '5', 'papalit', 'Reviewed', '2026-03-14 23:17:17'),
(5, 3, 'Attendance Issue', '5', 'papalit', 'Reviewed', '2026-03-14 23:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `user_id` int(255) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `date_applied` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`leave_id`, `user_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `date_applied`, `remarks`) VALUES
(1, 3, 'Vacation Leave', '2026-03-12', '2026-03-15', 'Boracay', 'Rejected', '2026-03-12 05:42:58', NULL),
(2, 3, 'Sick Leave', '2026-03-14', '2026-03-20', 'Stage 4 cancer', 'Rejected', '2026-03-12 07:25:20', NULL),
(3, 3, 'Sick Leave', '2026-03-20', '2026-03-31', 'sakit', 'Rejected', '2026-03-13 04:06:33', 'hindi pwede'),
(6, 3, 'Emergency Leave', '2026-03-20', '2026-03-22', 'Emergency', 'Approved', '2026-03-16 18:26:08', NULL),
(7, 3, 'Vacation Leave', '2026-04-10', '2026-04-21', 'mag Miming puh', 'Rejected', '2026-04-10 13:16:08', 'Need mo mag overtime'),
(8, 3, 'Sick Leave', '2026-04-10', '2026-04-14', 'Cancer sa panga', 'Rejected', '2026-04-10 13:24:42', ''),
(9, 3, 'Paternity/Maternity Leave', '2026-04-10', '2026-04-11', 'Nalabas na ang tubol', 'Approved', '2026-04-10 13:29:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payroll_period` varchar(100) NOT NULL,
  `days_worked` int(11) NOT NULL DEFAULT 0,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gross_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sss_employee_share` decimal(10,2) DEFAULT 0.00,
  `sss_employer_share` decimal(10,2) DEFAULT 0.00,
  `philhealth_employee_share` decimal(10,2) DEFAULT 0.00,
  `philhealth_employer_share` decimal(10,2) DEFAULT 0.00,
  `pagibig_employee_share` decimal(10,2) DEFAULT 0.00,
  `pagibig_employer_share` decimal(10,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `undertime_hours` decimal(5,2) DEFAULT 0.00,
  `undertime_deduction` decimal(10,2) DEFAULT 0.00,
  `late_minutes` int(11) DEFAULT 0,
  `late_deduction` decimal(10,2) DEFAULT 0.00,
  `total_mandatory_deductions` decimal(10,2) DEFAULT 0.00,
  `taxable_income` decimal(10,2) DEFAULT 0.00,
  `withholding_tax` decimal(10,2) DEFAULT 0.00,
  `adjusted_gross_salary` decimal(10,2) DEFAULT 0.00,
  `total_employer_contributions` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'Bank Transfer',
  `remarks` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`payroll_id`, `user_id`, `payroll_period`, `days_worked`, `daily_rate`, `gross_salary`, `sss_employee_share`, `sss_employer_share`, `philhealth_employee_share`, `philhealth_employer_share`, `pagibig_employee_share`, `pagibig_employer_share`, `overtime_hours`, `overtime_pay`, `undertime_hours`, `undertime_deduction`, `late_minutes`, `late_deduction`, `total_mandatory_deductions`, `taxable_income`, `withholding_tax`, `adjusted_gross_salary`, `total_employer_contributions`, `payment_method`, `remarks`, `processed_by`, `processed_date`, `deductions`, `net_salary`, `status`, `date_created`) VALUES
(1, 3, 'March 1 - March 15, 2026', 10, 800.00, 8000.00, 360.00, 760.00, 200.00, 200.00, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 760.00, 7240.00, 0.00, 0.00, 0.00, 'Bank Transfer', NULL, NULL, NULL, 500.00, 7500.00, 'Released', '2026-03-15 01:12:30'),
(2, 3, 'March 16 - March 31, 2026', 11, 800.00, 8800.00, 396.00, 836.00, 220.00, 220.00, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 816.00, 7984.00, 0.00, 0.00, 0.00, 'Bank Transfer', NULL, 1, '2026-04-11 16:08:36', 500.00, 8300.00, 'Released', '2026-03-15 01:12:30'),
(4, 3, '2026-04-10 to 2026-04-11', 1, 1000.00, 1000.00, 45.00, 95.00, 250.00, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 295.00, 705.00, 0.00, 1000.00, 345.00, 'Bank Transfer', NULL, 1, '2026-04-11 16:08:38', 295.00, 705.00, 'Released', '2026-04-10 23:25:46'),
(5, 18, '2026-04-10 to 2026-04-11', 0, 0.00, 500.00, 22.50, 47.50, 250.00, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 272.50, 227.50, 0.00, 500.00, 297.50, 'Bank Transfer', NULL, 1, '2026-04-11 16:23:49', 272.50, 227.50, 'Released', '2026-04-10 23:32:49'),
(9, 16, '2026-04-11 to 2026-04-12', 0, 0.00, 1000.00, 45.00, 95.00, 250.00, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 295.00, 705.00, 0.00, 1000.00, 345.00, 'Bank Transfer', NULL, 1, '2026-04-11 16:08:01', 295.00, 705.00, 'Released', '2026-04-11 14:43:11'),
(10, 17, '2026-03-31 to 2026-04-01', 0, 0.00, 1000.00, 45.00, 95.00, 250.00, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 295.00, 705.00, 0.00, 1000.00, 345.00, 'Bank Transfer', NULL, 1, '2026-04-11 16:23:44', 295.00, 705.00, 'Released', '2026-04-11 14:49:13'),
(14, 3, '2026-04-11 to 2026-04-12', 1, 1000.00, 1000.00, 45.00, 95.00, 250.00, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 295.00, 705.00, 0.00, 1000.00, 345.00, 'Bank Transfer', NULL, 2, NULL, 295.00, 705.00, 'Pending', '2026-04-11 16:53:33'),
(15, 17, '2026-04-11 to 2026-04-12', 0, 0.00, 1000.00, 45.00, 95.00, 250.00, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 295.00, 705.00, 0.00, 1000.00, 345.00, 'Bank Transfer', NULL, 2, NULL, 295.00, 705.00, 'Pending', '2026-04-11 16:57:59'),
(16, 17, '2026-04-11 to 2026-04-12', 0, 0.00, 1000.00, 45.00, 95.00, 25.00, 25.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 70.00, 930.00, 0.00, 1000.00, 120.00, 'Bank Transfer', NULL, 2, NULL, 70.00, 930.00, 'Pending', '2026-04-11 17:08:27'),
(17, 3, '2026-04-01 to 2026-04-15', 6, 2500.00, 15000.00, 675.00, 1425.00, 375.00, 375.00, 200.00, 200.00, 3.66, 779.83, 0.00, 0.00, 60, 170.45, 1250.00, 13750.00, 0.00, 15609.38, 2000.00, 'Bank Transfer', NULL, 1, '2026-04-11 18:09:18', 1420.45, 14188.93, 'Released', '2026-04-11 17:08:52'),
(18, 16, '2026-04-01 to 2026-04-30', 3, 10000.00, 30000.00, 1350.00, 1350.00, 750.00, 750.00, 200.00, 200.00, 0.00, 0.00, 4.00, 681.82, 30, 85.23, 2300.00, 27700.00, 1030.05, 29232.95, 2300.00, 'Bank Transfer', NULL, 1, '2026-04-11 18:09:03', 4097.10, 25135.85, 'Released', '2026-04-11 17:55:26'),
(19, 16, '2026-04-11 to 2026-04-12', 0, 0.00, 1000.00, 45.00, 95.00, 25.00, 25.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 70.00, 930.00, 0.00, 1000.00, 120.00, 'Bank Transfer', NULL, 1, '2026-04-11 18:09:08', 70.00, 930.00, 'Released', '2026-04-11 18:07:15'),
(20, 18, '2026-04-01 to 2026-04-30', 0, 0.00, 30000.00, 1350.00, 1350.00, 750.00, 750.00, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 0, 0.00, 2300.00, 27700.00, 1030.05, 30000.00, 2300.00, 'Bank Transfer', NULL, 2, NULL, 3330.05, 26669.95, 'Pending', '2026-04-11 18:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `first_name`, `last_name`, `email`, `role`, `password`, `status`) VALUES
(1, 'Christian Meynard', 'Samonte', 'admin123@gmail.com', 'Admin', '$2y$10$sWN9oEL3llvoS/EoSKDudeqAHC.k.BK.GjasVVC76DLjRDxwc/kZO', 1),
(2, 'Gerardo', 'Loquinario', 'staff123@gmail.com', 'HR Staff', '$2y$10$UMiFyRXVZzAK7SQNoC9u3usr1xGy/Rhbbs.QwuaNLh/dUJsOpcfHK', 1),
(3, 'Jairus', 'Fernandez', 'employee1@gmail.com', 'Employee', '$2y$10$G/OlgJjgCVlb3EDfBcPbUuDiRBbgAWcKkK3ZbgtKud389LUAs/HSK', 1),
(16, 'Christian Meynard', 'Samonte', 'xtnetomak@gmail.com', 'Employee', '$2y$10$SEXuCnefWQXEVrRU4cZh0udXHSXZzB8qy9k84kVh3GiobP3ShDAeS', 1),
(17, 'LOQUINARIO', 'GERARDO', 'gyeqiu159@gmail.com', 'Employee', '$2y$10$KWSmsqwGdumwA03dkADI.O3/eZ8BDng7E3Ta88fU88vUStebfeVYm', 1),
(18, 'Lucas', 'Traumen', 'lucastraumen50@gmail.com', 'Employee', '$2y$10$5XpzLGj9K1MLnj13mk9b.eQHOtjmsE4itp34JELycbzkQpFNIWZoC', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `employee_details`
--
ALTER TABLE `employee_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employee_requests`
--
ALTER TABLE `employee_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employee_details`
--
ALTER TABLE `employee_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employee_requests`
--
ALTER TABLE `employee_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_details`
--
ALTER TABLE `employee_details`
  ADD CONSTRAINT `employee_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_requests`
--
ALTER TABLE `employee_requests`
  ADD CONSTRAINT `employee_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
