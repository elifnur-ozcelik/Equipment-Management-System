-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 29, 2025 at 01:19 AM
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
-- Database: `ctis256`
--

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_tag_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `availability` enum('available','reserved','checked_out') COLLATE utf8mb4_turkish_ci DEFAULT 'available',
  `total_borrowed` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_tag_id` (`asset_tag_id`),
  UNIQUE KEY `asset_tag_id_2` (`asset_tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=798 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
