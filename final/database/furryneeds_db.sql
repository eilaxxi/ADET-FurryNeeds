-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 08:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `furryneeds_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `street` varchar(150) NOT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`address_id`, `user_id`, `phone`, `street`, `barangay`, `city`, `province`, `zip_code`, `is_default`) VALUES
(1, 2, '+63 912 345 6789', '123 Main Street', 'Barangay 1', 'Manila', 'Metro Manila', '1000', 1),
(2, 3, '+63 92741416455', '123', 'brgy 1', 'tabaco', 'albay', '4511', 0);

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `is_active`, `date_created`) VALUES
(1, 3, 1, '2026-05-17 13:45:03'),
(2, 2, 1, '2026-05-17 15:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `quantity`, `unit_price`, `discount_amount`, `subtotal`) VALUES
(3, 1, 3, 1, 89.00, 0.00, 89.00),
(4, 1, 2, 1, 199.00, 0.00, 199.00),
(5, 1, 4, 1, 149.00, 0.00, 149.00),
(6, 1, 1, 4, 899.00, 89.90, 3236.40);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Dog Food', 'Dry food, wet food, and treats for dogs'),
(2, 'Cat Food', 'Dry food, wet food, and treats for cats'),
(3, 'Accessories', 'Collars, toys, bowls, beds, and pet accessories'),
(4, 'Grooming', 'Shampoo, brushes, and grooming supplies'),
(5, 'Health Care', 'Vitamins, supplements, and pet care items');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `inventory_transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Restock','Sale','Adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`inventory_transaction_id`, `product_id`, `supplier_id`, `transaction_type`, `quantity`, `notes`, `transaction_date`) VALUES
(1, 1, 1, 'Restock', 50, 'Initial stock from PetSupply PH', '2026-05-17 15:28:21'),
(2, 2, 1, 'Restock', 60, 'Initial stock from PetSupply PH', '2026-05-17 15:28:21'),
(3, 3, 2, 'Restock', 80, 'Initial stock from Furry Goods Distributor', '2026-05-17 15:28:21'),
(4, 4, 2, 'Restock', 30, 'Initial stock from Furry Goods Distributor', '2026-05-17 15:28:21'),
(5, 5, 1, 'Restock', 25, 'Initial stock from PetSupply PH', '2026-05-17 15:28:21'),
(6, 1, 1, 'Restock', 50, 'Initial stock - PetSupply PH', '2026-05-17 15:37:35'),
(7, 2, 1, 'Restock', 60, 'Initial stock - PetSupply PH', '2026-05-17 15:37:35'),
(8, 3, 2, 'Restock', 80, 'Initial stock - Furry Goods', '2026-05-17 15:37:35'),
(9, 4, 2, 'Restock', 30, 'Initial stock - Furry Goods', '2026-05-17 15:37:35'),
(10, 5, 1, 'Restock', 25, 'Initial stock - PetSupply PH', '2026-05-17 15:37:35'),
(11, 1, NULL, 'Sale', 1, 'Order #1', '2026-05-17 15:37:35'),
(12, 3, NULL, 'Sale', 2, 'Order #2', '2026-05-17 15:37:35'),
(13, 5, NULL, 'Sale', 1, 'Order #2', '2026-05-17 15:37:35'),
(14, 4, NULL, 'Sale', 1, 'Order #3', '2026-05-17 15:37:35'),
(15, 2, NULL, 'Sale', 1, 'Order #4', '2026-05-17 15:37:35'),
(16, 4, NULL, 'Sale', 1, 'Order #4', '2026-05-17 15:37:35'),
(17, 2, NULL, 'Sale', 1, 'Order #5', '2026-05-17 15:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `online_payment_details`
--

CREATE TABLE `online_payment_details` (
  `online_detail_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `status` enum('Pending','Verified','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `online_payment_details`
--

INSERT INTO `online_payment_details` (`online_detail_id`, `payment_id`, `reference_number`, `account_name`, `mobile_number`, `status`) VALUES
(1, 1, 'GCX-20260501-001', 'Sarah Martinez', '+63 912 345 6789', 'Verified'),
(2, 3, 'GCX-20260510-003', 'Sarah Martinez', '+63 912 345 6789', 'Pending'),
(3, 5, 'GCX-20260512-005', 'Sarah Martinez', '+63 912 345 6789', 'Rejected');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `order_status` enum('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `address_id`, `order_date`, `total_amount`, `delivery_fee`, `discount_amount`, `final_amount`, `order_status`) VALUES
(1, 2, 1, '2026-05-17 15:37:35', 899.00, 0.00, 0.00, 899.00, 'Delivered'),
(2, 2, 1, '2026-05-17 15:37:35', 288.00, 99.00, 0.00, 387.00, 'Shipped'),
(3, 2, 1, '2026-05-17 15:37:35', 149.00, 99.00, 50.00, 198.00, 'Processing'),
(4, 2, 1, '2026-05-17 15:37:35', 338.00, 0.00, 0.00, 338.00, 'Pending'),
(5, 2, 1, '2026-05-17 15:37:35', 199.00, 99.00, 0.00, 298.00, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 1, 899.00, 899.00),
(2, 2, 3, 2, 89.00, 178.00),
(3, 2, 5, 1, 249.00, 249.00),
(4, 3, 4, 1, 149.00, 149.00),
(5, 4, 2, 1, 199.00, 199.00),
(6, 4, 4, 1, 149.00, 149.00),
(7, 5, 2, 1, 199.00, 199.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('Unpaid','Pending','Paid','Failed','Refunded') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_method_id`, `payment_amount`, `payment_date`, `payment_status`) VALUES
(1, 1, 2, 899.00, '2026-05-17 15:37:35', 'Paid'),
(2, 2, 1, 387.00, '2026-05-17 15:37:35', 'Unpaid'),
(3, 3, 2, 198.00, '2026-05-17 15:37:35', 'Pending'),
(4, 4, 1, 338.00, '2026-05-17 15:37:35', 'Unpaid'),
(5, 5, 2, 298.00, '2026-05-17 15:37:35', 'Refunded');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `payment_method_id` int(11) NOT NULL,
  `method_type` enum('Cash on Delivery','GCash','Maya','Bank Transfer','Card') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`payment_method_id`, `method_type`) VALUES
