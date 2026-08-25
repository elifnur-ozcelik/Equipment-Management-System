-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 29, 2025 at 01:15 AM
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
-- Table structure for table `quick_comments`
--

DROP TABLE IF EXISTS `quick_comments`;
CREATE TABLE IF NOT EXISTS `quick_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_text` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `comment_type` enum('approve','reject','general') COLLATE utf8mb4_turkish_ci DEFAULT 'general',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Dumping data for table `quick_comments`
--

INSERT INTO `quick_comments` (`id`, `comment_text`, `comment_type`) VALUES
(1, 'Approved. Please return the item in good condition by the due date.', 'approve'),
(2, 'Approved. You can collect the item from the lab during office hours.', 'approve'),
(3, 'Rejected. This item is reserved for another course project.', 'reject'),
(4, 'Rejected. Please provide a more detailed purpose for your request.', 'reject'),
(5, 'Please contact me via email for further discussion before approval.', 'general');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
