-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 27, 2025 at 05:25 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sky_mobile`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
CREATE TABLE IF NOT EXISTS `bills` (
  `bill_no` varchar(200) NOT NULL,
  `branch_id` int NOT NULL,
  `date` datetime NOT NULL,
  `payment_method` enum('Cash','Card') NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `total_discount` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `phone_repairing` decimal(10,2) DEFAULT '0.00',
  `reload` decimal(10,2) DEFAULT '0.00',
  `reload_profit` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`bill_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`bill_no`, `branch_id`, `date`, `payment_method`, `subtotal`, `total_discount`, `total`, `paid_amount`, `balance`, `phone_repairing`, `reload`, `reload_profit`) VALUES
('PS0003', 2, '2025-09-26 10:53:09', 'Cash', 13000.00, 0.00, 13000.00, 14000.00, 1000.00, 0.00, 0.00, 0.00),
('PS0002', 2, '2025-09-26 07:41:04', 'Cash', 13000.00, 0.00, 13000.00, 0.00, -13000.00, 0.00, 0.00, 0.00),
('PS0001', 2, '2025-09-25 19:00:13', 'Cash', 13000.00, 0.00, 13000.00, 0.00, -13000.00, 0.00, 0.00, 0.00),
('PS-B1-0003', 1, '2025-09-25 18:24:11', 'Cash', 150000.00, 0.00, 150000.00, 152000.00, 2000.00, 0.00, 0.00, 0.00),
('PS-B1-0002', 1, '2025-09-25 18:07:26', 'Cash', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
('PS-B1-0001', 1, '2025-09-25 18:07:16', 'Cash', 153100.00, 1100.00, 152000.00, 0.00, -152000.00, 2000.00, 0.00, 0.00),
('PS-B1-0004', 1, '2025-09-26 11:33:36', 'Cash', 1000.00, 0.00, 1000.00, 2000.00, 1000.00, 0.00, 1000.00, 40.00),
('PS-B1-0005', 1, '2025-09-26 11:36:02', 'Cash', 500.00, 0.00, 500.00, 600.00, 100.00, 0.00, 500.00, 20.00),
('PS-B1-0006', 1, '2025-09-26 11:38:52', 'Cash', 4000.00, 0.00, 4000.00, 4000.00, 0.00, 0.00, 4000.00, 265.00),
('PS-B1-0007', 1, '2025-09-26 11:58:02', 'Cash', 1700.00, 100.00, 1600.00, 1600.00, 0.00, 100.00, 500.00, 125.00),
('PS0004', 2, '2025-09-26 13:35:24', 'Cash', 2000.00, 0.00, 2000.00, 2000.00, 0.00, 0.00, 2000.00, 200.00),
('PS0005', 2, '2025-09-26 14:56:46', 'Cash', 14000.00, 1000.00, 13000.00, 13000.00, 0.00, 0.00, 1000.00, 100.00),
('PS0006', 2, '2025-09-26 16:31:34', 'Cash', 1000.00, 0.00, 1000.00, 1000.00, 0.00, 0.00, 1000.00, 100.00),
('PS-B1-0008', 1, '2025-09-26 17:11:38', 'Cash', 1100.00, 0.00, 1100.00, 1500.00, 400.00, 0.00, 0.00, 0.00),
('PS0007', 2, '2025-09-26 17:13:47', 'Cash', 13000.00, 0.00, 13000.00, 15000.00, 2000.00, 0.00, 0.00, 0.00),
('PS0008', 2, '2025-09-26 17:16:11', 'Cash', 2000.00, 0.00, 2000.00, 2000.00, 0.00, 0.00, 2000.00, 333.33),
('PS-B1-0009', 1, '2025-09-26 19:46:16', 'Cash', 150000.00, 0.00, 150000.00, 0.00, -150000.00, 0.00, 0.00, 0.00),
('PS0009', 2, '2025-09-26 22:34:07', 'Cash', 13000.00, 0.00, 13000.00, 0.00, -13000.00, 0.00, 0.00, 0.00),
('PS-B1-0010', 1, '2025-09-27 10:26:49', 'Cash', 1000.00, 0.00, 1000.00, 1000.00, 0.00, 0.00, 1000.00, 100.00),
('PS0010', 2, '2025-09-27 10:28:02', 'Cash', 500.00, 0.00, 500.00, 500.00, 0.00, 0.00, 500.00, 83.33),
('PS0011', 2, '2025-09-27 18:07:38', 'Cash', 1000.00, 0.00, 1000.00, 1000.00, 0.00, 0.00, 1000.00, 40.00),
('PS-B1-0011', 1, '2025-09-27 18:56:28', 'Cash', 50.00, 0.00, 50.00, 50.00, 0.00, 0.00, 50.00, 2.00),
('PS-B1-0012', 1, '2025-09-27 19:01:38', 'Cash', 4350.00, 300.00, 4050.00, 5000.00, 950.00, 0.00, 0.00, 0.00),
('PS-B1-0013', 1, '2025-09-27 19:54:09', 'Cash', 100.00, 0.00, 100.00, 100.00, 0.00, 0.00, 100.00, 4.00),
('PS-B1-0014', 1, '2025-09-27 20:09:57', 'Cash', 30000.00, 500.00, 29500.00, 30000.00, 500.00, 0.00, 0.00, 0.00),
('PS0012', 2, '2025-09-27 21:53:10', 'Card', 30000.00, 0.00, 30000.00, 30000.00, 0.00, 0.00, 0.00, 0.00),
('PS0013', 2, '2025-09-27 21:54:03', 'Card', 13000.00, 0.00, 13000.00, 13000.00, 0.00, 0.00, 0.00, 0.00),
('PS-B1-0015', 1, '2025-09-27 21:54:31', 'Cash', 60000.00, 500.00, 59500.00, 60000.00, 500.00, 0.00, 0.00, 0.00),
('PS0014', 2, '2025-09-27 22:01:34', 'Cash', 13000.00, 0.00, 13000.00, 13000.00, 0.00, 0.00, 0.00, 0.00),
('PS-B2-0001', 2, '2025-09-27 22:12:22', 'Cash', 30000.00, 1000.00, 29000.00, 30000.00, 1000.00, 0.00, 0.00, 0.00),
('PS-B1-0016', 1, '2025-09-27 22:36:40', 'Cash', 1450.00, 0.00, 1450.00, 1500.00, 50.00, 0.00, 0.00, 0.00),
('PS-B1-0017', 1, '2025-09-27 22:53:19', 'Cash', 200.00, 0.00, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

DROP TABLE IF EXISTS `bill_items`;
CREATE TABLE IF NOT EXISTS `bill_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_no` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL,
  `imei` varchar(15) DEFAULT NULL COMMENT 'Stores the IMEI number for the sold product, if applicable',
  PRIMARY KEY (`id`),
  KEY `bill_no` (`bill_no`(250)),
  KEY `product_id` (`product_id`(250))
) ENGINE=MyISAM AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `bill_no`, `product_id`, `quantity`, `price`, `discount`, `subtotal`, `imei`) VALUES
(24, 'PS0003', 'PB00001', 1, 13000.00, 0.00, 13000.00, NULL),
(23, 'PS0002', 'PB00001', 1, 13000.00, 0.00, 13000.00, NULL),
(22, 'PS0001', 'PB00001', 1, 13000.00, 0.00, 13000.00, NULL),
(21, 'PS-B1-0003', 'PM00001', 1, 150000.00, 0.00, 150000.00, NULL),
(20, 'PS-B1-0001', 'PM00002', 1, 1100.00, 1000.00, 1100.00, NULL),
(19, 'PS-B1-0001', 'PM00001', 1, 150000.00, 100.00, 150000.00, NULL),
(25, 'PS-B1-0007', 'PM00002', 1, 1100.00, 100.00, 1100.00, NULL),
(26, 'PS0005', 'PB00002', 1, 13000.00, 1000.00, 13000.00, NULL),
(27, 'PS-B1-0008', 'PM00002', 1, 1100.00, 0.00, 1100.00, NULL),
(28, 'PS0007', 'PB00002', 1, 13000.00, 0.00, 13000.00, NULL),
(29, 'PS-B1-0009', 'PM00001', 1, 150000.00, 0.00, 150000.00, NULL),
(30, 'PS0009', 'PB00001', 1, 13000.00, 0.00, 13000.00, NULL),
(31, 'PS-B1-0012', 'PM00003', 3, 1450.00, 300.00, 4350.00, NULL),
(32, 'PS-B1-0014', 'PM00005', 1, 30000.00, 500.00, 30000.00, NULL),
(33, 'PS0012', 'PB00003', 1, 30000.00, 0.00, 30000.00, NULL),
(34, 'PS0013', 'PB00001', 1, 13000.00, 0.00, 13000.00, NULL),
(35, 'PS-B1-0015', 'PM00005', 2, 30000.00, 500.00, 60000.00, NULL),
(36, 'PS0014', 'PB00001', 1, 13000.00, 0.00, 13000.00, NULL),
(37, 'PS-B2-0001', 'PB00003', 1, 30000.00, 1000.00, 30000.00, NULL),
(38, 'PS-B1-0016', 'PM00003', 1, 1450.00, 0.00, 1450.00, NULL),
(39, 'PS-B1-0017', 'PM00004', 1, 200.00, 0.00, 200.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `branch_id` int NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `branch_name` (`branch_name`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `is_main`) VALUES
(1, 'Main Branch', 1),
(2, 'Branch 2', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` varchar(20) NOT NULL,
  `branch_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `barcode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `sold` int DEFAULT '0',
  `last_sold` datetime DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `fk_products_branch` (`branch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `branch_id`, `name`, `category`, `original_price`, `sale_price`, `stock`, `barcode`, `color`, `photo`, `sold`, `last_sold`) VALUES
('PM00003', 1, 'charger', 'charge', 986.00, 1450.00, 6, '3165461466', 'white', '', 4, '2025-09-27 22:36:40'),
('PM00004', 1, 'corn', 'corn', 100.00, 200.00, 4, '6970855428677', 'white', '', 1, '2025-09-27 22:53:19'),
('PB00002', 2, 'Iphone 11', 'Phone', 12000.00, 13000.00, 13, '479111110468', 'Ash', 'Uploads/WhatsApp Image 2025-09-11 at 13.26.14_cd78b757.jpg', 2, '2025-09-26 17:13:47'),
('PM00002', 1, 'Baanu', 'Item', 1000.00, 1100.00, 0, '154689656489', 'Maroon', 'Uploads/WhatsApp Image 2025-08-25 at 09.55.44_6e15b8b3.jpg', 3, '2025-09-26 17:11:38'),
('PM00001', 1, 'Samsung S10', 'Phone', 142000.00, 150000.00, 7, '9300830054435', 'Black', 'Uploads/Black White Simple Modern Neon Griddy Bold Technology Pixel Electronics Store Logo.png', 3, '2025-09-26 19:46:16'),
('PB00001', 2, 'Iphone 11', 'Phone', 12000.00, 14000.00, 9, '4791111104614', 'Ash', 'Uploads/images.png', 6, '2025-09-27 22:01:34'),
('PM00005', 1, 'techno spark go30c(4 gb 128))', 'phone', 27500.00, 30000.00, 7, '', 'Black', '', 3, '2025-09-27 21:54:31'),
('PM00006', 1, 'techno spark go30c(4 gb 256))', 'phone', 27500.00, 30000.00, 10, NULL, 'Baanu', '', 0, NULL),
('PM00007', 1, 'techno spark go30c(4 gb 256))', 'phone', 27500.00, 30000.00, 10, NULL, 'Baanu', '', 0, NULL),
('PB00003', 2, 'techno spark go30c(4 gb 256))', 'phone', 27500.00, 30000.00, 8, NULL, 'kalai', '', 2, '2025-09-27 22:12:22');

-- --------------------------------------------------------

--
-- Table structure for table `reload_providers`
--

DROP TABLE IF EXISTS `reload_providers`;
CREATE TABLE IF NOT EXISTS `reload_providers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `branch_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reload_providers`
--

INSERT INTO `reload_providers` (`id`, `name`, `branch_id`) VALUES
(1, 'Dialog', 1),
(2, 'Airtel', 2),
(3, 'Airtel', 1),
(4, 'Dialog', 2),
(5, 'mobital', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reload_purchases`
--

DROP TABLE IF EXISTS `reload_purchases`;
CREATE TABLE IF NOT EXISTS `reload_purchases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `provider_id` int NOT NULL,
  `purchase_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `amount` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reload_purchases`
--

INSERT INTO `reload_purchases` (`id`, `provider_id`, `purchase_date`, `amount`, `cost`, `balance`) VALUES
(8, 5, '2025-09-27 18:54:41', 100.00, 96.00, 50.00),
(6, 3, '2025-09-26 19:44:44', 3000.00, 2700.00, 2000.00),
(7, 4, '2025-09-27 18:05:37', 5000.00, 4800.00, 4000.00),
(5, 2, '2025-09-26 17:15:36', 3000.00, 2500.00, 500.00),
(9, 5, '2025-09-27 18:57:15', 200.00, 160.00, 200.00),
(10, 1, '2025-09-27 19:52:45', 5000.00, 4800.00, 4900.00),
(11, 1, '2025-09-27 19:53:38', 3000.00, 2880.00, 3000.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_permanent` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_branch` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `branch_id`, `username`, `password_hash`, `is_permanent`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 1, '2025-09-19 02:48:35', '2025-09-19 02:48:35'),
(2, 2, 'admin2', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f', 0, '2025-09-19 02:48:35', '2025-09-25 11:58:23');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