(1, 'Cash on Delivery'),
(2, 'GCash'),
(3, 'Maya'),
(4, 'Bank Transfer'),
(5, 'Card');

-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `promo_id` int(11) DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_level` int(11) NOT NULL DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `promo_id`, `product_name`, `description`, `price`, `image`, `sku`, `ingredients`, `stock_quantity`, `low_stock_level`, `is_active`, `date_created`) VALUES
(1, 1, 1, 'Premium Grain-Free Dog Food', 'High-quality dog food for adult dogs.', 899.00, 'photo/organic.jpg', 'DGF-001', 'Chicken, sweet potato, vitamins', 35, 10, 1, '2026-05-17 13:30:47'),
(2, 1, NULL, 'Healthy Puppy Bites', 'Small bite-size treats for puppies.', 199.00, 'photo/bacon.png', 'DGT-002', 'Chicken, oats, carrots', 50, 10, 1, '2026-05-17 13:30:47'),
(3, 2, NULL, 'Tuna Cat Food', 'Wet cat food with tuna flavor.', 89.00, 'photo/tuna.jpg\r\n', 'CTF-001', 'Tuna, broth, vitamins', 80, 15, 1, '2026-05-17 13:30:47'),
(4, 3, NULL, 'Adjustable Pet Collar', 'Comfortable adjustable collar for pets.', 149.00, 'photo/harness.png', 'ACC-001', NULL, 25, 5, 1, '2026-05-17 13:30:47'),
(5, 4, 2, 'Gentle Pet Shampoo', 'Mild shampoo for dogs and cats.', 249.00, 'photo/shampoo.jpg', 'GRM-001', 'Aloe vera, mild cleanser', 18, 8, 1, '2026-05-17 13:30:47');

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `promo_id` int(11) NOT NULL,
  `promo_name` varchar(100) NOT NULL,
  `promo_type` enum('Percentage','Fixed Amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`promo_id`, `promo_name`, `promo_type`, `discount_value`, `start_date`, `end_date`, `is_active`) VALUES
(1, 'Summer Pet Sale', 'Percentage', 10.00, '2026-05-01', '2026-05-31', 1),
(2, 'New Customer Discount', 'Fixed Amount', 50.00, '2026-01-01', '2026-12-31', 1);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('Currency', 'PHP', '2026-05-18 03:49:12'),
('DefaultDeliveryFee', '99', '2026-05-18 03:49:12'),
('LowStockThreshold', '10', '2026-05-18 03:49:12'),
('StoreAddress', 'Philippines', '2026-05-18 03:49:12'),
('StoreEmail', 'support@furryneeds.com', '2026-05-18 03:49:12'),
('StoreName', 'FurryNeeds', '2026-05-18 03:49:12'),
('StorePhone', '+63 900 000 0000', '2026-05-18 03:49:12');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `phone`, `email`) VALUES
(1, 'PetSupply PH', 'Juan Dela Cruz', '+63 917 111 2222', 'orders@petsupplyph.com'),
(2, 'Furry Goods Distributor', 'Maria Santos', '+63 918 333 4444', 'sales@furrygoods.com');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_num` varchar(20) DEFAULT NULL,
  `role_type` enum('Customer','Admin') NOT NULL DEFAULT 'Customer',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `phone_num`, `role_type`, `date_created`) VALUES
