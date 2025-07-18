-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2025 at 01:35 PM
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

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`ID`, `Name`) VALUES
(1, 'T-shirt'),
(2, 'pants'),
(3, 'Hijab'),
(4, 'jizdan'),
(5, 'hat');

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

--
-- Dumping data for table `cloth`
--

INSERT INTO `cloth` (`ID`, `Source`, `ClothName`, `YardQuantity`, `Price`, `UsedQuantity`, `RemainingQuantity`, `RemainingClothLocation`) VALUES
(8, 'asda', 'ad', 123.00, 12.00, 50.00, 73.00, 'habbouch'),
(12, 'mardine', 'harir', 100.00, 2.00, 70.00, 30.00, 'nabatieh'),
(13, 'adad', 'as', 145.00, 12.00, 0.00, 145.00, 'overthere'),
(14, 'adad', 'qwe1', 234.00, 12.00, 0.00, 234.00, 'there'),
(15, 'habib faleha', 'lenen', 48.00, 2.25, 30.00, 18.00, 'here'),
(16, 'wad', 'sd', 123.00, 123.00, 0.00, 123.00, 'qwd');

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

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`ID`, `Code`, `Color`, `Quantity`, `Size`, `MediaType`, `ImagePath`, `StockPrice`, `WholeSalePrice`, `RetailPrice`, `Category`) VALUES
(3, 'eff43-56', 'aaa', 20, 1, 'image/png', 'uploads/685a82f55dab0.png', 1.00, 2.00, 3.00, 1),
(4, 'df-45', 'green', 19, 1, 'image/png', 'uploads/685a924a8b612.png', 10.00, 12.00, 15.00, 1),
(6, 'df-45', 'red', 20, 2, 'image/jpeg', 'uploads/685c07146cd1a.jpeg', 1.00, 2.00, 5.00, 1),
(7, 'df-45', 'green', 20, 4, 'image/png', 'uploads/685c0b92a059f.png', 1.00, 2.00, 5.00, 1),
(8, '096', 'mint', 18, 6, 'image/jpeg', 'uploads/685e76bdbc7ff.jpg', 6.00, 0.00, 10.00, 4);

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

--
-- Dumping data for table `onlineorderretail`
--

INSERT INTO `onlineorderretail` (`ID`, `OrderID`, `ItemID`, `Code`, `Color`, `Quantity`, `Size`, `UnitPrice`, `SelledPrice`, `Discount`, `StockPrice`) VALUES
(1, 90, 4, 'df-45', 'green', 1, '1', 15.00, 15.00, 0.00, 10.00),
(2, 91, 4, 'df-45', 'green', 1, '1', 15.00, 12.00, 20.00, 10.00);

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

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`ID`, `InitialTotalPrice`, `TotalPriceWithDiscount`, `Discount`, `Profit`, `order_date`, `status`) VALUES
(26, 18.00, 15.00, 60.00, 4.00, '2025-06-25 17:24:39', 'submitted'),
(27, 23.00, 21.50, 1.50, 9.50, '2025-06-25 17:47:03', 'submitted'),
(28, 6.00, 6.00, 0.00, 4.00, '2025-06-27 07:34:43', 'submitted'),
(29, 3.00, 3.00, 0.00, 2.00, '2025-06-27 07:38:43', 'submitted'),
(72, 15.00, 15.00, 0.00, 5.00, '2025-06-27 08:07:44', 'submitted'),
(73, 48.00, 38.40, 9.60, -1.60, '2025-06-27 08:09:48', 'submitted'),
(74, 3.00, 3.00, 0.00, 2.00, '2025-06-27 08:19:32', 'submitted'),
(75, 13.00, 13.00, 0.00, 6.00, '2025-06-27 14:05:19', 'submitted'),
(76, 35.00, 28.50, 6.50, 10.50, '2025-06-30 09:28:55', 'submitted'),
(77, 20.00, 18.75, 1.25, 10.75, '2025-06-30 09:30:45', 'submitted'),
(78, 18.00, 16.20, 1.80, 5.20, '2025-06-30 10:00:49', 'submitted'),
(79, 20.00, 16.50, 17.50, 5.50, '2025-06-30 10:08:22', 'submitted'),
(80, 5.00, 5.00, 0.00, 4.00, '2025-07-06 14:30:30', 'submitted'),
(81, 5.00, 5.00, 0.00, 4.00, '2025-07-06 15:37:14', 'submitted'),
(82, 5.00, 5.00, 0.00, 4.00, '2025-07-06 15:45:33', 'submitted'),
(83, 38.00, 38.00, 0.00, 16.00, '2025-07-06 15:45:58', 'submitted'),
(84, 20.00, 20.00, 0.00, 9.00, '2025-07-06 15:46:25', 'submitted'),
(85, 20.00, 20.00, 0.00, 9.00, '2025-07-08 11:26:49', 'submitted'),
(86, 30.00, 30.00, 0.00, 10.00, '2025-07-08 13:07:19', 'submitted'),
(87, 20.00, 20.00, 0.00, 9.00, '2025-07-08 13:32:29', 'submitted'),
(88, 30.00, 30.00, 0.00, 13.00, '2025-07-08 13:42:27', 'submitted'),
(89, 30.00, 30.00, 0.00, 13.00, '2025-07-11 12:25:58', 'submitted'),
(90, 15.00, 15.00, 0.00, 5.00, '2025-07-11 14:09:22', 'submitted'),
(91, 15.00, 12.00, 20.00, 2.00, '2025-07-11 14:09:44', 'submitted'),
(92, 5.00, 5.00, 0.00, 4.00, '2025-07-11 14:17:22', 'submitted'),
(93, 15.00, 15.00, 0.00, 5.00, '2025-07-11 06:38:59', 'submitted'),
(94, 10.00, 10.00, 0.00, 4.00, '2025-07-11 06:39:59', 'submitted'),
(95, 10.00, 10.00, 0.00, 4.00, '2025-07-11 06:40:30', 'submitted');

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

