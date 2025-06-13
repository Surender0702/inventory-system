-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 13, 2025 at 08:29 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `description`, `quantity`, `created_at`, `updated_at`, `image_path`) VALUES
(1, 'pencil', '', 95, '2025-06-01 08:55:41', '2025-06-07 20:29:32', NULL),
(2, 'A4 Size Paper', '', 153, '2025-06-01 09:10:48', '2025-06-08 02:26:23', NULL),
(3, 'Printer ', '', 52, '2025-06-01 09:11:14', '2025-06-07 19:44:54', NULL),
(4, 'Printer ', '', 170, '2025-06-01 10:07:47', '2025-06-07 19:44:26', NULL),
(8, 'AirWick Automactic Machin', '', 40, '2025-06-06 04:29:47', '2025-06-06 04:29:47', ''),
(9, 'AirWick Refill', '', 40, '2025-06-06 04:30:05', '2025-06-06 04:30:05', ''),
(10, 'Alpin', '', 30, '2025-06-06 04:30:18', '2025-06-06 04:30:18', ''),
(11, 'Ball Point Pen Pilot Black', '', 48, '2025-06-06 04:30:35', '2025-06-06 06:38:13', ''),
(12, 'Ball Point Pen Pilot Blue', '', 50, '2025-06-06 04:30:52', '2025-06-06 04:30:52', ''),
(13, 'Ball Point Pen Pilot Green', '', 50, '2025-06-06 04:31:12', '2025-06-06 04:31:12', ''),
(14, 'Ball Point Pen Pilot Red', '', 20, '2025-06-06 04:31:38', '2025-06-06 04:31:38', ''),
(15, 'Ball Point Pen Reynolds Black', '', 30, '2025-06-06 04:33:38', '2025-06-06 04:33:38', ''),
(16, 'Ball Point Pen Reynolds Blue', '', 30, '2025-06-06 04:34:35', '2025-06-06 04:34:35', ''),
(17, 'Ball Point Pen Reynolds Red', '', 40, '2025-06-06 04:34:46', '2025-06-06 04:34:46', ''),
(18, 'Bond Paper - A4 Size', '', 15, '2025-06-06 04:35:04', '2025-06-06 04:35:04', ''),
(19, 'Borosil Glass Medium Size', 'Available only for Registry', 26, '2025-06-06 04:36:04', '2025-06-06 04:36:04', ''),
(21, 'Borosil Glass samll Size', '', 11, '2025-06-06 04:37:17', '2025-06-08 14:43:39', ''),
(22, 'Car Towel - XL', '', 23, '2025-06-06 04:39:22', '2025-06-07 20:12:44', ''),
(23, 'Cartridge Canon 319', '', 23, '2025-06-06 04:39:38', '2025-06-06 04:39:38', ''),
(24, 'Cartridge Canon 326', '', 23, '2025-06-06 04:39:47', '2025-06-06 04:39:47', ''),
(25, 'Cartridge HP 12A', '', 45, '2025-06-06 04:39:55', '2025-06-06 04:39:55', ''),
(26, 'Cartridge HP 278-A', '', 32, '2025-06-06 04:40:04', '2025-06-06 04:40:04', ''),
(27, 'Cartridge HP 36A', '', 34, '2025-06-06 04:40:17', '2025-06-06 04:40:17', ''),
(28, 'Cartridge HP 49A', '', 33, '2025-06-06 04:40:27', '2025-06-06 05:18:21', ''),
(29, 'Cartridge HP 53A', '', 20, '2025-06-06 04:40:40', '2025-06-07 17:40:54', ''),
(30, 'Cartridge HP CC388A', '', 28, '2025-06-06 04:41:23', '2025-06-06 05:37:20', ''),
(31, 'Cartridge HP COLOR 685-Complete Set', '', 19, '2025-06-06 04:41:33', '2025-06-06 04:49:45', ''),
(32, 'Cartridge HP Laserjet 80A CF-280A', '', 40, '2025-06-06 04:41:50', '2025-06-06 05:32:42', ''),
(33, 'Cartridge HP Laserjet CF230 A', '', 79, '2025-06-06 04:42:04', '2025-06-08 10:05:07', ''),
(34, 'Cartridge Samsung MLT-D10435/XIP', '', 23, '2025-06-06 04:42:14', '2025-06-06 04:42:14', ''),
(35, 'Cartridge Samsung-1640', '', 16, '2025-06-06 04:42:24', '2025-06-08 01:55:10', ''),
(36, 'TV', '', 61, '2025-06-08 10:15:26', '2025-06-08 18:00:40', '');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_quantity_log`
--

CREATE TABLE `inventory_quantity_log` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `old_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `changed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_quantity_log`
--