(1, 'Admin', 'User', 'admin@furryneeds.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+63 900 000 0000', 'Admin', '2026-05-17 13:30:47'),
(2, 'Sarah', 'Martinez', 'sarah.martinez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+63 912 345 6789', 'Customer', '2026-05-17 13:30:47'),
(3, 'jolie', 'merl', 'jole@gmail.com', '$2y$10$ERF9wtJV9ygITEBCVEKnQeT9QnH8W/rLlsldLZNi5lhp1dD8SvIgO', '+63 9274146455', 'Customer', '2026-05-17 13:43:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `fk_addresses_users` (`user_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_carts_users` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `fk_cart_items_carts` (`cart_id`),
  ADD KEY `fk_cart_items_products` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`inventory_transaction_id`),
  ADD KEY `fk_inventory_products` (`product_id`),
  ADD KEY `fk_inventory_suppliers` (`supplier_id`);

--
-- Indexes for table `online_payment_details`
--
ALTER TABLE `online_payment_details`
  ADD PRIMARY KEY (`online_detail_id`),
  ADD KEY `fk_online_details_payments` (`payment_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_users` (`user_id`),
  ADD KEY `fk_orders_addresses` (`address_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_order_items_orders` (`order_id`),
  ADD KEY `fk_order_items_products` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payments_orders` (`order_id`),
  ADD KEY `fk_payments_methods` (`payment_method_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`payment_method_id`),
  ADD UNIQUE KEY `method_type` (`method_type`);

--
-- Indexes for table `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_products_categories` (`category_id`),
  ADD KEY `fk_products_promos` (`promo_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`promo_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

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
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `inventory_transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `online_payment_details`
--
ALTER TABLE `online_payment_details`
  MODIFY `online_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `payment_method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `fk_addresses_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_carts` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `fk_inventory_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inventory_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `online_payment_details`
--
ALTER TABLE `online_payment_details`
  ADD CONSTRAINT `fk_online_details_payments` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_addresses` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_methods` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_promos` FOREIGN KEY (`promo_id`) REFERENCES `promos` (`promo_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
