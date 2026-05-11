-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 04:59 PM
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
-- Database: `littlelands_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` int(11) NOT NULL,
  `requested_by` varchar(20) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` varchar(20) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approvals`
--

INSERT INTO `approvals` (`id`, `requested_by`, `action_type`, `target_type`, `target_id`, `reason`, `status`, `reviewed_by`, `review_notes`, `created_at`, `updated_at`) VALUES
(1, '0000-0009', 'register_basic-user', 'user', '0000-0001', 'New basic-user registration', 'approved', '0000-0001', '', '2026-05-10 16:32:01', '2026-05-11 14:34:24'),
(2, '0000-0002', 'block', 'user', '0000-0009', 'needs blocking', 'approved', '0000-0001', '', '2026-05-11 14:36:01', '2026-05-11 14:38:42'),
(3, '1111-1111', 'register_basic-user', 'user', '1111-1111', 'New basic-user registration', 'pending', NULL, NULL, '2026-05-11 14:58:02', '2026-05-11 14:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `playground_id` int(11) NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `group_size` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_cart`
--

CREATE TABLE `booking_cart` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_packages`
--

CREATE TABLE `cart_packages` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `action` enum('login','logout') NOT NULL,
  `log_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `action`, `log_time`) VALUES
(1, '0000-0001', 'login', '2026-05-10 21:44:03'),
(2, '0000-0001', 'logout', '2026-05-10 21:52:06'),
(3, '0000-0001', 'login', '2026-05-10 21:53:44'),
(4, '0000-0001', 'logout', '2026-05-10 21:53:47'),
(5, '0000-0001', 'login', '2026-05-10 22:04:44'),
(6, '0000-0001', 'logout', '2026-05-10 23:45:06'),
(7, '0000-0001', 'login', '2026-05-10 23:45:15'),
(8, '0000-0001', 'login', '2026-05-11 00:21:35'),
(9, '0000-0001', 'logout', '2026-05-11 00:21:43'),
(10, '0000-0001', 'login', '2026-05-11 00:35:33'),
(11, '0000-0001', 'login', '2026-05-11 01:25:01'),
(12, '0000-0001', 'logout', '2026-05-11 01:52:10'),
(13, '0000-0009', 'login', '2026-05-11 01:52:21'),
(14, '0000-0009', 'logout', '2026-05-11 01:52:27'),
(15, '0000-0001', 'login', '2026-05-11 01:55:00'),
(16, '0000-0001', 'logout', '2026-05-11 01:57:03'),
(17, '0000-0001', 'login', '2026-05-11 09:19:36'),
(18, '0000-0001', 'logout', '2026-05-11 09:28:00'),
(19, '0000-0009', 'login', '2026-05-11 09:28:05'),
(20, '0000-0009', 'logout', '2026-05-11 09:28:09'),
(21, '0000-0001', 'login', '2026-05-11 09:28:23'),
(22, '0000-0001', 'logout', '2026-05-11 09:28:28'),
(25, '0000-0001', 'login', '2026-05-11 09:30:30'),
(26, '0000-0001', 'logout', '2026-05-11 09:34:22'),
(27, '0000-0001', 'login', '2026-05-11 09:35:17'),
(28, '0000-0001', 'logout', '2026-05-11 09:38:17'),
(30, '0000-0009', 'login', '2026-05-11 22:31:26'),
(31, '0000-0009', 'logout', '2026-05-11 22:31:30'),
(34, '0000-0001', 'login', '2026-05-11 22:31:51'),
(35, '0000-0001', 'logout', '2026-05-11 22:35:22'),
(36, '0000-0002', 'login', '2026-05-11 22:35:34'),
(37, '0000-0001', 'login', '2026-05-11 22:38:36'),
(38, '0000-0001', 'logout', '2026-05-11 22:38:51'),
(39, '0000-0001', 'login', '2026-05-11 22:39:00'),
(40, '0000-0002', 'logout', '2026-05-11 22:40:09'),
(41, '0000-0009', 'login', '2026-05-11 22:40:23');

-- --------------------------------------------------------

--
-- Table structure for table `package_orders`
--

CREATE TABLE `package_orders` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `playground_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','preparing','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_order_items`
--

CREATE TABLE `package_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otp`
--

