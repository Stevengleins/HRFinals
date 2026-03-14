-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 10:42 AM
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
  `date_applied` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`leave_id`, `user_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `date_applied`) VALUES
(1, 3, 'Vacation Leave', '2026-03-12', '2026-03-15', 'Boracay', 'Rejected', '2026-03-12 05:42:58'),
(2, 3, 'Sick Leave', '2026-03-14', '2026-03-20', 'Stage 4 cancer', 'Pending', '2026-03-12 07:25:20'),
(3, 3, 'Sick Leave', '2026-03-20', '2026-03-31', 'sakit', 'Pending', '2026-03-13 04:06:33');

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
(11, 'Christian Meynard', 'Samonte', 'tiansamonte24@gmail.com', 'Employee', '$2y$10$lyD/uTstUyMvynyR8TMjiObXOmEy9h/dt4lnsMNpjn1cQIkVCwRXe', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
