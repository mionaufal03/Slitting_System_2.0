-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 14, 2026 at 02:36 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `slitting_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `coil_product_map`
--

CREATE TABLE `coil_product_map` (
  `id` int NOT NULL,
  `coil_code` varchar(10) NOT NULL,
  `product` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `coil_product_map`
--

INSERT INTO `coil_product_map` (`id`, `coil_code`, `product`) VALUES
(1, 'A', 'RS-3825'),
(2, 'B', 'TS-4525'),
(3, 'BP', 'RS-3825-04'),
(4, 'CG', 'DS-3020'),
(5, 'CH', 'DS-3825'),
(6, 'CI', 'DS-4525'),
(7, 'CJ', 'DS-5030'),
(8, 'CM', 'DS-8460'),
(9, 'EC', 'LN-2520-04'),
(10, 'ED', 'L1N2-2520-02'),
(11, 'FK', 'LN-2520'),
(12, 'FL', 'LN-2420'),
(13, 'FN', 'YW-2520-SG'),
(14, 'FR', 'LN-1715-1'),
(15, 'FV', 'LZ-2520 - 788'),
(16, 'G', 'RS-4020'),
(17, 'H', 'RS-5030'),
(18, 'HPM', 'HBV-4020'),
(19, 'J', 'RS-6040'),
(20, 'K', 'RS-7050'),
(21, 'LA', 'TS-5030'),
(22, 'LG', 'RS-4025'),
(23, 'LQ', 'TS-3525-SG'),
(24, 'N', 'TU-3020'),
(25, 'P', 'TS-3525'),
(26, 'P6', 'PS-6020'),
(27, 'PM', 'MV-4020'),
(28, 'PS', 'PS-8525'),
(29, 'QA', 'JZ-2520-2C'),
(30, 'QB', 'JZ-4020'),
(31, 'QE', 'JZ-3020'),
(32, 'QM', 'JZ-2820'),
(33, 'RA', 'RU-5040-1'),
(34, 'RG', 'RB-6440'),
(35, 'RH', 'GB-6440-05'),
(36, 'RK', 'KB-6440'),
(37, 'RL', 'GB-7640'),
(38, 'RN', 'RB-5040-2'),
(39, 'RR', 'GB-6440'),
(40, 'RU', 'RU-5040-1-S101'),
(41, 'TG', 'TU-4020'),
(42, 'V', 'RS-3020'),
(43, 'Z', 'TU-2620'),
(44, 'JCM', 'DS-8460'),
(45, 'JPM', 'MV-4020'),
(46, 'JQA', 'JZ-2520'),
(47, 'JQE', 'JZ-3020');

-- --------------------------------------------------------

--
-- Table structure for table `mother_coil`
--

CREATE TABLE `mother_coil` (
  `id` int NOT NULL,
  `coil_no` varchar(100) NOT NULL,
  `product` varchar(100) NOT NULL,
  `lot_no` varchar(100) NOT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `width` varchar(100) NOT NULL,
  `length` varchar(100) NOT NULL,
  `status` enum('NEW','IN','OUT') DEFAULT 'NEW',
  `date_in` datetime DEFAULT NULL,
  `date_out` datetime DEFAULT NULL,
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mother_coil_log`
--