CREATE TABLE `password_reset_otp` (
  `id` int(11) NOT NULL,
  `user_id` varchar(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resend_count` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_resend_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_otp`
--

INSERT INTO `password_reset_otp` (`id`, `user_id`, `otp_code`, `expires_at`, `used`, `created_at`, `resend_count`, `ip_address`, `last_resend_at`) VALUES
(2, '0000-0000', '424397', '2026-05-10 16:15:09', 1, '2026-05-10 14:00:09', 2, '::1', NULL),
(3, '0000-0000', '970434', '2026-05-10 16:16:49', 1, '2026-05-10 14:01:49', 1, '::1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `playgrounds`
--

CREATE TABLE `playgrounds` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `playground_type` varchar(100) DEFAULT 'General',
  `capacity` int(11) DEFAULT 100,
  `opening_time` time DEFAULT '08:00:00',
  `closing_time` time DEFAULT '20:00:00',
  `price_range` enum('$','$$','$$$','$$$$') DEFAULT '$$',
  `rating` decimal(2,1) DEFAULT 0.0,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playgrounds`
--

INSERT INTO `playgrounds` (`id`, `name`, `description`, `address`, `phone`, `playground_type`, `capacity`, `opening_time`, `closing_time`, `price_range`, `rating`, `image_path`, `is_active`, `created_at`) VALUES
(1, 'KidZania', 'Indoor type of kids only.', 'Purok 4, Barangay 9, Cabadbaran City', NULL, 'Indoor', 50, '09:00:00', '17:00:00', '$$', 0.0, '', 1, '2026-05-11 01:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `play_areas`
--

CREATE TABLE `play_areas` (
  `id` int(11) NOT NULL,
  `playground_id` int(11) NOT NULL,
  `area_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 20,
  `location_type` enum('indoor','outdoor','private_room') NOT NULL DEFAULT 'indoor',
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `play_packages`
--

CREATE TABLE `play_packages` (
  `id` int(11) NOT NULL,
  `playground_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `image_path` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleInitial` varchar(1) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `sex` enum('male','female') NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) NOT NULL,
  `purok` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `province` varchar(50) NOT NULL,
  `zipCode` varchar(10) NOT NULL,
  `country` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `secure_question` varchar(100) DEFAULT NULL,
  `secure_answer` varchar(255) DEFAULT NULL,
  `secure_question2` varchar(100) DEFAULT NULL,
  `secure_answer2` varchar(255) DEFAULT NULL,
  `secure_question3` varchar(100) DEFAULT NULL,
  `secure_answer3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('basic-user','admin','superadmin') NOT NULL DEFAULT 'basic-user',
  `status` varchar(20) DEFAULT 'registered',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstName`, `lastName`, `middleInitial`, `extension`, `sex`, `birthdate`, `age`, `purok`, `barangay`, `city`, `province`, `zipCode`, `country`, `username`, `email`, `password`, `secure_question`, `secure_answer`, `secure_question2`, `secure_answer2`, `secure_question3`, `secure_answer3`, `created_at`, `role`, `status`, `is_blocked`) VALUES
('0000-0001', 'Sofia', 'Gentle', 'L', '', 'female', '1991-01-01', 35, 'Purok 5', 'Baranggay 6', 'City of Cabadbaran', 'Agusan Del Norte', '8605', 'Philippines', 'sofia21', 'sofia@gmail.com', '$2y$10$wUvm2TPEMDp18g2vvqwGhOTM1AHKrd4QU0tWB1/xARV2Ndb7amMgO', 'Who is your bestfriend in elementary?', '$2y$10$3LloJ36C.1iqFkYWm9Uzo.vXqUl1QKUwkonYl7Z.4lPRVnq6kwcYe', 'What is your favorite food?', '$2y$10$1m0OaTO8mw4BcyXHVWXjteWT6LdU63VjwwAsvGajs0XkoNSzLROwK', 'What is the name of your first pet?', '$2y$10$KxRhXJZhIjYnYZsPrMV.Q.vmlmAvSOK5RuAmUd2.hmScBaFB8QVo.', '2026-05-10 13:43:07', 'superadmin', 'registered', 0),
('0000-0002', 'Ken', 'Sindy', '', '', 'male', '2003-02-02', 23, 'Purok 5', 'Baranggay 6', 'City of Cabadbaran', 'Agusan Del Norte', '8605', 'Philippines', 'ken21', 'kenn@gmail.com', '$2y$10$5qoqVc7RZEiOMLeEF9Ts8uRpkY8icgd06n7x.J5f3OEVYjaKKZVBC', '', '$2y$10$4Qd1G7uMd.Srg7jErbPZJ.H7wX/E3urkz3Z9QgZw6nU7UyVG6byD6', '', '$2y$10$X8S7MSepRrR14e2nUU8kJeD7Q5mWNyS1iMTyc5C66ljKEVEh9b34q', '', '$2y$10$Ot8qZdIZP95rLTONID5jmOmTBjL8gnOO8/PMLOT88HH9w11T6rh.u', '2026-05-11 14:32:59', 'admin', 'registered', 0),
('0000-0009', 'Maria', 'Cruz', '', '', 'female', '2004-02-02', 22, 'Purok 5', 'Baranggay 6', 'City of Cabadbaran', 'Agusan Del Norte', '8605', 'Philippines', 'maria21', 'maria@gmail.com', '$2y$10$rO6cUC/Qx6JO43iep0Qoye747ghHPWClLv3RDsCR4ikx8wBSimskS', 'What is the name of your pet?', '$2y$10$9EH1QJTElxfbRSzdLFk8TuZZwvXA.5yJ7xV1Okx5O4Aa6WJ.Ycc36', 'What is your favorite food?', '$2y$10$F3Xfyjn35oV/VD7Vq3RO.Oez1SNB93A.EhUlFTKOEudp/K1Yuz6tS', 'What is the name of your first pet?', '$2y$10$S14eNZuqgpRWFJtMk/qVPeuSXKCO6FUF9NMblxFtdJV9RTFDtqCua', '2026-05-10 16:32:01', 'basic-user', 'registered', 0),
('1111-1111', 'Jero', 'Herd', '', '', 'male', '2003-02-02', 23, 'Purok 5', 'Baranggay 6', 'City of Cabadbaran', 'Agusan Del Norte', '8605', 'Philippines', 'jero21', 'jero@gmail.com', '$2y$10$dEJ9.fB6PR5RjlmTUK0M.e5f3n8dsGln4rUzUZILo1ucOrAGXHbxO', 'What is the name of your pet?', '$2y$10$oLpV8pbFsWn2kP9fcGNxNOAAq51S1YtWiM1lW41E90XsMji4a0qdS', 'What elementary school did you attend?', '$2y$10$YIScwM8LBA6t8oAh3s7k1.lnkLj7f6tIKUAwunOR9MFQeR8hHgMGS', 'What is your favorite movie?', '$2y$10$XGPIl0dq8qXH5Is0rhEGEuk8oWd9Y7ZiWKnyH98WwhhtdSVPH0Nw.', '2026-05-11 14:58:02', 'basic-user', 'pending', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `playground_id` (`playground_id`),
  ADD KEY `area_id` (`area_id`);

--
-- Indexes for table `booking_cart`
--
ALTER TABLE `booking_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_packages`
--
ALTER TABLE `cart_packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_cart_package_unique` (`cart_id`,`package_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `package_orders`
--
ALTER TABLE `package_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `playground_id` (`playground_id`);

--
-- Indexes for table `package_order_items`
--
ALTER TABLE `package_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `playgrounds`
--
ALTER TABLE `playgrounds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `play_areas`
--
ALTER TABLE `play_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playground_id` (`playground_id`);

--
-- Indexes for table `play_packages`
--
ALTER TABLE `play_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playground_id` (`playground_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_cart`
--
ALTER TABLE `booking_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_packages`
--
ALTER TABLE `cart_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `package_orders`
--
ALTER TABLE `package_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_order_items`
--
ALTER TABLE `package_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `playgrounds`
--
ALTER TABLE `playgrounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `play_areas`
--
ALTER TABLE `play_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `play_packages`
--
ALTER TABLE `play_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `fk_approvals_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_area` FOREIGN KEY (`area_id`) REFERENCES `play_areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bookings_playground` FOREIGN KEY (`playground_id`) REFERENCES `playgrounds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_cart`
--
ALTER TABLE `booking_cart`
  ADD CONSTRAINT `fk_booking_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_packages`
--
ALTER TABLE `cart_packages`
  ADD CONSTRAINT `fk_cart_packages_cart` FOREIGN KEY (`cart_id`) REFERENCES `booking_cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_packages_package` FOREIGN KEY (`package_id`) REFERENCES `play_packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `fk_login_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_orders`
--
ALTER TABLE `package_orders`
  ADD CONSTRAINT `fk_package_orders_playground` FOREIGN KEY (`playground_id`) REFERENCES `playgrounds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_package_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_order_items`
--
ALTER TABLE `package_order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `package_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_package` FOREIGN KEY (`package_id`) REFERENCES `play_packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `play_areas`
--
ALTER TABLE `play_areas`
  ADD CONSTRAINT `fk_play_areas_playground` FOREIGN KEY (`playground_id`) REFERENCES `playgrounds` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `play_packages`
--
ALTER TABLE `play_packages`
  ADD CONSTRAINT `fk_play_packages_playground` FOREIGN KEY (`playground_id`) REFERENCES `playgrounds` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