INSERT INTO `inventory_quantity_log` (`id`, `inventory_id`, `old_quantity`, `new_quantity`, `changed_by`, `changed_at`) VALUES
(1, 3, 3, 8, 'Admin', '2025-06-08 01:02:47'),
(2, 3, 8, 7, 'Admin', '2025-06-08 01:03:03'),
(3, 3, 7, 2, 'Admin', '2025-06-08 01:03:23'),
(4, 3, 2, 2, 'Admin', '2025-06-08 01:03:37'),
(5, 3, 2, 2, 'Admin', '2025-06-08 01:03:40'),
(6, 35, 23, 6, 'Admin', '2025-06-08 01:05:01'),
(7, 35, 6, 6, 'Admin', '2025-06-08 01:05:29'),
(8, 3, 2, 50, 'Admin', '2025-06-08 01:05:50'),
(9, 3, 50, 50, 'Admin', '2025-06-08 01:05:57'),
(10, 2, 20, 50, 'Admin', '2025-06-08 01:08:18'),
(11, 2, 50, 50, 'Admin', '2025-06-08 01:08:28'),
(12, 2, 50, 100, 'Admin', '2025-06-08 01:11:21'),
(13, 2, 100, 125, 'Admin', '2025-06-08 01:11:36'),
(14, 4, 10, 50, 'Admin', '2025-06-08 01:11:56'),
(15, 4, 50, 90, 'Admin', '2025-06-08 01:12:01'),
(16, 4, 90, 130, 'Admin', '2025-06-08 01:12:10'),
(17, 4, 130, 170, 'Admin', '2025-06-08 01:14:26'),
(18, 3, 50, 51, 'Admin', '2025-06-08 01:14:36'),
(19, 3, 51, 52, 'Admin', '2025-06-08 01:14:54'),
(20, 2, 125, 148, 'Admin', '2025-06-08 01:36:45'),
(21, 1, 86, 87, 'Admin', '2025-06-08 01:37:00'),
(22, 22, 11, 23, 'Admin', '2025-06-08 01:42:44'),
(23, 1, 87, 90, 'Admin', '2025-06-08 01:46:30'),
(24, 1, 90, 92, 'Admin', '2025-06-08 01:52:16'),
(25, 1, 92, 95, 'Admin', '2025-06-08 01:59:32'),
(26, 2, 148, 150, 'Admin', '2025-06-08 02:08:06'),
(27, 35, 6, 16, 'Admin', '2025-06-08 07:25:10'),
(28, 2, 150, 153, 'Admin', '2025-06-08 07:56:23'),
(29, 33, 76, 79, 'Admin', '2025-06-08 15:35:07'),
(30, 36, 5, 7, 'Admin', '2025-06-08 20:10:27'),
(31, 21, 6, 11, 'Admin', '2025-06-08 20:13:39'),
(32, 36, 7, 8, 'Admin', '2025-06-08 20:15:48'),
(33, 36, 8, 9, 'Admin', '2025-06-08 20:18:06'),
(34, 36, 9, 10, 'Admin', '2025-06-08 20:27:35'),
(35, 36, 10, 60, 'Admin', '2025-06-08 20:32:57'),
(36, 36, 60, 61, 'Admin', '2025-06-08 23:30:40');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `employee_id`, `item_id`, `quantity`, `status`, `request_date`, `approved_quantity`) VALUES
(1, 1, 1, 1, 'denied', '2025-06-01 09:08:49', NULL),
(2, 1, 1, 1, 'approved', '2025-06-01 09:09:43', NULL),
(3, 1, 2, 3, 'approved', '2025-06-01 09:13:02', NULL),
(4, 1, 2, 3, 'approved', '2025-06-01 09:54:13', NULL),
(5, 1, 2, 3, 'approved', '2025-06-01 10:05:31', NULL),
(6, 1, 3, 2, 'denied', '2025-06-01 10:05:31', NULL),
(7, 1, 2, 3, 'approved', '2025-06-01 10:06:14', NULL),
(8, 1, 3, 2, 'approved', '2025-06-01 10:06:14', NULL),
(9, 1, 2, 3, 'denied', '2025-06-01 10:13:01', NULL),
(10, 1, 3, 4, 'denied', '2025-06-01 10:13:01', NULL),
(11, 1, 4, 3, 'denied', '2025-06-01 10:13:01', NULL),
(12, 1, 2, 3, 'denied', '2025-06-01 10:14:16', NULL),
(13, 1, 3, 4, 'denied', '2025-06-01 10:14:16', NULL),
(14, 1, 4, 3, 'denied', '2025-06-01 10:14:16', NULL),
(15, 1, 2, 3, 'denied', '2025-06-01 10:14:21', NULL),
(16, 1, 3, 4, 'denied', '2025-06-01 10:14:21', NULL),
(17, 1, 4, 3, 'denied', '2025-06-01 10:14:21', NULL),
(18, 1, 2, 1, 'denied', '2025-06-01 10:14:41', NULL),
(19, 1, 3, 2, 'denied', '2025-06-01 10:14:41', NULL),
(20, 1, 4, 3, 'denied', '2025-06-01 10:14:41', NULL),
(21, 1, 2, 1, 'denied', '2025-06-01 10:15:18', NULL),
(22, 1, 3, 2, 'denied', '2025-06-01 10:15:18', NULL),
(23, 1, 4, 3, 'denied', '2025-06-01 10:15:18', NULL),
(24, 1, 2, 1, 'denied', '2025-06-01 10:18:04', NULL),
(25, 1, 3, 2, 'denied', '2025-06-01 10:18:04', NULL),
(26, 1, 4, 3, 'denied', '2025-06-01 10:18:04', NULL),
(27, 1, 2, 2, 'denied', '2025-06-01 10:18:11', NULL),
(28, 1, 2, 3, 'approved', '2025-06-01 10:18:57', NULL),
(29, 1, 2, 2, 'approved', '2025-06-01 10:20:34', NULL),
(30, 1, 2, 1, 'denied', '2025-06-01 10:38:55', NULL),
(31, 1, 3, 3, 'approved', '2025-06-01 10:38:55', NULL),
(32, 1, 4, 1, 'approved', '2025-06-01 10:38:55', NULL),
(33, 1, 2, 2, 'approved', '2025-06-01 10:56:50', NULL),
(34, 4, 2, 1, 'approved', '2025-06-01 13:00:29', NULL),
(35, 4, 3, 3, 'approved', '2025-06-01 13:00:29', NULL),
(36, 4, 2, 5, 'denied', '2025-06-01 13:09:57', NULL),
(37, 4, 3, 1, 'denied', '2025-06-01 13:30:46', NULL),
(38, 4, 4, 1, 'denied', '2025-06-01 13:30:46', NULL),
(39, 4, 2, 1, 'approved', '2025-06-01 13:30:46', NULL),
(40, 6, 2, 2, 'approved', '2025-06-01 13:43:11', NULL),
(41, 6, 3, 1, 'approved', '2025-06-01 13:43:11', NULL),
(42, 6, 4, 1, 'approved', '2025-06-01 13:43:11', NULL),
(43, 4, 1, 2, 'approved', '2025-06-02 04:40:27', NULL),
(44, 4, 2, 2, 'approved', '2025-06-02 04:40:27', NULL),
(45, 4, 3, 3, 'approved', '2025-06-02 04:40:27', NULL),
(46, 4, 4, 3, 'denied', '2025-06-02 04:40:27', NULL),
(47, 4, 2, 1, 'denied', '2025-06-04 16:09:11', NULL),
(48, 4, 4, 1, 'denied', '2025-06-04 16:09:11', NULL),
(49, 4, 1, 1, 'denied', '2025-06-04 16:09:11', NULL),
(50, 4, 1, 1, 'approved', '2025-06-04 16:54:34', NULL),
(51, 4, 2, 1, 'approved', '2025-06-04 16:54:34', NULL),
(52, 4, 4, 1, 'denied', '2025-06-04 16:54:34', NULL),
(53, 4, 1, 7, 'approved', '2025-06-05 13:34:14', NULL),
(54, 4, 2, 3, 'approved', '2025-06-05 13:35:38', NULL),
(55, 4, 1, 1, 'approved', '2025-06-05 13:37:09', NULL),
(56, 4, 2, 1, 'denied', '2025-06-05 13:37:09', NULL),
(57, 4, 1, 1, 'denied', '2025-06-05 13:37:57', NULL),
(58, 4, 2, 1, 'approved', '2025-06-05 13:37:57', 3),
(59, 4, 4, 2, 'approved', '2025-06-05 13:37:57', 1),
(60, 5, 1, 1, 'approved', '2025-06-05 16:22:08', 2),
(61, 5, 2, 1, 'denied', '2025-06-05 16:22:08', NULL),
(62, 5, 4, 1, 'denied', '2025-06-05 16:22:08', NULL),
(63, 6, 1, 1, 'approved', '2025-06-05 16:22:50', NULL),
(64, 6, 2, 1, 'approved', '2025-06-05 16:22:50', 3),
(65, 6, 4, 1, 'approved', '2025-06-05 16:22:50', NULL),
(66, 7, 1, 1, 'denied', '2025-06-05 17:12:49', NULL),
(67, 7, 2, 1, 'denied', '2025-06-05 17:12:49', NULL),
(68, 7, 4, 1, 'denied', '2025-06-05 17:12:49', NULL),
(69, 7, 2, 1, 'approved', '2025-06-05 17:38:04', 2),
(70, 7, 1, 1, 'approved', '2025-06-06 04:48:35', 2),
(71, 7, 2, 1, 'approved', '2025-06-06 04:48:35', 2),
(72, 7, 3, 1, 'approved', '2025-06-06 04:48:35', 2),
(73, 7, 4, 1, 'approved', '2025-06-06 04:48:35', 1),
(74, 7, 8, 1, 'denied', '2025-06-06 04:48:35', NULL),
(75, 7, 9, 1, 'approved', '2025-06-06 04:48:35', 3),
(76, 7, 11, 1, 'approved', '2025-06-06 04:48:35', 2),
(77, 7, 12, 1, 'denied', '2025-06-06 04:48:35', NULL),
(78, 7, 13, 1, 'approved', '2025-06-06 04:48:35', 3),
(79, 7, 14, 1, 'approved', '2025-06-06 04:48:35', 2),
(80, 7, 15, 1, 'approved', '2025-06-06 04:48:35', 3),
(81, 7, 16, 1, 'approved', '2025-06-06 04:48:35', 2),
(82, 7, 17, 1, 'approved', '2025-06-06 04:48:35', NULL),
(83, 7, 31, 1, 'approved', '2025-06-06 04:48:35', 2),
(84, 7, 32, 1, 'approved', '2025-06-06 04:48:35', 3),
(85, 7, 33, 1, 'approved', '2025-06-06 04:48:35', 2),
(86, 7, 34, 1, 'approved', '2025-06-06 04:48:35', 2),
(87, 7, 35, 1, 'approved', '2025-06-06 04:48:35', 2),
(88, 7, 25, 1, 'approved', '2025-06-06 04:48:35', NULL),
(89, 7, 24, 1, 'approved', '2025-06-06 04:48:35', NULL),
(90, 7, 23, 1, 'approved', '2025-06-06 04:48:35', 2),
(91, 7, 22, 1, 'approved', '2025-06-06 04:48:35', 1),
(92, 7, 18, 1, 'denied', '2025-06-06 04:48:35', NULL),
(93, 7, 19, 1, 'approved', '2025-06-06 04:48:35', 3),
(94, 7, 21, 1, 'approved', '2025-06-06 04:48:35', NULL),
(95, 7, 29, 1, 'approved', '2025-06-06 04:48:35', 2),
(96, 7, 28, 1, 'approved', '2025-06-06 04:48:35', NULL),
(97, 7, 27, 1, 'approved', '2025-06-06 04:48:35', NULL),
(98, 7, 26, 1, 'approved', '2025-06-06 04:48:35', NULL),
(99, 7, 30, 1, 'approved', '2025-06-06 04:48:35', 4),
(100, 7, 18, 1, 'denied', '2025-06-06 09:34:50', 2),
(101, 7, 19, 1, 'denied', '2025-06-06 09:34:50', 3),
(102, 7, 1, 1, 'denied', '2025-06-06 09:49:07', NULL),
(103, 7, 2, 1, 'approved', '2025-06-06 09:49:07', 3),
(104, 7, 4, 1, 'approved', '2025-06-06 09:49:07', 2),
(105, 7, 8, 1, 'approved', '2025-06-06 09:49:07', NULL),
(106, 7, 9, 1, 'approved', '2025-06-06 09:49:07', NULL),
(107, 7, 10, 1, 'approved', '2025-06-06 09:49:07', 2),
(108, 7, 11, 1, 'approved', '2025-06-06 09:49:07', NULL),
(109, 7, 1, 1, 'approved', '2025-06-06 09:52:10', 2),
(110, 7, 3, 1, 'approved', '2025-06-06 09:52:10', 2),
(111, 7, 10, 1, 'approved', '2025-06-06 09:52:10', NULL),
(112, 7, 11, 1, 'approved', '2025-06-06 09:52:10', NULL),
(113, 7, 12, 1, 'approved', '2025-06-06 09:52:10', NULL),
(114, 7, 13, 1, 'approved', '2025-06-06 09:52:10', 2),
(115, 7, 14, 1, 'approved', '2025-06-06 09:52:10', NULL),
(116, 7, 15, 1, 'approved', '2025-06-06 09:52:10', NULL),
(117, 7, 16, 1, 'approved', '2025-06-06 09:52:10', 3),
(118, 7, 18, 1, 'approved', '2025-06-06 09:52:10', 2),
(119, 7, 19, 1, 'approved', '2025-06-06 09:52:10', NULL),
(120, 7, 21, 1, 'approved', '2025-06-06 09:52:10', 3),
(121, 7, 22, 1, 'approved', '2025-06-06 09:52:10', 2),
(122, 7, 23, 1, 'approved', '2025-06-06 09:52:10', 2),
(123, 7, 24, 1, 'approved', '2025-06-06 09:52:10', 2),
(124, 7, 25, 1, 'approved', '2025-06-06 09:52:10', 0),
(125, 7, 26, 1, 'approved', '2025-06-06 09:52:10', 4),
(126, 7, 27, 1, 'denied', '2025-06-06 09:52:10', 0),
(127, 7, 28, 1, 'approved', '2025-06-06 09:52:10', 2),
(128, 7, 29, 1, 'approved', '2025-06-06 09:52:10', 3),
(129, 7, 30, 1, 'denied', '2025-06-06 09:52:10', NULL),
(130, 7, 31, 1, 'approved', '2025-06-06 09:52:10', 1),
(131, 7, 32, 1, 'approved', '2025-06-06 09:52:10', 2),
(132, 7, 33, 1, 'approved', '2025-06-06 09:52:10', 1),
(133, 7, 34, 1, 'approved', '2025-06-06 09:52:10', 3),
(134, 7, 35, 1, 'approved', '2025-06-06 09:52:10', 3),
(135, 7, 17, 1, 'denied', '2025-06-06 09:52:10', NULL),
(136, 4, 2, 5, 'approved', '2025-06-07 11:15:52', 3),
(137, 4, 1, 4, 'approved', '2025-06-07 11:15:52', NULL),
(138, 4, 1, 4, 'approved', '2025-06-07 11:25:45', 3),
(139, 4, 8, 4, 'approved', '2025-06-07 11:25:45', 3),
(140, 4, 9, 3, 'approved', '2025-06-07 11:25:45', 3),
(141, 4, 10, 7, 'approved', '2025-06-07 11:25:45', 3),
(142, 4, 11, 10, 'approved', '2025-06-07 11:25:45', 5),
(143, 4, 12, 12, 'approved', '2025-06-07 11:25:45', 6),
(144, 4, 13, 14, 'approved', '2025-06-07 11:25:45', NULL),
(145, 4, 27, 1, 'approved', '2025-06-07 17:05:21', 4),
(146, 4, 28, 1, 'approved', '2025-06-07 17:05:21', 2),
(147, 4, 29, 1, 'approved', '2025-06-07 17:05:21', 4),
(148, 4, 30, 1, 'approved', '2025-06-07 17:05:21', 8),
(149, 4, 31, 1, 'approved', '2025-06-07 17:05:21', 5),
(150, 4, 32, 1, 'approved', '2025-06-07 17:05:21', 3),
(151, 4, 33, 1, 'approved', '2025-06-07 17:05:21', NULL),
(152, 4, 1, 1, 'approved', '2025-06-07 17:05:30', NULL),
(153, 4, 2, 1, 'approved', '2025-06-07 17:05:30', NULL),
(154, 4, 3, 1, 'approved', '2025-06-07 17:05:30', 2),
(155, 4, 4, 1, 'approved', '2025-06-07 17:05:30', NULL),
(156, 4, 3, 3, 'approved', '2025-06-07 17:19:06', NULL),
(157, 4, 4, 2, 'approved', '2025-06-07 17:19:06', NULL),
(158, 4, 3, 1, 'approved', '2025-06-07 17:19:31', NULL),
(159, 4, 4, 1, 'approved', '2025-06-07 17:19:31', NULL),
(160, 4, 3, 1, 'approved', '2025-06-07 17:20:15', NULL),
(161, 4, 4, 1, 'approved', '2025-06-07 17:20:15', NULL),
(162, 4, 3, 1, 'approved', '2025-06-07 17:32:50', NULL),
(163, 4, 4, 1, 'approved', '2025-06-07 17:32:50', NULL),
(164, 4, 3, 1, 'approved', '2025-06-07 17:41:28', NULL),
(165, 4, 4, 1, 'approved', '2025-06-07 17:41:28', NULL),
(166, 4, 23, 1, 'approved', '2025-06-07 17:41:37', 1),
(167, 4, 24, 1, 'approved', '2025-06-07 17:41:37', 2),
(168, 4, 25, 1, 'approved', '2025-06-07 17:41:37', NULL),
(169, 4, 8, 3, 'approved', '2025-06-07 17:44:56', NULL),
(170, 4, 9, 4, 'approved', '2025-06-07 17:44:56', 4),
(171, 4, 10, 3, 'approved', '2025-06-07 17:44:56', 3),
(172, 4, 11, 3, 'approved', '2025-06-07 17:44:56', 3),
(173, 4, 12, 50, 'approved', '2025-06-07 17:44:56', NULL),
(174, 4, 1, 5, 'approved', '2025-06-08 13:45:05', 2),
(175, 4, 2, 5, 'approved', '2025-06-08 13:45:05', 4),
(176, 4, 3, 5, 'approved', '2025-06-08 13:45:05', 6),
(177, 4, 4, 5, 'approved', '2025-06-08 13:45:05', 6),
(178, 4, 8, 5, 'approved', '2025-06-08 13:45:05', 7),
(179, 4, 9, 5, 'approved', '2025-06-08 13:45:05', 8),
(180, 4, 36, 3, 'approved', '2025-06-08 13:50:58', 2),
(181, 4, 1, 5, 'approved', '2025-06-08 13:58:16', 5),
(182, 4, 2, 5, 'approved', '2025-06-08 13:58:16', 5),
(183, 4, 3, 5, 'approved', '2025-06-08 13:58:16', 5),
(184, 4, 1, 1, 'approved', '2025-06-08 14:02:03', 1),
(185, 4, 2, 1, 'approved', '2025-06-08 14:02:03', 1),
(186, 4, 3, 1, 'approved', '2025-06-08 14:02:03', 1),
(187, 4, 21, 3, 'approved', '2025-06-08 14:04:33', 3),
(188, 4, 1, 1, 'approved', '2025-06-08 16:43:09', 1),
(189, 4, 2, 1, 'approved', '2025-06-08 16:43:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','employee') DEFAULT 'employee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$rVtiCyVK2Or5xbY2BuU.jOoEk/QQRjH1/NJEQm0BtOEI7iFoLFDN.', 'admin'),
(4, 'pooja', 'pooja@gmail.com', '$2y$10$ndUjDRYymtbloMIUoTRIX.NDD0rloTKzohNHrS0GxUevCb9DPTPtq', 'employee'),
(5, 'Admin1', 'admin@gmail.com', '$2y$10$uOAToVwN9G/Zvw.0B4MM3Of1MXO32C4hIXUFstFMYYPCdUJhLaxLS', 'admin'),
(6, 'anuj', 'anuj@gmail.com', '$2y$10$8BLdI7.FvGBjijTSW9ZlK.uwXEejGyI08JKZEW4vieT8gGzTxXi0u', 'employee'),
(7, 'manish', 'manish@gmail.com', '$2y$10$VImxCoO7daXA63MD9P804uZKCyS3Wy0m0ShXncfFOuRfujnwUXAPe', 'employee'),
(8, 'neeraj', 'neeraj@gmail.com', 'e73efee274e35cd0f133624774d16006', 'employee'),
(9, 'rakesh', 'rakesh@gmail.com', '67a05e3822ce48a6386746388e6c81f5', 'employee'),
(10, 'su', 'su@gmail.com', '$2y$10$sZiYsU40iq9qwQ2CYz8TUOL/mrLLGeWSA/5Eu2x5aIL0XkOoztZtm', 'admin'),
(11, 'Surender', 'surender0702@gmail.com', '$2y$10$rTxpKFr/kggWj2xWKHwEDu2zEFTRfn1BEEDXIwK1atxeHnoDR18yu', 'admin'),
(12, 'Omkar', 'riya@gmail.com', '$2y$10$zt0oP3bt.V.Ds409qyYb7OWEjxMtVZ4oVoKyJj2cvXjZQUdYn32ku', 'employee');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_quantity_log`
--
ALTER TABLE `inventory_quantity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `inventory_quantity_log`
--
ALTER TABLE `inventory_quantity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_quantity_log`
--
ALTER TABLE `inventory_quantity_log`
  ADD CONSTRAINT `inventory_quantity_log_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
