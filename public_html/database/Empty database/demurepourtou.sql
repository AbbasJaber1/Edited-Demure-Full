-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2025 at 03:14 PM
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
-- Database: `demurepourtou`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cloth`
--

CREATE TABLE `cloth` (
  `ID` int(11) NOT NULL,
  `Source` varchar(100) DEFAULT NULL,
  `ClothName` varchar(100) DEFAULT NULL,
  `YardQuantity` decimal(10,2) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `UsedQuantity` decimal(10,2) DEFAULT NULL,
  `RemainingQuantity` decimal(10,2) DEFAULT NULL,
  `RemainingClothLocation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `ID` int(11) NOT NULL,
  `Code` varchar(50) NOT NULL,
  `Color` varchar(100) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Size` int(11) NOT NULL,
  `MediaType` varchar(10) DEFAULT NULL,
  `ImagePath` varchar(255) DEFAULT NULL,
  `StockPrice` decimal(10,2) NOT NULL,
  `WholeSalePrice` decimal(10,2) NOT NULL,
  `RetailPrice` decimal(10,2) NOT NULL,
  `Category` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onlineorderretail`
--

CREATE TABLE `onlineorderretail` (
  `ID` int(11) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `ItemID` int(11) DEFAULT NULL,
  `Code` varchar(50) DEFAULT NULL,
  `Color` varchar(50) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Size` varchar(20) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `SelledPrice` decimal(10,2) DEFAULT NULL,
  `Discount` decimal(10,2) DEFAULT NULL,
  `StockPrice` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onlineorderwholesale`
--

CREATE TABLE `onlineorderwholesale` (
  `ID` int(11) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `ItemID` int(11) DEFAULT NULL,
  `Code` varchar(50) DEFAULT NULL,
  `Color` varchar(50) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Size` varchar(20) DEFAULT NULL,
  `UnitPrice` decimal(10,2) DEFAULT NULL,
  `SelledPrice` decimal(10,2) DEFAULT NULL,
  `Discount` decimal(10,2) DEFAULT NULL,
  `StockPrice` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `ID` int(11) NOT NULL,
  `InitialTotalPrice` decimal(10,2) NOT NULL,
  `TotalPriceWithDiscount` decimal(10,2) NOT NULL,
  `Discount` decimal(10,2) NOT NULL,
  `Profit` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderretail`
--

CREATE TABLE `orderretail` (
  `ID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ItemID` int(11) NOT NULL,
  `Code` varchar(50) NOT NULL,
  `Color` varchar(100) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Size` int(11) NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `SelledPrice` decimal(10,2) NOT NULL,
  `Discount` decimal(10,2) NOT NULL,
  `StockPrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderwholesale`
--

CREATE TABLE `orderwholesale` (
  `ID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ItemID` int(11) NOT NULL,
  `Code` varchar(50) NOT NULL,
  `Color` varchar(100) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Size` int(11) NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `SelledPrice` decimal(10,2) NOT NULL,
  `Discount` decimal(10,2) NOT NULL,
  `StockPrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `size`
--

CREATE TABLE `size` (
  `ID` int(11) NOT NULL,
  `Size` enum('8','10','12','14','16','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `size`
--

INSERT INTO `size` (`ID`, `Size`) VALUES
(1, '8'),
(2, '10'),
(3, '12'),
(4, '14'),
(5, '16'),
(6, '0');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `UserName` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Privilege` enum('admin','worker','user') NOT NULL,
  `email` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expire` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`ID`, `UserName`, `Password`, `Privilege`, `email`, `reset_token`, `token_expire`) VALUES
(1, 'Diyaa', '$2y$10$gNS5biFuOq83cHYnc4m8eeN2X2hdFdXTDaNdShdcQ4R2/E7GX5Fa6', 'admin', 'Diyaajaber1993@gmail.com', NULL, NULL),
(2, 'Ahmad', '$2y$10$A5RJd0/iKL7d0RAMtWB8BeYLUnfTIYj.JcTBLa9FhO5NRKBiXiuV2', 'admin', 'ahmadmalak892014@gmail.com', NULL, NULL),
(6, 'Abbas', '$2y$10$mrJg.2E8BFh/NAoT8OQC9upxjcZDOB9k50y7TQOO1j0k7o9jLjsMy', 'worker', 'abbasjaber9090@gmail.com', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cloth`
--
ALTER TABLE `cloth`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Size` (`Size`),
  ADD KEY `Category` (`Category`);

--
-- Indexes for table `onlineorderretail`
--
ALTER TABLE `onlineorderretail`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ItemID` (`ItemID`);

--
-- Indexes for table `onlineorderwholesale`
--
ALTER TABLE `onlineorderwholesale`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ItemID` (`ItemID`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `orderretail`
--
ALTER TABLE `orderretail`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ItemID` (`ItemID`),
  ADD KEY `Size` (`Size`);

--
-- Indexes for table `orderwholesale`
--
ALTER TABLE `orderwholesale`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ItemID` (`ItemID`),
  ADD KEY `Size` (`Size`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_action` (`ip_address`,`action`),
  ADD KEY `idx_time` (`attempt_time`);

--
-- Indexes for table `size`
--
ALTER TABLE `size`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UserName` (`UserName`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cloth`
--
ALTER TABLE `cloth`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `onlineorderretail`
--
ALTER TABLE `onlineorderretail`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `onlineorderwholesale`
--
ALTER TABLE `onlineorderwholesale`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `orderretail`
--
ALTER TABLE `orderretail`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `orderwholesale`
--
ALTER TABLE `orderwholesale`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `size`
--
ALTER TABLE `size`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`Size`) REFERENCES `size` (`ID`),
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`Category`) REFERENCES `category` (`ID`);

--
-- Constraints for table `onlineorderretail`
--
ALTER TABLE `onlineorderretail`
  ADD CONSTRAINT `onlineorderretail_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`ID`),
  ADD CONSTRAINT `onlineorderretail_ibfk_2` FOREIGN KEY (`ItemID`) REFERENCES `items` (`ID`);

--
-- Constraints for table `onlineorderwholesale`
--
ALTER TABLE `onlineorderwholesale`
  ADD CONSTRAINT `onlineorderwholesale_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`ID`),
  ADD CONSTRAINT `onlineorderwholesale_ibfk_2` FOREIGN KEY (`ItemID`) REFERENCES `items` (`ID`);

--
-- Constraints for table `orderretail`
--
ALTER TABLE `orderretail`
  ADD CONSTRAINT `orderretail_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`ID`),
  ADD CONSTRAINT `orderretail_ibfk_2` FOREIGN KEY (`ItemID`) REFERENCES `items` (`ID`),
  ADD CONSTRAINT `orderretail_ibfk_3` FOREIGN KEY (`Size`) REFERENCES `size` (`ID`);

--
-- Constraints for table `orderwholesale`
--
ALTER TABLE `orderwholesale`
  ADD CONSTRAINT `orderwholesale_ibfk_2` FOREIGN KEY (`ItemID`) REFERENCES `items` (`ID`),
  ADD CONSTRAINT `orderwholesale_ibfk_3` FOREIGN KEY (`Size`) REFERENCES `size` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
