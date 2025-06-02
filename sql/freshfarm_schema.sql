-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 12:46 PM
-- Server version: 11.7.2-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `freshfarm`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `consumer_id`, `product_id`, `quantity`, `added_at`, `price`) VALUES
(14, 5, 9, 1, '2025-06-02 04:14:26', 20.00),
(15, 5, 10, 1, '2025-06-02 04:27:51', 5.00),
(16, 5, 11, 1, '2025-06-02 10:40:26', 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `image_url`) VALUES
(1, 'Vegetables', 'Fresh and organic vegetables', 'vegetables.jpg'),
(2, 'Fruits', 'Seasonal and local fruits', 'fruits.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `favorite_farmers`
--

CREATE TABLE `favorite_farmers` (
  `id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `message`, `reference_id`, `is_read`, `created_at`) VALUES
(2, 4, 'new_order', 'You have received a new order!', 7, 0, '2025-05-21 10:28:01'),
(3, 4, 'new_order', 'You have received a new order!', 8, 0, '2025-05-21 10:29:24'),
(4, 4, 'new_order', 'You have received a new order!', 9, 0, '2025-05-22 04:43:58'),
(5, 4, 'new_order', 'You have received a new order!', 10, 0, '2025-05-22 05:15:40'),
(6, 4, 'new_order', 'You have received a new order!', 27, 0, '2025-06-01 06:35:48'),
(7, 4, 'new_order', 'You have received a new order!', 28, 0, '2025-06-01 09:03:10'),
(8, 4, 'new_order', 'You have received a new order!', 29, 0, '2025-06-01 09:29:29'),
(9, 4, 'new_order', 'You have received a new order for product: pineapple (Qty: 1)', 30, 0, '2025-06-02 04:01:15'),
(10, 4, 'new_order', 'You have received a new order for product: Apple (Qty: 2)', 30, 0, '2025-06-02 04:01:15'),
(11, 4, 'new_order', 'You have received a new order for product: Apple (Qty: 1)', 31, 0, '2025-06-02 04:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','canceled') DEFAULT 'pending',
  `order_date` datetime DEFAULT current_timestamp(),
  `shipping_address` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_zipcode` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `consumer_id`, `total_amount`, `status`, `order_date`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_zipcode`, `payment_method`, `transaction_id`, `updated_at`) VALUES
(1, 2, 10.00, 'shipped', '2025-05-15 09:23:09', '456 City St', 'Metro', 'NY', '10001', 'COD', 'TXN123456', '2025-05-18 21:01:08'),
(7, 5, 15.00, 'delivered', '2025-05-21 16:13:01', 'kalanki', NULL, NULL, NULL, 'cash_on_delivery', NULL, '2025-05-21 16:15:51'),
(8, 5, 8.00, 'delivered', '2025-05-21 16:14:24', 'kalanki', NULL, NULL, NULL, 'bank_transfer', NULL, '2025-05-21 16:15:43'),
(9, 5, 2.00, 'delivered', '2025-05-22 10:28:58', 'kalanki', NULL, NULL, NULL, 'cash_on_delivery', NULL, '2025-05-22 10:29:35'),
(10, 5, 2.00, 'delivered', '2025-05-22 11:00:40', 'kalanki', NULL, NULL, NULL, 'bank_transfer', NULL, '2025-05-22 11:01:18'),
(27, 5, 10.00, 'delivered', '2025-06-01 12:20:48', 'kalanki', NULL, NULL, NULL, 'cash_on_delivery', NULL, '2025-06-01 15:12:17'),
(28, 5, 20.00, 'delivered', '2025-06-01 14:48:10', 'kalanki', NULL, NULL, NULL, 'esewa', NULL, '2025-06-01 14:59:25'),
(29, 5, 20.00, 'pending', '2025-06-01 15:14:29', 'kalanki', NULL, NULL, NULL, 'cod', NULL, '2025-06-01 15:14:29'),
(30, 5, 30.00, 'canceled', '2025-06-02 09:46:15', 'kalanki', NULL, NULL, NULL, 'cod', NULL, '2025-06-02 15:47:03'),
(31, 5, 5.00, 'shipped', '2025-06-02 09:57:38', 'kalanki', NULL, NULL, NULL, 'esewa', NULL, '2025-06-02 14:35:59');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price_per_unit`, `subtotal`, `price`) VALUES
(1, 1, 1, 2, 2.50, 5.00, 0.00),
(2, 1, 2, 1, 5.00, 5.00, 0.00),
(5, 7, 7, 1, NULL, NULL, 15.00),
(6, 8, 8, 4, NULL, NULL, 2.00),
(7, 9, 8, 1, NULL, NULL, 2.00),
(8, 10, 8, 1, NULL, NULL, 2.00),
(9, 27, 10, 2, 5.00, 10.00, 0.00),
(10, 28, 9, 1, 20.00, 20.00, 0.00),
(11, 29, 9, 1, 20.00, 20.00, 0.00),
(12, 30, 9, 1, 20.00, 20.00, 0.00),
(13, 30, 10, 2, 5.00, 10.00, 0.00),
(14, 31, 10, 1, 5.00, 5.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `history_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`history_id`, `order_id`, `status`, `notes`, `created_at`) VALUES
(1, 7, 'pending', 'Order placed successfully', '2025-05-21 10:28:01'),
(2, 8, 'pending', 'Order placed successfully', '2025-05-21 10:29:24'),
(3, 9, 'pending', 'Order placed successfully', '2025-05-22 04:43:58'),
(4, 10, 'pending', 'Order placed successfully', '2025-05-22 05:15:40'),
(5, 27, 'pending', 'Order placed successfully', '2025-06-01 06:35:48'),
(6, 28, 'pending', 'Order placed successfully', '2025-06-01 09:03:10'),
(7, 29, 'pending', 'Order placed successfully', '2025-06-01 09:29:29'),
(8, 30, 'pending', 'Order placed successfully', '2025-06-02 04:01:15'),
(9, 31, 'pending', 'Order placed successfully', '2025-06-02 04:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `quantity_available` int(11) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `organic` tinyint(1) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_available` tinyint(1) DEFAULT 1,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `product_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `seller_id`, `category_id`, `name`, `description`, `price`, `quantity_available`, `unit`, `organic`, `image_url`, `created_at`, `updated_at`, `is_available`, `quantity`, `stock`, `product_image`, `status`) VALUES
(9, 4, 2, 'pineapple', 'tasty', 20.00, NULL, NULL, 0, NULL, '2025-06-01 11:29:27', '2025-06-02 13:18:25', 1, 0, 100, 'product_9_1748756667.jpeg', 'approved'),
(10, 4, 2, 'Apple', 'red apple', 5.00, NULL, NULL, 0, NULL, '2025-06-01 11:48:10', '2025-06-02 13:18:18', 1, 0, 48, 'product_10_1748757790.jpeg', 'approved'),
(11, 4, 1, 'potato', 'it is universal ', 50.00, NULL, NULL, 0, NULL, '2025-06-02 14:30:32', '2025-06-02 14:31:08', 1, 0, 40, 'product_11_1748853932.jpeg', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_verified_purchase` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`, `is_verified_purchase`) VALUES
(1, 1, 2, 5, 'Very fresh carrots, loved them!', '2025-05-15 09:23:09', 1),
(2, 2, 2, 4, 'Great apples, a bit small but tasty.', '2025-05-15 09:23:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `saved_items`
--

CREATE TABLE `saved_items` (
  `id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `role` enum('farmer','consumer','admin') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `city`, `state`, `zipcode`, `role`, `created_at`, `updated_at`, `profile_image`, `is_active`, `bio`) VALUES
(1, 'farmer_john', 'john@example.com', 'hashedpassword', 'John', 'Doe', '1234567890', '123 Green Rd', 'Farmville', 'CA', '90001', 'farmer', '2025-05-15 09:23:09', '2025-05-15 09:23:09', NULL, 1, NULL),
(2, 'consumer_amy', 'amy@example.com', 'hashedpassword', 'Amy', 'Smith', '9876543210', '456 City St', 'Metro', 'NY', '10001', 'consumer', '2025-05-15 09:23:09', '2025-05-15 09:23:09', NULL, 1, NULL),
(4, 'sonukjais', 'jaiswalsonukr7@gmail.com', '$2y$10$1Bo0vM.r3mYxKbPsx98Jp.zHTCgTIbbLpNtNJ/1Bm30N3FzivEruC', 'sonu', 'jaiswal', '9816313179', 'kalanki', NULL, NULL, NULL, 'farmer', '2025-05-15 11:57:09', '2025-06-01 11:26:51', 'farmer_4_1748180656.jpg', 1, 'i am the best farmer'),
(5, 'haripandey', 'sonukjaiswa113@gmail.com', '$2y$10$A8BZ1ek.GrvOAUHMtghJoekyNAVnNyGZE2wQMYALQ5UhkcifwyTsS', 'hari', 'pandey', NULL, NULL, NULL, NULL, NULL, 'consumer', '2025-05-15 12:00:56', '2025-05-18 20:33:38', NULL, 1, NULL),
(6, 'admin', 'admin@gmail.com', '$2y$10$k634scKSc3qX5GcJN/pnEeO3Efg6GeNaNErkM1yDdUE0p6MMOrEw.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', '2025-05-15 13:19:49', '2025-05-15 13:19:49', NULL, 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `consumer_id` (`consumer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `favorite_farmers`
--
ALTER TABLE `favorite_farmers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`consumer_id`,`seller_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `saved_items`
--
ALTER TABLE `saved_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_saved_item` (`consumer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `favorite_farmers`
--
ALTER TABLE `favorite_farmers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `saved_items`
--
ALTER TABLE `saved_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `favorite_farmers`
--
ALTER TABLE `favorite_farmers`
  ADD CONSTRAINT `favorite_farmers_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorite_farmers_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `saved_items`
--
ALTER TABLE `saved_items`
  ADD CONSTRAINT `saved_items_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