--
-- Dumping data for table `orderretail`
--

INSERT INTO `orderretail` (`ID`, `OrderID`, `ItemID`, `Code`, `Color`, `Quantity`, `Size`, `UnitPrice`, `SelledPrice`, `Discount`, `StockPrice`) VALUES
(1, 26, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 1.50, 50.00, 1.00),
(2, 26, 4, 'df-45', 'green', 1, 1, 15.00, 13.50, 10.00, 10.00),
(3, 27, 7, 'df-45', 'green', 1, 4, 5.00, 5.00, 0.00, 1.00),
(4, 27, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(5, 27, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 1.50, 50.00, 1.00),
(6, 28, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 3.00, 0.00, 1.00),
(7, 28, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 3.00, 0.00, 1.00),
(8, 29, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 3.00, 0.00, 1.00),
(51, 72, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(52, 74, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 3.00, 0.00, 1.00),
(53, 75, 8, '096', 'mint', 1, 6, 10.00, 10.00, 0.00, 6.00),
(54, 75, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 3.00, 0.00, 1.00),
(55, 76, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(56, 76, 6, 'df-45', 'red', 1, 2, 5.00, 4.50, 10.00, 1.00),
(57, 76, 4, 'df-45', 'green', 1, 1, 15.00, 12.00, 20.00, 10.00),
(58, 76, 8, '096', 'mint', 1, 6, 10.00, 7.00, 30.00, 6.00),
(59, 77, 8, '096', 'mint', 1, 6, 10.00, 9.00, 10.00, 6.00),
(60, 77, 6, 'df-45', 'red', 1, 2, 5.00, 4.75, 5.00, 1.00),
(61, 77, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(62, 78, 4, 'df-45', 'green', 1, 1, 15.00, 13.50, 10.00, 10.00),
(63, 78, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 2.70, 10.00, 3.00),
(64, 79, 8, '096', 'mint', 1, 6, 10.00, 10.00, 20.00, 10.00),
(65, 79, 8, '096', 'mint', 1, 6, 10.00, 10.00, 10.00, 10.00),
(66, 80, 8, '096', 'mint', 1, 6, 10.00, 5.00, 0.00, 10.00),
(67, 81, 7, 'df-45', 'green', 1, 4, 5.00, 5.00, 0.00, 1.00),
(68, 82, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(69, 83, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(70, 83, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(71, 83, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(72, 83, 3, 'eff43-56', 'aaa', 1, 1, 3.00, 3.00, 0.00, 1.00),
(73, 84, 7, 'df-45', 'green', 1, 4, 15.00, 15.00, 0.00, 10.00),
(74, 84, 7, 'df-45', 'green', 1, 4, 5.00, 5.00, 0.00, 1.00),
(75, 85, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(76, 85, 7, 'df-45', 'green', 1, 4, 15.00, 15.00, 0.00, 10.00),
(77, 86, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(78, 86, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(79, 87, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(80, 87, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(81, 88, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(82, 88, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(83, 88, 8, '096', 'mint', 1, 6, 10.00, 10.00, 0.00, 6.00),
(84, 89, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(85, 89, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(86, 89, 8, '096', 'mint', 1, 6, 10.00, 10.00, 0.00, 6.00),
(87, 92, 6, 'df-45', 'red', 1, 2, 5.00, 5.00, 0.00, 1.00),
(88, 93, 4, 'df-45', 'green', 1, 1, 15.00, 15.00, 0.00, 10.00),
(89, 94, 8, '096', 'mint', 1, 6, 10.00, 10.00, 0.00, 6.00),
(90, 95, 8, '096', 'mint', 1, 6, 10.00, 10.00, 0.00, 6.00);

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

--
-- Dumping data for table `orderwholesale`
--

INSERT INTO `orderwholesale` (`ID`, `OrderID`, `ItemID`, `Code`, `Color`, `Quantity`, `Size`, `UnitPrice`, `SelledPrice`, `Discount`, `StockPrice`) VALUES
(1, 73, 4, 'df-45', 'green', 4, 1, 12.00, 9.60, 20.00, 10.00);

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
(1, 'Diyaa', 'Diyaa@Jaber$Hallel', 'admin', 'Diyaajaber1993@gmail.com', NULL, NULL),
(2, 'Ahmad', 'Ahmad@Hallel$Jaber', 'admin', 'ahamd@gmail.com', NULL, NULL),
(6, 'Abbas', 'abbas123', 'worker', 'abbasjaber9090@gmail.com', NULL, NULL);

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `orderretail`
--
ALTER TABLE `orderretail`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `orderwholesale`
--
ALTER TABLE `orderwholesale`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `size`
--
ALTER TABLE `size`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
