-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2024 at 06:57 PM
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
-- Database: `condition_report`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminuser`
--

CREATE TABLE `adminuser` (
  `id` int(11) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adminuser`
--

INSERT INTO `adminuser` (`id`, `email`, `password`) VALUES
(1, 'faiq2001@gmail.com', '$2y$10$RirvofsKI.4eitSmwf0KT.9SMpYqZ/IEJCc6fvD1yIrqrrKfFF/DO');

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `email` varchar(255) NOT NULL,
  `catnum` varchar(255) NOT NULL,
  `specname` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `examination` varchar(255) NOT NULL,
  `speccond` tinyint(4) NOT NULL,
  `material` varchar(255) NOT NULL,
  `workmeth` text NOT NULL,
  `inspectname` varchar(255) NOT NULL,
  `remarks` text NOT NULL,
  `appID` varchar(8) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`email`, `catnum`, `specname`, `location`, `examination`, `speccond`, `material`, `workmeth`, `inspectname`, `remarks`, `appID`, `status`) VALUES
('test@example.com', 'dasd', 'asdasd', 'Natural History Building', 'Dirt Accumulation (Pengumpulan kotoran)', 2, 'Japanese Tissue, Wood Clay', 'dsadsad', 'Jane Smith', 'dsadas', 'APR72830', 2),
('test@example.com', 'test', 'test', 'Natural History Building', 'Dirt Accumulation (Pengumpulan kotoran), Stitch opening (Jahitan terbuka)', 1, 'Japanese Tissue, Wood Clay', 'test', 'John Doe', 'test', 'APR80630', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adminuser`
--
ALTER TABLE `adminuser`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`appID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adminuser`
--
ALTER TABLE `adminuser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