CREATE TABLE `mother_coil_log` (
  `id` int NOT NULL,
  `mother_id` int NOT NULL,
  `status` enum('IN','OUT') NOT NULL,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nci_product_mapping`
--

CREATE TABLE `nci_product_mapping` (
  `id` int NOT NULL,
  `internal_code` varchar(50) DEFAULT NULL,
  `grade` varchar(100) DEFAULT NULL,
  `width` varchar(50) DEFAULT NULL,
  `customer` varchar(100) DEFAULT NULL,
  `part_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nci_product_mapping`
--

INSERT INTO `nci_product_mapping` (`id`, `internal_code`, `grade`, `width`, `customer`, `part_no`, `created_at`) VALUES
(1, 'A-115', 'RS-3825', '115 mm', 'DELPHI (Mexico)', '6572050/6572051', '2026-01-22 07:20:23'),
(2, 'A-120', 'RS-3825', '120 mm', 'DELPHI (Brazil)', '06571928 / 06571927 / 06572176', '2026-01-22 07:20:23'),
(3, 'G-125', 'RS-4020', '125 mm', 'DELPHI (Mexico)', '06571982', '2026-01-22 07:20:23'),
(4, 'KB-101', 'KB-6440', '101 mm', 'AMBRAKE', '51-A3826-67434', '2026-01-22 07:20:23'),
(5, 'KB-111', 'KB-6440', '111 mm', 'AMBRAKE', 'AB-A4315-67430', '2026-01-22 07:20:23'),
(6, 'KB-113', 'KB-6440', '113 mm', 'ADVICS', '115-5314', '2026-01-22 07:20:23'),
(7, 'KB-136', 'KB-6440', '136 mm', 'ADVICS', '115-5704', '2026-01-22 07:20:23'),
(8, 'KB-137', 'KB-6440', '137 mm', 'ADVICS', '115-5704', '2026-01-22 07:20:23'),
(9, 'KB-141', 'KB-6440', '141 mm', 'ADVICS', '115-5315', '2026-01-22 07:20:23'),
(10, 'KB-155', 'KB-6440', '155 mm', 'AMBRAKE', '51-E4532-57431', '2026-01-22 07:20:23'),
(11, 'KB-167', 'KB-6440', '167 mm', 'AMAK / AMBRAKE', '51-E5112-57431 / AB-E5111-57431', '2026-01-22 07:20:23'),
(12, 'KB-210', 'KB-6440', '210 mm', 'AMAK', '51-A5739-57430', '2026-01-22 07:20:23'),
(13, 'N-313', 'TU-3020', '313 mm', 'TOYOTA', '17177/17178-0P020', '2026-01-22 07:20:23'),
(14, 'P-154', 'TS-3525', '154 mm', 'AAC', '213231-12090 (Plate Gasket)', '2026-01-22 07:20:23'),
(15, 'P-89', 'TS-3525', '89 mm', 'TOYOTA', '15147-0P020', '2026-01-22 07:20:23'),
(16, 'TG-313', 'TU-4020', '313 mm', 'AAC', '213231-12080 (WPG MK)', '2026-01-22 07:20:23');

-- --------------------------------------------------------

--
-- Table structure for table `raw_material_log`
--

CREATE TABLE `raw_material_log` (
  `id` int NOT NULL,
  `product` varchar(255) NOT NULL,
  `lot_no` varchar(100) NOT NULL,
  `coil_no` varchar(50) DEFAULT NULL,
  `width` varchar(100) NOT NULL,
  `length` decimal(10,2) NOT NULL,
  `status` enum('IN','OUT') NOT NULL,
  `action` varchar(50) DEFAULT NULL COMMENT 'normal or cut_into_2',
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_in` datetime DEFAULT NULL,
  `date_out` datetime DEFAULT NULL,
  `remark` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recoiling_product`
--

CREATE TABLE `recoiling_product` (
  `id` int NOT NULL,
  `status` enum('pending','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `product` varchar(100) NOT NULL,
  `lot_no` varchar(100) NOT NULL,
  `coil_no` varchar(100) NOT NULL,
  `roll_no` varchar(100) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `new_width` decimal(10,2) DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `new_length` decimal(10,2) DEFAULT NULL,
  `actual_length` decimal(10,2) DEFAULT NULL,
  `date_in` datetime DEFAULT CURRENT_TIMESTAMP,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cut_type` varchar(50) DEFAULT NULL,
  `remark` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reslit_product`
--

CREATE TABLE `reslit_product` (
  `id` int NOT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `product` varchar(100) DEFAULT NULL,
  `lot_no` varchar(100) DEFAULT NULL,
  `coil_no` varchar(100) DEFAULT NULL,
  `roll_no` varchar(100) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `new_width` decimal(10,2) DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `date_in` datetime DEFAULT CURRENT_TIMESTAMP,
  `qr_code` varchar(255) DEFAULT NULL,
  `cut_type` varchar(50) DEFAULT NULL,
  `actual_length` decimal(10,2) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `date_reslit` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reslit_rolls`
--

CREATE TABLE `reslit_rolls` (
  `id` int NOT NULL,
  `parent_id` int NOT NULL,
  `roll_no` varchar(50) NOT NULL,
  `cut_letter` varchar(10) DEFAULT NULL,
  `new_width` decimal(10,2) DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `actual_length` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `slitting_product`
--

CREATE TABLE `slitting_product` (
  `id` int NOT NULL,
  `product` varchar(100) DEFAULT NULL,
  `lot_no` varchar(100) DEFAULT NULL,
  `coil_no` varchar(50) DEFAULT NULL,
  `roll_no` varchar(100) DEFAULT NULL,
  `width` varchar(50) DEFAULT NULL,
  `length` varchar(50) DEFAULT NULL,
  `actual_length` varchar(50) DEFAULT NULL,
  `length_type` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'IN',
  `is_completed` tinyint(1) DEFAULT '0',
  `stock_counted` tinyint(1) DEFAULT '0',
  `date_in` date NOT NULL DEFAULT (curdate()),
  `date_out` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `mother_id` int DEFAULT NULL,
  `cut_type` varchar(20) DEFAULT 'normal' COMMENT 'normal or cut_into_2',
  `slit_quantity` decimal(10,2) DEFAULT NULL COMMENT 'Quantity untuk cut into 2',
  `stock_value` decimal(10,2) DEFAULT NULL COMMENT 'Stock amount yang return ke mother coil',
  `stock_mother_id` int DEFAULT NULL COMMENT 'Reference to new mother coil created from stock',
  `is_recoiled` tinyint(1) DEFAULT '0',
  `recoil_reason` varchar(255) DEFAULT NULL,
  `is_reslitted` tinyint(1) DEFAULT '0',
  `reslit_reason` varchar(255) DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `cut_reason` varchar(255) DEFAULT NULL,
  `stock` decimal(10,2) DEFAULT NULL,
  `std_weight` decimal(10,4) DEFAULT '0.0000' COMMENT 'Standard weight for calculation',
  `recoiling_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `std_wgt`
--

CREATE TABLE `std_wgt` (
  `id` int NOT NULL,
  `product_code` varchar(100) NOT NULL,
  `std_weight` decimal(10,4) NOT NULL COMMENT 'Standard weight for calculation',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Standard weight lookup table';

--
-- Dumping data for table `std_wgt`
--

INSERT INTO `std_wgt` (`id`, `product_code`, `std_weight`, `created_at`, `updated_at`) VALUES
(1, 'DS-3020', 1.7300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(2, 'DS-3825', 2.1690, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(3, 'DS-4525', 2.2600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(4, 'DS-5030', 2.6600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(5, 'DS-8460', 5.1100, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(6, 'GB-6440', 3.5100, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(7, 'GB-6440-S101', 3.5100, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(8, 'GB-7640', 3.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(9, 'JZ-2520', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(10, 'JZ-2520-2C', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(11, 'JZ-2820', 1.7000, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(12, 'JZ-3020', 1.7300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(13, 'JZ-4020', 1.8600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(14, 'KB-6440', 3.5120, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(15, 'L1N2-2520-02', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(16, 'LN-1715-1', 1.5600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(17, 'LN-2520', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(18, 'LN-2520-02', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(19, 'LN-2520-04', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(20, 'LZ-2420', 1.6100, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(21, 'LZ-2520', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(22, 'MV-4020', 1.7300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(23, 'PS-6020', 1.9100, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(24, 'PS-8525', 2.2400, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(25, 'RS-3020', 1.7300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(26, 'RS-3825', 2.1690, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(27, 'RS-3825-04', 2.1690, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(28, 'RS-4020', 1.8600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(29, 'RS-4025', 2.1900, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(30, 'RS-4525', 2.2600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(31, 'RS-5030', 2.6600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(32, 'RS-6040', 3.4600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(33, 'RS-7050', 4.2600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(34, 'RU-5040-1', 3.3300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(35, 'RU-5040-1-S101', 3.3300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(36, 'RV-3825', 2.1690, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(37, 'TS-2620', 1.6780, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(38, 'TS-3020', 1.7300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(39, 'TS-3525', 2.1300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(40, 'TS-3525-SG', 2.1300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(41, 'TS-4025', 2.1900, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(42, 'TS-4525', 2.2600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(43, 'TS-5030', 2.6600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(44, 'TS-9080', 6.5300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(45, 'TS-9080-SG', 6.5300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(46, 'TU-2620', 1.6780, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(47, 'TU-2620-C', 1.6790, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(48, 'TU-3020', 1.7300, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(49, 'TU-4020', 1.8600, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(50, 'JV-3825', 2.1690, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(51, 'YW-2520-SG', 1.6700, '2026-01-13 06:45:32', '2026-01-13 06:45:32'),
(52, 'HBV-4020', 1.7300, '2026-01-14 08:37:08', '2026-01-14 08:37:08');

-- --------------------------------------------------------

--
-- Table structure for table `stock_raw_material`
--

CREATE TABLE `stock_raw_material` (
  `id` int NOT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `lot_no` varchar(100) DEFAULT NULL,
  `coil_no` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  `date_in` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'slitting', '$2y$10$8utZ6odMkhIqAK/YZrNS2u7q8zDYRd/rXnNHhr1hzCub1b9evcF.K', 'slitting'),
(2, 'mkl3', '$2y$10$uA0aX6ot0Mh9.5s/b/Iap.FQKh03/YzN2nCzaGmvGo4JtIzamvMSu', 'mkl3'),
(3, 'qc', '$2y$10$IZOy3yJdOyLtebF6LtFzJONV9O5Eg7qtdrTHBwdpKkaRsg7bIKwve', 'qc');

-- --------------------------------------------------------

--
-- Table structure for table `waiting_approval`
--

CREATE TABLE `waiting_approval` (
  `id` int NOT NULL,
  `finish_id` int NOT NULL,
  `status` enum('PENDING','APPROVED','DELIVERED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `coil_product_map`
--
ALTER TABLE `coil_product_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coil_code` (`coil_code`);

--
-- Indexes for table `mother_coil`
--
ALTER TABLE `mother_coil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lot_no` (`lot_no`);

--
-- Indexes for table `mother_coil_log`
--
ALTER TABLE `mother_coil_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nci_product_mapping`
--
ALTER TABLE `nci_product_mapping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `raw_material_log`
--
ALTER TABLE `raw_material_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recoiling_product`
--
ALTER TABLE `recoiling_product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reslit_product`
--
ALTER TABLE `reslit_product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reslit_rolls`
--
ALTER TABLE `reslit_rolls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `slitting_product`
--
ALTER TABLE `slitting_product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recoiling_id` (`recoiling_id`);

--
-- Indexes for table `std_wgt`
--
ALTER TABLE `std_wgt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product` (`product_code`),
  ADD KEY `idx_product` (`product_code`);

--
-- Indexes for table `stock_raw_material`
--
ALTER TABLE `stock_raw_material`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `waiting_approval`
--
ALTER TABLE `waiting_approval`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `coil_product_map`
--
ALTER TABLE `coil_product_map`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `mother_coil`
--
ALTER TABLE `mother_coil`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mother_coil_log`
--
ALTER TABLE `mother_coil_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nci_product_mapping`
--
ALTER TABLE `nci_product_mapping`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `raw_material_log`
--
ALTER TABLE `raw_material_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recoiling_product`
--
ALTER TABLE `recoiling_product`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reslit_product`
--
ALTER TABLE `reslit_product`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reslit_rolls`
--
ALTER TABLE `reslit_rolls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `slitting_product`
--
ALTER TABLE `slitting_product`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `std_wgt`
--
ALTER TABLE `std_wgt`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `stock_raw_material`
--
ALTER TABLE `stock_raw_material`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `waiting_approval`
--
ALTER TABLE `waiting_approval`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
