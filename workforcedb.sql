-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2026 at 11:17 AM
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
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `date`, `time_in`, `time_out`) VALUES
(2, 3, '2026-03-20', '18:16:25', '18:16:31');

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
  `email` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_details`
--

INSERT INTO `employee_details` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `email`, `gender`, `birth_date`, `mobile_number`, `address`, `join_date`, `position`, `role`, `profile_image`) VALUES
(1, 12, 'Christian Meynard', 'Balbada', 'Samonte', 'meynardxt24@gmail.com', 'Male', '2005-02-24', '+639273262233', 'Block 6, Lot 21, Ipil St. Hillcrest Village, Caloocan City', '2026-03-20', 'Admin', 'Admin', NULL),
(2, 2, 'Gerardo', 'Flores', 'Loquinario', 'staff123@gmail.com', 'Male', '0000-00-00', '', '', NULL, 'HR Staff', 'HR Staff', NULL),
(3, 3, 'Jairus', '', 'Fernandez', 'employee1@gmail.com', 'Male', '0000-00-00', '', '', NULL, '', 'Employee', NULL),
(4, 4, 'Karl Christian', 'Azucena', 'Telan', 'Karltitilan@gmail.com', '', '0000-00-00', '', '', NULL, 'Employee', 'Employee', NULL);

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
(1, 3, 'Attendance Issue', 'ako to', 'boang', 'Pending', '2026-03-14 23:07:14'),
(2, 3, 'Attendance Issue', '5', 'papalit', 'Pending', '2026-03-14 23:16:39'),
(3, 3, 'Attendance Issue', '5', 'papalit', 'Reviewed', '2026-03-14 23:16:49'),
(4, 3, 'Attendance Issue', '5', 'papalit', 'Reviewed', '2026-03-14 23:17:17'),
(5, 3, 'Attendance Issue', '5', 'papalit', 'Reviewed', '2026-03-14 23:24:29'),
(6, 4, 'Other', 'qwe', 'asd', 'Reviewed', '2026-03-15 01:31:23');

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
(6, 3, 'Emergency Leave', '2026-03-20', '2026-03-22', 'Emergency', 'Pending', '2026-03-16 18:26:08', NULL);

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
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`payroll_id`, `user_id`, `payroll_period`, `days_worked`, `daily_rate`, `gross_salary`, `deductions`, `net_salary`, `status`, `date_created`) VALUES
(1, 3, 'March 1 - March 15, 2026', 10, 800.00, 8000.00, 500.00, 7500.00, 'Released', '2026-03-15 01:12:30'),
(2, 3, 'March 16 - March 31, 2026', 11, 800.00, 8800.00, 500.00, 8300.00, 'Pending', '2026-03-15 01:12:30');

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
(4, 'Karl Christian', 'Telan', 'Karltitilan@gmail.com', 'Employee', '$2y$10$E.5RN.VbT5S7KzY7FdWSPurTrz3Fp8QAcXfJgQk2aydWziy6ATqCS', 1),
(5, 'Karl', 'Jai', 'karljai1@gmail.com', 'Employee', '$2y$10$u/W0rywFIeny6Oepb70sROoxHf/mkl1J2vrRUqWhsBZ0KhalPD/XW', 0),
(12, 'Christian Meynard', 'Samonte', 'meynardxt24@gmail.com', 'Admin', '$2y$10$k3/gnaNWVMmLa2Dkoo7vge9ryjOoBs/iu20Ief85V2Cnz473Vu4TC', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_details`
--
ALTER TABLE `employee_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_requests`
--
ALTER TABLE `employee_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
